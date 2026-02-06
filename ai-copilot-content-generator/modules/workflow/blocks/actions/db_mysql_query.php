<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicAction_db_mysql_query extends WaicAction {
	protected $_code = 'db_mysql_query';
	protected $_order = 0;
	
	public function __construct( $block = null ) {
		$this->_name = __('MySQL: Execute Query', 'ai-copilot-content-generator');
		$this->_desc = __('Execute MySQL queries, insert, update, delete, or select data', 'ai-copilot-content-generator');
		$this->setBlock($block);
	}
	public function getSettings() {
		if (empty($this->_settings)) {
			$this->setSettings();
		}
		return $this->_settings;
	}
	
	public function setSettings() {
		$accounts = WaicFrame::_()->getModule('workflow')->getModel('integrations')->getIntegAccountsList('db', 'mysql');
		if (empty($accounts)) {
			$accounts = array('' => __('No connected accounts found', 'ai-copilot-content-generator'));
		}
		$keys = array_keys($accounts);
		
		$this->_settings = array(
			'account' => array(
				'type' => 'select',
				'label' => __('Account', 'ai-copilot-content-generator') . ' *',
				'options' => $accounts,
				'default' => $keys[0],
			),
			'query' => array(
				'type' => 'textarea',
				'label' => __('Query', 'ai-copilot-content-generator') . ' *',
				'default' => '',
				'rows' => 15,
				'variables' => true,
			),
		);
	}
	public function getVariables() {
		if (empty($this->_variables)) {
			$this->setVariables();
		}
		return $this->_variables;
	}
	public function setVariables() {
		$this->_variables = array(
			'success' => __('Message Sent Successfully', 'ai-copilot-content-generator'),
			'rows_affected' => __('Number of Rows Affected', 'ai-copilot-content-generator'),
			'insert_id' => __('Last Insert ID', 'ai-copilot-content-generator'),
			'result_data' => __('Query Result Data (JSON)', 'ai-copilot-content-generator'),
			'row_count' => __('Number of Rows Returned', 'ai-copilot-content-generator'),
			'query_executed' => __('Executed SQL Query', 'ai-copilot-content-generator'),
			'execution_time' => __('Query Execution Time (milliseconds)', 'ai-copilot-content-generator'),
		);
		return $this->_variables;
	}
	public function getResults( $taskId, $variables, $step = 0 ) {
		$account = $this->getParam('account');
		
		$integration = false;
		if (empty($account)) {
			$error = 'Account is empty';
		} else {
			$parts = explode('-', $account);
			if (count($parts) != 2) {
				$error = 'Account settings error';
			} else {
				$integCode = $parts[0];
				if ('mysql' !== $integCode) {
					$error = 'Account code unacceptable';
				} else {
					$accountNum = (int) $parts[1];
					$integration = WaicFrame::_()->getModule('workflow')->getModel('integrations')->getIntegration($integCode, $accountNum);
					if (!$integration) {
						$error = 'Intergation account not found';
					}
				}
			}
		}
		$result = array();
		if (empty($error)) {
			$query = $this->replaceVariables($this->getParam('query'), $variables);
			if (empty($query)) {
				$error = 'The Query is empty';
			}
		}
		if (empty($error) && $integration) {
			$result = $integration->doQuery($query);
			$error = empty($result['error']) ? '' : $result['error'];
			unset($result['error']);
		}
		if (empty($error)) {
			$result['success'] = 1;
		}
		
		$this->_results = array(
			'result' => $result,
			'error' => $error,
			'status' => empty($error) ? 3 : 7,
		);
		return $this->_results;
	}
	
}
