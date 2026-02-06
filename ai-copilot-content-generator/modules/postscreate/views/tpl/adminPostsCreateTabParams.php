<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$common = WaicUtils::getArrayValue($props['settings'], 'common', array(), 2);
$single = WaicUtils::getArrayValue($props['settings'], 'single', array(), 2);

$selectedFields = WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2);
$fields = $props['fields'];

$modes = array(
	'single' => __('Single', 'ai-copilot-content-generator'),
	( $props['is_pro'] ? 'bulk' : 'pro' ) => __('Bulk', 'ai-copilot-content-generator') . ( $props['is_pro'] ? '' : ' PRO' ),
);
$gMode = WaicUtils::getArrayValue($common, 'mode', 'single');
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the article generation mode. "Single" mode will generate one article at a time. "Bulk" mode allows you to generate multiple articles in a single operation, ideal for large-scale content creation.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('common[mode]', array(
					'options' => $modes,
					'value' => $gMode,
					'attrs' => 'class="wbw-small-field" id="waicTaskMode"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-hidden" data-parent-select="common[mode]" data-select-value="pro">
		<div class="wbw-alert-block">
			<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Upgrade to Access Pro Features', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-alert-info"><?php esc_html_e('This feature is available for Pro users only. Upgrade now to unlock advanced functionalities and enhance your experience!', 'ai-copilot-content-generator'); ?></div>
		</div>
	</div>
<?php 
$hidden = ( 'single' == $gMode ? '' : ' wbw-hidden' );
$sHidden = ( 'single' != $gMode && !$props['is_pro'] ? ' wbw-hidden' : '' );
?>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="common[mode]" data-select-value="single">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Topic', 'ai-copilot-content-generator'); ?> <sup>*</sup></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the main subject or theme of the article. This field defines the core content around which the article will be generated.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('single[topic]', array(
					'value' => WaicUtils::getArrayValue($single, 'topic', ''),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="common[mode]" data-select-value="single">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Keywords', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the keywords that should be incorporated into the article. Separate multiple keywords with commas. These keywords will help optimize the content for search engines and ensure relevant topics are covered.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('single[keywords]', array(
					'value' => WaicUtils::getArrayValue($single, 'keywords', ''),
				));
				?>
			</div>
		</div>
	</div>
<?php 
	$this->includeExtTemplate('postscreatepro', 'adminPostsCreateTabParamsBulk');
?>
	<div class="wbw-settings-form row" data-parent-select="common[mode]" data-select-value="single bulk">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Additional prompt', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide any extra information or context to guide the content generation for the entire article. This prompt applies to the entire article, including all selected fields. If you want to specify an additional prompt for a specific field, you can do so below in the Article Fields section under the necessary field.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('common[e_prompt]', array(
					'checked' => WaicUtils::getArrayValue($common, 'e_prompt', 0),
				));
				?>
			</div>
		</div>
	</div>
<?php 
	$hidden = WaicUtils::getArrayValue($common, 'e_prompt', 0) ? '' : ' wbw-hidden';
?>
	<div class="wbw-settings-form wbw-settings-sub row<?php echo esc_attr($hidden); ?>" data-parent-check="common[e_prompt]" data-parent-select="common[mode]" data-select-value="single bulk">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::textarea('common[prompt]', array(
					'value' => WaicUtils::getArrayValue($common, 'prompt', ''),
					'rows' => 4,
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-group-title<?php echo esc_attr($sHidden); ?>" data-parent-select="common[mode]" data-select-value="single bulk">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Article fields', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the specific fields you want to be generated for your article. Use the dropdown menu to choose a desired field and click "Add" to include it in your article setup. Once added, you can customize the generation parameters for each selected field, ensuring your article meets your specific requirements.', 'ai-copilot-content-generator'); ?>">
			<select class="wbw-small-field" id="waicFieldsForAdd">
			<?php 
			foreach ($fields as $key => $data) { 
				if (!empty($data['hidden'])) {
					continue;
				} else if ('custom' == $key) {
					?>
					<optgroup label="<?php echo esc_attr($data['label']); ?>">
					<?php foreach ($data['taxonomies'] as $slug => $label) { ?>
						<option value="custom-<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
					<?php } ?>
					</optgroup>
				<?php } else if (empty($data['results'])) { ?>
					<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($data['label']); ?></option>
				<?php } ?>
			<?php } ?>
			</select>
			
			<?php 
				/*WaicHtml::selectbox('', array(
					'options' => $fieldsList,
					//'value' => WaicUtils::getArrayValue($options, 'model', $defaults['model']),
					'attrs' => 'class="wbw-small-field" id="waicFieldsForAdd"',
				));*/
			?>
			<button id="waicAddField" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo esc_attr($sHidden); ?>" data-parent-select="common[mode]" data-select-value="single bulk">
		<div class="wbw-sections-list col-12">
			<?php 
			foreach ($fields as $key => $data) { 
				if (!empty($data['hidden']) || 'custom' == $key || !empty($data['results'])) {
					continue;
				}
				$hidden = !$data['required'] && !isset($selectedFields[$key]) ? ' wbw-hidden' : '';
				?>
			<div class="wbw-section<?php echo esc_attr($hidden); ?>" data-field="<?php echo esc_attr($key); ?>" data-required="<?php echo $data['required'] ? 1 : 0; ?>">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html($data['label']); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove<?php echo $data['required'] ? ' disabled' : ''; ?>"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include_once 'adminFieldOptions' . waicStrFirstUp($key) . '.php'; ?>
				</div>
			</div>
		<?php } ?>
		<?php 
		if (isset($fields['custom'])) {
			//var_dump($fields['custom']);
			$selectedCustom = WaicUtils::getArrayValue($selectedFields, 'custom', array(), 2);
			foreach ($fields['custom']['taxonomies'] as $customSlug => $label) { 
				$this->props['custom_slug'] = $customSlug;
				$hidden = !isset($selectedCustom[$customSlug]) ? ' wbw-hidden' : '';
				?>
			<div class="wbw-section<?php echo esc_attr($hidden); ?>" data-field="<?php echo esc_attr('custom-' . $customSlug); ?>" data-required="0">
				<div class="wbw-section-header">
					<div class="wbw-section-title"><?php echo esc_html($label); ?></div>
					<div class="wbw-section-action">
						<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
						<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
					</div>
				</div>
				<div class="wbw-section-options wbw-hidden">
					<?php include 'adminFieldOptionsCustom.php'; ?>
				</div>
			</div>
			<?php } ?>
		<?php } ?>
		</div>
	</div>
</section>