<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$context = WaicUtils::getArrayValue($props['settings'], 'context', array(), 2);
$imgUrl = $props['img_url'];
$aiAvatars = $props['ai_avatars'];
$userAvatars = $props['user_avatars'];
$defForChecks = empty($props['task_id']) ? 1 : 0;
$adminEmail = $props['admin_email'];

$modes = array(
	'chat' => __('Chat', 'ai-copilot-content-generator'),
	//'assistant' => __('Assistant', 'ai-copilot-content-generator'),
);
?>
<section class="wbw-body-options">
	<div class="wbw-settings-form row wbw-settings-top">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Instructions', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Provide specific instructions to guide the bot\'s behavior. You can either type the instructions directly or upload a text file with detailed guidelines.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::textarea('context[instructions]', array(
					'value' => WaicUtils::getArrayValue($context, 'instructions', __('You are an AI assistant designed to provide helpful, accurate, and context-aware responses. Follow the given instructions carefully and ensure your replies are relevant, concise, and aligned with the user\'s needs. Maintain a professional and friendly tone while engaging in conversations.', 'ai-copilot-content-generator')),
					'rows' => 6,
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Content Aware', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php echo esc_html__('If enabled, this feature allows AI to consider the context or content of the page where it was triggered. You can add and specify selectors to improve contextual understanding.', 'ai-copilot-content-generator') . '<br><br>' . esc_html__(' To enable this, simply include the placeholder {CONTENT} in the Instructions and the {CONTENT} is automatically replaced by the content of the page.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_aware]', array(
					'checked' => WaicUtils::getArrayValue($context, 'e_aware', 0, 1),
				));
				?>
			<button class="wbw-button wbw-button-small waic-add-list-block m-0" type="button" data-block="aware_selectors"><?php esc_html_e('Add selector', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
<?php 
	$selectors = WaicUtils::getArrayValue($context, 'aware_selectors', array(), 2);
?>
	<div class="wbw-settings-form row<?php echo ( empty($selectors) ? ' wbw-hidden' : '' ); ?>">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field waic-list-blocks" data-block="aware_selectors">
			<?php foreach ($selectors as $selector) { ?>
				<div class="waic-list-block">
					<?php 
						WaicHtml::text('context[aware_selectors][]', array(
							'value' => $selector,
						));
					?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>
			<?php } ?>
				<div class="waic-list-block wbw-hidden waic-list-blocks-tmp">
					<?php 
						WaicHtml::text('context[aware_selectors][]', array('attrs' => 'class="wbw-nosave"'));
					?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>
			</div>
		</div>
	</div>
<?php
$eHumanRequest = WaicUtils::getArrayValue($context, 'e_human_request', 0, 1);
$hidden = $eHumanRequest ? '' : ' wbw-hidden';
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Human Assistance Request', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('If enabled, adds a button to the chat allowing the user to request human assistance. When clicked, the bot sends a predefined message to the user and forwards the form to the specified admin email.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_human_request]', array(
					'checked' => $eHumanRequest,
				));
				?>
			<div class="wbw-settings-field<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_human_request]">
				<?php
				WaicHtml::text('context[human_request_button]', array(
					'value' => WaicUtils::getArrayValue($context, 'human_request_button', __('Talk to a Human', 'ai-copilot-content-generator')),
					'attrs' => 'class="wbw-medium-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_human_request]">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Admin Email', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the email address where the form will be sent when the user requests human assistance.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::text('context[human_admin_email]', array(
					'value' => WaicUtils::getArrayValue($context, 'human_admin_email', $adminEmail),
					'attrs' => 'class="waic-settings-sup wbw-medium-field" placeholder="' . esc_attr(__('Enter email', 'ai-copilot-content-generator')) . '"',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_human_request]">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Delayed Button Display', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the email address where the form will be sent when the user requests human assistance.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::number('context[human_request_delay]', array(
					'value' => WaicUtils::getArrayValue($context, 'human_request_delay', 0, 1),
					'attrs' => 'class="waic-settings-sup"',
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form wbw-settings-top row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_human_request]">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Predefined Message for User', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Enter the message that will be shown to the user when they request human assistance. This message typically asks for their email and informs them of the response time.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::textarea('context[human_request_message]', array(
					'value' => WaicUtils::getArrayValue($context, 'human_request_message'),
					'attrs' => 'placeholder="' . esc_attr(__('Please provide your email address so we can assist you further. Our team will get back to you within 24 hours. Thank you!', 'ai-copilot-content-generator')) . '"',
					'rows' => 6,
				));
				?>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Bot Identity', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the display name for your chatbot that users will see in conversations.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('context[ai_name]', array(
					'value' => WaicUtils::getArrayValue($context, 'ai_name', __('Chat Bot', 'ai-copilot-content-generator')),
				));
				?>
			</div>
		</div>
	</div>
