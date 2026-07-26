<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicProviderUsageNormalizer {
	public static function normalize( $raw ) {
		$raw = is_object( $raw ) ? json_decode( wp_json_encode( $raw ), true ) : $raw;
		$raw = is_array( $raw ) ? $raw : array();
		$usage = WaicUtils::getArrayValue( $raw, 'usage', $raw, 2 );
		$out = array(
			'input_tokens' => max( 0, (int) WaicUtils::getArrayValue( $usage, 'prompt_tokens', WaicUtils::getArrayValue( $usage, 'input_tokens', 0, 1 ), 1 ) ),
			'output_tokens' => max( 0, (int) WaicUtils::getArrayValue( $usage, 'completion_tokens', WaicUtils::getArrayValue( $usage, 'output_tokens', 0, 1 ), 1 ) ),
			'reasoning_tokens' => 0,
			'cached_tokens' => 0,
			'cache_write_tokens' => 0,
			'total_tokens' => max( 0, (int) WaicUtils::getArrayValue( $usage, 'total_tokens', 0, 1 ) ),
			'est_flags' => 0,
		);
		$promptDetails = WaicUtils::getArrayValue( $usage, 'prompt_tokens_details', array(), 2 );
		$completionDetails = WaicUtils::getArrayValue( $usage, 'completion_tokens_details', array(), 2 );
		$out['cached_tokens'] = max( 0, (int) WaicUtils::getArrayValue( $promptDetails, 'cached_tokens', WaicUtils::getArrayValue( $usage, 'cache_read_input_tokens', 0, 1 ), 1 ) );
		$out['cache_write_tokens'] = max( 0, (int) WaicUtils::getArrayValue( $usage, 'cache_creation_input_tokens', 0, 1 ) );
		$out['reasoning_tokens'] = max( 0, (int) WaicUtils::getArrayValue( $completionDetails, 'reasoning_tokens', WaicUtils::getArrayValue( $usage, 'reasoning_tokens', 0, 1 ), 1 ) );
		if ( empty( $out['total_tokens'] ) ) {
			$out['total_tokens'] = $out['input_tokens'] + $out['output_tokens'] + $out['reasoning_tokens'];
		}
		if ( isset( $usage['cost'] ) && is_numeric( $usage['cost'] ) ) {
			$out['provider_reported_cost'] = (float) $usage['cost'];
		}
		return $out;
	}
}
