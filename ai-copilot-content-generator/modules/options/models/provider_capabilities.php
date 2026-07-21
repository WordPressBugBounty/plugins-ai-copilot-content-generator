<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicProviderCapabilities {
	public static function normalizeCapabilityList( $capabilities ) {
		$result = array();
		if (!is_array($capabilities)) {
			return $result;
		}
		foreach ($capabilities as $capability) {
			$capability = sanitize_key((string) $capability);
			if ('image' === $capability) {
				$capability = 'image_generate';
			}
			if ('' !== $capability && !in_array($capability, $result, true)) {
				$result[] = $capability;
			}
		}
		return $result;
	}

	public static function inferCapabilities( $provider, $modelId, $raw = array() ) {
		$capabilities = self::normalizeCapabilityList(WaicUtils::getArrayValue($raw, 'capabilities', array(), 2));
		if (!empty($capabilities)) {
			return $capabilities;
		}
		// A discovery result without a reviewed contract never gains a capability
		// from its name. It may be displayed only as a conservative chat model.
		return array('chat');
	}

	public static function inferApiMode( $provider, $modelId, $raw = array() ) {
		$apiMode = sanitize_key((string) WaicUtils::getArrayValue($raw, 'api_mode', ''));
		if ('' !== $apiMode) {
			return $apiMode;
		}
		$modelId = strtolower((string) $modelId);
		return 'chat_completions';
	}

	public static function isModelVisible( $model, $provider, $settings, $currentModels = array() ) {
		if (empty($model) || !is_array($model)) {
			return false;
		}
		$id = (string) WaicUtils::getArrayValue($model, 'id', '');
		$providerId = (string) WaicUtils::getArrayValue($model, 'provider', '');
		$isCurrent = isset($currentModels[$providerId]) && $currentModels[$providerId] === $id;
		$visibility = sanitize_key((string) WaicUtils::getArrayValue($model, 'visibility', 'public'));
		$status = sanitize_key((string) WaicUtils::getArrayValue($model, 'status', 'active'));
		$source = sanitize_key((string) WaicUtils::getArrayValue($model, 'source', 'bundled'));
		if (!in_array($visibility, array('public', 'configured'), true) && !$isCurrent) {
			return false;
		}
		if ('deprecated' === $status && empty($settings['allow_deprecated']) && !$isCurrent) {
			return false;
		}
		if ('preview' === $status && empty($settings['allow_preview']) && !$isCurrent) {
			return false;
		}
		if ('limited' === $status && empty($settings['allow_limited']) && !$isCurrent) {
			return false;
		}
		if ('unverified' === $status && 'provider_live' === $source && empty($settings['allow_unverified_live']) && !$isCurrent) {
			return false;
		}
		$apiMode = sanitize_key((string) WaicUtils::getArrayValue($model, 'api_mode', 'chat_completions'));
		$providerModes = WaicUtils::getArrayValue($provider, 'api_modes', array(), 2);
		if (!empty($providerModes) && !in_array($apiMode, $providerModes, true) && !$isCurrent) {
			return false;
		}
		return true;
	}
}
