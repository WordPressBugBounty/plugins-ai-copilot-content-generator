<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicAnonymizerModel extends WaicModel {
	public function maskText( $text, $context = array(), $limit = 0 ) {
		$text = is_scalar($text) ? (string) $text : '';
		if ('' === $text) {
			return '';
		}

		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = wp_strip_all_tags($text);
		$text = preg_replace('/\s+/u', ' ', trim($text));
		$text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[EMAIL]', $text);
		$text = $this->_maskPaymentIdentifiers($text);
		$text = preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', '[IP]', $text);
		$text = preg_replace('/(?<![0-9a-f:])(?:[0-9a-f]{0,4}:){2,7}[0-9a-f]{0,4}(?![0-9a-f:])/i', '[IP]', $text);
		$text = preg_replace('/\border\s*#?\s*\d+\b/i', '[ORDER]', $text);
		$text = preg_replace('/(?<!\w)#\d{4,}\b/', '[ORDER]', $text);
		$text = $this->_maskPhoneNumbers($text);

		foreach ($this->_getNameMasks($context) as $name) {
			if ('' !== $name) {
				$text = preg_replace('/\b' . preg_quote($name, '/') . '\b/u', '[NAME]', $text);
			}
		}
		$text = $this->_maskGuestNames($text);

		return $limit > 0 ? $this->truncate($text, $limit) : $text;
	}

	public function maskForExport( $text, $context = array(), $limit = 0 ) {
		$text = $this->maskText($text, $context, $limit);
		if (preg_match('/^[=+\-@]/', ltrim($text))) {
			return '[FORMULA_REMOVED]';
		}
		return $text;
	}

	public function maskAttachment( $file ) {
		return empty($file) ? '' : '[FILE_REMOVED]';
	}

	public function maskArgsSummary( $args ) {
		if (is_string($args)) {
			$decoded = json_decode($args, true);
			if (is_array($decoded)) {
				$args = $decoded;
			}
		}
		if (!is_array($args)) {
			return $this->maskText(is_scalar($args) ? (string) $args : '', array(), 255);
		}

		$summary = array();
		$count = 0;
		foreach ($args as $key => $value) {
			if ($count >= 20) {
				$summary['truncated'] = true;
				break;
			}
			$key = is_scalar($key) ? sanitize_key((string) $key) : 'arg';
			if ('' === $key) {
				$key = 'arg';
			}
			if ($this->_isSensitiveArgKey($key) || $this->_looksSensitiveValue($value)) {
				$summary[$key] = '[REMOVED]';
			} else {
				$summary[$key] = $this->_argValueType($value);
			}
			$count++;
		}
		return $this->maskText(wp_json_encode($summary), array(), 255);
	}

	public function safeErrorLabel( $status, $meta = array() ) {
		$status = (int) $status;
		if (0 === $status) {
			return '';
		}
		if (is_string($meta)) {
			$decoded = json_decode($meta, true);
			$meta = is_array($decoded) ? $decoded : array('message' => $meta);
		}
		$text = strtolower(wp_json_encode(is_array($meta) ? $meta : array()));
		if (false !== strpos($text, 'limit') || false !== strpos($text, 'quota') || false !== strpos($text, 'budget')) {
			return esc_html__('blocked by limit', 'ai-copilot-content-generator');
		}
		if (false !== strpos($text, 'timeout') || false !== strpos($text, 'timed out')) {
			return esc_html__('timeout', 'ai-copilot-content-generator');
		}
		return esc_html__('provider issue', 'ai-copilot-content-generator');
	}

	public function clientHash( $identity ) {
		$identity = is_scalar($identity) ? trim((string) $identity) : '';
		if ('' === $identity) {
			return '';
		}
		$salt = function_exists('wp_salt') ? wp_salt('auth') : (defined('AUTH_SALT') ? AUTH_SALT : 'waic');
		return substr(sha1($salt . '|' . $identity), 0, 10);
	}

	public function truncate( $text, $limit ) {
		$limit = (int) $limit;
		if ($limit <= 0 || WaicUtils::mbstrlen($text) <= $limit) {
			return $text;
		}
		return rtrim(WaicUtils::mbsubstr($text, 0, max(0, $limit - 3))) . '...';
	}

	public function hasMask( $text ) {
		return (bool) preg_match('/\[(EMAIL|PHONE|IP|ORDER|NAME|CARD|IBAN|SSN|FILE_REMOVED|FORMULA_REMOVED)\]/', (string) $text);
	}

	private function _isSensitiveArgKey( $key ) {
		return (bool) preg_match('/(token|secret|password|passwd|pwd|key|auth|bearer|cookie|email|phone|payment|card|iban|ssn|order|ip)/i', (string) $key);
	}

	private function _looksSensitiveValue( $value ) {
		if (is_array($value)) {
			foreach ($value as $item) {
				if ($this->_looksSensitiveValue($item)) {
					return true;
				}
			}
			return false;
		}
		if (!is_scalar($value)) {
			return false;
		}
		$value = (string) $value;
		return (bool) preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}|bearer\s+[a-z0-9._\-]+|sk-[a-z0-9_\-]+|\b(?:\d{1,3}\.){3}\d{1,3}\b|\b(?:\d[ -]?){13,19}\b)/i', $value);
	}

	private function _argValueType( $value ) {
		if (is_array($value)) {
			return 'array(' . count($value) . ')';
		}
		if (is_bool($value)) {
			return 'bool';
		}
		if (is_int($value) || is_float($value)) {
			return 'number';
		}
		if (is_null($value)) {
			return 'null';
		}
		return 'text';
	}

	private function _maskPaymentIdentifiers( $text ) {
		$text = preg_replace('/\b[A-Z]{2}\d{2}[A-Z0-9]{11,30}\b/i', '[IBAN]', $text);
		$text = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[SSN]', $text);
		return preg_replace_callback('/\b(?:\d[ -]?){13,19}\b/', array($this, '_maskCardCandidate'), $text);
	}

	private function _maskCardCandidate( $matches ) {
		$digits = preg_replace('/\D/', '', $matches[0]);
		return strlen($digits) >= 13 && strlen($digits) <= 19 ? '[CARD]' : $matches[0];
	}

	private function _maskPhoneNumbers( $text ) {
		if (!preg_match_all('/(?<!\w)(\+?\d[\d\s().\-]{7,}\d)(?!\w)/', $text, $matches)) {
			return $text;
		}
		foreach ($matches[1] as $candidate) {
			$digits = preg_replace('/\D/', '', $candidate);
			$trimmed = trim($candidate);
			$hyphenOnly = (bool) preg_match('/^\d+(?:-\d+)+$/', $trimmed);
			$commonHyphenPhone = (bool) preg_match('/^(?:\d{3}-\d{3}-\d{4}|\d-\d{3}-\d{3}-\d{4})$/', $trimmed);
			if (strlen($digits) >= 7 && strlen($digits) <= 15 && (!$hyphenOnly || $commonHyphenPhone)) {
				$text = str_replace($candidate, '[PHONE]', $text);
			}
		}
		return $text;
	}

	private function _maskGuestNames( $text ) {
		$text = preg_replace('/\b(my name is|i am|i\'m|im|this is)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\b/iu', '$1 [NAME]', $text);
		return preg_replace('/\b(меня зовут|я)\s+([А-ЯЁ][а-яё]+(?:\s+[А-ЯЁ][а-яё]+){0,2})\b/u', '$1 [NAME]', $text);
	}

	private function _getNameMasks( $context ) {
		$names = array();
		$userId = isset($context['user_id']) ? (int) $context['user_id'] : 0;
		if ($userId > 0) {
			$user = get_user_by('id', $userId);
			if ($user) {
				$names[] = $user->display_name;
				$names[] = $user->user_login;
				$names[] = trim($user->first_name . ' ' . $user->last_name);
			}
		}
		return array_unique(array_filter(array_map('trim', $names)));
	}
}
