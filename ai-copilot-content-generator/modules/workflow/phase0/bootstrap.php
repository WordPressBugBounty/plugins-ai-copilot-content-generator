<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Bootstrap {
	const VERSION = '0.4.0-restore-guard';

	private static $loaded = false;

	public static function load() {
		if ( self::$loaded ) {
			return;
		}
		require_once __DIR__ . '/class-result.php';
		require_once __DIR__ . '/class-containment.php';
		require_once __DIR__ . '/class-operation-registry.php';
		require_once __DIR__ . '/class-path-policy.php';
		require_once __DIR__ . '/class-credential-store.php';
		require_once __DIR__ . '/class-egress-gateway.php';
		require_once __DIR__ . '/class-lifecycle.php';
		require_once __DIR__ . '/class-descriptor-registry.php';
		require_once __DIR__ . '/class-runtime-policy.php';
		require_once __DIR__ . '/class-principal-dispatch.php';
		require_once __DIR__ . '/class-audit-policy.php';
		require_once __DIR__ . '/class-revocation-ledger.php';
		require_once __DIR__ . '/class-storage.php';
		require_once __DIR__ . '/class-migration-handler.php';
		require_once __DIR__ . '/class-outbox.php';
		require_once __DIR__ . '/class-lease.php';
		require_once __DIR__ . '/class-authority.php';
		require_once __DIR__ . '/class-ingress.php';
		require_once __DIR__ . '/class-effect-ports.php';
		require_once __DIR__ . '/class-runner.php';
		self::$loaded = true;
	}

	public static function version() {
		return self::VERSION;
	}
}
