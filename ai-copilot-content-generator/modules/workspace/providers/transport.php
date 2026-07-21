<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicProviderTransport {
	const MAX_RESPONSE_BYTES = 524288;

	public static function request( $request, $customEndpoint = false ) {
		$url = isset( $request['url'] ) ? (string) $request['url'] : '';
		if ( ! self::validateUrl( $url, $customEndpoint ) ) {
			return new WaicProviderFailure( 'CONFIGURATION', 'Provider endpoint is not permitted.', WaicProviderErrorNormalizer::correlationId() );
		}
		$args = array(
			'timeout' => min( 60, max( 1, (int) WaicUtils::getArrayValue( $request, 'timeout', 30, 1 ) ) ),
			'redirection' => 0,
			'sslverify' => true,
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
			'headers' => WaicUtils::getArrayValue( $request, 'headers', array(), 2 ),
			'body' => isset( $request['body'] ) ? wp_json_encode( $request['body'] ) : '',
		);
		$method = strtoupper( (string) WaicUtils::getArrayValue( $request, 'method', 'POST' ) );
		$response = wp_safe_remote_request( $url, array_merge( $args, array( 'method' => $method ) ) );
		$correlationId = WaicProviderErrorNormalizer::correlationId();
		if ( is_wp_error( $response ) ) {
			return new WaicProviderFailure( 'NETWORK', 'Provider network request failed.', $correlationId, true );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		$decoded = is_array( $decoded ) ? $decoded : array();
		if ( $status < 200 || $status >= 300 ) {
			return WaicProviderErrorNormalizer::normalize( $status, $decoded, WaicUtils::getArrayValue( $request, 'secrets', array(), 2 ), $correlationId );
		}
		return array(
			'body' => $decoded,
			'status' => $status,
			'headers' => wp_remote_retrieve_headers( $response ),
			'correlation_id' => $correlationId,
		);
	}

	public static function validateUrl( $url, $customEndpoint = false ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== strtolower( (string) WaicUtils::getArrayValue( $parts, 'scheme', '' ) ) || empty( $parts['host'] ) || ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
			return false;
		}
		if ( ! empty( $parts['port'] ) && 443 !== (int) $parts['port'] ) { return false; }
		$host = strtolower( (string) $parts['host'] );
		if ( 'localhost' === $host || false !== strpos( $host, '..' ) || preg_match( '/(^|\.)(local|internal)$/', $host ) ) { return false; }
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return (bool) filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}
		if ( ! preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/', $host ) ) { return false; }
		if ( ! $customEndpoint ) { return true; }
		$ips = @gethostbynamel( $host );
		if ( false === $ips || empty( $ips ) ) { return false; }
		foreach ( $ips as $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) { return false; }
		}
		return true;
	}
}
