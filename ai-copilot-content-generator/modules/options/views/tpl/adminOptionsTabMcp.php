<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$props = $this->props;
$options = WaicUtils::getArrayValue($props['options'], 'mcp', array(), 2);
$variations = WaicUtils::getArrayValue($props['variations'], 'mcp', array(), 2);
$defaults = WaicUtils::getArrayValue($props['defaults'], 'mcp', array(), 2);

// Shared MCP URL variables — declared once, used across all tabs (Url for connectors / Claude / ChatGPT).
// Declared at the top so no tab has an implicit dependency on another tab's rendering order.
$mcpToken    = WaicUtils::getArrayValue($options, 'mcp_token', '');
$mcpHasToken = !empty($mcpToken);
$mcpBaseUrl  = home_url() . '/wp-json/mcp/v1/sse';
?>
<section class="wbw-body-options-api">
	<div class="wbw-group-title">
		<?php esc_html_e('AI MCP Integration', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Enable MCP', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enabling this option creates a Model Context Protocol (MCP) server that provides various tools.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('mcp[e_mcp]', array(
					'checked' => WaicUtils::getArrayValue($options, 'e_mcp'),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Enable MCP logging', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Turn on logging to capture detailed logs of MCP server activity for troubleshooting and analysis. Note that general logging in the Generation settings block should also be enabled.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::checkbox('mcp[mcp_logging]', array(
					'checked' => WaicUtils::getArrayValue($options, 'mcp_logging'),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Access Token', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('MCP will be usable by using this Access Token. If not set, you will need to build your own authentication by using the aiwu_allow_mcp filter.', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('mcp[mcp_token]', array(
					'value' => WaicUtils::getArrayValue($options, 'mcp_token', ''),
					'attrs' => 'aria-hidden="true" autocomplete="off" class="waic-fake-password" id="waicMCPToken" placeholder="' . __('32-character security token', 'ai-copilot-content-generator') . '"',
				));
				?>
			</div>
			<button id="waicGenarateMCPToken" class="wbw-button wbw-button-small"><?php esc_html_e('Generate', 'ai-copilot-content-generator'); ?></button>
			<button id="waicViewMCPToken" class="wbw-button wbw-button-small m-0"><?php esc_html_e('View', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Url for connectors', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Copy this URL and paste it into your AI connector settings. It already includes your Access Token. For Claude.ai with OAuth 2.1 enabled, the token acts as a fallback if OAuth discovery fails.', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field">
			<?php
				$tokenSuffix = $mcpHasToken ? '?token=' . $mcpToken : '?token=GENERATE_TOKEN_ABOVE';
				$mainClasses = 'waic-mcp-url-field wbw-fullwidth-max' . ($mcpHasToken ? ' waic-fake-password' : '');
				WaicHtml::text('', array(
					'value' => $mcpBaseUrl . $tokenSuffix,
					'attrs' => 'readonly id="waicMCPUrl" class="' . esc_attr($mainClasses) . '" data-mcp-base-url="' . esc_attr($mcpBaseUrl) . '"',
				));
				?>
			</div>
			<button type="button" id="waicCopyMCPUrl" class="wbw-button wbw-button-small waic-copy-btn" data-target="#waicMCPUrl"><?php esc_html_e('Copy', 'ai-copilot-content-generator'); ?></button>
			<?php if ($mcpHasToken) : ?>
			<button type="button" id="waicViewMCPUrl" class="wbw-button wbw-button-small m-0 waic-view-btn" data-target="#waicMCPUrl"><?php esc_html_e('View', 'ai-copilot-content-generator'); ?></button>
			<?php endif; ?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Enable OAuth 2.1', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Enable OAuth 2.1 authentication for MCP connectors. Required for Claude.ai integration. When enabled, the plugin acts as both an OAuth Authorization Server and Resource Server, providing /.well-known discovery endpoints, PKCE authorization flow, and Dynamic Client Registration.', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field">
			<?php
				WaicHtml::checkbox('mcp[mcp_oauth]', array(
					'checked' => WaicUtils::getArrayValue($options, 'mcp_oauth', 0, 1),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Connection Instructions', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form" id="waicMCPInstructions">
		<div class="wbw-submenu-tabs">
			<div class="wbw-grbtn">
				<button type="button" data-content="#content-subtab-claude" class="wbw-button current"><?php esc_html_e('Claude', 'ai-copilot-content-generator'); ?></button>
				<button type="button" data-content="#content-subtab-chatgpt" class="wbw-button"><?php esc_html_e('ChatGPT', 'ai-copilot-content-generator'); ?></button>
				<button type="button" data-content="#content-subtab-trouble" class="wbw-button"><?php esc_html_e('Troubleshooting', 'ai-copilot-content-generator'); ?></button>
				<button type="button" class="wbw-leer"></button>
			</div>
		</div>
		<div class="wbw-subtabs-content">
			<div class="wbw-subtab-content" id="content-subtab-claude">
				<div class="wbw-instrs-block wbw-info-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon square">i</div><?php esc_html_e('Claude MCP Integration', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Connect Claude through official MCP support in Claude.ai web interface. OAuth 2.1 method is recommended for Claude.ai.', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">1</div><?php esc_html_e('Enable OAuth 2.1 (Recommended)', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('In plugin settings above, check "Enable MCP", generate a token, and check "Enable OAuth 2.1". Save settings.', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">2</div><?php esc_html_e('Open Claude Settings', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Go to', 'ai-copilot-content-generator'); ?> <a href="https://claude.ai/" target="_blank">claude.ai</a> → <?php esc_html_e('Settings', 'ai-copilot-content-generator'); ?> → <?php esc_html_e('Connectors', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">3</div><?php esc_html_e('Add Custom Connector', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Click "Add custom connector" and paste this URL:', 'ai-copilot-content-generator'); ?>
					<?php
						$claudeConnectorUrl = $mcpHasToken ? $mcpBaseUrl . '?token=' . $mcpToken : $mcpBaseUrl;
						$claudeClasses = 'waic-mcp-url-field wbw-fullwidth-max' . ($mcpHasToken ? ' waic-fake-password' : '');
						WaicHtml::text('', array(
						'value' => $claudeConnectorUrl,
						'attrs' => 'readonly id="waicClaudeMCPUrl" class="' . esc_attr($claudeClasses) . '" data-mcp-base-url="' . esc_attr($mcpBaseUrl) . '"',
						));
						?>
					<button type="button" id="waicCopyClaudeMCPUrl" class="wbw-button wbw-button-small waic-copy-btn" data-target="#waicClaudeMCPUrl"><?php esc_html_e('Copy', 'ai-copilot-content-generator'); ?></button>
					<?php if ($mcpHasToken) : ?>
					<button type="button" id="waicViewClaudeMCPUrl" class="wbw-button wbw-button-small m-0 waic-view-btn" data-target="#waicClaudeMCPUrl"><?php esc_html_e('View', 'ai-copilot-content-generator'); ?></button>
					<br><small class="waic-secret-warning">⚠ <?php esc_html_e('This URL contains your Access Token. Do not share it publicly or in screenshots.', 'ai-copilot-content-generator'); ?></small>
					<?php endif; ?>
					<br><small><?php esc_html_e('With OAuth 2.1 enabled, Claude discovers OAuth endpoints automatically and shows an authorization page. The token in the URL acts as a fallback if /.well-known/ endpoints are blocked by the server (e.g. by nginx or a security plugin).', 'ai-copilot-content-generator'); ?></small>
					</div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">4</div><?php esc_html_e('Authorize & Configure', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Click "Authorize" on the consent page. Then in Claude.ai, find your connected MCP server → Tools and settings → select the tools you need.', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-alert-block">
					<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Requirements', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-alert-info"><?php esc_html_e('HTTPS certificate, public IP, disable caching for', 'ai-copilot-content-generator'); ?> /wp-json/mcp/v1/* <?php esc_html_e('and', 'ai-copilot-content-generator'); ?> /.well-known/*</div>
				</div>
			</div>
			<div class="wbw-subtab-content" id="content-subtab-chatgpt">
				<div class="wbw-instrs-block wbw-info-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon square">i</div><?php esc_html_e('ChatGPT MCP Integration', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Connect through developer mode in ChatGPT settings with OAuth authentication', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">1</div><?php esc_html_e('Enable Developer Mode', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Go to ChatGPT → Settings → Connectors → Advanced Settings → Enable Developer Mode', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">2</div><?php esc_html_e('Generate Token in Plugin', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Your plugin: Settings → MCP → Click "Enable MCP" → Generate token', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">3</div><?php esc_html_e('Create Custom Connector', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('ChatGPT → Settings → Connectors → Create New Connector', 'ai-copilot-content-generator'); ?>
					<ul>
						<li>URL:
						<?php
							$chatgptConnectorUrl = $mcpBaseUrl . '?token=' . ($mcpHasToken ? $mcpToken : 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
							$chatgptClasses = 'waic-mcp-url-field wbw-fullwidth-max' . ($mcpHasToken ? ' waic-fake-password' : '');
							WaicHtml::text('', array(
							'value' => $chatgptConnectorUrl,
							'attrs' => 'readonly id="waicChatgptMCPUrl" class="' . esc_attr($chatgptClasses) . '" data-mcp-base-url="' . esc_attr($mcpBaseUrl) . '"',
							));
							?>
						<button type="button" id="waicCopyChatgptMCPUrl" class="wbw-button wbw-button-small waic-copy-btn" data-target="#waicChatgptMCPUrl"><?php esc_html_e('Copy', 'ai-copilot-content-generator'); ?></button>
						<?php if ($mcpHasToken) : ?>
						<button type="button" id="waicViewChatgptMCPUrl" class="wbw-button wbw-button-small m-0 waic-view-btn" data-target="#waicChatgptMCPUrl"><?php esc_html_e('View', 'ai-copilot-content-generator'); ?></button>
						<?php endif; ?>
						</li>
						<li>Authentication: "No Authentication"</li>
						<li>Click "Create"</li>
					</ul>
					</div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">4</div><?php esc_html_e('Use Connector', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('In ChatGPT chat → Click "+" → Select "Developer Mode" → Choose your connector → Tell ChatGPT to use the connector features', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-alert-block">
					<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Important', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-alert-info"><?php esc_html_e('ChatGPT Pro version required. Developer mode is in beta. Token passed via URL parameters, not headers.', 'ai-copilot-content-generator'); ?></div>
				</div>
			</div>
			<div class="wbw-subtab-content" id="content-subtab-trouble">
				<div class="wbw-instrs-block wbw-trouble-block">
					<div class="wbw-instrs-title"><?php esc_html_e('Connection Issues', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info">
						<ul>
							<li><?php esc_html_e('Verify that SSL certificate is valid', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Ensure site is accessible from the internet (not localhost)', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Disable caching for /wp-json/mcp/v1/sse in Cloudflare/NGINX', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Check if MCP is enabled in AIWU settings', 'ai-copilot-content-generator'); ?></li>
						</ul>
					</div>
				</div>
				<div class="wbw-instrs-block wbw-trouble-block">
					<div class="wbw-instrs-title"><?php esc_html_e('Permission Errors', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info">
						<ul>
							<li><?php esc_html_e('Check WordPress user permissions', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Ensure MCP tools are enabled in AI', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Verify Access Token is correct', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Check logs in /wp-content/plugins/aiwu/logs/', 'ai-copilot-content-generator'); ?></li>
						</ul>
					</div>
				</div>
				<div class="wbw-instrs-block wbw-trouble-block">
					<div class="wbw-instrs-title"><?php esc_html_e('Performance Issues', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info">
						<ul>
							<li><?php esc_html_e('Monitor server resources during MCP operations', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Check MCP logs for errors', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Consider rate limiting for bulk operations', 'ai-copilot-content-generator'); ?></li>
							<li><?php esc_html_e('Use staging environment for testing', 'ai-copilot-content-generator'); ?></li>
						</ul>
					</div>
				</div>
				<div class="wbw-instrs-block wbw-info-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon square">i</div><?php esc_html_e('Connection Test', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Use the mcp_ping function to test your connection', 'ai-copilot-content-generator'); ?></div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php 
// phpcs:enable
