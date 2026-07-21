<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps each enabled chatbot's local index current without blocking an admin
 * request. Full rebuilds are processed in small, generation-safe WP-Cron jobs.
 */
class WaicFastIndexer {
	const REBUILD_HOOK = 'waic_fast_rebuild_task';
	const BATCH_SIZE = 200;
	const LOCK_TTL = 300;

	public static function init() {
		add_action('save_post', array(__CLASS__, 'indexSavedPost'), 30, 3);
		add_action('before_delete_post', array(__CLASS__, 'deletePost'));
		add_action('added_post_meta', array(__CLASS__, 'indexMetaChanged'), 30, 4);
		add_action('updated_post_meta', array(__CLASS__, 'indexMetaChanged'), 30, 4);
		add_action('deleted_post_meta', array(__CLASS__, 'indexMetaChanged'), 30, 4);
		add_action('set_object_terms', array(__CLASS__, 'indexTermsChanged'), 30, 6);
		add_action(self::REBUILD_HOOK, array(__CLASS__, 'rebuildTask'));
		add_action('admin_post_waic_fast_rebuild', array(__CLASS__, 'handleRebuild'));
	}

	public static function getAvailablePostTypes() {
		$objects = get_post_types(array('public' => true), 'objects');
		$options = array();
		foreach ((array) $objects as $name => $object) {
			if ('attachment' === $name) {
				continue;
			}
			$options[$name] = !empty($object->labels->singular_name) ? $object->labels->singular_name : $name;
		}
		return $options;
	}

	public static function syncTask( $taskId, $config, $oldConfig = array() ) {
		$taskId = absint($taskId);
		$config = WaicFastPath::sanitizeConfig($config);
		$oldConfig = WaicFastPath::sanitizeConfig($oldConfig);
		if (empty($config['enabled'])) {
			self::deleteTask($taskId);
			return;
		}
		$changed = wp_json_encode($config) !== wp_json_encode($oldConfig);
		$status = WaicFastIndex::getStatus($taskId);
		$versionChanged = absint(isset($status['pack_version']) ? $status['pack_version'] : 0) !== self::packIndexVersion($config);
		if ($changed || $versionChanged || WaicFastIndex::getTaskCount($taskId) < 1) {
			self::queueRebuild($taskId, $changed);
		}
	}

	/**
	 * Do not serve an index whose deterministic pack facets were produced by an
	 * older classifier. The first request after an upgrade queues a normal
	 * generation-safe rebuild and falls back to AIWU until it is ready.
	 */
	public static function isTaskIndexReady( $taskId, $config ) {
		$taskId = absint($taskId);
		if (!$taskId || !WaicFastIndex::schemaReady() || WaicFastIndex::getTaskCount($taskId) < 1) {
			return false;
		}
		$status = WaicFastIndex::getStatus($taskId);
		$state = sanitize_key(isset($status['state']) ? $status['state'] : '');
		if ('ready' === $state && absint(isset($status['pack_version']) ? $status['pack_version'] : 0) === self::packIndexVersion($config)) {
			return true;
		}
		if (!in_array($state, array('queued', 'running'), true)) {
			self::queueRebuild($taskId, true);
		}
		return false;
	}

	public static function queueRebuild( $taskId, $restart = false ) {
		$taskId = absint($taskId);
		$args = array($taskId);
		if (!$taskId) {
			return false;
		}
		if ($restart) {
			wp_clear_scheduled_hook(self::REBUILD_HOOK, $args);
		} elseif (wp_next_scheduled(self::REBUILD_HOOK, $args)) {
			return false;
		}
		$statusKey = 'waic_fast_index_status_' . $taskId;
		update_option($statusKey, array('state' => 'queued', 'queued_at' => current_time('mysql', true)), false);
		$scheduled = self::scheduleBatch($taskId);
		if (!$scheduled) {
			update_option($statusKey, array('state' => 'failed', 'failed' => 1, 'finished_at' => current_time('mysql', true)), false);
		}
		return $scheduled;
	}

