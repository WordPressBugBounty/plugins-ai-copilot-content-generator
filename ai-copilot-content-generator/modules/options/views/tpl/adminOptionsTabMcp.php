<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$options = WaicUtils::getArrayValue($props['options'], 'mcp', array(), 2);
$variations = WaicUtils::getArrayValue($props['variations'], 'mcp', array(), 2);
$defaults = WaicUtils::getArrayValue($props['defaults'], 'mcp', array(), 2);
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
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Url for connectors', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('', array(
					'value' => home_url() . '/wp-json/mcp/v1/sse',
					'attrs' => 'readonly id="waicMCPUrl" class="wbw-fullwidth-max"',
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
					<div class="wbw-instrs-info"><?php esc_html_e('Connect Claude through official MCP support in Claude.ai web interface', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">1</div><?php esc_html_e('Open Claude Settings', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Go to', 'ai-copilot-content-generator'); ?> <a href="https://claude.ai/" target="_blank">claude.ai</a> → <?php esc_html_e('Settings', 'ai-copilot-content-generator'); ?> → <?php esc_html_e('Connectors', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">2</div><?php esc_html_e('Add Custom Connector', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('Click "Add custom connector" and enter your MCP endpoint URL', 'ai-copilot-content-generator'); ?>:
					<?php 
						WaicHtml::text('', array(
						'value' => home_url() . '/wp-json/mcp/v1/sse?token=your-token',
						'attrs' => 'readonly',
						));
						?>
					</div>
				</div>
				<div class="wbw-instrs-block">
					<div class="wbw-instrs-title"><div class="wbw-instrs-icon">3</div><?php esc_html_e('Configure Permissions', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-instrs-info"><?php esc_html_e('In Claude.ai, find your connected MCP server → Tools and settings → select the tools you need', 'ai-copilot-content-generator'); ?></div>
				</div>
				<div class="wbw-alert-block">
					<div class="wbw-alert-title"><span>!</span> <?php esc_html_e('Requirements', 'ai-copilot-content-generator'); ?></div>
					<div class="wbw-alert-info"><?php esc_html_e('HTTPS certificate, public IP, disable caching for', 'ai-copilot-content-generator'); ?> /wp-json/mcp/v1/sse</div>
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
							WaicHtml::text('', array(
							'value' => home_url() . '/wp-json/mcp/v1/sse?token=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
							'attrs' => 'readonly',
							));
							?>
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