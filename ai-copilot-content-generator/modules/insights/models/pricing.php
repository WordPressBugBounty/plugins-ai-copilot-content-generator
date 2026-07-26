<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPricingModel extends WaicModel {
	const SUPPORTED_SCHEMA_VERSION = 1;
	const SOURCE_BUNDLED = 'bundled';
	const SOURCE_IMPORTED = 'imported';
	const SOURCE_CUSTOM_URL = 'custom_url';
	const SYNC_HOOK = 'waic_pricing_custom_url_sync';
	const LEGACY_SYNC_HOOK = 'waic_pricing_remote_sync';
	const MAX_SNAPSHOT_BYTES = 1048576;

	private $_pricing = null;
	private $_pricingFallback = false;

	public function ensureBundled( $force = false ) {
		$this->ensureOptions();
		$current = get_option('waic_pricing', array());
		$data = $this->loadBundledSnapshot();
		if (false === $data) {
			return !empty($current) && is_array($current);
		}
		if (!$force && self::SOURCE_BUNDLED !== $this->getSourceMode()) {
			return is_array($current) && !empty($current);
		}
		if (!$force && !empty($current) && is_array($current)) {
			$currentSource = $this->currentSource($current);
			$currentVersion = (int) WaicUtils::getArrayValue($current, 'version', 0, 1);
			if (self::SOURCE_BUNDLED === $currentSource && $currentVersion >= (int) $data['version']) {
				return true;
			}
		}
		$this->applySnapshot($data, self::SOURCE_BUNDLED, 'Bundled pricing snapshot.');
		return true;
	}

	public function getPricing() {
		if (is_null($this->_pricing)) {
			$this->ensureOptions();
			$data = get_option('waic_pricing', array());
			$source = $this->currentSource(is_array($data) ? $data : array());
			$normalized = $this->normalizeSnapshot($data, $source, false);
			$this->_pricingFallback = false;
			if (false === $normalized) {
				$normalized = $this->loadBundledSnapshot();
				$this->_pricingFallback = true;
			}
			$this->_pricing = is_array($normalized) ? $normalized : array();
		}
		return $this->_pricing;
	}

	public function getStatus() {
		$pricing = $this->getPricing();
		$display = $this->getDisplayCurrency();
		$customUrl = $this->getCustomUrl();
		$autoSync = (int) get_option('waic_pricing_auto_sync_enabled', 0);
		return array(
			'sync_enabled' => $autoSync,
			'auto_sync_enabled' => $autoSync,
			'source_mode' => $this->getSourceMode(),
			'source' => $this->currentSource($pricing),
			'version' => isset($pricing['version']) ? (int) $pricing['version'] : 0,
			'schema_version' => isset($pricing['schema_version']) ? (int) $pricing['schema_version'] : 0,
			'generated_at' => sanitize_text_field(WaicUtils::getArrayValue($pricing, 'generated_at', WaicUtils::getArrayValue($pricing, 'updated_at', ''))),
			'last_sync_at' => sanitize_text_field(get_option('waic_pricing_last_sync_at', '')),
			'last_sync_success_at' => sanitize_text_field(get_option('waic_pricing_last_sync_success_at', '')),
			'last_sync_status' => sanitize_key(get_option('waic_pricing_last_sync_status', 'not_run')),
			'last_sync_message' => $this->sanitizeStatusMessage(get_option('waic_pricing_last_sync_message', esc_html__('Pricing sync has not run yet.', 'ai-copilot-content-generator'))),
			'display_currency' => $display['currency'],
			'display_currency_symbol' => $display['symbol'],
			'display_currency_label' => $display['label'],
			'display_currency_fallback' => (int) $display['fallback'],
			'currencies' => $this->getDisplayCurrencies(),
			'custom_url' => esc_url($customUrl),
			'custom_url_host' => sanitize_text_field((string) WaicUtils::getArrayValue(wp_parse_url($customUrl), 'host', '')),
			'last_import_at' => sanitize_text_field(get_option('waic_pricing_last_import_at', '')),
			'fallback_active' => (int) $this->_pricingFallback,
			'can_sync' => (int) (self::SOURCE_CUSTOM_URL === $this->getSourceMode() && $this->isAllowedCustomUrl($customUrl)),
		);
	}

	public function saveSettings( $params ) {
		$this->clearErrors();
		$this->ensureOptions();
		$currency = strtoupper(sanitize_key((string) WaicUtils::getArrayValue($params, 'display_currency', 'USD')));
		$currencies = $this->getDisplayCurrencies();
		if (!isset($currencies[$currency])) {
			$currency = 'USD';
		}
		update_option('waic_pricing_display_currency', $currency, false);
		$mode = sanitize_key((string) WaicUtils::getArrayValue($params, 'pricing_source_mode', WaicUtils::getArrayValue($params, 'pricing_source', $this->getSourceMode())));
		if (!$this->isValidSource($mode)) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Unsupported pricing source.', 'ai-copilot-content-generator')));
			$mode = $this->getSourceMode();
		}
		$customUrlRaw = (string) WaicUtils::getArrayValue($params, 'pricing_custom_url', get_option('waic_pricing_custom_url', ''));
		$customUrl = $this->sanitizeCustomUrl($customUrlRaw);
		if (self::SOURCE_CUSTOM_URL === $mode && '' === $customUrl) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Use a valid HTTPS URL for pricing snapshots.', 'ai-copilot-content-generator')));
			$mode = $this->getSourceMode();
		}
		if ('' !== $customUrl) {
			update_option('waic_pricing_custom_url', $customUrl, false);
		}
		if (self::SOURCE_BUNDLED === $mode) {
			$this->resetToBundled(false);
		} elseif (self::SOURCE_IMPORTED === $mode) {
			if (!$this->activeSnapshotMatchesSource(self::SOURCE_IMPORTED)) {
				$this->pushError($this->sanitizeStatusMessage(esc_html__('Import a valid pricing snapshot before selecting imported pricing.', 'ai-copilot-content-generator')));
			} else {
				update_option('waic_pricing_source', self::SOURCE_IMPORTED, false);
			}
			$this->setAutoSync(0);
		} elseif (self::SOURCE_CUSTOM_URL === $mode) {
			update_option('waic_pricing_source', self::SOURCE_CUSTOM_URL, false);
			$this->setAutoSync(!empty($params['pricing_auto_sync_enabled']) ? 1 : 0);
		} else {
			$this->setAutoSync(0);
		}
		return $this->getStatus();
	}

	public function syncRemote( $manual = false ) {
		return $this->syncCustomUrl($manual);
	}

	public function syncCustomUrl( $manual = false ) {
		$this->ensureOptions();
		if (self::SOURCE_CUSTOM_URL !== $this->getSourceMode()) {
			$this->setSyncStatus('disabled', esc_html__('Custom pricing URL sync is disabled.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}
		if (!$manual && 1 !== (int) get_option('waic_pricing_auto_sync_enabled', 0)) {
			$this->setSyncStatus('disabled', esc_html__('Custom pricing URL auto-sync is disabled.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}

		$url = $this->getCustomUrl();
		if (!$this->isAllowedCustomUrl($url)) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing URL is unavailable. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}

		update_option('waic_pricing_last_sync_at', gmdate('Y-m-d H:i:s'), false);
		$response = wp_remote_get($url, array(
			'timeout' => 4,
			'redirection' => 1,
			'headers' => array('Accept' => 'application/json'),
			'reject_unsafe_urls' => true,
			'limit_response_size' => self::MAX_SNAPSHOT_BYTES,
		));
		if (is_wp_error($response)) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing sync failed. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 200 || $code >= 300) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing sync returned an unavailable snapshot. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$body = wp_remote_retrieve_body($response);
		if (strlen((string) $body) > self::MAX_SNAPSHOT_BYTES) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing snapshot is too large. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$raw = json_decode((string) $body, true);
		if (!is_array($raw)) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing sync returned invalid data. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$data = $this->normalizeSnapshot($raw, self::SOURCE_CUSTOM_URL, true);
		if (false === $data) {
			$this->setSyncStatus('failed', esc_html__('Custom pricing snapshot did not pass validation. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			return array('ok' => false, 'status' => $this->getStatus());
		}

		$this->applySnapshot($data, self::SOURCE_CUSTOM_URL, 'Custom URL pricing snapshot synced.');
		update_option('waic_pricing_last_sync_success_at', gmdate('Y-m-d H:i:s'), false);
		$this->setSyncStatus('success', esc_html__('Custom pricing snapshot synced successfully.', 'ai-copilot-content-generator'), false);
		return array('ok' => true, 'status' => $this->getStatus());
	}

	public function importSnapshot( $params, $files = array() ) {
		$this->clearErrors();
		$this->ensureOptions();
		$json = $this->readImportedSnapshotJson($params, $files);
		if (false === $json) {
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$raw = json_decode($json, true);
		if (!is_array($raw)) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot JSON is invalid.', 'ai-copilot-content-generator')));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$data = $this->normalizeSnapshot($raw, self::SOURCE_IMPORTED, true);
		if (false === $data) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot did not pass validation.', 'ai-copilot-content-generator')));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$this->applySnapshot($data, self::SOURCE_IMPORTED, 'Imported pricing snapshot.');
		$this->setAutoSync(0);
		update_option('waic_pricing_last_import_at', gmdate('Y-m-d H:i:s'), false);
		$this->setSyncStatus('success', esc_html__('Imported pricing snapshot successfully.', 'ai-copilot-content-generator'), false);
		return array('ok' => true, 'status' => $this->getStatus());
	}

	public function resetToBundled( $recordStatus = true ) {
		$this->ensureOptions();
		$data = $this->loadBundledSnapshot();
		if (false === $data) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Bundled pricing snapshot is unavailable.', 'ai-copilot-content-generator')));
			return array('ok' => false, 'status' => $this->getStatus());
		}
		$this->applySnapshot($data, self::SOURCE_BUNDLED, 'Bundled pricing snapshot restored.');
		$this->setAutoSync(0);
		if ($recordStatus) {
			$this->setSyncStatus('success', esc_html__('Bundled pricing snapshot restored.', 'ai-copilot-content-generator'), false);
		}
		return array('ok' => true, 'status' => $this->getStatus());
	}

	public function calculateMicro( $engine, $model, $usage, $operation = 'chat' ) {
		$usage = $this->normalizeUsageForPricing($usage);
		if (isset($usage['_openrouter_cost_micro'])) {
			return array('cost_micro_usd' => max(0, (int) $usage['_openrouter_cost_micro']), 'est_flags' => 0);
		}
		$pricing = $this->getPricing();
		$engine = $this->normalizeEngine($engine);
		$model = $this->normalizeModelId($model);
		$providers = $this->providersFromSnapshot($pricing);
		$models = isset($providers[$engine]['models']) ? $providers[$engine]['models'] : array();
		$resolved = $this->resolveModelPricing($pricing, $engine, $model, $models);
		$p = $resolved['pricing'];
		$estFlags = (int) $resolved['est_flags'];

		if (empty($p) || !is_array($p)) {
			return array(
				'cost_micro_usd' => 0,
				'est_flags' => $this->usageHasBillableSignal($usage, $operation) ? ($this->estimateFlag('cost') | $this->estimateFlag('pricing_unknown') | $estFlags) : $estFlags,
				'pricing_model' => $resolved['model'],
			);
		}
		if ('image' === $operation || 'image' === WaicUtils::getArrayValue($p, 'operation', '')) {
			$image = $this->calculateImageMicro($p, $usage);
			$image['est_flags'] = (int) $image['est_flags'] | $estFlags;
			$image['pricing_model'] = $resolved['model'];
			return $image;
		}

		$input = max(0, (int) WaicUtils::getArrayValue($usage, 'input_tokens', 0, 1));
		$cached = max(0, (int) WaicUtils::getArrayValue($usage, 'cached_tokens', 0, 1));
		$cacheWrite = max(0, (int) WaicUtils::getArrayValue($usage, 'cache_write_tokens', 0, 1));
		$output = max(0, (int) WaicUtils::getArrayValue($usage, 'output_tokens', 0, 1));
		$reasoning = max(0, (int) WaicUtils::getArrayValue($usage, 'reasoning_tokens', 0, 1));
		if ($reasoning > 0 && in_array($engine, array('open-ai', 'deepseek', 'openrouter', 'perplexity'), true)) {
			$output = max(0, $output - $reasoning);
		}
		$cost = 0.0;
		$cost += ($input / 1000000) * (float) WaicUtils::getArrayValue($p, 'input_per_1m', 0);
		$cost += ($cached / 1000000) * (float) WaicUtils::getArrayValue($p, 'cached_input_per_1m', WaicUtils::getArrayValue($p, 'input_per_1m', 0));
		$cost += ($cacheWrite / 1000000) * (float) WaicUtils::getArrayValue($p, 'cache_write_per_1m', WaicUtils::getArrayValue($p, 'input_per_1m', 0));
		$cost += ($output / 1000000) * (float) WaicUtils::getArrayValue($p, 'output_per_1m', 0);
		$cost += ($reasoning / 1000000) * (float) WaicUtils::getArrayValue($p, 'reasoning_per_1m', WaicUtils::getArrayValue($p, 'output_per_1m', 0));

		$perSearch = WaicUtils::getArrayValue($p, 'per_search', null);
		if (null !== $perSearch && '' !== $perSearch) {
			if (array_key_exists('search_count', $usage)) {
				$cost += max(0, (int) $usage['search_count']) * (float) $perSearch;
			} else {
				$estFlags |= $this->estimateFlag('cost') | $this->estimateFlag('pricing_unknown');
			}
		}

		return array(
			'cost_micro_usd' => max(0, (int) round($cost * 1000000)),
			'est_flags' => $estFlags,
			'pricing_model' => $resolved['model'],
		);
	}

	public function formatMicroUsd( $costMicro, $precision = 4 ) {
		$display = $this->getDisplayCurrency();
		$amount = ((int) $costMicro) / 1000000;
		if ('USD' !== $display['currency'] && !$display['fallback']) {
			$amount = $amount * (float) $display['rate'];
		}
		$formatted = number_format((float) $amount, (int) $precision, '.', '');
		return array(
			'amount' => $formatted,
			'formatted' => $display['symbol'] . $formatted,
			'currency' => $display['currency'],
			'fallback' => (int) $display['fallback'],
		);
	}

	private function normalizeUsageForPricing( $usage ) {
		if (!is_array($usage)) {
			return array();
		}
		if (class_exists('WaicAiproviderModel') && method_exists('WaicAiproviderModel', 'normalizeUsage')) {
			return WaicAiproviderModel::normalizeUsage($usage);
		}
		$normalized = array();
		$numericKeys = array(
			'input_tokens',
			'output_tokens',
			'cached_tokens',
			'cache_write_tokens',
			'reasoning_tokens',
			'total_tokens',
			'image_count',
			'images_count',
			'search_count',
		);
		foreach ($numericKeys as $key) {
			if (isset($usage[$key]) && is_numeric($usage[$key])) {
				$normalized[$key] = max(0, (int) $usage[$key]);
			}
		}
		foreach (array('size', 'dimensions') as $key) {
			if (isset($usage[$key])) {
				$value = preg_replace('/[^0-9xX]/', '', (string) $usage[$key]);
				if ('' !== $value) {
					$normalized[$key] = strtolower($value);
				}
			}
		}
		if (isset($usage['quality'])) {
			$normalized['quality'] = sanitize_key((string) $usage['quality']);
		}
		if (isset($usage['_openrouter_cost_micro']) && is_numeric($usage['_openrouter_cost_micro'])) {
			$normalized['_openrouter_cost_micro'] = max(0, (int) $usage['_openrouter_cost_micro']);
		}
		return $normalized;
	}

	private function estimateFlag( $key ) {
		$fallback = array(
			'cost' => 2,
			'fine_tuned_base_pricing' => 16,
			'pricing_unknown' => 32,
		);
		$constants = array(
			'cost' => 'EST_FLAG_COST',
			'fine_tuned_base_pricing' => 'EST_FLAG_FINE_TUNED_BASE_PRICING',
			'pricing_unknown' => 'EST_FLAG_PRICING_UNKNOWN',
		);
		if (isset($constants[$key]) && class_exists('WaicAiproviderModel') && defined('WaicAiproviderModel::' . $constants[$key])) {
			return (int) constant('WaicAiproviderModel::' . $constants[$key]);
		}
		return isset($fallback[$key]) ? (int) $fallback[$key] : 0;
	}

	private function calculateImageMicro( $p, $usage ) {
		$countKnown = array_key_exists('images_count', $usage) || array_key_exists('image_count', $usage);
		$count = max(1, (int) WaicUtils::getArrayValue($usage, 'images_count', WaicUtils::getArrayValue($usage, 'image_count', 1, 1), 1));
		$unit = $this->resolveImageUnit($p, $usage);
		if (null === $unit) {
			return array('cost_micro_usd' => 0, 'est_flags' => $this->estimateFlag('cost') | $this->estimateFlag('pricing_unknown'));
		}
		$flags = $unit['estimated'] || !$countKnown ? $this->estimateFlag('cost') : 0;
		return array('cost_micro_usd' => (int) round($count * (float) $unit['price'] * 1000000), 'est_flags' => $flags);
	}

	private function resolveImageUnit( $p, $usage ) {
		$size = $this->normalizeImageSize(WaicUtils::getArrayValue($usage, 'size', WaicUtils::getArrayValue($usage, 'dimensions', '')));
		$quality = sanitize_key((string) WaicUtils::getArrayValue($usage, 'quality', ''));
		$candidates = array();
		if ('' !== $size && '' !== $quality) {
			$candidates[] = 'per_image_' . $size . '_' . $quality;
		}
		if ('' !== $size) {
			$candidates[] = 'per_image_' . $size;
		}
		if ('' !== $quality) {
			$candidates[] = 'image_per_unit_' . $quality;
		}
		if ('' !== $size || '' !== $quality) {
			$candidates[] = 'image_per_unit';
			$candidates[] = 'per_image_1024x1024';
		}
		foreach ($candidates as $key) {
			if (isset($p[$key]) && is_numeric($p[$key])) {
				return array('price' => (float) $p[$key], 'estimated' => false);
			}
		}
		$max = null;
		foreach ($p as $key => $value) {
			if ((0 === strpos($key, 'per_image_') || 0 === strpos($key, 'image_per_unit')) && is_numeric($value)) {
				$max = is_null($max) ? (float) $value : max($max, (float) $value);
			}
		}
		return is_null($max) ? null : array('price' => $max, 'estimated' => true);
	}

	private function resolveModelPricing( $pricing, $engine, $model, $models ) {
		$p = isset($models[$model]) ? $models[$model] : null;
		$overrideKey = $engine . '/' . $model;
		if (!empty($pricing['overrides'][$overrideKey]) && is_array($pricing['overrides'][$overrideKey])) {
			$p = is_array($p) ? array_merge($p, $pricing['overrides'][$overrideKey]) : $pricing['overrides'][$overrideKey];
		}
		if (!empty($p) && is_array($p)) {
			return array('pricing' => $p, 'model' => $model, 'est_flags' => 0);
		}
		$baseModel = $this->resolveFineTunedBaseModel($engine, $model, $models);
		if ('' !== $baseModel && isset($models[$baseModel]) && is_array($models[$baseModel])) {
			return array(
				'pricing' => $models[$baseModel],
				'model' => $baseModel,
				'est_flags' => $this->estimateFlag('fine_tuned_base_pricing'),
			);
		}
		if ($this->isFineTunedModel($engine, $model)) {
			return array('pricing' => null, 'model' => $model, 'est_flags' => $this->estimateFlag('pricing_unknown'));
		}
		return array('pricing' => null, 'model' => $model, 'est_flags' => 0);
	}

	private function resolveFineTunedBaseModel( $engine, $model, $models ) {
		if (!$this->isFineTunedModel($engine, $model)) {
			return '';
		}
		$candidates = array();
		if (0 === strpos($model, 'ft:')) {
			$parts = explode(':', $model);
			if (!empty($parts[1])) {
				$candidates[] = $parts[1];
			}
		}
		if (preg_match('/^([^:]+):ft-[^:]+:/', $model, $match)) {
			$candidates[] = $match[1];
		}
		foreach ($candidates as $candidate) {
			$candidate = $this->normalizeModelId($candidate);
			foreach (array($candidate, preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $candidate), preg_replace('/-\d{4}$/', '', $candidate)) as $base) {
				if (is_string($base) && '' !== $base && isset($models[$base])) {
					return $base;
				}
			}
		}
		return '';
	}

	private function isFineTunedModel( $engine, $model ) {
		if ('open-ai' !== $engine) {
			return false;
		}
		return 0 === strpos($model, 'ft:') || false !== strpos($model, ':ft-') || false !== strpos($model, 'fine-tuned');
	}

	private function loadBundledSnapshot() {
		$path = WAIC_MODULES_DIR . 'insights' . WAIC_DS . 'data' . WAIC_DS . 'pricing-bundled.json';
		if (!file_exists($path) || !is_readable($path)) {
			return false;
		}
		$data = json_decode(file_get_contents($path), true);
		return $this->normalizeSnapshot($data, self::SOURCE_BUNDLED, false);
	}

	public function normalizeSnapshot( $data, $source = self::SOURCE_IMPORTED, $strict = true ) {
		if (empty($data) || !is_array($data)) {
			return false;
		}
		$source = $this->normalizeSource($source);
		if (!$this->isValidSource($source)) {
			return false;
		}
		if ($strict && !$this->containsOnlyKeys($data, array('schema_version', 'version', 'generated_at', 'updated_at', 'source', 'currency', 'providers', 'engines', 'overrides'))) {
			return false;
		}
		$schema = (int) WaicUtils::getArrayValue($data, 'schema_version', self::SUPPORTED_SCHEMA_VERSION, 1);
		if ($schema !== self::SUPPORTED_SCHEMA_VERSION) {
			return false;
		}
		$version = (int) WaicUtils::getArrayValue($data, 'version', 0, 1);
		if ($version < 1) {
			return false;
		}
		$currency = strtoupper(sanitize_key((string) WaicUtils::getArrayValue($data, 'currency', 'USD')));
		if ('USD' !== $currency) {
			return false;
		}
		$providers = $this->providersFromSnapshot($data);
		if (empty($providers) || !is_array($providers) || count($providers) > 50) {
			return false;
		}
		$cleanProviders = array();
		foreach ($providers as $provider => $providerData) {
			if ($strict && (!is_array($providerData) || !$this->containsOnlyKeys($providerData, array('models', '_note')))) {
				return false;
			}
			$provider = $this->normalizeEngine($provider);
			if (!$this->validProviderKey($provider) || !is_array($providerData) || !isset($providerData['models']) || !is_array($providerData['models'])) {
				return false;
			}
			if (count($providerData['models']) > 1000) {
				return false;
			}
			$cleanProviders[$provider] = array('models' => array());
			if (!empty($providerData['_note'])) {
				$cleanProviders[$provider]['_note'] = sanitize_text_field((string) $providerData['_note']);
			}
			foreach ($providerData['models'] as $model => $modelData) {
				$model = $this->normalizeModelId($model);
				if ('' === $model || empty($modelData) || !is_array($modelData)) {
					return false;
				}
				$clean = $this->normalizeModelPricing($modelData, $strict);
				if (false === $clean) {
					return false;
				}
				$cleanProviders[$provider]['models'][$model] = $clean;
			}
		}
		$out = array(
			'schema_version' => self::SUPPORTED_SCHEMA_VERSION,
			'version' => $version,
			'generated_at' => $this->normalizeDate(WaicUtils::getArrayValue($data, 'generated_at', WaicUtils::getArrayValue($data, 'updated_at', gmdate('c')))),
			'source' => $source,
			'currency' => 'USD',
			'providers' => $cleanProviders,
			'overrides' => array(),
		);
		if (!empty($data['overrides']) && is_array($data['overrides'])) {
			if (count($data['overrides']) > 500) {
				return false;
			}
			foreach ($data['overrides'] as $key => $override) {
				$key = $this->normalizeOverrideKey($key);
				if ('' === $key || !is_array($override)) {
					return false;
				}
				$clean = $this->normalizeModelPricing($override, $strict);
				if (false === $clean) {
					return false;
				}
				$out['overrides'][$key] = $clean;
			}
		} elseif ($strict && isset($data['overrides']) && !is_array($data['overrides'])) {
			return false;
		}
		return $out;
	}

	private function normalizeModelPricing( $data, $strict ) {
		if ($strict && !$this->containsOnlyKeys($data, array('prices', 'units', 'provider', 'model', 'operation', 'input_per_1m', 'output_per_1m', 'cached_input_per_1m', 'cache_write_per_1m', 'reasoning_per_1m', 'image_per_unit', 'embedding_per_1m', 'per_search', 'source', 'valid_from'))) {
			foreach ($data as $key => $value) {
				if (!$this->allowedPriceKey($key)) {
					return false;
				}
			}
		}
		$prices = isset($data['prices']) && is_array($data['prices']) ? $data['prices'] : $data;
		$units = isset($data['units']) && is_array($data['units']) ? $data['units'] : array();
		$allowed = array('input_per_1m', 'output_per_1m', 'cached_input_per_1m', 'cache_write_per_1m', 'reasoning_per_1m', 'image_per_unit', 'embedding_per_1m', 'per_search');
		$metadata = array('prices', 'units', 'provider', 'model', 'operation', 'source', 'valid_from');
		$clean = array();
		foreach ($prices as $key => $value) {
			$key = sanitize_key((string) $key);
			if (!in_array($key, $allowed, true) && 0 !== strpos($key, 'per_image_') && 0 !== strpos($key, 'image_per_unit')) {
				if ($strict && !in_array($key, $metadata, true)) {
					return false;
				}
				continue;
			}
			if (!is_numeric($value) || (float) $value < 0 || (float) $value > 100000) {
				return false;
			}
			$clean[$key] = (float) $value;
		}
		if (!empty($data['operation'])) {
			$operation = sanitize_key((string) $data['operation']);
			if (!in_array($operation, array('chat', 'embedding', 'image', 'fine_tune_upload', 'fine_tune_status'), true)) {
				return false;
			}
			$clean['operation'] = $operation;
		}
		if (isset($clean['embedding_per_1m']) && !isset($clean['input_per_1m'])) {
			$clean['input_per_1m'] = $clean['embedding_per_1m'];
		}
		if ($strict && isset($data['units']) && !is_array($data['units'])) {
			return false;
		}
		if (!empty($units)) {
			foreach ($units as $key => $unit) {
				$key = sanitize_key((string) $key);
				if (!isset($clean[$key])) {
					return false;
				}
				$unit = sanitize_key((string) $unit);
				if (!in_array($unit, array('usd_per_1m_tokens', 'usd_per_image', 'usd_per_search', 'usd'), true)) {
					return false;
				}
				$clean['units'][$key] = $unit;
			}
		}
		return !empty($clean) ? $clean : false;
	}

	private function ensureOptions() {
		$defaults = array(
			'waic_pricing_sync_enabled' => 0,
			'waic_pricing_auto_sync_enabled' => 0,
			'waic_pricing_display_currency' => 'USD',
			'waic_pricing_source' => self::SOURCE_BUNDLED,
			'waic_pricing_custom_url' => '',
			'waic_pricing_last_sync_at' => '',
			'waic_pricing_last_sync_success_at' => '',
			'waic_pricing_last_sync_status' => 'not_run',
			'waic_pricing_last_sync_message' => esc_html__('Pricing sync has not run yet.', 'ai-copilot-content-generator'),
			'waic_pricing_last_import_at' => '',
			'waic_pricing_migration_version' => 0,
		);
		foreach ($defaults as $key => $value) {
			if (false === get_option($key, false)) {
				add_option($key, $value, '', false);
			}
		}
		$this->migrateLegacyOptions();
		$this->setAutoSync((int) get_option('waic_pricing_auto_sync_enabled', 0));
	}

	private function setSyncStatus( $status, $message, $fallbackBundled ) {
		update_option('waic_pricing_last_sync_status', sanitize_key($status), false);
		update_option('waic_pricing_last_sync_message', $this->sanitizeStatusMessage($message), false);
		if ($fallbackBundled) {
			$this->ensureBundled(true);
		}
	}

	private function sanitizeStatusMessage( $message ) {
		$message = trim(wp_strip_all_tags((string) $message));
		$message = preg_replace('/sk-[A-Za-z0-9_\-]+/', '[redacted]', $message);
		$message = preg_replace('/Bearer\s+[A-Za-z0-9_\-\.]+/i', 'Bearer [redacted]', $message);
		$message = preg_replace('~https?://[^\s]+~i', '[remote]', $message);
		return substr($message, 0, 180);
	}

	private function clearErrors() {
		$this->_internalErrors = array();
		$this->_haveErrors = false;
	}

	private function applySnapshot( $data, $source, $summary = '' ) {
		$source = $this->normalizeSource($source);
		$data = $this->normalizeSnapshot($data, $source, false);
		if (false === $data) {
			return false;
		}
		update_option('waic_pricing', $data, false);
		update_option('waic_pricing_source', $source, false);
		$this->_pricing = $data;
		$this->_pricingFallback = false;
		$this->recordVersion($data, $source, $summary);
		return true;
	}

	private function migrateLegacyOptions() {
		if ((int) get_option('waic_pricing_migration_version', 0) >= 1) {
			return;
		}
		$legacySync = (int) get_option('waic_pricing_sync_enabled', 0);
		$source = sanitize_key((string) get_option('waic_pricing_source', self::SOURCE_BUNDLED));
		if ('remote' === $source) {
			$current = get_option('waic_pricing', array());
			$normalized = $this->normalizeSnapshot($current, self::SOURCE_IMPORTED, false);
			if (false !== $normalized) {
				update_option('waic_pricing', $normalized, false);
				update_option('waic_pricing_source', self::SOURCE_IMPORTED, false);
			} else {
				update_option('waic_pricing_source', self::SOURCE_BUNDLED, false);
			}
			if ($legacySync) {
				$this->setSyncStatus('disabled', esc_html__('Previous vendor pricing sync was disabled. Last valid pricing remains active.', 'ai-copilot-content-generator'), false);
			}
		} elseif (!$this->isValidSource($source)) {
			update_option('waic_pricing_source', self::SOURCE_BUNDLED, false);
		}
		if (self::SOURCE_CUSTOM_URL === $this->getSourceMode() && $this->isAllowedCustomUrl($this->getCustomUrl()) && $legacySync) {
			update_option('waic_pricing_auto_sync_enabled', 1, false);
		} else {
			update_option('waic_pricing_auto_sync_enabled', 0, false);
		}
		update_option('waic_pricing_sync_enabled', (int) get_option('waic_pricing_auto_sync_enabled', 0), false);
		update_option('waic_pricing_migration_version', 1, false);
	}

	private function setAutoSync( $enabled ) {
		$enabled = (self::SOURCE_CUSTOM_URL === $this->getSourceMode() && $this->isAllowedCustomUrl($this->getCustomUrl()) && (int) $enabled) ? 1 : 0;
		update_option('waic_pricing_auto_sync_enabled', $enabled, false);
		update_option('waic_pricing_sync_enabled', $enabled, false);
	}

	private function getSourceMode() {
		$source = $this->normalizeSource(get_option('waic_pricing_source', self::SOURCE_BUNDLED));
		return $this->isValidSource($source) ? $source : self::SOURCE_BUNDLED;
	}

	private function normalizeSource( $source ) {
		$source = sanitize_key((string) $source);
		return 'remote' === $source ? self::SOURCE_IMPORTED : $source;
	}

	private function isValidSource( $source ) {
		return in_array($source, array(self::SOURCE_BUNDLED, self::SOURCE_IMPORTED, self::SOURCE_CUSTOM_URL), true);
	}

	private function activeSnapshotMatchesSource( $source ) {
		$current = get_option('waic_pricing', array());
		if (!is_array($current)) {
			return false;
		}
		return $this->currentSource($current) === $source && false !== $this->normalizeSnapshot($current, $source, false);
	}

	private function getCustomUrl() {
		return $this->sanitizeCustomUrl(get_option('waic_pricing_custom_url', ''));
	}

	private function sanitizeCustomUrl( $url ) {
		$url = trim(wp_strip_all_tags((string) $url));
		if ('' === $url) {
			return '';
		}
		$url = esc_url_raw($url, array('https'));
		return $this->isAllowedCustomUrl($url) ? $url : '';
	}

	private function readImportedSnapshotJson( $params, $files ) {
		$pasted = isset($_POST['pricing_snapshot_json']) ? trim((string) wp_unslash($_POST['pricing_snapshot_json'])) : trim((string) WaicUtils::getArrayValue($params, 'pricing_snapshot_json', ''));
		if ('' !== $pasted) {
			if (strlen($pasted) > self::MAX_SNAPSHOT_BYTES) {
				$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot is too large.', 'ai-copilot-content-generator')));
				return false;
			}
			return $pasted;
		}
		$file = isset($files['pricing_snapshot_file']) ? $files['pricing_snapshot_file'] : (isset($_FILES['pricing_snapshot_file']) ? $_FILES['pricing_snapshot_file'] : array());
		if (empty($file) || (isset($file['error']) && UPLOAD_ERR_NO_FILE === (int) $file['error'])) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Paste JSON or upload a snapshot file.', 'ai-copilot-content-generator')));
			return false;
		}
		if (!isset($file['error']) || UPLOAD_ERR_OK !== (int) $file['error']) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot upload failed.', 'ai-copilot-content-generator')));
			return false;
		}
		$name = isset($file['name']) ? sanitize_file_name((string) $file['name']) : '';
		if ('json' !== strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot file must be JSON.', 'ai-copilot-content-generator')));
			return false;
		}
		$size = isset($file['size']) ? (int) $file['size'] : 0;
		if ($size < 1 || $size > self::MAX_SNAPSHOT_BYTES) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot is too large.', 'ai-copilot-content-generator')));
			return false;
		}
		$tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
		if ('' === $tmp || !is_readable($tmp)) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot upload could not be read.', 'ai-copilot-content-generator')));
			return false;
		}
		$json = file_get_contents($tmp, false, null, 0, self::MAX_SNAPSHOT_BYTES + 1);
		if (false === $json || strlen($json) > self::MAX_SNAPSHOT_BYTES) {
			$this->pushError($this->sanitizeStatusMessage(esc_html__('Pricing snapshot upload could not be read.', 'ai-copilot-content-generator')));
			return false;
		}
		return (string) $json;
	}

	private function getDisplayCurrency() {
		$currency = strtoupper(sanitize_key((string) get_option('waic_pricing_display_currency', 'USD')));
		$currencies = $this->getDisplayCurrencies();
		if (!isset($currencies[$currency]) || empty($currencies[$currency]['rate'])) {
			$currency = 'USD';
			$fallback = 1;
		} else {
			$fallback = 0;
		}
		return array(
			'currency' => $currency,
			'symbol' => $currencies[$currency]['symbol'],
			'label' => $currencies[$currency]['label'],
			'rate' => (float) $currencies[$currency]['rate'],
			'fallback' => $fallback,
		);
	}

	private function getDisplayCurrencies() {
		return apply_filters('waic_pricing_display_currencies', array(
			'USD' => array('label' => 'USD', 'symbol' => '$', 'rate' => 1),
			'EUR' => array('label' => 'EUR', 'symbol' => 'EUR ', 'rate' => 0.93),
			'GBP' => array('label' => 'GBP', 'symbol' => 'GBP ', 'rate' => 0.8),
		));
	}

	private function providersFromSnapshot( $pricing ) {
		return isset($pricing['providers']) ? $pricing['providers'] : (isset($pricing['engines']) ? $pricing['engines'] : array());
	}

	private function currentSource( $pricing ) {
		$source = $this->normalizeSource(WaicUtils::getArrayValue($pricing, 'source', get_option('waic_pricing_source', self::SOURCE_BUNDLED)));
		return $this->isValidSource($source) ? $source : self::SOURCE_BUNDLED;
	}

	private function containsOnlyKeys( $data, $allowed ) {
		foreach (array_keys($data) as $key) {
			if (!in_array((string) $key, $allowed, true)) {
				return false;
			}
		}
		return true;
	}

	private function allowedPriceKey( $key ) {
		$key = sanitize_key((string) $key);
		return in_array($key, array('prices', 'units', 'provider', 'model', 'operation', 'input_per_1m', 'output_per_1m', 'cached_input_per_1m', 'cache_write_per_1m', 'reasoning_per_1m', 'image_per_unit', 'embedding_per_1m', 'per_search', 'source', 'valid_from'), true)
			|| 0 === strpos($key, 'per_image_')
			|| 0 === strpos($key, 'image_per_unit');
	}

	private function normalizeOverrideKey( $key ) {
		$key = substr(sanitize_text_field((string) $key), 0, 220);
		if (false === strpos($key, '/')) {
			return '';
		}
		$parts = explode('/', $key, 2);
		$provider = $this->normalizeEngine($parts[0]);
		$model = $this->normalizeModelId($parts[1]);
		if (!$this->validProviderKey($provider) || '' === $model) {
			return '';
		}
		return $provider . '/' . $model;
	}

	private function usageHasBillableSignal( $usage, $operation ) {
		foreach (array('total_tokens', 'input_tokens', 'output_tokens', 'reasoning_tokens', 'cached_tokens', 'cache_write_tokens', 'images_count', 'search_count') as $key) {
			if (!empty($usage[$key])) {
				return true;
			}
		}
		return 'image' === $operation;
	}

	private function normalizeEngine( $engine ) {
		$engine = sanitize_key((string) $engine);
		if ('deep-seek' === $engine) {
			return 'deepseek';
		}
		return $engine;
	}

	private function normalizeModelId( $model ) {
		$model = trim(sanitize_text_field((string) $model));
		return substr($model, 0, 160);
	}

	private function normalizeImageSize( $size ) {
		$size = strtolower(trim(sanitize_text_field((string) $size)));
		$size = str_replace(array(' ', '*'), array('', 'x'), $size);
		return preg_match('/^\d{2,5}x\d{2,5}$/', $size) ? $size : '';
	}

	private function normalizeDate( $date ) {
		$timestamp = strtotime((string) $date);
		return $timestamp ? gmdate('c', $timestamp) : gmdate('c');
	}

	private function validProviderKey( $provider ) {
		return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{1,31}$/', (string) $provider);
	}

	private function isAllowedCustomUrl( $url ) {
		$url = trim((string) $url);
		if ('' === $url) {
			return false;
		}
		$parts = wp_parse_url($url);
		if (!is_array($parts) || 'https' !== WaicUtils::getArrayValue($parts, 'scheme') || empty($parts['host'])) {
			return false;
		}
		if (!empty($parts['user']) || !empty($parts['pass'])) {
			return false;
		}
		$host = strtolower((string) $parts['host']);
		if ('localhost' === $host || false !== strpos($host, '..') || preg_match('/(^|\.)local$/', $host)) {
			return false;
		}
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
		}
		if (!preg_match('/^[a-z0-9.-]+$/', $host) || false === strpos($host, '.')) {
			return false;
		}
		return true;
	}

	private function recordVersion( $data, $source, $summary = '' ) {
		if (!is_array($data) || empty($data['version']) || !WaicDb::exist('@__pricing_versions')) {
			return;
		}
		$version = (int) $data['version'];
		if (WaicDb::get('SELECT 1 FROM `@__pricing_versions` WHERE version=%d', 'one', ARRAY_A, array($version)) == 1) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . WAIC_DB_PREF . 'pricing_versions',
			array(
				'version' => $version,
				'applied_at' => gmdate('Y-m-d H:i:s'),
				'source' => sanitize_key($source),
				'snapshot' => wp_json_encode($data),
				'diff_summary' => $this->sanitizeStatusMessage($summary),
			),
			array('%d', '%s', '%s', '%s', '%s')
		);
	}
}
