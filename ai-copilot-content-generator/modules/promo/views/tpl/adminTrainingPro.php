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
			<div class="waic-ad-sub-title"><?php esc_html_e('AI Training', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-title"><?php echo esc_html__('Train Your Own AI.', 'ai-copilot-content-generator') . '<br>' . esc_html__('No Code Required.', 'ai-copilot-content-generator'); ?></div>
			<div class="waic-ad-header-text"><?php esc_html_e('Unlock the full power of AIWU by training custom language models on your data. Fine-tune models and embed deep knowledge context — without writing a single line of code. Use your AI everywhere: chats, articles, product pages, and more.', 'ai-copilot-content-generator'); ?></div>
			<a href="https://aiwuplugin.com/#pricing" target="_blank" class="wbw-button wbw-button-pro"><?php echo esc_html__('Upgrade to PRO', 'ai-copilot-content-generator'); ?></a>
		</div>
		<div class="waic-ad-header-image"><img src="<?php echo esc_url($imgPath . '/training-header.png'); ?>" alt="AI Training"></div>
	</div>
	<div class="waic-ad-body-block">
		<ul class="waic-ad-body"> 
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-custom.png'); ?>" alt="Your custom datasets">
				<div class="waic-ad-body-title"><?php esc_html_e('Your custom datasets', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Teach your AI everything about your products, support, or business. Import files, paste text, or auto-sync from your site — and structure your data in seconds. Zero coding. Zero friction.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-ft.png'); ?>" alt="Fine-Tune Your Own AI Model">
				<div class="waic-ad-body-title"><?php esc_html_e('Fine-Tune Your Own AI Model', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Create a fully personalized language model trained on your data. Improve precision, match your tone, and unlock powerful automation across any use case.', 'ai-copilot-content-generator'); ?></div>
			</li>
			<li>
				<img src="<?php echo esc_url($imgPath . '/lending-embed.png'); ?>" alt="Embed Deep Knowledge Instantly">
				<div class="waic-ad-body-title"><?php esc_html_e('Embed Deep Knowledge Instantly', 'ai-copilot-content-generator'); ?></div>
				<div class="waic-ad-body-text"><?php esc_html_e('Turn massive docs and site content into searchable vector databases. Use embeddings to supercharge chatbots, smart assistants, and content generation.', 'ai-copilot-content-generator'); ?></div>
			</li>
		</ul>
	</div>
</section>