<?php 
$eAiAvatar = WaicUtils::getArrayValue($context, 'e_ai_avatar', $defForChecks, 1, false, true);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Avatar', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose or upload an image to represent your chatbot visually in the chat.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_ai_avatar]', array(
					'checked' => $eAiAvatar,
				));
				?>
		</div>
	</div>
<?php 
$hidden = $eAiAvatar ? '' : ' wbw-hidden';
$aiAvatar = WaicUtils::getArrayValue($context, 'ai_avatar', 'ai_avatar0.png');
$isCustom = strpos($aiAvatar, 'ai_avatar') !== 0;
?>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_ai_avatar]">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field waic-media-wrap">
				<div class="waic-gallery-wrap">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><?php esc_html_e('Upload', 'ai-copilot-content-generator'); ?></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $aiAvatar : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($aiAvatars as $avatar) { ?>
							<div class="waic-gallery-element<?php echo ( $avatar == $aiAvatar ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($avatar); ?>">
								<img src="<?php echo esc_url($imgUrl . 'ai_avatars/' . $avatar); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('context[ai_avatar]', array('value' => $aiAvatar)); ?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row wbw-settings-top">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Welcome message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the first message your chatbot will display to users when the chat starts.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::textarea('context[welcome_message]', array(
					'value' => WaicUtils::getArrayValue($context, 'welcome_message', __('👋 Want to chat about AIWU? I\'m an AI chatbot here to help you find your way. Ask me or select an option below.', 'ai-copilot-content-generator')),
					'rows' => 6,
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Welcome buttons', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Add buttons to help users quickly access resources or navigate to specific pages.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_welcome_buttons]', array(
					'checked' => WaicUtils::getArrayValue($context, 'e_welcome_buttons', 0, 1),
				));
				?>
			<button class="wbw-button wbw-button-small waic-add-list-block m-0" type="button" data-block="welcome_buttons"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
<?php 
$welcomeButtons = WaicUtils::getArrayValue($context, 'welcome_buttons', array(), 2);
$n = 0;
?>
	<div class="wbw-settings-form row<?php echo ( empty($welcomeButtons) ? ' wbw-hidden' : '' ); ?>">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field waic-list-blocks" data-block="welcome_buttons">
			<?php foreach ($welcomeButtons as $button) { ?>
				<div class="waic-list-block">
					<?php 
						WaicHtml::text('context[welcome_buttons][' . $n . '][name]', array(
							'value' => WaicUtils::getArrayValue($button, 'name'),
							'attrs' => 'class="wbw-small-field"',
						));
						WaicHtml::text('context[welcome_buttons][' . $n . '][link]', array(
							'value' => WaicUtils::getArrayValue($button, 'link'),
							'attrs' => 'class="wbw-small-field"',
						));
						$n++;
					?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>			
			<?php } ?>
				<div class="waic-list-block wbw-hidden waic-list-blocks-tmp" data-next-n="<?php echo esc_attr($n); ?>">
					<?php 
						WaicHtml::text('context[welcome_buttons][$n][name]', array(
							'attrs' => 'class="wbw-small-field wbw-nosave" placeholder="' . esc_attr(__('Enter button text', 'ai-copilot-content-generator')) . '"',
						));
						WaicHtml::text('context[welcome_buttons][$n][link]', array(
							'attrs' => 'class="wbw-small-field wbw-nosave" placeholder="' . esc_attr(__('Enter link', 'ai-copilot-content-generator')) . '"',
						));
						?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Loader text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the text displayed while the chatbot is generating a response (e.g., Alex is typing).', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('context[loader_text]', array(
					'value' => WaicUtils::getArrayValue($context, 'loader_text', __('typing', 'ai-copilot-content-generator')),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('User Identity', 'ai-copilot-content-generator'); ?>
	</div>
<?php 
$eUserName = WaicUtils::getArrayValue($context, 'e_user_name', $defForChecks, 1, false, true);
$hidden = $eUserName ? '' : ' wbw-hidden';
$options = array(
	'user' => __('Use username if known', 'ai-copilot-content-generator'),
	'specified' => __('Always use the specified name', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Name', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Define how guest users are addressed in the chat. You can use their username if known or set a default name.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_user_name]', array(
					'checked' => $eUserName,
				));
				?>
			<div class="wbw-settings-field<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_user_name]">
			<?php
				WaicHtml::selectbox('context[mode_user_name]', array(
					'options' => $options,
					'value' => WaicUtils::getArrayValue($context, 'mode_user_name', 'user'),
					'attrs' => 'class="wbw-medium-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_user_name]">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<?php
				WaicHtml::text('context[user_name]', array(
					'value' => WaicUtils::getArrayValue($context, 'user_name', __('Guest', 'ai-copilot-content-generator')),
					'attrs' => 'class="waic-settings-sup"',
				));
				?>
		</div>
	</div>
<?php 
$eUserAvatar = WaicUtils::getArrayValue($context, 'e_user_avatar', $defForChecks, 1, false, true);
$hidden = $eUserAvatar ? '' : ' wbw-hidden';
$options = array(
	'user' => __('Use user photo, or gravatar if known', 'ai-copilot-content-generator'),
	'specified' => __('Always use the specified avatar', 'ai-copilot-content-generator'),
);
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Avatar', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select or upload an avatar to represent guest users in the chat.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_user_avatar]', array(
					'checked' => $eUserAvatar,
				));
				?>
			<div class="wbw-settings-field<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_user_avatar]">
			<?php
				WaicHtml::selectbox('context[mode_user_avatar]', array(
					'options' => $options,
					'value' => WaicUtils::getArrayValue($context, 'mode_user_avatar', 'user'),
					'attrs' => 'class="wbw-medium-field"',
				));
				?>
			</div>
		</div>
	</div>
<?php 
$userAvatar= WaicUtils::getArrayValue($context, 'user_avatar', 'user_avatar0.png');
$isCustom = strpos($userAvatar, 'user_avatar') !== 0;
?>
	<div class="wbw-settings-form row<?php echo esc_attr($hidden); ?>" data-parent-check="context[e_user_avatar]">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field waic-media-wrap">
				<div class="waic-gallery-wrap">
					<div class="waic-settings-gallery">
						<div class="waic-gallery-upload<?php echo $isCustom ? ' wbw-hidden' : ''; ?>">
							<button class="wbw-button wbw-button-upload" type="button"><?php esc_html_e('Upload', 'ai-copilot-content-generator'); ?></button>
						</div>
						<div class="waic-gallery-element waic-gallery-media <?php echo $isCustom ? 'selected' : 'wbw-hidden'; ?>" data-file="">
							<img src="<?php echo esc_url($isCustom ? $userAvatar : ''); ?>" class="waic-custom-media">
							<div class="waic-media-delete"><i class="fa fa-close"></i></div>
						</div>
						<?php foreach ($userAvatars as $avatar) { ?>
							<div class="waic-gallery-element<?php echo ( $avatar == $userAvatar ? ' selected' : '' ); ?>" data-file="<?php echo esc_attr($avatar); ?>">
								<img src="<?php echo esc_url($imgUrl . 'user_avatars/' . $avatar); ?>">
							</div>
						<?php } ?>
					</div>
				</div>
			<?php WaicHtml::hidden('context[user_avatar]', array('value' => $userAvatar)); ?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Placeholder text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set input placeholder text.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::text('context[plh_text]', array(
						'value' => WaicUtils::getArrayValue($context, 'plh_text', __('Write a message', 'ai-copilot-content-generator')),
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('File Loader text', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set the text displayed while the file is uploading.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('context[loader_file]', array(
					'value' => WaicUtils::getArrayValue($context, 'loader_file', __('Uploading', 'ai-copilot-content-generator')),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-group-title">
		<?php esc_html_e('Error Message', 'ai-copilot-content-generator'); ?>
	</div>
	<div class="wbw-settings-form row wbw-settings-top">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Predefined Error Message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('When any usage limit is reached during a conversation or AI get error, the bot will send an automated message informing the user about temporary technical issues.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::textarea('context[error_message]', array(
					'value' => WaicUtils::getArrayValue($context, 'error_message', __('We’re currently experiencing temporary technical issues. Please leave your email, and we’ll get back to you as soon as possible.', 'ai-copilot-content-generator')),
					'rows' => 4,
				));
				?>
			</div>
		</div>
	</div>
<?php
$eErrorRequest = WaicUtils::getArrayValue($context, 'e_error_request', 0, 1);
$hidden = $eErrorRequest ? '' : ' wbw-hidden';
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Prompt User Email?', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('If enabled, the bot will prompt the user to enter their email when an error occurs. The provided email will be sent to the designated admin email for further follow-up.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('context[e_error_request]', array(
					'checked' => $eErrorRequest,
				));
				?>
			<div class="wbw-settings-field">
				<?php
					WaicHtml::text('context[error_admin_email]', array(
						'value' => WaicUtils::getArrayValue($context, 'error_admin_email', $adminEmail),
						'attrs' => 'class="wbw-medium-field" placeholder="' . esc_attr(__('Enter admin email', 'ai-copilot-content-generator')) . '"',
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Placeholder for email', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set placeholder text for email-field.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::text('context[plh_email]', array(
					'value' => WaicUtils::getArrayValue($context, 'plh_email', __('Your email', 'ai-copilot-content-generator')),
				));
				?>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Invalid Email Message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Thank you message.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::text('context[error_email]', array(
						'value' => WaicUtils::getArrayValue($context, 'error_email', __('Email is not correct.', 'ai-copilot-content-generator')),
					));
					?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Thank you message', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set Thank you message.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
				<?php 
					WaicHtml::text('context[error_thank]', array(
						'value' => WaicUtils::getArrayValue($context, 'thank_message', __('Thank you, our expert will contact you as soon as possible.', 'ai-copilot-content-generator')),
					));
					?>
			</div>
		</div>
	</div>
</section>