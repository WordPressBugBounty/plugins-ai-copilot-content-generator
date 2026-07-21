<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Closed Phase 1 portable-block registry.
 *
 * A legacy block is not portable merely because a PHP file exists. Each
 * eligible block needs a pinned descriptor and implementation fingerprint.
 */
final class WaicWorkflowPhase1DescriptorRegistry {
	const REVISION = 'aiwu.workflow-descriptors.v1.0.0';

	private static $descriptors = array(
		'trigger:sy_manual' => array(
			'type'                   => 'trigger',
			'code'                   => 'sy_manual',
			'implementation'         => 'blocks/triggers/sy_manual.php',
			'implementation_sha256'  => 'sha256:9d8dba9d9a711f4b7f27d788db6e5d085463c56bd45e18d2072af541f914e65d',
			'input_handles'          => array(),
			'output_handles'         => array( 'default' ),
			'settings'               => array(),
			'risk'                   => array(),
			'effects'                => array( 'workflow_state' ),
			'data_paths'             => array(),
			'requires'               => array(),
		),
		'logic:un_stop' => array(
			'type'                   => 'logic',
			'code'                   => 'un_stop',
			'implementation'         => 'blocks/logics/un_stop.php',
			'implementation_sha256'  => 'sha256:806fb4f6f82a12dc605f0c5e0b5c60b006b235be0a59564492f048df1a114911',
			'input_handles'          => array( 'default' ),
			'output_handles'         => array(),
			'settings'               => array(),
			'risk'                   => array(),
			'effects'                => array( 'workflow_state' ),
			'data_paths'             => array(),
			'requires'               => array(),
		),
	);

	public static function get( $type, $code ) {
		$key = (string) $type . ':' . (string) $code;
		if ( ! isset( self::$descriptors[ $key ] ) ) {
			return false;
		}
		$descriptor = self::$descriptors[ $key ];
		$path = dirname( __DIR__ ) . '/' . $descriptor['implementation'];
		if ( ! is_file( $path ) ) {
			return false;
		}
		$current = 'sha256:' . hash_file( 'sha256', $path );
		if ( ! hash_equals( $descriptor['implementation_sha256'], $current ) ) {
			return false;
		}
		$descriptor['id'] = $key;
		$descriptor['revision'] = self::REVISION;
		return $descriptor;
	}

	public static function all() {
		$result = array();
		foreach ( self::$descriptors as $key => $unused ) {
			list( $type, $code ) = explode( ':', $key, 2 );
			$descriptor = self::get( $type, $code );
			if ( false !== $descriptor ) {
				$result[ $key ] = $descriptor;
			}
		}
		ksort( $result, SORT_STRING );
		return $result;
	}

	public static function digest() {
		return WaicWorkflowPhase1Canonicalizer::digest(
			array( 'revision' => self::REVISION, 'descriptors' => self::$descriptors ),
			'descriptor-catalog',
			self::REVISION
		);
	}
}
