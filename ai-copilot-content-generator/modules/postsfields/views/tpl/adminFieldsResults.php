<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$props = $this->props;
$task = $props['task'];
$tStatus = WaicUtils::getArrayValue($task, 'status', 9, 1);
$workspace = WaicFrame::_()->getModule('workspace');
$completed = round(WaicUtils::getArrayValue($task, 'step', 0, 1) * 100 / WaicUtils::getArrayValue($task, 'steps', 1, 1), 2);
//running_task
?>
<div class="waic-status-block waic-status-<?php echo esc_attr($tStatus); ?>" data-status="<?php echo esc_attr($tStatus); ?>"><?php echo esc_html($props['statuses'][$tStatus]); ?></div>
<div class="waic-complited-block">
	<span class="waic-complited-points"><?php echo esc_html($completed); ?></span>% <?php esc_html_e('completed', 'ai-copilot-content-generator'); ?>
</div>
<div class="waic-progressbar-block">
	<div class="waic-progressbar">
		<span></span>
	</div>
	<button class="wbw-button wbw-button-small" id="waicTaskStopStart"><?php esc_html_e('Stop', 'ai-copilot-content-generator'); ?></button>
</div>
<section class="">
	<div class="waic-task-results" data-bulk="1">
		<div class="waic-table-results wbw-table-list">
			<table id="waicPostsResultsBulk" data-mod="postsfields" data-action="getPostsResultsBulk" data-feature="postsfields">
				<thead>
					<tr>
						<th><input type="checkbox" class="waicCheckAll"></th>
						<th><?php esc_html_e('Article', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Results', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Status', 'ai-copilot-content-generator'); ?></th>
						<th><?php esc_html_e('Post', 'ai-copilot-content-generator'); ?></th>
					</tr>
				</thead>
			</table>
		</div>
		<div id="waicPostResultsDialog" class="wbw-hidden" title="<?php esc_attr_e('Article preview', 'ai-copilot-content-generator'); ?>">
			<div class="waic-results-popup">
				<div class="waic-post-results">
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-buttons-form row">
		<div class="col-12">
			<button class="wbw-button wbw-button-form wbw-button-main waic-group-button" disabled id="waicTaskPublish" data-action="publish">
				<?php esc_html_e('Publish', 'ai-copilot-content-generator'); ?>
			</button>
			<button class="wbw-button wbw-button-form wbw-button-minor waic-group-button" disabled id="waicTaskDelete" data-action="delete">
				<?php esc_html_e('Delete', 'ai-copilot-content-generator'); ?>
			</button>
			<button class="wbw-button wbw-button-form wbw-button-minor wbw-button-back" disabled id="waicTaskCansel" data-action="cancel">
				<?php esc_html_e('Back', 'ai-copilot-content-generator'); ?>
			</button>
		</div>
	</div>
	<?php 
		WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['lang']), 'attrs' => 'id="waicLangSettingsJson" class="wbw-nosave"'));
		WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['statuses']), 'attrs' => 'id="waicTaskStatusesJson" class="wbw-nosave"'));
		WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['actions']), 'attrs' => 'id="waicTaskActionsJson" class="wbw-nosave"'));
		WaicHtml::hidden('task_id', array('value' => $props['task_id'], 'attrs' => 'id="waicTaskId"'));
	?>
	<div id="waicBodyResultsEditor" class="wbw-hidden" title="<?php esc_attr_e('Edit body', 'ai-copilot-content-generator'); ?>">
		<textarea id="waicBodySectionEditor" class="waic-field-html"></textarea>
	</div>
</section>
