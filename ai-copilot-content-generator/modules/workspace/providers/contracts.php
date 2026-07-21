<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* PHP 7.4-compatible provider DTOs and contracts. */
if ( ! interface_exists( 'WaicAIProviderInterface' ) ) {
	$waicLegacyProviderInterface = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'aIProviderInterface.php';
	if ( file_exists( $waicLegacyProviderInterface ) ) {
		require_once $waicLegacyProviderInterface;
	}
}

class WaicProviderProfile {
	public $id = '';
	public $provider_id = '';
	public $label = '';
	public $enabled = false;
	public $configuration = array();
	public $secret_ref = '';
	public $created_at = '';
	public $last_test_at = '';
	public $last_test_status = '';

	public function __construct( $data = array() ) {
		$data = is_array( $data ) ? $data : array();
		foreach ( get_object_vars( $this ) as $key => $default ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}
		$this->id = substr( sanitize_text_field( (string) $this->id ), 0, 64 );
		$this->provider_id = sanitize_key( (string) $this->provider_id );
		$this->label = substr( sanitize_text_field( (string) $this->label ), 0, 120 );
		$this->enabled = ! empty( $this->enabled );
		$this->configuration = is_array( $this->configuration ) ? $this->configuration : array();
		$this->secret_ref = substr( sanitize_text_field( (string) $this->secret_ref ), 0, 64 );
	}

	public function toArray() {
		return array(
			'id' => $this->id,
			'provider_id' => $this->provider_id,
			'label' => $this->label,
			'enabled' => $this->enabled ? 1 : 0,
			'configuration' => $this->configuration,
			'secret_ref' => $this->secret_ref,
			'created_at' => $this->created_at,
			'last_test_at' => $this->last_test_at,
			'last_test_status' => $this->last_test_status,
		);
	}
}

class WaicProviderChatRequest {
	public $profile;
	public $model = '';
	public $messages = array();
	public $options = array();
	public $operation = 'chat';
}
class WaicProviderChatResponse {
	public $text = '';
	public $tool_calls = array();
	public $usage = array();
	public $request_id = '';
}
class WaicProviderImageRequest {
	public $profile;
	public $model = '';
	public $prompt = '';
	public $options = array();
}
class WaicProviderImageResult {
	public $url = '';
	public $usage = array();
	public $request_id = '';
	public $job = null;
}
class WaicProviderImageJob {
	public $id = '';
	public $provider_id = '';
	public $profile_id = '';
	public $model = '';
	public $status = 'queued';
	public $attempts = 0;
	public $attachment_id = 0;
	public $meta = array();
}
class WaicProviderEmbeddingRequest {
	public $profile;
	public $model = '';
	public $input = array();
	public $options = array();
}
class WaicProviderEmbeddingResponse {
	public $vectors = array();
	public $dimensions = 0;
	public $usage = array();
	public $request_id = '';
}
class WaicProviderUsage {
	public $input_tokens = 0;
	public $output_tokens = 0;
	public $reasoning_tokens = 0;
	public $cached_tokens = 0;
	public $cache_write_tokens = 0;
	public $total_tokens = 0;
	public $image_units = 0;
	public $embedding_units = 0;
	public $provider_reported_cost = null;
	public $est_flags = 0;
}
class WaicProviderFailure {
	public $code = 'UPSTREAM';
	public $message = '';
	public $correlation_id = '';
	public $retryable = false;
	public function __construct( $code, $message = '', $correlationId = '', $retryable = false ) {
		$this->code = sanitize_key( (string) $code );
		$this->message = sanitize_text_field( (string) $message );
		$this->correlation_id = sanitize_text_field( (string) $correlationId );
		$this->retryable = ! empty( $retryable );
	}
}

interface WaicProviderAdapterInterface extends WaicAIProviderInterface {
	public function getId();
	public function getDeclaredCapabilities();
	public function validateProfile( $profile );
	public function testConnection( $profile = array(), $context = array() );
	public function discoverModels( $profile = array(), $context = array() );
	public function chat( $request );
	public function stream( $request, $sink );
	public function createImage( $request );
	public function pollImage( $job );
	public function embed( $request );
	public function supports( $operation, $profile = array(), $model = '' );
}
interface WaicProviderRerankAdapterInterface { public function rerank( $request ); }
interface WaicProviderFilesAdapterInterface { public function uploadFile( $request ); }
interface WaicProviderFineTuningAdapterInterface { public function createFineTune( $request ); }
