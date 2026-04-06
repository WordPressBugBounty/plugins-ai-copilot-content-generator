<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$props = $this->props;
$apiOptions = WaicUtils::getArrayValue($props, 'options', array(), 2);
$apiVariations = WaicUtils::getArrayValue($props, 'variations', array(), 2);
$lang = get_option('WPLANG');
$module = $this->getModule();
$workspace = WaicFrame::_()->getModule('workspace');
$eProducts = WaicUtils::getArrayValue($props, 'exist_products', 0, 1);
$ePosts = WaicUtils::getArrayValue($props, 'exist_posts', 0, 1);

?>
<section class="wbw-body-options">
	<div class="waic-body-sub-title">
		<?php esc_html_e('Configure everything in one place — your chatbot will be live in seconds.', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="waic-body-content waic-body-setup">
		<form id="waicChatbotCreateForm">
		<div class="waic-setup-wrap">
			<div class="waic-setup-title">
				<?php esc_html_e('Bot Identity', 'ai-copilot-content-generator'); ?>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Bot Name', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('context[ai_name]', array(
							'value' => __('Chat bot', 'ai-copilot-content-generator'),
						));
					?>
					<input type="hidden" name="context[e_ai_avatar]" value="1">
					<input type="hidden" name="context[show_ai_data]" value="none">
					</div>
				</div>
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Default Language', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectbox('setup[language]', array(
							'options' => WaicUtils::getArrayValue($apiVariations, 'language', array(), 2),
							'value' => empty($lang) ? 'en' : $lang,
						));
					?>
					</div>
				</div>
			</div>
<?php 
$aiAvatar = 'ai_avatar0.png';
$isCustom = strpos($aiAvatar, 'ai_avatar') !== 0;
$imgUrl = $props['img_url'];
$aiAvatars = $props['ai_avatars'];
?>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Avatar', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
						<div class="wbw-settings-field waic-media-wrap">
							<div class="waic-gallery-wrap">
								<div class="waic-settings-gallery">
									<?php foreach ($aiAvatars as $avatar) { ?>
										<div class="waic-gallery-element<?php echo ( $avatar == $aiAvatar ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($avatar); ?>">
											<img src="<?php echo esc_url($imgUrl . 'ai_avatars/' . $avatar); ?>">
										</div>
									<?php } ?>
									<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">+</div>
									<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
										<img src="<?php echo esc_url($isCustom ? $aiAvatar : ''); ?>" class="waic-custom-media">
										<div class="waic-media-delete"><i class="fa fa-close"></i></div>
									</div>
								</div>
							</div>
							<?php WaicHtml::hidden('context[ai_avatar]', array('value' => $aiAvatar)); ?>
						</div>
					</div>
				</div>
			</div>
