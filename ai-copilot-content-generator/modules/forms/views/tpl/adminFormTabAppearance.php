<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$appearance = WaicUtils::getArrayValue($props['settings'], 'appearance', array(), 2);
$presets = $props['presets'];
$preset = WaicUtils::getArrayValue($appearance, 'preset', 'default', 0, array_keys($presets));
$customCss = WaicUtils::getArrayValue($appearance, 'custom_css');
//$borderStyles = array();
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Preset', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose a preconfigured design template for your form`s appearance. Customize further if needed.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('appearance[preset]', array(
					'options' => $presets,
					'value' => $preset,
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Custom CSS', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Here you can add custom CSS for the current form.', 'ai-copilot-content-generator'); ?>">
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-fields col-12">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::textarea('appearance[custom_css]', array(
					//'value' => empty($customCss) ? '' : base64_decode($customCss),
					'value' => $customCss,
					'attrs' => 'id="waicCssEditor"',
				));
				?>
			</div>
		</div>
	</div>
</section>
