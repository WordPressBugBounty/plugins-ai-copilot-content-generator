<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$curField = $this->props['cur_field'];
$isTemp = '#n#' === $curField;
$tooltip = $isTemp ? 'no-tooltip' : 'wbw-tooltip';
$fData = $isTemp ? array() : WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'rules', array(), 2), $curField, array(), 2);
$ifs = WaicUtils::getArrayValue($fData, 'ifs', array(), 2);
$thens = WaicUtils::getArrayValue($fData, 'thens', array(), 2);
$elses = WaicUtils::getArrayValue($fData, 'elses', array(), 2);

$fields = $this->props['fields'];
$outputs = $this->props['outputs'];
$allFields = array_merge($fields, $outputs);
$logics = array(
	'and' => __('AND', 'ai-copilot-content-generator'),
	'or' => __('OR', 'ai-copilot-content-generator'),
);
$operators = $this->props['if_operators'];
$actions = $this->props['then_actions'];
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Rule name', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Rule name - used solely for the convenience of form administration.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::text('rules[' . $curField . '][title]', array(
				'value' => WaicUtils::getArrayValue($fData, 'title'),
				'attrs' => 'class="waic-field-tlabel"',
			));
			?>
	</div>
</div>
<div class="wbw-settings-form row waic-add-block" data-type="if">
	<div class="wbw-settings-label col-2"><?php esc_html_e('IF', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Add and configure the logic conditions that will trigger specific actions in the form.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<button class="wbw-button wbw-button-small waic-add-rule-block" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
			<?php 
				WaicHtml::selectbox('rules[' . $curField . '][logic]', array(
					'options' => $logics,
					'attrs' => 'class="wbw-field-mini"',
					'value' => WaicUtils::getArrayValue($fData, 'logic'),
				));
				?>
		</div>
	</div>
</div>
<?php 
foreach ($ifs as $i => $data) { 
	if (empty($data)) {
		continue;
	}
	$operator = WaicUtils::getArrayValue($data, 'operator');
	$hidden = 'empty' == $operator || 'not_empty' == $operator ? ' wbw-hidden' : '';
?>
<div class="wbw-settings-form row waic-rule-block waic-rule-if" data-rule-num="<?php echo esc_attr($i); ?>">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Rule', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('rules[' . $curField . '][ifs][' . $i . '][target]', array(
					'options' => $allFields,
					'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					'value' => WaicUtils::getArrayValue($data, 'target'),
				));
				WaicHtml::selectbox('rules[' . $curField . '][ifs][' . $i . '][operator]', array(
					'options' => $operators,
					'attrs' => 'class="wbw-small-field waic-rule-operator"',
					'value' => $operator,
				));
				WaicHtml::text('rules[' . $curField . '][ifs][' . $i . '][value]', array(
					'attrs' => 'class="wbw-small-field waic-rule-value' . $hidden . '"',
					'value' => WaicUtils::getArrayValue($data, 'value'),
				));
			?>
			<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
		</div>
	</div>
</div>
<?php } ?>
<div class="wbw-settings-form row waic-add-block" data-type="then">
	<div class="wbw-settings-label col-2"><?php esc_html_e('THEN', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Specify what actions should happen when the condition is true, such as showing, hiding, enabling, disabling, or setting values for fields or outputs.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<button class="wbw-button wbw-button-small waic-add-rule-block" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>
<?php 
foreach ($thens as $i => $data) { 
	if (empty($data)) {
		continue;
	}
	$action = WaicUtils::getArrayValue($data, 'action');
	$hidden = 'value' == $action ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row waic-rule-block waic-rule-then" data-rule-num="<?php echo esc_attr($i); ?>">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('rules[' . $curField . '][thens][' . $i . '][target]', array(
					'options' => $allFields,
					'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					'value' => WaicUtils::getArrayValue($data, 'target'),
				));
				WaicHtml::selectbox('rules[' . $curField . '][thens][' . $i . '][action]', array(
					'options' => $actions,
					'attrs' => 'class="wbw-small-field waic-rule-operator"',
					'value' => $action,
				));
				WaicHtml::text('rules[' . $curField . '][thens][' . $i . '][value]', array(
					'attrs' => 'class="wbw-small-field waic-rule-value' . $hidden . '"',
					'value' => WaicUtils::getArrayValue($data, 'value'),
				));
			?>
			<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
		</div>
	</div>
</div>
<?php } ?>
<div class="wbw-settings-form row waic-add-block" data-type="else">
	<div class="wbw-settings-label col-2"><?php esc_html_e('ELSE', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="<?php echo esc_attr($tooltip); ?>" title="<?php esc_html_e('Specify what actions should happen when the condition is true, such as showing, hiding, enabling, disabling, or setting values for fields or outputs.', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-field">
			<button class="wbw-button wbw-button-small waic-add-rule-block" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
</div>
<?php 
foreach ($elses as $i => $data) { 
	if (empty($data)) {
		continue;
	}
	$action = WaicUtils::getArrayValue($data, 'action');
	$hidden = 'value' == $action ? '' : ' wbw-hidden';
?>
<div class="wbw-settings-form row waic-rule-block waic-rule-else" data-rule-num="<?php echo esc_attr($i); ?>">
	<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
		<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('rules[' . $curField . '][elses][' . $i . '][target]', array(
					'options' => $allFields,
					'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					'value' => WaicUtils::getArrayValue($data, 'target'),
				));
				WaicHtml::selectbox('rules[' . $curField . '][elses][' . $i . '][action]', array(
					'options' => $actions,
					'attrs' => 'class="wbw-small-field waic-rule-operator"',
					'value' => $action,
				));
				WaicHtml::text('rules[' . $curField . '][elses][' . $i . '][value]', array(
					'attrs' => 'class="wbw-small-field waic-rule-value' . $hidden . '"',
					'value' => WaicUtils::getArrayValue($data, 'value'),
				));
			?>
			<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
		</div>
	</div>
</div>
<?php } ?>