<?php 
$aiRoles = array(
	__('Handle customer support inquiries. Help with order tracking, returns, FAQs, and technical issues. Escalate complex issues to human agents.', 'ai-copilot-content-generator'),
	__('We help customers find the perfect products. Assist with recommendations, pricing, and promotions. Be friendly and enthusiastic.', 'ai-copilot-content-generator'),
	__('Answer frequently asked questions about products, services, policies, and business hours. Be concise and helpful.', 'ai-copilot-content-generator'),
);
$defRole = $eProducts ? 1 : 0;
?>
		<div class="waic-setup-settings">
				<div class="waic-setup-setting waic-check-wrapper">
					<div class="waic-setup-label"><?php esc_html_e('Bot Name', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-buttons mb-2">
						<button type="button" class="wbw-button waic-setup-check wbw-button-small<?php echo empty($defRole) ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($aiRoles[0]); ?>"><?php esc_html_e('Support Agent', 'ai-copilot-content-generator'); ?></button>
						<button type="button" class="wbw-button waic-setup-check wbw-button-small<?php echo ( 1 == $defRole ) ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($aiRoles[1]); ?>"><?php esc_html_e('Sales Assistant', 'ai-copilot-content-generator'); ?></button>
						<button type="button" class="wbw-button waic-setup-check wbw-button-small" data-value="<?php echo esc_attr($aiRoles[2]); ?>"><?php esc_html_e('FAQ Bot', 'ai-copilot-content-generator'); ?></button>
					</div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::textarea('setup[role]', array(
							'value' => $aiRoles[$defRole],
						));
					?>
					</div>
				</div>
			</div>
		</div>
		<div class="waic-setup-wrap compact">
			<div class="waic-setup-title">
				<?php esc_html_e('Capabilities', 'ai-copilot-content-generator'); ?>
			</div>
			<div class="waic-setup-block<?php echo $eProducts ? ' on' : '';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon waic-green-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_tool_prod.svg'); ?>">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name"><?php esc_html_e('Product Recommendations', 'ai-copilot-content-generator'); ?></div>
							<div class="waic-setup-block-desc"><?php esc_html_e('Search and recommend WooCommerce products in rich cards', 'ai-copilot-content-generator'); ?></div>
						</div>
					</div>
					<div class="waic-input-toggle">
						<input type="hidden" name="tools[prod_enabled]" value="<?php echo esc_attr($eProducts); ?>">
					</div>
				</div>
			</div>
			<div class="waic-setup-block<?php echo $ePosts ? ' on' : '';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon waic-blue-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_tool_post.svg'); ?>">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name"><?php esc_html_e('Blog Post Recommendations', 'ai-copilot-content-generator'); ?></div>
							<div class="waic-setup-block-desc"><?php esc_html_e('Find and suggest relevant posts during conversations', 'ai-copilot-content-generator'); ?></div>
						</div>
					</div>
					<div class="waic-input-toggle">
						<input type="hidden" name="tools[post_enabled]" value="<?php echo esc_attr($ePosts); ?>">
					</div>
				</div>
			</div>
<?php 
if ($props['is_pro']) {
	$eEmbedProducts = 0;
	$eEmbedPosts = 0;
	$eEmbedPages = 0;
	$args = array(
		'parent' => 0,
		'hide_empty' => 0,
		'orderby' => 'name',
		'order' => 'asc',
	);
?>
			<div class="waic-setup-divider"></div>
			<div class="waic-setup-block<?php echo $eEmbedProducts ? ' on' : '';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon waic-orange-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_embed_prod.svg'); ?>">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name"><?php esc_html_e('Product Knowledge', 'ai-copilot-content-generator'); ?><span class="waic-embed-badge"><?php esc_html_e('Embeddings', 'ai-copilot-content-generator'); ?></span></div>
							<div class="waic-setup-block-desc"><?php esc_html_e('Deep semantic search over your product catalog', 'ai-copilot-content-generator'); ?></div>
						</div>
					</div>
					<div class="waic-input-toggle">
						<input type="hidden" class="waic-embed-trigger" name="setup[knowledge_prods]" value="<?php echo esc_attr($eEmbedProducts); ?>">
					</div>
				</div>
				<div class="waic-setup-block-body">
					<div class="waic-setup-settings">
						<div class="waic-setup-setting">
							<div class="waic-setup-label"><?php esc_html_e('Include products', 'ai-copilot-content-generator'); ?></div>
							<div class="waic-setup-field">
							<?php 
								WaicHtml::selectbox('knowledge[prods][prod_include]', array(
									'options' => array(
										'' => __('All products', 'ai-copilot-content-generator'),
										'cat' => __('Specific categories', 'ai-copilot-content-generator'),
									),
								));
							?>
							</div>
						</div>
					</div>
					<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[prods][prod_include]" data-select-value="cat">
						<div class="waic-setup-setting">
							<div class="waic-setup-field">
							<?php 
								WaicHtml::selectlist('knowledge[prods][prod_inc_cat][]', array(
									'options' => $workspace->getTaxonomyHierarchy('product_cat', $args),
									'no_chosen' => 1,
									'class' => 'waic-multiselect',
								));
							?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="waic-setup-block<?php echo $eEmbedPosts ? ' on' : '';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon waic-violet-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_embed_post.svg'); ?>">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name"><?php esc_html_e('Post Knowledge', 'ai-copilot-content-generator'); ?><span class="waic-embed-badge"><?php esc_html_e('Embeddings', 'ai-copilot-content-generator'); ?></span></div>
							<div class="waic-setup-block-desc"><?php esc_html_e('Semantic understanding of your blog posts', 'ai-copilot-content-generator'); ?></div>
						</div>
					</div>
					<div class="waic-input-toggle">
						<input type="hidden" class="waic-embed-trigger" name="setup[knowledge_posts]" value="<?php echo esc_attr($eEmbedPosts); ?>">
					</div>
				</div>
				<div class="waic-setup-block-body">
					<div class="waic-setup-settings">
						<div class="waic-setup-setting">
							<div class="waic-setup-label"><?php esc_html_e('Include posts', 'ai-copilot-content-generator'); ?></div>
							<div class="waic-setup-field">
							<?php 
								WaicHtml::selectbox('knowledge[posts][post_include]', array(
									'options' => array(
										'' => __('All posts', 'ai-copilot-content-generator'),
										'cat' => __('Specific categories', 'ai-copilot-content-generator'),
									),
								));
							?>
							</div>
						</div>
					</div>
					<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[posts][post_include]" data-select-value="cat">
						<div class="waic-setup-setting">
							<div class="waic-setup-field">
							<?php 
								WaicHtml::selectlist('knowledge[posts][post_inc_cat][]', array(
									'options' => $workspace->getTaxonomyHierarchy('category', $args),
									'no_chosen' => 1,
									'class' => 'waic-multiselect',
								));
							?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="waic-setup-block<?php echo $eEmbedPosts ? ' on' : '';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon waic-violet-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_embed_post.svg'); ?>">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name"><?php esc_html_e('Page Knowledge', 'ai-copilot-content-generator'); ?><span class="waic-embed-badge"><?php esc_html_e('Embeddings', 'ai-copilot-content-generator'); ?></span></div>
							<div class="waic-setup-block-desc"><?php esc_html_e('Semantic understanding of your pages', 'ai-copilot-content-generator'); ?></div>
						</div>
					</div>
					<div class="waic-input-toggle">
						<input type="hidden" class="waic-embed-trigger" name="setup[knowledge_pages]" value="<?php echo esc_attr($eEmbedPosts); ?>">
					</div>
				</div>
			</div>
	
<?php 
} 
$aiEngine = WaicUtils::getArrayValue($apiOptions, 'engine', 'open-ai');
$aiModels = WaicUtils::getArrayValue($apiVariations, 'model');
$modelField = WaicUtils::getArrayValue($apiVariations['model-fields'], $aiEngine, 'model');
$keyFields = WaicUtils::getArrayValue($apiVariations, 'key-fields', array(), 2);
$aiKeys = array();
foreach ($keyFields as $e => $f) {
	$aiKeys[$e] = WaicUtils::getArrayValue($apiOptions, $f);
}
$aiModel = WaicUtils::getArrayValue($apiOptions, $modelField);
$connected = !empty($aiKeys[$aiEngine]);
?>
		</div>
		<div class="waic-setup-wrap">
			<div class="waic-setup-title">
				<?php esc_html_e('AI Provider & Model', 'ai-copilot-content-generator'); ?>
			</div>
			<div class="waic-setup-settings waic-api-models-block">
				<div class="waic-setup-buttons waic-setup-engine waic-check-wrapper">
				<input type="hidden" class="waic-ai-engine" id="waicAIEngine" name="setup[engine]" value="<?php echo esc_attr($aiEngine); ?>" data-model="#waicAIModel" data-models="<?php echo esc_attr(htmlentities(WaicUtils::jsonEncode($aiModels), ENT_COMPAT)); ?>">
			<?php 
				$logoUrl = WAIC_IMG_PATH . 'logo_ai/';
				foreach ($apiVariations['engines'] as $engine => $engineName) { ?>
					<button type="button" class="waic-setup-check<?php echo $engine == $aiEngine ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($engine); ?>">
						<img src="<?php echo esc_url($logoUrl . $engine) . '.svg'; ?>">
						<div class="waic-setup-engine-name"><?php echo esc_html($engineName); ?></div>
					</button>
			<?php } ?>
				</div>
			</div>
			<div class="waic-setup-block waic-setup-connect<?php echo $connected ? '' : ' on';?>">
				<div class="waic-setup-block-head">
					<div class="waic-setup-block-left">
						<div class="waic-setup-block-icon">
							<img src="<?php echo esc_url($imgUrl . 'setup_connected.svg'); ?>" class="waic-connected">
							<img src="<?php echo esc_url($imgUrl . 'setup_not_connected.svg'); ?>" class="waic-notconnected">
						</div>
						<div class="waic-setup-block-text">
							<div  class="waic-setup-block-name waic-connected"><?php esc_html_e('Connected', 'ai-copilot-content-generator'); ?></div>
							<div  class="waic-setup-block-name waic-notconnected"><?php esc_html_e('Not Connected', 'ai-copilot-content-generator'); ?></div>
							<div class="waic-setup-block-desc waic-connected"><?php echo esc_attr($module->getModel()->maskApiKey($aiKeys[$aiEngine])); ?></div>
						</div>
					</div>
					<div class="waic-button-toggle waic-connected">
						<button type="button"><?php esc_html_e('Change', 'ai-copilot-content-generator'); ?></button>
					</div>
				</div>
				<div class="waic-setup-block-body">
					<div class="waic-setup-settings wbw-settings-field">
						<div class="waic-setup-setting">
							<div class="waic-setup-field">
							<?php 
								WaicHtml::text('', array(
									'attrs' => 'class="waic-api-key" placeholder="' . esc_attr('Paste your API key here', 'ai-copilot-content-generator') . '"',
								));
							?>
							<input type="hidden" id="waicApiKeys" name="setup[api_keys]" value="<?php echo esc_attr(htmlentities(WaicUtils::jsonEncode($aiKeys), ENT_COMPAT)); ?>">
							</div>
						</div>
						<div class="waic-setup-setting waic-setup-by-content">
							<div class="waic-setup-field">
								<button type="button" class="wbw-button wbw-button-small waic-connect-button"><?php esc_html_e('Connect', 'ai-copilot-content-generator'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Chat Model', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectbox('setup[model]', array(
							'options' => WaicUtils::getArrayValue($aiModels, $aiEngine),
							'value' => $aiModel,
							'attrs' => 'id="waicAIModel"',
						));
					?>
					</div>
				</div>
			</div>
