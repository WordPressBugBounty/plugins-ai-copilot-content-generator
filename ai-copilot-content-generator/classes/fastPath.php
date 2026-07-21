<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A domain pack supplies deterministic parsing, safety filters, ranking and copy
 * for one local-search domain. Core routing and persistence stay in AIWU.
 */
interface WaicFastDomainPackInterface {
	public function getCode();
	public function getLabel();
	public function getDefaults();
	public function parse( $message, $context, $config );
	public function buildFacets( $post, $document, $config );
	public function filterRow( $row, $plan );
	public function scoreRow( $row, $plan );
	public function renderAnswer( $plan, $count );
	public function renderClarification( $plan );
	public function renderNoResults( $plan );
}

class WaicFastPath {
	const MODEL = 'aiwu-local-index-v1';
	const MAX_QUERY_GROUPS = 8;
	const MAX_QUERY_TERMS = 24;

	public static function init() {
		WaicFastIndexer::init();
	}

	public static function getDefaults() {
		return array(
			'enabled' => 0,
			'domain_pack' => 'savory',
			'post_types' => array(),
			'taxonomies' => array(),
			'meta_fields' => array(),
			'max_cards' => 5,
			'confidence_threshold' => 0.62,
			'fallback_on_low_confidence' => 1,
			'requests_per_minute' => 30,
			'card_taxonomy' => '',
		);
	}

	public static function sanitizeConfig( $raw ) {
		$raw = is_array($raw) ? $raw : array();
		$config = self::getDefaults();
		$config['enabled'] = empty($raw['enabled']) ? 0 : 1;
		$config['domain_pack'] = sanitize_key(isset($raw['domain_pack']) ? $raw['domain_pack'] : $config['domain_pack']);
		$config['post_types'] = self::sanitizeKeys(isset($raw['post_types']) ? $raw['post_types'] : $config['post_types'], 8);
		$config['taxonomies'] = self::sanitizeKeys(isset($raw['taxonomies']) ? $raw['taxonomies'] : $config['taxonomies'], 20);
		$config['meta_fields'] = self::sanitizeMetaPatterns(isset($raw['meta_fields']) ? $raw['meta_fields'] : $config['meta_fields']);
		$config['max_cards'] = max(1, min(8, absint(isset($raw['max_cards']) ? $raw['max_cards'] : $config['max_cards'])));
		$threshold = isset($raw['confidence_threshold']) ? (float) $raw['confidence_threshold'] : $config['confidence_threshold'];
		$config['confidence_threshold'] = max(0.30, min(0.95, $threshold));
		$config['fallback_on_low_confidence'] = !isset($raw['fallback_on_low_confidence']) || !empty($raw['fallback_on_low_confidence']) ? 1 : 0;
		$config['requests_per_minute'] = max(5, min(120, absint(isset($raw['requests_per_minute']) ? $raw['requests_per_minute'] : $config['requests_per_minute'])));
		$config['card_taxonomy'] = sanitize_key(isset($raw['card_taxonomy']) ? $raw['card_taxonomy'] : $config['card_taxonomy']);

		$pack = self::getDomainPack($config['domain_pack']);
		if ($pack) {
			$config = self::mergePackDefaults($config, $pack->getDefaults());
		} else {
			$config['enabled'] = 0;
		}
		return $config;
	}

	private static function mergePackDefaults( $config, $defaults ) {
		foreach ((array) $defaults as $key => $value) {
			if (!isset($config[$key]) || $config[$key] === '' || $config[$key] === array()) {
				$config[$key] = $value;
			}
		}
		return $config;
	}

	public static function getConfig( $params ) {
		$raw = WaicUtils::getArrayValue($params, 'fast_path', array(), 2);
		return self::sanitizeConfig($raw);
	}

	public static function getDomainPackOptions() {
		$options = array();
		foreach (self::getDomainPacks() as $code => $className) {
			if (class_exists($className)) {
				$pack = new $className();
				$options[$code] = $pack->getLabel();
			}
		}
		return $options;
	}

	public static function getDomainPack( $code ) {
		$packs = self::getDomainPacks();
		$className = isset($packs[$code]) ? $packs[$code] : '';
		if (!$className || !class_exists($className)) {
			return false;
		}
		$pack = new $className();
		return $pack instanceof WaicFastDomainPackInterface ? $pack : false;
	}

	private static function getDomainPacks() {
		$packs = array('savory' => 'WaicFastSavoryDomainPack');
		return (array) apply_filters('waic_fast_path_domain_packs', $packs);
	}

