<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicMcp extends WaicModule {
	private $logging = false;
	private $mcpToken = null;
	private $addedFilter = false;
	private $namespace = 'mcp/v1';
	private $sessionID = null;
	private $lastAction = 0;
	private $protocolVersion = '2025-06-18';
	private $serverVersion = '0.0.1';
	private $queueKey = 'aiwu_mcp_msg';
	private $oauthEnabled = false;
  
	public function init() {
		if (WaicFrame::_()->getModule('options')->get('mcp', 'e_mcp')) {
			$this->logging = WaicFrame::_()->getModule('options')->get('mcp', 'mcp_logging');
			$this->oauthEnabled = (bool) WaicFrame::_()->getModule('options')->get('mcp', 'mcp_oauth');
			add_action('rest_api_init', array($this, 'restApiInit'));
			
			if ( $this->logging ) {
				add_action('init', array($this, 'logRequest'), 1);
			}

			// Intercept .well-known OAuth routes early (before WP processes them as pages)
			if ( $this->oauthEnabled ) {
				add_action('parse_request', array($this, 'interceptWellKnown'), 1);
			}
		}
	}
	public function logRequest() {
		if ( !$this->logging || empty( $_SERVER['REQUEST_METHOD'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
		if (strpos( $uri, '/mcp/' ) === false && strpos( $uri, '/aiwu/' ) === false && strpos( $uri, '/.well-known/oauth' ) === false ) {
			return;
		}
		$ignore = array('/wp-admin/', '/wp-cron.php', '/favicon.ico');

		foreach ($ignore as $pattern) {
			if (strpos($uri, $pattern) !== false) {
				return;
			}
		}
		$userAgent = WaicUtils::getUserAgent();
		$ip = WaicUtils::getIP();
		$uri = WaicReq::getRequestUri();
		if ($this->logging) {
			WaicFrame::_()->saveDebugLogging(array('uri' => $uri, 'agent' => $userAgent, 'ip' => $ip, 'method' => sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))), false, 'MCP');
		}
	}

	/**
	 * Intercept .well-known OAuth discovery requests before WP processes them.
	 * Handles:
	 *   GET /.well-known/oauth-protected-resource   (RFC 9728)
	 *   GET /.well-known/oauth-authorization-server  (RFC 8414)
	 */
	public function interceptWellKnown( $wp ) {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH );
		if ( false === $uri ) {
			return;
		}
		$uri = rtrim( $uri, '/' );
		$homePath = wp_parse_url(home_url(), PHP_URL_PATH);
		if (!empty($homePath) && '/' !== $homePath) {
			$homePath = '/' . trim($homePath, '/');
			if (0 === strpos($uri, $homePath . '/') || $uri === $homePath) {
				$uri = substr($uri, strlen($homePath));
				if ('' === $uri) {
					$uri = '/';
				}
			}
		}

		if ( '/.well-known/oauth-protected-resource' === $uri ) {
			$this->serveProtectedResourceMetadata();
		}
		if ( '/.well-known/oauth-authorization-server' === $uri ) {
			$this->serveAuthServerMetadata();
		}
	}

	/**
	 * RFC 9728 – Protected Resource Metadata.
	 * Tells the MCP client which Authorization Server to use.
	 */
	private function serveProtectedResourceMetadata() {
		$siteUrl = home_url();
		$meta = array(
			'resource'                  => rest_url( $this->namespace . '/sse' ),
			'authorization_servers'     => array( $siteUrl ),
			'bearer_methods_supported'  => array( 'header' ),
			'scopes_supported'          => array( 'mcp' ),
		);
		if ( $this->logging ) {
			WaicFrame::_()->saveDebugLogging( 'Serving /.well-known/oauth-protected-resource', false, 'MCP' );
		}
		$this->sendJson( $meta );
	}

	/**
	 * RFC 8414 – Authorization Server Metadata.
	 * Provides endpoints for the OAuth 2.1 flow.
	 */
	private function serveAuthServerMetadata() {
		$siteUrl = home_url();
		$meta = array(
			'issuer'                                 => $siteUrl,
			'authorization_endpoint'                 => rest_url( $this->namespace . '/oauth/authorize' ),
			'token_endpoint'                         => rest_url( $this->namespace . '/oauth/token' ),
			'registration_endpoint'                  => rest_url( $this->namespace . '/oauth/register' ),
			'response_types_supported'               => array( 'code' ),
			'grant_types_supported'                  => array( 'authorization_code', 'refresh_token' ),
			'token_endpoint_auth_methods_supported'  => array( 'none' ),
			'code_challenge_methods_supported'        => array( 'S256' ),
			'scopes_supported'                       => array( 'mcp' ),
		);
		if ( $this->logging ) {
			WaicFrame::_()->saveDebugLogging( 'Serving /.well-known/oauth-authorization-server', false, 'MCP' );
		}
		$this->sendJson( $meta );
	}

	/**
	 * Send a JSON response and exit (for .well-known handlers).
	 */
	private function sendJson( $data, $code = 200 ) {
		status_header( $code );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Cache-Control: no-store' );
		header( 'Access-Control-Allow-Origin: *' );
		echo wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function restApiInit() {
		if ($this->mcpToken === null) {
			$this->mcpToken = WaicFrame::_()->getModule('options')->getModel()->get('mcp', 'mcp_token');
		}

		if (!empty($this->mcpToken) && !$this->addedFilter) {
			WaicDispatcher::addFilter('allow_mcp', array($this, 'authViaBeaberToken'), 10, 2);
			$this->addedFilter = true;
		}
		register_rest_route($this->namespace, '/sse', array(
			'methods' => 'GET',
			'callback' => array($this, 'handleSSE'),
			'permission_callback' => function( $request ) {
				return $this->canAccessMCP($request);
			},
		));
		register_rest_route($this->namespace, '/sse', array(
			'methods' => 'POST',
			'callback' => array($this, 'handleSSE'),
			'permission_callback' => function( $request ) {
				return $this->canAccessMCP($request);
			},
		));
		register_rest_route($this->namespace, '/messages', array(
			'methods' => 'POST',
			'callback' => array($this, 'handleMessage'),
			'permission_callback' => function( $request ) {
				return $this->canAccessMCP($request);
			},
		));
		//WaicDispatcher::applyFilters('mcp_tools', $tools);
		//add_filter( 'mwai_mcp_tools', [ $this, 'register_rest_tools' ] );
		WaicDispatcher::addFilter('mcp_callback', array($this, 'handleCallback'), 10, 4);

		// OAuth 2.1 endpoints (only when OAuth auth mode is enabled)
		if ( $this->oauthEnabled ) {
			register_rest_route($this->namespace, '/oauth/authorize', array(
				'methods'             => 'GET',
				'callback'            => array($this, 'oauthAuthorize'),
				'permission_callback' => '__return_true',
			));
			register_rest_route($this->namespace, '/oauth/token', array(
				'methods'             => 'POST',
				'callback'            => array($this, 'oauthToken'),
				'permission_callback' => '__return_true',
			));
			register_rest_route($this->namespace, '/oauth/register', array(
				'methods'             => 'POST',
				'callback'            => array($this, 'oauthRegister'),
				'permission_callback' => '__return_true',
			));
		}
	}
	public function canAccessMCP( $request ) {
		//return true;
		$isAdmin = current_user_can('manage_options');
		return WaicDispatcher::applyFilters('allow_mcp', $isAdmin, $request);
	}
	public function handleCallback( $result, string $tool, array $args, int $id ) {
		if (!empty($result)) {
			return $result;
		}
		$tools = $this->getModel()->getTools();
		if (!isset($tools[$tool])) {
			WaicFrame::_()->saveDebugLogging('Tool not found ' . $tool, false, 'MCP');
			return $result;
		}
		return $this->getModel()->dispatchTool($tool, $args, $id);
	}

	public function authViaBeaberToken($allow, $request) {

		$hdr = $request->get_header('Authorization');

		if (!$hdr && !empty($this->mcpToken)) {
			$token = sanitize_text_field($request->get_param('token'));

			if ($token && hash_equals($this->mcpToken, $token)) {
				WaicUtils::setAdminUser();
				return true;
			}

			// Check if this is an OAuth-issued access token (in query param)
			if ( $token && $this->oauthEnabled && $this->validateOAuthAccessToken( $token ) ) {
				WaicUtils::setAdminUser();
				return true;
			}

			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('No authorization header provided.', false, 'MCP');
			}
			return false;
		}
		if ($hdr && preg_match('/Bearer\s+(.+)/i', $hdr, $m)) {
			$token = trim($m[1]);
			$result = false;

			// Check direct MCP token
			if (!empty( $this->mcpToken) && hash_equals($this->mcpToken, $token)) {
				WaicUtils::setAdminUser();
				$result = true;
				if ($this->logging && strpos( $request->get_route(), '/sse' ) !== false ) {
					WaicFrame::_()->saveDebugLogging('Auth OK (token)', false, 'MCP');
				}
				return true;
			}

			// Check OAuth-issued access token
			if ( $this->oauthEnabled && $this->validateOAuthAccessToken( $token ) ) {
				WaicUtils::setAdminUser();
				if ( $this->logging ) {
					WaicFrame::_()->saveDebugLogging( 'Auth OK (OAuth)', false, 'MCP' );
				}
				return true;
			}

			if ($this->logging && !$result) {
				WaicFrame::_()->saveDebugLogging('Bearer token invalid', false, 'MCP');
			}
			return false;
		}
		if (!empty($this->mcpToken)) {
			return false;
		}
		return $allow;
	}
	 private function getSSEid($req) {
		$last = $req ? $req->get_header('last-event-id') : '';
		return empty($last) ? str_replace('-', '', wp_generate_uuid4()) : $last;
	}
	// Handle POST request with JSON-RPC body (Direct MCP client behavior)
	// Claude.ai requests directly to the SSE endpoint instead of establishing an SSE connection first. This is non-standard but we need to support it.
	// Expected flow: GET /sse (establish stream) → POST /messages (send JSON-RPC)
	// Actual flow: POST /sse with JSON-RPC body → expects immediate JSON response
	public function handleSSE( WP_REST_Request $request ) {
		$body = $request->get_body();

		if ($request->get_method() === 'POST' && !empty($body)) {
			$data = json_decode($body, true);
			if ($data && isset($data['method'])) {
				return $this->handleDirectJsonRPC($request, $data);
			}
		}

		@ini_set('zlib.output_compression', '0'); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		@ini_set('output_buffering', '0'); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		@ini_set('implicit_flush', '1'); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		if (function_exists('ob_implicit_flush')) {
			ob_implicit_flush( true );
		}

		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('X-Accel-Buffering: no');
		header('Connection: keep-alive');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: Authorization, Content-Type');
		while (ob_get_level()) {
			ob_end_flush();
		}

		$this->sessionID = $this->getSSEid($request);
		$this->lastAction = time();
		//echo "id: {$this->sessionID}\n\n";
		//flush();

		$msgUri = sprintf('%s/messages?session_id=%s', rest_url($this->namespace), $this->sessionID);
		if (!empty($this->mcpToken)) {
			$msgUri .= '&token=' . $this->mcpToken;
		}
		$this->reply('endpoint', $msgUri, 'text');


		if ($this->logging) {
			WaicFrame::_()->saveDebugLogging('SSE connected (' . substr( $this->sessionID, 0, 8 ) . '...)', false, 'MCP');
		}

		while (true) {
			$maxTime = $this->logging ? 60 : 60 * 5;
			$idle = ( time() - $this->lastAction ) >= $maxTime;
			if (connection_aborted() || $idle) {
				$this->reply('bye');
				if ($this->logging) {
					WaicFrame::_()->saveDebugLogging('SSE closed (' . ( $idle ? 'idle' : 'abort' ) . ')', false, 'MCP');
				}
				break;
			}

			foreach ($this->fetchMessages($this->sessionID) as $p) {
				if (isset($p['method']) && 'aiwu/kill' === $p['method']) {
					if ($this->logging) {
						WaicFrame::_()->saveDebugLogging('Kill signal - terminating', false, 'MCP');
					}
					$this->reply('bye');
					exit;
				}
				//WaicFrame::_()->saveDebugLogging('message', false, 'MCP');
				//WaicFrame::_()->saveDebugLogging($p, false, 'MCP');
				$this->reply('message', $p);
			}
			usleep(200000);
			if (time() - $this->lastAction > 10) $this->reply('heartbeat', ['status' => 'alive']);
		}
		exit;
	}
	private function reply( string $event, $data = null, string $enc = 'json' ) {
		if ('bye' === $event) {
			echo "event: bye\ndata: \n\n";
			if (ob_get_level()) {
				ob_end_flush();
			}
			flush();
			$this->lastAction = time();
			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('Clean disconnection (' . substr( $this->sessionID, 0, 8 ) . '...)', false, 'MCP');
			}
			return;
		}

		if ('json' === $enc && null === $data) {
			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('no data for ' . $event, false, 'MCP');
			}
			return;
		}
		echo 'event: ' . esc_html($event) . "\n";
		if ('json' === $enc) {
			$data = null === $data ? '{}' : str_replace('[]', '{}', wp_json_encode($data, JSON_UNESCAPED_UNICODE));
		}
		echo 'data: ' . esc_html($data) . "\n\n";

		if (ob_get_level()) {
			ob_end_flush();
		}
		//ob_flush();
		flush();

		$this->lastAction = time();
		if ($this->logging && 'endpoint' === $event) {
			WaicFrame::_()->saveDebugLogging('SSE endpoint ready', false, 'MCP');
		}
	}

	private function log( $msg ) {
		if ($this->logging) {
			if (strpos($msg, 'queued') === false && strpos($msg, 'flush') === false) {
				WaicFrame::_()->saveDebugLogging('MCP: ' . $msg, false, 'MCP');
			}
		}
	}
  

	private function rpcError( $id, int $code, string $msg, $extra = null ): array {
		$err = array('code' => $code, 'message' => $msg);
		if (!is_null($extra)) {
			$err['data'] = $extra;
		}
		return array('jsonrpc' => '2.0', 'id' => $id, 'error' => $err);
	}

	private function queueError( $sess, $id, int $code, string $msg, $extra = null ): void {
		$this->storeMessage($sess, $this->rpcError($id, $code, $msg, $extra));
	}

	private function formatToolResult( $result ): array {
		if (is_string($result)) {
			return array(
				'content' => array(
					array(
						'type' => 'text',
						'text' => $result,
					),
				),
			);
		}

		if (is_array($result) && isset($result['content'])) {
			return $result;
		}
		if (is_array($result)) {
			return array(
				'content' => array(
					array(
						'type' => 'text',
						'text' => wp_json_encode($result, JSON_PRETTY_PRINT),
					),
				),
				'data' => $result,
			);
		}
		return array(
			'content' => array(
				array(
					'type' => 'text',
					'text' => (string) $result,
				),
			),
		);
	}


	//Claude's MCP client (via Anthropic API) sends JSON-RPC requests directly to the SSE endpoint
	//as POST requests, rather than following the typical SSE flow:
	//- Normal flow: GET /sse → establish SSE stream → POST /messages for JSON-RPC
	//- Claude's flow: POST /sse with JSON-RPC body → expect immediate JSON response
	private function handleDirectJsonRPC( WP_REST_Request $request, $data ) {

		$id = isset($data['id']) ? $data['id'] : null;
		$method = isset($data['method']) ? $data['method'] : null;

		if (json_last_error() !== JSON_ERROR_NONE) {
			return new WP_REST_Response(array(
				'jsonrpc' => '2.0',
				'id' => null,
				'error' => array('code' => -32700, 'message' => 'Parse error: invalid JSON'),
			), 200);
		}

		if (!is_array($data) || !$method) {
			return new WP_REST_Response(array(
				'jsonrpc' => '2.0',
				'id' => $id,
				'error' => array('code' => -32600, 'message' => 'Invalid Request'),
			), 200);
		}

		try {
			$reply = null;

			switch ($method) {
				case 'initialize':
					$params = WaicUtils::getArrayValue($data, 'params', array(), 2);
					$reqVersion = WaicUtils::getArrayValue($params, 'protocolVersion', null);
					$clientInfo = WaicUtils::getArrayValue($params, 'clientInfo', false);

					if ($this->logging && is_array($clientInfo)) {
						WaicFrame::_()->saveDebugLogging('Client: ' . WaicUtils::getArrayValue($clientInfo, 'name', 'unknown') . ' v:' . WaicUtils::getArrayValue($clientInfo, 'version', 'unknown'), false, 'MCP');
					}

					if ($this->logging && $reqVersion && $reqVersion !== $this->protocolVersion ) {
						WaicFrame::_()->saveDebugLogging('!Client requested protocol version is ' . $reqVersion . '. Supported only ' . $this->protocolVersion, false, 'MCP');
					}

					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array(
							'protocolVersion' => $this->protocolVersion,
							'serverInfo' => (object) array(
								'name' => get_bloginfo('name') . ' MCP',
								'version' => $this->serverVersion,
							),
							'capabilities' => array(
								'tools' => array('listChanged' => true),
								'prompts' => array('subscribe' => false, 'listChanged' => false),
								'resources' => array('subscribe' => false, 'listChanged' => false),
							),
						),
					);
					break;
				case 'tools/list':
					$tools = $this->getToolsList();
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('tools' => $tools),
					);
					if ($this->logging) {
						WaicFrame::_()->saveDebugLogging('Returning ' . count( $tools ) . ' tools.', false, 'MCP');
					}
					break;
				case 'tools/call':
					$params = WaicUtils::getArrayValue($data, 'params', array(), 2);
					$tool = WaicUtils::getArrayValue($params, 'name');
					$arguments = WaicUtils::getArrayValue($params, 'arguments', array(), 2);
					$reply = $this->executeTool($tool, $arguments, $id);
					break;
				case 'notifications/initialized':
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'method' => 'tools/listChanged',
					);
					//$this->reply('tools/listChanged', array('jsonrpc' => '2.0', 'method' => 'tools/listChanged', 'params' => array()));
					//return new WP_REST_Response(null, 204);
					break;
				case 'resources/list':
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('resources' => array()),
					);
					break;
				case 'prompts/list':
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('prompts' => array()),
					);
					break;


				default:
					if (is_null($id) && strpos($method, 'notifications/') === 0) {
						if ($this->logging) {
							WaicFrame::_()->saveDebugLogging('Notification received: ' . $method, false, 'MCP');
						}
						return new WP_REST_Response(null, 204);
					}
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'error' => array('code' => -44001, 'message' => "Method not found: {$method}"),
					);
			}
			$response = new WP_REST_Response($reply, 200);
			$response->set_headers(array('Content-Type' => 'application/json'));

			return $response;
		}
		catch ( Exception $e ) {
			$response = new WP_REST_Response(array(
				'jsonrpc' => '2.0',
				'id' => $id,
				'error' => array('code' => -44000, 'message' => 'Internal error', 'data' => $e->getMessage())
			), 200);
			$response->set_headers(array('Content-Type' => 'application/json'));
			return $response;
		}
	}
	public function handleMessage( WP_REST_Request $request ) {
		$sess = sanitize_text_field($request->get_param('session_id'));
		$body = $request->get_body();
		$data = json_decode($body, true);
		if ( $this->logging && $data && isset($data['method'])) {
			$method = $data['method'];
			if (!in_array($method, array('notifications/initialized', 'notifications/cancelled'))) {
				WaicFrame::_()->saveDebugLogging('Handle method: ' . $method, false, 'MCP');
			}
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->queueError($sess, null, -44500, 'Parse error: invalid JSON');
			return new WP_REST_Response(null, 204);
		}
		if (!is_array($data)) {
			$this->queueError($sess, null, -44800, 'Invalid Request');
			return new WP_REST_Response(null, 204);
		}
		$id = isset($data['id']) ? $data['id'] : null;
		$method = WaicUtils::getArrayValue($data, 'method', null);
		if ('initialized' === $method) {
			return new WP_REST_Response(null, 204);
		}
		if ('aiwu/kill' === $method) {
			$this->storeMessage($sess, array('jsonrpc' => '2.0', 'method' => 'aiwu/kill'));
			usleep( 100000 );
			return new WP_REST_Response(null, 204);
		}
		if (is_null($id) && !is_null($method)) {
			return new WP_REST_Response(null, 204);
		}
		if (!$method) {
			$this->queueError($sess, $id, -32900, 'Invalid Request: method missing');
			return new WP_REST_Response(null, 204);
		}

		try {
			$reply = null;
			switch ($method) {
				case 'initialize':
					$params = WaicUtils::getArrayValue($data, 'params', array(), 2);
					$requestedVersion = WaicUtils::getArrayValue($params, 'protocolVersion', null);
					$clientInfo = WaicUtils::getArrayValue($params, 'clientInfo', null);

					if ($this->logging && is_array($clientInfo)) {
						WaicFrame::_()->saveDebugLogging('Client: ' . WaicUtils::getArrayValue($clientInfo, 'name', 'unknown') . ' v' . WaicUtils::getArrayValue($clientInfo, 'version', 'unknown'), false, 'MCP');
					}

					if ($requestedVersion && $requestedVersion !== $this->protocolVersion) {
						if ($this->logging) {
							WaicFrame::_()->saveDebugLogging('Client requested protocol version is ' . $requestedVersion . '. Supported only ' . $this->protocolVersion, false, 'MCP');
						}
					}

					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array(
							'protocolVersion' => $this->protocolVersion,
							'serverInfo' => (object) array(
								'name' => get_bloginfo( 'name' ) . ' MCP',
								'version' => $this->serverVersion,
							),
							'capabilities' => array(
								'tools' => array('listChanged' => true),
								'prompts' => array('subscribe' => false, 'listChanged' => false),
								'resources' => array('subscribe' => false, 'listChanged' => false),
							),
						),
					);
					break;
				case 'tools/list':
					$tools = $this->getToolsList();
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('tools' => $tools),
					);
					break;
				case 'resources/list':
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('resources' => $this->getResourcesList()),
					);
					break;
				case 'prompts/list':
					$reply = array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => array('prompts' => $this->getPromptsList()),
					);
					break;
				case 'tools/call':
					$params = WaicUtils::getArrayValue($data, 'params', array(), 2);
					$tool = WaicUtils::getArrayValue($params, 'name');
					$arguments = WaicUtils::getArrayValue($params, 'arguments', array(), 2);
					$reply = $this->executeTool($tool, $arguments, $id);
					break;
				default:
					$reply = $this->rpcError($id, -45601, "Method not found: {$method}");
			}
			if ($reply) {
				$this->storeMessage($sess, $reply);
			}
		}
		catch ( Exception $e ) {
			$this->queueError($sess, $id, -45603, 'Internal error', $e->getMessage() );
		}

		return new WP_REST_Response(null, 204);
	}

	public function getToolsList() {
		return $this->getModel()->getToolsList();
	}

	private function getResourcesList() {
		return array();
	}
	private function getPromptsList() {
		return array();
	}

	private function executeTool( $tool, $args, $id ) {
		try {
			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('Tool: ' . $tool, false, 'MCP');
				if (!empty($args)) {
					WaicFrame::_()->saveDebugLogging($args, false, 'MCP');
				}
			}
			$filtered = WaicDispatcher::applyFilters('mcp_callback', null, $tool, $args, $id, $this);

			if (!is_null($filtered)) {
				if (is_array($filtered) && isset($filtered['jsonrpc']) && isset($filtered['id'])) {
					return $filtered;
				}
				return array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'result' => $this->formatToolResult($filtered),
				);
			}
			throw new Exception("Unknown tool: {$tool}");
		}
		catch ( Exception $e ) {
			return $this->rpcError( $id, -44003, $e->getMessage() );
		}
	}


	private function transientKey( $sess, $id ) {
		return "{$this->queueKey}_{$sess}_{$id}";
	}

	private function storeMessage( $sess, $payload ) {
		if (!$sess) {
			return;
		}
		$idKey = array_key_exists('id', $payload) ? ( isset($payload['id']) ? $payload['id'] : 'NULL' ) : 'N/A';
		set_transient($this->transientKey($sess, $idKey), $payload, 30);

		$this->log("queued #{$idKey}");
	}

	private function fetchMessages( $sess ) {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . "{$this->queueKey}_{$sess}_" ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",  $like),
			ARRAY_A
		);

		$msgs = array();
		foreach ($rows as $r) {
			$msgs[] = maybe_unserialize($r['option_value']);
			delete_option( $r['option_name'] );
		}
		usort($msgs, function( $a, $b ) {
			$aId = isset($a['id']) ? $a['id'] : 0;
			$bId = isset($b['id']) ? $b['id'] : 0;

			if ($aId == $bId) {
				return 0;
			}
			return ($aId < $bId) ? -1 : 1;
		});
		if ($msgs) {
			$this->log( 'flush ' . count( $msgs ) . ' msg(s)' );
		}
		return $msgs;
	}

	// ──────────────────────────────────────────────────────────────
	// OAuth 2.1 Endpoints  (RFC 8414, RFC 9728, RFC 7591, PKCE)
	// ──────────────────────────────────────────────────────────────

	/**
	 * Validate an OAuth-issued access token stored in transients.
	 */
	private function validateOAuthAccessToken( $token ) {
		if ( empty( $token ) || strlen( $token ) < 32 ) {
			return false;
		}
		$key = 'aiwu_oauth_at_' . hash( 'sha256', $token );
		$data = get_transient( $key );
		if ( ! $data || ! is_array( $data ) ) {
			return false;
		}
		if ( ! empty( $data['expires'] ) && time() > $data['expires'] ) {
			delete_transient( $key );
			return false;
		}
		return true;
	}

	/**
	 * Store an OAuth access token in transients.
	 */
	private function storeOAuthAccessToken( $token, $ttl = 3600 ) {
		$key = 'aiwu_oauth_at_' . hash( 'sha256', $token );
		set_transient( $key, array(
			'created' => time(),
			'expires' => time() + $ttl,
		), $ttl + 60 );
	}

	/**
	 * Store an OAuth refresh token in transients.
	 */
	private function storeOAuthRefreshToken( $refreshToken, $accessToken, $ttl = 86400 ) {
		$key = 'aiwu_oauth_rt_' . hash( 'sha256', $refreshToken );
		set_transient( $key, array(
			'access_token' => $accessToken,
			'created'      => time(),
			'expires'      => time() + $ttl,
		), $ttl + 60 );
	}

	/**
	 * Validate and consume a refresh token. Returns access_token data or false.
	 */
	private function consumeRefreshToken( $refreshToken ) {
		$key = 'aiwu_oauth_rt_' . hash( 'sha256', $refreshToken );
		$data = get_transient( $key );
		if ( ! $data || ! is_array( $data ) ) {
			return false;
		}
		if ( ! empty( $data['expires'] ) && time() > $data['expires'] ) {
			delete_transient( $key );
			return false;
		}
		// Rotate: delete old refresh token
		delete_transient( $key );
		return $data;
	}

	/**
	 * Generate a cryptographically random token string.
	 */
	private function generateToken( $length = 48 ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}

	/**
	 * Verify PKCE code_verifier against stored code_challenge (S256).
	 */
	private function verifyPKCE( $verifier, $challenge ) {
		$computed = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
		return hash_equals( $challenge, $computed );
	}

	/**
	 * POST /oauth/register — Dynamic Client Registration (RFC 7591).
	 * Claude.ai sends client_name, redirect_uris, etc.
	 * We issue a client_id (no secret needed for public clients).
	 */
	public function oauthRegister( WP_REST_Request $request ) {
		$body = json_decode( $request->get_body(), true );
		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_request' ), 400 );
		}

		$clientName = sanitize_text_field( isset( $body['client_name'] ) ? $body['client_name'] : 'MCP Client' );
		$redirectUris = isset( $body['redirect_uris'] ) && is_array( $body['redirect_uris'] ) ? $body['redirect_uris'] : array();

		// Sanitize redirect URIs
		$cleanUris = array();
		foreach ( $redirectUris as $uri ) {
			$clean = esc_url_raw( $uri );
			if ( $clean ) {
				$cleanUris[] = $clean;
			}
		}

		$clientId = 'aiwu_' . $this->generateToken( 24 );

		// Store client registration (7 days TTL)
		set_transient( 'aiwu_oauth_client_' . $clientId, array(
			'client_id'     => $clientId,
			'client_name'   => $clientName,
			'redirect_uris' => $cleanUris,
			'created'       => time(),
		), 7 * DAY_IN_SECONDS );

		if ( $this->logging ) {
			WaicFrame::_()->saveDebugLogging( 'DCR: registered client "' . $clientName . '" → ' . $clientId, false, 'MCP' );
		}

		$response = new WP_REST_Response( array(
			'client_id'                  => $clientId,
			'client_name'                => $clientName,
			'redirect_uris'              => $cleanUris,
			'grant_types'                => array( 'authorization_code', 'refresh_token' ),
			'response_types'             => array( 'code' ),
			'token_endpoint_auth_method' => 'none',
		), 201 );
		$response->set_headers( array( 'Cache-Control' => 'no-store' ) );
		return $response;
	}

	/**
	 * GET /oauth/authorize — Authorization Endpoint.
	 *
	 * Claude.ai redirects the user here. We show a simple consent page.
	 * On approval, we redirect back with an authorization code.
	 *
	 * Query params: client_id, redirect_uri, state, scope,
	 *               code_challenge, code_challenge_method, response_type
	 */
	public function oauthAuthorize( WP_REST_Request $request ) {
		$clientId            = sanitize_text_field( $request->get_param( 'client_id' ) );
		$redirectUri         = esc_url_raw( $request->get_param( 'redirect_uri' ) );
		$state               = sanitize_text_field( $request->get_param( 'state' ) );
		$scope               = sanitize_text_field( $request->get_param( 'scope' ) );
		$codeChallenge       = sanitize_text_field( $request->get_param( 'code_challenge' ) );
		$codeChallengeMethod = sanitize_text_field( $request->get_param( 'code_challenge_method' ) );
		$responseType        = sanitize_text_field( $request->get_param( 'response_type' ) );

		// Validate required params
		if ( 'code' !== $responseType || empty( $redirectUri ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_request', 'error_description' => 'response_type must be "code" and redirect_uri is required' ), 400 );
		}

		// Validate PKCE is present (required for public clients)
		if ( empty( $codeChallenge ) || 'S256' !== $codeChallengeMethod ) {
			return new WP_REST_Response( array( 'error' => 'invalid_request', 'error_description' => 'PKCE with S256 is required' ), 400 );
		}

		// Check if user submitted the approval form
		$approved = sanitize_text_field( $request->get_param( 'approved' ) );
		$formNonce = sanitize_text_field( $request->get_param( '_wpnonce' ) );

		if ( '1' === $approved && wp_verify_nonce( $formNonce, 'aiwu_mcp_oauth_approve' ) ) {
			// Generate authorization code
			$code = $this->generateToken( 32 );

			// Store code with PKCE challenge (5 min TTL)
			set_transient( 'aiwu_oauth_code_' . hash( 'sha256', $code ), array(
				'client_id'      => $clientId,
				'redirect_uri'   => $redirectUri,
				'code_challenge' => $codeChallenge,
				'scope'          => $scope,
				'created'        => time(),
			), 5 * MINUTE_IN_SECONDS );

			if ( $this->logging ) {
				WaicFrame::_()->saveDebugLogging( 'OAuth: authorization code issued for ' . $clientId, false, 'MCP' );
			}

			// Redirect back to client with code
			$callbackUrl = add_query_arg( array(
				'code'  => $code,
				'state' => $state,
			), $redirectUri );

			header( 'Location: ' . $callbackUrl, true, 302 );
			exit;
		}

		// Show consent page
		$this->renderAuthorizePage( $request, $clientId, $scope );
	}

	/**
	 * Render the OAuth authorization/consent HTML page.
	 */
	private function renderAuthorizePage( $request, $clientId, $scope ) {
		$siteName  = get_bloginfo( 'name' );
		$nonce     = wp_create_nonce( 'aiwu_mcp_oauth_approve' );
		$actionUrl = rest_url( $this->namespace . '/oauth/authorize' );

		// Collect all original params for the form
		$params = array(
			'client_id'             => $request->get_param( 'client_id' ),
			'redirect_uri'          => $request->get_param( 'redirect_uri' ),
			'state'                 => $request->get_param( 'state' ),
			'scope'                 => $request->get_param( 'scope' ),
			'code_challenge'        => $request->get_param( 'code_challenge' ),
			'code_challenge_method' => $request->get_param( 'code_challenge_method' ),
			'response_type'         => $request->get_param( 'response_type' ),
			'resource'              => $request->get_param( 'resource' ),
		);

		// Resolve client name
		$clientName = 'MCP Client';
		if ( ! empty( $clientId ) ) {
			$clientData = get_transient( 'aiwu_oauth_client_' . $clientId );
			if ( $clientData && ! empty( $clientData['client_name'] ) ) {
				$clientName = esc_html( $clientData['client_name'] );
			}
		}

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		echo '<title>' . esc_html( $siteName ) . ' – MCP Authorization</title>';
		echo '<style>
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 460px; margin: 60px auto; padding: 20px; background: #f0f0f1; color: #1d2327; }
			.card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
			h1 { font-size: 20px; margin: 0 0 16px; }
			.scope { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; padding: 8px 12px; margin: 12px 0; font-size: 14px; }
			.btn { display: inline-block; padding: 8px 18px; border-radius: 3px; font-size: 14px; cursor: pointer; border: 1px solid transparent; text-decoration: none; }
			.btn-primary { background: #2271b1; color: #fff; border-color: #2271b1; }
			.btn-primary:hover { background: #135e96; }
			.btn-cancel { background: #f0f0f1; color: #50575e; border-color: #c3c4c7; margin-left: 8px; }
			.actions { margin-top: 20px; text-align: right; }
			.info { color: #646970; font-size: 13px; margin-top: 12px; }
		</style></head><body><div class="card">';
		/* translators: %s - client name */
		echo '<h1>' . esc_html( sprintf( __( 'Authorize %s', 'ai-copilot-content-generator' ), $clientName ) ) . '</h1>';
		/* translators: %1$s - client name, %2$s - site name */
		echo '<p>' . esc_html( sprintf( __( '%1$s wants to access your MCP tools on %2$s.', 'ai-copilot-content-generator' ), $clientName, $siteName ) ) . '</p>';

		if ( $scope ) {
			echo '<div class="scope"><strong>' . esc_html__( 'Requested scope:', 'ai-copilot-content-generator' ) . '</strong> ' . esc_html( $scope ) . '</div>';
		}

		echo '<form method="GET" action="' . esc_url( $actionUrl ) . '">';
		foreach ( $params as $k => $v ) {
			if ( $v !== null ) {
				echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
			}
		}
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
		echo '<input type="hidden" name="approved" value="1">';
		echo '<div class="actions">';
		echo '<button type="submit" class="btn btn-primary">' . esc_html__( 'Authorize', 'ai-copilot-content-generator' ) . '</button>';
		echo '</div>';
		echo '</form>';
		echo '<p class="info">' . esc_html__( 'You can revoke this access at any time from the AI client settings.', 'ai-copilot-content-generator' ) . '</p>';
		echo '</div></body></html>';
		exit;
	}

	/**
	 * POST /oauth/token — Token Endpoint.
	 *
	 * Handles:
	 *   grant_type=authorization_code  → exchange code for access + refresh token
	 *   grant_type=refresh_token       → exchange refresh token for new tokens
	 */
	public function oauthToken( WP_REST_Request $request ) {
		$body = $request->get_body_params();
		if ( empty( $body ) ) {
			$body = json_decode( $request->get_body(), true );
		}
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$grantType = sanitize_text_field( isset( $body['grant_type'] ) ? $body['grant_type'] : '' );

		if ( 'authorization_code' === $grantType ) {
			return $this->oauthTokenFromCode( $body );
		}
		if ( 'refresh_token' === $grantType ) {
			return $this->oauthTokenFromRefresh( $body );
		}

		return new WP_REST_Response( array(
			'error'             => 'unsupported_grant_type',
			'error_description' => 'Only authorization_code and refresh_token are supported.',
		), 400 );
	}

	/**
	 * Exchange authorization code for tokens.
	 */
	private function oauthTokenFromCode( $body ) {
		$code         = sanitize_text_field( isset( $body['code'] ) ? $body['code'] : '' );
		$codeVerifier = isset( $body['code_verifier'] ) ? $body['code_verifier'] : '';
		$redirectUri  = esc_url_raw( isset( $body['redirect_uri'] ) ? $body['redirect_uri'] : '' );

		if ( empty( $code ) || empty( $codeVerifier ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_request', 'error_description' => 'code and code_verifier are required' ), 400 );
		}

		// Retrieve and delete the authorization code
		$codeKey  = 'aiwu_oauth_code_' . hash( 'sha256', $code );
		$codeData = get_transient( $codeKey );
		delete_transient( $codeKey );

		if ( ! $codeData || ! is_array( $codeData ) ) {
			if ( $this->logging ) {
				WaicFrame::_()->saveDebugLogging( 'OAuth: invalid/expired authorization code', false, 'MCP' );
			}
			return new WP_REST_Response( array( 'error' => 'invalid_grant', 'error_description' => 'Authorization code is invalid or expired' ), 400 );
		}

		// Verify redirect_uri matches
		if ( $redirectUri && $codeData['redirect_uri'] !== $redirectUri ) {
			return new WP_REST_Response( array( 'error' => 'invalid_grant', 'error_description' => 'redirect_uri mismatch' ), 400 );
		}

		// Verify PKCE
		if ( ! $this->verifyPKCE( $codeVerifier, $codeData['code_challenge'] ) ) {
			if ( $this->logging ) {
				WaicFrame::_()->saveDebugLogging( 'OAuth: PKCE verification failed', false, 'MCP' );
			}
			return new WP_REST_Response( array( 'error' => 'invalid_grant', 'error_description' => 'PKCE verification failed' ), 400 );
		}

		// Issue tokens
		$accessToken  = $this->generateToken( 48 );
		$refreshToken = $this->generateToken( 48 );
		$expiresIn    = 3600; // 1 hour

		$this->storeOAuthAccessToken( $accessToken, $expiresIn );
		$this->storeOAuthRefreshToken( $refreshToken, $accessToken, 7 * DAY_IN_SECONDS );

		if ( $this->logging ) {
			WaicFrame::_()->saveDebugLogging( 'OAuth: tokens issued for ' . $codeData['client_id'], false, 'MCP' );
		}

		$response = new WP_REST_Response( array(
			'access_token'  => $accessToken,
			'token_type'    => 'Bearer',
			'expires_in'    => $expiresIn,
			'refresh_token' => $refreshToken,
			'scope'         => isset( $codeData['scope'] ) ? $codeData['scope'] : 'mcp',
		), 200 );
		$response->set_headers( array(
			'Cache-Control' => 'no-store',
			'Pragma'        => 'no-cache',
		) );
		return $response;
	}

	/**
	 * Exchange refresh token for new tokens.
	 */
	private function oauthTokenFromRefresh( $body ) {
		$refreshToken = sanitize_text_field( isset( $body['refresh_token'] ) ? $body['refresh_token'] : '' );

		if ( empty( $refreshToken ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_request' ), 400 );
		}

		$data = $this->consumeRefreshToken( $refreshToken );
		if ( ! $data ) {
			return new WP_REST_Response( array( 'error' => 'invalid_grant', 'error_description' => 'Refresh token is invalid or expired' ), 400 );
		}

		// Revoke old access token
		if ( ! empty( $data['access_token'] ) ) {
			delete_transient( 'aiwu_oauth_at_' . hash( 'sha256', $data['access_token'] ) );
		}

		// Issue new tokens
		$newAccessToken  = $this->generateToken( 48 );
		$newRefreshToken = $this->generateToken( 48 );
		$expiresIn       = 3600;

		$this->storeOAuthAccessToken( $newAccessToken, $expiresIn );
		$this->storeOAuthRefreshToken( $newRefreshToken, $newAccessToken, 7 * DAY_IN_SECONDS );

		if ( $this->logging ) {
			WaicFrame::_()->saveDebugLogging( 'OAuth: tokens refreshed', false, 'MCP' );
		}

		$response = new WP_REST_Response( array(
			'access_token'  => $newAccessToken,
			'token_type'    => 'Bearer',
			'expires_in'    => $expiresIn,
			'refresh_token' => $newRefreshToken,
			'scope'         => 'mcp',
		), 200 );
		$response->set_headers( array(
			'Cache-Control' => 'no-store',
			'Pragma'        => 'no-cache',
		) );
		return $response;
	}
}
