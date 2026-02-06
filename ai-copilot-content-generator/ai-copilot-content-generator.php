<?php
/**
 * Plugin Name: AI Copilot - Content Generator
 * Description: AI Copilot for WordPress saves time and boosts your website's performance with human-like content with GPT, Internal AI and more.
 * Version: 1.4.6
 * Author: AIWU
 * Author URI: https://aiwuplugin.com/
 * Text Domain: ai-copilot-content-generator
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 **/
/**
 * Base config constants and functions
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
/**
 * Connect all required core classes
 */
waicImportClass('WaicAIProviderInterface');
waicImportClass('WaicDb');
waicImportClass('WaicInstaller');
waicImportClass('WaicBaseObject');
waicImportClass('WaicModule');
waicImportClass('WaicModel');
waicImportClass('WaicView');
waicImportClass('WaicController');
waicImportClass('WaicHelper');
waicImportClass('WaicDispatcher');
waicImportClass('WaicField');
waicImportClass('WaicTable');
waicImportClass('WaicFrame');

waicImportClass('WaicReq');
waicImportClass('WaicUri');
waicImportClass('WaicHtml');
waicImportClass('WaicResponse');
waicImportClass('WaicFieldAdapter');
waicImportClass('WaicValidator');
waicImportClass('WaicErrors');
waicImportClass('WaicUtils');
waicImportClass('WaicModInstaller');
waicImportClass('WaicInstallerDbUpdater');
waicImportClass('WaicDate');
waicImportClass('WaicAssets');
waicImportClass('WaicCache');
waicImportClass('WaicUser');
waicImportClass('WaicBuilderBlock');
waicImportClass('WaicIntegration');
/**
 * Check plugin version - maybe we need to update database, and check global errors in request
 */
WaicInstaller::update();
WaicErrors::init();
/**
 * Start application
 */
WaicFrame::_()->parseRoute();
WaicFrame::_()->init();
WaicFrame::_()->exec();
