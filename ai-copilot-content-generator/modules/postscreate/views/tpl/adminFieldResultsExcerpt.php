<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<div class="wbw-settings-form wbw-settings-top row waic-field-block" data-block="excerpt">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::textarea('excerpt', array(
				'value' => empty($data['s']) ? $props['loading_text'] : ( 2 == $data['s'] ? WaicUtils::getArrayValue($data, 'm', $props['error_text']) : WaicUtils::getArrayValue($data, 'r', $props['loading_text']) ),
				'attrs' => 'class="waic-results-' . $data['s'] . '"',
				'rows' => 4,
			));
			?>
		</div>
	</div>
</div>