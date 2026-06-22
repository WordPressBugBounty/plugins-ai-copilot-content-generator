<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInsightsView extends WaicView {
	public function showInsights() {
		if (!current_user_can(apply_filters('waic_insights_capability', 'manage_options'))) {
			return '';
		}
		$assets = WaicAssets::_();
		$assets->loadCoreJs();
		$assets->loadAdminEndCss();

		$frame = WaicFrame::_();
		$path = $this->getModule()->getModPath() . 'assets/';
		$frame->addScript('waic-chartjs', WAIC_LIB_PATH . 'chartjs/chart.umd.min.js', array(), '4.4.0');
		$frame->addScript('waic-insights-admin', $path . 'js/admin.insights.js', array('jquery', 'waic-chartjs'));
		$pricing = $this->getModule()->getModel('pricing');
		$pricingStatus = $pricing->getStatus();
		$frame->addJSVar('waic-insights-admin', 'WAIC_INSIGHTS_I18N', array(
			'view' => esc_html__('View', 'ai-copilot-content-generator'),
			'masked' => esc_html__('masked', 'ai-copilot-content-generator'),
			'pagination' => esc_html__('%1$d conversations, page %2$d of %3$d', 'ai-copilot-content-generator'),
			'conversation' => esc_html__('Conversation', 'ai-copilot-content-generator'),
			'loadError' => esc_html__('Could not load Insights data.', 'ai-copilot-content-generator'),
			'timeoutError' => esc_html__('Insights data request timed out. Please try a smaller date range.', 'ai-copilot-content-generator'),
			'dateRequired' => esc_html__('Select both dates before applying filters.', 'ai-copilot-content-generator'),
			'unknown' => esc_html__('unknown', 'ai-copilot-content-generator'),
			'currencySymbol' => WaicUtils::getArrayValue($pricingStatus, 'display_currency_symbol', '$'),
			'apply' => esc_html__('Apply filters', 'ai-copilot-content-generator'),
			'pricingSaved' => esc_html__('Pricing settings saved.', 'ai-copilot-content-generator'),
			'pricingSyncFailed' => esc_html__('Pricing sync did not complete. Last valid pricing remains active.', 'ai-copilot-content-generator'),
			'pricingImportFailed' => esc_html__('Pricing import did not complete.', 'ai-copilot-content-generator'),
		));
		$frame->addStyle('waic-insights-admin', $path . 'css/admin.insights.css');

		$model = $this->getModel();
		$this->assign('filters', $model->getDefaultFilters());
		$this->assign('chatbots', $model->getChatbotOptions());
		$this->assign('usage_options', $model->getUsageOptions());
		$this->assign('is_pro', WaicFrame::_()->isPro());
		$this->assign('pro_url', WaicFrame::_()->getProUrl());
		$this->assign('pricing_status', $pricingStatus);

		return parent::getContent('adminInsights');
	}
}
