<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'categories', array(), 2);
$fCategories = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'categories', array(), 2);
$tMode = WaicUtils::getArrayValue($fCategories, 'mode', 'fixed');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(empty($options['modes_tooltip']) ? __('Choose how categories for the article will be assigned. Options include Fixed, where you can select existing categories, or Generate based on article, where a specified number of categories will be generated based on the article content.', 'ai-copilot-content-generator') : $options['modes_tooltip']); ?>">
		<?php 
			WaicHtml::selectbox('fields[categories][mode]', array(
				'options' => $options['modes'],
				'value' => $tMode,
			));
			?>
	</div>
</div>
<?php 
$hidden = 'fixed' == $tMode ? '' : ' wbw-hidden';
$args = array(
	'parent' => 0,
	'hide_empty' => 0,
	'orderby' => 'name',
	'order' => 'asc',
);
$categories = WaicFrame::_()->getModule('workspace')->getTaxonomyHierarchy($options['taxonomy'], $args);
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[categories][mode]" data-select-value="fixed">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Select categories', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose existing categories for the article.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectlist('fields[categories][list]', array(
				'options' => $categories,
				'value' => WaicUtils::getArrayValue($fCategories, 'list'),
			));
			?>
		</div>
	</div>
</div>
<?php 
$hidden = 'generate' == $tMode ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[categories][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Count categories', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Specify the number of categories to be generated for the article.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[categories][count]', array(
				'value' => WaicUtils::getArrayValue($fCategories, 'count', 5, 1),
			));
			?>
	</div>
</div>
<?php if (!empty($data['append'])) { ?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Do not delete existing ones', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Check this box to keep existing categories. New categories will be added without removing the current ones.', 'ai-copilot-content-generator'); ?>">
			<?php 
				WaicHtml::checkbox('fields[categories][append]', array(
					'checked' => WaicUtils::getArrayValue($fCategories, 'append', 0),
				));
			?>
		</div>
	</div>
<?php } ?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[categories][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions to refine the generation of categories. This helps ensure the categories align with your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[categories][prompt]', array(
				'value' => WaicUtils::getArrayValue($fCategories, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
