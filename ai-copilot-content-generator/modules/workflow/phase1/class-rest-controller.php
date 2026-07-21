<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1RestController {
	const NS = 'aiwu/v1';

	public static function registerRoutes() {
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'core' ) ) {
			return;
		}
		self::route( '/workflow-packs/import/preflight', WP_REST_Server::CREATABLE, 'importPreflight', 'import' );
		self::route( '/workflow-packs/import/commit', WP_REST_Server::CREATABLE, 'importCommit', 'import' );
		self::route( '/workflow-packs/export', WP_REST_Server::CREATABLE, 'exportPack', 'edit' );
		self::route( '/workflow-packs', WP_REST_Server::READABLE, 'listPacks', 'edit' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)', WP_REST_Server::READABLE, 'readPack', 'edit' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/update/preflight', WP_REST_Server::CREATABLE, 'updatePreflight', 'import' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/update/commit', WP_REST_Server::CREATABLE, 'updateCommit', 'import' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/rollback/preflight', WP_REST_Server::CREATABLE, 'rollbackPreflight', 'import' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/rollback/commit', WP_REST_Server::CREATABLE, 'rollbackCommit', 'import' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/uninstall/preflight', WP_REST_Server::CREATABLE, 'uninstallPreflight', 'import' );
		self::route( '/workflow-packs/(?P<package_id>[a-z0-9]+(?:[.-][a-z0-9]+)*)/uninstall/commit', WP_REST_Server::CREATABLE, 'uninstallCommit', 'import' );
		if ( WaicWorkflowPhase1Config::isEnabled( 'ai' ) ) {
			self::route( '/workflow-ai/draft-plans', WP_REST_Server::CREATABLE, 'compileAiPlan', 'ai' );
			self::route( '/workflow-ai/drafts', WP_REST_Server::CREATABLE, 'saveAiDraft', 'ai' );
		}
	}

	private static function route( $path, $methods, $callback, $authority ) {
		register_rest_route(
			self::NS,
			$path,
			array(
				'methods'             => $methods,
				'callback'            => array( __CLASS__, $callback ),
				'permission_callback' => function ( $request ) use ( $authority ) { return self::authorize( $request, $authority ); },
			)
		);
	}

	private static function authorize( WP_REST_Request $request, $authority ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', __( 'Authentication is required.', 'ai-copilot-content-generator' ), array( 'status' => 401 ) );
		}
		$nonce = $request->get_header( 'X-WP-Nonce' );
		$cookie_authenticated = defined( 'LOGGED_IN_COOKIE' ) && isset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		if ( $cookie_authenticated && ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) ) {
			return new WP_Error( 'rest_cookie_invalid_nonce', __( 'The REST nonce is invalid.', 'ai-copilot-content-generator' ), array( 'status' => 403 ) );
		}
		$capability = array( 'import' => 'aiwu_import_workflow_packs', 'edit' => 'aiwu_edit_workflows', 'ai' => 'aiwu_generate_workflow_drafts' );
		if ( ! isset( $capability[ $authority ] ) || ! current_user_can( $capability[ $authority ] ) ) {
			return new WP_Error( 'rest_forbidden', __( 'The workflow object is unavailable.', 'ai-copilot-content-generator' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public static function importPreflight( WP_REST_Request $request ) {
		$limit = self::limit( 'preflight', 'workflow.import.preflight' );
		if ( true !== $limit ) { return $limit; }
		$confirmation = self::confirmationBody( $request, 'workflow.import.preflight' );
		if ( $confirmation instanceof WaicWorkflowPhase1Result ) { return $confirmation->toResponse(); }
		if ( is_array( $confirmation ) ) {
			return self::preparedActionResponse( 'workflow.import.preflight', $confirmation );
		}
		$input = self::parseImport( $request, 'workflow.import.preflight' );
		if ( $input instanceof WaicWorkflowPhase1Result ) { return $input->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->preflight( $input['type'], $input['source'] )->toResponse();
	}

	public static function importCommit( WP_REST_Request $request ) {
		$limit = self::limit( 'commit', 'workflow.import.commit' );
		if ( true !== $limit ) { return $limit; }
		$body = self::closedBody( $request, array( 'operation_handle','validation_stamp','confirmation' ), 256 * KB_IN_BYTES, 'workflow.import.commit' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->commitImport( $body['operation_handle'], $body['validation_stamp'], $body['confirmation'], $request->get_header( 'Idempotency-Key' ) )->toResponse();
	}

	public static function updatePreflight( WP_REST_Request $request ) {
		$limit = self::limit( 'preflight', 'workflow.package.update' );
		if ( true !== $limit ) { return $limit; }
		$confirmation = self::confirmationBody( $request, 'workflow.package.update' );
		if ( $confirmation instanceof WaicWorkflowPhase1Result ) { return $confirmation->toResponse(); }
		if ( is_array( $confirmation ) ) {
			return self::preparedActionResponse( 'workflow.package.update', $confirmation );
		}
		$input = self::parseImport( $request, 'workflow.package.update' );
		if ( $input instanceof WaicWorkflowPhase1Result ) { return $input->toResponse(); }
		$result = ( new WaicWorkflowPhase1PackService() )->preflight( $input['type'], $input['source'], 'workflow.package.update' );
		if ( '' !== self::resultPackageId( $result ) && $request['package_id'] !== self::resultPackageId( $result ) ) {
			return WaicWorkflowPhase1Result::blocked( 'workflow.package.update', 'object_unavailable', __( 'The workflow object is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 404 )->toResponse();
		}
		return $result->toResponse();
	}

	public static function updateCommit( WP_REST_Request $request ) {
		$limit = self::limit( 'commit', 'workflow.package.update' );
		if ( true !== $limit ) { return $limit; }
		$body = self::closedBody( $request, array( 'operation_handle','validation_stamp','confirmation' ), 256 * KB_IN_BYTES, 'workflow.package.update' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->commitUpdate( $body['operation_handle'], $body['validation_stamp'], $body['confirmation'], $request->get_header( 'Idempotency-Key' ) )->toResponse();
	}

	public static function rollbackPreflight( WP_REST_Request $request ) {
		$limit = self::limit( 'preflight', 'workflow.package.rollback' );
		if ( true !== $limit ) { return $limit; }
		$confirmation = self::confirmationBody( $request, 'workflow.package.rollback' );
		if ( $confirmation instanceof WaicWorkflowPhase1Result ) { return $confirmation->toResponse(); }
		if ( is_array( $confirmation ) ) {
			return self::preparedActionResponse( 'workflow.package.rollback', $confirmation );
		}
		$body = self::closedBody( $request, array( 'target_version' ), 256 * KB_IN_BYTES, 'workflow.package.rollback' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->preflightLifecycle( 'workflow.package.rollback', $request['package_id'], $body['target_version'] )->toResponse();
	}

	public static function rollbackCommit( WP_REST_Request $request ) {
		return self::lifecycleCommit( $request, 'workflow.package.rollback' );
	}

	public static function uninstallPreflight( WP_REST_Request $request ) {
		$limit = self::limit( 'preflight', 'workflow.package.uninstall' );
		if ( true !== $limit ) { return $limit; }
		$confirmation = self::confirmationBody( $request, 'workflow.package.uninstall' );
		if ( $confirmation instanceof WaicWorkflowPhase1Result ) { return $confirmation->toResponse(); }
		if ( is_array( $confirmation ) ) {
			return self::preparedActionResponse( 'workflow.package.uninstall', $confirmation );
		}
		$body = self::closedBody( $request, array(), 256 * KB_IN_BYTES, 'workflow.package.uninstall', false );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->preflightLifecycle( 'workflow.package.uninstall', $request['package_id'] )->toResponse();
	}

	public static function uninstallCommit( WP_REST_Request $request ) {
		return self::lifecycleCommit( $request, 'workflow.package.uninstall' );
	}

	private static function lifecycleCommit( WP_REST_Request $request, $operation_id ) {
		$limit = self::limit( 'commit', $operation_id );
		if ( true !== $limit ) { return $limit; }
		$body = self::closedBody( $request, array( 'operation_handle','validation_stamp','confirmation' ), 256 * KB_IN_BYTES, $operation_id );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1PackService() )->commitLifecycle( $operation_id, $body['operation_handle'], $body['validation_stamp'], $body['confirmation'], $request->get_header( 'Idempotency-Key' ) )->toResponse();
	}

	public static function listPacks( WP_REST_Request $request ) {
		$limit = self::limit( 'read', 'workflow.package.list' );
		if ( true !== $limit ) { return $limit; }
		return ( new WaicWorkflowPhase1PackService() )->listPacks( (string) $request->get_param( 'cursor' ), (int) ( $request->get_param( 'limit' ) ?: 20 ) )->toResponse();
	}

	public static function readPack( WP_REST_Request $request ) {
		$limit = self::limit( 'read', 'workflow.package.read' );
		if ( true !== $limit ) { return $limit; }
		return ( new WaicWorkflowPhase1PackService() )->readPack( $request['package_id'] )->toResponse();
	}

	public static function exportPack( WP_REST_Request $request ) {
		$limit = self::limit( 'commit', 'workflow.export' );
		if ( true !== $limit ) { return $limit; }
		$body = self::closedBody( $request, array( 'package_id' ), 256 * KB_IN_BYTES, 'workflow.export' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		$response = ( new WaicWorkflowPhase1PackService() )->exportPack( $body['package_id'] )->toResponse();
		$response->header( 'X-Content-Type-Options', 'nosniff' );
		$response->header( 'Cache-Control', 'private, no-store' );
		$response->header( 'Content-Type', 'application/json; charset=' . get_option( 'blog_charset', 'UTF-8' ) );
		$response->header( 'Content-Disposition', 'attachment; filename="aiwu-workflow-pack.json"; filename*=UTF-8\'\'aiwu-workflow-pack.json' );
		return $response;
	}

	public static function compileAiPlan( WP_REST_Request $request ) {
		$limit = self::limit( 'ai', 'workflow.ai.plan.compile' );
		if ( true !== $limit ) { return $limit; }
		$confirmation = self::confirmationBody( $request, 'workflow.ai.plan.compile' );
		if ( $confirmation instanceof WaicWorkflowPhase1Result ) { return $confirmation->toResponse(); }
		if ( is_array( $confirmation ) ) {
			return self::preparedActionResponse( 'workflow.ai.plan.compile', $confirmation );
		}
		$body = self::closedBody( $request, array( 'intent','answers','snapshot_id' ), 64 * KB_IN_BYTES, 'workflow.ai.plan.compile' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1Ai() )->compile( $body )->toResponse();
	}

	public static function saveAiDraft( WP_REST_Request $request ) {
		$limit = self::limit( 'ai_save', 'workflow.draft.save' );
		if ( true !== $limit ) { return $limit; }
		$body = self::closedBody( $request, array( 'operation_handle','plan_digest','validation_stamp','confirmation' ), 256 * KB_IN_BYTES, 'workflow.draft.save' );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body->toResponse(); }
		return ( new WaicWorkflowPhase1Ai() )->saveDraft( $body, $request->get_header( 'Idempotency-Key' ) )->toResponse();
	}

	private static function parseImport( WP_REST_Request $request, $operation_id ) {
		$type = sanitize_key( (string) $request->get_param( 'input_type' ) );
		if ( ! in_array( $type, array( 'standalone_json_file','standalone_json_body','workflow_pack_zip' ), true ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'unsupported_input_type', __( 'The workflow input type is unsupported.', 'ai-copilot-content-generator' ), array(), array(), 415 );
		}
		if ( 'standalone_json_body' === $type ) {
			$body = self::closedBody( $request, array( 'input_type','workflow' ), 2 * MB_IN_BYTES + 4096, $operation_id );
			if ( $body instanceof WaicWorkflowPhase1Result ) { return $body; }
			$source = WaicWorkflowPhase1Canonicalizer::encode( $body['workflow'] );
			return array( 'type' => $type, 'source' => $source );
		}
		$files = $request->get_file_params();
		$file = isset( $files['pack'] ) && is_array( $files['pack'] ) ? $files['pack'] : array();
		if ( UPLOAD_ERR_OK !== ( isset( $file['error'] ) ? (int) $file['error'] : -1 ) || empty( $file['tmp_name'] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'A workflow upload is required.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$max = 'workflow_pack_zip' === $type ? 10 * MB_IN_BYTES : 2 * MB_IN_BYTES;
		if ( 'workflow_pack_zip' === $type ) { return array( 'type' => $type, 'source' => $file['tmp_name'] ); }
		$bytes = WaicWorkflowPhase1Staging::readUpload( $file['tmp_name'], $max );
		if ( false === $bytes ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The workflow upload could not be read.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		return array( 'type' => $type, 'source' => $bytes );
	}

	private static function closedBody( WP_REST_Request $request, array $required, $max_bytes, $operation_id, $non_empty = true ) {
		if ( ! self::isJsonRequest( $request ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'unsupported_media_type', __( 'The request media type is unsupported.', 'ai-copilot-content-generator' ), array(), array(), 415 );
		}
		$raw = $request->get_body();
		if ( strlen( $raw ) > $max_bytes ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'request_too_large', __( 'The request body is too large.', 'ai-copilot-content-generator' ), array(), array(), 413 );
		}
		try {
			$params = WaicWorkflowPhase1Json::decodeStrict( $raw, $max_bytes, 32, 64 * KB_IN_BYTES );
		} catch ( Exception $error ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The JSON request body is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		if ( $params instanceof stdClass ) { $params = array(); }
		if ( ! is_array( $params ) || ( ! empty( $params ) && array_keys( $params ) === range( 0, count( $params ) - 1 ) ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The JSON request body must be an object.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		$allowed = $required;
		if ( array_diff( array_keys( $params ), $allowed ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The request contains unknown fields.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $params ) || ( $non_empty && ( '' === $params[ $key ] || null === $params[ $key ] ) ) ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The request is missing a required field.', 'ai-copilot-content-generator' ), array(), array(), 400 );
			}
		}
		return $params;
	}

	private static function confirmationBody( WP_REST_Request $request, $operation_id ) {
		if ( ! self::isJsonRequest( $request ) ) { return false; }
		$raw = $request->get_body();
		if ( strlen( $raw ) > 256 * KB_IN_BYTES ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'request_too_large', __( 'The request body is too large.', 'ai-copilot-content-generator' ), array(), array(), 413 );
		}
		try {
			$params = WaicWorkflowPhase1Json::decodeStrict( $raw, 256 * KB_IN_BYTES, 32, 64 * KB_IN_BYTES );
		} catch ( Exception $error ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'The JSON request body is invalid.', 'ai-copilot-content-generator' ), array(), array(), 400 );
		}
		if ( ! is_array( $params ) || ! array_key_exists( 'preflight_action', $params ) ) { return false; }
		$action = isset( $params['preflight_action'] ) ? $params['preflight_action'] : '';
		$required = 'map_dependencies' === $action ? array( 'preflight_action','operation_handle','validation_stamp','mappings' ) : array( 'preflight_action','operation_handle','validation_stamp','confirmed' );
		$body = self::closedBody( $request, $required, 256 * KB_IN_BYTES, $operation_id );
		if ( $body instanceof WaicWorkflowPhase1Result ) { return $body; }
		if ( 'confirm' === $body['preflight_action'] && true === $body['confirmed'] ) { return $body; }
		if ( 'map_dependencies' === $body['preflight_action'] && is_array( $body['mappings'] ) ) { return $body; }
		return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'An explicit preflight action is required.', 'ai-copilot-content-generator' ), array(), array(), 400 );
	}

	private static function preparedActionResponse( $operation_id, array $body ) {
		$service = new WaicWorkflowPhase1PackService();
		if ( 'map_dependencies' === $body['preflight_action'] ) {
			return $service->mapDependencies( $operation_id, $body['operation_handle'], $body['validation_stamp'], $body['mappings'] )->toResponse();
		}
		if ( 'confirm' !== $body['preflight_action'] ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'invalid_request_envelope', __( 'Explicit confirmation is required.', 'ai-copilot-content-generator' ), array(), array(), 400 )->toResponse();
		}
		return $service->confirmPrepared( $operation_id, $body['operation_handle'], $body['validation_stamp'] )->toResponse();
	}

	private static function isJsonRequest( WP_REST_Request $request ) {
		$content_type = strtolower( trim( (string) $request->get_header( 'Content-Type' ) ) );
		$semicolon = strpos( $content_type, ';' );
		if ( false !== $semicolon ) { $content_type = trim( substr( $content_type, 0, $semicolon ) ); }
		return 'application/json' === $content_type;
	}

	private static function limit( $family, $operation_id ) {
		$result = WaicWorkflowPhase1RateLimiter::check( $family, $operation_id );
		return true === $result ? true : $result->toResponse();
	}

	private static function resultPackageId( WaicWorkflowPhase1Result $result ) {
		$array = $result->toArray();
		return isset( $array['meta']['package_id'] ) ? $array['meta']['package_id'] : '';
	}
}
