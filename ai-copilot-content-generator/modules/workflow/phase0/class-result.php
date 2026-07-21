<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Result {
	const SCHEMA = 'workflow_result.v1';

	private $data;

	private function __construct( $code, $message_key, $impact_key, $remediation_key, $terminal, $actions = array() ) {
		$allowed_actions = array( 'retry', 'review', 'disable', 'revalidate', 'reenter_credential' );
		$actions         = array_values( array_intersect( array_map( 'sanitize_key', (array) $actions ), $allowed_actions ) );
		$code            = strtoupper( (string) $code );
		if ( ! preg_match( '/^WF-[A-Z0-9-]{3,64}$/', $code ) ) {
			$code = 'WF-RESULT-001';
		}

		$this->data = array(
			'schema'          => self::SCHEMA,
			'code'            => $code,
			'message_key'     => sanitize_key( $message_key ),
			'impact_key'      => sanitize_key( $impact_key ),
			'remediation_key' => sanitize_key( $remediation_key ),
			'correlation_id'  => self::newCorrelationId(),
			'actions'         => $actions,
			'terminal'        => (bool) $terminal,
		);
	}

	public static function success( $code, $message_key = 'workflow_completed' ) {
		return new self( $code, $message_key, 'workflow_effect_applied', 'none', true );
	}

	public static function blocked( $code, $impact_key, $remediation_key, $actions = array() ) {
		return new self( $code, 'workflow_request_blocked', $impact_key, $remediation_key, true, $actions );
	}

	public static function unavailable( $code, $remediation_key ) {
		return new self( $code, 'workflow_temporarily_unavailable', 'workflow_effect_not_applied', $remediation_key, true, array( 'retry' ) );
	}

	public function toArray() {
		return $this->data;
	}

	public function getCode() {
		return $this->data['code'];
	}

	private static function newCorrelationId() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}
		$bytes    = random_bytes( 16 );
		$bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );
		$bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );
		$hex      = bin2hex( $bytes );
		return substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-' . substr( $hex, 12, 4 ) . '-' . substr( $hex, 16, 4 ) . '-' . substr( $hex, 20 );
	}
}
