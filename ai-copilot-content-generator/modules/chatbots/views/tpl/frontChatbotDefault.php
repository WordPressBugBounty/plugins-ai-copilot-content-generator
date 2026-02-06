<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$viewId = $props['view_id'];
$isPreview = $this->props['preview'];
$imgPath = $props['img_path'];
$messages = $props['messages'];
$classes = $props['classes'];

$aiAvatar = $this->props['ai_avatar'];
$aiName = $this->props['ai_name'];
$userAvatar = $this->props['user_avatar'];
$userName = $this->props['user_name'];

$data = $this->props['data'];
$desktop = WaicUtils::getArrayValue($data, 'desktop', array(), 2);
$mobile = WaicUtils::getArrayValue($data, 'mobile', array(), 2);

$addSelector = '#waic-' . $props['view_id'];
$viewMode = $this->props['view_mode'];
$modes = array('');
if ($this->existCustomCss('mobile')) {
	$this->setCustomCss('.waic-mobile', 'display', 'block', 'mobile');
	$this->setCustomCss('.waic-desktop', 'display', 'none', 'mobile');
}
if (!empty($viewMode)) {
	if ('mobile' == $viewMode) {
		$modes = array('', 'mobile');
		//$this->setCustomCss('.waic-mobile', 'display', 'block', 'mobile');
		//$this->setCustomCss('.waic-desktop', 'display', 'none', 'mobile');
		$data['desktop'] = $data['mobile'];
		unset($data['mobile']);
	} else {
		$modes = array('', 'desktop');
	}
}
$customCss = $this->getCustomCssString($modes, $addSelector);

if (empty($viewMode)) {
	if (isset($classes['wrapper']) && in_array('waic-full-mobile', $classes['wrapper'])) {
		$this->setCustomCss('.waic-full-show', 'left', '0', 'mobile');
		$this->setCustomCss('.waic-full-show', 'top', '0', 'mobile');
		$this->setCustomCss('.waic-full-show', 'width', '100%', 'mobile');
		$this->setCustomCss('.waic-full-show', 'height', '10000px', 'mobile');
		$this->setCustomCss('.waic-full-show', 'max-height', '100%', 'mobile');
		$this->setCustomCss('.waic-full-show', 'z-index', '100000', 'mobile');
		$this->setCustomCss('.waic-full-show .waic-chatbot-panel', 'width', '100%', 'mobile');
		$this->setCustomCss('.waic-full-show .waic-chatbot-panel', 'height', '10000px', 'mobile');
		$this->setCustomCss('.waic-full-show .waic-chatbot-panel', 'max-height', '100%', 'mobile');
	}
	if ($this->existCustomCss('desktop')) {
		$customCss .= '@media screen and (min-width: ' . $this->props['breakpoint'] . 'px) {' . $this->getCustomCssString(array('desktop'), $addSelector) . '}';
	}
	if ($this->existCustomCss('mobile')) {
		//$this->setCustomCss('.waic-mobile', 'display', 'block', 'mobile');
		//$this->setCustomCss('.waic-desktop', 'display', 'none', 'mobile');
		$customCss .= '@media screen and (max-width: ' . $this->props['breakpoint'] . 'px) {' . $this->getCustomCssString(array('mobile'), $addSelector) . '}';
	}
	
}

//WaicUtils::getArrayValue($desktop, 'header_close', 'minus');




