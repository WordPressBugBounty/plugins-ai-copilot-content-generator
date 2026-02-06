<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$users = WaicFrame::_()->getModule('workspace')->getUsersList();
?>
<div class="wbw-settings-form row waic-field-block" data-block="author">
	<div class="wbw-settings-label col-2"><?php echo esc_html($fields[$field]['label']); ?></div>
	<div class="wbw-settings-fields col-10 waic-editable">
		<i class="fa fa-refresh wbw-refresh-button<?php echo esc_attr(empty($data['g']) || empty($data['s']) ? ' waic-invisible' : ''); ?>"></i>
		<div class="wbw-settings-field">
		<?php 
			WaicHtml::selectbox('author', array(
				'options' => $users,
				'value' => WaicUtils::getArrayValue($data, 'r', get_current_user_id()),
			));
			?>
		</div>
	</div>
</div>