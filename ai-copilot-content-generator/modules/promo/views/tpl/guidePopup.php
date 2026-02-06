<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$imgPath = $props['image_path'];
$data = $props['data'];
$step = $props['step'];
?>
<div id="waicGuidePopup" class="waic-admin-popup waic-guide-popup waic-popup-compact<?php echo empty($props['is_skipped']) ? '' : ' waic-popup-hidden'; ?>" style="display:none;" data-step="<?php echo esc_attr($step); ?>">
	<div class="waic-popup-panel">
		<div class="waic-popup-header">
			<div class="waic-text"><?php echo esc_html(( $step + 1 ) . '. ' . $data['title']); ?></div>
			<img class="waic-popup-close" data-action="skip" src="<?php echo esc_url($imgPath . 'close.svg'); ?>">
		</div>
		<div class="waic-popup-body">
			<div class="waic-popup-block">
				<?php echo wp_kses_post($data['body']); ?>
			</div>
		</div>
		<div class="waic-popup-buttons">
			<button type="button" class="waic-popup-button waic-button-main<?php echo empty($data['next']) ? ' waic-popup-hidden' : ''; ?>" data-action="next"><?php esc_html_e('Next', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="waic-popup-button waic-button-main<?php echo empty($data['end']) ? ' waic-popup-hidden' : ''; ?>" data-action="end"><?php esc_html_e('Finish', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="waic-popup-button waic-button-minor<?php echo empty($data['back']) ? ' waic-popup-hidden' : ''; ?>" data-action="back"><?php esc_html_e('Back', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="waic-popup-button waic-button-minor<?php echo empty($data['skip']) ? ' waic-popup-hidden' : ''; ?>" data-action="skip"><?php esc_html_e('Skip', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>
