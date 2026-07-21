<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0Runner {
	private $lifecycle;
	private $ports;

	public function __construct( WaicWorkflowPhase0Lifecycle $lifecycle, WaicWorkflowPhase0EffectPorts $ports ) {
		$this->lifecycle = $lifecycle;
		$this->ports = $ports;
	}

	public function runEffect( array $state, array $current_digests, array $command, $now ) {
		if ( empty( $command['local_authority'] ) || ! empty( $command['ai_authority'] ) || ! empty( $command['catalog_authority'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-RUN-003', 'workflow_remote_authority_rejected', 'review_workflow' );
		}
		$authority = $this->lifecycle->authorizeEffect( $state, $current_digests, $now );
		if ( ! ( $authority instanceof WaicWorkflowPhase0Result ) || 'WF-P0-RUN-000' !== $authority->getCode() ) {
			return $authority;
		}
		$command['authority_valid'] = true;
		return $this->ports->execute( isset( $command['family'] ) ? $command['family'] : '', $command );
	}

	public function recover( $stage, $attempts ) {
		$stage = sanitize_key( (string) $stage );
		if ( in_array( $stage, array( 'post_commit', 'crash_after_intent', 'retry_exhausted' ), true ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-RUN-005', 'recover_workflow' );
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-RUN-004', 'workflow_lease_or_clock_invalid', 'retry_workflow' );
	}
}
