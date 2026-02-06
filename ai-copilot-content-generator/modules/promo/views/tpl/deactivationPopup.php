<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$imgPath = $props['image_path'];
$options = $props['options'];
?>
<div id="waicDeactivationPopup" class="waic-admin-popup waic-modal-popup">
	<div class="waic-popup-panel">
		<div class="waic-popup-header">
			<?php esc_html_e('Are you sure you want to deactivate AIWU?', 'ai-copilot-content-generator'); ?>
			<img class="waic-popup-close" src="<?php echo esc_url($imgPath . 'close.svg'); ?>">
		</div>
		<div class="waic-popup-body">
			<div class="waic-popup-block">
				<?php esc_html_e('We’re sorry to see you go! Before you delete the plugin, could you please let us know the reason? Your feedback helps us improve and better serve you in the future.', 'ai-copilot-content-generator'); ?>
			</div>
			<div class="waic-popup-block waic-popup-paragraph">
				<?php 
					WaicHtml::radiobuttons('waic_reason', array('options' => $options, 'ul' => true));
					WaicHtml::textarea('waic_other', array('rows' => 2));
				?>
			</div>
		</div>
		<div class="waic-popup-buttons">
			<button type="button" class="waic-popup-button waic-button-main waic-deactivate"><?php esc_html_e('Deactivate', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="waic-popup-button waic-button-back waic-cancel"><?php esc_html_e('Cancel', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="waic-popup-button waic-button-minor waic-skip"><?php esc_html_e('Skip and deactivate', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>
