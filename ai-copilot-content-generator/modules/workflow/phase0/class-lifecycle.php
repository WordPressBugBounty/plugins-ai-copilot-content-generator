<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Lifecycle {
	const REVISION = 'workflow_lifecycle.v1';
	const STAMP_TTL = 900;
	const CONFIRMATION_TTL = 300;
	const RUNTIME_TTL = 86400;

	private static $transitions = array(
		'draft'     => array( 'validated' ),
		'validated' => array( 'confirmed', 'draft' ),
		'confirmed' => array( 'active', 'draft' ),
		'active'    => array( 'suspended', 'revoked', 'draft' ),
		'suspended' => array( 'validated', 'revoked', 'draft' ),
		'revoked'   => array( 'draft' ),
	);

	public function transition( array $state, $target, $now ) {
		$current = isset( $state['state'] ) ? sanitize_key( $state['state'] ) : '';
		$target  = sanitize_key( (string) $target );
		if ( ! isset( self::$transitions[ $current ] ) || ! in_array( $target, self::$transitions[ $current ], true ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-LIFE-001', 'workflow_lifecycle_transition_invalid', 'revalidate_workflow', array( 'revalidate' ) );
		}
		if ( ! $this->hasDigests( $state ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-LIFE-001', 'workflow_lifecycle_digest_missing', 'revalidate_workflow', array( 'revalidate' ) );
		}

		$next              = $state;
		$next['state']     = $target;
		$next['updated_at']= (int) $now;
		if ( 'validated' === $target ) {
			$next['stamp_expires_at'] = (int) $now + self::STAMP_TTL;
			unset( $next['confirmation_expires_at'], $next['grant_expires_at'] );
		} elseif ( 'confirmed' === $target ) {
			if ( empty( $state['stamp_expires_at'] ) || (int) $state['stamp_expires_at'] < (int) $now ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-LIFE-001', 'workflow_validation_stamp_stale', 'revalidate_workflow', array( 'revalidate' ) );
			}
			$next['confirmation_expires_at'] = (int) $now + self::CONFIRMATION_TTL;
		} elseif ( 'active' === $target ) {
			if ( empty( $state['confirmation_expires_at'] ) || (int) $state['confirmation_expires_at'] < (int) $now ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-LIFE-001', 'workflow_confirmation_stale', 'revalidate_workflow', array( 'revalidate' ) );
			}
			$next['grant_expires_at'] = (int) $now + self::RUNTIME_TTL;
			unset( $next['confirmation_expires_at'] );
		} elseif ( in_array( $target, array( 'draft', 'revoked' ), true ) ) {
			unset( $next['stamp_expires_at'], $next['confirmation_expires_at'], $next['grant_expires_at'] );
		}
		return $next;
	}

	public function authorizeEffect( array $state, array $current_digests, $now ) {
		if ( 'active' !== ( isset( $state['state'] ) ? $state['state'] : '' ) || empty( $state['grant_expires_at'] ) || (int) $state['grant_expires_at'] < (int) $now ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-RUN-002', 'workflow_runtime_grant_invalid', 'revalidate_workflow', array( 'revalidate' ) );
		}
		foreach ( array( 'workflow_digest', 'policy_digest', 'descriptor_digest', 'implementation_fingerprint' ) as $key ) {
			if ( empty( $state[ $key ] ) || empty( $current_digests[ $key ] ) || ! hash_equals( (string) $state[ $key ], (string) $current_digests[ $key ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-RUN-001', 'workflow_runtime_digest_stale', 'revalidate_workflow', array( 'revalidate' ) );
			}
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-RUN-000', 'workflow_runtime_authorized' );
	}

	public function invalidateOnEdit( array $state, array $new_digests, $now ) {
		$state['state'] = 'draft';
		foreach ( array( 'workflow_digest', 'policy_digest', 'descriptor_digest', 'implementation_fingerprint' ) as $key ) {
			$state[ $key ] = isset( $new_digests[ $key ] ) ? (string) $new_digests[ $key ] : '';
		}
		$state['updated_at'] = (int) $now;
		unset( $state['stamp_expires_at'], $state['confirmation_expires_at'], $state['grant_expires_at'] );
		return $state;
	}

	private function hasDigests( array $state ) {
		foreach ( array( 'workflow_digest', 'policy_digest', 'descriptor_digest', 'implementation_fingerprint' ) as $key ) {
			if ( empty( $state[ $key ] ) || ! preg_match( '/^sha256:[a-f0-9]{64}$/', (string) $state[ $key ] ) ) {
				return false;
			}
		}
		return true;
	}
}
