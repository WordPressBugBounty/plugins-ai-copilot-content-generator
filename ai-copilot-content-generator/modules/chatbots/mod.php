<?php
class WaicChatbots extends WaicModule {
	public function init() {
		add_shortcode(WAIC_CHATBOT, array($this, 'renderChatbot'));
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		WaicDispatcher::addFilter('addTaskColumns_chatbots', array($this, 'addTaskColumns'), 10, 3);
		add_action('wp_footer', array($this, 'autoSetShortcode'));
	}
	
	public function addAdminTab( $tabs ) {
		$code = 'workspace';
		$tabs['chatbots']   = array(
			'label'      => esc_html__( 'Create AI Chatbot', 'ai-copilot-content-generator' ),
			'callback'   => array( $this, 'showChatbotsTabContent' ),
			'hidden'     => 1,
			'sort_order' => 0,
			'bread'      => true,
			'last_Id' => 'waicTaskNameWrapper',
		);
		return $tabs;
	}
	
	public function showChatbotsTabContent() {
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
	public function getChatbotsTabsList( $current = '' ) {
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
			'context' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Context', 'ai-copilot-content-generator'),
			),
			'tools' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Tools', 'ai-copilot-content-generator'),
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
	public function getChatbotsPresetsList() {
		$list = array(
			'default' => array(
				'label' => 'AIWU',
				'pro' => false,
			),
		);
		return $list;
	}
	public function getChatbotsToolGroups( $current = '' ) {
		$groups = array(
			'products' => array(
				'title' => __('WooCommerce', 'ai-copilot-content-generator'),
				'desc' => __('Recommend products, manage cart, and more', 'ai-copilot-content-generator'),
				'class' => '',
			),
			'posts' => array(
				'title' => __('Posts', 'ai-copilot-content-generator'),
				'desc' => __('Recommend blog posts and knowledge articles', 'ai-copilot-content-generator'),
				'class' => '',
			),
			'pages' => array(
				'title' => __('Pages', 'ai-copilot-content-generator'),
				'desc' => __('Recommend important website pages', 'ai-copilot-content-generator'),
				'class' => 'wbw-ws-disabled',
				'soon' => true,
			),
		);
		if (empty($current) || !isset($groups[$current])) {
			reset($groups);
			$current = key($groups);
		}
		$groups[$current]['class'] .= ' current';
		
		return $groups;
	}
	public function getAiChatbotImages( $type, $exts = array('png', 'svg') ) {
		$path = WAIC_MODULES_DIR . 'chatbots/assets/img/' . $type;
		$found = array();
		if (file_exists(stream_resolve_include_path($path))) {
			$dir = opendir($path);
			$path .= '/';
			while ( ( $file = readdir($dir) ) !== false ) {
				if ( '.' == $file || '..' == $file ) {
					continue;
				}
				if (is_file($path . $file)) {
					$len = strlen($file) - 1;
					foreach ($exts as $e) {
						if (strripos($file, '.' . $e) + strlen($e) == $len ) {
							$found[] = $file;
							break;
						}
					}
				}
			}
			closedir($dir);
		}
		return $found;
	}
	public function renderChatbot( $params ) {
		$p = array(
			'id' => ( isset($params['id']) ? (int) $params['id'] : 0 ),
			'mode' => ( isset($params['mode']) && 'widget' == $params['mode'] ? 'widget' : '' ),
			'auto' => !empty($params['auto']),
		);
		return $this->getView()->renderChatbotHtml($p, $params['id']);
	}
	public function addTaskColumns( $columns, $params, $taskId ) {
		if (empty($taskId)) {
			$columns['status'] = 4;
		} else {
			unset($columns['status']);
		}
		return $columns;
	}
	public function autoSetShortcode() {
		if (is_admin()) {
			return;
		}
		$rules = $this->getModel()->getChatbotShowRules();
		if (!empty($rules) && is_array($rules)) {
			$displayIds = array();
			foreach ($rules as $taskId => $rule) {
				$display = empty($rule['display']) ? false : $rule['display'];
				if ($display) {
					$found = false;
					foreach ($display as $key) {
						if ($this->isItPage($key)) {
							$displayIds[$taskId] = 1;
							$found = true;
							break;
						}
					}
					if ($found) {
						$hide = empty($rule['hide']) ? false : $rule['hide'];
						if ($hide) {
							foreach ($hide as $key) {
								if ($this->isItPage($key)) {
									unset($displayIds[$taskId]);
									break;
								}
							}
						}
					}
					
				}
			}
			if (!empty($displayIds)) {
				foreach ($displayIds as $taskId => $f) {
					echo do_shortcode('[' . WAIC_CHATBOT . ' id="' . $taskId . '" auto="1"]');
				}
			}
		}
	}
	public function isItPage( $key ) {
		switch ($key) {
			case 'all':
				return true;
				break;
			case 'home':
				if (is_front_page()) {
					return true;
				}
				break;
			case 'account':
				if (is_account_page()) {
					return true;
				}
				break;
			case 'blog':
				if (is_home()) {
					return true;
				}
				break;
			case 'blog_post':
				if (is_singular('post')) {
					return true;
				}
				break;
			case 'blog_сat':
				if (is_category()) {
					return true;
				}
				break;
			case 'blog_tag':
				if (is_tag()) {
					return true;
				}
				break;
			case 'shop': 
				if (WaicUtils::isWooCommercePluginActivated() && is_shop()) {
					return true;
				}
				break;
			case 'product': 
				if (WaicUtils::isWooCommercePluginActivated() && is_product()) {
					return true;
				}
				break;
			case 'product_cat': 
				if (WaicUtils::isWooCommercePluginActivated() && is_product_category()) {
					return true;
				}
				break;
			case 'product_tag': 
				if (WaicUtils::isWooCommercePluginActivated() && is_product_tag()) {
					return true;
				}
				break;
			case 'cart': 
				if (WaicUtils::isWooCommercePluginActivated() && is_cart()) {
					return true;
				}
				break;
			case 'checkout': 
				if (WaicUtils::isWooCommercePluginActivated() && is_checkout()) {
					return true;
				}
				break;
			default:
				if (is_numeric($key)) {
					if (is_page($key)) {
						return true;
					}
					if (get_queried_object_id() == $key) {
						return true;
					}
				}
		}
		return false;
	}
}
