<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicMagictextView extends WaicView {
	public function showCreateTabContent( $id = 0, $task = array() ) {
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();

		$assets->loadSlider();
		$assets->loadChosenSelects();
		$assets->loadDateTimePicker();

		$path = $this->getModule()->getModPath() . 'assets/';
		$frame->addScript('waic-posts-mt', $path . 'js/admin.magictext.js');

		$options = $frame->getModule('options')->getModel();
		$module = $frame->getModule('magictext');
		$model = $module->getModel();
		$opts = $model->getAll();
		$tasks = $frame->getModule('workspace')->getModel('tasks');

		$lang = array(
			'add-btn' => esc_html__('Add', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'confirm-restore' => esc_html__('Do you really want to restore the settings to their default values?', 'ai-copilot-content-generator'),
			'need-title' => esc_html( __('Please enter a title', 'ai-copilot-content-generator') ),
		);

		if (empty($task)) {
			$task = $tasks->getTask($id);
		}

		$settings = empty($task) ? array() : $model->convertTaskParameters($task['params'], false);

		$apiOptions = WaicUtils::getArrayValue($opts, 'api', array(), 2);
		if (empty($apiOptions)) {
			$apiOptions = $options->get('api');
		}

		$this->assign('lang', WaicDispatcher::applyFilters('addLangMagictext', $lang));
		$this->assign('tabs', $module->getMagictextTabsList());
		$this->assign('is_pro', $frame->isPro());
		$this->assign('options', array('api' => $apiOptions));
		$this->assign('variations', $options->getVariations());
		$this->assign('defaults', $options->getDefaults());
		$this->assign('task_id', $id);
		$this->assign('settings', $settings);
		$this->assign('fields', $opts['fields']);
		$this->assign('custom_slug', '');
		$this->assign('body_mode', 'single');
		$this->assign('task_title', WaicUtils::getArrayValue($task, 'title'));
		$this->assign('read_only', false);
		$this->assign('published', ( $task && !$tasks->isTaskInPause($task['status']) ));

		return parent::getContent('adminMagictext');
	}
}
