<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$curField = $this->props['cur_field'];
$isTemp = '#n#' === $curField;
$tooltip = $isTemp ? 'no-tooltip' : 'wbw-tooltip';
$multi = $isTemp ? ' no-chosen ' : '';
$fData = $isTemp ? array() : WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'resets', array(), 2), $curField, array(), 2);
$outputs = $this->props['outputs'];
$fields = $this->props['fields'];
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Button text', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Text displayed on the reset button.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('resets[' . $curField . '][title]', array(
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
			WaicHtml::text('resets[' . $curField . '][css]', array(
				'value' => WaicUtils::getArrayValue($fData, 'css'),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
	</div>
</div>
<?php 
	$targetFields = WaicUtils::getArrayValue($fData, 'field');
	$hidden = $targetFields == 'selected' ? '' : ' wbw-hidden';
	$mode = array(
		'no' => __('None', 'ai-copilot-content-generator'),
		'all' => __('All fields', 'ai-copilot-content-generator'),
		'selected' => __('Selected', 'ai-copilot-content-generator'),
	);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Target fields', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Choose which fields will be cleared when the button is clicked.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('resets[' . $curField . '][field]', array(
				'options' => $mode,
				'attrs' => 'class="wbw-small-field"',
				'value' => $targetFields,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form wbw-settings-sub row<?php echo esc_attr($hidden); ?>" data-parent-select="resets[<? echo esc_attr($curField); ?>][field]" data-select-value="selected">
	<div class="wbw-settings-label col-2"></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field wbw-settings-mono">
			<?php 
				WaicHtml::selectlist('resets[' . $curField . '][field_list]', array(
					'options' => $fields,
					'value' => WaicUtils::getArrayValue($fData, 'field_list'),
					'attrs' => 'data-label="FIELD"',
					'class' => 'waic-dynamic-list' . $multi,
				));
			?>
		</div>
	</div>
</div>
<?php 
	$targetOutput = WaicUtils::getArrayValue($fData, 'output');
	$hidden = $targetOutput == 'selected' ? '' : ' wbw-hidden';
	$mode = array(
		'no' => __('None', 'ai-copilot-content-generator'),
		'all' => __('All outputs', 'ai-copilot-content-generator'),
		'selected' => __('Selected', 'ai-copilot-content-generator'),
	);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Target outputs', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Choose which outputs will be cleared when the button is clicked.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('resets[' . $curField . '][output]', array(
				'options' => $mode,
				'attrs' => 'class="wbw-small-field"',
				'value' => $targetOutput,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form wbw-settings-sub row<?php echo esc_attr($hidden); ?>" data-parent-select="resets[<? echo esc_attr($curField); ?>][output]" data-select-value="selected">
	<div class="wbw-settings-label col-2"></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field wbw-settings-mono">
			<?php 
				WaicHtml::selectlist('resets[' . $curField . '][output_list]', array(
					'options' => $outputs,
					'value' => WaicUtils::getArrayValue($fData, 'output_list'),
					'attrs' => 'data-label="OUTPUT"',
					'class' => 'waic-dynamic-list' . $multi,
				));
			?>
		</div>
	</div>
</div>
