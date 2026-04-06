<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ($this->props['is_pro']) {
	$this->includeExtTemplate('chatbotspro', 'adminChatbotTabKnowledge');
} else {
?>
<section class="wbw-body-workspace">
	<div class="waic-img-ad">
		<a href="https://aiwuplugin.com/#pricing" target="_blank">
			<img src="<?php echo esc_url($this->props['img_url'] . 'knowledge.png'); ?>">
		</a>
	</div>
</section>
<?php } ?>