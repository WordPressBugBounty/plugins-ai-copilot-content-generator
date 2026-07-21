<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0PathPolicy {
	private $root;

	public function __construct( $immutable_root ) {
		$root = realpath( $immutable_root );
		if ( false === $root || ! is_dir( $root ) ) {
			throw new InvalidArgumentException( 'Workflow built-in root is unavailable.' );
		}
		$this->root = rtrim( wp_normalize_path( $root ), '/' ) . '/';
	}

	public function resolvePhp( $relative_path, $expected_sha256 ) {
		if ( ! is_string( $relative_path ) || ! preg_match( '#^[a-z0-9][a-z0-9_/-]*\.php$#', $relative_path ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CODE-001', 'workflow_code_path_rejected', 'disable_workflow' );
		}
		if ( false !== strpos( $relative_path, '..' ) || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $relative_path ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CODE-001', 'workflow_code_path_rejected', 'disable_workflow' );
		}

		$unresolved = $this->root . ltrim( wp_normalize_path( $relative_path ), '/' );
		if ( $this->containsSymlink( $unresolved ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CODE-001', 'workflow_code_symlink_rejected', 'disable_workflow' );
		}
		$candidate = realpath( $unresolved );
		if ( false === $candidate || ! is_file( $candidate ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CODE-001', 'workflow_code_missing', 'disable_workflow' );
		}
		$normalized = wp_normalize_path( $candidate );
		if ( 0 !== strpos( $normalized, $this->root ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CODE-001', 'workflow_code_outside_builtin_root', 'disable_workflow' );
		}

		$actual = hash_file( 'sha256', $candidate );
		$expected_sha256 = strtolower( preg_replace( '/^sha256:/', '', (string) $expected_sha256 ) );
		if ( 64 !== strlen( $expected_sha256 ) || ! hash_equals( $expected_sha256, $actual ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-DESC-002', 'workflow_implementation_drift', 'revalidate_workflow', array( 'revalidate' ) );
		}

		return array( 'path' => $candidate, 'sha256' => 'sha256:' . $actual );
	}

	private function containsSymlink( $path ) {
		$current = $path;
		while ( strlen( $current ) >= strlen( $this->root ) ) {
			if ( is_link( $current ) ) {
				return true;
			}
			$parent = dirname( $current );
			if ( $parent === $current ) {
				break;
			}
			$current = $parent;
		}
		return false;
	}
}
