<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
if (isset($results['image'])) {
	?>
<div class="wbw-settings-form wbw-settings-top row waic-field-block" data-block="image">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-field">
			<?php if (1 == $data['s']) { ?>
				<img src="<?php echo esc_url(is_numeric($data['r']) ? wp_get_attachment_url($data['r']) : $data['r']); ?>" class="waic-results-image">
				<?php 
			} else { 
				WaicHtml::text('image', array(
					'value' => empty($data['s']) ? $props['loading_text'] : WaicUtils::getArrayValue($data, 'm', $props['error_text']),
					'attrs' => 'class="waic-results-' . $data['s'] . '"',
				));
			}
			?>
		</div>
	</div>
</div>
	<?php 
}
if (isset($results['image_alt'])) {
	$data = $results['image_alt'];
	?>
<div class="wbw-settings-form wbw-settings-top row waic-field-block" data-block="image_alt">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Alt text for image', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('image_alt', array(
					'value' => empty($data['s']) ? $props['loading_text'] : ( 2 == $data['s'] ? WaicUtils::getArrayValue($data, 'm', $props['error_text']) : WaicUtils::getArrayValue($data, 'r', $props['loading_text']) ),
					'attrs' => 'class="waic-results-' . $data['s'] . '"',
				));
			?>
		</div>
	</div>
</div>
<?php } ?>
