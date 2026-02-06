<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'body', array(), 2);
$fBody = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'body', array(), 2);
$tMode = WaicUtils::getArrayValue($fBody, 'mode', 'single');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose how the body of the article will be generated.<br>- Single-prompt article: Generates the entire body of the article in one step based on the title.<br>- Sectioned article generation: Generates the body of the article by first creating an outline and then generating each section individually.<br>- Custom sectioned article generation: Allows you to manually input an outline, using variables like {topic}, {keyword1}, {keyword2}, etc., and then generates each section based on this custom outline before combining them into the full body.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('fields[body][mode]', array(
				'options' => $options['modes'],
				'value' => $tMode,
				'attrs' => 'id="waicBodyMode"',
			));
			?>
	</div>
</div>
<?php 
$hidden = 'sections' == $tMode ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[body][mode]" data-select-value="sections">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Count headings', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Determine the number of sections for your article. You can add an unlimited number of sections, rearrange their order, and delete sections as needed.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[body][count]', array(
				'value' => WaicUtils::getArrayValue($fBody, 'count', 5, 1),
			));
			?>
		<div class="wbw-settings-label"><?php esc_html_e('pause after generating headers', 'ai-copilot-content-generator'); ?></div>
		<?php 
			WaicHtml::checkbox('fields[body][pause]', array(
				'checked' => WaicUtils::getArrayValue($fBody, 'pause', 1, 1),
			));
			?>
	</div>
</div>
<?php 
$hidden = 'custom' == $tMode ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[body][mode]" data-select-value="custom">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Sections', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Determine the number of sections for your article. You can add an unlimited number of sections, rearrange their order, and delete sections as needed. Use variables such as {topic}, {keyword1}, and {keyword2} to create custom sections and structure your article based on your specific needs and preferences.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<button id="waicAddBodySection" class="wbw-button wbw-button-small"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>
<?php 
$sections = WaicUtils::getArrayValue($fBody, 'sections', $options['custom_sections'], 2);
?>
<div class="wbw-settings-form row wbw-settings-sub<?php echo esc_attr($hidden); ?>" data-parent-select="fields[body][mode]" data-select-value="custom">
	<div class="wbw-settings-label col-2"></div>
	<div class="wbw-settings-fields col-10 wbw-settings-wrap" id="waicBodySectionsWrapper">
		<?php 
		foreach ($sections as $n => $section) { 
			if (empty($section)) {
				continue;
			}
			?>
		<div class="wbw-settings-field">
			<div class="waic-list-number"><?php echo esc_html($n + 1); ?></div>
			<?php 
				WaicHtml::text('fields[body][sections][]', array(
					'value' => $section,
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
<?php 
$hidden = 'single' == $tMode ? ' wbw-hidden' : '';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[body][mode]" data-select-value="custom sections">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Word Count per Section', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Specify the approximate  number of words per section to determine the approximate length of each section. Leave the field blank to allow the AI to automatically determine the section size based on section content.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[body][length]', array(
				'value' => WaicUtils::getArrayValue($fBody, 'length', 300, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra context or specific instructions to refine the generation of the article body. This prompt will help customize the content based on your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::textarea('fields[body][prompt]', array(
				'value' => WaicUtils::getArrayValue($fBody, 'prompt', ''),
				'rows' => 4,
			));
			?>
	</div>
</div>
<div class="wbw-nosave wbw-template">
	<div class="wbw-settings-field" id="waicCustomSectionTpl">
		<div class="waic-list-number"></div>
		<?php WaicHtml::text(''); ?>
		<div class="wbw-elem-action">
			<a href="#" class="wbw-elem-move"><i class="fa fa-arrows"></i></a>
			<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
		</div>
	</div>
</div>
