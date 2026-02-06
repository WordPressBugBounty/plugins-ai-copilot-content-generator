<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$curField = $this->props['cur_field'];
$isTemp = '#n#' === $curField;
$tooltip = $isTemp ? 'no-tooltip' : 'wbw-tooltip';
$fData = $isTemp ? array() : WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'submits', array(), 2), $curField, array(), 2);
$outputs = $this->props['outputs'];
$outputs['custom'] = __('Custom CSS Selector', 'ai-copilot-content-generator');
$notifications = $this->getModel()->getNotificationsSendModes();
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Button text', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Text shown on the submit button.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('submits[' . $curField . '][title]', array(
				'value' => WaicUtils::getArrayValue($fData, 'title'),
				'attrs' => 'class="waic-field-tlabel"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('CSS class', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Custom CSS class for styling or targeting this button.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('submits[' . $curField . '][class]', array(
				'value' => WaicUtils::getArrayValue($fData, 'class'),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('AI request template. Supports variables for inserting form field values or page element content.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('submits[' . $curField . '][prompt]', array(
				'value' => WaicUtils::getArrayValue($fData, 'prompt'),
				'rows' => 8,
			));
			?>
	</div>
</div>
<?php 
	$targetOutput = WaicUtils::getArrayValue($fData, 'output');
	$hidden = $targetOutput == 'custom' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Target output', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Select where the AI response should appear — an existing output or a custom CSS selector.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('submits[' . $curField . '][output]', array(
					'options' => $outputs,
					'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="OUTPUT" data-custom="' . $outputs['custom'] . '"',
					'value' => $targetOutput,
				));
				?>
			<?php 
				WaicHtml::text('submits[' . $curField . '][selector]', array(
					'value' => WaicUtils::getArrayValue($fData, 'selector'),
					'attrs' => 'class="wbw-small-field wbw-settings-field' . $hidden . '" data-parent-select="submits[' . $curField . '][output]" data-select-value="custom" placeholder="CSS Selector"',
				));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Autoscroll', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Automatically scroll to the output area after the form is submitted.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('submits[' . $curField . '][scroll]', array(
				'checked' => WaicUtils::getArrayValue($fData, 'scroll', 0, 1),
			));
			?>
	</div>
</div>
<?php 
	$webhook = WaicUtils::getArrayValue($fData, 'webhook', 0, 1);
	$hidden = $webhook ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Webhook', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Configure automatic sending of results via webhook after submission.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('submits[' . $curField . '][webhook]', array(
				'checked' => $webhook,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][webhook]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('URL', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('POST JSON', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('submits[' . $curField . '][w_url]', array(
				'value' => WaicUtils::getArrayValue($fData, 'w_url'),
				'attrs' => 'class="wbw-small-field"',
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][webhook]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Headers', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Enter each header on a new line. Separate the Key and Value with a colon (key:value).', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('submits[' . $curField . '][w_headers]', array(
				'value' => WaicUtils::getArrayValue($fData, 'w_headers'),
				'rows' => 4,
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][webhook]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Payload template', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Text with variables {FIELD}, {AI_RESPONSE}, {FORM_ID}, {TIMESTAMP}', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('submits[' . $curField . '][w_message]', array(
				'value' => WaicUtils::getArrayValue($fData, 'w_message'),
				'rows' => 4,
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][webhook]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Send when', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('???', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('submits[' . $curField . '][w_send]', array(
				'options' => $notifications,
				'attrs' => 'class="wbw-small-field"',
				'value' => WaicUtils::getArrayValue($fData, 'w_send'),
			));
		?>
	</div>
</div>
<?php 
	$email = WaicUtils::getArrayValue($fData, 'email', 0, 1);
	$hidden = $email ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Email', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('automatic sending of results via email after submission.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('submits[' . $curField . '][email]', array(
				'checked' => $email,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][email]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('To', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Email list', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('submits[' . $curField . '][e_to]', array(
				'value' => WaicUtils::getArrayValue($fData, 'e_to'),
				'attrs' => 'class="wbw-small-field"',
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][email]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Subject', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Subject with variables.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('submits[' . $curField . '][e_subject]', array(
				'value' => WaicUtils::getArrayValue($fData, 'e_subject'),
				'rows' => 4,
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][email]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Message template', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('HTML/text with variables {FIELD}, {AI_RESPONSE}, {FORM_ID}, {TIMESTAMP}', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('submits[' . $curField . '][e_message]', array(
				'value' => WaicUtils::getArrayValue($fData, 'e_message'),
				'rows' => 4,
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo $hidden; ?>" data-parent-check="submits[<?php echo esc_attr($curField); ?>][email]">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Send when', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('???', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('submits[' . $curField . '][e_send]', array(
				'options' => $notifications,
				'attrs' => 'class="wbw-small-field"',
				'value' => WaicUtils::getArrayValue($fData, 'e_send'),
			));
		?>
	</div>
</div>
