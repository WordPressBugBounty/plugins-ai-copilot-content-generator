<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$pcData = $props['pc_data'];
?>
<div class="wbw-settings-form row waic-field-block" data-block="date">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button waic-invisible"></i>
			<?php 
			if (empty($pcData['post_id'])) {
				if (!empty($pcData['pub_mode'])) {
					$dt = WaicUtils::getArrayValue($data, 'r');
					WaicHtml::text('date', array(
						'value' => empty($dt) ? '' : WaicUtils::convertDateTimeToFront($dt),
						'attrs' => 'class="wbw-field-datetime waic-results-' . $data['s'] . '"',
					));
				}
				WaicHtml::button(array(
					'value' => __('Publish', 'ai-copilot-content-generator'),
					'attrs' => 'class="wbw-button wbw-button-small waic-publish-button' . ( empty($pcData['pub_mode']) ? ' m-0' : '' ) . '" disabled data-action="publish"',
				));
			} else {
				$postObj = get_post($pcData['post_id']);
				if ($postObj) {
					?>
				<a href="<?php echo esc_url(get_post_permalink($pcData['post_id'])); ?>" target="_blank" class="waic-post-link"><?php echo esc_html($postObj->post_date); ?></a>
			<?php } ?>
		<?php } ?>
	</div>
</div>