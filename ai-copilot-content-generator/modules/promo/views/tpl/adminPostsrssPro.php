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
			<div class="waic-ad-sub-title"><?php esc_html_e('Autoblogging', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-title"><?php echo esc_html__('Turn Your WordPress Into a 24/7 Content Machine', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-text"><?php esc_html_e('Transform any RSS feed into SEO-ready articles and social media posts automatically. Create a 24/7 content machine for your WordPress site with complete control over scheduling, customization, and management.', 'ai-copilot-content-generator'); ?></div>
			<a href="https://aiwuplugin.com/#pricing" target="_blank" class="wbw-button wbw-button-pro"><?php echo esc_html__('Upgrade to PRO', 'ai-copilot-content-generator'); ?></a>
		</div>
		<div class="waic-ad-header-image">
			<iframe class="waic-video-iframe loaded" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" src="https://www.youtube.com/embed/fh2MV2NTVRk?autoplay=0&amp;rel=0" title="WordPress Autoblogging Tutorial – RSS Feed Aggregator Plugin">
			</iframe>
		</div>
	</div>
	<div class="waic-ad-body-block">
		<ul class="waic-ad-body"> 
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-custom.png'); ?>" alt="Connect RSS Feeds & Schedule">
				<div class="waic-ad-body-title"><?php esc_html_e('Connect RSS Feeds & Schedule', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Set up RSS feeds and define custom publishing schedules. Control frequency, number of posts per cycle, and timing for automated content delivery.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-ft.png'); ?>" alt="Customizable Content Fields">
				<div class="waic-ad-body-title"><?php esc_html_e('Customizable Content Fields', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Tailor every aspect of your content from titles to full articles and social media posts. Complete control to match your brand voice and messaging.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-embed.png'); ?>" alt="Real-Time Management">
				<div class="waic-ad-body-title"><?php esc_html_e('Real-Time Management', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Track and manage your content generation with a user-friendly dashboard. Get instant notifications and full transparency over your publishing process.', 'ai-copilot-content-generator'); ?></div>
			</li>
		</ul>
	</div>
</section>