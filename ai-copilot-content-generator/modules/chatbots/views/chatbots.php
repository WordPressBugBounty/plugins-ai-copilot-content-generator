<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicChatbotsView extends WaicView {
	public $maxViewMessages = 20;
	public $sidesCss = array('t' => 'top', 'r' => 'right', 'b' => 'bottom', 'l' => 'left');
	protected static $customCss = array();
	protected static $customFonts = array();
	protected static $standartFonts = array();
	
	public function resetCustomCss() {
		self::$customCss = array();
		self::$customFonts = array();
	}
	public function setCustomCss( $selector, $property, $value, $mode = '' ) {
		if (!isset(self::$customCss[$mode])) {
			self::$customCss[$mode] = array();
		}
		if (!isset(self::$customCss[$mode][$selector])) {
			self::$customCss[$mode][$selector] = array();
		}
		if ('font-family' == $property) {
			$this->setCustomFont($value);
		}
		self::$customCss[$mode][$selector][$property] = $value;
	}
	public function setCustomFont( $font ) {
		if (in_array($font, self::$customFonts)) {
			return;
		}
		if (empty(self::$standartFonts)) {
			self::$standartFonts = WaicHtml::getStandardFontsList();
		}
		if (!in_array($font, self::$standartFonts)) {
			self::$customFonts[str_replace(' ', '+', $font)] = $font;
		}
	}
	public function existCustomCss( $mode ) {
		return !empty(self::$customCss[$mode]);
	}
	public function getFontsCssString() {
		$str = '';
		foreach (self::$customFonts as $key => $font) {
			$str .= '@import url("//fonts.googleapis.com/css?family=' . $font . '");';
		}
		return $str;
	}
		
	public function getCustomCssString( $modes = array(''), $addSelector = '' ) {
		$str = '';
		if (!empty($addSelector)) {
			$addSelector .= ' ';
		}
		foreach ($modes as $mode) {
			if (empty($mode)) {
				$str .= $this->getFontsCssString();
			}
			if (!isset(self::$customCss[$mode]) || !is_array(self::$customCss[$mode])) {
				continue;
			}
			foreach (self::$customCss[$mode] as $selector => $data) {
				$str .= $addSelector . $selector . '{';
				foreach ($data as $property => $value) {
					$str .= $property . ':' . $value . ';';
				}
				$str .= '}';
			}
		}
		return $str;
	}
	
	public function showCreateTabContent( $id = 0, $task = array(), $showSettings = false ) {
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();

		$assets->loadSlider();
		$assets->loadChosenSelects();
		$assets->loadColorPicker();
		$module = $this->getModule();
		
		$path = $this->getModule()->getModPath() . 'assets/';
	
		$frame->addScript('waic-hextofilter', $path . 'js/hextofilter.js');
		$frame->addScript('waic-chatbots-admin', $path . 'js/admin.chatbots.js');
		$frame->addScript('waic-chatbots-front', $path . 'js/front.chatbots.js');
		$frame->addStyle('waic-chatbots-admin', $path . 'css/admin.chatbots.css');
		$assets->loadDataTables(array('responsive'));

		$options = $frame->getModule('options')->getModel();
		$model = $module->getModel();

		$isNew = empty($id);
		if (empty($task)) {
			$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($id);
		} 
		$settings = empty($task) ? array() : $task['params'];
		
		$apiOptions = WaicUtils::getArrayValue($settings, 'api', array(), 2);
		if (empty($apiOptions)) {
			$apiOptions = $options->get('api');
		}
		if ($isNew) {
			$apiOptions['pre_minute'] = 6;
		}
		$lang = array(
			'add-btn' => esc_html__('Add', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'close-btn' => esc_html__('Close', 'ai-copilot-content-generator'),
			'need-save' => esc_html__('First save the current chat bot and then you can try how the messaging works.', 'ai-copilot-content-generator'),
			'reset-appearance' => esc_html__('Reset appearance settings to default values?', 'ai-copilot-content-generator'),
		);

		$this->assign('lang', $lang);
		$this->assign('tabs', $module->getChatbotsTabsList());
		$this->assign('tool_groups', $module->getChatbotsToolGroups());
		$this->assign('options', array('api' => $apiOptions));
		$this->assign('variations', $options->getVariations());
		$this->assign('defaults', $options->getDefaults());
		$this->assign('admin_email', get_option('admin_email'));
		
		$this->assign('presets', $module->getChatbotsPresetsList());
		$this->assign('task_id', $id);
		$this->assign('settings', $settings);
		$this->assign('tpl_path', WAIC_MODULES_DIR . 'workspace/views/tpl/');
		$this->assign('img_url', $path . 'img/');
		$this->assign('ai_avatars', $module->getAiChatbotImages('ai_avatars'));
		$this->assign('user_avatars', $module->getAiChatbotImages('user_avatars'));
		$this->assign('open_icons', $module->getAiChatbotImages('opens'));
		$this->assign('close_icons', $module->getAiChatbotImages('closes'));
		
		$this->assign('task_title', WaicUtils::getArrayValue($task, 'title'));

		return parent::getContent('adminChatbot');
	}
	public function renderChatbotHtml( $params, $taskId = 0, $preview = false, $viewMode = false, $log = false ) {
		$taskId = (int) $taskId;
		$module = $this->getModule();
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();
		$path = $module->getModPath() . 'assets/';
		$isAutoDisplay = !$preview && !empty($params['auto']);
		
		$general = false;
		if (!$preview) {
			$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
			$task = $taskModel->getTask($taskId);
			if (!$task) {
				return '';
			}
			$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
			
			if (!$isAutoDisplay) {
				$rules = $this->getModel()->getChatbotShowRules();
				if ($rules && is_array($rules) && !empty($rules[$taskId])) {
					$hide = empty($rules[$taskId]['hide']) ? false : $rules[$taskId]['hide'];
					if ($hide) {
						foreach ($hide as $key) {
							if ($module->isItPage($key)) {
								return '';
							}
						}
					}
				}
			}
			if (!$taskModel->isPublished($task['status'])) {
				// if month limit pause
				if (8 == $task['status']) {
					$general = WaicUtils::getArrayValue($params, 'general', array(), 2);
					$monLimit = WaicUtils::getArrayValue($general, 'monthly_limit', 0, 1);
					$workspace = WaicFrame::_()->getModule('workspace');
					$isOk = true;
					if (!empty($monLimit)) {
						$where = array(
							'task_id' => $taskId,
							'mode' => 0,
							'additionalCondition' => "created BETWEEN'" . WaicUtils::getConvertedDate(false, 'Y-m-01 00:00:00') . "' AND '" . WaicUtils::getConvertedDate(false, 'Y-m-t 23:59:59') . "'",
						);
						$cntTokens = $workspace->getModel('history')->getCountTokens($where);
						if ($cntTokens >= $monLimit) {
							$isOk = false;
						}
					}
					if ($isOk) {
						$task = $workspace->getModel('tasks')->updateTask($taskId, array('status' => 4));
					} else {
						return '';
					}
				}
			}
			
			$assets->loadCoreJs(true, true);
			$assets->loadFontAwesome();
			$frame->addScript('waic-chatbots-front', $path . 'js/front.chatbots.js');
		}
		$presets = $module->getChatbotsPresetsList();
		$general = empty($general) ? WaicUtils::getArrayValue($params, 'general', array(), 2) : $general;
		$context = WaicUtils::getArrayValue($params, 'context', array(), 2);
		$api = WaicUtils::getArrayValue($params, 'api', array(), 2);
		$appearance = WaicUtils::getArrayValue($params, 'appearance', array(), 2);
		$preset = WaicUtils::getArrayValue($appearance, 'preset', 'default', 0, array_keys($presets));
		
		if (!$preview) {
			$frame->addStyle('waic-chatbots-front', $path . 'css/front.chatbot.' . $preset . '.css');
		}
		
		$desktop = WaicUtils::getArrayValue($appearance, 'desktop', array(), 2);
		$mobile = WaicUtils::getArrayValue($appearance, 'mobile', array(), 2);
		$imgPath = $path . 'img/';
		$this->resetCustomCss();
		
		if ($preview) {
			$this->setCustomCss('.waic-chatbot-panel', 'display', 'block!important');
			$this->setCustomCss('.waic-chatbot-panel:not(.waic-chatbot-show)', 'visibility', 'hidden');
		}
		$aiAvatar = false;
		if (WaicUtils::getArrayValue($context, 'e_ai_avatar', 0, 1)) {
			$aiAvatar = WaicUtils::getArrayValue($context, 'ai_avatar', 'ai_avatar0.png');
			if (strpos($aiAvatar, 'ai_avatar') === 0) {
				$aiAvatar = $imgPath . 'ai_avatars/' . $aiAvatar; 
			}
		}
		if (empty($aiAvatar)) {
			$this->setCustomCss('.waic-chatbot-main-avatar', 'display', 'none');
			$this->setCustomCss('.waic-avatar-ai', 'display', 'none');
		}
		
		$userAvatar = false;
		if (WaicUtils::getArrayValue($context, 'e_user_avatar', 0, 1)) {
			if (WaicUtils::getArrayValue($context, 'mode_user_avatar') == 'user') {
				$user = wp_get_current_user();
				if ($user && $user->ID) {
					$userAvatar = get_avatar_url($user->ID);
				}
			}
			if (empty($userAvatar)) {
				$userAvatar = WaicUtils::getArrayValue($context, 'user_avatar', 'ai_avatar0.png');
				if (strpos($userAvatar, 'user_avatar') === 0) {
					$userAvatar = $imgPath . 'user_avatars/' . $userAvatar; 
				}
			}
			
		}
		if (empty($userAvatar)) {
			$this->setCustomCss('.waic-avatar-user', 'display', 'none');
		}
		
		$userName = '';
		if (WaicUtils::getArrayValue($context, 'e_user_name', 0, 1)) {
			if (WaicUtils::getArrayValue($context, 'mode_user_name') == 'user') {
				$user = wp_get_current_user();
				if ($user && $user->ID) {
					$userName = trim($user->user_firstname . ' ' . $user->user_lastname);
					if (empty($userName)) {
						$userName = $user->display_name;
					}
					if (empty($userName)) {
						$userName = $user->user_nicename;
					}
					if (empty($userName)) {
						$userName = $user->user_login;
					}
				}
			}
			$userName = trim($userName);
			if (empty($userName)) {
				$userName = WaicUtils::getArrayValue($context, 'user_name');
			}
		}
		
		$buttons = array();
		if (WaicUtils::getArrayValue($context, 'e_welcome_buttons', 0, 1)) {
			$buttons = WaicUtils::getArrayValue($context, 'welcome_buttons', array(), 2);
		}
		if (WaicUtils::getArrayValue($context, 'e_human_request', 0, 1) && WaicUtils::getArrayValue($context, 'human_request_delay', 0, 1) == 0) {
			$buttons[] = array('link' => '#', 'name' => WaicUtils::getArrayValue($context, 'human_request_button'), 'class' => 'waic-human-request');
		}
		$aware = array();
		if (!$preview && WaicUtils::getArrayValue($context, 'e_aware', 0, 1)) {
			$aware = WaicUtils::getArrayValue($context, 'aware_selectors', array('body'), 2);
		}
		
		$viewId = $taskId . '-' . mt_rand(0, 999999);
		$class = 'waic-' . $viewId;
		$classes = array(
			'widget' => array($class),
			'wrapper' => array(),
			'panel' => array($class),
			'buttons' => array($class),
		);
		$classes['welcome'][] = 'waic-welcome-hidden';
		if ($preview) {
			$classes['panel'][] = 'waic-chatbot-show';
		} else {
			$classes['wrapper'][] = 'waic-chatbot-float';
			$classes['panel'][] = 'waic-chatbot-hidden';
		}
		
		$data = array();
		$modes = array('desktop', 'mobile');
		foreach ($modes as $mode) {
			if (!empty($viewMode) && $viewMode != $mode) {
				continue;
			}
			$data[$mode] = array();
			$options = 'mobile' == $mode ? $mobile : $desktop;
			
			//open-close buttons
			$color = WaicUtils::getArrayValue($options, 'icon_color_filter');
			
			$btn = WaicUtils::getArrayValue($options, 'icon_open', 'open0.svg');
			if (strpos($btn, 'open') === 0) {
				$btn = $imgPath . 'opens/' . $btn; 
			}
			$data[$mode]['icon_open'] = $btn;
			if (strtolower(substr($btn, -4, 4)) == '.svg') {
				if (!empty($color)) {
					$this->setCustomCss('.waic-chatbot-open img.waic-icon-svg', 'filter', $color, $mode);
				}
				$data[$mode]['io_class'] = 'waic-icon-svg';
			}
			
			$btn = WaicUtils::getArrayValue($options, 'icon_close', 'close0.svg');
			if (strpos($btn, 'close') === 0) {
				$btn = $imgPath . 'closes/' . $btn; 
			} 
			$data[$mode]['icon_close'] = $btn;
			
			if (substr($btn, -4, 4) == '.svg') {
				if (!empty($color)) {
					$this->setCustomCss('.waic-chatbot-close img.waic-icon-svg', 'filter', $color, $mode);
				}
				$data[$mode]['ic_class'] = 'waic-icon-svg';
			}
			$selector = '.waic-chatbot-button';
			$this->setSizeCss($selector . ' img', 'icon', $options, $mode );
			$this->setBGColorSizeCss($selector, 'icon_btn', $options, $mode );
			$this->setCornerCss($selector, 'icon_btn', $options, $mode );
			$this->setShadowCss($selector, 'icon_btn', $options, $mode );

			$animation = WaicUtils::getArrayValue($options, 'icon_animation', 'scale');
			$cssStr = '';
			if ('bounce' == $animation) {
				$cssStr = 'translate(0, -4px)';
			}
			if ('rotate' == $animation) {
				$cssStr = 'rotate(10deg)';
			}
			if (!empty($cssStr)) {
				$this->setCustomCss('.waic-chatbot-button:hover', 'transform', $cssStr);
				$this->setCustomCss('.waic-chatbot-button:focus', 'transform', $cssStr);
			}
			
			//chat window (panel)
			$selector = '.waic-chatbot-panel';
			$this->setBGColorSizeCss($selector, 'panel', $options, $mode);
			$this->setCornerCss($selector, 'panel', $options, $mode);
			$this->setShadowCss($selector, 'panel', $options, $mode);
			
			if ('mobile' == $mode && WaicUtils::getArrayValue($options, 'panel_width') === '' && WaicUtils::getArrayValue($options, 'panel_height') === '') {
				$classes['wrapper'][] = 'waic-full-mobile';
			}
			
			//chat header
			$selector = '.waic-chatbot-header';
			$this->setBGColorSizeCss($selector, 'header', $options, $mode);
			$content = WaicUtils::getArrayValue($options, 'header_content');
			if (!empty($content)) {
				if ('none' == $content) {
					$this->setCustomCss('.waic-chatbot-header', 'display', 'none', $mode);
				} else {
					if ('na' != $content && 'ao' != $content) {
						$this->setCustomCss('.waic-chatbot-main-avatar', 'display', 'none', $mode);
					} 
					if ('na' != $content && 'no' != $content && 'custom' != $content) {
						$this->setCustomCss('.waic-chatbot-name', 'display', 'none', $mode);
					}
					if ('custom' == $content) {
						$data[$mode]['header_name'] = WaicUtils::getArrayValue($options, 'header_custom');
					}
				}
			}
			$this->setSizeCss('.waic-chatbot-main-avatar .waic-chatbot-avatar', 'header_avatar', $options, $mode);
			$this->setTextCss('.waic-chatbot-name', 'header_text', $options, $mode);
			$data[$mode]['header_close'] = WaicUtils::getArrayValue($options, 'header_close_icon');
			$this->setIconCss('.waic-header-close', 'header_close', $options, $mode);
			
			//chat body
			$selector = '.waic-chatbot-body';
			$this->setBGColorSizeCss($selector, 'body', $options, $mode);
			$this->setPaddingCss($selector, 'body', $options, $mode);
			
			
			//ai message
			$this->setMessageCss('ai', $options, $mode);
			//user message
			$this->setMessageCss('user', $options, $mode);
			
			//chat card box
			$selector = '.waic-chatbot-card';
			$this->setBGColorSizeCss($selector, 'card', $options, $mode);
			$this->setPaddingCss($selector, 'card', $options, $mode);
			$this->setCornerCss($selector, 'card', $options, $mode);
			$this->setShadowCss($selector, 'card', $options, $mode);
			$this->setTextCss('.waic-card-name', 'card_title', $options, $mode);
			$this->setTextCss('.waic-card-desc', 'card_desc', $options, $mode);
			$this->setTextCss('.waic-card-price>.amount', 'card_price', $options, $mode);
			$this->setTextCss('.waic-card-price>ins>.amount', 'card_price', $options, $mode);
			$selector = '.waic-card-cat';
			$this->setTextCss($selector, 'card_cat', $options, $mode);
			$this->setBGColorSizeCss($selector, 'card_cat', $options, $mode);
			$this->setPaddingCss($selector, 'card_cat', $options, $mode);
			$this->setCornerCss($selector, 'card_cat', $options, $mode);
			$this->setTextCss('.waic-card-footer', 'card_footer', $options, $mode);
			
			//message buttons
			$selector = 'a.waic-message-button';
			$this->setBGColorSizeCss($selector, 'buttons', $options, $mode, true);
			$this->setBorderCss($selector, 'buttons', $options, $mode);
			$this->setTextCss($selector, 'buttons', $options, $mode);
			$this->setCornerCss($selector, 'buttons', $options, $mode);
			$selector = 'a.waic-message-button:hover';
			$this->setBGColorSizeCss($selector, 'buttons_hover', $options, $mode);
			$this->setBorderCss($selector, 'buttons_hover', $options, $mode);
			$this->setTextCss($selector, 'buttons_hover', $options, $mode);
			$selector = 'a.waic-message-button:focus';
			$this->setBGColorSizeCss($selector, 'buttons_hover', $options, $mode);
			$this->setBorderCss($selector, 'buttons_hover', $options, $mode);
			$this->setTextCss($selector, 'buttons_hover', $options, $mode);
			
			//chat footor
			$selector = '.waic-chatbot-footer';
			$this->setBGColorSizeCss($selector, 'footer', $options, $mode);
			$selector = '.waic-chatbot-input';
			$this->setTextCss($selector, 'input_text', $options, $mode);
			$this->setTextCss($selector . '::placeholder', 'input_placeholder', $options, $mode);
			$data[$mode]['clip'] = WaicUtils::getArrayValue($options, 'clip_icon');
			$this->setIconCss('.waic-message-clip', 'clip', $options, $mode);
			$data[$mode]['send'] = WaicUtils::getArrayValue($options, 'send_icon');
			$this->setIconCss('.waic-message-send', 'send', $options, $mode);
			
			$selector = '.waic-message-action:hover';
			$this->setBGColorSizeCss($selector, 'action_hover', $options, $mode);
			$this->setTextCss($selector, 'action_hover', $options, $mode);
			
			$placement = WaicUtils::getArrayValue($options, 'placement', 'float');
			if ('fixed' == $placement) {
				$this->setCustomCss('.waic-chatbot-wrapper .waic-chatbot-buttons', 'display', 'none', $mode);
				$this->setCustomCss('.waic-chatbot-fixed', 'display', 'block', $mode);
				if (!in_array('waic-need-fixed', $classes['buttons'])) {
					$classes['buttons'][] = ' waic-need-fixed';
				}
			} else {
				$this->setCustomCss('.waic-chatbot-wrapper .waic-chatbot-buttons', 'display', 'flex', $mode);
				$this->setCustomCss('.waic-chatbot-fixed', 'display', 'none', $mode);
			}
			$position = WaicUtils::getArrayValue($options, 'position', 'br');
			$bottom = WaicUtils::getArrayValue($options, 'position_bottom', '', 1, false, true, true);
			$side = WaicUtils::getArrayValue($options, 'position_side', '', 1, false, true, true);
			if ('' === $side) { 
				$side = 20;
			}
			$isLeft = 'bl' == $position;
			if ('' != $bottom) {
				$this->setCustomCss('.waic-chatbot-float', 'bottom', $bottom . 'px', $mode);
			}
			if ('' != $side) {
				$this->setCustomCss('.waic-chatbot-float', ( $isLeft ? 'left' : 'right' ), $side . 'px', $mode);
			}
			$this->setCustomCss('.waic-chatbot-buttons', 'justify-content', ( $isLeft ? 'flex-start' : 'flex-end' ), $mode);
		
			$data[$mode]['autostart'] = 0;
			if (!$preview) {
				$animation = WaicUtils::getArrayValue($options, 'animation_open');
				$duration = WaicUtils::getArrayValue($options, 'animation_duration', 1, 1);
				switch ($animation) {
					case 'fade':
						$this->setCustomCss('.waic-chatbot-panel', 'opacity', '0', $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transition', 'opacity ' . $duration . 's', $mode);
						$this->setCustomCss('.waic-chatbot-show', 'opacity', '1', $mode);
						break;
					case 'slide_r':
						$this->setCustomCss('.waic-chatbot-panel', 'transform', 'translateX(120%)', $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transition', 'transform ' . $duration . 's ease-out', $mode);
						$this->setCustomCss('.waic-chatbot-show', 'transform', 'translateX(0)', $mode);
						break;
					case 'slide_l':
						$this->setCustomCss('.waic-chatbot-panel', 'transform', 'translateX(-120%)', $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transition', 'transform ' . $duration . 's ease-out', $mode);
						$this->setCustomCss('.waic-chatbot-show', 'transform', 'translateX(0)', $mode);
						break;
					case 'slide_b':
						$this->setCustomCss('.waic-chatbot-panel', 'transform', 'translateY(120%)', $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transition', 'transform ' . $duration . 's ease-out', $mode);
						$this->setCustomCss('.waic-chatbot-show', 'transform', 'translateY(0)', $mode);
						break;
					case 'scale':
						$this->setCustomCss('.waic-chatbot-panel', 'transform', 'scale(0)', $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transform-origin', ( $isLeft ? 10 : 90 ) . '% 110%' , $mode);
						$this->setCustomCss('.waic-chatbot-panel', 'transition', 'transform ' . $duration . 's ease-out', $mode);
						$this->setCustomCss('.waic-chatbot-show', 'transform', 'scale(1)', $mode);
						break;
					default:
						$this->setCustomCss('.waic-chatbot-panel', 'transition-duration', '0s', $mode);
						break;
				}
				
				if (WaicUtils::getArrayValue($options, 'e_autostart') == 1) {
					$data[$mode]['autostart'] = WaicUtils::getArrayValue($options, 'autostart', 0, 1);
				}
			}
			
			//popup welcome message
			$data[$mode]['need_welcome'] = (!$preview && WaicUtils::getArrayValue($options, 'e_popup', 0, 1) == 1 && !empty($options['popup_message']) );

			if ($data[$mode]['need_welcome']) {
				$selector = '.waic-welcome-popup';
				$this->setBGColorSizeCss($selector, 'popup', $options, $mode);
				$this->setPaddingCss($selector, 'popup', $options, $mode);
				$this->setBorderCss($selector, 'popup', $options, $mode);
				$this->setCornerCss($selector, 'popup', $options, $mode);
				$this->setShadowCss($selector, 'popup', $options, $mode);

				if (empty(WaicUtils::getArrayValue($options, 'popup_avatar'))) {
					$this->setCustomCss('.waic-welcome-avatar', 'display', 'none', $mode);
				} else {
					$this->setSizeCss('.waic-welcome-avatar', 'popup_avatar', $options, $mode);
				}
				$this->setTextCss('.waic-welcome-text', 'popup_text', $options, $mode);
				
				//$this->setCustomCss('.waic-chatbot-name', 'display', 'none', $mode);
				$data[$mode]['popup_close'] = WaicUtils::getArrayValue($options, 'popup_close_icon');
				$this->setCustomCss('.waic-welcome-close', 'justify-content', ( $isLeft ? 'flex-start' : 'flex-end' ), $mode);
				
				$selector = '.waic-close-popup';
				$this->setIconCss($selector, 'popup_close', $options, $mode);
				$this->setBGColorSizeCss($selector, 'popup_btn', $options, $mode);
				$this->setCornerCss($selector, 'popup_btn', $options, $mode);
				$data[$mode]['popup_message'] = WaicUtils::getArrayValue($options, 'popup_message');
				$data[$mode]['popup_show'] = WaicUtils::getArrayValue($options, 'popup_show', 0, 1, false, true, true);
			}
		}

		if (empty($log) || !is_array($log)) {
			$user = wp_get_current_user();
			$userId = $user ? $user->ID : 0;
			$ip = WaicUtils::getRealUserIp();
			$isActive = true;
			if (!$preview) {
				$lifetime = WaicUtils::getArrayValue($general, 'lifetime', 0, 1);
				$isActive = $this->getModel()->isActiveChat($taskId, $userId, $ip, $preview, $lifetime);
			}
			$log = $isActive ? $this->getModel()->getUserChatLog($taskId, $userId, $ip, $preview, $this->maxViewMessages) : array();
		}
		$history = array();
		if (count($log) < $this->maxViewMessages) {
			$history[] = array(
				'typ' => 'ai',
				'msg' => stripcslashes(WaicUtils::getArrayValue($context, 'welcome_message', '👋')),
				'btn' => $buttons,
			);
		}
		
		$tools = WaicUtils::getArrayValue($params, 'tools', array(), 2);
		$today = WaicUtils::getFormatedDateTime(WaicUtils::getTimestamp(), 'Y-m-d');
		$dtFormat = WaicUtils::getCurrentDateTimeFormat();
		foreach ($log as $l) {
			$created = $l['created'];
			$format = strpos($created, $today) === 0 ? 'H:i' : $dtFormat;
			$tt = WaicUtils::convertDateFormat($l['created'], 'Y-m-d H:i:s', $format);
			if (!empty($l['question'])) {
				$history[] = array('typ' => 'user', 'msg' => $l['question'], 'tt' => $tt);
			}
			if (!empty($l['file'])) {
				//$history[] = array('typ' => 'user', 'msg' => __('File uploaded', 'ai-copilot-content-generator'), 'tt' => $tt);
				$history[] = array('typ' => 'user', 'msg' => '', 'file' => $l['file'], 'tt' => $tt);
			
			}
			if (!empty($l['answer'])) {
				$history[] = $this->renderCards(array('typ' => 'ai', 'msg' => $l['answer'], 'tt' => $tt), 'msg', $tools);
			}
		}
		
		//<button type="button" class="waic-message-button">📖 Knowledge Base</button>
		//<button type="button" class="waic-message-button">💬 Contact Sales</button>
		$vision = ( WaicUtils::getArrayValue($api, 'engine') != 'deep-seek' && WaicUtils::getArrayValue($api, 'vision', 0, 1) == 1 );
		
		$this->assign('view_id', $viewId);
		$this->assign('task_id', $taskId);
		$this->assign('maxlength', WaicUtils::getArrayValue($general, 'max_input', 0, 1, false, true));
		$this->assign('ai_avatar', $aiAvatar);
		$this->assign('ai_name', WaicUtils::getArrayValue($context, 'ai_name'));
		$this->assign('loader_text', WaicUtils::getArrayValue($context, 'loader_text'));
		$this->assign('user_avatar', $userAvatar);
		$this->assign('user_name', $userName);
		$this->assign('plh_text', WaicUtils::getArrayValue($context, 'plh_text'));
		$this->assign('loader_file', WaicUtils::getArrayValue($context, 'loader_file'));
		$this->assign('aware', empty($aware) ? '' : htmlentities(WaicUtils::jsonEncode($aware), ENT_COMPAT));
		$this->assign('messages', $history);
		$this->assign('breakpoint', WaicUtils::getArrayValue($appearance, 'breakpoint', 500, 1));
		$this->assign('classes', $classes);
		$this->assign('data', $data);
		$this->assign('view_mode', $viewMode);
		$this->assign('preview', $preview);
		$this->assign('preset', $preset);
		$this->assign('can_upload', $vision);
		$this->assign('max_file_size', $vision ? round(WaicUtils::getArrayValue($api, 'max_file_size', 5, 1) * 1048576) : 0);
		$this->assign('presets_path', WAIC_MODULES_DIR . 'chatbots/assets/css/');
		$this->assign('img_path', $imgPath);
		
		return parent::getContent('frontChatbot');
	}
	

	public function setBGColorSizeCss( $selector, $preName, $options, $mode, $min = false, $max = false ) {
		$color = WaicUtils::getArrayValue($options, $preName . '_bg');
		if (!empty($color)) {
			$this->setCustomCss($selector, 'background-color', $color, $mode);
		}
		$this->setSizeCss($selector, $preName, $options, $mode, $min, $max);
		$left = WaicUtils::getArrayValue($options, $preName . '_left', '', 1, false, true, true);
		if ('' !== $left && $left >= 0) {
			$this->setCustomCss($selector, 'padding-left', $left . 'px', $mode);
		}
		$right = WaicUtils::getArrayValue($options, $preName . '_right', '', 1, false, true, true);
		if ('' !== $right && $right >= 0) {
			$this->setCustomCss($selector, 'padding-right', $right . 'px', $mode);
		}
		if ('body' == $preName) {
			if (WaicUtils::getArrayValue($options, 'header_content') == 'none') {
				$header = 0;
			} else {
				$header = WaicUtils::getArrayValue($options, 'header_height', '', 1, false, true, true);
			}
			$footer = WaicUtils::getArrayValue($options, 'footer_height', '', 1, false, true, true);
			if ('' != $header || '' != $footer) {
				$this->setCustomCss($selector, 'height', 'calc(100% - ' . ( ( ( '' === $header ) ? 70 : $header ) + ( empty($footer) ? 70 : $footer ) ) . 'px)', $mode);
			}
		}
	}
	public function setSizeCss( $selector, $preName, $options, $mode, $min = false, $max = false ) {
		$width = WaicUtils::getArrayValue($options, $preName . '_width', '', 1, false, true, true);
		if ('' !== $width && $width >= 0) {
			$this->setCustomCss($selector, 'width', $width . 'px', $mode);
			if ($min) {
				$this->setCustomCss($selector, 'min-width', $width . 'px', $mode);
			}
			if ($max) {
				$this->setCustomCss($selector, 'max-width', $width . 'px', $mode);
			}
		}
		$height = WaicUtils::getArrayValue($options, $preName . '_height', '', 1, false, true, true);
		if ('' !== $height && $height >= 0) {
			$this->setCustomCss($selector, 'height', $height . 'px', $mode);
			if ($min) {
				$this->setCustomCss($selector, 'min-height', $height . 'px', $mode);
			}
			if ($max) {
				$this->setCustomCss($selector, 'max-height', $height . 'px', $mode);
			}
		}
		$size = WaicUtils::getArrayValue($options, $preName . '_wh', '', 1, false, true, true);
		if ('' !== $size) {
			$this->setCustomCss($selector, 'width', $size . 'px', $mode);
			$this->setCustomCss($selector, 'height', $size . 'px', $mode);
		}
	}
	public function setCornerCss( $selector, $preName, $options, $mode ) {
		$values = array();
		foreach ($this->sidesCss as $n => $s) {
			$value = WaicUtils::getArrayValue($options, $preName . '_corner_' . $n, '', 1, false, true, true);
			if ('' !== $value && $value >= 0) {
				$values[$n] = $value . 'px';
			}
		}
		if (!empty($values)) {
			$value = '';
			foreach ($this->sidesCss as $n => $s) {
				$value .= ( isset($values[$n]) ? $values[$n] : '0' ) . ' ';
			}
			$this->setCustomCss($selector, 'border-radius', $value, $mode);
		}
	}
	public function setPaddingCss( $selector, $preName, $options, $mode ) {
		foreach ($this->sidesCss as $k => $s) {
			$value = WaicUtils::getArrayValue($options, $preName . '_padding_' . $k, '', 1, false, true, true);
			if ('' !== $value && $value >= 0) {
				$this->setCustomCss($selector, 'padding-' . $s, $value . 'px', $mode);
			}
		}
	}
	public function setShadowCss( $selector, $preName, $options, $mode ) {
		$x = WaicUtils::getArrayValue($options, $preName . '_shadow_x', '', 1, false, true, true);
		$y = WaicUtils::getArrayValue($options, $preName . '_shadow_y', '', 1, false, true, true);
		if ( '' !== $x && '' !== $y ) {
			$value = $x . 'px ' . $y . 'px';
			$blur = WaicUtils::getArrayValue($options, $preName . '_shadow_blur', '', 1, false, true, true);
			if ('' !== $blur) {
				$value .= ' ' . $blur . 'px';
			}
			$spread = WaicUtils::getArrayValue($options, $preName . '_shadow_spread', '', 1, false, true, true);
			if ('' !== $spread) {
				$value .= ' ' . $spread . 'px';
			}
			$color = WaicUtils::getArrayValue($options, $preName . '_shadow_color');
			if (!empty($color)) {
				$alpha = WaicUtils::getArrayValue($options, $preName . '_shadow_alpha', '', 1, false, true, true);
				if ('' !== $alpha) {
					$color = WaicUtils::hexToRgbaStr($color, $alpha);
				}
				$value .= ' ' . $color;
			}
			$this->setCustomCss($selector, 'box-shadow', $value, $mode);
		}
	}
	public function setTextCss( $selector, $preName, $options, $mode ) {
		$font = WaicUtils::getArrayValue($options, $preName . '_font');
		if ( '' !== $font ) {
			$this->setCustomCss($selector, 'font-family', $font, $mode);
		}
		$style = WaicUtils::getArrayValue($options, $preName . '_style');
		if ('n' == $style) {
			$this->setCustomCss($selector, 'font-weight', 'normal', $mode);
		} else if ( 'b' == $style || 'bi' == $style ) {
			$this->setCustomCss($selector, 'font-weight', 'bold', $mode);
		}
		if ( 'i' == $style || 'bi' == $style ) {
			$this->setCustomCss($selector, 'font-style', 'italic', $mode);
			if ('i' == $style) {
				$this->setCustomCss($selector, 'font-weight', 'normal', $mode);
			}
		}
		$color = WaicUtils::getArrayValue($options, $preName . '_color');
		if (!empty($color)) {
			$this->setCustomCss($selector, 'color', $color, $mode);
		}
		$size = WaicUtils::getArrayValue($options, $preName . '_size', '', 1, false, true, true);
		if ('' !== $size) {
			$this->setCustomCss($selector, 'font-size', $size . 'px', $mode);
			//$this->setCustomCss($selector, 'line-height', $size . 'px', $mode);
			$this->setCustomCss($selector, 'line-height', 'normal', $mode);
		}
	}
	public function setIconCss( $selector, $preName, $options, $mode ) {
		if (WaicUtils::getArrayValue($options, $preName . '_icon') == 'no') {
			$this->setCustomCss($selector, 'display', 'none', $mode);
			return;
		}
		$color = WaicUtils::getArrayValue($options, $preName . '_color');
		if (!empty($color)) {
			$this->setCustomCss($selector, 'color', $color, $mode);
		}
		$size = WaicUtils::getArrayValue($options, $preName . '_size', '', 1, false, true, true);
		if ('' !== $size) {
			$this->setCustomCss($selector, 'font-size', $size . 'px', $mode);
			$this->setCustomCss($selector, 'line-height', $size . 'px', $mode);
		}
	}
	public function setBorderCss( $selector, $preName, $options, $mode ) {
		$color = WaicUtils::getArrayValue($options, $preName . '_border_color');
		if (!empty($color)) {
			$this->setCustomCss($selector, 'border-color', $color, $mode);
		}
		$style = WaicUtils::getArrayValue($options, $preName . '_border_style');
		if (!empty($style)) {
			$this->setCustomCss($selector, 'border-style', $style, $mode);
		}
		$size = WaicUtils::getArrayValue($options, $preName . '_border_size', '', 1, false, true, true);
		if ('' !== $size) {
			$this->setCustomCss($selector, 'border-width', $size . 'px', $mode);
		}
	}
	public function setMessageCss( $typ, $options, $mode ) {
		$position = WaicUtils::getArrayValue($options, $typ . '_message_position');
		if ('left' == $position) {
			$this->setCustomCss('.waic-message-' . $typ, 'justify-content', 'flex-start', $mode);
		} else if ('right' == $position) {
			$this->setCustomCss('.waic-message-' . $typ, 'justify-content', 'flex-end', $mode);
		}
		$width = WaicUtils::getArrayValue($options, $typ . '_message_width', '', 1, false, true, true);
		if ('' !== $width && $width >= 0) {
			$this->setCustomCss('.waic-message-' . $typ . ' .waic-message-wrap', 'max-width', $width . '%', $mode);
		}
		$content = WaicUtils::getArrayValue($options, $typ . '_message_content');
		if (!empty($content)) {
			if ('no' == $content || 'none' == $content) {
				$this->setCustomCss('.waic-avatar-' . $typ, 'display', 'none', $mode);
			} 
			if ('ao' == $content || 'none' == $content) {
				$this->setCustomCss('.waic-name-' . $typ, 'display', 'none', $mode);
			}
		}
		
		$this->setSizeCss('.waic-avatar-' . $typ, $typ . '_avatar', $options, $mode);
		$width = WaicUtils::getArrayValue($options, $typ . '_avatar_width', '', 1, false, true, true);
		if ('' !== $width && $width >= 0) {
			$this->setCustomCss('.waic-avatar-' . $typ, 'flex', '0 0 ' . $width . 'px', $mode);
		}
		$this->setTextCss('.waic-name-' . $typ, $typ . '_name', $options, $mode);
		
		$color = WaicUtils::getArrayValue($options, $typ . '_text_bg');
		if (!empty($color)) {
			$this->setCustomCss('.waic-text-' . $typ, 'background-color', $color, $mode);
		}
		$this->setPaddingCss('.waic-text-' . $typ, $typ . '_text', $options, $mode);
		$this->setTextCss('.waic-text-' . $typ, $typ . '_text', $options, $mode);
		
		$position = WaicUtils::getArrayValue($options, $typ . '_time_position');
		if ('none' == $position) {
			$this->setCustomCss('.waic-time-' . $typ, 'display', 'none', $mode);
		} else if ('left' == $position) {
			$this->setCustomCss('.waic-time-' . $typ, 'text-align', 'left', $mode);
		} else if ('right' == $position) {
			$this->setCustomCss('.waic-time-' . $typ, 'text-align', 'right', $mode);
		}
		$this->setTextCss('.waic-time-' . $typ, $typ . '_time', $options, $mode);
	}
	
	public function getLogData( $params ) {
		$html = '';
		$taskId = WaicUtils::getArrayValue($params, 'task_id', 0, 1);
		$userId = WaicUtils::getArrayValue($params, 'user_id', 0, 1);
		$ip = WaicUtils::getArrayValue($params, 'ip');
		/*if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return '';
		}*/
		$mode = WaicUtils::getArrayValue($params, 'mode');
		$dd = WaicUtils::getArrayValue($params, 'dd');
		if (!WaicUtils::checkDateTime($dd, 'Y-m-d')) {
			return '';
		}
		$log = $this->getModel()->getUserChatLog($taskId, $userId, $ip, $mode, 0, false, $dd);

		foreach ($log as $log) {
			if (!empty($log['file'])) {
				$html .= 'User (' . $log['created'] . '): File uploaded<br>';
			}
			if (!empty($log['question'])) {
				$html .= 'User (' . $log['created'] . '): ' . ( 3 == $log['status'] ? 'EMAIL - ' : '' ) . strip_tags($log['question']) . '<br>';
			}
			if (!empty($log['answer'])) {
				$html .= 'Bot (' . $log['created'] . '): ' . ( empty($log['status']) ? '' : 'ERROR - ' ) . strip_tags($log['answer']) . '<br>';
			}
		}
		return '<div class="waic-log-innner">' . $html . '</div>';
	}
	
	public function renderCards( $log, $key, $tools ) {
		if (empty($log[$key])) {
			return $log;
		}
		$msg = $log[$key];
		$this->assign('tools', $tools);
		$parts = explode('##IDS##', $msg);
		if (count($parts) == 2) {
			$log[$key] = $parts[0];
			$textIds = trim($parts[1]);
			$parts = explode(':', $textIds);
			if (count($parts) == 2) {
				$typ = $parts[0];
				if ('prod' == $typ && !WaicUtils::isWooCommercePluginActivated()) {
					return $log;
				}
				$layout = waicStrFirstUp(WaicUtils::getArrayValue($tools, $typ . '_card_layout', 'h'));
				$tmp = 'frontChatbotCard' . waicStrFirstUp($typ) . $layout;
				$ids = explode(',', $parts[1]);
				$cards = array();
				foreach ($ids as $id) {
					$obj = $this->getCardObj($typ, (int)$id);
					if ($obj) {
						$this->assign('obj', $obj);
						$log['cards'][] = parent::getContent($tmp);
					}
				}
			}
		}
		return $log;
	}
	public function getCardObj( $typ, $id ) {
		$obj = false;
		switch ($typ) {
			case 'prod':
				$obj = wc_get_product($id);
				break;
			default:
				$obj = get_post($id);
				break;
		}
		return $obj;
	}
}