/*$css = $this->props['css'];
$customCss = $this->props['custom_css'] . ( empty($css['desktop']) ? '' : $css['desktop'] );
if (!empty($css['mobile'])) {
	$customCss .= '@media screen and (max-width: ' . $this->props['breakpoint'] . 'px) {' . 
		$css['mobile'] .
		'.waic-mobile {display: block;} .waic-desktop {display: none;}' .
	'}';
}*/
if (!empty($customCss)) { ?>
<style type="text/css" id="waicCustomCss-<?php echo esc_attr($viewId); ?>">
	<?php WaicHtml::echoEscapedHtml($customCss); ?>
</style>
<?php } ?>
<div class="waic-chatbot-wrapper <?php echo esc_attr(implode(' ' , $classes['wrapper'])); ?>">
	<div class="waic-chatbot-panel <?php echo esc_attr(implode(' ' , $classes['panel'])); ?>">
		<div class="waic-chatbot-header">
			<div class="waic-chatbot-main-avatar">
				<div class="waic-chatbot-avatar">
					<img src="<?php echo esc_url($aiAvatar); ?>">
				</div>
			</div>
			<div class="waic-chatbot-name waic-desktop"><?php echo esc_html(WaicUtils::getArrayValue($desktop, 'header_name', $aiName)); ?></div>
			<div class="waic-chatbot-name waic-mobile"><?php echo esc_html(WaicUtils::getArrayValue($mobile, 'header_name', $aiName)); ?></div>
			<div class="waic-header-close">
				<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'header_close', 'minus')); ?> waic-desktop"></i>
				<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'header_close', 'minus')); ?> waic-mobile"></i>
			</div>
		</div>
		<div class="waic-chatbot-body">
			<div class="waic-chatbot-messages">
				<div class="waic-chatbot-message waic-chatbot-tmp waic-message-ai">
					<div class="waic-chatbot-avatar waic-avatar-ai">
						<img src="<?php echo esc_url($aiAvatar); ?>">
					</div>
					<div class="waic-message-wrap">
						<div class="waic-message-name waic-name-ai"><?php echo esc_html($aiName); ?></div>
						<div class="waic-message-text waic-text-ai">
							<div class="waic-message-loader">
								<?php echo esc_html($props['loader_text']); ?>
								<div class="waic-typing">
									<div class="dot"></div>
									<div class="dot"></div>
									<div class="dot"></div>
								</div>
							</div>
						</div>
						<div class="waic-message-time waic-time-ai"></div>
						<div class="waic-message-buttons"></div>
					</div>
				</div>
				<div class="waic-chatbot-message waic-chatbot-tmp waic-message-user">
					<div class="waic-chatbot-avatar waic-avatar-user">
						<img src="<?php echo esc_url($userAvatar); ?>">
					</div>
					<div class="waic-message-wrap">
						<div class="waic-message-name waic-name-user"><?php echo esc_html($userName); ?></div>
						<div class="waic-message-text waic-text-user">
						<?php if ($props['can_upload']) { ?>
							<div class="waic-message-loader">
								<?php echo esc_html($props['loader_file']); ?>
								<div class="waic-typing">
									<div class="dot"></div>
									<div class="dot"></div>
									<div class="dot"></div>
								</div>
							</div>
						<?php } ?>
						</div>
						<div class="waic-message-time waic-time-user"></div>
					</div>
				</div>
				<?php 
				foreach ($messages as $message) { 
					$isAi = 'ai' == $message['typ'];
					$typ = $isAi ? 'ai' : 'user';
					?>
				<div class="waic-chatbot-message waic-message-<?php echo esc_html($typ); ?>">
					<div class="waic-chatbot-avatar waic-avatar-<?php echo esc_html($typ); ?>">
						<img src="<?php echo esc_url($isAi ? $aiAvatar : $userAvatar); ?>">
					</div>
					<div class="waic-message-wrap">
						<div class="waic-message-name waic-name-<?php echo esc_html($typ); ?>"><?php echo esc_html($isAi ? $aiName : $userName); ?></div>
						<div class="waic-message-text waic-text-<?php echo esc_html($typ); ?>">
						<?php 
							echo empty($message['file']) ? wp_kses_post($message['msg']) : '<img src="' . $message['file'] . '">'; 
						?>
						</div>
						<?php 
							if (!empty($message['cards']) && is_array($message['cards'])) {
								foreach ($message['cards'] as $card) {
									echo wp_kses_post($card);
								}
							}
						?>
						
						<?php if (!empty($message['tt'])) { ?>
							<div class="waic-message-time waic-time-<?php echo esc_html($typ); ?>"><?php echo esc_html($message['tt']); ?></div>
						<?php } ?>
						<?php if (!empty($message['btn'])) { ?>
						<div class="waic-message-buttons">
							<?php foreach ($message['btn'] as $btn) { ?>
								<a href="<?php echo esc_url($btn['link']); ?>" target="_blank" class="waic-message-button<?php echo empty($btn['class']) ? '' : ' ' . esc_attr($btn['class']); ?>"><?php echo esc_html($btn['name']); ?></a>
							<?php } ?>
						</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
			</div>
		</div>
		<div class="waic-chatbot-footer">
			<textarea type="text" name="" class="waic-chatbot-input" placeholder="<?php echo esc_attr($props['plh_text']); ?>" rows="1" data-plh="<?php echo esc_attr($props['plh_text']); ?>"<?php echo empty($props['maxlength']) ? '' : ' maxlength="' . esc_attr($props['maxlength']) . '"'; ?>></textarea>
			<div class="waic-message-actions">
				<?php if ($props['can_upload']) { ?>
					<div class="waic-message-action waic-message-clip" data-max-size="<?php echo esc_attr($props['max_file_size']); ?>" data-too-many="<?php esc_html_e('Too many files', 'ai-copilot-content-generator'); ?>" data-too-big="<?php esc_html_e('File is too big!', 'ai-copilot-content-generator'); ?>">
						<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'clip', 'paperclip')); ?> waic-desktop"></i>
						<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'clip', 'paperclip')); ?> waic-mobile"></i>
					</div>
					<form class="waic-chatbot-hidden waic-chatbot-upload" method="POST" enctype="multipart/form-data">  
						<input type="file" accept="image/jpeg,image/png,image/gif" name="loadfile">  
					</form>
				<?php } ?>
				<div class="waic-message-action waic-message-send">
					<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'send', 'send')); ?> waic-desktop"></i>
					<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'send', 'send')); ?> waic-mobile"></i>
				</div>
			</div>
		</div>
	</div>
	<?php if (!empty($desktop['need_welcome'])) { ?>
		<div class="waic-chatbot-welcome waic-desktop <?php echo esc_attr(implode(' ' , $classes['welcome'])); ?>"<?php echo ( '' === $desktop['popup_show'] ? '' : ' data-autoshow="' . esc_attr($desktop['popup_show']) . '"' ); ?>>
			<div class="waic-welcome-close">
				<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'popup_close', 'close')); ?> waic-close-popup"></i>
			</div>
			<div class="waic-welcome-popup">
				<div class="waic-welcome-avatar waic-chatbot-avatar">
					<img src="<?php echo esc_url($aiAvatar); ?>">
				</div>
				<div class="waic-welcome-text"><?php echo esc_html(WaicUtils::getArrayValue($desktop, 'popup_message')); ?></div>
			</div>
		</div>
	<?php } ?>
	<?php if (!empty($mobile['need_welcome'])) { ?>
		<div class="waic-chatbot-welcome waic-mobile <?php echo esc_attr(implode(' ' , $classes['welcome'])); ?>"<?php echo ( '' === $mobile['popup_show'] ? '' : ' data-autoshow="' . esc_attr($mobile['popup_show']) . '"' ); ?>>
			<div class="waic-welcome-close">
				<i class="fa fa-<?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'popup_close', 'close')); ?> waic-close-popup"></i>
			</div>
			<div class="waic-welcome-popup">
				<div class="waic-welcome-avatar waic-chatbot-avatar">
					<img src="<?php echo esc_url($aiAvatar); ?>">
				</div>
				<div class="waic-welcome-text"><?php echo esc_html(WaicUtils::getArrayValue($mobile, 'popup_message')); ?></div>
			</div>
		</div>
	<?php } ?>
	<div class="waic-chatbot-buttons <?php echo esc_attr(implode(' ' , $classes['buttons'])); ?>" data-viewid="<?php echo esc_attr($viewId); ?>">
		<div class="waic-chatbot-button waic-chatbot-open<?php echo $isPreview ? ' waic-chatbot-hidden' : ''; ?>">
			<?php if (!empty($desktop['icon_open'])) { ?>
			<img class="waic-desktop <?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'io_class')); ?>" data-autostart="<?php echo esc_attr($desktop['autostart']); ?>" src="<?php echo esc_url($desktop['icon_open']); ?>">
			<?php } ?>
			<?php if (!empty($mobile['icon_open'])) { ?>
			<img class="waic-mobile <?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'io_class')); ?>"  data-autostart="<?php echo esc_attr($mobile['autostart']); ?>" src="<?php echo esc_url($mobile['icon_open']); ?>">
			<?php } ?>
		</div>
		<div class="waic-chatbot-button waic-chatbot-close<?php echo $isPreview ? '' : ' waic-chatbot-hidden'; ?>">
			<?php if (!empty($desktop['icon_close'])) { ?>
			<img class="waic-desktop <?php echo esc_attr(WaicUtils::getArrayValue($desktop, 'ic_class')); ?>" src="<?php echo esc_url($desktop['icon_close']); ?>">
			<?php } ?>
			<?php if (!empty($mobile['icon_close'])) { ?>
			<img class="waic-mobile <?php echo esc_attr(WaicUtils::getArrayValue($mobile, 'ic_class')); ?>" src="<?php echo esc_url($mobile['icon_close']); ?>">
			<?php } ?>
		</div>
	</div>
</div>
