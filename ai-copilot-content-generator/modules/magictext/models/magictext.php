<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicMagictextModel extends WaicModel {
	private $feature = 'magictext';
	private $values = array();
	private $data = array();
	protected $_code = 'magictext';

	public function getDefaultData() {
		return array(
			'fields' => array(
				'grammar' => array(
					'name' => __('Fix grammar', 'ai-copilot-content-generator'),
					'text' => 'Correct the grammar and spelling of the following text while preserving its original meaning and style. Return only the corrected text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'enhance' => array(
					'name' => __('Enhance Text', 'ai-copilot-content-generator'),
					'text'=> 'Improve the clarity, readability, and flow of the following text while maintaining its original meaning and style. Return only the enhanced text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'longer' => array(
					'name' => __('Longer Text', 'ai-copilot-content-generator'),
					'text' => 'Expand the following text by adding more details, explanations, and relevant information while maintaining its original meaning and style. Return only the expanded text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'shorter' => array(
					'name' => __('Shorter Text', 'ai-copilot-content-generator'),
					'text' => 'Make the following text more concise while preserving its original meaning. Remove unnecessary words and improve clarity. Return only the shortened text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'translate' => array(
					'name' => __('Translate Text', 'ai-copilot-content-generator'),
					'text' => 'Translate the following text into {Selected Language} while preserving its original meaning and tone. Return only the translated text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'synonyms' => array(
					'name' => __('Suggest Synonyms', 'ai-copilot-content-generator'),
					'text' => 'Rewrite the following text by replacing words with appropriate synonyms while maintaining the original meaning and readability. Return only the rephrased text without any explanations.' . PHP_EOL . '{Selected Text}',
				),
				'cust' => array(
					'name' => __('Custom prompt', 'ai-copilot-content-generator'),
					'text' => '{Custom Prompt}' . PHP_EOL . PHP_EOL . 'Text to edit:' . PHP_EOL . '{Selected Text}',
				),
			),
		);
	}

	public function getAll() {
		$tabs = $this->getModule()->getMagictextTabsList();

		foreach ($tabs as $gr => $d) {
			$this->loadOptValues($gr);
		}
		return $this->values;
	}

	private function loadOptValues( $gr ) {
		if (!isset($this->values[$gr])) {
			$this->values[$gr] = $this->loadOptions($gr);
			if (empty($this->values[$gr]) || count($this->values[$gr])==0) {
				if ('fields' == $gr) {
					$this->values[$gr] = $this->getDefaultData()['fields'];
				} else {
					$this->values[$gr] = array();
				}
			}
		}
	}

	public function isEnabled() {
		$data = $this->getTask();
		return 4 == $data['status'];
	}

	public function getData() {
		if (!empty($this->data)) {
			return $this->data;
		}

		$tasks = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$tasks->setWhere(array('feature' => $this->feature));
		$result = $tasks->getFromTbl();

		if (isset($result[0]['params'])) {
			$this->data = json_decode($result[0]['params'], true);
			$this->data = $this->fixData($this->data);
			return $this->data;
		}

		return array();
	}

	private function fixData( $data ) {
		if (empty($data['fields'])) {
			$data['fields'] = $this->getDefaultData()['fields'];
		}

		if (empty($data['api'])) {
			$data['api'] = WaicFrame::_()->getModule('options')->get('api');
		}

		return $data;
	}

	public function loadOptions( $gr ) {
		$tasks = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$tasks->setWhere("`feature`='{$this->feature}'");
		$result = $tasks->getFromTbl();
		if ($result) {
			if ($result[0]['params']) {
				$decoded = json_decode($result[0]['params'], true);

				return $decoded[ $gr ] ?? null;
			}
		}

		return null;
	}

	public function getTaskId() {
		$result = $this->getTask();

		if (!empty($result)) {
			return $result['id'];
		} else {
			return null;
		}
	}

	public function getTask() {
		$tasks = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$tasks->setWhere(array('feature' => $this->feature));
		$result = $tasks->getFromTbl();
		if (!empty($result)) {
			$result = $result[0];
			$result = $this->fixData($result);
		} else {
			$params = $this->getDefaultData();
			$params['api'] = WaicFrame::_()->getModule('options')->get('api');
			$params['task_title'] = $this->getTitle();

			$tasks->saveTask(
				$this->feature,
				null,
				$params
			);
			$tasks->setWhere(array('feature' => $this->feature));
			$result = $tasks->getFromTbl();

			if (!empty($result)) {
				$result = $result[0];
			}
		}

		return $result;
	}

	public function convertTaskParameters( $params, $toDB = true ) {
		if (isset($params['fields'])) {
			$dtFields = array('date' => array('date', 'from', 'to', 'period'));
			foreach ($dtFields as $field => $data) {
				if (isset($params['fields'][$field])) {
					foreach ($data as $d) {
						if (!empty($params['fields'][$field][$d])) {
							$params['fields'][$field][$d] = $toDB ? WaicUtils::convertDateTimeToDB($params['fields'][$field][$d]) : WaicUtils::convertDateTimeToFront($params['fields'][$field][$d]);
						}
					}
				}
			}
		}

		return WaicDispatcher::applyFilters('convertTaskParameters', $params, $toDB);
	}

	public function getText( $params ) {
		if ($this->isEnabled()) {
			$workspace = WaicFrame::_()->getModule('workspace');

			$user = wp_get_current_user();
			$userId = $user ? $user->ID : 0;
			$ip = WaicUtils::getRealUserIp();

			$data = $this->getData();
			$prompt = WaicUtils::getArrayValue($data['fields'][$params['item']], 'text', '');
			$prompt = str_replace('{Selected Text}', $params['selected'], $prompt);

			if ('translate' === $params['item']) {
				if (empty($params['lang'])) {
					WaicFrame::_()->pushError(esc_html__('Language is required!', 'ai-copilot-content-generator'));
					return false;
				}

				$prompt = str_replace('{Selected Language}', $params['lang'], $prompt);
			}

			if ('cust' === $params['item']) {
				if (empty($params['prompt'])) {
					WaicFrame::_()->pushError(esc_html__('Prompt is required!', 'ai-copilot-content-generator'));
					return false;
				}

				$prompt = str_replace('{Custom Prompt}', $params['prompt'], $prompt);
			}

			$instructions = array();
			$instructions[] = array('role' => 'user', 'content' => $prompt);

			$apiOptions = $data['api'];

			$aiProvider = $workspace->getModel('aiprovider')->getInstance($apiOptions);
			if (!$aiProvider) {
				WaicFrame::_()->pushError(esc_html__('AI Provider not found', 'ai-copilot-content-generator'));
				return false;
			}

			$task = $this->getTask();

			$aiProvider->init( $task['id'], $userId, $ip, 0, false );

			if ($aiProvider->setApiOptions($apiOptions)) {
				$opts = array('messages' => $instructions);
				$result = $aiProvider->getText($opts);

				if (0 == $result['error']) {
					return trim($result['data']);
				} else {
					WaicFrame::_()->pushError($result['msg']);
					return false;
				}
			}

		} else {
			WaicFrame::_()->pushError(esc_html__('Module is not enabled!', 'ai-copilot-content-generator'));
			return false;
		}
	}

	public function getTitle() {
		return 'Magic Text';
	}

	public function publishResults( $taskId ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 4));
		return false;
	}

	public function canUnpublish() {
		return true;
	}

	public function unpublishEtaps( $taskId ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 6));
		return false;
	}

	public function clearEtaps( $taskId ) {
		return false;
	}

	public function setStatus( $status ) {
		add_filter('waic_addTaskColumns_' . $this->_code, function ( $columns, $params, $id ) use ( $status ) {
			$columns['status'] = $status;
			return $columns;
		}, 10, 3);
	}
}
