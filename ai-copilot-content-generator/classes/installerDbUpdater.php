<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInstallerDbUpdater {
	const INSIGHTS_360_SCHEMA = 2;

	public static function isInsightsPhase1SchemaInstalled() {
		return WaicDb::existsTableColumn( '@__history', 'cost_micro_usd' )
			&& WaicDb::existsTableColumn( '@__history', 'tool_calls_count' )
			&& WaicDb::exist( '@__history_daily' )
			&& WaicDb::exist( '@__sessions_daily' )
			&& WaicDb::exist( '@__pricing_versions' )
			&& false !== get_option( 'waic_pricing_display_currency', false )
			&& false !== get_option( 'waic_pricing_auto_sync_enabled', false )
			&& false !== get_option( 'waic_pricing_custom_url', false );
	}

	public static function isInsights360SchemaInstalled() {
		return WaicDb::exist( '@__insight_events' )
			&& WaicDb::exist( '@__conversation_outcomes' )
			&& WaicDb::exist( '@__kb_attribution' )
			&& WaicDb::exist( '@__mcp_audit' )
			&& WaicDb::exist( '@__insight_state' )
			&& (int) get_option( 'waic_insights_360_schema_version', 0 ) >= self::INSIGHTS_360_SCHEMA;
	}

	public static function runUpdate( $current_version ) {
		if ($current_version && version_compare($current_version, '1.1.1', '<')) {
			WaicDb::query( "ALTER TABLE `@__tasks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
			WaicDb::query( "ALTER TABLE `@__posts_create` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
		}
		
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='postsfields'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'postsfields', 1, 1, 'PostsFields');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='chatbots'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'chatbots', 1, 1, 'Chatbots');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='insights'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'insights', 1, 1, 'Insights');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='promo'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'promo', 1, 1, 'Promo');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='magictext'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'magictext', 1, 1, 'Magictext');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='mcp'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'mcp', 1, 1, 'MCP');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='forms'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'forms', 1, 1, 'Forms');" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__modules` WHERE code='workflow'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES (NULL, 'workflow', 1, 1, 'Workflow');" );
		}
		if ( ! WaicDb::existsTableColumn( '@__workspace', 'flag' ) ) {
			WaicDb::query( 'ALTER TABLE `@__workspace` ADD COLUMN `flag` INT NOT NULL DEFAULT 0 AFTER `value`' );
			WaicDb::query( "ALTER TABLE `@__workspace` ADD COLUMN `timeout` INT NOT NULL DEFAULT 0 AFTER `flag`" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__workspace` WHERE name='flow'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__workspace` (id, name, value, flag, timeout) VALUES (11, 'flow', 0, 0, 0);" );
		}
		if ( WaicDb::get( "SELECT 1 FROM `@__workspace` WHERE name='launch_bot'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__workspace` (id, name, value, flag, timeout) VALUES (21, 'launch_bot', 0, 0, 0);" );
		}
		if ( ! WaicDb::existsTableColumn( '@__tasks', 'cycle' ) ) {
			WaicDb::query( 'ALTER TABLE `@__tasks` ADD COLUMN `cycle` INT NOT NULL DEFAULT 0 AFTER `steps`' );
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `message` VARCHAR(250) DEFAULT '' AFTER `cycle`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__tasks', 'title' ) ) {
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `title` VARCHAR(250) DEFAULT '' AFTER `author`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__tasks', 'tokens' ) ) {
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `tokens` BIGINT NOT NULL DEFAULT 0 AFTER `message`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__tasks', 'mode' ) ) {
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `mode` VARCHAR(24) DEFAULT '' AFTER `tokens`" );
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `obj_id` BIGINT NOT NULL DEFAULT 0 AFTER `mode`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__tasks', 'recalc' ) ) {
			WaicDb::query( "ALTER TABLE `@__tasks` ADD COLUMN `recalc` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`" );
		}
		
		if ( ! WaicDb::existsTableColumn( '@__posts_create', 'added' ) ) {
			WaicDb::query( 'ALTER TABLE `@__posts_create` ADD COLUMN `added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `post_id`' );
			WaicDb::query( "ALTER TABLE `@__posts_create` ADD COLUMN `uniq` VARCHAR(32) NULL AFTER `added`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__history', 'feature' ) ) {
			WaicDb::query( "ALTER TABLE `@__history` ADD COLUMN `feature` VARCHAR(24) NOT NULL AFTER `task_id`" );
		}
		if ( ! WaicDb::existsTableColumn( '@__history', 'engine' ) ) {
			WaicDb::query( "ALTER TABLE `@__history` ADD COLUMN `engine` VARCHAR(20) DEFAULT '' AFTER `ip`" );
		}
		self::ensureColumnDefinition( '@__history', 'ip', "ALTER TABLE `@__history` MODIFY COLUMN `ip` VARCHAR(45) DEFAULT ''" );
		if ( WaicDb::get( "SELECT 1 FROM `@__tasks` WHERE feature='magictext'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, author, status) VALUES (NULL, 'magictext', 'Magic Text', 0, 4);");
		}
		if ( ! WaicDb::existsTableColumn( '@__chatlogs', 'status' ) ) {
			WaicDb::query( "ALTER TABLE `@__chatlogs` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 0 AFTER `file`" );
		}
		
		if ( WaicDb::get( "SELECT 1 FROM `@__tasks` WHERE feature='template'", 'one' ) != 1 ) {
			$json = '{"nodes":[{"id":"1","type":"trigger","position":{"x":350,"y":200},"data":{"dragged":true,"type":"trigger","category":"wp","error":false,"code":"wp_user_register","label":"New user registered","settings":{"login":"","name":"","email":"","role":"","capability":""}}},{"id":"2","type":"logic","position":{"x":535,"y":200},"data":{"dragged":true,"type":"logic","category":"un","code":"un_branch","label":"Branch","settings":{"name":"IF","criteria":"{{node#1.display_name}}","operator":"is_known"},"error":false}},{"id":"3","type":"action","position":{"x":719.11372505269,"y":99.933325895769},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"{{node#1.user_email}}","from":"max@aiwuplugin.com","from_name":"Max from AIWU","subject":"Welcome to our WordPress community!","body":"Hey {{node#1.display_name}}!\\nThanks for joining! We’re really happy to have you on board.\\nYour account is ready — you can log in anytime and start exploring.\\n\\nIf you have any questions, just reply to this email — we’re here to help.\\nSee you around!\\n\\n— The AIWU Team"}}},{"id":"4","type":"action","position":{"x":724.9120303178,"y":286.61233136753},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"{{node#1.user_email}}","from":"max@aiwuplugin.com","from_name":"Max form AIWU","subject":"Welcome to our WordPress community!","body":"Hey there!\\nThanks for joining! We’re really happy to have you on board.\\nYour account is ready — you can log in anytime and start exploring.\\n\\nIf you have any questions, just reply to this email — we’re here to help.\\nSee you around!\\n\\n— The AIWU Team"}}}],"edges":[{"id":"1","source":"1","target":"2","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"2","source":"2","target":"3","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"3","source":"2","target":"4","sourceHandle":"output-else","targetHandle":"input-left","type":"default"}],"viewport":{"x":-70.95266505528,"y":-38.855946890096,"zoom":1.1892071068141},"settings":"","version":"1.0.0"}';
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, mode, message, params) VALUES (NULL, 'template', 'Personalized Welcome Emails', 1, 'Automatically send personalized welcome emails to new users when they register on your WordPress site.','" . addslashes($json) . "');");
			$json = '{"nodes":[{"id":"1","type":"trigger","position":{"x":350,"y":200},"data":{"dragged":true,"type":"trigger","category":"sy","code":"sy_manual","label":"Manually","settings":[],"error":false}},{"id":"2","type":"logic","position":{"x":535,"y":200},"data":{"dragged":true,"type":"logic","category":"lp","error":false,"code":"lp_posts","label":"Search Posts","settings":{"name":"","ids":"","title":"","body":"","date_mode":"","categories":"","tags":"","status":"publish","author":""}}},{"id":"3","type":"logic","position":{"x":709,"y":134},"data":{"dragged":true,"type":"logic","category":"un","code":"un_branch","label":"Branch","settings":{"name":"IF","criteria":"{{node#2.post_excerpt}}","operator":"is_unknown"},"error":false}},{"id":"4","type":"action","position":{"x":890,"y":46},"data":{"dragged":true,"type":"action","category":"ai","error":false,"code":"ai_generate_text","label":"Generate Text","settings":{"name":"Open AI - Generate Text","model":"gpt-4o","tokens":"4096","temperature":"0.7","prompt":"Write a short excerpt up to 130 character based on the post title: {{node#2.post_title}}"}}},{"id":"5","type":"action","position":{"x":1090,"y":45.253725775965},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_update_post","label":"Update Post","settings":{"id":"{{node#2.post_ID}}","title":"","body":"","excerpt":"{{node#4.content}}","status":"","author":""}}},{"id":"6","type":"action","position":{"x":726.50732078504,"y":322.88980344863},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"max@aiwuplugin.com","from":"max@aiwuplugin.com","from_name":"Max","subject":"Workflow completed","body":"Hi Max\\n\\nThe meta description generation workflow is complete - please check it out.\\n\\nThanks"}}}],"edges":[{"id":"1","source":"1","target":"2","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"2","source":"2","target":"3","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"3","source":"3","target":"4","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"4","source":"4","target":"5","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"5","source":"2","target":"6","sourceHandle":"output-else","targetHandle":"input-left","type":"default"}],"viewport":{"x":176.23629455824,"y":70.813761773201,"zoom":0.76962215630794},"settings":"","version":"1.0.0"}';
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, mode, message, params) VALUES (NULL, 'template', 'Generate Missing Post Excerpts', 2, 'This workflow scans your content, identifies posts without excerpts, and uses AI to create engaging 150-character summaries based on post titles.','" . addslashes($json) . "');");
			$json = '{"nodes":[{"id":"1","type":"trigger","position":{"x":350,"y":200},"data":{"dragged":true,"type":"trigger","category":"sy","code":"sy_manual","label":"Manually","settings":[],"error":false}},{"id":"2","type":"logic","position":{"x":535,"y":200},"data":{"dragged":true,"type":"logic","category":"lp","error":false,"code":"lp_posts","label":"Search Posts","settings":{"name":"","ids":"","title":"","body":"","date_mode":"","categories":"","tags":"","status":"publish","author":""}}},{"id":"3","type":"logic","position":{"x":714.93187267115,"y":123},"data":{"dragged":true,"type":"logic","category":"un","code":"un_branch","label":"Branch","settings":{"name":"IF","criteria":"{{node#2.post_image}}","operator":"is_unknown"},"error":false}},{"id":"4","type":"action","position":{"x":896,"y":69.666666666667},"data":{"dragged":true,"type":"action","category":"ai","error":false,"code":"ai_generate_image","label":"Open AI Generate Image","settings":{"name":"Generate Image","model":"dall-e-3","orientation":"horizontal","prompt":"Create a high-quality featured image for the article that visually interprets its central themes and ideas, based on the provided details: \\n\\n- Article Title: {{node#2.post_title}}\\n\\nThe image should embody the main concepts and mood of the article without including any text, ensuring it complements the content effectively. This visual representation should enhance the article appeal and provide deeper insight into its themes."}}},{"id":"6","type":"action","position":{"x":1269.3333333333,"y":68.333333333333},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_update_post_image","label":"Update Post Featured image","settings":{"id":"{{node#2.post_ID}}","image":"{{node#4.image_id}}","alt":"{{node#8.content}}"}}},{"id":"8","type":"action","position":{"x":1089.3333333333,"y":69},"data":{"dragged":true,"type":"action","category":"ai","error":false,"code":"ai_generate_text","label":"Open AI Generate Text","settings":{"name":"Generate Alt Text","model":"gpt-4o","tokens":"300","temperature":0.7,"prompt":"We created an image for an article about the topic &quot;{{node#2.post_title}}&quot;. \\n\\nPlease generate an Alt text for this image. \\nThe Alt text must be between 10-70 characters long. Your response should contain only the Alt text, with no additional text or explanations."}}}],"edges":[{"id":"1","source":"1","target":"2","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"2","source":"2","target":"3","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"3","source":"3","target":"4","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"6","source":"4","target":"8","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"7","source":"8","target":"6","sourceHandle":"output-right","targetHandle":"input-left","type":"default"}],"viewport":{"x":-961.82371544285,"y":65.956273933069,"zoom":1.5},"settings":"","version":"1.0.0"}';
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, mode, message, params) VALUES (NULL, 'template', 'Generate Missing Featured Images', 3, 'Automatically generate AI-powered featured images and alt text for all posts missing visuals. Perfect for bulk content optimization.','" . addslashes($json) . "');");
			$json = '{"nodes":[{"id":"1","type":"trigger","position":{"x":332,"y":198},"data":{"dragged":true,"type":"trigger","category":"wc","error":false,"code":"wc_new_order","label":"New Order Created","settings":{"status":"","total":"","customer":"","products":"","categories":"","tags":""}}},{"id":"7","type":"logic","position":{"x":628.33333333333,"y":196},"data":{"dragged":true,"type":"logic","category":"un","code":"un_branch","label":"Branch","settings":{"name":"is not completed","criteria":"{{node#1.order_status}}","operator":"does_not_equal","value":"Completed","compare":"text"},"error":false}},{"id":"8","type":"logic","position":{"x":491,"y":198.66666666667},"data":{"dragged":true,"type":"logic","category":"un","code":"un_delay","label":"Delay","settings":{"mode":"amount","days":"","hours":"1","minutes":"0"},"error":false}},{"id":"9","type":"action","position":{"x":782,"y":126.66666666667},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"{{node#1.user_email}}","from":"max@aiwuplugin.com","from_name":"Max from AIWU","subject":"You left something in your cart 🛒","body":"Hey {{node#1.display_name}}! 👋\\n\\nWe noticed you didn&#039;t finish your order. Your cart is waiting for you:\\n\\n{{node#1.order_products}}\\n\\nTotal: {{node#1.order_total}}\\n\\nThese items are popular and might sell out soon! \\n\\n👉 Complete your order now:\\nhttps://aiwuplugin.com/cart\\n\\nQuestions? Just hit reply – we&#039;re here to help!\\n\\nCheers,\\nMax"}}},{"id":"10","type":"logic","position":{"x":922.91879759531,"y":125.17125070133},"data":{"dragged":true,"type":"logic","category":"un","code":"un_delay","label":"Delay","settings":{"mode":"amount","days":"1","hours":"0","minutes":"0"},"error":false}},{"id":"12","type":"logic","position":{"x":1092.5480638794,"y":124.5582176804},"data":{"dragged":true,"type":"logic","category":"un","code":"un_branch","label":"Branch","settings":{"name":"Still Pending Payment","criteria":"","operator":"equals","value":"","compare":"text"},"error":false}},{"id":"18","type":"action","position":{"x":1257.6187652117,"y":26.537738448272},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"{{node#1.user_email}}","from":"max@aiwuplugin.com","from_name":"Max from AIWU","subject":"Reminder: You left something in your cart 🛒","body":"Hi {{node#1.display_name}}, still thinking about it? 🤔\\n\\nYour cart is still here, and honestly – we really think you&#039;ll love these items.\\n\\nSo here&#039;s a little nudge: 20% OFF just for you.\\n\\n{{node#1.order_products}}\\n\\nTotal: {{node#1.order_total}}\\n\\n💰 Use code: AIWU20 at checkout\\n\\nThis deal expires in 24 hours, so don&#039;t wait too long!\\n\\nComplete your order:\\nhttps://aiwuplugin.com/cart\\n\\nAny questions? Hit reply.\\n\\nMax\\nAIWU Team"}}}],"edges":[{"id":"6","source":"1","target":"8","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"7","source":"8","target":"7","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"8","source":"7","target":"9","sourceHandle":"output-then","targetHandle":"input-left","type":"default"},{"id":"9","source":"9","target":"10","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"10","source":"10","target":"12","sourceHandle":"output-right","targetHandle":"input-left","type":"default"},{"id":"11","source":"12","target":"18","sourceHandle":"output-then","targetHandle":"input-left","type":"default"}],"viewport":{"x":-311.49041249201,"y":8.5837701174823,"zoom":1.25},"settings":"","version":"1.0.0"}';
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, mode, message, params) VALUES (NULL, 'template', 'Abandoned Cart Reminder Emails', 4, 'Recover lost sales automatically! Send timed cart reminders with discount offers to customers who abandon checkout.','" . addslashes($json) . "');");
			$json = '{"nodes":[{"id":"1","type":"trigger","position":{"x":350,"y":200},"data":{"dragged":true,"type":"trigger","category":"wc","error":false,"code":"wc_new_order","label":"New Order Created","settings":{"status":["completed"],"total":"","customer":"first","products":"","categories":"","tags":""}}},{"id":"3","type":"action","position":{"x":530,"y":200},"data":{"dragged":true,"type":"action","category":"wp","error":false,"code":"wp_send_email","label":"Send Email","settings":{"to":"{{node#1.user_email}}","from":"max@aiwuplugin.com","from_name":"Max from AIWU","subject":"Thank you for your first order! 🎉","body":"Hey {{node#1.billing_first_name}} 👋\\n\\nWe&#039;re absolutely thrilled to have you as a customer! Your order has been confirmed and is on its way.\\n\\n🎁 As a thank you for choosing us, here&#039;s a special gift: use code WELCOME15 on your next purchase for 15% off!\\n\\n📦 Order Details:\\n{{node#1.order_ID}}\\n\\nBest Regards,\\nAdmin"}}}],"edges":[{"id":"2","source":"1","target":"3","sourceHandle":"output-right","targetHandle":"input-left","type":"default"}],"viewport":{"x":-99.940278981879,"y":-73.739280322291,"zoom":1.5},"settings":"","version":"1.0.0"}';
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, mode, message, params) VALUES (NULL, 'template', 'Thank You Email – First Time Customer', 5, 'Automate thank you emails for first-time WooCommerce customers. Include order details and welcome discount to boost repeat purchases.','" . addslashes($json) . "');");
		}

		self::ensureInsightsPhase1Schema();
		self::ensureInsights360Schema();
	}

	private static function ensureInsightsPhase1Schema() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		self::ensureHistoryColumn( 'operation', "`operation` VARCHAR(24) NOT NULL DEFAULT 'chat' AFTER `feature`" );
		self::ensureHistoryColumn( 'session_id', "`session_id` VARCHAR(64) NULL DEFAULT NULL AFTER `user_id`" );
		self::ensureHistoryColumn( 'duration_ms', "`duration_ms` INT UNSIGNED NULL DEFAULT NULL AFTER `status`" );
		self::ensureHistoryColumn( 'input_tokens', "`input_tokens` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `tokens`" );
		self::ensureHistoryColumn( 'output_tokens', "`output_tokens` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `input_tokens`" );
		self::ensureHistoryColumn( 'reasoning_tokens', "`reasoning_tokens` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `output_tokens`" );
		self::ensureHistoryColumn( 'cached_tokens', "`cached_tokens` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `reasoning_tokens`" );
		self::ensureHistoryColumn( 'cache_write_tokens', "`cache_write_tokens` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `cached_tokens`" );
		self::ensureHistoryColumn( 'cost_micro_usd', "`cost_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `cost`" );
		self::ensureHistoryColumn( 'est_flags', "`est_flags` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `cost_micro_usd`" );
		self::ensureHistoryColumn( 'meta', "`meta` TEXT NULL AFTER `est_flags`" );
		self::ensureHistoryColumn( 'best_score_x1000', "`best_score_x1000` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `meta`" );
		self::ensureHistoryColumn( 'tool_calls_count', "`tool_calls_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `best_score_x1000`" );
		self::ensureColumnDefinition( '@__history', 'model', 'ALTER TABLE `@__history` MODIFY COLUMN `model` VARCHAR(160) DEFAULT \'\'' );

		self::ensureIndex( '@__history', 'idx_created_feature', 'ALTER TABLE `@__history` ADD INDEX `idx_created_feature` (`created`, `feature`)' );
		self::ensureIndex( '@__history', 'idx_operation', 'ALTER TABLE `@__history` ADD INDEX `idx_operation` (`operation`)' );
		self::ensureIndex( '@__history', 'idx_session', 'ALTER TABLE `@__history` ADD INDEX `idx_session` (`session_id`)' );
		self::ensureIndex( '@__history', 'idx_engine_model', 'ALTER TABLE `@__history` ADD INDEX `idx_engine_model` (`engine`, `model`)' );
		self::ensureIndex( '@__history', 'idx_best_score', 'ALTER TABLE `@__history` ADD INDEX `idx_best_score` (`best_score_x1000`)' );

		if ( ! WaicDb::exist( '@__history_daily' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__history_daily` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`day` DATE NOT NULL,
				`feature` VARCHAR(24) NOT NULL,
				`operation` VARCHAR(24) NOT NULL,
				`engine` VARCHAR(20) NOT NULL,
				`model` VARCHAR(160) NOT NULL,
				`mode` TINYINT(1) NOT NULL DEFAULT 0,
				`events_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`errors_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`tokens_est_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`cost_est_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`aborted_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`low_conf_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`tool_calls_steps_sum` INT UNSIGNED NOT NULL DEFAULT 0,
				`input_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`output_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`reasoning_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`cached_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`cache_write_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`total_tokens` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`cost_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`cost` DECIMAL(14,6) NOT NULL DEFAULT 0,
				`duration_ms_sum` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_day_dims` (`day`, `feature`, `operation`, `engine`, `model`, `mode`),
				KEY `idx_day` (`day`),
				KEY `idx_feature_day` (`feature`, `day`),
				KEY `idx_engine_model_day` (`engine`, `model`, `day`)
			) DEFAULT CHARSET=utf8mb4;"));
		} else {
			self::ensureColumnDefinition( '@__history_daily', 'model', 'ALTER TABLE `@__history_daily` MODIFY COLUMN `model` VARCHAR(160) NOT NULL' );
		}

		if ( ! WaicDb::exist( '@__sessions_daily' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__sessions_daily` (
				`day` DATE NOT NULL,
				`feature` VARCHAR(24) NOT NULL,
				`session_id` VARCHAR(64) NOT NULL,
				`first_seen` DATETIME NOT NULL,
				`last_seen` DATETIME NOT NULL,
				`messages_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`cost_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`cost` DECIMAL(14,6) NOT NULL DEFAULT 0,
				PRIMARY KEY (`day`, `feature`, `session_id`),
				KEY `idx_day_feature` (`day`, `feature`),
				KEY `idx_session` (`session_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		if ( ! WaicDb::exist( '@__pricing_versions' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__pricing_versions` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`version` INT UNSIGNED NOT NULL,
				`applied_at` DATETIME NOT NULL,
				`source` VARCHAR(32) NOT NULL,
				`snapshot` LONGTEXT NOT NULL,
				`diff_summary` TEXT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_version` (`version`),
				KEY `idx_applied` (`applied_at`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		if ( WaicDb::get( "SELECT 1 FROM `@__tasks` WHERE feature='system'", 'one' ) != 1 ) {
			WaicDb::query( "INSERT INTO `@__tasks` (id, feature, title, author, status, message) VALUES (NULL, 'system', 'System Operations', 0, 4, 'System Operations');" );
		}

		$legacy_sync_enabled = (int) get_option( 'waic_pricing_sync_enabled', 0 );
		self::ensureOption( 'waic_pricing_sync_enabled', 0 );
		self::ensureOption( 'waic_pricing_auto_sync_enabled', 0 );
		self::ensureOption( 'waic_pricing_display_currency', 'USD' );
		self::ensureOption( 'waic_pricing_source', 'bundled' );
		self::ensureOption( 'waic_pricing_custom_url', '' );
		self::ensureOption( 'waic_pricing_last_sync_at', '' );
		self::ensureOption( 'waic_pricing_last_sync_success_at', '' );
		self::ensureOption( 'waic_pricing_last_sync_status', 'not_run' );
		self::ensureOption( 'waic_pricing_last_sync_message', esc_html__( 'Pricing sync has not run yet.', 'ai-copilot-content-generator' ) );
		self::ensureOption( 'waic_pricing_last_import_at', '' );
		self::ensureOption( 'waic_pricing_migration_version', 0 );
		self::migratePricingOptions( $legacy_sync_enabled );

		if ( false === get_option( 'waic_history_retention_days', false ) ) {
			add_option( 'waic_history_retention_days', 30, '', false );
		}
		if ( false === get_option( 'waic_daily_retention_days', false ) ) {
			add_option( 'waic_daily_retention_days', 0, '', false );
		}

		self::ensureBundledPricing();
	}

	private static function ensureInsights360Schema() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( ! WaicDb::exist( '@__insight_events' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__insight_events` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`his_id` BIGINT UNSIGNED NOT NULL,
				`created` DATETIME NOT NULL,
				`day` DATE NOT NULL,
				`feature` VARCHAR(24) NOT NULL DEFAULT '',
				`operation` VARCHAR(24) NOT NULL DEFAULT '',
				`task_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`session_id` VARCHAR(64) NOT NULL DEFAULT '',
				`engine` VARCHAR(20) NOT NULL DEFAULT '',
				`model` VARCHAR(160) NOT NULL DEFAULT '',
				`mode` TINYINT(1) NOT NULL DEFAULT 0,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`best_score_x1000` SMALLINT UNSIGNED NULL,
				`tool_calls_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				`cost_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`est_flags` INT UNSIGNED NOT NULL DEFAULT 0,
				`signal_mask` INT UNSIGNED NOT NULL DEFAULT 0,
				`problem_code` VARCHAR(32) NOT NULL DEFAULT '',
				`kb_used` TINYINT(1) NOT NULL DEFAULT 0,
				`commerce_flag` TINYINT(1) NOT NULL DEFAULT 0,
				`cluster_hash` CHAR(40) NOT NULL DEFAULT '',
				`updated_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_his` (`his_id`),
				KEY `idx_day` (`day`),
				KEY `idx_feature_day` (`feature`, `day`),
				KEY `idx_task_day` (`task_id`, `day`),
				KEY `idx_problem_day` (`problem_code`, `day`),
				KEY `idx_cluster` (`cluster_hash`),
				KEY `idx_session` (`session_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		if ( ! WaicDb::exist( '@__conversation_outcomes' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__conversation_outcomes` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`feature` VARCHAR(24) NOT NULL DEFAULT '',
				`session_id` VARCHAR(64) NOT NULL DEFAULT '',
				`task_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`first_seen` DATETIME NOT NULL,
				`last_seen` DATETIME NOT NULL,
				`day` DATE NOT NULL,
				`messages_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`events_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`outcome` VARCHAR(20) NOT NULL DEFAULT 'unknown',
				`severity` TINYINT(1) NOT NULL DEFAULT 0,
				`signal_mask` INT UNSIGNED NOT NULL DEFAULT 0,
				`reask_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				`min_best_score_x1000` SMALLINT UNSIGNED NULL,
				`had_error` TINYINT(1) NOT NULL DEFAULT 0,
				`had_handoff` TINYINT(1) NOT NULL DEFAULT 0,
				`had_commerce` TINYINT(1) NOT NULL DEFAULT 0,
				`cost_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`wasted_micro_usd` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`updated_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_conv` (`feature`, `session_id`),
				KEY `idx_day` (`day`),
				KEY `idx_outcome_day` (`outcome`, `day`),
				KEY `idx_task_day` (`task_id`, `day`),
				KEY `idx_severity` (`severity`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		if ( ! WaicDb::exist( '@__kb_attribution' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__kb_attribution` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`day` DATE NOT NULL,
				`object_type` VARCHAR(24) NOT NULL DEFAULT 'kb',
				`object_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`chunk_ref` VARCHAR(64) NOT NULL DEFAULT '',
				`task_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`retrieved_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`used_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`reask_after_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`resolved_after_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`score_sum_x1000` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`score_samples` INT UNSIGNED NOT NULL DEFAULT 0,
				`health` VARCHAR(16) NOT NULL DEFAULT 'unknown',
				`updated_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_obj_day` (`object_type`, `object_id`, `chunk_ref`, `day`),
				KEY `idx_day` (`day`),
				KEY `idx_object` (`object_type`, `object_id`),
				KEY `idx_health` (`health`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		self::ensureKbAttributionChunkIndex();

		if ( ! WaicDb::exist( '@__mcp_audit' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__mcp_audit` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`created` DATETIME NOT NULL,
				`day` DATE NOT NULL,
				`his_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`client_hash` CHAR(40) NOT NULL DEFAULT '',
				`client_label` VARCHAR(64) NOT NULL DEFAULT '',
				`tool` VARCHAR(96) NOT NULL DEFAULT '',
				`tool_class` VARCHAR(16) NOT NULL DEFAULT 'read',
				`target_type` VARCHAR(24) NOT NULL DEFAULT '',
				`target_id` VARCHAR(64) NOT NULL DEFAULT '',
				`args_summary` VARCHAR(255) NOT NULL DEFAULT '',
				`status` TINYINT(1) NOT NULL DEFAULT 1,
				`acting_user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`duration_ms` INT UNSIGNED NOT NULL DEFAULT 0,
				`risk` VARCHAR(8) NOT NULL DEFAULT 'low',
				`updated_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_day` (`day`),
				KEY `idx_class_day` (`tool_class`, `day`),
				KEY `idx_risk_day` (`risk`, `day`),
				KEY `idx_client` (`client_hash`),
				KEY `idx_tool` (`tool`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		if ( ! WaicDb::exist( '@__insight_state' ) ) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__insight_state` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`entity_type` VARCHAR(16) NOT NULL DEFAULT 'cluster',
				`entity_hash` CHAR(40) NOT NULL DEFAULT '',
				`state` VARCHAR(16) NOT NULL DEFAULT 'new',
				`note` VARCHAR(255) NOT NULL DEFAULT '',
				`updated_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`updated_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_entity` (`entity_type`, `entity_hash`),
				KEY `idx_state` (`state`)
			) DEFAULT CHARSET=utf8mb4;"));
		}

		update_option( 'waic_insights_360_schema_version', self::INSIGHTS_360_SCHEMA, false );
		self::ensureOption( 'waic_insights_360_backfill_done', 0 );
		self::ensureOption( 'waic_insights_360_backfill_cursor', 0 );
		self::ensureOption( 'waic_insights_360_cache_ver', 1 );
		self::ensureOption( 'waic_insights_360_last_rollup_at', '' );
		self::ensureOption( 'waic_insights_360_data_through', '' );
		self::ensureOption( 'waic_insights_analytics_enabled', 1 );
		self::ensureOption( 'waic_insights_mcp_audit_source', 'auto' );
	}

	private static function ensureHistoryColumn( $column, $definition ) {
		if ( ! WaicDb::existsTableColumn( '@__history', $column ) ) {
			WaicDb::query( 'ALTER TABLE `@__history` ADD COLUMN ' . $definition );
		}
	}

	private static function ensureIndex( $table, $index, $sql ) {
		$tableName = WaicDb::controlTableName( $table );
		$exists = WaicDb::get( "SHOW INDEX FROM `{$tableName}` WHERE Key_name=%s", 'one', ARRAY_A, array( $index ) );
		if ( empty( $exists ) ) {
			WaicDb::query( $sql );
		}
	}

	private static function ensureKbAttributionChunkIndex() {
		if ( ! WaicDb::exist( '@__kb_attribution' ) ) {
			return;
		}
		$tableName = WaicDb::controlTableName( '@__kb_attribution' );
		$columns = WaicDb::get(
			"SHOW INDEX FROM `{$tableName}` WHERE Key_name='uq_obj_day'",
			'all',
			ARRAY_A
		);
		$columns = (array) $columns;
		usort(
			$columns,
			function ( $left, $right ) {
				return (int) $left['Seq_in_index'] - (int) $right['Seq_in_index'];
			}
		);
		$names = array();
		foreach ( (array) $columns as $column ) {
			if ( ! empty( $column['Column_name'] ) ) {
				$names[] = (string) $column['Column_name'];
			}
		}
		if ( array( 'object_type', 'object_id', 'chunk_ref', 'day' ) === $names ) {
			return;
		}
		if ( ! empty( $columns ) ) {
			WaicDb::query( "ALTER TABLE `{$tableName}` DROP INDEX `uq_obj_day`" );
		}
		WaicDb::query( "ALTER TABLE `{$tableName}` ADD UNIQUE KEY `uq_obj_day` (`object_type`, `object_id`, `chunk_ref`, `day`)" );
	}

	private static function ensureColumnDefinition( $table, $column, $sql ) {
		if ( WaicDb::existsTableColumn( $table, $column ) ) {
			WaicDb::query( $sql );
		}
	}

	private static function ensureOption( $option, $default ) {
		if ( false === get_option( $option, false ) ) {
			add_option( $option, $default, '', false );
		}
	}

	private static function ensureBundledPricing() {
		$path = WAIC_MODULES_DIR . 'insights' . WAIC_DS . 'data' . WAIC_DS . 'pricing-bundled.json';
		if ( ! is_readable( $path ) ) {
			return;
		}

		$raw = file_get_contents( $path );
		$data = json_decode( $raw, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}
		$current = get_option( 'waic_pricing', false );
		if ( is_array( $current ) ) {
			$current_source = isset( $current['source'] ) ? sanitize_key( $current['source'] ) : 'bundled';
			$current_version = isset( $current['version'] ) ? (int) $current['version'] : 0;
			$bundled_version = isset( $data['version'] ) ? (int) $data['version'] : 0;
			if ( in_array( $current_source, array( 'imported', 'custom_url' ), true ) || $current_version >= $bundled_version ) {
				return;
			}
		}

		update_option( 'waic_pricing', $data, false );
		update_option( 'waic_pricing_source', 'bundled', false );
		if ( WaicDb::exist( '@__pricing_versions' ) && isset( $data['version'] ) ) {
			$version = (int) $data['version'];
			if ( WaicDb::get( 'SELECT 1 FROM `@__pricing_versions` WHERE version=%d', 'one', ARRAY_A, array( $version ) ) != 1 ) {
				global $wpdb;
				$wpdb->insert(
					$wpdb->prefix . WAIC_DB_PREF . 'pricing_versions',
					array(
						'version'      => $version,
						'applied_at'   => gmdate( 'Y-m-d H:i:s' ),
						'source'       => 'bundled',
						'snapshot'     => wp_json_encode( $data ),
						'diff_summary' => 'Initial bundled pricing snapshot.',
					),
					array( '%d', '%s', '%s', '%s', '%s' )
				);
			}
		}
	}

	private static function migratePricingOptions( $legacy_sync_enabled ) {
		if ( (int) get_option( 'waic_pricing_migration_version', 0 ) >= 1 ) {
			return;
		}
		$source = sanitize_key( (string) get_option( 'waic_pricing_source', 'bundled' ) );
		if ( 'remote' === $source ) {
			$current = get_option( 'waic_pricing', array() );
			if ( is_array( $current ) ) {
				$current['source'] = 'imported';
				update_option( 'waic_pricing', $current, false );
				update_option( 'waic_pricing_source', 'imported', false );
			} else {
				update_option( 'waic_pricing_source', 'bundled', false );
			}
			if ( $legacy_sync_enabled ) {
				update_option( 'waic_pricing_last_sync_status', 'disabled', false );
				update_option( 'waic_pricing_last_sync_message', esc_html__( 'Previous vendor pricing sync was disabled. Last valid pricing remains active.', 'ai-copilot-content-generator' ), false );
			}
		} elseif ( ! in_array( $source, array( 'bundled', 'imported', 'custom_url' ), true ) ) {
			update_option( 'waic_pricing_source', 'bundled', false );
		}
		update_option( 'waic_pricing_auto_sync_enabled', 0, false );
		update_option( 'waic_pricing_sync_enabled', 0, false );
		update_option( 'waic_pricing_migration_version', 1, false );
	}
}
