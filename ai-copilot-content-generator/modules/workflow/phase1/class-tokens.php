<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Tokens {
	public static function issue( $kind, array $binding, $ttl ) {
		$kind = sanitize_key( (string) $kind );
		if ( ! in_array( $kind, array( 'validation', 'confirmation' ), true ) ) {
			throw new InvalidArgumentException( 'token_kind_invalid' );
		}
		$now = time();
		$payload = array(
			'v'      => 1,
			'kind'   => $kind,
			'iat'    => $now,
			'exp'    => $now + max( 1, (int) $ttl ),
			'nonce'  => bin2hex( random_bytes( 32 ) ),
			'binding'=> self::normalizeBinding( $binding ),
		);
		$encoded = self::base64UrlEncode( WaicWorkflowPhase1Canonicalizer::encode( $payload ) );
		$signature = hash_hmac( 'sha256', $encoded, self::key(), true );
		$token = $encoded . '.' . self::base64UrlEncode( $signature );
		$normalized = self::normalizeBinding( $binding );
		if ( ! isset( $normalized['site_id'], $normalized['actor_id'], $normalized['operation_id'], $normalized['artifact_digest'] ) ) {
			return '';
		}
		return WaicWorkflowPhase1Storage::storeGrant( $kind, self::tokenHash( $token ), $normalized, $payload['exp'] ) ? $token : '';
	}

	public static function verify( $token, $kind, array $binding, $consume = false ) {
		if ( ! is_string( $token ) || strlen( $token ) > 4096 || 1 !== substr_count( $token, '.' ) ) {
			return false;
		}
		list( $encoded, $signature ) = explode( '.', $token, 2 );
		$expected = self::base64UrlEncode( hash_hmac( 'sha256', $encoded, self::key(), true ) );
		if ( ! hash_equals( $expected, $signature ) ) {
			return false;
		}
		$decoded = self::base64UrlDecode( $encoded );
		if ( false === $decoded ) {
			return false;
		}
		try {
			$payload = WaicWorkflowPhase1Json::decodeStrict( $decoded, 4096, 8, 1024 );
		} catch ( Exception $exception ) {
			return false;
		}
		if ( ! is_array( $payload ) || ! isset( $payload['v'], $payload['kind'], $payload['exp'] ) || 1 !== $payload['v'] || $kind !== $payload['kind'] || time() > (int) $payload['exp'] ) {
			return false;
		}
		$matches = hash_equals(
			WaicWorkflowPhase1Canonicalizer::digest( self::normalizeBinding( $binding ) ),
			WaicWorkflowPhase1Canonicalizer::digest( isset( $payload['binding'] ) ? $payload['binding'] : array() )
		);
		if ( ! $matches ) {
			return false;
		}
		return WaicWorkflowPhase1Storage::verifyGrant( $kind, self::tokenHash( $token ), $consume );
	}

	public static function tokenHash( $token ) {
		return hash( 'sha256', (string) $token );
	}

	public static function binding( $operation_id, $artifact_digest, $handle_hash, array $validated ) {
		return array(
			'actor_id'           => get_current_user_id(),
			'site_id'            => get_current_blog_id(),
			'operation_id'       => (string) $operation_id,
			'artifact_digest'     => (string) $artifact_digest,
			'handle_hash'        => (string) $handle_hash,
			'policy_revision'    => isset( $validated['policy_revision'] ) ? $validated['policy_revision'] : '',
			'descriptor_revision'=> isset( $validated['descriptor_revision'] ) ? $validated['descriptor_revision'] : '',
			'risk'               => isset( $validated['risk'] ) ? $validated['risk'] : array(),
			'dependencies'       => isset( $validated['dependencies'] ) ? $validated['dependencies'] : array(),
			'mapping_digest'     => isset( $validated['mapping_digest'] ) ? $validated['mapping_digest'] : '',
			'command_digest'     => isset( $validated['command_digest'] ) ? $validated['command_digest'] : '',
		);
	}

	private static function normalizeBinding( array $binding ) {
		$allowed = array( 'actor_id', 'site_id', 'operation_id', 'artifact_digest', 'handle_hash', 'policy_revision', 'descriptor_revision', 'risk', 'dependencies', 'mapping_digest', 'command_digest', 'target_version', 'plan_digest' );
		$binding = array_intersect_key( $binding, array_flip( $allowed ) );
		return WaicWorkflowPhase1Canonicalizer::normalize( $binding );
	}

	private static function key() {
		return hash_hmac( 'sha256', 'aiwu-workflow-phase1-token-v1', wp_salt( 'auth' ), true );
	}

	private static function base64UrlEncode( $bytes ) {
		return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
	}

	private static function base64UrlDecode( $value ) {
		if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
			return false;
		}
		$padding = strlen( $value ) % 4;
		if ( $padding ) {
			$value .= str_repeat( '=', 4 - $padding );
		}
		return base64_decode( strtr( $value, '-_', '+/' ), true );
	}
}