<?php 
if ($props['is_pro']) {
	$trainModule = WaicFrame::_()->getModule('training');
	$emEngine = 'open-ai';
	$embedVector = 'wpvector';
	$embedModel = $trainModule->getModel('embeddings');
	$embedEngines = $embedModel->getEmbedEngines();
	$emModels = $embedModel->getEmbedModels(null, '');
	$embedVectors = $embedModel->getDbVectors();
?>
		<div class="waic-embed-block wbw-hidden">
			<div class="waic-setup-settings waic-api-models-block">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Embeddings Provider', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-buttons waic-setup-engine waic-check-wrapper">
					<input type="hidden" class="waic-ai-engine" name="knowledge[embed][engine]" value="<?php echo esc_attr($emEngine); ?>" data-model="#waicEmbedModel" data-models="<?php echo esc_attr(htmlentities(WaicUtils::jsonEncode($emModels), ENT_COMPAT)); ?>">
				<?php 
					$logoUrl = WAIC_IMG_PATH . 'logo_ai/';
					foreach ($embedEngines as $engine => $engineName) { ?>
						<button type="button" class="waic-setup-check<?php echo $engine == $emEngine ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($engine); ?>">
							<img src="<?php echo esc_url($logoUrl . $engine) . '.svg'; ?>">
							<div class="waic-setup-engine-name"><?php echo esc_html($engineName); ?></div>
						</button>
				<?php } ?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Embeddings Model', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectbox('knowledge[embed][model]', array(
							'options' => WaicUtils::getArrayValue($emModels, $emEngine),
							'attrs' => 'id="waicEmbedModel"',
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Vector Database', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-buttons waic-check-wrapper">
						<?php foreach ($embedVectors as $vector => $vectorName) { ?>
							<button type="button" class="wbw-button waic-setup-check waic-setup-group-button<?php echo $vector == $embedVector ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($vector); ?>"><?php echo esc_attr($vectorName); ?></button>
						<?php } ?>
						<?php 
							WaicHtml::selectbox('knowledge[embed][vector]', array(
								'options' => $embedVectors,
								'value' => $embedVector,
								'attrs' => 'class="wbw-hidden"',
							));
						?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="pinecone">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][pinecone][api_key]', array(
							'attrs' => 'placeholder="API Key"',
						));
						?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="pinecone">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][pinecone][index_host]', array(
							'attrs' => 'placeholder="Index HOST"',
						));
						?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="pinecone">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][pinecone][namespace]', array(
							'attrs' => 'placeholder="Namespace"',
						));
						?>
					</div>
				</div>
				<div class="waic-setup-setting waic-setup-by-content">
					<div class="waic-setup-field">
						<button type="button" class="wbw-button wbw-button-small waic-test-vector"><?php esc_html_e('Test Connection', 'ai-copilot-content-generator'); ?></button>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="qdrant4">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][qdrant4][api_key]', array(
							'attrs' => 'placeholder="API Key"',
						));
						?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="qdrant4">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][qdrant4][api_url]', array(
							'attrs' => 'placeholder="API URL"',
						));
						?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="knowledge[embed][vector]" data-select-value="qdrant4">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::text('knowledge[embed][qdrant4][collection]', array(
							'attrs' => 'placeholder="Collection Name"',
						));
						?>
					</div>
				</div>
				<div class="waic-setup-setting waic-setup-by-content">
					<div class="waic-setup-field">
						<button type="button" class="wbw-button wbw-button-small waic-test-vector"><?php esc_html_e('Test Connection', 'ai-copilot-content-generator'); ?></button>
					</div>
				</div>
			</div>
		</div>
