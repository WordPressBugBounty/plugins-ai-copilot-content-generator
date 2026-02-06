<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<div class="wbw-settings-form row waic-field-block" data-block="tags">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-field">
			<?php 
			if (empty($data['g'])) {
				$args = array(
					'parent' => 0,
					'hide_empty' => 0,
					'orderby' => 'name',
					'order' => 'asc',
				);
				$tags = WaicFrame::_()->getModule('workspace')->getTaxonomyHierarchy('post_tag', $args);
				WaicHtml::selectlist('tags', array(
					'options' => $tags,
					'value' => WaicUtils::getArrayValue($data, 'r', ''),
					'class' => 'no-chosen',
				));
			} else {
				WaicHtml::text('tags', array(
					'value' => empty($data['s']) ? $props['loading_text'] : ( 2 == $data['s'] ? WaicUtils::getArrayValue($data, 'm', $props['error_text']) : WaicUtils::getArrayValue($data, 'r', $props['loading_text']) ),
					'attrs' => 'class="waic-results-' . $data['s'] . '"',
				));
			}
			?>
		</div>
	</div>
</div>