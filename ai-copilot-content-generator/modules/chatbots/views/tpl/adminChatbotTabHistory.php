<section class="wbw-body-history">
	<div class="wbw-table-list mt-3">
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
	<div id="waicLogDialog" class="wbw-hidden" title="<?php esc_attr_e('Conversation Log', 'ai-copilot-content-generator'); ?>">
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('Date', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogDate"></div>
					(<div id="waicLogMode"></div>)
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('User', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogUser"></div>
					(<div id="waicLogIP"></div>)
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('Tokens', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogTokens"></div>
					<div id="waicLogCost"></div>
				</div>
			</div>
		</div>
		<div class="wbw-settings-form row wbw-settings-top">
			<div class="wbw-settings-label col-2"><?php esc_html_e('Log', 'ai-copilot-content-generator'); ?></div>
			<div class="wbw-settings-fields col-10">
				<div class="wbw-settings-field">
					<div id="waicLogMessages" class="waic-log-messages">43534535</div>
				</div>
			</div>
		</div>
	</div>
</section>