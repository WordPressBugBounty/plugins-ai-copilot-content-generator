<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0EgressGateway {
	private $resolver;
	private $max_bytes;

	public function __construct( $resolver = null, $max_bytes = 1048576 ) {
		$this->resolver  = is_callable( $resolver ) ? $resolver : array( $this, 'resolveHost' );
		$this->max_bytes = max( 1024, min( 5242880, (int) $max_bytes ) );
	}

	public function validateUrl( $url, $credential_origin = '' ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url || strlen( $url ) > 2048 || false !== strpos( $url, "\0" ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_invalid', 'review_egress_profile' );
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) || empty( $parts['host'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_requires_https', 'review_egress_profile' );
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_authority_invalid', 'review_egress_profile' );
		}

		$host = strtolower( rtrim( $parts['host'], '.' ) );
		if ( preg_match( '/[^\x20-\x7e]/', $host ) ) {
			if ( ! function_exists( 'idn_to_ascii' ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_host_invalid', 'review_egress_profile' );
			}
			$host = idn_to_ascii( $host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
		}
		if ( ! is_string( $host ) || '' === $host || 'localhost' === $host || '.local' === substr( $host, -6 ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_private', 'review_egress_profile' );
		}

		$addresses = call_user_func( $this->resolver, $host );
		if ( ! is_array( $addresses ) || empty( $addresses ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-EGRESS-003', 'retry_dns_resolution' );
		}
		foreach ( array_unique( $addresses ) as $address ) {
			if ( ! $this->isPublicIp( $address ) ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-001', 'workflow_destination_private', 'review_egress_profile' );
			}
		}

		$origin = 'https://' . $host . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' );
		if ( '' !== $credential_origin && ! hash_equals( strtolower( rtrim( $credential_origin, '/' ) ), $origin ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-003', 'workflow_credential_destination_mismatch', 'review_egress_profile' );
		}
		return array( 'url' => $url, 'origin' => $origin, 'host' => $host, 'addresses' => array_values( array_unique( $addresses ) ) );
	}

	public function request( $url, array $args = array(), $credential_origin = '' ) {
		if ( ! function_exists( 'wp_safe_remote_request' ) ) {
			return WaicWorkflowPhase0Result::unavailable( 'WF-EGRESS-003', 'wordpress_http_unavailable' );
		}
		$current_url    = $url;
		$current_origin = '';
		$was_redirected = false;
		for ( $redirect = 0; $redirect <= 3; $redirect++ ) {
			$validated = $this->validateUrl( $current_url, $credential_origin );
			if ( $validated instanceof WaicWorkflowPhase0Result ) {
				if ( $was_redirected && 'WF-CRED-003' !== $validated->getCode() ) {
					return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-002', 'workflow_redirect_destination_rejected', 'review_egress_profile' );
				}
				return $validated;
			}
			if ( '' !== $current_origin && $current_origin !== $validated['origin'] && isset( $args['headers'] ) ) {
				unset( $args['headers']['Authorization'], $args['headers']['authorization'], $args['headers']['Cookie'], $args['headers']['cookie'] );
			}
			$current_origin = $validated['origin'];
			$request_args   = $this->boundedArgs( $args );
			$response       = wp_safe_remote_request( $current_url, $request_args );
			if ( is_wp_error( $response ) ) {
				return WaicWorkflowPhase0Result::unavailable( 'WF-EGRESS-003', 'retry_egress_request' );
			}
			$status = (int) wp_remote_retrieve_response_code( $response );
			if ( $status < 300 || $status >= 400 ) {
				return $response;
			}
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( empty( $location ) || 3 === $redirect ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-002', 'workflow_redirect_rejected', 'review_egress_profile' );
			}
			$current_url = $this->absoluteRedirect( $current_url, $location );
			if ( false === $current_url ) {
				return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-002', 'workflow_redirect_rejected', 'review_egress_profile' );
			}
			$was_redirected = true;
		}
		return WaicWorkflowPhase0Result::blocked( 'WF-EGRESS-002', 'workflow_redirect_rejected', 'review_egress_profile' );
	}

	private function boundedArgs( array $args ) {
		$args['timeout']             = min( 15, max( 1, isset( $args['timeout'] ) ? (int) $args['timeout'] : 10 ) );
		$args['redirection']         = 0;
		$args['reject_unsafe_urls']  = true;
		$args['limit_response_size'] = $this->max_bytes;
		$args['blocking']            = true;
		return $args;
	}

	private function resolveHost( $host ) {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return array( $host );
		}
		$records = dns_get_record( $host, DNS_A | DNS_AAAA );
		$result  = array();
		foreach ( (array) $records as $record ) {
			if ( ! empty( $record['ip'] ) ) {
				$result[] = $record['ip'];
			} elseif ( ! empty( $record['ipv6'] ) ) {
				$result[] = $record['ipv6'];
			}
		}
		return $result;
	}

	private function isPublicIp( $address ) {
		return false !== filter_var( $address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	private function absoluteRedirect( $base, $location ) {
		if ( preg_match( '#^https://#i', $location ) ) {
			return $location;
		}
		if ( 0 === strpos( $location, '//' ) ) {
			return 'https:' . $location;
		}
		$parts = wp_parse_url( $base );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || 0 !== strpos( $location, '/' ) ) {
			return false;
		}
		return 'https://' . $parts['host'] . $location;
	}
}
