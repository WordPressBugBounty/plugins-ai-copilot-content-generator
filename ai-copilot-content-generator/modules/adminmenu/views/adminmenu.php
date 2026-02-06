<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicAdminmenuView extends WaicView {
	public function getAdminPage() {
		$accets = WaicAssets::_();
		$accets->loadCoreJs();
		$accets->loadAdminEndCss();
		
		$tabs = $this->getModule()->getTabs();
		$activeTab = $this->getModule()->getActiveTab();
		$content = __('No scenario\'s content found - perhaps this is PRO-scenario', 'ai-copilot-content-generator');
		$tabData = isset($tabs[$activeTab]) ? $tabs[$activeTab] : array();
		if (isset($tabData['callback'])) {
			$content = call_user_func($tabData['callback']);
		} 
		// if updated 
		$activeTab = $this->getModule()->getActiveTab();
		//$tabData = isset($tabs[$activeTab]) ? $tabs[$activeTab] : $tabData;
		
		$this->assign('tabs', $tabs);
		$this->assign('activeTab', $activeTab);
		
		//$this->assign('tabData', $tabData);
		//$this->assign('hideMenu', !empty($tabData['hide_menu']));
		$this->assign('bread', empty($tabData['bread']) ? false : $tabData['bread']);
		$this->assign('lastBread', $this->getModule()->getLastBread());
		$this->assign('lastBreadId', empty($tabData['last_Id']) ? false : $tabData['last_Id']);
		$this->assign('content', $content);
		$this->assign('guide', WaicFrame::_()->getModule('promo')->getView()->printGuidePopup(false, $activeTab));
		$this->assign('mainUrl', $this->getModule()->getTabUrl());
		$this->assign('is_pro', WaicFrame::_()->isPro(false));

		parent::display('adminNavPage');
	}

	public function displayAdminFooter() {
		parent::display('adminFooter');
	}
}
