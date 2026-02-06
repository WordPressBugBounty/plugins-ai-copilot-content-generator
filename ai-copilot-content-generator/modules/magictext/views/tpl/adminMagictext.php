<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>

<section class="wbw-body-options">
	<?php include_once 'adminMagictextTabs.php'; ?>
	<div class="wbw-tabs-content">

		<form id="waicMagictextForm">
			<?php foreach ($props['tabs'] as $key => $data) { ?>
				<div class="wbw-tab-content" id="content-tab-<?php echo esc_attr($key); ?>">
					<?php include_once 'adminMagictextTab' . waicStrFirstUp($key) . '.php'; ?>
					<div class="wbw-clear"></div>
				</div>
			<?php } ?>
			<?php if (!$props['read_only']) { ?>
				<div class="wbw-settings-form row">
					<div class="col-12">
						<button class="wbw-button wbw-button-form wbw-button-main" id="waicSaveMagictext" data-mod="magictext">
							<?php esc_html_e('Save', 'ai-copilot-content-generator'); ?>
						</button>
						<button class="wbw-button wbw-button-form wbw-button-minor wbw-button-back" id="waicBackButton"><?php esc_html_e('Back', 'ai-copilot-content-generator'); ?></button>
						<button class="wbw-button wbw-button-form wbw-button-leer wbw-button-restore" id="waicRestore"><?php esc_html_e('Restore by default', 'ai-copilot-content-generator'); ?></button>
					</div>
				</div>
				<?php
			}
			?>
		</form>
		<?php
		WaicHtml::hidden('', array('value' => WaicUtils::jsonEncode($props['lang']), 'attrs' => 'id="waicLangSettingsJson" class="wbw-nosave"'));
		WaicHtml::hidden('task_id', array('value' => $props['task_id'], 'attrs' => 'id="waicMTId"'));
		?>
	</div>
</section>
<div id="waic-mt-new-item-template" class="wbw-hidden">
	<div class="wbw-section" data-field="{data_field}" data-required="1">
		<div class="wbw-section-header">
			<div class="wbw-section-title">{title}</div>
			<div class="wbw-section-action">
				<a href="#" class="wbw-section-toggle"><i class="fa fa-chevron-up"></i></a>
				<a href="#" class="wbw-section-remove" data-field="{data_field}"><i class="fa fa-close"></i></a>
			</div>
		</div>
		<div class="wbw-section-options wbw-options-block">
			<div class="wbw-settings-form row">
				<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-10">
					<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="{wbw_tooltip}" title="<?php echo esc_html($tooltip); ?>">
					<input type="text" name="fields[{data_field}][name]" value="{title}" placeholder="<?php echo esc_attr__('Enter Name', 'ai-copilot-content-generator'); ?>">
				</div>
			</div>
			<div class="wbw-settings-form row wbw-settings-top" data-parent-select="fields[{data_field}][text]" data-select-value="gen_by_topic gen_by_sections">
				<div class="wbw-settings-label col-2"><?php esc_html_e('Fix prompt', 'ai-copilot-content-generator'); ?></div>
				<div class="wbw-settings-fields col-10">
					<img src="<?php echo esc_url(WAIC_IMG_PATH . 'info.png'); ?>" class="{wbw_tooltip}" title="<?php esc_html_e('Provide any extra context or specific instructions specifically for generating the article title. This prompt will help refine the title to better match your specific requirements or preferences.', 'ai-copilot-content-generator'); ?>">
					<textarea name="fields[{data_field}][text]" rows="4" placeholder="<?php echo esc_attr__('Enter your data.', 'ai-copilot-content-generator'); ?>"></textarea>
				</div>
			</div>
		</div>
	</div>
</div>
