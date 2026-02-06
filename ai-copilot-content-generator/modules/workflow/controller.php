<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicWorkflowController extends WaicController {

	protected $_code = 'workflow';

	public function getNoncedMethods() {
		return array('saveWorkflow', 'stopWorkflow', 'runWorkflow', 'getLogData', 'getHistoryList', 'saveIntegration', 'createTemplate', 'deleteTemplate', 'getJSON', 'importTemplate');
	}
	public function getHistoryList() {
		$res = new WaicResponse();
		$res->ignoreShellData();

		$params = WaicReq::get('post');
		$params['feature'] = 'workflow';
		$params['compact'] = true;
		$params['actions'] = '<div class="waic-table-actions">
						<a href="#" class="waic-action-template wbw-tooltip" title="' . esc_html__('Save as template', 'ai-copilot-content-generator'). '"><i class="fa fa-clipboard"></i></a>
						<a href="#" class="waic-action-export wbw-tooltip" title="' . esc_html__('Export JSON', 'ai-copilot-content-generator'). '"><i class="fa fa-download"></i></a>
					</div>';
		$result = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getHistory($params);
		
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
	
	public function saveWorkflow() {
		$res = new WaicResponse();
		$params = json_decode(stripslashes(WaicReq::getVar('flow', 'post', '', true, false)), true);
		$params['task_title'] = WaicReq::getVar('title', 'post');
		$error = '';
		$taskId = WaicReq::getVar('task_id', 'post');
		$workspace = WaicFrame::_()->getModule('workspace');
		$taskModel = $workspace->getModel('tasks');
		if (!empty($taskId)) {
			$task = $taskModel->getById($taskId);
			if ($task && $taskModel->isPublished($task['status'])) {
				$res->pushError(esc_html__('To save changes, first stop the workflow.', 'ai-copilot-content-generator'));
				return $res->ajaxExec();
			}
		}
		$errNodes = array();
		if (!$this->getModel()->controlTaskParameters($params, $error, $errNodes, $taskId)) {
			$res->pushError($error);
			$res->addData('err_nodes', $errNodes);
		} else {
			$params = $this->getModel()->convertTaskParameters($params);
			$id = $taskModel->saveTask($this->_code, $taskId, $params);
			
			if (empty($id)) {
				$res->pushError(WaicFrame::_()->getErrors());
			} else {
				$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
				$res->addData('taskUrl', WaicFrame::_()->getModule('workspace')->getTaskUrl($id, $this->_code));
			}
		}
		return $res->ajaxExec();
	}
	public function saveIntegration() {
		$res = new WaicResponse();
		$code = WaicReq::getVar('code', 'post');
		$accounts = WaicReq::getVar('accounts', 'post');
		
		$accounts = $this->getModel('integrations')->saveIntegrations($code, $accounts);

		if (false === $accounts) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			$res->addData('accounts', $accounts);
		}
		return $res->ajaxExec();
	}
	public function runWorkflow() {
		$res = new WaicResponse();
		$status = $this->getModel()->publishResults(WaicReq::getVar('task_id', 'post'), false, true);

		if (empty($status)) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			$res->addData('status', $status);
		}
		return $res->ajaxExec();
	}
	public function stopWorkflow() {
		$res = new WaicResponse();
		$status = $this->getModel()->unpublishEtaps(WaicReq::getVar('task_id', 'post'),true);

		if (empty($status)) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			$res->addData('status', $status);
		}
		return $res->ajaxExec();
	}
	public function getLogData() {
		$res = new WaicResponse();
		$taskId = WaicReq::getVar('task_id', 'post');
		$dd = WaicReq::getVar('date', 'post');
		
		$html = $this->getView()->getLogData($taskId, $dd);
		$res->setHtml($html);

		return $res->ajaxExec();
	}
	public function createTemplate() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$params = empty($params) ? array() : json_decode(wp_unslash($params), true);

		$result = $this->getModel()->createTemplate($params);

		if (false === $result) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}
	public function importTemplate() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$params = empty($params) ? array() : json_decode(wp_unslash($params), true);

		$result = $this->getModel()->importTemplate($params);

		if (false === $result) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}
	public function deleteTemplate() {
		$res = new WaicResponse();
		$id = WaicReq::getVar('id', 'post');
		$result = $this->getModel()->deleteTemplate($id);

		if (false === $result) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}
	public function getJSON() {
		$res = new WaicResponse();
		$id = WaicReq::getVar('id', 'post');
		$result = $this->getModel()->getJSON($id);

		if (false === $result) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addData('json', $result);
		}
		return $res->ajaxExec();
	}
}