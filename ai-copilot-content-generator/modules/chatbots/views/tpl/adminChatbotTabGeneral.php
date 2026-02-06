<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$general = WaicUtils::getArrayValue($props['settings'], 'general', array(), 2);

/*$modes = array(
	'chat' => __('Chat', 'ai-copilot-content-generator'),
	//'assistant' => __('Assistant', 'ai-copilot-content-generator'),
);*/
$limitRoles = WaicUtils::getArrayValue($general, 'limit_roles', array(), 2);
$roles = WaicUtils::getAllUserRolesList();
?>
<section class="wbw-body-options">
<?php if (!empty($props['task_id'])) { ?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Shortcode', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Shortcode', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('', array(
					'value' => '[' . WAIC_CHATBOT . ' id="' . $props['task_id'] . '"]',
					'attrs' => 'readonly class="wbw-small-field wbw-shortcode-field"',
				));
			?>
			</div>
		</div>
	</div>
<?php } ?>
<?php 
$displayOn = WaicUtils::getArrayValue($general, 'display_on', 'shortcode');
$pages = array(
	'home' => __('Home', 'ai-copilot-content-generator'),
	'account' => __('Account', 'ai-copilot-content-generator'),
	'blog' => __('Blog page', 'ai-copilot-content-generator'),
	'blog_post' => __('Blog posts', 'ai-copilot-content-generator'),
	'blog_сat' => __('Blog categories', 'ai-copilot-content-generator'),
	'blog_tag' => __('Blog tags', 'ai-copilot-content-generator'),
);
if (WaicUtils::isWooCommercePluginActivated()) {
	$pages['shop'] = __('Shop', 'ai-copilot-content-generator');
	$pages['product'] = __('Product Pages', 'ai-copilot-content-generator');
	$pages['product_cat'] = __('Product categories', 'ai-copilot-content-generator');
	$pages['product_tag'] = __('Product tags', 'ai-copilot-content-generator');
	$pages['cart'] = __('Cart', 'ai-copilot-content-generator');
	$pages['checkout'] = __('Checkout', 'ai-copilot-content-generator');
}
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Display on', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Choose where the chatbot should appear on your website.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('general[display_on]', array(
					'options' => array(
						'shortcode' => __('Shortcode only', 'ai-copilot-content-generator'),
						'all' => __('All pages', 'ai-copilot-content-generator'),
						'specific' => __('Specific pages', 'ai-copilot-content-generator'),
					),
					'value' => $displayOn,
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo 'specific' == $displayOn ? '' : ' wbw-hidden'; ?>" data-parent-select="general[display_on]" data-select-value="specific">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the page groups where you want the chatbot to appear.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('general[display_pages]', array(
					'options' => $pages,
					'value' => WaicUtils::getArrayValue($general, 'display_pages'),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo 'specific' == $displayOn ? '' : ' wbw-hidden'; ?>" data-parent-select="general[display_on]" data-select-value="specific">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('You can specify the page/post/product IDs where the chatbot should be displayed. Separate them with commas.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('general[display_ids]', array(
					'value' => WaicUtils::getArrayValue($general, 'display_ids'),
				));
				?>
			</div>
		</div>
	</div>
