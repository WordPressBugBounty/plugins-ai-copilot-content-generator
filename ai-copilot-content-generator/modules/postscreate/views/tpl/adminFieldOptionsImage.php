<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'image', array(), 2);
$fImage = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'image', array(), 2);
$tooltip = !empty($options['modes_tooltip']) ? $options['modes_tooltip'] : __('Choose how images for the article will be generated.', 'ai-copilot-content-generator');
$apiOptions = WaicUtils::getArrayValue($props['options'], 'api', array(), 2);
$defaults = WaicUtils::getArrayValue($props['defaults'], 'api', array(), 2);
$curImageEngine = WaicUtils::getArrayValue($apiOptions, 'image_engine', $defaults['image_engine']);

?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html($tooltip); ?>">
		<?php 
			WaicHtml::selectbox('fields[image][mode]', array(
				'options' => $options['modes'],
				'value' => WaicUtils::getArrayValue($fImage, 'mode'),
			));
			?>
	</div>
</div>
<?php 
$hidden = WaicUtils::getArrayValue($fImage, 'mode', 'generate') == 'generate' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[image][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Preset', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html(empty($options['preset_tooltip']) ? __('Select a predefined style or setting for the images to be generated, ensuring consistency with your article\'s theme.', 'ai-copilot-content-generator') : $options['preset_tooltip']); ?>">
		<?php 
			WaicHtml::selectbox('fields[image][preset]', array(
				'options' => $options['presetes'],
				'value' => WaicUtils::getArrayValue($fImage, 'preset'),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?><?php echo ( 'open-ai' != $curImageEngine ? ' waic-hidden' : '' ); ?>" data-parent-select="fields[image][mode]" data-select-value="generate" data-parent-select2="api[image_engine]" data-select-value2="open-ai">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Orientation', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html(empty($options['orientation_tooltip']) ? __('Choose the orientation for the images: square, horizontal, or vertical. Note: The DALL-E-2 model does not support article orientation options.', 'ai-copilot-content-generator') : $options['orientation_tooltip']); ?>">
		<?php 
			WaicHtml::selectbox('fields[image][orientation]', array(
				'options' => $options['orientation'],
				'value' => WaicUtils::getArrayValue($fImage, 'orientation'),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?><?php echo ( !in_array($curImageEngine, array('gemini', 'openrouter')) ? ' waic-hidden' : '' ); ?>" data-parent-select="fields[image][mode]" data-select-value="generate" data-parent-select2="api[image_engine]" data-select-value2="gemini openrouter">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Aspect Ratio', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html('Note: The Gemini 2.0 Flash model does not support this options.', 'ai-copilot-content-generator'); ?>">
		<?php
		WaicHtml::selectbox('fields[image][gemini_orientation]', array(
			'options' => $options['gemini_orientation'],
			'value' => WaicUtils::getArrayValue($fImage, 'gemini_orientation'),
		));
		?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top<?php echo esc_attr($hidden); ?>" data-parent-select="fields[image][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions to refine the image generation. This helps ensure the images align with your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[image][prompt]', array(
				'value' => WaicUtils::getArrayValue($fImage, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Generate Alt text', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html(empty($options['alt_tooltip']) ? __("Enable to automatically generate Alt text for images based on the article's topic.", 'ai-copilot-content-generator') : $options['alt_tooltip']); ?>">
		<?php 
			WaicHtml::checkbox('fields[image][alt]', array(
				'checked' => WaicUtils::getArrayValue($fImage, 'alt', 0, 1),
			));
			?>
	</div>
</div>