	public static function rebuildTask( $taskId ) {
		$taskId = absint($taskId);
		$config = self::getTaskConfig($taskId);
		if (!$taskId || empty($config['enabled'])) {
			return false;
		}
		$packVersion = self::packIndexVersion($config);
		$lockKey = 'waic_fast_rebuild_' . $taskId;
		if (get_transient($lockKey)) {
			self::scheduleBatch($taskId, 30);
			return false;
		}
		set_transient($lockKey, 1, self::LOCK_TTL);
		$statusKey = 'waic_fast_index_status_' . $taskId;
		try {
			$status = get_option($statusKey, array());
			if (!is_array($status) || 'running' !== WaicUtils::getArrayValue($status, 'state') || empty($status['generation']) || absint(isset($status['pack_version']) ? $status['pack_version'] : 0) !== $packVersion) {
				$status = array(
					'state' => 'running',
					'started_at' => current_time('mysql', true),
					'generation' => substr(wp_hash($taskId . '|' . microtime(true) . '|' . wp_rand()), 0, 20),
					'pack_version' => $packVersion,
					'last_id' => 0,
					'indexed' => 0,
					'failed' => 0,
				);
				update_option($statusKey, $status, false);
			}
			$generation = sanitize_key($status['generation']);
			$lastId = absint(isset($status['last_id']) ? $status['last_id'] : 0);
			$indexed = absint(isset($status['indexed']) ? $status['indexed'] : 0);
			$failed = absint(isset($status['failed']) ? $status['failed'] : 0);
			$ids = self::getBatchPostIds($config, $lastId);
			if (false === $ids) {
				update_option($statusKey, array(
					'state' => 'failed',
					'indexed' => $indexed,
					'failed' => $failed + 1,
					'finished_at' => current_time('mysql', true),
					'generation' => $generation,
					'pack_version' => $packVersion,
				), false);
				return false;
			}
			self::primePostCaches($ids, $config);
			foreach ($ids as $postId) {
				$result = self::indexPost($postId, $taskId, $config, $generation);
				if (true === $result) {
					$indexed++;
				} elseif (false === $result) {
					$failed++;
				}
			}
			if (!empty($ids)) {
				$lastId = (int) end($ids);
			}

			$current = get_option($statusKey, array());
			if (!is_array($current) || 'running' !== WaicUtils::getArrayValue($current, 'state') || $generation !== WaicUtils::getArrayValue($current, 'generation') || $packVersion !== absint(isset($current['pack_version']) ? $current['pack_version'] : 0)) {
				self::deleteGeneration($taskId, $generation);
				return false;
			}
			if ($failed > 0) {
				update_option($statusKey, array(
					'state' => 'failed',
					'indexed' => $indexed,
					'failed' => $failed,
					'last_id' => $lastId,
					'finished_at' => current_time('mysql', true),
					'generation' => $generation,
					'pack_version' => $packVersion,
				), false);
				return false;
			}
			if (count($ids) >= self::BATCH_SIZE) {
				$status['last_id'] = $lastId;
				$status['indexed'] = $indexed;
				$status['failed'] = 0;
				update_option($statusKey, $status, false);
				if (!self::scheduleBatch($taskId)) {
					$status['state'] = 'failed';
					$status['failed'] = 1;
					$status['finished_at'] = current_time('mysql', true);
					update_option($statusKey, $status, false);
					return false;
				}
				return $indexed;
			}

			global $wpdb;
			// Delete the previous generation only after the replacement generation completed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$cleaned = $wpdb->query($wpdb->prepare(
				'DELETE FROM ' . WaicFastIndex::tableName() . ' WHERE task_id=%d AND generation<>%s',
				$taskId,
				$generation
			));
			if (false === $cleaned) {
				update_option($statusKey, array(
					'state' => 'failed',
					'indexed' => $indexed,
					'failed' => 1,
					'finished_at' => current_time('mysql', true),
					'generation' => $generation,
					'pack_version' => $packVersion,
				), false);
				return false;
			}
			update_option($statusKey, array(
				'state' => 'ready',
				'indexed' => $indexed,
				'failed' => 0,
				'finished_at' => current_time('mysql', true),
				'generation' => $generation,
				'pack_version' => $packVersion,
			), false);
			return $indexed;
		} finally {
			delete_transient($lockKey);
		}
	}

	private static function getBatchPostIds( $config, $lastId ) {
		global $wpdb;
		$postTypes = array_values(array_filter(array_map('sanitize_key', (array) $config['post_types'])));
		if (empty($postTypes)) {
			return array();
		}
		$args = array(absint($lastId), 'publish', '');
		$args = array_merge($args, $postTypes);
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE ID>%d AND post_status=%s AND post_password=%s AND post_type IN ("
			. implode(',', array_fill(0, count($postTypes), '%s')) . ') ORDER BY ID ASC LIMIT ' . self::BATCH_SIZE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col($wpdb->prepare($sql, $args));
		return $wpdb->last_error !== '' ? false : array_map('absint', (array) $ids);
	}

	private static function primePostCaches( $ids, $config ) {
		if (empty($ids)) {
			return;
		}
		get_posts(array(
			'post__in' => array_map('absint', $ids),
			'post_type' => (array) $config['post_types'],
			'post_status' => 'publish',
			'has_password' => false,
			'posts_per_page' => count($ids),
			'orderby' => 'post__in',
			'ignore_sticky_posts' => true,
			'no_found_rows' => true,
			'suppress_filters' => true,
		));
	}

	private static function scheduleBatch( $taskId, $delay = 1 ) {
		$args = array(absint($taskId));
		if (wp_next_scheduled(self::REBUILD_HOOK, $args)) {
			return true;
		}
		return (bool) wp_schedule_single_event(time() + max(1, absint($delay)), self::REBUILD_HOOK, $args);
	}

	private static function deleteGeneration( $taskId, $generation ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete(
			WaicFastIndex::tableName(),
			array('task_id' => absint($taskId), 'generation' => sanitize_key($generation)),
			array('%d', '%s')
		);
	}

	public static function indexSavedPost( $postId, $post, $update ) {
		unset($update);
		if (!$post instanceof WP_Post || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
			return;
		}
		foreach (self::getEnabledTasks() as $taskId => $config) {
			self::syncPostForTask($postId, $taskId, $config, $post);
		}
	}

	/**
	 * Keep allowlisted recipe fields current when a plugin changes post meta
	 * directly, without rebuilding every enabled chatbot index.
	 */
	public static function indexMetaChanged( $metaId, $postId, $metaKey, $metaValue ) {
		unset($metaId, $metaValue);
		$metaKey = (string) $metaKey;
		$postId = absint($postId);
		if (!$postId || $metaKey === '' || is_protected_meta($metaKey, 'post') || !WaicFastIndex::schemaReady()) {
			return;
		}
		$post = get_post($postId);
		if (!$post instanceof WP_Post || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
			return;
		}
		foreach (self::getEnabledTasks() as $taskId => $config) {
			if (self::allowedMetaKey($metaKey, $config['meta_fields'])) {
				self::syncPostForTask($postId, $taskId, $config, $post);
			}
		}
	}

	/**
	 * Keep allowlisted taxonomy text and Savory facets current after direct term
	 * assignment. Non-post objects and unrelated taxonomies are ignored.
	 */
	public static function indexTermsChanged( $objectId, $terms, $ttIds, $taxonomy, $append, $oldTtIds ) {
		unset($terms, $ttIds, $append, $oldTtIds);
		$objectId = absint($objectId);
		$taxonomy = sanitize_key($taxonomy);
		if (!$objectId || $taxonomy === '' || !WaicFastIndex::schemaReady()) {
			return;
		}
		$post = get_post($objectId);
		if (!$post instanceof WP_Post || wp_is_post_revision($objectId) || wp_is_post_autosave($objectId)) {
			return;
		}
		if (!is_object_in_taxonomy($post->post_type, $taxonomy)) {
			return;
		}
		clean_object_term_cache($objectId, $post->post_type);
		foreach (self::getEnabledTasks() as $taskId => $config) {
			if (in_array($taxonomy, (array) $config['taxonomies'], true)) {
				self::syncPostForTask($objectId, $taskId, $config, $post);
			}
		}
	}

	private static function syncPostForTask( $postId, $taskId, $config, $post = null ) {
		if (!WaicFastIndex::schemaReady()) {
			return;
		}
		$postId = absint($postId);
		$post = $post instanceof WP_Post ? $post : get_post($postId);
		if (!$post instanceof WP_Post) {
			return;
		}
		if (!in_array($post->post_type, (array) $config['post_types'], true)) {
			self::deletePostForTask($postId, $taskId);
			return;
		}
		$type = get_post_type_object($post->post_type);
		if ('publish' !== $post->post_status || $post->post_password !== '' || !$type || empty($type->public)) {
			self::deletePostForTask($postId, $taskId);
			return;
		}
		$status = WaicFastIndex::getStatus($taskId);
		$generation = !empty($status['generation']) ? sanitize_key($status['generation']) : 'live';
		self::indexPost($postId, $taskId, $config, $generation);
	}

	public static function indexPost( $postId, $taskId, $config, $generation ) {
		$post = get_post(absint($postId));
		if (!$post || 'publish' !== $post->post_status || $post->post_password !== '' || !in_array($post->post_type, (array) $config['post_types'], true)) {
			return null;
		}
		$type = get_post_type_object($post->post_type);
		if (!$type || empty($type->public)) {
			return null;
		}
		$pack = WaicFastPath::getDomainPack($config['domain_pack']);
		if (!$pack) {
			return false;
		}
		$document = self::buildDocument($post, $config);
		$facets = $pack->buildFacets($post, $document, $config);
		$facetsJson = wp_json_encode($facets);
		if (!is_string($facetsJson)) {
			$facetsJson = '{}';
		}
		global $wpdb;
		$data = array(
			'task_id' => absint($taskId),
			'post_id' => absint($post->ID),
			'post_type' => sanitize_key($post->post_type),
			'title' => self::limitText($document['title'], 512),
			'excerpt' => self::limitText($document['excerpt'], 2000),
			'terms_text' => self::limitText($document['terms_text'], 8000),
			'meta_text' => self::limitText($document['meta_text'], 16000),
			'search_text' => self::limitText($document['search_text'], 32000),
			'facets_json' => $facetsJson,
			'generation' => substr(sanitize_key($generation), 0, 32),
			'post_modified_gmt' => $post->post_modified_gmt ? $post->post_modified_gmt : null,
			'indexed_at' => current_time('mysql', true),
		);
		$formats = array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $wpdb->replace(WaicFastIndex::tableName(), $data, $formats);
	}

	private static function buildDocument( $post, $config ) {
		$termMap = array();
		$termParts = array();
		foreach ((array) $config['taxonomies'] as $taxonomy) {
			$taxonomy = sanitize_key($taxonomy);
			$object = get_taxonomy($taxonomy);
			if (!$object || empty($object->public) || !is_object_in_taxonomy($post->post_type, $taxonomy)) {
				continue;
			}
			$terms = get_the_terms($post->ID, $taxonomy);
			if (!is_array($terms)) {
				continue;
			}
			$termMap[$taxonomy] = array();
			foreach ($terms as $term) {
				$label = self::limitText($term->name . ' ' . $term->slug, 300);
				$termMap[$taxonomy][] = $label;
				$termParts[] = $taxonomy . ' ' . $label;
			}
		}

		$allMeta = get_post_meta($post->ID);
		$metaMap = array();
		$metaParts = array();
		foreach ((array) $allMeta as $key => $values) {
			if (is_protected_meta($key, 'post') || !self::allowedMetaKey($key, $config['meta_fields'])) {
				continue;
			}
			foreach ((array) $values as $value) {
				$value = maybe_unserialize($value);
				if (!is_scalar($value)) {
					continue;
				}
				$value = self::limitText(wp_strip_all_tags((string) $value), 4000);
				if ($value === '') {
					continue;
				}
				if (!isset($metaMap[$key])) {
					$metaMap[$key] = array();
				}
				$metaMap[$key][] = $value;
				$metaParts[] = $key . ' ' . $value;
			}
		}

		$excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 45, '');
		$content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 250, '');
		$title = WaicFastIndex::normalizeText($post->post_title);
		$excerpt = WaicFastIndex::normalizeText($excerpt);
		$content = WaicFastIndex::normalizeText($content);
		$termsText = WaicFastIndex::normalizeText(implode(' ', $termParts));
		$metaText = WaicFastIndex::normalizeText(implode(' ', $metaParts));
		return array(
			'title' => $title,
			'excerpt' => $excerpt,
			'terms_text' => $termsText,
			'meta_text' => $metaText,
			'search_text' => WaicFastIndex::normalizeText($title . ' ' . $excerpt . ' ' . $content . ' ' . $termsText . ' ' . $metaText),
			'term_map' => $termMap,
			'meta_map' => $metaMap,
		);
	}

	private static function allowedMetaKey( $key, $patterns ) {
		foreach ((array) $patterns as $pattern) {
			$pattern = str_replace('%', '*', (string) $pattern);
			$regex = '/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/i';
			if (preg_match($regex, $key)) {
				return true;
			}
		}
		return false;
	}

	private static function limitText( $text, $limit ) {
		$text = (string) $text;
		return function_exists('mb_substr') ? mb_substr($text, 0, $limit, 'UTF-8') : substr($text, 0, $limit);
	}

	public static function deletePost( $postId ) {
		if (!WaicFastIndex::schemaReady()) {
			return;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete(WaicFastIndex::tableName(), array('post_id' => absint($postId)), array('%d'));
	}

	private static function deletePostForTask( $postId, $taskId ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete(WaicFastIndex::tableName(), array('post_id' => absint($postId), 'task_id' => absint($taskId)), array('%d', '%d'));
	}

	public static function deleteTask( $taskId ) {
		wp_clear_scheduled_hook(self::REBUILD_HOOK, array(absint($taskId)));
		delete_transient('waic_fast_rebuild_' . absint($taskId));
		if (WaicFastIndex::schemaReady()) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete(WaicFastIndex::tableName(), array('task_id' => absint($taskId)), array('%d'));
		}
		delete_option('waic_fast_index_status_' . absint($taskId));
	}

	private static function packIndexVersion( $config ) {
		$code = sanitize_key(isset($config['domain_pack']) ? $config['domain_pack'] : '');
		$pack = $code ? WaicFastPath::getDomainPack($code) : false;
		if ($pack && method_exists($pack, 'getIndexVersion')) {
			return max(1, min(999, absint($pack->getIndexVersion())));
		}
		return 1;
	}

	public static function getTaskConfig( $taskId ) {
		global $wpdb;
		$table = $wpdb->prefix . WAIC_DB_PREF . 'tasks';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$json = $wpdb->get_var($wpdb->prepare("SELECT params FROM {$table} WHERE id=%d AND feature=%s LIMIT 1", absint($taskId), 'chatbots'));
		$params = $json ? WaicUtils::jsonDecode($json) : array();
		return WaicFastPath::getConfig(is_array($params) ? $params : array());
	}

	private static function getEnabledTasks() {
		global $wpdb;
		$table = $wpdb->prefix . WAIC_DB_PREF . 'tasks';
		// This method is called from mutation hooks. Do not retain a static cache:
		// long-running workers and PHPUnit share a PHP process, and a task can be
		// created, changed or removed before a later content mutation is handled.
		$tasks = array();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results($wpdb->prepare("SELECT id, params FROM {$table} WHERE feature=%s", 'chatbots'), ARRAY_A);
		foreach ((array) $rows as $row) {
			$params = WaicUtils::jsonDecode($row['params']);
			$config = WaicFastPath::getConfig(is_array($params) ? $params : array());
			if (!empty($config['enabled'])) {
				$tasks[absint($row['id'])] = $config;
			}
		}
		return $tasks;
	}

	public static function handleRebuild() {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Forbidden.', 'ai-copilot-content-generator'), '', array('response' => 403));
		}
		$taskId = isset($_POST['task_id']) ? absint(wp_unslash($_POST['task_id'])) : 0;
		check_admin_referer('waic_fast_rebuild_' . $taskId);
		$config = self::getTaskConfig($taskId);
		if (!$taskId || empty($config['enabled'])) {
			wp_die(esc_html__('Fast path is not enabled for this chatbot.', 'ai-copilot-content-generator'), '', array('response' => 400));
		}
		self::queueRebuild($taskId, true);
		$url = wp_get_referer();
		$url = $url ? add_query_arg('fast_rebuild', 'queued', $url) : admin_url();
		wp_safe_redirect($url);
		exit;
	}
}
