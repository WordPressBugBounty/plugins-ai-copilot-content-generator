<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$props = $this->props;
$tools = $props['tools'];
$defValue = empty($tools) ? 1 : 0;
$postObj = $props['obj'];
$objId = $postObj->ID;
// phpcs:enable
?>
<div class="waic-chatbot-card waic-card-post waic-card-hor" data-obj-id="<?php echo esc_attr($objId); ?>" data-href="<?php echo esc_url(get_permalink($postObj)); ?>">
	<?php if (WaicUtils::getArrayValue($tools, 'post_card_image', $defValue, 1)) { ?>
		<div class="waic-card-image"><?php echo wp_get_attachment_image(get_post_thumbnail_id($objId)); ?></div>
	<?php } ?>
	<div class="waic-card-body">
		<?php if (WaicUtils::getArrayValue($tools, 'post_card_cat', $defValue, 1)) { ?>
			<div class="waic-card-cat"><?php echo wp_kses_post(WaicUtils::getTaxonomyTermsList($objId, 'category')); ?></div>
		<?php } ?>
		<?php if (WaicUtils::getArrayValue($tools, 'post_card_name', $defValue, 1)) { ?>
			<div class="waic-card-name"><?php echo wp_kses_post($postObj->post_title); ?></div>
		<?php } ?>
		<div class="waic-card-footer">
			<?php if (WaicUtils::getArrayValue($tools, 'post_card_date', $defValue, 1)) { ?>
				<div class="waic-card-date"><?php echo wp_kses_post(WaicUtils::convertDateTimeToFront($postObj->post_date, true)); ?></div>
			<?php } ?>
		</div>
	</div>
</div>

