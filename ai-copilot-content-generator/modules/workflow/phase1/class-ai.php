<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Ai {
	public function compile( array $input ) {
		$operation_id = 'workflow.ai.plan.compile';
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'ai' ) || ! WaicWorkflowPhase1Config::hasAcceptedAiSnapshot() ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_snapshot_unavailable', __( 'No accepted immutable AI snapshot is enabled.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		if ( array_diff( array_keys( $input ), array( 'intent','answers','snapshot_id' ) ) || ! isset( $input['intent'], $input['answers'], $input['snapshot_id'] ) || ! is_string( $input['intent'] ) || ! is_array( $input['answers'] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_plan_invalid', __( 'The AI planning request is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		if ( strlen( $input['intent'] ) < 1 || strlen( $input['intent'] ) > 8192 || WaicWorkflowPhase1Validator::containsSecret( $input ) || $this->containsSensitiveData( WaicWorkflowPhase1Canonicalizer::encode( $input ) ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_sensitive_input_blocked', __( 'Sensitive input cannot be sent for workflow planning.', 'ai-copilot-content-generator' ) );
		}
		$snapshot = WaicWorkflowPhase1Config::aiSnapshot();
		if ( ! hash_equals( (string) $snapshot['snapshot_id'], (string) $input['snapshot_id'] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_snapshot_drift', __( 'The selected AI snapshot is stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$reservation = $this->reserveBudget( isset( $snapshot['max_cost_micro_usd'] ) ? (int) $snapshot['max_cost_micro_usd'] : 1000 );
		if ( ! is_array( $reservation ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_budget_exceeded', __( 'The configured AI budget is unavailable or exhausted.', 'ai-copilot-content-generator' ), array(), array(), 429 );
		}
		$plan = $this->generatePlan( $input, $snapshot );
		if ( $plan instanceof WaicWorkflowPhase1Result ) {
			$this->reconcileBudget( $reservation, $reservation['amount'] );
			return $plan;
		}
		if ( ! $this->planShapeValid( $plan ) ) {
			$plan = $this->repairPlan( $plan, $input, $snapshot );
			if ( $plan instanceof WaicWorkflowPhase1Result || ! $this->planShapeValid( $plan ) ) {
				$this->reconcileBudget( $reservation, $reservation['amount'] );
				return $plan instanceof WaicWorkflowPhase1Result ? $plan : WaicWorkflowPhase1Result::blocked( $operation_id, 'ai_plan_invalid', __( 'The generated workflow plan failed the closed compiler contract after one repair attempt.', 'ai-copilot-content-generator' ) );
			}
		}
		$workflow = $this->compilePlan( $plan, $input, $snapshot );
		$service = new WaicWorkflowPhase1PackService();
		$result = $service->preflight( 'standalone_json_body', WaicWorkflowPhase1Canonicalizer::encode( $workflow ), $operation_id, 'ai_generated', 'ai_plan_compiled' );
		$this->reconcileBudget( $reservation, isset( $plan['actual_cost_micro_usd'] ) ? (int) $plan['actual_cost_micro_usd'] : $reservation['amount'] );
		if ( 'awaiting_confirmation' !== $result->getState() ) {
			return $result;
		}
		$data = $result->toArray();
		$meta = $data['meta'];
		$meta['plan_digest'] = $meta['artifact_digest'];
		$meta['estimated_max_cost_micro_usd'] = $reservation['amount'];
		$meta['actual_cost_micro_usd'] = isset( $plan['actual_cost_micro_usd'] ) ? (int) $plan['actual_cost_micro_usd'] : $reservation['amount'];
		$meta['provider'] = isset( $snapshot['provider'] ) ? $snapshot['provider'] : 'test_stub';
		$meta['model_id'] = isset( $snapshot['model_id'] ) ? $snapshot['model_id'] : 'test-stub-1';
		return WaicWorkflowPhase1Result::waiting( $operation_id, 'awaiting_confirmation', 'ai_plan_compiled', __( 'The AI workflow plan was compiled and awaits draft confirmation.', 'ai-copilot-content-generator' ), array(), $meta );
	}

	public function saveDraft( array $input, $idempotency_key ) {
		if ( ! isset( $input['operation_handle'], $input['plan_digest'], $input['validation_stamp'], $input['confirmation'] ) || ! preg_match( '/^sha256:[a-f0-9]{64}$/', (string) $input['plan_digest'] ) ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.draft.save', 'invalid_request_envelope', __( 'The AI draft envelope is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		return ( new WaicWorkflowPhase1PackService() )->commitAiDraft( $input['operation_handle'], $input['validation_stamp'], $input['confirmation'], $idempotency_key, $input['plan_digest'] );
	}

	private function generatePlan( array $input, array $snapshot ) {
		if ( ! WaicWorkflowPhase1Config::isTestMode() || 'aiwu-test-stub-v1' !== $snapshot['snapshot_id'] ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.ai.plan.compile', 'ai_provider_unavailable', __( 'No release-approved provider adapter is installed.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		return array(
			'name' => __( 'AI workflow draft', 'ai-copilot-content-generator' ),
			'description' => __( 'Deterministic test-provider draft.', 'ai-copilot-content-generator' ),
			'nodes' => array(
				array( 'key' => 'trigger', 'type' => 'trigger', 'code' => 'sy_manual', 'position' => array( 'x' => 0, 'y' => 0 ), 'settings' => new stdClass() ),
				array( 'key' => 'finish', 'type' => 'logic', 'code' => 'un_stop', 'position' => array( 'x' => 280, 'y' => 0 ), 'settings' => new stdClass() ),
			),
			'edges' => array( array( 'source_key' => 'trigger', 'target_key' => 'finish', 'source_handle' => 'default', 'target_handle' => 'default' ) ),
			'actual_cost_micro_usd' => 100,
		);
	}

	private function repairPlan( $unused_plan, array $input, array $snapshot ) {
		if ( WaicWorkflowPhase1Config::isTestMode() && 'aiwu-test-stub-v1' === $snapshot['snapshot_id'] ) {
			return $this->generatePlan( $input, $snapshot );
		}
		return WaicWorkflowPhase1Result::blocked( 'workflow.ai.plan.compile', 'ai_plan_invalid', __( 'The provider did not return a valid plan after one repair attempt.', 'ai-copilot-content-generator' ) );
	}

	private function planShapeValid( $plan ) {
		if ( ! is_array( $plan ) || array_diff( array_keys( $plan ), array( 'name','description','nodes','edges','actual_cost_micro_usd' ) ) ) { return false; }
		foreach ( array( 'name','description','nodes','edges' ) as $key ) { if ( ! array_key_exists( $key, $plan ) ) { return false; } }
		if ( ! is_string( $plan['name'] ) || strlen( $plan['name'] ) > 200 || ! is_string( $plan['description'] ) || strlen( $plan['description'] ) > 4000 ) { return false; }
		if ( ! is_array( $plan['nodes'] ) || ! is_array( $plan['edges'] ) || count( $plan['nodes'] ) < 1 || count( $plan['nodes'] ) > 10 || count( $plan['edges'] ) > 20 ) { return false; }
		$triggers = 0;
		$keys = array();
		foreach ( $plan['nodes'] as $node ) {
			if ( ! is_array( $node ) || array_diff( array_keys( $node ), array( 'key','type','code','position','settings' ) ) || 5 !== count( $node ) || ! isset( $node['key'], $node['type'], $node['code'], $node['position'], $node['settings'] ) ) { return false; }
			if ( ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/', $node['key'] ) || isset( $keys[ $node['key'] ] ) || false === WaicWorkflowPhase1DescriptorRegistry::get( $node['type'], $node['code'] ) ) { return false; }
			$keys[ $node['key'] ] = true;
			if ( 'trigger' === $node['type'] ) { $triggers++; }
		}
		foreach ( $plan['edges'] as $edge ) {
			if ( ! is_array( $edge ) || array_diff( array_keys( $edge ), array( 'source_key','target_key','source_handle','target_handle' ) ) || 4 !== count( $edge ) || ! isset( $edge['source_key'], $edge['target_key'], $edge['source_handle'], $edge['target_handle'] ) || ! isset( $keys[ $edge['source_key'] ], $keys[ $edge['target_key'] ] ) ) { return false; }
		}
		return 1 === $triggers;
	}

	private function compilePlan( array $plan, array $input, array $snapshot ) {
		$seed = WaicWorkflowPhase1Canonicalizer::digest( array( 'input' => $input, 'snapshot_id' => $snapshot['snapshot_id'] ), 'ai-plan-seed', 'v1' );
		$node_ids = array();
		$nodes = array();
		foreach ( $plan['nodes'] as $node ) {
			$node_ids[ $node['key'] ] = 'n:' . $this->deterministicUuid( $seed . '|node|' . $node['key'] );
			$nodes[] = array( 'id' => $node_ids[ $node['key'] ], 'type' => $node['type'], 'code' => $node['code'], 'position' => $node['position'], 'settings' => $node['settings'] );
		}
		$edges = array();
		foreach ( $plan['edges'] as $index => $edge ) {
			$edges[] = array( 'id' => 'e:' . $this->deterministicUuid( $seed . '|edge|' . $index . '|' . $edge['source_key'] . '|' . $edge['target_key'] ), 'source' => $node_ids[ $edge['source_key'] ], 'target' => $node_ids[ $edge['target_key'] ], 'source_handle' => $edge['source_handle'], 'target_handle' => $edge['target_handle'] );
		}
		return array(
			'schema' => 'aiwu.workflow.v1',
			'id' => 'ai-draft.' . substr( hash( 'sha256', $seed ), 0, 16 ),
			'name' => $plan['name'],
			'description' => $plan['description'],
			'nodes' => $nodes,
			'edges' => $edges,
			'settings' => new stdClass(),
		);
	}

	private function deterministicUuid( $seed ) {
		$hex = substr( hash( 'sha256', $seed ), 0, 32 );
		$hex[12] = '4';
		$hex[16] = dechex( 8 + ( hexdec( $hex[16] ) % 4 ) );
		return substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-' . substr( $hex, 12, 4 ) . '-' . substr( $hex, 16, 4 ) . '-' . substr( $hex, 20, 12 );
	}

	private function containsSensitiveData( $text ) {
		return (bool) preg_match( '/(?:[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\+?[0-9][0-9 ()-]{8,}[0-9]|\b(?:password|passwd|secret|api[_ -]?key)\s*[:=])/i', $text );
	}

	private function reserveBudget( $amount ) {
		return WaicWorkflowPhase1Storage::reserveAiBudget( $amount );
	}

	private function reconcileBudget( array $reservation, $actual ) {
		return WaicWorkflowPhase1Storage::reconcileAiBudget( $reservation, $actual );
	}
}
