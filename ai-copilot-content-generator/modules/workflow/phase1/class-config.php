<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Config {
	const OPTION = 'waic_workflow_phase1_flags';
	const SNAPSHOT_OPTION = 'waic_workflow_phase1_ai_snapshot';
	const IMPLEMENTATION_DECISION = 'P1-OWNER-START-001';

	public static function defaults() {
		return array(
			'core' => false,
			'ui'   => false,
			'ai'   => false,
		);
	}

	public static function flags() {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		return array_merge( self::defaults(), array_intersect_key( $stored, self::defaults() ) );
	}

	public static function isEnabled( $feature ) {
		$feature = sanitize_key( (string) $feature );
		$constants = array(
			'core' => 'AIWU_WORKFLOW_PHASE1A_ENABLED',
			'ui'   => 'AIWU_WORKFLOW_PHASE1B_ENABLED',
			'ai'   => 'AIWU_WORKFLOW_PHASE1C_ENABLED',
		);
		if ( ! isset( $constants[ $feature ] ) ) {
			return false;
		}
		$flags = self::flags();
		$enabled = defined( $constants[ $feature ] )
			? true === constant( $constants[ $feature ] )
			: true === $flags[ $feature ];
		if ( 'ui' === $feature ) {
			$enabled = $enabled && self::isEnabled( 'core' );
		}
		if ( 'ai' === $feature ) {
			$enabled = $enabled && self::isEnabled( 'ui' ) && self::hasAcceptedAiSnapshot();
		}
		return $enabled;
	}

	public static function isTestMode() {
		return defined( 'AIWU_WORKFLOW_PHASE1_TEST_MODE' )
			&& true === constant( 'AIWU_WORKFLOW_PHASE1_TEST_MODE' );
	}

	public static function aiSnapshot() {
		$snapshot = get_option( self::SNAPSHOT_OPTION, array() );
		return is_array( $snapshot ) ? $snapshot : array();
	}

	public static function hasAcceptedAiSnapshot() {
		$snapshot = self::aiSnapshot();
		if ( self::isTestMode() && isset( $snapshot['snapshot_id'] ) && 'aiwu-test-stub-v1' === $snapshot['snapshot_id'] ) {
			return true;
		}
		if ( ! defined( 'AIWU_WORKFLOW_PHASE1C_EVIDENCE_ACCEPTED' ) || true !== constant( 'AIWU_WORKFLOW_PHASE1C_EVIDENCE_ACCEPTED' ) ) {
			return false;
		}
		$required = array(
			'snapshot_id', 'status', 'provider_adapter_id', 'provider_adapter_version',
			'provider', 'model_id', 'response_model_revision', 'api_revision', 'region',
			'endpoint_profile', 'dpa_revision', 'retention', 'pricing_digest',
			'tokenizer_digest', 'prompt_digest', 'schema_digest', 'descriptor_digest',
			'policy_digest', 'tool_config_digest', 'evaluation_corpus_digest',
			'evaluation_rubric_digest', 'input_token_limit', 'output_token_limit',
			'raw_byte_limit', 'timeout_seconds', 'call_limit', 'temperature', 'seed',
		);
		foreach ( $required as $key ) {
			if ( empty( $snapshot[ $key ] ) ) {
				return false;
			}
		}
		if ( 'accepted' !== $snapshot['status'] || preg_match( '/(?:^|[-_.])(latest|current|default)(?:$|[-_.])/i', $snapshot['model_id'] ) ) {
			return false;
		}
		foreach ( array( 'pricing_digest', 'tokenizer_digest', 'prompt_digest', 'schema_digest', 'descriptor_digest', 'policy_digest', 'tool_config_digest', 'evaluation_corpus_digest', 'evaluation_rubric_digest' ) as $key ) {
			if ( ! preg_match( '/^sha256:[a-f0-9]{64}$/', (string) $snapshot[ $key ] ) ) {
				return false;
			}
		}
		if ( 16384 !== (int) $snapshot['input_token_limit'] || 4096 !== (int) $snapshot['output_token_limit'] || 524288 !== (int) $snapshot['raw_byte_limit'] || 45 !== (int) $snapshot['timeout_seconds'] || 2 !== (int) $snapshot['call_limit'] || 0.0 !== (float) $snapshot['temperature'] ) {
			return false;
		}
		return true;
	}

	public static function ensureCapabilities() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		WaicWorkflowPhase1Storage::ensureCapabilities();
	}

	public static function declareHposCompatibility() {
		if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			return;
		}
		$main_file = dirname( __DIR__, 3 ) . '/ai-copilot-content-generator.php';
		$compatible = defined( 'AIWU_WORKFLOW_PHASE1_HPOS_EVIDENCE_ACCEPTED' )
			&& true === constant( 'AIWU_WORKFLOW_PHASE1_HPOS_EVIDENCE_ACCEPTED' );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			$main_file,
			$compatible
		);
	}

	public static function hardLimits() {
		return array(
			'archive_compressed'   => 10 * MB_IN_BYTES,
			'archive_uncompressed' => 50 * MB_IN_BYTES,
			'archive_entries'      => 100,
			'path_depth'           => 8,
			'path_bytes'           => 180,
			'path_segment_bytes'   => 80,
			'entry_bytes'          => 10 * MB_IN_BYTES,
			'manifest_bytes'       => 1 * MB_IN_BYTES,
			'workflow_bytes'       => 2 * MB_IN_BYTES,
			'json_depth'           => 32,
			'scalar_bytes'         => 64 * KB_IN_BYTES,
			'workflows'            => 20,
			'nodes'                => 100,
			'edges'                => 200,
			'compression_ratio'    => 100,
		);
	}
}
