<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
//$module = $this->getModule();
$tmpUrl = $props['tmp_url'];
?>
<section class="wbw-body-workspace">
	<ul class="wbw-ws-group">
		<li class="wbw-ws-block wbw-ws-block-big wbw-ws-block-create">
			<a href="<?php echo esc_url($props['new_url']); ?>" class="wbw-feature-link">
				<div class="wbw-ws-block-in">
					<img src="<?php echo esc_url(WAIC_IMG_PATH . '/create.svg'); ?>" alt="?">
					<div class="wbw-ws-block-text">
						<div class="wbw-ws-title"><?php esc_html_e('Create Blank Workflow', 'ai-copilot-content-generator'); ?></div>
						<div class="wbw-ws-desc"><?php esc_html_e('Start from stretch and build your custom automation', 'ai-copilot-content-generator'); ?></div>
					</div>
				</div>
			</a>
		</li>
	</ul>
	<div class="waic-tab-header waic-row-coltrols waic-wide">
		<div class="waic-row-coltrol wbw-group-title">
			<?php esc_html_e('Or start from template:', 'ai-copilot-content-generator'); ?>
		</div>
		<div class="waic-row-coltrol">
			<input type="search" id="waicSearchTemplate" placeholder="<?php echo esc_attr__('Search template', 'ai-copilot-content-generator'); ?>">
			<button class="wbw-button wbw-button-small" id="waicImportTemplate"><?php esc_html_e('Import', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<ul class="wbw-ws-group" id="waicTemplatesList">
	<?php foreach ($props['templates'] as $key => $block) { ?>
		<li class="wbw-ws-block<?php echo empty($block['class']) ? '' : ' ' . esc_attr($block['class']); ?>">
			<a href="<?php echo $tmpUrl . '&task_id=' . esc_attr($key); ?>" class="wbw-feature-link">
				<div class="wbw-ws-block-in">
					<div class="wbw-ws-block-text">
						<div class="wbw-ws-title"><?php echo esc_html($block['title']); ?></div>
						<div class="wbw-ws-desc"><?php echo esc_html($block['desc']); ?></div>
					</div>
				</div>
			</a>
			<?php echo (empty($block['mode']) ? '<a href="#" class="waic-delete-template" data-id="' . esc_attr($key) . '"><i class="fa fa-close"></i></a>' : ''); ?>
		</li>
	<?php 
		} 
		$cntTmps = count($props['templates']);
		$needPlh = $cntTmps % 3;
		if ($cntTmps % 2 != 0) {
			echo '<li class="wbw-ws-block wbw-ws-plh"></li>';
			if (1 == $needPlh) {
				echo '<li class="wbw-ws-block wbw-ws-plh"></li>';
			}
		} else {
			if (0 < $needPlh) {
				echo '<li class="wbw-ws-block wbw-ws-plh"></li>';
			}
			if (1 == $needPlh) {
				echo '<li class="wbw-ws-block wbw-ws-plh"></li>';
			}
		}
	?>
	</ul>
	<div class="wbw-clear"></div>
</section>
