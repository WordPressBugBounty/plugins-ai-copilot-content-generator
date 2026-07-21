<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only adapter from Phase 0 credential authority to opaque UI refs.
 */
final class WaicWorkflowPhase1AccountRegistry {
	const REF_PATTERN = '/^urn:aiwu:account-ref:[a-f0-9]{64}$/';

	public static function choices( $purpose ) {
		$rows = self::rows( $purpose );
		if ( false === $rows ) { return array(); }
		$result = array();
		foreach ( $rows as $row ) {
			$result[] = array(
				'reference' => self::reference( $row['site_uuid'], $purpose, $row['credential_id'] ),
				'purpose'   => $purpose,
			);
		}
		return $result;
	}

	public static function resolve( $purpose, $reference ) {
		if ( ! is_string( $reference ) || 1 !== preg_match( self::REF_PATTERN, $reference ) ) { return false; }
		$rows = self::rows( $purpose );
		if ( false === $rows ) { return false; }
		foreach ( $rows as $row ) {
			$expected = self::reference( $row['site_uuid'], $purpose, $row['credential_id'] );
			if ( hash_equals( $expected, $reference ) ) {
				return array( 'credential_id' => $row['credential_id'], 'purpose' => $purpose, 'reference' => $reference );
			}
		}
		return false;
	}

	private static function rows( $purpose ) {
		global $wpdb;
		$purpose = sanitize_key( (string) $purpose );
		$site_uuid = (string) get_option( 'waic_workflow_site_uuid', '' );
		if ( ! preg_match( '/^[a-f0-9-]{36}$/', $site_uuid ) || ! preg_match( '/^[a-z0-9_]{2,64}$/', $purpose ) || ! class_exists( 'WaicWorkflowPhase0Storage' ) ) { return false; }
		$table = WaicWorkflowPhase0Storage::table( 'workflow_credentials' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { return false; }
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT credential_id,site_uuid FROM {$table} WHERE site_uuid=%s AND purpose=%s AND deleted_at IS NULL ORDER BY credential_id ASC LIMIT 100", $site_uuid, $purpose ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : false;
	}

	private static function reference( $site_uuid, $purpose, $credential_id ) {
		$material = WaicWorkflowPhase1Canonicalizer::encode(
			array( 'site_uuid' => $site_uuid, 'purpose' => $purpose, 'credential_id' => $credential_id )
		);
		return 'urn:aiwu:account-ref:' . hash_hmac( 'sha256', "aiwu-account-ref-v1\0" . $material, wp_salt( 'auth' ) );
	}
}
