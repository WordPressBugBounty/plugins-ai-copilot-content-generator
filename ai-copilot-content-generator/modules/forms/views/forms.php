<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicFormsView extends WaicView {
	private $allowedTags = array(
		'input' => array('type' => true, 'name' => true, 'value' => true, 'class' => true, 'placeholder' => true, 'min' => true, 'max' => true, 'required' => true, 'id' => true, 'data-id' => true),
		'textarea' => array('name' => true, 'value' => true, 'class' => true, 'placeholder' => true, 'required' => true, 'readonly' => true, 'data-id' => true),
		'select' => array('name' => true, 'value' => true, 'class' => true, 'required' => true, 'data-id' => true),
		'option' => array('value' => true),
		'button' => array('type' => true, 'class' => true, 'id' => true, 'data-settings' => true, 'data-id' => true),
		'label' => array('for' => true),
		'div' => array('class' => true, 'data-settings' => true, 'data-id' => true),
		'span' => array('class' => true),
		'b' => array(),
		'i' => array(),
	);
	
	public function showCreateTabContent( $id = 0, $task = array(), $showSettings = false ) {
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();

		$assets->loadSlider();
		$assets->loadChosenSelects();
		$assets->loadCodemirror();
		$module = $this->getModule();
		
		$path = $this->getModule()->getModPath() . 'assets/';
	
		$frame->addScript('waic-forms-admin', $path . 'js/admin.forms.js');
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

		$lang = array(
			'add-btn' => esc_html__('Add', 'ai-copilot-content-generator'),
			'cancel-btn' => esc_html__('Cancel', 'ai-copilot-content-generator'),
			'close-btn' => esc_html__('Close', 'ai-copilot-content-generator'),
		);

		$this->assign('lang', $lang);
		$this->assign('tabs', $module->getFormsTabsList());
		$this->assign('options', array('api' => $apiOptions));
		$this->assign('variations', $options->getVariations());
		$this->assign('defaults', $options->getDefaults());
		$this->assign('admin_email', get_option('admin_email'));
		
		$this->assign('presets', $module->getFormsPresetsList());
		$this->assign('task_id', $id);
		$this->assign('settings', $settings);
		$this->assign('tpl_path', WAIC_MODULES_DIR . 'workspace/views/tpl/');
		$this->assign('fields_list', $model->getFields());
		$this->assign('if_operators', $this->getModel()->getIfOperators());
		$this->assign('then_actions', $this->getModel()->getThenActions());
		$this->assign('task_title', WaicUtils::getArrayValue($task, 'title'));

		return parent::getContent('adminForm');
	}
	
	public function renderFormHtml( $params, $taskId = 0, $log = false ) {
		$taskId = (int) $taskId;
		$module = $this->getModule();
		$assets = WaicAssets::_();
		$frame = WaicFrame::_();
		$path = $module->getModPath() . 'assets/';
		
		$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$task = $taskModel->getTask($taskId);
		if (!$task || !$taskModel->isPublished($task['status'])) {
			return '';
		}
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		$general = WaicUtils::getArrayValue($params, 'general', array(), 2);

		$assets->loadCoreJs(true, true);
		//$assets->loadFontAwesome();
		$frame->addScript('waic-forms-front', $path . 'js/front.forms.js');

		$presets = $module->getFormsPresetsList();

		$appearance = WaicUtils::getArrayValue($params, 'appearance', array(), 2);
		$preset = WaicUtils::getArrayValue($appearance, 'preset', 'default', 0, array_keys($presets));
		
		$frame->addStyle('waic-forms-front', $path . 'css/front.forms.' . $preset . '.css');
		
		$viewId = $taskId . '-' . mt_rand(0, 999999);
		$this->assign('view_id', $viewId);
		$this->assign('task_id', $taskId);
		$this->assign('preset', $preset);
		$this->assign('custom_css', WaicUtils::getArrayValue($appearance, 'custom_css'));
		$this->assign('title', WaicUtils::getArrayValue($general, 'title'));
		$this->assign('description', WaicUtils::getArrayValue($general, 'description'));
		$this->assign('fields', WaicUtils::getArrayValue($params, 'fields', array(), 2));
		$this->assign('resets', WaicUtils::getArrayValue($params, 'resets', array(), 2));
		$this->assign('submits', WaicUtils::getArrayValue($params, 'submits', array(), 2));
		$this->assign('outputs', WaicUtils::getArrayValue($params, 'outputs', array(), 2));
		$this->assign('rules', WaicUtils::getArrayValue($params, 'rules', array(), 2));
		
		return parent::getContent('frontForm');
	}
	public function renderFormOutputHtml( $id, $data ) {
		$settings = array();
		$display = WaicUtils::getArrayValue($data, 'display');
		if ('custom' == $display) {
			$selector = WaicUtils::getArrayValue($data, 'selector');
			if (empty($selector)) {
				return;
			}
			$settings['selector'] = $selector;
		}
		$settings['replace'] = WaicUtils::getArrayValue($data, 'repeat') == 'replace' ? 1 : 0;
		$settings['hide'] = WaicUtils::getArrayValue($data, 'hide', 0, 1) ? 1 : 0;
		$loader = WaicUtils::getArrayValue($data, 'loader');
		$initial = WaicUtils::getArrayValue($data, 'initial');
		$title = WaicUtils::getArrayValue($data, 'title');
		if (!empty($title)) {
			$title = '<div class="aiwu-output-title">' . html_entity_decode($title) . '</div>';
		}
		if (!empty($initial)) {
			$settings['initial'] = html_entity_decode($initial);
		}
		if (!empty($loader)) {
			$settings['loader'] = html_entity_decode($loader);
		}
		$error = WaicUtils::getArrayValue($data, 'error');
		if (!empty($error)) {
			$settings['error'] = html_entity_decode($error);
		}

		$html = '<div class="aiwu-field-wrapper aiwu-form-output aiwu-output-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . 
			'" data-id="' . $id .
			'" data-settings="' . htmlentities(json_encode($settings)) . '">' .
			$title .
			'<div class="aiwu-output-text"></div>'.
			'</div>';
		echo wp_kses($html, $this->allowedTags);
	}
	public function renderFormResetHtml( $id, $data ) {
		$field = WaicUtils::getArrayValue($data, 'field');
		$fieldList = '';
		if ('all' == $field) {
			$fieldList = 'all';
		} else if ('selected' == $field) {
			$list = WaicUtils::getArrayValue($data, 'field_list', array(), 2);
			if (!empty($list)) {
				$fieldList = implode(',', $list);
			}
		}
		$output = WaicUtils::getArrayValue($data, 'output');
		$outputList = '';
		if ('all' == $output) {
			$outputList = 'all';
		} else if ('selected' == $field) {
			$list = WaicUtils::getArrayValue($data, 'output_list', array(), 2);
			if (!empty($list)) {
				$outputList = implode(',', $list);
			}
		}
		$settings = array('fields' => $fieldList, 'outputs' => $outputList);
		
		$html = '<button type="button"' . 
			' class="aiwu-form-button aiwu-button-reset aiwu-button-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . 
			( empty($data['class']) ? '' : ' ' . $data['class'] ) .
			'" data-id="' . $id .
			'" data-settings="' . htmlentities(json_encode($settings)) . '">' . 
			( empty($data['title']) ? '???' : html_entity_decode($data['title']) ) .
			'</button>';
		echo wp_kses($html, $this->allowedTags);
	}
	public function renderFormSubmitHtml( $id, $data ) {
		$prompt = WaicUtils::getArrayValue($data, 'prompt');

		$withFields = array('prompt', 'e_to', 'e_subject', 'e_message', 'w_url', 'w_headers', 'w_message');
		$fields = array();
		foreach ($withFields as $f) {
			$field = WaicUtils::getArrayValue($data, $f);
			preg_match_all('/(?<!\$)\{([^}]+)\}/', $field, $vars);
			if (isset($vars[1])) {
				$fields = array_merge($fields, $vars[1]);
			}
		}
		if (empty($prompt)) {
			return;
		}
		preg_match_all('/\$\{([^}]+)\}/', $prompt, $selectors);
		
		$output = WaicUtils::getArrayValue($data, 'output');

		$settings = array(
			'selectors' => isset($selectors[1]) ? $selectors[1] : array(),
			'fields' => array_unique($fields),
			'output' => $output,
			'selector' => 'custom' == $output ? WaicUtils::getArrayValue($data, 'selector') : '',
			'scroll' => WaicUtils::getArrayValue($data, 'scroll') == 1 ? 1 : 0,
		);
		
		$html = '<button type="button"' . 
			' class="aiwu-form-button aiwu-button-submit aiwu-button-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) .
			'" data-id="' . $id . 
			'" data-settings="' . htmlentities(json_encode($settings)) . '">' . 
			( empty($data['title']) ? '???' : html_entity_decode($data['title']) ) . '</button>';
		echo wp_kses($html, $this->allowedTags);
	}
	public function renderFormFieldHtml( $id, $data ) {
		$key = WaicUtils::getArrayValue($data, 'key');
		$html = '';
		$name = $id;
		switch ($key) {
			case 'input':
				$html = '<input type="text" name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . 
					( empty($data['placeholder']) ? '' : ' placeholder="' . $data['placeholder'] . '"' ) . '>';
				break;
			case 'email':
				$html = '<input type="email" name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . 
					( empty($data['placeholder']) ? '' : ' placeholder="' . $data['placeholder'] . '"' ) . '>';
				break;
			case 'date':
				$html = '<input type="date" name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . 
					( empty($data['placeholder']) ? '' : ' placeholder="' . $data['placeholder'] . '"' ) . '>';
				break;
			case 'number':
				$html = '<input type="number" name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . 
					( empty($data['min']) ? '' : ' min="' . $data['min'] . '"' ) .
					( empty($data['max']) ? '' : ' max="' . $data['max'] . '"' ) .
					( empty($data['placeholder']) ? '' : ' placeholder="' . $data['placeholder'] . '"' ) . '>';
				break;
			case 'textarea':
				$html = '<textarea name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . 
					( empty($data['placeholder']) ? '' : ' placeholder="' . $data['placeholder'] . '"' ) . '></textarea>';
				break;
			case 'dropdown':
				$html = '<select name="' . $name . '"' . 
					' data-id="' . $id . '"' .
					( empty($data['required']) ? '' : ' required' ) . 
					' class="aiwu-form-field aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '"' . '>';
				if (!empty($data['placeholder'])) {
					$html .= '<option value="">' . $data['placeholder'] . '</option>'; 
				}
				$choices = WaicUtils::getArrayValue($data, 'choices');
				if (!empty($choices)) {
					$choices = explode(PHP_EOL, $choices);
					foreach ($choices as $choice) {
						$choice = trim($choice);
						$html .= '<option value="' . $choice . '">' . $choice . '</option>';
					}
				}
				$html .= '</select>';
				break;
			case 'checkboxes':
				$choices = WaicUtils::getArrayValue($data, 'choices');
				if (!empty($choices)) {
					$choices = explode(PHP_EOL, $choices);
					$cnt = count($choices);
					foreach ($choices as $choice) {
						$choice = trim($choice);
						$for = 'aiwu_check_' . mt_rand(9, 99999);
						$html .= '<div class="aiwu-field-check aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '">' .
							'<input type="checkbox" class="aiwu-form-field" name="' . $name . '[]"' . 
							' data-id="' . $id . '"' .
							( !empty($data['required']) && $cnt > 1 ? ' required' : '' ) . 
							' value="' . $choice . '"' .
							' id="' . $for . '">' .
							'<label for="' . $for . '">' . html_entity_decode($choice) . '</label>' . '</div>';
					}
				}
				break;
			case 'radio':
				$choices = WaicUtils::getArrayValue($data, 'choices');
				if (!empty($choices)) {
					$choices = explode(PHP_EOL, $choices);
					foreach ($choices as $choice) {
						$choice = trim($choice);
						$for = 'aiwu_radio_' . mt_rand(9, 99999);
						$cnt = count($choices);
						$html .= '<div class="aiwu-field-radio aiwu-field-' . $id . ( empty($data['class']) ? '' : ' ' . $data['class'] ) . '">' .
							'<input type="radio" class="aiwu-form-field" name="' . $name . '"' . 
							' data-id="' . $id . '"' .
							( !empty($data['required']) && $cnt > 1 ? ' required' : '' ) . 
							' value="' . $choice . '"' .
							' id="' . $for . '">' .
							'<label for="' . $for . '">' . html_entity_decode($choice) . '</label>' . '</div>';
					}
				}
				break;
		}
		echo wp_kses($html, $this->allowedTags);
	}
}
