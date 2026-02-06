<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$curField = $this->props['cur_field'];
$isTemp = '#n#' === $curField;
$tooltip = $isTemp ? 'no-tooltip' : 'wbw-tooltip';
$fData = $isTemp ? array() : WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), $curField, array(), 2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Field name', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('The visible name of the field shown to users.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::hidden('fields[' . $curField . '][key]', array('value' => 'email'));
			WaicHtml::text('fields[' . $curField . '][title]', array(
				'value' => WaicUtils::getArrayValue($fData, 'title'),
				'attrs' => 'class="waic-field-tlabel"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Placeholder', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Example text shown inside the field before the user enters a value.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('fields[' . $curField . '][placeholder]', array(
				'value' => WaicUtils::getArrayValue($fData, 'placeholder'),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('CSS class', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Custom CSS class for styling or targeting this field.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('fields[' . $curField . '][class]', array(
				'value' => WaicUtils::getArrayValue($fData, 'class'),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Required', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Make this field mandatory before the form can be submitted.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('fields[' . $curField . '][required]', array(
				'checked' => WaicUtils::getArrayValue($fData, 'required', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Hide', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Keep this field hidden from users while keeping it in the form’s structure.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('fields[' . $curField . '][hide]', array(
				'checked' => WaicUtils::getArrayValue($fData, 'hide', 0, 1),
			));
			?>
	</div>
</div>