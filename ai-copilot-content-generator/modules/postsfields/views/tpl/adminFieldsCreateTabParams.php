<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$common = WaicUtils::getArrayValue($props['settings'], 'common', array(), 2);
$selectedFields = WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2);
$tPosts = WaicUtils::getArrayValue($props['settings'], 'posts', array(), 2);
$fields = $props['fields'];
$tplPath = $props['tpl_path'];
$publishes = array(
	'no' => __('Generate draft and send for Approval', 'ai-copilot-content-generator'),
	'now' => __('Publish changes immediately', 'ai-copilot-content-generator'),
);

?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Publishing Status', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select how the generated fields will be processed. Generate draft and send for Approval: New changes will be saved as drafts for manual review before publishing. Ideal if you want to check or edit updates before they go live. Publish changes immediately: Automatically applies all updates and publishes them directly to your site without further approval. Suitable for trusted or finalized updates.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('common[publish]', array(
					'options' => $publishes,
					'value' => WaicUtils::getArrayValue($common, 'publish', ''),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Select posts', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select posts.', 'ai-copilot-content-generator'); ?>">
			<button id="waicAddPosts" class="wbw-button wbw-button-small m-0"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
			<button id="waicDeletePosts" class="wbw-button wbw-button-small"><?php esc_html_e('Delete', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="waic-table-data col-12">
			<div class="wbw-table-list">
				<table id="waicPostsList">
					<thead>
						<tr>
							<th><input type="checkbox" class="waicCheckAll"></th>
							<th><?php esc_html_e('Title', 'ai-copilot-content-generator'); ?></th>
							<th><?php esc_html_e('Additional context', 'ai-copilot-content-generator'); ?><img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide specific context or additional details to refine the AI-generated fields for this article. This input can guide the AI to create more accurate and relevant content tailored to your needs.', 'ai-copilot-content-generator'); ?>"></th>
							<th><?php esc_html_e('Keywords to include', 'ai-copilot-content-generator'); ?><img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter a list of keywords separated by commas to guide the AI in focusing on important terms. These keywords can help emphasize specific topics, improve SEO, or ensure the content aligns with your goals.', 'ai-copilot-content-generator'); ?>"></th>
						</tr>
					</thead>
					<?php if (!empty($tPosts)) { ?>
						<tbody>
							<?php foreach ($tPosts as $i => $data) { ?>
								<tr>
									<td><input type="checkbox" class="waicCheckOne" data-id="<?php echo esc_attr($data[0]); ?>"></td>
								<?php if (count($data) == 3) { ?>
									<td><?php echo esc_html(get_the_title($data[0])); ?></td>
									<td><input type="text" class="waic-field-topic" value="<?php echo esc_attr($data[1]); ?>"></td>
									<td><input type="text" class="waic-field-keywords" value="<?php echo esc_attr($data[2]); ?>"></td>
								<?php } ?>
								</tr>
							<?php } ?>
						</tbody>
					<?php } ?>
				</table>
			</div>
		</div>
		<?php 
			WaicHtml::hidden('posts', array('value' => '', 'attrs' => 'id="waicPostsTopics"'));
		?>
	</div>
	<div id="waicPostsListDialog" class="wbw-hidden" title="<?php esc_attr_e('Select posts', 'ai-copilot-content-generator'); ?>">
		<div id="waicSearchPostsFilters">
			<div class="wbw-settings-fields mb-2">
				<div class="wbw-settings-field">
				<?php 
					WaicHtml::text('post_title', array(
						'placeholder' => __('Enter title', 'ai-copilot-content-generator'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
				<?php 
					WaicHtml::selectlist('post_categories[]', array(
						'options' => $this->getTaxonomyHierarchy(0, '', 'category'),
						'attrs' => 'class="wbw-small-field" data-placeholder="' . esc_attr(__('Select categories', 'ai-copilot-content-generator')) . '"',
					));
					?>
				<?php 
					WaicHtml::selectlist('post_tags[]', array(
						'options' => $this->getTaxonomyHierarchy(0, '', 'post_tag'),
						'attrs' => 'class="wbw-small-field" data-placeholder="' . esc_attr(__('Select tags', 'ai-copilot-content-generator')) . '"',
					));
					?>
				<button id="waicFilterPosts" class="wbw-button wbw-button-small"><?php esc_html_e('Filter', 'ai-copilot-content-generator'); ?></button>
				</div>
			</div>
		</div>
		<div class="wbw-table-list waic-table-list">
			<table id="waicPostsSelect">
				<thead>
					<tr>
						<th><input type="checkbox" class="waicCheckAll"></th>
						<th><?php esc_html_e('Title', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Categories', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Tags', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Author', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Date', 'ai-copilot-content-generator'); ?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	<div class="wbw-settings-form row">
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
	<div class="wbw-settings-form wbw-settings-sub row<?php echo esc_attr($hidden); ?>" data-parent-check="common[e_prompt]">
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
	<div class="wbw-settings-form row wbw-group-title">
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
			<button id="waicAddField" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
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
					<?php 
						$fileName = 'adminFieldOptions' . waicStrFirstUp($key) . '.php';
						include_once file_exists(stream_resolve_include_path($fileName)) ? $fileName : $tplPath . $fileName; 
					?>
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
					<?php include $tplPath . 'adminFieldOptionsCustom.php'; ?>
				</div>
			</div>
			<?php } ?>
		<?php } ?>
		</div>
	</div>
</section>