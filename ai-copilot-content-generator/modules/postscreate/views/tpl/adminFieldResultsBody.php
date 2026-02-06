<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$bodys = WaicUtils::getArrayValue($data, 'r', array(), 2);//$data['r'];
$isOne = count($bodys) <= 1;
if ($isOne) {
	$canRefresh = !empty($data['s']);
} else {
	$sections = WaicUtils::getArrayValue($results, 'sections', array(), 2);
	$refresh = array();
	foreach ($bodys as $n => $b) {
		if (!empty($b['s'])) {
			$refresh[$n] = $props['section_text'] . ' ' . $n;
		}
	}
	$canRefresh = !empty($refresh);
	//if ($canRefresh && 1 == $data['s']) {
	if ($canRefresh) {
		$refresh['all'] = __('All sections', 'ai-copilot-content-generator');
	}
}

?>
<div class="wbw-settings-form wbw-settings-top row waic-field-block" data-block="body">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10" id="waicResultsBodyWrapper">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr($canRefresh ? '' : ' waic-invisible'); ?>"></i>
		<div class="wbw-settings-wrap wbw-fullwidth">
		<?php if ($canRefresh && !empty($refresh)) { ?>
			<div class="wbw-settings-field">
			<?php
				WaicHtml::selectbox('section', array(
					'options' => $refresh,
					'attrs' => 'class="waic-refresh-data wbw-small-field"',
				));
			?>
			</div>
		<?php } ?>
			<div class="wbw-settings-field">
				<div class="waic-results-body">
				<?php foreach ($bodys as $n => $body) { ?>
					<div class="waic-editable" data-section="<?php echo esc_attr($n); ?>">
						<div class="waic-results-section-body waic-results-<?php echo esc_attr($body['s']); ?>">
							<?php echo wp_kses_post(empty($body['s']) ? $props['loading_text'] : ( 2 == $body['s'] ? WaicUtils::getArrayValue($body, 'm', $props['error_text']) : htmlspecialchars_decode(WaicUtils::getArrayValue($body, 'r', $props['loading_text']), ENT_QUOTES) )); ?>
						</div>
						<input type="hidden" name="body[<?php echo esc_attr($n); ?>]" class="waic-results-<?php echo esc_attr($body['s']); ?>" value="">
					</div>
				<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>