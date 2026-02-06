<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostsfieldsController extends WaicController {

	protected $_code = 'postsfields';

	public function getNoncedMethods() {
		return array('startGeneration', 'getPostsResultsBulk', 'doActionTask', 'searchPostsList');
	}
	
	public function searchPostsList() {
		$res = new WaicResponse();
		$res->ignoreShellData();

		$params = WaicReq::get('post');
		$result = $this->getModel()->searchPostsList($params);

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
	
	public function startGeneration() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$error = '';
		if (!$this->getModel()->controlTaskParameters($params, $error)) {
			$res->pushError($error);
		} else {
			$workspace = WaicFrame::_()->getModule('workspace');
			$params = $this->getModel()->convertTaskParameters($params);

			$id = $workspace->getModel('tasks')->saveTask($this->_code, WaicReq::getVar('task_id', 'post'), $params);
			
			if (empty($id) || !$workspace->getModel()->startGeneration($id)) {
				$res->pushError(WaicFrame::_()->getErrors());
			} else {
				$res->addData('taskUrl', WaicFrame::_()->getModule('workspace')->getTaskUrl($id, $this->_code));
			}

		}
		return $res->ajaxExec();
	}
	
	public function getPostsResultsBulk() {
		$res = new WaicResponse();
		$res->ignoreShellData();

		$params = WaicReq::get('post');
		$result = $this->getModel()->getBulkResultsList($params, WaicReq::getVar('param'));
		
		if ($result) {
			$res->data = $result['data'];

			$res->recordsFiltered = $result['total'];
			$res->recordsTotal = $result['total'];
			$res->draw = WaicUtils::getArrayValue($params, 'draw', 0, 1);
			
			$taskId = WaicUtils::getArrayValue($params, 'task_id', 0, 1);
			$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
			$task = $taskModel->getTask($taskId);
			$res->task = $task;
			$res->actions = $taskModel->getTaskActions($taskId, $task['status']);

		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		$res->ajaxExec();
	}
	
	public function doActionTask() {
		$res = new WaicResponse();
		$taskId = WaicReq::getVar('task_id', 'post');
		$action = WaicReq::getVar('task_action', 'post');
		$param = WaicReq::getVar('param', 'post');
		
		switch ($action) {
			case 'cancel':
				$cancelled = WaicReq::getVar('deleted', 'post');
				$result = empty($cancelled) ? true : $this->getModel()->cancelTaskEtaps($taskId, $cancelled);
				break;
			default:
				//$result = WaicDispatcher::applyFilters('doActionTask_' . $feature, true);
			
				$result = true;
				break;
		}
		if (-1 === $result) {
			$res->confirm = WaicFrame::_()->getErrors();
		} else if (!$result) {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		return $res->ajaxExec();
	}
}
