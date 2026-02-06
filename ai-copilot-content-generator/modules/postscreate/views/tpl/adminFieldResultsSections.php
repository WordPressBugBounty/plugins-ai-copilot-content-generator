<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$sections = WaicUtils::getArrayValue($data, 'r', array(), 2);
?>
<div class="wbw-settings-form wbw-settings-top row waic-field-block" data-block="sections">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-wrap wbw-fullwidth waic-results-sections waic-results-<?php echo esc_html($data['s']); ?>">
		<?php foreach ($sections as $n => $section) { ?>
			<div class="wbw-settings-field">
				<div class="waic-list-number"><?php echo esc_html($n); ?></div>
				<?php 
					WaicHtml::text('sections[]', array(
						'value' => empty($section['s']) ? $props['loading_text'] : WaicUtils::getArrayValue($section, 'r', $props['loading_text']),
						'attrs' => 'class="waic-results-' . $section['s'] . '"',
					));
				?>
				<div class="wbw-elem-action">
					<a href="#" class="wbw-elem-move"><i class="fa fa-arrows"></i></a>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
</div>
