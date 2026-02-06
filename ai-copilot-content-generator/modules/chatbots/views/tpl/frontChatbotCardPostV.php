<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$tools = $props['tools'];
$postObj = $props['obj'];
$objId = $postObj->ID;
?>
<div class="waic-chatbot-card waic-card-post waic-card-ver" data-obj-id="<?php echo esc_attr($objId); ?>" data-href="<?php echo esc_url(get_permalink($postObj)); ?>">
	<?php if (WaicUtils::getArrayValue($tools, 'post_card_image', 0, 1)) { ?>
		<div class="waic-card-image"><?php echo wp_get_attachment_image(get_post_thumbnail_id($objId), 'large'); ?></div>
	<?php } ?>
	<div class="waic-card-body">
		<?php if (WaicUtils::getArrayValue($tools, 'post_card_cat', 0, 1)) { ?>
			<div class="waic-card-cat"><?php echo wp_kses_post(WaicUtils::getTaxonomyTermsList($objId, 'category')); ?></div>
		<?php } ?>
		<?php if (WaicUtils::getArrayValue($tools, 'post_card_name', 0, 1)) { ?>
			<div class="waic-card-name"><?php echo wp_kses_post($postObj->post_title); ?></div>
		<?php } ?>
		<?php if (WaicUtils::getArrayValue($tools, 'post_card_desc', 0, 1)) { ?>
			<div class="waic-card-desc"><?php echo wp_trim_words(wp_strip_all_tags(isset($postObj->post_excerpt) && !empty($postObj->post_excerpt) ? $postObj->post_excerpt : ''), 255); ?></div>
		<?php } ?>
		<div class="waic-card-footer">
			<?php 
				if (WaicUtils::getArrayValue($tools, 'post_card_author', 0, 1)) {
				$user = get_userdata($postObj->post_author);
				if ($user) {
				?>
					<div class="waic-card-author"><?php echo esc_html($user->display_name); ?></div>
				<?php } ?>
			<?php } ?>
			<?php if (WaicUtils::getArrayValue($tools, 'post_card_date', 0, 1)) { ?>
				<div class="waic-card-date"><?php echo wp_kses_post(WaicUtils::convertDateTimeToFront($postObj->post_date, true)); ?></div>
			<?php } ?>
		</div>
	</div>
</div>

