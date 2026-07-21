<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0EffectPorts {
	private $ports = array();
	private $claims = array();

	public function register( $family, callable $adapter ) {
		$family = sanitize_key( (string) $family );
		$allowed = array( 'ai', 'http', 'sql', 'filesystem', 'wordpress', 'woocommerce' );
		if ( ! in_array( $family, $allowed, true ) || isset( $this->ports[ $family ] ) ) {
			return false;
		}
		$this->ports[ $family ] = $adapter;
		return true;
	}

	public function denyDirect( $family ) {
		return WaicWorkflowPhase0Result::blocked( 'WF-EFFECT-001', 'workflow_direct_effect_disabled', 'disable_workflow' );
	}

	public function execute( $family, array $command ) {
		$family = sanitize_key( (string) $family );
		if ( empty( $command['committed'] ) || empty( $command['authority_valid'] ) || empty( $command['idempotency_key'] ) || ! isset( $this->ports[ $family ] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-EFFECT-001', 'workflow_effect_authority_invalid', 'disable_workflow' );
		}
		$key = $family . ':' . (string) $command['idempotency_key'];
		if ( isset( $this->claims[ $key ] ) ) {
			return $this->claims[ $key ];
		}
		$result = call_user_func( $this->ports[ $family ], $command );
		if ( $result instanceof WaicWorkflowPhase0Result && 0 !== strpos( $result->getCode(), 'WF-P0-' ) ) {
			return $result;
		}
		$success = WaicWorkflowPhase0Result::success( 'WF-P0-EFFECT-000', 'workflow_effect_applied' );
		$this->claims[ $key ] = $success;
		return $success;
	}
}