	public static function maybeHandle( $message, $taskId, $params, $context = array() ) {
		$config = self::getConfig($params);
		if (empty($config['enabled'])) {
			return false;
		}
		$pack = self::getDomainPack($config['domain_pack']);
		if (!$pack) {
			return false;
		}

		$plan = $pack->parse((string) $message, $context, $config);
		if (!is_array($plan) || empty($plan['mode']) || 'decline' === $plan['mode']) {
			return false;
		}
		if (!self::allowRequest($taskId, $config, $context)) {
			return self::result(
				'<p>' . esc_html__('Too many requests. Please wait a moment and try again.', 'ai-copilot-content-generator') . '</p>',
				array(),
				'rate_limited',
				1.0
			);
		}
		if ('clarify' === $plan['mode']) {
			return self::result($pack->renderClarification($plan), array(), 'clarify', 1.0);
		}
		if ('search' !== $plan['mode']) {
			return false;
		}
		if (!WaicFastIndexer::isTaskIndexReady($taskId, $config)) {
			return (!empty($plan['hard_absent']) || !empty($plan['literal_excludes']))
				? self::result($pack->renderNoResults($plan), array(), 'no_results', 0.0)
				: false;
		}

		$search = WaicFastIndex::search($taskId, $config, $plan, $pack);
		$rows = isset($search['rows']) ? $search['rows'] : array();
		$confidence = isset($search['confidence']) ? (float) $search['confidence'] : 0.0;
		if (empty($rows)) {
			if (self::shouldReturnLocalNoResults($plan, $config)) {
				return self::result($pack->renderNoResults($plan), array(), 'no_results', $confidence);
			}
			return false;
		}
		if ($confidence < (float) $config['confidence_threshold']) {
			if (!empty($plan['hard_absent']) || !empty($plan['literal_excludes'])) {
				return self::result($pack->renderNoResults($plan), array(), 'no_results', $confidence);
			}
			if (!empty($config['fallback_on_low_confidence'])) {
				return false;
			}
		}

		$ids = array();
		foreach ($rows as $row) {
			$id = absint(isset($row['post_id']) ? $row['post_id'] : 0);
			if ($id && WaicFastIndex::isPublicPost($id)) {
				$ids[] = $id;
			}
			if (count($ids) >= $config['max_cards']) {
				break;
			}
		}
		if (empty($ids)) {
			return self::shouldReturnLocalNoResults($plan, $config)
				? self::result($pack->renderNoResults($plan), array(), 'no_results', 0.0)
				: false;
		}
		$answer = $pack->renderAnswer($plan, count($ids));
		$answer .= '##IDS##post:' . implode(',', array_map('absint', $ids));
		return self::result($answer, $ids, 'search', $confidence);
	}

	/**
	 * A local no-results response is authoritative only when fallback is disabled
	 * or a strict exclusion has ruled every indexed candidate out. Other empty
	 * searches should continue through the normal AIWU provider flow.
	 */
	private static function shouldReturnLocalNoResults( $plan, $config ) {
		return empty($config['fallback_on_low_confidence'])
			|| !empty($plan['hard_absent'])
			|| !empty($plan['literal_excludes']);
	}

	private static function result( $answer, $ids, $route, $confidence ) {
		return array(
			'handled' => true,
			'answer' => wp_kses_post($answer),
			'post_ids' => array_values(array_map('absint', (array) $ids)),
			'route' => sanitize_key($route),
			'confidence' => max(0, min(1, (float) $confidence)),
			'engine' => 'local',
			'model' => self::MODEL,
			'tokens' => 0,
		);
	}

	public static function cardOptions( $tools, $config ) {
		$tools = is_array($tools) ? $tools : array();
		$config = is_array($config) ? $config : self::getDefaults();
		$tools['post_card_layout'] = isset($tools['post_card_layout']) ? $tools['post_card_layout'] : 'v';
		foreach (array('post_card_image', 'post_card_cat', 'post_card_name', 'post_card_desc') as $key) {
			if (!isset($tools[$key])) {
				$tools[$key] = 1;
			}
		}
		$tools['post_card_taxonomy'] = sanitize_key(isset($config['card_taxonomy']) ? $config['card_taxonomy'] : 'category');
		$tools['post_card_target'] = '_self';
		return $tools;
	}

	public static function sanitizeConversationId( $value ) {
		if (!is_scalar($value)) {
			return '';
		}
		$value = strtolower(trim((string) $value));
		return preg_match('/\A[a-z0-9-]{16,48}\z/', $value) ? $value : '';
	}

	public static function requestIdentity( $userId = 0 ) {
		if ($userId) {
			return 'user:' . absint($userId);
		}
		$remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		$remote = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : 'unknown';
		$identity = apply_filters('waic_fast_path_rate_identity', 'ip:' . $remote, $remote);
		return substr((string) $identity, 0, 128);
	}

	private static function allowRequest( $taskId, $config, $context ) {
		$limit = absint(isset($config['requests_per_minute']) ? $config['requests_per_minute'] : 30);
		if ($limit < 1) {
			return true;
		}
		$identity = isset($context['identity']) ? (string) $context['identity'] : '';
		$key = 'waic_fast_rate_' . substr(hash('sha256', absint($taskId) . '|' . $identity), 0, 32);
		$state = get_transient($key);
		$count = is_array($state) && isset($state['count']) ? absint($state['count']) : 0;
		if ($count >= $limit) {
			return false;
		}
		set_transient($key, array('count' => $count + 1), MINUTE_IN_SECONDS);
		return true;
	}

	private static function sanitizeKeys( $values, $limit ) {
		if (!is_array($values)) {
			$values = preg_split('/[\s,]+/', (string) $values, -1, PREG_SPLIT_NO_EMPTY);
		}
		$out = array();
		foreach ((array) $values as $value) {
			$value = sanitize_key($value);
			if ($value !== '') {
				$out[$value] = $value;
			}
			if (count($out) >= $limit) {
				break;
			}
		}
		return array_values($out);
	}

	private static function sanitizeMetaPatterns( $values ) {
		if (!is_array($values)) {
			$values = preg_split('/[\r\n,]+/', (string) $values, -1, PREG_SPLIT_NO_EMPTY);
		}
		$out = array();
		foreach ((array) $values as $value) {
			$value = trim((string) $value);
			$value = str_replace('%', '*', $value);
			$value = preg_replace('/[^A-Za-z0-9_\-*]/', '', $value);
			if ($value === '' || strpos($value, '_') === 0 || strlen(preg_replace('/[^A-Za-z0-9]/', '', $value)) < 3) {
				continue;
			}
			$out[$value] = $value;
			if (count($out) >= 20) {
				break;
			}
		}
		return array_values($out);
	}
}
