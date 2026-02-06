<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicWorkspace extends WaicModule {
	
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		add_action('waic_run_generation_task', array($this, 'doGenerationTask'), 10, 1);
		add_action('waic_run_delayed_actions', array($this, 'doDelayedActions'), 10, 1);
		add_action('waic_run_scheduled_task', array($this, 'doScheduledTasks'), 10, 1);
		add_filter('cron_schedules', array($this, 'addCronInterval'));
				
		if ( is_admin() ) {
			add_action('admin_notices', array($this, 'showAdminInfo'));
		}
		$this->runPreparedTask();
		$this->runSchedulededTask();
	}
	
	public function addCronInterval( $schedules ) {
		$schedules['waic_interval'] = array(
			'interval' => 60 * 15,
			'display'  => 'Every 15 minutes',
		);
		$schedules['waic_interval5'] = array(
			'interval' => 60 * 5,
			'display'  => 'Every 5 minutes',
		);
		$schedules['waic_interval1'] = array(
			'interval' => 60,
			'display'  => 'Every minute',
		);
		return $schedules;
	}
	
	public function showAdminInfo() {
		return $this->getView()->showAdminInfo();
	}
	
	public function addAdminTab( $tabs ) {
		$icon = WaicFrame::_()->isPro() ? '' : ' wbw-show-pro';
		$code = $this->getCode();
		$tabs[$code] = array('label' => esc_html__('Apps', 'ai-copilot-content-generator'), 'callback' => array($this, 'showWorkspace'), 'fa_icon' => 'fa-list', 'sort_order' => 10, 'add_bread' => $this->getCode());
		//$tabs['history'] = array('label' => esc_html__('Scenarios', 'ai-copilot-content-generator'), 'hidden' => true, 'callback' => array($this, 'showHistory'), 'fa_icon' => 'fa-list', 'sort_order' => 20, 'add_bread' => $this->getCode());
		return $tabs;
	}
	
	public function showWorkspace() {
		return $this->getView()->showWorkspace();
	}
	public function showHistory() {
		return $this->getView()->showHistory();
	}
	public function getWorkspaceTabsList( $current = '' ) {
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
		);

		if (empty($current) || !isset($tabs[$current])) {
			reset($tabs);
			$current = key($tabs);
		}
		$tabs[$current]['class'] .= ' current';
		
		return WaicDispatcher::applyFilters('getWorkspaceTabsList', $tabs);
	}
	
	public function getWorkspaceFeatures() {
		$features = array(
			'workflow' => array(
				'title' => __('Workflow Builder'),
				'desc' => __('Build custom AI automations with a visual drag-and-drop editor. Connect triggers, AI actions, and WordPress tools seamlessly.', 'ai-copilot-content-generator'),
				'class' => 'wbw-ws-block-big',
				'fake' => true,
				'hidden' => true,
			),
			'template' => array(
				'title' => __('Workflow Template'),
				'desc' => '',
				'fake' => true,
				'hidden' => true,
			),
			'mcp' => array(
				'title' => __('Claude MCP Integration', 'ai-copilot-content-generator'),
				'desc' => __('Connect Claude AI directly to WordPress. Chat naturally to create pages, manage content, and control your entire site.', 'ai-copilot-content-generator'),
				'link' => $this->getFeatureUrl('settings', 'mcp'),
				'fake' => true,
			),
			'chatbots' => array(
				'title' => __('AI Chatbot', 'ai-copilot-content-generator'),
				'desc' => __('Engage visitors with customizable AI chatbots. Personalize design, track conversations, and improve the overall user experience.', 'ai-copilot-content-generator'),
			),
			'forms' => array(
				'title' => __('AI Forms', 'ai-copilot-content-generator'),
				'desc' => __('Design customizable AI-powered forms for surveys, quizzes, or polls. Define prompts, set logic, and display results anywhere.', 'ai-copilot-content-generator'),
			),
			'postsrss' => array(
				'title' => __('Autoblogging', 'ai-copilot-content-generator'),
				'desc' => __('Automate blog articles from RSS feeds and industry news, with draft social media posts generated alongside.', 'ai-copilot-content-generator'),
				'pro' => true,
			),
			'postscreate' => array(
				'title' => __('Bulk Post Generator', 'ai-copilot-content-generator'),
				'desc' => __('Generate single or multiple articles at once. Populate your WordPress site with high-quality content efficiently and quickly.', 'ai-copilot-content-generator'),
			),
			'postsfields' => array(
				'title' => __('Bulk Post Field Generator', 'ai-copilot-content-generator'),
				'desc' => __('Edit and update multiple article details in bulk. Save time and reduce manual work with AI-driven field generation.', 'ai-copilot-content-generator'),
			),
			'productsfields' => array(
				'title' => __('WooCommerce Product Generator', 'ai-copilot-content-generator'),
				'desc' => __('Auto-create product descriptions, categories, tags, images, and reviews. Bulk generation for WooCommerce powered entirely by AI.', 'ai-copilot-content-generator'),
				'pro' => true,
			),
			'postslinks' => array(
				'title' => __('Smart Post Crosslinking', 'ai-copilot-content-generator'),
				'desc' => __('Insert internal and external links across articles automatically. AI analyzes content and adds relevant links for SEO growth.', 'ai-copilot-content-generator'),
				'pro' => true,
			),
			'magictext' => array(
				'title' => __('AI Magic Text Enhancer', 'ai-copilot-content-generator'),
				'desc' => __('Enhance content instantly in the editor. Rewrite, expand, or polish text with AI suggestions tailored to your writing needs.', 'ai-copilot-content-generator'),
			),
			'training' => array(
				'title' => __('AI Training', 'ai-copilot-content-generator'),
				'desc' => __('Unlock the full power of AIWU by training custom language models on your data.', 'ai-copilot-content-generator'),
				//'hidden' => true,
				'pro' => true,
			),
			'developer' => array(
				'title' => __('Developer API', 'ai-copilot-content-generator'),
				'desc' => __('Use everything above programmatically via REST and client-side calls.', 'ai-copilot-content-generator'),
				'class' => 'wbw-ws-block-big wbw-ws-block-vivid',
				'link' => 'https://aiwuplugin.com/api/',
				'target' => '_blank',
				'full' => true,
				'fake' => true,
			),
		);
		
		//return WaicDispatcher::applyFilters('getWorkspaceFeatures', $features);
		return $features;
	}
	public function getFeaturesList( $fake = true ) {
		$blocks = $this->getWorkspaceFeatures();
		$features = array();
		foreach ($blocks as $key => $block) {
			if ($fake || empty($block['fake'])) {
				$features[$key] = $block['title'];
			}
		}
		return $features;
	}
		
	public function getFeatureUrl( $feature = '', $cur = '' ) {
		static $mainUrl;
		if (empty($mainUrl)) {
			$mainUrl = WaicFrame::_()->getModule('adminmenu')->getMainLink();
		}
		$url = $mainUrl;
		if (!empty($feature)) {
			$url .= '&tab=' . $feature;
		}
		if (!empty($cur)) {
			$url .= '&cur=' . $cur;
		}
		return $url;
	}
	public function getTaskUrl( $taskId, $feature = '' ) {
		static $mainUrl;
		if (empty($mainUrl)) {
			$mainUrl = WaicFrame::_()->getModule('adminmenu')->getMainLink();
		}
		if (empty($feature)) {
			$feature = $this->getModel('tasks')->getTaskFeature($taskId);
			/*if ($task) {
				$feature = $task['feature'];
				$module = WaicFrame::_()->getModule($feature);
				if ($module) {
					return $module->showTaskTabContent($task);
				}
			}*/
		}
		/*if ('workflow' == $feature) {
			$feature = 'builder';
		}*/
		return $mainUrl . '&tab=' . ( empty($feature) ? $this->getCode() : $feature ) . ( empty($taskId) ? '' : '&task_id=' . $taskId );
	}
	/*public function getStopTaskUrl( $taskId ) {
		static $mainUrl;
		if (empty($mainUrl)) {
			$mainUrl = WaicFrame::_()->getModule('adminmenu')->getMainLink();
		}
		return $mainUrl . '&tab=' . $this->getCode() . '&task_id=' . $taskId;
	}*/

	public function getTaxonomyHierarchy( $taxonomy, $argsIn, $parent = true, $r = 0 ) {
		$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
		$args = array(
			'taxonomy' => $taxonomy,
			'hide_empty' => $argsIn['hide_empty'],
		);
		if (isset($argsIn['order'])) {
			$args['orderby'] = !empty($argsIn['orderby']) ? $argsIn['orderby'] : 'name';
			$args['order']   = $argsIn['order'];
		}

		if ( !empty($argsIn['parent']) && 0 !== $argsIn['parent'] ) {
			$args['parent'] = $argsIn['parent'];
		} else {
			$args['parent'] = 0;
		}

		if ('' === $taxonomy) {
			return false;
		}

		if ( 'product_cat' === $taxonomy && $parent ) {
			$args['parent'] = 0;
		}
		$terms = get_terms( $args );
		$children = array();
		if (!is_wp_error($terms)) {
			foreach ( $terms as $term ) {
				if (empty($argsIn['only_parent'])) {
					if (!empty($term->term_id)) {
						$args = array(
							'hide_empty' => $argsIn['hide_empty'],
							'parent' => $term->term_id,
						);
						if (isset($argsIn['order'])) {
							$args['order']   = $argsIn['order'];
							$args['orderby'] = !empty($argsIn['orderby']) ? $argsIn['orderby'] : 'name';
						}
						$term->children = $this->getTaxonomyHierarchy( $taxonomy, $args, false, $r + 1 );
					}
				}
				//$children[ $term->term_id ] = $term;
				$children[ $term->term_id ] = str_repeat('—', $r) . $term->name;
				foreach ($term->children as $k => $t) {
					$children[ $k ] = str_repeat('—', $r) . $t;
				}
			}
		}
		return $children;
	}
	public function getUsersList( $arr = false ) {
		$list = is_array($arr) ? $arr : array();
		$users = get_users();
		if ($users) {
			foreach ($users as $user) {
				$list[$user->ID] = $user->display_name;
			}
		}
		return $list;
	}
	public function getCustomTaxonomiesList( $type = 'post', $add = '' ) {
		$isProduct = ( 'product' == $type );
		if ($isProduct) {
			$exclude = array('product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class');
		} else {
			$exclude = array('category', 'post_tag', 'post_format');
		}
		
		$taxs = array();
		foreach ( get_object_taxonomies($type, 'objects') as $slug => $tax ) {
			if ( ! in_array( $slug, $exclude ) ) {
				if (!$isProduct || strpos($slug, 'pa_') !== 0) {
					$taxs[$slug] = $add . $tax->label;
				}
			}
		}
		return $taxs;
	}
	public function runPreparedTask() {
		if (!wp_next_scheduled('waic_run_generation_task') && !$this->getModel()->isRunningFlag()) {
			$need = false;
			if (!empty($this->getModel()->getRunningTask())) {
				$need = true;
			} else {
				$prepared = $this->getModel('tasks')->getPreparedTask();
				if (!empty($prepared)) {
					$this->getModel()->setRunningTask($prepared);
					$need = true;
				}
			}
			if ($need) {
				if (!wp_next_scheduled('waic_run_generation_task')) {
					wp_schedule_single_event(time(), 'waic_run_generation_task');
				}
			}
		}
		//wp_clear_scheduled_hook('waic_run_delayed_actions');
		if (!wp_next_scheduled('waic_run_delayed_actions')) {
			wp_schedule_event(time(), 'hourly', 'waic_run_delayed_actions');
		}
	}
	public function runSchedulededTask( $force = false ) {
		$minCycle = $this->getModel('tasks')->getMinCycle();
		if (wp_next_scheduled('waic_run_scheduled_task')) {
			if (empty($minCycle)) {
				$timestamp = wp_next_scheduled('waic_run_scheduled_task');
				wp_unschedule_event( $timestamp, 'waic_run_scheduled_task');
			}
		} else if (!empty($minCycle)) {
			wp_reschedule_event( time(), 'waic_interval', 'waic_run_scheduled_task' );
		}
		if ($force && wp_next_scheduled('waic_run_scheduled_task')) {
			/**
			 * Do custom action
			 * 
			 * @since 3.4
			*/
			do_action('waic_run_scheduled_task');
		}
	}
	
	public function doScheduledTasks() {
		$model = $this->getModel();
		$result = $model->doScheduledTasks();
		if (!$result) {
			$model->setStoppingTaskGeneration();
			$model->resetRunningFlag();
			WaicFrame::_()->saveDebugLogging();
		}
	}
	
	public function runGenerationTask( $force = false ) {
		if (!wp_next_scheduled('waic_run_generation_task') && !$this->getModel()->isRunningFlag()) {
			wp_schedule_single_event(time(), 'waic_run_generation_task');
		}
		if ($force) {
			/**
			 * Do custom action
			 * 
			 * @since 3.4
			*/
			do_action('waic_run_generation_task');
		}
	}
	public function doGenerationTask() {
		$model = $this->getModel();
		$result = $model->doGenerationTasks();
		if (!$result) {
			$model->setStoppingTaskGeneration();
			$model->resetRunningFlag();
			WaicFrame::_()->saveDebugLogging();
		}
	}
	public function doDelayedActions() {
		$result = $this->getModel()->doDelayedActions();
		if (!$result) {
			WaicFrame::_()->saveDebugLogging();
		}
	}
}
