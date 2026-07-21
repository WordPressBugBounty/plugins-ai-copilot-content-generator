<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Authority {
	private static $read_operations = array( 'workflow.audit.read', 'workflow.export' );

	public function authorizeController( $operation_id, array $context ) {
		$operation = WaicWorkflowPhase0OperationRegistry::get( $operation_id );
		if ( ! $operation || 'cutover' !== $operation['disposition'] ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-007', 'workflow_operation_unknown', 'disable_workflow' );
		}
		$method = strtoupper( isset( $context['method'] ) ? (string) $context['method'] : '' );
		if ( ! in_array( $operation_id, self::$read_operations, true ) && 'POST' !== $method ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-001', 'workflow_request_method_invalid', 'retry_workflow' );
		}
		if ( empty( $context['csrf_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-001', 'workflow_request_csrf_invalid', 'retry_workflow' );
		}
		if ( ! empty( $operation['capability'] ) && empty( $context['capability_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-002', 'workflow_capability_missing', 'review_workflow' );
		}
		if ( empty( $context['site_valid'] ) || empty( $context['object_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-003', 'workflow_scope_invalid', 'review_workflow' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-CTRL-000', 'workflow_command_authorized' );
	}

	public function authorizeSystemPrincipal( $channel, array $context ) {
		$channel = sanitize_key( (string) $channel );
		$expected = array(
			'url'  => 'aiwu-site-url',
			'hook' => 'wp-hook:',
			'cron' => 'aiwu-site-cron',
		);
		if ( ! isset( $expected[ $channel ] ) || empty( $context['principal'] ) || empty( $context['site_valid'] ) || empty( $context['grant_valid'] ) || empty( $context['provenance_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-005', 'workflow_system_principal_invalid', 'disable_workflow' );
		}
		$principal = (string) $context['principal'];
		if ( 'hook' === $channel ? 0 !== strpos( $principal, $expected[ $channel ] ) : ! hash_equals( $expected[ $channel ], $principal ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-005', 'workflow_system_principal_invalid', 'disable_workflow' );
		}
		$code = 'url' === $channel ? 'WF-P0-URL-000' : ( 'hook' === $channel ? 'WF-P0-HOOK-000' : 'WF-P0-RUN-000' );
		return WaicWorkflowPhase0Result::success( $code, 'workflow_system_principal_authorized' );
	}

	public function denyCredentialCallback( array $context ) {
		if ( empty( $context['state_valid'] ) || empty( $context['pkce_valid'] ) || ! empty( $context['replayed'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-004', 'workflow_callback_state_invalid', 'reenter_credential' );
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-CRED-003', 'workflow_callback_disabled', 'reenter_credential' );
	}

	public function denyImport( array $context ) {
		if ( ! empty( $context['public'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-IMPORT-001', 'workflow_import_public_disabled', 'review_workflow' );
		}
		$entries = isset( $context['entries'] ) ? (array) $context['entries'] : array();
		$seen = array();
		foreach ( $entries as $entry ) {
			$entry = wp_normalize_path( (string) $entry );
			$key = strtolower( $entry );
			if ( '' === $entry || false !== strpos( $entry, '..' ) || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $entry ) || isset( $seen[ $key ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-IMPORT-002', 'workflow_import_archive_invalid', 'review_workflow' );
			}
			$seen[ $key ] = true;
		}
		if ( empty( $context['stamp_valid'] ) || empty( $context['confirmation_valid'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-IMPORT-003', 'workflow_import_confirmation_stale', 'review_workflow' );
		}
		return WaicWorkflowPhase0Result::unavailable( 'WF-OUTBOX-002', 'recover_import' );
	}

	public function denyLegacy( array $context ) {
		if ( ! empty( $context['newer_schema'] ) || ! empty( $context['restored_authority'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_schema_code_mismatch', 'update_plugin' );
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-LEGACY-001', 'workflow_legacy_path_disabled', 'review_workflow' );
	}

	public function scanRedacted( $value ) {
		$encoded = is_string( $value ) ? $value : wp_json_encode( $value );
		$patterns = array( '/AIWU[_-]SECRET/i', '/BEGIN[ ]+PRIVATE[ ]+KEY/i', '/Bearer\s+[A-Za-z0-9._-]{12,}/i', '/sk-[A-Za-z0-9]{12,}/' );
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, (string) $encoded ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-SEC-001', 'workflow_sensitive_value_detected', 'disable_workflow' );
			}
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-SEC-000', 'workflow_redaction_verified' );
	}
}
