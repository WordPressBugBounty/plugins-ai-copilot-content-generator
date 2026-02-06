<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['options'], 'plugin', array(), 2);
$variations = WaicUtils::getArrayValue($props['variations'], 'plugin', array(), 2);
$defaults = WaicUtils::getArrayValue($props['defaults'], 'plugin', array(), 2);
?>
<section class="wbw-body-options-api">
	<div class="wbw-group-title">
		<?php esc_html_e('Generation settings', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Start generation', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Manually start the generator if it did not start automatically. This feature allows you to troubleshoot and ensure the generation process is initiated correctly.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
				<button class="wbw-button wbw-button-small" id="waicStartGeneration"><?php esc_html_e('Run', 'ai-copilot-content-generator'); ?></button>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Allow to send user statistics', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Send information about what plugin options you prefer to use, this will help us make our solution better for You.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('plugin[user_statistics]', array(
					'checked' => WaicUtils::getArrayValue($options, 'user_statistics', $defaults['user_statistics'], 1, false, true),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Enable logging', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Turn on logging to capture detailed activity logs for troubleshooting and analysis.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('plugin[logging]', array(
					'checked' => WaicUtils::getArrayValue($options, 'logging'),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Post Review Notification', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('If enabled, you will receive notifications when a new post is generated and ready for review. This applies only for scenarios with automatic publishing disabled.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('plugin[notifications]', array(
					'checked' => WaicUtils::getArrayValue($options, 'notifications', $defaults['notifications'], 1, false, true),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Date format', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the preferred date format for displaying dates throughout the plugin.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectBox('plugin[date_format]', array(
					'options' => $variations['date_format'],
					'value' => WaicUtils::getArrayValue($options, 'date_format', $defaults['date_format']),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Workflow blocks path', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the path to the folder with custom blocks for workflow. Specify the relative path from the site root.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('plugin[blocks_path]', array(
					'value' => WaicUtils::getArrayValue($options, 'blocks_path', ''),
					'attrs' => 'class="wbw-medium-field"',
				));
				?>
			</div>
		</div>
	</div>
</section>