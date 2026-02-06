<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPromoController extends WaicController {
	public function getNoncedMethods() {
		return array('sendDeactivationReason', 'nextGuide', 'skipGuide', 'endGuide', 'backGuide', 'startGuide', 'contactForm');
	}
	public function sendDeactivationReason() {
		$res = new WaicResponse();
		$isPro = WaicReq::getVar('plugin', 'post') == 'pro';
		$data = array();
		if (WaicReq::getVar('skip', 'post')) {
			$data['reason'] = array(-1, 'skip');
		} else {
			$reason = WaicReq::getVar('reason', 'post');
			$other = WaicReq::getVar('other', 'post');
			$data['reason'] = array((int) $reason, $this->getModule()->getDeactivationOptions($reason));
			if (!empty($other)) {
				$data['other_reason'] = array(0, $other);
			}
		}
		
		$this->getModel()->sendStats(2, $isPro, $data);
		return $res->ajaxExec();
	}
	public function nextGuide() {
		$res = new WaicResponse();
		$step = WaicReq::getVar('step', 'post');
		$res->data = $this->getModule()->getModel()->nextStep($step);

		return $res->ajaxExec();
	}
	public function backGuide() {
		$res = new WaicResponse();
		$step = WaicReq::getVar('step', 'post');
		$res->data = $this->getModule()->getModel()->backStep($step);

		return $res->ajaxExec();
	}
	public function skipGuide() {
		$res = new WaicResponse();
		$step = WaicReq::getVar('step', 'post');
		$this->getModule()->getModel()->skipGuide($step);
		$res->data = array('is_skip' => true);
		
		return $res->ajaxExec();
	}
	public function endGuide() {
		$res = new WaicResponse();
		$this->getModule()->getModel()->endGuide();
		$res->data = array('is_end' => true);

		return $res->ajaxExec();
	}
	public function startGuide() {
		$res = new WaicResponse();
		$res->data = $this->getModule()->getModel()->startGuide();

		return $res->ajaxExec();
	}
	public function contactForm() {
		$res = new WaicResponse();
		if (!$this->getModel()->sendContactForm()) {
			$res->pushError(WaicFrame::_()->getErrors());
		} else {
			$res->addMessage(esc_html__('Done', 'ai-copilot-content-generator'));
		}
		return $res->ajaxExec();
	}
}
