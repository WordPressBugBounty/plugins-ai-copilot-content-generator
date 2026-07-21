<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Canonicalizer {
	const REVISION = 'rfc8785-jcs-aiwu-v1';
	private static $domains = array(
		'member', 'manifest', 'artifact', 'workflow', 'dependency-state',
		'descriptor-catalog', 'policy-input', 'source-tree', 'ai-plan-seed',
		'account-mappings', 'account-mapping', 'workflow-command',
	);

	public static function encode( $value ) {
		return self::encodeValue( $value );
	}

	public static function digest( $value, $domain = 'artifact', $version = self::REVISION ) {
		return self::digestBytes( self::encode( $value ), $domain, $version );
	}

	public static function digestBytes( $bytes, $domain, $version = self::REVISION ) {
		if ( ! is_string( $bytes ) || ! in_array( $domain, self::$domains, true ) || ! preg_match( '/^[a-z0-9][a-z0-9._-]{0,79}$/', (string) $version ) ) {
			throw new UnexpectedValueException( 'canonical_digest_domain_invalid' );
		}
		$preimage = "AIWU\0" . $domain . "\0" . $version . "\0" . $bytes;
		return 'sha256:' . hash( 'sha256', $preimage );
	}

	public static function normalize( $value ) {
		if ( is_object( $value ) ) {
			$normalized = new stdClass();
			$properties = get_object_vars( $value );
			foreach ( self::sortedKeys( $properties ) as $key ) {
				$normalized->{$key} = self::normalize( $properties[ $key ] );
			}
			return $normalized;
		}
		if ( ! is_array( $value ) ) {
			self::assertScalar( $value );
			return $value;
		}
		if ( self::isList( $value ) ) {
			return array_map( array( __CLASS__, 'normalize' ), $value );
		}
		$result = array();
		foreach ( self::sortedKeys( $value ) as $key ) {
			$result[ $key ] = self::normalize( $value[ $key ] );
		}
		return $result;
	}

	private static function encodeValue( $value ) {
		if ( null === $value ) { return 'null'; }
		if ( true === $value ) { return 'true'; }
		if ( false === $value ) { return 'false'; }
		if ( is_int( $value ) ) {
			self::assertScalar( $value );
			return (string) $value;
		}
		if ( is_float( $value ) ) { return self::encodeFloat( $value ); }
		if ( is_string( $value ) ) {
			if ( 1 !== preg_match( '//u', $value ) ) { throw new UnexpectedValueException( 'canonical_utf8_invalid' ); }
			$encoded = json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS );
			if ( false === $encoded ) { throw new UnexpectedValueException( 'canonical_json_failed' ); }
			return $encoded;
		}
		if ( is_object( $value ) ) { $value = get_object_vars( $value ); $is_object = true; }
		elseif ( is_array( $value ) ) { $is_object = ! self::isList( $value ); }
		else { throw new UnexpectedValueException( 'canonical_type_invalid' ); }
		if ( ! $is_object ) {
			$items = array();
			foreach ( $value as $item ) { $items[] = self::encodeValue( $item ); }
			return '[' . implode( ',', $items ) . ']';
		}
		$items = array();
		foreach ( self::sortedKeys( $value ) as $key ) {
			$items[] = self::encodeValue( $key ) . ':' . self::encodeValue( $value[ $key ] );
		}
		return '{' . implode( ',', $items ) . '}';
	}

	private static function encodeFloat( $value ) {
		self::assertScalar( $value );
		$token = strtolower( json_encode( $value ) );
		if ( false === $token || 'null' === $token ) { throw new UnexpectedValueException( 'canonical_number_unsafe' ); }
		if ( '-0' === $token || '-0.0' === $token ) { throw new UnexpectedValueException( 'canonical_negative_zero' ); }
		if ( false === strpos( $token, 'e' ) ) { return $token; }
		list( $coefficient, $exponent ) = explode( 'e', $token, 2 );
		$negative = '-' === $coefficient[0];
		if ( $negative ) { $coefficient = substr( $coefficient, 1 ); }
		$decimal = strpos( $coefficient, '.' );
		$fraction = false === $decimal ? 0 : strlen( $coefficient ) - $decimal - 1;
		$digits = str_replace( '.', '', $coefficient );
		$decimal_exponent = (int) $exponent - $fraction;
		while ( strlen( $digits ) > 1 && '0' === substr( $digits, -1 ) ) { $digits = substr( $digits, 0, -1 ); $decimal_exponent++; }
		$scientific_exponent = $decimal_exponent + strlen( $digits ) - 1;
		if ( $scientific_exponent >= 21 || $scientific_exponent <= -7 ) {
			$mantissa = $digits[0] . ( strlen( $digits ) > 1 ? '.' . substr( $digits, 1 ) : '' );
			$result = $mantissa . 'e' . ( $scientific_exponent >= 0 ? '+' : '' ) . $scientific_exponent;
		} else {
			$point = strlen( $digits ) + $decimal_exponent;
			if ( $point <= 0 ) { $result = '0.' . str_repeat( '0', -$point ) . $digits; }
			elseif ( $point >= strlen( $digits ) ) { $result = $digits . str_repeat( '0', $point - strlen( $digits ) ); }
			else { $result = substr( $digits, 0, $point ) . '.' . substr( $digits, $point ); }
		}
		return ( $negative ? '-' : '' ) . $result;
	}

	private static function assertScalar( $value ) {
		if ( is_float( $value ) && ( ! is_finite( $value ) || abs( $value ) > 9007199254740991 ) ) { throw new UnexpectedValueException( 'canonical_number_unsafe' ); }
		if ( is_int( $value ) && abs( $value ) > 9007199254740991 ) { throw new UnexpectedValueException( 'canonical_number_unsafe' ); }
	}

	private static function sortedKeys( array $value ) {
		$keys = array_keys( $value );
		foreach ( $keys as $key ) { if ( ! is_string( $key ) ) { throw new UnexpectedValueException( 'canonical_object_key_invalid' ); } }
		usort( $keys, array( __CLASS__, 'compareUtf16' ) );
		return $keys;
	}

	private static function compareUtf16( $left, $right ) {
		$left_units = function_exists( 'mb_convert_encoding' ) ? mb_convert_encoding( $left, 'UTF-16BE', 'UTF-8' ) : iconv( 'UTF-8', 'UTF-16BE', $left );
		$right_units = function_exists( 'mb_convert_encoding' ) ? mb_convert_encoding( $right, 'UTF-16BE', 'UTF-8' ) : iconv( 'UTF-8', 'UTF-16BE', $right );
		return strcmp( $left_units, $right_units );
	}

	private static function isList( array $value ) {
		$index = 0;
		foreach ( $value as $key => $unused ) {
			if ( $key !== $index ) {
				return false;
			}
			$index++;
		}
		return true;
	}
}
