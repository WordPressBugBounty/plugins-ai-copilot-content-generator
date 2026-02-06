<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//var_dump($fields);
$customSlug = str_replace($field . '-', '', $block);
//var_dump($field);
//var_dump($block);
//var_dump($customSlug);
?>
<div class="wbw-settings-form row waic-field-block" data-block="<?php echo esc_attr($block); ?>">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['taxonomies'][$customSlug]); ?></div>
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
				//$customSlug = str_replace($field . '_', '', $block);
				//var_dump($customSlug);
				$terms = WaicFrame::_()->getModule('workspace')->getTaxonomyHierarchy($customSlug, $args);
				WaicHtml::selectlist($block, array(
					'options' => $terms,
					'value' => WaicUtils::getArrayValue($data, 'r', ''),
					'class' => 'no-chosen',
				));
			} else {
				WaicHtml::text($block, array(
					'value' => empty($data['s']) ? $props['loading_text'] : ( 2 == $data['s'] ? WaicUtils::getArrayValue($data, 'm', $props['error_text']) : WaicUtils::getArrayValue($data, 'r', $props['loading_text']) ),
					'attrs' => 'class="waic-results-' . $data['s'] . '"',
				));
			}
			?>
		</div>
	</div>
</div>