<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$tools = WaicUtils::getArrayValue($props['settings'], 'tools', array(), 2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Recommend Products', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html__('Enable AI-powered product recommendations. When activated, the chatbot can search your WooCommerce catalog and suggest relevant products based on user queries. Note: Perplexity models do not support this feature.', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('tools[prod_enabled]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_enabled', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row wbw-settings-top">
	<div class="wbw-settings-label col-3"><?php esc_html_e('When to Recommend Products', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('This instruction tells the AI when to use the product search function.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::textarea('tools[prod_prompt]', array(
				'value' => WaicUtils::getArrayValue($tools, 'prod_prompt', __('When a user asks about products, items, or anything related to our store catalog.', 'ai-copilot-content-generator')),
				'rows' => 6,
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Enable Taxonomy Search', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html__('Enable advanced filtering by categories, tags, and product attributes. When enabled, AI can search by specific colors, sizes, brands, and other product characteristics for more accurate results. This uses additional API calls but provides better search precision. When disabled, search will only use product titles, descriptions, and basic filters (price, featured, on sale).', 'ai-copilot-content-generator'); ?>">
		<?php
			WaicHtml::checkbox('tools[prod_taxonomies]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_taxonomies', 0, 1),
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Maximum Products per Response', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Maximum number of product cards to show in a single response. Recommended: 1-3 products.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::number('tools[prod_limit]', array(
				'value' => WaicUtils::getArrayValue($tools, 'prod_limit', 3, 1),
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<?php 
$modeInclude = WaicUtils::getArrayValue($tools, 'prod_include');
$args = array(
	'parent' => 0,
	'hide_empty' => 0,
	'orderby' => 'name',
	'order' => 'asc',
);
$workspace = WaicFrame::_()->getModule('workspace');
$categories = $workspace->getTaxonomyHierarchy('product_cat', $args);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Include Products', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Control which products are available for recommendations.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[prod_include]', array(
				'options' => array(
					'' => __('All products', 'ai-copilot-content-generator'),
					'cat' => __('Specific categories', 'ai-copilot-content-generator'),
					'ids' => __('Specific products', 'ai-copilot-content-generator'),
				),
				'value' => $modeInclude,
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'cat' == $modeInclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_include]" data-select-value="cat">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('tools[prod_inc_cat]', array(
				'options' => $categories,
				'value' => WaicUtils::getArrayValue($tools, 'prod_inc_cat'),
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'ids' == $modeInclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_include]" data-select-value="ids">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter product IDs separated by commas', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('tools[prod_inc_ids]', array(
					'value' => WaicUtils::getArrayValue($tools, 'prod_inc_ids'),
				));
			?>
		</div>
	</div>
</div>
<?php 
$modeExclude = WaicUtils::getArrayValue($tools, 'prod_exclude');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Exclude Products', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Exclude specific products or categories from recommendations.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[prod_exclude]', array(
				'options' => array(
					'' => __('None', 'ai-copilot-content-generator'),
					'cat' => __('Specific categories', 'ai-copilot-content-generator'),
					'ids' => __('Specific products', 'ai-copilot-content-generator'),
				),
				'value' => $modeExclude,
				'attrs' => 'class="wbw-small-field"',
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'cat' == $modeExclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_exclude]" data-select-value="cat">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('tools[prod_exc_cat]', array(
				'options' => $categories,
				'value' => WaicUtils::getArrayValue($tools, 'prod_exc_cat'),
			));
			?>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'ids' == $modeExclude ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_exclude]" data-select-value="ids">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter product IDs separated by commas', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('tools[prod_exc_ids]', array(
					'value' => WaicUtils::getArrayValue($tools, 'prod_exc_ids'),
				));
			?>
		</div>
	</div>
</div>
<?php 
$modeCards = WaicUtils::getArrayValue($tools, 'prod_card_layout', 'h');
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"><?php esc_html_e('Card Display', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose card layout and customize which product information to display. Vertical cards show more details, while horizontal cards are more compact.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('tools[prod_card_layout]', array(
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
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_image]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_image', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Image', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_name]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_name', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Product name', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'v' == $modeCards ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_card_layout]" data-select-value="v">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_desc]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_desc', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Short Description', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_price]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_price', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Price', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'v' == $modeCards ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_card_layout]" data-select-value="v">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_cat]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_cat', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Category', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row<?php echo 'v' == $modeCards ? '' : ' wbw-hidden'; ?>" data-parent-select="tools[prod_card_layout]" data-select-value="v">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_featured]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_featured', 0, 1),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Featured', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-3"></div>
	<div class="wbw-settings-fields col-9">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::checkbox('tools[prod_card_cart]', array(
				'checked' => WaicUtils::getArrayValue($tools, 'prod_card_cart', 1, 1, false, true, true),
			));
			?>
		<label class="wbw-settings-after"><?php esc_html_e('Add to cart button', 'ai-copilot-content-generator'); ?></label>
		</div>
	</div>
</div>


