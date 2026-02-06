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
			<form id="waicChatbotCreateForm">
			<?php foreach ($props['tabs'] as $key => $data) { ?>
				<div class="wbw-tab-content" id="content-tab-<?php echo esc_attr($key); ?>">
					<?php include_once 'adminChatbotTab' . waicStrFirstUp($key) . '.php'; ?>
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
		<div class="wbw-preview-content">
			<div class="waic-preview-actions">
				<div class="waic-grbtn" data-name="preview">
					<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
					<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
				</div>
				<button class="wbw-button wbw-button-small waic-reset-preview">
					<i class="fa fa-refresh"></i>
				</button>
			</div>
			<div id="waicChatbotPreviewBlock"></div>
		</div>
	</div>
</section>
