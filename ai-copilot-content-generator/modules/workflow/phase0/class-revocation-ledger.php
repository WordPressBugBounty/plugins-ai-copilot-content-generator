<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HMAC-authenticated revocation tombstones outside the WordPress database.
 * Restoring an older database cannot silently revive a revoked grant/credential.
 */
final class WaicWorkflowPhase0RevocationLedger {
	const SCHEMA = 'aiwu.workflow-revocation.v1';

	private $directory;
	private $auth_key;

	public function __construct( $directory, $auth_key ) {
		$directory = wp_normalize_path( (string) $directory );
		$auth_key = (string) $auth_key;
		if ( '' === $directory || strlen( $auth_key ) < 32 ) {
			throw new InvalidArgumentException( 'Workflow revocation authority is unavailable.' );
		}
		$this->directory = rtrim( $directory, '/' );
		$this->auth_key = $auth_key;
	}

	public static function fromConstants() {
		if ( ! defined( 'WAIC_WORKFLOW_REVOCATION_DIR' ) || ! defined( 'WAIC_WORKFLOW_REVOCATION_KEY' ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-CRED-001', 'configure_revocation_authority' );
		}
		try {
			return new self( WAIC_WORKFLOW_REVOCATION_DIR, WAIC_WORKFLOW_REVOCATION_KEY );
		} catch ( InvalidArgumentException $error ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-CRED-001', 'configure_revocation_authority' );
		}
	}

	public function revoke( $kind, $site_uuid, $subject_id, $now ) {
		$identity = $this->identity( $kind, $site_uuid, $subject_id );
		if ( $identity instanceof WaicWorkflowPhase0Result ) {
			return $identity;
		}
		if ( ! is_dir( $this->directory ) && ! wp_mkdir_p( $this->directory ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-CRED-001', 'configure_revocation_authority' );
		}
		$record = array(
			'schema'     => self::SCHEMA,
			'kind'       => $identity['kind'],
			'site_uuid'  => $identity['site_uuid'],
			'subject_id' => $identity['subject_id'],
			'revoked_at' => (int) $now,
		);
		$record['mac'] = $this->mac( $record );
		$encoded = wp_json_encode( $record, JSON_UNESCAPED_SLASHES );
		$path = $this->path( $identity );
		$temporary = tempnam( $this->directory, '.aiwu-' );
		if ( false === $temporary || false === file_put_contents( $temporary, $encoded, LOCK_EX ) ) {
			if ( is_string( $temporary ) && is_file( $temporary ) ) {
				@unlink( $temporary );
			}
			return WaicWorkflowPhase0Result::unavailable( 'WF-CRED-001', 'configure_revocation_authority' );
		}
		@chmod( $temporary, 0600 );
		if ( ! @rename( $temporary, $path ) ) {
			@unlink( $temporary );
			return WaicWorkflowPhase0Result::unavailable( 'WF-CRED-001', 'configure_revocation_authority' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-CRED-000', 'workflow_revocation_recorded' );
	}

	public function isRevoked( $kind, $site_uuid, $subject_id ) {
		$identity = $this->identity( $kind, $site_uuid, $subject_id );
		if ( $identity instanceof WaicWorkflowPhase0Result ) {
			return $identity;
		}
		$path = $this->path( $identity );
		if ( ! is_file( $path ) ) {
			return false;
		}
		$record = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $record ) || empty( $record['mac'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_revocation_record_invalid', 'suspend_workflow' );
		}
		$mac = (string) $record['mac'];
		unset( $record['mac'] );
		if ( self::SCHEMA !== ( isset( $record['schema'] ) ? $record['schema'] : '' ) || ! hash_equals( $this->mac( $record ), $mac ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_revocation_record_invalid', 'suspend_workflow' );
		}
		foreach ( array( 'kind', 'site_uuid', 'subject_id' ) as $key ) {
			if ( ! isset( $record[ $key ] ) || ! hash_equals( (string) $identity[ $key ], (string) $record[ $key ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_revocation_scope_invalid', 'suspend_workflow' );
			}
		}
		return true;
	}

	public function guardRestore( $kind, $site_uuid, $subject_id ) {
		$revoked = $this->isRevoked( $kind, $site_uuid, $subject_id );
		if ( true === $revoked || $revoked instanceof WaicWorkflowPhase0Result ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_restore_authority_revoked', 'revalidate_workflow' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-MIG-000', 'workflow_restore_authority_current' );
	}

	private function identity( $kind, $site_uuid, $subject_id ) {
		$kind = sanitize_key( (string) $kind );
		$site_uuid = strtolower( (string) $site_uuid );
		$subject_id = sanitize_key( (string) $subject_id );
		if ( ! in_array( $kind, array( 'credential', 'grant', 'legacy_adapter' ), true ) || ! preg_match( '/^[a-f0-9-]{36}$/', $site_uuid ) || '' === $subject_id ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-MIG-003', 'workflow_revocation_scope_invalid', 'suspend_workflow' );
		}
		return compact( 'kind', 'site_uuid', 'subject_id' );
	}

	private function path( array $identity ) {
		return $this->directory . '/' . hash( 'sha256', implode( '|', $identity ) ) . '.json';
	}

	private function mac( array $record ) {
		return hash_hmac( 'sha256', wp_json_encode( $record, JSON_UNESCAPED_SLASHES ), $this->auth_key );
	}
}
