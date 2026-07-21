<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Validator {
	const REVISION = 'aiwu.workflow-validator.v1';
	const POLICY_REVISION = 'aiwu.workflow-policy.v1';

	private $limits;

	public function __construct() {
		$this->limits = WaicWorkflowPhase1Config::hardLimits();
	}

	public function validatePack( array $pack, $provenance = 'local_unsigned', $operation_id = 'workflow.import.preflight' ) {
		$manifest = isset( $pack['manifest'] ) && is_array( $pack['manifest'] ) ? $pack['manifest'] : array();
		$members = isset( $pack['members'] ) && is_array( $pack['members'] ) ? $pack['members'] : array();
		$issues = $this->validateManifest( $manifest, $members );
		$workflows = array();
		$risk = array();
		$effects = array();
		$data_paths = array();
		$workflow_digests = array();
		if ( empty( $issues ) ) {
			foreach ( $manifest['workflows'] as $reference ) {
				$path = $reference['path'];
				try {
					$workflow = WaicWorkflowPhase1Json::decodeStrict(
						$members[ $path ],
						$this->limits['workflow_bytes'],
						$this->limits['json_depth'],
						$this->limits['scalar_bytes']
					);
				} catch ( Exception $exception ) {
					$issues[] = $this->issue( 'manifest_schema_invalid', 'workflow', 'select_valid_workflow' );
					continue;
				}
				$validated = $this->validateWorkflow( $workflow, $provenance );
				$issues = array_merge( $issues, $validated['issues'] );
				$normalized_workflow = isset( $validated['workflow'] ) ? $validated['workflow'] : $workflow;
				$workflows[] = $normalized_workflow;
				$risk = array_merge( $risk, $validated['risk'] );
				$effects = array_merge( $effects, $validated['effects'] );
				$data_paths = array_merge( $data_paths, $validated['data_paths'] );
				if ( isset( $normalized_workflow['id'] ) && is_string( $normalized_workflow['id'] ) && '' !== $normalized_workflow['id'] ) {
					$workflow_digests[ $normalized_workflow['id'] ] = WaicWorkflowPhase1Canonicalizer::digest( $normalized_workflow, 'workflow', self::REVISION );
				}
			}
		}
		if ( ! empty( $issues ) ) {
			return WaicWorkflowPhase1Result::blocked(
				$operation_id,
				$issues[0]['code'],
				__( 'The workflow pack is blocked by server validation.', 'ai-copilot-content-generator' ),
				$issues,
				array( 'issues_count' => count( $issues ) )
			);
		}
		$risk = array_values( array_unique( $risk ) );
		sort( $risk, SORT_STRING );
		$effects = array_values( array_unique( $effects ) );
		sort( $effects, SORT_STRING );
		usort( $data_paths, function ( $left, $right ) { return strcmp( WaicWorkflowPhase1Canonicalizer::encode( $left ), WaicWorkflowPhase1Canonicalizer::encode( $right ) ); } );
		$dependencies = $this->resolveDependencies( isset( $manifest['requires'] ) ? $manifest['requires'] : array() );
		return array(
			'manifest'          => $manifest,
			'workflows'         => $workflows,
			'artifact_digest'    => WaicWorkflowPhase1Canonicalizer::digest( array( 'manifest' => $manifest, 'members' => $members ), 'artifact', self::REVISION ),
			'workflow_digests'   => $workflow_digests,
			'risk'               => $risk,
			'effects'            => $effects,
			'data_paths'         => $data_paths,
			'dependencies'       => $dependencies,
			'descriptor_revision'=> self::descriptorDigest(),
			'policy_revision'    => WaicWorkflowPhase1Canonicalizer::digest( array( 'revision' => self::POLICY_REVISION ), 'policy-input', self::POLICY_REVISION ),
			'validator_revision' => self::REVISION,
			'provenance'         => sanitize_key( $provenance ),
		);
	}

	public function validateWorkflow( $workflow, $provenance = 'local_unsigned' ) {
		$issues = array();
		$risk = array();
		$effects = array();
		$data_paths = array();
		if ( ! is_array( $workflow ) ) {
			return array( 'issues' => array( $this->issue( 'manifest_schema_invalid', 'workflow', 'select_valid_workflow' ) ), 'risk' => array(), 'effects' => array(), 'data_paths' => array(), 'workflow' => array() );
		}
		$this->requireClosedKeys( $workflow, array( 'schema', 'id', 'name', 'description', 'nodes', 'edges', 'settings' ), $issues, 'workflow' );
		if ( 'aiwu.workflow.v1' !== ( isset( $workflow['schema'] ) ? $workflow['schema'] : '' ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'schema', 'select_supported_schema' ); }
		if ( ! isset( $workflow['id'] ) || ! self::validId( $workflow['id'] ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'id', 'select_valid_workflow' ); }
		if ( ! isset( $workflow['name'] ) || ! is_string( $workflow['name'] ) || strlen( $workflow['name'] ) > 200 ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'name', 'repair_workflow_metadata' ); }
		if ( ! isset( $workflow['description'] ) || ! is_string( $workflow['description'] ) || strlen( $workflow['description'] ) > 4000 ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'description', 'repair_workflow_metadata' ); }
		$root_settings = self::objectToArray( isset( $workflow['settings'] ) ? $workflow['settings'] : null );
		if ( false === $root_settings || ! empty( $root_settings ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'settings', 'remove_unknown_fields' ); }

		$nodes = isset( $workflow['nodes'] ) && is_array( $workflow['nodes'] ) ? $workflow['nodes'] : array();
		$edges = isset( $workflow['edges'] ) && is_array( $workflow['edges'] ) ? $workflow['edges'] : array();
		if ( count( $nodes ) < 1 || count( $nodes ) > $this->limits['nodes'] || count( $edges ) > $this->limits['edges'] ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'reduce_workflow_size' ); }
		$normalized_nodes = array();
		$node_ids = array();
		$descriptors = array();
		$trigger_ids = array();
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'repair_workflow_graph' ); continue; }
			$this->requireClosedKeys( $node, array( 'id', 'type', 'code', 'position', 'settings' ), $issues, 'node' );
			$id = isset( $node['id'] ) && is_string( $node['id'] ) ? $node['id'] : '';
			$type = isset( $node['type'] ) && is_string( $node['type'] ) ? $node['type'] : '';
			$code = isset( $node['code'] ) && is_string( $node['code'] ) ? $node['code'] : '';
			if ( ! self::validNodeId( $id ) || isset( $node_ids[ $id ] ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'node_id', 'repair_workflow_graph', array( 'node_id' => $id ) ); continue; }
			$node_ids[ $id ] = true;
			if ( ! in_array( $type, array( 'trigger', 'logic', 'action' ), true ) || ! preg_match( '/^[a-z0-9_]{2,80}$/', $code ) ) { $issues[] = $this->issue( 'unknown_block', 'code', 'select_supported_block', array( 'node_id' => $id ) ); continue; }
			$descriptor = self::descriptor( $type, $code );
			if ( false === $descriptor ) { $issues[] = $this->issue( 'descriptor_drift', 'code', 'select_supported_block', array( 'node_id' => $id ) ); continue; }
			$position = self::objectToArray( isset( $node['position'] ) ? $node['position'] : null );
			if ( false === $position || 2 !== count( $position ) || array() !== array_diff( array_keys( $position ), array( 'x', 'y' ) ) || ! isset( $position['x'], $position['y'] ) || ! self::boundedCoordinate( $position['x'] ) || ! self::boundedCoordinate( $position['y'] ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'position', 'repair_workflow_graph', array( 'node_id' => $id ) );
				$position = array( 'x' => 0, 'y' => 0 );
			}
			$settings = self::objectToArray( isset( $node['settings'] ) ? $node['settings'] : null );
			if ( false === $settings || ! $this->validateDescriptorSettings( $settings, $descriptor ) ) { $issues[] = $this->issue( 'setting_contract_invalid', 'settings', 'repair_block_settings', array( 'node_id' => $id ) ); $settings = array(); }
			$settings_json = WaicWorkflowPhase1Canonicalizer::encode( (object) $settings );
			if ( self::containsSecret( $settings_json ) ) { $issues[] = $this->issue( 'credential_in_artifact', 'settings', 'remove_sensitive_value', array( 'node_id' => $id ) ); }
			if ( 'trigger' === $type ) { $trigger_ids[] = $id; }
			$descriptors[ $id ] = $descriptor;
			$risk = array_merge( $risk, $descriptor['risk'] );
			$effects = array_merge( $effects, $descriptor['effects'] );
			foreach ( $descriptor['data_paths'] as $path ) { $data_paths[] = array( 'node_id' => $id, 'path' => $path ); }
			$normalized_nodes[] = array( 'id' => $id, 'type' => $type, 'code' => $code, 'position' => array( 'x' => $position['x'], 'y' => $position['y'] ), 'settings' => (object) $settings );
		}
		if ( 1 !== count( $trigger_ids ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'select_single_trigger' ); }

		$normalized_edges = array();
		$edge_ids = array();
		$adjacency = array();
		foreach ( $edges as $edge ) {
			if ( ! is_array( $edge ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'edges', 'repair_workflow_graph' ); continue; }
			$this->requireClosedKeys( $edge, array( 'id', 'source', 'target', 'source_handle', 'target_handle' ), $issues, 'edge' );
			$id = isset( $edge['id'] ) && is_string( $edge['id'] ) ? $edge['id'] : '';
			$source = isset( $edge['source'] ) && is_string( $edge['source'] ) ? $edge['source'] : '';
			$target = isset( $edge['target'] ) && is_string( $edge['target'] ) ? $edge['target'] : '';
			$source_handle = isset( $edge['source_handle'] ) && is_string( $edge['source_handle'] ) ? $edge['source_handle'] : '';
			$target_handle = isset( $edge['target_handle'] ) && is_string( $edge['target_handle'] ) ? $edge['target_handle'] : '';
			if ( ! self::validEdgeId( $id ) || isset( $edge_ids[ $id ] ) || ! isset( $node_ids[ $source ], $node_ids[ $target ], $descriptors[ $source ], $descriptors[ $target ] ) || $source === $target ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'edges', 'repair_workflow_graph', array( 'edge_id' => $id ) ); continue;
			}
			if ( ! in_array( $source_handle, $descriptors[ $source ]['output_handles'], true ) || ! in_array( $target_handle, $descriptors[ $target ]['input_handles'], true ) ) {
				$issues[] = $this->issue( 'handle_contract_invalid', 'edges', 'repair_workflow_handles', array( 'edge_id' => $id ) ); continue;
			}
			$edge_ids[ $id ] = true;
			$adjacency[ $source ][] = $target;
			$normalized_edges[] = array( 'id' => $id, 'source' => $source, 'target' => $target, 'source_handle' => $source_handle, 'target_handle' => $target_handle );
		}
		if ( $this->hasCycle( array_keys( $node_ids ), $adjacency ) ) { $issues[] = $this->issue( 'risk_blocked', 'edges', 'remove_unsupported_cycle' ); }
		if ( 1 === count( $trigger_ids ) && ! $this->allReachable( $trigger_ids[0], array_keys( $node_ids ), $adjacency ) ) { $issues[] = $this->issue( 'graph_unreachable', 'edges', 'connect_all_workflow_nodes' ); }
		$risk = array_values( array_unique( $risk ) );
		if ( 'ai_generated' === $provenance ) {
			$forbidden = array( 'content_delete', 'user_delete', 'commerce_write', 'db_write', 'raw_sql', 'filesystem_write', 'privilege_change', 'php_code', 'public_endpoint', 'dynamic_destination' );
			if ( array_intersect( $forbidden, $risk ) ) { $issues[] = $this->issue( 'risk_blocked', 'nodes', 'remove_forbidden_ai_effect' ); }
		}
		usort( $normalized_nodes, function ( $left, $right ) { return strcmp( $left['id'], $right['id'] ); } );
		usort( $normalized_edges, function ( $left, $right ) { return strcmp( $left['id'], $right['id'] ); } );
		$normalized = array( 'schema' => 'aiwu.workflow.v1', 'id' => isset( $workflow['id'] ) ? $workflow['id'] : '', 'name' => isset( $workflow['name'] ) ? $workflow['name'] : '', 'description' => isset( $workflow['description'] ) ? $workflow['description'] : '', 'nodes' => $normalized_nodes, 'edges' => $normalized_edges, 'settings' => new stdClass() );
		return array( 'issues' => $issues, 'risk' => $risk, 'effects' => array_values( array_unique( $effects ) ), 'data_paths' => $data_paths, 'workflow' => $normalized );
	}

	private function validateWorkflowLegacy( $workflow, $provenance = 'local_unsigned' ) {
		$issues = array();
		$risk = array();
		$effects = array();
		$data_paths = array();
		if ( ! is_array( $workflow ) ) {
			return array( 'issues' => array( $this->issue( 'manifest_schema_invalid', 'workflow', 'select_valid_workflow' ) ), 'risk' => array(), 'effects' => array(), 'data_paths' => array() );
		}
		$this->requireClosedKeys( $workflow, array( 'schema', 'id', 'name', 'description', 'nodes', 'edges', 'settings', 'provenance' ), $issues, 'workflow' );
		if ( 'aiwu.workflow.v1' !== ( isset( $workflow['schema'] ) ? $workflow['schema'] : '' ) ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'schema', 'select_supported_schema' );
		}
		if ( ! isset( $workflow['id'] ) || ! self::validId( $workflow['id'] ) ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'id', 'select_valid_workflow' );
		}
		$nodes = isset( $workflow['nodes'] ) && is_array( $workflow['nodes'] ) ? $workflow['nodes'] : array();
		$edges = isset( $workflow['edges'] ) && is_array( $workflow['edges'] ) ? $workflow['edges'] : array();
		if ( count( $nodes ) < 1 || count( $nodes ) > $this->limits['nodes'] || count( $edges ) > $this->limits['edges'] ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'reduce_workflow_size' );
		}
		$node_ids = array();
		$trigger_count = 0;
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'repair_workflow_graph' );
				continue;
			}
			$this->requireClosedKeys( $node, array( 'id', 'type', 'code', 'position', 'settings' ), $issues, 'node' );
			$id = isset( $node['id'] ) ? (string) $node['id'] : '';
			$type = isset( $node['type'] ) ? sanitize_key( $node['type'] ) : '';
			$code = isset( $node['code'] ) ? sanitize_key( $node['code'] ) : '';
			if ( ! preg_match( '/^[a-z0-9][a-z0-9_.-]{0,63}$/', $id ) || isset( $node_ids[ $id ] ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'node_id', 'repair_workflow_graph', array( 'node_id' => $id ) );
				continue;
			}
			$node_ids[ $id ] = true;
			if ( ! in_array( $type, array( 'trigger', 'logic', 'action' ), true ) || ! preg_match( '/^[a-z0-9_]{2,80}$/', $code ) ) {
				$issues[] = $this->issue( 'unknown_block', 'code', 'select_supported_block', array( 'node_id' => $id ) );
				continue;
			}
			if ( 'trigger' === $type ) {
				$trigger_count++;
			}
			$descriptor = self::descriptor( $type, $code );
			if ( false === $descriptor ) {
				$issues[] = $this->issue( 'unknown_block', 'code', 'select_supported_block', array( 'node_id' => $id ) );
				continue;
			}
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$settings_json = WaicWorkflowPhase1Canonicalizer::encode( $settings );
			if ( strlen( $settings_json ) > $this->limits['entry_bytes'] || self::containsSecret( $settings_json ) ) {
				$issues[] = $this->issue( 'credential_in_artifact', 'settings', 'remove_sensitive_value', array( 'node_id' => $id ) );
			}
			$risk = array_merge( $risk, $descriptor['risk'] );
			$effects = array_merge( $effects, $descriptor['effects'] );
			foreach ( $descriptor['data_paths'] as $path ) {
				$data_paths[] = array( 'node_id' => $id, 'path' => $path );
			}
		}
		if ( 1 !== $trigger_count ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'nodes', 'select_single_trigger' );
		}
		$edge_ids = array();
		$adjacency = array();
		foreach ( $edges as $edge ) {
			if ( ! is_array( $edge ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'edges', 'repair_workflow_graph' );
				continue;
			}
			$this->requireClosedKeys( $edge, array( 'id', 'source', 'target', 'source_handle', 'target_handle' ), $issues, 'edge' );
			$id = isset( $edge['id'] ) ? (string) $edge['id'] : '';
			$source = isset( $edge['source'] ) ? (string) $edge['source'] : '';
			$target = isset( $edge['target'] ) ? (string) $edge['target'] : '';
			if ( ! preg_match( '/^[a-z0-9][a-z0-9_.-]{0,63}$/', $id ) || isset( $edge_ids[ $id ] ) || ! isset( $node_ids[ $source ], $node_ids[ $target ] ) || $source === $target ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'edges', 'repair_workflow_graph', array( 'edge_id' => $id ) );
				continue;
			}
			$edge_ids[ $id ] = true;
			$adjacency[ $source ][] = $target;
		}
		if ( $this->hasCycle( array_keys( $node_ids ), $adjacency ) ) {
			$issues[] = $this->issue( 'risk_blocked', 'edges', 'remove_unsupported_cycle' );
		}
		if ( 'ai_generated' === $provenance ) {
			$forbidden = array( 'content_delete', 'user_delete', 'commerce_write', 'db_write', 'raw_sql', 'filesystem_write', 'privilege_change', 'php_code', 'public_endpoint', 'dynamic_destination' );
			if ( array_intersect( $forbidden, $risk ) ) {
				$issues[] = $this->issue( 'risk_blocked', 'nodes', 'remove_forbidden_ai_effect' );
			}
		}
		return array(
			'issues'    => $issues,
			'risk'      => array_values( array_unique( $risk ) ),
			'effects'   => array_values( array_unique( $effects ) ),
			'data_paths'=> $data_paths,
		);
	}

	private function validateManifest( array $manifest, array $members ) {
		$issues = array();
		$this->requireClosedKeys( $manifest, array( 'schema', 'id', 'name', 'version', 'description', 'requires', 'risk', 'workflows', 'files', 'extensions' ), $issues, 'manifest' );
		foreach ( array( 'schema', 'id', 'name', 'version', 'description', 'requires', 'risk', 'workflows', 'files' ) as $required ) {
			if ( ! array_key_exists( $required, $manifest ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', $required, 'add_required_manifest_field' );
			}
		}
		if ( 'aiwu.workflow-pack.v1' !== ( isset( $manifest['schema'] ) ? $manifest['schema'] : '' ) || ! isset( $manifest['id'] ) || ! self::validId( $manifest['id'] ) ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'schema', 'select_supported_schema' );
		}
		if ( ! isset( $manifest['version'] ) || ! preg_match( '/^(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $manifest['version'] ) ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'version', 'select_valid_version' );
		}
		if ( ! isset( $manifest['name'] ) || ! is_string( $manifest['name'] ) || strlen( $manifest['name'] ) > 200 || ! isset( $manifest['description'] ) || ! is_string( $manifest['description'] ) || strlen( $manifest['description'] ) > 4000 ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'name', 'repair_pack_metadata' );
		}
		$requirements = isset( $manifest['requires'] ) && is_array( $manifest['requires'] ) ? $manifest['requires'] : array();
		if ( ! isset( $manifest['requires'] ) || ! is_array( $manifest['requires'] ) || count( $requirements ) > 40 ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'requires', 'repair_dependency_contract' ); }
		foreach ( $requirements as $requirement ) {
			if ( ! is_array( $requirement ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'requires', 'repair_dependency_contract' ); continue; }
			$this->requireClosedKeys( $requirement, array( 'kind','id','version','optional','slot' ), $issues, 'requires' );
			$kind = isset( $requirement['kind'] ) ? $requirement['kind'] : '';
			$id = isset( $requirement['id'] ) ? $requirement['id'] : '';
			$version = isset( $requirement['version'] ) ? $requirement['version'] : '';
			$slot = isset( $requirement['slot'] ) ? $requirement['slot'] : '';
			if ( ! in_array( $kind, array( 'plugin','account' ), true ) || ! is_string( $id ) || ! preg_match( '/^[a-z0-9][a-z0-9._\/-]{1,190}$/', $id ) || ( '' !== $version && ! preg_match( '/^(?:>=|<=|=|>|<)(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)$/', $version ) ) || ( isset( $requirement['optional'] ) && ! is_bool( $requirement['optional'] ) ) || ( 'account' === $kind && ( ! preg_match( '/^[a-z0-9_]{2,64}$/', $id ) || ! is_string( $slot ) || ! preg_match( '/^[a-z0-9][a-z0-9_.-]{1,79}$/', $slot ) ) ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'requires', 'repair_dependency_contract' );
			}
		}
		$declared_risk = isset( $manifest['risk'] ) && is_array( $manifest['risk'] ) ? $manifest['risk'] : array();
		if ( ! isset( $manifest['risk'] ) || ! is_array( $manifest['risk'] ) || count( $declared_risk ) > 40 || count( $declared_risk ) !== count( array_unique( $declared_risk, SORT_REGULAR ) ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'risk', 'repair_declared_risk' ); }
		foreach ( $declared_risk as $risk_code ) { if ( ! is_string( $risk_code ) || ! preg_match( '/^[a-z][a-z0-9_]{1,79}$/', $risk_code ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'risk', 'repair_declared_risk' ); break; } }
		if ( isset( $manifest['extensions'] ) ) {
			$extensions = self::objectToArray( $manifest['extensions'] );
			if ( false === $extensions || ! empty( $extensions ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'extensions', 'select_supported_schema' ); }
		}
		$workflows = isset( $manifest['workflows'] ) && is_array( $manifest['workflows'] ) ? $manifest['workflows'] : array();
		$files = isset( $manifest['files'] ) && is_array( $manifest['files'] ) ? $manifest['files'] : array();
		if ( count( $workflows ) < 1 || count( $workflows ) > $this->limits['workflows'] ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', 'workflows', 'reduce_workflow_count' );
		}
		$file_table = array();
		foreach ( $files as $file ) {
			if ( ! is_array( $file ) || ! isset( $file['path'], $file['media_type'], $file['size'], $file['sha256'] ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'files', 'repair_file_table' );
				continue;
			}
			$this->requireClosedKeys( $file, array( 'path','media_type','size','sha256' ), $issues, 'files' );
			$path = (string) $file['path'];
			$key = strtolower( $path );
			if ( ! self::validMemberPath( $path ) || isset( $file_table[ $key ] ) || ! isset( $members[ $path ] ) ) {
				$issues[] = $this->issue( 'archive_member_unlisted', 'files', 'repair_file_table' );
				continue;
			}
			if ( 'application/json' !== $file['media_type'] || ! is_int( $file['size'] ) || $file['size'] < 0 || $file['size'] > $this->limits['entry_bytes'] || ! is_string( $file['sha256'] ) || ! preg_match( '/^sha256:[a-f0-9]{64}$/', $file['sha256'] ) || strlen( $members[ $path ] ) !== $file['size'] || ! hash_equals( 'sha256:' . hash( 'sha256', $members[ $path ] ), $file['sha256'] ) ) {
				$issues[] = $this->issue( 'archive_member_unlisted', 'files', 'repair_file_table' );
			}
			try { WaicWorkflowPhase1Json::decodeStrict( $members[ $path ], $this->limits['entry_bytes'], $this->limits['json_depth'], $this->limits['scalar_bytes'] ); } catch ( Exception $error ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'files', 'select_valid_json_member' ); }
			$file_table[ $key ] = true;
		}
		foreach ( $members as $path => $bytes ) {
			if ( 'aiwu-workflow-pack.json' === $path ) {
				continue;
			}
			if ( ! isset( $file_table[ strtolower( $path ) ] ) ) {
				$issues[] = $this->issue( 'archive_member_unlisted', 'files', 'repair_file_table' );
			}
		}
		$workflow_ids = array();
		$workflow_paths = array();
		foreach ( $workflows as $reference ) {
			if ( ! is_array( $reference ) || ! isset( $reference['id'], $reference['path'] ) || ! self::validId( $reference['id'] ) || ! isset( $members[ $reference['path'] ] ) ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'workflows', 'repair_workflow_reference' );
				continue;
			}
			$this->requireClosedKeys( $reference, array( 'id','path' ), $issues, 'workflows' );
			if ( $reference['path'] !== 'workflows/' . $reference['id'] . '.json' || isset( $workflow_ids[ $reference['id'] ] ) || isset( $workflow_paths[ $reference['path'] ] ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'workflows', 'repair_workflow_reference' ); continue; }
			$workflow_ids[ $reference['id'] ] = true;
			$workflow_paths[ $reference['path'] ] = true;
			try {
				$workflow = WaicWorkflowPhase1Json::decodeStrict( $members[ $reference['path'] ], $this->limits['workflow_bytes'], $this->limits['json_depth'], $this->limits['scalar_bytes'] );
				if ( ! is_array( $workflow ) || ! isset( $workflow['id'] ) || ! hash_equals( $reference['id'], (string) $workflow['id'] ) ) { $issues[] = $this->issue( 'manifest_schema_invalid', 'workflows', 'repair_workflow_reference' ); }
			} catch ( Exception $error ) {
				$issues[] = $this->issue( 'manifest_schema_invalid', 'workflows', 'select_valid_workflow' );
			}
		}
		return $issues;
	}

	private static function objectToArray( $value ) {
		if ( is_object( $value ) ) { return get_object_vars( $value ); }
		if ( is_array( $value ) && empty( $value ) ) { return array(); }
		if ( is_array( $value ) && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) { return $value; }
		return false;
	}

	private static function boundedCoordinate( $value ) {
		return ( is_int( $value ) || is_float( $value ) ) && is_finite( (float) $value ) && abs( $value ) <= 1000000;
	}

	private function validateDescriptorSettings( array $settings, array $descriptor ) {
		$contracts = isset( $descriptor['settings'] ) && is_array( $descriptor['settings'] ) ? $descriptor['settings'] : array();
		if ( array_diff( array_keys( $settings ), array_keys( $contracts ) ) ) { return false; }
		foreach ( $contracts as $key => $contract ) {
			if ( ! empty( $contract['required'] ) && ! array_key_exists( $key, $settings ) ) { return false; }
			if ( ! array_key_exists( $key, $settings ) ) { continue; }
			$value = $settings[ $key ];
			$type = isset( $contract['type'] ) ? $contract['type'] : '';
			if ( 'string' === $type && ( ! is_string( $value ) || strlen( $value ) > $this->limits['scalar_bytes'] ) ) { return false; }
			if ( 'boolean' === $type && ! is_bool( $value ) ) { return false; }
			if ( 'integer' === $type && ! is_int( $value ) ) { return false; }
			if ( isset( $contract['enum'] ) && ! in_array( $value, $contract['enum'], true ) ) { return false; }
		}
		return true;
	}

	private function allReachable( $start, array $nodes, array $adjacency ) {
		$seen = array();
		$queue = array( $start );
		while ( ! empty( $queue ) && count( $seen ) <= $this->limits['nodes'] ) {
			$current = array_shift( $queue );
			if ( isset( $seen[ $current ] ) ) { continue; }
			$seen[ $current ] = true;
			foreach ( isset( $adjacency[ $current ] ) ? $adjacency[ $current ] : array() as $next ) { $queue[] = $next; }
		}
		return count( $seen ) === count( $nodes );
	}

	private function resolveDependencies( $requirements ) {
		if ( ! is_array( $requirements ) ) { return array(); }
		$unresolved = array();
		foreach ( $requirements as $requirement ) {
			if ( ! is_array( $requirement ) || ! isset( $requirement['kind'], $requirement['id'] ) ) { continue; }
			$optional = ! empty( $requirement['optional'] );
			if ( 'account' === $requirement['kind'] ) {
				if ( ! $optional ) {
					$unresolved[] = array(
						'kind'    => 'account',
						'id'      => $requirement['id'],
						'slot'    => isset( $requirement['slot'] ) ? $requirement['slot'] : $requirement['id'],
						'state'   => 'mapping_required',
						'choices' => WaicWorkflowPhase1AccountRegistry::choices( $requirement['id'] ),
					);
				}
				continue;
			}
			if ( 'plugin' !== $requirement['kind'] ) { continue; }
			$plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) { $plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ); }
			$active = in_array( $requirement['id'], $plugins, true );
			$version_ok = $active && $this->pluginVersionMatches( $requirement['id'], isset( $requirement['version'] ) ? $requirement['version'] : '' );
			if ( ! $optional && ( ! $active || ! $version_ok ) ) { $unresolved[] = array( 'kind' => 'plugin', 'id' => $requirement['id'], 'state' => $active ? 'version_incompatible' : 'plugin_inactive' ); }
		}
		return $unresolved;
	}

	private function pluginVersionMatches( $plugin, $constraint ) {
		if ( '' === $constraint ) { return true; }
		if ( ! preg_match( '/^(>=|<=|=|>|<)(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/', $constraint, $match ) ) { return false; }
		if ( ! function_exists( 'get_plugin_data' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
		$path = WP_PLUGIN_DIR . '/' . $plugin;
		if ( ! is_file( $path ) ) { return false; }
		$data = get_plugin_data( $path, false, false );
		return isset( $data['Version'] ) && version_compare( $data['Version'], $match[2] . '.' . $match[3] . '.' . $match[4], $match[1] );
	}

	private function requireClosedKeys( array $value, array $allowed, array &$issues, $field ) {
		$unknown = array_diff( array_keys( $value ), $allowed );
		if ( ! empty( $unknown ) ) {
			$issues[] = $this->issue( 'manifest_schema_invalid', $field, 'remove_unknown_fields' );
		}
	}

	private function hasCycle( array $nodes, array $adjacency ) {
		$state = array();
		$visit = function ( $node ) use ( &$visit, &$state, $adjacency ) {
			if ( isset( $state[ $node ] ) ) {
				return 1 === $state[ $node ];
			}
			$state[ $node ] = 1;
			foreach ( isset( $adjacency[ $node ] ) ? $adjacency[ $node ] : array() as $next ) {
				if ( $visit( $next ) ) {
					return true;
				}
			}
			$state[ $node ] = 2;
			return false;
		};
		foreach ( $nodes as $node ) {
			if ( $visit( $node ) ) {
				return true;
			}
		}
		return false;
	}

	public static function descriptor( $type, $code ) {
		return WaicWorkflowPhase1DescriptorRegistry::get( $type, $code );
		$folders = array( 'trigger' => 'triggers', 'logic' => 'logics', 'action' => 'actions' );
		if ( ! isset( $folders[ $type ] ) || ! preg_match( '/^[a-z0-9_]{2,80}$/', $code ) ) {
			return false;
		}
		$path = dirname( __DIR__ ) . '/blocks/' . $folders[ $type ] . '/' . $code . '.php';
		if ( ! is_file( $path ) ) {
			return false;
		}
		$risk = array();
		$effects = array( 'workflow_state' );
		$data_paths = array();
		if ( 0 === strpos( $code, 'db_' ) ) {
			$risk = array_merge( $risk, array( 'raw_sql', 'db_write' ) );
		}
		if ( false !== strpos( $code, '_delete_' ) ) {
			$risk[] = false !== strpos( $code, 'user' ) ? 'user_delete' : 'content_delete';
		}
		if ( 0 === strpos( $code, 'wc_create_' ) || 0 === strpos( $code, 'wc_update_' ) ) {
			$risk[] = 'commerce_write';
		}
		if ( in_array( $code, array( 'wp_create_user', 'wp_update_user' ), true ) ) {
			$risk[] = 'privilege_change';
		}
		if ( in_array( $code, array( 'wp_send_webhook', 'sy_webhook', 'sy_url_visited' ), true ) ) {
			$risk[] = 0 === strpos( $code, 'sy_' ) ? 'public_endpoint' : 'dynamic_destination';
		}
		if ( preg_match( '/^(?:ai_|em_|sl_|te_|di_|ca_)/', $code ) ) {
			$risk[] = 'external_network';
			$data_paths[] = 'site_to_external_provider';
		}
		if ( 0 === strpos( $code, 'ai_' ) ) {
			$risk[] = 'cost';
		}
		if ( 'action' === $type ) {
			$effects[] = 'external_or_content_effect';
		}
		return array(
			'id'         => $type . ':' . $code,
			'path'       => $path,
			'fingerprint'=> 'sha256:' . hash_file( 'sha256', $path ),
			'risk'       => array_values( array_unique( $risk ) ),
			'effects'    => array_values( array_unique( $effects ) ),
			'data_paths' => $data_paths,
		);
	}

	public static function descriptorDigest() {
		return WaicWorkflowPhase1DescriptorRegistry::digest();
		$files = glob( dirname( __DIR__ ) . '/blocks/{triggers,logics,actions}/*.php', GLOB_BRACE );
		$manifest = array();
		foreach ( is_array( $files ) ? $files : array() as $path ) {
			$manifest[ wp_normalize_path( str_replace( dirname( __DIR__ ) . '/', '', $path ) ) ] = hash_file( 'sha256', $path );
		}
		ksort( $manifest, SORT_STRING );
		return WaicWorkflowPhase1Canonicalizer::digest( $manifest );
	}

	public static function containsSecret( $value ) {
		$value = is_string( $value ) ? $value : WaicWorkflowPhase1Canonicalizer::encode( $value );
		$patterns = array(
			'/AIWU[_-]SECRET/i',
			'/BEGIN[ ]+PRIVATE[ ]+KEY/i',
			'/Bearer\s+[A-Za-z0-9._-]{12,}/i',
			'/sk-[A-Za-z0-9]{12,}/',
			'/AKIA[0-9A-Z]{16}/',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return true;
			}
		}
		return false;
	}

	private static function validId( $id ) {
		return is_string( $id ) && strlen( $id ) <= 128 && preg_match( '/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/', $id );
	}

	private static function validMemberPath( $path ) {
		return is_string( $path ) && strlen( $path ) <= 180 && 1 === preg_match( '#^[a-z0-9][a-z0-9._-]*(?:/[a-z0-9][a-z0-9._-]*)*$#', $path );
	}

	private static function validNodeId( $id ) {
		return is_string( $id ) && 1 === preg_match( '/^n:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id );
	}

	private static function validEdgeId( $id ) {
		return is_string( $id ) && 1 === preg_match( '/^e:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id );
	}

	private function issue( $code, $field, $remediation, array $context = array() ) {
		$context['field'] = $field;
		return array( 'severity' => 'error', 'code' => $code, 'context' => $context, 'remediation' => $remediation );
	}
}
