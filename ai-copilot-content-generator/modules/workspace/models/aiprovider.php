<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaicAiproviderModel extends WaicModel implements WaicAIProviderInterface {
	const EST_FLAG_TOKENS = 1;
	const EST_FLAG_COST = 2;
	const EST_FLAG_ABORTED = 4;
	const EST_FLAG_CACHE_MAJ = 8;
	const EST_FLAG_FINE_TUNED_BASE_PRICING = 16;
	const EST_FLAG_PRICING_UNKNOWN = 32;

	private $provider = null;
	private $imageProvider = null;
	private $taskId;
	private $feature = '';
	private $userId;
	private $userIP;
	private $genMode;
	private $saveError = true;
	private $sessionId = null;
	private $lastDurationMs = null;
	
	public function getEngine( $type = '' ) {
		switch ( $type ) {
			case 'image':
				return $this->imageProvider->getEngine();
			default:
				return $this->provider->getEngine();
		}
	}
	public function getEngineModel( $type = '' ) {
		switch ( $type ) {
			case 'image':
				return $this->imageProvider->getEngineModel($type);
			default:
				return $this->provider->getEngineModel($type);
		}
	}

	public function getInstance( $params ) {
		$defaults = WaicFrame::_()->getModule('options')->getModel()->getDefaults('api');

		$engine = $this->getModelName(WaicUtils::getArrayValue($params, 'engine'));
		if (empty($engine)) {
			WaicFrame::_()->pushError(esc_html__('AI Provider not found.', 'ai-copilot-content-generator'));
			return false;
		}
		
		$this->provider = $this->getModule()->getModel($engine);
		if ( !$this->provider ) {
			WaicFrame::_()->pushError(esc_html__('AI Provider not found', 'ai-copilot-content-generator'));
			return false;
		}
		
		$this->imageProvider = $this->getModule()->getModel($this->getModelName(WaicUtils::getArrayValue($params, 'image_engine', $defaults['image_engine'])));

		return $this;
	}

	public function init( $taskId = 0, $userId = 0, $userIP = '', $genMode = 0, $saveError = true ) {
		$this->taskId = $taskId;
		$this->userId = $userId;
		$this->userIP = $userIP;
		$this->genMode = $genMode;
		$this->saveError = $saveError;
		if (!empty($taskId)) {
			$this->feature = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTaskFeature($taskId);
		}

		if ( $this->imageProvider ) {
			$this->imageProvider->init();
		}

		return $this->provider->init();
	}
	public function setFeature( $feature ) {
		$this->feature = $feature;
	}
	public function setSessionId( $sessionId ) {
		$sessionId = trim(sanitize_text_field((string) $sessionId));
		$this->sessionId = empty($sessionId) ? null : substr($sessionId, 0, 64);
		return $this;
	}
	public function setSaveError( $saveError ) {
		$this->saveError = $saveError;
	}

	public function setApiOptions( $options ) {
		$result =  $this->provider->setApiOptions($options);
		if (false === $result && $this->saveError) {
			WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($this->taskId, array('status' => 7, 'message' => substr(WaicFrame::_()->getLastError(), 0, 240)));
		}

		if ( $this->imageProvider ) {
			$this->imageProvider->setApiOptions($options);
		}

		return $result;
	}
	
	public function getModels() {
		return $this->provider->getModels();
	}

	public function getText( $params, $stream = null, $type = '' ) {
		$step = 0;
		$isTools = false;
		$maxSteps = 5;
		$start = microtime(true);
		$stepUsages = array();
		$toolNames = array();
		if (!empty($params['tools'])) {
			$toolsOptions = $params['tools']['options'];
			$params['tools'] = $this->getToolsList($params['tools']['functions']);
			$isTools = true;
		}
		do {
			$step++;
			if($isTools) {
				set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			}
			$data = $this->provider->getText( $params, $stream );

			if (false === $data) {
				$results['error'] = 1;
				$results['msg'] = WaicFrame::_()->getLastError();
				$this->lastDurationMs = $this->elapsedMs($start);
				$usage = self::aggregateUsages($stepUsages);
				if (empty($usage['total_tokens']) && empty($usage['input_tokens']) && empty($usage['output_tokens'])) {
					$usage = $this->usageFromResults($results, $params, 'chat');
				}
				$usage['est_flags'] |= self::EST_FLAG_ABORTED;
				$history = $this->getHistory($results, $params, $type, '', $usage, array('steps_count' => $step, 'tool_names' => $toolNames, 'tool_calls_count' => count($toolNames)));
				$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);
				return $results;
			}

			$results = $data['results'];
			$params = $data['params'];
			$usage = $this->usageFromResults($results, $params, 'chat');
			$stepUsages[] = $usage;
			if ($isTools && $step < $maxSteps && $results['data'] == 'tool_calls' && !empty($results['tools']) && !empty($results['tools'][0])) {
				$tool = $results['tools'][0];
				$name = empty($tool->function->name) ? '' : $tool->function->name;
				if (!empty($name)) {
					$toolNames[] = sanitize_key($name);
				}
				if (empty($tool->function->arguments)) {
					$args = array();
				} else if (is_string($tool->function->arguments)) {
					$args = json_decode($tool->function->arguments, true);
				} else {
					$args = $tool->function->arguments;
				}
				$answer = $this->doTool($name, $args, $toolsOptions);
				if (!empty($params['messages']) && is_array($params['messages'])) {
					$params['messages'][] = empty($results['tools_message']) ? array(
						'role' => 'assistant',
						'tool_calls' => array($tool),
					) : $results['tools_message'];
					$params['messages'][] = $this->provider->getToolsAnswer($answer, $tool);
				}
				continue;
			}
			$this->lastDurationMs = $this->elapsedMs($start);
			$usage = self::aggregateUsages($stepUsages);
			$results['tokens'] = (int) $usage['total_tokens'];
			$history = $this->getHistory($results, $params, $type, '', $usage, array(
				'steps_count' => $step,
				'tool_names' => array_values(array_unique($toolNames)),
				'tool_calls_count' => count($toolNames),
			));
			$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);
			if ($results['data'] == 'tool_calls') {
				$results['data'] = __('I couldn\'t find anything matching your query. Could you try rephrasing?', 'ai-copilot-content-generator');
			}
			break;
		} while (true);

		return $results;
	}

	public function getImage( $params ) {
		if ( !$this->imageProvider ) {
			WaicFrame::_()->pushError(esc_html__('Image AI Provider not found', 'ai-copilot-content-generator'));
			return false;
		}

		$start = microtime(true);
		$data = $this->imageProvider->getImage( $params );

		if (false === $data) {
			$results['error'] = 1;
			$results['msg'] = WaicFrame::_()->getLastError();
			$this->lastDurationMs = $this->elapsedMs($start);
			$history = $this->getHistory($results, $params, '', 'image');
			$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

			return $results;
		}


		$results = $data['results'];
		$params = $data['params'];
		$this->lastDurationMs = $this->elapsedMs($start);

		$history = $this->getHistory($results, $params, '', 'image');

		$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

		return $results;
	}

	private function getModelName( $engine ) {
		return str_replace('-', '', $engine);
	}

	private function getHistory( $results, $params, $type = '', $typeProvider = '', $unifiedUsage = null, $meta = array() ) {
		$engine = $this->getEngine($typeProvider);
		$model = empty($params['model']) ? $this->getEngineModel($typeProvider) : $params['model'];
		$operation = $this->getHistoryOperation($type, $typeProvider);
		if (is_null($unifiedUsage)) {
			$unifiedUsage = $this->usageFromResults($results, $params, $operation);
		}
		$unifiedUsage = self::normalizeUsage($unifiedUsage);
		if (!empty($unifiedUsage['cached_tokens']) && !empty($unifiedUsage['input_tokens']) && $unifiedUsage['cached_tokens'] > (0.8 * $unifiedUsage['input_tokens'])) {
			$unifiedUsage['est_flags'] |= self::EST_FLAG_CACHE_MAJ;
		}
		$pricing = WaicFrame::_()->getModule('insights')->getModel('pricing')->calculateMicro($engine, $model, $unifiedUsage, $operation);
		$estFlags = (int) $unifiedUsage['est_flags'] | (int) WaicUtils::getArrayValue($pricing, 'est_flags', 0, 1);
		$historyMeta = array_merge(array(
			'steps_count' => 1,
			'tool_names' => array(),
		), is_array($meta) ? $meta : array());
		$historyMeta['tool_names'] = empty($historyMeta['tool_names']) || !is_array($historyMeta['tool_names'])
			? array()
			: array_values(array_unique(array_map('sanitize_key', $historyMeta['tool_names'])));
		$toolCallsCount = isset($historyMeta['tool_calls_count']) ? max(0, (int) $historyMeta['tool_calls_count']) : count($historyMeta['tool_names']);
		unset($historyMeta['tool_calls_count']);
		$history = array(
			'engine' => $engine,
			'model' => $model,
			'operation' => $operation,
			'task_id' => $this->taskId,
			'feature' => $this->feature,
			'user_id' => $this->userId,
			'session_id' => $this->sessionId,
			'ip' => $this->userIP,
			'mode' => $this->genMode,
			'duration_ms' => $this->lastDurationMs,
			'input_tokens' => (int) $unifiedUsage['input_tokens'],
			'output_tokens' => (int) $unifiedUsage['output_tokens'],
			'reasoning_tokens' => (int) $unifiedUsage['reasoning_tokens'],
			'cached_tokens' => (int) $unifiedUsage['cached_tokens'],
			'cache_write_tokens' => (int) $unifiedUsage['cache_write_tokens'],
			'tokens' => (int) $unifiedUsage['total_tokens'],
			'cost_micro_usd' => (int) WaicUtils::getArrayValue($pricing, 'cost_micro_usd', 0, 1),
			'est_flags' => $estFlags,
			'tool_calls_count' => $toolCallsCount,
			'meta' => $historyMeta,
		);
		$history['status'] = (int) WaicUtils::getArrayValue($results, 'error', 0, 1);
		if (isset($results['best_score'])) {
			$history['best_score_x1000'] = (int) round(max(0, min(1, (float) $results['best_score'])) * 1000);
		}
		$history['cost'] = round($history['cost_micro_usd'] / 1000000, 4);

		return $history;
	}
	private function getHistoryOperation( $type = '', $typeProvider = '' ) {
		if ('image' === $typeProvider) {
			return 'image';
		}
		switch ($type) {
			case 'embeddings':
				return 'embedding';
			case 'train':
				return 'fine_tune_upload';
			case 'check_train':
				return 'fine_tune_status';
			default:
				return 'chat';
		}
	}
	
	public function sendFile( $params ) {
		$start = microtime(true);
		$data = $this->provider->sendFile( $params );

		if (false === $data) {
			$results['error'] = 1;
			$results['msg'] = WaicFrame::_()->getLastError();
			$this->lastDurationMs = $this->elapsedMs($start);
			$history = $this->getHistory($results, $params, 'train');
			$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

			return $results;
		}

		$results = $data['results'];
		$params = $data['params'];
		$this->lastDurationMs = $this->elapsedMs($start);

		$history = $this->getHistory($results, $params, 'train');

		$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

		return $results;
	}
	public function getFineTunes( $params, $method = 'POST', $job = false ) {
		$start = microtime(true);
		$data = $this->provider->getFineTunes( $params, $method, $job );

		if (false === $data) {
			$results['error'] = 1;
			$results['msg'] = WaicFrame::_()->getLastError();
			$this->lastDurationMs = $this->elapsedMs($start);
			$history = $this->getHistory($results, $params, 'check_train');
			$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

			return $results;
		}

		$results = $data['results'];
		$params = $data['params'];
		$this->lastDurationMs = $this->elapsedMs($start);

		$history = $this->getHistory($results, $params, 'check_train');

		$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

		return $results;
	}
	public function sendEmbeddings( $params, $method = 'POST' ) {
		$start = microtime(true);
		$data = $this->provider->sendEmbeddings( $params, $method );

		if (false === $data) {
			$results['error'] = 1;
			$results['msg'] = WaicFrame::_()->getLastError();
			$this->lastDurationMs = $this->elapsedMs($start);
			$history = $this->getHistory($results, $params, 'embeddings');
			$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

			return $results;
		}

		$results = $data['results'];
		$params = $data['params'];
		$this->lastDurationMs = $this->elapsedMs($start);

		$history = $this->getHistory($results, $params, 'embeddings');

		$results['his_id'] = WaicFrame::_()->getModule('workspace')->getModel('history')->saveHistory($history);

		return $results;
	}
	private function elapsedMs( $start ) {
		return max(0, (int) round((microtime(true) - $start) * 1000));
	}

	public static function emptyUsage() {
		return array(
			'input_tokens' => 0,
			'output_tokens' => 0,
			'reasoning_tokens' => 0,
			'cached_tokens' => 0,
			'cache_write_tokens' => 0,
			'total_tokens' => 0,
			'est_flags' => 0,
		);
	}

	public static function normalizeUsage( $usage ) {
		$base = self::emptyUsage();
		if (is_array($usage)) {
			foreach ($base as $key => $value) {
				if (isset($usage[$key])) {
					$base[$key] = max(0, (int) $usage[$key]);
				}
			}
			if (isset($usage['_openrouter_cost_micro'])) {
				$base['_openrouter_cost_micro'] = max(0, (int) $usage['_openrouter_cost_micro']);
			}
			if (isset($usage['images_count'])) {
				$base['images_count'] = max(0, (int) $usage['images_count']);
			}
			if (isset($usage['image_count'])) {
				$base['images_count'] = max((int) WaicUtils::getArrayValue($base, 'images_count', 0, 1), max(0, (int) $usage['image_count']));
			}
			foreach (array('size', 'dimensions', 'quality') as $key) {
				if (isset($usage[$key]) && is_scalar($usage[$key])) {
					$base[$key] = substr(sanitize_text_field((string) $usage[$key]), 0, 40);
				}
			}
			foreach (array('search_count', 'searches', 'web_search_count', 'num_search_queries') as $key) {
				if (isset($usage[$key])) {
					$base['search_count'] = max((int) WaicUtils::getArrayValue($base, 'search_count', 0, 1), max(0, (int) $usage[$key]));
				}
			}
		}
		$sumTokens = $base['input_tokens'] + $base['output_tokens'] + $base['reasoning_tokens'] + $base['cached_tokens'] + $base['cache_write_tokens'];
		if (empty($base['total_tokens']) && $sumTokens > 0) {
			$base['total_tokens'] = $sumTokens;
		}
		return $base;
	}

	public static function aggregateUsages( $stepUsages ) {
		$total = self::emptyUsage();
		if (!is_array($stepUsages)) {
			return $total;
		}
		foreach ($stepUsages as $usage) {
			if (!is_array($usage)) {
				continue;
			}
			$usage = self::normalizeUsage($usage);
			foreach (array('input_tokens', 'output_tokens', 'reasoning_tokens', 'cached_tokens', 'cache_write_tokens', 'total_tokens') as $key) {
				$total[$key] += (int) $usage[$key];
			}
			$total['est_flags'] |= (int) $usage['est_flags'];
			if (isset($usage['_openrouter_cost_micro'])) {
				if (!isset($total['_openrouter_cost_micro'])) {
					$total['_openrouter_cost_micro'] = 0;
				}
				$total['_openrouter_cost_micro'] += (int) $usage['_openrouter_cost_micro'];
			}
		}
		return $total;
	}

	public static function parseProviderUsage( $engine, $raw ) {
		$engine = sanitize_key((string) $engine);
		if (is_object($raw)) {
			$raw = json_decode(wp_json_encode($raw), true);
		}
		$raw = is_array($raw) ? $raw : array();
		$u = self::emptyUsage();
		if ('gemini' === $engine) {
			$usage = WaicUtils::getArrayValue($raw, 'usageMetadata', array(), 2);
			if (empty($usage)) {
				$u['est_flags'] |= self::EST_FLAG_TOKENS;
				return $u;
			}
			$u['input_tokens'] = (int) WaicUtils::getArrayValue($usage, 'promptTokenCount', 0, 1);
			$u['output_tokens'] = (int) WaicUtils::getArrayValue($usage, 'candidatesTokenCount', 0, 1);
			$u['cached_tokens'] = (int) WaicUtils::getArrayValue($usage, 'cachedContentTokenCount', 0, 1);
			$u['reasoning_tokens'] = (int) WaicUtils::getArrayValue($usage, 'thoughtsTokenCount', 0, 1);
			$u['total_tokens'] = (int) WaicUtils::getArrayValue($usage, 'totalTokenCount', 0, 1);
			$u['input_tokens'] = max(0, $u['input_tokens'] - $u['cached_tokens']);
			return self::normalizeUsage($u);
		}
		$usage = WaicUtils::getArrayValue($raw, 'usage', array(), 2);
		if (empty($usage)) {
			$u['est_flags'] |= self::EST_FLAG_TOKENS;
			return $u;
		}
		if ('claude' === $engine) {
			$u['input_tokens'] = (int) WaicUtils::getArrayValue($usage, 'input_tokens', 0, 1);
			$u['output_tokens'] = (int) WaicUtils::getArrayValue($usage, 'output_tokens', 0, 1);
			$u['cached_tokens'] = (int) WaicUtils::getArrayValue($usage, 'cache_read_input_tokens', 0, 1);
			$u['cache_write_tokens'] = (int) WaicUtils::getArrayValue($usage, 'cache_creation_input_tokens', 0, 1);
			return self::normalizeUsage($u);
		}
		$u['input_tokens'] = (int) WaicUtils::getArrayValue($usage, 'prompt_tokens', 0, 1);
		$u['output_tokens'] = (int) WaicUtils::getArrayValue($usage, 'completion_tokens', 0, 1);
		$u['total_tokens'] = (int) WaicUtils::getArrayValue($usage, 'total_tokens', 0, 1);
		$details = WaicUtils::getArrayValue($usage, 'prompt_tokens_details', array(), 2);
		$u['cached_tokens'] = (int) WaicUtils::getArrayValue($details, 'cached_tokens', 0, 1);
		if ('deep-seek' === $engine || 'deepseek' === $engine) {
			$u['cached_tokens'] = (int) WaicUtils::getArrayValue($usage, 'prompt_cache_hit_tokens', $u['cached_tokens'], 1);
			$u['reasoning_tokens'] = (int) WaicUtils::getArrayValue($usage, 'reasoning_tokens', 0, 1);
		} else {
			$completionDetails = WaicUtils::getArrayValue($usage, 'completion_tokens_details', array(), 2);
			$u['reasoning_tokens'] = (int) WaicUtils::getArrayValue($completionDetails, 'reasoning_tokens', 0, 1);
		}
		$u['input_tokens'] = max(0, $u['input_tokens'] - $u['cached_tokens']);
		if ('openrouter' === $engine && isset($usage['cost'])) {
			$u['_openrouter_cost_micro'] = (int) round(((float) $usage['cost']) * 1000000);
		}
		if ('perplexity' === $engine) {
			foreach (array('search_count', 'searches', 'web_search_count', 'num_search_queries') as $key) {
				if (isset($usage[$key])) {
					$u['search_count'] = max(0, (int) $usage[$key]);
					break;
				}
			}
		}
		return self::normalizeUsage($u);
	}

	private function usageFromResults( $results, $params, $operation = 'chat' ) {
		$engine = $this->getEngine('image' === $operation ? 'image' : '');
		$usage = self::emptyUsage();
		if (!empty($results['raw_response'])) {
			$usage = self::parseProviderUsage($engine, $results['raw_response']);
		} else if (!empty($results['usage']) && is_array($results['usage'])) {
			$usage = self::normalizeUsage($results['usage']);
		} else if (!empty($results['tokens'])) {
			$usage['total_tokens'] = (int) $results['tokens'];
		} else {
			$usage['est_flags'] |= self::EST_FLAG_TOKENS;
		}
		if ($usage['est_flags'] & self::EST_FLAG_TOKENS) {
			$promptText = $this->textFromParams($params);
			$outputText = is_string(WaicUtils::getArrayValue($results, 'data')) ? WaicUtils::getArrayValue($results, 'data') : '';
			$usage['input_tokens'] = $this->estimateTokensFromText($promptText);
			$usage['output_tokens'] = $this->estimateTokensFromText($outputText);
			$usage['total_tokens'] = $usage['input_tokens'] + $usage['output_tokens'];
		}
		if ('image' === $operation && empty($usage['total_tokens'])) {
			$usage['images_count'] = max(1, (int) WaicUtils::getArrayValue($params, 'n', 1, 1));
			$usage['size'] = WaicUtils::getArrayValue($params, 'size', WaicUtils::getArrayValue($params, 'dimensions', ''));
			$usage['quality'] = WaicUtils::getArrayValue($params, 'quality', '');
		}
		return self::normalizeUsage($usage);
	}

	private function textFromParams( $params ) {
		if (isset($params['prompt']) && is_scalar($params['prompt'])) {
			return (string) $params['prompt'];
		}
		$text = '';
		if (!empty($params['messages']) && is_array($params['messages'])) {
			foreach ($params['messages'] as $message) {
				if (is_array($message) && isset($message['content'])) {
					if (is_scalar($message['content'])) {
						$text .= ' ' . $message['content'];
					} else if (is_array($message['content'])) {
						$text .= ' ' . wp_json_encode($message['content']);
					}
				}
			}
		}
		return trim($text);
	}

	private function estimateTokensFromText( $text ) {
		$text = trim(wp_strip_all_tags((string) $text));
		if ('' === $text) {
			return 0;
		}
		$length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
		return max(1, (int) ceil($length / 4));
	}

	public function addTaxonomiesArgs( $args ) {
		$args['taxonomies'] = array(
			'type' => 'array',
			'description' => 'List of taxonomy filters (categories, tags, attributes). Each item specifies taxonomy slug, value, and logic.',
			'items' => array(
				'type' => 'object',
				'properties' => array(
					'taxonomy' => array(
						'type' => 'string',
						'description' => 'Taxonomy slug (e.g., product_cat, product_tag, pa_color).'
					),
					'values' => array(
						'type' => 'array',
						'description' => 'List of term slugs or values for this taxonomy',
						'items' => array('type' => 'string'),
					),
					'logic' => array(
						'type' => 'string',
						'description' => 'Logic to combine multiple values inside this taxonomy',
						'enum' => array('AND', 'OR'),
					),
				),
				'required' => array('taxonomy', 'values'),
			),
		);
		$args['tax_logic'] = array(
			'type' => 'string',
			'enum' => array('AND', 'OR'),
			'description' => 'Logic to combine different taxonomies together',
		);
		return $args;
	}
	public function getTool( $key ) {
		$tool = false;
		switch ($key) {
			case 'get_taxonomy_values':
				$tool = array(
					'type' => 'function',
					'function' => array(
						'name' => 'get_taxonomy_values',
						'description' => 'Return possible values (terms) for one or more taxonomies. The result is always a dictionary where the KEY is the term slug (used in queries) and the VALUE is the human-readable name.',
						'parameters' => array(
							'type' => 'object',
							'properties' => array(
								'taxonomies' => array(
									'type' => 'array',
									'description' => 'List of taxonomy slugs to fetch values for (e.g., "product_cat", "product_tag", "pa_color").',
									'items' => array('type' => 'string'),
								)
							),
							'required' => array('taxonomies'),
						),
					),
				);
				break;
			case 'search_products':
			case 'search_products_tax':
				$tool = array(
					'type' => 'function',
					'function' => array(
						'name' => 'search_products',
						'description' => 'Search WooCommerce products on this website. Use when the user asks about: products to buy, items for sale, specific product types, products with certain features or characteristics. Searches across product titles, descriptions, SKUs, taxonomies. Returns matching products with their details.',
						'parameters' => array(
							'type' => 'object',
							'properties' => array(
								'query' => array('type' => 'string', 'description' => 'Text search across title, description, excerpt'),
								'sku' => array('type' => 'string', 'description' => 'Search by product SKU (Stock Keeping Unit). Use when the user specifies an product number or code.'),
								'price_min' => array('type' => 'number', 'description' => 'Minimum price'),
								'price_max' => array('type' => 'number', 'description' => 'Maximum price'),
								'on_sale' => array('type' => 'boolean', 'description' => 'Only discounted products'),
								'featured' => array('type' => 'boolean', 'description' => 'Only featured products'),
								'sort_by' => array('type' => 'string', 'enum' => array('price_asc', 'price_desc', 'popularity', 'rating', 'date'), 'description' => 'Sorting method'),
								'in_stock' => array('type' => 'boolean', 'description' => 'Only in-stock products (default: true)'),
							), 
						),
					),
				);
				if ('search_products_tax' == $key) {
					$tool['function']['parameters']['properties'] = $this->addTaxonomiesArgs($tool['function']['parameters']['properties']);
				}
				break;
			case 'search_posts':
			case 'search_posts_tax':
				$tool = array(
					'type' => 'function',
					'function' => array(
						'name' => 'search_posts',
						'description' => 'Search WordPress blog posts on this website. Use when the user asks about: articles, blog content, posts on specific topics, content by author or category, or recent publications. Searches across post titles, content, and excerpts. Returns matching posts with their details.',
						'parameters' => array(
							'type' => 'object',
							'properties' => array(
								'query' => array('type' => 'string', 'description' => 'Text search across title, description, excerpt'),
								'author' => array('type' => 'string', 'description' => 'Filter by author name'),
								'date_after' => array('type' => 'string', 'description' => 'Posts published after this date (YYYY-MM-DD format)'),
								'date_before' => array('type' => 'string', 'description' => 'Posts published before this date (YYYY-MM-DD format)'),
								'sort_by' => array('type' => 'string', 'enum' => array('date_desc', 'date_asc', 'popularity', 'comments'), 'description' => 'Sort order for results'),
							), 
						),
					),
				);
				if ('search_posts_tax' == $key) {
					$tool['function']['parameters']['properties'] = $this->addTaxonomiesArgs($tool['function']['parameters']['properties']);
				}
				break;
		}
		return $tool;
	}
	public function getToolsList( $tools ) {
		$list = array();
		foreach ($tools as $t) {
			$tool = $this->getTool($t);
			if ($tool) {
				$list[] = $tool;
			}
		}
		
		return WaicDispatcher::applyFilters('call_tools', $list);
	}
	public function doTool( $tool, $args, $options ) {
		$result = array();
		switch ($tool) {
			case 'get_taxonomy_values':
				if (isset($args['taxonomies']) && is_array($args['taxonomies'])) {
					foreach ($args['taxonomies'] as $taxonomy) {
						$terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => true));
						if (!is_wp_error($terms) && !empty($terms)) {
							$values = []; 
							foreach ($terms as $term) {
								$values[$term->slug] = $term->name;
							} 
							$result[$taxonomy] = $values;
						}
					}
				}
				break;
			case 'search_products':
				if (!WaicUtils::isWooCommercePluginActivated()) {
					break;
				}
				$query = WaicUtils::getArrayValue($args, 'query');
				
				$queryArgs = array(
					'post_type' => array('product'),
					'post_status' => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page' => WaicUtils::getArrayValue($options, 'prod_limit', 3, 1),
					//'fields' => 'ids',
					'tax_query' => array(),
					'meta_query' => array(),
				);
		
				if (!empty($query)) {
					$queryArgs['waic_search_query'] = $query;
					add_filter('posts_where', array($this, 'addSearchByWhere'), 10, 2 );
				}
				if (!isset($args['in_stock']) || $args['in_stock']) {
					$queryArgs['meta_query'][] = array(
						'key' => '_stock_status',
						'value' => array('instock'),
						'compare' => 'IN',
					);
				}
				if (!empty($args['sku'])) {
					$queryArgs['meta_query'][] = array(
						'key' => '_sku',
						'value' => trim($args['sku']),
						'compare' => '=',
					);
				}
				if (!empty($args['price_min'])) {
					$queryArgs['meta_query'][] = array(
						'key' => '_price',
						'value' => (float)$args['price_min'],
						'compare' => '>=',
						'type' => 'NUMERIC',
					);
				}
				if (!empty($args['price_max'])) {
					$queryArgs['meta_query'][] = array(
						'key' => '_price',
						'value' => (float)$args['price_max'],
						'compare' => '<=',
						'type' => 'NUMERIC',
					);
				}
				if (isset($args['on_sale']) && $args['on_sale']) {
					$saleIds = wc_get_product_ids_on_sale();
					if ($args['on_sale']) {
						$queryArgs['post__in'] = array_merge(array(0), $saleIds);
					} /*else if (!empty($saleIds)) {
						$queryArgs['post__not_in'] = $saleIds;
					}*/
				}
				
				if (isset($args['featured']) && $args['featured']) {
					$queryArgs['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field' => 'slug',
						'terms' => array('featured'),
						'operator' => $args['featured'] ? 'IN' : 'NOT IN',
					);
				}

				$include = WaicUtils::getArrayValue($options, 'prod_include');
				if (!empty($include)) {
					if ('ids' == $include) {
						$ids = trim(WaicUtils::getArrayValue($options, 'prod_inc_ids'));
						if (!empty($ids)) {
							$ids = WaicUtils::controlNumericValues(explode(',', $ids));
							$queryArgs['post__in'] = empty($queryArgs['post__in']) ? $ids : array_intersect($queryArgs['post__in'], $ids);
						}
					} else if ('cat' == $include) {
						$cats = WaicUtils::getArrayValue($options, 'prod_inc_cat', array(), 2);
						if (!empty($cats)) {
							$queryArgs['tax_query'][] = array(
								'taxonomy' => 'product_cat',
								'field' => 'term_id',
								'terms' => $cats,
								'operator' => 'IN',
								'include_children' => false,
							);
						}
					}
				}
				$exclude = WaicUtils::getArrayValue($options, 'prod_exclude');
				if (!empty($exclude)) {
					if ('ids' == $exclude) {
						$ids = trim(WaicUtils::getArrayValue($options, 'prod_exc_ids'));
						if (!empty($ids)) {
							$ids = WaicUtils::controlNumericValues(explode(',', $ids));
							$queryArgs['post__not_in'] = empty($queryArgs['post__not_in']) ? $ids : array_merge($queryArgs['post__not_in'], $ids);
						}
					} else if ('cat' == $exclude) {
						$cats = WaicUtils::getArrayValue($options, 'prod_exc_cat', array(), 2);
						if (!empty($cats)) {
							$queryArgs['tax_query'][] = array(
								'taxonomy' => 'product_cat',
								'field' => 'term_id',
								'terms' => $cats,
								'operator' => 'NOT IN',
								'include_children' => false,
							);
						}
					}
				}
				
				if (!empty($queryArgs['tax_query'])) {
					$queryArgs['tax_query']['relation'] = 'AND';
				}

				$taxonomies = WaicUtils::getArrayValue($args, 'taxonomies', array(), 2);
				if (!empty($taxonomies)) {
					$taxQuery = array('relation' => WaicUtils::getArrayValue($args, 'tax_logic') === 'OR' ? 'OR' : 'AND');
					foreach ($taxonomies as $tax) {
						$taxQuery[] = array(
							'taxonomy' => $tax['taxonomy'],
							'field' => 'slug', 
							'terms' => $tax['values'], 
							'operator' =>  WaicUtils::getArrayValue($tax, 'logic') === 'AND' ? 'AND' : 'IN',
							'include_children' => $tax['taxonomy'] == 'product_cat',
						);
					}
					if (empty($queryArgs['tax_query'])) {
						$queryArgs['tax_query'] = $taxQuery;
					} else {
						$queryArgs['tax_query'][] = $taxQuery;
					}
				}
				
				$sortBy = WaicUtils::getArrayValue($args, 'sort_by');
				switch ($sortBy) {
					case 'price_asc':
					case 'price_desc':
						$queryArgs['meta_key'] = '_price';
						$queryArgs['orderby'] = 'meta_value_num';
						$queryArgs['order'] = ( 'price_asc' == $sortBy ? 'ASC' : 'DESC' );
						break;
					case 'popularity':
						$queryArgs['meta_key'] = 'total_sales';
						$queryArgs['orderby'] = 'meta_value_num';
						$queryArgs['order'] = 'DESC';
						break;
					case 'rating':
						$queryArgs['meta_key'] = '_wc_average_rating';
						$queryArgs['orderby'] = 'meta_value_num';
						$queryArgs['order'] = 'DESC';
						break;
					case 'date':
						$queryArgs['orderby'] = 'date';
						$queryArgs['order'] = 'DESC';
						break;
				}
				
				$select = new WP_Query($queryArgs);
				if ($select->have_posts()) {
					foreach ($select->posts as $post) {
						$result[] = array(
							'id' => $post->ID,
							'title' => $post->post_title,
							'short_description' => $post->post_excerpt,
						);
					}
				}
				wp_reset_postdata();
				break;
			case 'search_posts':
				$query = WaicUtils::getArrayValue($args, 'query');
				
				$queryArgs = array(
					'post_type' => array('post'),
					'post_status' => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page' => WaicUtils::getArrayValue($options, 'post_limit', 3, 1),
					'tax_query' => array(),
					'meta_query' => array(),
				);
		
				if (!empty($query)) {
					$queryArgs['waic_search_query'] = $query;
					add_filter('posts_where', array($this, 'addSearchByWhere'), 10, 2 );
				}
				if (!empty($args['author'])) {
					$users = get_users(array(
						'search' => '*' . esc_sql($args['author']) . '*',
						'search_columns' => array('display_name', 'user_login', 'user_nicename', 'user_email'),
						'fields' => 'ID',
						'number' => 50,
					));
					if (empty($users)) {
						$users = array(0);
					}
					$queryArgs['author__in'] = $users;
				}
				if (!empty($args['date_after'])) {
					$queryArgs['date_query'] = array('after' => $args['date_after']);
				}
				if (!empty($args['date_before'])) {
					$queryArgs['date_query'] = array('before' => $args['date_before']);
				}

				$include = WaicUtils::getArrayValue($options, 'post_include');
				if (!empty($include)) {
					if ('ids' == $include) {
						$ids = trim(WaicUtils::getArrayValue($options, 'post_inc_ids'));
						if (!empty($ids)) {
							$queryArgs['post__in'] = WaicUtils::controlNumericValues(explode(',', $ids));
						}
					} else if ('cat' == $include) {
						$cats = WaicUtils::getArrayValue($options, 'post_inc_cat', array(), 2);
						if (!empty($cats)) {
							$queryArgs['tax_query'][] = array(
								'taxonomy' => 'category',
								'field' => 'term_id',
								'terms' => $cats,
								'operator' => 'IN',
								'include_children' => false,
							);
						}
					}
				}
				$exclude = WaicUtils::getArrayValue($options, 'post_exclude');
				if (!empty($exclude)) {
					if ('ids' == $exclude) {
						$ids = trim(WaicUtils::getArrayValue($options, 'post_exc_ids'));
						if (!empty($ids)) {
							$queryArgs['post__not_in'] = WaicUtils::controlNumericValues(explode(',', $ids));
						}
					} else if ('cat' == $exclude) {
						$cats = WaicUtils::getArrayValue($options, 'post_exc_cat', array(), 2);
						if (!empty($cats)) {
							$queryArgs['tax_query'][] = array(
								'taxonomy' => 'category',
								'field' => 'term_id',
								'terms' => $cats,
								'operator' => 'NOT IN',
								'include_children' => false,
							);
						}
					}
				}
				
				if (!empty($queryArgs['tax_query'])) {
					$queryArgs['tax_query']['relation'] = 'AND';
				}

				$taxonomies = WaicUtils::getArrayValue($args, 'taxonomies', array(), 2);
				if (!empty($taxonomies)) {
					$taxQuery = array('relation' => WaicUtils::getArrayValue($args, 'tax_logic') === 'OR' ? 'OR' : 'AND');
					foreach ($taxonomies as $tax) {
						$taxQuery[] = array(
							'taxonomy' => $tax['taxonomy'],
							'field' => 'slug', 
							'terms' => $tax['values'], 
							'operator' =>  WaicUtils::getArrayValue($tax, 'logic') === 'AND' ? 'AND' : 'IN',
							'include_children' => $tax['taxonomy'] == 'category',
						);
					}
					if (empty($queryArgs['tax_query'])) {
						$queryArgs['tax_query'] = $taxQuery;
					} else {
						$queryArgs['tax_query'][] = $taxQuery;
					}
				}
				
				$sortBy = WaicUtils::getArrayValue($args, 'sort_by');
				switch ($sortBy) {
					case 'date_desc':
						$queryArgs['orderby'] = 'date';
						$queryArgs['order'] = 'DESC';
						break;
					case 'date_asc':
						$queryArgs['orderby'] = 'date';
						$queryArgs['order'] = 'ASC';
						break;
					case 'popularity':
					case 'comments':
						$queryArgs['orderby'] = 'comment_count';
						$queryArgs['order'] = 'DESC';
						break;
				}
				
				$select = new WP_Query($queryArgs);
				if ($select->have_posts()) {
					foreach ($select->posts as $post) {
						$result[] = array(
							'id' => $post->ID,
							'title' => $post->post_title,
							'excerpt' => $post->post_excerpt,
						);
					}
				}
				wp_reset_postdata();
				break;
		}
		return $result;
	}
	
	public function addSearchByWhere( $where, $wp_query ) {
		global $wpdb;
		if (!empty($wp_query->get( 'waic_search_query' ))) {
			$s = esc_sql($wp_query->get('waic_search_query'));
			$like = '%' . $wpdb->esc_like($s) . '%';
			$where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s)", $like, $like, $like);
		}
		return $where;
	}
}
