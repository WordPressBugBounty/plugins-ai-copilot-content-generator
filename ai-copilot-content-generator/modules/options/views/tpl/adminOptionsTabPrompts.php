<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//var_dump($props['options']['prompts']);
$options = WaicUtils::getArrayValue($props['options'], 'prompts', array(), 2);
$defaults = WaicUtils::getArrayValue($props['defaults'], 'prompts', array(), 2);
?>
<div class="wbw-alert-block">
	<div class="wbw-alert-title"><span>!</span> <?php echo esc_html_e('For Experienced Users Only', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-alert-info"><?php esc_html_e('Modifying prompts can affect the quality of the generated content and the overall functionality of the plugin. Proceed only if you are an experienced user of generative neural networks. Default values can always be restored.', 'ai-copilot-content-generator'); ?></div>
</div>
<section class="wbw-body-options-prompts">
	<div class="wbw-group-title">
		<?php esc_html_e('Articles', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Title based on topic', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[title_topic]', array(
					'value' => WaicUtils::getArrayValue($options, 'title_topic', $defaults['title_topic']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Title based on outline (for custom sectioned article generation only)', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[title_sections]', array(
					'value' => WaicUtils::getArrayValue($options, 'title_sections', $defaults['title_sections']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Title (generate based on article)', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[title_body]', array(
					'value' => WaicUtils::getArrayValue($options, 'title_body', $defaults['title_body']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Body (Single-prompt article)', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[body]', array(
					'value' => WaicUtils::getArrayValue($options, 'body', $defaults['body']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Outline (Sectioned article generation)', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[sections]', array(
					'value' => WaicUtils::getArrayValue($options, 'sections', $defaults['sections']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Section (Sectioned article generation & Custom sectioned article generation)', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[body_section]', array(
					'value' => WaicUtils::getArrayValue($options, 'body_section', $defaults['body_section']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Category', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[categories]', array(
					'value' => WaicUtils::getArrayValue($options, 'categories', $defaults['categories']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Tag', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[tags]', array(
					'value' => WaicUtils::getArrayValue($options, 'tags', $defaults['tags']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Excerpt', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[excerpt]', array(
					'value' => WaicUtils::getArrayValue($options, 'excerpt', $defaults['excerpt']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Custom field', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[custom]', array(
					'value' => WaicUtils::getArrayValue($options, 'custom', $defaults['custom']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Image', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[image]', array(
					'value' => WaicUtils::getArrayValue($options, 'image', $defaults['image']),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label wbw-settings-label-top col-2"><?php esc_html_e('Alt text for image', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<?php 
				WaicHtml::textarea('prompts[image_alt]', array(
					'value' => WaicUtils::getArrayValue($options, 'image_alt', $defaults['image_alt']),
				));
				?>
		</div>
	</div>
<?php 
	$this->includeExtTemplate('postsrss', 'adminOptionsTabPrompts');
	$this->includeExtTemplate('postslinks', 'adminOptionsTabPrompts');
	$this->includeExtTemplate('productsfields', 'adminOptionsTabPrompts');
?>
</section>