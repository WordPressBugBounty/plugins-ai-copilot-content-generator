<?php
if (! defined('ABSPATH')) {
	exit;
}
$props = $this->props;
$fields = $props['fields'];
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-group-title col-2">Menu Editor </div>
		<div class="wbw-settings-fields col-2 align-self-end">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Here, you can fully customize AI Magic Text Enhancer. Reorder features in the quick menu, rename them, edit prompts, or even add new custom features. Want to start fresh? Reset everything to default anytime!'); ?>">
			<div class="wbw-settings-field">
				<button id="waicAddButton" class="wbw-button wbw-button-small" type="button"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
			</div>
		</div>

		<div class="wbw-settings-fields col-8 align-self-end">
			<div class="wbw-settings-field d-flex justify-content-end align-items-center gap-2 wbw-pr-10">
				<?php
				echo esc_html(__('Enabled', 'ai-copilot-content-generator'));
				?>
				<span id="waic-enabled-cb">
				<?php
				WaicHtml::checkboxToggle('enabled', array(
					'checked' => $this->getModel()->isEnabled(),
				));
				?>
				</span>
			</div>
		</div>
	</div>

	<div class="wbw-settings-form row">
		<div class="wbw-sections-list col-12">
			<?php
			$i = 0;
			foreach ($fields as $key => $data) {
				if (!empty($data['hidden']) || 'custom' == $key || !empty($data['results'])) {
					continue;
				}

				if (trim($key)==='0') {
					continue;
				}
				?>
				<div class="wbw-section" data-field="<?php echo esc_attr($key); ?>" data-id="<?php echo esc_html($i); ?>">
					<div class="wbw-section-header">
						<div class="wbw-section-title"><?php echo esc_html($data['name']); ?></div>
						<div class="wbw-section-action">
							<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-down"></i></a>
							<a href="#" class="wbw-section-remove" data-field="<?php echo esc_html($key); ?>"><i class="fa fa-close"></i></a>
						</div>
					</div>
					<div class="wbw-section-options wbw-hidden">
						<?php
						if (0 === strpos($key, 'c_prompt_')) {
							include 'adminFieldOptionsCust.php';
						} else {
							include_once 'adminFieldOptions' . waicStrFirstUp($key) . '.php';
						}
						?>
					</div>
				</div>
				<?php
				$i++;
			}
			?>
		</div>
	</div>
</section>

<?php
$tooltip = !empty($options['modes_tooltip']) ? $options['modes_tooltip'] : __('Set a custom name for this feature. This is how it will appear in the quick menu.', 'ai-copilot-content-generator');
?>
