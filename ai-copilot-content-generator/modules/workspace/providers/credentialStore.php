<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'contracts.php';

/*
 * Profiles contain only non-secret configuration. Secrets are encrypted in a
 * separate non-autoloaded option and are never returned by public methods.
 */
class WaicProviderCredentialStore {
	const PROFILES_OPTION = 'waic_provider_profiles';
	const CREDENTIALS_OPTION = 'waic_provider_credentials';
	const VERSION_OPTION = 'waic_provider_store_version';
	const STORE_VERSION = 1;

	public function getProfiles() {
		$profiles = get_option( self::PROFILES_OPTION, array() );
		$out = array();
		foreach ( is_array( $profiles ) ? $profiles : array() as $row ) {
			$profile = new WaicProviderProfile( $row );
			if ( '' !== $profile->id && '' !== $profile->provider_id ) { $out[ $profile->id ] = $profile->toArray(); }
		}
		return $out;
	}

	public function getProfile( $id ) {
		$id = sanitize_text_field( (string) $id );
		$profiles = $this->getProfiles();
		return isset( $profiles[ $id ] ) ? $profiles[ $id ] : false;
	}

	public function saveProfile( $profile, $secrets = array() ) {
		$profile = $profile instanceof WaicProviderProfile ? $profile : new WaicProviderProfile( $profile );
		if ( '' === $profile->id ) { $profile->id = $this->newId(); }
		if ( '' === $profile->provider_id ) { return new WaicProviderFailure( 'CONFIGURATION', 'Provider profile is incomplete.' ); }
		$profiles = $this->getProfiles();
		$old = isset( $profiles[ $profile->id ] ) ? $profiles[ $profile->id ] : array();
		$profile->secret_ref = ! empty( $profile->secret_ref ) ? $profile->secret_ref : WaicUtils::getArrayValue( $old, 'secret_ref', $this->newId() );
		$profile->created_at = ! empty( $profile->created_at ) ? $profile->created_at : gmdate( 'c' );
		if ( is_array( $secrets ) && ! empty( $secrets ) ) {
			$written = $this->writeCredentials( $profile->secret_ref, $secrets );
			if ( $written instanceof WaicProviderFailure ) { return $written; }
		}
		$profiles[ $profile->id ] = $profile->toArray();
		$this->updateOptionNoAutoload( self::PROFILES_OPTION, $profiles );
		$this->updateOptionNoAutoload( self::VERSION_OPTION, self::STORE_VERSION );
		return $profile->toArray();
	}

	public function rotate( $profileId, $secrets ) {
		$profile = $this->getProfile( $profileId );
		if ( false === $profile ) { return new WaicProviderFailure( 'CONFIGURATION', 'Provider profile was not found.' ); }
		$profile['secret_ref'] = $this->newId();
		return $this->saveProfile( $profile, $secrets );
	}

	public function delete( $profileId, $deleteCredentials = false ) {
		$profiles = $this->getProfiles();
		if ( empty( $profiles[ $profileId ] ) ) { return false; }
		$secretRef = WaicUtils::getArrayValue( $profiles[ $profileId ], 'secret_ref', '' );
		unset( $profiles[ $profileId ] );
		$this->updateOptionNoAutoload( self::PROFILES_OPTION, $profiles );
		if ( $deleteCredentials && '' !== $secretRef ) { $this->deleteCredentials( $secretRef ); }
		return true;
	}

	public function getCredentials( $secretRef ) {
		$all = get_option( self::CREDENTIALS_OPTION, array() );
		$row = is_array( $all ) && isset( $all[ $secretRef ] ) ? $all[ $secretRef ] : false;
		if ( ! is_array( $row ) ) { return array(); }
		return $this->decrypt( $row );
	}

	public function mask( $secretRef ) {
		$secrets = $this->getCredentials( $secretRef );
		$out = array();
		foreach ( $secrets as $key => $value ) {
			$value = (string) $value;
			$out[ sanitize_key( $key ) ] = '' === $value ? '' : '••••' . substr( $value, -4 );
		}
		return $out;
	}

