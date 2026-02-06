<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPromoView extends WaicView {

	public function printGuidePopup( $only = false, $tab = false ) {
		$model = $this->getModule()->getModel();
		
		$step = $model->getCurrentGuideStep();
		if (( false !== $only && $step != $only ) || $model->isEndGuide($step)) {
			return;
		}
		$isSkipped = ( 0 > $step );
		$stepData = $isSkipped ? array('title' => '', 'body' => '') : $model->getGuideSteps($step);
		if (false === $stepData) {
			$model->endGuide();
			return;
		} 
		if (false !== $tab && !empty($stepData['tab']) && $stepData['tab'] != $tab) {
			return;
		}
		$frame = WaicFrame::_();
		$accets = WaicAssets::_();
		$accets->loadCoreJs(true, false, true);
		$path = $this->getModule()->getModPath() . 'assets/';
		
		$frame->addScript('waic-promo', $path . 'js/admin.promo.js');
		$frame->addStyle('waic-promo', $path . 'css/admin.promo.css');

		$this->assign('step', $step);
		$this->assign('is_skipped', $isSkipped);
		$this->assign('data', $stepData);
		$this->assign('image_path', $path . 'img/');
		parent::display('guidePopup');
	}
	public function printDeactivationPopup() {
		$frame = WaicFrame::_();
		$accets = WaicAssets::_();
		$accets->loadCoreJs(true, false, true);
		$path = $this->getModule()->getModPath() . 'assets/';
		
		$frame->addScript('waic-promo', $path . 'js/admin.promo.js');
		$frame->addStyle('waic-promo', $path . 'css/admin.promo.css');

		$this->assign('image_path', $path . 'img/');
		$this->assign('options', $this->getModule()->getDeactivationOptions());
		parent::display('deactivationPopup');
	}
	public function showLendingPro( $name ) {
		$frame = WaicFrame::_();
		$path = $this->getModule()->getModPath() . 'assets/';
		$frame->addStyle('waic-ad', $path . 'css/admin.ad.css');

		$this->assign('img_path', $path . 'img');
		$this->assign('extendUrl', 'https://aiwuplugin.com/');

		return parent::getContent('admin' . waicStrFirstUp($name) . 'Pro');
		return '';
	}
}
