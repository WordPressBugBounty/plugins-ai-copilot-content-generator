<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fail-closed boundary for legacy Workflow code retained for migration/read use.
 */
final class WaicWorkflowPhase0Containment {
	const REVISION = 'workflow_containment.v1';

	public static function isLegacyExecutionAllowed() {
		return false;
	}

	public static function blocked( $code = 'WF-LEGACY-001', $impact_key = 'workflow_legacy_path_quarantined' ) {
		return WaicWorkflowPhase0Result::blocked(
			(string) $code,
			(string) $impact_key,
			'disable_workflow',
			array( 'review_workflow_security' )
		);
	}

	public static function assertQuarantined() {
		if ( self::isLegacyExecutionAllowed() ) {
			return self::blocked( 'WF-LEGACY-001', 'workflow_legacy_path_enabled' );
		}
		return WaicWorkflowPhase0Result::success( 'WF-P0-LEGACY-000', 'workflow_legacy_path_quarantined' );
	}
}
