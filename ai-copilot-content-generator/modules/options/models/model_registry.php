<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once WAIC_MODULES_DIR . 'workspace' . WAIC_DS . 'providers' . WAIC_DS . 'factory.php';

class WaicModelRegistry {
	const SOURCE_BUNDLED = 'bundled';
	const SOURCE_REMOTE = 'aiwu_remote';
	const SOURCE_LIVE = 'provider_live';
	const SOURCE_CUSTOM = 'user_custom';
	const CACHE_OPTION = 'waic_model_registry_cache';
	const LAST_SYNC_OPTION = 'waic_model_registry_last_sync';
	const SOURCE_VERSION_OPTION = 'waic_model_registry_source_version';
	const ERRORS_OPTION = 'waic_model_registry_errors';
	const CUSTOM_OPTION = 'waic_model_registry_custom';
	const SETTINGS_OPTION = 'waic_model_registry_settings';
	const CRON_HOOK = 'waic_model_registry_sync';
	const MAX_MANIFEST_BYTES = 1048576;

	private $optionsModel = null;
	private $registry = null;
	private $errors = array();

	public function __construct( $optionsModel = null ) {
		$this->optionsModel = $optionsModel;
	}

	public function registerHooks() {
		add_action(self::CRON_HOOK, array($this, 'cronSync'));
		$this->ensureScheduled();
	}

	public function ensureScheduled() {
		if (function_exists('wp_next_scheduled') && !wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
		}
	}

	public function cronSync() {
		$settings = $this->getSettings();
		if (empty($settings['auto_sync_enabled']) && empty($settings['provider_live_enabled'])) {
			return array('ok' => false, 'message' => esc_html__('Model registry auto-sync is disabled.', 'ai-copilot-content-generator'));
		}
		return $this->refresh(array(), false);
	}

	public function getSettings() {
		$defaults = array(
			'remote_url' => '',
			'auto_sync_enabled' => 0,
			'provider_live_enabled' => 0,
			'allow_preview' => 0,
			'allow_deprecated' => 0,
			'allow_limited' => 0,
			'allow_unverified_live' => 0,
		);
		$settings = get_option(self::SETTINGS_OPTION, array());
		$settings = is_array($settings) ? array_merge($defaults, $settings) : $defaults;
		$settings['remote_url'] = $this->sanitizeRemoteUrl($settings['remote_url']);
		foreach (array('auto_sync_enabled', 'provider_live_enabled', 'allow_preview', 'allow_deprecated', 'allow_limited', 'allow_unverified_live') as $key) {
			$settings[$key] = empty($settings[$key]) ? 0 : 1;
		}
		return $settings;
	}

	public function saveSettings( $params ) {
		$settings = $this->getSettings();
		if (isset($params['model_registry_remote_url'])) {
			$settings['remote_url'] = $this->sanitizeRemoteUrl($params['model_registry_remote_url']);
		}
		$map = array(
			'model_registry_auto_sync' => 'auto_sync_enabled',
			'model_registry_live_discovery' => 'provider_live_enabled',
			'model_registry_allow_preview' => 'allow_preview',
			'model_registry_allow_deprecated' => 'allow_deprecated',
			'model_registry_allow_limited' => 'allow_limited',
			'model_registry_allow_unverified_live' => 'allow_unverified_live',
		);
		foreach ($map as $input => $key) {
			if (array_key_exists($input, $params)) {
				$settings[$key] = empty($params[$input]) ? 0 : 1;
			}
		}
		update_option(self::SETTINGS_OPTION, $settings, false);
		$this->ensureScheduled();
		return $settings;
	}

	public function getStatus() {
		$registry = $this->getRegistry();
		$settings = $this->getSettings();
		$cache = $this->getCache();
		$groups = array('core' => 0, 'china' => 0, 'image' => 0, 'aggregator' => 0, 'custom' => 0);
		foreach (WaicUtils::getArrayValue($registry, 'providers', array(), 2) as $provider) {
			$group = sanitize_key((string) WaicUtils::getArrayValue($provider, 'group', 'custom'));
			if (!isset($groups[$group])) {
				$group = 'custom';
			}
			$groups[$group]++;
		}
		return array(
			'settings' => $settings,
			'last_sync' => sanitize_text_field((string) get_option(self::LAST_SYNC_OPTION, '')),
			'source_version' => sanitize_text_field((string) get_option(self::SOURCE_VERSION_OPTION, '')),
			'errors' => $this->getStoredErrors(),
			'providers_count' => count(WaicUtils::getArrayValue($registry, 'providers', array(), 2)),
			'models_count' => count(WaicUtils::getArrayValue($registry, 'models', array(), 2)),
			'groups' => $groups,
			'sources' => array_keys(WaicUtils::getArrayValue($cache, 'sources', array(), 2)),
			'has_rollback' => !empty($cache['previous_sources']) ? 1 : 0,
			'group_labels' => $this->getGroupLabels(),
			'capability_labels' => $this->getCapabilityLabels(),
		);
	}

	public function refresh( $params = array(), $manual = true ) {
		$this->clearErrors();
		if (!empty($params) && is_array($params)) {
			$this->saveSettings($params);
		}
		$settings = $this->getSettings();
		$ok = false;
		$messages = array();
		if (!empty($settings['remote_url'])) {
			$remote = $this->syncRemote($settings['remote_url'], $manual);
			$ok = $ok || !empty($remote['ok']);
			if (!empty($remote['message'])) {
				$messages[] = $remote['message'];
			}
		} elseif ($manual) {
			$messages[] = esc_html__('Remote model registry URL is empty. Bundled registry remains active.', 'ai-copilot-content-generator');
		}
		if (!empty($settings['provider_live_enabled'])) {
			$live = $this->syncProviderLive();
			$ok = $ok || !empty($live['ok']);
			if (!empty($live['message'])) {
				$messages[] = $live['message'];
			}
		}
		if (empty($messages)) {
			$messages[] = esc_html__('Model registry refreshed from available sources.', 'ai-copilot-content-generator');
		}
		$this->setStoredErrors($this->errors);
		return array('ok' => $ok, 'message' => implode(' ', $messages), 'status' => $this->getStatus());
	}

