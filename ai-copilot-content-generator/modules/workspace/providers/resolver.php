<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WaicProviderProfileResolver {
	private $store;
	public function __construct( $store = null ) { $this->store = $store ? $store : new WaicProviderCredentialStore(); }
	public function resolve( $providerId, $operation, $model = '', $profileId = '', $legacyOptions = array(), $registry = array() ) {
		$providerId = sanitize_key( (string) $providerId ); $operation = $this->normalizeOperation( $operation );
		$providers = WaicUtils::getArrayValue( $registry, 'providers', array(), 2 );
		$manifest = WaicUtils::getArrayValue( $providers, $providerId, array(), 2 );
		if ( empty( $manifest ) ) { return new WaicProviderFailure( 'CONFIGURATION', 'Unknown provider.' ); }
		$profile = '' !== $profileId ? $this->store->getProfile( $profileId ) : array('provider_id' => $providerId, 'enabled' => true, 'configuration' => array());
		if ( false === $profile || $providerId !== WaicUtils::getArrayValue( $profile, 'provider_id', $providerId ) || ( isset( $profile['enabled'] ) && empty( $profile['enabled'] ) ) ) { return new WaicProviderFailure( 'CONFIGURATION', 'Provider profile is disabled or unavailable.' ); }
		$adapter = WaicProviderAdapterFactory::getAdapter( $providerId, '', $manifest );
		if ( ! $adapter ) { return new WaicProviderFailure( 'CONFIGURATION', 'Provider adapter is unavailable.' ); }
		if ( ! $adapter->supports( $operation, $profile, $model ) ) { return new WaicProviderFailure( 'CAPABILITY_UNSUPPORTED', 'Selected provider and model do not support this operation.' ); }
		$secrets = ! empty( $profile['secret_ref'] ) ? $this->store->getCredentials( $profile['secret_ref'] ) : array();
		$options = array_merge( is_array( $legacyOptions ) ? $legacyOptions : array(), WaicUtils::getArrayValue( $profile, 'configuration', array(), 2 ), $secrets );
		if ( ! $adapter->setApiOptions( $options ) ) { return new WaicProviderFailure( 'CONFIGURATION', 'Provider credentials are unavailable.' ); }
		return array('adapter' => $adapter, 'profile' => $profile, 'options' => $options);
	}
	private function normalizeOperation( $operation ) { $operation = sanitize_key( (string) $operation ); return 'image' === $operation ? 'image_generate' : $operation; }
}
