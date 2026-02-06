<?php
if (! defined('ABSPATH')) {
	exit;
}
$props = $this->props;

$options = WaicUtils::getArrayValue($props['fields'], $key, array(), 2);
$tooltip = !empty($options['modes_tooltip']) ? $options['modes_tooltip'] : __('Set a custom name for this feature. This is how it will appear in the quick menu.', 'ai-copilot-content-generator');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html($tooltip); ?>">
		<?php
		WaicHtml::text('fields[' . $key . '][name]', array(
			'value' => WaicUtils::getArrayValue($options, 'name', ''),
			'attrs' => 'placeholder="' . esc_attr__('Enter Name', 'ai-copilot-content-generator') . '"',
		));
		?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Fix prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions specifically for generating the article title. This prompt will help refine the title to better match your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php
		WaicHtml::textarea('fields[' . $key . '][text]', array(
			'value' => WaicUtils::getArrayValue($options, 'text', ''),
			'rows' => 4,
			'attrs' => 'placeholder="' . esc_attr__('Enter your data.', 'ai-copilot-content-generator') . '"',
		));
		?>
	</div>
</div>
