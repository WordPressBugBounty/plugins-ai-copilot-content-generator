<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Lease {
	public function acquire( $site_id, $workflow_id, $owner_id, $ttl_seconds = 60 ) {
		global $wpdb;
		$site_id    = (int) $site_id;
		$workflow_id= (int) $workflow_id;
		$owner_id   = sanitize_key( (string) $owner_id );
		$ttl_seconds= max( 5, min( 300, (int) $ttl_seconds ) );
		if ( $site_id <= 0 || $workflow_id <= 0 || '' === $owner_id ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-RUN-004', 'workflow_lease_context_invalid', 'retry_workflow' );
		}
		$table = WaicWorkflowPhase0Storage::table( 'workflow_leases' );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (site_id,workflow_id,fence,owner_id,expires_at,updated_at)
			 VALUES (%d,%d,1,%s,DATE_ADD(UTC_TIMESTAMP(), INTERVAL %d SECOND),UTC_TIMESTAMP())
			 ON DUPLICATE KEY UPDATE
			 fence=IF(expires_at < UTC_TIMESTAMP(),fence+1,fence),
			 owner_id=IF(expires_at < UTC_TIMESTAMP(),VALUES(owner_id),owner_id),
			 expires_at=IF(expires_at < UTC_TIMESTAMP(),VALUES(expires_at),expires_at),
			 updated_at=UTC_TIMESTAMP()",
			$site_id,
			$workflow_id,
			$owner_id,
			$ttl_seconds
		);
		if ( false === $wpdb->query( $sql ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-RUN-004', 'retry_workflow' );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT fence,owner_id,expires_at FROM {$table} WHERE site_id=%d AND workflow_id=%d", $site_id, $workflow_id ), ARRAY_A );
		if ( ! is_array( $row ) || ! hash_equals( $owner_id, (string) $row['owner_id'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-RUN-004', 'workflow_lease_held', 'retry_workflow', array( 'retry' ) );
		}
		return array( 'site_id' => $site_id, 'workflow_id' => $workflow_id, 'owner_id' => $owner_id, 'fence' => (int) $row['fence'], 'expires_at' => $row['expires_at'] );
	}

	public function validate( array $token ) {
		global $wpdb;
		foreach ( array( 'site_id', 'workflow_id', 'owner_id', 'fence' ) as $key ) {
			if ( empty( $token[ $key ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-RUN-004', 'workflow_lease_token_invalid', 'retry_workflow' );
			}
		}
		$table = WaicWorkflowPhase0Storage::table( 'workflow_leases' );
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE site_id=%d AND workflow_id=%d AND owner_id=%s AND fence=%d AND expires_at >= UTC_TIMESTAMP()",
				(int) $token['site_id'],
				(int) $token['workflow_id'],
				sanitize_key( $token['owner_id'] ),
				(int) $token['fence']
			)
		);
		if ( 1 !== $count ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-RUN-004', 'workflow_lease_fence_stale', 'retry_workflow' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-RUN-000', 'workflow_lease_current' );
	}

	public function release( array $token ) {
		global $wpdb;
		foreach ( array( 'site_id', 'workflow_id', 'owner_id', 'fence' ) as $key ) {
			if ( empty( $token[ $key ] ) ) {
				return false;
			}
		}
		$table = WaicWorkflowPhase0Storage::table( 'workflow_leases' );
		return false !== $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE site_id=%d AND workflow_id=%d AND owner_id=%s AND fence=%d",
				(int) $token['site_id'],
				(int) $token['workflow_id'],
				sanitize_key( $token['owner_id'] ),
				(int) $token['fence']
			)
		);
	}
}
