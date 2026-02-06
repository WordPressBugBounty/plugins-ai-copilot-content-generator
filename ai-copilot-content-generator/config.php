<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
if (!defined('WPLANG') || WPLANG == '') {
	define('WAIC_WPLANG', 'en_GB');
} else {
	define('WAIC_WPLANG', WPLANG);
}

define('WAIC_DS', DIRECTORY_SEPARATOR);
define('WAIC_PLUG_NAME', basename(dirname(__FILE__)));
define('WAIC_DIR', WP_PLUGIN_DIR . WAIC_DS . WAIC_PLUG_NAME . WAIC_DS);
define('WAIC_LOG_DIR', WAIC_DIR . 'logs' . WAIC_DS);
define('WAIC_CLASSES_DIR', WAIC_DIR . 'classes' . WAIC_DS);
define('WAIC_TABLES_DIR', WAIC_CLASSES_DIR . 'tables' . WAIC_DS);
define('WAIC_HELPERS_DIR', WAIC_CLASSES_DIR . 'helpers' . WAIC_DS);
define('WAIC_LANG_DIR', WAIC_DIR . 'languages' . WAIC_DS);
define('WAIC_ASSETS_DIR', WAIC_DIR . 'common' . WAIC_DS);
define('WAIC_IMG_DIR', WAIC_ASSETS_DIR . 'img' . WAIC_DS);
define('WAIC_JS_DIR', WAIC_ASSETS_DIR . 'js' . WAIC_DS);
define('WAIC_LIB_DIR', WAIC_ASSETS_DIR . 'lib' . WAIC_DS);
define('WAIC_MODULES_DIR', WAIC_DIR . 'modules' . WAIC_DS);
define('WAIC_ADMIN_DIR', ABSPATH . 'wp-admin' . WAIC_DS);

define('WAIC_PLUGINS_URL', plugins_url());
if (!defined('WAIC_SITE_URL')) {
	define('WAIC_SITE_URL', get_bloginfo('wpurl') . '/');
}
define('WAIC_LIB_PATH', WAIC_PLUGINS_URL . '/' . WAIC_PLUG_NAME . '/common/lib/');
define('WAIC_JS_PATH', WAIC_PLUGINS_URL . '/' . WAIC_PLUG_NAME . '/common/js/');
define('WAIC_CSS_PATH', WAIC_PLUGINS_URL . '/' . WAIC_PLUG_NAME . '/common/css/');
define('WAIC_IMG_PATH', WAIC_PLUGINS_URL . '/' . WAIC_PLUG_NAME . '/common/img/');
define('WAIC_MODULES_PATH', WAIC_PLUGINS_URL . '/' . WAIC_PLUG_NAME . '/modules/');

define('WAIC_URL', WAIC_SITE_URL);

define('WAIC_LOADER_IMG', WAIC_IMG_PATH . 'loading.gif');
define('WAIC_TIME_FORMAT', 'H:i:s');
define('WAIC_DATE_DL', '/');
define('WAIC_DATE_FORMAT', 'm/d/Y');
define('WAIC_DATE_FORMAT_HIS', 'm/d/Y (' . WAIC_TIME_FORMAT . ')');
define('WAIC_DB_PREF', 'waic_');
define('WAIC_MAIN_FILE', 'ai-copilot-content-generator.php');

define('WAIC_DEFAULT', 'default');

define('WAIC_VERSION', '1.4.6');

define('WAIC_CLASS_PREFIX', 'waicc');
define('WAIC_TEST_MODE', true);

define('WAIC_ADMIN', 'admin');
define('WAIC_LOGGED', 'logged');
define('WAIC_GUEST', 'guest');

define('WAIC_METHODS', 'methods');
define('WAIC_USERLEVELS', 'userlevels');
/**
 * Framework instance code
 */
define('WAIC_CODE', 'waic');
/**
 * Plugin name
 */
define('WAIC_WP_PLUGIN_NAME', 'AI Copilot - Content Generator');
/**
 * Custom defined for plugin
 */
define('WAIC_CHATBOT', 'aiwu-chatbot');
define('WAIC_FORM', 'aiwu-form');
