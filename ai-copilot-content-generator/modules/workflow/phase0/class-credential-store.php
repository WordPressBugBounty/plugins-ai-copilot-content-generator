<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase0CredentialStore {
	const SCHEMA = 'aiwu.workflow-credential-envelope.v1';

	private $key_ring;
	private $active_version;

	public function __construct( array $key_ring, $active_version ) {
		if ( ! function_exists( 'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' ) ) {
			throw new RuntimeException( 'Sodium XChaCha20-Poly1305 is required.' );
		}
		$this->key_ring = array();
		foreach ( $key_ring as $version => $encoded ) {
			$version = sanitize_key( (string) $version );
			$key     = base64_decode( (string) $encoded, true );
			if ( '' === $version || false === $key || 32 !== strlen( $key ) ) {
				throw new InvalidArgumentException( 'Invalid Workflow credential root key.' );
			}
			$this->key_ring[ $version ] = $key;
		}
		$this->active_version = sanitize_key( (string) $active_version );
		if ( ! isset( $this->key_ring[ $this->active_version ] ) ) {
			throw new InvalidArgumentException( 'Active Workflow credential key version is unavailable.' );
		}
	}

	public static function fromConstants() {
		if ( ! defined( 'AIWU_WORKFLOW_CREDENTIAL_KEYS' ) || ! defined( 'AIWU_WORKFLOW_ACTIVE_CREDENTIAL_KEY_VERSION' ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-001', 'workflow_credential_key_missing', 'configure_credential_key', array( 'reenter_credential' ) );
		}
		$ring = constant( 'AIWU_WORKFLOW_CREDENTIAL_KEYS' );
		if ( ! is_array( $ring ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-001', 'workflow_credential_key_invalid', 'configure_credential_key' );
		}
		try {
			return new self( $ring, constant( 'AIWU_WORKFLOW_ACTIVE_CREDENTIAL_KEY_VERSION' ) );
		} catch ( Throwable $error ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-001', 'workflow_credential_key_invalid', 'configure_credential_key' );
		}
	}

	public function encrypt( $plaintext, $site_uuid, $purpose, $credential_id ) {
		if ( ! is_string( $plaintext ) || '' === $plaintext ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-003', 'workflow_credential_empty', 'reenter_credential', array( 'reenter_credential' ) );
		}
		$context = $this->normalizeContext( $site_uuid, $purpose, $credential_id, $this->active_version );
		if ( false === $context ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-003', 'workflow_credential_scope_invalid', 'reenter_credential' );
		}

		$nonce      = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );
		$derived    = $this->deriveKey( $this->key_ring[ $this->active_version ], $context['site_uuid'], $context['purpose'] );
		$aad        = $this->aad( $context );
		$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt( $plaintext, $aad, $nonce, $derived );
		sodium_memzero( $derived );

		return array(
			'schema'        => self::SCHEMA,
			'key_version'   => $this->active_version,
			'site_uuid'     => $context['site_uuid'],
			'purpose'       => $context['purpose'],
			'credential_id' => $context['credential_id'],
			'nonce'         => base64_encode( $nonce ),
			'ciphertext'    => base64_encode( $ciphertext ),
		);
	}

	public function decrypt( array $envelope, $site_uuid, $purpose, $credential_id ) {
		$version = isset( $envelope['key_version'] ) ? sanitize_key( $envelope['key_version'] ) : '';
		if ( self::SCHEMA !== ( isset( $envelope['schema'] ) ? $envelope['schema'] : '' ) || ! isset( $this->key_ring[ $version ] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-001', 'workflow_credential_key_unavailable', 'reenter_credential', array( 'reenter_credential' ) );
		}
		$context = $this->normalizeContext( $site_uuid, $purpose, $credential_id, $version );
		if ( false === $context
			|| ! hash_equals( $context['site_uuid'], (string) $envelope['site_uuid'] )
			|| ! hash_equals( $context['purpose'], (string) $envelope['purpose'] )
			|| ! hash_equals( $context['credential_id'], (string) $envelope['credential_id'] ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-003', 'workflow_credential_scope_mismatch', 'reenter_credential' );
		}

		$nonce      = base64_decode( isset( $envelope['nonce'] ) ? $envelope['nonce'] : '', true );
		$ciphertext = base64_decode( isset( $envelope['ciphertext'] ) ? $envelope['ciphertext'] : '', true );
		if ( false === $nonce || false === $ciphertext || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES !== strlen( $nonce ) ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-002', 'workflow_credential_authentication_failed', 'reenter_credential' );
		}

		$derived = $this->deriveKey( $this->key_ring[ $version ], $context['site_uuid'], $context['purpose'] );
		$plain   = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt( $ciphertext, $this->aad( $context ), $nonce, $derived );
		sodium_memzero( $derived );
		if ( false === $plain ) {
			return WaicWorkflowPhase0Result::blocked( 'WF-CRED-002', 'workflow_credential_authentication_failed', 'reenter_credential' );
		}
		return $plain;
	}

	private function normalizeContext( $site_uuid, $purpose, $credential_id, $version ) {
		$site_uuid    = strtolower( trim( (string) $site_uuid ) );
		$purpose      = sanitize_key( (string) $purpose );
		$credential_id= sanitize_key( (string) $credential_id );
		if ( ! preg_match( '/^[a-f0-9-]{16,64}$/', $site_uuid ) || '' === $purpose || '' === $credential_id ) {
			return false;
		}
		return array( 'site_uuid' => $site_uuid, 'purpose' => $purpose, 'credential_id' => $credential_id, 'key_version' => $version );
	}

	private function deriveKey( $root, $site_uuid, $purpose ) {
		return hash_hkdf( 'sha256', $root, 32, 'aiwu-workflow-credential/' . $purpose, $site_uuid );
	}

	private function aad( array $context ) {
		return wp_json_encode( array( self::SCHEMA, $context['key_version'], $context['site_uuid'], $context['purpose'], $context['credential_id'] ), JSON_UNESCAPED_SLASHES );
	}
}
