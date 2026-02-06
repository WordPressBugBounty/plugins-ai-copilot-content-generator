<?php
if (! defined('ABSPATH')) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'shorter', array(), 2);
$tooltip = !empty($options['modes_tooltip']) ? $options['modes_tooltip'] : __('This setting determines how the article title will be generated. Generate based on Topic: Generates the article title based on the topic you enter. Generate based on section headers: Creates the article title based on the section headers within the article (unavailable for Single-prompt article body creation mode). Use topic as title: The article title will be exactly the same as the topic you enter.', 'ai-copilot-content-generator');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html($tooltip); ?>">
		<?php
		WaicHtml::text('fields[shorter][name]', array(
			'value' => WaicUtils::getArrayValue($options, 'name', ''),
			'attrs' => 'placeholder="' . esc_attr__('Enter Name', 'ai-copilot-content-generator') . '"',
		));
		?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Fix shorter', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions specifically for generating the article title. This prompt will help refine the title to better match your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php
		WaicHtml::textarea('fields[shorter][text]', array(
			'value' => WaicUtils::getArrayValue($options, 'text', ''),
			'rows' => 4,
			'attrs' => 'placeholder="' . esc_attr__('Enter your data.', 'ai-copilot-content-generator') . '"',
		));
		?>
	</div>
</div>
