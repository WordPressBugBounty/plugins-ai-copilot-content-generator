<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Json {
	private $source = '';
	private $length = 0;
	private $position = 0;
	private $max_depth = 32;
	private $max_string = 65536;

	public static function decodeStrict( $source, $max_bytes = 2097152, $max_depth = 32, $max_string = 65536 ) {
		$source = (string) $source;
		if ( strlen( $source ) > (int) $max_bytes ) {
			throw new LengthException( 'json_too_large' );
		}
		if ( 0 === strncmp( $source, "\xEF\xBB\xBF", 3 ) ) {
			throw new UnexpectedValueException( 'json_bom_forbidden' );
		}
		if ( 1 !== preg_match( '//u', $source ) ) {
			throw new UnexpectedValueException( 'json_utf8_invalid' );
		}
		$parser = new self();
		$parser->source = $source;
		$parser->length = strlen( $source );
		$parser->max_depth = min( 64, max( 1, (int) $max_depth ) );
		$parser->max_string = min( 1048576, max( 1, (int) $max_string ) );
		$value = $parser->parseValue( 0 );
		$parser->skipWhitespace();
		if ( $parser->position !== $parser->length ) {
			throw new UnexpectedValueException( 'json_trailing_bytes' );
		}
		return $value;
	}

	private function parseValue( $depth ) {
		if ( $depth > $this->max_depth ) {
			throw new LengthException( 'json_depth_exceeded' );
		}
		$this->skipWhitespace();
		if ( $this->position >= $this->length ) {
			throw new UnexpectedValueException( 'json_value_missing' );
		}
		$char = $this->source[ $this->position ];
		if ( '{' === $char ) {
			return $this->parseObject( $depth + 1 );
		}
		if ( '[' === $char ) {
			return $this->parseArray( $depth + 1 );
		}
		if ( '"' === $char ) {
			return $this->parseString();
		}
		if ( '-' === $char || ctype_digit( $char ) ) {
			return $this->parseNumber();
		}
		foreach ( array( 'true' => true, 'false' => false, 'null' => null ) as $literal => $value ) {
			if ( 0 === substr_compare( $this->source, $literal, $this->position, strlen( $literal ) ) ) {
				$this->position += strlen( $literal );
				return $value;
			}
		}
		throw new UnexpectedValueException( 'json_token_invalid' );
	}

	private function parseObject( $depth ) {
		$this->position++;
		$result = array();
		$this->skipWhitespace();
		if ( $this->consume( '}' ) ) {
			return new stdClass();
		}
		while ( true ) {
			$this->skipWhitespace();
			if ( $this->position >= $this->length || '"' !== $this->source[ $this->position ] ) {
				throw new UnexpectedValueException( 'json_object_key_invalid' );
			}
			$key = $this->parseString();
			if ( array_key_exists( $key, $result ) ) {
				throw new UnexpectedValueException( 'json_duplicate_key' );
			}
			$this->skipWhitespace();
			if ( ! $this->consume( ':' ) ) {
				throw new UnexpectedValueException( 'json_colon_missing' );
			}
			$result[ $key ] = $this->parseValue( $depth );
			$this->skipWhitespace();
			if ( $this->consume( '}' ) ) {
				break;
			}
			if ( ! $this->consume( ',' ) ) {
				throw new UnexpectedValueException( 'json_object_separator_invalid' );
			}
		}
		return $result;
	}

	private function parseArray( $depth ) {
		$this->position++;
		$result = array();
		$this->skipWhitespace();
		if ( $this->consume( ']' ) ) {
			return $result;
		}
		while ( true ) {
			$result[] = $this->parseValue( $depth );
			$this->skipWhitespace();
			if ( $this->consume( ']' ) ) {
				break;
			}
			if ( ! $this->consume( ',' ) ) {
				throw new UnexpectedValueException( 'json_array_separator_invalid' );
			}
		}
		return $result;
	}

	private function parseString() {
		$start = $this->position;
		$this->position++;
		$escaped = false;
		while ( $this->position < $this->length ) {
			$char = $this->source[ $this->position ];
			if ( ! $escaped && '"' === $char ) {
				$this->position++;
				$encoded = substr( $this->source, $start, $this->position - $start );
				$decoded = json_decode( $encoded, true, 2, JSON_THROW_ON_ERROR );
				if ( strlen( $decoded ) > $this->max_string ) {
					throw new LengthException( 'json_string_too_large' );
				}
				return $decoded;
			}
			if ( ! $escaped && ord( $char ) < 0x20 ) {
				throw new UnexpectedValueException( 'json_control_character' );
			}
			if ( ! $escaped && '\\' === $char ) {
				$escaped = true;
			} else {
				$escaped = false;
			}
			$this->position++;
		}
		throw new UnexpectedValueException( 'json_string_unterminated' );
	}

	private function parseNumber() {
		$remaining = substr( $this->source, $this->position );
		if ( ! preg_match( '/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/', $remaining, $matches ) ) {
			throw new UnexpectedValueException( 'json_number_invalid' );
		}
		$token = $matches[0];
		$this->position += strlen( $token );
		if ( false !== strpbrk( $token, '.eE' ) ) {
			$value = (float) $token;
			if ( ! is_finite( $value ) ) {
				throw new UnexpectedValueException( 'json_number_unsafe' );
			}
			if ( 0.0 === $value && '-' === $token[0] ) {
				throw new UnexpectedValueException( 'json_negative_zero' );
			}
			return $value;
		}
		$negative = 0 === strpos( $token, '-' );
		$digits = ltrim( $token, '-' );
		if ( $negative && '0' === $digits ) { throw new UnexpectedValueException( 'json_negative_zero' ); }
		$limit = '9007199254740991';
		if ( strlen( $digits ) > strlen( $limit ) || ( strlen( $digits ) === strlen( $limit ) && strcmp( $digits, $limit ) > 0 ) ) {
			throw new UnexpectedValueException( 'json_number_unsafe' );
		}
		$value = (int) $digits;
		return $negative ? -$value : $value;
	}

	private function skipWhitespace() {
		while ( $this->position < $this->length && false !== strpos( " \t\r\n", $this->source[ $this->position ] ) ) {
			$this->position++;
		}
	}

	private function consume( $character ) {
		if ( $this->position < $this->length && $character === $this->source[ $this->position ] ) {
			$this->position++;
			return true;
		}
		return false;
	}
}
