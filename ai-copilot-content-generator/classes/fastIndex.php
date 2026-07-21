<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Task-scoped, local content index used by the chatbot fast path.
 */
class WaicFastIndex {
	const SCHEMA_VERSION = 1;
	const CANDIDATE_LIMIT = 250;
	private static $schemaReady = array();

	public static function tableName() {
		global $wpdb;
		return $wpdb->prefix . WAIC_DB_PREF . 'fast_index';
	}

	public static function installSchema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::tableName();
		$charset = $wpdb->get_charset_collate();
		dbDelta("CREATE TABLE {$table} (
			task_id BIGINT(20) UNSIGNED NOT NULL,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			post_type VARCHAR(64) NOT NULL,
			title TEXT NOT NULL,
			excerpt MEDIUMTEXT NOT NULL,
			terms_text MEDIUMTEXT NOT NULL,
			meta_text MEDIUMTEXT NOT NULL,
			search_text LONGTEXT NOT NULL,
			facets_json LONGTEXT NULL,
			generation VARCHAR(32) NOT NULL DEFAULT '',
			post_modified_gmt DATETIME NULL,
			indexed_at DATETIME NOT NULL,
			PRIMARY KEY (task_id, post_id),
			KEY task_type (task_id, post_type),
			KEY task_generation (task_id, generation),
			KEY post_modified_gmt (post_modified_gmt)
		) {$charset};");
		update_option('waic_fast_index_schema_version', self::SCHEMA_VERSION, false);
		unset(self::$schemaReady[$table]);
	}

	public static function schemaReady() {
		global $wpdb;
		$table = self::tableName();
		if (array_key_exists($table, self::$schemaReady)) {
			return self::$schemaReady[$table];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::$schemaReady[$table] = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);
		return self::$schemaReady[$table];
	}

	public static function getTaskCount( $taskId ) {
		if (!self::schemaReady()) {
			return 0;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::tableName() . ' WHERE task_id=%d', absint($taskId)));
	}

	public static function getStatus( $taskId ) {
		$status = get_option('waic_fast_index_status_' . absint($taskId), array());
		$status = is_array($status) ? $status : array();
		$status['count'] = self::getTaskCount($taskId);
		return $status;
	}

	public static function search( $taskId, $config, $plan, $pack ) {
		global $wpdb;
		$postTypes = isset($plan['post_types']) ? (array) $plan['post_types'] : (array) $config['post_types'];
		$postTypes = array_values(array_filter(array_map('sanitize_key', $postTypes)));
		if (empty($postTypes)) {
			return array('rows' => array(), 'confidence' => 0.0);
		}
		$args = array(absint($taskId));
		$where = array('task_id=%d');
		$where[] = 'post_type IN (' . implode(',', array_fill(0, count($postTypes), '%s')) . ')';
		$args = array_merge($args, $postTypes);

		$candidateGroups = self::candidateGroups($plan);
		$candidateTerms = array();
		$requiredHits = min(count($candidateGroups), absint(isset($plan['required_group_hits']) ? $plan['required_group_hits'] : 0));
		if ($requiredHits > 0) {
			$groupSql = array();
			foreach ($candidateGroups as $group) {
				$likes = array();
				foreach ($group as $term) {
					$likes[] = 'search_text LIKE %s';
					$args[] = '%' . $wpdb->esc_like($term) . '%';
				}
				$groupSql[] = '(' . implode(' OR ', $likes) . ')';
			}
			$where[] = '((' . implode(') + (', $groupSql) . ')) >= %d';
			$args[] = $requiredHits;
		} else {
			$candidateTerms = self::candidateTerms($plan);
		}
		if (empty($candidateGroups) && !empty($candidateTerms)) {
			$likes = array();
			foreach ($candidateTerms as $term) {
				$likes[] = 'search_text LIKE %s';
				$args[] = '%' . $wpdb->esc_like($term) . '%';
			}
			$where[] = '(' . implode(' OR ', $likes) . ')';
		}
		$sql = 'SELECT task_id, post_id, post_type, title, excerpt, terms_text, meta_text, search_text, facets_json'
			. ' FROM ' . self::tableName()
			. ' WHERE ' . implode(' AND ', $where)
			. ' ORDER BY post_id DESC LIMIT ' . self::CANDIDATE_LIMIT;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A);
		if (empty($rows)) {
			return array('rows' => array(), 'confidence' => 0.0);
		}

		$ranked = array();
		$groupCount = min(WaicFastPath::MAX_QUERY_GROUPS, count(isset($plan['groups']) ? (array) $plan['groups'] : array()));
		foreach ($rows as $row) {
			$row['facets'] = json_decode(isset($row['facets_json']) ? $row['facets_json'] : '', true);
			$row['facets'] = is_array($row['facets']) ? $row['facets'] : array();
			if (!$pack->filterRow($row, $plan)) {
				continue;
			}
			$base = self::baseScore($row, $plan);
			$score = $base['score'] + (float) $pack->scoreRow($row, $plan);
			$requiredHits = isset($plan['required_group_hits']) ? absint($plan['required_group_hits']) : 0;
			if ($requiredHits && $base['hits'] < $requiredHits) {
				continue;
			}
			if ($score <= 0) {
				continue;
			}
			$row['_score'] = $score;
			$row['_hit_groups'] = $base['hits'];
			$ranked[] = $row;
		}
		usort($ranked, array(__CLASS__, 'compareRows'));
		if (empty($ranked)) {
			return array('rows' => array(), 'confidence' => 0.0);
		}

		$top = $ranked[0];
		$coverage = $groupCount > 0 ? min(1, ((float) $top['_hit_groups']) / $groupCount) : 1.0;
		$scoreTarget = max(12, $groupCount * 12);
		$strength = min(1, ((float) $top['_score']) / $scoreTarget);
		$confidence = round(($coverage * 0.65) + ($strength * 0.35), 4);
		$take = max(3, min(24, absint($config['max_cards']) * 3));
		return array(
			'rows' => array_slice($ranked, 0, $take),
			'confidence' => $confidence,
		);
	}

	private static function candidateTerms( $plan ) {
		$terms = isset($plan['candidate_terms']) ? (array) $plan['candidate_terms'] : array();
		if (empty($terms) && !empty($plan['groups'])) {
			foreach ((array) $plan['groups'] as $group) {
				foreach (array_slice((array) $group, 0, 2) as $term) {
					$terms[] = $term;
				}
			}
		}
		$out = array();
		foreach ($terms as $term) {
			$term = self::normalizeText($term);
			if ($term !== '' && strlen($term) > 1) {
				$out[$term] = $term;
			}
			if (count($out) >= WaicFastPath::MAX_QUERY_TERMS) {
				break;
			}
		}
		return array_values($out);
	}

	private static function candidateGroups( $plan ) {
		$groups = array();
		$termCount = 0;
		foreach (array_slice(isset($plan['groups']) ? (array) $plan['groups'] : array(), 0, WaicFastPath::MAX_QUERY_GROUPS) as $group) {
			$terms = array();
			foreach ((array) $group as $term) {
				$term = self::normalizeText($term);
				if ($term !== '' && strlen($term) > 1) {
					$terms[$term] = $term;
					$termCount++;
				}
				if ($termCount >= WaicFastPath::MAX_QUERY_TERMS) {
					break;
				}
			}
			if (!empty($terms)) {
				$groups[] = array_values($terms);
			}
			if ($termCount >= WaicFastPath::MAX_QUERY_TERMS) {
				break;
			}
		}
		return $groups;
	}

	private static function baseScore( $row, $plan ) {
		$groups = array_slice(isset($plan['groups']) ? (array) $plan['groups'] : array(), 0, WaicFastPath::MAX_QUERY_GROUPS);
		$texts = array(
			'title' => self::normalizeText(isset($row['title']) ? $row['title'] : ''),
			'terms' => self::normalizeText(isset($row['terms_text']) ? $row['terms_text'] : ''),
			'meta' => self::normalizeText(isset($row['meta_text']) ? $row['meta_text'] : ''),
			'excerpt' => self::normalizeText(isset($row['excerpt']) ? $row['excerpt'] : ''),
			'search' => self::normalizeText(isset($row['search_text']) ? $row['search_text'] : ''),
		);
		$weights = array('title' => 8, 'terms' => 5, 'meta' => 4, 'excerpt' => 2, 'search' => 1);
		$score = 0;
		$hits = 0;
		foreach ($groups as $group) {
			$best = 0;
			foreach (array_slice((array) $group, 0, 10) as $term) {
				$term = self::normalizeText($term);
				if ($term === '') {
					continue;
				}
				foreach ($texts as $field => $text) {
					if (self::containsTerm($text, $term)) {
						$best = max($best, $weights[$field]);
					}
				}
			}
			if ($best > 0) {
				$score += $best + 4;
				$hits++;
			}
		}
		if ($hits > 1) {
			$score += ($hits - 1) * 5;
		}
		return array('score' => $score, 'hits' => $hits);
	}

	public static function normalizeText( $text ) {
		$text = html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
		$text = remove_accents($text);
		$text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
		$text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
		return trim(preg_replace('/\s+/u', ' ', (string) $text));
	}

	public static function containsTerm( $text, $term ) {
		$text = self::normalizeText($text);
		$term = self::normalizeText($term);
		return $term !== '' && strpos(' ' . $text . ' ', ' ' . $term . ' ') !== false;
	}

	public static function compareRows( $left, $right ) {
		$score = (float) $right['_score'] - (float) $left['_score'];
		if (0.0 !== $score) {
			return $score > 0 ? 1 : -1;
		}
		return (int) $right['post_id'] - (int) $left['post_id'];
	}

	public static function isPublicPost( $postId ) {
		$post = get_post(absint($postId));
		if (!$post || 'publish' !== $post->post_status || $post->post_password !== '') {
			return false;
		}
		$type = get_post_type_object($post->post_type);
		if (!$type || empty($type->public)) {
			return false;
		}
		return !function_exists('is_post_publicly_viewable') || is_post_publicly_viewable($post);
	}
}
