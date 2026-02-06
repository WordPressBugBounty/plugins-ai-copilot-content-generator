<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$licenseData = $this->props['license_data'];
$credentials = WaicUtils::getArrayValue($licenseData, 'credentials', array(), 2);
$isActive = WaicUtils::getArrayValue($licenseData, 'isActive', 0, 1);
$isExpired = WaicUtils::getArrayValue($licenseData, 'isExpired', 0, 1);
?>
<?php if ( !$props['is_pro'] ) { ?>
<div class="wbw-alert-block">
	<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Install Pro version', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-alert-info"><?php esc_html_e('To access advanced features and benefits, visit the aiwuplugin.com website to purchase and obtain the plugin\'s Pro version. Then, install the archive with the PRO version on your website.', 'ai-copilot-content-generator'); ?></div>
</div>
<?php } else if ($isActive) { ?>
<div class="wbw-success-block">
	<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Congratulations', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-alert-info">
	<?php 
		/* translators: %s: plugin name */
		echo wp_kses_post(sprintf(__('PRO version of %s plugin is activated and working fine!', 'ai-copilot-content-generator'), WAIC_WP_PLUGIN_NAME)); 
	?>
	</div>
</div>
<?php } else if ($isExpired) { ?>
<div class="wbw-alert-block">
	<div class="wbw-alert-title"><span>!</span> 
	<?php 
		echo wp_kses_post(__('Your license has expired.', 'ai-copilot-content-generator'));
	?>
	</div>
	<div class="wbw-alert-info">
	<?php 
		/* translators: %s: plugin name */
		echo wp_kses_post(sprintf(__('Please check your license status/renewal date in your personal account, or contact us using the form below or the contact details on our website. You can %s to extend your license, then - click on "Re-activate" button to re-activate your PRO version.', 'ai-copilot-content-generator'), '<a href="' . esc_url($props['extendUrl']) . '" target="_blank">click here</a>')); 
		echo '</br>' . wp_kses_post(__('If you believe your license expired by mistake, please reach out and we’ll help resolve it.', 'ai-copilot-content-generator'));
	?>
	</div>
</div>
<?php } else { ?>
<div class="wbw-alert-block">
	<div class="wbw-alert-title"><span>!</span> 
	<?php 
		/* translators: %s: plugin name */
		echo wp_kses_post(sprintf(__('Congratulations! You have successfully installed PRO version of %s plugin.', 'ai-copilot-content-generator'), WAIC_WP_PLUGIN_NAME)); 
	?>
	</div>
	<div class="wbw-alert-info"><?php esc_html_e('Final step to finish Your PRO version setup - is to enter your Email and License Key on this page. This will activate Your copy of software on this site.', 'ai-copilot-content-generator'); ?></div>
</div>
<?php } ?>
<section class="wbw-body-license">
	<div class="wbw-group-title">
		<?php esc_html_e('Activation Pro version', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-group-info">
		<?php esc_html_e('To activate the Pro version, please enter your email and license key below.', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Email', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Your email address, used on checkout procedure on', 'ai-copilot-content-generator') . ' <a href="http://aiwuplugin.com/" target="_blank">http://aiwuplugin.com/</a>'); ?>">
			<?php 
				WaicHtml::text('', array(
					'value' => WaicUtils::getArrayValue($credentials, 'email'),
					'attrs' => 'id="waicLicenseEmail"' . ( $props['is_pro'] ? '' : ' disabled' ),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('License', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Your License Key from your account on', 'ai-copilot-content-generator') . ' <a href="http://aiwuplugin.com/" target="_blank">http://aiwuplugin.com/</a>'); ?>">
			<?php 
				WaicHtml::text('', array(
					'value' => WaicUtils::getArrayValue($credentials, 'key'),
					'attrs' => 'class="waic-fake-password" id="waicLicenseKey"' . ( $props['is_pro'] ? '' : ' disabled' ),
				));
				?>
		</div>
	</div>
<?php if ($props['is_pro']) { ?>
	<div class="wbw-settings-form row">
		<div class="col-12">
			<button class="wbw-button wbw-button-form wbw-button-main" id="waicActivateLicense">
				<?php 
				if ($isExpired) {
					esc_html_e('Re-activate', 'ai-copilot-content-generator');
				} else {
					esc_html_e('Activate', 'ai-copilot-content-generator');
				} 
				?>
			</button>
			<div class="wbw-hidden wbw-error-message"></div>
		</div>
	</div>
<?php } ?>
</section>
<?php include_once 'adminContact.php'; ?>