	public function importManifestJson( $json ) {
		$this->clearErrors();
		$json = trim((string) $json);
		if ('' === $json) {
			$this->addError(esc_html__('Paste a model registry JSON manifest first.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		if (strlen($json) > self::MAX_MANIFEST_BYTES) {
			$this->addError(esc_html__('Model registry manifest is too large.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$raw = json_decode($json, true);
		if (!is_array($raw)) {
			$this->addError(esc_html__('Model registry JSON is invalid.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$manifest = $this->normalizeManifest($raw, self::SOURCE_REMOTE, true);
		if (false === $manifest) {
			$this->addError(esc_html__('Model registry manifest did not pass validation.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$this->applySourceManifest(self::SOURCE_REMOTE, $manifest, esc_html__('Imported AIWU model registry manifest.', 'ai-copilot-content-generator'));
		return array('ok' => true, 'status' => $this->getStatus());
	}

	public function rollback() {
		$cache = $this->getCache();
		if (empty($cache['previous_sources']) || !is_array($cache['previous_sources'])) {
			$this->addError(esc_html__('No previous valid model registry snapshot is available.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$cache['sources'] = $cache['previous_sources'];
		$cache['previous_sources'] = array();
		$active = $this->mergeManifests($this->getSourceStack($cache['sources']));
		if (false === $active) {
			$active = $this->loadBundledManifest();
		}
		$cache['last_valid_registry'] = $active;
		$cache['last_valid_at'] = gmdate('c');
		update_option(self::CACHE_OPTION, $cache, false);
		update_option(self::LAST_SYNC_OPTION, gmdate('Y-m-d H:i:s'), false);
		$this->registry = null;
		return array('ok' => true, 'status' => $this->getStatus());
	}

	public function addCustomModel( $provider, $modelId, $label = '' ) {
		$provider = sanitize_key((string) $provider);
		$modelId = $this->sanitizeModelId($modelId);
		if ('' === $provider || '' === $modelId) {
			return false;
		}
		$registry = $this->getRegistry();
		$providers = WaicUtils::getArrayValue($registry, 'providers', array(), 2);
		$providerData = isset($providers[$provider]) ? $providers[$provider] : array(
			'id' => $provider,
			'label' => $provider,
			'group' => 'custom',
			'kind' => 'llm',
			'status' => 'active',
			'visibility' => 'public',
			'adapter' => false,
			'capabilities' => array('chat'),
			'api_modes' => array('chat_completions'),
			'source' => self::SOURCE_CUSTOM,
		);
		$custom = get_option(self::CUSTOM_OPTION, array());
		$custom = is_array($custom) ? $custom : array();
		$custom['schema_version'] = 1;
		$custom['version'] = max(1, (int) WaicUtils::getArrayValue($custom, 'version', 0, 1) + 1);
		$custom['generated_at'] = gmdate('c');
		$custom['source'] = self::SOURCE_CUSTOM;
		if (empty($custom['providers']) || !is_array($custom['providers'])) {
			$custom['providers'] = array();
		}
		$custom['providers'][$provider] = $providerData;
		if (empty($custom['models']) || !is_array($custom['models'])) {
			$custom['models'] = array();
		}
		$apiModes = WaicUtils::getArrayValue($providerData, 'api_modes', array('chat_completions'), 2);
		$apiMode = empty($apiModes) ? 'chat_completions' : reset($apiModes);
		$custom['models'][] = array(
			'id' => $modelId,
			'label' => '' === $label ? $modelId : sanitize_text_field((string) $label),
			'provider' => $provider,
			'capabilities' => array('chat'),
			'api_mode' => $apiMode,
			'status' => 'active',
			'visibility' => 'public',
			'context_tokens' => 0,
			'max_output_tokens' => 0,
			'pricing_key' => $provider . '/' . $modelId,
			'deprecated_after' => null,
			'replacement' => null,
			'badges' => array('custom'),
			'source' => self::SOURCE_CUSTOM,
		);
		$normalized = $this->normalizeManifest($custom, self::SOURCE_CUSTOM, false);
		if (false === $normalized) {
			return false;
		}
		update_option(self::CUSTOM_OPTION, $normalized, false);
		$this->registry = null;
		return true;
	}

	public function addCustomProvider( $provider, $label, $baseUrl, $modelId, $apiKey = '' ) {
		$provider = sanitize_key((string) $provider);
		$modelId = $this->sanitizeModelId($modelId);
		$baseUrl = esc_url_raw(trim((string) $baseUrl), array('https'));
		if ('' === $provider || '' === $modelId || !WaicProviderTransport::validateUrl($baseUrl, false)) {
			return false;
		}
		$custom = get_option(self::CUSTOM_OPTION, array());
		$custom = is_array($custom) ? $custom : array();
		$custom['schema_version'] = 2;
		$custom['version'] = max(1, (int) WaicUtils::getArrayValue($custom, 'version', 0, 1) + 1);
		$custom['generated_at'] = gmdate('c');
		$custom['source'] = self::SOURCE_CUSTOM;
		$customProviders = WaicUtils::getArrayValue($custom, 'providers', array(), 2);
		$customModels = WaicUtils::getArrayValue($custom, 'models', array(), 2);
		$custom['providers'] = is_array($customProviders) ? $customProviders : array();
		$custom['models'] = is_array($customModels) ? $customModels : array();
		$custom['providers'][$provider] = array(
			'id' => $provider, 'label' => sanitize_text_field((string) $label), 'group' => 'custom', 'kind' => 'llm',
			'support_type' => 'custom_oai', 'status' => 'beta', 'visibility' => 'configured', 'adapter' => true,
			'adapter_type' => 'openai_compatible', 'api_family' => array('chat_completions'), 'api_modes' => array('chat_completions'),
			'auth_schema' => 'custom_oai', 'configuration_schema' => array('base_url', 'model'), 'endpoint_policy' => 'custom_oai',
			'discovery_strategy' => 'manual', 'live_discovery' => false, 'capabilities' => array('chat'),
			'credential_fields' => array($provider . '_api_key', $provider . '_base_url'), 'api_key_field' => $provider . '_api_key',
			'model_field' => $provider . '_model', 'docs_url' => '', 'api_reference_url' => '', 'terms_url' => '', 'privacy_url' => '', 'verified_at' => gmdate('c'),
		);
		$custom['models'][] = array('id' => $modelId, 'label' => '' === trim((string) $label) ? $modelId : sanitize_text_field((string) $label), 'provider' => $provider, 'capabilities' => array('chat'), 'api_mode' => 'chat_completions', 'status' => 'active', 'visibility' => 'configured', 'context_tokens' => 0, 'max_output_tokens' => 0, 'pricing_key' => $provider . '/' . $modelId, 'badges' => array('custom'));
		$normalized = $this->normalizeManifest($custom, self::SOURCE_CUSTOM, false);
		if (false === $normalized) { return false; }
		update_option(self::CUSTOM_OPTION, $normalized, false);
		if ('' !== trim((string) $apiKey)) {
			$store = new WaicProviderCredentialStore();
			$store->saveProfile(array('id' => 'custom-' . $provider, 'provider_id' => $provider, 'label' => sanitize_text_field((string) $label), 'enabled' => false, 'configuration' => array('base_url' => $baseUrl, 'model' => $modelId)), array($provider . '_api_key' => $apiKey));
		}
		$this->registry = null;
		return true;
	}

	public function applyProviderLiveResults( $provider, $results ) {
		$provider = sanitize_key((string) $provider);
		if (empty($results) || !is_array($results) || '' === $provider) {
			return false;
		}
		$registry = $this->getRegistry();
		$providers = WaicUtils::getArrayValue($registry, 'providers', array(), 2);
		if (empty($providers[$provider])) {
			return false;
		}
		$cache = $this->getCache();
		$live = WaicUtils::getArrayValue(WaicUtils::getArrayValue($cache, 'sources', array(), 2), self::SOURCE_LIVE, array(), 2);
		if (empty($live) || !is_array($live)) {
			$live = array(
				'schema_version' => 1,
				'version' => time(),
				'generated_at' => gmdate('c'),
				'source' => self::SOURCE_LIVE,
				'providers' => array(),
				'models' => array(),
			);
		}
		$live['providers'][$provider] = $providers[$provider];
		$live['providers'][$provider]['source'] = self::SOURCE_LIVE;
		$cleanModels = array();
		foreach (WaicUtils::getArrayValue($live, 'models', array(), 2) as $model) {
			if (WaicUtils::getArrayValue($model, 'provider') !== $provider) {
				$cleanModels[] = $model;
			}
		}
		$tokens = WaicUtils::getArrayValue($results, 'tokens', array(), 2);
		foreach (array('models' => 'chat_completions', 'img_models' => 'image_generations') as $key => $apiMode) {
			foreach (WaicUtils::getArrayValue($results, $key, array(), 2) as $id => $label) {
				$id = $this->sanitizeModelId($id);
				if ('' === $id) {
					continue;
				}
				$isImage = 'image_generations' === $apiMode;
				$cleanModels[] = array(
					'id' => $id,
					'label' => sanitize_text_field((string) $label),
					'provider' => $provider,
					'capabilities' => $isImage ? array('image') : WaicProviderCapabilities::inferCapabilities($provider, $id),
					'api_mode' => $apiMode,
					'status' => 'unverified',
					'visibility' => 'public',
					'context_tokens' => (int) WaicUtils::getArrayValue($tokens, $id, 0, 1),
					'max_output_tokens' => (int) WaicUtils::getArrayValue($tokens, $id, 0, 1),
					'pricing_key' => $provider . '/' . $id,
					'deprecated_after' => null,
					'replacement' => null,
					'badges' => array('live'),
					'source' => self::SOURCE_LIVE,
				);
			}
		}
		$live['models'] = $cleanModels;
		$live['version'] = time();
		$live['generated_at'] = gmdate('c');
		$manifest = $this->normalizeManifest($live, self::SOURCE_LIVE, false);
		if (false === $manifest) {
			return false;
		}
		return $this->applySourceManifest(self::SOURCE_LIVE, $manifest, esc_html__('Provider live model list refreshed.', 'ai-copilot-content-generator'));
	}

	public function applyToVariations( $vars, $currentOptions = array() ) {
		if (empty($vars['api']) || !is_array($vars['api'])) {
			return $vars;
		}
		$registry = $this->getRegistry();
		$providers = WaicUtils::getArrayValue($registry, 'providers', array(), 2);
		$models = WaicUtils::getArrayValue($registry, 'models', array(), 2);
		if (empty($providers)) {
			return $vars;
		}
		$settings = $this->getSettings();
		$api = $vars['api'];
		$currentModels = $this->getCurrentModels($api, $currentOptions);
		$legacyModels = WaicUtils::getArrayValue($api, 'model', array(), 2);
		$legacyTokens = WaicUtils::getArrayValue($api, 'tokens', array(), 2);
		$imageFields = $this->getImageModelFields($providers);
		$legacyImageModels = array();
		foreach ($imageFields as $field) {
			$legacyImageModels[$field] = WaicUtils::getArrayValue($api, $field, array(), 2);
			$api[$field] = array();
			$api['image-model-options'][$field] = array();
		}
		$api['engines'] = array();
		$api['engine-options'] = array();
		$api['image-engines'] = array();
		$api['image-engine-options'] = array();
		$api['model'] = array();
		$api['model-options'] = array();
		$api['tokens'] = array();
		$api['provider-meta'] = array();
		$api['model-meta'] = array();
		$api['provider-groups'] = $this->getGroupLabels();
		$api['capability-filters'] = $this->getCapabilityLabels();

		foreach ($providers as $providerId => $provider) {
			$providerId = sanitize_key($providerId);
			$api['provider-meta'][$providerId] = $provider;
			if (!$this->isRuntimeProvider($provider, $currentOptions)) {
				continue;
			}
			$providerCapabilities = WaicUtils::getArrayValue($provider, 'capabilities', array(), 2);
			$isImageOnly = 'image' === WaicUtils::getArrayValue($provider, 'kind', '') || (!in_array('chat', $providerCapabilities, true) && in_array('image_generate', $providerCapabilities, true));
			if (in_array('image_generate', $providerCapabilities, true)) {
				$api['image-engines'][$providerId] = WaicUtils::getArrayValue($provider, 'label', $providerId);
				$api['image-engine-options'][$providerId] = array('label' => WaicUtils::getArrayValue($provider, 'label', $providerId), 'attrs' => $this->providerAttrs($provider));
			}
			if ($isImageOnly) {
				$api['key-fields'][$providerId] = $this->getProviderField($provider, 'api_key_field', $providerId);
				continue;
			}
			$api['engines'][$providerId] = WaicUtils::getArrayValue($provider, 'label', $providerId);
			$api['engine-options'][$providerId] = array(
				'label' => WaicUtils::getArrayValue($provider, 'label', $providerId),
				'attrs' => $this->providerAttrs($provider),
			);
			$api['model-fields'][$providerId] = $this->getProviderField($provider, 'model_field', $providerId);
			$api['key-fields'][$providerId] = $this->getProviderField($provider, 'api_key_field', $providerId);
			$api['model'][$providerId] = array();
			$api['model-options'][$providerId] = array();
		}

		foreach ($models as $modelKey => $model) {
			$providerId = sanitize_key((string) WaicUtils::getArrayValue($model, 'provider', ''));
			if (empty($providerId) || empty($providers[$providerId])) {
				continue;
			}
			$provider = $providers[$providerId];
			if (!$this->isRuntimeProvider($provider, $currentOptions)) {
				continue;
			}
			if (!WaicProviderCapabilities::isModelVisible($model, $provider, $settings, $currentModels)) {
				continue;
			}
			$id = (string) WaicUtils::getArrayValue($model, 'id', '');
			$label = $this->formatModelLabel($model);
			$apiMode = sanitize_key((string) WaicUtils::getArrayValue($model, 'api_mode', 'chat_completions'));
			$capabilities = WaicUtils::getArrayValue($model, 'capabilities', array(), 2);
			if ('image_generations' === $apiMode || in_array('image_generate', $capabilities, true)) {
				$field = $this->getProviderField($provider, 'image_model_field', $providerId);
				if ('' !== $field) {
					$api[$field][$id] = $label;
					$api['image-model-options'][$field][$id] = array('label' => $label, 'attrs' => $this->modelAttrs($model));
				}
				continue;
			}
			if (!in_array('chat', $capabilities, true)) {
				continue;
			}
			$api['model'][$providerId][$id] = $label;
			$api['model-options'][$providerId][$id] = array('label' => $label, 'attrs' => $this->modelAttrs($model));
			$api['model-meta'][$providerId . '/' . $id] = $model;
			$maxTokens = (int) WaicUtils::getArrayValue($model, 'max_output_tokens', WaicUtils::getArrayValue($model, 'context_tokens', 0, 1), 1);
			if ($maxTokens > 0) {
				$api['tokens'][$id] = $maxTokens;
			}
		}

		foreach ($legacyModels as $providerId => $providerModels) {
			if (!isset($api['model'][$providerId])) {
				$api['model'][$providerId] = array();
				$api['model-options'][$providerId] = array();
			}
			if (!is_array($providerModels)) {
				continue;
			}
			foreach ($providerModels as $id => $label) {
				if (!isset($api['model'][$providerId][$id]) && (empty($api['model'][$providerId]) || (isset($currentModels[$providerId]) && $currentModels[$providerId] === (string) $id))) {
					$api['model'][$providerId][$id] = $label;
					$api['model-options'][$providerId][$id] = array('label' => $label, 'attrs' => ' data-source="legacy"');
				}
			}
		}
		foreach ($legacyImageModels as $field => $legacyModelsByField) {
			foreach ((array) $legacyModelsByField as $id => $label) {
				if (!isset($api[$field][$id]) && empty($api[$field])) {
					$api[$field][$id] = $label;
					$api['image-model-options'][$field][$id] = array('label' => $label, 'attrs' => ' data-source="legacy"');
				}
			}
		}
		$api['tokens'] = array_merge($legacyTokens, $api['tokens']);
		$api['model-registry'] = $this->getStatus();
		$vars['api'] = $api;
		return $vars;
	}

	public function getRegistry() {
		if (!is_null($this->registry)) {
			return $this->registry;
		}
		$cache = $this->getCache();
		$merged = $this->mergeManifests($this->getSourceStack(WaicUtils::getArrayValue($cache, 'sources', array(), 2)));
		if (false === $merged) {
			$merged = WaicUtils::getArrayValue($cache, 'last_valid_registry', array(), 2);
			$merged = $this->normalizeManifest($merged, self::SOURCE_BUNDLED, false);
		}
		if (false === $merged) {
			$merged = $this->loadBundledManifest();
		}
		$this->registry = is_array($merged) ? $merged : array('schema_version' => 2, 'version' => 1, 'providers' => array(), 'models' => array());
		return $this->registry;
	}

	private function syncRemote( $url, $manual ) {
		$url = $this->sanitizeRemoteUrl($url);
		if ('' === $url) {
			return array('ok' => false, 'message' => esc_html__('Model registry remote URL is invalid.', 'ai-copilot-content-generator'));
		}
		update_option(self::LAST_SYNC_OPTION, gmdate('Y-m-d H:i:s'), false);
		$response = wp_remote_get($url, array(
			'timeout' => 5,
			'redirection' => 1,
			'headers' => array('Accept' => 'application/json'),
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_MANIFEST_BYTES,
		));
		if (is_wp_error($response)) {
			$this->addError(esc_html__('Remote model registry sync failed. Last valid registry remains active.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'message' => esc_html__('Remote model registry sync failed.', 'ai-copilot-content-generator'));
		}
		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 200 || $code >= 300) {
			$this->addError(esc_html__('Remote model registry returned an unavailable manifest.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'message' => esc_html__('Remote model registry returned an unavailable manifest.', 'ai-copilot-content-generator'));
		}
		$body = (string) wp_remote_retrieve_body($response);
		if (strlen($body) > self::MAX_MANIFEST_BYTES) {
			$this->addError(esc_html__('Remote model registry manifest is too large.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'message' => esc_html__('Remote model registry manifest is too large.', 'ai-copilot-content-generator'));
		}
		$raw = json_decode($body, true);
		if (!is_array($raw)) {
			$this->addError(esc_html__('Remote model registry returned invalid JSON.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'message' => esc_html__('Remote model registry returned invalid JSON.', 'ai-copilot-content-generator'));
		}
		$manifest = $this->normalizeManifest($raw, self::SOURCE_REMOTE, true);
		if (false === $manifest) {
			$this->addError(esc_html__('Remote model registry did not pass validation.', 'ai-copilot-content-generator'));
			return array('ok' => false, 'message' => esc_html__('Remote model registry did not pass validation.', 'ai-copilot-content-generator'));
		}
		$this->applySourceManifest(self::SOURCE_REMOTE, $manifest, esc_html__('Remote AIWU model registry synced.', 'ai-copilot-content-generator'));
		return array('ok' => true, 'message' => esc_html__('Remote AIWU model registry synced.', 'ai-copilot-content-generator'));
	}

	private function syncProviderLive() {
		if (!class_exists('WaicProviderDiscovery')) {
			return array('ok' => false, 'message' => esc_html__('Provider live discovery is unavailable.', 'ai-copilot-content-generator'));
		}
		$options = $this->getApiOptions();
		$registry = $this->getRegistry();
		$providers = WaicUtils::getArrayValue($registry, 'providers', array(), 2);
		$discovery = new WaicProviderDiscovery();
		$live = array(
			'schema_version' => 2,
			'version' => time(),
			'generated_at' => gmdate('c'),
			'source' => self::SOURCE_LIVE,
			'providers' => array(),
			'models' => array(),
		);
		foreach ($providers as $providerId => $provider) {
			if (empty($provider['live_discovery']) || !$this->isRuntimeProvider($provider, $options)) {
				continue;
			}
			$keyField = $this->getProviderField($provider, 'api_key_field', $providerId);
			$apiKey = WaicUtils::getArrayValue($options, $keyField);
			if (empty($apiKey)) {
				continue;
			}
			$result = $discovery->discover($providerId, $apiKey);
			if (false === $result) {
				continue;
			}
			$normalized = $this->normalizeManifest($result, self::SOURCE_LIVE, false);
			if (false === $normalized) {
				continue;
			}
			$live['providers'] = array_merge($live['providers'], WaicUtils::getArrayValue($normalized, 'providers', array(), 2));
			$live['models'] = array_merge($live['models'], WaicUtils::getArrayValue($normalized, 'models', array(), 2));
		}
		if (empty($live['models'])) {
			return array('ok' => false, 'message' => esc_html__('No provider live models were discovered.', 'ai-copilot-content-generator'));
		}
		$manifest = $this->normalizeManifest($live, self::SOURCE_LIVE, false);
		$this->applySourceManifest(self::SOURCE_LIVE, $manifest, esc_html__('Provider live model discovery synced.', 'ai-copilot-content-generator'));
		return array('ok' => true, 'message' => esc_html__('Provider live model discovery synced.', 'ai-copilot-content-generator'));
	}

	private function applySourceManifest( $source, $manifest, $summary ) {
		$cache = $this->getCache();
		$sources = WaicUtils::getArrayValue($cache, 'sources', array(), 2);
		$cache['previous_sources'] = $sources;
		$sources[$source] = $manifest;
		$cache['sources'] = $sources;
		$active = $this->mergeManifests($this->getSourceStack($sources));
		if (false === $active) {
			return false;
		}
		$cache['last_valid_registry'] = $active;
		$cache['last_valid_at'] = gmdate('c');
		$cache['last_summary'] = sanitize_text_field((string) $summary);
		update_option(self::CACHE_OPTION, $cache, false);
		update_option(self::LAST_SYNC_OPTION, gmdate('Y-m-d H:i:s'), false);
		update_option(self::SOURCE_VERSION_OPTION, sanitize_text_field((string) WaicUtils::getArrayValue($manifest, 'version', '')), false);
		$this->registry = null;
		return true;
	}

	private function getSourceStack( $sources ) {
		$stack = array($this->loadBundledManifest());
		if (!empty($sources[self::SOURCE_LIVE])) {
			$stack[] = $this->normalizeManifest($sources[self::SOURCE_LIVE], self::SOURCE_LIVE, false);
		}
		if (!empty($sources[self::SOURCE_REMOTE])) {
			$stack[] = $this->normalizeManifest($sources[self::SOURCE_REMOTE], self::SOURCE_REMOTE, false);
		}
		$custom = get_option(self::CUSTOM_OPTION, array());
		if (!empty($custom)) {
			$stack[] = $this->normalizeManifest($custom, self::SOURCE_CUSTOM, false);
		}
		return $stack;
	}

	private function loadBundledManifest() {
		$modelPath = WAIC_MODULES_DIR . 'options' . WAIC_DS . 'data' . WAIC_DS . 'model-registry-bundled.json';
		$providerPath = WAIC_MODULES_DIR . 'options' . WAIC_DS . 'data' . WAIC_DS . 'provider-registry-bundled.json';
		if (!file_exists($modelPath) || !is_readable($modelPath) || !file_exists($providerPath) || !is_readable($providerPath)) {
			return false;
		}
		$models = json_decode(file_get_contents($modelPath), true);
		$providers = json_decode(file_get_contents($providerPath), true);
		if (!is_array($models) || !is_array($providers)) {
			return false;
		}
		$models['providers'] = WaicUtils::getArrayValue($providers, 'providers', array(), 2);
		$models['version'] = max((int) WaicUtils::getArrayValue($models, 'version', 1, 1), (int) WaicUtils::getArrayValue($providers, 'version', 1, 1));
		return $this->normalizeManifest($models, self::SOURCE_BUNDLED, false);
	}

	private function mergeManifests( $manifests ) {
		$merged = array(
			'schema_version' => 1,
			'version' => 1,
			'generated_at' => gmdate('c'),
			'source' => 'merged',
			'providers' => array(),
			'models' => array(),
		);
		foreach ($manifests as $manifest) {
			if (false === $manifest || empty($manifest) || !is_array($manifest)) {
				continue;
			}
			foreach (WaicUtils::getArrayValue($manifest, 'providers', array(), 2) as $providerId => $provider) {
				$providerId = sanitize_key($providerId);
				if (isset($merged['providers'][$providerId])) {
					$old = $merged['providers'][$providerId];
					if (in_array(WaicUtils::getArrayValue($provider, 'source', ''), array(self::SOURCE_REMOTE, self::SOURCE_LIVE), true)) {
						// A downloaded manifest may update presentation/model metadata,
						// never runtime code, auth, endpoints, status or capabilities.
						foreach (array('adapter', 'adapter_type', 'api_family', 'api_modes', 'auth_schema', 'configuration_schema', 'endpoint_policy', 'discovery_strategy', 'capabilities', 'status', 'visibility', 'credential_fields', 'api_key_field', 'model_field', 'image_model_field') as $locked) {
							$provider[$locked] = WaicUtils::getArrayValue($old, $locked, isset($provider[$locked]) ? $provider[$locked] : '');
						}
					}
					$provider['capabilities'] = array_values(array_unique(array_merge(WaicUtils::getArrayValue($old, 'capabilities', array(), 2), WaicUtils::getArrayValue($provider, 'capabilities', array(), 2))));
					$provider['api_modes'] = array_values(array_unique(array_merge(WaicUtils::getArrayValue($old, 'api_modes', array(), 2), WaicUtils::getArrayValue($provider, 'api_modes', array(), 2))));
					$merged['providers'][$providerId] = array_merge($old, $provider);
				} elseif (in_array(WaicUtils::getArrayValue($provider, 'source', ''), array(self::SOURCE_REMOTE, self::SOURCE_LIVE), true)) {
					// Downloaded data cannot introduce a provider that has no
					// reviewed bundled adapter mapping.
					continue;
				} else {
					$merged['providers'][$providerId] = $provider;
				}
			}
			foreach (WaicUtils::getArrayValue($manifest, 'models', array(), 2) as $modelKey => $model) {
				$key = $this->modelKey($model);
				if ('' === $key) {
					continue;
				}
				if (isset($merged['models'][$key])) {
					$old = $merged['models'][$key];
					if (in_array(WaicUtils::getArrayValue($model, 'source', ''), array(self::SOURCE_REMOTE, self::SOURCE_LIVE), true)) {
						$model['capabilities'] = WaicUtils::getArrayValue($old, 'capabilities', array(), 2);
						$model['api_mode'] = WaicUtils::getArrayValue($old, 'api_mode', 'chat_completions');
					}
					$model['capabilities'] = array_values(array_unique(array_merge(WaicUtils::getArrayValue($old, 'capabilities', array(), 2), WaicUtils::getArrayValue($model, 'capabilities', array(), 2))));
					$model['badges'] = array_values(array_unique(array_merge(WaicUtils::getArrayValue($old, 'badges', array(), 2), WaicUtils::getArrayValue($model, 'badges', array(), 2))));
					$merged['models'][$key] = array_merge($old, $model);
				} else {
					$merged['models'][$key] = $model;
				}
			}
			$merged['version'] = max($merged['version'], (int) WaicUtils::getArrayValue($manifest, 'version', 1, 1));
			$merged['generated_at'] = WaicUtils::getArrayValue($manifest, 'generated_at', $merged['generated_at']);
		}
		return empty($merged['providers']) ? false : $merged;
	}

	public function normalizeManifest( $data, $source, $strict = true ) {
		if (empty($data) || !is_array($data)) {
			return false;
		}
		$schema = (int) WaicUtils::getArrayValue($data, 'schema_version', 1, 1);
		if (!in_array($schema, array(1, 2), true)) {
			return false;
		}
		$source = $this->normalizeSource($source);
		$providers = WaicUtils::getArrayValue($data, 'providers', array(), 2);
		$models = WaicUtils::getArrayValue($data, 'models', array(), 2);
		if (!is_array($providers) || !is_array($models)) {
			return false;
		}
		$out = array(
			'schema_version' => 2,
			'version' => max(1, (int) WaicUtils::getArrayValue($data, 'version', 1, 1)),
			'generated_at' => $this->normalizeDate(WaicUtils::getArrayValue($data, 'generated_at', gmdate('c'))),
			'source' => $source,
			'providers' => array(),
			'models' => array(),
		);
		if (count($providers) > 100 || count($models) > 2000) {
			return false;
		}
		foreach ($providers as $providerId => $provider) {
			$clean = $this->normalizeProvider($providerId, $provider, $source);
			if (false === $clean) {
				if ($strict) {
					return false;
				}
				continue;
			}
			$out['providers'][$clean['id']] = $clean;
		}
		foreach ($models as $modelId => $model) {
			$clean = $this->normalizeModel($modelId, $model, $source);
			if (false === $clean) {
				if ($strict) {
					return false;
				}
				continue;
			}
			$out['models'][$this->modelKey($clean)] = $clean;
		}
		return $out;
	}

	private function normalizeProvider( $providerId, $provider, $source ) {
		if (!is_array($provider)) {
			return false;
		}
		$id = sanitize_key((string) WaicUtils::getArrayValue($provider, 'id', $providerId));
		if (!$this->validProviderKey($id)) {
			return false;
		}
		$apiModes = $this->sanitizeList(WaicUtils::getArrayValue($provider, 'api_modes', array('chat_completions'), 2));
		$capabilities = WaicProviderCapabilities::normalizeCapabilityList(WaicUtils::getArrayValue($provider, 'capabilities', array('chat'), 2));
		$apiFamily = $this->sanitizeList(WaicUtils::getArrayValue($provider, 'api_family', $apiModes, 2));
		$apiFamilyValue = empty($apiFamily) ? $apiModes : $apiFamily;
		// The historical custom endpoint contract exposed this stable string;
		// retain it while all shipped provider manifests use schema-v2 arrays.
		if (self::SOURCE_CUSTOM === $source && 'custom_oai' === WaicUtils::getArrayValue($provider, 'support_type', '')) {
			$apiFamilyValue = 'openai_compatible';
		}
		$configuration = $this->sanitizeList(WaicUtils::getArrayValue($provider, 'configuration_schema', array(), 2));
		$credentials = $this->sanitizeList(WaicUtils::getArrayValue($provider, 'credential_fields', array(), 2));
		$normalized = array(
			'id' => $id,
			'label' => sanitize_text_field((string) WaicUtils::getArrayValue($provider, 'label', $id)),
			'group' => $this->normalizeProviderGroup(WaicUtils::getArrayValue($provider, 'group', 'custom')),
			'kind' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'kind', 'llm')),
			'status' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'status', 'active')),
			'visibility' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'visibility', 'public')),
			'adapter' => empty($provider['adapter']) ? 0 : 1,
			'live_discovery' => empty($provider['live_discovery']) ? 0 : 1,
			'support_type' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'support_type', 'custom_oai')),
			'adapter_type' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'adapter_type', '')),
			'api_family' => $apiFamilyValue,
			'auth_schema' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'auth_schema', 'bearer')),
			'configuration_schema' => $configuration,
			'endpoint_policy' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'endpoint_policy', 'builtin_allowlist')),
			'discovery_strategy' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'discovery_strategy', 'none')),
			'capabilities' => $capabilities,
			'credential_fields' => $credentials,
			'api_modes' => empty($apiModes) ? array('chat_completions') : $apiModes,
			'api_key_field' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'api_key_field', $this->defaultKeyField($id))),
			'model_field' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'model_field', $this->defaultModelField($id))),
			'image_model_field' => sanitize_key((string) WaicUtils::getArrayValue($provider, 'image_model_field', $this->defaultImageModelField($id))),
			'docs_url' => esc_url_raw((string) WaicUtils::getArrayValue($provider, 'docs_url', '')),
			'api_reference_url' => esc_url_raw((string) WaicUtils::getArrayValue($provider, 'api_reference_url', '')),
			'terms_url' => esc_url_raw((string) WaicUtils::getArrayValue($provider, 'terms_url', '')),
			'privacy_url' => esc_url_raw((string) WaicUtils::getArrayValue($provider, 'privacy_url', '')),
			'verified_at' => $this->normalizeDate(WaicUtils::getArrayValue($provider, 'verified_at', gmdate('c'))),
			'source' => $source,
		);
		if (self::SOURCE_BUNDLED === $source && ('' === $normalized['adapter_type'] || empty($normalized['api_family']) || '' === $normalized['auth_schema'] || '' === $normalized['endpoint_policy'] || '' === $normalized['terms_url'] || '' === $normalized['privacy_url'])) {
			return false;
		}
		return $normalized;
	}

	private function normalizeModel( $modelId, $model, $source ) {
		if (!is_array($model)) {
			return false;
		}
		$id = $this->sanitizeModelId(WaicUtils::getArrayValue($model, 'id', is_string($modelId) ? $modelId : ''));
		$provider = sanitize_key((string) WaicUtils::getArrayValue($model, 'provider', ''));
		if ('' === $id || !$this->validProviderKey($provider)) {
			return false;
		}
		$apiMode = WaicProviderCapabilities::inferApiMode($provider, $id, $model);
		$capabilities = WaicProviderCapabilities::inferCapabilities($provider, $id, $model);
		if (in_array($source, array(self::SOURCE_REMOTE, self::SOURCE_LIVE), true)) {
			// Discovery is display metadata only: it cannot turn on a new
			// operation or route a model through a different API family.
			$apiMode = 'chat_completions';
			$capabilities = array('chat');
		}
		$status = sanitize_key((string) WaicUtils::getArrayValue($model, 'status', 'active'));
		$badges = $this->sanitizeList(WaicUtils::getArrayValue($model, 'badges', array(), 2));
		foreach (array($status, $source) as $badge) {
			if (in_array($badge, array('new', 'deprecated', 'preview', 'limited', 'custom', 'provider_live', 'user_custom'), true) && !in_array($badge, $badges, true)) {
				$badges[] = 'provider_live' === $badge ? 'live' : ('user_custom' === $badge ? 'custom' : $badge);
			}
		}
		return array(
			'id' => $id,
			'label' => sanitize_text_field((string) WaicUtils::getArrayValue($model, 'label', $id)),
			'provider' => $provider,
			'capabilities' => $capabilities,
			'api_mode' => $apiMode,
			'status' => $status,
			'visibility' => sanitize_key((string) WaicUtils::getArrayValue($model, 'visibility', 'public')),
			'context_tokens' => max(0, (int) WaicUtils::getArrayValue($model, 'context_tokens', 0, 1)),
			'max_output_tokens' => max(0, (int) WaicUtils::getArrayValue($model, 'max_output_tokens', 0, 1)),
			'pricing_key' => sanitize_text_field((string) WaicUtils::getArrayValue($model, 'pricing_key', $provider . '/' . $id)),
			'deprecated_after' => $this->nullableText(WaicUtils::getArrayValue($model, 'deprecated_after', null)),
			'replacement' => $this->nullableText(WaicUtils::getArrayValue($model, 'replacement', null)),
			'badges' => array_values(array_unique($badges)),
			'source' => $source,
		);
	}

	private function isRuntimeProvider( $provider, $options = array() ) {
		$visibility = WaicUtils::getArrayValue($provider, 'visibility', 'configured');
		if (empty($provider['adapter']) || ('public' !== $visibility && 'configured' !== $visibility)) {
			return false;
		}
		$adapter = WaicProviderAdapterFactory::getAdapter(WaicUtils::getArrayValue($provider, 'id', ''), '', $provider);
		if (!$adapter) {
			return false;
		}
		return true === $adapter->validateProfile(array('configuration' => is_array($options) ? $options : array()));
	}

	private function formatModelLabel( $model ) {
		$label = WaicUtils::getArrayValue($model, 'label', WaicUtils::getArrayValue($model, 'id', ''));
		$badges = WaicUtils::getArrayValue($model, 'badges', array(), 2);
		$show = array();
		foreach ($badges as $badge) {
			if (in_array($badge, array('new', 'deprecated', 'preview', 'limited', 'custom', 'live'), true)) {
				$show[] = $badge;
			}
		}
		return empty($show) ? $label : $label . ' [' . implode(', ', $show) . ']';
	}

	private function providerAttrs( $provider ) {
		$attrs = array(
			'data-provider-group' => WaicUtils::getArrayValue($provider, 'group', 'custom'),
			'data-capabilities' => implode(',', WaicUtils::getArrayValue($provider, 'capabilities', array(), 2)),
			'data-key-field' => WaicUtils::getArrayValue($provider, 'api_key_field', ''),
			'data-model-field' => WaicUtils::getArrayValue($provider, 'model_field', ''),
			'data-image-model-field' => WaicUtils::getArrayValue($provider, 'image_model_field', ''),
			'data-profile-schema' => implode(',', WaicUtils::getArrayValue($provider, 'configuration_schema', array(), 2)),
			'data-source' => WaicUtils::getArrayValue($provider, 'source', ''),
		);
		return $this->htmlDataAttrs($attrs);
	}

	private function modelAttrs( $model ) {
		$attrs = array(
			'data-capabilities' => implode(',', WaicUtils::getArrayValue($model, 'capabilities', array(), 2)),
			'data-status' => WaicUtils::getArrayValue($model, 'status', ''),
			'data-source' => WaicUtils::getArrayValue($model, 'source', ''),
			'data-badges' => implode(',', WaicUtils::getArrayValue($model, 'badges', array(), 2)),
			'data-api-mode' => WaicUtils::getArrayValue($model, 'api_mode', ''),
		);
		return $this->htmlDataAttrs($attrs);
	}

	private function htmlDataAttrs( $attrs ) {
		$out = '';
		foreach ($attrs as $key => $value) {
			if ('' !== (string) $value) {
				$out .= ' ' . $key . '="' . esc_attr((string) $value) . '"';
			}
		}
		return $out;
	}

	private function getCurrentModels( $api, $currentOptions ) {
		$current = array();
		$modelFields = WaicUtils::getArrayValue($api, 'model-fields', array(), 2);
		foreach ($modelFields as $provider => $field) {
			$value = WaicUtils::getArrayValue($currentOptions, $field);
			if (!empty($value)) {
				$current[$provider] = (string) $value;
			}
		}
		return $current;
	}

	private function getImageModelFields( $providers ) {
		$fields = array('img_model', 'gemini_img_model', 'openrouter_img_model');
		foreach ($providers as $providerId => $provider) {
			$field = $this->getProviderField($provider, 'image_model_field', $providerId);
			if ('' !== $field && !in_array($field, $fields, true)) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	private function getProviderField( $provider, $field, $providerId ) {
		$value = sanitize_key((string) WaicUtils::getArrayValue($provider, $field, ''));
		if ('' !== $value) {
			return $value;
		}
		if ('api_key_field' === $field) {
			return $this->defaultKeyField($providerId);
		}
		if ('image_model_field' === $field) {
			return $this->defaultImageModelField($providerId);
		}
		return $this->defaultModelField($providerId);
	}

	private function defaultModelField( $provider ) {
		$map = array(
			'open-ai' => 'model',
			'deep-seek' => 'deep_seek_model',
			'gemini' => 'gemini_model',
			'claude' => 'claude_model',
			'perplexity' => 'perplexity_model',
			'openrouter' => 'openrouter_model',
		);
		return isset($map[$provider]) ? $map[$provider] : $provider . '_model';
	}

	private function defaultKeyField( $provider ) {
		$map = array(
			'open-ai' => 'api_key',
			'deep-seek' => 'deep_seek_api_key',
			'gemini' => 'gemini_api_key',
			'claude' => 'claude_api_key',
			'perplexity' => 'perplexity_api_key',
			'openrouter' => 'openrouter_api_key',
		);
		return isset($map[$provider]) ? $map[$provider] : $provider . '_api_key';
	}

	private function defaultImageModelField( $provider ) {
		$map = array(
			'open-ai' => 'img_model',
			'gemini' => 'gemini_img_model',
			'openrouter' => 'openrouter_img_model',
		);
		return isset($map[$provider]) ? $map[$provider] : '';
	}

	private function getApiOptions() {
		if ($this->optionsModel && method_exists($this->optionsModel, 'get')) {
			$options = $this->optionsModel->get('api');
			return is_array($options) ? $options : array();
		}
		return array();
	}

	private function getCache() {
		$cache = get_option(self::CACHE_OPTION, array());
		return is_array($cache) ? $cache : array();
	}

	private function getStoredErrors() {
		$errors = get_option(self::ERRORS_OPTION, array());
		return is_array($errors) ? $errors : array();
	}

	private function setStoredErrors( $errors ) {
		update_option(self::ERRORS_OPTION, array_slice(array_map('sanitize_text_field', (array) $errors), 0, 10), false);
	}

	private function clearErrors() {
		$this->errors = array();
	}

	private function addError( $message ) {
		$this->errors[] = sanitize_text_field((string) $message);
		$this->setStoredErrors($this->errors);
	}

	private function getGroupLabels() {
		return array(
			'core' => esc_html__('Core', 'ai-copilot-content-generator'),
			'china' => esc_html__('China', 'ai-copilot-content-generator'),
			'image' => esc_html__('Image', 'ai-copilot-content-generator'),
			'aggregator' => esc_html__('Aggregator', 'ai-copilot-content-generator'),
			'custom' => esc_html__('Custom', 'ai-copilot-content-generator'),
		);
	}

	private function getCapabilityLabels() {
		return array(
			'' => esc_html__('All capabilities', 'ai-copilot-content-generator'),
			'chat' => 'chat',
			'vision' => 'vision',
			'tools' => 'tools',
			'image' => 'image',
			'embedding' => 'embedding',
			'reasoning' => 'reasoning',
		);
	}

	private function normalizeSource( $source ) {
		$source = sanitize_key((string) $source);
		return in_array($source, array(self::SOURCE_BUNDLED, self::SOURCE_REMOTE, self::SOURCE_LIVE, self::SOURCE_CUSTOM), true) ? $source : self::SOURCE_BUNDLED;
	}

	private function normalizeProviderGroup( $group ) {
		$group = sanitize_key((string) $group);
		return in_array($group, array('core', 'china', 'image', 'aggregator', 'custom'), true) ? $group : 'custom';
	}

	private function sanitizeList( $values ) {
		$out = array();
		foreach ((array) $values as $value) {
			$value = sanitize_key((string) $value);
			if ('' !== $value && !in_array($value, $out, true)) {
				$out[] = $value;
			}
		}
		return $out;
	}

	private function sanitizeModelId( $model ) {
		$model = trim(sanitize_text_field((string) $model));
		return substr($model, 0, 180);
	}

	private function nullableText( $value ) {
		if (is_null($value) || '' === $value) {
			return null;
		}
		return sanitize_text_field((string) $value);
	}

	private function modelKey( $model ) {
		$provider = sanitize_key((string) WaicUtils::getArrayValue($model, 'provider', ''));
		$id = (string) WaicUtils::getArrayValue($model, 'id', '');
		return ('' === $provider || '' === $id) ? '' : $provider . '::' . $id;
	}

	private function validProviderKey( $provider ) {
		return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{1,48}$/', (string) $provider);
	}

	private function normalizeDate( $date ) {
		$timestamp = strtotime((string) $date);
		return $timestamp ? gmdate('c', $timestamp) : gmdate('c');
	}

	private function sanitizeRemoteUrl( $url ) {
		$url = trim(wp_strip_all_tags((string) $url));
		if ('' === $url) {
			return '';
		}
		$url = esc_url_raw($url, array('https'));
		if ('' === $url) {
			return '';
		}
		$parts = wp_parse_url($url);
		if (!is_array($parts) || 'https' !== WaicUtils::getArrayValue($parts, 'scheme') || empty($parts['host'])) {
			return '';
		}
		if (!empty($parts['user']) || !empty($parts['pass'])) {
			return '';
		}
		$host = strtolower((string) $parts['host']);
		if ('localhost' === $host || false !== strpos($host, '..') || preg_match('/(^|\.)local$/', $host)) {
			return '';
		}
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? $url : '';
		}
		return preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host) ? $url : '';
	}
}
