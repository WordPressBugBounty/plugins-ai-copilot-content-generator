<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'tags', array(), 2);
$fTags = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'tags', array(), 2);
$tMode = WaicUtils::getArrayValue($fTags, 'mode', 'fixed');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(empty($options['modes_tooltip']) ? __('Choose how tags for the article will be assigned. Options include Fixed, where you can select existing tags, or Generate based on article, where a specified number of tags will be generated based on the article content.', 'ai-copilot-content-generator') : $options['modes_tooltip']); ?>">
		<?php 
			WaicHtml::selectbox('fields[tags][mode]', array(
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
$tags = WaicFrame::_()->getModule('workspace')->getTaxonomyHierarchy($options['taxonomy'], $args);
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[tags][mode]" data-select-value="fixed">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Select tags', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose existing tags for the article.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectlist('fields[tags][list]', array(
				'options' => $tags,
				'value' => WaicUtils::getArrayValue($fTags, 'list'),
			));
			?>
		</div>
	</div>
</div>
<?php 
$hidden = 'generate' == $tMode ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[tags][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Count tags', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Specify the number of tags to be generated for the article.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[tags][count]', array(
				'value' => WaicUtils::getArrayValue($fTags, 'count', 5, 1),
			));
			?>
	</div>
</div>
<?php if (!empty($data['append'])) { ?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Do not delete existing ones', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Check this box to keep existing tags. New tags will be added without removing the current ones.', 'ai-copilot-content-generator'); ?>">
			<?php 
				WaicHtml::checkbox('fields[tags][append]', array(
					'checked' => WaicUtils::getArrayValue($fTags, 'append', 0),
				));
			?>
		</div>
	</div>
<?php } ?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[tags][mode]" data-select-value="generate">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions to refine the generation of tags. This helps ensure the tags align with your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[tags][prompt]', array(
				'value' => WaicUtils::getArrayValue($fTags, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
