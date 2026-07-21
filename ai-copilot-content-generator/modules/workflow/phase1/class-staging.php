<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private filesystem adapter for bounded, non-authoritative workflow staging. */
final class WaicWorkflowPhase1Staging {
	public static function write( $hash, array $stage ) {
		if ( ! self::validHash( $hash ) ) {
			return false;
		}
		$root = self::root();
		if ( false === $root || ( ! is_dir( $root ) && ! wp_mkdir_p( $root ) ) ) {
			return false;
		}
		@chmod( $root, 0700 );
		$bytes = WaicWorkflowPhase1Canonicalizer::encode( $stage );
		if ( strlen( $bytes ) > 15 * MB_IN_BYTES ) {
			return false;
		}
		$path = $root . '/' . $hash . '.json';
		$temp = $path . '.' . bin2hex( random_bytes( 8 ) ) . '.tmp';
		$handle = @fopen( $temp, 'xb' );
		if ( false === $handle ) { return false; }
		$offset = 0;
		$complete = true;
		try {
			while ( $offset < strlen( $bytes ) ) {
				$written = fwrite( $handle, substr( $bytes, $offset ) );
				if ( false === $written || 0 === $written ) { $complete = false; break; }
				$offset += $written;
			}
			if ( $complete && ! fflush( $handle ) ) { $complete = false; }
		} finally {
			fclose( $handle );
		}
		if ( ! $complete ) { @unlink( $temp ); return false; }
		@chmod( $temp, 0600 );
		if ( ! @rename( $temp, $path ) ) {
			@unlink( $temp );
			return false;
		}
		@chmod( $path, 0600 );
		return true;
	}

	public static function readUpload( $path, $max_bytes ) {
		$max_bytes = max( 1, (int) $max_bytes );
		if ( ! is_string( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) { return false; }
		$size = filesize( $path );
		if ( false === $size || $size > $max_bytes ) { return false; }
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) { return false; }
		try { $bytes = stream_get_contents( $handle, $max_bytes + 1 ); } finally { fclose( $handle ); }
		return is_string( $bytes ) && strlen( $bytes ) === $size ? $bytes : false;
	}

	public static function read( $hash ) {
		if ( ! self::validHash( $hash ) ) {
			return false;
		}
		$root = self::root();
		if ( false === $root ) { return false; }
		$path = $root . '/' . $hash . '.json';
		if ( ! is_file( $path ) || filesize( $path ) > 15 * MB_IN_BYTES ) {
			return false;
		}
		try {
			return WaicWorkflowPhase1Json::decodeStrict( file_get_contents( $path ), 15 * MB_IN_BYTES, 64, 2 * MB_IN_BYTES );
		} catch ( Exception $error ) {
			return false;
		}
	}

	public static function delete( $hash ) {
		$root = self::root();
		return false !== $root && self::validHash( $hash ) && ( ! is_file( $root . '/' . $hash . '.json' ) || @unlink( $root . '/' . $hash . '.json' ) );
	}

	public static function cleanup( $limit = 100 ) {
		$root = self::root();
		$files = false !== $root && is_dir( $root ) ? glob( $root . '/*.{json,tmp}', GLOB_BRACE ) : array();
		$count = 0;
		foreach ( is_array( $files ) ? $files : array() as $path ) {
			if ( $count >= max( 1, (int) $limit ) ) {
				break;
			}
			$modified = filemtime( $path );
			if ( false !== $modified && $modified < time() - 15 * MINUTE_IN_SECONDS && @unlink( $path ) ) {
				$count++;
			}
		}
		return $count;
	}

	private static function root() {
		$temp = wp_normalize_path( get_temp_dir() );
		$blocked = array( wp_normalize_path( ABSPATH ), wp_normalize_path( WP_CONTENT_DIR ) );
		foreach ( $blocked as $prefix ) {
			if ( 0 === strpos( trailingslashit( strtolower( $temp ) ), trailingslashit( strtolower( $prefix ) ) ) ) { return false; }
		}
		return untrailingslashit( $temp ) . '/aiwu-workflow-phase1/site-' . get_current_blog_id();
	}

	private static function validHash( $hash ) {
		return is_string( $hash ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $hash );
	}
}
