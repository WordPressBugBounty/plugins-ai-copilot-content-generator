<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicLogic_un_branch extends WaicLogic {
	protected $_code = 'un_branch';
	protected $_subtype = 2;
	protected $_order = 1;
	
	public function __construct( $block = null ) {
		//$this->setSettings();
		$this->_name = __('Branch', 'ai-copilot-content-generator');
		$this->_desc = __('Check condition and choose execution path', 'ai-copilot-content-generator');
		$this->_sublabel = array('name');
		$this->setBlock($block);
	}
	public function getSettings() {
		if (empty($this->_settings)) {
			$this->setSettings();
		}
		return $this->_settings;
	}
	
	public function setSettings() {
		$this->_settings = array(
			'name' => array(
				'type' => 'input',
				'label' => __('Node Name', 'ai-copilot-content-generator'),
				'default' => 'IF',
			),
			'criteria' => array(
				'type' => 'input',
				'label' => __('Criteria', 'ai-copilot-content-generator'),
				'default' => '',
				'variables' => true,
			),
			'operator' => array(
				'type' => 'select',
				'label' => __('Operator', 'ai-copilot-content-generator'),
				'default' => 'equals',
				'options' => array(
					'equals' => __('Equals', 'ai-copilot-content-generator'),
					'contains' => __('Contains', 'ai-copilot-content-generator'),
					'does_not_equal' => __('Does not equal', 'ai-copilot-content-generator'),
					'greater_than' => __('Greater than', 'ai-copilot-content-generator'),
					'less_than' => __('Less than', 'ai-copilot-content-generator'),
					'is_one_of' => __('Is one of', 'ai-copilot-content-generator'),
					'is_not_one_of' => __('Is not one of', 'ai-copilot-content-generator'),
					'is_known' => __('Is known', 'ai-copilot-content-generator'),
					'is_unknown' => __('Is unknown', 'ai-copilot-content-generator'),
				),
			),
			'value' => array(
				'type' => 'input',
				'label' => __('Value', 'ai-copilot-content-generator'),
				'default' => '',
				'show' => array('operator' => array('equals', 'contains', 'does_not_equal', 'greater_than', 'less_than')),
				'variables' => true,
			),
			'values' => array(
				'type' => 'input',
				'label' => __('Values sep. with commas', 'ai-copilot-content-generator'),
				'default' => '',
				'show' => array('operator' => array('is_one_of', 'is_not_one_of')),
				'variables' => true,
			),
			'compare' => array(
				'type' => 'select',
				'label' => __('Compare as', 'ai-copilot-content-generator'),
				'default' => 'text',
				'show' => array('operator' => array('equals', 'does_not_equal', 'greater_than', 'less_than', 'is_one_of', 'is_not_one_of')),
				'options' => array(
					'text' => __('Text', 'ai-copilot-content-generator'),
					'number' => __('Number', 'ai-copilot-content-generator'),
				),
			),
		);
	}
	
	public function getResults( $taskId, $variables, $step = 0 ) {
		$result = false;
		
		$criteria = $this->replaceVariables($this->getParam('criteria'), $variables);

		$value = $this->replaceVariables($this->getParam('value'), $variables);
		$values = explode(',', $this->replaceVariables($this->getParam('values'), $variables));

		$isNumber = $this->getParam('compare') == 'number';
		if ($isNumber) {
			$criteria = (float) $criteria;
			$value = (float) $value;
			if (!empty($values)) {
				$values = $this->controlIdsArray($values);
			}
		}
		
		switch ($this->getParam('operator')) {
			case 'equals':
				if ($criteria == $value) {
					$result = true;
				}
				break;
			case 'contains':
				if (WaicUtils::mbstrpos($criteria, $value) !== false) {
					$result = true;
				}
				break;
			case 'does_not_equal':
				if ($criteria != $value) {
					$result = true;
				}
				break;
			case 'greater_than':
				if ($criteria > $value) {
					$result = true;
				}
				break;
			case 'less_than':
				if ($criteria < $value) {
					$result = true;
				}
				break;
			case 'is_one_of':
				if (in_array($criteria, $values)) {
					$result = true;
				}
				break;
			case 'is_not_one_of':
				if (!in_array($criteria, $values)) {
					$result = true;
				}
				break;
			case 'is_known':
				if (!empty($criteria)) {
					$result = true;
				}
				break;
			case 'is_unknown':
				if (empty($criteria)) {
					$result = true;
				}
				break;
		}
		
		$this->_results = array(
			'result' => array('result' => $result),
			'error' => '',
			'status' => 3,
			'sourceHandle' => $result ? 'output-then' : 'output-else',
		);
		return $this->_results;
	}
}
