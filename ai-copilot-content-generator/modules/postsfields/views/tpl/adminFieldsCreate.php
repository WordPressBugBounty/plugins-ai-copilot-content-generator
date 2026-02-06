<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<section class="wbw-body-options">
	<?php include_once $props['tpl_path'] . 'adminPostsCreateTabs.php'; ?>
	<div class="wbw-tabs-content">
		<form id="waicPostsCreateForm">
		<?php foreach ($props['tabs'] as $key => $data) { ?>
			<div class="wbw-tab-content" id="content-tab-<?php echo esc_attr($key); ?>">
				<?php include_once 'adminFieldsCreateTab' . waicStrFirstUp($key) . '.php'; ?>
				<div class="wbw-clear"></div>
			</div>
		<?php } ?>
		<?php if (!$props['read_only']) { ?>
		<div class="wbw-settings-form row">
			<div class="col-12">
				<button class="wbw-button wbw-button-form wbw-button-main" id="waicStartGeneration" data-mod="postsfields" data-typ="start" data-save="<?php esc_html_e('Start generation', 'ai-copilot-content-generator'); ?>">
					<?php esc_html_e('Start generation', 'ai-copilot-content-generator'); ?>
				</button>
				<button class="wbw-button wbw-button-form wbw-button-minor wbw-button-back"><?php esc_html_e('Back', 'ai-copilot-content-generator'); ?></button>
			</div>
		</div>
			<?php 
			WaicHtml::hidden('task_title', array('value' => $props['task_title'], 'attrs' => 'id="waicTaskTitle"'));
		} 
		?>
		</form>
		<?php 
			WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['lang']), 'attrs' => 'id="waicLangSettingsJson" class="wbw-nosave"'));
			WaicHtml::hidden('task_id', array('value' => $props['task_id'], 'attrs' => 'id="waicPCId"'));
		?>
	</div>
</section>
