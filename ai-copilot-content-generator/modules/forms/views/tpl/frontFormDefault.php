<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$allowedTags = array(
	'div' => array('class' => true),
	'span' => array('class' => true),
	'b' => true,
	'i' => true,
	'a' => array('href' => true, 'title' => true),
);
?>
<div class="aiwu-form-wrapper">
	<div class="aiwu-form-header">
	<?php if (!empty($props['title'])) { ?>
		<div class="aiwu-form-title"><?php echo wp_kses(html_entity_decode($props['title']), $allowedTags); ?></div>
	<?php } ?>
	<?php if (!empty($props['description'])) { ?>
		<div class="aiwu-form-desc"><?php echo wp_kses(html_entity_decode($props['description']), $allowedTags); ?></div>
	<?php } ?>
	</div>
	<form id="aiwu-form-<?php echo esc_attr($props['view_id']); ?>">
		<div class="aiwu-form-fields">
		<?php foreach ($props['fields'] as $id => $data) { ?>
			<div class="aiwu-field-wrapper aiwu-field-<?php echo esc_attr($id); ?>" data-hide="<?php echo empty($data['hide']) ? '0' : '1'; ?>">
			<?php if (!empty($data['title'])) { ?>
				<div class="aiwu-field-title">
					<?php echo wp_kses(html_entity_decode($data['title']), $allowedTags); ?>
					<?php if (!empty($data['required'])) { ?>
						 *
					<?php } ?>
				</div>
			<?php } ?>
				<div class="aiwu-field-elem" data-required="<?php echo empty($data['required']) ? '0' : '1'; ?>">
					<?php $this->renderFormFieldHtml($id, $data); ?>
				</div>
			</div>
		<?php } ?> 
		</div>
		<div class="aiwu-form-buttons">
		<?php foreach ($props['resets'] as $id => $data) { ?>
			<?php $this->renderFormResetHtml($id, $data); ?>
		<?php } ?>
		<?php foreach ($props['submits'] as $id => $data) { ?>
			<?php $this->renderFormSubmitHtml($id, $data); ?>
		<?php } ?>
		</div>
	</form>
	<?php 
		WaicHtml::hidden('form_rules', array('value' => WaicUtils::jsonEncode($props['rules'])));
		foreach ($props['outputs'] as $id => $data) {
			//if (!empty($data['display']) && 'below' == $data['display']) {
				$this->renderFormOutputHtml($id, $data);
			//}
		} 
	?>
</div>
