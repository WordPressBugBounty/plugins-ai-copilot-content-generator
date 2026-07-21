<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Storage {
	const SCHEMA_VERSION = 1;
	const SCHEMA_OPTION = 'waic_workflow_phase1_schema_version';
	const HOLD_OPTION = 'waic_workflow_phase1_migration_hold';

	private static $tables = array(
		'workflow_pack_versions',
		'workflow_pack_objects',
		'workflow_operations',
		'workflow_audit_events',
		'workflow_validation_stamps',
		'workflow_confirmations',
		'workflow_policy_overrides',
		'workflow_rate_limits',
		'workflow_ai_budgets',
		'workflow_outcome_counters',
	);

	public static function table( $name ) {
		global $wpdb;
		if ( ! in_array( $name, self::$tables, true ) ) {
			throw new InvalidArgumentException( 'Unknown Phase 1 storage table.' );
		}
		return $wpdb->prefix . WAIC_DB_PREF . $name;
	}

	public static function maybeInstall() {
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'core' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$stored = (int) get_option( self::SCHEMA_OPTION, 0 );
		$existing = self::countExistingTables();
		if ( 0 === $stored && 0 === $existing ) {
			self::install();
			return;
		}
		if ( self::SCHEMA_VERSION !== $stored || count( self::$tables ) !== $existing ) {
			update_option(
				self::HOLD_OPTION,
				array( 'code' => 'WF-MIG-003', 'stored_schema' => $stored, 'current_schema' => self::SCHEMA_VERSION, 'tables_detected' => $existing ),
				false
			);
		}
		if ( self::SCHEMA_VERSION === $stored && count( self::$tables ) === $existing && false === get_option( 'waic_workflow_site_uuid', false ) ) {
			add_option( 'waic_workflow_site_uuid', wp_generate_uuid4(), '', false );
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$pack_versions = self::table( 'workflow_pack_versions' );
		$pack_objects = self::table( 'workflow_pack_objects' );
		$operations = self::table( 'workflow_operations' );
		$audit = self::table( 'workflow_audit_events' );
		$stamps = self::table( 'workflow_validation_stamps' );
		$confirmations = self::table( 'workflow_confirmations' );
		$overrides = self::table( 'workflow_policy_overrides' );
		$rate_limits = self::table( 'workflow_rate_limits' );
		$budgets = self::table( 'workflow_ai_budgets' );
		$counters = self::table( 'workflow_outcome_counters' );

		dbDelta( "CREATE TABLE {$pack_versions} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			package_id varchar(128) NOT NULL,
			version varchar(128) NOT NULL,
			manifest_digest char(71) NOT NULL,
			artifact_digest char(71) NOT NULL,
			manifest_json longtext NOT NULL,
			provenance varchar(32) NOT NULL,
			state varchar(32) NOT NULL,
			is_current tinyint unsigned NOT NULL DEFAULT 0,
			created_by bigint unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_site_package_version (site_id,package_id,version),
			KEY idx_site_current (site_id,is_current,state),
			KEY idx_artifact_digest (artifact_digest)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$pack_objects} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			pack_version_id bigint unsigned NOT NULL,
			package_id varchar(128) NOT NULL,
			workflow_id varchar(128) NOT NULL,
			object_type varchar(32) NOT NULL,
			ownership varchar(32) NOT NULL,
			local_modified tinyint unsigned NOT NULL DEFAULT 0,
			content_digest char(71) NOT NULL,
			workflow_json longtext NOT NULL,
			state varchar(32) NOT NULL,
			created_by bigint unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_pack_workflow (pack_version_id,workflow_id),
			KEY idx_site_package_state (site_id,package_id,state),
			KEY idx_site_owner (site_id,created_by,ownership)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$operations} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			operation_id varchar(80) NOT NULL,
			idempotency_hash char(64) NOT NULL,
			operation_handle_hash char(64) NOT NULL,
			input_digest char(71) NOT NULL,
			confirmation_hash char(64) NOT NULL,
			state varchar(32) NOT NULL,
			code varchar(80) NOT NULL,
			result_json longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			retained_until datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_scoped_idempotency (site_id,actor_id,operation_id,idempotency_hash),
			KEY idx_site_state_retention (site_id,state,retained_until)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$audit} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			operation_row_id bigint unsigned NOT NULL,
			event_code varchar(80) NOT NULL,
			object_type varchar(32) NOT NULL,
			object_ref varchar(191) NOT NULL,
			context_json text NOT NULL,
			created_at datetime NOT NULL,
			retained_until datetime NOT NULL,
			legal_hold tinyint unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY idx_site_created (site_id,created_at),
			KEY idx_retention_hold (retained_until,legal_hold)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$stamps} (
			stamp_hash char(64) NOT NULL,
			site_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			operation_id varchar(80) NOT NULL,
			artifact_digest char(71) NOT NULL,
			consumed_at datetime NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (stamp_hash),
			KEY idx_stamp_expiry (site_id,expires_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$confirmations} (
			confirmation_hash char(64) NOT NULL,
			site_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			operation_id varchar(80) NOT NULL,
			artifact_digest char(71) NOT NULL,
			consumed_at datetime NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (confirmation_hash),
			KEY idx_confirmation_expiry (site_id,expires_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$overrides} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint unsigned NOT NULL,
			operation_id varchar(80) NOT NULL,
			policy_revision char(71) NOT NULL,
			disposition varchar(16) NOT NULL,
			reason_code varchar(80) NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_site_operation_expiry (site_id,operation_id,expires_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$rate_limits} (
			scope_key char(64) NOT NULL,
			scope_type varchar(16) NOT NULL,
			family varchar(32) NOT NULL,
			window_start bigint unsigned NOT NULL,
			window_seconds int unsigned NOT NULL,
			request_count int unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (scope_key),
			KEY idx_rate_expiry (window_start,window_seconds)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$budgets} (
			site_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			period_key varchar(24) NOT NULL,
			limit_micro_usd bigint unsigned NOT NULL,
			reserved_micro_usd bigint unsigned NOT NULL DEFAULT 0,
			used_micro_usd bigint unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (site_id,actor_id,period_key)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$counters} (
			site_id bigint unsigned NOT NULL,
			code varchar(80) NOT NULL,
			terminal_state varchar(32) NOT NULL,
			duration_bucket varchar(16) NOT NULL,
			size_bucket varchar(16) NOT NULL,
			time_bucket bigint unsigned NOT NULL,
			counter bigint unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (site_id,code,terminal_state,duration_bucket,size_bucket,time_bucket),
			KEY idx_counter_expiry (time_bucket)
		) {$charset};" );

		if ( count( self::$tables ) !== self::countExistingTables() ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.plugin.migrate', 'workflow_schema_incomplete', __( 'Phase 1 storage could not be installed.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		if ( false === get_option( 'waic_workflow_site_uuid', false ) ) {
			add_option( 'waic_workflow_site_uuid', wp_generate_uuid4(), '', false );
		}
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
		delete_option( self::HOLD_OPTION );
		return WaicWorkflowPhase1Result::success( 'workflow.plugin.migrate', 'workflow_schema_verified', __( 'Phase 1 storage is ready.', 'ai-copilot-content-generator' ) );
	}

	public static function verify() {
		if ( self::SCHEMA_VERSION !== (int) get_option( self::SCHEMA_OPTION, 0 ) || count( self::$tables ) !== self::countExistingTables() || get_option( self::HOLD_OPTION, false ) ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.plugin.migrate', 'workflow_schema_incomplete', __( 'Phase 1 storage is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		return WaicWorkflowPhase1Result::success( 'workflow.plugin.migrate', 'workflow_schema_verified', __( 'Phase 1 storage is ready.', 'ai-copilot-content-generator' ) );
	}

	public static function countExistingTables() {
		global $wpdb;
		$count = 0;
		foreach ( self::$tables as $name ) {
			$table = self::table( $name );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				$count++;
			}
		}
		return $count;
	}

	public static function begin() {
		global $wpdb;
		return false !== $wpdb->query( 'START TRANSACTION' );
	}

	public static function commit() {
		global $wpdb;
		return false !== $wpdb->query( 'COMMIT' );
	}

	public static function rollback() {
		global $wpdb;
		return false !== $wpdb->query( 'ROLLBACK' );
	}

	public static function findOperation( $operation_id, $key_hash, $actor_id, $site_id ) {
		global $wpdb;
		$table = self::table( 'workflow_operations' );
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE site_id=%d AND actor_id=%d AND operation_id=%s AND idempotency_hash=%s LIMIT 1",
				$site_id,
				$actor_id,
				$operation_id,
				$key_hash
			),
			ARRAY_A
		);
	}

	public static function createOperation( $operation_id, $key_hash, $handle_hash, $input_digest, $confirmation_hash, $actor_id, $site_id ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$result = $wpdb->insert(
			self::table( 'workflow_operations' ),
			array(
				'site_id'           => $site_id,
				'actor_id'          => $actor_id,
				'operation_id'       => $operation_id,
				'idempotency_hash'   => $key_hash,
				'operation_handle_hash'=> $handle_hash,
				'input_digest'       => $input_digest,
				'confirmation_hash'  => $confirmation_hash,
				'state'              => 'committing',
				'code'               => 'operation_started',
				'result_json'        => '{}',
				'created_at'         => $now,
				'updated_at'         => $now,
				'retained_until'     => gmdate( 'Y-m-d H:i:s', time() + 180 * DAY_IN_SECONDS ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return false === $result ? false : (int) $wpdb->insert_id;
	}

	public static function completeOperation( $id, $state, $code, array $result ) {
		global $wpdb;
		$updated = $wpdb->update(
			self::table( 'workflow_operations' ),
			array( 'state' => $state, 'code' => sanitize_key( $code ), 'result_json' => WaicWorkflowPhase1Canonicalizer::encode( $result ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		return 1 === $updated;
	}

	public static function insertAudit( $operation_row_id, $event_code, $object_type, $object_ref, array $context, $actor_id, $site_id ) {
		global $wpdb;
		$context = array_intersect_key( $context, array_flip( array( 'package_id', 'version', 'artifact_digest', 'workflow_id', 'state', 'code' ) ) );
		$result = $wpdb->insert(
			self::table( 'workflow_audit_events' ),
			array(
				'site_id'         => $site_id,
				'actor_id'        => $actor_id,
				'operation_row_id' => (int) $operation_row_id,
				'event_code'       => sanitize_key( $event_code ),
				'object_type'      => sanitize_key( $object_type ),
				'object_ref'       => substr( (string) $object_ref, 0, 191 ),
				'context_json'     => WaicWorkflowPhase1Canonicalizer::encode( $context ),
				'created_at'       => current_time( 'mysql', true ),
				'retained_until'   => gmdate( 'Y-m-d H:i:s', time() + 365 * DAY_IN_SECONDS ),
				'legal_hold'       => 0,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);
		return 1 === $result;
	}

	public static function recordOutcome( $code, $state, $duration_ms = 0, $size = 0, $site_id = null ) {
		global $wpdb;
		$site_id = null === $site_id ? get_current_blog_id() : (int) $site_id;
		$duration_bucket = $duration_ms < 100 ? 'lt100ms' : ( $duration_ms < 1000 ? 'lt1s' : ( $duration_ms < 5000 ? 'lt5s' : 'gte5s' ) );
		$size_bucket = $size < 1024 ? 'lt1k' : ( $size < 1048576 ? 'lt1m' : 'gte1m' );
		$bucket = (int) ( floor( time() / HOUR_IN_SECONDS ) * HOUR_IN_SECONDS );
		$table = self::table( 'workflow_outcome_counters' );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (site_id,code,terminal_state,duration_bucket,size_bucket,time_bucket,counter) VALUES (%d,%s,%s,%s,%s,%d,1) ON DUPLICATE KEY UPDATE counter=counter+1",
			$site_id,
			sanitize_key( $code ),
			sanitize_key( $state ),
			$duration_bucket,
			$size_bucket,
			$bucket
		);
		return false !== $wpdb->query( $sql );
	}

	public static function listPacks( $site_id, $limit, $after_id = 0 ) {
		global $wpdb;
		$table = self::table( 'workflow_pack_versions' );
		$limit = max( 1, min( 50, (int) $limit ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id,package_id,version,artifact_digest,provenance,state,created_at,updated_at FROM {$table} WHERE site_id=%d AND is_current=1 AND id>%d ORDER BY id ASC LIMIT %d",
				$site_id,
				$after_id,
				$limit
			),
			ARRAY_A
		);
	}

	public static function getPack( $site_id, $package_id, $version = '' ) {
		global $wpdb;
		$table = self::table( 'workflow_pack_versions' );
		if ( '' === $version ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_id=%d AND package_id=%s AND is_current=1 LIMIT 1", $site_id, $package_id ), ARRAY_A );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_id=%d AND package_id=%s AND version=%s LIMIT 1", $site_id, $package_id, $version ), ARRAY_A );
	}

	public static function getPackObjects( $site_id, $pack_version_id, $include_removed = false ) {
		global $wpdb;
		$table = self::table( 'workflow_pack_objects' );
		$sql = "SELECT * FROM {$table} WHERE site_id=%d AND pack_version_id=%d";
		if ( ! $include_removed ) {
			$sql .= " AND state<>'removed'";
		}
		$sql .= ' ORDER BY id ASC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $site_id, $pack_version_id ), ARRAY_A );
	}

	public static function reserveAiBudget( $amount ) {
		global $wpdb;
		$amount = max( 1, (int) $amount );
		$limits = get_option( 'waic_workflow_phase1_ai_budget_limits', array() );
		if ( WaicWorkflowPhase1Config::isTestMode() && empty( $limits ) ) {
			$limits = array( 'daily' => 100000, 'monthly' => 1000000 );
		}
		if ( ! is_array( $limits ) || empty( $limits['daily'] ) || empty( $limits['monthly'] ) ) {
			return false;
		}
		$periods = array( gmdate( 'Y-m-d' ) => (int) $limits['daily'], gmdate( 'Y-m' ) => (int) $limits['monthly'] );
		$table = self::table( 'workflow_ai_budgets' );
		if ( ! self::begin() ) { return false; }
		foreach ( $periods as $period => $limit ) {
			$insert = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$table} (site_id,actor_id,period_key,limit_micro_usd,reserved_micro_usd,used_micro_usd,updated_at) VALUES (%d,%d,%s,%d,0,0,%s)", get_current_blog_id(), get_current_user_id(), $period, $limit, current_time( 'mysql', true ) ) );
			if ( false === $insert ) { self::rollback(); return false; }
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET reserved_micro_usd=reserved_micro_usd+%d,updated_at=%s WHERE site_id=%d AND actor_id=%d AND period_key=%s AND used_micro_usd+reserved_micro_usd+%d<=limit_micro_usd", $amount, current_time( 'mysql', true ), get_current_blog_id(), get_current_user_id(), $period, $amount ) );
			if ( 1 !== $updated ) { self::rollback(); return false; }
		}
		if ( ! self::commit() ) { self::rollback(); return false; }
		return array( 'amount' => $amount, 'periods' => array_keys( $periods ) );
	}

	public static function reconcileAiBudget( array $reservation, $actual ) {
		global $wpdb;
		if ( ! isset( $reservation['amount'], $reservation['periods'] ) || ! is_array( $reservation['periods'] ) ) {
			return false;
		}
		$actual = max( 0, min( (int) $reservation['amount'], (int) $actual ) );
		$table = self::table( 'workflow_ai_budgets' );
		if ( ! self::begin() ) { return false; }
		foreach ( $reservation['periods'] as $period ) {
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET reserved_micro_usd=reserved_micro_usd-%d,used_micro_usd=used_micro_usd+%d,updated_at=%s WHERE site_id=%d AND actor_id=%d AND period_key=%s AND reserved_micro_usd>=%d", $reservation['amount'], $actual, current_time( 'mysql', true ), get_current_blog_id(), get_current_user_id(), $period, $reservation['amount'] ) );
			if ( 1 !== $updated ) { self::rollback(); return false; }
		}
		return self::commit();
	}

	public static function exportActorOperations( $actor_id, $page ) {
		global $wpdb;
		$offset = ( max( 1, (int) $page ) - 1 ) * 100;
		$table = self::table( 'workflow_operations' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT id,operation_id,state,code,created_at,updated_at FROM {$table} WHERE site_id=%d AND actor_id=%d ORDER BY id ASC LIMIT 101 OFFSET %d", get_current_blog_id(), (int) $actor_id, $offset ), ARRAY_A );
	}

	public static function eraseActorData( $actor_id ) {
		global $wpdb;
		$site_id = get_current_blog_id();
		$actor_id = (int) $actor_id;
		$pseudonym = max( 1, (int) hexdec( substr( hash_hmac( 'sha256', $site_id . ':' . $actor_id, wp_salt( 'auth' ) ), 0, 12 ) ) );
		$objects = self::table( 'workflow_pack_objects' );
		$operations = self::table( 'workflow_operations' );
		$audit = self::table( 'workflow_audit_events' );
		$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$objects} WHERE site_id=%d AND created_by=%d AND ownership='user_owned' ORDER BY id ASC LIMIT 100", $site_id, $actor_id ) );
		$operation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$operations} WHERE site_id=%d AND actor_id=%d ORDER BY id ASC LIMIT 100", $site_id, $actor_id ) );
		$audit_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$audit} WHERE site_id=%d AND actor_id=%d ORDER BY id ASC LIMIT 100", $site_id, $actor_id ) );
		$removed = false;
		$retained = false;
		$failed = false;
		foreach ( $object_ids as $id ) {
			$changed = $wpdb->update( $objects, array( 'created_by' => $pseudonym, 'workflow_json' => '{}', 'content_digest' => WaicWorkflowPhase1Canonicalizer::digest( array() ), 'state' => 'removed', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $id, 'site_id' => $site_id, 'created_by' => $actor_id ), array( '%d','%s','%s','%s','%s' ), array( '%d','%d','%d' ) );
			if ( false === $changed ) { $failed = true; } else { $removed = true; }
		}
		foreach ( $operation_ids as $id ) {
			$changed = $wpdb->update( $operations, array( 'actor_id' => $pseudonym, 'result_json' => '{}', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $id, 'site_id' => $site_id, 'actor_id' => $actor_id ), array( '%d','%s','%s' ), array( '%d','%d','%d' ) );
			if ( false === $changed ) { $failed = true; } else { $removed = true; }
		}
		foreach ( $audit_ids as $id ) {
			$changed = $wpdb->update( $audit, array( 'actor_id' => $pseudonym ), array( 'id' => (int) $id, 'site_id' => $site_id, 'actor_id' => $actor_id ), array( '%d' ), array( '%d','%d','%d' ) );
			if ( false === $changed ) { $failed = true; } else { $retained = true; }
		}
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'write_failed'   => $failed,
			'done'           => count( $object_ids ) < 100 && count( $operation_ids ) < 100 && count( $audit_ids ) < 100,
		);
	}

	public static function disableSiteAuthority() {
		$flags = update_option( WaicWorkflowPhase1Config::OPTION, WaicWorkflowPhase1Config::defaults(), false );
		$disabled = WaicWorkflowPhase1Config::defaults() === get_option( WaicWorkflowPhase1Config::OPTION, null );
		return $flags || $disabled;
	}

	public static function recordDeletionDelayed( $site_id ) {
		$state = array( 'state' => 'deletion_delayed', 'site_id' => (int) $site_id, 'started_at' => time() );
		update_option( 'waic_workflow_phase1_deletion_state', $state, false );
		return $state === get_option( 'waic_workflow_phase1_deletion_state', null );
	}

	public static function ensureCapabilities() {
		$revision = '1';
		if ( $revision === get_option( 'waic_workflow_phase1_capability_revision', '' ) ) {
			return true;
		}
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return false;
		}
		$role->add_cap( 'aiwu_edit_workflows' );
		$role->add_cap( 'aiwu_import_workflow_packs' );
		$role->add_cap( 'aiwu_generate_workflow_drafts' );
		update_option( 'waic_workflow_phase1_capability_revision', $revision, false );
		return current_user_can( 'aiwu_edit_workflows' )
			&& current_user_can( 'aiwu_import_workflow_packs' )
			&& current_user_can( 'aiwu_generate_workflow_drafts' );
	}

	public static function storeGrant( $kind, $hash, array $binding, $expires_at ) {
		global $wpdb;
		if ( ! in_array( $kind, array( 'validation', 'confirmation' ), true ) || ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
			return false;
		}
		$table_name = 'confirmation' === $kind ? 'workflow_confirmations' : 'workflow_validation_stamps';
		$key_name = 'confirmation' === $kind ? 'confirmation_hash' : 'stamp_hash';
		if ( 'confirmation' === $kind ) {
			$table = self::table( $table_name );
			$now = current_time( 'mysql', true );
			$revoked = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET consumed_at=%s WHERE site_id=%d AND actor_id=%d AND operation_id=%s AND artifact_digest=%s AND consumed_at IS NULL",
					$now,
					(int) $binding['site_id'],
					(int) $binding['actor_id'],
					$binding['operation_id'],
					$binding['artifact_digest']
				)
			);
			if ( false === $revoked ) { return false; }
		}
		$inserted = $wpdb->insert(
			self::table( $table_name ),
			array( $key_name => $hash, 'site_id' => (int) $binding['site_id'], 'actor_id' => (int) $binding['actor_id'], 'operation_id' => $binding['operation_id'], 'artifact_digest' => $binding['artifact_digest'], 'consumed_at' => null, 'expires_at' => gmdate( 'Y-m-d H:i:s', (int) $expires_at ) ),
			array( '%s','%d','%d','%s','%s','%s','%s' )
		);
		return 1 === $inserted;
	}

	public static function verifyGrant( $kind, $hash, $consume = false ) {
		global $wpdb;
		if ( ! in_array( $kind, array( 'validation', 'confirmation' ), true ) || ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
			return false;
		}
		$now = current_time( 'mysql', true );
		if ( 'confirmation' === $kind ) {
			$table = self::table( 'workflow_confirmations' );
			if ( $consume ) {
				$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET consumed_at=%s WHERE confirmation_hash=%s AND site_id=%d AND actor_id=%d AND consumed_at IS NULL AND expires_at>=%s", $now, $hash, get_current_blog_id(), get_current_user_id(), $now ) );
				return 1 === $updated;
			}
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT confirmation_hash FROM {$table} WHERE confirmation_hash=%s AND site_id=%d AND actor_id=%d AND consumed_at IS NULL AND expires_at>=%s LIMIT 1", $hash, get_current_blog_id(), get_current_user_id(), $now ) );
		} else {
			$table = self::table( 'workflow_validation_stamps' );
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT stamp_hash FROM {$table} WHERE stamp_hash=%s AND site_id=%d AND actor_id=%d AND expires_at>=%s LIMIT 1", $hash, get_current_blog_id(), get_current_user_id(), $now ) );
		}
		return is_string( $exists ) && hash_equals( $hash, $exists );
	}

	public static function persistPack( $operation_id, $key_hash, $handle_hash, $confirmation, array $validated, array $manifest, $success_code, $http_status ) {
		global $wpdb;
		$site_id = get_current_blog_id();
		$actor_id = get_current_user_id();
		if ( ! self::verifyGrant( 'confirmation', WaicWorkflowPhase1Tokens::tokenHash( $confirmation ), true ) ) {
			throw new RuntimeException( 'Confirmation consumption failed.' );
		}
		$operation_row = self::createOperation( $operation_id, $key_hash, $handle_hash, isset( $validated['command_digest'] ) ? $validated['command_digest'] : $validated['artifact_digest'], WaicWorkflowPhase1Tokens::tokenHash( $confirmation ), $actor_id, $site_id );
		if ( false === $operation_row ) {
			throw new RuntimeException( 'Phase 1 operation write failed.' );
		}
		$table = self::table( 'workflow_pack_versions' );
		$objects = self::table( 'workflow_pack_objects' );
		$cleared = $wpdb->update( $table, array( 'is_current' => 0, 'updated_at' => current_time( 'mysql', true ) ), array( 'site_id' => $site_id, 'package_id' => $manifest['id'], 'is_current' => 1 ), array( '%d', '%s' ), array( '%d', '%s', '%d' ) );
		if ( false === $cleared ) {
			throw new RuntimeException( 'Current pack transition failed.' );
		}
		$existing_version = self::getPack( $site_id, $manifest['id'], $manifest['version'] );
		if ( $existing_version ) {
			if ( ! hash_equals( $existing_version['artifact_digest'], $validated['artifact_digest'] ) ) {
				throw new RuntimeException( 'Immutable pack version conflict.' );
			}
			$updated = $wpdb->update( $table, array( 'is_current' => 1, 'state' => 'installed', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $existing_version['id'] ), array( '%d', '%s', '%s' ), array( '%d' ) );
			if ( false === $updated ) {
				throw new RuntimeException( 'Existing pack activation failed.' );
			}
			$pack_version_id = (int) $existing_version['id'];
		} else {
			$inserted = $wpdb->insert(
				$table,
				array(
					'site_id' => $site_id, 'package_id' => $manifest['id'], 'version' => $manifest['version'],
					'manifest_digest' => WaicWorkflowPhase1Canonicalizer::digest( $manifest ), 'artifact_digest' => $validated['artifact_digest'],
					'manifest_json' => WaicWorkflowPhase1Canonicalizer::encode( $manifest ), 'provenance' => $validated['provenance'],
					'state' => 'installed', 'is_current' => 1, 'created_by' => $actor_id,
					'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ),
				),
				array( '%d','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s' )
			);
			if ( 1 !== $inserted ) {
				throw new RuntimeException( 'Pack version write failed.' );
			}
			$pack_version_id = (int) $wpdb->insert_id;
			foreach ( $validated['workflows'] as $workflow ) {
				$written = $wpdb->insert(
					$objects,
					array(
						'site_id' => $site_id, 'pack_version_id' => $pack_version_id, 'package_id' => $manifest['id'],
						'workflow_id' => $workflow['id'], 'object_type' => 'draft', 'ownership' => 'ai_generated' === $validated['provenance'] ? 'user_owned' : 'pack_managed', 'local_modified' => 0,
						'content_digest' => WaicWorkflowPhase1Canonicalizer::digest( $workflow ), 'workflow_json' => WaicWorkflowPhase1Canonicalizer::encode( $workflow ),
						'state' => 'draft', 'created_by' => $actor_id, 'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ),
					),
					array( '%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%d','%s','%s' )
				);
				if ( 1 !== $written ) {
					throw new RuntimeException( 'Pack object write failed.' );
				}
			}
		}
		$mapping_rows_removed = $wpdb->delete( $objects, array( 'site_id' => $site_id, 'pack_version_id' => $pack_version_id, 'object_type' => 'dependency_mapping' ), array( '%d','%d','%s' ) );
		if ( false === $mapping_rows_removed ) { throw new RuntimeException( 'Dependency mapping reset failed.' ); }
		foreach ( isset( $validated['account_mappings'] ) && is_array( $validated['account_mappings'] ) ? $validated['account_mappings'] : array() as $mapping ) {
			$mapping_json = WaicWorkflowPhase1Canonicalizer::encode( array( 'schema' => 'aiwu.workflow-account-mapping.v1', 'slot' => $mapping['slot'], 'purpose' => $mapping['purpose'], 'reference' => $mapping['reference'] ) );
			$mapping_id = 'mapping.' . substr( hash( 'sha256', $mapping['slot'] ), 0, 32 );
			$written = $wpdb->insert(
				$objects,
				array(
					'site_id' => $site_id, 'pack_version_id' => $pack_version_id, 'package_id' => $manifest['id'],
					'workflow_id' => $mapping_id, 'object_type' => 'dependency_mapping', 'ownership' => 'pack_managed', 'local_modified' => 0,
					'content_digest' => WaicWorkflowPhase1Canonicalizer::digest( $mapping, 'account-mapping', 'v1' ), 'workflow_json' => $mapping_json,
					'state' => 'active', 'created_by' => $actor_id, 'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ),
				),
				array( '%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%d','%s','%s' )
			);
			if ( 1 !== $written ) { throw new RuntimeException( 'Dependency mapping write failed.' ); }
		}
		if ( ! self::insertAudit( $operation_row, $success_code, 'workflow_pack', $manifest['id'], array( 'package_id' => $manifest['id'], 'version' => $manifest['version'], 'artifact_digest' => $validated['artifact_digest'], 'state' => 'installed' ), $actor_id, $site_id ) ) {
			throw new RuntimeException( 'Audit write failed.' );
		}
		$result = WaicWorkflowPhase1Result::success( $operation_id, $success_code, __( 'The workflow pack was committed as inactive drafts.', 'ai-copilot-content-generator' ), array( 'package_id' => $manifest['id'], 'version' => $manifest['version'], 'artifact_digest' => $validated['artifact_digest'], 'created_objects' => count( $validated['workflows'] ) ), $http_status );
		if ( ! self::completeOperation( $operation_row, 'succeeded', $success_code, $result->toArray() ) ) {
			throw new RuntimeException( 'Operation completion write failed.' );
		}
		return $result;
	}

	public static function commitLifecycleMutation( $operation_id, $key_hash, $handle_hash, array $stage, $confirmation ) {
		global $wpdb;
		if ( ! self::verifyGrant( 'confirmation', WaicWorkflowPhase1Tokens::tokenHash( $confirmation ), true ) ) {
			throw new RuntimeException( 'Confirmation consumption failed.' );
		}
			$row_id = self::createOperation( $operation_id, $key_hash, $handle_hash, $stage['validated']['artifact_digest'], WaicWorkflowPhase1Tokens::tokenHash( $confirmation ), get_current_user_id(), get_current_blog_id() );
			if ( false === $row_id ) {
				throw new RuntimeException( 'Operation write failed.' );
			}
			$packs = self::table( 'workflow_pack_versions' );
			$objects = self::table( 'workflow_pack_objects' );
			if ( 'workflow.package.rollback' === $operation_id ) {
				$cleared = $wpdb->update( $packs, array( 'is_current' => 0, 'updated_at' => current_time( 'mysql', true ) ), array( 'site_id' => get_current_blog_id(), 'package_id' => $stage['package_id'], 'is_current' => 1 ), array( '%d','%s' ), array( '%d','%s','%d' ) );
				if ( false === $cleared ) { throw new RuntimeException( 'Current transition failed.' ); }
				$activated = $wpdb->update( $packs, array( 'is_current' => 1, 'state' => 'installed', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $stage['target_id'] ), array( '%d','%s','%s' ), array( '%d' ) );
				if ( 1 !== $activated ) { throw new RuntimeException( 'Rollback target failed.' ); }
				$success_code = 'pack_update_committed';
			} else {
				$uninstalled = $wpdb->update( $packs, array( 'is_current' => 0, 'state' => 'uninstalled', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $stage['current_id'] ), array( '%d','%s','%s' ), array( '%d' ) );
				if ( 1 !== $uninstalled ) { throw new RuntimeException( 'Pack uninstall failed.' ); }
				$removed = $wpdb->update( $objects, array( 'state' => 'removed', 'updated_at' => current_time( 'mysql', true ) ), array( 'pack_version_id' => $stage['current_id'], 'ownership' => 'pack_managed' ), array( '%s','%s' ), array( '%d','%s' ) );
				if ( false === $removed ) { throw new RuntimeException( 'Managed object transition failed.' ); }
				$success_code = 'pack_uninstalled';
			}
			if ( ! self::insertAudit( $row_id, $success_code, 'workflow_pack', $stage['package_id'], array( 'package_id' => $stage['package_id'], 'artifact_digest' => $stage['validated']['artifact_digest'], 'state' => $success_code ), get_current_user_id(), get_current_blog_id() ) ) {
				throw new RuntimeException( 'Audit write failed.' );
			}
			$result = WaicWorkflowPhase1Result::success( $operation_id, $success_code, __( 'The package change was committed.', 'ai-copilot-content-generator' ), array( 'package_id' => $stage['package_id'], 'artifact_digest' => $stage['validated']['artifact_digest'] ) );
			if ( ! self::completeOperation( $row_id, 'succeeded', $success_code, $result->toArray() ) ) {
				throw new RuntimeException( 'Operation completion failed.' );
			}
			return $result;
	}

	public static function boundedCleanup() {
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'core' ) || get_option( self::HOLD_OPTION, false ) ) {
			return;
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_operations' ) . ' WHERE retained_until<%s LIMIT 100', $now ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_audit_events' ) . ' WHERE retained_until<%s AND legal_hold=0 LIMIT 100', $now ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_validation_stamps' ) . ' WHERE expires_at<%s LIMIT 100', $now ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_confirmations' ) . ' WHERE expires_at<%s LIMIT 100', $now ) );
		$cutoff = time() - 2 * DAY_IN_SECONDS;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_rate_limits' ) . ' WHERE window_start<%d LIMIT 500', $cutoff ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( 'workflow_outcome_counters' ) . ' WHERE time_bucket<%d LIMIT 500', time() - 180 * DAY_IN_SECONDS ) );
		WaicWorkflowPhase1PackService::cleanupStaging( 100 );
	}

	public static function deleteSiteData( $site_id ) {
		global $wpdb;
		$site_id = (int) $site_id;
		$pending = false;
		foreach ( self::$tables as $name ) {
			$table = self::table( $name );
			if ( 'workflow_rate_limits' === $name ) {
				continue;
			}
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE site_id=%d LIMIT 500", $site_id ) );
			if ( false === $deleted ) {
				return false;
			}
			$remaining = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE site_id=%d LIMIT 1", $site_id ) );
			if ( null !== $remaining ) { $pending = true; }
		}
		if ( $pending ) {
			self::recordDeletionDelayed( $site_id );
			return false;
		}
		return true;
	}
}
