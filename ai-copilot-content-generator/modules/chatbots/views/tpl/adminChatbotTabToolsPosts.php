<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$tools = WaicUtils::getArrayValue($props['settings'], 'tools', array(), 2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Recommend Posts', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html__('Enable AI-powered blog post recommendations. When activated, the chatbot can search your posts and suggest relevant articles based on user queries.', 'ai-copilot-content-generator') . '<br><br>' . esc_html__('Note: Perplexity models do not support this feature.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('tools[post_enabled]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_enabled', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-3"><?php esc_html_e('When to Recommend Posts', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('This instruction tells the AI when to use the post search function.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::textarea('tools[post_prompt]', array(
				'value' => WaicUtils::getArrayValue($tools, 'post_prompt', __('When a user asks about articles, blog posts, or content on our website.', 'ai-copilot-content-generator')),
				'rows' => 6,
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Enable Taxonomy Search', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html__('Enable advanced filtering by categories and tags. When enabled, AI can search by specific categories and tags for more accurate results. This uses additional API calls but provides better search precision. When disabled, search will only use post titles, content, and basic filters (author, date).', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('tools[post_taxonomies]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_taxonomies', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Max Results per Response', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Maximum number of post cards to show in a single response. Recommended: 1-3 posts.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::number('tools[post_limit]', array(
				'value' => WaicUtils::getArrayValue($tools, 'post_limit', 3, 1),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<?php 
$modeInclude = WaicUtils::getArrayValue($tools, 'post_include');
$args = array(
	'parent' => 0,
	'hide_empty' => 0,
	'orderby' => 'name',
	'order' => 'asc',
);
$workspace = WaicFrame::_()->getModule('workspace');
$categories = $workspace->getTaxonomyHierarchy('category', $args);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Include Posts', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Control which posts are available for recommendations.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[post_include]', array(
				'options' => array(
					'' => __('All posts', 'ai-copilot-content-generator'),
					'cat' => __('Specific categories', 'ai-copilot-content-generator'),
					'ids' => __('Specific posts', 'ai-copilot-content-generator'),
				),
				'value' => $modeInclude,
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'cat' == $modeInclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_include]" data-select-value="cat">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('tools[post_inc_cat]', array(
				'options' => $categories,
				'value' => WaicUtils::getArrayValue($tools, 'post_inc_cat'),
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'ids' == $modeInclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_include]" data-select-value="ids">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter post IDs separated by commas', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('tools[post_inc_ids]', array(
					'value' => WaicUtils::getArrayValue($tools, 'post_inc_ids'),
				));
			?>
		</div>
	</div>
</div>
<?php 
$modeExclude = WaicUtils::getArrayValue($tools, 'post_exclude');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Exclude Posts', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Exclude specific posts or categories from recommendations.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[post_exclude]', array(
				'options' => array(
					'' => __('None', 'ai-copilot-content-generator'),
					'cat' => __('Specific categories', 'ai-copilot-content-generator'),
					'ids' => __('Specific posts', 'ai-copilot-content-generator'),
				),
				'value' => $modeExclude,
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'cat' == $modeExclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_exclude]" data-select-value="cat">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('tools[post_exc_cat]', array(
				'options' => $categories,
				'value' => WaicUtils::getArrayValue($tools, 'post_exc_cat'),
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'ids' == $modeExclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_exclude]" data-select-value="ids">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter post IDs separated by commas', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('tools[post_exc_ids]', array(
					'value' => WaicUtils::getArrayValue($tools, 'post_exc_ids'),
				));
			?>
		</div>
	</div>
</div>
<?php 
$modeCards = WaicUtils::getArrayValue($tools, 'post_card_layout', 'h');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Card Display', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose card layout and customize which post information to display. Vertical cards show more details, while horizontal cards are more compact.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[post_card_layout]', array(
				'options' => array(
					'h' => __('Horizontal', 'ai-copilot-content-generator'),
					'v' => __('Vertical', 'ai-copilot-content-generator'),
				),
				'value' => $modeCards,
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_image]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_image', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Image', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_name]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_name', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Post title', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'v' == $modeCards ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_card_layout]" data-select-value="v">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_desc]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_desc', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Excerpt', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_cat]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_cat', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Category', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'v' == $modeCards ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[post_card_layout]" data-select-value="v">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_author]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_author', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Author', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . 'leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[post_card_date]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'post_card_date', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Date of publication', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>

