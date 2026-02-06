<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicGopro extends WaicModule {
	
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
	}
	
	public function addAdminTab( $tabs ) {
		//return $tabs;
		$show = true;
		if ( function_exists('is_multisite') && is_multisite() ) {
			$availableSites = array( SITE_ID_CURRENT_SITE, get_option( 'wpmuclone_default_blog' ) );
			if ( !in_array( get_current_blog_id(), $availableSites ) ) {
				$show = false;
			}
		}
		if ( $show ) {
			$tabs['license'] = array('label' => esc_html__('License', 'ai-copilot-content-generator'), 'callback' => array($this, 'showGopro'), 'fa_icon' => 'fa-list', 'sort_order' => 50, 'add_bread' => $this->getCode());
		}
		return $tabs;
	}

	public function showGopro() {
		return $this->getView()->showGopro();
	}
}
