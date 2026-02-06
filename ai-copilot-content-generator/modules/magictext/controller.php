<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicMagictextController extends WaicController {
	protected $_code = 'magictext';

	public function getText() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		if (empty($params['selected'])) {
			$res->pushError(esc_html__('Selection not found!', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}

		$result = $this->getModel()->getText($params);

		if (!$result) {
			$res->pushError(WaicFrame::_()->getLastError());
		} else {
			$res->addMessage($result);
		}

		return $res->ajaxExec();
	}

	public function updateStatus() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$workspace = WaicFrame::_()->getModule('workspace');

		$data['status'] = isset($params['enabled']) && $params['enabled'] ? 4 : 6;
		$workspace->getModel('tasks')->updateTask(WaicReq::getVar('task_id', 'post'), $data);

		$res->addMessage(esc_html__('Done!', 'ai-copilot-content-generator'));
		return $res->ajaxExec();
	}

	public function startGeneration() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		if (isset($params['enabled']) && $params['enabled']) {
			$this->getModel()->setStatus(4);
			unset($params['enabled']);
		} else {
			$this->getModel()->setStatus(6);
		}
		$workspace = WaicFrame::_()->getModule('workspace');

		if ($params) {
			foreach ($params['fields'] as $field) {
				if (!$field['name'] || !$field['text']) {
					$res->pushError(esc_html__('Data is empty!', 'ai-copilot-content-generator'));
					return $res->ajaxExec();
				}
			}

			$params['task_title'] = $this->getModel()->getTitle();
			$id = $workspace->getModel('tasks')->saveTask($this->_code, WaicReq::getVar('task_id', 'post'), $params);
			if (empty($id)) {
				$res->pushError(WaicFrame::_()->getErrors());
			} else {
				$res->addMessage(esc_html__('Done!', 'ai-copilot-content-generator'));
			}
		} else {
			$res->pushError(esc_html__('Cannot get params!', 'ai-copilot-content-generator'));
		}

		return $res->ajaxExec();
	}

	public function restoreOptions() {
		$res = new WaicResponse();
		$model = $this->getModel();

		$workspace = WaicFrame::_()->getModule('workspace');

		$id = WaicReq::getVar('task_id', 'post');

		if (empty($id)) {
			$res->pushError(__('Task ID is required!', 'ai-copilot-content-generator'));
		} else {
			$this->getModel()->setStatus(4);

			$params = $model->getDefaultData();
			$params['api'] = WaicFrame::_()->getModule('options')->get('api');
			$params['task_title'] = $this->getModel()->getTitle();
			$workspace->getModel('tasks')->saveTask($this->_code, $id, $params);
			$res->addMessage(esc_html__('Done!', 'ai-copilot-content-generator'));
		}

		return $res->ajaxExec();
	}
}
