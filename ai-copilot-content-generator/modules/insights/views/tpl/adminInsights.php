<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$filters = empty($props['filters']) ? array() : $props['filters'];
$usageOptions = empty($props['usage_options']) ? array() : $props['usage_options'];
$chatbots = empty($props['chatbots']) ? array() : $props['chatbots'];
$isPro = !empty($props['is_pro']);
$proUrl = empty($props['pro_url']) ? '' : $props['pro_url'];
$features = empty($usageOptions['features']) ? array() : $usageOptions['features'];
$engines = empty($usageOptions['engines']) ? array() : $usageOptions['engines'];
$models = empty($usageOptions['models']) ? array() : $usageOptions['models'];
$groupByOptions = empty($usageOptions['group_by']) ? array('feature') : $usageOptions['group_by'];
$pricingStatus = empty($props['pricing_status']) ? array() : $props['pricing_status'];
$pricingCurrencies = empty($pricingStatus['currencies']) ? array() : $pricingStatus['currencies'];
$pricingSource = WaicUtils::getArrayValue($pricingStatus, 'source', 'bundled');
$pricingSourceMode = WaicUtils::getArrayValue($pricingStatus, 'source_mode', $pricingSource);
$pricingCustomUrl = WaicUtils::getArrayValue($pricingStatus, 'custom_url', '');
?>
<div class="aiwu-insights" data-testid="waic-insights-page">
	<div class="wbw-info-block aiwu-insights-notice" data-testid="waic-insights-privacy-notice">
		<strong><?php esc_html_e('Privacy-first reporting.', 'ai-copilot-content-generator'); ?></strong>
		<?php esc_html_e('Prompts, upstream failure details, uploaded content, credentials, network addresses and free-form message text are not shown in Phase 1 usage tables.', 'ai-copilot-content-generator'); ?>
	</div>

	<section class="wbw-body-workspace aiwu-pricing-settings" data-testid="waic-pricing-settings">
		<div class="aiwu-pricing-status">
			<span><?php esc_html_e('Pricing mode', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-source-mode"><?php echo esc_html($pricingSourceMode); ?></strong></span>
			<span><?php esc_html_e('Active snapshot', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-source"><?php echo esc_html($pricingSource); ?></strong></span>
			<span><?php esc_html_e('Snapshot', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-version"><?php echo esc_html((int) WaicUtils::getArrayValue($pricingStatus, 'version', 0, 1)); ?></strong></span>
			<span><?php esc_html_e('Generated', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-generated"><?php echo esc_html(WaicUtils::getArrayValue($pricingStatus, 'generated_at', '')); ?></strong></span>
			<span><?php esc_html_e('Last sync', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-last-sync"><?php echo esc_html(WaicUtils::getArrayValue($pricingStatus, 'last_sync_at', esc_html__('Never', 'ai-copilot-content-generator'))); ?></strong></span>
			<span><?php esc_html_e('Status', 'ai-copilot-content-generator'); ?>: <strong data-testid="waic-pricing-last-status"><?php echo esc_html(WaicUtils::getArrayValue($pricingStatus, 'last_sync_message', '')); ?></strong></span>
		</div>
		<form class="aiwu-pricing-form" action="" method="post">
			<div class="aiwu-pricing-source-field" role="radiogroup" aria-label="<?php esc_attr_e('Pricing source', 'ai-copilot-content-generator'); ?>">
				<label>
					<input type="radio" name="pricing_source_mode" value="bundled" data-testid="waic-pricing-source-bundled" <?php checked($pricingSourceMode, 'bundled'); ?>>
					<span><?php esc_html_e('Bundled', 'ai-copilot-content-generator'); ?></span>
				</label>
				<label>
					<input type="radio" name="pricing_source_mode" value="imported" data-testid="waic-pricing-source-imported" <?php checked($pricingSourceMode, 'imported'); ?>>
					<span><?php esc_html_e('Imported JSON', 'ai-copilot-content-generator'); ?></span>
				</label>
				<label>
					<input type="radio" name="pricing_source_mode" value="custom_url" data-testid="waic-pricing-source-custom-url" <?php checked($pricingSourceMode, 'custom_url'); ?>>
					<span><?php esc_html_e('Custom URL', 'ai-copilot-content-generator'); ?></span>
				</label>
			</div>
			<label>
				<span><?php esc_html_e('Display currency', 'ai-copilot-content-generator'); ?></span>
				<select name="display_currency" data-testid="waic-pricing-display-currency">
					<?php foreach ($pricingCurrencies as $code => $currency) { ?>
						<option value="<?php echo esc_attr($code); ?>" <?php selected(WaicUtils::getArrayValue($pricingStatus, 'display_currency', 'USD'), $code); ?>><?php echo esc_html(WaicUtils::getArrayValue($currency, 'label', $code)); ?></option>
					<?php } ?>
				</select>
			</label>
			<div class="aiwu-pricing-url-panel" data-aiwu-pricing-panel="custom_url">
				<label>
					<span><?php esc_html_e('HTTPS URL', 'ai-copilot-content-generator'); ?></span>
					<input type="url" name="pricing_custom_url" value="<?php echo esc_attr($pricingCustomUrl); ?>" placeholder="https://example.com/pricing.json" data-testid="waic-pricing-custom-url">
				</label>
				<label class="aiwu-pricing-inline-check">
					<input type="checkbox" name="pricing_auto_sync_enabled" value="1" data-testid="waic-pricing-auto-sync-enabled" <?php checked((int) WaicUtils::getArrayValue($pricingStatus, 'auto_sync_enabled', 0, 1), 1); ?>>
					<span><?php esc_html_e('Auto-sync daily', 'ai-copilot-content-generator'); ?></span>
				</label>
			</div>
			<button type="submit" class="wbw-button" data-testid="waic-pricing-save"><?php esc_html_e('Save', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="wbw-button wbw-button-main" data-testid="waic-pricing-manual-sync"><?php esc_html_e('Sync now', 'ai-copilot-content-generator'); ?></button>
			<button type="button" class="wbw-button" data-testid="waic-pricing-reset"><?php esc_html_e('Reset to bundled', 'ai-copilot-content-generator'); ?></button>
		</form>
		<form class="aiwu-pricing-import-form" action="" method="post" enctype="multipart/form-data" data-aiwu-pricing-panel="imported">
			<label>
				<span><?php esc_html_e('Snapshot JSON', 'ai-copilot-content-generator'); ?></span>
				<textarea name="pricing_snapshot_json" rows="5" data-testid="waic-pricing-import-json"></textarea>
			</label>
			<label>
				<span><?php esc_html_e('Snapshot file', 'ai-copilot-content-generator'); ?></span>
				<input type="file" name="pricing_snapshot_file" accept="application/json,.json" data-testid="waic-pricing-import-file">
			</label>
			<button type="submit" class="wbw-button wbw-button-main" data-testid="waic-pricing-import"><?php esc_html_e('Import snapshot', 'ai-copilot-content-generator'); ?></button>
		</form>
	</section>

	<section class="wbw-body-workspace aiwu-insights-shell">
		<div class="wbw-menu-tabs">
			<div class="wbw-grbtn" role="tablist" aria-label="<?php esc_attr_e('Insights sections', 'ai-copilot-content-generator'); ?>">
				<button type="button" class="wbw-button current" data-testid="waic-insights-subtab-overview" data-aiwu-insights-tab="overview" role="tab" aria-selected="true"><?php esc_html_e('Overview', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="problems" role="tab" aria-selected="false"><?php esc_html_e('Problem Questions', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="outcomes" role="tab" aria-selected="false"><?php esc_html_e('Outcomes', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-testid="waic-insights-subtab-usage" data-aiwu-insights-tab="usage" role="tab" aria-selected="false"><?php esc_html_e('Usage & Cost', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="kb-attribution" role="tab" aria-selected="false"><?php esc_html_e('KB Attribution', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="commerce-gaps" role="tab" aria-selected="false"><?php esc_html_e('WooCommerce Gaps', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="mcp-audit" role="tab" aria-selected="false"><?php esc_html_e('MCP Audit', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="actions" role="tab" aria-selected="false"><?php esc_html_e('Actions', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-button" data-aiwu-insights-tab="conversations" role="tab" aria-selected="false"><?php esc_html_e('Conversations', 'ai-copilot-content-generator'); ?><?php if (!$isPro) { ?> <i class="fa fa-lock" aria-hidden="true"></i><?php } ?></button>
				<button type="button" class="wbw-leer"></button>
			</div>
		</div>

		<div class="aiwu-insights-loading hidden" data-testid="waic-insights-loading" aria-live="polite">
			<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>
			<span><?php esc_html_e('Loading Insights data...', 'ai-copilot-content-generator'); ?></span>
		</div>
		<div class="aiwu-insights-state aiwu-insights-empty hidden" data-testid="waic-insights-empty-state"><?php esc_html_e('No usage data found for this range.', 'ai-copilot-content-generator'); ?></div>
		<div class="aiwu-insights-state aiwu-insights-error hidden" data-testid="waic-insights-error-state"><?php esc_html_e('Could not load Insights data.', 'ai-copilot-content-generator'); ?></div>

		<div class="aiwu-insights-panels">
			<section class="aiwu-insights-panel" data-aiwu-insights-panel="overview">
				<form class="aiwu-insights-filters aiwu-insights-overview-filters" action="" method="post">
					<label>
						<span><?php esc_html_e('Period', 'ai-copilot-content-generator'); ?></span>
						<select name="period" data-testid="waic-insights-period">
							<option value="7d"><?php esc_html_e('Last 7 days', 'ai-copilot-content-generator'); ?></option>
							<option value="30d"><?php esc_html_e('Last 30 days', 'ai-copilot-content-generator'); ?></option>
							<option value="90d" <?php selected($isPro); ?>><?php esc_html_e('Last 90 days', 'ai-copilot-content-generator'); ?></option>
						</select>
					</label>
					<input type="hidden" name="date_from" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_from')); ?>">
					<input type="hidden" name="date_to" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_to')); ?>">
					<input type="hidden" name="mode" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'mode', '0')); ?>">
				</form>

				<div class="aiwu-insights-kpis">
					<div class="aiwu-insights-kpi" data-testid="waic-insights-kpi-cost"><span><?php esc_html_e('Cost', 'ai-copilot-content-generator'); ?></span><strong>$0.00</strong></div>
					<div class="aiwu-insights-kpi" data-testid="waic-insights-kpi-conversations"><span><?php esc_html_e('AI events', 'ai-copilot-content-generator'); ?></span><strong>0</strong></div>
					<div class="aiwu-insights-kpi" data-testid="waic-insights-kpi-cost-per-conv"><span><?php esc_html_e('Cost per event', 'ai-copilot-content-generator'); ?></span><strong>$0.0000</strong></div>
					<div class="aiwu-insights-kpi" data-testid="waic-insights-kpi-low-conf"><span><?php esc_html_e('Low confidence', 'ai-copilot-content-generator'); ?></span><strong>0</strong></div>
				</div>

				<section class="aiwu-insights-chart-wrap">
					<h3><?php esc_html_e('Daily cost', 'ai-copilot-content-generator'); ?></h3>
					<canvas data-testid="waic-insights-daily-cost-trend" height="120"></canvas>
				</section>

				<section class="aiwu-insights-breakdowns" data-testid="waic-insights-feature-breakdown">
					<h3><?php esc_html_e('Where your money goes', 'ai-copilot-content-generator'); ?></h3>
					<table class="dataTable"><thead><tr><th><?php esc_html_e('Feature', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Tokens', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Cost', 'ai-copilot-content-generator'); ?></th></tr></thead><tbody></tbody></table>
				</section>

				<section class="aiwu-insights-attention">
					<h3><?php esc_html_e('Attention', 'ai-copilot-content-generator'); ?></h3>
					<ul data-testid="waic-insights-attention-list"></ul>
				</section>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="problems">
				<h3><?php esc_html_e('Problem Questions', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-problem-clusters">
					<thead><tr><th><?php esc_html_e('Cluster', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Problem', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('State', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="outcomes">
				<h3><?php esc_html_e('Outcomes', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-outcomes">
					<thead><tr><th><?php esc_html_e('Outcome', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Sessions', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Wasted cost', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="usage">
				<form class="aiwu-insights-filters aiwu-usage-filters" action="" method="post">
					<input type="hidden" name="date_from" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_from')); ?>">
					<input type="hidden" name="date_to" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_to')); ?>">
					<input type="hidden" name="mode" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'mode', '0')); ?>">
					<label>
						<span><?php esc_html_e('Feature', 'ai-copilot-content-generator'); ?></span>
						<select name="feature" data-testid="waic-usage-filter-feature">
							<option value="all"><?php esc_html_e('All features', 'ai-copilot-content-generator'); ?></option>
							<?php foreach ($features as $feature) { ?>
								<option value="<?php echo esc_attr($feature); ?>"><?php echo esc_html($feature); ?></option>
							<?php } ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Engine', 'ai-copilot-content-generator'); ?></span>
						<select name="engine" data-testid="waic-usage-filter-engine">
							<option value="all"><?php esc_html_e('All engines', 'ai-copilot-content-generator'); ?></option>
							<?php foreach ($engines as $engine) { ?>
								<option value="<?php echo esc_attr($engine); ?>"><?php echo esc_html($engine); ?></option>
							<?php } ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Model', 'ai-copilot-content-generator'); ?></span>
						<select name="model" data-testid="waic-usage-filter-model" <?php disabled(!$isPro); ?>>
							<option value="all"><?php esc_html_e('All models', 'ai-copilot-content-generator'); ?></option>
							<?php foreach ($models as $model) { ?>
								<option value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
							<?php } ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Group by', 'ai-copilot-content-generator'); ?></span>
						<select name="group_by" data-testid="waic-usage-filter-group-by">
							<?php foreach ($groupByOptions as $groupBy) { ?>
								<option value="<?php echo esc_attr($groupBy); ?>"><?php echo esc_html(ucfirst($groupBy)); ?></option>
							<?php } ?>
						</select>
					</label>
					<button type="submit" class="wbw-button wbw-button-main"><?php esc_html_e('Apply', 'ai-copilot-content-generator'); ?></button>
					<?php if (!$isPro && !empty($proUrl)) { ?>
						<a href="<?php echo esc_url($proUrl); ?>" class="wbw-button wbw-button-pro" data-testid="waic-insights-pro-lock"><?php esc_html_e('PRO', 'ai-copilot-content-generator'); ?></a>
					<?php } ?>
				</form>

				<section class="aiwu-insights-chart-wrap">
					<canvas data-testid="waic-usage-chart" height="160"></canvas>
				</section>

				<section class="aiwu-usage-table-wrap">
					<table class="dataTable" data-testid="waic-usage-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Group', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Sessions', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Input', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Output', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Tokens', 'ai-copilot-content-generator'); ?></th>
								<th><?php esc_html_e('Cost', 'ai-copilot-content-generator'); ?></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</section>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="kb-attribution">
				<h3><?php esc_html_e('KB Attribution', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-kb-attribution">
					<thead><tr><th><?php esc_html_e('Object', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Retrieved', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Used', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Health', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="commerce-gaps">
				<h3><?php esc_html_e('WooCommerce Gaps', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-commerce-gaps">
					<thead><tr><th><?php esc_html_e('Problem', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Feature', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Cost', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="mcp-audit">
				<h3><?php esc_html_e('MCP Audit', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-mcp-audit">
					<thead><tr><th><?php esc_html_e('Day', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Tool', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Risk', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Calls', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="actions">
				<h3><?php esc_html_e('Actions', 'ai-copilot-content-generator'); ?></h3>
				<table class="dataTable" data-testid="waic-insights-actions">
					<thead><tr><th><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Priority', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('State', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Events', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
			</section>

			<section class="aiwu-insights-panel hidden" data-aiwu-insights-panel="conversations">
				<form class="aiwu-insights-filters aiwu-conversation-filters" action="" method="post">
					<label>
						<span><?php esc_html_e('Chatbot', 'ai-copilot-content-generator'); ?></span>
						<select name="task_id">
							<option value="0"><?php esc_html_e('All chatbots', 'ai-copilot-content-generator'); ?></option>
							<?php foreach ($chatbots as $taskId => $title) { ?>
								<option value="<?php echo esc_attr($taskId); ?>"><?php echo esc_html($title); ?></option>
							<?php } ?>
						</select>
					</label>
					<input type="hidden" name="date_from" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_from')); ?>">
					<input type="hidden" name="date_to" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'date_to')); ?>">
					<input type="hidden" name="mode" value="<?php echo esc_attr(WaicUtils::getArrayValue($filters, 'mode', '0')); ?>">
				</form>
				<table class="dataTable" data-testid="aiwu-insights-conversations-table">
					<thead><tr><th><?php esc_html_e('Date', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Chatbot', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Status', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Snippet', 'ai-copilot-content-generator'); ?></th><th><?php esc_html_e('Action', 'ai-copilot-content-generator'); ?></th></tr></thead>
					<tbody></tbody>
				</table>
				<div class="waic-table-pages aiwu-insights-pagination" data-testid="aiwu-insights-pagination"></div>
			</section>
		</div>
	</section>

	<div class="aiwu-insights-modal hidden" data-testid="aiwu-insights-conversation-modal" role="dialog" aria-modal="true" aria-hidden="true">
		<div class="aiwu-insights-modal-panel" data-testid="waic-usage-session-modal">
			<div class="aiwu-insights-modal-head">
				<div>
					<strong class="aiwu-insights-modal-title"><?php esc_html_e('Conversation', 'ai-copilot-content-generator'); ?></strong>
					<span class="aiwu-pii-badge"><?php esc_html_e('Masked', 'ai-copilot-content-generator'); ?></span>
				</div>
				<button type="button" class="aiwu-insights-modal-close" data-testid="aiwu-insights-modal-close" aria-label="<?php esc_attr_e('Close', 'ai-copilot-content-generator'); ?>">&times;</button>
			</div>
			<div class="aiwu-insights-modal-meta"></div>
			<div class="aiwu-insights-modal-messages"></div>
		</div>
	</div>
</div>
