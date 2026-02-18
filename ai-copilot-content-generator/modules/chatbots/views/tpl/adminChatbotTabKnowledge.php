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
	place for ADS
</section>
<?php } ?>