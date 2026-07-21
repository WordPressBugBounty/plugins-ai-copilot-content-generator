<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Result {
	const SCHEMA = 'aiwu.operation-result.v1';

	private $operation_id;
	private $state;
	private $code;
	private $message;
	private $issues;
	private $meta;
	private $http_status;
	private $correlation_id;

	public function __construct( $operation_id, $state, $code, $message, array $issues = array(), array $meta = array(), $http_status = 200 ) {
		$this->operation_id = (string) $operation_id;
		$this->state = (string) $state;
		$this->code = sanitize_key( (string) $code );
		$this->message = (string) $message;
		$this->issues = self::redactIssues( $issues );
		$this->meta = self::redactMeta( $meta );
		$this->http_status = (int) $http_status;
		$this->correlation_id = 'urn:aiwu:correlation:' . wp_generate_uuid4();
	}

	public static function success( $operation_id, $code, $message, array $meta = array(), $http_status = 200 ) {
		return new self( $operation_id, 'succeeded', $code, $message, array(), $meta, $http_status );
	}

	public static function blocked( $operation_id, $code, $message, array $issues = array(), array $meta = array(), $http_status = 422 ) {
		return new self( $operation_id, 'blocked', $code, $message, $issues, $meta, $http_status );
	}

	public static function waiting( $operation_id, $state, $code, $message, array $issues = array(), array $meta = array() ) {
		$allowed = array( 'awaiting_confirmation', 'awaiting_dependency_mapping', 'recovery_pending' );
		$state = in_array( $state, $allowed, true ) ? $state : 'blocked';
		return new self( $operation_id, $state, $code, $message, $issues, $meta, 'recovery_pending' === $state ? 202 : 200 );
	}

	public static function rateLimited( $operation_id, $retry_after ) {
		return new self(
			$operation_id,
			'rate_limited',
			'rate_limited',
			__( 'Too many workflow requests. Try again later.', 'ai-copilot-content-generator' ),
			array(),
			array( 'retry_after' => max( 1, min( 3600, (int) $retry_after ) ) ),
			429
		);
	}

	public function toArray() {
		return array(
			'schema'         => self::SCHEMA,
			'operation_id'   => $this->operation_id,
			'correlation_id' => $this->correlation_id,
			'state'          => $this->state,
			'code'           => $this->code,
			'message'        => $this->message,
			'issues'         => $this->issues,
			'meta'           => $this->meta,
		);
	}

	public function toResponse() {
		$response = new WP_REST_Response( $this->toArray(), $this->http_status );
		$response->header( 'Cache-Control', 'no-store' );
		if ( 429 === $this->http_status && isset( $this->meta['retry_after'] ) ) {
			$response->header( 'Retry-After', (string) $this->meta['retry_after'] );
		}
		return $response;
	}

	public function getState() {
		return $this->state;
	}

	public function getCode() {
		return $this->code;
	}

	public function getHttpStatus() {
		return $this->http_status;
	}

	private static function redactIssues( array $issues ) {
		$result = array();
		foreach ( array_slice( $issues, 0, 100 ) as $issue ) {
			if ( ! is_array( $issue ) ) {
				continue;
			}
			$context = isset( $issue['context'] ) && is_array( $issue['context'] ) ? $issue['context'] : array();
			$context = array_intersect_key(
				$context,
				array_flip( array( 'field', 'node_id', 'edge_id', 'package_id', 'version', 'limit', 'actual', 'digest' ) )
			);
			$result[] = array(
				'severity'    => in_array( isset( $issue['severity'] ) ? $issue['severity'] : '', array( 'error', 'warning', 'info' ), true ) ? $issue['severity'] : 'error',
				'code'        => sanitize_key( isset( $issue['code'] ) ? $issue['code'] : 'internal_boundary_failed' ),
				'context'     => $context,
				'remediation' => sanitize_key( isset( $issue['remediation'] ) ? $issue['remediation'] : 'review_workflow' ),
			);
		}
		return $result;
	}

	private static function redactMeta( array $meta ) {
		$allowed = array(
			'retryable', 'retry_after', 'package_id', 'version', 'artifact_digest',
			'workflow_digests', 'operation_handle', 'validation_stamp', 'confirmation',
			'expires_at', 'issues_count', 'created_objects', 'cursor', 'items', 'item',
			'export', 'risk', 'dependencies', 'effects', 'data_paths', 'plan_digest',
			'estimated_max_cost_micro_usd', 'actual_cost_micro_usd', 'provider', 'model_id',
			'mapping_digest',
		);
		return array_intersect_key( $meta, array_flip( $allowed ) );
	}
}
