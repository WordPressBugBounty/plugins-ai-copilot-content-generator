<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicOptionsController extends WaicController {
	public function getNoncedMethods() {
		return array('saveOptions', 'restoreOptions', 'saveApiKey', 'checkApiModels', 'refreshModelRegistry', 'importModelRegistry', 'rollbackModelRegistry', 'testApiModel', 'listProviderProfiles', 'saveProviderProfile', 'rotateProviderProfile', 'deleteProviderProfile');
	}
	public function saveOptions() {
		$res = new WaicResponse();

		$model = $this->getModel();
		$gr = WaicReq::getVar('group');
		$params = WaicReq::getVar('params', 'post', null, $model->getHtmlParams($gr));
		$params = $model->correctOptions($params, $gr);

		if ($model->saveOptions($params)) {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		return $res->ajaxExec();
	}
	public function restoreOptions() {
		$res = new WaicResponse();
		$model = $this->getModel();
		$gr = WaicReq::getVar('group');
		$isApi = ( 'api' == $gr );
		if ($isApi) {
			$apiKey = $model->get('api', 'api_key');
			$deepSeekApiKey = $model->get('api', 'deep_seek_api_key');
			$geminiApiKey = $model->get('api', 'gemini_api_key');
		}
		if ($model->removeOptions($gr)) {
			if ($isApi) {
				$model->save('api', 'api_key', $apiKey);
				$model->save('api', 'deep_seek_api_key', $deepSeekApiKey);
				$model->save('api', 'gemini_api_key', $geminiApiKey);
			}
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		return $res->ajaxExec();
	}
	public function saveApiKey() {
		$res = new WaicResponse();
		if ($this->getModel()->save('api', 'api_key', WaicReq::getVar('key', 'post'))) {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		return $res->ajaxExec();
	}
	public function checkApiModels() {
		$res = new WaicResponse();
		$provider = WaicReq::getVar('provider', 'post');
		$apiKey = WaicReq::getVar('api_key', 'post');
		$results = $this->getModel()->checkApiModels($provider, $apiKey);
		
		if (is_array($results)) {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
			$res->addData('results', $results);
		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}

		return $res->ajaxExec();
	}
	public function refreshModelRegistry() {
		$res = new WaicResponse();
		$params = WaicReq::getVar('params', 'post');
		$result = $this->getModel()->refreshModelRegistry(is_array($params) ? $params : array());
		if (!empty($result['ok'])) {
			$res->addMessage(esc_html__('AI models refreshed.', 'ai-copilot-content-generator'));
			$res->addData('status', WaicUtils::getArrayValue($result, 'status', array(), 2));
		} else {
			$message = WaicUtils::getArrayValue($result, 'message', esc_html__('AI model registry refresh completed with warnings.', 'ai-copilot-content-generator'));
			$res->addMessage($message);
			$res->addData('status', WaicUtils::getArrayValue($result, 'status', array(), 2));
		}
		return $res->ajaxExec();
	}
	public function importModelRegistry() {
		$res = new WaicResponse();
		$json = isset($_POST['manifest_json']) ? wp_unslash($_POST['manifest_json']) : '';
		$result = $this->getModel()->importModelRegistry($json);
		if (!empty($result['ok'])) {
			$res->addMessage(esc_html__('AI model registry imported.', 'ai-copilot-content-generator'));
			$res->addData('status', WaicUtils::getArrayValue($result, 'status', array(), 2));
		} else {
			$status = WaicUtils::getArrayValue($result, 'status', array(), 2);
			$errors = WaicUtils::getArrayValue($status, 'errors', array(), 2);
			$res->pushError(empty($errors) ? WaicFrame::_()->getErrors() : $errors);
		}
		return $res->ajaxExec();
	}
	public function rollbackModelRegistry() {
		$res = new WaicResponse();
		$result = $this->getModel()->rollbackModelRegistry();
		if (!empty($result['ok'])) {
			$res->addMessage(esc_html__('AI model registry rolled back.', 'ai-copilot-content-generator'));
			$res->addData('status', WaicUtils::getArrayValue($result, 'status', array(), 2));
		} else {
			$status = WaicUtils::getArrayValue($result, 'status', array(), 2);
			$errors = WaicUtils::getArrayValue($status, 'errors', array(), 2);
			$res->pushError(empty($errors) ? WaicFrame::_()->getErrors() : $errors);
		}
		return $res->ajaxExec();
	}
	public function testApiModel() {
		$res = new WaicResponse();
		$provider = WaicReq::getVar('provider', 'post');
		$model = WaicReq::getVar('model', 'post');
		$apiKey = WaicReq::getVar('api_key', 'post');
		$result = $this->getModel()->testApiModel($provider, $model, $apiKey);
		if (is_array($result)) {
			$res->addMessage(WaicUtils::getArrayValue($result, 'message', esc_html__('Model test completed.', 'ai-copilot-content-generator')));
			$res->addData('result', $result);
		} else {
			$res->pushError(WaicFrame::_()->getErrors());
		}
		return $res->ajaxExec();
	}
	public function listProviderProfiles() {
		$res = new WaicResponse();
		$res->addData('profiles', $this->getModel()->getProviderProfiles());
		return $res->ajaxExec();
	}
	public function saveProviderProfile() {
		$res = new WaicResponse();
		$profile = WaicReq::getVar('profile', 'post');
		$credentials = WaicReq::getVar('credentials', 'post');
		$saved = $this->getModel()->saveProviderProfile(is_array($profile) ? $profile : array(), is_array($credentials) ? $credentials : array());
		if ($saved) { $res->addMessage(esc_html__('Provider profile saved.', 'ai-copilot-content-generator')); $res->addData('profile', $saved); }
		else { $res->pushError(WaicFrame::_()->getErrors()); }
		return $res->ajaxExec();
	}
	public function rotateProviderProfile() {
		$res = new WaicResponse();
		$saved = $this->getModel()->rotateProviderProfile(WaicReq::getVar('profile_id', 'post'), WaicReq::getVar('credentials', 'post'));
		if ($saved) { $res->addMessage(esc_html__('Provider credentials rotated.', 'ai-copilot-content-generator')); $res->addData('profile', $saved); }
		else { $res->pushError(WaicFrame::_()->getErrors()); }
		return $res->ajaxExec();
	}
	public function deleteProviderProfile() {
		$res = new WaicResponse();
		if ($this->getModel()->deleteProviderProfile(WaicReq::getVar('profile_id', 'post'), WaicReq::getVar('delete_credentials', 'post'))) { $res->addMessage(esc_html__('Provider profile deleted.', 'ai-copilot-content-generator')); }
		else { $res->pushError(esc_html__('Provider profile was not found.', 'ai-copilot-content-generator')); }
		return $res->ajaxExec();
	}
}
