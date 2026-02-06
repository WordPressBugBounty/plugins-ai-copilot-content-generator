<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicGoproView extends WaicView {

	public function showGopro() {
		$frame = WaicFrame::_();
		
		$path = $this->getModule()->getModPath() . 'assets/';
	
		$frame->addStyle('waic-gopro-admin', $path . 'css/admin.gopro.css');
		$frame->addScript('waic-gopro-admin', $path . 'js/admin.gopro.js');
		
		WaicDispatcher::doAction('getLicenseAssets');
		$isPro = $frame->isPro(false);
		$this->assign('is_pro', $isPro);
		$this->assign('license_data', WaicDispatcher::applyFilters('getLicenseData', array()));
		$this->assign('extendUrl', 'https://aiwuplugin.com/');

		return parent::getContent($isPro ? 'adminLicense' : 'adminPrice');
	}
}
