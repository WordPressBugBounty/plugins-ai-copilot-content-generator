<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$chatbots = array();
$taskId = $props['task_id'];
if (!empty($taskId)) {
	$chatbots[$taskId] = __('Current ChatBot', 'ai-copilot-content-generator');
}
$chatbots[''] = __('All', 'ai-copilot-content-generator');

foreach ($props['chatbots'] as $tId => $tTitle) {
	if ($tId != $taskId) {
		$chatbots[$tId] = $tTitle;
	}
}
?>
<section class="wbw-body-history">
	<div class="waic-table-filters mt-3" data-no-preview="1">
		<div class="waic-history-actions" id="waicHistoryActions">
		<?php 
			WaicHtml::selectbox('', array(
				'options' => $chatbots,
				'attrs' => 'id="waicHistoryChatbots" class="wbw-small-field wbw-nosave"',
			));
			?>
			<button class="wbw-button wbw-button-small" id="waicHistoryExport"><?php esc_html_e('Export', 'ai-copilot-content-generator'); ?></button>
		</div>
		<div class="waic-history-search">
		<?php 
			WaicHtml::text('', array(
				'attrs' => 'class="wbw-small-field wbw-nosave" id="waicHistorySearch" placeholder="' . __('Search logs', 'ai-copilot-content-generator') . '..."',
			));
			?>
		</div>
	</div>
	<div class="wbw-table-list">
		<table id="waicHistoryTable">
			<thead>
				<tr>
					<th><?php esc_html_e('Date', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('User', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('IP', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Tokens', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Duration', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Count', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Log', 'ai-copilot-content-generator'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div class="wbw-clear"></div>
	<div class="wbw-log-viewer wbw-hidden">
		<div class="waic-two-panels">
			<div class="waic-left-panel">
				<div class="waic-loader wbw-hidden"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>
				<div class="waic-panel-body">
					<div class="waic-log-list">
					</div>
				</div>
				<div class="waic-panel-footer dt-container">
					<div class="waic-table-pages">
					</div>
				</div>
			</div>
			<div class="waic-right-panel">
				<div class="waic-loader wbw-hidden"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>
				<div class="waic-panel-header">
					<div class="waic-panel-hidden wbw-hidden">
						<i class="fa fa-arrow-left"></i>
					</div>
					<div class="waic-log-user-name">
						<div class="waic-log-user"></div><div class="waic-log-ip"></div>
					</div>
					<div class="waic-log-tokens"></div>
				</div>
				<div class="waic-panel-body">
				</div>
			</div>
		</div>
	</div>
	<div id="waicExportDialog" class="wbw-hidden" title="<?php esc_attr_e('Export chat history', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-form row">
			<div class="wbw-settings-label col-3"><?php esc_html_e('Chatbot', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-9">
				<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('chat_id', array(
						'options' => $chatbots,
						'attrs' => 'id="waicExportTask" class="wbw-small-field wbw-nosave"',
					));
				?>
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row">
			<div class="wbw-settings-label col-3"><?php esc_html_e('Mode', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-9">
				<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('mode', array(
						'options' => array(0 => 'front', 1 => 'admin', 2 => 'custom', 9 => 'All'),
						'attrs' => 'class="wbw-small-field wbw-nosave"',
					));
				?>
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row">
			<div class="wbw-settings-label col-3"><?php esc_html_e('Users', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-9">
				<div class="wbw-settings-field">
				<?php 
					WaicHtml::selectbox('users', array(
						'options' => array(
							0 => __('All', 'ai-copilot-content-generator'),
							1 => __('logged in', 'ai-copilot-content-generator'), 
							2 => __('guests', 'ai-copilot-content-generator'),
						),
						'attrs' => 'class="wbw-small-field wbw-nosave"',
					));
				?>
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row">
			<div class="wbw-settings-label col-3"><?php esc_html_e('From', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-9">
				<div class="wbw-settings-field">
					<input type="date" value="" name="from" class="wbw-small-field wbw-nosave">
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row">
			<div class="wbw-settings-label col-3"><?php esc_html_e('To', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-9">
				<div class="wbw-settings-field">
					<input type="date" value="" name="to" class="wbw-small-field wbw-nosave">
				</div>
			</div>
		</div>
	</div>
</section>
