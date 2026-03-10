<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicChatbotsModel extends WaicModel {
	private $_optionChatbotShow = 'waic_chatbot_show';
	private $_chatbotShowRules = null;
	private $_launchFlagId = 21;
	private $_launchTimeout = 300;
	private $_workspace = null;

	public function __construct() {
		$this->_setTbl('chatlogs');
	}
	public function getChatbotShowRules() {
		if (is_null($this->_chatbotShowRules)) {
			$option = get_option($this->_optionChatbotShow);
			$this->_chatbotShowRules = $option && is_array($option) ? $option : false;
		}
		return $this->_chatbotShowRules;
	}
	public function setChatbotShowRules( $rules ) {
		update_option($this->_optionChatbotShow, empty($rules) ? '' : $rules);
	}
	public function isDeleteByCancel() {
		return false;
	}
	public function canUnpublish() {
		return true;
	}
	public function unpublishEtaps( $taskId ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 6));
		return false;
	}
	public function publishResults( $taskId, $publish ) {
		WaicFrame::_()->getModule('workspace')->getModel('tasks')->updateTask($taskId, array('status' => 4));
		return false;
	}
	public function getColorSchemes( $default = false ) {
		$schemes = array('#2D3E70', '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4');
		if ($default) {
			$schemes[] = '#2D3E50';
		}
		return $schemes;
	}
	public function getDisplayPages() {
		$pages = array(
			'home' => __('Home', 'ai-copilot-content-generator'),
			'account' => __('Account', 'ai-copilot-content-generator'),
			'blog' => __('Blog page', 'ai-copilot-content-generator'),
			'blog_post' => __('Blog posts', 'ai-copilot-content-generator'),
			'blog_сat' => __('Blog categories', 'ai-copilot-content-generator'),
			'blog_tag' => __('Blog tags', 'ai-copilot-content-generator'),
		);
		if (WaicUtils::isWooCommercePluginActivated()) {
			$pages['shop'] = __('Shop', 'ai-copilot-content-generator');
			$pages['product'] = __('Product Pages', 'ai-copilot-content-generator');
			$pages['product_cat'] = __('Product categories', 'ai-copilot-content-generator');
			$pages['product_tag'] = __('Product tags', 'ai-copilot-content-generator');
			$pages['cart'] = __('Cart', 'ai-copilot-content-generator');
			$pages['checkout'] = __('Checkout', 'ai-copilot-content-generator');
		}
		return $pages;
	}
	public function clearEtaps( $taskId, $ids = false, $withContent = true ) {
		if ($withContent) {
			WaicFrame::_()->getModule('workspace')->getModel('history')->deleteHistory($taskId);
			$query = 'DELETE t FROM @__chatlogs t WHERE NOT EXISTS(SELECT 1 FROM @__history h WHERE h.id=t.his_id)';
			WaicDb::query($query);
		}
		return true;
	}
	public function setChatbotParams( $taskId, $oldTask ) {
		$task = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTask($taskId);
		if (!$task || empty($task)) {
			return ;
		}
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		$general = WaicUtils::getArrayValue($params, 'general', array(), 2);
		
		$rules = $this->getChatbotShowRules();
		
		$rule = array();
		$display = WaicUtils::getArrayValue($general, 'display_on');
		$pages = array();
		if ('all' == $display) {
			$pages = array('all');
		} else if ('specific' == $display) {
			$pages = WaicUtils::getArrayValue($general, 'display_pages', array(), 2);
			$ids = WaicUtils::getArrayValue($general, 'display_ids');
			if (!empty($ids)) {
				$pages = array_merge($pages, WaicUtils::controlNumericValues(explode(',', $ids)));
			}
		}
		if (!empty($pages)) {
			$rule['display'] = $pages;
		}
		
		$hide = WaicUtils::getArrayValue($general, 'hide_on');
		$pages = array();
		if ('specific' == $hide) {
			$pages = WaicUtils::getArrayValue($general, 'hide_pages', array(), 2);
			$ids = WaicUtils::getArrayValue($general, 'hide_ids');
			if (!empty($ids)) {
				$pages = array_merge($pages, WaicUtils::controlNumericValues(explode(',', $ids)));
			}
		}
		if (!empty($pages)) {
			$rule['hide'] = $pages;
		}
		if (empty($rule)) {
			unset($rules[$taskId]);
		} else {
			$rules[$taskId] = $rule;
		}
		$this->setChatbotShowRules($rules);
		
		return WaicDispatcher::applyFilters('setChatbotParams', false, $oldTask, $task);
	}
	public function isActiveChat( $taskId = 0, $userId = 0, $ip = '', $mode = 0, $lifetime = 0 ) {
		$query = 'SELECT 1 FROM @__history' .
			' WHERE task_id=' . ( (int) $taskId ) .
			' AND mode=' . ( (int) $mode ) . 
			' AND user_id=' . ( (int) $userId ) .
			( empty($userId) ? " AND ip='" . $ip . "'" : '' );
		if (!empty($lifetime)) {
			$now = WaicUtils::getTimestampDB();
			$query .= " AND created>DATE_SUB('" . $now . "', INTERVAL " . $lifetime . ' MINUTE) ';
		}
		$query .= ' LIMIT 1';
		return ( WaicDb::get($query, 'one') == 1 );
	}
	public function getUserChatLog( $taskId = 0, $userId = 0, $ip = '', $mode = 0, $cnt = 0, $status = 0, $dd = false ) {
		$forDate = !empty($dd);
		$query = 'SELECT h.id as his_id, h.created, l.question, l.answer, h.status, l.file' .
			' FROM @__history as h' .
			' INNER JOIN @__chatlogs l ON (l.his_id=h.id)' .
			' WHERE h.task_id=' . ( (int) $taskId ) .
			( false !== $status ? ' AND h.status= ' . ( (int) $status ) : '' ) .
			' AND h.mode=' . ( (int) $mode ) .
			' AND h.user_id=' . ( (int) $userId ) .
			( false !== $status ? ' AND l.status=0' : '' ) .
			( empty($userId) || $forDate ? " AND ip='" . $ip . "'" : '' ) .
			( $forDate ? " AND h.created BETWEEN '" . $dd . " 00:00:00' AND '" . $dd . " 23:59:59'" : '' ) .
			' ORDER BY h.id' .
			( empty($cnt) ? '' : ' DESC LIMIT ' . ( (int) $cnt ) ); 
		$log = WaicDb::get($query);
		if ($log && !empty($log)) {
			if (!empty($cnt)) {
				$log = array_reverse($log);
			}
		} else {
			$log = array();
		}
		return $log;
	}
	public function deleteUserChatLog( $taskId = 0, $userId = 0, $ip = '', $mode = 0 ) {
		$query = 'DELETE l FROM @__chatlogs l' .
			' INNER JOIN @__history h ON (l.his_id=h.id)' .
			' WHERE h.status=0 AND h.task_id=' . ( (int) $taskId ) .
			' AND h.mode=' . ( (int) $mode ) .
			' AND ' . ( empty($userId) ? "ip='" . $ip . "'" : 'h.user_id=' . ( (int) $userId ) ); 
		WaicDb::query($query);
		return true;
	}
	public function resetUserChatLog( $taskId = 0, $userId = 0, $ip = '', $mode = 0 ) {
		$query = 'UPDATE @__chatlogs l' .
			' INNER JOIN @__history h ON (l.his_id=h.id)' .
			' SET l.status=9' .
			' WHERE h.status=0 AND h.task_id=' . ( (int) $taskId ) .
			' AND h.mode=' . ( (int) $mode ) .
			' AND ' . ( empty($userId) ? "h.ip='" . $ip . "'" : 'h.user_id=' . ( (int) $userId ) ); 
		WaicDb::query($query);
		return true;
	}
	public function humanRequest( $taskId ) {
		$workspace = WaicFrame::_()->getModule('workspace');
		$task = $workspace->getModel('tasks')->getTask($taskId);
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		$context = WaicUtils::getArrayValue($params, 'context', array(), 2);

		$newLog = array(
			'answer' => WaicUtils::getArrayValue($context, 'human_request_message', __('Please provide your email address so we can assist you further. Our team will get back to you within 24 hours. Thank you!', 'ai-copilot-content-generator')),
			'error' => 0,
			'need_email' => 1,
			'request' => 'human',
			'plh_email' => WaicUtils::getArrayValue($context, 'plh_email'),
		);

		return $newLog;
	}
	public function sendEmail( $email, $taskId, $mode, $request = '' ) {
		$workspace = WaicFrame::_()->getModule('workspace');
		$task = $workspace->getModel('tasks')->getTask($taskId);
		if (!$task || empty($task)) {
			return array('question' => __('Unexpected error.', 'ai-copilot-content-generator'), 'error' => 1);
		}
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		$context = WaicUtils::getArrayValue($params, 'context', array(), 2);
		
		$isError = ( 'error' == $request );
		$newLog = array(
			'answer' => __('Email is not correct', 'ai-copilot-content-generator'),
			'error' => $isError ? 1 : 0,
			'need_email' => 1,
		);
		if (!is_email($email)) {
			$newLog['answer'] =  WaicUtils::getArrayValue($context, 'error_email', $newLog['answer']);
			return $newLog;
		}
		$user = wp_get_current_user();
		$userId = $user ? $user->ID : 0;
		$isGuest = empty($userId);
		$ip = WaicUtils::getRealUserIp();
		
		$history = array(
			'task_id' => $taskId,
			'user_id' => $userId,
			'ip' => $ip, 
			'mode' => $mode,
			'status' => 3,
			'tokens' => 0,
		);
		$newLog = array(
			'his_id' => $workspace->getModel('history')->saveHistory($history),
			'question' => $email,
		);
		$this->insert($newLog);

		if (WaicUtils::getArrayValue($context, 'e_' . $request . '_request', 0, 1) != 1) {
			return $newLog;
		}
		$adminEmail = WaicUtils::getArrayValue($context, $request . '_admin_email');
		if (empty($adminEmail)) {
			return $newLog;
		}
		$headers = array(
			'Content-type: text/html; charset=utf-8',
			'Content-Transfer-Encoding: 8bit',
			'From: ' . get_option( 'woocommerce_email_from_name' ) . ' <' . get_option( 'woocommerce_email_from_address' ) . '>',
		);
		$subject = $isError ? 'Chatbot Error Request' : 'Chatbot Human Request';
		
		$message = 'A user has requested human assistance through the chatbot. Below are the details:' . PHP_EOL .
			'User Login: ' . ( $isGuest ? 'guest' : $user->user_login ) . PHP_EOL .
			'User Name: ' . ( $isGuest ? '' : $user->user_firstname . ' ' . $user->user_lastname ) . PHP_EOL .
			'User Email: ' . $email . PHP_EOL .
			'User IP: ' . $ip . PHP_EOL .
			'Date & Time: ' . WaicUtils::getTimestampDB() . PHP_EOL .
			'Chatbot Name: ' . $task['title'] . PHP_EOL . PHP_EOL .
			'Conversation Log: ' . PHP_EOL ;

		$log = $this->getUserChatLog($taskId, $userId, $ip, 0, 0, false);
		foreach ($log as $log) {
			if (!empty($log['file'])) {
				$message .= 'User (' . $log['created'] . '): File uploaded' . PHP_EOL;
			}
			if (!empty($log['question'])) {
				$message .= 'User (' . $log['created'] . '): ' . ( 3 == $log['status'] ? 'EMAIL - ' : '' ) . $log['question'] . PHP_EOL;
			}
			if (!empty($log['answer'])) {
				$message .= 'Bot (' . $log['created'] . '): ' . ( empty($log['status']) ? '' : 'ERROR - ' ) . $log['answer'] . PHP_EOL;
			}
		}

		$message .= PHP_EOL . 'Please follow up with the user as soon as possible.' . PHP_EOL . PHP_EOL .
			'Best regards,' . PHP_EOL .
			'AIWU plugin';
			
		if (!wp_mail($adminEmail, $subject, $message, $headers)) {
			WaicFrame::_()->pushError(__('Error by sending email', 'ai-copilot-content-generator'));
		}
		$newLog['answer'] = WaicUtils::getArrayValue($context, 'error_thank', __('Thank you', 'ai-copilot-content-generator'));
		$newLog['need_email'] = 0;
		$newLog['disable'] = $isError ? 1 : 0;

		return $newLog;
	}
	/*public function sendFile( $message, $files, $taskId, $mode ) {
		error_log('$files='.json_encode($files));
		if (empty($files['img'])) {
			return array('question' => __('Empty File', 'ai-copilot-content-generator'), 'error' => 1);
		}
		$file = $files['img'];
		
		$error = $this->controlUploatedFile($file);
		if (!empty($error)) {
			return array('question' => $error, 'error' => 1);
		}
		$workspace = WaicFrame::_()->getModule('workspace');
		$hisModel = $workspace->getModel('history');
		$task = $workspace->getModel('tasks')->getTask($taskId);
		if (!$task) {
			return array('answer' => __('Unexpected error', 'ai-copilot-content-generator'), 'error' => 1);
		}
		
		$user = wp_get_current_user();
		$userId = $user ? $user->ID : 0;
		$isGuest = empty($userId);
		$ip = WaicUtils::getRealUserIp();
		
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		
		$openAI = $workspace->getModel('openai')->init($taskId, $userId, $ip, $mode);
		if (!$openAI) {
			return false;
		}
		//f"data:image/png;base64,{base64_image}";
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$f = base64_encode(file_get_contents($file['tmp_name']));
		$fStr = 'data:image/' . $extension . ";base64,{$f}";
		//if (empty($message)) 
		//$message = 'Что это?';
		$log = $this->sendMessage($message, $taskId, $mode, '', $fStr);
		return $log;
		
		
		$boundary = wp_generate_uuid4();
		$options = WaicUtils::getArrayValue($params, 'api', array(), 2);
		$options['contentType'] = 'multipart/form-data; boundary=' . $boundary;
		error_log('ffffffffffff');
		$openAI->setApiOptions($options);
		$opts = array(
			'boundary' => $boundary,
			'file_name' => sanitize_file_name($file['name']),
			'file_data' => file_get_contents($file['tmp_name']),
		);
		error_log(sanitize_file_name($file['name']));
		$result = $openAI->sendFile($opts);
		$newLog = array(
			'his_id' => $result['his_id'], 
			'question' => empty($result['file_id']) ? __('File not Uploaded', 'ai-copilot-content-generator') : __('File Uploaded', 'ai-copilot-content-generator'),
			'answer' => $result['error'] ? $this->controlText($result['msg']) : '',
			'typ' => 1,
		);
		if (!empty($result['his_id'])) {
			$this->insert($newLog);
		}

		if (empty($result['file_id'])) {
			//$context = WaicUtils::getArrayValue($params, 'context', array(), 2);
			$newLog['tt'] = WaicUtils::getFormatedDateTime(WaicUtils::getTimestamp(), 'H:i');
			//$msg = WaicUtils::getArrayValue($context, 'error_message');
			$newLog['answer'] = '';
			//if (!empty($msg)) {
			// $newLog['answer'] = $msg;
			//} 

			return $newLog;
		}
		$message = 'Analyze the attached file with ID: ' . $result['file_id'];
		$log = $this->sendMessage( $message, $taskId, $mode, '', 2);
		
		if (!empty($log['error'])) {
			$log['question'] = $newLog['question'];
		}
		
		return $log;
	}*/
	public function getFileString( $files, $maxSize ) { 
		$result = array(
			'file' => '',
			'error' => '',
		);
		
		if (empty($files['img'])) {
			$result['error'] = __('Empty File', 'ai-copilot-content-generator');
			return $result;
		}
		$file = $files['img'];
		
		$extensions = array('png', 'gif', 'jpeg', 'jpg');
		$error = WaicUtils::controlUploatedFile($file, $extensions);
		
		if (!empty($error)) {
			$result['error'] = $error;
			return $result;
		}
		$size = (int) $file['name'];
		if ($size > $maxSize) {
			$result['error'] = __('File is too big!', 'ai-copilot-content-generator');
			return $result;
		}
			
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$f = base64_encode(file_get_contents($file['tmp_name']));
		$result['file'] = 'data:image/' . $extension . ";base64,{$f}";
		return $result;
	}
		
	public function sendMessage( $message, $taskId, $mode, $cAware = '', $files = false, $options = array() ) {
		$workspace = WaicFrame::_()->getModule('workspace');
		$task = $workspace->getModel('tasks')->getTask($taskId);
		if (!$task || empty($task)) {
			return array('question' => __('Unexpected error.', 'ai-copilot-content-generator'), 'error' => 1);
		}
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		
		$file = '';
		if ($files) {
			$api = WaicUtils::getArrayValue($params, 'api', array(), 2);
			$files = $this->getFileString($files, WaicUtils::getArrayValue($api, 'max_file_size', 5, 1) * 1048576);
			if (!empty($files['error'])) {
				return array('question' => $files['error'], 'error' => 1);
			}
			$file = $files['file'];
		}
		if (isset($options['user_id'])) {
			$user = empty($options['user_id']) ? false : get_user_by('id', (int) $options['user_id']);
		} else {
			$user = wp_get_current_user();
		}
		$userId = $user ? $user->ID : 0;
		$isGuest = empty($userId);
		$ip = isset($options['ip']) ? $options['ip'] : WaicUtils::getRealUserIp();
		
		$hisModel = $workspace->getModel('history');
	
		$general = WaicUtils::getArrayValue($params, 'general', array(), 2);
		$context = WaicUtils::getArrayValue($params, 'context', array(), 2);
		$tools = WaicUtils::getArrayValue($params, 'tools', array(), 2);
		
		$callTools = array();
		
		$isError = false;
		$allLimit = WaicUtils::getArrayValue($general, 'alltime_limit', 0, 1);
		if (!empty($allLimit)) {
			$where = array(
				'task_id' => $taskId,
				'mode' => 0,
			);
			$cntTokens = $hisModel->getCountTokens($where);
			if ($cntTokens >= $allLimit) {
				$isError = true;
				$errMsg = 'Exceeds value: All-Time Limit = ' . $allLimit;
				$task = $workspace->getModel('tasks')->updateTask($taskId, array('status' => 6));
			}
		}

		if (!$isError) {
			$monLimit = WaicUtils::getArrayValue($general, 'monthly_limit', 0, 1);
			$whereMonth = "created BETWEEN'" . WaicUtils::getConvertedDate(false, 'Y-m-01 00:00:00') . "' AND '" . WaicUtils::getConvertedDate(false, 'Y-m-t 23:59:59') . "'";
			if (!empty($monLimit)) {
				$where = array(
					'task_id' => $taskId,
					'mode' => 0,
					'additionalCondition' => $whereMonth,
				);
				$cntTokens = $hisModel->getCountTokens($where);
				if ($cntTokens >= $monLimit) {
					$isError = true;
					$errMsg = 'Exceeds value: Monthly Limit = ' . $monLimit;
					$task = $workspace->getModel('tasks')->updateTask($taskId, array('status' => 8));
				}
			}
		}
		if (!$isError) {
			if (empty($mode) && WaicUtils::getArrayValue($general, 'e_limit_roles', 0, 1)) {
				$rLimits = WaicUtils::getArrayValue($general, 'limit_roles', array(), 2);
				$uRoles = $isGuest ? array('') : $user->roles;
				$userLimit = '';
				if (!empty($rLimits)) {
					foreach ($rLimits as $limit) {
						$role = WaicUtils::getArrayValue($limit, 'role');
						if (in_array($role, $uRoles)) {
							$userLimit = WaicUtils::getArrayValue($limit, 'max', '', 1, false, true, true);
							 break;
						}
					}
				}
				if ('' !== $userLimit) {
					if (!empty($userLimit)) {
						$where = array(
							'task_id' => $taskId,
							'mode' => 0,
							'additionalCondition' => $whereMonth,
						);
						if ($isGuest) {
							$where['ip'] = $ip;
						} 
						$where['user_id'] = $userId;
						$cntTokens = $hisModel->getCountTokens($where);
					} else {
						$cntTokens = 1;
					}
					if ($cntTokens >= $userLimit) {
						$isError = true;
						$errMsg = 'Exceeds value: Role-Based Limit = ' . $userLimit;
					}
				}
			}
		}
		if (!$isError) {
			if (!empty($message)) {
				$maxInput = WaicUtils::getArrayValue($general, 'max_input', 0, 1);
				if (!empty($maxInput)) {
					$message = WaicUtils::mbsubstr($message, 0, $maxInput);
				}
			}
			
			$maxContext = WaicUtils::getArrayValue($general, 'max_context', 0, 1);
			$needContolLen = !empty($maxContext);
			$prompt = array();
			$apiOptions = WaicUtils::getArrayValue($params, 'api', array(), 2);
			
			$instructions = "You are a chatbot. You strictly follow the given instructions and, while maintaining context, respond with exactly one message to the user's last message, without any additional text or unnecessary information." . PHP_EOL . PHP_EOL;
			
			$apiModel = WaicUtils::getArrayValue($apiOptions, 'model');
			if (strpos($apiModel, 'ft:') === 0) {
				$instructions .= 'You are a fine-tuned AI assistant trained on a proprietary knowledge base. ' . PHP_EOL .
					'You must always attempt to extract answers directly from this internal knowledge base first. ' . PHP_EOL .
					'Only if no relevant information exists in the training data, you may infer an answer — but stay within the logical boundaries of the question. ' . PHP_EOL .
					'Never invent facts that contradict your training. ' . PHP_EOL .
					'If the question is vague, incomplete, or misleading — try to clarify or rephrase it before answering.' . PHP_EOL;
			}
			
			$instructions .= '- Tone of voice: ' . WaicUtils::getArrayValue($apiOptions, 'tone') . PHP_EOL .
				'- Instructions: ' . WaicUtils::getArrayValue($context, 'instructions') . PHP_EOL .
				'Output format (must-follow): ' . PHP_EOL .
				'- Respond only in valid HTML. ' . PHP_EOL . 
				'- Use only content-level HTML tags such as <p>, <ul>, <li>, <strong>, <em> (only if needed). ' . PHP_EOL .
				'- Do not include any document-level tags such as <html>, <head>, or <body>. ' . PHP_EOL .
				'- Return HTML only — no explanations, no extra text.';
				
			$knowledge = WaicUtils::getArrayValue($params, 'knowledge', array(), 2);
			$embedId = WaicUtils::getArrayValue($knowledge, 'embeddings', 0, 1);
			if (!empty($embedId)) {
				$instructions .= WaicDispatcher::applyFilters('addEmbeddingText', '', $embedId, $message);
			}
			
			$aiProvider = $workspace->getModel('aiprovider')->getInstance($apiOptions);
			if (!$aiProvider) {
				return false;
			}
			$aware = '';
			if (!empty($cAware) && WaicUtils::mbstrpos($instructions, '{CONTENT}')) {
				$aware = $cAware;
			}
			if ($needContolLen) {
				$lenInstr = WaicUtils::mbstrlen($instructions);
				$lenContent = $lenInstr;
				$lenAware = WaicUtils::mbstrlen($aware);
				$lenContent += $lenAware;
				$lens = array();
			} else if (!empty($aware)) {
				$instructions = str_replace('{CONTENT}', $aware, $instructions);
			}
			$toolsSupport = ( 'perplexity' != $aiProvider->getEngine() );
			
			if ($toolsSupport && WaicUtils::getArrayValue($tools, 'prod_enabled', 0, 1)) {
				$instructions .= '=== PRODUCT RECOMMENDATIONS ===' . PHP_EOL .
					'When a user asks about products to buy, use the search_products function to find and recommend WooCommerce products.' . PHP_EOL;
				
				$prodPrompt = WaicUtils::getArrayValue($tools, 'prod_prompt');
				if (!empty($prodPrompt)) {
					$instructions .= 'Additional instructions: ' . $prodPrompt . PHP_EOL;
				}
				
				if (WaicUtils::getArrayValue($tools, 'prod_taxonomies', 0, 1)) {
					$callTools[] = 'get_taxonomy_values';
					$callTools[] = 'search_products_tax';
					$instructions .= 'Available product taxonomies in this store:' . PHP_EOL; 
					$taxonomies = get_object_taxonomies(array('product', 'product_variation'), 'objects');
					foreach ($taxonomies as $taxonomy) {
						$instructions .= '- ' . $taxonomy->name . ' (' . $taxonomy->label . ')' . PHP_EOL;
					}
					$instructions .= 'IMPORTANT RULE: If the user request includes categories, tags or attributes such as color, size, material, or any other taxonomy value, you MUST first call the get_taxonomy_values function for the relevant taxonomy to retrieve the available values. Only after receiving the taxonomy values should you select the correct slug(s) and then call the search_products function with those slugs in the taxonomies parameter. Never pass user text directly into search_products without verifying the correct slug from get_taxonomy_values.' . PHP_EOL .
						'When taxonomy values are needed, make a single get_taxonomy_values call with all taxonomies combined into one taxonomies array.' . PHP_EOL;
				} else {
					$callTools[] = 'search_products';
				}
				
				$instructions .= 'IMPORTANT: When calling the search_products function, always provide a brief message to the user indicating that you\'re searching.' . PHP_EOL .
					'After receiving search results with product details (title and short description) the "content" field MUST be a plain string.' . PHP_EOL .
					'The content field should describe the specific products based on their titles and short descriptions. Use the same language as the user\'s query.' . PHP_EOL .
					'Do not place the product IDs inside any HTML tags. After closing all tags, append the product IDs as a comma-separated list, separated from the text by the delimiter ##IDS##prod:.' . PHP_EOL .
					'=== END PRODUCT RECOMMENDATIONS ===' . PHP_EOL;
			}
			
			if ($toolsSupport && WaicUtils::getArrayValue($tools, 'post_enabled', 0, 1)) {
				$instructions .= '=== BLOG POST RECOMMENDATIONS ===' . PHP_EOL .
					'When a user asks about blog articles or content, use the search_posts function to find and recommend blog posts.' . PHP_EOL;
				
				$prodPrompt = WaicUtils::getArrayValue($tools, 'post_prompt');
				if (!empty($prodPrompt)) {
					$instructions .= 'Additional instructions: ' . $prodPrompt . PHP_EOL;
				}
				
				if (WaicUtils::getArrayValue($tools, 'post_taxonomies', 0, 1)) {
					if (!in_array('get_taxonomy_values', $callTools)) {
						$callTools[] = 'get_taxonomy_values';
					}
					$callTools[] = 'search_posts_tax';
					$instructions .= 'Available post taxonomies in this store:' . PHP_EOL; 
					$taxonomies = get_object_taxonomies(array('post'), 'objects');
					foreach ($taxonomies as $taxonomy) {
						$instructions .= '- ' . $taxonomy->name . ' (' . $taxonomy->label . ')' . PHP_EOL;
					}
					$instructions .= 'IMPORTANT RULE: If the user request includes categories, tags or any other taxonomy value, you MUST first call the get_taxonomy_values function for the relevant taxonomy to retrieve the available values. Only after receiving the taxonomy values should you select the correct slug(s) and then call the search_posts function with those slugs in the taxonomies parameter. Never pass user text directly into search_posts without verifying the correct slug from get_taxonomy_values.' . PHP_EOL .
						'When taxonomy values are needed, make a single get_taxonomy_values call with all taxonomies combined into one taxonomies array.' . PHP_EOL;
				} else {
					$callTools[] = 'search_posts';
				}
				
				$instructions .= 'IMPORTANT: When calling the search_posts function, always provide a brief message to the user indicating that you\'re searching.' . PHP_EOL .
					'After receiving search results with post details (title and excerpt) the "content" field MUST be a plain string.' . PHP_EOL .
					'The content field should describe the specific posts based on their titles and excerpts. Use the same language as the user\'s query.' . PHP_EOL .
					'Do not place the post IDs inside any HTML tags. After closing all tags, append the post IDs as a comma-separated list, separated from the text by the delimiter ##IDS##post:.' . PHP_EOL .
					'=== END BLOG POST RECOMMENDATIONS ===' . PHP_EOL;
			}

			$prompt[] = array('role' => 'system', 'content' => $instructions);
			$maxCntMessages = WaicUtils::getArrayValue($general, 'max_messages', 10, 1);
			$isActive = true;
			if (empty($mode)) {
				$lifetime = WaicUtils::getArrayValue($general, 'lifetime', 0, 1);
				$isActive = $this->isActiveChat($taskId, $userId, $ip, $mode, $lifetime);
			}
			if (isset($options['use_log'])) {
				$isActive = ( true == $options['use_log'] );
			}
			
			$log = $isActive ? $this->getUserChatLog($taskId, $userId, $ip, $mode, $maxCntMessages) : array();
			$log[] = array(
				'question' => $message,
				'answer' => '',
				'file' => $file,
			);
			$n = 1;
			foreach ($log as $l) {
				$question = $l['question'];
				$answer = $l['answer'];
				$fileStr = $l['file'];
				
				if ($needContolLen) {
					$len = WaicUtils::mbstrlen($question);
					if (!empty($fileStr)) {
						$len += WaicUtils::mbstrlen($fileStr);
					}
					$lens[$n] = $len;
					$n++;
					$lenContent += $len;
					if (!empty($answer)) {
						$len = WaicUtils::mbstrlen($answer);
						$lens[$n] = $len;
						$n++;
						$lenContent += $len;
					}
				}
				if (empty($fileStr)) {
					$content = $question;
				} else {
					$content = array();
					if (!empty($question)) {
						$content[] = array('type' => 'text', 'text' => $question);
					}
					$content[] = array(
						'type' => 'image_url', 
						'image_url' => array('url' => $fileStr),
					);
				}
				$prompt[] = array('role' => 'user', 'content' => $content);
				if (!empty($answer)) {
					$prompt[] = array('role' => 'assistant', 'content' => $answer);
				}
			}

			if ($needContolLen) {
				if ($lenContent > $maxContext) {
					$delta = $lenContent - $maxContext;
					if ($delta < $lenAware) {
						$d = $lenAware - $delta;
						$aware = WaicUtils::mbstrlen($aware, 0, $d);
						$lenContent = $maxContext;
					} else {
						$aware = '';
						$lenContent -= $lenAware;
					}
				} 
				
				if ($lenContent > $maxContext) { 
					$delta = $lenContent - $maxContext;
					foreach ($lens as $n => $l) {
						unset($prompt[$n]);
						$lenContent -= $l;
						if ($lenContent <= $maxContext) { 
							break;
						}
					}
					$prompt = array_values($prompt);
				}
				if ($lenContent > $maxContext || count($prompt) < 2) {
					$isError = true;
					$errMsg = 'Exceeds value: Context Max Length = ' . $maxContext;
				} else if (!empty($aware)) {
					$prompt[0]['content'] = str_replace('{CONTENT}', $aware, $prompt[0]['content']);
				}
			}
		}
		$history = array(
			'task_id' => $taskId,
			'user_id' => $userId,
			'ip' => $ip,
			'mode' => $mode,
			'status' => 2,
			'tokens' => 0,
		);
		
		if ($isError) {
			$result = array(
				'his_id' => $hisModel->saveHistory($history),
				'msg' => $errMsg,
				'error' => 1,
			);
		} else {
			$aiProvider->init( $taskId, $userId, $ip, $mode, false );

			if ($aiProvider->setApiOptions($apiOptions)) {
				$opts = array('messages' => $prompt);
				if (!empty($callTools)) {
					$opts['tools'] = array('functions' => $callTools, 'options' => $tools);
				}
				$result = $aiProvider->getText($opts);
			} else {
				$result = array(
					'error' => 1,
					'msg' => WaicFrame::_()->getLastError(),
					'his_id' => $hisModel->saveHistory($history),
				);
			}
		}
		$newLog = array(
			'his_id' => $result['his_id'], 
			'question' => $this->controlText($message),
			'answer' => $this->controlText($result['error'] ? $result['msg'] : $result['data']),
			'file' => $file,
			'error' => $result['error'],
			'tt' => WaicUtils::getFormatedDateTime(WaicUtils::getTimestamp(), 'H:i'),
		);

		if (!empty($result['his_id'])) {
			$this->insert($newLog);
		}
		
		if (empty($newLog['error'])) {
			if (!empty($file)) {
				$newLog['question'] = '<img src="' . $file . '">';//__('File uploaded', 'ai-copilot-content-generator');
			}
			if (WaicUtils::getArrayValue($context, 'e_human_request', 0, 1)) {
				$delay = WaicUtils::getArrayValue($context, 'human_request_delay', 0, 1);
				if (!empty($delay)) {
					if ($maxCntMessages < $delay) {
						$where = array(
							'task_id' => $taskId,
							'mode' => $mode,
						);
						if ($isGuest) {
							$where['ip'] = $ip;
						} else {
							$where['user_id'] = $userId;
						}
						$cnt = $hisModel->getCountRequests($where);
					} else {
						$cnt = count($log);
					}
					if ($cnt >= $delay) {
						$newLog['btn'] = array(
							array(
								'link' => '#', 
								'name' => WaicUtils::getArrayValue($context, 'human_request_button'), 
								'class' => 'waic-message-button waic-human-request',
								'uniq' => '.waic-human-request',
							),
						);
					}
				}
			}
			if (!empty($callTools)) {
				$newLog = $this->getModule()->getView()->renderCards($newLog, 'answer', $tools);
			}
		} else {
			$msg = WaicUtils::getArrayValue($context, 'error_message');
			if (!empty($msg)) {
				$newLog['answer'] = $msg;
			}
			if (WaicUtils::getArrayValue($context, 'e_error_request', 0, 1)) {
				$newLog['need_email'] = 1;
				$newLog['request'] = 'error';
				$newLog['plh_email'] = WaicUtils::getArrayValue($context, 'plh_email');
			} else {
				$newLog['disable'] = 1;
			}
		}
		return $newLog;
	}

	public function controlText( $str ) {
		if ( !$str ) {
			return '';
		}
		$str = str_replace(array('```html', '```'), array('', ''), $str);
		return str_replace(array("'"), array('`'), $str);
		//return preg_replace('/\r\n|\r|\n/', '<br>', str_replace(array("'"), array('`'), $str));
	}
	
	public function getHistory( $params ) {
		$length = WaicUtils::getArrayValue($params, 'length', 10, 1);
		$start = WaicUtils::getArrayValue($params, 'start', 0, 1);
		//$search = esc_sql(WaicUtils::getArrayValue(WaicUtils::getArrayValue($params, 'search', array(), 2), 'value'));
		$search = esc_sql(WaicUtils::getArrayValue($params, 'search'));

		$taskId = WaicUtils::getArrayValue($params, 'task_id', 0, 1);
		$where = array();
		$args = array();
		if (!empty($search)) {
			global $wpdb;
			$like = '%' . $wpdb->esc_like($search) . '%';
			if ('admin' == $search) {
				$where[] = 'h.mode=1';
			} else if ('front' == $search) {
				$where[] = 'h.mode=0';
			}
			$where[] = 'h.created LIKE %s';
			$where[] = 'u.user_login LIKE %s';
			$where[] = 'h.ip LIKE %s';
			$where[] = 'EXISTS(SELECT 1 FROM @__chatlogs l WHERE l.his_id=h.id AND (l.question LIKE %s OR l.answer LIKE %s))';
			$args = array($like, $like, $like, $like, $like);
		}
		
		$query = 'SELECT DATE(h.created) as dd, h.task_id, h.user_id, u.user_login, h.ip, h.mode, sum(tokens) as sum_tokens, TIMEDIFF(MAX(h.created),MIN(h.created)) as duration, count(*) as cnt_mes' .
			' FROM @__history as h' .
			' LEFT JOIN #__users u ON (u.id=h.user_id)' .
			" WHERE h.feature='chatbots'" .
			( empty($taskId) ? '' : ' AND h.task_id=' . $taskId ) .
			( empty($where) ? '' : ' AND (' . implode(' OR ', $where) . ')' ) .
			' GROUP BY DATE(h.created), h.task_id, h.user_id, u.user_login, h.ip, h.mode'; 
		
		$order = WaicUtils::getArrayValue($params, 'order', array(), 2);
		$orderBy = 0;
		$sortOrder = 'desc';
		$orders = array('dd', 'user_login', 'ip', 'mode', 'sum_tokens', 'duration', 'cnt_mes');
		if (isset($order[0])) {
			$orderBy = WaicUtils::getArrayValue($order[0], 'column', $orderBy, 1);
			$sortOrder = WaicUtils::getArrayValue($order[0], 'dir', $sortOrder);
		}
		$query .= ' ORDER BY ' . $orders[$orderBy] . ( 'desc' == strtolower($sortOrder) ? ' desc' : ' asc' );
		$logs = WaicDb::get($query, 'all', ARRAY_A, $args);
		
		$totalCount = empty($logs) ? 0 : count($logs);
		
		if ($length > 0) {
			if ($start >= $totalCount) {
				$start = 0;
			}
			$end = $start + $length;
		} else {
			$end = $totalCount;
		}
		$query = 'SELECT IF(l.question LIKE %s, l.question, l.answer)' .
			' FROM @__history as h' .
			' INNER JOIN @__chatlogs l ON (l.his_id=h.id)' .
			' WHERE h.task_id=%d' .
			' AND h.mode=%d' .
			' AND h.user_id=%d' .
			" AND h.created BETWEEN %s AND %s" .
			' AND (l.question LIKE %s OR l.answer LIKE %s)' .
			" AND ip=%s" .
			' LIMIT 1'; 
		$rows = array();
		if ($totalCount > 0) {
			$view = __('view', 'ai-copilot-content-generator');
			$guest = __('guest', 'ai-copilot-content-generator');
			$modes = array('front', 'admin', 'custom');
			$maxStr = 50;
			$halb = 25;
			$sLen = WaicUtils::mbstrlen($search);
			$dFormat = WaicUtils::getCurrentDateFormat();
			$n = 0;
			for ($i = $start; $i < $end; $i++) {
				if (!isset($logs[$i])) {
					break;
				}
				$log = $logs[$i];
				$tId = $log['task_id'];
				$mode = $log['mode'];
				$uId = $log['user_id'];
				$ip = $log['ip'];
				$dd = $log['dd'];
				$str = '';
				
				if (!empty($search)) {
					$found = WaicDb::get($query, 'one', ARRAY_A, array($like, $tId, $mode, $uId, $dd . ' 00:00:00', $dd . ' 23:59:59', $like, $like, $ip));
					if ($found) {
						$found = wp_strip_all_tags($found);
						$fLen = WaicUtils::mbstrlen($found);
						if ($fLen <= $maxStr) {
							$str = $found;
						} else {
							$pos = WaicUtils::mbstripos($found, $search);
							if ($pos !== false) {
								if ($pos < $halb) {
									$str = 	WaicUtils::mbsubstr($found, 0, $maxStr);
								} else if ($fLen - $pos < $maxStr) {
									$str = '...' . WaicUtils::mbsubstr($found, $fLen - $maxStr);
								} else {
									$str = '...' . WaicUtils::mbsubstr($found, $pos - $halb, $maxStr) . '...';
								}
							}
						}
						$str = str_ireplace($search, '{{' . $search . '}}', str_replace('"', '', $str));
					}
				}
				$data = array(
					'task_id' => $tId,
					'dd' => $dd,
					'user_id' => $uId,
					'ip' => $ip,
					'mode' => $mode,
				);
				$rows[] = array(
					WaicUtils::convertDateFormat($dd, 'Y-m-d', $dFormat),
					( empty($uId) ? $guest : $log['user_login'] ),
					$ip,
					$modes[$mode],
					$log['sum_tokens'],
					$log['duration'],
					$log['cnt_mes'],
					'<a href="#" class="waic-history-log" data-num="' . $n . '"data-log="' . esc_attr(json_encode($data)) . '" data-found="' . esc_attr($str) . '">' . $view . '</a>',
				);
				$n++;
			}
		}
		return array(
			'data' => $rows,
			'total' => $totalCount,
		);
	}
	public function exportLog( $params ) {
		$taskId = WaicUtils::getArrayValue($params, 'chat_id', 0, 1);
		$isAll = empty($taskId);
		
		$mode = WaicUtils::getArrayValue($params, 'mode', 0, 1);
		$users = WaicUtils::getArrayValue($params, 'users', 0, 1);
		$from = WaicUtils::getArrayValue($params, 'from');
		if (!WaicUtils::checkDateTime($from, 'Y-m-d')) {
			$from = false;
		}
		$to = WaicUtils::getArrayValue($params, 'to');
		if (!WaicUtils::checkDateTime($to, 'Y-m-d')) {
			$to = false;
		}
		
		if (ob_get_contents()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8'); 
		header('Content-Disposition: attachment; filename="aiwu_export.json"');
		if (ob_get_contents()) {
			ob_end_clean();
		}
		$query = 'SELECT DATE(created) as dd, task_id, user_id, ip, mode, model, sum(tokens) as sum_tokens, min(created) as started' .
			' FROM @__history' .
			" WHERE feature='chatbots'" .
			( empty($taskId) ? '' : ' AND task_id=' . $taskId ) .
			( 9 == $mode ? '' : ' AND mode=' . $mode ) .
			( empty($users) ? '' : ' AND user_id' . ( 1 == $users ? '>0' : '=0' ) ) .
			( $isAll ? '' : ' AND task_id=' . $taskId ) .
			( $from ? " AND created>='" . $from . " 00:00:00'" : '' ) .
			( $to ? " AND created<='" . $to . " 23:59:59'" : '' ) .
			' GROUP BY DATE(created), task_id, user_id, ip, mode, model';
		$sessions = array();
		$history = WaicDb::get($query);
		if ($history && !empty($history)) {
			$chatbots = WaicFrame::_()->getModule('workspace')->getModel('tasks')->getTasksList(array('feature' => 'chatbots'));
			$tools = array('prod' => 'search_products', 'post' => 'search_posts');
			foreach ($history as $his) {
				$dd = $his['dd'];
				$model = $his['model'];
				$tId = $his['task_id'];
				$uId = $his['user_id'];
				$ip = $his['ip'];
				$query = "SELECT h.created, l.question, l.answer, IF(l.file='',0,1) as has_file" .
					' FROM @__history as h' .
					' INNER JOIN @__chatlogs l ON (l.his_id=h.id)' .
					' WHERE h.task_id=' . ( (int) $tId ) .
					" AND h.model='" . $model . "'" .
					' AND h.mode=' . ( (int) $his['mode'] ) .
					' AND h.user_id=' . ( (int) $uId ) .
					" AND ip='" . $ip . "'" .
					" AND h.created BETWEEN '" . $dd . " 00:00:00' AND '" . $dd . " 23:59:59'" .
					' ORDER BY h.id';
				$logs = WaicDb::get($query);
				if ($logs && !empty($logs)) {
					$messages = array();
					foreach ($logs as $log) {
						$messages[] = array(
							'role' => 'user',
							'ts' => $log['created'],
							'text' => ( empty($log['has_file']) ? $this->tolalCleanStr($log['question']) : 'FILE UPLOADED' ),
						);
						$parts = explode('##IDS##', $log['answer']);
						$message = array(
							'role' => 'assistant',
							'ts' => $log['created'],
							'text' =>  $this->tolalCleanStr($parts[0]),     
						);
						if (count($parts) == 2) {
							$textIds = trim($parts[1]);
							$ps = explode(':', $textIds);
							if (count($ps) == 2) {
								$tool = WaicUtils::getArrayValue($tools, $ps[0]);
								if (!empty($tool)) {
									$message['tools'] = array($tool => $ps[1]);
								}
							}
						}
						$messages[] = $message;
					}
					$sessions[] = array(
						'bot_id' => $tId,
						'bot_name' => WaicUtils::getArrayValue($chatbots, $tId),
						'model' => $model,
						'user_id' => $uId,
						'ip' => $ip,
						'started' => $his['started'],
						'tokens' => $his['sum_tokens'],
						'messages' => $messages,
					);
					
				}
			}
		}
		if (empty($sessions)) {
			WaicFrame::_()->pushError(esc_html__('The log is empty', 'ai-copilot-content-generator'));
			return false;
		}
		
		return json_encode(array('sessions' => $sessions), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
	public function tolalCleanStr( $str ) {
		return str_replace(["\n", "\r", "\t", '  '], ' ', wp_strip_all_tags($str));
	}
	
	public function isRunningLaunch() {
		$option = $this->_workspace->getWorkspaceFlagById($this->_launchFlagId);
		if (is_null($option) || empty($option)) {
			return true;
		}
		if (empty((int) $option['value'])) {
			return false;
		} 
		if ($option['timeout'] < WaicUtils::getTimestamp()) {
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		return true;
	}
	public function setRunningLaunch( $data ) {
		if (!$this->isRunningLaunch()) {
			return $this->_workspace->updateById($data, $this->_launchFlagId);
		}
		return false;
	}
	public function updateRunningLaunch( $data ) {
		if ($this->isRunningLaunch()) {
			return $this->_workspace->updateById($data, $this->_launchFlagId);
		}
		return false;
	}
	/*public function resetRunningLaunch() {
		return $this->updateRunningLaunch(array('value' => 0, 'flag' => 0, 'timeout' => 0));
	}*/
	public function getLaunchPercent( $code ) {
		$launch = WaicFrame::_()->getModule('workspace')->getModel()->getWorkspaceFlagById($this->_launchFlagId);
		if (empty($launch)) {
			WaicFrame::_()->pushError(esc_html__('The launch process not found.', 'ai-copilot-content-generator'));
			return false;
		}
		
		if (!empty($launch['value']) && $launch['value'] != $code) {
			WaicFrame::_()->pushError(esc_html__('Another launch process is running. Please try again later', 'ai-copilot-content-generator'));
			return false;
		}
		return (int) $launch['flag'];
	}
	
	
	public function launchChatbot( $code, $params ) {
		$workspace = WaicFrame::_()->getModule('workspace');
		$this->_workspace = $workspace->getModel();
		
		$setup = WaicUtils::getArrayValue($params, 'setup', array(), 2);
		if (!empty($setup['api_keys']) && is_string($setup['api_keys'])) {
			$setup['api_keys'] = WaicUtils::jsonDecode(stripslashes($setup['api_keys']), true);
		}
		$options = WaicFrame::_()->getModule('options')->getModel();
		$apiOptions = $options->get('api');
		$apiVariations = $options->getVariations('api');
		
		$engine = WaicUtils::getArrayValue($setup, 'engine');
		$engines = WaicUtils::getArrayValue($apiVariations, 'engines', array(), 2);
		if (!isset($engines[$engine])) {
			WaicFrame::_()->pushError(esc_html__('The specified provider is not supported.', 'ai-copilot-content-generator'));
			return false;
		}
		$model = WaicUtils::getArrayValue($setup, 'model');
		$models = WaicUtils::getArrayValue($apiVariations, 'model', array(), 2);

		if (empty($models[$engine]) || !isset($models[$engine][$model])) {
			WaicFrame::_()->pushError(esc_html__('The specified model is not supported.', 'ai-copilot-content-generator'));
			return false;
		}
		$apiOptions['engine'] = $engine;
		$modelField = WaicUtils::getArrayValue($apiVariations['model-fields'], $engine, 'model');
		$apiOptions[$modelField] = $model;
		
		$apiKeys = WaicUtils::getArrayValue($setup, 'api_keys', array(), 2);
		$keyFields = WaicUtils::getArrayValue($apiVariations, 'key-fields', array(), 2);
		$aiKeys = array();
		foreach ($keyFields as $e => $field) {
			$apiOptions[$field] = WaicUtils::getArrayValue($apiKeys, $e);
		}
		$keyField = WaicUtils::getArrayValue($keyFields, $engine);
		if (empty($keyField) || empty($apiOptions[$keyField])) {
			WaicFrame::_()->pushError(esc_html__('Enter the API key for the selected AI provider.', 'ai-copilot-content-generator'));
			return false;
		}
		$context = WaicUtils::getArrayValue($params, 'context', array(), 2);
		$botName = WaicUtils::getArrayValue($context, 'ai_name');
		if (empty($botName)) {
			WaicFrame::_()->pushError(esc_html__('Set Bot Name please.', 'ai-copilot-content-generator'));
			return false;
		}
		
		$emProds = WaicUtils::getArrayValue($setup, 'knowledge_prods', 0, 1);
		$emPosts = WaicUtils::getArrayValue($setup, 'knowledge_posts', 0, 1);
		$emPages = WaicUtils::getArrayValue($setup, 'knowledge_pages', 0, 1);
		$withEmbed = $emProds || $emPosts || $emPages;
		$knowledge = $withEmbed ? WaicUtils::getArrayValue($params, 'knowledge', array(), 2) : array();
		
		if ($withEmbed) {
			$embedOptions = WaicUtils::getArrayValue($knowledge, 'embed');
			$embedEngine = WaicUtils::getArrayValue($embedOptions, 'engine');
			if (!isset($engines[$embedEngine])) {
				WaicFrame::_()->pushError(esc_html__('The specified provider for Embeddings is not supported.', 'ai-copilot-content-generator'));
				return false;
			}
			$keyField = WaicUtils::getArrayValue($keyFields, $embedEngine);
			if (empty($keyField) || empty($apiOptions[$keyField])) {
				WaicFrame::_()->pushError(esc_html__('Enter the API key for the selected Embedding AI provider.', 'ai-copilot-content-generator'));
				return false;
			}
			if (!$emProds) {
				$knowledge['prods'] = array('prod_include' => 'none');
			}
			if (!$emPosts) {
				$knowledge['posts'] = array('post_include' => 'none');
			}
			$knowledge['pages'] = array('page_include' => $emPages ? '' : 'none');
			$knowledge['embeddings'] = 'new';
			
			$vector = WaicUtils::getArrayValue($embedOptions, 'vector');
			if ('wpvector' != $vector) {
				$vectorData = WaicUtils::getArrayValue($embedOptions, $vector, array(), 2);
				if (empty($vectorData['api_key']) 
					|| ('pinecone' == $vector && empty($vectorData['index_host']))
					|| ('qdrant4' == $vector && empty($vectorData['api_url']))) {
					WaicFrame::_()->pushError(esc_html__('Enter vector base data', 'ai-copilot-content-generator'));
					return false;
				}
			}
		}
		
		if ($this->isRunningLaunch()) {
			WaicFrame::_()->pushError(esc_html__('Another launch process is running. Please try again later.', 'ai-copilot-content-generator'));
			return false;
		}
		set_time_limit(0);
		
		if (!$this->setRunningLaunch(array('value' => $code, 'flag' => 0, 'timeout' => WaicUtils::getTimestamp() + $this->_launchTimeout))) {
			WaicFrame::_()->pushError(esc_html__('Error related to setting the launch flag.', 'ai-copilot-content-generator'));
			return false;
		}
		
		$summary = $this->getSiteSummary();
		if (!$this->updateRunningLaunch(array('flag' => 20))) {
			WaicFrame::_()->pushError(esc_html__('Error related to setting the launch flag.', 'ai-copilot-content-generator') . '(1)');
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		
		$aiProvider = WaicFrame::_()->getModule('workspace')->getModel('aiprovider')->getInstance($apiOptions);
		if (!$aiProvider) {
			WaicFrame::_()->pushError(esc_html__('The specified provider is not found.', 'ai-copilot-content-generator'));
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		$aiProvider->init(0, 0, '', 0, false);
		
		if (!$aiProvider->setApiOptions($apiOptions)) {
			WaicFrame::_()->pushError(esc_html__('The specified provider options is not supported.', 'ai-copilot-content-generator'));
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		$lang = WaicUtils::getArrayValue($setup, 'language', 'en');
		$language = WaicUtils::getArrayValue(WaicUtils::getArrayValue($apiVariations, 'language', array(), 2), $lang);
		
		$prompt = 'Your task is to generate a system prompt for an AI chatbot for the following website: ' . PHP_EOL .
			$summary . PHP_EOL .
			'Context: ' . PHP_EOL .
			'- Website language: ' . $language . PHP_EOL .
			'- User instructions: ' . WaicUtils::getArrayValue($setup, 'role') . PHP_EOL .
			'Requirements: ' . PHP_EOL .
			'Generate a concise system prompt (3–5 sentences) that: ' . PHP_EOL .
			'1. Clearly defines the chatbot’s role and purpose for THIS specific website and business. ' . PHP_EOL .
			'2. Describes what the chatbot can help users with (sales, support, navigation, information), based on the business type and selected role. ' . PHP_EOL .
			'3. Reflects the brand’s tone and communication style inferred from the website content. ' . PHP_EOL .
			'4. Explains how the chatbot should guide users (e.g. to products, categories, pages, support, or contact options when needed). ' . PHP_EOL .
			'5. Sets clear boundaries: what the chatbot can answer, and when it should suggest contacting a human or using official pages. ' . PHP_EOL .
			'Important rules: ' . PHP_EOL .
			'- Do not invent product details. When users ask about products or availability, rely on connected tools (e.g. catalog search / function calling) if they exist. If such tools are not available, answer only based on the provided website summary. ' . PHP_EOL .
			'- If key pages or links (such as Contact, Support, Shipping, Returns, FAQ, About) are provided in the website summary, use them as the primary sources for directing users. If they are not available, guide users in a generic way (e.g. suggest visiting the contact or support section of the website). ' . PHP_EOL .
			'- Keep the prompt specific to this business, not a generic assistant description. ' . PHP_EOL .
			'Write the final system prompt in ' . $language . PHP_EOL . 
			'Be specific, practical, and tailored to this website. ' . PHP_EOL .
			'You must respond with exactly the system prompt text only. Do NOT include any headings, labels, explanations, code fences, markdown, or extra text. Return a single paragraph of 3-5 sentences tailored to the website. Nothing else.';
		$result = $aiProvider->getText(array('prompt' => $prompt));
		
		
		if ($result['error']) {
			WaicFrame::_()->pushError($result['msg']);
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		if (!$this->updateRunningLaunch(array('flag' => 60))) {
			WaicFrame::_()->pushError(esc_html__('Error related to setting the launch flag.', 'ai-copilot-content-generator') . '(2)');
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		$appearance = WaicUtils::getArrayValue($params, 'appearance', array(), 2);
		$chatParams = array(
			'general' => WaicUtils::getArrayValue($params, 'general', array(), 2),
			'api' => $apiOptions,
			'tools' => WaicUtils::getArrayValue($params, 'tools', array(), 2),
			'knowledge' => $knowledge,
		);
		$context['instructions'] = 'You are an AI assistant designed to provide helpful, accurate, and context-aware responses. Follow the given instructions carefully and ensure your replies are relevant, concise, and aligned with the user’s needs.' . PHP_EOL . $result['data'];
		$position = WaicUtils::getArrayValue($setup, 'position');
		$appearance['desktop'] = array('position' => $position);
		$appearance['mobile'] = array('position' => $position);
		
		$messages = array(
			'welcome_message' => '👋 Want to chat? I’m an AI chatbot here to help you find your way. Ask me or select an option below.',
			'human_assistance_request' => 'Talk to a Human',
			'predefined_message_for_user' => 'Please provide your email address so we can assist you further. Our team will get back to you within 24 hours. Thank you!',
			'loader_text' => 'typing',
			'placeholder_text' => 'Write a message',
			'file_loader_text' => 'Uploading',
			'predefined_error_message' => 'We’re currently experiencing temporary technical issues. Please leave your email, and we’ll get back to you as soon as possible.',
			'placeholder_for_email' => 'Your email',
			'invalid_email_message' => 'Email is not correct',
			'thank_you_message' => 'Thank you, our expert will contact you as soon as possible',
			'pop_up_welcome_message' => '👋 Want to chat? I’m an AI chatbot here to help you find your way. Ask me or select an option below.',
		);
		$messagesNew = array();
		if ('en' != $lang) {
			$prompt = 'You are a localization assistant for AIWU ChatBOT. Your job is to produce ALL system UI messages for the chatbot in the requested language. ' . PHP_EOL . PHP_EOL .
				'IMPORTANT RULES: ' . PHP_EOL .
				'- Output must be ONLY valid JSON. No markdown, no comments, no extra text. Return only valid JSON — no markdown, no code fences, no extra text.' . PHP_EOL .
				'- Do not change JSON keys. ' . PHP_EOL .
				'- Preserve meaning, keep it natural and concise for UI. ' . PHP_EOL .
				'- Keep capitalization and punctuation appropriate for the target language. ' . PHP_EOL .
				'- Do NOT add emojis unless they already exist in the source text. ' . PHP_EOL .
				'- If the source contains placeholders in curly braces (e.g., {website_summary}, {bot_name}), keep them EXACTLY as-is. ' . PHP_EOL . PHP_EOL .
				'INPUT: ' . PHP_EOL .
				'Target language: ' . $language . PHP_EOL .
				'Website summary: ' . $summary . PHP_EOL .
				'Chatbot name: ' . $botName . PHP_EOL . PHP_EOL .
				'TASK: ' . PHP_EOL .
				'1) Generate a localized "welcome_message" in ' . $language . ' (1–2 sentences) that: ' . PHP_EOL .
				'- Introduces the chatbot by name (Chatbot name) ' . PHP_EOL .
				'- Briefly explains what it can help with, based on Website summary ' . PHP_EOL .
				'- Invites the user to ask a question or choose an option ' . PHP_EOL .
				'- Matches the brand tone implied by Website summary ' . PHP_EOL .
				'Return ONLY the final message text in the JSON field.' . PHP_EOL . PHP_EOL .
				'2) Translate ALL other provided UI strings into ' . $language . '. Return only translations (no explanations). ' . PHP_EOL . PHP_EOL .
				' SOURCE STRINGS (English): ' . json_encode($messages, JSON_UNESCAPED_UNICODE) . PHP_EOL .
				' OUTPUT JSON SCHEMA (keys must match exactly): ' . PHP_EOL .
				'{"welcome_message": "","human_assistance_request": "","predefined_message_for_user": "","loader_text": "","placeholder_text": "","file_loader_text": "","predefined_error_message": "","placeholder_for_email": "","invalid_email_message": "","thank_you_message": "","pop_up_welcome_message": ""}' . PHP_EOL . PHP_EOL .
				'Return only valid JSON — no markdown, no code fences, no extra text. Now produce the final JSON in ' . $language . '.';
			$result = $aiProvider->getText(array('prompt' => $prompt));
		
			if ($result['error']) {
				WaicFrame::_()->pushError($result['msg']);
				$this->_workspace->resetRunningFlag($this->_launchFlagId);
				return false;
			}
			$messagesNew = $result['data'];
			if (is_string($messagesNew)) {
				$messagesNew = WaicUtils::jsonDecode(stripslashes(str_replace(array('```json', '```', "\n"), array('', '', ''), $messagesNew)));
			}
		}
		if (!$this->updateRunningLaunch(array('flag' => 90))) {
			WaicFrame::_()->pushError(esc_html__('Error related to setting the launch flag.', 'ai-copilot-content-generator') . '(2)');
			$this->_workspace->resetRunningFlag($this->_launchFlagId);
			return false;
		}
		
		$context['welcome_message'] = empty($messagesNew['welcome_message']) ? $messages['welcome_message'] : $messagesNew['welcome_message'];
		$context['human_request_button'] = empty($messagesNew['human_assistance_request']) ? $messages['human_assistance_request'] : $messagesNew['human_assistance_request'];
		$context['human_request_message'] = empty($messagesNew['predefined_message_for_user']) ? $messages['predefined_message_for_user'] : $messagesNew['predefined_message_for_user'];
		
		$context['loader_text'] = empty($messagesNew['loader_text']) ? $messages['loader_text'] : $messagesNew['loader_text'];
		$context['plh_text'] = empty($messagesNew['placeholder_text']) ? $messages['placeholder_text'] : $messagesNew['placeholder_text'];
		$context['loader_file'] = empty($messagesNew['file_loader_text']) ? $messages['file_loader_text'] : $messagesNew['file_loader_text'];
		$context['error_message'] = empty($messagesNew['predefined_error_message']) ? $messages['predefined_error_message'] : $messagesNew['predefined_error_message'];
		
		$context['plh_email'] = empty($messagesNew['placeholder_for_email']) ? $messages['placeholder_for_email'] : $messagesNew['placeholder_for_email'];
		$context['error_email'] = empty($messagesNew['invalid_email_message']) ? $messages['invalid_email_message'] : $messagesNew['invalid_email_message'];
		$context['error_thank'] = empty($messagesNew['thank_you_message']) ? $messages['thank_you_message'] : $messagesNew['thank_you_message'];
		$popupMessage = empty($messagesNew['pop_up_welcome_message']) ? $messages['pop_up_welcome_message'] : $messagesNew['pop_up_welcome_message'];
		
		$appearance['desktop']['popup_message'] = $popupMessage;
		$appearance['mobile']['popup_message'] = $popupMessage;
		
		$chatParams['context'] = $context;
		$chatParams['appearance'] = $appearance;
		
		$id = $workspace->getModel('tasks')->saveTask('chatbots', 0, $chatParams);
		$this->_workspace->resetRunningFlag($this->_launchFlagId);
		return $id;
	}
	public function getSiteSummary() {
		$isShop = WaicUtils::isWooCommercePluginActivated();
		$summary = 'Website URL: ' . home_url() . PHP_EOL .
			'Website name: ' . get_bloginfo('name') . PHP_EOL .
			'Website description: ' . get_bloginfo('description') . PHP_EOL;
			
		if ($isShop) {
			$summary .= 'Business type: ecommerce' . PHP_EOL .
				'Country: ' . get_option('woocommerce_default_country') . PHP_EOL .
				'Currency: ' . get_woocommerce_currency() . PHP_EOL;
			$zones = WC_Shipping_Zones::get_zones(); 
			$data = array();
			foreach ($zones as $zone) {
				$shippingZone = new WC_Shipping_Zone($zone['id']);
				$methods = $shippingZone->get_shipping_methods();
				$zoneData = array(
					'zone' => $shippingZone->get_zone_name(),
					'methods' => array()
				); 
				foreach ($methods as $method) {
					$methodInfo = array(
						'id' => isset($method->id) ? $method->id : null,
						'instance_id' => method_exists($method, 'get_instance_id') ? $method->get_instance_id() : null,
						'title' => method_exists($method, 'get_title') ? $method->get_title() : ( isset($method->title) ? $method->title : '' ),
						'cost' => null, 
					);
					if (method_exists( $method, 'get_cost' )) { 
						$methodInfo['cost'] = $method->get_cost();
					} else {
						if (method_exists($method, 'get_option')) {
							$opt = $method->get_option('cost', '');
							$methodInfo['cost'] = $opt !== '' ? $opt : null; 
						} else if (property_exists($method, 'cost')) {
							$methodInfo['cost'] = $method->cost;
						} 
					} 
					$zoneData['methods'][] = $methodInfo;
				}
				$data[] = $zoneData; 
			} 
			$summary .= 'Shipping Terms: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
			
			$args = array(
				'post_type' => array('product'),
				'post_status' => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page' => 10,
				'meta_key' => 'total_sales',
				'orderby' => 'meta_value_num',
				'order' => 'DESC',
			);
			$products = array();
			$topProducts = new WP_Query($args);
			if ($topProducts->have_posts()) {
				foreach ($topProducts->posts as $post) {
					$products[] = array(
						'id' => $post->ID,
						'title' => $post->post_title,
						'short_description' => $post->post_excerpt,
					);
				}
			}
			$summary .= 'Top products: ' . json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
				
			global $wpdb;
			$query = 'SELECT t.term_id, t.name, tt.count' .
				' FROM ' . $wpdb->terms . ' as t' .
				' INNER JOIN ' . $wpdb->term_taxonomy . ' as tt ON t.term_id=tt.term_id' .
				" WHERE tt.taxonomy='product_cat'" .
				' ORDER BY tt.count DESC' .
				' LIMIT 10';
			$topCategories = $wpdb->get_results($query);
			$categories = array();
			foreach ($topCategories as $cat) {
				$categories[] = array(
					'id' => $cat->term_id,
					'name' => $cat->name,
					'count_products' => $cat->count,
				); 
			}
			$summary .= 'Top categories: ' . json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

		}
		
		$locations = get_nav_menu_locations();
		$location = isset($locations['footer']) ? 'footer' : '';
		if (empty($location)) {
			foreach (array_keys($locations) as $key) {
				if (strpos($key, 'footer') !== false) {
					$location = $key;
					break;
				}
			}
		}
		$menuItems = array();
		if ($location && isset($locations[$location])) {
			$menuId = $locations[$location];
			$items = wp_get_nav_menu_items($menuId);
        
			if ($items) {
				foreach ($items as $item) {
					$menuItems[] = array(
						'id' => $item->ID,
						'title' => $item->title,
						'url' => $item->url,
						'description' => $item->description,
						'parent' => $item->menu_item_parent,
					);
				}
			}
		}
		$summary .= 'Footer menu: ' . json_encode($menuItems, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
		$limit = 3000;
		$homePageText = '';
		if (get_option('show_on_front') === 'page') {
			$homepageId = get_option('page_on_front');
			if ($homepageId) {
				$homePageText = $this->convertContenToText($post->post_content);
			}
		} else {
			$recentPosts = get_posts(array(
				'numberposts' => 10,
				'post_status' => 'publish',
				'orderby' => 'date',
				'order' => 'DESC', 
				'post_type' => 'post',
			));

			foreach ($recentPosts as $post) {
				$homePageText .= $this->convertContenToText($post->post_content) . " ";
				if (WaicUtils::mbstrlen($homePageText) >= $limit) {
					break;
				}
			}
		}
		$summary .= 'Homepage content: ' . WaicUtils::mbsubstr($homePageText, 0, $limit);

		return $summary;
	}
	public function convertContenToText( $raw ) {
		//$content = strip_shortcodes($raw);
		$content = apply_filters('the_content', $raw);
		$content = str_replace(']]>', ']]&gt;', $content);
		
		/*$content = do_shortcode($content);
		$textParts = array(); 
		if (function_exists('parse_blocks')) {
			$blocks = parse_blocks($content);
			$textParts = $this->extractTextFromBlocks($blocks);
		} else {
			$textParts = array($content);
		}
		$combined = implode("\n\n", $textParts);
		$combined = preg_replace('#<script.*?>.*?</script>#is', '', $combined);
		$combined = preg_replace('#<style.*?>.*?</style>#is', '', $combined);
		$combined = preg_replace('#<pre.*?>.*?</pre>#is', '', $combined);
		$combined = preg_replace('#<code.*?>.*?</code>#is', '', $combined);
		$text = wp_strip_all_tags($combined);*/
		
		$text = wp_strip_all_tags($content);
		
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', trim($text));
    
		//$content = wp_strip_all_tags($content);
		//$content = preg_replace('/\s+/', ' ', $content);
		return trim($text);
	}

	public function extractTextFromBlocks( $blocks ) {
		$out = array();

		$skipBlocks = array(
			'core/code',
			'core/preformatted',
			'core/html',
			'core/embed',
			'core/video',
			'core/audio',
			'core/file',
		);
		$textBlocks = array(
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/quote',
			'core/pullquote',
			'core/table',
			'core/group',
			'core/columns',
			'core/column',
			'core/verse',
		);

		foreach ($blocks as $block) {
			if (empty($block) || !is_array($block)) {
				continue;
			}
			$blockName = isset($block['blockName']) ? $block['blockName'] : '';
			if ($blockName && in_array($blockName, $skipBlocks, true)) {
				continue;
			}
			if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				$inner = $this->extractTextFromBlocks($block['innerBlocks']);
				if (!empty($inner)) {
					$out = array_merge($out, $inner);
				}
			}
			$renderedHtml = '';
			if ($blockName) {
				try {
					$renderedHtml = render_block($block);
				} catch (Exception $e) {
					$renderedHtml = '';
				}
			}
			if (!empty($renderedHtml)) {
				$renderedHtml = preg_replace('#<script.*?>.*?</script>#is', '', $renderedHtml);
				$renderedHtml = preg_replace('#<style.*?>.*?</style>#is', '', $renderedHtml);
				$renderedHtml = preg_replace('#<pre.*?>.*?</pre>#is', '', $renderedHtml);
				$renderedHtml = preg_replace('#<code.*?>.*?</code>#is', '', $renderedHtml);

				$out[] = $renderedHtml;
				continue;
			}

			if (isset($block['innerHTML']) && $block['innerHTML'] !== '') {
				if ($blockName === '' || in_array($blockName, $text_blocks, true)) {
					$out[] = $block['innerHTML'];
					continue;
				}
				$out[] = $block['innerHTML'];
				continue;
			}
			if (isset( $block['attrs']) && is_array($block['attrs'])) {
				foreach (array('content', 'text', 'heading', 'description') as $k) {
					if (!empty($block['attrs'][$k]) && is_string($block['attrs'][$k])) {
						$out[] = $block['attrs'][$k];
					}
				}
			}
			if (isset($block['innerContent']) && is_array($block['innerContent'])) {
				$out = array_merge($out, $block['innerContent']);
			}
		}
		return $out;
	}

	public function maskApiKey( $key, $startLen = 7, $endLen = 4, $maskChar = ' . . . ' ) {
		$total = strlen($key);
		if ($total == 0) {
			return ''; 
		}
		if ($total <= $startLen + $endLen) {
			return substr($key, 0, $total - 2) . $maskChar;
		} 
		
		return substr($key, 0, $startLen) . $maskChar . substr($key, $total - $endLen); 
	}
}
