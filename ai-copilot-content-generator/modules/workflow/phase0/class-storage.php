<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Storage {
	const SCHEMA_VERSION = 1;
	const MIGRATION_HOLD_OPTION = 'waic_workflow_migration_hold';

	public static function table( $name ) {
		global $wpdb;
		$allowed = array( 'workflow_journal', 'workflow_outbox', 'workflow_leases', 'workflow_credentials' );
		if ( ! in_array( $name, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Unknown Workflow storage table.' );
		}
		return $wpdb->prefix . WAIC_DB_PREF . $name;
	}

	public static function getSchemaVersion() {
		return (int) get_option( 'waic_workflow_schema_version', 0 );
	}

	public static function countExistingTables() {
		global $wpdb;
		$count = 0;
		foreach ( array( 'workflow_journal', 'workflow_outbox', 'workflow_leases', 'workflow_credentials' ) as $name ) {
			$table = self::table( $name );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				$count++;
			}
		}
		return $count;
	}

	public static function enterMigrationHold( $stored, $current, $existing ) {
		$policy = WaicWorkflowPhase0RuntimePolicy::defaults();
		$hold = array(
			'code'            => 'WF-MIG-003',
			'stored_schema'   => (int) $stored,
			'current_schema'  => (int) $current,
			'tables_detected' => (int) $existing,
		);
		update_option( WaicWorkflowPhase0RuntimePolicy::OPTION, $policy, false );
		update_option(
			self::MIGRATION_HOLD_OPTION,
			$hold,
			false
		);
		if ( $policy !== get_option( WaicWorkflowPhase0RuntimePolicy::OPTION, null ) || $hold !== get_option( self::MIGRATION_HOLD_OPTION, null ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-004', 'workflow_migration_hold_persistence_failed', 'repair_workflow_storage' );
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_schema_code_mismatch', 'update_plugin' );
	}

	public static function clearMigrationHold() {
		return delete_option( self::MIGRATION_HOLD_OPTION );
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$journal = self::table( 'workflow_journal' );
		$outbox = self::table( 'workflow_outbox' );
		$leases = self::table( 'workflow_leases' );
		$credentials = self::table( 'workflow_credentials' );

		dbDelta( "CREATE TABLE {$journal} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			workflow_id bigint unsigned NOT NULL DEFAULT 0,
			operation_id varchar(80) NOT NULL,
			idempotency_key varchar(64) NOT NULL,
			payload_digest char(71) NOT NULL,
			state varchar(24) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_operation_idempotency (site_id,operation_id,idempotency_key),
			KEY idx_workflow_state (site_id,workflow_id,state)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$outbox} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			journal_id bigint unsigned NOT NULL,
			event_code varchar(80) NOT NULL,
			payload longtext NOT NULL,
			state varchar(24) NOT NULL,
			attempts smallint unsigned NOT NULL DEFAULT 0,
			available_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_journal_event (journal_id,event_code),
			KEY idx_delivery (state,available_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$leases} (
			site_id bigint unsigned NOT NULL,
			workflow_id bigint unsigned NOT NULL,
			fence bigint unsigned NOT NULL DEFAULT 0,
			owner_id varchar(64) NOT NULL,
			expires_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (site_id,workflow_id),
			KEY idx_expiry (expires_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$credentials} (
			credential_id varchar(64) NOT NULL,
			site_uuid char(36) NOT NULL,
			purpose varchar(64) NOT NULL,
			key_version varchar(32) NOT NULL,
			nonce varbinary(24) NOT NULL,
			ciphertext longblob NOT NULL,
			deleted_at datetime NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (credential_id),
			KEY idx_site_purpose (site_uuid,purpose),
			KEY idx_key_version (key_version)
		) {$charset};" );

		if ( false === get_option( WaicWorkflowPhase0RuntimePolicy::OPTION, false ) ) {
			add_option( WaicWorkflowPhase0RuntimePolicy::OPTION, WaicWorkflowPhase0RuntimePolicy::defaults(), '', false );
		}
		update_option( 'waic_workflow_schema_version', self::SCHEMA_VERSION, false );
		return self::verify();
	}

	public static function verify() {
		global $wpdb;
		foreach ( array( 'workflow_journal', 'workflow_outbox', 'workflow_leases', 'workflow_credentials' ) as $name ) {
			$table = self::table( $name );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-MIG-001', 'workflow_schema_incomplete', 'run_workflow_migration' );
			}
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-MIG-000', 'workflow_schema_verified' );
	}
}
