<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class WaicAbstractProviderAdapter implements WaicProviderAdapterInterface {
	protected $id;
	protected $apiMode;
	protected $manifest;
	protected $options = array();
	protected $lastFailure = null;

	public function __construct( $id, $apiMode = '', $manifest = array() ) {
		$this->id = sanitize_key( (string) $id );
		$this->apiMode = sanitize_key( (string) $apiMode );
		$this->manifest = is_array( $manifest ) ? $manifest : array();
	}
	public function init() { return $this; }
	public function getId() { return $this->id; }
	public function getEngine() { return $this->id; }
	public function getEngineModel( $type = '' ) {
		$field = 'image' === $type ? WaicUtils::getArrayValue( $this->manifest, 'image_model_field', '' ) : WaicUtils::getArrayValue( $this->manifest, 'model_field', $this->id . '_model' );
		return WaicUtils::getArrayValue( $this->options, $field, '' );
	}
	public function getDeclaredCapabilities() { return WaicProviderCapabilities::normalizeCapabilityList( WaicUtils::getArrayValue( $this->manifest, 'capabilities', array(), 2 ) ); }
	public function setApiOptions( $options ) {
		$this->options = array_merge( $this->loadStoredOptions(), $this->options, is_array( $options ) ? $options : array() );
		$validation = $this->validateProfile( array( 'configuration' => $this->options ) );
		if ( $validation instanceof WaicProviderFailure ) { $this->lastFailure = $validation; $this->pushFailure(); return false; }
		return true;
	}
	public function validateProfile( $profile ) {
		$config = $profile instanceof WaicProviderProfile ? $profile->configuration : WaicUtils::getArrayValue( $profile, 'configuration', is_array( $profile ) ? $profile : array(), 2 );
		$required = WaicUtils::getArrayValue( $this->manifest, 'credential_fields', array(), 2 );
		if ( empty( $required ) ) { $required = $this->defaultCredentialFields(); }
		foreach ( $required as $field ) {
			if ( ! empty( $config[ $field ] ) ) { continue; }
			if ( 'adobe_firefly_access_token' === $field && ! empty( $config['adobe_firefly_client_secret'] ) ) { continue; }
			return new WaicProviderFailure( 'CONFIGURATION', 'Provider credentials are incomplete.' );
		}
		if ( 'custom_oai' === WaicUtils::getArrayValue( $this->manifest, 'endpoint_policy', '' ) && ! WaicProviderTransport::validateUrl( WaicUtils::getArrayValue( $config, 'base_url', WaicUtils::getArrayValue( $config, $this->id . '_base_url', '' ) ), false ) ) {
			return new WaicProviderFailure( 'CONFIGURATION', 'Custom provider endpoint is not permitted.' );
		}
		return true;
	}
	public function supports( $operation, $profile = array(), $model = '' ) {
		$operation = 'image' === $operation ? 'image_generate' : sanitize_key( (string) $operation );
		$capabilities = $this->getDeclaredCapabilities();
		if ( 'image_generate' === $operation && in_array( 'image', $capabilities, true ) ) { return true; }
		return in_array( $operation, $capabilities, true );
	}
	public function testConnection( $profile = array(), $context = array() ) {
		$model = is_string( $profile ) ? $profile : WaicUtils::getArrayValue( $context, 'model', $this->getEngineModel() );
		if ( $this->supports( 'image_generate', $profile, $model ) && ! $this->supports( 'chat', $profile, $model ) ) { return $this->getImage( array('prompt' => 'connection test', 'model' => $model, 'n' => 1) ); }
		return $this->getText( array('prompt' => 'Reply with OK.', 'model' => $model, 'max_tokens' => 8, 'temperature' => 0) );
	}
	public function discoverModels( $profile = array(), $context = array() ) { return $this->getModels(); }
	public function getModels() { return array('models' => array(), 'img_models' => array(), 'tokens' => array()); }
	public function chat( $request ) { return $this->getText( $this->requestToArray( $request ) ); }
	public function stream( $request, $sink ) {
		$result = $this->getText( $this->requestToArray( $request ) );
		if ( is_callable( $sink ) && is_array( $result ) && empty( $result['results']['error'] ) ) { call_user_func( $sink, WaicUtils::getArrayValue( $result['results'], 'data', '' ) ); }
		return $result;
	}
	public function createImage( $request ) { return $this->getImage( $this->requestToArray( $request ) ); }
	public function pollImage( $job ) { return new WaicProviderFailure( 'CAPABILITY_UNSUPPORTED', 'Image job polling is unavailable.' ); }
	public function embed( $request ) { return $this->sendEmbeddings( $this->requestToArray( $request ) ); }
	public function getText( $params, $stream = null ) {
		if ( ! $this->supports( 'chat', array(), WaicUtils::getArrayValue( $params, 'model', '' ) ) ) { return $this->unsupported(); }
		$request = $this->buildTextRequest( $params );
		return $this->sendTextRequest( $request, $params );
	}
	public function getImage( $params ) {
		if ( ! $this->supports( 'image_generate', array(), WaicUtils::getArrayValue( $params, 'model', '' ) ) ) { return $this->unsupported(); }
		$request = $this->buildImageRequest( $params );
		$response = $this->dispatch( $request );
		if ( $response instanceof WaicProviderFailure ) { return $this->legacyFailure( $response ); }
		$url = $this->imageUrl( $response['body'] );
		if ( '' === $url ) { return $this->legacyFailure( new WaicProviderFailure( 'UPSTREAM', 'Provider returned no image result.', WaicUtils::getArrayValue( $response, 'correlation_id', '' ) ) ); }
		return array('results' => array('error' => 0, 'data' => $url, 'raw_response' => $response['body'], 'usage' => WaicProviderUsageNormalizer::normalize( $response['body'] )), 'params' => $params);
	}
	public function sendEmbeddings( $params, $method = 'POST' ) {
		if ( ! $this->supports( 'embedding', array(), WaicUtils::getArrayValue( $params, 'model', '' ) ) ) { return $this->unsupported(); }
		$model = WaicUtils::getArrayValue( $params, 'model', $this->getEngineModel() );
		$request = array('method' => $method, 'url' => rtrim( $this->baseUrl(), '/' ) . '/embeddings', 'headers' => $this->authHeaders(), 'body' => array('model' => $model, 'input' => WaicUtils::getArrayValue( $params, 'input', WaicUtils::getArrayValue( $params, 'text', '' ) )));
		$response = $this->dispatch( $request );
		if ( $response instanceof WaicProviderFailure ) { return $this->legacyFailure( $response ); }
		return array('results' => array('error' => 0, 'data' => WaicUtils::getArrayValue( $response['body'], 'data', array() ), 'raw_response' => $response['body'], 'usage' => WaicProviderUsageNormalizer::normalize( $response['body'] )), 'params' => $params);
	}
	public function getToolsAnswer( $answer, $tool ) { return array('role' => 'tool', 'tool_call_id' => is_object( $tool ) && isset( $tool->id ) ? (string) $tool->id : '', 'content' => wp_json_encode( $answer )); }
	public function sendFile( $params ) { return $this->unsupported(); }
	public function getFineTunes( $params, $method = 'POST', $job = false ) { return $this->unsupported(); }
	public function normalizeUsage( $raw ) { return WaicProviderUsageNormalizer::normalize( $raw ); }
	public function normalizeError( $raw ) { return WaicProviderErrorNormalizer::redact( is_string( $raw ) ? $raw : wp_json_encode( $raw ), $this->secrets() ); }

	public function buildTextRequest( $params ) {
		$model = WaicUtils::getArrayValue( $params, 'model', $this->getEngineModel() );
		$body = $this->textBody( $params, $model );
		return array('method' => 'POST', 'url' => rtrim( $this->baseUrl(), '/' ) . $this->chatPath(), 'headers' => $this->authHeaders(), 'body' => $body);
	}
	protected function buildImageRequest( $params ) {
		$model = WaicUtils::getArrayValue( $params, 'model', $this->getEngineModel( 'image' ) );
		$body = array('model' => $model, 'prompt' => WaicUtils::getArrayValue( $params, 'prompt', '' ));
		foreach ( array('n', 'size', 'quality', 'style') as $key ) { if ( isset( $params[ $key ] ) ) { $body[ $key ] = $params[ $key ]; } }
		return array('method' => 'POST', 'url' => rtrim( $this->baseUrl(), '/' ) . '/images/generations', 'headers' => $this->authHeaders(), 'body' => $body);
	}
	protected function sendTextRequest( $request, $params ) {
		$response = $this->dispatch( $request );
		if ( $response instanceof WaicProviderFailure ) { return $this->legacyFailure( $response ); }
		$body = $response['body']; $text = $this->textFromBody( $body );
		$tools = WaicUtils::getArrayValue( WaicUtils::getArrayValue( $body, 'choices', array(), 2 ), 0, array(), 2 );
		$toolCalls = WaicUtils::getArrayValue( WaicUtils::getArrayValue( $tools, 'message', array(), 2 ), 'tool_calls', array(), 2 );
		return array('results' => array('error' => 0, 'data' => empty( $toolCalls ) ? $text : 'tool_calls', 'tools' => $toolCalls, 'tokens' => WaicUtils::getArrayValue( WaicProviderUsageNormalizer::normalize( $body ), 'total_tokens', 0, 1 ), 'raw_response' => $body, 'usage' => WaicProviderUsageNormalizer::normalize( $body )), 'params' => $params);
	}
	protected function dispatch( $request ) { $request['secrets'] = $this->secrets(); return WaicProviderTransport::request( $request, 'custom_oai' === WaicUtils::getArrayValue( $this->manifest, 'endpoint_policy', '' ) ); }
	protected function textBody( $params, $model ) {
		$messages = WaicUtils::getArrayValue( $params, 'messages', array(), 2 );
		if ( empty( $messages ) ) { $messages = array(array('role' => 'user', 'content' => WaicUtils::getArrayValue( $params, 'prompt', '' ))); }
		$body = array('model' => $model, 'messages' => $messages);
		foreach ( array('max_tokens', 'max_completion_tokens', 'temperature', 'top_p', 'frequency_penalty', 'presence_penalty', 'tools', 'tool_choice', 'stream') as $key ) { if ( array_key_exists( $key, $params ) ) { $body[ $key ] = $params[ $key ]; } }
		return $body;
	}
	protected function chatPath() { return 'responses' === $this->apiMode ? '/responses' : '/chat/completions'; }
	protected function baseUrl() {
		$custom = WaicUtils::getArrayValue( $this->options, 'base_url', WaicUtils::getArrayValue( $this->options, $this->id . '_base_url', '' ) );
		if ( 'custom_oai' === WaicUtils::getArrayValue( $this->manifest, 'endpoint_policy', '' ) && ! empty( $custom ) ) { return rtrim( $custom, '/' ); }
		$map = array('open-ai' => 'https://api.openai.com/v1', 'deep-seek' => 'https://api.deepseek.com/v1', 'perplexity' => 'https://api.perplexity.ai', 'openrouter' => 'https://openrouter.ai/api/v1', 'mistral' => 'https://api.mistral.ai/v1', 'xai' => 'https://api.x.ai/v1', 'kimi' => 'https://api.moonshot.ai/v1', 'zhipu' => 'https://open.bigmodel.cn/api/paas/v4', 'qwen' => 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1', 'doubao' => 'https://ark.cn-beijing.volces.com/api/v3', 'llama' => '');
		return isset( $map[ $this->id ] ) ? $map[ $this->id ] : '';
	}
	protected function authHeaders() { return array('Authorization' => 'Bearer ' . $this->apiKey(), 'Content-Type' => 'application/json', 'Accept' => 'application/json'); }
	protected function apiKey() { return (string) WaicUtils::getArrayValue( $this->options, WaicUtils::getArrayValue( $this->manifest, 'api_key_field', $this->id . '_api_key' ), '' ); }
	protected function defaultCredentialFields() { $field = WaicUtils::getArrayValue( $this->manifest, 'api_key_field', $this->id . '_api_key' ); return empty( $field ) ? array() : array( $field ); }
	protected function loadStoredOptions() { if ( class_exists( 'WaicFrame' ) && WaicFrame::_()->getModule('options') ) { $stored = WaicFrame::_()->getModule('options')->get('api'); return is_array( $stored ) ? $stored : array(); } return array(); }
	protected function secrets() { $out = array(); foreach ( $this->options as $key => $value ) { if ( false !== strpos( $key, 'key' ) || false !== strpos( $key, 'secret' ) || false !== strpos( $key, 'token' ) ) { $out[] = $value; } } return $out; }
	protected function imageUrl( $body ) { $data = WaicUtils::getArrayValue( $body, 'data', array(), 2 ); $first = WaicUtils::getArrayValue( $data, 0, array(), 2 ); return esc_url_raw( (string) WaicUtils::getArrayValue( $first, 'url', WaicUtils::getArrayValue( $first, 'b64_json', '' ) ) ); }
	protected function textFromBody( $body ) {
		$choices = WaicUtils::getArrayValue( $body, 'choices', array(), 2 ); $first = WaicUtils::getArrayValue( $choices, 0, array(), 2 );
		$text = WaicUtils::getArrayValue( WaicUtils::getArrayValue( $first, 'message', array(), 2 ), 'content', WaicUtils::getArrayValue( $first, 'text', '' ) );
		if ( is_array( $text ) ) { $text = wp_json_encode( $text ); }
		return sanitize_textarea_field( (string) $text );
	}
	protected function unsupported() { $failure = new WaicProviderFailure( 'CAPABILITY_UNSUPPORTED', 'Selected provider does not support this operation.' ); $this->lastFailure = $failure; $this->pushFailure(); return false; }
	protected function legacyFailure( $failure ) { $this->lastFailure = $failure; $this->pushFailure(); return false; }
	protected function pushFailure() { if ( $this->lastFailure && class_exists( 'WaicFrame' ) ) { WaicFrame::_()->pushError( $this->lastFailure->message . ( $this->lastFailure->correlation_id ? ' [' . $this->lastFailure->correlation_id . ']' : '' ) ); } }
	protected function requestToArray( $request ) { if ( is_object( $request ) ) { return get_object_vars( $request ); } return is_array( $request ) ? $request : array(); }
}

class WaicOpenAICompatibleAdapter extends WaicAbstractProviderAdapter {
	public function getModels() {
		if ( ! $this->supports( 'model_discovery' ) ) { return parent::getModels(); }
		$response = $this->dispatch( array('method' => 'GET', 'url' => rtrim( $this->baseUrl(), '/' ) . '/models', 'headers' => $this->authHeaders(), 'body' => array()) );
		if ( $response instanceof WaicProviderFailure ) { return parent::getModels(); }
		$out = array(); foreach ( WaicUtils::getArrayValue( $response['body'], 'data', array(), 2 ) as $model ) { $id = sanitize_text_field( (string) WaicUtils::getArrayValue( $model, 'id', '' ) ); if ( '' !== $id ) { $out[ $id ] = $id; } }
		return array('models' => $out, 'img_models' => array(), 'tokens' => array());
	}
}

class WaicAnthropicAdapter extends WaicAbstractProviderAdapter {
	protected function baseUrl() { return 'https://api.anthropic.com/v1'; }
	protected function chatPath() { return '/messages'; }
	protected function authHeaders() { return array('x-api-key' => $this->apiKey(), 'anthropic-version' => '2023-06-01', 'Content-Type' => 'application/json'); }
	protected function textBody( $params, $model ) { $body = parent::textBody( $params, $model ); $body['max_tokens'] = max( 1, (int) WaicUtils::getArrayValue( $body, 'max_tokens', 1024, 1 ) ); unset( $body['temperature'], $body['frequency_penalty'], $body['presence_penalty'] ); return $body; }
	protected function textFromBody( $body ) { $text = parent::textFromBody( $body ); if ( '' !== $text ) { return $text; } $content = WaicUtils::getArrayValue( $body, 'content', array(), 2 ); return sanitize_textarea_field( (string) WaicUtils::getArrayValue( WaicUtils::getArrayValue( $content, 0, array(), 2 ), 'text', '' ) ); }
}

class WaicGeminiAdapter extends WaicAbstractProviderAdapter {
	protected function baseUrl() { return 'https://generativelanguage.googleapis.com/v1beta'; }
	public function buildTextRequest( $params ) {
		$model = WaicUtils::getArrayValue( $params, 'model', $this->getEngineModel() ); $messages = WaicUtils::getArrayValue( $params, 'messages', array(), 2 ); if ( empty( $messages ) ) { $messages = array(array('role' => 'user', 'content' => WaicUtils::getArrayValue( $params, 'prompt', '' ))); }
		$contents = array(); foreach ( $messages as $message ) { $contents[] = array('role' => 'assistant' === WaicUtils::getArrayValue( $message, 'role', '' ) ? 'model' : 'user', 'parts' => array(array('text' => (string) WaicUtils::getArrayValue( $message, 'content', '' )))); }
		return array('method' => 'POST', 'url' => $this->baseUrl() . '/models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $this->apiKey() ), 'headers' => array('Content-Type' => 'application/json'), 'body' => array('contents' => $contents, 'generationConfig' => array('maxOutputTokens' => (int) WaicUtils::getArrayValue( $params, 'max_tokens', 1024, 1 ))));
	}
	protected function textFromBody( $body ) { $text = parent::textFromBody( $body ); if ( '' !== $text ) { return $text; } $candidates = WaicUtils::getArrayValue( $body, 'candidates', array(), 2 ); $parts = WaicUtils::getArrayValue( WaicUtils::getArrayValue( WaicUtils::getArrayValue( $candidates, 0, array(), 2 ), 'content', array(), 2 ), 'parts', array(), 2 ); return sanitize_textarea_field( (string) WaicUtils::getArrayValue( WaicUtils::getArrayValue( $parts, 0, array(), 2 ), 'text', '' ) ); }
}

class WaicCohereV2Adapter extends WaicAbstractProviderAdapter {
	protected function baseUrl() { return 'https://api.cohere.com/v2'; }
	protected function chatPath() { return '/chat'; }
}

class WaicQianfanAdapter extends WaicOpenAICompatibleAdapter {
	private $accessToken = '';
	protected function baseUrl() { return 'https://qianfan.baidubce.com/v2'; }
	protected function defaultCredentialFields() { return array('baidu_qianfan_api_key', 'baidu_qianfan_secret_key'); }
	protected function apiKey() { return (string) WaicUtils::getArrayValue( $this->options, 'baidu_qianfan_api_key', '' ); }
	public function buildTextRequest( $params ) { $token = $this->accessToken(); if ( '' === $token ) { return array('error' => new WaicProviderFailure( 'AUTH', 'Baidu access token is unavailable.' )); } $request = parent::buildTextRequest( $params ); $request['url'] .= ( false === strpos( $request['url'], '?' ) ? '?' : '&' ) . 'access_token=' . rawurlencode( $token ); unset( $request['headers']['Authorization'] ); return $request; }
	public function getText( $params, $stream = null ) { $request = $this->buildTextRequest( $params ); if ( isset( $request['error'] ) ) { return $this->legacyFailure( $request['error'] ); } return $this->sendTextRequest( $request, $params ); }
	private function accessToken() {
		if ( ! empty( $this->accessToken ) ) { return $this->accessToken; }
		$saved = WaicUtils::getArrayValue( $this->options, 'baidu_qianfan_access_token', '' ); if ( ! empty( $saved ) ) { return $this->accessToken = $saved; }
		$url = 'https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=' . rawurlencode( WaicUtils::getArrayValue( $this->options, 'baidu_qianfan_api_key', '' ) ) . '&client_secret=' . rawurlencode( WaicUtils::getArrayValue( $this->options, 'baidu_qianfan_secret_key', '' ) );
		$response = WaicProviderTransport::request( array('method' => 'POST', 'url' => $url, 'headers' => array('Content-Type' => 'application/json'), 'body' => array(), 'secrets' => $this->secrets()) ); if ( $response instanceof WaicProviderFailure ) { return ''; }
		return $this->accessToken = (string) WaicUtils::getArrayValue( $response['body'], 'access_token', '' );
	}
}

class WaicAzureOpenAiAdapter extends WaicOpenAICompatibleAdapter {
	protected function defaultCredentialFields() { return array('azure_openai_endpoint', 'azure_openai_api_key', 'azure_openai_deployment'); }
	protected function baseUrl() { return rtrim( (string) WaicUtils::getArrayValue( $this->options, 'azure_openai_endpoint', '' ), '/' ); }
	protected function authHeaders() { return array('api-key' => WaicUtils::getArrayValue( $this->options, 'azure_openai_api_key', '' ), 'Content-Type' => 'application/json'); }
	public function buildTextRequest( $params ) { $deployment = WaicUtils::getArrayValue( $this->options, 'azure_openai_deployment', WaicUtils::getArrayValue( $params, 'model', '' ) ); $version = WaicUtils::getArrayValue( $this->options, 'azure_openai_api_version', '2024-10-21' ); $body = $this->textBody( $params, $deployment ); unset( $body['model'] ); return array('method' => 'POST', 'url' => $this->baseUrl() . '/openai/deployments/' . rawurlencode( $deployment ) . '/chat/completions?api-version=' . rawurlencode( $version ), 'headers' => $this->authHeaders(), 'body' => $body); }
}

class WaicBedrockRuntimeAdapter extends WaicAbstractProviderAdapter {
	protected function defaultCredentialFields() { return array('bedrock_region', 'bedrock_access_key', 'bedrock_secret_key'); }
	public function buildTextRequest( $params ) {
		$model = WaicUtils::getArrayValue( $params, 'model', WaicUtils::getArrayValue( $this->options, 'bedrock_model', '' ) ); $region = WaicUtils::getArrayValue( $this->options, 'bedrock_region', 'us-east-1' ); $host = 'bedrock-runtime.' . $region . '.amazonaws.com'; $url = 'https://' . $host . '/model/' . rawurlencode( $model ) . '/converse'; $body = array('messages' => $this->textBody( $params, $model )['messages']);
		$headers = $this->sign( $host, $region, '/model/' . rawurlencode( $model ) . '/converse', $body ); return array('method' => 'POST', 'url' => $url, 'headers' => $headers, 'body' => $body);
	}
	protected function sign( $host, $region, $path, $body ) {
		$access = (string) WaicUtils::getArrayValue( $this->options, 'bedrock_access_key', '' ); $secret = (string) WaicUtils::getArrayValue( $this->options, 'bedrock_secret_key', '' ); $date = gmdate('Ymd'); $amzDate = gmdate('Ymd\\THis\\Z'); $payload = wp_json_encode( $body ); $hash = hash('sha256', $payload); $scope = $date . '/' . $region . '/bedrock/aws4_request'; $signed = 'content-type;host;x-amz-date'; $canonical = "POST\n{$path}\n\ncontent-type:application/json\nhost:{$host}\nx-amz-date:{$amzDate}\n\n{$signed}\n{$hash}"; $kDate = hash_hmac('sha256', $date, 'AWS4' . $secret, true); $kRegion = hash_hmac('sha256', $region, $kDate, true); $kService = hash_hmac('sha256', 'bedrock', $kRegion, true); $signature = hash_hmac('sha256', 'AWS4-HMAC-SHA256\n' . $amzDate . '\n' . $scope . '\n' . hash('sha256', $canonical), hash_hmac('sha256', 'aws4_request', $kService, true)); $headers = array('Content-Type' => 'application/json', 'Host' => $host, 'x-amz-date' => $amzDate, 'x-amz-content-sha256' => $hash, 'Authorization' => 'AWS4-HMAC-SHA256 Credential=' . $access . '/' . $scope . ', SignedHeaders=' . $signed . ', Signature=' . $signature); if ( ! empty( $this->options['bedrock_session_token'] ) ) { $headers['x-amz-security-token'] = $this->options['bedrock_session_token']; } return $headers;
	}
	protected function textFromBody( $body ) { $text = parent::textFromBody( $body ); if ( '' !== $text ) { return $text; } $output = WaicUtils::getArrayValue( WaicUtils::getArrayValue( $body, 'output', array(), 2 ), 'message', array(), 2 ); $content = WaicUtils::getArrayValue( $output, 'content', array(), 2 ); return sanitize_textarea_field( (string) WaicUtils::getArrayValue( WaicUtils::getArrayValue( $content, 0, array(), 2 ), 'text', '' ) ); }
}

class WaicFireflyAdapter extends WaicAbstractProviderAdapter {
	protected function defaultCredentialFields() { return array('adobe_firefly_client_id', 'adobe_firefly_access_token'); }
	protected function baseUrl() { return 'https://firefly-api.adobe.io/v3'; }
	protected function authHeaders() { return array('x-api-key' => WaicUtils::getArrayValue( $this->options, 'adobe_firefly_client_id', '' ), 'Authorization' => 'Bearer ' . WaicUtils::getArrayValue( $this->options, 'adobe_firefly_access_token', WaicUtils::getArrayValue( $this->options, 'adobe_firefly_client_secret', '' ) ), 'Content-Type' => 'application/json'); }
	protected function buildImageRequest( $params ) { return array('method' => 'POST', 'url' => $this->baseUrl() . '/images/generate', 'headers' => $this->authHeaders(), 'body' => array('prompt' => WaicUtils::getArrayValue( $params, 'prompt', '' ), 'model' => WaicUtils::getArrayValue( $params, 'model', $this->getEngineModel('image') ))); }
}

class WaicFluxAdapter extends WaicAbstractProviderAdapter {
	protected function defaultCredentialFields() { return array('bfl_api_key'); }
	protected function baseUrl() { return 'https://api.bfl.ai/v1'; }
	protected function authHeaders() { return array('x-key' => WaicUtils::getArrayValue( $this->options, 'bfl_api_key', '' ), 'Content-Type' => 'application/json'); }
	protected function buildImageRequest( $params ) { $model = WaicUtils::getArrayValue( $params, 'model', WaicUtils::getArrayValue( $this->options, 'bfl_model', 'flux-pro-1.1' ) ); return array('method' => 'POST', 'url' => $this->baseUrl() . '/' . rawurlencode( $model ), 'headers' => $this->authHeaders(), 'body' => array('prompt' => WaicUtils::getArrayValue( $params, 'prompt', '' ))); }
	public function getImage( $params ) { $request = $this->buildImageRequest( $params ); $submitted = $this->dispatch( $request ); if ( $submitted instanceof WaicProviderFailure ) { return $this->legacyFailure( $submitted ); } $body = $submitted['body']; $url = $this->imageUrl( $body ); if ( '' === $url && ! empty( $body['id'] ) ) { $job = new WaicProviderImageJob(); $job->id = sanitize_text_field( $body['id'] ); $job->provider_id = $this->id; $job->model = WaicUtils::getArrayValue( $params, 'model', '' ); $job->meta = array('poll_attempts' => max( 1, min( 10, (int) WaicUtils::getArrayValue( $params, 'poll_attempts', 3, 1 ) ) )); $polled = $this->pollImage( $job ); if ( $polled instanceof WaicProviderFailure ) { return $this->legacyFailure( $polled ); } $url = $polled->url; } if ( '' === $url ) { return $this->legacyFailure( new WaicProviderFailure( 'UPSTREAM', 'Image job did not return a media URL.' ) ); } return array('results' => array('error' => 0, 'data' => $url, 'raw_response' => $body), 'params' => $params); }
	public function pollImage( $job ) { $id = is_object( $job ) ? $job->id : WaicUtils::getArrayValue( $job, 'id', '' ); $attempts = is_object( $job ) ? (int) WaicUtils::getArrayValue( $job->meta, 'poll_attempts', 3, 1 ) : 3; for ( $i = 0; $i < $attempts; $i++ ) { $response = $this->dispatch( array('method' => 'GET', 'url' => $this->baseUrl() . '/get_result?id=' . rawurlencode( $id ), 'headers' => $this->authHeaders(), 'body' => array()) ); if ( $response instanceof WaicProviderFailure ) { return $response; } $body = $response['body']; $url = esc_url_raw( (string) WaicUtils::getArrayValue( WaicUtils::getArrayValue( $body, 'result', array(), 2 ), 'sample', $this->imageUrl( $body ) ) ); if ( '' !== $url ) { $result = new WaicProviderImageResult(); $result->url = $url; $result->usage = WaicProviderUsageNormalizer::normalize( $body ); return $result; } } return new WaicProviderFailure( 'TIMEOUT', 'Image job is not ready yet.', '', true ); }
}

class WaicManualExternalAdapter extends WaicAbstractProviderAdapter {
	public function validateProfile( $profile ) { return true; }
	protected function defaultCredentialFields() { return array(); }
	public function getImage( $params ) { return array('results' => array('error' => 0, 'data' => '', 'manual_external' => 1, 'instruction' => 'Copy the prompt to Midjourney and upload the completed image manually.'), 'params' => $params); }
	public function getText( $params, $stream = null ) { return $this->unsupported(); }
}
