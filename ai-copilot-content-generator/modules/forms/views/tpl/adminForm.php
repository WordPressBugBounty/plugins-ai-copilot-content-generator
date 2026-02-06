<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<section class="wbw-body-options">
	<?php include_once $props['tpl_path'] . 'adminTaskCreateTabs.php'; ?>
	<div class="waic-body-content">
		<div class="wbw-tabs-content">
			<form id="waicFormCreateForm">
			<?php foreach ($props['tabs'] as $key => $data) { ?>
				<div class="wbw-tab-content" id="content-tab-<?php echo esc_attr($key); ?>">
					<?php include_once 'adminFormTab' . waicStrFirstUp($key) . '.php'; ?>
					<div class="wbw-clear"></div>
				</div>
			<?php } ?>
			<div class="wbw-settings-form row" id="waicMainButtons">
				<div class="col-12">
					<button class="wbw-button wbw-button-form wbw-button-main" id="waicSaveTask"><?php esc_html_e('Save', 'ai-copilot-content-generator'); ?></button>
					<button class="wbw-button wbw-button-form wbw-button-minor wbw-button-back"><?php esc_html_e('Back', 'ai-copilot-content-generator'); ?></button>
				</div>
			</div>
			<?php 
				WaicHtml::hidden('task_title', array('value' => $props['task_title'], 'attrs' => 'id="waicTaskTitle"'));
			?>
			</form>
			<?php 
				WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['lang']), 'attrs' => 'id="waicLangSettingsJson" class="wbw-nosave"'));
				WaicHtml::hidden('task_id', array('value' => $props['task_id'], 'attrs' => 'id="waicPCId"'));
			?>
		</div>
	</div>
</section>
<div class="wbw-nosave wbw-template">
<?php 
	$this->props['cur_field'] = '#n#';
	foreach ($fieldsList as $key => $label) { 
	?>
	<div class="wbw-section" data-section-key="<?php echo esc_attr($key); ?>" data-section-label="FIELD">
		<div class="wbw-section-header">
			<div class="wbw-section-title"><?php echo esc_html($label); ?></div>
			<div class="wbw-section-label"></div>
			<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
			<div class="wbw-section-tlabel"></div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
				<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-hidden">
			<?php include 'adminFieldOptions' . waicStrFirstUp($key) . '.php'; ?>
		</div>
	</div>
<?php } ?>
	<div class="wbw-section" data-section-key="output" data-section-label="OUTPUT">
		<div class="wbw-section-header">
			<div class="wbw-section-title"><?php echo esc_html_e('Output', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-section-label"></div>
			<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
			<div class="wbw-section-tlabel"></div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
				<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-hidden">
			<?php include 'adminOutputOptions.php'; ?>
		</div>
	</div>
	<div class="wbw-section" data-section-key="submit" data-section-label="SUBMIT">
		<div class="wbw-section-header">
			<div class="wbw-section-title"><?php echo esc_html_e('Submit', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-section-label"></div>
			<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
			<div class="wbw-section-tlabel"></div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
				<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-hidden">
			<?php include 'adminSubmitOptions.php'; ?>
		</div>
	</div>
	<div class="wbw-section" data-section-key="reset" data-section-label="RESET">
		<div class="wbw-section-header">
			<div class="wbw-section-title"><?php echo esc_html_e('Reset', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-section-label"></div>
			<div class="wbw-section-copy wbw-action-icon"><i class="fa fa-copy"></i></div>
			<div class="wbw-section-tlabel"></div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
				<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-hidden">
			<?php include 'adminResetOptions.php'; ?>
		</div>
	</div>
	<div class="wbw-section" data-section-key="rule" data-section-label="RULE">
		<div class="wbw-section-header">
			<div class="wbw-section-title"><?php echo esc_html_e('Rule', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-section-tlabel"></div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
				<a href="#" class="wbw-section-remove"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-hidden">
			<?php include 'adminRuleOptions.php'; ?>
		</div>
	</div>
	<div class="wbw-settings-form row waic-rule-block waic-rule-if">
		<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Rule', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('rules[' . $curField . '][ifs][#m#][target]', array(
						'options' => array(),
						'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					));
					WaicHtml::selectbox('rules[' . $curField . '][ifs][#m#][operator]', array(
						'options' => $props['if_operators'],
						'attrs' => 'class="wbw-small-field waic-rule-operator"',
					));
					WaicHtml::text('rules[' . $curField . '][ifs][#m#][value]', array(
						'attrs' => 'class="wbw-small-field waic-rule-value"',
					));
				?>
				<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row waic-rule-block waic-rule-then">
		<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('rules[' . $curField . '][thens][#m#][target]', array(
						'options' => array(),
						'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					));
					WaicHtml::selectbox('rules[' . $curField . '][thens][#m#][action]', array(
						'options' => $props['then_actions'],
						'attrs' => 'class="wbw-small-field waic-rule-operator"',
					));
					WaicHtml::text('rules[' . $curField . '][thens][#m#][value]', array(
						'attrs' => 'class="wbw-small-field waic-rule-value wbw-hidden"',
					));
				?>
				<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row waic-rule-block waic-rule-else">
		<div class="wbw-settings-label col-2 wbw-label-sub"><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('rules[' . $curField . '][elses][#m#][target]', array(
						'options' => array(),
						'attrs' => 'class="wbw-small-field waic-dynamic-list" data-label="FIELD OUTPUT"',
					));
					WaicHtml::selectbox('rules[' . $curField . '][elses][#m#][action]', array(
						'options' => $props['then_actions'],
						'attrs' => 'class="wbw-small-field waic-rule-operator"',
					));
					WaicHtml::text('rules[' . $curField . '][elses][#m#][value]', array(
						'attrs' => 'class="wbw-small-field waic-rule-value wbw-hidden"',
					));
				?>
				<i class="fa fa-close wbw-action-icon wbw-rule-delete"></i>
			</div>
		</div>
	</div>
</div>