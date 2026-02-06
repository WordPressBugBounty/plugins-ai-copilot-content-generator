<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['fields'], 'date', array(), 2);
$fDate = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'date', array(), 2);
$now = WaicUtils::getFormatedDateTime(WaicUtils::getTimestamp());
$to = WaicUtils::addDays(2);
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select how and when the article should be published. If publishing is selected, the article will be published immediately after generation without the opportunity to make intermediate edits.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('fields[date][mode]', array(
				'options' => $options['modes'],
				'value' => WaicUtils::getArrayValue($fDate, 'mode'),
			));
			?>
	</div>
</div>
<?php 
$hidden = WaicUtils::getArrayValue($fDate, 'mode') == 'date' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[date][mode]" data-select-value="date">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Select date', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose the publication date for your article. The article will be scheduled for this date immediately after generation.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('fields[date][date]', array(
				'value' => WaicUtils::getArrayValue($fDate, 'date', $now),
				'attrs' => 'class="wbw-field-datetime"',
			));
			?>
	</div>
</div>
<?php 
$hidden = WaicUtils::getArrayValue($fDate, 'mode') == 'random' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[date][mode]" data-select-value="random">
	<div class="wbw-settings-label col-2"><?php esc_html_e('From', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Specify the start and end dates for the random publication range.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('fields[date][from]', array(
				'value' => WaicUtils::getArrayValue($fDate, 'from', $now),
				'attrs' => 'class="wbw-field-datetime"',
			));
			?>
		<div class="wbw-settings-label">-</div>
		<?php 
			WaicHtml::text('fields[date][to]', array(
				'value' => WaicUtils::getArrayValue($fDate, 'to', $to),
				'attrs' => 'class="wbw-field-datetime"',
			));
			?>
	</div>
</div>
<?php 
$hidden = WaicUtils::getArrayValue($fDate, 'mode') == 'period' ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-select="fields[date][mode]" data-select-value="period">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Period', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the interval for publishing each post, starting from the chosen date. Useful for bulk publications.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::number('fields[date][cnt]', array(
				'value' => WaicUtils::getArrayValue($fDate, 'cnt', 12),
				'attrs' => 'class="wbw-field-mini" min="1"',
			));
			?>
		<?php 
			WaicHtml::selectbox('fields[date][unit]', array(
				'options' => $options['units'],
				'value' => WaicUtils::getArrayValue($fDate, 'unit', 'h'),
				'attrs' => 'class="wbw-field-mini"',
			));
			?>
		<div class="wbw-settings-label"><?php esc_html_e('from', 'ai-copilot-content-generator'); ?></div>
		<?php 
			WaicHtml::text('fields[date][period]', array(
				'value' => WaicUtils::getArrayValue($fDate, 'period', $now),
				'attrs' => 'class="wbw-field-datetime"',
			));
			?>
	</div>
</div>
