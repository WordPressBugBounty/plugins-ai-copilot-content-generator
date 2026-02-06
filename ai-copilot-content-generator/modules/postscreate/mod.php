<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostscreate extends WaicModule {
	
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
	}
	
	public function addAdminTab( $tabs ) {
		$code = 'workspace';
		$tabs['postscreate'] = array(
			'label'      => esc_html__('Create single or bulk posts', 'ai-copilot-content-generator'),
			'callback'   => array($this, 'showCreateTabContent'),
			'hidden'     => 1,
			'sort_order' => 0,
			'bread'      => true,
			'last_Id' => 'waicTaskNameWrapper',
		);
		/*$tabs['postscreate-results']   = array(
			'label'      => esc_html__( 'Results', 'ai-copilot-content-generator' ),
			'callback'   => array( $this, 'showCreateTabContent' ),
			'hidden'     => 1,
			//'hide_menu'  => 1,
			//'bread'      => array($code, 'postscreate'),
		);*/
		return $tabs;
	}
	
	public function showCreateTabContent() {
		$taskId = WaicReq::getVar('task_id');
		$title = __( 'Your Scenario name', 'ai-copilot-content-generator' );
		if (!empty($taskId)) {
			$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($taskId);
			if ($task && !empty($task['id'])) {
				WaicFrame::_()->getModule('adminmenu')->setLastBread(WaicUtils::getArrayValue($task, 'title', $title));
				$showSettings = WaicReq::getVar('show_settings') == 1;
				if (empty($task['status']) || 9 == $task['status'] || $showSettings) {
					return $this->getView()->showCreateTabContent($task['id'], $task, $showSettings);
				}
				//if (WaicUtils::getArrayValue($task, 'cnt', 0, 1) <= 1) {
					//WaicFrame::_()->getModule('adminmenu')->setActiveTab('postscreate-results');
					return $this->getView()->showResultTabContent($task);
				//}
			}
		}
		WaicFrame::_()->getModule('adminmenu')->setLastBread($title);
		return $this->getView()->showCreateTabContent($taskId);
	}
	public function getPostsCreateTabsList( $current = '' ) {
		$tabs = array(
			'params' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Generation', 'ai-copilot-content-generator'),
			),
			'api' => array(
				'class' => '',
				'pro' => false,
				'label' => __('API settings', 'ai-copilot-content-generator'),
			),
		);

		if (empty($current) || !isset($tabs[$current])) {
			reset($tabs);
			$current = key($tabs);
		}
		$tabs[$current]['class'] .= ' current';
		
		return WaicDispatcher::applyFilters('getPostsCreateTabsList', $tabs);
	}
}
