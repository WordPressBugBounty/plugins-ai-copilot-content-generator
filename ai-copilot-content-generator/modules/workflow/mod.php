<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicWorkflow extends WaicModule {
	
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		add_action('rest_api_init', array($this, 'webhookRestApiInit'));
		add_action('init', array($this, 'controlUrlTrigger'));
		add_action('waic_create_scheduled_flow', array($this, 'doScheduledFlows'), 10, 1);
		add_action('waic_run_workflow', array($this, 'doWorkflowRuns'), 10, 1);
		$this->runCronEvents();
		$this->getModel()->doHookedFlows();
		add_action('admin_enqueue_scripts', array($this, 'disableConflictingScripts'), 100);
	}
	public function webhookRestApiInit() {
		register_rest_route('aiwu/v1', '/oauth2callback', [
			'methods' => 'GET',
			'callback' => array($this, 'oauthRedirect'),
			'permission_callback' => '__return_true',
		]);
		$this->getModel('workflow')->registerWebhookRoutes();
	}
	public function oauthRedirect() {
		$code = WaicReq::getVar('code');
		$integ = WaicReq::getVar('cur');

		header('Content-Type: text/html; charset=utf-8');
    		echo "<script>
			window.opener.postMessage({type: 'oauth_code', code: '$code'}, '*');
			window.close();
		</script>";
	}

	public function controlUrlTrigger() {
		$url = $_SERVER['REQUEST_URI'];
		if (
			is_admin() ||
			wp_doing_ajax() ||
			str_starts_with($url, '/wp-json/') ||
			str_starts_with($url, '/wp-cron.php') ||
			str_starts_with($url, '/favicon.ico') ||
			preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|map)(\?.*)?$#', $url)
		) {
			return;
		}
	
		$this->getModel('workflow')->runUrlTriggers($url);
	}
	public function disableConflictingScripts() {
		$screen = get_current_screen();
		if ($screen->id === 'toplevel_page_waic-workspace') {
			wp_deregister_script('svg-painter');
			wp_deregister_script('heartbeat');
			wp_deregister_script('customize-controls');
			//wp_deregister_script('media-editor');
			wp_deregister_script('block-editor');
			wp_deregister_script('updates');
		}
	}
	
	public function addAdminTab( $tabs ) {
		$code = $this->getCode();
		$tabs[$code] = array('label' => esc_html__('Workflows', 'ai-copilot-content-generator'), 'callback' => array($this, 'showWorkflow'), 'fa_icon' => 'fa-list', 'sort_order' => 5, 'add_bread' => $this->getCode());
		
		$tabs['builder'] = array(
			'label' => esc_html__('Builder', 'ai-copilot-content-generator'), 
			'hidden'     => 1,
			'sort_order' => 0,
			'callback' => array($this, 'showWorkflowBuilder'), 
			'bread'      => false,
			'last_Id' => 'waicTaskNameWrapper'
		);
		$tabs['template'] = array(
			'label' => esc_html__('Template', 'ai-copilot-content-generator'), 
			'hidden'     => 1,
			'sort_order' => 1,
			'callback' => array($this, 'createWorkflowByTemplate'), 
			'bread'      => false,
			'last_Id' => 'waicTaskNameWrapper'
		);
		$tabs['oauth'] = array(
			'label' => '', 
			'hidden'     => 1,
			'sort_order' => 0,
			'callback' => array($this, 'oauthRedirect'), 
			'bread'      => false,
		);
		return $tabs;
	}
	public function showWorkflow() {
		$taskId = WaicReq::getVar('task_id');
		if (!empty($taskId)) {
			return $this->getView()->showWorkflowBuilder($taskId);
		}
		return $this->getView()->showWorkflow();
	}
	
	public function showWorkflowBuilder() {
		$taskId = WaicReq::getVar('task_id');
		$feature = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTaskFeature($taskId);
		if ('template' == $feature) {
			$taskId = $this->getModel()->createWorkflowByTemplate($taskId);
		}
		return $this->getView()->showWorkflowBuilder($taskId);
	}
	
	public function createWorkflowByTemplate() {
		$taskId = WaicReq::getVar('task_id');
		$taskId = $this->getModel()->createWorkflowByTemplate($taskId);
		$url = WaicFrame::_()->getModule('workspace')->getTaskUrl($taskId, 'builder');
		if (headers_sent()) {
			echo '<script type="text/javascript"> document.location.href="' . $url . '"; </script>';
		} else {
			wp_redirect($url);
		}
		
		exit;
		//return $this->getView()->showWorkflowBuilder($taskId);
	}
	
	public function getWorkflowTabsList( $current = '' ) {
		$tabs = array(
			'new' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Create New', 'ai-copilot-content-generator'),
			),
			'history' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Scenarios', 'ai-copilot-content-generator'),
			),
			'integrations' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Integrations', 'ai-copilot-content-generator'),
			),
		);

		if (empty($current) || !isset($tabs[$current])) {
			reset($tabs);
			$current = key($tabs);
		}
		$tabs[$current]['class'] .= ' current';
		
		return WaicDispatcher::applyFilters('getWorkspaceTabsList', $tabs);
	}
	
	
	public function runCronEvents( $force = false ) {
		$existScheduled = $this->getModel('workflow')->existScheduledFlows();
		if (empty($existScheduled)) {
			wp_clear_scheduled_hook('waic_create_scheduled_flow');
		} else if (!wp_next_scheduled('waic_create_scheduled_flow')) {
			wp_schedule_event( time(), 'waic_interval5', 'waic_create_scheduled_flow' );
		} else if ($force) {
			wp_reschedule_event( time(), 'waic_interval5', 'waic_create_scheduled_flow' );
		}
		if (!wp_next_scheduled('waic_run_workflow')) {
			wp_schedule_event( time(), 'waic_interval1', 'waic_run_workflow' );
		}
	}
	
	public function doScheduledFlows() {
		$result = $this->getModel()->doScheduledFlows();
		if (!$result) {
			WaicFrame::_()->saveDebugLogging();
		}
	}
	public function doWorkflowRuns() {
		$result = $this->getModel()->doFlowRuns();
		if (!$result) {
			WaicFrame::_()->saveDebugLogging();
		}
	}
}