	public function migrateLegacyOptions( $legacy = array() ) {
		$legacy = is_array( $legacy ) ? $legacy : array();
		$map = array(
			'open-ai' => array('api_key'), 'claude' => array('claude_api_key'), 'gemini' => array('gemini_api_key'),
			'deep-seek' => array('deep_seek_api_key'), 'perplexity' => array('perplexity_api_key'), 'openrouter' => array('openrouter_api_key'),
		);
		foreach ( $map as $providerId => $keys ) {
			$hasSecret = false; $secrets = array();
			foreach ( $keys as $key ) { if ( ! empty( $legacy[ $key ] ) ) { $secrets[ $key ] = (string) $legacy[ $key ]; $hasSecret = true; } }
			if ( ! $hasSecret ) { continue; }
			$id = 'legacy-' . $providerId;
			if ( false === $this->getProfile( $id ) ) {
				$this->saveProfile( array('id' => $id, 'provider_id' => $providerId, 'label' => 'Legacy ' . $providerId, 'enabled' => false, 'configuration' => array()), $secrets );
			}
		}
		return true;
	}

	private function writeCredentials( $secretRef, $secrets ) {
		$encrypted = $this->encrypt( $secrets );
		if ( $encrypted instanceof WaicProviderFailure ) { return $encrypted; }
		$all = get_option( self::CREDENTIALS_OPTION, array() );
		$all = is_array( $all ) ? $all : array();
		$all[ $secretRef ] = $encrypted;
		$this->updateOptionNoAutoload( self::CREDENTIALS_OPTION, $all );
		return true;
	}

	private function deleteCredentials( $secretRef ) {
		$all = get_option( self::CREDENTIALS_OPTION, array() );
		if ( is_array( $all ) && isset( $all[ $secretRef ] ) ) { unset( $all[ $secretRef ] ); $this->updateOptionNoAutoload( self::CREDENTIALS_OPTION, $all ); }
	}

	private function encrypt( $secrets ) {
		$plain = wp_json_encode( $this->sanitizeSecrets( $secrets ) );
		$key = $this->masterKey();
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			return array('v' => 1, 'alg' => 'sodium_secretbox', 'nonce' => base64_encode( $nonce ), 'ciphertext' => base64_encode( sodium_crypto_secretbox( $plain, $nonce, $key ) ));
		}
		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv = random_bytes( 12 ); $tag = '';
			$ciphertext = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false !== $ciphertext ) { return array('v' => 1, 'alg' => 'aes-256-gcm', 'nonce' => base64_encode( $iv ), 'tag' => base64_encode( $tag ), 'ciphertext' => base64_encode( $ciphertext )); }
		}
		return new WaicProviderFailure( 'CONFIGURATION', 'Secure credential encryption is unavailable.' );
	}

	private function decrypt( $row ) {
		$key = $this->masterKey(); $plain = false;
		if ( 'sodium_secretbox' === WaicUtils::getArrayValue( $row, 'alg', '' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$plain = sodium_crypto_secretbox_open( base64_decode( $row['ciphertext'] ), base64_decode( $row['nonce'] ), $key );
		} elseif ( 'aes-256-gcm' === WaicUtils::getArrayValue( $row, 'alg', '' ) && function_exists( 'openssl_decrypt' ) ) {
			$plain = openssl_decrypt( base64_decode( $row['ciphertext'] ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode( $row['nonce'] ), base64_decode( $row['tag'] ) );
		}
		$decoded = is_string( $plain ) ? json_decode( $plain, true ) : array();
		return is_array( $decoded ) ? $decoded : array();
	}

	private function masterKey() {
		$configured = defined( 'WAIC_PROVIDER_MASTER_KEY' ) ? (string) WAIC_PROVIDER_MASTER_KEY : getenv( 'WAIC_PROVIDER_MASTER_KEY' );
		if ( empty( $configured ) ) { $configured = wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ); }
		return hash( 'sha256', $configured, true );
	}

	private function sanitizeSecrets( $secrets ) {
		$out = array();
		foreach ( (array) $secrets as $key => $value ) { if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) { $out[ sanitize_key( $key ) ] = trim( (string) $value ); } }
		return $out;
	}
	private function updateOptionNoAutoload( $key, $value ) { if ( false === get_option( $key, false ) ) { add_option( $key, $value, '', false ); } else { update_option( $key, $value, false ); } }
	private function newId() { return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'waic-', true ); }
}