<?php 
$hideOn = WaicUtils::getArrayValue($general, 'hide_on', 'none');
?>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Hide on', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select pages where the chatbot should be hidden.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectbox('general[hide_on]', array(
					'options' => array(
						'node' => __('None', 'ai-copilot-content-generator'),
						'specific' => __('Specific pages', 'ai-copilot-content-generator'),
					),
					'value' => $hideOn,
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo 'specific' == $hideOn ? '' : ' wbw-hidden'; ?>" data-parent-select="general[hide_on]" data-select-value="specific">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Select the page groups where the chatbot should be hidden.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::selectlist('general[hide_pages]', array(
					'options' => $pages,
					'value' => WaicUtils::getArrayValue($general, 'hide_pages'),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row<?php echo 'specific' == $hideOn ? '' : ' wbw-hidden'; ?>" data-parent-select="general[hide_on]" data-select-value="specific">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('You can specify the page/post/product IDs where the chatbot should be hidden. Separate them with commas.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::text('general[hide_ids]', array(
					'value' => WaicUtils::getArrayValue($general, 'hide_ids'),
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-separator col-12"></div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Chat Lifetime', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Defines the duration (in minutes) before the chatbot resets the conversation due to inactivity. Once exceeded, the chat history is cleared, and a new session starts.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[lifetime]', array(
					'value' => WaicUtils::getArrayValue($general, 'lifetime', 60, 1, false, true, true),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			<label class="wbw-settings-after">min</label>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Input Max Length', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Maximum number of characters that can be input by the user.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[max_input]', array(
					'value' => WaicUtils::getArrayValue($general, 'max_input', 100, 1, false, true, true),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Max Messages', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Maximum number of historical messages that is sent to the AI model.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[max_messages]', array(
					'value' => WaicUtils::getArrayValue($general, 'max_messages', 10, 1),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Context Max Length', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Limits the context length, including instructions, content-aware data, embeddings, etc., to ensure it does not exceed this number of characters.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[max_context]', array(
					'value' => WaicUtils::getArrayValue($general, 'max_context', 10000, 1, false, true, true),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Monthly Limit', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Maximum number of tokens allowed per month. Usage resets at the start of each month.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[monthly_limit]', array(
					'value' => WaicUtils::getArrayValue($general, 'monthly_limit', 1000000, 1, false, true, true),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('All-Time Limit', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Total token limit for the bot`s lifetime. Once exceeded, the bot will stop processing requests.', 'ai-copilot-content-generator'); ?>">
			<div class="wbw-settings-field">
			<?php 
				WaicHtml::number('general[alltime_limit]', array(
					'value' => WaicUtils::getArrayValue($general, 'alltime_limit', 1000000, 1, false, true, true),
					'attrs' => 'class="wbw-small-field"',
				));
				?>
			</div>
		</div>
	</div>
	<div class="wbw-settings-form row">
		<div class="wbw-settings-label col-2"><?php esc_html_e('Role-Based Limit', 'ai-copilot-content-generator'); ?></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info.png'); ?>" class="wbw-tooltip" title="<?php esc_html_e('Set token usage limits per conversation based on user roles, ensuring different access levels for roles like customers or guests. Limits apply on a monthly basis and reset at the start of each month.', 'ai-copilot-content-generator'); ?>">
			<?php
				WaicHtml::checkbox('general[e_limit_roles]', array(
					'checked' => WaicUtils::getArrayValue($general, 'e_limit_roles', 0, 1),
				));
				?>
			<button class="wbw-button wbw-button-small waic-add-list-block m-0" type="button" data-block="limit_roles"><?php esc_html_e('Add', 'ai-copilot-content-generator'); ?></button>
		</div>
	</div>
<?php 
$limitRoles = WaicUtils::getArrayValue($general, 'limit_roles', array(), 2);
$roles = WaicUtils::getAllUserRolesList();
$roles[''] = __('Guest', 'ai-copilot-content-generator');
$n = 0;
?>
	<div class="wbw-settings-form row<?php echo ( empty($limitRoles) ? ' wbw-hidden' : '' ); ?>">
		<div class="wbw-settings-label col-2"></div>
		<div class="wbw-settings-fields col-10">
			<img src="<?php echo esc_url(WAIC_IMG_PATH . '/info_leer.png'); ?>" class="wbw-tooltip">
			<div class="wbw-settings-field waic-list-blocks" data-block="limit_roles">
			<?php foreach ($limitRoles as $r) { ?>
				<div class="waic-list-block">
					<?php 
						WaicHtml::selectbox('general[limit_roles][' . $n . '][role]', array(
							'options' => $roles,
							'value' => WaicUtils::getArrayValue($r, 'role'),
							'attrs' => 'class="wbw-small-field"',
						));
						WaicHtml::number('general[limit_roles][' . $n . '][max]', array(
							'value' => WaicUtils::getArrayValue($r, 'max', '', 1, false, true, true),
							'attrs' => 'class="wbw-small-field"',
						));
						$n++;
					?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>			
			<?php } ?>
				<div class="waic-list-block wbw-hidden waic-list-blocks-tmp" data-next-n="<?php echo esc_attr($n); ?>">
					<?php 
						WaicHtml::selectbox('general[limit_roles][$n][role]', array(
							'options' => $roles,
							'attrs' => 'class="wbw-small-field wbw-nosave"',
						));
						WaicHtml::number('general[limit_roles][$n][max]', array(
							'value' => 1000000,
							'attrs' => 'class="wbw-small-field wbw-nosave"',
						));
						?>
					<a href="#" class="wbw-elem-remove"><i class="fa fa-close"></i></a>
				</div>
			</div>
		</div>
	</div>
</section>