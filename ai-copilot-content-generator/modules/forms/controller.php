<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicFormsController extends WaicController {

	protected $_code = 'forms';

	public function getNoncedMethods() {
		return array('saveForm', 'sendForm', 'getHistoryPage');
	}
	public function saveForm() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$taskId = WaicReq::getVar('task_id', 'post');
		$error = '';
		$error = '';
		if (!$this->getModel()->controlTaskParameters($params, $error, $taskId)) {
			$res->pushError($error);
		} else {
			$workspace = WaicFrame::_()->getModule('workspace');
			$params = $this->getModel()->convertTaskParameters($params);
			$id = $workspace->getModel('tasks')->saveTask($this->_code, WaicReq::getVar('task_id', 'post'), $params);
			$this->getModel()->createTable();
			if (empty($id)) {
				$res->pushError(WaicFrame::_()->getErrors());
			} else if (empty($taskId)) {
				$res->addData('taskUrl', $workspace->getTaskUrl($id, $this->_code));
			} else {
				$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			}
		}

		return $res->ajaxExec();
	}
	
	public function getHistoryPage() {
		$res = new WaicResponse();
		$res->ignoreShellData();

		$params = WaicReq::get('post');
		$result = $this->getModel()->getHistory($params);
		
		if ($result) {
			$res->data = $result['data'];

			$res->recordsFiltered = $result['total'];
			$res->recordsTotal = $result['total'];
			$res->draw = WaicUtils::getArrayValue($params, 'draw', 0, 1);

		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		$res->ajaxExec();
	}
	
	public function sendForm() {
		$res = new WaicResponse();
		$taskId = WaicReq::getVar('task_id', 'post');
		$submit = WaicReq::getVar('submit', 'post');
		$fields = WaicReq::getVar('fields', 'post');

		$result = $this->getModel()->sendForm($taskId, $submit, $fields);
		$result['submit'] = $submit;
		$res->addData('result', $result);
		
		return $res->ajaxExec();
	}
}
