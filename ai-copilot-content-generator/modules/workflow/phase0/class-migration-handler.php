<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sole Phase 0 schema transition boundary.
 *
 * A restored or downgraded database with existing Workflow tables is held
 * fail-closed for an explicit code-forward migration/consistency decision.
 */
final class WaicWorkflowPhase0MigrationHandler {
	const HOLD_OPTION = 'waic_workflow_migration_hold';

	public function migrate() {
		$stored = WaicWorkflowPhase0Storage::getSchemaVersion();
		$current = WaicWorkflowPhase0Storage::SCHEMA_VERSION;
		$existing = WaicWorkflowPhase0Storage::countExistingTables();

		if ( $stored > $current || ( $stored < $current && $existing > 0 ) ) {
			return WaicWorkflowPhase0Storage::enterMigrationHold( $stored, $current, $existing );
		}

		if ( $stored < $current ) {
			WaicWorkflowPhase0Storage::clearMigrationHold();
			return WaicWorkflowPhase0Storage::install();
		}

		$verified = WaicWorkflowPhase0Storage::verify();
		if ( $verified instanceof WaicWorkflowPhase0Result && 'WF-P0-MIG-000' === $verified->getCode() ) {
			WaicWorkflowPhase0Storage::clearMigrationHold();
		}
		return $verified;
	}
}
