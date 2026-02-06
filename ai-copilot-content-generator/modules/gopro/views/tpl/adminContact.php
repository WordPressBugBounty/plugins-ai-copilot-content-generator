<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<div class="waic-gopro-parag">
	<?php esc_html_e('Have questions or need help choosing the right option? Contact us using the form below — we’ll get back to you shortly.', 'ai-copilot-content-generator'); ?>
</div>
<section class="waic-contact-form">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Email', 'ai-copilot-content-generator'); ?><div class="waic-required">*</div></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::text('waicEmail', array(
					'value' => '',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?><div class="waic-required">*</div></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::text('waicName', array(
					'value' => '',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Subject', 'ai-copilot-content-generator'); ?><div class="waic-required">*</div></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::text('waicSubject', array(
					'value' => '',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Body', 'ai-copilot-content-generator'); ?><div class="waic-required">*</div></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('waicBody', array(
					'value' => '',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="col-12">
			<button class="wbw-button wbw-button-form wbw-button-main" id="waicSend"><?php esc_html_e('Send', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>