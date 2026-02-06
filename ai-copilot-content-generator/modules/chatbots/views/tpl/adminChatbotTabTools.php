<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<section class="wbw-body-workspace">
	<ul class="wbw-ws-group waic-tools-groups" id="waicToolsList">
	<?php foreach ($props['tool_groups'] as $key => $block) { ?>
		<li class="wbw-ws-block <?php echo ( empty($block['class']) ? '' : esc_attr($block['class']) ); ?>" data-tool="content-tool-<?php echo esc_attr($key); ?>">
			<div class="wbw-ws-block-in">
				<div class="wbw-ws-block-text">
					<div class="wbw-ws-title"><?php echo esc_html($block['title']); ?></div>
					<div class="wbw-ws-desc"><?php echo esc_html($block['desc']); ?></div>
				</div>
			</div>
			<?php if (!empty($block['soon'])) { ?>
				<div class="wbw-ws-soon"><?php esc_html_e('Coming soon', 'ai-copilot-content-generator'); ?></div>
			<?php } ?>
		</li>
	<?php } ?>
	</ul>
	<div class="waic-tools-content">
		<?php foreach ($props['tool_groups'] as $key => $block) { ?>
			<div class="waic-tool-content<?php echo ( strpos($block['class'], 'current') ? ' active' : '' ); ?>" data-tool="content-tool-<?php echo esc_attr($key); ?>">
				<?php include_once 'adminChatbotTabTools' . waicStrFirstUp($key) . '.php'; ?>
				<div class="wbw-clear"></div>
			</div>
			<?php 
		} 
		//WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['lang']), 'attrs' => 'id="waicLangSettingsJson" class="wbw-nosave"'));
		?>
	</div>
</section>
