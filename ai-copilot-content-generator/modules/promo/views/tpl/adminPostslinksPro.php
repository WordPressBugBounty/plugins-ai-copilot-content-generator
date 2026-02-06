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
			<div class="waic-ad-sub-title"><?php esc_html_e('Smart Post Crosslinking', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-title"><?php echo esc_html__('Effortless Internal & External Linking', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-text"><?php esc_html_e('Automatically enhance your articles with relevant internal and external links. AI analyzes each post and generates contextually accurate links to boost SEO and engagement with minimal effort.', 'ai-copilot-content-generator'); ?></div>
			<a href="https://aiwuplugin.com/#pricing" target="_blank" class="wbw-button wbw-button-pro"><?php echo esc_html__('Upgrade to PRO', 'ai-copilot-content-generator'); ?></a>
		</div>
		<div class="waic-ad-header-image"><img src="<?php echo esc_url($imgPath . '/postslinks-header.png'); ?>" alt="Smart Post Crosslinking"></div>
	</div>
	<div class="waic-ad-body-block">
		<ul class="waic-ad-body"> 
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-custom.png'); ?>" alt="Strategic Link Configuration">
				<div class="waic-ad-body-title"><?php esc_html_e('Strategic Link Configuration', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Define how links will be added to your content. Choose between one link per article or multiple links per article, set the publishing status, and fine-tune placement settings for maximum SEO impact.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-ft.png'); ?>" alt="Select Target Articles">
				<div class="waic-ad-body-title"><?php esc_html_e('Select Target Articles', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Easily pick the articles where links should be placed. Add URLs, set anchor keywords, and let AI generate seamless, contextually relevant paragraphs that naturally integrate the links.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-embed.png'); ?>" alt="Review, Approve & Publish">
				<div class="waic-ad-body-title"><?php esc_html_e('Review, Approve & Publish', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Preview the AI-generated paragraphs before publishing. Ensure every link placement is relevant and well-structured, then either approve changes manually or let the system publish them instantly.', 'ai-copilot-content-generator'); ?></div>
			</li>
		</ul>
	</div>
</section>