<?php } ?>
		</div>
		<div class="waic-setup-wrap">
			<div class="waic-setup-title">
				<?php esc_html_e('Appearance & Placement', 'ai-copilot-content-generator'); ?>
			</div>
			<div class="waic-setup-settings waic-check-wrapper" data-input="#waicSchemeColor">
				<?php 
					$schemes = $module->getModel()->getColorSchemes(true);
					$scheme = '#2D3E50';
					foreach ($schemes as $color) { 
					?>
						<div class="waic-setup-check waic-btn-scheme<?php echo $scheme == $color ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($color); ?>" style="background-color: <?php echo esc_attr($color); ?>;"></div>
				<?php } ?>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Custom Color', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::colorpicker('appearance[scheme]', array(
							'value' => $scheme,
							'attrs' => 'id="waicSchemeColor"'
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Position', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-buttons waic-check-wrapper">
						<button type="button" class="wbw-button waic-setup-check waic-setup-group-button" data-value="bl"><?php esc_html_e('Bottom Left', 'ai-copilot-content-generator'); ?></button>
						<button type="button" class="wbw-button waic-setup-check waic-setup-group-button selected" data-value="br"><?php esc_html_e('Bottom Right', 'ai-copilot-content-generator'); ?></button>
						<input type="hidden" name="setup[position]" value="br">
					</div>
				</div>
			</div>
