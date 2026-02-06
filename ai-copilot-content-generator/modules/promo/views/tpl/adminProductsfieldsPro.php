<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$imgPath = $props['img_path'];
?>
<section class="waic-content-ad">
	<div class="waic-ad-header-block">
		<div class="waic-ad-header"> 
			<div class="waic-ad-sub-title"><?php esc_html_e('WooCommerce Product Generator', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-title"><?php echo esc_html__('Turn Any Woo Product Into A Best-Seller', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-text"><?php esc_html_e('Bulk-generate SEO-optimized, high-converting WooCommerce product fields in one click — including titles, descriptions, categories, tags, images, and reviews. No copywriters needed.', 'ai-copilot-content-generator'); ?></div>
			<a href="https://aiwuplugin.com/#pricing" target="_blank" class="wbw-button wbw-button-pro"><?php echo esc_html__('Upgrade to PRO', 'ai-copilot-content-generator'); ?></a>
		</div>
		<div class="waic-ad-header-image"><img src="<?php echo esc_url($imgPath . '/productsfields-header.png'); ?>" alt="WooCommerce Product Generator"></div>
	</div>
	<div class="waic-ad-body-block">
		<ul class="waic-ad-body"> 
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-custom.png'); ?>" alt="Complete Product Fields">
				<div class="waic-ad-body-title"><?php esc_html_e('Complete Product Fields', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Generate titles, descriptions, categories, tags, images, and reviews all in one click. Everything you need for professional product listings.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-ft.png'); ?>" alt="Bulk Generation">
				<div class="waic-ad-body-title"><?php esc_html_e('Bulk Generation', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Create multiple product listings simultaneously. Save hours of manual work by generating dozens of products at once with consistent quality.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-embed.png'); ?>" alt="SEO-Optimized Content">
				<div class="waic-ad-body-title"><?php esc_html_e('SEO-Optimized Content', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Every generated product is optimized for search engines and conversions. Get high-converting product descriptions that drive sales automatically.', 'ai-copilot-content-generator'); ?></div>
			</li>
		</ul>
	</div>
</section>