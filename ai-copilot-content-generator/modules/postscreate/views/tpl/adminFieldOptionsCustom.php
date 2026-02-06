<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//var_dump($props);
$customSlug = $props['custom_slug'];
$options = WaicUtils::getArrayValue($props['fields'], 'custom', array(), 2);
$fCustom = WaicUtils::getArrayValue(WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'custom', array(), 2), $customSlug, array(), 2);
$tMode = WaicUtils::getArrayValue($fCustom, 'mode', 'fixed');
$prefix = 'fields[custom][' . $customSlug . ']';
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(empty($options['modes_tooltip']) ? __('Choose how custom fields for the article will be assigned. Options include Fixed, where you can enter an exact value for the custom field, or Generate based on article, where the custom field value will be generated based on the article content, selected type, and desired length.', 'ai-copilot-content-generator') : $options['modes_tooltip']); ?>">
		<?php 
			WaicHtml::selectbox($prefix . '[mode]', array(
				'options' => $options['modes'],
				'value' => $tMode,
			));
			?>
	</div>
</div>
<?php 
$hidden = 'fixed' == $tMode ? '' : ' wbw-hidden';
$args = array(
	'parent' => 0,
	'hide_empty' => 0,
	'orderby' => 'name',
	'order' => 'asc',
);
$terms = WaicFrame::_()->getModule('workspace')->getTaxonomyHierarchy($customSlug, $args);
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="<?php echo esc_attr($prefix); ?>[mode]" data-select-value="fixed">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Select terms', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the value for the custom field.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectlist($prefix . '[list]', array(
				'options' => $terms,
				'value' => WaicUtils::getArrayValue($fCustom, 'list'),
			));
			?>
	</div>
</div>
<?php 
$terms = array(
	'one-line text' => __('one-line text', 'ai-copilot-content-generator'),
	'multi-line text' => __('multi-line text', 'ai-copilot-content-generator'),
	'numbers' => __('numbers', 'ai-copilot-content-generator'),
);
$hidden = 'generate' == $tMode ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="<?php echo esc_attr($prefix); ?>[mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Field Type', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the type of custom field to determine how data will be generated and displayed. One-line: a short single-line text entry will be generated based on the chosen length. Multi-line: a long paragraph or block of text will be generated based on the chosen length. Numbers: a numeric value will be generated based on the chosen length.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox($prefix . '[type]', array(
				'options' => $terms,
				'value' => WaicUtils::getArrayValue($fCustom, 'type'),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="<?php echo esc_attr($prefix); ?>[mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Desired Length', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the desired length of the custom field. ', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number($prefix . '[length]', array(
				'value' => WaicUtils::getArrayValue($fCustom, 'length', 30, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="<?php echo esc_attr($prefix); ?>[mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Description', 'ai-copilot-content-generator'); ?><sup>*</sup></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(empty($options['prompt_tooltip']) ? __('Provide a description in your own words of what this field does or is used for. This will help generate the correct value for the selected custom field of the article.', 'ai-copilot-content-generator') : $options['prompt_tooltip']); ?>">
		<?php 
			WaicHtml::textarea($prefix . '[prompt]', array(
				'value' => WaicUtils::getArrayValue($fCustom, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
