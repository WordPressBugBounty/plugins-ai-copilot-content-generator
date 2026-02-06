<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostscreateController extends WaicController {

	protected $_code = 'postscreate';

	public function getNoncedMethods() {
		return array('startGeneration');
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
				$res->addData('taskUrl', WaicFrame::_()->getModule('workspace')->getTaskUrl($id, 'postscreate'));
			}
		}
		return $res->ajaxExec();
	}
}
