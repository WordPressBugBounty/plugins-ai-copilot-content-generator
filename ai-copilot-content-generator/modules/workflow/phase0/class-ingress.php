<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Ingress {
	private $clock;
	private $claim;

	public function __construct( $clock = null, $claim = null ) {
		$this->clock = is_callable( $clock ) ? $clock : 'time';
		$this->claim = is_callable( $claim ) ? $claim : array( $this, 'claimTransient' );
	}

	public function verify( array $request, $secret ) {
		$method = strtoupper( isset( $request['method'] ) ? (string) $request['method'] : '' );
		$type = strtolower( isset( $request['content_type'] ) ? trim( (string) $request['content_type'] ) : '' );
		$body = isset( $request['body'] ) ? (string) $request['body'] : '';
		if ( 'POST' !== $method || 'application/json' !== $type || '' === $body || strlen( $body ) > 1048576 || empty( $request['rate_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-INGRESS-003', 'workflow_ingress_bounds_invalid', 'retry_workflow' );
		}
		if ( ! is_string( $secret ) || strlen( $secret ) < 32 || empty( $request['signature'] ) || empty( $request['timestamp'] ) || empty( $request['idempotency_key'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-INGRESS-001', 'workflow_ingress_signature_invalid', 'disable_workflow' );
		}
		$timestamp = (int) $request['timestamp'];
		$now = (int) call_user_func( $this->clock );
		if ( abs( $now - $timestamp ) > 300 ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-INGRESS-002', 'workflow_ingress_expired', 'retry_workflow' );
		}
		$preimage = 'aiwu-webhook.v1\n' . $timestamp . '\n' . $request['idempotency_key'] . '\n' . hash( 'sha256', $body );
		$expected = hash_hmac( 'sha256', $preimage, $secret );
		$provided = strtolower( preg_replace( '/^sha256=/', '', (string) $request['signature'] ) );
		if ( 64 !== strlen( $provided ) || ! hash_equals( $expected, $provided ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-INGRESS-001', 'workflow_ingress_signature_invalid', 'disable_workflow' );
		}
		$claim_key = hash( 'sha256', $request['idempotency_key'] . '|' . $provided );
		if ( ! call_user_func( $this->claim, $claim_key, 600 ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-INGRESS-002', 'workflow_ingress_replayed', 'review_workflow' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-INGRESS-000', 'workflow_ingress_authorized' );
	}

	public static function signature( $body, $timestamp, $idempotency_key, $secret ) {
		$preimage = 'aiwu-webhook.v1\n' . (int) $timestamp . '\n' . $idempotency_key . '\n' . hash( 'sha256', (string) $body );
		return 'sha256=' . hash_hmac( 'sha256', $preimage, $secret );
	}

	public function claimTransient( $key, $ttl ) {
		$key = 'waic_wf_ingress_' . substr( $key, 0, 40 );
		if ( get_transient( $key ) ) {
			return false;
		}
		return set_transient( $key, 1, max( 60, min( 3600, (int) $ttl ) ) );
	}
}
