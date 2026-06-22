<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInsightsModel extends WaicModel {
	const SIG_ERROR = 1;
	const SIG_LOW_SCORE = 2;
	const SIG_NO_ANSWER = 4;
	const SIG_FALLBACK_PHRASE = 8;
	const SIG_REASK = 16;
	const SIG_NEGATIVE_USER = 32;
	const SIG_HANDOFF = 64;
	const SIG_PRODUCT_SHOWN = 128;
	const SIG_CART_EVENT = 256;
	const SIG_ZERO_RESULT = 512;
	const SIG_TOOL_RETRY = 1024;
	const SIG_ABORTED = 2048;
	const SIG_LIMIT_BLOCKED = 4096;

	private $_maxRangeDays = 90;
	private $_freeRangeDays = 7;
	private $_proRangeDays = 90;
	private $_rawEventsRangeDays = 30;
	private $_defaultPerPage = 25;
	private $_maxPerPage = 50;
	private $_snippetLimit = 160;
	private $_modalMessageLimit = 2000;

	public function getDefaultFilters() {
		$rangeDays = $this->_rangeLimitDays();
		return array(
			'date_from' => WaicUtils::addDays(-1 * ($rangeDays - 1), 'Y-m-d'),
			'date_to' => WaicUtils::getConvertedDate(false, 'Y-m-d'),
			'feature' => 'all',
			'engine' => 'all',
			'model' => 'all',
			'operation' => 'all',
			'group_by' => 'feature',
			'task_id' => 0,
			'mode' => '0',
			'status' => 'all',
		);
	}

	public function getChatbotOptions() {
		$rows = WaicDb::get(
			"SELECT id, title FROM `@__tasks` WHERE feature=%s ORDER BY title ASC, id ASC",
			'all',
			ARRAY_A,
			array('chatbots')
		);
		$options = array();
		if ($rows) {
			foreach ($rows as $row) {
				$options[(int) $row['id']] = $this->_safeTitle($row['title'], (int) $row['id']);
			}
		}
		return $options;
	}

	public function getOverview( $params ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			return false;
		}
		list($where, $args) = $this->_buildDailyWhere($filters);

		$kpis = WaicDb::get(
			"SELECT COALESCE(SUM(d.events_count), 0) AS events_count,
				COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
				COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd,
				COALESCE(SUM(d.errors_count), 0) AS errors_count,
				COALESCE(SUM(d.low_conf_count), 0) AS low_conf_count,
				COALESCE(SUM(d.tokens_est_count), 0) AS tokens_est_count,
				COALESCE(SUM(d.cost_est_count), 0) AS cost_est_count,
				COALESCE(SUM(d.aborted_count), 0) AS aborted_count,
				COALESCE(SUM(d.tool_calls_steps_sum), 0) AS tool_calls_count,
				COALESCE(SUM(d.duration_ms_sum), 0) AS duration_ms_sum
			FROM `@__history_daily` d" . $where,
			'row',
			ARRAY_A,
			$args
		);
		$events = (int) WaicUtils::getArrayValue($kpis, 'events_count', 0, 1);
		$costMicro = (int) WaicUtils::getArrayValue($kpis, 'cost_micro_usd', 0, 1);
		$sessions = $this->_getSessionCount($filters);

		$kpis = array(
			'events' => $events,
			'conversations' => $events,
			'sessions' => $sessions,
			'tokens' => (int) WaicUtils::getArrayValue($kpis, 'total_tokens', 0, 1),
			'cost_micro_usd' => $costMicro,
			'cost' => $this->_formatCostMicro($costMicro),
			'cost_per_event' => $events > 0 ? $this->_formatCostMicro((int) floor($costMicro / $events)) : $this->_formatCostMicro(0),
			'errors' => (int) WaicUtils::getArrayValue($kpis, 'errors_count', 0, 1),
			'low_conf' => (int) WaicUtils::getArrayValue($kpis, 'low_conf_count', 0, 1),
			'tokens_est' => (int) WaicUtils::getArrayValue($kpis, 'tokens_est_count', 0, 1),
			'cost_est' => (int) WaicUtils::getArrayValue($kpis, 'cost_est_count', 0, 1),
			'aborted' => (int) WaicUtils::getArrayValue($kpis, 'aborted_count', 0, 1),
			'tool_calls' => (int) WaicUtils::getArrayValue($kpis, 'tool_calls_count', 0, 1),
		);
		$displayCost = $this->_formatDisplayCostMicro($costMicro, 2);
		$displayPerEvent = $this->_formatDisplayCostMicro($events > 0 ? (int) floor($costMicro / $events) : 0, 4);
		$kpis['cost_display'] = $displayCost['formatted'];
		$kpis['cost_display_amount'] = $displayCost['amount'];
		$kpis['cost_display_currency'] = $displayCost['currency'];
		$kpis['cost_per_event_display'] = $displayPerEvent['formatted'];

		return array(
			'filters' => $this->_publicFilters($filters),
			'is_pro' => $this->_isPro(),
			'kpis' => $kpis,
			'trend' => $this->_getDailyCostTrend($filters),
			'breakdowns' => array(
				'features' => $this->_getFeatureBreakdown($filters),
				'cost' => $this->_getProviderBreakdown($filters),
			),
			'attention' => $this->_getAttentionItems($kpis),
			'options' => $this->getUsageOptions($filters),
		);
	}

	public function getUsageOptions( $params = array() ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			$filters = $this->getDefaultFilters();
		}
		list($where, $args) = $this->_buildDailyWhere($filters, array('feature', 'engine', 'model', 'operation'));
		$options = array(
			'features' => $this->_distinctDailyValues('feature', $where, $args),
			'engines' => $this->_distinctDailyValues('engine', $where, $args),
			'models' => $this->_distinctDailyValues('model', $where, $args),
			'operations' => $this->_distinctDailyValues('operation', $where, $args),
			'group_by' => $this->_isPro() ? array('feature', 'engine', 'model', 'day') : array('feature'),
		);
		return $options;
	}

	public function getUsageChart( $params ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			return false;
		}
		$groupBy = $filters['group_by'];
		$column = $this->_usageGroupColumn($groupBy);
		list($where, $args) = $this->_buildDailyWhere($filters);
		if ('day' === $groupBy) {
			$rows = WaicDb::get(
				"SELECT d.day, 'total' AS group_key,
					COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd,
					COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
					COALESCE(SUM(d.events_count), 0) AS events_count
				FROM `@__history_daily` d" . $where . "
				GROUP BY d.day ORDER BY d.day ASC",
				'all',
				ARRAY_A,
				$args
			);
		} else {
			$rows = WaicDb::get(
				"SELECT d.day, COALESCE(NULLIF(d.{$column}, ''), 'unknown') AS group_key,
					COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd,
					COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
					COALESCE(SUM(d.events_count), 0) AS events_count
				FROM `@__history_daily` d" . $where . "
				GROUP BY d.day, group_key ORDER BY d.day ASC, cost_micro_usd DESC",
				'all',
				ARRAY_A,
				$args
			);
		}
		return array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepareChartRows($rows),
		);
	}

	public function getUsageTable( $params ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			return false;
		}
		$groupBy = $filters['group_by'];
		$column = $this->_usageGroupColumn($groupBy);
		list($where, $args) = $this->_buildDailyWhere($filters);
		$selectKey = 'day' === $groupBy ? 'd.day' : "COALESCE(NULLIF(d.{$column}, ''), 'unknown')";
		$rows = WaicDb::get(
			"SELECT {$selectKey} AS group_key,
				COALESCE(SUM(d.events_count), 0) AS events_count,
				COALESCE(SUM(d.errors_count), 0) AS errors_count,
				COALESCE(SUM(d.low_conf_count), 0) AS low_conf_count,
				COALESCE(SUM(d.input_tokens), 0) AS input_tokens,
				COALESCE(SUM(d.output_tokens), 0) AS output_tokens,
				COALESCE(SUM(d.reasoning_tokens), 0) AS reasoning_tokens,
				COALESCE(SUM(d.cached_tokens), 0) AS cached_tokens,
				COALESCE(SUM(d.cache_write_tokens), 0) AS cache_write_tokens,
				COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
				COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd,
				COALESCE(SUM(d.tool_calls_steps_sum), 0) AS tool_calls_count
			FROM `@__history_daily` d" . $where . "
			GROUP BY group_key ORDER BY cost_micro_usd DESC, events_count DESC, group_key ASC",
			'all',
			ARRAY_A,
			$args
		);
		$sessions = 'feature' === $groupBy ? $this->_getSessionsByFeature($filters) : array();
		$out = array();
		$totals = $this->_emptyUsageTotals();
		if ($rows) {
			foreach ($rows as $row) {
				$key = (string) $row['group_key'];
				$item = $this->_prepareUsageSummaryRow($row, $groupBy, isset($sessions[$key]) ? $sessions[$key] : null);
				$out[] = $item;
				$totals = $this->_addUsageTotals($totals, $item);
			}
		}
		return array(
			'filters' => $this->_publicFilters($filters),
			'options' => $this->getUsageOptions($filters),
			'rows' => $out,
			'totals' => $this->_finalizeUsageTotals($totals),
		);
	}

	public function getUsageDrill( $params ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			return false;
		}
		$level = max(0, (int) WaicUtils::getArrayValue($params, 'level', 0, 1));
		$path = $this->_normalizePathFilters($params);
		foreach ($path as $key => $value) {
			$filters[$key] = $value;
		}
		$sequence = $this->_drillSequence((string) WaicUtils::getArrayValue($params, 'group_by', $filters['group_by']));
		$next = isset($sequence[$level + 1]) ? $sequence[$level + 1] : '';
		if ('' === $next) {
			return $this->getUsageEvents(array_merge($filters, $path));
		}
		$filters['group_by'] = $next;
		$data = $this->getUsageTable($filters);
		if (false === $data) {
			return false;
		}
		$data['level'] = $level + 1;
		$data['group_by'] = $next;
		$data['path'] = $path;
		return $data;
	}

	public function getUsageEvents( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if ($this->_rangeDays($filters) > $this->_rawEventsRangeDays) {
			return array(
				'filters' => $this->_publicFilters($filters),
				'rows' => array(),
				'limited' => true,
				'message' => esc_html__('Raw events are available for the last 30 days only.', 'ai-copilot-content-generator'),
			);
		}
		list($where, $args) = $this->_buildUsageHistoryWhere($filters);
		$args[] = (int) $filters['offset'];
		$args[] = (int) $filters['per_page'];
		$rows = WaicDb::get(
			"SELECT h.id, h.created, h.task_id, h.feature, h.operation, h.session_id, h.user_id,
				h.engine, h.model, h.mode, h.status, h.duration_ms,
				h.input_tokens, h.output_tokens, h.reasoning_tokens, h.cached_tokens, h.cache_write_tokens,
				h.tokens, h.cost_micro_usd, h.est_flags, h.tool_calls_count
			FROM `@__history` h" . $where . "
			ORDER BY h.cost_micro_usd DESC, h.created DESC, h.id DESC LIMIT %d, %d",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$out[] = $this->_prepareUsageEventRow($row);
			}
		}
		return array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $out,
			'limited' => false,
			'page' => $filters['page'],
			'per_page' => $filters['per_page'],
		);
	}

	public function getExpensiveSessions( $params ) {
		$filters = $this->_normalizeUsageFilters($params);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isPro()) {
			return array(
				'filters' => $this->_publicFilters($filters),
				'rows' => array(),
				'is_locked' => true,
			);
		}
		list($where, $args) = $this->_buildUsageHistoryWhere($filters);
		$where .= " AND h.session_id IS NOT NULL AND h.session_id <> ''";
		$rows = WaicDb::get(
			"SELECT h.session_id, h.feature, MIN(h.task_id) AS task_id,
				MIN(h.created) AS started_at, MAX(h.created) AS ended_at,
				COUNT(*) AS events_count, COALESCE(SUM(h.tokens), 0) AS total_tokens,
				COALESCE(SUM(h.cost_micro_usd), 0) AS cost_micro_usd
			FROM `@__history` h" . $where . "
			GROUP BY h.session_id, h.feature
			ORDER BY cost_micro_usd DESC, events_count DESC LIMIT 10",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$display = $this->_formatDisplayCostMicro($row['cost_micro_usd']);
				$out[] = array(
					'session_id' => sanitize_text_field($row['session_id']),
					'feature' => sanitize_key($row['feature']),
					'task_id' => (int) $row['task_id'],
					'started_at' => sanitize_text_field($row['started_at']),
					'ended_at' => sanitize_text_field($row['ended_at']),
					'events_count' => (int) $row['events_count'],
					'total_tokens' => (int) $row['total_tokens'],
					'cost_micro_usd' => (int) $row['cost_micro_usd'],
					'cost' => $this->_formatCostMicro($row['cost_micro_usd']),
					'cost_display' => $display['formatted'],
					'cost_display_amount' => $display['amount'],
				);
			}
		}
		return array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $out,
			'is_locked' => false,
		);
	}

	public function getUsageSession( $params ) {
		if (!$this->_isPro()) {
			return array('is_locked' => true, 'events' => array());
		}
		$sessionId = $this->_normalizeSessionId(WaicUtils::getArrayValue($params, 'session_id', ''));
		if ('' === $sessionId) {
			$this->pushError(esc_html__('Invalid session ID.', 'ai-copilot-content-generator'));
			return false;
		}
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		$filters['session_id'] = $sessionId;
		list($where, $args) = $this->_buildUsageHistoryWhere($filters);
		$args[] = 0;
		$args[] = 100;
		$rows = WaicDb::get(
			"SELECT h.id, h.created, h.task_id, h.feature, h.operation, h.session_id, h.user_id,
				h.engine, h.model, h.mode, h.status, h.duration_ms,
				h.input_tokens, h.output_tokens, h.reasoning_tokens, h.cached_tokens, h.cache_write_tokens,
				h.tokens, h.cost_micro_usd, h.est_flags, h.tool_calls_count
			FROM `@__history` h" . $where . "
			ORDER BY h.created ASC, h.id ASC LIMIT %d, %d",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		$total = $this->_emptyUsageTotals();
		if ($rows) {
			foreach ($rows as $row) {
				$item = $this->_prepareUsageEventRow($row);
				$out[] = $item;
				$total['events_count']++;
				$total['total_tokens'] += (int) $item['total_tokens'];
				$total['cost_micro_usd'] += (int) $item['cost_micro_usd'];
			}
		}
		return array(
			'is_locked' => false,
			'session_id' => $sessionId,
			'events' => $out,
			'totals' => $this->_finalizeUsageTotals($total),
		);
	}

	public function getUsageExport( $params ) {
		if (!$this->_isPro()) {
			return array(
				'is_locked' => true,
				'message' => esc_html__('CSV export is available in PRO.', 'ai-copilot-content-generator'),
			);
		}
		$events = $this->getUsageEvents($params);
		if (false === $events) {
			return false;
		}
		return array(
			'is_locked' => false,
			'filename' => 'waic-usage-' . $events['filters']['date_from'] . '--' . $events['filters']['date_to'] . '.csv',
			'columns' => array('id', 'created_utc', 'feature', 'operation', 'task_id', 'session_id', 'engine', 'model', 'mode', 'status', 'input_tokens', 'output_tokens', 'reasoning_tokens', 'cached_tokens', 'cache_write_tokens', 'total_tokens', 'cost_usd', 'est_flags', 'tool_calls_count', 'duration_ms'),
			'rows' => $events['rows'],
			'limited' => $events['limited'],
		);
	}

	public function getConversations( $params ) {
		$filters = $this->_normalizeFilters($params, true);
		if (false === $filters) {
			return false;
		}
		$page = $filters['page'];
		$perPage = $filters['per_page'];
		$offset = ($page - 1) * $perPage;

		list($where, $args) = $this->_buildHistoryWhere($filters);
		$total = (int) WaicDb::get("SELECT COUNT(DISTINCT h.id) FROM `@__history` h" . $where, 'one', ARRAY_A, $args);
		$queryArgs = array_merge($args, array($offset, $perPage));
		$rows = WaicDb::get(
			"SELECT h.id, h.task_id, h.user_id, h.engine, h.model, h.mode, h.created, h.status, h.tokens, h.cost_micro_usd, t.title
			FROM `@__history` h
			LEFT JOIN `@__tasks` t ON t.id = h.task_id" . $where .
			" ORDER BY h.created DESC, h.id DESC LIMIT %d, %d",
			'all',
			ARRAY_A,
			$queryArgs
		);
		$logs = $this->_getLogsForRows($rows);
		$data = array();
		if ($rows) {
			foreach ($rows as $row) {
				$id = (int) $row['id'];
				$data[] = $this->_prepareConversationRow($row, isset($logs[$id]) ? $logs[$id] : array(), false);
			}
		}

		return array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $data,
			'total' => $total,
			'page' => $page,
			'per_page' => $perPage,
			'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
		);
	}

	public function getConversation( $params ) {
		$conversationId = (int) WaicUtils::getArrayValue($params, 'conversation_id', WaicUtils::getArrayValue($params, 'id', 0, 1), 1);
		if ($conversationId <= 0) {
			$this->pushError(esc_html__('Invalid conversation ID.', 'ai-copilot-content-generator'));
			return false;
		}
		$row = WaicDb::get(
			"SELECT h.id, h.task_id, h.user_id, h.engine, h.model, h.mode, h.created, h.status, h.tokens, h.cost_micro_usd, t.title
			FROM `@__history` h
			LEFT JOIN `@__tasks` t ON t.id = h.task_id
			WHERE h.id=%d AND h.feature=%s",
			'row',
			ARRAY_A,
			array($conversationId, 'chatbots')
		);
		if (!$row) {
			$this->pushError(esc_html__('Conversation not found.', 'ai-copilot-content-generator'));
			return false;
		}
		$logs = $this->_getLogsForRows(array($row));
		return array(
			'conversation' => $this->_prepareConversationRow($row, isset($logs[$conversationId]) ? $logs[$conversationId] : array(), true),
		);
	}

	public function getInsights360Status( $params = array() ) {
		return $this->_insights360Envelope(array());
	}

	public function refreshAnalytics( $params = array() ) {
		$days = max(1, (int) WaicUtils::getArrayValue($params, 'days', 2, 1));
		return $this->rollupInsights360($days);
	}

	public function getActionCenter( $params ) {
		$clusters = $this->getProblemClusters($params);
		if (false === $clusters) {
			return false;
		}
		$outcomes = $this->getOutcomes($params);
		$actions = $this->getActions($params);
		return $this->_insights360Envelope(array(
			'clusters' => WaicUtils::getArrayValue($clusters, 'rows', array(), 2),
			'outcomes' => false === $outcomes ? array() : WaicUtils::getArrayValue($outcomes, 'rows', array(), 2),
			'actions' => false === $actions ? array() : WaicUtils::getArrayValue($actions, 'rows', array(), 2),
			'filters' => WaicUtils::getArrayValue($clusters, 'filters', array(), 2),
		));
	}

	public function getProblemClusters( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		list($where, $args) = $this->_build360EventsWhere($filters, 'e');
		$rows = WaicDb::get(
			"SELECT e.cluster_hash, e.problem_code, COUNT(*) AS events_count, COUNT(DISTINCT e.session_id) AS sessions,
				COALESCE(SUM(e.cost_micro_usd), 0) AS cost_micro_usd,
				MAX(e.signal_mask) AS signal_mask,
				MAX(CASE WHEN e.status <> 0 THEN 1 ELSE 0 END) AS had_error,
				MAX(e.updated_at) AS updated_at,
				COALESCE(st.state, 'new') AS state
			FROM `@__insight_events` e
			LEFT JOIN `@__insight_state` st ON st.entity_type='cluster' AND st.entity_hash=e.cluster_hash"
			. $where . " AND e.cluster_hash <> ''
			GROUP BY e.cluster_hash, e.problem_code, st.state
			ORDER BY had_error DESC, events_count DESC, cost_micro_usd DESC
			LIMIT 100",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getProblemEvidence( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		$clusterHash = strtolower((string) WaicUtils::getArrayValue($params, 'cluster_hash', ''));
		if ('' !== $clusterHash && !preg_match('/^[a-f0-9]{40}$/', $clusterHash)) {
			$this->pushError(esc_html__('Invalid cluster hash.', 'ai-copilot-content-generator'));
			return false;
		}
		list($where, $args) = $this->_build360EventsWhere($filters, 'e');
		if ('' !== $clusterHash) {
			$where .= ' AND e.cluster_hash=%s';
			$args[] = $clusterHash;
		}
		$rows = WaicDb::get(
			"SELECT e.his_id, e.created, e.feature, e.operation, e.task_id, e.session_id, e.engine, e.model,
				e.status, e.best_score_x1000, e.tool_calls_count, e.cost_micro_usd, e.signal_mask, e.problem_code, e.cluster_hash
			FROM `@__insight_events` e" . $where . "
			ORDER BY e.created DESC
			LIMIT 50",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getOutcomes( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		list($where, $args) = $this->_build360OutcomesWhere($filters, 'o');
		$rows = WaicDb::get(
			"SELECT o.outcome, COUNT(*) AS sessions, COALESCE(SUM(o.events_count), 0) AS events_count,
				COALESCE(SUM(o.messages_count), 0) AS messages_count,
				COALESCE(SUM(o.cost_micro_usd), 0) AS cost_micro_usd,
				COALESCE(SUM(o.wasted_micro_usd), 0) AS wasted_micro_usd,
				MAX(o.severity) AS severity
			FROM `@__conversation_outcomes` o"
			. $where . "
			GROUP BY o.outcome
			ORDER BY severity DESC, wasted_micro_usd DESC, sessions DESC",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getCostByOutcome( $params ) {
		$result = $this->getOutcomes($params);
		if (false === $result) {
			return false;
		}
		$rows = array();
		foreach ((array) WaicUtils::getArrayValue($result, 'rows', array(), 2) as $row) {
			$rows[] = array(
				'outcome' => WaicUtils::getArrayValue($row, 'outcome', 'unknown'),
				'cost_micro_usd' => (int) WaicUtils::getArrayValue($row, 'cost_micro_usd', 0, 1),
				'cost_display' => WaicUtils::getArrayValue($row, 'cost_display', $this->_formatDisplayCostMicro(0, 4)),
				'wasted_micro_usd' => (int) WaicUtils::getArrayValue($row, 'wasted_micro_usd', 0, 1),
				'wasted_display' => WaicUtils::getArrayValue($row, 'wasted_display', $this->_formatDisplayCostMicro(0, 4)),
			);
		}
		return $this->_insights360Envelope(array(
			'filters' => WaicUtils::getArrayValue($result, 'filters', array(), 2),
			'rows' => $rows,
		));
	}

	public function getKbAttribution( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		list($where, $args) = $this->_build360KbWhere($filters, 'k');
		$rows = WaicDb::get(
			"SELECT k.object_type, k.object_id, k.chunk_ref, k.task_id,
				COALESCE(SUM(k.retrieved_count), 0) AS retrieved_count,
				COALESCE(SUM(k.used_count), 0) AS used_count,
				COALESCE(SUM(k.reask_after_count), 0) AS reask_after_count,
				COALESCE(SUM(k.resolved_after_count), 0) AS resolved_after_count,
				COALESCE(SUM(k.score_sum_x1000), 0) AS score_sum_x1000,
				COALESCE(SUM(k.score_samples), 0) AS score_samples,
				MAX(k.health) AS health
			FROM `@__kb_attribution` k"
			. $where . "
			GROUP BY k.object_type, k.object_id, k.chunk_ref, k.task_id
			ORDER BY reask_after_count DESC, used_count DESC
			LIMIT 100",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getCommerceGaps( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		list($where, $args) = $this->_build360EventsWhere($filters, 'e');
		$rows = WaicDb::get(
			"SELECT e.cluster_hash, e.problem_code, e.feature, e.task_id, COUNT(*) AS events_count,
				COUNT(DISTINCT e.session_id) AS sessions,
				COALESCE(SUM(e.cost_micro_usd), 0) AS cost_micro_usd,
				MAX(e.updated_at) AS updated_at
			FROM `@__insight_events` e"
			. $where . " AND (e.commerce_flag=1 OR e.problem_code='product_no_results')
			GROUP BY e.cluster_hash, e.problem_code, e.feature, e.task_id
			ORDER BY events_count DESC, cost_micro_usd DESC
			LIMIT 100",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getMcpAudit( $params ) {
		$filters = $this->_normalizeUsageFilters($params, true);
		if (false === $filters) {
			return false;
		}
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('filters' => $this->_publicFilters($filters), 'rows' => array()));
		}
		$source = $this->_resolveMcpAuditSource($filters);
		$rows = 'table' === $source ? $this->_getMcpAuditFromTable($filters) : $this->_getMcpAuditFromHistoryMeta($filters);
		return $this->_insights360Envelope(array(
			'filters' => $this->_publicFilters($filters),
			'mcp_audit_source' => $source,
			'rows' => $this->_prepare360Rows($rows),
		));
	}

	public function getActions( $params ) {
		$clusters = $this->getProblemClusters($params);
		if (false === $clusters) {
			return false;
		}
		$rows = array();
		foreach ((array) WaicUtils::getArrayValue($clusters, 'rows', array(), 2) as $cluster) {
			$state = (string) WaicUtils::getArrayValue($cluster, 'state', 'new');
			if (in_array($state, array('fixed', 'ignored'), true)) {
				continue;
			}
			$clusterHash = (string) WaicUtils::getArrayValue($cluster, 'cluster_hash', '');
			$problemCode = (string) WaicUtils::getArrayValue($cluster, 'problem_code', 'unknown');
			$actionHash = sha1('action|' . $clusterHash . '|' . $problemCode);
			$rows[] = array(
				'action_hash' => $actionHash,
				'entity_hash' => $clusterHash,
				'problem_code' => $problemCode,
				'title' => $this->_actionTitle($problemCode),
				'priority' => $this->_actionPriority($cluster),
				'state' => $this->_getInsightState('action', $actionHash, 'new'),
				'events_count' => (int) WaicUtils::getArrayValue($cluster, 'events_count', 0, 1),
				'sessions' => (int) WaicUtils::getArrayValue($cluster, 'sessions', 0, 1),
				'cost_display' => WaicUtils::getArrayValue($cluster, 'cost_display', ''),
			);
		}
		return $this->_insights360Envelope(array(
			'filters' => WaicUtils::getArrayValue($clusters, 'filters', array(), 2),
			'rows' => $rows,
		));
	}

	public function getActionsExport( $params ) {
		$actions = $this->getActions($params);
		if (false === $actions) {
			return false;
		}
		$headers = array('action_hash', 'entity_hash', 'problem_code', 'title', 'priority', 'state', 'events_count', 'sessions', 'cost_display');
		$rows = array();
		foreach ((array) WaicUtils::getArrayValue($actions, 'rows', array(), 2) as $action) {
			$row = array();
			foreach ($headers as $header) {
				$row[$header] = $this->maskForExport(WaicUtils::getArrayValue($action, $header, ''));
			}
			$rows[] = $row;
		}
		return $this->_insights360Envelope(array(
			'filters' => WaicUtils::getArrayValue($actions, 'filters', array(), 2),
			'headers' => $headers,
			'rows' => $rows,
			'csv' => $this->_csvFromRows($headers, $rows),
		));
	}

	public function setInsightState( $params ) {
		if (!$this->_isInsights360SchemaReady()) {
			$this->pushError(esc_html__('Insights 360 storage is not ready.', 'ai-copilot-content-generator'));
			return false;
		}
		// entity_type whitelist: cluster, action. State whitelist: new, reviewed, fixed, ignored.
		$entityType = sanitize_key((string) WaicUtils::getArrayValue($params, 'entity_type', ''));
		$entityHash = strtolower((string) WaicUtils::getArrayValue($params, 'entity_hash', ''));
		$state = sanitize_key((string) WaicUtils::getArrayValue($params, 'state', ''));
		$note = sanitize_text_field((string) WaicUtils::getArrayValue($params, 'note', ''));
		if (!in_array($entityType, array('cluster', 'action'), true)) {
			$this->pushError(esc_html__('Invalid Insights entity type.', 'ai-copilot-content-generator'));
			return false;
		}
		if (!preg_match('/^[a-f0-9]{40}$/', $entityHash)) {
			$this->pushError(esc_html__('Invalid 40-char entity hash.', 'ai-copilot-content-generator'));
			return false;
		}
		if (!in_array($state, array('new', 'reviewed', 'fixed', 'ignored'), true)) {
			$this->pushError(esc_html__('Invalid Insights state.', 'ai-copilot-content-generator'));
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . WAIC_DB_PREF . 'insight_state';
		$updatedAt = gmdate('Y-m-d H:i:s');
		$updatedBy = (int) get_current_user_id();
		$sql = "INSERT INTO `{$table}` (entity_type, entity_hash, state, note, updated_by, updated_at)
			VALUES (%s, %s, %s, %s, %d, %s)
			ON DUPLICATE KEY UPDATE state=VALUES(state), note=VALUES(note), updated_by=VALUES(updated_by), updated_at=VALUES(updated_at)";
		$wpdb->query($wpdb->prepare($sql, array($entityType, $entityHash, $state, $note, $updatedBy, $updatedAt)));
		$this->_invalidateInsights360Cache();
		return array(
			'entity_type' => $entityType,
			'entity_hash' => $entityHash,
			'state' => $state,
			'note' => $note,
			'updated_by' => $updatedBy,
			'updated_at' => $updatedAt,
		);
	}

	public function maskForExport( $value ) {
		if (is_bool($value)) {
			$value = $value ? '1' : '0';
		}
		if (is_array($value) || is_object($value)) {
			$value = wp_json_encode($value);
		}
		$value = wp_strip_all_tags((string) $value);
		$value = preg_replace('/[\r\n\t]+/', ' ', $value);
		$value = sanitize_text_field($value);
		if (preg_match('/^[=+\-@]/', $value)) {
			$value = "'" . $value;
		}
		return mb_substr($value, 0, 255);
	}

	public function rollupInsights360( $days = 2, $batchSize = 500 ) {
		if (!$this->_isInsights360SchemaReady()) {
			return $this->_insights360Envelope(array('processed' => 0, 'reason' => 'schema_not_ready'));
		}
		if (!$this->_isInsightsAnalyticsEnabled()) {
			return $this->_insights360Envelope(array('processed' => 0, 'reason' => 'analytics_disabled'));
		}

		$batchSize = min(1000, max(1, (int) $batchSize));
		$processed = 0;
		$conversationKeys = array();
		$eventDays = array();
		if (!$this->_isInsights360BackfillDone()) {
			$cursor = max(0, (int) get_option('waic_insights_360_backfill_cursor', 0));
			$rows = $this->_getHistoryRowsFor360Backfill($cursor, $batchSize);
			if (empty($rows)) {
				update_option('waic_insights_360_backfill_done', 1, false);
				update_option('waic_insights_360_backfill_cursor', 0, false);
			} else {
				$processed = $this->_projectHistoryRowsToInsightEvents($rows, $conversationKeys, $eventDays);
				$last = end($rows);
				update_option('waic_insights_360_backfill_cursor', (int) $last['id'], false);
				if ($processed < $batchSize) {
					update_option('waic_insights_360_backfill_done', 1, false);
					update_option('waic_insights_360_backfill_cursor', 0, false);
				}
			}
		} else {
			$days = min($this->_maxRangeDays, max(1, (int) $days));
			$fromDateTime = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
			$rows = $this->_getHistoryRowsFor360Since($fromDateTime, 5000);
			$processed = $this->_projectHistoryRowsToInsightEvents($rows, $conversationKeys, $eventDays);
		}

		if (!empty($conversationKeys)) {
			$this->_rollupConversationOutcomes($conversationKeys);
		}
		if (!empty($eventDays)) {
			$this->_rollupKbAttributionForDays(array_keys($eventDays));
		}
		$this->_touchInsights360Freshness($processed);

		return $this->_insights360Envelope(array('processed' => $processed));
	}

	private function _isInsights360SchemaReady() {
		return class_exists('WaicInstallerDbUpdater') && WaicInstallerDbUpdater::isInsights360SchemaInstalled();
	}

	private function _isInsightsAnalyticsEnabled() {
		return 1 === (int) get_option('waic_insights_analytics_enabled', 1);
	}

	private function _isInsights360BackfillDone() {
		return 1 === (int) get_option('waic_insights_360_backfill_done', 0);
	}

	private function _insights360Envelope( $payload ) {
		return array_merge(array(
			'schema_ready' => $this->_isInsights360SchemaReady(),
			'analytics_enabled' => $this->_isInsightsAnalyticsEnabled(),
			'backfill_done' => $this->_isInsights360BackfillDone(),
			'last_rollup_at' => sanitize_text_field((string) get_option('waic_insights_360_last_rollup_at', '')),
			'data_through' => sanitize_text_field((string) get_option('waic_insights_360_data_through', '')),
			'mcp_audit_source' => sanitize_key((string) get_option('waic_insights_mcp_audit_source', 'auto')),
			'cache_ver' => (int) get_option('waic_insights_360_cache_ver', 1),
		), (array) $payload);
	}

	private function _build360EventsWhere( $filters, $alias = 'e' ) {
		$prefix = $alias . '.';
		$where = array($prefix . 'day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		foreach (array('feature', 'engine', 'model', 'operation') as $key) {
			if (!empty($filters[$key]) && 'all' !== $filters[$key]) {
				$where[] = $prefix . $key . '=%s';
				$args[] = $filters[$key];
			}
		}
		if ('0' === $filters['mode'] || '1' === $filters['mode']) {
			$where[] = $prefix . 'mode=%d';
			$args[] = (int) $filters['mode'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _build360OutcomesWhere( $filters, $alias = 'o' ) {
		$prefix = $alias . '.';
		$where = array($prefix . 'day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		if (!empty($filters['feature']) && 'all' !== $filters['feature']) {
			$where[] = $prefix . 'feature=%s';
			$args[] = $filters['feature'];
		}
		if (!empty($filters['task_id'])) {
			$where[] = $prefix . 'task_id=%d';
			$args[] = (int) $filters['task_id'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _build360KbWhere( $filters, $alias = 'k' ) {
		$prefix = $alias . '.';
		$where = array($prefix . 'day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		if (!empty($filters['task_id'])) {
			$where[] = $prefix . 'task_id=%d';
			$args[] = (int) $filters['task_id'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _build360McpWhere( $filters, $alias = 'm' ) {
		$prefix = $alias . '.';
		$where = array($prefix . 'day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _resolveMcpAuditSource( $filters ) {
		$source = sanitize_key((string) get_option('waic_insights_mcp_audit_source', 'auto'));
		if (!in_array($source, array('auto', 'meta', 'table'), true)) {
			$source = 'auto';
		}
		if ('auto' === $source) {
			return $this->_mcpAuditTableHasRows($filters) ? 'table' : 'meta';
		}
		return $source;
	}

	private function _mcpAuditTableHasRows( $filters ) {
		list($where, $args) = $this->_build360McpWhere($filters, 'm');
		return (int) WaicDb::get(
			"SELECT COUNT(*) FROM `@__mcp_audit` m" . $where,
			'one',
			ARRAY_A,
			$args
		) > 0;
	}

	private function _getMcpAuditFromTable( $filters ) {
		list($where, $args) = $this->_build360McpWhere($filters, 'm');
		return WaicDb::get(
			"SELECT m.day, m.client_hash, m.client_label, m.tool, m.tool_class, m.target_type, m.status,
				m.risk, COUNT(*) AS calls, COALESCE(SUM(m.duration_ms), 0) AS duration_ms
			FROM `@__mcp_audit` m"
			. $where . "
			GROUP BY m.day, m.client_hash, m.client_label, m.tool, m.tool_class, m.target_type, m.status, m.risk
			ORDER BY m.day DESC, calls DESC
			LIMIT 100",
			'all',
			ARRAY_A,
			$args
		);
	}

	private function _getMcpAuditFromHistoryMeta( $filters ) {
		list($where, $args) = $this->_buildUsageHistoryWhere($filters);
		$rows = WaicDb::get(
			"SELECT h.id, h.created, DATE(h.created) AS day, h.user_id, h.status,
				COALESCE(h.duration_ms, 0) AS duration_ms, h.meta
			FROM `@__history` h" . $where . "
				AND (h.feature='mcp' OR COALESCE(h.tool_calls_count, 0) > 0)
				AND h.meta IS NOT NULL AND h.meta <> ''
			ORDER BY h.created DESC, h.id DESC
			LIMIT 5000",
			'all',
			ARRAY_A,
			$args
		);
		$grouped = array();
		foreach ((array) $rows as $row) {
			$meta = $this->_decodeHistoryMeta(WaicUtils::getArrayValue($row, 'meta', ''));
			$tools = $this->_extractToolNamesFromMeta($meta);
			if (empty($tools)) {
				continue;
			}
			$userId = max(0, (int) WaicUtils::getArrayValue($row, 'user_id', 0, 1));
			$status = 0 === (int) WaicUtils::getArrayValue($row, 'status', 0, 1) ? 1 : 0;
			$clientHash = $userId > 0 ? sha1('wp-user|' . $userId) : '';
			$clientLabel = $userId > 0 ? 'WP user #' . $userId : 'Unknown client';
			foreach ($tools as $tool) {
				$toolClass = $this->_mcpToolClass($tool);
				$risk = $this->_mcpToolRisk($tool, $toolClass, $status, '' === $clientHash);
				$targetType = $this->_mcpToolTargetType($tool);
				$key = implode('|', array(
					(string) WaicUtils::getArrayValue($row, 'day', ''),
					$clientHash,
					$clientLabel,
					$tool,
					$toolClass,
					$targetType,
					(string) $status,
					$risk,
				));
				if (!isset($grouped[$key])) {
					$grouped[$key] = array(
						'day' => sanitize_text_field((string) WaicUtils::getArrayValue($row, 'day', '')),
						'client_hash' => $clientHash,
						'client_label' => $clientLabel,
						'tool' => $tool,
						'tool_class' => $toolClass,
						'target_type' => $targetType,
						'status' => $status,
						'risk' => $risk,
						'calls' => 0,
						'duration_ms' => 0,
					);
				}
				$grouped[$key]['calls']++;
				$grouped[$key]['duration_ms'] += max(0, (int) WaicUtils::getArrayValue($row, 'duration_ms', 0, 1));
			}
		}
		$out = array_values($grouped);
		usort($out, array($this, '_sortMcpAuditRows'));
		return array_slice($out, 0, 100);
	}

	private function _sortMcpAuditRows( $a, $b ) {
		$dayCmp = strcmp((string) WaicUtils::getArrayValue($b, 'day', ''), (string) WaicUtils::getArrayValue($a, 'day', ''));
		if (0 !== $dayCmp) {
			return $dayCmp;
		}
		$callsCmp = (int) WaicUtils::getArrayValue($b, 'calls', 0, 1) - (int) WaicUtils::getArrayValue($a, 'calls', 0, 1);
		if (0 !== $callsCmp) {
			return $callsCmp;
		}
		return strcmp((string) WaicUtils::getArrayValue($a, 'tool', ''), (string) WaicUtils::getArrayValue($b, 'tool', ''));
	}

	private function _extractToolNamesFromMeta( $meta ) {
		$tools = array();
		foreach (array('tool_names', 'tools') as $key) {
			if (empty($meta[$key])) {
				continue;
			}
			$raw = is_array($meta[$key]) ? $meta[$key] : preg_split('/[\s,]+/', (string) $meta[$key]);
			foreach ((array) $raw as $tool) {
				$tool = sanitize_key((string) $tool);
				if ('' !== $tool) {
					$tools[$tool] = true;
				}
			}
		}
		return array_keys($tools);
	}

	private function _mcpToolClass( $tool ) {
		$tool = sanitize_key((string) $tool);
		$overrides = apply_filters('waic_insights_mcp_tool_class_map', array(
			'exec_php' => 'destructive',
			'delete_file' => 'destructive',
			'activate_plugin' => 'write',
		));
		if (isset($overrides[$tool]) && in_array($overrides[$tool], array('read', 'write', 'destructive'), true)) {
			return $overrides[$tool];
		}
		if (preg_match('/(^|_)(delete|remove|drop|truncate|deactivate|uninstall)(_|$)/', $tool)) {
			return 'destructive';
		}
		if (preg_match('/(^|_)(create|update|set|add|edit|import|activate|install)(_|$)/', $tool)) {
			return 'write';
		}
		return 'read';
	}

	private function _mcpToolRisk( $tool, $toolClass, $status, $unknownClient ) {
		$tool = sanitize_key((string) $tool);
		$highRiskTools = apply_filters('waic_insights_mcp_high_risk_tools', array('exec_php', 'delete_file', 'activate_plugin'));
		if (in_array($tool, (array) $highRiskTools, true) || 'destructive' === $toolClass || (0 === (int) $status && 'write' === $toolClass) || $unknownClient) {
			return 'high';
		}
		if ('write' === $toolClass) {
			return 'medium';
		}
		return 'low';
	}

	private function _mcpToolTargetType( $tool ) {
		$tool = sanitize_key((string) $tool);
		foreach (array('post', 'user', 'comment', 'plugin', 'option', 'term', 'product', 'page') as $target) {
			if (false !== strpos($tool, '_' . $target) || false !== strpos($tool, $target . '_')) {
				return $target;
			}
		}
		return '';
	}

	private function _prepare360Rows( $rows ) {
		$prepared = array();
		foreach ((array) $rows as $row) {
			$item = array();
			foreach ((array) $row as $key => $value) {
				$item[$key] = is_scalar($value) || null === $value ? $value : '';
			}
			if (isset($item['cost_micro_usd'])) {
				$item['cost_display'] = $this->_formatDisplayCostMicro((int) $item['cost_micro_usd'], 4);
			}
			if (isset($item['wasted_micro_usd'])) {
				$item['wasted_display'] = $this->_formatDisplayCostMicro((int) $item['wasted_micro_usd'], 4);
			}
			if (isset($item['score_sum_x1000']) && !empty($item['score_samples'])) {
				$item['avg_score_x1000'] = (int) floor(((int) $item['score_sum_x1000']) / max(1, (int) $item['score_samples']));
			}
			$prepared[] = $item;
		}
		return $prepared;
	}

	private function _actionTitle( $problemCode ) {
		$labels = array(
			'error' => esc_html__('Review failed AI events', 'ai-copilot-content-generator'),
			'product_no_results' => esc_html__('Improve product discovery coverage', 'ai-copilot-content-generator'),
			'unknown_answer' => esc_html__('Add missing answer coverage', 'ai-copilot-content-generator'),
			'wrong_kb_match' => esc_html__('Tune knowledge base matching', 'ai-copilot-content-generator'),
			'missing_kb' => esc_html__('Add knowledge source coverage', 'ai-copilot-content-generator'),
			'reask' => esc_html__('Reduce repeated questions', 'ai-copilot-content-generator'),
			'tool_loop' => esc_html__('Review tool loop behavior', 'ai-copilot-content-generator'),
		);
		return isset($labels[$problemCode]) ? $labels[$problemCode] : esc_html__('Review Insight cluster', 'ai-copilot-content-generator');
	}

	private function _actionPriority( $cluster ) {
		if (!empty($cluster['had_error'])) {
			return 'high';
		}
		if ((int) WaicUtils::getArrayValue($cluster, 'events_count', 0, 1) >= 5) {
			return 'medium';
		}
		return 'low';
	}

	private function _getInsightState( $entityType, $entityHash, $fallback = 'new' ) {
		if (!$this->_isInsights360SchemaReady()) {
			return $fallback;
		}
		$state = WaicDb::get(
			"SELECT state FROM `@__insight_state` WHERE entity_type=%s AND entity_hash=%s",
			'one',
			ARRAY_A,
			array($entityType, $entityHash)
		);
		return empty($state) ? $fallback : sanitize_key((string) $state);
	}

	private function _csvFromRows( $headers, $rows ) {
		$lines = array();
		$lines[] = implode(',', array_map(array($this, '_csvCell'), $headers));
		foreach ((array) $rows as $row) {
			$cells = array();
			foreach ($headers as $header) {
				$cells[] = $this->_csvCell(WaicUtils::getArrayValue($row, $header, ''));
			}
			$lines[] = implode(',', $cells);
		}
		return implode("\n", $lines);
	}

	private function _csvCell( $value ) {
		$value = $this->maskForExport($value);
		return '"' . str_replace('"', '""', $value) . '"';
	}

	private function _getHistoryRowsFor360Backfill( $cursor, $limit ) {
		$retentionDays = max(1, (int) get_option('waic_history_retention_days', 30));
		$cutoff = gmdate('Y-m-d H:i:s', time() - ($retentionDays * DAY_IN_SECONDS));
		return WaicDb::get(
			"SELECT id, task_id, feature, operation, session_id, user_id, engine, model, mode, created, status,
				best_score_x1000, tool_calls_count, cost_micro_usd, est_flags, meta
			FROM `@__history`
			WHERE id > %d AND created >= %s
			ORDER BY id ASC LIMIT %d",
			'all',
			ARRAY_A,
			array((int) $cursor, $cutoff, (int) $limit)
		);
	}

	private function _getHistoryRowsFor360Since( $fromDateTime, $limit ) {
		return WaicDb::get(
			"SELECT id, task_id, feature, operation, session_id, user_id, engine, model, mode, created, status,
				best_score_x1000, tool_calls_count, cost_micro_usd, est_flags, meta
			FROM `@__history`
			WHERE created >= %s
			ORDER BY id ASC LIMIT %d",
			'all',
			ARRAY_A,
			array($fromDateTime, (int) $limit)
		);
	}

	private function _projectHistoryRowsToInsightEvents( $rows, &$conversationKeys, &$eventDays = array() ) {
		if (empty($rows)) {
			return 0;
		}
		$logs = $this->_getLogsFor360Rows($rows);
		$processed = 0;
		foreach ($rows as $row) {
			$id = (int) $row['id'];
			$item = $this->_buildInsightEventProjection($row, isset($logs[$id]) ? $logs[$id] : array());
			$this->_upsertInsightEvent($item);
			if (!empty($item['day'])) {
				$eventDays[$item['day']] = true;
			}
			if ('' !== $item['session_id']) {
				$conversationKeys[$item['feature'] . '|' . $item['session_id']] = array(
					'feature' => $item['feature'],
					'session_id' => $item['session_id'],
				);
			}
			$processed++;
		}
		return $processed;
	}

	private function _getLogsFor360Rows( $rows ) {
		$ids = array();
		foreach ((array) $rows as $row) {
			$ids[] = (int) $row['id'];
		}
		$ids = array_values(array_filter(array_unique($ids)));
		if (empty($ids)) {
			return array();
		}
		$placeholders = WaicDb::placeholders($ids, '%d');
		$logs = WaicDb::get(
			"SELECT his_id, question, answer, file FROM `@__chatlogs`
			WHERE status != 9 AND his_id IN (" . $placeholders . ")
			ORDER BY his_id ASC, id ASC",
			'all',
			ARRAY_A,
			$ids
		);
		$out = array();
		foreach ((array) $logs as $log) {
			$hisId = (int) $log['his_id'];
			if (!isset($out[$hisId])) {
				$out[$hisId] = array();
			}
			$out[$hisId][] = $log;
		}
		return $out;
	}

	private function _buildInsightEventProjection( $row, $logs ) {
		$meta = $this->_decodeHistoryMeta(WaicUtils::getArrayValue($row, 'meta', ''));
		$signalMask = $this->_detectSignalMask($row, $logs, $meta);
		$problemCode = $this->_detectProblemCode($signalMask, $row, $meta);
		$topic = $this->_normalizedTopicFromLogs($logs);
		if ('' === $topic) {
			$topic = 'event-' . (int) $row['id'];
		}
		$bestScore = WaicUtils::getArrayValue($row, 'best_score_x1000', null);
		$bestScore = (null === $bestScore || '' === $bestScore) ? null : (int) $bestScore;
		$kbUsed = (null !== $bestScore || $this->_historyMetaHas($meta, array('kb', 'knowledge', 'embeddings'))) ? 1 : 0;
		$commerceFlag = (($signalMask & (self::SIG_PRODUCT_SHOWN | self::SIG_CART_EVENT)) > 0 || (($signalMask & self::SIG_ZERO_RESULT) && $this->_historyMetaHasCommerceIntent($meta, $row))) ? 1 : 0;
		$created = sanitize_text_field((string) WaicUtils::getArrayValue($row, 'created', gmdate('Y-m-d H:i:s')));
		$day = substr($created, 0, 10);

		return array(
			'his_id' => (int) $row['id'],
			'created' => $created,
			'day' => $day,
			'feature' => $this->_truncate360Field($row['feature'], 24),
			'operation' => $this->_truncate360Field($row['operation'], 24),
			'task_id' => (int) $row['task_id'],
			'session_id' => $this->_normalizeSessionId(WaicUtils::getArrayValue($row, 'session_id', '')),
			'engine' => $this->_truncate360Field($row['engine'], 20),
			'model' => $this->_truncate360Field($row['model'], 160),
			'mode' => (int) $row['mode'],
			'status' => (int) $row['status'],
			'best_score_x1000' => $bestScore,
			'tool_calls_count' => (int) $row['tool_calls_count'],
			'cost_micro_usd' => max(0, (int) $row['cost_micro_usd']),
			'est_flags' => max(0, (int) $row['est_flags']),
			'signal_mask' => $signalMask,
			'problem_code' => $problemCode,
			'kb_used' => $kbUsed,
			'commerce_flag' => $commerceFlag,
			'cluster_hash' => '' === $problemCode ? '' : sha1($problemCode . '|' . $topic),
			'updated_at' => gmdate('Y-m-d H:i:s'),
		);
	}

	private function _upsertInsightEvent( $item ) {
		global $wpdb;
		$table = $wpdb->prefix . WAIC_DB_PREF . 'insight_events';
		$bestScoreSql = is_null($item['best_score_x1000']) ? 'NULL' : '%d';
		$args = array(
			$item['his_id'],
			$item['created'],
			$item['day'],
			$item['feature'],
			$item['operation'],
			$item['task_id'],
			$item['session_id'],
			$item['engine'],
			$item['model'],
			$item['mode'],
			$item['status'],
		);
		if (!is_null($item['best_score_x1000'])) {
			$args[] = $item['best_score_x1000'];
		}
		$args = array_merge($args, array(
			$item['tool_calls_count'],
			$item['cost_micro_usd'],
			$item['est_flags'],
			$item['signal_mask'],
			$item['problem_code'],
			$item['kb_used'],
			$item['commerce_flag'],
			$item['cluster_hash'],
			$item['updated_at'],
		));
		$sql = "INSERT INTO `{$table}`
			(his_id, created, day, feature, operation, task_id, session_id, engine, model, mode, status,
			best_score_x1000, tool_calls_count, cost_micro_usd, est_flags, signal_mask, problem_code,
			kb_used, commerce_flag, cluster_hash, updated_at)
			VALUES (%d, %s, %s, %s, %s, %d, %s, %s, %s, %d, %d, {$bestScoreSql}, %d, %d, %d, %d, %s, %d, %d, %s, %s)
			ON DUPLICATE KEY UPDATE
				created = VALUES(created),
				day = VALUES(day),
				feature = VALUES(feature),
				operation = VALUES(operation),
				task_id = VALUES(task_id),
				session_id = VALUES(session_id),
				engine = VALUES(engine),
				model = VALUES(model),
				mode = VALUES(mode),
				status = VALUES(status),
				best_score_x1000 = VALUES(best_score_x1000),
				tool_calls_count = VALUES(tool_calls_count),
				cost_micro_usd = VALUES(cost_micro_usd),
				est_flags = VALUES(est_flags),
				signal_mask = VALUES(signal_mask),
				problem_code = VALUES(problem_code),
				kb_used = VALUES(kb_used),
				commerce_flag = VALUES(commerce_flag),
				cluster_hash = VALUES(cluster_hash),
				updated_at = VALUES(updated_at)";
		$wpdb->query($wpdb->prepare($sql, $args));
	}

	private function _detectSignalMask( $row, $logs, $meta ) {
		$mask = 0;
		if ((int) WaicUtils::getArrayValue($row, 'status', 0, 1) !== 0) {
			$mask |= self::SIG_ERROR;
		}
		$bestScore = WaicUtils::getArrayValue($row, 'best_score_x1000', null);
		if (null !== $bestScore && '' !== $bestScore && (int) $bestScore < 650) {
			$mask |= self::SIG_LOW_SCORE;
		}
		if (((int) WaicUtils::getArrayValue($row, 'est_flags', 0, 1) & 4) === 4) {
			$mask |= self::SIG_ABORTED;
		}
		if ((int) WaicUtils::getArrayValue($row, 'tool_calls_count', 0, 1) >= 5) {
			$mask |= self::SIG_TOOL_RETRY;
		}
		if ($this->_historyMetaHas($meta, array('limit_blocked', 'blocked_by_limit', 'usage_limit'))) {
			$mask |= self::SIG_LIMIT_BLOCKED;
		}
		if ($this->_historyMetaHas($meta, array('cart_event', 'add_to_cart', 'checkout'))) {
			$mask |= self::SIG_CART_EVENT;
		}
		if ($this->_historyMetaHasZeroResult($meta)) {
			$mask |= self::SIG_ZERO_RESULT;
		}

		$hasLogs = !empty($logs);
		$hasAnswer = false;
		foreach ((array) $logs as $log) {
			$question = (string) WaicUtils::getArrayValue($log, 'question', '');
			$answer = (string) WaicUtils::getArrayValue($log, 'answer', '');
			if ('' !== trim($answer)) {
				$hasAnswer = true;
			}
			if ($this->_textHasAnyPhrase($answer, $this->_fallbackPhrases())) {
				$mask |= self::SIG_NO_ANSWER | self::SIG_FALLBACK_PHRASE;
			}
			if ($this->_textHasAnyPhrase($question, $this->_negativePhrases())) {
				$mask |= self::SIG_NEGATIVE_USER;
			}
			if ($this->_textHasAnyPhrase($question . ' ' . $answer, $this->_handoffPhrases())) {
				$mask |= self::SIG_HANDOFF;
			}
			if (false !== stripos($answer, '##IDS##prod:')) {
				$mask |= self::SIG_PRODUCT_SHOWN;
			}
		}
		if ($hasLogs && !$hasAnswer) {
			$mask |= self::SIG_NO_ANSWER;
		}
		return $mask;
	}

	private function _detectProblemCode( $signalMask, $row, $meta ) {
		$kbUsed = null !== WaicUtils::getArrayValue($row, 'best_score_x1000', null) || $this->_historyMetaHas($meta, array('kb', 'knowledge', 'embeddings'));
		if (($signalMask & self::SIG_ZERO_RESULT) && $this->_historyMetaHasCommerceIntent($meta, $row)) {
			return 'product_no_results';
		}
		return $this->_problemCodeFromSignals($signalMask, $kbUsed);
	}

	private function _problemCodeFromSignals( $signalMask, $kbUsed ) {
		if ($signalMask & self::SIG_ERROR) {
			return 'error';
		}
		if (($signalMask & self::SIG_ZERO_RESULT) && ($signalMask & (self::SIG_PRODUCT_SHOWN | self::SIG_CART_EVENT))) {
			return 'product_no_results';
		}
		if ($signalMask & (self::SIG_NO_ANSWER | self::SIG_FALLBACK_PHRASE)) {
			return 'unknown_answer';
		}
		if ($kbUsed && ($signalMask & (self::SIG_REASK | self::SIG_NEGATIVE_USER))) {
			return 'wrong_kb_match';
		}
		if (($signalMask & self::SIG_LOW_SCORE) && !$kbUsed) {
			return 'missing_kb';
		}
		if ($signalMask & self::SIG_REASK) {
			return 'reask';
		}
		if ($signalMask & self::SIG_TOOL_RETRY) {
			return 'tool_loop';
		}
		return '';
	}

	private function _rollupConversationOutcomes( $conversationKeys ) {
		foreach ($conversationKeys as $conversation) {
			if (empty($conversation['feature']) || empty($conversation['session_id'])) {
				continue;
			}
			$this->_rollupConversationOutcome($conversation['feature'], $conversation['session_id']);
		}
	}

	private function _rollupConversationOutcome( $feature, $sessionId ) {
		global $wpdb;
		$prefix = $wpdb->prefix . WAIC_DB_PREF;
		$this->_applySessionReaskSignals($feature, $sessionId);
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT e.feature, e.session_id, MIN(e.task_id) AS task_id, MIN(e.created) AS first_seen,
					MAX(e.created) AS last_seen, DATE(MAX(e.created)) AS day,
					COUNT(*) AS events_count, COALESCE(SUM(l.log_count), 0) AS messages_count,
					BIT_OR(e.signal_mask) AS signal_mask,
					SUM(CASE WHEN (e.signal_mask & %d) = %d THEN 1 ELSE 0 END) AS reask_count,
					MIN(e.best_score_x1000) AS min_best_score_x1000,
					MAX(CASE WHEN e.status <> 0 THEN 1 ELSE 0 END) AS had_error,
					MAX(CASE WHEN (e.signal_mask & %d) = %d THEN 1 ELSE 0 END) AS had_handoff,
					MAX(e.commerce_flag) AS had_commerce,
					COALESCE(SUM(e.cost_micro_usd), 0) AS cost_micro_usd
				FROM `{$prefix}insight_events` e
				LEFT JOIN (
					SELECT his_id, COUNT(*) AS log_count FROM `{$prefix}chatlogs` WHERE status != 9 GROUP BY his_id
				) l ON l.his_id = e.his_id
				WHERE e.feature=%s AND e.session_id=%s
				GROUP BY e.feature, e.session_id",
				self::SIG_REASK,
				self::SIG_REASK,
				self::SIG_HANDOFF,
				self::SIG_HANDOFF,
				$feature,
				$sessionId
			),
			ARRAY_A
		);
		if (!$row) {
			return;
		}
		$outcome = $this->_classifyOutcome($row);
		$severity = $this->_outcomeSeverity($outcome, (int) $row['signal_mask']);
		$costMicro = max(0, (int) $row['cost_micro_usd']);
		$wastedMicro = $this->_wastedMicroUsd($outcome, $severity, $costMicro);
		$this->_upsertConversationOutcome(array(
			'feature' => $this->_truncate360Field($row['feature'], 24),
			'session_id' => $this->_normalizeSessionId($row['session_id']),
			'task_id' => (int) $row['task_id'],
			'first_seen' => sanitize_text_field($row['first_seen']),
			'last_seen' => sanitize_text_field($row['last_seen']),
			'day' => sanitize_text_field($row['day']),
			'messages_count' => max(0, (int) $row['messages_count']),
			'events_count' => max(0, (int) $row['events_count']),
			'outcome' => $outcome,
			'severity' => $severity,
			'signal_mask' => (int) $row['signal_mask'],
			'reask_count' => max(0, (int) $row['reask_count']),
			'min_best_score_x1000' => null === $row['min_best_score_x1000'] ? null : (int) $row['min_best_score_x1000'],
			'had_error' => (int) $row['had_error'],
			'had_handoff' => (int) $row['had_handoff'],
			'had_commerce' => (int) $row['had_commerce'],
			'cost_micro_usd' => $costMicro,
			'wasted_micro_usd' => $wastedMicro,
			'updated_at' => gmdate('Y-m-d H:i:s'),
		));
	}

	private function _upsertConversationOutcome( $item ) {
		global $wpdb;
		$table = $wpdb->prefix . WAIC_DB_PREF . 'conversation_outcomes';
		$scoreSql = is_null($item['min_best_score_x1000']) ? 'NULL' : '%d';
		$args = array(
			$item['feature'],
			$item['session_id'],
			$item['task_id'],
			$item['first_seen'],
			$item['last_seen'],
			$item['day'],
			$item['messages_count'],
			$item['events_count'],
			$item['outcome'],
			$item['severity'],
			$item['signal_mask'],
			$item['reask_count'],
		);
		if (!is_null($item['min_best_score_x1000'])) {
			$args[] = $item['min_best_score_x1000'];
		}
		$args = array_merge($args, array(
			$item['had_error'],
			$item['had_handoff'],
			$item['had_commerce'],
			$item['cost_micro_usd'],
			$item['wasted_micro_usd'],
			$item['updated_at'],
		));
		$sql = "INSERT INTO `{$table}`
			(feature, session_id, task_id, first_seen, last_seen, day, messages_count, events_count, outcome,
			severity, signal_mask, reask_count, min_best_score_x1000, had_error, had_handoff, had_commerce,
			cost_micro_usd, wasted_micro_usd, updated_at)
			VALUES (%s, %s, %d, %s, %s, %s, %d, %d, %s, %d, %d, %d, {$scoreSql}, %d, %d, %d, %d, %d, %s)
			ON DUPLICATE KEY UPDATE
				task_id = VALUES(task_id),
				first_seen = VALUES(first_seen),
				last_seen = VALUES(last_seen),
				day = VALUES(day),
				messages_count = VALUES(messages_count),
				events_count = VALUES(events_count),
				outcome = VALUES(outcome),
				severity = VALUES(severity),
				signal_mask = VALUES(signal_mask),
				reask_count = VALUES(reask_count),
				min_best_score_x1000 = VALUES(min_best_score_x1000),
				had_error = VALUES(had_error),
				had_handoff = VALUES(had_handoff),
				had_commerce = VALUES(had_commerce),
				cost_micro_usd = VALUES(cost_micro_usd),
				wasted_micro_usd = VALUES(wasted_micro_usd),
				updated_at = VALUES(updated_at)";
		$wpdb->query($wpdb->prepare($sql, $args));
	}

	private function _classifyOutcome( $row ) {
		$mask = (int) WaicUtils::getArrayValue($row, 'signal_mask', 0, 1);
		$events = (int) WaicUtils::getArrayValue($row, 'events_count', 0, 1);
		$outcomes = array('resolved', 'probably_resolved', 'unresolved', 'handoff', 'abandoned', 'error', 'limit_blocked', 'unknown');
		if ($mask & self::SIG_ERROR) {
			return 'error';
		}
		if ($mask & self::SIG_LIMIT_BLOCKED) {
			return 'limit_blocked';
		}
		if ($mask & self::SIG_HANDOFF) {
			return 'handoff';
		}
		if ($mask & self::SIG_ABORTED) {
			return 'abandoned';
		}
		if (($mask & (self::SIG_NO_ANSWER | self::SIG_FALLBACK_PHRASE | self::SIG_NEGATIVE_USER))) {
			return 'unresolved';
		}
		if ((int) WaicUtils::getArrayValue($row, 'messages_count', 0, 1) <= 0) {
			return 'unknown';
		}
		if ($mask & (self::SIG_LOW_SCORE | self::SIG_REASK)) {
			return 'probably_resolved';
		}
		return $events > 0 ? $outcomes[0] : 'unknown';
	}

	private function _outcomeSeverity( $outcome, $signalMask ) {
		if (in_array($outcome, array('error', 'unresolved'), true)) {
			return 2;
		}
		if (in_array($outcome, array('abandoned', 'limit_blocked'), true)) {
			return 1;
		}
		if ('handoff' === $outcome && ($signalMask & self::SIG_REASK)) {
			return 1;
		}
		return 0;
	}

	private function _wastedMicroUsd( $outcome, $severity, $costMicro ) {
		if (in_array($outcome, array('error', 'unresolved', 'abandoned'), true)) {
			return $costMicro;
		}
		return $severity >= 1 ? $costMicro : 0;
	}

	private function _applySessionReaskSignals( $feature, $sessionId ) {
		global $wpdb;
		$prefix = $wpdb->prefix . WAIC_DB_PREF;
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, his_id, created, signal_mask, best_score_x1000, kb_used
				FROM `{$prefix}insight_events`
				WHERE feature=%s AND session_id=%s
				ORDER BY created ASC, id ASC",
				$feature,
				$sessionId
			),
			ARRAY_A
		);
		if (count((array) $events) < 2) {
			return;
		}
		$historyRows = array();
		foreach ((array) $events as $event) {
			$historyRows[] = array('id' => (int) $event['his_id']);
		}
		$logsByHistory = $this->_getLogsFor360Rows($historyRows);
		$reaskIds = $this->_sessionReaskEventIds($events, $logsByHistory);
		$table = $prefix . 'insight_events';
		foreach ((array) $events as $event) {
			$eventId = (int) $event['id'];
			$baseMask = ((int) $event['signal_mask']) & ~self::SIG_REASK;
			$signalMask = isset($reaskIds[$eventId]) ? ($baseMask | self::SIG_REASK) : $baseMask;
			$problemCode = $this->_problemCodeFromSignals($signalMask, 1 === (int) $event['kb_used']);
			$logs = isset($logsByHistory[(int) $event['his_id']]) ? $logsByHistory[(int) $event['his_id']] : array();
			$topic = $this->_normalizedTopicFromLogs($logs);
			if ('' === $topic) {
				$topic = 'event-' . (int) $event['his_id'];
			}
			$clusterHash = '' === $problemCode ? '' : sha1($problemCode . '|' . $topic);
			if ($signalMask === (int) $event['signal_mask']) {
				continue;
			}
			$wpdb->update(
				$table,
				array(
					'signal_mask' => $signalMask,
					'problem_code' => $problemCode,
					'cluster_hash' => $clusterHash,
					'updated_at' => gmdate('Y-m-d H:i:s'),
				),
				array('id' => $eventId),
				array('%d', '%s', '%s', '%s'),
				array('%d')
			);
		}
	}

	private function _sessionReaskEventIds( $events, $logsByHistory ) {
		$threshold = (float) apply_filters('waic_insights_360_reask_jaccard_threshold', 0.6);
		$threshold = max(0.0, min(1.0, $threshold));
		$window = max(1, (int) apply_filters('waic_insights_360_reask_turn_window', 5));
		$previous = array();
		$reaskIds = array();
		foreach ((array) $events as $event) {
			$hisId = (int) WaicUtils::getArrayValue($event, 'his_id', 0, 1);
			$logs = isset($logsByHistory[$hisId]) ? $logsByHistory[$hisId] : array();
			$tokens = $this->_topicTokenSet($this->_normalizedTopicFromLogs($logs));
			if (empty($tokens)) {
				continue;
			}
			$recent = array_slice($previous, -1 * $window);
			foreach ($recent as $priorTokens) {
				if ($this->_jaccardScore($tokens, $priorTokens) >= $threshold) {
					$reaskIds[(int) $event['id']] = true;
					break;
				}
			}
			$previous[] = $tokens;
		}
		return $reaskIds;
	}

	private function _topicTokenSet( $topic ) {
		$tokens = preg_split('/\s+/u', trim((string) $topic));
		$out = array();
		foreach ((array) $tokens as $token) {
			if ('' !== $token) {
				$out[$token] = true;
			}
		}
		return array_keys($out);
	}

	private function _jaccardScore( $left, $right ) {
		$leftSet = array_fill_keys((array) $left, true);
		$rightSet = array_fill_keys((array) $right, true);
		if (empty($leftSet) || empty($rightSet)) {
			return 0.0;
		}
		$intersection = 0;
		foreach ($leftSet as $token => $_) {
			if (isset($rightSet[$token])) {
				$intersection++;
			}
		}
		$union = count($leftSet + $rightSet);
		return $union > 0 ? ($intersection / $union) : 0.0;
	}

	private function _rollupKbAttributionForDays( $days ) {
		global $wpdb;
		$days = array_values(array_unique(array_filter((array) $days, array($this, '_isSqlDate'))));
		if (empty($days)) {
			return;
		}
		$prefix = $wpdb->prefix . WAIC_DB_PREF;
		$table = $prefix . 'kb_attribution';
		foreach ($days as $day) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT e.day, e.task_id, e.feature, e.session_id, e.best_score_x1000, e.signal_mask,
						e.kb_used, h.meta, o.outcome, o.reask_count
					FROM `{$prefix}insight_events` e
					LEFT JOIN `{$prefix}history` h ON h.id=e.his_id
					LEFT JOIN `{$prefix}conversation_outcomes` o ON o.feature=e.feature AND o.session_id=e.session_id
					WHERE e.day=%s AND e.kb_used=1",
					$day
				),
				ARRAY_A
			);
			$aggregates = array();
			foreach ((array) $rows as $row) {
				$bestScore = WaicUtils::getArrayValue($row, 'best_score_x1000', null);
				$bestScore = (null === $bestScore || '' === $bestScore) ? null : (int) $bestScore;
				$meta = $this->_decodeHistoryMeta(WaicUtils::getArrayValue($row, 'meta', ''));
				$refs = $this->_extractKbReferencesFromMeta($meta);
				if (empty($refs) && null !== $bestScore) {
					$refs[] = array('object_type' => 'kb', 'object_id' => 0, 'chunk_ref' => 'top', 'score_x1000' => $bestScore);
				}
				if (empty($refs)) {
					continue;
				}
				$outcome = (string) WaicUtils::getArrayValue($row, 'outcome', 'unknown');
				$isResolved = in_array($outcome, array('resolved', 'probably_resolved'), true);
				$hadReask = ((int) WaicUtils::getArrayValue($row, 'reask_count', 0, 1) > 0) || (((int) WaicUtils::getArrayValue($row, 'signal_mask', 0, 1) & self::SIG_REASK) > 0);
				foreach ($refs as $ref) {
					$score = $this->_scoreFromKbRef($ref, $bestScore);
					$objectType = $this->_truncate360Field(WaicUtils::getArrayValue($ref, 'object_type', 'kb'), 24);
					if ('' === $objectType) {
						$objectType = 'kb';
					}
					$objectId = max(0, (int) WaicUtils::getArrayValue($ref, 'object_id', 0, 1));
					$chunkRef = $this->_truncate360Field(WaicUtils::getArrayValue($ref, 'chunk_ref', ''), 64);
					$key = $objectType . '|' . $objectId . '|' . $chunkRef . '|' . $day;
					if (!isset($aggregates[$key])) {
						$aggregates[$key] = array(
							'day' => $day,
							'object_type' => $objectType,
							'object_id' => $objectId,
							'chunk_ref' => $chunkRef,
							'task_id' => max(0, (int) WaicUtils::getArrayValue($row, 'task_id', 0, 1)),
							'retrieved_count' => 0,
							'used_count' => 0,
							'reask_after_count' => 0,
							'resolved_after_count' => 0,
							'score_sum_x1000' => 0,
							'score_samples' => 0,
						);
					}
					$aggregates[$key]['retrieved_count']++;
					if ($isResolved) {
						$aggregates[$key]['resolved_after_count']++;
					}
					if ($hadReask) {
						$aggregates[$key]['reask_after_count']++;
					}
					if (null !== $score) {
						$aggregates[$key]['score_sum_x1000'] += $score;
						$aggregates[$key]['score_samples']++;
						if ($isResolved && $score >= 650) {
							$aggregates[$key]['used_count']++;
						}
					}
				}
			}
			$wpdb->delete($table, array('day' => $day), array('%s'));
			foreach ($aggregates as $item) {
				$item['health'] = $this->_kbHealth($item);
				$item['updated_at'] = gmdate('Y-m-d H:i:s');
				$wpdb->insert(
					$table,
					$item,
					array('%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
				);
			}
		}
	}

	private function _isSqlDate( $value ) {
		return is_string($value) && WaicUtils::checkDateTimeFormat($value, 'Y-m-d');
	}

	private function _extractKbReferencesFromMeta( $meta ) {
		$refs = array();
		foreach (array('kb', 'knowledge', 'embeddings') as $key) {
			if (!empty($meta[$key])) {
				$this->_appendKbMetaRefs($refs, $meta[$key]);
			}
		}
		foreach (array('kb_object_id', 'kb_post_id', 'knowledge_object_id', 'embedding_object_id') as $key) {
			if (!empty($meta[$key])) {
				$refs[] = array(
					'object_type' => 'kb',
					'object_id' => max(0, (int) $meta[$key]),
					'chunk_ref' => '',
					'score_x1000' => null,
				);
			}
		}
		return $refs;
	}

	private function _appendKbMetaRefs( &$refs, $value ) {
		$ref = $this->_normalizeKbReference($value);
		if (false !== $ref) {
			$refs[] = $ref;
			return;
		}
		if (!is_array($value)) {
			return;
		}
		foreach ($value as $item) {
			$this->_appendKbMetaRefs($refs, $item);
		}
	}

	private function _normalizeKbReference( $value ) {
		if (is_scalar($value) && is_numeric($value)) {
			return array('object_type' => 'kb', 'object_id' => max(0, (int) $value), 'chunk_ref' => '', 'score_x1000' => null);
		}
		if (!is_array($value)) {
			return false;
		}
		$objectId = 0;
		foreach (array('object_id', 'post_id', 'source_id', 'document_id', 'id') as $key) {
			if (isset($value[$key]) && is_numeric($value[$key])) {
				$objectId = max(0, (int) $value[$key]);
				break;
			}
		}
		$chunkRef = '';
		foreach (array('chunk_ref', 'chunk_id', 'ref', 'source_ref') as $key) {
			if (!empty($value[$key]) && is_scalar($value[$key])) {
				$chunkRef = $this->_truncate360Field($value[$key], 64);
				break;
			}
		}
		$score = null;
		foreach (array('score_x1000', 'best_score_x1000') as $key) {
			if (isset($value[$key]) && is_numeric($value[$key])) {
				$score = max(0, min(1000, (int) $value[$key]));
				break;
			}
		}
		if (null === $score && isset($value['score']) && is_numeric($value['score'])) {
			$rawScore = (float) $value['score'];
			$score = $rawScore <= 1 ? (int) round(max(0, $rawScore) * 1000) : max(0, min(1000, (int) round($rawScore)));
		}
		if (0 === $objectId && '' === $chunkRef && null === $score) {
			return false;
		}
		$objectType = !empty($value['object_type']) && is_scalar($value['object_type']) ? sanitize_key((string) $value['object_type']) : 'kb';
		return array(
			'object_type' => '' === $objectType ? 'kb' : $objectType,
			'object_id' => $objectId,
			'chunk_ref' => $chunkRef,
			'score_x1000' => $score,
		);
	}

	private function _scoreFromKbRef( $ref, $fallback ) {
		$score = WaicUtils::getArrayValue($ref, 'score_x1000', null);
		if (null === $score || '' === $score) {
			return null === $fallback ? null : max(0, min(1000, (int) $fallback));
		}
		return max(0, min(1000, (int) $score));
	}

	private function _kbHealth( $item ) {
		$retrieved = max(0, (int) WaicUtils::getArrayValue($item, 'retrieved_count', 0, 1));
		$used = max(0, (int) WaicUtils::getArrayValue($item, 'used_count', 0, 1));
		$reask = max(0, (int) WaicUtils::getArrayValue($item, 'reask_after_count', 0, 1));
		$samples = max(0, (int) WaicUtils::getArrayValue($item, 'score_samples', 0, 1));
		$scoreSum = max(0, (int) WaicUtils::getArrayValue($item, 'score_sum_x1000', 0, 1));
		$avgScore = $samples > 0 ? ($scoreSum / $samples) : null;
		$usedPct = $retrieved > 0 ? ($used / $retrieved) : 0;
		$reaskRate = $retrieved > 0 ? ($reask / $retrieved) : 0;
		$minSamples = max(1, (int) apply_filters('waic_insights_360_kb_health_min_samples', 5));
		if (null !== $avgScore && $avgScore < 650) {
			return 'low_score';
		}
		if ($retrieved >= $minSamples && $reaskRate >= 0.30) {
			return 'stale';
		}
		if ($retrieved >= $minSamples && $usedPct < 0.25) {
			return 'weak';
		}
		if ($retrieved >= $minSamples && $usedPct >= 0.50 && $reaskRate < 0.15) {
			return 'helpful';
		}
		return 'unknown';
	}

	private function _touchInsights360Freshness( $processed ) {
		update_option('waic_insights_360_last_rollup_at', gmdate('Y-m-d H:i:s'), false);
		$dataThrough = WaicDb::get("SELECT MAX(day) FROM `@__insight_events`", 'one');
		if (!empty($dataThrough)) {
			update_option('waic_insights_360_data_through', sanitize_text_field($dataThrough), false);
		}
		if ((int) $processed > 0) {
			$this->_invalidateInsights360Cache();
		}
	}

	private function _invalidateInsights360Cache() {
		$version = max(1, (int) get_option('waic_insights_360_cache_ver', 1));
		update_option('waic_insights_360_cache_ver', $version + 1, false);
	}

	private function _decodeHistoryMeta( $meta ) {
		if (is_array($meta)) {
			return $meta;
		}
		$meta = is_scalar($meta) ? trim((string) $meta) : '';
		if ('' === $meta) {
			return array();
		}
		$decoded = json_decode($meta, true);
		return is_array($decoded) ? $decoded : array();
	}

	private function _historyMetaHas( $meta, $keys ) {
		foreach ((array) $keys as $key) {
			if (isset($meta[$key]) && !empty($meta[$key])) {
				return true;
			}
		}
		return false;
	}

	private function _historyMetaHasZeroResult( $meta ) {
		foreach (array('zero_result', 'zero_results', 'no_results') as $key) {
			if (!empty($meta[$key])) {
				return true;
			}
		}
		foreach (array('result_count', 'results_count', 'matches_count') as $key) {
			if (array_key_exists($key, $meta) && 0 === (int) $meta[$key]) {
				return true;
			}
		}
		return false;
	}

	private function _historyMetaHasCommerceIntent( $meta, $row = array() ) {
		if ($this->_historyMetaHas($meta, array('cart_event', 'add_to_cart', 'checkout', 'product_id', 'product_ids', 'products', 'sku', 'skus'))) {
			return true;
		}
		foreach (array('tool_names', 'tools', 'tool') as $key) {
			if (empty($meta[$key])) {
				continue;
			}
			$tools = is_array($meta[$key]) ? $meta[$key] : preg_split('/[\s,]+/', (string) $meta[$key]);
			foreach ((array) $tools as $tool) {
				$tool = sanitize_key((string) $tool);
				if ('' !== $tool && (false !== strpos($tool, 'product') || false !== strpos($tool, 'woocommerce') || 0 === strpos($tool, 'wc_'))) {
					return true;
				}
			}
		}
		$feature = sanitize_key((string) WaicUtils::getArrayValue($row, 'feature', ''));
		$operation = sanitize_key((string) WaicUtils::getArrayValue($row, 'operation', ''));
		return in_array($feature, array('woocommerce', 'products', 'product'), true) || in_array($operation, array('product_search', 'search_products'), true);
	}

	private function _normalizedTopicFromLogs( $logs ) {
		$text = '';
		foreach ((array) $logs as $log) {
			$question = trim((string) WaicUtils::getArrayValue($log, 'question', ''));
			if ('' !== $question) {
				$text = $question;
				break;
			}
		}
		if ('' === $text) {
			return '';
		}
		$text = wp_strip_all_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
		$text = preg_replace('/[^\p{L}\s]+/u', ' ', $text);
		$tokens = preg_split('/\s+/u', trim($text));
		$stop = array_fill_keys(array('the', 'and', 'for', 'with', 'this', 'that', 'from', 'what', 'where', 'when', 'how', 'why', 'can', 'you', 'please', 'about', 'have', 'has', 'are', 'is', 'to', 'of', 'in', 'on', 'a', 'an'), true);
		$out = array();
		foreach ((array) $tokens as $token) {
			if (WaicUtils::mbstrlen($token) < 3 || isset($stop[$token])) {
				continue;
			}
			$out[$token] = 1;
		}
		$out = array_keys($out);
		sort($out);
		return implode(' ', array_slice($out, 0, 12));
	}

	private function _textHasAnyPhrase( $text, $phrases ) {
		$text = function_exists('mb_strtolower') ? mb_strtolower((string) $text, 'UTF-8') : strtolower((string) $text);
		foreach ((array) $phrases as $phrase) {
			$phrase = function_exists('mb_strtolower') ? mb_strtolower((string) $phrase, 'UTF-8') : strtolower((string) $phrase);
			if ('' !== $phrase && false !== strpos($text, $phrase)) {
				return true;
			}
		}
		return false;
	}

	private function _fallbackPhrases() {
		return apply_filters('waic_insights_360_fallback_phrases', array(
			"i don't know",
			'i do not know',
			"i'm not sure",
			'i am not sure',
			"don't have that information",
			'do not have that information',
			'cannot answer',
			"can't answer",
			'no information',
		));
	}

	private function _negativePhrases() {
		return apply_filters('waic_insights_360_negative_phrases', array(
			'not what i meant',
			"doesn't help",
			'does not help',
			'wrong',
			'incorrect',
			'not helpful',
			'try again',
		));
	}

	private function _handoffPhrases() {
		return apply_filters('waic_insights_360_handoff_phrases', array(
			'contact support',
			'contact us',
			'human agent',
			'talk to a human',
			'speak to a human',
			'representative',
			'operator',
			'support team',
		));
	}

	private function _truncate360Field( $value, $limit ) {
		$value = trim(sanitize_text_field((string) $value));
		return WaicUtils::mbsubstr($value, 0, (int) $limit);
	}

	private function _normalizeUsageFilters( $params, $forList = false ) {
		$defaults = $this->getDefaultFilters();
		$dateFrom = WaicUtils::getArrayValue($params, 'date_from', WaicUtils::getArrayValue($params, 'from', $defaults['date_from']));
		$dateTo = WaicUtils::getArrayValue($params, 'date_to', WaicUtils::getArrayValue($params, 'to', $defaults['date_to']));
		if (!WaicUtils::checkDateTimeFormat($dateFrom, 'Y-m-d') || !WaicUtils::checkDateTimeFormat($dateTo, 'Y-m-d')) {
			$this->pushError(esc_html__('Invalid date range.', 'ai-copilot-content-generator'));
			return false;
		}
		$fromTs = strtotime($dateFrom . ' 00:00:00');
		$toTs = strtotime($dateTo . ' 23:59:59');
		if ($fromTs > $toTs) {
			$this->pushError(esc_html__('The start date must be before the end date.', 'ai-copilot-content-generator'));
			return false;
		}
		$rangeLimit = $this->_rangeLimitDays();
		$maxSeconds = $rangeLimit * DAY_IN_SECONDS;
		if (($toTs - $fromTs) > $maxSeconds) {
			$fromTs = $toTs - $maxSeconds + DAY_IN_SECONDS;
			$dateFrom = gmdate('Y-m-d', $fromTs);
		}

		$feature = $this->_normalizeFilterValue(WaicUtils::getArrayValue($params, 'feature', $defaults['feature']));
		$engine = $this->_normalizeFilterValue(WaicUtils::getArrayValue($params, 'engine', $defaults['engine']));
		$model = $this->_normalizeFilterValue(WaicUtils::getArrayValue($params, 'model', $defaults['model']));
		$operation = $this->_normalizeFilterValue(WaicUtils::getArrayValue($params, 'operation', $defaults['operation']));
		$groupBy = sanitize_key((string) WaicUtils::getArrayValue($params, 'group_by', $defaults['group_by']));
		if (!in_array($groupBy, array('feature', 'engine', 'model', 'day'), true)) {
			$groupBy = 'feature';
		}
		if (!$this->_isPro() && 'feature' !== $groupBy) {
			$groupBy = 'feature';
		}

		$mode = (string) WaicUtils::getArrayValue($params, 'mode', $defaults['mode']);
		if (!in_array($mode, array('all', '0', '1'), true)) {
			$mode = '0';
		}
		$status = (string) WaicUtils::getArrayValue($params, 'status', $defaults['status']);
		if (!in_array($status, array('all', '0', '1', '2', '3'), true)) {
			$status = 'all';
		}

		$filters = array(
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'date_from_db' => $dateFrom . ' 00:00:00',
			'date_to_db' => $dateTo . ' 23:59:59',
			'feature' => $feature,
			'engine' => $engine,
			'model' => $model,
			'operation' => $operation,
			'group_by' => $groupBy,
			'mode' => $mode,
			'status' => $status,
			'task_id' => max(0, (int) WaicUtils::getArrayValue($params, 'task_id', 0, 1)),
		);
		$sessionId = $this->_normalizeSessionId(WaicUtils::getArrayValue($params, 'session_id', ''));
		if ('' !== $sessionId) {
			$filters['session_id'] = $sessionId;
		}
		if ($forList) {
			$page = max(1, (int) WaicUtils::getArrayValue($params, 'page_num', WaicUtils::getArrayValue($params, 'page', 1, 1), 1));
			$perPage = (int) WaicUtils::getArrayValue($params, 'per_page', $this->_defaultPerPage, 1);
			$filters['page'] = $page;
			$filters['per_page'] = min($this->_maxPerPage, max(1, $perPage));
			$filters['offset'] = ($page - 1) * $filters['per_page'];
		}
		return $filters;
	}

	private function _buildDailyWhere( $filters, $skipKeys = array() ) {
		$where = array('d.day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		$skip = array_fill_keys((array) $skipKeys, true);
		foreach (array('feature', 'engine', 'model', 'operation') as $key) {
			if (empty($skip[$key]) && !empty($filters[$key]) && 'all' !== $filters[$key]) {
				$where[] = 'd.' . $key . '=%s';
				$args[] = $filters[$key];
			}
		}
		if ('0' === $filters['mode'] || '1' === $filters['mode']) {
			$where[] = 'd.mode=%d';
			$args[] = (int) $filters['mode'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _buildSessionsWhere( $filters ) {
		$where = array('s.day BETWEEN %s AND %s');
		$args = array($filters['date_from'], $filters['date_to']);
		if (!empty($filters['feature']) && 'all' !== $filters['feature']) {
			$where[] = 's.feature=%s';
			$args[] = $filters['feature'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _buildUsageHistoryWhere( $filters ) {
		$where = array('h.created BETWEEN %s AND %s');
		$args = array($filters['date_from_db'], $filters['date_to_db']);
		foreach (array('feature', 'engine', 'model', 'operation') as $key) {
			if (!empty($filters[$key]) && 'all' !== $filters[$key]) {
				$where[] = 'h.' . $key . '=%s';
				$args[] = $filters[$key];
			}
		}
		if (!empty($filters['task_id'])) {
			$where[] = 'h.task_id=%d';
			$args[] = (int) $filters['task_id'];
		}
		if (!empty($filters['session_id'])) {
			$where[] = 'h.session_id=%s';
			$args[] = $filters['session_id'];
		}
		if ('0' === $filters['mode'] || '1' === $filters['mode']) {
			$where[] = 'h.mode=%d';
			$args[] = (int) $filters['mode'];
		}
		if ('all' !== $filters['status']) {
			$where[] = 'h.status=%d';
			$args[] = (int) $filters['status'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _distinctDailyValues( $column, $where, $args ) {
		$column = $this->_usageGroupColumn($column);
		$rows = WaicDb::get(
			"SELECT DISTINCT COALESCE(NULLIF(d.{$column}, ''), 'unknown') AS value
			FROM `@__history_daily` d" . $where . "
			ORDER BY value ASC",
			'col',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$out[] = sanitize_text_field($row);
			}
		}
		return $out;
	}

	private function _getSessionCount( $filters ) {
		if ('1' === $filters['mode']) {
			return 0;
		}
		list($where, $args) = $this->_buildSessionsWhere($filters);
		return (int) WaicDb::get(
			"SELECT COUNT(DISTINCT CONCAT(s.feature, ':', s.session_id)) FROM `@__sessions_daily` s" . $where,
			'one',
			ARRAY_A,
			$args
		);
	}

	private function _getSessionsByFeature( $filters ) {
		if ('1' === $filters['mode']) {
			return array();
		}
		list($where, $args) = $this->_buildSessionsWhere($filters);
		$rows = WaicDb::get(
			"SELECT s.feature, COUNT(DISTINCT s.session_id) AS sessions
			FROM `@__sessions_daily` s" . $where . "
			GROUP BY s.feature",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$out[sanitize_key($row['feature'])] = (int) $row['sessions'];
			}
		}
		return $out;
	}

	private function _getDailyCostTrend( $filters ) {
		list($where, $args) = $this->_buildDailyWhere($filters);
		$rows = WaicDb::get(
			"SELECT d.day,
				COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd,
				COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
				COALESCE(SUM(d.events_count), 0) AS events_count
			FROM `@__history_daily` d" . $where . "
			GROUP BY d.day ORDER BY d.day ASC",
			'all',
			ARRAY_A,
			$args
		);
		return $this->_prepareChartRows($rows);
	}

	private function _getFeatureBreakdown( $filters ) {
		$copy = $filters;
		$copy['group_by'] = 'feature';
		list($where, $args) = $this->_buildDailyWhere($copy);
		$rows = WaicDb::get(
			"SELECT COALESCE(NULLIF(d.feature, ''), 'unknown') AS group_key,
				COALESCE(SUM(d.events_count), 0) AS events_count,
				COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
				COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd
			FROM `@__history_daily` d" . $where . "
			GROUP BY group_key ORDER BY cost_micro_usd DESC, events_count DESC LIMIT 6",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$out[] = $this->_prepareUsageSummaryRow($row, 'feature', null);
			}
		}
		return $out;
	}

	private function _getProviderBreakdown( $filters ) {
		list($where, $args) = $this->_buildDailyWhere($filters);
		$rows = WaicDb::get(
			"SELECT COALESCE(NULLIF(d.engine, ''), 'unknown') AS engine,
				COALESCE(NULLIF(d.model, ''), 'unknown') AS model,
				COALESCE(SUM(d.total_tokens), 0) AS total_tokens,
				COALESCE(SUM(d.cost_micro_usd), 0) AS cost_micro_usd
			FROM `@__history_daily` d" . $where . "
			GROUP BY engine, model ORDER BY cost_micro_usd DESC, total_tokens DESC LIMIT 10",
			'all',
			ARRAY_A,
			$args
		);
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$display = $this->_formatDisplayCostMicro($row['cost_micro_usd']);
				$out[] = array(
					'engine' => sanitize_text_field($row['engine']),
					'model' => sanitize_text_field($row['model']),
					'tokens' => (int) $row['total_tokens'],
					'cost_micro_usd' => (int) $row['cost_micro_usd'],
					'cost' => $this->_formatCostMicro($row['cost_micro_usd']),
					'cost_display' => $display['formatted'],
					'cost_display_amount' => $display['amount'],
				);
			}
		}
		return $out;
	}

	private function _getAttentionItems( $kpis ) {
		$items = array();
		if (!empty($kpis['aborted'])) {
			$items[] = array(
				'type' => 'aborted',
				'label' => sprintf(esc_html__('%d aborted AI calls need review.', 'ai-copilot-content-generator'), (int) $kpis['aborted']),
			);
		}
		if (!empty($kpis['errors'])) {
			$items[] = array(
				'type' => 'errors',
				'label' => sprintf(esc_html__('%d AI events ended with an error.', 'ai-copilot-content-generator'), (int) $kpis['errors']),
			);
		}
		if (!empty($kpis['low_conf'])) {
			$items[] = array(
				'type' => 'low_conf',
				'label' => sprintf(esc_html__('%d low-confidence retrieval events found.', 'ai-copilot-content-generator'), (int) $kpis['low_conf']),
			);
		}
		if (!empty($kpis['tokens_est']) || !empty($kpis['cost_est'])) {
			$items[] = array(
				'type' => 'estimated',
				'label' => esc_html__('Some usage or cost values are estimated.', 'ai-copilot-content-generator'),
			);
		}
		if (empty($items)) {
			$items[] = array(
				'type' => 'ok',
				'label' => esc_html__('No urgent usage anomalies in this period.', 'ai-copilot-content-generator'),
			);
		}
		return $items;
	}

	private function _prepareChartRows( $rows ) {
		$out = array();
		if ($rows) {
			foreach ($rows as $row) {
				$costMicro = (int) WaicUtils::getArrayValue($row, 'cost_micro_usd', 0, 1);
				$display = $this->_formatDisplayCostMicro($costMicro);
				$out[] = array(
					'day' => sanitize_text_field($row['day']),
					'group' => sanitize_text_field(WaicUtils::getArrayValue($row, 'group_key', 'total')),
					'events_count' => (int) WaicUtils::getArrayValue($row, 'events_count', 0, 1),
					'total_tokens' => (int) WaicUtils::getArrayValue($row, 'total_tokens', 0, 1),
					'cost_micro_usd' => $costMicro,
					'cost' => $this->_formatCostMicro($costMicro),
					'cost_display' => $display['formatted'],
					'cost_display_amount' => $display['amount'],
				);
			}
		}
		return $out;
	}

	private function _prepareUsageSummaryRow( $row, $groupBy, $sessions ) {
		$costMicro = (int) WaicUtils::getArrayValue($row, 'cost_micro_usd', 0, 1);
		$key = sanitize_text_field(WaicUtils::getArrayValue($row, 'group_key', 'unknown'));
		$display = $this->_formatDisplayCostMicro($costMicro);
		return array(
			'group_by' => $groupBy,
			'key' => $key,
			'label' => $this->_usageLabel($groupBy, $key),
			'events_count' => (int) WaicUtils::getArrayValue($row, 'events_count', 0, 1),
			'sessions' => is_null($sessions) ? null : (int) $sessions,
			'errors_count' => (int) WaicUtils::getArrayValue($row, 'errors_count', 0, 1),
			'low_conf_count' => (int) WaicUtils::getArrayValue($row, 'low_conf_count', 0, 1),
			'input_tokens' => (int) WaicUtils::getArrayValue($row, 'input_tokens', 0, 1),
			'output_tokens' => (int) WaicUtils::getArrayValue($row, 'output_tokens', 0, 1),
			'reasoning_tokens' => (int) WaicUtils::getArrayValue($row, 'reasoning_tokens', 0, 1),
			'cached_tokens' => (int) WaicUtils::getArrayValue($row, 'cached_tokens', 0, 1),
			'cache_write_tokens' => (int) WaicUtils::getArrayValue($row, 'cache_write_tokens', 0, 1),
			'total_tokens' => (int) WaicUtils::getArrayValue($row, 'total_tokens', 0, 1),
			'tool_calls_count' => (int) WaicUtils::getArrayValue($row, 'tool_calls_count', 0, 1),
			'cost_micro_usd' => $costMicro,
			'cost' => $this->_formatCostMicro($costMicro),
			'cost_display' => $display['formatted'],
			'cost_display_amount' => $display['amount'],
			'drill' => true,
		);
	}

	private function _prepareUsageEventRow( $row ) {
		$costMicro = (int) WaicUtils::getArrayValue($row, 'cost_micro_usd', 0, 1);
		$display = $this->_formatDisplayCostMicro($costMicro);
		return array(
			'id' => (int) $row['id'],
			'created' => sanitize_text_field($row['created']),
			'task_id' => (int) $row['task_id'],
			'feature' => sanitize_key($row['feature']),
			'operation' => sanitize_key($row['operation']),
			'session_id' => $this->_normalizeSessionId($row['session_id']),
			'user_id' => (int) $row['user_id'],
			'engine' => sanitize_text_field($row['engine']),
			'model' => sanitize_text_field($row['model']),
			'mode' => (int) $row['mode'],
			'status' => (int) $row['status'],
			'duration_ms' => is_null($row['duration_ms']) ? null : (int) $row['duration_ms'],
			'input_tokens' => (int) $row['input_tokens'],
			'output_tokens' => (int) $row['output_tokens'],
			'reasoning_tokens' => (int) $row['reasoning_tokens'],
			'cached_tokens' => (int) $row['cached_tokens'],
			'cache_write_tokens' => (int) $row['cache_write_tokens'],
			'total_tokens' => (int) $row['tokens'],
			'cost_micro_usd' => $costMicro,
			'cost' => $this->_formatCostMicro($costMicro),
			'cost_display' => $display['formatted'],
			'cost_display_amount' => $display['amount'],
			'est_flags' => (int) $row['est_flags'],
			'tool_calls_count' => (int) $row['tool_calls_count'],
		);
	}

	private function _emptyUsageTotals() {
		return array(
			'events_count' => 0,
			'total_tokens' => 0,
			'cost_micro_usd' => 0,
			'sessions' => 0,
		);
	}

	private function _addUsageTotals( $totals, $item ) {
		$totals['events_count'] += (int) WaicUtils::getArrayValue($item, 'events_count', 0, 1);
		$totals['total_tokens'] += (int) WaicUtils::getArrayValue($item, 'total_tokens', 0, 1);
		$totals['cost_micro_usd'] += (int) WaicUtils::getArrayValue($item, 'cost_micro_usd', 0, 1);
		if (!is_null(WaicUtils::getArrayValue($item, 'sessions', null))) {
			$totals['sessions'] += (int) WaicUtils::getArrayValue($item, 'sessions', 0, 1);
		}
		return $totals;
	}

	private function _finalizeUsageTotals( $totals ) {
		$totals['cost'] = $this->_formatCostMicro($totals['cost_micro_usd']);
		$display = $this->_formatDisplayCostMicro($totals['cost_micro_usd']);
		$totals['cost_display'] = $display['formatted'];
		$totals['cost_display_amount'] = $display['amount'];
		return $totals;
	}

	private function _normalizePathFilters( $params ) {
		$out = array();
		foreach (array('feature', 'engine', 'model', 'operation') as $key) {
			$value = $this->_normalizeFilterValue(WaicUtils::getArrayValue($params, $key, 'all'));
			if ('all' !== $value) {
				$out[$key] = $value;
			}
		}
		return $out;
	}

	private function _drillSequence( $groupBy ) {
		$sequences = array(
			'feature' => array('feature', 'engine', 'model'),
			'engine' => array('engine', 'model', 'feature'),
			'model' => array('model', 'feature'),
			'day' => array('day', 'feature', 'model'),
		);
		return isset($sequences[$groupBy]) ? $sequences[$groupBy] : $sequences['feature'];
	}

	private function _usageGroupColumn( $groupBy ) {
		$map = array(
			'feature' => 'feature',
			'engine' => 'engine',
			'model' => 'model',
			'operation' => 'operation',
			'day' => 'day',
		);
		$groupBy = sanitize_key((string) $groupBy);
		return isset($map[$groupBy]) ? $map[$groupBy] : 'feature';
	}

	private function _normalizeFilterValue( $value ) {
		$value = trim(sanitize_text_field((string) $value));
		if ('' === $value || '*' === $value) {
			return 'all';
		}
		return substr($value, 0, 80);
	}

	private function _normalizeSessionId( $sessionId ) {
		$sessionId = trim(sanitize_text_field((string) $sessionId));
		return '' === $sessionId ? '' : substr($sessionId, 0, 64);
	}

	private function _rangeDays( $filters ) {
		$fromTs = strtotime($filters['date_from'] . ' 00:00:00');
		$toTs = strtotime($filters['date_to'] . ' 23:59:59');
		return max(1, (int) ceil(($toTs - $fromTs + 1) / DAY_IN_SECONDS));
	}

	private function _rangeLimitDays() {
		return $this->_isPro() ? $this->_proRangeDays : $this->_freeRangeDays;
	}

	private function _isPro() {
		return WaicFrame::_()->isPro();
	}

	private function _usageLabel( $groupBy, $value ) {
		if ('day' === $groupBy) {
			return $value;
		}
		if ('' === $value || 'unknown' === $value) {
			return esc_html__('Unknown', 'ai-copilot-content-generator');
		}
		return $value;
	}

	private function _normalizeFilters( $params, $forList = false ) {
		$defaults = $this->getDefaultFilters();
		$dateFrom = WaicUtils::getArrayValue($params, 'date_from', $defaults['date_from']);
		$dateTo = WaicUtils::getArrayValue($params, 'date_to', $defaults['date_to']);
		if (!WaicUtils::checkDateTimeFormat($dateFrom, 'Y-m-d') || !WaicUtils::checkDateTimeFormat($dateTo, 'Y-m-d')) {
			$this->pushError(esc_html__('Invalid date range.', 'ai-copilot-content-generator'));
			return false;
		}
		$fromTs = strtotime($dateFrom . ' 00:00:00');
		$toTs = strtotime($dateTo . ' 23:59:59');
		if ($fromTs > $toTs) {
			$this->pushError(esc_html__('The start date must be before the end date.', 'ai-copilot-content-generator'));
			return false;
		}
		$maxSeconds = $this->_rangeLimitDays() * DAY_IN_SECONDS;
		if (($toTs - $fromTs) > $maxSeconds) {
			$fromTs = $toTs - $maxSeconds + DAY_IN_SECONDS;
			$dateFrom = gmdate('Y-m-d', $fromTs);
		}

		$mode = (string) WaicUtils::getArrayValue($params, 'mode', $defaults['mode']);
		if (!in_array($mode, array('all', '0', '1', 'unknown'), true)) {
			$mode = '0';
		}
		$status = (string) WaicUtils::getArrayValue($params, 'status', $defaults['status']);
		if (!in_array($status, array('all', '0', '1', '2', '3'), true)) {
			$status = 'all';
		}

		$filters = array(
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'date_from_db' => $dateFrom . ' 00:00:00',
			'date_to_db' => $dateTo . ' 23:59:59',
			'task_id' => max(0, (int) WaicUtils::getArrayValue($params, 'task_id', 0, 1)),
			'mode' => $mode,
			'status' => $status,
		);
		if ($forList) {
			$page = max(1, (int) WaicUtils::getArrayValue($params, 'page_num', WaicUtils::getArrayValue($params, 'page', 1, 1), 1));
			$perPage = (int) WaicUtils::getArrayValue($params, 'per_page', $this->_defaultPerPage, 1);
			$filters['page'] = $page;
			$filters['per_page'] = min($this->_maxPerPage, max(1, $perPage));
		}
		return $filters;
	}

	private function _buildHistoryWhere( $filters ) {
		$where = array('h.feature=%s', 'h.created BETWEEN %s AND %s');
		$args = array('chatbots', $filters['date_from_db'], $filters['date_to_db']);
		if (!empty($filters['task_id'])) {
			$where[] = 'h.task_id=%d';
			$args[] = (int) $filters['task_id'];
		}
		if ('0' === $filters['mode'] || '1' === $filters['mode']) {
			$where[] = 'h.mode=%d';
			$args[] = (int) $filters['mode'];
		} elseif ('unknown' === $filters['mode']) {
			$where[] = 'h.mode NOT IN (0, 1)';
		}
		if ('all' !== $filters['status']) {
			$where[] = 'h.status=%d';
			$args[] = (int) $filters['status'];
		}
		return array(' WHERE ' . implode(' AND ', $where), $args);
	}

	private function _getLogsForRows( $rows ) {
		$ids = array();
		if ($rows) {
			foreach ($rows as $row) {
				$ids[] = (int) $row['id'];
			}
		}
		$ids = array_values(array_filter(array_unique($ids)));
		if (empty($ids)) {
			return array();
		}
		$placeholders = WaicDb::placeholders($ids, '%d');
		$logs = WaicDb::get(
			"SELECT id, his_id, question, answer, file, status FROM `@__chatlogs`
			WHERE status != 9 AND his_id IN (" . $placeholders . ")
			ORDER BY his_id ASC, id ASC",
			'all',
			ARRAY_A,
			$ids
		);
		$out = array();
		if ($logs) {
			foreach ($logs as $log) {
				$hisId = (int) $log['his_id'];
				if (!isset($out[$hisId])) {
					$out[$hisId] = array();
				}
				$out[$hisId][] = $log;
			}
		}
		return $out;
	}

	private function _prepareConversationRow( $row, $logs, $detail ) {
		$conversationId = (int) $row['id'];
		$context = array('user_id' => (int) $row['user_id']);
		$anonymizer = $this->getModule()->getController()->getModel('anonymizer');
		$messageCount = 0;
		$snippet = '';
		$hasMask = false;
		$messages = array();
		$recommendations = array();

		foreach ($logs as $log) {
			$question = $anonymizer->maskText($log['question'], $context, $detail ? $this->_modalMessageLimit : $this->_snippetLimit);
			$answer = $anonymizer->maskText($this->_stripRecommendationMarkers($log['answer']), $context, $detail ? $this->_modalMessageLimit : $this->_snippetLimit);
			$file = $anonymizer->maskAttachment($log['file']);
			$recommendations = array_merge($recommendations, $this->_parseRecommendationMarkers($log['answer']));
			if ('' !== $question) {
				$messageCount++;
				if ('' === $snippet) {
					$snippet = $question;
				}
				if ($detail) {
					$messages[] = array('role' => 'user', 'text' => $question);
				}
			}
			if ('' !== $file) {
				if ('' === $snippet) {
					$snippet = $file;
				}
				if ($detail) {
					$messages[] = array('role' => 'user', 'text' => $file);
				}
			}
			if ('' !== $answer) {
				$messageCount++;
				if ('' === $snippet) {
					$snippet = $answer;
				}
				if ($detail) {
					$messages[] = array('role' => 'assistant', 'text' => $answer);
				}
			}
			$hasMask = $hasMask || $anonymizer->hasMask($question . ' ' . $answer . ' ' . $file);
		}
		if ('' === $snippet) {
			$snippet = esc_html__('No message content available.', 'ai-copilot-content-generator');
		}
		$costMicro = (int) WaicUtils::getArrayValue($row, 'cost_micro_usd', 0, 1);
		$display = $this->_formatDisplayCostMicro($costMicro);
		$data = array(
			'conversation_id' => $conversationId,
			'task_id' => (int) $row['task_id'],
			'title' => $this->_safeTitle($row['title'], (int) $row['task_id']),
			'created' => sanitize_text_field($row['created']),
			'mode' => $this->_modeKey($row['mode']),
			'mode_label' => $this->_getLabel('mode_key', $this->_modeKey($row['mode'])),
			'status' => (int) $row['status'],
			'status_label' => $this->_getLabel('status_key', $row['status']),
			'tokens' => (int) $row['tokens'],
			'cost' => $this->_formatCostMicro($costMicro),
			'cost_display' => $display['formatted'],
			'cost_display_amount' => $display['amount'],
			'messages_count' => $messageCount,
			'user_label' => empty($row['user_id']) ? esc_html__('Guest', 'ai-copilot-content-generator') : esc_html__('Registered user', 'ai-copilot-content-generator'),
			'snippet' => $detail ? $anonymizer->truncate($snippet, $this->_snippetLimit) : $snippet,
			'has_masked_data' => $hasMask,
			'recommendations' => $this->_uniqueRecommendations($recommendations),
		);
		if ($detail) {
			$data['messages'] = $messages;
		}
		return $data;
	}

	private function _parseRecommendationMarkers( $answer ) {
		$out = array();
		if (!preg_match_all('/##IDS##(prod|post):([0-9,\s]+)/', (string) $answer, $matches, PREG_SET_ORDER)) {
			return $out;
		}
		foreach ($matches as $match) {
			$ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', trim($match[2]))));
			foreach ($ids as $id) {
				$out[] = array('type' => $match[1], 'id' => $id);
			}
		}
		return $out;
	}

	private function _stripRecommendationMarkers( $answer ) {
		return preg_replace('/##IDS##[a-z]+:[^\s<]+/i', '', (string) $answer);
	}

	private function _uniqueRecommendations( $items ) {
		$seen = array();
		$out = array();
		foreach ($items as $item) {
			$key = $item['type'] . ':' . $item['id'];
			if (!isset($seen[$key])) {
				$seen[$key] = 1;
				$out[] = $item;
			}
		}
		return $out;
	}

	private function _safeTitle( $title, $fallbackId = 0 ) {
		$title = trim(wp_strip_all_tags(html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
		if ('' === $title) {
			$title = $fallbackId > 0
				? sprintf(esc_html__('Chatbot #%d', 'ai-copilot-content-generator'), $fallbackId)
				: esc_html__('Unknown chatbot', 'ai-copilot-content-generator');
		}
		return $title;
	}

	private function _formatCost( $cost ) {
		return number_format((float) $cost, 4, '.', '');
	}

	private function _formatCostMicro( $costMicro ) {
		return $this->_formatCost(((int) $costMicro) / 1000000);
	}

	private function _formatDisplayCostMicro( $costMicro, $precision = 4 ) {
		return WaicFrame::_()->getModule('insights')->getModel('pricing')->formatMicroUsd($costMicro, $precision);
	}

	private function _modeKey( $mode ) {
		$mode = (string) $mode;
		if ('0' === $mode || 0 === $mode) {
			return 'real';
		}
		if ('1' === $mode || 1 === $mode) {
			return 'preview';
		}
		return 'unknown';
	}

	private function _getLabel( $field, $value ) {
		if ('mode_key' === $field) {
			$labels = array(
				'real' => esc_html__('Real shopper chats', 'ai-copilot-content-generator'),
				'preview' => esc_html__('Preview/admin tests', 'ai-copilot-content-generator'),
				'unknown' => esc_html__('Unknown mode', 'ai-copilot-content-generator'),
			);
			return isset($labels[$value]) ? $labels[$value] : $labels['unknown'];
		}
		$statuses = array(
			0 => esc_html__('OK', 'ai-copilot-content-generator'),
			1 => esc_html__('AI Error', 'ai-copilot-content-generator'),
			2 => esc_html__('Plugin Error', 'ai-copilot-content-generator'),
			3 => esc_html__('Plugin Data', 'ai-copilot-content-generator'),
		);
		$value = (int) $value;
		return isset($statuses[$value]) ? $statuses[$value] : esc_html__('Unknown status', 'ai-copilot-content-generator');
	}

	private function _publicFilters( $filters ) {
		return array(
			'date_from' => $filters['date_from'],
			'date_to' => $filters['date_to'],
			'feature' => isset($filters['feature']) ? $filters['feature'] : 'all',
			'engine' => isset($filters['engine']) ? $filters['engine'] : 'all',
			'model' => isset($filters['model']) ? $filters['model'] : 'all',
			'operation' => isset($filters['operation']) ? $filters['operation'] : 'all',
			'group_by' => isset($filters['group_by']) ? $filters['group_by'] : 'feature',
			'task_id' => (int) $filters['task_id'],
			'mode' => $filters['mode'],
			'status' => $filters['status'],
		);
	}
}
