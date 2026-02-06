<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$curField = $this->props['cur_field'];
$isTemp = '#n#' === $curField;
$tooltip = $isTemp ? 'no-tooltip' : 'wbw-tooltip';
$fData = $isTemp ? array() : WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'outputs', array(), 2), $curField, array(), 2);
$displayModes = $this->getModel()->getDisplayOutputModes();
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Title text', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Heading displayed above the output area.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('outputs[' . $curField . '][title]', array(
				'value' => WaicUtils::getArrayValue($fData, 'title'),
				'attrs' => 'class="waic-field-tlabel"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('CSS class', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Custom CSS class for styling or targeting this.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('outputs[' . $curField . '][class]', array(
				'value' => WaicUtils::getArrayValue($fData, 'class'),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
	</div>
</div>
<?php 
	$displayMode = WaicUtils::getArrayValue($fData, 'display');
	$hidden = $displayMode == 'custom' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Display Location', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Choose where the AI response will appear on your page.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('outputs[' . $curField . '][display]', array(
					'options' => $displayModes,
					'attrs' => 'class="wbw-small-field"',
					'value' => $displayMode,
				));
			?>
			<?php 
				WaicHtml::text('outputs[' . $curField . '][selector]', array(
					'value' => WaicUtils::getArrayValue($fData, 'selector'),
					'attrs' => 'class="wbw-small-field wbw-settings-field' . $hidden . '" data-parent-select="outputs[' . $curField . '][display]" data-select-value="custom" placeholder="CSS Selector"',
				));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Initial Content', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Text or HTML shown before an AI response is received (e.g., “Waiting on Results”).', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('outputs[' . $curField . '][initial]', array(
				'value' => WaicUtils::getArrayValue($fData, 'initial'),
				'rows' => 4,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Hide until requested', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Keep the output hidden until the AI sends a response.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('outputs[' . $curField . '][hide]', array(
				'checked' => WaicUtils::getArrayValue($fData, 'hide', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Repeat Request Behavior', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Define how new responses are handled — replace the old content or append below it.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('outputs[' . $curField . '][repeat]', array(
				'options' => $this->getModel()->getRepeatOutputModes(),
				'attrs' => 'class="wbw-small-field"',
				'value' => WaicUtils::getArrayValue($fData, 'repeat'),
			));
		?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Loader', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Placeholder text displayed while the AI is processing the request.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('outputs[' . $curField . '][loader]', array(
				'value' => WaicUtils::getArrayValue($fData, 'loader'),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Errors', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Message shown if the AI response fails to load.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('outputs[' . $curField . '][error]', array(
				'value' => WaicUtils::getArrayValue($fData, 'error'),
			));
			?>
	</div>
</div>