<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInsights extends WaicModule {
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		add_action('rest_api_init', array($this, 'restApiInit'));
		add_action('waic_rollup_history_daily', array($this, 'rollupHistoryDaily'));
		add_action('waic_cleanup_history', array($this, 'cleanupHistory'));
		add_action('waic_pricing_custom_url_sync', array($this, 'syncRemotePricing'));
		add_filter('wp_privacy_personal_data_exporters', array($this, 'registerPrivacyExporters'));
		add_filter('wp_privacy_personal_data_erasers', array($this, 'registerPrivacyErasers'));
		add_action('admin_init', array($this, 'addPrivacyPolicyContent'));
		if (!wp_next_scheduled('waic_rollup_history_daily')) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'waic_rollup_history_daily');
		}
		if (!wp_next_scheduled('waic_cleanup_history')) {
			wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'daily', 'waic_cleanup_history');
		}
		$this->getModel('pricing')->ensureBundled();
		$this->syncPricingCronSchedule();
	}

	public function addAdminTab( $tabs ) {
		$tabs[$this->getCode()] = array(
			'label' => esc_html__('Insights', 'ai-copilot-content-generator'),
			'callback' => array($this, 'showInsights'),
			'fa_icon' => 'fa-bar-chart',
			'sort_order' => 35,
			'bread' => true,
		);
		return $tabs;
	}

	public function showInsights() {
		WaicFrame::_()->getModule('adminmenu')->setLastBread(esc_html__('AI Analytics', 'ai-copilot-content-generator'));
		return $this->getView()->showInsights();
	}

	public function registerPrivacyExporters( $exporters ) {
		$exporters['aiwu-insights-360'] = array(
			'exporter_friendly_name' => esc_html__('AIWU Insights 360', 'ai-copilot-content-generator'),
			'callback' => array($this, 'exportPrivacyData'),
		);
		return $exporters;
	}

	public function registerPrivacyErasers( $erasers ) {
		$erasers['aiwu-insights-360'] = array(
			'eraser_friendly_name' => esc_html__('AIWU Insights 360', 'ai-copilot-content-generator'),
			'callback' => array($this, 'erasePrivacyData'),
		);
		return $erasers;
	}

	public function exportPrivacyData( $emailAddress, $page = 1 ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	public function erasePrivacyData( $emailAddress, $page = 1 ) {
		return array(
			'items_removed' => false,
			'items_retained' => false,
			'messages' => array(),
			'done' => true,
		);
	}

	public function addPrivacyPolicyContent() {
		if (!function_exists('wp_add_privacy_policy_content')) {
			return;
		}
		wp_add_privacy_policy_content(
			esc_html__('AIWU Insights 360', 'ai-copilot-content-generator'),
			wp_kses_post(__('AIWU can send prompts, selected model identifiers and files only to an AI provider explicitly enabled by an administrator. Provider credentials are stored separately from ordinary settings with authenticated encryption and are never displayed after saving. AIWU Insights stores aggregate usage, cost and troubleshooting signals; it does not expose prompts, raw provider errors, credentials, uploaded files or network addresses in reporting surfaces. Consult the provider links shown in AI settings for that service’s Terms and Privacy Policy.', 'ai-copilot-content-generator'))
		);
	}

	public function restApiInit() {
		$routes = array(
			'/insights/overview' => array($this, 'restOverview'),
			'/insights/usage/chart' => array($this, 'restUsageChart'),
			'/insights/usage/table' => array($this, 'restUsageTable'),
			'/insights/usage/drill' => array($this, 'restUsageDrill'),
			'/insights/usage/events' => array($this, 'restUsageEvents'),
			'/insights/usage/expensive-sessions' => array($this, 'restExpensiveSessions'),
		);
		foreach ($routes as $route => $callback) {
			register_rest_route('waic/v1', $route, array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => $callback,
				'permission_callback' => array($this, 'restPermission'),
			));
		}
		register_rest_route('waic/v1', '/insights/usage/session/(?P<session_id>[^/]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'restUsageSession'),
			'permission_callback' => array($this, 'restPermission'),
		));
		register_rest_route('waic/v1', '/insights/usage/export', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'restUsageExport'),
			'permission_callback' => array($this, 'restPermission'),
		));
	}

	public function restPermission() {
		return current_user_can(apply_filters('waic_insights_capability', 'manage_options'));
	}

	private function restResponse( $method, WP_REST_Request $request ) {
		$model = $this->getModel();
		if (!method_exists($model, $method)) {
			return new WP_Error('waic_insights_unavailable', esc_html__('Insights endpoint is unavailable.', 'ai-copilot-content-generator'), array('status' => 404));
		}
		$data = $model->$method($request->get_params());
		if (false === $data) {
			return new WP_Error('waic_insights_error', esc_html__('Could not load Insights data.', 'ai-copilot-content-generator'), array('status' => 400));
		}
		return rest_ensure_response($data);
	}

	public function restOverview( WP_REST_Request $request ) {
		return $this->restResponse('getOverview', $request);
	}
	public function restUsageChart( WP_REST_Request $request ) {
		return $this->restResponse('getUsageChart', $request);
	}
	public function restUsageTable( WP_REST_Request $request ) {
		return $this->restResponse('getUsageTable', $request);
	}
	public function restUsageDrill( WP_REST_Request $request ) {
		return $this->restResponse('getUsageDrill', $request);
	}
	public function restUsageEvents( WP_REST_Request $request ) {
		return $this->restResponse('getUsageEvents', $request);
	}
	public function restExpensiveSessions( WP_REST_Request $request ) {
		return $this->restResponse('getExpensiveSessions', $request);
	}
	public function restUsageSession( WP_REST_Request $request ) {
		return $this->restResponse('getUsageSession', $request);
	}
	public function restUsageExport( WP_REST_Request $request ) {
		return $this->restResponse('getUsageExport', $request);
	}
	public function rollupHistoryDaily() {
		$result = WaicFrame::_()->getModule('workspace')->getModel('history')->rollupHistoryDaily();
		$this->getModel()->rollupInsights360(2);
		return $result;
	}
	public function cleanupHistory() {
		return WaicFrame::_()->getModule('workspace')->getModel('history')->cleanupRawHistory();
	}
	public function syncRemotePricing() {
		return $this->getModel('pricing')->syncRemote(false);
	}
	public function syncPricingCronSchedule() {
		wp_clear_scheduled_hook('waic_pricing_remote_sync');
		$status = $this->getModel('pricing')->getStatus();
		if ('custom_url' === WaicUtils::getArrayValue($status, 'source_mode', 'bundled') && 1 === (int) WaicUtils::getArrayValue($status, 'auto_sync_enabled', 0, 1) && 1 === (int) WaicUtils::getArrayValue($status, 'can_sync', 0, 1)) {
			if (!wp_next_scheduled('waic_pricing_custom_url_sync')) {
				wp_schedule_event(time() + (3 * HOUR_IN_SECONDS), 'daily', 'waic_pricing_custom_url_sync');
			}
		} else {
			wp_clear_scheduled_hook('waic_pricing_custom_url_sync');
		}
	}
}
