<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//$options = WaicUtils::getArrayValue($props['fields'], 'body', array(), 2);
$fExcerpt = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'excerpt', array(), 2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Maximum characters', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the maximum number of characters for the excerpt. This limit helps ensure that the excerpt fits within the desired length constraints for your article.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[excerpt][length]', array(
				'value' => WaicUtils::getArrayValue($fExcerpt, 'length', 500, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions to refine the generation of the excerpt. This helps customize the excerpt to better match your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[excerpt][prompt]', array(
				'value' => WaicUtils::getArrayValue($fExcerpt, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
