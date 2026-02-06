<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$preset = $props['preset'];
$isPreview = $props['preview'];
$classes = $props['classes'];
$viewId = $props['view_id'];
?>
<div class="waic-chatbot-widget-wrapper" data-preset="<?php echo esc_attr($preset); ?>"<?php echo ( $isPreview ? '' : ' style="display:none;"' ); ?>>
<?php if ($isPreview) { ?>
	<style type="text/css" id="waic-chatbot-css">
		<?php include_once $props['presets_path'] . 'front.chatbot.' . $preset . '.css'; ?>
	</style>
<?php } ?>
	<div class="waic-chatbot-widget <?php echo esc_attr(implode(' ' , $classes['widget'])); ?>" data-viewid="<?php echo esc_attr($viewId); ?>" id="waic-<?php echo esc_attr($props['view_id']); ?>" data-task-id="<?php echo esc_attr($props['task_id']); ?>" data-preview="<?php echo $isPreview ? 1 : 0; ?>" data-aware="<?php echo esc_attr($props['aware']); ?>">
		<?php include 'frontChatbot' . waicStrFirstUp($preset) . '.php'; ?>
	</div>
	<div class="waic-chatbot-fixed" data-viewid="<?php echo esc_attr($viewId); ?>"></div>
</div>