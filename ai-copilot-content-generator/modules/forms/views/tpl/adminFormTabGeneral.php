<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$settings =$props['settings'];
$general = WaicUtils::getArrayValue($settings, 'general', array(), 2);
$selectedFields = WaicUtils::getArrayValue($settings, 'fields', array(), 2);
$fields = $this->getModel()->getLabelVarsList($selectedFields, 'FIELD');
$selectedOutputs = WaicUtils::getArrayValue($settings, 'outputs', array(), 2);
$outputs = $this->getModel()->getLabelVarsList($selectedOutputs, 'OUTPUT');
$selectedSubmits = WaicUtils::getArrayValue($settings, 'submits', array(), 2);
$submits = $this->getModel()->getLabelVarsList($selectedSubmits, 'SUBMIT');
$selectedResets = WaicUtils::getArrayValue($settings, 'resets', array(), 2);
$resets = $this->getModel()->getLabelVarsList($selectedResets, 'RESET');
$rules = $this->getModel()->getLabelVarsList(WaicUtils::getArrayValue($settings, 'rules', array(), 2), 'RULE');

//$tplPath = $props['tpl_path'];
$fieldsList = $props['fields_list'];
$this->props['outputs'] = $outputs;
$this->props['fields'] = $fields;
?>
<section class="wbw-body-options">
<?php if (!empty($props['task_id'])) { ?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shortcode', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Shortcode', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('', array(
					'value' => '[' . WAIC_FORM . ' id="' . $props['task_id'] . '"]',
					'attrs' => 'readonly class="wbw-small-field wbw-shortcode-field"',
				));
			?>
			</div>
		</div>
	</div>
<?php } ?>
	<div class="wbw-group-title">
		<?php esc_html_e('Container', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Title', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Main heading of the form.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('general[title]', array(
					'value' => WaicUtils::getArrayValue($general, 'title', ''),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Description', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Brief explanation or instructions for the form.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('general[description]', array(
					'value' => WaicUtils::getArrayValue($general, 'description', ''),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Form fields', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select a field type from the dropdown and click “Add” to insert it into the form. You can reorder, customize, or remove fields at any time.', 'ai-copilot-content-generator'); ?>">
			<?php 
				WaicHtml::selectbox('', array(
					'options' => $fieldsList,
					'attrs' => 'class="wbw-small-field" id="waicFieldsForAdd"',
				));
			?>
			<button id="waicAddField" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-sections-list wbw-sections-sortable col-12" id="waicFieldsSection">
			<?php 
			foreach ($selectedFields as $i => $data) { 
				$key = WaicUtils::getArrayValue($data, 'key');
				if (empty($key) || empty($fieldsList[$key])) {
					continue;
				}
				$l = $fields[$i];
				$this->props['cur_field'] = $i;
			?>
			<div class="wbw-section" data-section-key="<?php echo esc_attr($key); ?>" data-section-id="<?php echo esc_attr($i); ?>" data-section-label="FIELD">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html($fieldsList[$key]); ?></div>
					<div class="wbw-section-label">FIELD_<?php echo esc_html($i); ?></div>
					<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
					<div class="wbw-section-tlabel"><?php echo esc_html($data['title']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminFieldOptions' . waicStrFirstUp($key) . '.php'; ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Outputs', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Add an output area to display AI responses. You can customize its appearance, behavior, and initial content.', 'ai-copilot-content-generator'); ?>">
			<button id="waicAddOutput" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-sections-list wbw-sections-sortable col-12" id="waicOutputsSection">
			<?php 
			foreach ($selectedOutputs as $i => $data) { 
				$this->props['cur_field'] = $i;
			?>
			<div class="wbw-section" data-section-id="<?php echo esc_attr($i); ?>" data-section-label="OUTPUT">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html_e('Output', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-section-label">OUTPUT_<?php echo esc_html($i); ?></div>
					<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
					<div class="wbw-section-tlabel"><?php echo esc_html($data['title']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminOutputOptions.php'; ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Submits', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Add a submit button that sends form data to AI, displays the response, and can trigger integrations like webhooks or email notifications.', 'ai-copilot-content-generator'); ?>">
			<button id="waicAddSubmit" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-sections-list wbw-sections-sortable col-12" id="waicSubmitsSection">
			<?php 
			foreach ($selectedSubmits as $i => $data) { 
				$this->props['cur_field'] = $i;
			?>
			<div class="wbw-section" data-section-id="<?php echo esc_attr($i); ?>" data-section-label="SUBMIT">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html_e('Submit', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-section-label">SUBMIT_<?php echo esc_html($i); ?></div>
					<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
					<div class="wbw-section-tlabel"><?php echo esc_html($data['title']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminSubmitOptions.php'; ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Resets', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Add a reset button to clear form fields and/or outputs.', 'ai-copilot-content-generator'); ?>">
			<button id="waicAddReset" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-sections-list wbw-sections-sortable col-12" id="waicResetsSection">
			<?php 
			foreach ($selectedResets as $i => $data) { 
				$this->props['cur_field'] = $i;
			?>
			<div class="wbw-section" data-section-id="<?php echo esc_attr($i); ?>" data-section-label="RESET">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html_e('Reset', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-section-label">RESET_<?php echo esc_html($i); ?></div>
					<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
					<div class="wbw-section-tlabel"><?php echo esc_html($data['title']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminResetOptions.php'; ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Conditional logic', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Create logic rules to show, hide, enable, disable, or set values for fields and outputs based on user input or AI responses.', 'ai-copilot-content-generator'); ?>">
			<button id="waicAddRule" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-sections-list wbw-sections-sortable col-12" id="waicRulesSection">
			<?php 
			foreach ($rules as $i => $l) { 
				$this->props['cur_field'] = $i;
			?>
			<div class="wbw-section" data-section-id="<?php echo esc_attr($i); ?>" data-section-label="RULE">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html_e('Rule', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-section-tlabel"><?php echo empty($data['title']) ? '' : esc_html($data['title']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminRuleOptions.php'; ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
</section>
