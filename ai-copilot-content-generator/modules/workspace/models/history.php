<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicHistoryModel extends WaicModel {

	public function __construct() {
		$this->_setTbl('history');
	}
	public function getStatuses( $st = null ) {
		$statuses = array(
			0 => __('OK', 'ai-copilot-content-generator'),
			1 => __('AI Error', 'ai-copilot-content-generator'),
			2 => __('Plugin Error', 'ai-copilot-content-generator'),
			3 => __('Plugin Data', 'ai-copilot-content-generator'),
		);
		return is_null($st) ? $statuses : ( isset($statuses[$st]) ? $statuses[$st] : '' );
	}
	public function getModes( $m = null ) {
		$modes = array(
			0 => __('Real', 'ai-copilot-content-generator'),
			1 => __('Preview', 'ai-copilot-content-generator'),
		);
		return is_null($m) ? $modes : ( isset($modes[$m]) ? $modes[$m] : '' );
	}
	public function saveHistory( $data = array() ) {
		$data['created'] = WaicUtils::getTimestampDB();
		if (empty($data['feature']) && !empty($data['task_id'])) {
			$data['feature'] = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTaskFeature($data['task_id']);
		}
		$data = $this->normalizeUsageRow($data);
		$id = $this->insert($data);
		
		return $id;
	}
	public function normalizeUsageRow( $data ) {
		$intDefaults = array(
			'user_id' => 0,
			'mode' => 0,
			'status' => 0,
			'tokens' => 0,
			'input_tokens' => 0,
			'output_tokens' => 0,
			'reasoning_tokens' => 0,
			'cached_tokens' => 0,
			'cache_write_tokens' => 0,
			'cost_micro_usd' => 0,
			'est_flags' => 0,
			'tool_calls_count' => 0,
		);
		foreach ($intDefaults as $key => $default) {
			if (!isset($data[$key]) || '' === $data[$key]) {
				$data[$key] = $default;
			}
		}
		if (empty($data['operation'])) {
			$data['operation'] = 'chat';
		}
		if (!array_key_exists('session_id', $data)) {
			$data['session_id'] = null;
		} else if ('' === $data['session_id']) {
			$data['session_id'] = null;
		} else {
			$data['session_id'] = substr(sanitize_text_field((string) $data['session_id']), 0, 64);
		}
		if (!array_key_exists('duration_ms', $data) || '' === $data['duration_ms']) {
			$data['duration_ms'] = null;
		}
		if (!array_key_exists('best_score_x1000', $data) || '' === $data['best_score_x1000']) {
			$data['best_score_x1000'] = null;
		}
		foreach (array('feature' => 24, 'operation' => 24, 'engine' => 64, 'model' => 160, 'profile_id' => 64) as $key => $limit) {
			if (isset($data[$key])) {
				$data[$key] = substr(sanitize_text_field((string) $data[$key]), 0, $limit);
			}
		}
		if (is_array(WaicUtils::getArrayValue($data, 'meta', null))) {
			$data['meta'] = wp_json_encode($data['meta'], JSON_UNESCAPED_SLASHES);
		}
		$tokensSum = (int) $data['input_tokens'] + (int) $data['output_tokens'] + (int) $data['reasoning_tokens'] + (int) $data['cached_tokens'] + (int) $data['cache_write_tokens'];
		if ($tokensSum > 0 && empty($data['tokens'])) {
			$data['tokens'] = $tokensSum;
		} else if (empty($data['tokens'])) {
			$data['tokens'] = 0;
		}
		if (empty($data['cost_micro_usd']) && isset($data['cost']) && '' !== $data['cost'] && null !== $data['cost']) {
			$data['cost_micro_usd'] = (int) round(((float) $data['cost']) * 1000000);
		}
		$data['cost'] = round(((int) $data['cost_micro_usd']) / 1000000, 4);

		return $data;
	}
	public function getCountTokens( $where ) {
		$cnt = $this->setWhere($where)->setSelectFields('sum(tokens)')->getFromTbl(array('return' => 'one'));
		return $cnt ? $cnt : 0;
	}
	public function getCountTokensPerFeature( $where = array() ) {
		$data = $this->setWhere($where)->setSelectFields('feature, sum(tokens) as total')->groupBy('feature')->getFromTbl();
		return $data ? $data : array();
	}
	public function getCountRequests( $where ) {
		$cnt = $this->setWhere($where)->setSelectFields('count(id)')->getFromTbl(array('return' => 'one'));
		return $cnt ? $cnt : 0;
	}
	
	public function getLastDate( $year, $month ) {
		$cnt = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		return $year . '-' . ( $month >= 10 ? $month : '0' . $month ) . '-' . ( $cnt >= 10 ? $cnt : '0' . $cnt );
	}
	public function getDBDate( $year, $month, $day ) {
		return $year . '-' . ( $month >= 10 ? $month : '0' . $month ) . '-' . ( $day >= 10 ? $day : '0' . $day );
	}
	public function calcTokens() {
		$query = 'UPDATE @__tasks t SET t.tokens=(SELECT sum(h.tokens) FROM @__history h WHERE h.task_id=t.id)';
		WaicDb::query($query);
		return true;
	}
	public function rollupHistoryDaily( $fromDateTime = '' ) {
		global $wpdb;
		$prefix = $wpdb->prefix . WAIC_DB_PREF;
		$where = '';
		if ('' === $fromDateTime) {
			$hasRollup = WaicDb::get('SELECT 1 FROM `@__history_daily` LIMIT 1', 'one');
			if ($hasRollup == 1) {
				$fromDateTime = gmdate('Y-m-d H:i:s', time() - (2 * DAY_IN_SECONDS));
			}
		}
		if ('' !== $fromDateTime) {
			$where = $wpdb->prepare(' WHERE created >= %s', $fromDateTime);
		}
		$wpdb->query(
			"INSERT INTO `{$prefix}history_daily`
				(day, feature, operation, engine, model, mode,
				events_count, errors_count, tokens_est_count, cost_est_count, aborted_count, low_conf_count,
				tool_calls_steps_sum, input_tokens, output_tokens, reasoning_tokens, cached_tokens,
				cache_write_tokens, total_tokens, cost_micro_usd, cost, duration_ms_sum)
			SELECT DATE(created) AS day,
				feature, operation, engine, model, mode,
				COUNT(*) AS events_count,
				SUM(CASE WHEN status <> 0 THEN 1 ELSE 0 END) AS errors_count,
				SUM(CASE WHEN (est_flags & 1) = 1 THEN 1 ELSE 0 END) AS tokens_est_count,
				SUM(CASE WHEN (est_flags & 2) = 2 THEN 1 ELSE 0 END) AS cost_est_count,
				SUM(CASE WHEN (est_flags & 4) = 4 THEN 1 ELSE 0 END) AS aborted_count,
				SUM(CASE WHEN best_score_x1000 < 650 AND best_score_x1000 IS NOT NULL THEN 1 ELSE 0 END) AS low_conf_count,
				SUM(tool_calls_count) AS tool_calls_steps_sum,
				SUM(input_tokens), SUM(output_tokens), SUM(reasoning_tokens), SUM(cached_tokens),
				SUM(cache_write_tokens), SUM(tokens), SUM(cost_micro_usd),
				ROUND(SUM(cost_micro_usd) / 1000000, 6),
				SUM(COALESCE(duration_ms, 0))
			FROM `{$prefix}history`{$where}
			GROUP BY day, feature, operation, engine, model, mode
			ON DUPLICATE KEY UPDATE
				events_count = VALUES(events_count),
				errors_count = VALUES(errors_count),
				tokens_est_count = VALUES(tokens_est_count),
				cost_est_count = VALUES(cost_est_count),
				aborted_count = VALUES(aborted_count),
				low_conf_count = VALUES(low_conf_count),
				tool_calls_steps_sum = VALUES(tool_calls_steps_sum),
				input_tokens = VALUES(input_tokens),
				output_tokens = VALUES(output_tokens),
				reasoning_tokens = VALUES(reasoning_tokens),
				cached_tokens = VALUES(cached_tokens),
				cache_write_tokens = VALUES(cache_write_tokens),
				total_tokens = VALUES(total_tokens),
				cost_micro_usd = VALUES(cost_micro_usd),
				cost = VALUES(cost),
				duration_ms_sum = VALUES(duration_ms_sum)"
		);
		$wpdb->query(
			"INSERT INTO `{$prefix}sessions_daily`
				(day, feature, session_id, first_seen, last_seen, messages_count, cost_micro_usd, cost)
			SELECT DATE(created) AS day, feature, session_id, MIN(created), MAX(created),
				COUNT(*), SUM(cost_micro_usd), ROUND(SUM(cost_micro_usd) / 1000000, 6)
			FROM `{$prefix}history`{$where}
			" . ('' === $where ? 'WHERE' : 'AND') . " session_id IS NOT NULL AND session_id <> '' AND mode = 0
			GROUP BY day, feature, session_id
			ON DUPLICATE KEY UPDATE
				first_seen = VALUES(first_seen),
				last_seen = VALUES(last_seen),
				messages_count = VALUES(messages_count),
				cost_micro_usd = VALUES(cost_micro_usd),
				cost = VALUES(cost)"
		);
		$this->invalidateInsightsTransients();
		return true;
	}
	public function cleanupRawHistory( $retentionDays = null, $batchSize = 500 ) {
		global $wpdb;
		$retentionDays = is_null($retentionDays) ? (int) get_option('waic_history_retention_days', 30) : (int) $retentionDays;
		$batchSize = min(5000, max(1, (int) $batchSize));
		if ($retentionDays < 1) {
			return 0;
		}
		$cutoff = gmdate('Y-m-d H:i:s', time() - ($retentionDays * DAY_IN_SECONDS));
		$table = $wpdb->prefix . WAIC_DB_PREF . 'history';
		$chatlogs = $wpdb->prefix . WAIC_DB_PREF . 'chatlogs';
		$deleted = (int) $wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE created < %s ORDER BY id ASC LIMIT %d", $cutoff, $batchSize));
		$orphanIds = $wpdb->get_col($wpdb->prepare(
			"SELECT l.id FROM `{$chatlogs}` l LEFT JOIN `{$table}` h ON h.id = l.his_id WHERE h.id IS NULL ORDER BY l.id ASC LIMIT %d",
			$batchSize
		));
		$orphanDeleted = 0;
		if (!empty($orphanIds)) {
			$orphanIds = array_map('intval', $orphanIds);
			$orphanDeleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$chatlogs}` WHERE id IN (" . WaicDb::placeholders($orphanIds, '%d') . ")",
					$orphanIds
				)
			);
		}
		if ($deleted > 0 || $orphanDeleted > 0) {
			$this->invalidateInsightsTransients();
		}
		return $deleted + $orphanDeleted;
	}
	private function invalidateInsightsTransients() {
		delete_transient('waic_insights_overview');
		delete_transient('waic_insights_usage_chart');
		delete_transient('waic_insights_usage_table');
	}
	public function deleteHistory( $taskId ) {
		$this->delete(array('task_id' => $taskId));
		return true;
	}
}
