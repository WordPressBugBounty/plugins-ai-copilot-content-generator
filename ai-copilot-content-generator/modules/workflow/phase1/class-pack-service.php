<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1PackService {
	const STAGE_SCHEMA = 'aiwu.workflow-stage.v1';

	public function preflight( $input_type, $source, $operation_id = 'workflow.import.preflight', $provenance = 'local_unsigned', $ready_code = 'pack_preflight_ready' ) {
		$started = microtime( true );
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'core' ) ) {
			return $this->disabled( $operation_id );
		}
		$ready = WaicWorkflowPhase1Storage::verify();
		if ( 'succeeded' !== $ready->getState() ) {
			return $ready;
		}
		if ( ! in_array( $input_type, array( 'standalone_json_file', 'standalone_json_body', 'workflow_pack_zip' ), true ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'unsupported_input_type', __( 'The workflow input type is unsupported.', 'ai-copilot-content-generator' ), array(), array(), 415 );
		}
		$pack = 'workflow_pack_zip' === $input_type ? WaicWorkflowPhase1Archive::fromZip( $source, $operation_id ) : WaicWorkflowPhase1Archive::fromStandaloneJson( $source, $operation_id );
		if ( $pack instanceof WaicWorkflowPhase1Result ) {
			return $pack;
		}
		$validator = new WaicWorkflowPhase1Validator();
		$validated = $validator->validatePack( $pack, $provenance, $operation_id );
		if ( $validated instanceof WaicWorkflowPhase1Result ) {
			return $validated;
		}
		$validated = $this->withAccountMappings( $validated, array() );
		$handle = bin2hex( random_bytes( 32 ) );
		$handle_hash = hash( 'sha256', $handle );
		$binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $validated['artifact_digest'], $handle_hash, $validated );
		$stage = array(
			'schema'       => self::STAGE_SCHEMA,
			'operation_id' => $operation_id,
			'site_id'      => get_current_blog_id(),
			'actor_id'     => get_current_user_id(),
			'created_at'   => time(),
			'expires_at'   => time() + 15 * MINUTE_IN_SECONDS,
			'handle_hash'  => $handle_hash,
			'pack'         => $pack,
			'validated'    => $validated,
		);
		if ( ! $this->writeStage( $handle_hash, $stage ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'staging_unavailable', __( 'Private workflow staging is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$stamp = WaicWorkflowPhase1Tokens::issue( 'validation', $binding, 15 * MINUTE_IN_SECONDS );
		if ( '' === $stamp ) {
			$this->deleteStage( $handle_hash );
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'grant_store_unavailable', __( 'The validation grant store is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$dependencies = isset( $validated['dependencies'] ) && is_array( $validated['dependencies'] ) ? $validated['dependencies'] : array();
		$state = empty( $dependencies ) ? 'awaiting_confirmation' : 'awaiting_dependency_mapping';
		WaicWorkflowPhase1Storage::recordOutcome( $ready_code, $state, (int) ( ( microtime( true ) - $started ) * 1000 ), strlen( WaicWorkflowPhase1Canonicalizer::encode( $pack ) ) );
		return WaicWorkflowPhase1Result::waiting(
			$operation_id,
			$state,
			$ready_code,
			empty( $dependencies ) ? __( 'The workflow pack passed validation and awaits explicit review.', 'ai-copilot-content-generator' ) : __( 'The workflow pack requires dependency mapping before confirmation.', 'ai-copilot-content-generator' ),
			array(),
			array(
				'package_id'       => $validated['manifest']['id'],
				'version'          => $validated['manifest']['version'],
				'artifact_digest'  => $validated['artifact_digest'],
				'workflow_digests' => $validated['workflow_digests'],
				'operation_handle' => $handle,
				'validation_stamp' => $stamp,
				'expires_at'       => time() + 15 * MINUTE_IN_SECONDS,
				'risk'             => $validated['risk'],
				'dependencies'     => $dependencies,
				'effects'          => $validated['effects'],
				'data_paths'       => $validated['data_paths'],
			)
		);
	}

	public function mapDependencies( $operation_id, $handle, $stamp, array $mappings ) {
		if ( ! self::validHandle( $handle ) || count( $mappings ) > 40 ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The dependency mapping request is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$handle_hash = hash( 'sha256', $handle );
		$stage = $this->readStage( $handle_hash );
		if ( ! is_array( $stage ) || $operation_id !== $stage['operation_id'] || time() > (int) $stage['expires_at'] || get_current_blog_id() !== (int) $stage['site_id'] || get_current_user_id() !== (int) $stage['actor_id'] || ! isset( $stage['pack'] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The staged validation is missing or stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$current = ( new WaicWorkflowPhase1Validator() )->validatePack( $stage['pack'], $stage['validated']['provenance'], $operation_id );
		if ( $current instanceof WaicWorkflowPhase1Result || ! hash_equals( $stage['validated']['artifact_digest'], $current['artifact_digest'] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'Current workflow policy no longer matches preflight.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$current = $this->withAccountMappings( $current, isset( $stage['validated']['account_mappings'] ) ? $stage['validated']['account_mappings'] : array() );
		if ( false === $current ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'account_mapping_stale', __( 'A selected account reference is no longer available.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $current['artifact_digest'], $handle_hash, $current );
		if ( ! WaicWorkflowPhase1Tokens::verify( $stamp, 'validation', $binding ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The validation grant is invalid or stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$combined = isset( $current['account_mappings'] ) ? $current['account_mappings'] : array();
		foreach ( $mappings as $mapping ) {
			if ( ! is_array( $mapping ) || 2 !== count( $mapping ) || array() !== array_diff( array_keys( $mapping ), array( 'slot','reference' ) ) || ! isset( $mapping['slot'], $mapping['reference'] ) || ! is_string( $mapping['slot'] ) || ! is_string( $mapping['reference'] ) ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'A dependency mapping entry is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
			}
			$combined[ $mapping['slot'] ] = array( 'slot' => $mapping['slot'], 'reference' => $mapping['reference'] );
		}
		$current = ( new WaicWorkflowPhase1Validator() )->validatePack( $stage['pack'], $stage['validated']['provenance'], $operation_id );
		$current = $current instanceof WaicWorkflowPhase1Result ? false : $this->withAccountMappings( $current, $combined );
		if ( false === $current ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'account_mapping_invalid', __( 'The selected account mapping is unavailable for this site and purpose.', 'ai-copilot-content-generator' ), array(), array(), 422 );
		}
		$stage['validated'] = $current;
		if ( ! $this->writeStage( $handle_hash, $stage ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'staging_unavailable', __( 'Private workflow staging is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$new_binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $current['artifact_digest'], $handle_hash, $current );
		$remaining_ttl = max( 1, min( 15 * MINUTE_IN_SECONDS, (int) $stage['expires_at'] - time() ) );
		$new_stamp = WaicWorkflowPhase1Tokens::issue( 'validation', $new_binding, $remaining_ttl );
		if ( '' === $new_stamp ) { return WaicWorkflowPhase1Result::blocked( $operation_id, 'grant_store_unavailable', __( 'The validation grant store is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 ); }
		$remaining = isset( $current['dependencies'] ) ? $current['dependencies'] : array();
		$state = empty( $remaining ) ? 'awaiting_confirmation' : 'awaiting_dependency_mapping';
		return WaicWorkflowPhase1Result::waiting( $operation_id, $state, 'dependency_mapping_saved', empty( $remaining ) ? __( 'Dependency mapping is complete and awaits explicit review.', 'ai-copilot-content-generator' ) : __( 'Additional dependency mapping is required.', 'ai-copilot-content-generator' ), array(), array( 'package_id' => $current['manifest']['id'], 'artifact_digest' => $current['artifact_digest'], 'mapping_digest' => $current['mapping_digest'], 'operation_handle' => $handle, 'validation_stamp' => $new_stamp, 'dependencies' => $remaining, 'expires_at' => (int) $stage['expires_at'] ) );
	}

	public function confirmPrepared( $operation_id, $handle, $stamp ) {
		$allowed = array( 'workflow.import.preflight', 'workflow.package.update', 'workflow.package.rollback', 'workflow.package.uninstall', 'workflow.ai.plan.compile' );
		if ( ! self::validHandle( $handle ) || ! in_array( $operation_id, $allowed, true ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The confirmation request is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$handle_hash = hash( 'sha256', $handle );
		$stage = $this->readStage( $handle_hash );
		if ( ! is_array( $stage ) || $operation_id !== $stage['operation_id'] || time() > (int) $stage['expires_at'] || get_current_blog_id() !== (int) $stage['site_id'] || get_current_user_id() !== (int) $stage['actor_id'] ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The staged validation is missing or stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$current = $stage['validated'];
		if ( isset( $stage['pack'] ) ) {
			$current = ( new WaicWorkflowPhase1Validator() )->validatePack( $stage['pack'], $stage['validated']['provenance'], $operation_id );
			if ( $current instanceof WaicWorkflowPhase1Result || ! hash_equals( $stage['validated']['artifact_digest'], $current['artifact_digest'] ) ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'Current workflow policy no longer matches preflight.', 'ai-copilot-content-generator' ), array(), array(), 409 );
			}
			$current = $this->withAccountMappings( $current, isset( $stage['validated']['account_mappings'] ) ? $stage['validated']['account_mappings'] : array() );
			if ( false === $current ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'account_mapping_stale', __( 'A selected account reference is no longer available.', 'ai-copilot-content-generator' ), array(), array(), 409 );
			}
		}
		$binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $current['artifact_digest'], $handle_hash, $current );
		if ( ! WaicWorkflowPhase1Tokens::verify( $stamp, 'validation', $binding ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The validation grant is invalid or stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$dependencies = isset( $current['dependencies'] ) && is_array( $current['dependencies'] ) ? $current['dependencies'] : array();
		$package_id = isset( $stage['package_id'] ) ? $stage['package_id'] : $current['manifest']['id'];
		if ( ! empty( $dependencies ) ) {
			return WaicWorkflowPhase1Result::waiting( $operation_id, 'awaiting_dependency_mapping', 'account_mapping_required', __( 'Dependency mapping is required before confirmation.', 'ai-copilot-content-generator' ), array(), array( 'package_id' => $package_id, 'artifact_digest' => $current['artifact_digest'], 'operation_handle' => $handle, 'validation_stamp' => $stamp, 'dependencies' => $dependencies, 'expires_at' => (int) $stage['expires_at'] ) );
		}
		$confirmation = WaicWorkflowPhase1Tokens::issue( 'confirmation', $binding, 5 * MINUTE_IN_SECONDS );
		if ( '' === $confirmation ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'grant_store_unavailable', __( 'The confirmation grant store is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$version = isset( $current['manifest']['version'] ) ? $current['manifest']['version'] : '';
		return WaicWorkflowPhase1Result::waiting( $operation_id, 'awaiting_confirmation', 'confirmation_issued', __( 'The one-use confirmation is ready for this reviewed artifact.', 'ai-copilot-content-generator' ), array(), array( 'package_id' => $package_id, 'version' => $version, 'artifact_digest' => $current['artifact_digest'], 'operation_handle' => $handle, 'validation_stamp' => $stamp, 'confirmation' => $confirmation, 'expires_at' => time() + 5 * MINUTE_IN_SECONDS, 'risk' => isset( $current['risk'] ) ? $current['risk'] : array(), 'effects' => isset( $current['effects'] ) ? $current['effects'] : array(), 'data_paths' => isset( $current['data_paths'] ) ? $current['data_paths'] : array() ) );
	}

	public function commitImport( $handle, $stamp, $confirmation, $idempotency_key ) {
		return $this->commitPack( 'workflow.import.commit', 'workflow.import.preflight', $handle, $stamp, $confirmation, $idempotency_key, 201, 'pack_import_committed' );
	}

	public function commitUpdate( $handle, $stamp, $confirmation, $idempotency_key ) {
		return $this->commitPack( 'workflow.package.update', 'workflow.package.update', $handle, $stamp, $confirmation, $idempotency_key, 200, 'pack_update_committed' );
	}

	public function commitAiDraft( $handle, $stamp, $confirmation, $idempotency_key, $expected_digest ) {
		return $this->commitPack( 'workflow.draft.save', 'workflow.ai.plan.compile', $handle, $stamp, $confirmation, $idempotency_key, 201, 'ai_draft_saved', $expected_digest );
	}

	private function commitPack( $operation_id, $staged_operation, $handle, $stamp, $confirmation, $idempotency_key, $http_status, $success_code, $expected_digest = '' ) {
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'core' ) ) {
			return $this->disabled( $operation_id );
		}
		if ( ! self::validHandle( $handle ) || ! self::validIdempotency( $idempotency_key ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The commit envelope is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$handle_hash = hash( 'sha256', $handle );
		$key_hash = hash( 'sha256', $idempotency_key );
		$existing = WaicWorkflowPhase1Storage::findOperation( $operation_id, $key_hash, get_current_user_id(), get_current_blog_id() );
		if ( is_array( $existing ) ) {
			if ( hash_equals( $existing['operation_handle_hash'], $handle_hash ) && 'succeeded' === $existing['state'] ) {
				$data = json_decode( $existing['result_json'], true );
				return WaicWorkflowPhase1Result::success( $operation_id, $existing['code'], __( 'The prior idempotent workflow operation succeeded.', 'ai-copilot-content-generator' ), is_array( $data ) && isset( $data['meta'] ) ? $data['meta'] : array(), $http_status );
			}
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'idempotency_conflict', __( 'The idempotency key conflicts with another input.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$stage = $this->readStage( $handle_hash );
		if ( ! is_array( $stage ) || $staged_operation !== $stage['operation_id'] || time() > (int) $stage['expires_at'] || get_current_blog_id() !== (int) $stage['site_id'] || get_current_user_id() !== (int) $stage['actor_id'] ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The staged validation is missing or stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$validator = new WaicWorkflowPhase1Validator();
		$current = $validator->validatePack( $stage['pack'], $stage['validated']['provenance'], $staged_operation );
		if ( $current instanceof WaicWorkflowPhase1Result || $current['artifact_digest'] !== $stage['validated']['artifact_digest'] ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'Current workflow policy no longer matches preflight.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$current = $this->withAccountMappings( $current, isset( $stage['validated']['account_mappings'] ) ? $stage['validated']['account_mappings'] : array() );
		if ( false === $current ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'account_mapping_stale', __( 'A selected account reference is no longer available.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		if ( '' !== $expected_digest && ( ! preg_match( '/^sha256:[a-f0-9]{64}$/', $expected_digest ) || ! hash_equals( $current['artifact_digest'], $expected_digest ) ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The compiled plan digest is stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$binding = WaicWorkflowPhase1Tokens::binding( $staged_operation, $current['artifact_digest'], $handle_hash, $current );
		if ( ! WaicWorkflowPhase1Tokens::verify( $stamp, 'validation', $binding ) || ! WaicWorkflowPhase1Tokens::verify( $confirmation, 'confirmation', $binding ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'confirmation_invalid', __( 'The validation or confirmation grant is invalid.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$manifest = $current['manifest'];
		$service = $this;
		$outbox = new WaicWorkflowPhase0Outbox();
		$outcome = $outbox->commit(
			array(
				'site_id'        => get_current_blog_id(),
				'workflow_id'    => 1,
				'operation_id'   => $operation_id,
				'idempotency_key'=> $key_hash,
				'payload_digest' => $current['command_digest'],
			),
			function ( $journal_id ) use ( $operation_id, $key_hash, $handle_hash, $confirmation, $current, $manifest, $service, $success_code, $http_status ) {
				return $service->persistPack( $journal_id, $operation_id, $key_hash, $handle_hash, $confirmation, $current, $manifest, $success_code, $http_status );
			},
			'workflow.pack.' . ( 'workflow.import.commit' === $operation_id ? 'imported' : 'updated' ),
			array( 'package_id' => $manifest['id'], 'version' => $manifest['version'], 'artifact_digest' => $current['artifact_digest'] )
		);
		if ( ! is_array( $outcome ) || ! isset( $outcome['mutation_result'] ) || ! $outcome['mutation_result'] instanceof WaicWorkflowPhase1Result ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'internal_boundary_failed', __( 'The transactional workflow boundary failed.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$this->deleteStage( $handle_hash );
		return $outcome['mutation_result'];
	}

	public function persistPack( $journal_id, $operation_id, $key_hash, $handle_hash, $confirmation, array $validated, array $manifest, $success_code, $http_status ) {
		return WaicWorkflowPhase1Storage::persistPack( $operation_id, $key_hash, $handle_hash, $confirmation, $validated, $manifest, $success_code, $http_status );
	}

	public function listPacks( $cursor = '', $limit = 20 ) {
		$after = $this->decodeCursor( $cursor );
		if ( false === $after ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.package.list', 'cursor_invalid', __( 'The list cursor is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$items = WaicWorkflowPhase1Storage::listPacks( get_current_blog_id(), $limit, $after );
		$next = count( $items ) === min( 50, max( 1, (int) $limit ) ) ? $this->encodeCursor( (int) end( $items )['id'] ) : '';
		return WaicWorkflowPhase1Result::success( 'workflow.package.list', 'pack_list_ready', __( 'Workflow packs loaded.', 'ai-copilot-content-generator' ), array( 'items' => $items, 'cursor' => $next ) );
	}

	public function readPack( $package_id ) {
		if ( ! self::validPackageId( $package_id ) ) {
			return $this->notFound( 'workflow.package.read' );
		}
		$row = WaicWorkflowPhase1Storage::getPack( get_current_blog_id(), $package_id );
		if ( ! $row ) {
			return $this->notFound( 'workflow.package.read' );
		}
		$item = array_intersect_key( $row, array_flip( array( 'package_id','version','artifact_digest','provenance','state','created_at','updated_at' ) ) );
		$item['workflows'] = array_map(
			function ( $object ) {
				return array_intersect_key( $object, array_flip( array( 'workflow_id','object_type','ownership','content_digest','state' ) ) );
			},
			array_values( array_filter( WaicWorkflowPhase1Storage::getPackObjects( get_current_blog_id(), (int) $row['id'] ), function ( $object ) { return 'dependency_mapping' !== $object['object_type']; } ) )
		);
		return WaicWorkflowPhase1Result::success( 'workflow.package.read', 'pack_detail_ready', __( 'Workflow pack loaded.', 'ai-copilot-content-generator' ), array( 'item' => $item ) );
	}

	public function exportPack( $package_id ) {
		if ( ! self::validPackageId( $package_id ) ) {
			return $this->notFound( 'workflow.export' );
		}
		$row = WaicWorkflowPhase1Storage::getPack( get_current_blog_id(), $package_id );
		if ( ! $row ) {
			return $this->notFound( 'workflow.export' );
		}
		$objects = WaicWorkflowPhase1Storage::getPackObjects( get_current_blog_id(), (int) $row['id'] );
		$export = array( 'schema' => 'aiwu.workflow-pack-export.v1', 'manifest' => json_decode( $row['manifest_json'], true ), 'workflows' => array() );
		foreach ( $objects as $object ) {
			if ( 'dependency_mapping' === $object['object_type'] ) { continue; }
			$export['workflows'][] = json_decode( $object['workflow_json'], true );
		}
		return WaicWorkflowPhase1Result::success( 'workflow.export', 'pack_exported', __( 'Workflow pack export is ready.', 'ai-copilot-content-generator' ), array( 'package_id' => $row['package_id'], 'version' => $row['version'], 'artifact_digest' => WaicWorkflowPhase1Canonicalizer::digest( $export ), 'export' => $export ) );
	}

	public function preflightLifecycle( $operation_id, $package_id, $target_version = '' ) {
		if ( ! in_array( $operation_id, array( 'workflow.package.rollback', 'workflow.package.uninstall' ), true ) || ! self::validPackageId( $package_id ) ) {
			return $this->notFound( $operation_id );
		}
		$current = WaicWorkflowPhase1Storage::getPack( get_current_blog_id(), $package_id );
		$target = 'workflow.package.rollback' === $operation_id ? WaicWorkflowPhase1Storage::getPack( get_current_blog_id(), $package_id, $target_version ) : $current;
		if ( ! $current || ! $target ) {
			return $this->notFound( $operation_id );
		}
		$artifact_digest = WaicWorkflowPhase1Canonicalizer::digest( array( 'operation_id' => $operation_id, 'package_id' => $package_id, 'current' => $current['artifact_digest'], 'target' => $target['artifact_digest'] ) );
		$handle = bin2hex( random_bytes( 32 ) );
		$handle_hash = hash( 'sha256', $handle );
		$validated = array( 'artifact_digest' => $artifact_digest, 'descriptor_revision' => WaicWorkflowPhase1Validator::descriptorDigest(), 'policy_revision' => 'sha256:' . hash( 'sha256', WaicWorkflowPhase1Validator::POLICY_REVISION ), 'validator_revision' => WaicWorkflowPhase1Validator::REVISION );
		$binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $artifact_digest, $handle_hash, $validated );
		$stage = array( 'schema' => self::STAGE_SCHEMA, 'operation_id' => $operation_id, 'site_id' => get_current_blog_id(), 'actor_id' => get_current_user_id(), 'created_at' => time(), 'expires_at' => time() + 15 * MINUTE_IN_SECONDS, 'handle_hash' => $handle_hash, 'package_id' => $package_id, 'current_id' => (int) $current['id'], 'target_id' => (int) $target['id'], 'validated' => $validated );
		if ( ! $this->writeStage( $handle_hash, $stage ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'staging_unavailable', __( 'Private workflow staging is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$stamp = WaicWorkflowPhase1Tokens::issue( 'validation', $binding, 15 * MINUTE_IN_SECONDS );
		if ( '' === $stamp ) {
			$this->deleteStage( $handle_hash );
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'grant_store_unavailable', __( 'The validation grant store is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		return WaicWorkflowPhase1Result::waiting( $operation_id, 'awaiting_confirmation', 'pack_preflight_ready', __( 'The package change awaits explicit review.', 'ai-copilot-content-generator' ), array(), array( 'package_id' => $package_id, 'version' => $target['version'], 'artifact_digest' => $artifact_digest, 'operation_handle' => $handle, 'validation_stamp' => $stamp, 'expires_at' => time() + 15 * MINUTE_IN_SECONDS, 'effects' => array( 'workflow_draft_state' ) ) );
	}

	public function commitLifecycle( $operation_id, $handle, $stamp, $confirmation, $idempotency_key ) {
		if ( ! in_array( $operation_id, array( 'workflow.package.rollback', 'workflow.package.uninstall' ), true ) || ! self::validHandle( $handle ) || ! self::validIdempotency( $idempotency_key ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The commit envelope is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$hash = hash( 'sha256', $handle );
		$key_hash = hash( 'sha256', $idempotency_key );
		$existing = WaicWorkflowPhase1Storage::findOperation( $operation_id, $key_hash, get_current_user_id(), get_current_blog_id() );
		if ( is_array( $existing ) ) {
			if ( hash_equals( $existing['operation_handle_hash'], $hash ) && 'succeeded' === $existing['state'] ) {
				return WaicWorkflowPhase1Result::success( $operation_id, $existing['code'], __( 'The prior idempotent package operation succeeded.', 'ai-copilot-content-generator' ) );
			}
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'idempotency_conflict', __( 'The idempotency key conflicts with another input.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$stage = $this->readStage( $hash );
		if ( ! is_array( $stage ) || $operation_id !== $stage['operation_id'] || time() > (int) $stage['expires_at'] || get_current_blog_id() !== (int) $stage['site_id'] || get_current_user_id() !== (int) $stage['actor_id'] ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'validation_stamp_stale', __( 'The staged package change is stale.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$binding = WaicWorkflowPhase1Tokens::binding( $operation_id, $stage['validated']['artifact_digest'], $hash, $stage['validated'] );
		if ( ! WaicWorkflowPhase1Tokens::verify( $stamp, 'validation', $binding ) || ! WaicWorkflowPhase1Tokens::verify( $confirmation, 'confirmation', $binding ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'confirmation_invalid', __( 'The validation or confirmation grant is invalid.', 'ai-copilot-content-generator' ), array(), array(), 409 );
		}
		$outcome = ( new WaicWorkflowPhase0Outbox() )->commit(
			array(
				'site_id'         => get_current_blog_id(),
				'workflow_id'     => 1,
				'operation_id'    => $operation_id,
				'idempotency_key' => $key_hash,
				'payload_digest'  => $stage['validated']['artifact_digest'],
			),
			function () use ( $operation_id, $key_hash, $hash, $stage, $confirmation ) {
				return WaicWorkflowPhase1Storage::commitLifecycleMutation( $operation_id, $key_hash, $hash, $stage, $confirmation );
			},
			'workflow.pack.' . ( 'workflow.package.rollback' === $operation_id ? 'rolled_back' : 'uninstalled' ),
			array( 'package_id' => $stage['package_id'], 'artifact_digest' => $stage['validated']['artifact_digest'] )
		);
		if ( ! is_array( $outcome ) || ! isset( $outcome['mutation_result'] ) || ! $outcome['mutation_result'] instanceof WaicWorkflowPhase1Result ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'internal_boundary_failed', __( 'The transactional workflow boundary failed.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$this->deleteStage( $hash );
		return $outcome['mutation_result'];
	}

	public static function cleanupStaging( $limit = 100 ) {
		return WaicWorkflowPhase1Staging::cleanup( $limit );
	}

	private function writeStage( $hash, array $stage ) {
		return WaicWorkflowPhase1Staging::write( $hash, $stage );
	}

	private function readStage( $hash ) {
		return WaicWorkflowPhase1Staging::read( $hash );
	}

	private function deleteStage( $hash ) {
		return WaicWorkflowPhase1Staging::delete( $hash );
	}

	private function withAccountMappings( array $validated, array $mappings ) {
		$by_slot = array();
		foreach ( $mappings as $key => $mapping ) {
			if ( ! is_array( $mapping ) || ! isset( $mapping['slot'], $mapping['reference'] ) || ! is_string( $mapping['slot'] ) || ! is_string( $mapping['reference'] ) ) { return false; }
			$by_slot[ $mapping['slot'] ] = array( 'slot' => $mapping['slot'], 'reference' => $mapping['reference'] );
		}
		$remaining = array();
		$resolved = array();
		$known_account_slots = array();
		foreach ( isset( $validated['dependencies'] ) && is_array( $validated['dependencies'] ) ? $validated['dependencies'] : array() as $dependency ) {
			if ( ! is_array( $dependency ) || 'account' !== ( isset( $dependency['kind'] ) ? $dependency['kind'] : '' ) ) {
				$remaining[] = $dependency;
				continue;
			}
			$slot = isset( $dependency['slot'] ) ? $dependency['slot'] : '';
			$purpose = isset( $dependency['id'] ) ? $dependency['id'] : '';
			$known_account_slots[ $slot ] = true;
			if ( ! isset( $by_slot[ $slot ] ) ) { $remaining[] = $dependency; continue; }
			$account = WaicWorkflowPhase1AccountRegistry::resolve( $purpose, $by_slot[ $slot ]['reference'] );
			if ( false === $account ) { return false; }
			$resolved[ $slot ] = array( 'slot' => $slot, 'purpose' => $purpose, 'reference' => $by_slot[ $slot ]['reference'] );
		}
		foreach ( $by_slot as $slot => $mapping ) {
			if ( ! isset( $known_account_slots[ $slot ] ) ) { return false; }
		}
		ksort( $resolved, SORT_STRING );
		$validated['account_mappings'] = $resolved;
		$validated['dependencies'] = array_values( $remaining );
		$validated['mapping_digest'] = WaicWorkflowPhase1Canonicalizer::digest( array_values( $resolved ), 'account-mappings', 'v1' );
		$validated['command_digest'] = WaicWorkflowPhase1Canonicalizer::digest(
			array( 'artifact_digest' => $validated['artifact_digest'], 'mapping_digest' => $validated['mapping_digest'] ),
			'workflow-command',
			'v1'
		);
		return $validated;
	}

	private function encodeCursor( $id ) {
		$payload = get_current_blog_id() . ':' . (int) $id;
		return rtrim( strtr( base64_encode( $payload . ':' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ) ), '+/', '-_' ), '=' );
	}

	private function decodeCursor( $cursor ) {
		if ( '' === $cursor ) { return 0; }
		$raw = base64_decode( strtr( $cursor, '-_', '+/' ), true );
		$parts = is_string( $raw ) ? explode( ':', $raw, 3 ) : array();
		if ( 3 !== count( $parts ) || (int) $parts[0] !== get_current_blog_id() ) { return false; }
		$payload = $parts[0] . ':' . $parts[1];
		return ctype_digit( $parts[1] ) && hash_equals( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), $parts[2] ) ? (int) $parts[1] : false;
	}

	private static function validHandle( $value ) { return is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $value ); }
	private static function validIdempotency( $value ) { return is_string( $value ) && 1 === preg_match( '/^[A-Za-z0-9_-]{22,128}$/', $value ); }
	private static function validPackageId( $value ) { return is_string( $value ) && strlen( $value ) <= 128 && 1 === preg_match( '/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/', $value ); }
	private function disabled( $operation_id ) { return WaicWorkflowPhase1Result::blocked( $operation_id, 'feature_disabled', __( 'Phase 1 workflow features are disabled.', 'ai-copilot-content-generator' ), array(), array(), 404 ); }
	private function notFound( $operation_id ) { return WaicWorkflowPhase1Result::blocked( $operation_id, 'object_unavailable', __( 'The workflow object is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 404 ); }
}
