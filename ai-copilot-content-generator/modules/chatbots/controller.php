<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicChatbotsController extends WaicController {

	protected $_code = 'chatbots';

	public function getNoncedMethods() {
		return array('saveChatbot', 'sendMessage', 'sendFile', 'resetChatbotAdmin', 'getHistoryPage', 'getLogData');
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
	public function getLogData() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		
		$html = $this->getView()->getLogData($params);
		$res->setHtml($html);

		return $res->ajaxExec();
	}
	public function saveChatbot() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$taskId = WaicReq::getVar('task_id', 'post');
		$error = '';
		
		$workspace = WaicFrame::_()->getModule('workspace');

		$id = $workspace->getModel('tasks')->saveTask($this->_code, WaicReq::getVar('task_id', 'post'), $params);
		if (empty($id)) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$this->getModel()->setChatbotParams($id);
			if (empty($taskId)) {
				$res->addData('taskUrl', $workspace->getTaskUrl($id, $this->_code));
			} else {
				$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			}
		}
		return $res->ajaxExec();
	}
	public function getChatbotAjax() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		
		$html = $this->getView()->renderChatbotHtml($params, WaicReq::getVar('task_id', 'post'), true, WaicReq::getVar('mobile', 'post'));
		$res->setHtml($html);

		return $res->ajaxExec();
	}
	public function resetChatbotAdmin() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$user = wp_get_current_user();
		$userId = $user ? $user->ID : 0;
		$ip = WaicUtils::getRealUserIp();
		
		$this->getModel()->deleteUserChatLog(WaicReq::getVar('task_id', 'post'), $userId, $ip, 1);

		return $res->ajaxExec();
	}
	public function sendFile() {
		$res = new WaicResponse();
		$taskId = WaicReq::getVar('task_id', 'post');
		$mode = WaicReq::getVar('mode', 'post');
		$message = WaicReq::getVar('message', 'post');
		$files = WaicReq::get('files');
		$log = $this->getModel()->sendMessage($message, $taskId, $mode, WaicReq::getVar('aware', 'post'), $files);
		
		if (empty($log)) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$log['task_id'] = $taskId;
			$log['chat_id'] = WaicReq::getVar('chat_id', 'post');
			$log['mes_id'] = WaicReq::getVar('mes_id', 'post');
			$res->addData('log', $log);
		} 

		return $res->ajaxExec();
	}
	
	public function sendMessage() {
		$res = new WaicResponse();
		$message = empty($_POST['message']) ? '' : preg_replace('/\r\n|\r|\n/', '<br>', sanitize_textarea_field($_POST['message'])); // WaicReq::getVar('message', 'post');
		$taskId = WaicReq::getVar('task_id', 'post');
		$mode = WaicReq::getVar('mode', 'post');
		$typ = WaicReq::getVar('typ', 'post');
		$request = WaicReq::getVar('request', 'post');

		if ('email' == $typ) {
			$log = $this->getModel()->sendEmail($message, $taskId, $mode, $request);
		} else {
			$mem = '';
			if ('human' == $request) {
				$log = $this->getModel()->humanRequest($taskId);
			} else {
				$log = $this->getModel()->sendMessage($message, $taskId, $mode, WaicReq::getVar('aware', 'post'));
			}
		}
		if (empty($log)) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$log['task_id'] = $taskId;
			$log['chat_id'] = WaicReq::getVar('chat_id', 'post');
			$log['mes_id'] = WaicReq::getVar('mes_id', 'post');
			$res->addData('log', $log);
		} 

		return $res->ajaxExec();
	}
}
