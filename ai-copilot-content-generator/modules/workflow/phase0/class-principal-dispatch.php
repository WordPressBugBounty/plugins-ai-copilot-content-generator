<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure, default-off planner for named URL and hook principals.
 * It returns run intents only; it never loads a descriptor or performs an effect.
 */
final class WaicWorkflowPhase0PrincipalDispatch {
	private $authority;
	private $claims = array();

	public function __construct( WaicWorkflowPhase0Authority $authority ) {
		$this->authority = $authority;
	}

	public function planUrl( $url, array $workflows, array $context ) {
		$authorized = $this->authority->authorizeSystemPrincipal( 'url', $context );
		if ( 'WF-P0-URL-000' !== $authorized->getCode() ) {
			return $authorized;
		}
		$url = is_string( $url ) ? $url : '';
		$matches = array();
		$failures = 0;
		foreach ( $workflows as $workflow ) {
			$id = isset( $workflow['workflow_id'] ) ? (int) $workflow['workflow_id'] : 0;
			$pattern = isset( $workflow['pattern'] ) ? (string) $workflow['pattern'] : '';
			if ( $id <= 0 || '' === $pattern || strlen( $pattern ) > 512 ) {
				$failures++;
				continue;
			}
			$matched = @preg_match( $pattern, $url );
			if ( false === $matched ) {
				$failures++;
				continue;
			}
			if ( 1 === $matched ) {
				$matches[ $id ] = $id;
			}
		}
		return array(
			'result'       => $failures > 0
				? WaicWorkflowPhase0Result::unavailable( 'WF-P0-URL-001', 'review_url_workflow' )
				: WaicWorkflowPhase0Result::success( 'WF-P0-URL-000', 'workflow_url_intents_planned' ),
			'workflow_ids' => array_values( $matches ),
			'failure_count'=> $failures,
		);
	}

	public function planHook( $hook, $event_id, array $workflow_ids, array $context ) {
		$hook = sanitize_key( (string) $hook );
		$allowed = array(
			'woocommerce_order_status_changed',
			'woocommerce_new_order',
			'save_post',
		);
		if ( ! in_array( $hook, $allowed, true ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-005', 'workflow_hook_not_allowlisted', 'disable_workflow' );
		}
		$authorized = $this->authority->authorizeSystemPrincipal( 'hook', $context );
		if ( 'WF-P0-HOOK-000' !== $authorized->getCode() ) {
			return $authorized;
		}
		$event_id = (string) $event_id;
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,96}$/', $event_id ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-005', 'workflow_hook_provenance_invalid', 'disable_workflow' );
		}
		$planned = array();
		foreach ( array_unique( array_map( 'intval', $workflow_ids ) ) as $workflow_id ) {
			if ( $workflow_id <= 0 ) {
				continue;
			}
			$key = $hook . ':' . $event_id . ':' . $workflow_id;
			if ( isset( $this->claims[ $key ] ) ) {
				continue;
			}
			$this->claims[ $key ] = true;
			$planned[] = $workflow_id;
		}
		return array(
			'result'       => WaicWorkflowPhase0Result::success( 'WF-P0-HOOK-000', 'workflow_hook_intents_planned' ),
			'workflow_ids' => $planned,
		);
	}
}
