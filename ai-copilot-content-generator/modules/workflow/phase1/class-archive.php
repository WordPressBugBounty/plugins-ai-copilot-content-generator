<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Archive {
	public static function fromStandaloneJson( $bytes, $operation_id = 'workflow.import.preflight' ) {
		$limits = WaicWorkflowPhase1Config::hardLimits();
		try {
			$workflow = WaicWorkflowPhase1Json::decodeStrict(
				$bytes,
				$limits['workflow_bytes'],
				$limits['json_depth'],
				$limits['scalar_bytes']
			);
		} catch ( Exception $exception ) {
			return WaicWorkflowPhase1Result::blocked(
				$operation_id,
				'manifest_schema_invalid',
				__( 'The workflow JSON is invalid.', 'ai-copilot-content-generator' ),
				array( self::issue( 'manifest_schema_invalid', 'workflow', 'select_valid_workflow' ) ),
				array(),
				400
			);
		}
		$id = isset( $workflow['id'] ) && is_string( $workflow['id'] ) ? $workflow['id'] : '';
		if ( ! preg_match( '/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/', $id ) ) {
			return WaicWorkflowPhase1Result::blocked(
				$operation_id,
				'manifest_schema_invalid',
				__( 'The workflow identifier is invalid.', 'ai-copilot-content-generator' ),
				array( self::issue( 'manifest_schema_invalid', 'id', 'select_valid_workflow' ) )
			);
		}
		$path = 'workflows/' . $id . '.json';
		$canonical = WaicWorkflowPhase1Canonicalizer::encode( $workflow );
		$manifest = array(
			'schema'      => 'aiwu.workflow-pack.v1',
			'id'          => $id . '.local',
			'name'        => isset( $workflow['name'] ) ? (string) $workflow['name'] : $id,
			'version'     => '0.0.0-local',
			'description' => '',
			'requires'    => array(),
			'risk'        => array(),
			'workflows'   => array( array( 'id' => $id, 'path' => $path ) ),
			'files'       => array(
				array(
					'path'         => $path,
					'media_type'   => 'application/json',
					'size'         => strlen( $canonical ),
					'sha256'       => 'sha256:' . hash( 'sha256', $canonical ),
				),
			),
		);
		return array( 'manifest' => $manifest, 'members' => array( $path => $canonical ) );
	}

	public static function fromZip( $path, $operation_id = 'workflow.import.preflight' ) {
		$limits = WaicWorkflowPhase1Config::hardLimits();
		if ( ! class_exists( 'ZipArchive' ) ) {
			return self::error( $operation_id, 'archive_reader_unavailable', 'install_zip_extension', 503 );
		}
		if ( ! is_string( $path ) || ! is_file( $path ) || filesize( $path ) > $limits['archive_compressed'] ) {
			return self::error( $operation_id, 'archive_too_large', 'select_smaller_archive', 413 );
		}
		if ( self::hasUnsupportedContainer( $path ) ) {
			return self::error( $operation_id, 'archive_container_unsupported', 'select_standard_zip_archive', 415 );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $path, ZipArchive::RDONLY ) ) {
			return self::error( $operation_id, 'archive_path_invalid', 'select_valid_archive', 400 );
		}
		try {
			if ( $zip->numFiles < 1 || $zip->numFiles > $limits['archive_entries'] ) {
				return self::error( $operation_id, 'archive_too_large', 'select_smaller_archive', 413 );
			}
			$seen = array();
			$members = array();
			$total_size = 0;
			$total_compressed = 0;
			for ( $index = 0; $index < $zip->numFiles; $index++ ) {
				$stat = $zip->statIndex( $index, ZipArchive::FL_UNCHANGED );
				if ( ! is_array( $stat ) || ! isset( $stat['name'], $stat['size'], $stat['comp_size'] ) ) {
					return self::error( $operation_id, 'archive_path_invalid', 'select_valid_archive' );
				}
				$name = self::normalizePath( $stat['name'], $limits );
				if ( false === $name ) {
					return self::error( $operation_id, 'archive_path_invalid', 'select_valid_archive' );
				}
				if ( '/' === substr( $name, -1 ) ) {
					continue;
				}
				$key = strtolower( $name );
				if ( isset( $seen[ $key ] ) ) {
					return self::error( $operation_id, 'archive_path_invalid', 'remove_duplicate_archive_member' );
				}
				$seen[ $key ] = true;
				$size = (int) $stat['size'];
				$compressed = (int) $stat['comp_size'];
				if ( $size < 0 || $size > $limits['entry_bytes'] || $compressed < 0 ) {
					return self::error( $operation_id, 'archive_too_large', 'select_smaller_archive', 413 );
				}
				if ( ( 0 === $compressed && $size > 0 ) || ( $compressed > 0 && $size / $compressed > $limits['compression_ratio'] ) ) {
					return self::error( $operation_id, 'archive_too_large', 'select_smaller_archive', 413 );
				}
				$total_size += $size;
				$total_compressed += max( 1, $compressed );
				if ( $total_size > $limits['archive_uncompressed'] || $total_size / $total_compressed > $limits['compression_ratio'] ) {
					return self::error( $operation_id, 'archive_too_large', 'select_smaller_archive', 413 );
				}
				if ( self::isLink( $zip, $index ) || ! empty( $stat['encryption_method'] ) ) {
					return self::error( $operation_id, 'archive_path_invalid', 'remove_unsupported_archive_member' );
				}
				$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				if ( 'json' !== $extension ) {
					return self::error( $operation_id, 'archive_executable_forbidden', 'remove_executable_content' );
				}
				$stream = $zip->getStream( $stat['name'] );
				if ( ! is_resource( $stream ) ) {
					return self::error( $operation_id, 'archive_path_invalid', 'select_valid_archive' );
				}
				$bytes = stream_get_contents( $stream, $size + 1 );
				fclose( $stream );
				if ( false === $bytes || strlen( $bytes ) !== $size ) {
					return self::error( $operation_id, 'archive_path_invalid', 'select_valid_archive' );
				}
				if ( ! preg_match( '/^\s*[\[{]/', $bytes ) || preg_match( '/^\s*(?:<\?(?:php|=)?|#!|MZ|\x7fELF|%PDF-|PK\x03\x04)/i', $bytes ) ) {
					return self::error( $operation_id, 'archive_executable_forbidden', 'remove_executable_content' );
				}
				$members[ $name ] = $bytes;
			}
			if ( ! isset( $members['aiwu-workflow-pack.json'] ) || strlen( $members['aiwu-workflow-pack.json'] ) > $limits['manifest_bytes'] ) {
				return self::error( $operation_id, 'manifest_schema_invalid', 'add_valid_manifest' );
			}
			try {
				$manifest = WaicWorkflowPhase1Json::decodeStrict(
					$members['aiwu-workflow-pack.json'],
					$limits['manifest_bytes'],
					$limits['json_depth'],
					$limits['scalar_bytes']
				);
			} catch ( Exception $exception ) {
				return self::error( $operation_id, 'manifest_schema_invalid', 'add_valid_manifest', 400 );
			}
			return array( 'manifest' => $manifest, 'members' => $members );
		} finally {
			$zip->close();
		}
	}

	private static function normalizePath( $path, array $limits ) {
		$path = str_replace( '\\', '/', (string) $path );
		if ( '' === $path || strlen( $path ) > $limits['path_bytes'] || 1 !== preg_match( '//u', $path ) ) {
			return false;
		}
		if ( ! preg_match( '#^[a-z0-9][a-z0-9._-]*(?:/[a-z0-9][a-z0-9._-]*)*/?$#', $path ) ) {
			return false;
		}
		if ( '/' === $path[0] || preg_match( '#^(?:[a-z]:|//|[a-z][a-z0-9+.-]*:)#i', $path ) || false !== strpos( $path, "\0" ) ) {
			return false;
		}
		$segments = explode( '/', $path );
		if ( count( $segments ) > $limits['path_depth'] + 1 ) {
			return false;
		}
		foreach ( $segments as $segment ) {
			if ( '' === $segment && '/' !== substr( $path, -1 ) ) {
				return false;
			}
			if ( in_array( $segment, array( '.', '..' ), true ) || strlen( $segment ) > $limits['path_segment_bytes'] ) {
				return false;
			}
			if ( preg_match( '/^(?:\.env|\.htaccess|web\.config|id_rsa)$/i', $segment ) ) {
				return false;
			}
		}
		return $path;
	}

	private static function isLink( ZipArchive $zip, $index ) {
		$opsys = 0;
		$attributes = 0;
		if ( ! $zip->getExternalAttributesIndex( $index, $opsys, $attributes ) ) {
			return false;
		}
		$mode = ( $attributes >> 16 ) & 0xF000;
		return 0xA000 === $mode || 0x6000 === $mode;
	}

	private static function hasUnsupportedContainer( $path ) {
		$size = filesize( $path );
		if ( false === $size || $size < 22 ) { return true; }
		$length = min( 131072, $size );
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) { return true; }
		try {
			if ( 0 !== fseek( $handle, $size - $length ) ) { return true; }
			$tail = fread( $handle, $length );
		} finally { fclose( $handle ); }
		if ( ! is_string( $tail ) || false !== strpos( $tail, "PK\x06\x06" ) || false !== strpos( $tail, "PK\x06\x07" ) ) { return true; }
		$position = strrpos( $tail, "PK\x05\x06" );
		if ( false === $position || strlen( $tail ) - $position < 22 ) { return true; }
		$record = substr( $tail, $position, 22 );
		$disk = unpack( 'vdisk/vcentral_disk/ventries_disk/ventries_total/Vcentral_size/Vcentral_offset/vcomment_length', substr( $record, 4 ) );
		if ( ! is_array( $disk ) || 0 !== $disk['disk'] || 0 !== $disk['central_disk'] || $disk['entries_disk'] !== $disk['entries_total'] ) { return true; }
		return 0xffff === $disk['entries_total'] || 0xffffffff === $disk['central_size'] || 0xffffffff === $disk['central_offset'];
	}

	private static function error( $operation_id, $code, $remediation, $status = 422 ) {
		return WaicWorkflowPhase1Result::blocked(
			$operation_id,
			$code,
			__( 'The workflow archive could not be accepted.', 'ai-copilot-content-generator' ),
			array( self::issue( $code, 'archive', $remediation ) ),
			array(),
			$status
		);
	}

	private static function issue( $code, $field, $remediation ) {
		return array(
			'severity'    => 'error',
			'code'        => $code,
			'context'     => array( 'field' => $field ),
			'remediation' => $remediation,
		);
	}
}
