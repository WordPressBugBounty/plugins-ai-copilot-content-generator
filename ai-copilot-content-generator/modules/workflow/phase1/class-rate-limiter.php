<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1RateLimiter {
	private static $held_locks = array();
	private static $release_registered = false;

	private static $limits = array(
		'read'      => array( 'actor' => array( 60, 60 ), 'site' => array( 300, 60 ) ),
		'preflight' => array( 'actor' => array( 10, 60 ), 'site' => array( 30, 60 ) ),
		'commit'    => array( 'actor' => array( 5, 60 ), 'site' => array( 15, 60 ) ),
		'ai'        => array( 'actor' => array( 3, 60 ), 'site' => array( 30, 3600 ) ),
		'ai_save'   => array( 'actor' => array( 10, 60 ), 'site' => array( 30, 60 ) ),
	);

	public static function check( $family, $operation_id ) {
		global $wpdb;
		$family = sanitize_key( (string) $family );
		if ( ! isset( self::$limits[ $family ] ) ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$storage = WaicWorkflowPhase1Storage::verify();
		if ( 'succeeded' !== $storage->getState() ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		$actor_id = get_current_user_id();
		$site_id = get_current_blog_id();
		foreach ( self::$limits[ $family ] as $scope_type => $limit ) {
			$scope_value = 'actor' === $scope_type ? $actor_id . ':' . $site_id : (string) $site_id;
			$result = self::increment( $scope_type, $scope_value, $family, $limit[0], $limit[1] );
			if ( false === $result ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
			}
			if ( $result['count'] > $limit[0] ) {
				WaicWorkflowPhase1Storage::recordOutcome( 'rate_limited', 'rate_limited' );
				return WaicWorkflowPhase1Result::rateLimited( $operation_id, $result['retry_after'] );
			}
		}
		if ( 'ai' === $family ) {
			$hour = self::increment( 'actor_hour', $actor_id . ':' . $site_id, $family, 10, HOUR_IN_SECONDS );
			if ( false === $hour ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
			}
			if ( $hour['count'] > 10 ) {
				WaicWorkflowPhase1Storage::recordOutcome( 'rate_limited', 'rate_limited' );
				return WaicWorkflowPhase1Result::rateLimited( $operation_id, $hour['retry_after'] );
			}
		}
		if ( is_multisite() && in_array( $family, array( 'preflight', 'commit', 'ai' ), true ) ) {
			$active_sites = max( 1, (int) get_sites( array( 'count' => true, 'number' => 0, 'deleted' => 0, 'spam' => 0, 'archived' => 0 ) ) );
			if ( 'ai' === $family ) {
				$ceiling = min( 300, max( 60, 2 * $active_sites ) );
				$window = 3600;
			} else {
				$ceiling = min( 600, max( 120, 4 * $active_sites ) );
				$window = 60;
			}
			$network_id = function_exists( 'get_current_network_id' ) ? get_current_network_id() : 1;
			$result = self::increment( 'network', (string) $network_id, $family, $ceiling, $window );
			if ( false === $result ) {
				return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
			}
			if ( $result['count'] > $ceiling ) {
				return WaicWorkflowPhase1Result::rateLimited( $operation_id, $result['retry_after'] );
			}
		}
		$concurrency = self::acquireConcurrency( $family, $actor_id, $site_id );
		if ( false === $concurrency ) {
			return WaicWorkflowPhase1Result::blocked( $operation_id, 'rate_limit_state_unavailable', __( 'The workflow limiter is unavailable.', 'ai-copilot-content-generator' ), array(), array(), 503 );
		}
		if ( 'limited' === $concurrency ) {
			WaicWorkflowPhase1Storage::recordOutcome( 'rate_limited', 'rate_limited' );
			return WaicWorkflowPhase1Result::rateLimited( $operation_id, 1 );
		}
		return true;
	}

	private static function acquireConcurrency( $family, $actor_id, $site_id ) {
		$requirements = array();
		switch ( $family ) {
			case 'read':
				$requirements[] = array( 'site', $site_id, 10 );
				break;
			case 'preflight':
				$requirements[] = array( 'site', $site_id, 2 );
				break;
			case 'commit':
				// A site-wide single slot is intentionally stricter than the
				// per-package maximum and therefore cannot mint extra authority.
				$requirements[] = array( 'site', $site_id, 1 );
				break;
			case 'ai':
				$requirements[] = array( 'actor', $actor_id . ':' . $site_id, 1 );
				$requirements[] = array( 'site', $site_id, 3 );
				break;
			case 'ai_save':
				$requirements[] = array( 'site', $site_id, 2 );
				break;
		}
		$acquired = array();
		foreach ( $requirements as $requirement ) {
			$slot = self::acquireSlot( $family, $requirement[0], (string) $requirement[1], (int) $requirement[2] );
			if ( false === $slot || 'limited' === $slot ) {
				self::releaseNames( $acquired );
				return $slot;
			}
			$acquired[] = $slot;
		}
		self::$held_locks = array_merge( self::$held_locks, $acquired );
		if ( ! self::$release_registered ) {
			self::$release_registered = true;
			add_filter( 'rest_post_dispatch', array( __CLASS__, 'releaseAfterDispatch' ), PHP_INT_MAX, 1 );
			add_action( 'shutdown', array( __CLASS__, 'releaseAll' ), PHP_INT_MAX );
		}
		return true;
	}

	private static function acquireSlot( $family, $scope_type, $scope_value, $slots ) {
		global $wpdb;
		$unavailable = false;
		for ( $slot = 0; $slot < $slots; $slot++ ) {
			$name = 'aiwu:p1:' . substr( hash( 'sha256', $family . '|' . $scope_type . '|' . $scope_value . '|' . $slot ), 0, 48 );
			$result = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) );
			if ( '1' === (string) $result ) { return $name; }
			if ( null === $result || false === $result ) { $unavailable = true; }
		}
		return $unavailable ? false : 'limited';
	}

	public static function releaseAfterDispatch( $response ) {
		self::releaseAll();
		return $response;
	}

	public static function releaseAll() {
		self::releaseNames( self::$held_locks );
		self::$held_locks = array();
	}

	private static function releaseNames( array $names ) {
		global $wpdb;
		foreach ( array_reverse( $names ) as $name ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
		}
	}

	private static function increment( $scope_type, $scope_value, $family, $limit, $window ) {
		global $wpdb;
		$now = time();
		$window_start = (int) ( floor( $now / $window ) * $window );
		$scope_key = hash( 'sha256', $scope_type . '|' . $scope_value . '|' . $family . '|' . $window_start . '|' . $window );
		$table = WaicWorkflowPhase1Storage::table( 'workflow_rate_limits' );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (scope_key,scope_type,family,window_start,window_seconds,request_count,updated_at) VALUES (%s,%s,%s,%d,%d,1,%s) ON DUPLICATE KEY UPDATE request_count=request_count+1,updated_at=VALUES(updated_at)",
			$scope_key,
			$scope_type,
			$family,
			$window_start,
			$window,
			current_time( 'mysql', true )
		);
		if ( false === $wpdb->query( $sql ) ) {
			return false;
		}
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT request_count FROM {$table} WHERE scope_key=%s", $scope_key ) );
		if ( null === $count ) {
			return false;
		}
		return array( 'count' => (int) $count, 'retry_after' => max( 1, $window_start + $window - $now ), 'limit' => $limit );
	}
}
