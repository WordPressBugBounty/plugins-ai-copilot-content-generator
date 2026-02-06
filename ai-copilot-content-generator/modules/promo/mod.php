<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPromo extends WaicModule {
	public function init() {
		if (is_admin()) {
			add_action('admin_footer', array($this, 'checkPluginDeactivation'));
			add_action('init', array($this, 'addAfterInit'));
		}
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
	}
	public function addAdminTab( $tabs ) {
		$tabs['training'] = array('label' => esc_html__('AI Training', 'ai-copilot-content-generator'), 'hidden' => true, 'callback' => array($this, 'showTraining'), 'fa_icon' => 'fa-list', 'sort_order' => 30, 'add_bread' => $this->getCode());
		if (!WaicFrame::_()->getModule('postsrss')) {
			$tabs['postsrss'] = array('label' => esc_html__('Autoblogging', 'ai-copilot-content-generator'), 'hidden' => true, 'callback' => array($this, 'showPostsrss'), 'fa_icon' => 'fa-list', 'sort_order' => 0, 'add_bread' => $this->getCode());
		}
		if (!WaicFrame::_()->getModule('productsfields')) {
			$tabs['productsfields'] = array('label' => esc_html__('WooCommerce Product Generator', 'ai-copilot-content-generator'), 'hidden' => true, 'callback' => array($this, 'showProductsfields'), 'fa_icon' => 'fa-list', 'sort_order' => 0, 'add_bread' => $this->getCode());
		}
		if (!WaicFrame::_()->getModule('postslinks')) {
			$tabs['postslinks'] = array('label' => esc_html__('Smart Post Crosslinking', 'ai-copilot-content-generator'), 'hidden' => true, 'callback' => array($this, 'showPostslinks'), 'fa_icon' => 'fa-list', 'sort_order' => 0, 'add_bread' => $this->getCode());
		}
		return $tabs;
	}
	public function showTraining() {
		$module = WaicFrame::_()->getModule('training');
		if ($module) {
			return $module->showTrainingTabContent();
		}
		return $this->getView()->showLendingPro('training');
	}
	public function showPostsrss() {
		return $this->getView()->showLendingPro('postsrss');
	}
	public function showProductsfields() {
		return $this->getView()->showLendingPro('productsfields');
	}
	public function showPostslinks() {
		return $this->getView()->showLendingPro('postslinks');
	}
	public function checkPluginDeactivation() {
		if (function_exists('get_current_screen')) {
			$screen = get_current_screen();
			if ($screen && isset($screen->base) && 'plugins' == $screen->base) {
				$this->getView()->printDeactivationPopup();
				$this->getView()->printGuidePopup(0);
			}
		}
	}
	public function addAfterInit() {
		$firstActivation = (int) get_option(WAIC_DB_PREF . 'first_activation', false);
		$this->getModel()->sendStats($firstActivation ? 1 : 0, $firstActivation ? 0 : null);
		
		$firstActivationPro = (int) get_option(WAIC_DB_PREF . 'first_activation_pro', false);
		if ($firstActivationPro) {
			$this->getModel()->sendStats(1, 1);
		}
	}
	public function isEndGuide() {
		return $this->getModel()->isEndGuide();
	}
	public function getDeactivationOptions( $o = false ) {
		$options = array(
			__('Requires third-party APIs', 'ai-copilot-content-generator'),
			__('Difficult to use', 'ai-copilot-content-generator'),
			__('Lacking necessary features', 'ai-copilot-content-generator'),
			__('Current features are not good enough', 'ai-copilot-content-generator'),
			__('Missing features in the free version', 'ai-copilot-content-generator'),
			__('Other (please specify)', 'ai-copilot-content-generator'),
		);
		return ( false === $o ? $options : ( empty($options[$o]) ? '' : $options[$o] ) );
	}
}
