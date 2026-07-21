<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class WaicWorkflowPhase1Bootstrap {
	const VERSION = '1.0.0-owner-authorized-default-off';

	private static $loaded = false;
	private static $registered = false;

	public static function load() {
		if ( self::$loaded ) {
			return;
		}
		$files = array(
			'class-config.php',
			'class-result.php',
			'class-json.php',
			'class-canonicalizer.php',
			'class-archive.php',
			'class-descriptor-registry.php',
			'class-account-registry.php',
			'class-validator.php',
			'class-tokens.php',
			'class-storage.php',
			'class-staging.php',
			'class-rate-limiter.php',
			'class-pack-service.php',
			'class-rest-controller.php',
			'class-privacy.php',
			'class-ai.php',
			'class-admin.php',
		);
		foreach ( $files as $file ) {
			require_once __DIR__ . '/' . $file;
		}
		self::$loaded = true;
	}

	public static function register() {
		self::load();
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;
		add_action( 'rest_api_init', array( 'WaicWorkflowPhase1RestController', 'registerRoutes' ) );
		add_action( 'admin_init', array( 'WaicWorkflowPhase1Storage', 'maybeInstall' ) );
		add_action( 'admin_init', array( 'WaicWorkflowPhase1Config', 'ensureCapabilities' ) );
		add_action( 'waic_phase0_run_workflow', array( 'WaicWorkflowPhase1Storage', 'boundedCleanup' ), 50 );
		add_filter( 'wp_privacy_personal_data_exporters', array( 'WaicWorkflowPhase1Privacy', 'registerExporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( 'WaicWorkflowPhase1Privacy', 'registerEraser' ) );
		add_action( 'admin_init', array( 'WaicWorkflowPhase1Privacy', 'addPolicyText' ) );
		add_action( 'wp_initialize_site', array( 'WaicWorkflowPhase1Privacy', 'initializeSite' ), 20, 2 );
		add_action( 'wp_uninitialize_site', array( 'WaicWorkflowPhase1Privacy', 'uninitializeSite' ), 5, 1 );
		add_action( 'before_woocommerce_init', array( 'WaicWorkflowPhase1Config', 'declareHposCompatibility' ) );
	}

	public static function version() {
		return self::VERSION;
	}
}
