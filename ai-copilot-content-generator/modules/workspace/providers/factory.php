<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'contracts.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'errorNormalizer.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'usageNormalizer.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'transport.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'credentialStore.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'adapters.php';

class WaicProviderAdapterFactory {
	private static $lastFailure = null;
	private static $types = array(
		'openai_compatible' => 'WaicOpenAICompatibleAdapter',
		'anthropic' => 'WaicAnthropicAdapter',
		'gemini' => 'WaicGeminiAdapter',
		'cohere_v2' => 'WaicCohereV2Adapter',
		'qianfan' => 'WaicQianfanAdapter',
		'azure_openai' => 'WaicAzureOpenAiAdapter',
		'bedrock_runtime' => 'WaicBedrockRuntimeAdapter',
		'firefly' => 'WaicFireflyAdapter',
		'flux' => 'WaicFluxAdapter',
		'manual_external' => 'WaicManualExternalAdapter',
	);

	public static function getAdapter( $providerId, $apiMode = '', $provider = array() ) {
		$providerId = sanitize_key( (string) $providerId ); $provider = is_array( $provider ) ? $provider : array();
		if ( '' === $providerId || empty( $provider['adapter'] ) ) { return self::failure( 'CONFIGURATION', 'Provider adapter is not shipped.' ); }
		$status = sanitize_key( (string) WaicUtils::getArrayValue( $provider, 'status', 'beta' ) );
		if ( in_array( $status, array('future', 'disabled'), true ) ) { return self::failure( 'CONFIGURATION', 'Provider is not enabled.' ); }
		$modes = WaicUtils::getArrayValue( $provider, 'api_modes', array(), 2 );
		$apiMode = sanitize_key( (string) $apiMode );
		if ( '' !== $apiMode && ! empty( $modes ) && ! in_array( $apiMode, $modes, true ) ) { return self::failure( 'CAPABILITY_UNSUPPORTED', 'Provider API mode is not shipped.' ); }
		$type = sanitize_key( (string) WaicUtils::getArrayValue( $provider, 'adapter_type', '' ) );
		if ( '' === $type ) { $type = self::legacyType( $providerId ); }
		if ( empty( self::$types[ $type ] ) || ! class_exists( self::$types[ $type ] ) ) { return self::failure( 'CONFIGURATION', 'Provider adapter is not shipped.' ); }
		self::$lastFailure = null;
		return new self::$types[ $type ]( $providerId, $apiMode, $provider );
	}

	public static function hasAdapter( $providerId, $apiMode = '', $provider = array() ) { return self::getAdapter( $providerId, $apiMode, $provider ) instanceof WaicProviderAdapterInterface; }
	public static function getLastFailure() { return self::$lastFailure; }
	private static function failure( $code, $message ) { self::$lastFailure = new WaicProviderFailure( $code, $message ); return false; }
	private static function legacyType( $providerId ) {
		$map = array('open-ai' => 'openai_compatible', 'deep-seek' => 'openai_compatible', 'perplexity' => 'openai_compatible', 'openrouter' => 'openai_compatible', 'mistral' => 'openai_compatible', 'xai' => 'openai_compatible', 'kimi' => 'openai_compatible', 'zhipu' => 'openai_compatible', 'qwen' => 'openai_compatible', 'doubao' => 'openai_compatible', 'llama' => 'openai_compatible', 'claude' => 'anthropic', 'gemini' => 'gemini', 'cohere' => 'cohere_v2', 'baidu-qianfan' => 'qianfan', 'azure-openai' => 'azure_openai', 'bedrock' => 'bedrock_runtime', 'adobe-firefly' => 'firefly', 'bfl' => 'flux', 'midjourney' => 'manual_external');
		return isset( $map[ $providerId ] ) ? $map[ $providerId ] : '';
	}
}
