<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostscreateView extends WaicView {

	public function showCreateTabContent( $id = 0, $task = array(), $showSettings = false ) {
		$accets = WaicAssets::_();
		$frame = WaicFrame::_();

		$accets->loadSlider();
		$accets->loadChosenSelects();
		$accets->loadDateTimePicker();
		
		$path = $this->getModule()->getModPath() . 'assets/';
		
		$frame->addScript('waic-posts-create', $path . 'js/admin.postscreate.js');
		WaicDispatcher::doAction('addPostsCreateAssets');

		$options = $frame->getModule('options')->getModel();
		$module = $frame->getModule('postscreate');
		$model = $module->getModel();
		
		$lang = array(
			'add-btn' => esc_html__('Add', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
		);
		$isNew = empty($id);
		if (empty($task)) {
			$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($id);
		} 
		$settings = empty($task) ? array() : $model->convertTaskParameters($task['params'], false);
		
		$apiOptions = WaicUtils::getArrayValue($settings, 'api', array(), 2);
		if (empty($apiOptions)) {
			$apiOptions = $options->get('api');
		}

		$this->assign('lang', WaicDispatcher::applyFilters('addLangPostsCreate', $lang));
		$this->assign('tabs', $module->getPostsCreateTabsList());
		$this->assign('is_pro', $frame->isPro());
		$this->assign('options', array('api' => $apiOptions));
		$this->assign('variations', $options->getVariations());
		$this->assign('defaults', $options->getDefaults());
		$this->assign('task_id', $id);
		$this->assign('settings', $settings);
		$this->assign('fields', $model->getFields());
		$this->assign('custom_slug', '');
		$this->assign('body_mode', 'single');
		$this->assign('task_title', WaicUtils::getArrayValue($task, 'title'));
		$this->assign('read_only', $showSettings && !empty($task['status']) && 9 > $task['status']);

		return parent::getContent('adminPostsCreate');
	}
	
	public function showResultTabContent( $task ) {
		$accets = WaicAssets::_();
		$frame = WaicFrame::_();

		$accets->loadChosenSelects();
		$accets->loadDateTimePicker();
		wp_enqueue_editor();
		wp_enqueue_script('media-upload');
		
		$path = $this->getModule()->getModPath() . 'assets/';
		
		$frame->addScript('waic-posts-result', $path . 'js/admin.postsresults.js');
		WaicDispatcher::doAction('addPostsResultsAssets');

		$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$module = $frame->getModule('postscreate');
		$model = $module->getModel();
		$statuses = $taskModel->getStatuses();
		$taskId = $task['id'];
		
		$lang = array(
			'stop' => esc_html__('Stop', 'ai-copilot-content-generator'),
			'start' => esc_html__('Start', 'ai-copilot-content-generator'),
			'save-btn' => esc_html__('Save', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'confirm-back' => esc_html__('This action will stop the generation, delete all results, but allow you to edit the generation parameters. Are you sure this is what you want?', 'ai-copilot-content-generator'),
		);
		
		$this->assign('lang', WaicDispatcher::applyFilters('addLangPostsResults', $lang));
		$this->assign('is_pro', $frame->isPro());
		$this->assign('is_bulk', $frame->isPro() && $task['cnt'] > 1 ? 1 : 0 );

		$this->assign('task', $task);
		$this->assign('task_id', $taskId);
		$this->assign('running_task', $frame->getModule('workspace')->getModel()->getRunningTask());
		$this->assign('fields', $model->getFields());
		$this->assign('results_all', 1 == $task['cnt'] ? $model->getTaskResults($taskId) : array());
		$this->assign('statuses', $statuses);
		$this->assign('actions', $taskModel->getTaskActions($taskId, $task['status']));
		$this->assign('loading_text', esc_html__('Loading...', 'ai-copilot-content-generator'));
		$this->assign('error_text', esc_html__('Error', 'ai-copilot-content-generator'));
		$this->assign('section_text', esc_html__('section', 'ai-copilot-content-generator'));

		return parent::getContent('adminPostsResults');
	}
	
	public function getTableResults( $task, $postId = 0 ) {
		$frame = WaicFrame::_();
		$module = $frame->getModule('postscreate');
		$model = $module->getModel();
		$taskId = $task['id'];
		
		$results = $model->getTaskResults($taskId, $postId);

		$result = empty($results[0]) ? $results : $results[0];
		$status = empty($result['status']) ? 0 : $result['status'];
		$result['can_publish'] = $model->canPostPublish($status);
		$result['can_update'] = $model->canPostUpdate($status);

		$this->assign('is_pro', $frame->isPro());
		$this->assign('task', $task);
		$this->assign('fields', $model->getFields());
		$this->assign('pc_data', $result);
		$this->assign('loading_text', esc_html__('Loading...', 'ai-copilot-content-generator'));
		$this->assign('error_text', esc_html__('Error', 'ai-copilot-content-generator'));
		$this->assign('section_text', esc_html__('section', 'ai-copilot-content-generator'));

		return parent::getContent('adminPostsResultsTable');
	}
}
