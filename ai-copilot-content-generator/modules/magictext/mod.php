<?php
class WaicMagictext extends WaicModule {
	public function init() {
		WaicDispatcher::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		if ( is_admin() ) {
			add_action('current_screen', array($this, 'conditionally_load_assets'));
		}
	}

	public function addAdminTab( $tabs ) {
		$tabs['magictext']   = array(
			'label'      => esc_html__('AI Magic Text Enhancer', 'ai-copilot-content-generator'),
			'callback'   => array( $this, 'showCreateTabContent' ),
			'hidden'     => 1,
			'sort_order' => 0,
			'bread'      => true,
			'last_Id' => 'waicTaskNameWrapper',
		);

		return $tabs;
	}

	public function showCreateTabContent() {
		$taskId = WaicReq::getVar('task_id');
		$title = '';

		if (empty($taskId)) {
			$taskId = $this->getModel()->getTaskId();
		}

		if (!empty($taskId)) {
			$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($taskId);
			if ($task && !empty($task['id'])) {
				$showSettings = true;
				if (empty($task['status']) || 9 == $task['status'] || $showSettings) {
					return $this->getView()->showCreateTabContent($task['id'], $task, $showSettings);
				}
				if (WaicUtils::getArrayValue($task, 'cnt', 0, 1) <= 1) {
					return $this->getView()->showResultTabContent($task);
				}
			}
		}

		return $this->getView()->showCreateTabContent($taskId);
	}

	public function conditionally_load_assets( $screen ) {
		if ( 'post' === $screen->base ) {
			if ($this->getModel()->isEnabled()) {
				//$data = $this->getModel()->getData();
				$assets = WaicAssets::_();
				$assets->loadCoreJs(false);
				$assets->loadAdminCoreJs();
				$assets->loadCoreCss();
				$assets->loadAdminEndCss();
				$assets->loadBootstrap();
				$assets->loadFontAwesome();
				$assets->loadJqueryUi();

				wp_register_script(
					'waic-mce-localizer',
					plugins_url('assets/js/waic-mce-localizer.js', __FILE__),
					array(),
					null,
					true
				);

				//$optModel = WaicFrame::_()->getModule('options')->getModel();
				//$language = $optModel->getVariations('api', 'language');

				//wp_localize_script('waic-mce-localizer', 'WaicMCEData', array(
				//  'items' => $data['fields'],
				//  'language' => $language,
				//  'lang' => array(
				//      'translate_to' => __('Translate to', 'ai-copilot-content-generator'),
				//      'custom_prompt' => __('Custom prompt', 'ai-copilot-content-generator'),
				//    ),
				//));

				wp_enqueue_script('waic-mce-localizer');

				$this->my_custom_mce_button();
				$this->magictext_enqueue_gutenberg_assets();
				add_action('admin_enqueue_scripts', array($this, 'my_plugin_enqueue_css'));
			}
		}
	}

	public function my_custom_mce_button() {
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
			return;
		}

		if ( get_user_option('rich_editing') !== 'true' ) {
			return;
		}

		add_filter( 'mce_external_plugins', array($this, 'my_add_tinymce_plugin') );
		add_filter( 'mce_buttons', array( $this, 'my_register_mce_button' ) );
	}

	public function my_add_tinymce_plugin( $plugin_array ) {
		$plugin_array['my_custom_button'] = plugins_url('assets/js/waic-custom-button.js', __FILE__);
		return $plugin_array;
	}

	public function my_register_mce_button( $buttons ) {
		array_push($buttons, 'my_custom_button');
		return $buttons;
	}

	public function my_plugin_enqueue_css() {
		wp_enqueue_style('magictext-style', plugins_url('assets/css/magictext.css', __FILE__));
	}

	public function magictext_enqueue_gutenberg_assets() {
		wp_enqueue_script(
			'waic-magictext-toolbar',
			plugins_url('assets/build/index.js', __FILE__),
			array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'),
			filemtime(plugin_dir_path(__FILE__) . 'assets/build/index.js'),
			true
		);

		$optModel = WaicFrame::_()->getModule('options')->getModel();
		$language = $optModel->getVariations('api', 'language');

		wp_localize_script('waic-magictext-toolbar', 'WaicMagicTextData', array(
			'items' => $this->getModel()->getData()['fields'],
			'language' => $language,
			'lang' => array(
				'translate_to' => __('Translate to', 'ai-copilot-content-generator'),
				'custom_prompt' => __('Custom prompt', 'ai-copilot-content-generator'),
				'ask_ai' => __('Ask AI', 'ai-copilot-content-generator'),
				'ask_ai_title' => __('Menu', 'ai-copilot-content-generator'),
			),
			'icon_url' =>  plugins_url('assets/img/ai.svg', __FILE__),
		));
	}

	public function getMagictextTabsList( $current = '' ) {
		$tabs = array(
			'fields' => array(
				'class' => '',
				'pro' => false,
				'label' => __('Generation', 'ai-copilot-content-generator'),
			),
			'api' => array(
				'class' => '',
				'pro' => false,
				'label' => __('API settings', 'ai-copilot-content-generator'),
			),
		);

		if (empty($current) || !isset($tabs[$current])) {
			reset($tabs);
			$current = key($tabs);
		}
		$tabs[$current]['class'] .= ' current';

		return WaicDispatcher::applyFilters('getMagictextTabsList', $tabs);
	}
}
