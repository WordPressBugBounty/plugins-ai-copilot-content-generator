<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0DescriptorRegistry {
	const REVISION = 'workflow_descriptors.v1';

	private $path_policy;
	private $descriptors = array();

	public function __construct( WaicWorkflowPhase0PathPolicy $path_policy, array $manifest ) {
		$this->path_policy = $path_policy;
		$validation = self::validateManifest( $manifest );
		if ( 'WF-P0-DESC-000' !== $validation->getCode() ) {
			throw new InvalidArgumentException( 'Workflow descriptor manifest is invalid.' );
		}
		$seen = array();
		foreach ( $manifest as $descriptor ) {
			$id = isset( $descriptor['id'] ) ? strtolower( (string) $descriptor['id'] ) : '';
			if ( ! preg_match( '/^(trigger|logic|action):[a-z0-9_]{2,80}$/', $id ) || isset( $seen[ $id ] ) ) {
				throw new InvalidArgumentException( 'Workflow descriptor identity is invalid or duplicated.' );
			}
			if ( empty( $descriptor['relative_path'] ) || empty( $descriptor['sha256'] ) || empty( $descriptor['effects'] ) ) {
				throw new InvalidArgumentException( 'Workflow descriptor is incomplete.' );
			}
			$descriptor['id'] = $id;
			$descriptor['effects'] = array_values( array_unique( array_map( 'sanitize_key', (array) $descriptor['effects'] ) ) );
			$descriptor['sensitive_fields'] = array_values( array_unique( array_map( 'sanitize_key', isset( $descriptor['sensitive_fields'] ) ? (array) $descriptor['sensitive_fields'] : array() ) ) );
			$this->descriptors[ $id ] = $descriptor;
			$seen[ $id ] = true;
		}
	}

	public static function validateManifest( array $manifest ) {
		$seen = array();
		foreach ( $manifest as $descriptor ) {
			$id = isset( $descriptor['id'] ) ? (string) $descriptor['id'] : '';
			$canonical_id = strtolower( $id );
			if ( ! preg_match( '/^(trigger|logic|action):[a-z0-9_]{2,80}$/', $canonical_id ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-DESC-001', 'workflow_descriptor_invalid', 'disable_workflow' );
			}
			if ( isset( $seen[ $canonical_id ] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-DESC-003', 'workflow_descriptor_shadowed', 'disable_workflow' );
			}
			if ( $id !== $canonical_id ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-DESC-003', 'workflow_descriptor_case_collision', 'disable_workflow' );
			}
			if ( empty( $descriptor['relative_path'] ) || empty( $descriptor['sha256'] ) || empty( $descriptor['effects'] ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-DESC-001', 'workflow_descriptor_incomplete', 'disable_workflow' );
			}
			$seen[ $canonical_id ] = true;
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-DESC-000', 'workflow_descriptor_manifest_valid' );
	}

	public function resolve( $type, $code ) {
		$id = sanitize_key( (string) $type ) . ':' . sanitize_key( (string) $code );
		if ( ! isset( $this->descriptors[ $id ] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-DESC-001', 'workflow_descriptor_unknown', 'disable_workflow' );
		}
		$descriptor = $this->descriptors[ $id ];
		$resolved = $this->path_policy->resolvePhp( $descriptor['relative_path'], $descriptor['sha256'] );
		if ( $resolved instanceof WaicWorkflowPhase0Result ) {
			return $resolved;
		}
		$descriptor['resolved_path'] = $resolved['path'];
		$descriptor['fingerprint'] = $this->fingerprint( $descriptor );
		return $descriptor;
	}

	public function digest() {
		$canonical = array();
		foreach ( $this->descriptors as $id => $descriptor ) {
			$canonical[ $id ] = array(
				'relative_path'    => $descriptor['relative_path'],
				'sha256'          => strtolower( $descriptor['sha256'] ),
				'effects'         => $descriptor['effects'],
				'sensitive_fields'=> $descriptor['sensitive_fields'],
			);
		}
		ksort( $canonical );
		return 'sha256:' . hash( 'sha256', wp_json_encode( $canonical, JSON_UNESCAPED_SLASHES ) );
	}

	public function count() {
		return count( $this->descriptors );
	}

	private function fingerprint( array $descriptor ) {
		$preimage = array(
			self::REVISION,
			$descriptor['id'],
			strtolower( preg_replace( '/^sha256:/', '', $descriptor['sha256'] ) ),
			$descriptor['effects'],
			$descriptor['sensitive_fields'],
		);
		return 'sha256:' . hash( 'sha256', wp_json_encode( $preimage, JSON_UNESCAPED_SLASHES ) );
	}
}
