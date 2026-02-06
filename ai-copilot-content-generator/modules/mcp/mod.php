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
  
	public function init() {
		if (WaicFrame::_()->getModule('options')->get('mcp', 'e_mcp')) {
			$this->logging = WaicFrame::_()->getModule('options')->get('mcp', 'mcp_logging');
			add_action('rest_api_init', array($this, 'restApiInit'));
			
			if ( $this->logging ) {
				add_action('init', array($this, 'logRequest'), 1);
			}
		}
	}
	public function logRequest() {
		if ( !$this->logging || empty( $_SERVER['REQUEST_METHOD'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = $_SERVER['REQUEST_URI'];
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
			WaicFrame::_()->saveDebugLogging(array('uri' => $uri, 'agent' => $userAgent, 'ip' => $ip, 'method' => $_SERVER['REQUEST_METHOD']), false, 'MCP');
		}
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
	}
	public function canAccessMCP( $request ) {
		//return true;
		$isAdmin = current_user_can('manage_options');
		return WaicDispatcher::applyFilters('allow_mcp', $isAdmin, $request);
	}
	public function handleCallback( $result, string $tool, $args, $id ) {
		if (!empty($result)) {
			return $result;
		}
		
		// Ensure $args is array
		if (!is_array($args)) {
			$args = array();
		}
		
		// Strip namespace prefix if present (e.g., "wupsales:wp_get_posts" -> "wp_get_posts")
		$originalTool = $tool;
		if (strpos($tool, ':') !== false) {
			$parts = explode(':', $tool, 2);
			$tool = $parts[1];
		}
		
		$tools = $this->getModel()->getTools();
		if (!isset($tools[$tool])) {
			WaicFrame::_()->saveDebugLogging('Tool not found: ' . $originalTool . ' (parsed as: ' . $tool . ')', false, 'MCP');
			return $result;
		}
		return $this->getModel()->dispatchTool($tool, $args, $id);
	}

	public function authViaBeaberToken($allow, $request) {
		/*if ($allow) {
			return $allow;
		}*/

		$hdr = $request->get_header('Authorization');

		if (!$hdr && !empty($this->mcpToken)) {
			$token = sanitize_text_field($request->get_param('token'));
			
			/*if ($request->get_method() === 'POST' && strpos( $request->get_route(), '/sse' ) === false) {
				WaicFrame::_()->saveDebugLogging('Need GET-method.', false, 'MCP');
				return false;
			}*/

			if ($token && hash_equals($this->mcpToken, $token)) {
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

			if (!empty( $this->mcpToken) && hash_equals($this->mcpToken, $token)) {
				WaicUtils::setAdminUser();
				$result = true;
				if ($this->logging && strpos( $request->get_route(), '/sse' ) !== false ) {
					WaicFrame::_()->saveDebugLogging('Auth OK', false, 'MCP');
				}
				return true;
			}

			if ($this->logging && !$result) {
				WaicFrame::_()->saveDebugLogging('Bearer token invalid', false, 'MCP');
			}
			return false;
		}
		/*if (!empty($this->mcpToken)) {
			$q = sanitize_text_field($request->get_param('token'));
			if ($q && hash_equals($this->mcpToken, $q)) {
				WaicUtils::setAdminUser();
				return true;
			}
		}*/
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

		@ini_set('zlib.output_compression', '0');
		@ini_set('output_buffering', '0');
		@ini_set('implicit_flush', '1');
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
		echo 'event: ' . $event . "\n";
		if ('json' === $enc) {
    // Determine if we need to force an object (only if data is exactly an empty array and intended as an object)
    // However, since your mcp.php already casts properties to (object), standard encoding is safer.
    		$data = null === $data ? '{}' : wp_json_encode($data, JSON_UNESCAPED_UNICODE);	
		}
		echo 'data: ' . $data . "\n\n";

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
		
		if ($this->logging) {
			$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
			WaicFrame::_()->saveDebugLogging('handleDirectJsonRPC from: ' . $userAgent, false, 'MCP');
			WaicFrame::_()->saveDebugLogging('Request data: ' . wp_json_encode($data), false, 'MCP');
		}

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
			// Ensure $args is array
			if (!is_array($args)) {
				$args = array();
			}
			
			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('executeTool called: ' . $tool . ' (id: ' . (is_scalar($id) ? $id : gettype($id)) . ')', false, 'MCP');
				if (!empty($args)) {
					WaicFrame::_()->saveDebugLogging($args, false, 'MCP');
				}
			}
			$filtered = WaicDispatcher::applyFilters('mcp_callback', null, $tool, $args, $id, $this);
			
			if ($this->logging) {
				WaicFrame::_()->saveDebugLogging('mcp_callback result: ' . (is_null($filtered) ? 'NULL' : 'has value'), false, 'MCP');
			}

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
}
