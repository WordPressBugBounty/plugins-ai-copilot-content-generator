<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0OperationRegistry {
	const REVISION = 'workflow_operations.v2';

	private static $operations = array(
		'workflow.draft.save'          => array( 'disposition' => 'cutover', 'capability' => 'aiwu_edit_workflows' ),
		'workflow.template.clone'      => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.template.create'     => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.template.delete'     => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.import.preflight'    => array( 'disposition' => 'cutover', 'capability' => 'aiwu_import_workflow_packs' ),
		'workflow.import.commit'       => array( 'disposition' => 'cutover', 'capability' => 'aiwu_import_workflow_packs' ),
		'workflow.export'              => array( 'disposition' => 'cutover', 'capability' => 'aiwu_edit_workflows' ),
		'workflow.publish.change'      => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.activation.change'   => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.package.list'        => array( 'disposition' => 'cutover', 'capability' => 'aiwu_edit_workflows' ),
		'workflow.package.read'        => array( 'disposition' => 'cutover', 'capability' => 'aiwu_edit_workflows' ),
		'workflow.package.update'      => array( 'disposition' => 'cutover', 'capability' => 'aiwu_import_workflow_packs' ),
		'workflow.package.rollback'    => array( 'disposition' => 'cutover', 'capability' => 'aiwu_import_workflow_packs' ),
		'workflow.package.uninstall'   => array( 'disposition' => 'cutover', 'capability' => 'aiwu_import_workflow_packs' ),
		'workflow.ai.plan.compile'     => array( 'disposition' => 'cutover', 'capability' => 'aiwu_generate_workflow_drafts' ),
		'workflow.authority.revoke'    => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.credential.manage'   => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.credential.callback' => array( 'disposition' => 'deny', 'capability' => '' ),
		'workflow.policy.manage'       => array( 'disposition' => 'not_available', 'capability' => 'manage_options' ),
		'workflow.ingress.manage'      => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.egress.manage'       => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.runtime.start'       => array( 'disposition' => 'cutover', 'capability' => '' ),
		'workflow.runtime.resume'      => array( 'disposition' => 'cutover', 'capability' => '' ),
		'workflow.trusted_code.manage' => array( 'disposition' => 'not_available', 'capability' => 'manage_options' ),
		'workflow.audit.read'          => array( 'disposition' => 'cutover', 'capability' => 'manage_options' ),
		'workflow.plugin.migrate'      => array( 'disposition' => 'cutover', 'capability' => 'activate_plugins' ),
	);

	private static $controller_map = array(
		'saveworkflow'   => 'workflow.draft.save',
		'stopworkflow'   => 'workflow.authority.revoke',
		'runworkflow'    => 'workflow.runtime.start',
		'getlogdata'     => 'workflow.audit.read',
		'gethistorylist' => 'workflow.audit.read',
		'saveintegration'=> 'workflow.credential.manage',
		'createtemplate' => 'workflow.template.create',
		'deletetemplate' => 'workflow.template.delete',
		'getjson'        => 'workflow.export',
		'importtemplate' => 'workflow.import.commit',
	);

	public static function get( $operation_id ) {
		$operation_id = strtolower( (string) $operation_id );
		if ( ! isset( self::$operations[ $operation_id ] ) ) {
			return false;
		}
		return self::$operations[ $operation_id ] + array( 'operation_id' => $operation_id, 'revision' => self::REVISION );
	}

	public static function forControllerAction( $action ) {
		$key = strtolower( (string) $action );
		return isset( self::$controller_map[ $key ] ) ? self::$controller_map[ $key ] : false;
	}

	public static function canRegister( $operation_id ) {
		$operation = self::get( $operation_id );
		return $operation && 'cutover' === $operation['disposition'];
	}

	public static function digest() {
		return 'sha256:' . hash( 'sha256', wp_json_encode( self::$operations, JSON_UNESCAPED_SLASHES ) );
	}
}
