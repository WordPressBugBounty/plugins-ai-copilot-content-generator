<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$tools = $props['tools'];
$prodObj = $props['obj'];
$objId = $prodObj->get_id();
?>
<div class="waic-chatbot-card waic-card-prod waic-card-hor" data-obj-id="<?php echo esc_attr($objId); ?>" data-href="<?php echo esc_url($prodObj->get_permalink()); ?>">
	<?php if (WaicUtils::getArrayValue($tools, 'prod_card_image', 0, 1)) { ?>
		<div class="waic-card-image"><?php echo wp_get_attachment_image(get_post_thumbnail_id($objId)); ?></div>
	<?php } ?>
	<div class="waic-card-body">
		<?php if (WaicUtils::getArrayValue($tools, 'prod_card_name', 0, 1)) { ?>
			<div class="waic-card-name"><?php echo wp_kses_post($prodObj->get_title()); ?></div>
		<?php } ?>
		<?php if (WaicUtils::getArrayValue($tools, 'prod_card_price', 0, 1)) { ?>
			<div class="waic-card-price"><?php echo $prodObj->get_price_html(); ?></div>
		<?php } ?>
		<?php if (WaicUtils::getArrayValue($tools, 'prod_card_cart', 0, 1)) { ?>
			<div class="waic-card-cart"><?php echo do_shortcode('[add_to_cart id="' . $objId . '" class="" style="" show_price="false"]'); ?></div>
		<?php } ?>
	</div>
</div>

