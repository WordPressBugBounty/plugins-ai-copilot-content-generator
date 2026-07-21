<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Privacy {
	public static function registerExporter( array $exporters ) {
		$exporters['aiwu-workflow-phase1'] = array(
			'exporter_friendly_name' => __( 'AIWU workflow operations', 'ai-copilot-content-generator' ),
			'callback'               => array( __CLASS__, 'exportPersonalData' ),
		);
		return $exporters;
	}

	public static function registerEraser( array $erasers ) {
		$erasers['aiwu-workflow-phase1'] = array(
			'eraser_friendly_name' => __( 'AIWU workflow personal metadata', 'ai-copilot-content-generator' ),
			'callback'             => array( __CLASS__, 'erasePersonalData' ),
		);
		return $erasers;
	}

	public static function exportPersonalData( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user || ! WaicWorkflowPhase1Config::isEnabled( 'core' ) || 'succeeded' !== WaicWorkflowPhase1Storage::verify()->getState() ) {
			return array( 'data' => array(), 'done' => true );
		}
		$page = max( 1, (int) $page );
		$rows = WaicWorkflowPhase1Storage::exportActorOperations( (int) $user->ID, $page );
		$done = count( $rows ) <= 100;
		$rows = array_slice( $rows, 0, 100 );
		$data = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'group_id'    => 'aiwu-workflow-operations',
				'group_label' => __( 'AIWU workflow operations', 'ai-copilot-content-generator' ),
				'item_id'     => 'operation-' . (int) $row['id'],
				'data'        => array(
					array( 'name' => __( 'Operation', 'ai-copilot-content-generator' ), 'value' => $row['operation_id'] ),
					array( 'name' => __( 'State', 'ai-copilot-content-generator' ), 'value' => $row['state'] ),
					array( 'name' => __( 'Result code', 'ai-copilot-content-generator' ), 'value' => $row['code'] ),
					array( 'name' => __( 'Created', 'ai-copilot-content-generator' ), 'value' => $row['created_at'] ),
					array( 'name' => __( 'Updated', 'ai-copilot-content-generator' ), 'value' => $row['updated_at'] ),
				),
			);
		}
		return array( 'data' => $data, 'done' => $done );
	}

	public static function erasePersonalData( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		$result = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		if ( ! $user || ! WaicWorkflowPhase1Config::isEnabled( 'core' ) || 'succeeded' !== WaicWorkflowPhase1Storage::verify()->getState() ) {
			return $result;
		}
		$erased = WaicWorkflowPhase1Storage::eraseActorData( (int) $user->ID );
		$result['items_removed'] = $erased['items_removed'];
		$result['items_retained'] = $erased['items_retained'];
		if ( $erased['write_failed'] ) {
			$result['messages'][] = __( 'Some workflow personal metadata could not be erased and will be retried.', 'ai-copilot-content-generator' );
		}
		if ( $result['items_retained'] ) { $result['messages'][] = __( 'Pseudonymized security events remain for the approved audit retention period or legal hold.', 'ai-copilot-content-generator' ); }
		$result['messages'] = array_slice( array_values( array_unique( $result['messages'] ) ), 0, 5 );
		$result['done'] = $erased['done'];
		return $result;
	}

	public static function addPolicyText() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) { return; }
		$content = '<p>' . esc_html__( 'AIWU workflow packs store site-scoped draft, operation and audit metadata. Operation metadata is retained for up to 180 days and audit metadata for up to 365 days unless a legal hold applies.', 'ai-copilot-content-generator' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Private staging is outside the web root and expires after 15 minutes in normal use. AI intent and raw provider responses are not stored locally. AI is disabled by default and requires an accepted provider, region, retention and model snapshot.', 'ai-copilot-content-generator' ) . '</p>';
		$content .= '<p>' . esc_html__( 'WordPress personal-data export and erasure tools cover user-owned workflow metadata. Host and provider backups may retain deleted data according to their disclosed policies.', 'ai-copilot-content-generator' ) . '</p>';
		wp_add_privacy_policy_content( __( 'AIWU workflows', 'ai-copilot-content-generator' ), wp_kses_post( $content ) );
	}

	public static function initializeSite( $new_site, $args = array() ) {
		$site_id = is_object( $new_site ) && isset( $new_site->blog_id ) ? (int) $new_site->blog_id : 0;
		if ( $site_id < 1 || ! is_multisite() ) { return; }
		switch_to_blog( $site_id );
		try {
			if ( WaicWorkflowPhase1Config::isEnabled( 'core' ) ) { WaicWorkflowPhase1Storage::install(); }
		} finally { restore_current_blog(); }
	}

	public static function uninitializeSite( $old_site ) {
		$site_id = is_object( $old_site ) && isset( $old_site->blog_id ) ? (int) $old_site->blog_id : (int) $old_site;
		if ( $site_id < 1 ) { return; }
		switch_to_blog( $site_id );
		try {
			WaicWorkflowPhase1Storage::disableSiteAuthority();
			WaicWorkflowPhase1PackService::cleanupStaging( 100 );
			if ( ! WaicWorkflowPhase1Storage::deleteSiteData( $site_id ) ) {
				WaicWorkflowPhase1Storage::recordDeletionDelayed( $site_id );
			}
		} finally { restore_current_blog(); }
	}
}
