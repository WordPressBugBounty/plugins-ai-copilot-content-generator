<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$preset = $props['preset'];
$viewId = $props['view_id'];
?>
<div class="aiwu-form-widget-wrapper" style="display:none;">
	<div class="aiwu-form-widget aiwu-form-<?php echo esc_attr($props['task_id']); ?>" data-viewid="<?php echo esc_attr($viewId); ?>" id="aiwu-<?php echo esc_attr($viewId); ?>" data-task-id="<?php echo esc_attr($props['task_id']); ?>">
		<?php if (!empty($props['custom_css'])) { ?>
			<style type="text/css" id="waicCustomCss-<?php echo esc_attr($viewId); ?>" data-waic-form="1">
				<?php WaicHtml::echoEscapedHtml($props['custom_css']); ?>
			</style>
		<? } ?>
		<?php include 'frontForm' . waicStrFirstUp($preset) . '.php'; ?>
	</div>
</div>