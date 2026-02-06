<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicFormsModel extends WaicModel {
	private $_fields = null;
	
	public function __construct() {
		$this->_setTbl('formlogs');
	}
	public function isDeleteByCancel() {
		return false;
	}
	public function canUnpublish() {
		return true;
	}
	public function unpublishEtaps( $taskId ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 6));
		return false;
	}
	public function publishResults( $taskId, $publish ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 4));
		return false;
	}
	public function clearEtaps( $taskId, $ids = false, $withContent = true ) {
		/*if ($withContent) {
			WaicFrame::_()->getModule('workspace')->getModel('history')->deleteHistory($taskId);
			$query = 'DELETE t FROM @__chatlogs t WHERE NOT EXISTS(SELECT 1 FROM @__history h WHERE h.id=t.his_id)';
			WaicDb::query($query);
		}*/
		return true;
	}
	public function createTable() {
		if (!WaicDb::exist('@__formlogs')) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__formlogs` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`his_id` INT NOT NULL DEFAULT 0,
				`question` MEDIUMTEXT NOT NULL,
				`answer` MEDIUMTEXT NOT NULL,
				`file` MEDIUMTEXT NOT NULL,
				PRIMARY KEY (`id`),
				INDEX `his_id` (`his_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
	}
	public function getFields() {
		if (is_null($this->_fields)) {
			$this->_fields = array(
				'email' => __('Email', 'ai-copilot-content-generator'),
				'input' => __('Input', 'ai-copilot-content-generator'),
				'textarea' => __('Textarea', 'ai-copilot-content-generator'),
				'number' => __('Number', 'ai-copilot-content-generator'),
				'date' => __('Date', 'ai-copilot-content-generator'),
				'dropdown' => __('Select (Dropdown)', 'ai-copilot-content-generator'),
				'checkboxes' => __('Checkboxes', 'ai-copilot-content-generator'),
				'radio' => __('Radio Buttons', 'ai-copilot-content-generator'),
				//'file' => __('File Upload', 'ai-copilot-content-generator'),
			);
		}
		return $this->_fields;
	}
	public function getDisplayOutputModes() {
		$modes = array(
			'below' => __('Below Form', 'ai-copilot-content-generator'),
			'custom' => __('Custom CSS Selector', 'ai-copilot-content-generator'),
		);
		return $modes;
	}
	public function getRepeatOutputModes() {
		$modes = array(
			'replace' => __('Replace', 'ai-copilot-content-generator'),
			'append' => __('Append', 'ai-copilot-content-generator'),
		);
		return $modes;
	}
	public function getNotificationsSendModes() {
		$modes = array(
			'before' => __('Before AI', 'ai-copilot-content-generator'),
			'after' => __('After AI success', 'ai-copilot-content-generator'),
			'error' => __('On AI error', 'ai-copilot-content-generator'),
		);
		return $modes;
	}
	public function getIfOperators() {
		$operators = array(
			'equals' => __('Equals', 'ai-copilot-content-generator'),
			'not_equals' => __('Not Equals', 'ai-copilot-content-generator'),
			'contains' => __('Contains', 'ai-copilot-content-generator'),
			'not_contains' => __('Not Contains', 'ai-copilot-content-generator'),
			'greater' => __('Greater Than', 'ai-copilot-content-generator'),
			'less' => __('Less Than', 'ai-copilot-content-generator'),
			'empty' => __('Is Empty', 'ai-copilot-content-generator'),
			'not_empty' => __('Is Not Empty', 'ai-copilot-content-generator'),
		);
		return $operators;
	}
	public function getThenActions() {
		$actions = array(
			'show' => __('Show', 'ai-copilot-content-generator'),
			'hide' => __('Hide', 'ai-copilot-content-generator'),
			'enable' => __('Enable', 'ai-copilot-content-generator'),
			'disable' => __('Disable', 'ai-copilot-content-generator'),
			'value' => __('Set value', 'ai-copilot-content-generator'),
		);
		return $actions;
	}

	public function getLabelVarsList( $fields, $label ) {
		$list = array();
		if (!empty($fields)) {
			foreach ($fields as $id => $data) {
				
				$list[$id] = $label . '_' . $id;
				if (!empty($data['title'])) {
					$len = strlen($data['title']);
					$list[$id] .= ' ' . ( $len > 10 ? WaicUtils::mbsubstr($data['title'], 0, 10) . '...' : $data['title'] );
				}
			}
		}
		return $list;
	}
	public function controlTaskParameters( $params, &$error = '', $taskId = 0 ) {
		if (!WaicFrame::_()->isPro()) {
			$forms = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTaskByParams(array('feature' => 'forms', 'additionalCondition' => 'id!=' . ((int) $taskId)), 'id');
			if ($forms && !is_null($forms)) {
				$error = __('To get access to creating more than one form, you need to purchase and receive the Pro version of the plugin.', 'ai-copilot-content-generator');
				return false;
			}
		}
		$apiParams = WaicUtils::getArrayValue($params, 'api', array(), 2);

		if (false === WaicUtils::checkEmptyApiKeys($apiParams, $error)) {
			return false;
		}

		/*$fields = WaicUtils::getArrayValue($params, 'fields', array(), 2);
		if (empty($fields)) {
			$error = __('Fields are not specified', 'ai-copilot-content-generator');
			return false;
		}
		$outputs = WaicUtils::getArrayValue($params, 'outputs', array(), 2);
		if (empty($outputs)) {
			$error = __('Outputs are not specified', 'ai-copilot-content-generator');
			return false;
		}*/
		$submits = WaicUtils::getArrayValue($params, 'submits', array(), 2);
		if (empty($submits)) {
			$error = __('Submits are not specified', 'ai-copilot-content-generator');
			return false;
		}
		return empty($error);
	}
	public function convertTaskParameters( $params, $toDB = true ) {
		$rules = WaicUtils::getArrayValue($params, 'rules', array(), 2);
		if (!empty($rules)) {
			foreach ($rules as $id => $data) {
				$ifs = WaicUtils::getArrayValue($data, 'ifs', array(), 2);
				$ifsNew = empty($ifs) ? array() : array_values($ifs);
				$rules[$id]['ifs'] = $ifsNew;
				$thens = WaicUtils::getArrayValue($data, 'thens', array(), 2);
				$thensNew = empty($thens) ? array() : array_values($thens);
				$rules[$id]['thens'] = $thensNew;
				$elses = WaicUtils::getArrayValue($data, 'elses', array(), 2);
				$elsesNew = empty($elses) ? array() : array_values($elses);
				$rules[$id]['elses'] = $elsesNew;
			}
			$params['rules'] = $rules;
		}
		return WaicDispatcher::applyFilters('convertTaskParameters', $params, $toDB);
	}
	public function preparePrompt( $prompt, $data ) {
		preg_match_all('/\$\{([^{}]+)\}/', $prompt, $selectors);
		//preg_match_all('/(?<!\$)\{([^}]+)\}/', $prompt, $fields);
		preg_match_all('/(?<!\$)\{([^{}]+)\}/', $prompt, $fields);
		
		if (!empty($selectors[1])) {
			foreach ($selectors[1] as $selector) {
				$prompt = str_replace('${' . $selector . '}', WaicUtils::getArrayValue($data, $selector), $prompt);
			}
		}
		if (!empty($fields[1])) {
			foreach ($fields[1] as $field) {
				$prompt = str_replace('{' . $field . '}', WaicUtils::getArrayValue($data, $field), $prompt);
			}
		}
		return $prompt;
	}
	public function sendForm( $taskId, $subId, $data, $files = false, $options = array() ) {
		$workspace = WaicFrame::_()->getModule('workspace');
		$task = $workspace->getModel('tasks')->getTask($taskId);
		if (!$task || empty($task)) {
			return array('answer' => __('Form not found.', 'ai-copilot-content-generator'), 'error' => 1);
		}
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		
		$file = '';
		if ($files) {
			$api = WaicUtils::getArrayValue($params, 'api', array(), 2);
			$files = $this->getFileString($files, WaicUtils::getArrayValue($api, 'max_file_size', 5, 1) * 1048576);
			if (!empty($files['error'])) {
				return array('answer' => $files['error'], 'error' => 1);
			}
			$file = $files['file'];
		}
		if (isset($options['user_id'])) {
			$user = empty($options['user_id']) ? false : get_user_by('id', (int) $options['user_id']);
		} else {
			$user = wp_get_current_user();
		}
		$userId = $user ? $user->ID : 0;
		$isGuest = empty($userId);
		$ip = isset($options['ip']) ? $options['ip'] : WaicUtils::getRealUserIp();
		
		$hisModel = $workspace->getModel('history');
	
		$submits = WaicUtils::getArrayValue($params, 'submits', array(), 2);
		$submit = WaicUtils::getArrayValue($submits, $subId, array(), 2);
		if (empty($submit)) {
			return array('answer' => __('Submit not found.', 'ai-copilot-content-generator'), 'error' => 1);
		}
		$data['FORM_ID'] = $taskId;
		$data['TIMESTAMP'] = date('Y-m-d H:i:s');
		
		$sendWebhook = WaicUtils::getArrayValue($submit, 'webhook', 0, 1) == 1;
		$webhookSend = $sendWebhook ? WaicUtils::getArrayValue($submit, 'w_send') : '';
		if ('before' == $webhookSend) {
			$this->sendWebhook($submit, $data);
		}
		$sendEmail = WaicUtils::getArrayValue($submit, 'email', 0, 1) == 1;
		$emailSend = $sendEmail ? WaicUtils::getArrayValue($submit, 'e_send') : '';
		if ('before' == $emailSend) {
			$this->sendMails($submit, $data);
		}
		
		$prompt = WaicUtils::getArrayValue($submit, 'prompt');
		if (empty($prompt)) {
			return array('answer' => __('Prompt not found.', 'ai-copilot-content-generator'), 'error' => 1);
		}

		$prompt = $this->preparePrompt($prompt, $data);
		
		$apiOptions = WaicUtils::getArrayValue($params, 'api', array(), 2);
		
		$aiProvider = $workspace->getModel('aiprovider')->getInstance($apiOptions);
		if (!$aiProvider) {
			return false;
		}
		$aiProvider->init( $taskId, $userId, $ip, 0, false );
		$history = array(
			'task_id' => $taskId,
			'user_id' => $userId,
			'ip' => $ip,
			'mode' => 0,
			'status' => 2,
			'tokens' => 0,
		);

		if ($aiProvider->setApiOptions($apiOptions)) {
			$opts = array('prompt' => $prompt);
			$result = $aiProvider->getText($opts);
		} else {
			$result = array(
				'error' => 1,
				'msg' => WaicFrame::_()->getLastError(),
				'his_id' => $hisModel->saveHistory($history),
			);
		}
		
		$newLog = array(
			'his_id' => $result['his_id'], 
			'question' => $this->controlText(str_replace(PHP_EOL, '<br>', $prompt)),
			'answer' => $this->controlText($result['error'] ? $result['msg'] : $result['data'], true),
			'file' => $file,
			'error' => $result['error'],
		);

		if (!empty($result['his_id'])) {
			$this->insert($newLog);
		}
		$error = $newLog['error'];
		if ($sendEmail || $sendWebhook) {
			$data['AI_RESPONSE'] = $newLog['answer'];
			if (($error && 'error' == $emailSend) || (empty($error) && 'after' == $emailSend)) {
				$this->sendMails($submit, $data);
			}
			if (($error && 'error' == $webhookSend) || (empty($error) && 'after' == $webhookSend)) {
				$this->sendWebhook($submit, $data);
			}
		}

		return array('answer' => $newLog['answer'], 'error' => $error);
	}
	public function controlText( $str, $mark = false ) {
		if ( !$str ) {
			return '';
		}
		if ($mark) {
			$str = WaicUtils::markdownToHtml($str);
		}
		$str = str_replace(array('```html', '```'), array('', ''), $str);
		return str_replace(array("'"), array('`'), $str);
		//return preg_replace('/\r\n|\r|\n/', '<br>', str_replace(array("'"), array('`'), $str));
	}
	public function getHistory( $params ) {
		$length = WaicUtils::getArrayValue($params, 'length', 10, 1);
		$start = WaicUtils::getArrayValue($params, 'start', 0, 1);
		$search = esc_sql(WaicUtils::getArrayValue(WaicUtils::getArrayValue($params, 'search', array(), 2), 'value'));

		$taskId = WaicUtils::getArrayValue($params, 'task_id', 0, 1);
		$where = array();
		$args = array();
		if (!empty($search)) {
			global $wpdb;
			$like = '%' . $wpdb->esc_like($search) . '%';
			$where[] = 'h.created LIKE %s';
			$where[] = 'u.user_login LIKE %s';
			$where[] = 'h.ip LIKE %s';
			$where[] = 'l.question LIKE %s';
			$where[] = 'l.answer LIKE %s';
			$args = array($like, $like, $like, $like, $like);
		}
		
		$query = 'SELECT l.id, h.created as dd, h.user_id, u.user_login, h.ip, h.tokens, l.question, l.answer' .
			' FROM @__history as h' .
			' INNER JOIN @__formlogs l ON (l.his_id=h.id)' .
			' LEFT JOIN #__users u ON (u.id=h.user_id)' .
			' WHERE h.task_id=' . ( (int) $taskId ) .
			( empty($where) ? '' : ' AND (' . implode(' OR ', $where) . ')' ); 
		
		$order = WaicUtils::getArrayValue($params, 'order', array(), 2);
		$orderBy = 0;
		$sortOrder = 'desc';
		$orders = array('id', 'dd', 'user_login', 'ip', 'tokens', 'question', 'answer');
		if (isset($order[0])) {
			$orderBy = WaicUtils::getArrayValue($order[0], 'column', $orderBy, 1);
			$sortOrder = WaicUtils::getArrayValue($order[0], 'dir', $sortOrder);
		}
		$query .= ' ORDER BY ' . $orders[$orderBy] . ( 'desc' == strtolower($sortOrder) ? ' desc' : ' asc' );
		$logs = WaicDb::get($query, 'all', ARRAY_A, $args);
		
		$totalCount = empty($logs) ? 0 : count($logs);
		
		if ($length > 0) {
			if ($start >= $totalCount) {
				$start = 0;
			}
			$end = $start + $length;
		} else {
			$end = $totalCount;
		}

		$rows = array();
		if ($totalCount > 0) {
			$view = __('view', 'ai-copilot-content-generator');
			$guest = __('guest', 'ai-copilot-content-generator');
			for ($i = $start; $i < $end; $i++) {
				if (!isset($logs[$i])) {
					break;
				}
				$log = $logs[$i];
				$rows[] = array(
					'<div class="waic-log-id" data-value="' . $log['id'] . '">' . $log['id'] . '</div>',
					'<div class="waic-log-dd" data-value="' . $log['dd'] . '">' . $log['dd'] . '</div>',
					'<div class="waic-log-user" data-value="' . $log['user_id'] . '">' . ( empty($log['user_id']) ? $guest : $log['user_login'] ) . '</div>',
					'<div class="waic-log-ip" data-value="' . $log['ip'] . '">' . $log['ip'] . '</div>',
					'<div class="waic-log-tokens" data-value="' . $log['tokens'] . '">' . $log['tokens'] . '</div>',
					'<div class="waic-log-question" data-value="' . $log['question'] . '">' . WaicUtils::mbsubstr($log['question'], 0, 50) . '...</div>',
					'<div class="waic-log-answer" data-value="' . $log['answer'] . '">' . WaicUtils::mbsubstr($log['answer'], 0, 50) . '...</div>',
					'<a href="#" class="waic-history-log">' . $view . '</a>',
				);
			}
		}
		return array(
			'data' => $rows,
			'total' => $totalCount,
		);
	}
	public function sendMails( $submit, $data ) {
		$to = WaicUtils::getArrayValue($submit, 'e_to');
		$subject = WaicUtils::getArrayValue($submit, 'e_subject');
		$message = WaicUtils::getArrayValue($submit, 'e_message');

		$subject = $this->preparePrompt($subject, $data);
		$message = $this->preparePrompt($message, $data);
		$to = trim($this->preparePrompt($to, $data));

		if (empty($to) || empty($subject) || empty($message)) {
			return;
		}
		
		$headers = array(
			'Content-type: text/html; charset=utf-8',
			'Content-Transfer-Encoding: 8bit',
			'From: ' . get_option( 'woocommerce_email_from_name' ) . ' <' . get_option( 'woocommerce_email_from_address' ) . '>'
			);
		if (!wp_mail($to, $subject, $message, $headers)) {
			WaicFrame::_()->pushError(__('Error by sending email', 'wupsales-reward-points'));
			return false;
		}
		return true;
	}
	public function sendWebhook( $submit, $data ) {
		$url = WaicUtils::getArrayValue($submit, 'w_url');
		$headers = WaicUtils::getArrayValue($submit, 'w_headers');
		$message = html_entity_decode(stripslashes(WaicUtils::getArrayValue($submit, 'w_message')));
		if (empty($url) || empty($message)) {
			return;
		}
		
		$url = $this->preparePrompt($url, $data);
		$headers = $this->preparePrompt($headers, $data);
		$message = $this->preparePrompt($message, $data);
		if (!empty($headers)) {
			$paars = explode(PHP_EOL, $headers);
			$headers = array();
			foreach ($paars as $paar) {
				$p = explode(':', $paar);
				if (count($p)) {
					$headers[trim($p[0])] = trim($p[1]);
				}
			}
		}
		if (empty($headers)) {
			$headers = array('Content-Type' => 'application/json');
		}
		$options = array(
			'headers' => $headers,
			'method' => 'POST',
			'body' => $message,
		);
		WaicFrame::_()->saveDebugLogging(array('endpoint' => $url, 'Send WEBHOOK' => $options));
		$response = wp_remote_request($url, $options);
		if (is_wp_error($response)) {
			WaicFrame::_()->pushError($response->get_error_message());
			return false;
		}
	}
}
