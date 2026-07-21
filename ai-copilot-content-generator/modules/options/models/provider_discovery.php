<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicProviderDiscovery {
	const MAX_RESPONSE_BYTES = 524288;

	public function discover( $provider, $apiKey ) {
		$provider = sanitize_key((string) $provider);
		$apiKey = trim((string) $apiKey);
		if ('' === $apiKey) {
			return false;
		}
		switch ($provider) {
			case 'open-ai':
				return $this->discoverOpenAiCompatible($provider, 'https://api.openai.com/v1/models', $apiKey);
			case 'deep-seek':
				return $this->discoverOpenAiCompatible($provider, 'https://api.deepseek.com/models', $apiKey);
			case 'openrouter':
				return $this->discoverOpenRouter($apiKey);
			case 'gemini':
				return $this->discoverGemini($apiKey);
		}
		return false;
	}

	private function discoverOpenAiCompatible( $provider, $url, $apiKey ) {
		$response = wp_remote_get($url, array(
			'timeout' => 5,
			'redirection' => 1,
			'headers' => array(
				'Authorization' => 'Bearer ' . $apiKey,
				'Accept' => 'application/json',
			),
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
		));
		if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) < 200 || (int) wp_remote_retrieve_response_code($response) >= 300) {
			return false;
		}
		$data = json_decode((string) wp_remote_retrieve_body($response), true);
		if (empty($data['data']) || !is_array($data['data'])) {
			return false;
		}
		$models = array();
		foreach ($data['data'] as $row) {
			$id = $this->modelId(WaicUtils::getArrayValue($row, 'id', ''));
			if (!$this->isSafeLiveModelId($provider, $id)) {
				continue;
			}
			$models[] = $this->liveModel($provider, $id, $this->labelFromId($id), WaicUtils::getArrayValue($row, 'context_length', 0, 1));
		}
		return $this->manifest($provider, $models);
	}

	private function discoverGemini( $apiKey ) {
		$url = add_query_arg('key', rawurlencode($apiKey), 'https://generativelanguage.googleapis.com/v1beta/models');
		$response = wp_remote_get($url, array(
			'timeout' => 5,
			'redirection' => 1,
			'headers' => array('Accept' => 'application/json'),
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
		));
		if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) < 200 || (int) wp_remote_retrieve_response_code($response) >= 300) {
			return false;
		}
		$data = json_decode((string) wp_remote_retrieve_body($response), true);
		if (empty($data['models']) || !is_array($data['models'])) {
			return false;
		}
		$models = array();
		foreach ($data['models'] as $row) {
			$name = (string) WaicUtils::getArrayValue($row, 'name', '');
			$id = $this->modelId(preg_replace('#^models/#', '', $name));
			if ('' === $id || false !== strpos($id, 'embedding')) {
				continue;
			}
			$methods = WaicUtils::getArrayValue($row, 'supportedGenerationMethods', array(), 2);
			if (!in_array('generateContent', $methods, true)) {
				continue;
			}
			$label = sanitize_text_field((string) WaicUtils::getArrayValue($row, 'displayName', $this->labelFromId($id)));
			$tokens = (int) WaicUtils::getArrayValue($row, 'outputTokenLimit', 0, 1);
			$models[] = $this->liveModel('gemini', $id, $label, $tokens);
		}
		return $this->manifest('gemini', $models);
	}

	private function discoverOpenRouter( $apiKey ) {
		$response = wp_remote_get('https://openrouter.ai/api/v1/models', array(
			'timeout' => 5,
			'redirection' => 1,
			'headers' => array(
				'Authorization' => 'Bearer ' . $apiKey,
				'Accept' => 'application/json',
			),
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
		));
		if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) < 200 || (int) wp_remote_retrieve_response_code($response) >= 300) {
			return false;
		}
		$data = json_decode((string) wp_remote_retrieve_body($response), true);
		if (empty($data['data']) || !is_array($data['data'])) {
			return false;
		}
		$models = array();
		foreach ($data['data'] as $row) {
			$id = $this->modelId(WaicUtils::getArrayValue($row, 'id', ''));
			if ('' === $id || false !== strpos($id, ':free') || false !== strpos($id, 'moderation')) {
				continue;
			}
			$architecture = WaicUtils::getArrayValue($row, 'architecture', array(), 2);
			$output = WaicUtils::getArrayValue($architecture, 'output_modalities', array(), 2);
			if (!in_array('text', $output, true) && !in_array('image', $output, true)) {
				continue;
			}
			$tokens = 0;
			$topProvider = WaicUtils::getArrayValue($row, 'top_provider', array(), 2);
			if (!empty($topProvider['max_completion_tokens'])) {
				$tokens = (int) $topProvider['max_completion_tokens'];
			} elseif (!empty($topProvider['context_length'])) {
				$tokens = (int) $topProvider['context_length'];
			} elseif (!empty($row['context_length'])) {
				$tokens = (int) $row['context_length'];
			}
			$model = $this->liveModel('openrouter', $id, sanitize_text_field((string) WaicUtils::getArrayValue($row, 'name', $id)), $tokens);
			if (in_array('image', $output, true) && !in_array('text', $output, true)) {
				$model['capabilities'] = array('image');
				$model['api_mode'] = 'image_generations';
			}
			$models[] = $model;
		}
		return $this->manifest('openrouter', $models);
	}

	private function liveModel( $provider, $id, $label, $tokens ) {
		$tokens = max(0, (int) $tokens);
		return array(
			'id' => $id,
			'label' => $label,
			'provider' => $provider,
			'capabilities' => WaicProviderCapabilities::inferCapabilities($provider, $id),
			'api_mode' => WaicProviderCapabilities::inferApiMode($provider, $id),
			'status' => 'unverified',
			'visibility' => 'public',
			'context_tokens' => $tokens,
			'max_output_tokens' => $tokens,
			'pricing_key' => $provider . '/' . $id,
			'deprecated_after' => null,
			'replacement' => null,
			'badges' => array('live'),
			'source' => WaicModelRegistry::SOURCE_LIVE,
		);
	}

	private function manifest( $provider, $models ) {
		if (empty($models)) {
			return false;
		}
		return array(
			'schema_version' => 1,
			'version' => time(),
			'generated_at' => gmdate('c'),
			'source' => WaicModelRegistry::SOURCE_LIVE,
			'providers' => array(
				$provider => array(
					'id' => $provider,
					'label' => $this->providerLabel($provider),
					'group' => $this->providerGroup($provider),
					'kind' => 'llm',
					'status' => 'active',
					'visibility' => 'public',
					'adapter' => true,
					'live_discovery' => true,
					'capabilities' => array('chat'),
					'api_modes' => $this->providerApiModes($provider),
					'source' => WaicModelRegistry::SOURCE_LIVE,
				),
			),
			'models' => $models,
		);
	}

	private function isSafeLiveModelId( $provider, $id ) {
		if ('' === $id || false !== strpos($id, 'ft:') || false !== strpos($id, ':ft-') || false !== strpos($id, 'fine-tune')) {
			return false;
		}
		if ('open-ai' === $provider) {
			return (bool) preg_match('/^(gpt-|o[0-9]|dall-e|text-embedding)/', $id);
		}
		if ('deep-seek' === $provider) {
			return 0 === strpos($id, 'deepseek-');
		}
		return true;
	}

	private function providerLabel( $provider ) {
		$labels = array(
			'open-ai' => 'Open AI',
			'gemini' => 'Gemini',
			'deep-seek' => 'Deep Seek',
			'openrouter' => 'OpenRouter',
		);
		return isset($labels[$provider]) ? $labels[$provider] : $provider;
	}

	private function providerGroup( $provider ) {
		return 'deep-seek' === $provider ? 'china' : ('openrouter' === $provider ? 'aggregator' : 'core');
	}

	private function providerApiModes( $provider ) {
		if ('gemini' === $provider) {
			return array('gemini_generate_content', 'image_generations', 'embeddings');
		}
		return array('chat_completions', 'image_generations', 'embeddings');
	}

	private function modelId( $id ) {
		return substr(trim(sanitize_text_field((string) $id)), 0, 180);
	}

	private function labelFromId( $id ) {
		$label = str_replace(array('-', '_', '/'), ' ', (string) $id);
		return ucwords($label);
	}
}
