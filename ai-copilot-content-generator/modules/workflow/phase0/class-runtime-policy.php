<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0RuntimePolicy {
	const OPTION = 'waic_workflow_phase0_flags';

	private static $flags = array( 'site', 'workflow', 'ingress', 'egress', 'runner', 'credential' );

	public static function defaults() {
		return array_fill_keys( self::$flags, false );
	}

	public static function isEnabled( $flag, $site_id = 0 ) {
		$flag = sanitize_key( (string) $flag );
		if ( ! in_array( $flag, self::$flags, true ) ) {
			return false;
		}
		if ( $site_id && function_exists( 'get_current_blog_id' ) && (int) $site_id !== (int) get_current_blog_id() ) {
			return false;
		}
		$stored = self::readAuthorityFlags();
		$flags  = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		return true === $flags['site'] && true === $flags[ $flag ];
	}

	private static function readAuthorityFlags() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || empty( $wpdb->options ) ) {
			return array();
		}
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", self::OPTION )
		);
		return null === $value ? array() : maybe_unserialize( $value );
	}

	public static function withSite( $site_id, callable $callback ) {
		$site_id = (int) $site_id;
		if ( $site_id <= 0 || ! function_exists( 'get_current_blog_id' ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-003', 'workflow_site_context_invalid', 'review_workflow' );
		}
		$current = (int) get_current_blog_id();
		$switched = false;
		if ( $current !== $site_id ) {
			if ( ! is_multisite() || ! switch_to_blog( $site_id ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-CTRL-003', 'workflow_site_context_invalid', 'review_workflow' );
			}
			$switched = true;
		}
		try {
			return call_user_func( $callback, $site_id );
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}
}
