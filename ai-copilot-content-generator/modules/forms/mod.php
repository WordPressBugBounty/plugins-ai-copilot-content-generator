<?php
class WaicForms extends WaicModule {
	public function init() {
		add_shortcode(WAIC_FORM, array($this, 'renderForm'));
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		WaicDispatcher::addFilter('addTaskColumns_forms', array($this, 'addTaskColumns'), 10, 3);
	}
	
	public function addAdminTab( $tabs ) {
		$code = 'workspace';
		$tabs['forms']   = array(
			'label'      => esc_html__( 'AI Forms Builder', 'ai-copilot-content-generator' ),
			'callback'   => array( $this, 'showFormsTabContent' ),
			'hidden'     => 1,
			'sort_order' => 0,
			'bread'      => true,
			'last_Id' => 'waicTaskNameWrapper',
		);
		return $tabs;
	}
	
	public function showFormsTabContent() {
		$taskId = WaicReq::getVar('task_id');
		$title = __( 'Your Scenario name', 'ai-copilot-content-generator' );
		if (!empty($taskId)) {
			$taskTitle = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTaskTitle($taskId);
			if (!is_null($taskTitle) && !empty($taskTitle)) {
				$title = $taskTitle;
			}
		}
		WaicFrame::_()->getModule('adminmenu')->setLastBread($title);
		return $this->getView()->showCreateTabContent($taskId);
	}
	public function getFormsTabsList( $current = '' ) {
		$tabs = array(
			'general' => array(
				'class' => '',
				'pro' => false,
				'label' => __('General', 'ai-copilot-content-generator'),
			),
			'api' => array(
				'class' => '',
				'pro' => false,
				'label' => __('API settings', 'ai-copilot-content-generator'),
			),
			'appearance' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Appearance', 'ai-copilot-content-generator'),
			),
			'history' => array(
				'class' => '',
				'pro' => false,
				'label' => __('History', 'ai-copilot-content-generator'),
			),
		);

		if (empty($current) || !isset($tabs[$current])) {
			reset($tabs);
			$current = key($tabs);
		}
		$tabs[$current]['class'] .= ' current';
		
		return $tabs;
	}
	public function getFormsPresetsList() {
		$list = array(
			'default' => array(
				'label' => 'AIWU',
				'pro' => false,
			),
		);
		return $list;
	}
	public function renderForm( $params ) {
		$p = array(
			'id' => ( isset($params['id']) ? (int) $params['id'] : 0 ),
			'mode' => ( isset($params['mode']) && 'widget' == $params['mode'] ? 'widget' : '' ),
		);
		return $this->getView()->renderFormHtml($p, $params['id']);
	}
	public function addTaskColumns( $columns, $params, $taskId ) {
		if (empty($taskId)) {
			$columns['status'] = 4;
		} else {
			unset($columns['status']);
		}
		return $columns;
	}
}
