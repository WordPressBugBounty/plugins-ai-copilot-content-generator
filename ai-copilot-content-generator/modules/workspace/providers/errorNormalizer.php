<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicProviderErrorNormalizer {
	const CODES = array('CONFIGURATION', 'AUTH', 'PERMISSION', 'CAPABILITY_UNSUPPORTED', 'MODEL_UNAVAILABLE', 'INVALID_REQUEST', 'CONTENT_POLICY', 'RATE_LIMIT', 'QUOTA', 'BILLING', 'REGION_MISMATCH', 'TIMEOUT', 'NETWORK', 'UPSTREAM', 'CANCELED');

	public static function correlationId() {
		return substr( wp_hash( uniqid( 'waic-provider-', true ) ), 0, 16 );
	}

	public static function normalize( $status, $body = array(), $secrets = array(), $correlationId = '' ) {
		$status = (int) $status;
		$code = 'UPSTREAM';
		if ( 401 === $status ) { $code = 'AUTH'; }
		elseif ( 403 === $status ) { $code = 'PERMISSION'; }
		elseif ( 404 === $status ) { $code = 'MODEL_UNAVAILABLE'; }
		elseif ( 408 === $status || 504 === $status ) { $code = 'TIMEOUT'; }
		elseif ( 409 === $status || 422 === $status || 400 === $status ) { $code = 'INVALID_REQUEST'; }
		elseif ( 429 === $status ) { $code = 'RATE_LIMIT'; }
		elseif ( 402 === $status ) { $code = 'BILLING'; }
		elseif ( 0 === $status ) { $code = 'NETWORK'; }
		$message = self::messageFromBody( $body );
		$message = self::redact( $message, $secrets );
		if ( '' === $message ) {
			$message = sprintf( 'Provider request failed (%s).', $code );
		}
		return new WaicProviderFailure( $code, $message, $correlationId, in_array( $code, array('RATE_LIMIT', 'TIMEOUT', 'NETWORK', 'UPSTREAM'), true ) );
	}

	public static function redact( $value, $secrets = array() ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		foreach ( (array) $secrets as $secret ) {
			$secret = trim( (string) $secret );
			if ( strlen( $secret ) >= 4 ) {
				$value = str_replace( $secret, '[redacted]', $value );
			}
		}
		$value = preg_replace( '/(authorization\s*[:=]\s*(?:bearer\s+)?)[^\s,;]+/i', '$1[redacted]', $value );
		$value = preg_replace( '/\b(?:sk|pk|api|key|token)[_-][a-z0-9_-]{6,}\b/i', '[redacted]', $value );
		$value = preg_replace( '/\b[a-z0-9_-]*(?:secret|token|api[_-]?key)[a-z0-9_-]*\b\s*[:=]\s*[^\s,;]+/i', '[redacted]', $value );
		return substr( sanitize_text_field( $value ), 0, 240 );
	}

	private static function messageFromBody( $body ) {
		if ( is_string( $body ) ) {
			$decoded = json_decode( $body, true );
			$body = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $body ) ) { return ''; }
		$error = WaicUtils::getArrayValue( $body, 'error', array(), 2 );
		if ( is_array( $error ) ) {
			return (string) WaicUtils::getArrayValue( $error, 'message', '' );
		}
		return (string) WaicUtils::getArrayValue( $body, 'message', '' );
	}
}
