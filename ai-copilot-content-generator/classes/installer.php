<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicInstaller {
	public static $update_to_version_method = '';
	private static $_firstTimeActivated = false;
	public static function init( $isUpdate = false ) {
		global $wpdb;
		$wpPrefix = $wpdb->prefix; /* add to 0.0.3 Versiom */
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$current_version = get_option($wpPrefix . WAIC_DB_PREF . 'db_version', 0);
		if (!$current_version) {
			self::$_firstTimeActivated = true;
		}
		/**
		 * Table modules 
		 */
		if (!WaicDb::exist('@__modules')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__modules` (
				`id` smallint(3) NOT NULL AUTO_INCREMENT,
				`code` varchar(32) NOT NULL,
				`active` tinyint(1) NOT NULL DEFAULT '0',
				`type_id` tinyint(1) NOT NULL DEFAULT '0',
				`label` varchar(64) DEFAULT NULL,
				`ex_plug_dir` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE INDEX `code` (`code`)
			) DEFAULT CHARSET=utf8;"));
			WaicDb::query("INSERT INTO `@__modules` (id, code, active, type_id, label) VALUES
				(NULL, 'adminmenu',1,1,'Admin Menu'),
				(NULL, 'options',1,1,'Options'),
				(NULL, 'workspace',1,1,'Workspace'),
				(NULL, 'gopro',1,1,'GoPro'),
				(NULL, 'postscreate',1,1,'PostsCreate'),
				(NULL, 'postsfields',1,1,'PostsFields'),
				(NULL, 'chatbots',1,1,'Chatbots'),
				(NULL, 'insights',1,1,'Insights'),
				(NULL, 'magictext',1,1,'Magictext'),
				(NULL, 'promo',1,1,'Promo'),
				(NULL, 'mcp',1,1,'MCP'),
				(NULL, 'forms',1,1,'Forms'),
				(NULL, 'workflow',1,1,'Workflow');");
		}
		
		/**
		 * Table workspace
		 */
		if (!WaicDb::exist('@__workspace')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__workspace` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(10) NOT NULL,
				`value` VARCHAR(128) NOT NULL,
				`flag` INT NOT NULL DEFAULT 0,
				`timeout` INT NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
			  UNIQUE INDEX `code` (`name`)
			) DEFAULT CHARSET=utf8;"));
			WaicDb::query("INSERT INTO `@__workspace` (id, name, value, flag, timeout) VALUES
				(1, 'task', 0, 0, 0),
				(2, 'flag', 0, 0, 0),
				(3, 'publish', 0, 0, 0),
				(11, 'flow', 0, 0, 0),
				(21, 'launch_bot', 0, 0, 0);");
		}
		/**
		 * Table tasks
		 */
		if (!WaicDb::exist('@__tasks')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__tasks` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`feature` VARCHAR(24) NOT NULL,
				`author` INT NOT NULL DEFAULT 0,
				`title` VARCHAR(250) DEFAULT '',
				`params` MEDIUMTEXT NOT NULL,
				`cnt` INT NOT NULL DEFAULT 0,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`recalc` TINYINT(1) NOT NULL DEFAULT 0,
				`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated` TIMESTAMP NULL,
				`start` TIMESTAMP NULL,
				`end` TIMESTAMP NULL,
				`step` INT NOT NULL DEFAULT 0,
				`steps` INT NOT NULL DEFAULT 0,
				`cycle` INT NOT NULL DEFAULT 0,
				`message` VARCHAR(250) DEFAULT '',
				`tokens` BIGINT NOT NULL DEFAULT 0,
				`mode` VARCHAR(24) DEFAULT '',
				`obj_id` BIGINT NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		
		/**
		 * Table workflows
		 */
		if (!WaicDb::exist('@__workflows')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__workflows` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`task_id` INT NOT NULL DEFAULT 0,
				`version` INT NOT NULL DEFAULT 0,
				`params` MEDIUMTEXT NOT NULL,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`tr_id` INT NOT NULL DEFAULT 0,
				`tr_code` VARCHAR(30) DEFAULT '',
				`tr_type` TINYINT(1) NOT NULL DEFAULT 0,
				`sch_start` TIMESTAMP NULL,
				`sch_period` INT NOT NULL DEFAULT 0,
				`tr_hook` VARCHAR(500) DEFAULT '',
				`timeout` INT NOT NULL,
				`flags` CHAR(10) DEFAULT '',
				`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated` TIMESTAMP NULL,
				PRIMARY KEY (`id`),
				UNIQUE INDEX `task_id` (`task_id`, `version`, `tr_id`),
				INDEX `status` (`status`, `tr_type`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		
		/**
		 * Table flowruns
		 */
		if (!WaicDb::exist('@__flowruns')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__flowruns` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`task_id` INT NOT NULL DEFAULT 0,
				`fl_id` INT NOT NULL DEFAULT 0,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`params` MEDIUMTEXT NOT NULL,
				`obj_id` BIGINT NOT NULL DEFAULT 0,
				`tokens` INT NOT NULL DEFAULT 0,
				`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`started` TIMESTAMP NULL,
				`ended` TIMESTAMP NULL,
				`log_id` INT NOT NULL DEFAULT 0,
				`waiting` INT NOT NULL DEFAULT 0,
				`error` VARCHAR(500) DEFAULT '',
				PRIMARY KEY (`id`),
				INDEX `fl_id` (`fl_id`),
				INDEX `status` (`status`, `task_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		
		/**
		 * Table flowlogs
		 */
		if (!WaicDb::exist('@__flowlogs')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__flowlogs` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`run_id` BIGINT NOT NULL DEFAULT 0,
				`bl_type` TINYINT(1) NOT NULL DEFAULT 0,
				`bl_id` INT NOT NULL DEFAULT 0,
				`bl_code` VARCHAR(30) DEFAULT '',
				`parent` INT NOT NULL DEFAULT 0,
				`step` INT NOT NULL DEFAULT 0,
				`cnt` INT NOT NULL DEFAULT 0,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`result` MEDIUMTEXT NOT NULL,
				`started` TIMESTAMP NULL,
				`ended` TIMESTAMP NULL,
				`error` VARCHAR(500) DEFAULT '',
				PRIMARY KEY (`id`),
				INDEX `run_id` (`run_id`, `bl_id`, `step`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		
		/**
		 * Table posts_create
		 */
		if (!WaicDb::exist('@__posts_create')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__posts_create` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`task_id` INT NOT NULL DEFAULT 0,
				`num` INT NOT NULL DEFAULT 0,
				`params` MEDIUMTEXT NOT NULL,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`updated` TIMESTAMP NULL,
				`start` TIMESTAMP NULL,
				`end` TIMESTAMP NULL,
				`flag` TINYINT NOT NULL DEFAULT 0,
				`step` MEDIUMINT NOT NULL DEFAULT 0,
				`steps` MEDIUMINT NOT NULL DEFAULT 0,
				`results` MEDIUMTEXT NOT NULL,
				`pub_mode` TINYINT(1) NOT NULL DEFAULT 0,
				`publish` TIMESTAMP NULL,
				`post_id` INT NOT NULL DEFAULT 0,
				`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`uniq` VARCHAR(32) NULL,
				PRIMARY KEY (`id`),
				INDEX `task_id` (`task_id`),
				INDEX `task_uniq` (`uniq`)
			) DEFAULT CHARSET=utf8mb4;"));
		} 
		/**
		 * Table history
		 */
		if (!WaicDb::exist('@__history')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__history` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`task_id` INT NOT NULL DEFAULT 0,
				`feature` VARCHAR(24) NOT NULL,
				`user_id` INT NOT NULL DEFAULT 0,
				`ip` VARCHAR(45) DEFAULT '',
				`engine` VARCHAR(64) DEFAULT '',
				`model` VARCHAR(160) DEFAULT '',
				`mode` TINYINT(1) NOT NULL DEFAULT 0,
				`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				`tokens` BIGINT NOT NULL DEFAULT 0,
				`cost` decimal(19,4),
				PRIMARY KEY (`id`),
				INDEX `task_id` (`task_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		/**
		 * Table chatlogs
		 */
		if (!WaicDb::exist('@__chatlogs')) {
			dbDelta(WaicDb::prepareQuery("CREATE TABLE IF NOT EXISTS `@__chatlogs` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`his_id` INT NOT NULL DEFAULT 0,
				`question` MEDIUMTEXT NOT NULL,
				`answer` MEDIUMTEXT NOT NULL,
				`file` MEDIUMTEXT NOT NULL,
				`status` TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				INDEX `task_id` (`his_id`)
			) DEFAULT CHARSET=utf8mb4;"));
		}
		WaicFastIndex::installSchema();
		
		WaicInstallerDbUpdater::runUpdate($current_version);
		if ($current_version && !self::$_firstTimeActivated) {
			self::setUsed();
		}
		update_option($wpPrefix . WAIC_DB_PREF . 'db_version', WAIC_VERSION);
		add_option($wpPrefix . WAIC_DB_PREF . 'db_installed', 1);
		self::setFirstActivation();
	}
	public static function setFirstActivation() {
		if (get_option(WAIC_DB_PREF . 'first_activation', false) === false) {
			update_option(WAIC_DB_PREF . 'first_activation', 1);
		}
	}
	public static function setUsed() {
		update_option(WAIC_DB_PREF . 'plug_was_used', 1);
	}
	public static function isUsed() {
		return (int) get_option(WAIC_DB_PREF . 'plug_was_used');
	}
	public static function delete() {
		global $wpdb;
		$wpPrefix = $wpdb->prefix;
		$wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . esc_sql(WAIC_DB_PREF) . 'modules`'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . esc_sql(WAIC_DB_PREF) . 'fast_index`'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach (array('history_daily', 'sessions_daily', 'pricing_versions', 'insight_events', 'conversation_outcomes', 'kb_attribution', 'mcp_audit', 'insight_state') as $table) {
			$wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . esc_sql(WAIC_DB_PREF . $table) . '`'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like('waic_fast_index_status_') . '%'
		)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		delete_option('waic_fast_index_schema_version');
		delete_option('waic_pricing');
		delete_option('waic_pricing_sync_enabled');
		delete_option('waic_pricing_auto_sync_enabled');
		delete_option('waic_pricing_display_currency');
		delete_option('waic_pricing_source');
		delete_option('waic_pricing_custom_url');
		delete_option('waic_pricing_last_sync_at');
		delete_option('waic_pricing_last_sync_success_at');
		delete_option('waic_pricing_last_sync_status');
		delete_option('waic_pricing_last_sync_message');
		delete_option('waic_pricing_last_import_at');
		delete_option('waic_pricing_migration_version');
		delete_option('waic_history_retention_days');
		delete_option('waic_daily_retention_days');
		delete_option('waic_insights_360_schema_version');
		delete_option('waic_insights_360_backfill_done');
		delete_option('waic_insights_360_backfill_cursor');
		delete_option('waic_insights_360_cache_ver');
		delete_option('waic_insights_360_last_rollup_at');
		delete_option('waic_insights_360_data_through');
		delete_option('waic_insights_analytics_enabled');
		delete_option('waic_insights_mcp_audit_source');
		delete_option('waic_model_registry_cache');
		delete_option('waic_model_registry_last_sync');
		delete_option('waic_model_registry_source_version');
		delete_option('waic_model_registry_errors');
		delete_option('waic_model_registry_custom');
		delete_option('waic_model_registry_settings');
		delete_option($wpPrefix . WAIC_DB_PREF . 'db_version');
		delete_option($wpPrefix . WAIC_DB_PREF . 'db_installed');
	}
	public static function deactivate() {
		wp_clear_scheduled_hook('waic_run_generation_task');
		wp_clear_scheduled_hook('waic_run_delayed_actions');
		wp_clear_scheduled_hook('waic_create_scheduled_flow');
		wp_clear_scheduled_hook('waic_run_workflow');
		wp_clear_scheduled_hook('waic_rollup_history_daily');
		wp_clear_scheduled_hook('waic_cleanup_history');
		wp_clear_scheduled_hook('waic_pricing_remote_sync');
		wp_clear_scheduled_hook('waic_pricing_custom_url_sync');
		wp_clear_scheduled_hook('waic_model_registry_sync');
		wp_clear_scheduled_hook(WaicFastIndexer::REBUILD_HOOK);
		WaicFrame::_()->getModule('workspace')->getModel()->setStoppingTaskGeneration();
	}
	public static function update() {
		global $wpdb;
		$wpPrefix = $wpdb->prefix;
		$currentVersion = get_option($wpPrefix . WAIC_DB_PREF . 'db_version', 0);
		if (!$currentVersion || version_compare(WAIC_VERSION, $currentVersion, '>') || !WaicInstallerDbUpdater::isInsightsPhase1SchemaInstalled() || !WaicInstallerDbUpdater::isInsights360SchemaInstalled() || !WaicFastIndex::schemaReady()) {
			if ($currentVersion && version_compare((string) $currentVersion, '1.5.4', '<')) {
				self::purgeMcpOauthTransients();
				update_option(WAIC_CODE . '_mcp_owner_binding_required', 1, false);
			}
			self::init( true );
			update_option($wpPrefix . WAIC_DB_PREF . 'db_version', WAIC_VERSION);
		}
	}
	private static function purgeMcpOauthTransients() {
		global $wpdb;
		$like = $wpdb->esc_like('_transient_aiwu_oauth_') . '%';
		$likeTimeout = $wpdb->esc_like('_transient_timeout_aiwu_oauth_') . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$like,
			$likeTimeout
		));
	}
	public static function maybeBindExistingMcpTokenToCurrentAdmin() {
		if (!get_option(WAIC_CODE . '_mcp_owner_binding_required')) {
			return;
		}
		if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
			return;
		}
		$userId = get_current_user_id();
		if (!$userId) {
			return;
		}
		$optionName = WAIC_CODE . '_options_mcp';
		$options = get_option($optionName);
		if (empty($options) || !is_array($options)) {
			$optionName = WAIC_CODE . '_options_plugin';
			$options = get_option($optionName);
		}
		if (empty($options) || !is_array($options) || empty($options['mcp_token']) || !empty($options['mcp_token_user_id'])) {
			delete_option(WAIC_CODE . '_mcp_owner_binding_required');
			return;
		}
		$options['mcp_token_user_id'] = absint($userId);
		if (update_option($optionName, $options)) {
			delete_option(WAIC_CODE . '_mcp_owner_binding_required');
		}
	}
}
