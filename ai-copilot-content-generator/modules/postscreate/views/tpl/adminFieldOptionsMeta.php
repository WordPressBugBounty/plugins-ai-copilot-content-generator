<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//$options = WaicUtils::getArrayValue($props['fields'], 'meta', array(), 2);
$fMeta = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'meta', array(), 2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Maximum characters', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('???', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[meta][max]', array(
				'value' => WaicUtils::getArrayValue($fMeta, 'max', 150, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('???', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[meta][prompt]', array(
				'value' => WaicUtils::getArrayValue($fMeta, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
