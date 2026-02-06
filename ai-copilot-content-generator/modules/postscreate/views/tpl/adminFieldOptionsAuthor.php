<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$fAuthor = WaicUtils::getArrayValue(WaicUtils::getArrayValue($props['settings'], 'fields', array(), 2), 'author', array(), 2);
$users = WaicFrame::_()->getModule('workspace')->getUsersList();
?>
<div class="wbw-settings-form row">
	<div class="wbw-settings-label col-2"><?php esc_html_e('Select user', 'ai-copilot-content-generator'); ?></div>
	<div class="wbw-settings-fields col-10">
		<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose an author for the article from the list of existing users. This assigns the selected user as the author of the generated article.', 'ai-copilot-content-generator'); ?>">
		<?php 
			WaicHtml::selectbox('fields[author][id]', array(
				'options' => $users,
				'value' => WaicUtils::getArrayValue($fAuthor, 'id', get_current_user_id()),
			));
			?>
	</div>
</div>
