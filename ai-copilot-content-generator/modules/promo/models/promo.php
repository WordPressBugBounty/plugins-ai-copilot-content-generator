<?php
class WaicPromoModel extends WaicModel {
	private $_apiUrl = '';
	private $_endGuideStep = -99;
	public function __construct() {
		$this->_initApiUrl();
	}
	public function getCurrentGuideStep() {
		$step = get_option(WAIC_CODE . '_guide_step');
		return empty($step) ? 0 : (int) $step;
	}
	public function isEndGuide( $step = false ) {
		if (false == $step) {
			$step = $this->getCurrentGuideStep();
		}
		return $step == $this->_endGuideStep;
	}
	public function endGuide() {
		update_option(WAIC_CODE . '_guide_step', $this->_endGuideStep);
	}
	public function getSkipStep( $step ) {
		return ( $step + 1 ) * ( -1 );
	}
	public function skipGuide( $step = false ) {
		if (false == $step) {
			$step = $this->getCurrentGuideStep();
		}
		if (0 <= $step) {
			update_option(WAIC_CODE . '_guide_step', $this->getSkipStep($step), 'no');
		}
	}
	public function nextStep( $step ) {
		if (0 <= $step) {
			$step++;
			$data = $this->getGuideSteps($step, true);
			if (!$data || !is_array($data)) {
				$step = $this->_endGuideStep;
			}
			update_option(WAIC_CODE . '_guide_step', $step, 'no');
			$data['step'] = $step;
			return $data;
		}
		return false;
	}
	public function backStep( $step ) {
		if (0 < $step) {
			$step--;
			$data = $this->getGuideSteps($step, true);
			if (!$data || !is_array($data)) {
				$step = 0;
			}
			update_option(WAIC_CODE . '_guide_step', $step, 'no');
			$data['step'] = $step;
			return $data;
		}
		return false;
	}
	public function startGuide( $restart = false ) {
		$step = $this->getCurrentGuideStep();
		if ($this->_endGuideStep == $step && !$restart) {
			return false;
		}
		if (0 > $step) {
			$step = ( $this->_endGuideStep == $step ? 0 : ( $step * ( -1 ) - 1 ) );
			$data = $this->getGuideSteps($step, true);
			if (!$data || !is_array($data)) {
				$step = 0;
			}
			update_option(WAIC_CODE . '_guide_step', $step, 'no');
			$data['step'] = $step;
			return $data;
		}
	}
	public function getGuideSteps( $s = false, $url = false ) {
		$steps = array(
			array(
				'title' => __('Welcome to AIWU plugin', 'ai-copilot-content-generator'),
				'body' => __('Thank you for installing AIWU! You’ve just unlocked the best AI-powered content automation for your website. AIWU helps you generate, optimize, and manage content effortlessly.', 'ai-copilot-content-generator') .
					'<br><br>' .
					__('This quick step-by-step tutorial will guide you through the key features in just 2 minutes. Let’s get started!', 'ai-copilot-content-generator'),
				'next' => true,
				'skip' => true,
			),
			array(
				'title' => __('Connect your AI API key', 'ai-copilot-content-generator'),
				'body' => __('To activate AI-powered content automation, you need to connect an AI provider. AIWU supports OpenAI, Gemini, and DeepSeek. We do not take any commission – you only pay for the tokens used by your selected provider.', 'ai-copilot-content-generator') .
					'<br><br>' .
					__('Now, let’s enter your API key. If you don’t have an account yet, check out our detailed guide on how to create one.', 'ai-copilot-content-generator'),
				'next' => true,
				'back' => true,
				'tab' => 'settings',
			),
			array(
				'title' => __('Explore your AI workspace', 'ai-copilot-content-generator'),
				'body' => __('This is your AI Workspace, where all AI-powered automation happens:', 'ai-copilot-content-generator') .
					'<ul class="waic-popup-paragraph">' .
					'<li>' . __('Bulk generate or update content for blog and WooCommerce', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('Automate your blog & social media with an RSS-based AI workflow', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('Crosslink existing content or build new SEO content clusters', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('Enhance your website with AI chatbots', 'ai-copilot-content-generator') . '</li></ul>' .
					sprintf(__('Each tool is designed to save you time and maximize efficiency. If you need guidance on using these features, check out our %1$s or %2$s for support.', 'ai-copilot-content-generator'), '<a href="https://aiwuplugin.com/knowledge-base/">' . esc_html__('Knowledge Base', 'ai-copilot-content-generator') . '</a>', '<a href="https://aiwuplugin.com/contact/">' . esc_html__('Contact Us', 'ai-copilot-content-generator') . '</a>'),
				'next' => true,
				'back' => true,
				'tab' => 'workspace',
			),
			array(
				'title' => __('Manage and track your scenarios', 'ai-copilot-content-generator'),
				'body' => __('All operations in AIWU are called Scenarios, and they will appear in this section. This section gives you full control over your AI-powered workflows.', 'ai-copilot-content-generator') .
					'<ul class="waic-popup-paragraph">' .
					'<li>' . __('You can view, edit, and delete scenarios at any time.', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('Monitor detailed analytics to track performance and optimize automation.', 'ai-copilot-content-generator') . '</li>',
				'next' => true,
				'back' => true,
				'tab' => 'history',
			),
			array(
				'title' => __('Unlock the full power of AIWU!', 'ai-copilot-content-generator'),
				'body' => __('AIWU PRO version unlocks the full potential of the plugin, giving you access to powerful automation tools, including:', 'ai-copilot-content-generator') .
					'<ul class="waic-popup-paragraph">' .
					'<li>' . __('Bulk article generation for effortless content scaling', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('AI model training to fine-tune responses', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('SEO cluster creation to optimize site structure', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('WooCommerce product generation to automate store content', 'ai-copilot-content-generator') . '</li>' .
					'<li>' . __('RSS feed-based automation for seamless content updates', 'ai-copilot-content-generator') . '</li></ul>' .
					__('If you already have AIWU Pro, install it first, then enter your email and license key below to activate it. If not, we highly recommend getting the Pro version on our website:', 'ai-copilot-content-generator') . ' <a href="https://aiwuplugin.com/#pricing">' . esc_html__('Get AIWU PRO', 'ai-copilot-content-generator') . '</a>.',
				'end' => true,
				'back' => true,
				'tab' => 'license',
			),
		);
		$result = ( false === $s ? $steps : ( empty($steps[$s]) ? false : $steps[$s] ) );
		if ($url && !empty($result['tab'])) {
			$result['url'] = WaicFrame::_()->getModule('adminmenu')->getTabUrl($result['tab']);
			$result['title'] = wp_kses_post($result['title']);
			$result['body'] = wp_kses_post($result['body']);
		}
		return $result;
	}
	
	// $mode: 0 - save stats, 1 - first activated, 2 - deactivated
	public function sendStats( $mode = 0, $isPro = null, $data = array() ) {
		$workspace = WaicFrame::_()->getModule('workspace')->getModel();
		$isActivation = ( 1 == $mode );

		if ($isActivation) {
			update_option(WAIC_DB_PREF . 'first_activation' . ( $isPro ? '_pro' : '' ), '');
			$lastSend = $workspace->getWorkspaceFlag($isPro ? 'activ_pro' : 'activ', 'value');
			if ($lastSend) {
				return true;
			}
			$workspace->setWorkspaceFlag(0, $isPro ? 'activ_pro' : 'activ', time());
		} else {
			$time = time(); 
			if (empty($mode)) {
				if (WaicFrame::_()->getModule('options')->getWithDefaults('plugin', 'user_statistics', false) != 1) {
					return true;
				}
				$lastSend = $workspace->getWorkspaceFlag('send_stats', 'value');
				if (!empty($lastSend) && ( $time - $lastSend ) <= 7 * 24 * 3600) {
					return true;
				}
			}
			$workspace->setWorkspaceFlag(0, 'send_stats', $time);
		}
		$request = array(
			'url' => WAIC_SITE_URL,
			'plugin' => WAIC_CODE,
			'license' => base64_decode(WaicFrame::_()->getModule('options')->get('lic', 'license_key')),
			'email' => get_option('admin_email'),
			'is_pro' => ( is_null($isPro) ? WaicFrame::_()->isPro() : (int) $isPro ),
			'mode' => ( (int) $mode ),
		);
		if (!$isActivation) {
			$request['data'] = array_merge($data, $this->getStatData());
		}
		$resData = $this->_req('save', $request);
		
		return true;
	}
	public function getStatData() {
		$workspace = WaicFrame::_()->getModule('workspace');
		$api = WaicFrame::_()->getModule('options')->get('api');
		$apiKey = WaicUtils::getArrayValue($api, 'api_key');
		$deepSeekApiKey = WaicUtils::getArrayValue($api, 'deep_seek_api_key');
		$geminiApiKey = WaicUtils::getArrayValue($api, 'gemini_api_key');
		$data = array();
		if (!empty($apiKey)) {
			$data['apikey'] = array(1, $apiKey);
		}
		if (!empty($deepSeekApiKey)) {
			$data['deep_seek_apikey'] = array(1, $deepSeekApiKey);
		}
		if (!empty($geminiApiKey)) {
			$data['gemini_api_key'] = array(1, $geminiApiKey);
		}
		$cntTasks = (int) $workspace->getModel('tasks')->getCount();
		if (!empty($cntTasks)) {
			$data['cnt_tasks'] = array($cntTasks, '');
			$tokens = $workspace->getModel('history')->getCountTokensPerFeature();
			foreach ($tokens as $feature) {
				$data['tokens_' . $feature['feature']] = array($feature['total'], '');
			}
		}
		return $data;
	}
	public function overviewHttpRequestTimeout( $handle ) {
		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt( $handle, CURLOPT_TIMEOUT, 30 );
	}
	private function _req( $action, $data = array(), $mod = '' ) {
		add_filter('http_api_curl', array($this, 'overviewHttpRequestTimeout'), 100, 1);
		
		$data = array_merge($data, array(
			'mod' => empty($mod) ? 'stats' : $mod,
			'pl' => 'lms',
			'action' => $action,
		));
		$response = wp_remote_post($this->_apiUrl, array(
			'body' => $data,
			'timeout' => 30,
		));

		remove_filter('http_api_curl', array($this, 'overviewHttpRequestTimeout'));
		if (!is_wp_error($response)) {
			$code = wp_remote_retrieve_response_code($response);
			if (200 === $code) {
				$resArr = WaicUtils::jsonDecode($response['body']);
				if ( isset($response['body']) && !empty($response['body']) && $resArr ) {
					if (empty($resArr['error'])) {
						return true;
					} else {
						WaicFrame::_()->pushError($resArr['errors']);
						return false;
					}
				}
			} else {
				WaicFrame::_()->pushError('send_failed (' . $code . ')');
				return false;
			}
		}
		return false;
	}
	private function _initApiUrl() {
		if (empty($this->_apiUrl)) {
			//$this->_apiUrl = 'https://aiwuplugin.com/';
			$this->_apiUrl = 'https://check.aiwuplugin.com/';
		}
	}
	
	public function sendContactForm() {
		$email = WaicReq::getVar('email', 'post');
		if (empty($email)) {
			WaicFrame::_()->pushError('Email required');
			return false;
		}
		if (!is_email($email)) {
			WaicFrame::_()->pushError('Invalid email!');
			return false;
		}
		$name = WaicReq::getVar('name', 'post');
		if (empty($name)) {
			WaicFrame::_()->pushError('Name required');
			return false;
		}
		$subject = WaicReq::getVar('subject', 'post');
		if (empty($subject)) {
			WaicFrame::_()->pushError('Subject required');
			return false;
		}
		$body = WaicReq::getVar('body', 'post');
		if (empty($body)) {
			WaicFrame::_()->pushError('Body required');
			return false;
		}
		
		$request = array(
			'url' => WAIC_SITE_URL,
			'plugin' => WAIC_CODE,
			'license' => base64_decode(WaicFrame::_()->getModule('options')->get('lic', 'license_key')),
			'admin_email' => get_option('admin_email'),
			//'is_pro' => ( is_null($isPro) ? WaicFrame::_()->isPro() : (int) $isPro ),
			'form_email' => $email,
			'form_name' => $name,
			'form_subject' => $subject,
			'form_body' => $body,
		);
		return $this->_req('send', $request, 'contacts');
		
		return true;
	}
}
