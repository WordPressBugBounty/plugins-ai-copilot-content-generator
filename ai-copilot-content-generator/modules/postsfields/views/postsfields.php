<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostsfieldsView extends WaicView {

	public function showCreateTabContent( $id = 0, $task = array(), $showSettings = false ) {
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();

		$assets->loadSlider();
		$assets->loadChosenSelects();
		//$assets->loadDateTimePicker();
		
		$path = $this->getModule()->getModPath() . 'assets/';
		$postsModule = $frame->getModule('postscreate');
		$postsPath = $postsModule->getModPath() . 'assets/';
		
		$frame->addScript('waic-posts-create', $postsPath . 'js/admin.postscreate.js');
		$assets->loadDataTables(array('responsive'));
		$frame->addScript('waic-posts-fields', $path . 'js/admin.postsfields.js', array('waic-posts-create'));

		$options = $frame->getModule('options')->getModel();
		$module = $frame->getModule('postsfields');
		$model = $module->getModel();

		$isNew = empty($id);
		if (empty($task)) {
			$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($id);
		} 
		$settings = empty($task) ? array() : $model->convertTaskParameters($task['params'], false);
		
		$apiOptions = WaicUtils::getArrayValue($settings, 'api', array(), 2);
		if (empty($apiOptions)) {
			$apiOptions = $options->get('api');
		}
		$lang = array(
			'emptyTable' => esc_html__('Posts not yet selected', 'ai-copilot-content-generator'),
			'emptyTableSearch' => esc_html__('Posts not found', 'ai-copilot-content-generator'),
			'pageNext' => esc_html__('Next', 'ai-copilot-content-generator'),
			'pagePrev' => esc_html__('Prev', 'ai-copilot-content-generator'),
			'lengthMenu' => esc_html__('per page', 'ai-copilot-content-generator'),
			'add-btn' => esc_html__('Add', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'pageAll' => esc_html__('All', 'ai-copilot-content-generator'),
		);

		$this->assign('lang', $lang);
		$this->assign('tabs', $module->getFieldsTabsList());
		$this->assign('options', array('api' => $apiOptions));
		$this->assign('variations', $options->getVariations());
		$this->assign('defaults', $options->getDefaults());
		$this->assign('task_id', $id);
		$this->assign('settings', $settings);
		$this->assign('fields', $postsModule->getModel()->getFields('postsfields'));
		$this->assign('custom_slug', '');
		//$this->assign('body_mode', 'single');
		$this->assign('tpl_path', WAIC_MODULES_DIR . 'postscreate/views/tpl/');
		$this->assign('task_title', WaicUtils::getArrayValue($task, 'title'));
		$this->assign('read_only', $showSettings && !empty($task['status']) && 9 > $task['status']);

		return parent::getContent('adminFieldsCreate');
	}
	
	public function showResultTabContent( $task ) {
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();

		$assets->loadChosenSelects();
		//$assets->loadDateTimePicker();
		wp_enqueue_editor();
		wp_enqueue_script('media-upload');
		
		$path = $this->getModule()->getModPath() . 'assets/';
		$postsModule = $frame->getModule('postscreate');
		$postsPath = $postsModule->getModPath() . 'assets/';
		
		$fieldsModel = $frame->getModule('postsfields')->getModel();
		
		$frame->addScript('waic-posts-result', $postsPath . 'js/admin.postsresults.js');
		//WaicDispatcher::doAction('addPostsResultsAssets');
		$assets->loadDataTables(array('responsive'));
		WaicFrame::_()->addScript('waic-posts-results-bulk', $postsPath . 'js/admin.postsresults.bulk.js');

		$taskModel = $frame->getModule('workspace')->getModel('tasks');
		$module = $frame->getModule('postscreate');
		$model = $module->getModel();
		$statuses = $taskModel->getStatuses();
		$taskId = $task['id'];
		
		$lang = array(
			'stop' => esc_html__('Stop', 'ai-copilot-content-generator'),
			'start' => esc_html__('Start', 'ai-copilot-content-generator'),
			'save-btn' => esc_html__('Save', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'confirm-back' => esc_html__('This action will stop the generation, delete all unpublished results, but allow you to edit the generation parameters. Are you sure this is what you want?', 'ai-copilot-content-generator'),
		);
		
		$this->assign('lang', WaicDispatcher::applyFilters('addLangPostsResults', $lang));
		$this->assign('is_pro', 1);
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		//$this->assign('is_bulk', $frame->isPro() && $task['cnt'] > 1 ? 1 : 0 );

		$this->assign('task', $task);
		$this->assign('task_id', $taskId);
		$this->assign('running_task', $frame->getModule('workspace')->getModel()->getRunningTask());
		$this->assign('fields', $model->getFields());
		$this->assign('results_all', 1 == $task['cnt'] ? $model->getTaskResults($taskId) : array());
		$this->assign('statuses', $statuses);
		$this->assign('with_socials', !empty(WaicUtils::getArrayValue($params, 'socials', array(), 2)));
		$this->assign('actions', $taskModel->getTaskActions($taskId, $task['status']));
		//$this->assign('sheduled', $rssModel->canSheduled($params));
		$this->assign('loading_text', esc_html__('Loading...', 'ai-copilot-content-generator'));
		$this->assign('error_text', esc_html__('Error', 'ai-copilot-content-generator'));
		$this->assign('section_text', esc_html__('section', 'ai-copilot-content-generator'));
		//$this->assign('categories_html', array_merge(''$this->getTaxonomyHierarchyHtml()

		return parent::getContent('adminFieldsResults');
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
		
		$fieldsModel = $frame->getModule('postsfields')->getModel();

		$this->assign('is_pro', 1);
		$this->assign('task', $task);
		$this->assign('fields', $model->getFields('postsfields'));
		//$this->assign('socials', $rssModel->getSocials());
		$this->assign('pc_data', $result);
		$this->assign('tpl_path', WAIC_MODULES_DIR . 'postscreate/views/tpl/');
		$this->assign('loading_text', esc_html__('Loading...', 'ai-copilot-content-generator'));
		$this->assign('error_text', esc_html__('Error', 'ai-copilot-content-generator'));
		$this->assign('section_text', esc_html__('section', 'ai-copilot-content-generator'));

		return parent::getContent('adminFieldsResultsTable');
	}
	
	/*public function getTaxonomyHierarchy( $parent = 0, $pre = '', $tax = 'product_cat' ) {
		$args    = array(
			'hide_empty' => true,
			'parent'     => $parent
		);
		$terms   = get_terms( $tax, $args );
		$taxes = array();
		foreach ( $terms as $term ) {
			if ( ! empty( $term->term_id ) ) {
				//$taxes[$term->term_id] = $pre . esc_html( $term->name );
				$taxes[$term->term_id] = '[' . $term->term_id . '] ' . esc_html( $term->name );
				$children = $this->getTaxonomyHierarchy( $term->term_id, $pre . '&nbsp;&nbsp;&nbsp;', $tax );
				//var_dump($taxes);
				if (!empty($children)) {
					$taxes = $taxes + $children;
				}
			}
		}
		return $taxes;
	}*/
}
