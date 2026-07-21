<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Outbox {
	public function commit( array $context, callable $mutation, $event_code, array $redacted_payload ) {
		global $wpdb;
		$required = array( 'site_id', 'workflow_id', 'operation_id', 'idempotency_key', 'payload_digest' );
		foreach ( $required as $key ) {
			if ( empty( $context[ $key ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_outbox_context_invalid', 'review_workflow' );
			}
		}
		$operation = WaicWorkflowPhase0OperationRegistry::get( $context['operation_id'] );
		if ( ! $operation || 'cutover' !== $operation['disposition'] ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_operation_not_mutable', 'review_workflow' );
		}
		$idempotency_key = (string) $context['idempotency_key'];
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,64}$/', $idempotency_key ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_idempotency_invalid', 'review_workflow' );
		}
		$payload_digest = strtolower( (string) $context['payload_digest'] );
		if ( ! preg_match( '/^sha256:[a-f0-9]{64}$/', $payload_digest ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_payload_digest_invalid', 'review_workflow' );
		}
		$event_code = strtolower( (string) $event_code );
		if ( ! preg_match( '/^workflow\.[a-z0-9._-]{3,72}$/', $event_code ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_event_invalid', 'review_workflow' );
		}
		$journal = WaicWorkflowPhase0Storage::table( 'workflow_journal' );
		$outbox  = WaicWorkflowPhase0Storage::table( 'workflow_outbox' );
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$journal} WHERE site_id=%d AND operation_id=%s AND idempotency_key=%s LIMIT 1",
				(int) $context['site_id'],
				$operation['operation_id'],
				$idempotency_key
			)
		);
		if ( null !== $existing ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_idempotency_conflict', 'review_workflow' );
		}
		$now     = gmdate( 'Y-m-d H:i:s' );
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-OUTBOX-002', 'recover_workflow_outbox' );
		}
		try {
			$inserted = $wpdb->insert(
				$journal,
				array(
					'site_id'        => (int) $context['site_id'],
					'workflow_id'    => (int) $context['workflow_id'],
					'operation_id'   => $operation['operation_id'],
					'idempotency_key'=> $idempotency_key,
					'payload_digest' => $payload_digest,
					'state'          => 'prepared',
					'created_at'     => $now,
					'updated_at'     => $now,
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			if ( false === $inserted ) {
				$wpdb->query( 'ROLLBACK' );
				return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_idempotency_conflict', 'review_workflow' );
			}
			$journal_id = (int) $wpdb->insert_id;
			$mutation_result = call_user_func( $mutation, $journal_id );
			if ( $mutation_result instanceof WaicWorkflowPhase0Result && 0 === strpos( $mutation_result->getCode(), 'WF-' ) && 0 !== strpos( $mutation_result->getCode(), 'WF-P0-' ) ) {
				$wpdb->query( 'ROLLBACK' );
				return $mutation_result;
			}
			$outbox_inserted = $wpdb->insert(
				$outbox,
				array(
					'journal_id'  => $journal_id,
					'event_code'  => $event_code,
					'payload'     => wp_json_encode( $redacted_payload ),
					'state'       => 'pending',
					'attempts'    => 0,
					'available_at'=> $now,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
			if ( false === $outbox_inserted ) {
				throw new RuntimeException( 'Workflow outbox write failed.' );
			}
			$journal_updated = $wpdb->update( $journal, array( 'state' => 'committed', 'updated_at' => $now ), array( 'id' => $journal_id ), array( '%s', '%s' ), array( '%d' ) );
			if ( false === $journal_updated || ! empty( $wpdb->last_error ) ) {
				throw new RuntimeException( 'Workflow transaction write failed.' );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				return WaicWorkflowPhase0Result::unavailable( 'WF-OUTBOX-002', 'recover_workflow_outbox' );
			}
			return array( 'journal_id' => $journal_id, 'mutation_result' => $mutation_result );
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_transaction_failed', 'retry_workflow', array( 'retry' ) );
		}
	}

	public function recover( $stage ) {
		$stage = sanitize_key( (string) $stage );
		if ( in_array( $stage, array( 'post_commit', 'delivery_interrupted', 'duplicate_delivery' ), true ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-OUTBOX-002', 'recover_workflow_outbox' );
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_outbox_recovery_invalid', 'suspend_workflow' );
	}
}
