<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInsightsController extends WaicController {
	protected $_code = 'insights';

	public function getNoncedMethods() {
		return array('getOverview', 'getUsageChart', 'getUsageTable', 'getUsageDrill', 'getUsageEvents', 'getExpensiveSessions', 'getUsageSession', 'getUsageExport', 'getConversations', 'getConversation', 'getActionCenter', 'getProblemClusters', 'getProblemEvidence', 'getOutcomes', 'getCostByOutcome', 'getKbAttribution', 'getCommerceGaps', 'getMcpAudit', 'getActions', 'getActionsExport', 'setInsightState', 'refreshAnalytics', 'savePricingSettings', 'syncPricing', 'importPricing', 'resetPricing');
	}

	public function getPermissions() {
		return array(
			WAIC_USERLEVELS => array(
				WAIC_ADMIN => array('getOverview', 'getUsageChart', 'getUsageTable', 'getUsageDrill', 'getUsageEvents', 'getExpensiveSessions', 'getUsageSession', 'getUsageExport', 'getConversations', 'getConversation', 'getActionCenter', 'getProblemClusters', 'getProblemEvidence', 'getOutcomes', 'getCostByOutcome', 'getKbAttribution', 'getCommerceGaps', 'getMcpAudit', 'getActions', 'getActionsExport', 'setInsightState', 'refreshAnalytics', 'savePricingSettings', 'syncPricing', 'importPricing', 'resetPricing'),
			),
		);
	}

	public function allowNoprivAjax( $action ) {
		return false;
	}

	public function getOverview() {
		return $this->_readOnlyResponse('getOverview');
	}

	public function getUsageChart() {
		return $this->_readOnlyResponse('getUsageChart');
	}

	public function getUsageTable() {
		return $this->_readOnlyResponse('getUsageTable');
	}

	public function getUsageDrill() {
		return $this->_readOnlyResponse('getUsageDrill');
	}

	public function getUsageEvents() {
		return $this->_readOnlyResponse('getUsageEvents');
	}

	public function getExpensiveSessions() {
		return $this->_readOnlyResponse('getExpensiveSessions');
	}

	public function getUsageSession() {
		return $this->_readOnlyResponse('getUsageSession');
	}

	public function getUsageExport() {
		return $this->_readOnlyResponse('getUsageExport');
	}

	public function getConversations() {
		return $this->_readOnlyResponse('getConversations');
	}

	public function getConversation() {
		return $this->_readOnlyResponse('getConversation');
	}

	public function getActionCenter() {
		return $this->_readOnlyResponse('getActionCenter');
	}

	public function getProblemClusters() {
		return $this->_readOnlyResponse('getProblemClusters');
	}

	public function getProblemEvidence() {
		return $this->_readOnlyResponse('getProblemEvidence');
	}

	public function getOutcomes() {
		return $this->_readOnlyResponse('getOutcomes');
	}

	public function getCostByOutcome() {
		return $this->_readOnlyResponse('getCostByOutcome');
	}

	public function getKbAttribution() {
		return $this->_readOnlyResponse('getKbAttribution');
	}

	public function getCommerceGaps() {
		return $this->_readOnlyResponse('getCommerceGaps');
	}

	public function getMcpAudit() {
		return $this->_readOnlyResponse('getMcpAudit');
	}

	public function getActions() {
		return $this->_readOnlyResponse('getActions');
	}

	public function getActionsExport() {
		return $this->_readOnlyResponse('getActionsExport');
	}

	public function setInsightState() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to update Insights state.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$result = $this->getModel()->setInsightState(WaicReq::get('post'));
		if (false === $result) {
			$errors = $this->getModel()->getErrors();
			$res->pushError(empty($errors) ? esc_html__('Could not update Insights state.', 'ai-copilot-content-generator') : $errors);
		} else {
			$res->addData($result);
			$res->addMessage(esc_html__('Insights state updated.', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}

	public function refreshAnalytics() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to refresh Insights analytics.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$result = $this->getModel()->rollupInsights360(2);
		$res->addData(array('insights_360' => $result));
		$res->addMessage(esc_html__('Insights analytics refreshed.', 'ai-copilot-content-generator'));
		return $res->ajaxExec();
	}

	public function savePricingSettings() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to manage pricing settings.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$model = $this->getModel('pricing');
		$status = $model->saveSettings(WaicReq::get('post'));
		$this->getModule()->syncPricingCronSchedule();
		$res->addData(array('pricing_status' => $status));
		if ($model->haveErrors()) {
			$res->pushError($model->getErrors());
		} else {
			$res->addMessage(esc_html__('Pricing settings saved.', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}

	public function syncPricing() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to sync pricing.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$result = $this->getModel('pricing')->syncRemote(true);
		$res->addData(array('pricing_status' => WaicUtils::getArrayValue($result, 'status', array(), 2)));
		if (empty($result['ok'])) {
			$res->pushError(esc_html__('Pricing sync did not complete. Last valid pricing remains active.', 'ai-copilot-content-generator'));
		} else {
			$res->addMessage(esc_html__('Pricing synced.', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}

	public function importPricing() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to import pricing.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$model = $this->getModel('pricing');
		$result = $model->importSnapshot(WaicReq::get('post'), isset($_FILES) ? $_FILES : array());
		$this->getModule()->syncPricingCronSchedule();
		$res->addData(array('pricing_status' => WaicUtils::getArrayValue($result, 'status', array(), 2)));
		if (empty($result['ok'])) {
			$res->pushError($model->haveErrors() ? $model->getErrors() : esc_html__('Pricing import did not complete.', 'ai-copilot-content-generator'));
		} else {
			$res->addMessage(esc_html__('Pricing imported.', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}

	public function resetPricing() {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to reset pricing.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}
		$model = $this->getModel('pricing');
		$result = $model->resetToBundled(true);
		$this->getModule()->syncPricingCronSchedule();
		$res->addData(array('pricing_status' => WaicUtils::getArrayValue($result, 'status', array(), 2)));
		if (empty($result['ok'])) {
			$res->pushError($model->haveErrors() ? $model->getErrors() : esc_html__('Pricing reset did not complete.', 'ai-copilot-content-generator'));
		} else {
			$res->addMessage(esc_html__('Bundled pricing restored.', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}

	private function _readOnlyResponse( $method ) {
		$res = new WaicResponse();
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			status_header(403);
			$res->pushError(esc_html__('You have no permissions to view Insights.', 'ai-copilot-content-generator'));
			return $res->ajaxExec();
		}

		$params = WaicReq::get('post');
		$model = $this->getModel();
		$result = $model->$method($params);
		if (false === $result) {
			$errors = $model->getErrors();
			$res->pushError(empty($errors) ? esc_html__('Could not load Insights data.', 'ai-copilot-content-generator') : $errors);
		} else {
			$res->addData($result);
		}
		return $res->ajaxExec();
	}
}
