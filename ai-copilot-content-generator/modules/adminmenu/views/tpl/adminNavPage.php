<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<div class="wbw-wrap">
	<div class="wbw-plugin wbw-main">
		<section class="wbw-content">
			<div class="wbw-header wbw-sticky">
				<div class="wbw-head">
					<div class="wbw-logo"><a href="https://aiwuplugin.com/" target="_blank"><img src="<?php echo esc_url( WAIC_IMG_PATH . '/logo_aiwu.png'); ?>" alt="WBW" height="44"></a>
						<div class="wbw-version">v<?php echo esc_html( WAIC_VERSION); ?></div>
					</div>
					<a href="#" class="wbw-head-btn">
						<i class="fa fa-bars"></i>
					</a>
					<nav class="wbw-navigation">
						<ul>
							<?php foreach ($props['tabs'] as $tabKey => $t) { ?>
								<?php 
								if (isset($t['hidden']) && $t['hidden']) {
									continue;
								}
								?>
								<li class="wbw-tab-nav <?php echo ( $props['activeTab'] == $tabKey ? 'active' : '' ); ?>">
									<a href="<?php echo esc_url($t['url']); ?>"><?php echo esc_html($t['label']); ?></a>
								</li>
							<?php } ?>
							
						</ul>
					</nav>
					<div class="wbw-info-right">
						<?php if (!WaicFrame::_()->getModule('promo')->isEndGuide()) { ?>
							<img class="waic-start-guide" src="<?php echo esc_url( WAIC_IMG_PATH . '/guide.png'); ?>" alt="Quick guide">
						<?php } ?>
						<a href="https://aiwuplugin.com/knowledge-base/" target="_blank">
							<img class="wbw-docs" src="<?php echo esc_url( WAIC_IMG_PATH . '/q.png'); ?>" alt="?">
						</a>
					</div>
					<?php if (!$props['is_pro']) { ?>
						<a href="https://aiwuplugin.com/#pricing" target="_blank" class="wbw-button wbw-button-pro"><?php echo esc_html__('Get a PRO', 'ai-copilot-content-generator'); ?></a>
					<?php } ?>
				</div>
				<?php if (!empty($props['bread'])) { ?>
				<nav class="wbw-bread-crumbs">
					<ul>
						<li><?php echo esc_html($props['tabs'][$props['activeTab']]['label']); ?></li>
						<?php if ($props['lastBread']) { ?>
							<li class="wbw-bread-separator">|</li>
							<li class="wbw-bread-last">
								<?php if ($props['lastBreadId']) { ?>
								<div id="<?php echo esc_attr($props['lastBreadId']); ?>">
									<?php 
								} 
								echo esc_html($props['lastBread']); 
								if ($props['lastBreadId']) { 
									?>
								</div>
								<?php } ?>
							</li>
						<?php } ?>
					</ul>
				</nav>
				<?php } ?>
			</div>
			<div class="wbw-container">
				<?php WaicHtml::echoEscapedHtml($props['content']); ?>
				<div class="wbw-clear"></div>
			</div>
			<div class="wbw-footer">
				<span class="wbw-rate-link">🌟 <a href="https://wordpress.org/support/plugin/ai-copilot-content-generator/reviews/#new-post" target="_blank"><?php esc_html_e('Rate Our Plugin!', 'ai-copilot-content-generator'); ?></a></span>
				<span class="wbw-footer-text"><?php esc_html_e('Your valuable feedback helps us improve and better serve you. Thanks for your support!', 'ai-copilot-content-generator'); ?></span>
			</div>
		</section>
	</div>
	<div class="wbw-plugin-loader">
		<div class="waic-loader">
			<div class="waic-loader-bar bar1"></div>
			<div class="waic-loader-bar bar2"></div>
		</div>
	</div>
</div>
<?php WaicHtml::echoEscapedHtml($props['guide']); ?>