<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicWorkflow extends WaicModule {
	
	public function init() {
		require_once __DIR__ . WAIC_DS . 'phase0' . WAIC_DS . 'bootstrap.php';
		WaicWorkflowPhase0Bootstrap::load();
		require_once __DIR__ . WAIC_DS . 'phase1' . WAIC_DS . 'bootstrap.php';
		WaicWorkflowPhase1Bootstrap::register();

		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		if ( $this->isPhase0RunnerEnabled() ) {
			add_action('waic_phase0_create_scheduled_flow', array($this, 'doScheduledFlows'), 10, 1);
			add_action('waic_phase0_run_workflow', array($this, 'doWorkflowRuns'), 10, 1);
			$this->runCronEvents();
		}
		wp_clear_scheduled_hook('waic_create_scheduled_flow');
		wp_clear_scheduled_hook('waic_run_workflow');
		add_action('admin_enqueue_scripts', array($this, 'disableConflictingScripts'), 100);
	}
	public function isPhase0RunnerEnabled() {
		return defined( 'AIWU_WORKFLOW_PHASE0_RUNTIME_ENABLED' )
			&& true === constant( 'AIWU_WORKFLOW_PHASE0_RUNTIME_ENABLED' )
			&& WaicWorkflowPhase0RuntimePolicy::isEnabled( 'runner', get_current_blog_id() );
	}

	public function webhookRestApiInit() {
		return false;
	}
	public function oauthRedirect() {
		return false;
	}

	public function controlUrlTrigger() {
		return false;
	}
	public function disableConflictingScripts() {
		$screen = get_current_screen();
		if ($screen->id === 'toplevel_page_waic-workspace') {
			wp_deregister_script('svg-painter');
			//wp_deregister_script('heartbeat');
			wp_deregister_script('customize-controls');
			wp_deregister_script('block-editor');
			//wp_deregister_script('updates');
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
			WaicFrame::_()->pushError( esc_html__( 'Template cloning is unavailable until the guarded Phase 0 command handler is enabled.', 'ai-copilot-content-generator' ) );
			return $this->getView()->showWorkflow();
		}
		return $this->getView()->showWorkflowBuilder($taskId);
	}
	
	public function createWorkflowByTemplate() {
		WaicFrame::_()->pushError( esc_html__( 'Template cloning is unavailable until the guarded Phase 0 command handler is enabled.', 'ai-copilot-content-generator' ) );
		$taskId = 0;
		$url = WaicFrame::_()->getModule('workspace')->getTaskUrl($taskId, 'builder');
		if (headers_sent()) {
			echo '<script type="text/javascript"> document.location.href="' . esc_url($url) . '"; </script>';
		} else {
			wp_safe_redirect($url);
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
			wp_clear_scheduled_hook('waic_phase0_create_scheduled_flow');
		} else if (!wp_next_scheduled('waic_phase0_create_scheduled_flow')) {
			wp_schedule_event( time(), 'waic_interval5', 'waic_phase0_create_scheduled_flow' );
		} else if ($force) {
			wp_reschedule_event( time(), 'waic_interval5', 'waic_phase0_create_scheduled_flow' );
		}
		if (!wp_next_scheduled('waic_phase0_run_workflow')) {
			wp_schedule_event( time(), 'waic_interval1', 'waic_phase0_run_workflow' );
		}
	}
	
	public function doScheduledFlows() {
		if ( ! $this->isPhase0RunnerEnabled() ) {
			return false;
		}
		// Legacy scheduled model execution is intentionally quarantined. Canonical
		// run intents are consumed only by the Phase 0 journal/outbox runner.
		return false;
	}
	public function doWorkflowRuns() {
		if ( ! $this->isPhase0RunnerEnabled() ) {
			return false;
		}
		return false;
	}
}
