<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$appearance = WaicUtils::getArrayValue($props['settings'], 'appearance', array(), 2);
$presets = $props['presets'];
$preset = WaicUtils::getArrayValue($appearance, 'preset', 'default', 0, array_keys($presets));
$borderStyles = array();
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Preset', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose a preconfigured design template for your chatbot`s appearance. Customize further if needed.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('appearance[preset]', array(
					'options' => $presets,
					'value' => $preset,
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
			<button type="button" data-value="mobile" class="wbw-button wbw-button-small waic-reset-appearance"><?php esc_html_e('Reset', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Mobile Breakpoint', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set breakpoint for all options that depend on a mobile/desktop view.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('appearance[breakpoint]', array(
					'value' => WaicUtils::getArrayValue($appearance, 'breakpoint', 500, 1),
					'min' => '100',
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$desktop = WaicUtils::getArrayValue($appearance, 'desktop', array(), 2);
$mobile = WaicUtils::getArrayValue($appearance, 'mobile', array(), 2);
$options = array(
	0 => __('No', 'ai-copilot-content-generator'),
	1 => __('Yes', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-group-title">
		<?php esc_html_e('Chatbot Display Settings', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="display">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Autostart', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Automatically opens the chatbot if the user has not opened it manually within the specified number of seconds.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="display" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][e_autostart]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'e_autostart', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[desktop][autostart]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'autostart', 5, 1),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="display" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][e_autostart]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'e_autostart', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[mobile][autostart]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'autostart', 5, 1),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'float' => __('Floating button', 'ai-copilot-content-generator'),
	'fixed' => __('Fixed button', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Placement', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Choose how the chat button is displayed on the page. Floating Button – the button stays fixed on the screen and remains visible while scrolling. Fixed Button – the button is placed within a specific section of the page and moves along with the content.', 'ai-copilot-content-generator') . '<br><br>' . __('For Floating mode choose where the chat button will appear on the screen. Also you can specify exact offsets from the bottom and right/left edges of the screen.', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field" data-group="display" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][placement]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'placement'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="display" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][placement]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'placement'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
		</div>
	</div>
<?php 
$positions = array(
	'br' => __('Bottom right', 'ai-copilot-content-generator'),
	'bl' => __('Bottom left', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Positioning', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_attr(__('Choose where the chat button or chat panel will appear on the screen. Also you can specify exact offsets from the bottom and right/left edges of the screen.', 'ai-copilot-content-generator') . '<br><br>' . __('For Floating mode choose where the chat button will appear on the screen. Also you can specify exact offsets from the bottom and right/left edges of the screen.', 'ai-copilot-content-generator')); ?>">
			<div class="wbw-settings-field" data-group="display" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($desktop, 'position', 'br'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[desktop][position_bottom]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'position_bottom'),
						'attrs' => 'class="wbw-field-micro"',
					));
					WaicHtml::number('appearance[desktop][position_side]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'position_side'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="display" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($mobile, 'position', 'br'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[mobile][position_bottom]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'position_bottom', 20, 1),
						'attrs' => 'class="wbw-field-micro"',
					));
					WaicHtml::number('appearance[mobile][position_side]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'position_side', 20, 1),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => __('None', 'ai-copilot-content-generator'),
	'fade' => __('Fade in', 'ai-copilot-content-generator'),
	'slide_b' => __('Slide from Bottom', 'ai-copilot-content-generator'),
	'slide_r' => __('Slide from Right', 'ai-copilot-content-generator'),
	'slide_l' => __('Slide from Left', 'ai-copilot-content-generator'),
	'scale' => __('Scale', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Open Animation', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the animation effect that will be used when the chat opens and duration for animation.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="display" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][animation_open]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'animation_open', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[desktop][animation_duration]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'animation_duration'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="display" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][animation_open]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'animation_open', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[mobile][animation_duration]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'animation_duration'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Icon', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="icons">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
<?php 
$icons = $props['open_icons'];
$icon = WaicUtils::getArrayValue($desktop, 'icon_open', 'open0.svg');
$isCustom = strpos($icon, 'open') !== 0;
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Icon to open', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Customize the appearance of the chat open icon, including its size, color, background, and animation effects. This icon is used to launch the chat window when clicked.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field waic-media-wrap" data-group="icons" data-group-value="desktop">
				<div class="waic-gallery-wrap waic-gallery-small">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><i class="fa fa-upload"></i></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $icon : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($icons as $ic) { ?>
							<div class="waic-gallery-element<?php echo ( $ic == $icon ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($ic); ?>">
								<img src="<?php echo esc_url($imgUrl . 'opens/' . $ic); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('appearance[desktop][icon_open]', array('value' => $icon)); ?>
			</div>
<?php
$icon = WaicUtils::getArrayValue($mobile, 'icon_open', 'open0.svg');
$isCustom = strpos($icon, 'open') !== 0;
?>
			<div class="wbw-settings-field waic-group-hidden waic-media-wrap" data-group="icons" data-group-value="mobile">
				<div class="waic-gallery-wrap waic-gallery-small">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><i class="fa fa-upload"></i></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $icon : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($icons as $ic) { ?>
							<div class="waic-gallery-element<?php echo ( $ic == $icon ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($ic); ?>">
								<img src="<?php echo esc_url($imgUrl . 'opens/' . $ic); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('appearance[mobile][icon_open]', array('value' => $icon)); ?>
			</div>
		</div>
	</div>
<?php 
$icons = $props['close_icons'];
$icon = WaicUtils::getArrayValue($desktop, 'icon_close', 'close0.svg');
$isCustom = strpos($icon, 'close') !== 0;
?>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Icon to close', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Customize the appearance of the chat close icon, including its size, color, background, and animation effects. This icon is used to close the chat window when clicked.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field waic-media-wrap" data-group="icons" data-group-value="desktop">
				<div class="waic-gallery-wrap waic-gallery-small">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><i class="fa fa-upload"></i></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $icon : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($icons as $ic) { ?>
							<div class="waic-gallery-element<?php echo ( $ic == $icon ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($ic); ?>">
								<img src="<?php echo esc_url($imgUrl . 'closes/' . $ic); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('appearance[desktop][icon_close]', array('value' => $icon)); ?>
			</div>
<?php
$icon = WaicUtils::getArrayValue($mobile, 'icon_close', 'close0.svg');
$isCustom = strpos($icon, 'close') !== 0;
?>
			<div class="wbw-settings-field waic-group-hidden waic-media-wrap" data-group="icons" data-group-value="mobile">
				<div class="waic-gallery-wrap waic-gallery-small">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><i class="fa fa-upload"></i></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $icon : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($icons as $ic) { ?>
							<div class="waic-gallery-element<?php echo ( $ic == $icon ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($ic); ?>">
								<img src="<?php echo esc_url($imgUrl . 'closes/' . $ic); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('appearance[mobile][icon_close]', array('value' => $icon)); ?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Icon Color & Size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set icon params in this order: color, width, height. Сolor setting is available only for svg images.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="icons" data-group-value="desktop">
				<?php WaicHtml::colorSizeBlock('appearance[desktop]', 'icon', $desktop, true); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="icons" data-group-value="mobile">
				<?php WaicHtml::colorSizeBlock('appearance[mobile]', 'icon', $mobile, true); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Background', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set background settings in this order: color, width, height.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="icons" data-group-value="desktop">
				<?php WaicHtml::bgSizeBlock('appearance[desktop]', 'icon_btn', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="icons" data-group-value="mobile">
				<?php WaicHtml::bgSizeBlock('appearance[mobile]', 'icon_btn', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Rounded Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="icons" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'icon_btn', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="icons" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'icon_btn', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shadow', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set button shadow in this order: color, opacity, X, Y, blur, spread (px).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="icons" data-group-value="desktop">
				<?php WaicHtml::shadowBlock('appearance[desktop]', 'icon_btn', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="icons" data-group-value="mobile">
				<?php WaicHtml::shadowBlock('appearance[mobile]', 'icon_btn', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php
$options = array(
	'scale' => __('Scale', 'ai-copilot-content-generator'),
	'bounce' => __('Bounce', 'ai-copilot-content-generator'),
	'rotate' => __('Rotate', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Animation', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('???', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="icons" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][icon_animation]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'icon_animation'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="icons" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][icon_animation]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'icon_animation'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Chat Window', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="panel">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & Size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Chat Window settings in this order: color, width, height (px).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="panel" data-group-value="desktop">
				<?php WaicHtml::bgSizeBlock('appearance[desktop]', 'panel', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="panel" data-group-value="mobile">
				<?php WaicHtml::bgSizeBlock('appearance[mobile]', 'panel', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Rounded Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="panel" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'panel', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="panel" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'panel', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shadow', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set window shadow in this order: color, opacity, X, Y, blur, spread (px).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="panel" data-group-value="desktop">
				<?php WaicHtml::shadowBlock('appearance[desktop]', 'panel', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="panel" data-group-value="mobile">
				<?php WaicHtml::shadowBlock('appearance[mobile]', 'panel', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Header', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="header">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set header settings in this order: color, height, left-padding, right-padding', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="header" data-group-value="desktop">
				<?php WaicHtml::bgHeightPadBlock('appearance[desktop]', 'header', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="header" data-group-value="mobile">
				<?php WaicHtml::bgHeightPadBlock('appearance[mobile]', 'header', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'na' => __('Use AI Name & Avatar', 'ai-copilot-content-generator'),
	'no' => __('Use only AI Name', 'ai-copilot-content-generator'),
	'ao' => __('Use only AI Avatar', 'ai-copilot-content-generator'),
	'none' => __('None', 'ai-copilot-content-generator'),
	'custom' => __('Custom text', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Content', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set header content.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="header" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][header_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'header_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::text('appearance[desktop][header_custom]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'header_custom'),
						'attrs' => 'class="wbw-small-field" placeholder="' . esc_attr__('Custom text', 'ai-copilot-content-generator') . '"',
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="header" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][header_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'header_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::text('appearance[mobile][header_custom]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'header_custom'),
						'attrs' => 'class="wbw-small-field" placeholder="' . esc_attr__('Custom text', 'ai-copilot-content-generator') . '"',
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Avatar size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set avatar size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="header" data-group-value="desktop">
				<?php WaicHtml::sizeBlock('appearance[desktop]', 'header_avatar', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="header" data-group-value="mobile">
				<?php WaicHtml::sizeBlock('appearance[mobile]', 'header_avatar', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set avatar name settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="header" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'header_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="header" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'header_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'no' => __('none', 'ai-copilot-content-generator'),
	'minus' => __('Minus', 'ai-copilot-content-generator'),
	'close' => __('X', 'ai-copilot-content-generator'),
	'times-circle' => __('Circle with X', 'ai-copilot-content-generator'),
	'times-rectangle' => __('Rectangle with X', 'ai-copilot-content-generator'),
	'chevron-down' => __('Chevron down', 'ai-copilot-content-generator'),
	'chevron-up' => __('Chevron up', 'ai-copilot-content-generator'),
	'caret-down' => __('Arrow down', 'ai-copilot-content-generator'),
	'caret-up' => __('Arrow up', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Close icon', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set header close icon type, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="header" data-group-value="desktop">
				<?php WaicHtml::iconColorSizeBlock('appearance[desktop]', 'header_close', $desktop, $options); ?> 
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="header" data-group-value="mobile">
				<?php WaicHtml::iconColorSizeBlock('appearance[mobile]', 'header_close', $mobile, $options); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Messages panel', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="body">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & paddings', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Messages panel color and paddings (top, right, bottom, left).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::bgPaddingsBlock('appearance[desktop]', 'body', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::bgPaddingsBlock('appearance[mobile]', 'body', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$positions = array(
	'' => '',
	'left' => __('left', 'ai-copilot-content-generator'),
	'right' => __('right', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI message position & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set ai message position and width.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][ai_message_position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($desktop, 'ai_message_position', 0),
						'attrs' => 'class="wbw-field-mini"',
					));
					WaicHtml::number('appearance[desktop][ai_message_width]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'ai_message_width'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">%</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][ai_message_position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($mobile, 'ai_message_position', 0),
						'attrs' => 'class="wbw-field-mini"',
					));
					WaicHtml::number('appearance[mobile][ai_message_width]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'ai_message_width'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">%</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'na' => __('Show AI Name & Avatar', 'ai-copilot-content-generator'),
	'no' => __('Show only AI Name', 'ai-copilot-content-generator'),
	'ao' => __('Show only AI Avatar', 'ai-copilot-content-generator'),
	'none' => __('nothing', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI message content', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set ai message content.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][ai_message_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'ai_message_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][ai_message_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'ai_message_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI Avatar size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set AI avatar size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::sizeBlock('appearance[desktop]', 'ai_avatar', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::sizeBlock('appearance[mobile]', 'ai_avatar', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI name', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set AI name settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'ai_name', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'ai_name', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI message box', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set AI message box color and paddings in this order: top, right, bottom, left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::bgPaddingsBlock('appearance[desktop]', 'ai_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::bgPaddingsBlock('appearance[mobile]', 'ai_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI message text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set AI message settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'ai_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'ai_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php
$positions = array(
	'' => '',
	'none' => __('none', 'ai-copilot-content-generator'),
	'left' => __('left', 'ai-copilot-content-generator'),
	'right' => __('right', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('AI message time', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set AI message time settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
					<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'ai_time', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
					<label class="wbw-settings-after"><?php esc_html_e('position', 'ai-copilot-content-generator'); ?></label>
					<?php
						WaicHtml::selectbox('appearance[desktop][ai_time_position]', array(
							'options' => $positions,
							'value' => WaicUtils::getArrayValue($desktop, 'ai_time_position', 0),
							'attrs' => 'class="wbw-field-mini"',
						));
						?>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
					<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'ai_time', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
					<label class="wbw-settings-after"><?php esc_html_e('position', 'ai-copilot-content-generator'); ?></label>
					<?php
						WaicHtml::selectbox('appearance[mobile][ai_time_position]', array(
							'options' => $positions,
							'value' => WaicUtils::getArrayValue($mobile, 'ai_time_position', 0),
							'attrs' => 'class="wbw-field-mini"',
						));
						?>
				</div>
			</div>
		</div>
	</div>
<?php 
$positions = array(
	'' => '',
	'left' => __('left', 'ai-copilot-content-generator'),
	'right' => __('right', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User message position & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user message position and width.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][user_message_position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($desktop, 'user_message_position', 0),
						'attrs' => 'class="wbw-field-mini"',
					));
					WaicHtml::number('appearance[desktop][user_message_width]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'user_message_width'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">%</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][user_message_position]', array(
						'options' => $positions,
						'value' => WaicUtils::getArrayValue($mobile, 'user_message_position', 0),
						'attrs' => 'class="wbw-field-mini"',
					));
					WaicHtml::number('appearance[mobile][user_message_width]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'user_message_width'),
						'attrs' => 'class="wbw-field-micro"',
					));
					?>
				<label class="wbw-settings-after">%</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'na' => __('Show User Name & Avatar', 'ai-copilot-content-generator'),
	'no' => __('Show only User Name', 'ai-copilot-content-generator'),
	'ao' => __('Show only User Avatar', 'ai-copilot-content-generator'),
	'none' => __('nothing', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user message content.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][user_message_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'user_message_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][user_message_content]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'user_message_content'),
						'attrs' => 'class="wbw-small-field"',
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User avatar size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user avatar size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::sizeBlock('appearance[desktop]', 'user_avatar', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::sizeBlock('appearance[mobile]', 'user_avatar', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User name', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user name settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'user_name', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'user_name', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User message box', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user message box color and paddings in this order: top, right, bottom, left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::bgPaddingsBlock('appearance[desktop]', 'user_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::bgPaddingsBlock('appearance[mobile]', 'user_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User message text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user message settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'user_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'user_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php
$positions = array(
	'' => '',
	'none' => __('none', 'ai-copilot-content-generator'),
	'left' => __('left', 'ai-copilot-content-generator'),
	'right' => __('right', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('User message time', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set user message time settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
					<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'user_time', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field" data-group="body" data-group-value="desktop">
					<label class="wbw-settings-after"><?php esc_html_e('position', 'ai-copilot-content-generator'); ?></label>
					<?php
						WaicHtml::selectbox('appearance[desktop][user_time_position]', array(
							'options' => $positions,
							'value' => WaicUtils::getArrayValue($desktop, 'user_time_position', 0),
							'attrs' => 'class="wbw-field-mini"',
						));
						?>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
					<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'user_time', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="body" data-group-value="mobile">
					<label class="wbw-settings-after"><?php esc_html_e('position', 'ai-copilot-content-generator'); ?></label>
					<?php
						WaicHtml::selectbox('appearance[mobile][user_time_position]', array(
							'options' => $positions,
							'value' => WaicUtils::getArrayValue($mobile, 'user_time_position', 0),
							'attrs' => 'class="wbw-field-mini"',
						));
						?>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Recommendation cards', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="cards">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & paddings', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Card color and paddings (top, right, bottom, left).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::bgPaddingsBlock('appearance[desktop]', 'card', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::bgPaddingsBlock('appearance[mobile]', 'card', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Rounded Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'card', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'card', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shadow', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set card shadow in this order: color, opacity, X, Y, blur, spread (px).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::shadowBlock('appearance[desktop]', 'card', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::shadowBlock('appearance[mobile]', 'card', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Title text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set card title text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'card_title', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'card_title', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Description text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set card description text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'card_desc', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'card_desc', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Price text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set price text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'card_price', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'card_price', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Category text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set category text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'card_cat', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'card_cat', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & paddings Category', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Category Box color and paddings (top, right, bottom, left).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::bgPaddingsBlock('appearance[desktop]', 'card_cat', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::bgPaddingsBlock('appearance[mobile]', 'card_cat', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Category Box Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Category Box Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'card_cat', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'card_cat', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Footer text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set footer text (author, date) settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="cards" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'card_footer', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="cards" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'card_footer', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Welcome Buttons', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="buttons">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Welcome Buttons settings in this order: color, width, height', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<?php WaicHtml::bgSizeBlock('appearance[desktop]', 'buttons', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[desktop][buttons_hover_bg]', array(
							'value' => WaicUtils::getArrayValue($desktop, 'buttons_hover_bg'),
						));
						?>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<?php WaicHtml::colorSizeBlock('appearance[mobile]', 'buttons', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[mobile][buttons_hover_bg]', array(
							'value' => WaicUtils::getArrayValue($mobile, 'buttons_hover_bg'),
						));
						?>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Borders', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Welcome Buttons borders settings in this order: color, style and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<?php WaicHtml::bordersBlock('appearance[desktop]', 'buttons', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[desktop][buttons_hover_border_color]', array(
							'value' => WaicUtils::getArrayValue($desktop, 'buttons_hover_border_color'),
						));
						?>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<?php WaicHtml::bordersBlock('appearance[mobile]', 'buttons', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[mobile][buttons_hover_border_color]', array(
							'value' => WaicUtils::getArrayValue($mobile, 'buttons_hover_border_color'),
						));
						?>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Welcome Buttons text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'buttons', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[desktop][buttons_hover_color]', array(
							'value' => WaicUtils::getArrayValue($desktop, 'buttons_hover_color'),
						));
						?>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'buttons', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
					<label class="wbw-settings-after"><?php esc_html_e('hover', 'ai-copilot-content-generator'); ?></label>
					<?php 
						WaicHtml::colorpicker('appearance[mobile][buttons_hover_color]', array(
							'value' => WaicUtils::getArrayValue($mobile, 'buttons_hover_color'),
						));
						?>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Rounded Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="buttons" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'buttons', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="buttons" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'buttons', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Footer', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="footer">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set footer settings in this order: color, height, left-padding, right-padding', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php WaicHtml::bgHeightPadBlock('appearance[desktop]', 'footer', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php WaicHtml::bgHeightPadBlock('appearance[mobile]', 'footer', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Input text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set input text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'input_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'input_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Input placeholder', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set input placeholder settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'input_placeholder', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'input_placeholder', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'send' => __('Plane', 'ai-copilot-content-generator'),
	'send-o' => __('Plane transparent', 'ai-copilot-content-generator'),
	'play' => __('Play', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Send icon', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set send icon type, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php WaicHtml::iconColorSizeBlock('appearance[desktop]', 'send', $desktop, $options); ?> 
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php WaicHtml::iconColorSizeBlock('appearance[mobile]', 'send', $mobile, $options); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'paperclip' => __('Paperclip', 'ai-copilot-content-generator'),
	'folder-open' => __('Folder', 'ai-copilot-content-generator'),
	'folder-open-o' => __('Folder Transparent', 'ai-copilot-content-generator'),
	'upload' => __('Upload', 'ai-copilot-content-generator'),
	'cloud-upload' => __('Cloud Upload', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Attachment icon', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set attachment icon type, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php WaicHtml::iconColorSizeBlock('appearance[desktop]', 'clip', $desktop, $options); ?> 
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php WaicHtml::iconColorSizeBlock('appearance[mobile]', 'clip', $mobile, $options); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Action hover', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set action icon hover settings in order: icon color, background color.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="footer" data-group-value="desktop">
				<?php 
					WaicHtml::colorpicker('appearance[desktop][action_hover_color]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'action_hover_color'),
					)); 
					WaicHtml::colorpicker('appearance[desktop][action_hover_bg]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'action_hover_bg'),
					));
					?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="footer" data-group-value="mobile">
				<?php 
					WaicHtml::colorpicker('appearance[mobile][action_hover_color]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'action_hover_color'),
					)); 
					WaicHtml::colorpicker('appearance[mobile][action_hover_bg]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'action_hover_bg'),
					));
					?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php
$options = array(
	0 => __('No', 'ai-copilot-content-generator'),
	1 => __('Yes', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-group-title">
		<?php esc_html_e('Pop-up welcome message', 'ai-copilot-content-generator'); ?>
		<div class="waic-grbtn" data-name="popup">
			<button type="button" data-value="desktop" class="wbw-button current"><i class="fa fa-desktop"></i></button>
			<button type="button" data-value="mobile" class="wbw-button"><i class="fa fa-mobile"></i></button>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Enabled', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Automatically show the Pop-up welcome message if the user has not opened the chatbot within the specified number of seconds.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][e_popup]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'e_popup', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[desktop][popup_show]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'popup_show', 5, 1, false, true, true),
						'attrs' => 'class="wbw-field-micro" min="0"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][e_popup]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'e_popup', 0),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::number('appearance[mobile][popup_show]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'popup_show', 5, 1, false, true, true),
						'attrs' => 'class="wbw-field-micro" min="0"',
					));
					?>
				<label class="wbw-settings-after">sec</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the welcome message.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php 
					WaicHtml::textarea('appearance[desktop][popup_message]', array(
						'value' => WaicUtils::getArrayValue($desktop, 'popup_message', __('👋 Want to chat about AIWU? I\'m an AI chatbot here to help you find your way.', 'ai-copilot-content-generator')), 
						'rows' => 2,
					));
					?>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php 
					WaicHtml::textarea('appearance[mobile][popup_message]', array(
						'value' => WaicUtils::getArrayValue($mobile, 'popup_message', __('👋 Want to chat about AIWU? I\'m an AI chatbot here to help you find your way.', 'ai-copilot-content-generator')), 
						'rows' => 2,
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Avatar', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enable if need bot avatar to show. Set avatar size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php 
					WaicHtml::selectbox('appearance[desktop][popup_avatar]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($desktop, 'popup_avatar'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::sizeBlock('appearance[desktop]', 'popup_avatar', $desktop);
					?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php 
					WaicHtml::selectbox('appearance[mobile][popup_avatar]', array(
						'options' => $options,
						'value' => WaicUtils::getArrayValue($mobile, 'popup_avatar'),
						'attrs' => 'class="wbw-small-field"',
					));
					WaicHtml::sizeBlock('appearance[mobile]', 'popup_avatar', $desktop);
					?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set message text settings in this order: font-family, style, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::fontStyleBlock('appearance[desktop]', 'popup_text', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::fontStyleBlock('appearance[mobile]', 'popup_text', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Color & size', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set welcome pop-up settings in this order: color, width, height', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::bgSizeBlock('appearance[desktop]', 'popup', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::bgSizeBlock('appearance[mobile]', 'popup', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Paggings', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set welcome pop-up paddings in this order: top, right, bottom, left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::paddingsBlock('appearance[desktop]', 'popup', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::paddingsBlock('appearance[mobile]', 'popup', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form wbw-settings-top row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Borders', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set welcome pop-up borders settings in this order: color, style and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-column">
				<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
					<?php WaicHtml::bordersBlock('appearance[desktop]', 'popup', $desktop); ?>
					<label class="wbw-settings-after">px</label>
				</div>
				<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
					<?php WaicHtml::bordersBlock('appearance[mobile]', 'popup', $mobile); ?>
					<label class="wbw-settings-after">px</label>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Rounded Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Icon Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'popup', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'popup', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shadow', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set welcome pop-up shadow in this order: color, opacity, X, Y, blur, spread (px).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::shadowBlock('appearance[desktop]', 'popup', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::shadowBlock('appearance[mobile]', 'popup', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
<?php 
$options = array(
	'' => '',
	'no' => __('none', 'ai-copilot-content-generator'),
	'minus' => __('Minus', 'ai-copilot-content-generator'),
	'close' => __('X', 'ai-copilot-content-generator'),
	'times-circle' => __('Circle with X', 'ai-copilot-content-generator'),
	'times-rectangle' => __('Rectangle with X', 'ai-copilot-content-generator'),
	'chevron-down' => __('Chevron down', 'ai-copilot-content-generator'),
	'chevron-up' => __('Chevron up', 'ai-copilot-content-generator'),
	'caret-down' => __('Arrow down', 'ai-copilot-content-generator'),
	'caret-up' => __('Arrow up', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Close icon', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set welcome pop-up close icon type, color and size.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::iconColorSizeBlock('appearance[desktop]', 'popup_close', $desktop, $options); ?> 
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::iconColorSizeBlock('appearance[mobile]', 'popup_close', $mobile, $options); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Icon Background', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set pop-up background settings in this order: color, width, height.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::bgSizeBlock('appearance[desktop]', 'popup_btn', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::bgSizeBlock('appearance[mobile]', 'popup_btn', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Icon Corners', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Pop-up Rounded Corners in this order: top left, top right, bottom right, bottom left.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field" data-group="popup" data-group-value="desktop">
				<?php WaicHtml::cornerRadiusBlock('appearance[desktop]', 'popup_btn', $desktop); ?>
				<label class="wbw-settings-after">px</label>
			</div>
			<div class="wbw-settings-field waic-group-hidden" data-group="popup" data-group-value="mobile">
				<?php WaicHtml::cornerRadiusBlock('appearance[mobile]', 'popup_btn', $mobile); ?>
				<label class="wbw-settings-after">px</label>
			</div>
		</div>
	</div>
</section>