<?php 
$pages = $module->getModel()->getDisplayPages();
?>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Show on pages', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectbox('general[display_on]', array(
							'options' => array(
								'all' => __('All pages', 'ai-copilot-content-generator'),
								'specific' => __('Specific pages', 'ai-copilot-content-generator'),
							),
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="general[display_on]" data-select-value="specific">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectlist('general[display_pages]', array(
							'options' => $pages,
							'no_chosen' => 1,
							'class' => 'waic-multiselect',
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-label"><?php esc_html_e('Hide on pages', 'ai-copilot-content-generator'); ?></div>
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectbox('general[hide_on]', array(
							'options' => array(
								'none' => __('Don\'t hide anywhere', 'ai-copilot-content-generator'),
								'specific' => __('Specific pages', 'ai-copilot-content-generator'),
							),
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings wbw-hidden wbw-settings-field" data-parent-select="general[hide_on]" data-select-value="specific">
				<div class="waic-setup-setting">
					<div class="waic-setup-field">
					<?php 
						WaicHtml::selectlist('general[hide_pages]', array(
							'options' => $pages,
							'no_chosen' => 1,
							'class' => 'waic-multiselect',
						));
					?>
					</div>
				</div>
			</div>
			<div class="waic-setup-settings">
				<div class="waic-setup-setting">
					<div class="waic-setup-buttons">
						<button type="button" class="wbw-button waic-setup-group-button wbw-button-main" id="waicLaunchChatbot"><?php esc_html_e('Launch ChatBot', 'ai-copilot-content-generator'); ?></button>
					</div>
					<div class="waic-setup-link">
						<a href="<?php echo esc_url($workspace->getFeatureUrl('chatbots', '', 'adv=1')); ?>"><?php esc_html_e('Or configure manually with advanced settings', 'ai-copilot-content-generator'); ?></a>
					</div>
				</div>
			</div>
		</div>
		</form>
	</div>
<?php 
$loadTexts = array(
	__('Your chatbot is learning everything it needs to know. 📚 This won\'t take long! ⏳', 'ai-copilot-content-generator'),
	__('Analyzing your products and services... 🛍️ Finding the best matches!', 'ai-copilot-content-generator'),
	__('Reading your blog posts and pages... 📝 Understanding your content!', 'ai-copilot-content-generator'),
	__('Learning your brand voice and style... 🎨 Making it uniquely yours!', 'ai-copilot-content-generator'),
	__('Indexing product categories... 📦 Organizing everything!', 'ai-copilot-content-generator'),
	__('Understanding customer questions... 💬 Getting smarter!', 'ai-copilot-content-generator'),
	__('Configuring recommendation engine... ✨ Almost there!', 'ai-copilot-content-generator'),
	__('Optimizing response quality... 🚀 Fine-tuning!', 'ai-copilot-content-generator'),
	__('Setting up personalization... 🎯 Making it perfect!', 'ai-copilot-content-generator'),
	__('Finalizing your AI assistant... 🎉 Ready in seconds!', 'ai-copilot-content-generator'),
);
// phpcs:enable
?>
	<div id="waicWaitingPopup" class="waic-setup-popup-overlay wbw-hidden">
		<div class="waic-setup-popup-body">
			<div class="wbw-waiting-loader">
				<div class="waic-loader">
					<div class="waic-loader-bar bar1"></div>
					<div class="waic-loader-bar bar2"></div>
				</div>
			</div>
			<div class="waic-waiting-text"><?php echo esc_attr($loadTexts[0]); ?></div>
			<div class="waic-progressbar-block">
				<div class="waic-progressbar">
					<span></span>
				</div>
			</div>
			<div class="waic-waiting-percents">0%</div>
		</div>
		<input type="hidden" id="waicWaitingTexts" value="<?php echo esc_attr(htmlentities(WaicUtils::jsonEncode($loadTexts), ENT_COMPAT)); ?>">
	</div>
</section>
