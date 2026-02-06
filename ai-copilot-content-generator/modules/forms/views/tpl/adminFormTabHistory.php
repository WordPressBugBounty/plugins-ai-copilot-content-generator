<section class="wbw-body-history">
	<div class="wbw-table-list mt-3">
		<table id="waicHistoryTable">
			<thead>
				<tr>
					<th><?php esc_html_e('ID', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Date', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('User', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('IP', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Tokens', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Request', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('Response', 'ai-copilot-content-generator'); ?></th>
					<th><?php esc_html_e('View', 'ai-copilot-content-generator'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div class="wbw-clear"></div>
	<div id="waicLogDialog" class="wbw-hidden" title="<?php esc_attr_e('Request Log', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('Request', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogRequest" class="waic-log-messages">43534535</div>
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('Response', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogResponse" class="waic-log-messages">43534535</div>
				</div>
			</div>
		</div>
	</div>
</section>