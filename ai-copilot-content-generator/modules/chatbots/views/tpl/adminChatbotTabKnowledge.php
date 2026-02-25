<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
if ($props['is_pro']) {
	$this->includeExtTemplate('chatbotspro', 'adminChatbotTabKnowledge');
} else {
?>
<section class="wbw-body-workspace">
	<div class="waic-img-ad">
		<a href="https://aiwuplugin.com/#pricing" target="_blank">
			<img src="<?php echo esc_url($props['img_url'] . 'knowledge.png'); ?>">
		</a>
	</div>
</section>
<?php } ?>