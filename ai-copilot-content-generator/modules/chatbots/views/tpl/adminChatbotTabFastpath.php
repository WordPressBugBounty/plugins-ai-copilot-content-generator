<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$props = $this->props;
$fast = $props['fast_config'];
$status = $props['fast_status'];
$taskId = absint($props['task_id']);
$canRebuild = $taskId && !empty($fast['enabled']);
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Local fast path', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_attr_e('Use the local published-content index before calling an AI provider. Existing chatbots stay off until enabled.', 'ai-copilot-content-generator'); ?>">
			<?php WaicHtml::checkbox('fast_path[enabled]', array('checked' => !empty($fast['enabled']))); ?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Domain pack', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::selectbox('fast_path[domain_pack]', array(
				'options' => $props['fast_domain_packs'],
				'value' => $fast['domain_pack'],
				'attrs' => 'class="wbw-small-field"',
			)); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Indexed post types', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::selectlist('fast_path[post_types]', array(
				'options' => $props['fast_post_types'],
				'value' => $fast['post_types'],
				'attrs' => 'class="wbw-small-field"',
			)); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Taxonomy allowlist', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::text('fast_path[taxonomies]', array('value' => implode(',', (array) $fast['taxonomies']))); ?>
			<label class="wbw-settings-after"><?php esc_html_e('comma-separated', 'ai-copilot-content-generator'); ?></label>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Meta allowlist', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::text('fast_path[meta_fields]', array('value' => implode(',', (array) $fast['meta_fields']))); ?>
			<label class="wbw-settings-after"><?php esc_html_e('supports * wildcard; protected meta is always excluded', 'ai-copilot-content-generator'); ?></label>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Card taxonomy', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::text('fast_path[card_taxonomy]', array('value' => $fast['card_taxonomy'], 'attrs' => 'class="wbw-small-field"')); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Max cards', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::number('fast_path[max_cards]', array('value' => $fast['max_cards'], 'attrs' => 'min="1" max="8" class="wbw-small-field"')); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Confidence threshold', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::text('fast_path[confidence_threshold]', array('value' => $fast['confidence_threshold'], 'attrs' => 'class="wbw-small-field"')); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Fallback on weak match', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<input type="hidden" name="fast_path[fallback_on_low_confidence]" value="0">
			<?php WaicHtml::checkbox('fast_path[fallback_on_low_confidence]', array('checked' => !empty($fast['fallback_on_low_confidence']))); ?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Local requests per minute', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10"><div class="wbw-settings-field">
			<?php WaicHtml::number('fast_path[requests_per_minute]', array('value' => $fast['requests_per_minute'], 'attrs' => 'min="5" max="120" class="wbw-small-field"')); ?>
		</div></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-separator col-12"></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Index status', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<p>
				<?php
				echo esc_html(sprintf(
					/* translators: 1: state, 2: indexed row count */
					__('State: %1$s; indexed rows: %2$d', 'ai-copilot-content-generator'),
					isset($status['state']) ? $status['state'] : 'not built',
					isset($status['count']) ? absint($status['count']) : 0
				));
				?>
			</p>
			<?php if (!empty($status['finished_at'])) { ?>
				<p><?php echo esc_html(sprintf(__('Last rebuild: %s UTC', 'ai-copilot-content-generator'), $status['finished_at'])); ?></p>
			<?php } ?>
			<?php if ($canRebuild) { ?>
				<button type="submit" form="waicFastRebuildForm" class="wbw-button wbw-button-small"><?php esc_html_e('Queue index rebuild', 'ai-copilot-content-generator'); ?></button>
			<?php } else { ?>
				<p><?php esc_html_e('Enable fast path and save the chatbot before building its index.', 'ai-copilot-content-generator'); ?></p>
			<?php } ?>
		</div>
	</div>
</section>
<?php // phpcs:enable ?>
