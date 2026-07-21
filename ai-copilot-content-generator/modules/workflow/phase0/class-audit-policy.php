<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fail-closed capacity guard for mandatory Workflow audit/outbox events.
 */
final class WaicWorkflowPhase0AuditPolicy {
	const MAX_BACKLOG = 10000;
	const MAX_RATE_PER_MINUTE = 1200;
	const MAX_RETENTION_DAYS = 90;
	const MIN_FREE_BYTES = 67108864;

	public function authorize( array $metrics ) {
		$required = array( 'backlog', 'rate_per_minute', 'retention_days', 'free_bytes', 'event_code' );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $metrics ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_capacity_unknown', 'suspend_workflow' );
			}
		}
		$event_code = strtolower( (string) $metrics['event_code'] );
		if ( ! preg_match( '/^workflow\.[a-z0-9._-]{3,72}$/', $event_code ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_event_invalid', 'suspend_workflow' );
		}
		if ( (int) $metrics['backlog'] < 0 || (int) $metrics['backlog'] > self::MAX_BACKLOG ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_backlog_bounded', 'drain_workflow_outbox' );
		}
		if ( (int) $metrics['rate_per_minute'] < 0 || (int) $metrics['rate_per_minute'] > self::MAX_RATE_PER_MINUTE ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_rate_bounded', 'suspend_workflow' );
		}
		if ( (int) $metrics['retention_days'] < 1 || (int) $metrics['retention_days'] > self::MAX_RETENTION_DAYS ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_retention_bounded', 'review_retention' );
		}
		if ( (int) $metrics['free_bytes'] < self::MIN_FREE_BYTES ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-OUTBOX-001', 'workflow_audit_storage_bounded', 'restore_audit_capacity' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-OUTBOX-000', 'workflow_audit_capacity_available' );
	}
}
