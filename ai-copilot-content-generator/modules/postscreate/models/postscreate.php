<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostscreateModel extends WaicModel {
	private $_fields = null;
	private $_fieldsMode = array();
	private $aiProvider = null;
	public $publishFlagId = 3;
	public $publishFlagTimeout = 20; //min
	private $_varOptions = null;
	private $_feature = 'postscreate';
	
	public function __construct() {
		$this->_setTbl('posts_create');
	}
	public function isDeleteByCancel() {
		return true;
	}
	public function canUnpublish() {
		return true;
	}
	public function setOpenAI( $aiProvider ) {
		$this->aiProvider = $aiProvider;
	}
	public function setAiProvider( $aiProvider ) {
		$this->aiProvider = $aiProvider;
	}
	public function getPromptVarOptions() {
		if (is_null($this->_varOptions)) {
			// 0 - from params, 1 - from results, 2 - custom from results, 3 - custom from params
			$this->_varOptions = array(
				'topic' => 0,
				'title' => 1,
				'number_of_sections' => 0,
				'sections' => 1,
				'section#' => 2,
				'body' => 1,
				'keywords' => 0,
				'language' => 0,
				'tone_of_voice' => 0,
				'additional_prompt' => 0,
				'additional_prompt_for_body' => 0,
				'additional_prompt_for_@' => 0,
				'number_of_@' => 0,
				'length_for_@' => 0,
				'field_type_for_@' => 0,
				'image_preset' => 0,
				'image_preset_description' => 0,
				'image_orientation' => 0,
				'use_common_language' => 0,
				'use_human_like_style' => 0,
				'original_article_title' => 0,
				'original_article_body' => 0,
				'keyword#' => 0,
			);
			$this->_varOptions = WaicDispatcher::applyFilters('getPromptVarOptions', $this->_varOptions);
		}
		return $this->_varOptions;
	}
	public function getStatuses( $st = null ) {
		$statuses = array(
			0 => __('New', 'ai-copilot-content-generator'),
			1 => __('Waiting', 'ai-copilot-content-generator'),
			2 => __('Processing', 'ai-copilot-content-generator'),
			3 => __('Generated', 'ai-copilot-content-generator'),
			4 => __('Published', 'ai-copilot-content-generator'),
			7 => __('Error', 'ai-copilot-content-generator'),
			8 => __('Pause', 'ai-copilot-content-generator'),
			9 => __('Canceled', 'ai-copilot-content-generator'),
		);
		return is_null($st) ? $statuses : ( isset($statuses[$st]) ? $statuses[$st] : '' );
	}
	public function getPublishMode( $mode ) {
		$modes = array(
			0 => __('No publish', 'ai-copilot-content-generator'),
			1 => __('Instantly', 'ai-copilot-content-generator'),
			2 => __('Datetime', 'ai-copilot-content-generator'),
		);
		return is_null($mode) ? $modes : ( isset($modes[$mode]) ? $modes[$mode] : '' );
	}
	public function canPostUpdate( $st ) {
		return in_array($st, array(1, 2, 3, 7, 8));
	}
	public function canPostStop( $st ) {
		return 2 == $st;
	}
	public function canPostStart( $st ) {
		return in_array($st, array(1, 3, 7, 8));
	}
	public function canPostPublish( $st ) {
		return 3 == $st;
	}
	public function canPostCancel( $st ) {
		return 2 != $st;
	}
	public function canPostSave( $st ) {
		return !in_array($st, array(2, 4, 9));
	}
	public function isPostRunning( $st ) {
		return 2 == $st;
	}
	
	public function getFields( $feature = 'postscreate' ) {
		if (isset($this->_fieldsMode[$feature])) {
			return $this->_fieldsMode[$feature];
		}

		if (is_null($this->_fields)) {
			$this->_fields = array(
				'title' => array(
					'label' => __('Title', 'ai-copilot-content-generator'),
					'required' => true,
					'multi' => false,
					'modes' => array(
						'gen_by_topic' => __('Generate based on Topic', 'ai-copilot-content-generator'),
						'gen_by_sections' => __('Generate based on section headers', 'ai-copilot-content-generator'),
						'use_topic' => __('Use topic as title', 'ai-copilot-content-generator'),
					),
				),
				'body' => array(
					'label' => __('Body', 'ai-copilot-content-generator'),
					'required' => true,
					'multi' => false,
					'modes' => array(
						'single' => __('Single-prompt article', 'ai-copilot-content-generator'),
						'sections' => __('Sectioned article generation', 'ai-copilot-content-generator'),
						'custom' => __('Custom sectioned article generation', 'ai-copilot-content-generator'),
					),
					'custom_sections' => array(
						__('Introduction', 'ai-copilot-content-generator'),
						__('What is', 'ai-copilot-content-generator') . ' {topic}',
						__('Conclusion', 'ai-copilot-content-generator'),
					),
				),
				'excerpt' => array(
					'label' => __('Excerpt', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
				),
				'categories' => array(
					'label' => __('Categories', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
					'taxonomy' => 'category',
					'modes' => array(
						'fixed' => __('Fixed value', 'ai-copilot-content-generator'),
						'generate' => __('Generate based on article', 'ai-copilot-content-generator'),
					),
				),
				'tags' => array(
					'label' => __('Tags', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
					'taxonomy' => 'post_tag',
					'modes' => array(
						'fixed' => __('Fixed value', 'ai-copilot-content-generator'),
						'generate' => __('Generate based on article', 'ai-copilot-content-generator'),
					),
				),
				/*'meta' => array(
					'label' => __('Meta-description', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
				),*/
				'author' => array(
					'label' => __('Author', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
				),
				'image' => array(
					'label' => __('Image', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => false,
					'modes' => array(
						'generate' => __('Generate based on title', 'ai-copilot-content-generator'),
						//'fixed' => __('Search in stock', 'ai-copilot-content-generator'),
					),
					'presetes' => array(
						'Realistic' => __('Realistic', 'ai-copilot-content-generator'), 
						'4k' => __('4k', 'ai-copilot-content-generator'),
						'High resolution' => __('High resolution', 'ai-copilot-content-generator'),
						'Trending in artstation' => __('Trending in artstation', 'ai-copilot-content-generator'),
						'Artstation three' => __('Artstation three', 'ai-copilot-content-generator'),
						'3D Render' => __('3D Render', 'ai-copilot-content-generator'),
						'Digital painting' => __('Digital painting', 'ai-copilot-content-generator'),
						'Amazing art' => __('Amazing art', 'ai-copilot-content-generator'),
						'Expert' => __('Expert', 'ai-copilot-content-generator'),
						'Stunning' => __('Stunning', 'ai-copilot-content-generator'),
						'Creative' => __('Creative', 'ai-copilot-content-generator'),
						'Popular' => __('Popular', 'ai-copilot-content-generator'),
						'Inspired' => __('Inspired', 'ai-copilot-content-generator'),
						'Surreal' => __('Surreal', 'ai-copilot-content-generator'),
						'Abstract' => __('Abstract', 'ai-copilot-content-generator'),
						'Fantasy' => __('Fantasy', 'ai-copilot-content-generator'),
						'Pop art' => __('Pop art', 'ai-copilot-content-generator'),
						'Vector' => __('Vector', 'ai-copilot-content-generator'),
						'Landscape' => __('Landscape', 'ai-copilot-content-generator'),
						'Portrait' => __('Portrait', 'ai-copilot-content-generator'),
						'Iconic' => __('Iconic', 'ai-copilot-content-generator'),
						'Neo expressionism' => __('Neo expressionism', 'ai-copilot-content-generator'),
						'Landscape painting' => __('Landscape painting', 'ai-copilot-content-generator'),
						'Digital Art' => __('Digital Art', 'ai-copilot-content-generator'),
						'Abstract Art' => __('Abstract Art', 'ai-copilot-content-generator'),
						'Surrealistic Art' => __('Surrealistic Art', 'ai-copilot-content-generator'),
						'Portrait Painting' => __('Portrait Painting', 'ai-copilot-content-generator'),
						'Neon' => __('Neon', 'ai-copilot-content-generator'),
						'Neon light' => __('Neon light', 'ai-copilot-content-generator'),
					),
					'orientation' => array(
						'horizontal' => __('Horizontal', 'ai-copilot-content-generator'),
						'vertical' => __('Vertical', 'ai-copilot-content-generator'),
						'square' => __('Square', 'ai-copilot-content-generator'),
					),
					'gemini_orientation' => array(
						'1:1' => __('1:1', 'ai-copilot-content-generator'),
						'3:4' => __('3:4', 'ai-copilot-content-generator'),
						'4:3' => __('4:3', 'ai-copilot-content-generator'),
						'9:16' => __('9:16', 'ai-copilot-content-generator'),
						'16:9' => __('16:9', 'ai-copilot-content-generator'),
					),
				),
				'sections' => array(
					'label' => __('Sections', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => true,
					'results' => true,
				),
				'date' => array(
					'label' => __('Date of publication', 'ai-copilot-content-generator'),
					'required' => true,
					'multi' => false,
					'modes' => array(
						'no' => __('Do not publish', 'ai-copilot-content-generator'),
						'now' => __('Publish instantly', 'ai-copilot-content-generator'),
						'date' => __('Delayed publication', 'ai-copilot-content-generator'),
						'random' => __('Publish randomly', 'ai-copilot-content-generator'),
						'period' => __('Publish 1 post every', 'ai-copilot-content-generator'), /* pro */
					),
					'units' => array(
						'h' => __('hours', 'ai-copilot-content-generator'),
						'm' => __('minutes', 'ai-copilot-content-generator'),
					),
				),
			);
			$custom = WaicFrame::_()->getModule('workspace')->getCustomTaxonomiesList('post');
			if (!empty($custom)) {
				$this->_fields['custom'] = array(
					'label' => __('Custom taxonomies', 'ai-copilot-content-generator'),
					'required' => false,
					'multi' => true,
					'modes' => array(
						'fixed' => __('Fixed value', 'ai-copilot-content-generator'),
						'generate' => __('Generate based on article', 'ai-copilot-content-generator'),
					),
					'taxonomies' => $custom,
				);
			}
			$d = $this->_fields['date'];
			unset($this->_fields['date']);
			$this->_fields['date'] = $d;

		}
		$this->_fieldsMode[$feature] = WaicDispatcher::applyFilters('addPostFields_' . $feature, $this->_fields);
		
		return $this->_fieldsMode[$feature];
	}
	public function controlTaskParameters( $params, &$error = '' ) {
		$apiParams = WaicUtils::getArrayValue($params, 'api', array(), 2);

		if (false === WaicUtils::checkEmptyApiKeys($apiParams, $error)) {
			return false;
		}

		$common = WaicUtils::getArrayValue($params, 'common', array(), 2);
		$e = __('Not all required fields are specified', 'ai-copilot-content-generator');
		if (empty($common)) {
			$error = $e;
			return false;
		}
		$mode = WaicUtils::getArrayValue($common, 'mode', '', 0, array('single', 'bulk'));
		if (empty($mode)) {
			$error = $e . ': ' . __('Mode', 'ai-copilot-content-generator');
			return false;
		}
		if ('single' == $mode) {
			$single = WaicUtils::getArrayValue($params, 'single', array(), 2);
			if (empty($single)) {
				$error = $e . '.';
				return false;
			}
			$topic = WaicUtils::getArrayValue($single, 'topic');
			if (empty($topic)) {
				$error = $e . ': ' . __('Topic', 'ai-copilot-content-generator');
				return false;
			}
		}
		if (isset($params['fields']) && isset($params['fields']['custom']) && is_array($params['fields']['custom'])) {
			foreach ($params['fields']['custom'] as $field => $data) {
				if (WaicUtils::getArrayValue($data, 'mode') == 'generate' && empty($data['prompt'])) {
					$error = $e . ': ' . __('Description for custom field', 'ai-copilot-content-generator') . ' ' . $field;
					return false;
				}
			}
		}
		$error = WaicDispatcher::applyFilters('controlTaskParameters', '', $params);
		return empty($error);
	}
	
	public function convertTaskParameters( $params, $toDB = true ) {
		if (isset($params['fields'])) {
			$dtFields = array('date' => array('date', 'from', 'to', 'period'));
			foreach ($dtFields as $field => $data) {
				if (isset($params['fields'][$field])) {
					foreach ($data as $d) {
						if (!empty($params['fields'][$field][$d])) {
							$params['fields'][$field][$d] = $toDB ? WaicUtils::convertDateTimeToDB($params['fields'][$field][$d]) : WaicUtils::convertDateTimeToFront($params['fields'][$field][$d]);
						}
					}
				}
			}
		}
		
		return WaicDispatcher::applyFilters('convertTaskParameters', $params, $toDB);
	}

	public function clearEtaps( $taskId, $ids = false, $withContent = true ) {
		$taskId = (int) $taskId;
		if ($withContent) {
			$this->unpublishEtaps($taskId, $ids);
			$this->deleteImages($taskId, $ids);
		}
		if (empty($ids)) {
			$this->delete(array('task_id' => $taskId));
		} else {
			$this->removeGroup($ids);
		}
		return true;
	}
	public function unpublishEtaps( $taskId, $ids = false ) {
		$taskId = (int) $taskId;

		$where = 'task_id=' . $taskId . ' AND post_id>0';
		if (!empty($ids)) {
			$where .= ' AND id IN (' . implode(',', WaicUtils::controlNumericValues($ids)) . ')';
		}
		$posts = $this->setSelectFields('id, post_id, params')->setWhere($where)->getFromTbl();
		if ($posts && is_array($posts)) {
			foreach ($posts as $post) {
				//wp_update_post(array('ID' => (int) $post['post_id'], 'post_status' => 'draft'));
				if (!empty($post['params']['post_id'])) {
					continue;
				}
				wp_delete_post($post['post_id'], true);
				
				/*$image = WaicUtils::getArrayValue($post['post_id'], 'image', array(), 2);
				if (!empty($image)) {
					$imgId = WaicUtils::getArrayValue($image, 'r', 0, 1);
					if (!empty($imgId)) {
						wp_delete_attachment($imgId, true);
					}
				}*/
				$this->updateById(array('post_id' => 0, 'status' => 3, 'pub_mode' => 0), $post['id']);
			}
		}
		return true;
	}
	public function deleteImages( $taskId, $ids = false ) {
		$taskId = (int) $taskId;
		$where = 'task_id=' . $taskId;
		if (!empty($ids)) {
			$where .= ' AND id IN (' . implode(',', WaicUtils::controlNumericValues($ids)) . ')';
		}
		
		$posts = $this->setSelectFields('id, status, results')->setWhere($where)->getFromTbl();
		if ($posts && is_array($posts)) {
			foreach ($posts as $post) {
				if (4 == $post['status']) {
					continue;
				}
				$results = $post['results'];
				if (isset($results['image']) && !empty($results['image']['r'])) {
					$imgId = (int) $results['image']['r'];
					if (!empty($imgId)) {
						wp_delete_attachment($imgId, true);
					}
				}
			}
		}
	}
	public function prepareGeneration( $task, $clear = true, $params = false ) {
		if (false == $params) {
			$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		}
		if (empty($params)) {
			WaicFrame::_()->pushError(esc_html__('Tasks parameters empty', 'ai-copilot-content-generator'));
			return false;
		}
		$taskId = $task['id'];
		$common = WaicUtils::getArrayValue($params, 'common', array(), 2);
		if (empty($common['mode'])) {
			WaicFrame::_()->pushError(esc_html__('Tasks parameters damaged', 'ai-copilot-content-generator'));
			return false;
		}
		if ($clear) {
			$this->clearEtaps($taskId);
		}
		$feature = $task['feature'];
		$optsModel = WaicFrame::_()->getModule('options')->getModel();
		$api = WaicUtils::getArrayValue($params, 'api');
		$language = $optsModel->getVariations('api', 'language', WaicUtils::getArrayValue($api, 'language'));
		$tone = WaicUtils::getArrayValue($api, 'tone');
		$commonLang = WaicUtils::getArrayValue($api, 'common_language', 0, 1) == 1 ? 'Use Only Common Language' : '';
		$humanStyle = WaicUtils::getArrayValue($api, 'human_style', 0, 1) == 1 ? 'Use Only Human-Like Language Style' : '';
		$ePrompt = WaicUtils::getArrayValue($common, 'e_prompt', 0, 1);
		$addPrompt = WaicUtils::getArrayValue($common, 'prompt');
		$fields = WaicUtils::getArrayValue($params, 'fields', array(), 2);
		$isSingle = ( 'single' == $common['mode'] );
		$steps = 0;
		$cnt = 0;
		$needTopic = ( WaicUtils::getArrayValue($params, 'without_topic', 0, 1) == 0 );
		
		if ($isSingle) {
			$single = WaicUtils::getArrayValue($params, 'single', array(), 2);
			$topics = array(array(WaicUtils::getArrayValue($single, 'topic'), WaicUtils::getArrayValue($single, 'keywords')));
		} else {
			$bulk = WaicUtils::getArrayValue($params, 'bulk', array(), 2);
			$topics = WaicUtils::getArrayValue($bulk, 'topics', array(), 2);
		}
		$postNum = 0;
		foreach ($topics as $tk) {
			$topic = isset($tk[0]) ? trim($tk[0]) : '';
			if (empty($topic) && $needTopic) {
				return false;
			}
			$keywords = isset($tk[1]) ? trim($tk[1]) : '';

			$vars = array(
				'feature' => $feature,
				'topic' => $topic,
				'keywords' => $keywords,
				'language' => $language,
				'tone_of_voice' => $tone,
				'use_common_language' => $commonLang,
				'use_human_like_style' => $humanStyle,
				'steps' => 0,
				'pub_mode' => 0,
				'publish' => null,
				'pause' => '',
				'order' => array(),
			);
			if (!empty($keywords)) {
				$keywords = explode(',', $keywords);
				foreach ($keywords as $i => $keyword) {
					$vars['keyword' . ( $i + 1 )] = trim($keyword);
				}
			}
			if ($ePrompt) {
				$vars['additional_prompt'] = $addPrompt;
			}
			if ($ePrompt) {
				$vars['additional_prompt'] = $addPrompt;
			}
			if (!empty($tk[2]) && is_array($tk[2])) {
				$vars = array_merge($vars, $tk[2]);
			}

			//$fields = WaicUtils::getArrayValue($params, 'fields', array(), 2);
			$results = $this->prepareResults( $fields, $vars, $postNum );
			$postNum++;
			//$results = WaicDispatcher::applyFilters('addPrepareResults_' . $feature, $results, $params);
			if (!empty($tk[2]) && is_array($tk[2])) {
				$vars = array_merge($vars, $tk[2]);
			}
			
			if (!empty($vars['image_preset'])) {
				$vars['image_preset_description'] = $optsModel->getImagePresetDescriptions($vars['image_preset']);
			}
			//$vars = WaicDispatcher::applyFilters('addPrepareVars_' . $feature, $vars, $params);
			/*if (!empty($tk[2]) && is_array($tk[2])) {
				$vars = array_merge($vars, $tk[2]);
			}*/
			$uniq = isset($vars['uniq']) ? $vars['uniq'] : false;
			unset($vars['uniq']);

			$post = array(
				'task_id' => $taskId,
				'steps' => $vars['steps'],
				'pub_mode' => $vars['pub_mode'],
				'publish' => $vars['publish'],
				'results' => WaicUtils::jsonEncode($results),
				'params' => WaicUtils::jsonEncode($vars, true),
			);
			if ($uniq) {
				$post['uniq'] = $uniq;
			}
			$id = $this->insert($post);
			if (!empty($results)) {
				$cnt++;
				//$params['cnt'] = 1;
				//$params['steps'] = $vars['steps'];
				$steps += $vars['steps'];
			}
		}
		
		return array('cnt' => $cnt, 'steps' => $steps);
	}
	
	protected function _afterGetFromTbl( $row ) {
		if (!empty($row['results'])) {
			if ('{' != $row['results'][0]) {
				$row['results'] = base64_decode($row['results']);
			}
			$row['results'] = WaicUtils::jsonDecode($row['results']);
		}
		if (!empty($row['params'])) {
			$row['params'] = WaicUtils::jsonDecode($row['params']);
		}
		return $row;
	}
	public function getTaskResults( $taskId, $id = 0, $limit = 0 ) {
		$where = array();
		if (!empty($id)) {
			$where['id'] = (int) $id;
		}
		if (!empty($taskId)) {
			$where['task_id'] = (int) $taskId;
		}
		if (empty($where)) {
			return array();
		}
		$this->setSelectFields('id, status, post_id, pub_mode, results, params')->setWhere($where);//->getFromTbl();//->setLimit(1)->getFromTbl(array('return' => 'one'));
		if (!empty($limit)) {
			$this->setLimit($limit);
		}
		$results = $this->getFromTbl(array('return' => empty($id) ? 'all' : 'row'));
		return $results ? $results : array();
	}
	public function getPostParams( $id, $taskId = 0 ) {
		$id = (int) $id;
		$where = array('id' => $id);
		
		if (!empty($taskId)) {
			$where['task_id'] = (int) $taskId;
		}
		$params = $this->setSelectFields('params')->setWhere($where)->setLimit(1)->getFromTbl(array('return' => 'one'));
		return $params ? WaicUtils::jsonDecode($params) : array();
	}
	public function getPostResults( $id, $taskId = 0 ) {
		$id = (int) $id;
		$where = array('id' => $id);
		
		if (!empty($taskId)) {
			$where['task_id'] = (int) $taskId;
		}
		$results = $this->setSelectFields('results')->setWhere($where)->setLimit(1)->getFromTbl(array('return' => 'one'));
		if ($results) {
			if ('{' != $results) {
				$results = base64_decode($results);
			}
		}
		return $results ? WaicUtils::jsonDecode($results) : array();
	}
	
	public function getCustomPhrase( $str, $vars ) {
		foreach ($vars as $key => $value) {
			if (is_string($value)) {
				$str = str_replace('{' . $key . '}', $value, $str);
			}
		}
		return $str;
	}
	
	public function prepareResults( $params, &$vars, $postNum = 0 ) {
		//g - need generate, s - status (1-ready,2-error), e - cnt steps, r - result
		$feature = isset($vars['feature']) ? $vars['feature'] : $this->_feature;
		$fields = $this->getFields($feature);
		$results = array();
		$steps = 0;
		$single = WaicUtils::getArrayValue($params, 'single', array(), 2);
		$order = array();
		$after = array();
		foreach ($fields as $key => $data) {
			$field = WaicUtils::getArrayValue($params, $key, array(), 2);
			if (!empty($field)) {
				$prompt = WaicUtils::getArrayValue($field, 'prompt');
				if (!empty($prompt) && 'custom' != $key) {
					$vars['additional_prompt_for_' . $key] = $prompt;
				}
				$vars['length_for_' . $key] = WaicUtils::getArrayValue($field, 'length', 0, 1);
				/*if ('custom' != $key) {
					$order[] = $key;
				}*/
				
				switch ($key) {
					case 'title':
						$m = WaicUtils::getArrayValue($field, 'mode');
						$needGen = 'gen_by_topic' == $m || 'gen_by_sections' == $m || 'gen_by_body' == $m ? 1 : 0;
						$results['title'] = array(
							'g' => $needGen,
							's' => $needGen ? 0 : 1,
							'e' => $needGen ? 1 : 0,
							'r' => $needGen ? '' : $vars['topic'],
						);
						if ($needGen) {
							if ('gen_by_sections' == $m) {
								$after['title'] = 'sections';
								$vars['prompt_title'] = 'title_sections';
							} else {
								$vars['prompt_title'] = 'title_topic';
							}
							$steps++;
						}
						break;
					case 'body':
						$mode = WaicUtils::getArrayValue($field, 'mode');
						$vars['mode'] = $mode;
						$sections = array();
						$body = array();
						
						if ('sections' == $mode) {
							$cnt = (int) WaicUtils::getArrayValue($field, 'count', 0, 1);
							if (!empty($cnt)) {
								$sections = array(
									'g' => 1,
									's' => 0,
									'e' => 1,
									'r' => array_fill(1, $cnt, array('s' => 0, 'r' => '')),
								);
								$steps++;
								
								$body = array(
									'g' => 1,
									's' => 0,
									'e' => $cnt,
									'r' => array_fill(1, $cnt, array('s' => 0, 'r' => '')),
								);
								$steps += $cnt;
								if (WaicUtils::getArrayValue($field, 'pause', 0, 1)) {
									$vars['pause'] = 'sections';
								}
							}
						} else if ('custom' == $mode) {
							$custom = WaicUtils::getArrayValue($field, 'sections', array(), 2);
							if (!empty($custom)) {
								$r = array();
								$i = 1;
								foreach ($custom as $name) {
									$r[$i] = array('s' => 1, 'r' => $this->getCustomPhrase($name, $vars));
									$i++;
								}
								$sections = array(
									'g' => 0,
									's' => 1,
									'r' => $r,
								);
								$cnt = count($custom);
								$body = array(
									'g' => 1,
									's' => 0,
									'e' => $cnt,
									'r' => array_fill(1, $cnt, array('s' => 0, 'r' => '')),
								);
								$steps += $cnt;
							}
						}
						if (!empty($sections)) {
							$results['sections'] = $sections;
							$order[] = 'sections';
						}
						
						if (empty($body)) {
							$vars['mode'] = 'single';
							$body = array(
								'g' => 1,
								's' => 0,
								'e' => 1,
								'r' => array(array('s' => 0, 'r' => '')),
								//'r' => '',
							);
							$vars['prompt_body'] = 'body';
							$steps++;
						} else {
							$vars['prompt_body'] = 'body_section';
						}
						$vars['number_of_sections'] = $body['e'];

						$results['body'] = $body;
						break;
					case 'custom':
						foreach ($field as $slug => $f) {
							$keyC = $key . '-' . $slug;
							$needGen = WaicUtils::getArrayValue($f, 'mode') == 'generate' ? 1 : 0;
							if ($needGen) {
								//$vars['number_of_' . $keyC] = WaicUtils::getArrayValue($f, 'count', 1, 1);
								$vars['field_type_for_' . $keyC] = WaicUtils::getArrayValue($f, 'type', 'one-line text.');
								$vars['length_for_' . $keyC] = WaicUtils::getArrayValue($f, 'length', 1, 1);
								$gen = 1;
								$steps++;
							}
							$order[] = $keyC;
							$results[$keyC] = array(
								'g' => $needGen,
								's' => $needGen ? 0 : 1,
								'e' => 1,
								'r' => $needGen ? '' : WaicUtils::getArrayValue($f, 'list'),
							);
							$vars['additional_prompt_for_' . $keyC] = WaicUtils::getArrayValue($f, 'prompt');
							$vars['prompt_' . $keyC] = 'custom';
						} 
						break;
					case 'categories': 
					case 'tags': 
						$needGen = WaicUtils::getArrayValue($field, 'mode') == 'generate' ? 1 : 0;
						if ($needGen) {
							$vars['number_of_' . $key] = WaicUtils::getArrayValue($field, 'count', 1, 1);
							$steps++;
						}
						if (WaicUtils::getArrayValue($field, 'append', 0, 1)) {
							$vars[$key . '_append'] = 1;
						}
						$results[$key] = array(
							'g' => $needGen,
							's' => $needGen ? 0 : 1,
							'e' => $needGen ? 1 : 0,
							'r' => $needGen ? '' : WaicUtils::getArrayValue($field, 'list'),
						);
						break;
					case 'author': 
						$results[$key] = array('g' => 0, 's' => 1, 'r' => WaicUtils::getArrayValue($field, 'id', 0, 1));
						break;
					case 'date':
						$mode = WaicUtils::getArrayValue($field, 'mode');
						$dt = '';
						switch ($mode) {
							case 'no':
								$vars['pub_mode'] = 0;
								break;
							case 'now':
								$vars['pub_mode'] = 1;
								break;
							case 'date':
								$dt = WaicUtils::getArrayValue($field, 'date');
								break;
							case 'random':
								$from = WaicUtils::getArrayValue($field, 'from');
								$to = WaicUtils::getArrayValue($field, 'to');
								if (!empty($from) && !empty($to)) {
									$f = WaicUtils::getTimestampFrom($from);
									$t = WaicUtils::getTimestampFrom($to);
									if (!empty($f) && !empty($t)) {
										$dt = WaicUtils::getFormatedDateTimeDB(rand($f, $t));
									}
								}
								break;
							case 'period':
								$from = WaicUtils::getArrayValue($field, 'period');
								if (empty($postNum)) {
									$dt = $from;
								} else if (!empty($from)) {
									$f = WaicUtils::getTimestampFrom($from);
									$addDt = WaicUtils::getArrayValue($field, 'cnt', 0, 1) * ( WaicUtils::getArrayValue($field, 'unit', 'h') == 'h' ? 3600 : 60 );
									$dt = WaicUtils::getFormatedDateTimeDB($f + $postNum * $addDt);
								}
								break;
						}
						if (!empty($dt)) {
							$vars['pub_mode'] = 2;
							$vars['publish'] = $dt;
						}
						$results[$key] = array('g' => 0, 's' => 1, 'r' => $dt);
						//$vars['publish'] = 
						break;
					case 'image':
						$needGen = WaicUtils::getArrayValue($field, 'mode') == 'generate' ? 1 : 0;
						if ($needGen) {
							$vars['image_preset'] = WaicUtils::getArrayValue($field, 'preset');
							//$vars['image_orientation'] = 'Horizontal';
							$vars['image_orientation'] = WaicUtils::getArrayValue($field, 'orientation');
							$vars['gemini_image_orientation'] = WaicUtils::getArrayValue($field, 'gemini_orientation');
							$results[$key] = array(
								'g' => 1,
								's' => 0,
								'e' => 1,
								'r' => '',
							);
							$order[] = 'image';
							$steps++;
						}
						if ( WaicUtils::getArrayValue($field, 'alt') == 1) {
							$results['image_alt'] = array(
								'g' => 1,
								's' => 0,
								'e' => 1,
								'r' => '',
							);
							$order[] = 'image_alt';
							/*if ($needGen) {
								$after['image_alt'] = 'image';
							}*/
							$steps++;
						}
						break;
					case 'paragraph':
						$cnt = (int) WaicUtils::getArrayValue($vars, 'cnt_paragraphs', 0, 1);
						if (!empty($cnt)) {
							$results['paragraph'] = array(
								'g' => 1,
								's' => 0,
								'e' => $cnt,
								'r' => array_fill(1, $cnt, array('s' => 0, 'r' => '')),
							);
							$steps += $cnt;
						} 
						break;
					case 'place':
						$places = WaicUtils::getArrayValue($vars, 'places', array(), 2);
						$r = array();
						$i = 1;
						$cnt = 0;
						foreach ($places as $place) {
							$needGen = ( 'auto' == $place );
							$r[$i] = array('s' => ( $needGen ? 0 : 1 ), 'r' => '');
							$i++;
							if ($needGen) {
								$cnt++;
							}
						}
						$steps += $cnt;
						$gen = !empty($cnt);
						$results['place'] = array(
							'g' => ( $gen ? 1 : 0 ),
							's' => ( $gen ? 0 : 1 ),
							'e' => count($r),
							'r' => $r,
						);
						break;
					default: 
						$cntGen = 'cnt_' . $key;
						if (isset($vars[$cntGen])) {
							$cnt = (int) WaicUtils::getArrayValue($vars, $cntGen, 0, 1);
							if (!empty($cnt)) {
								$results[$key] = array(
									'g' => 1,
									's' => 0,
									'e' => $cnt,
									'r' => array_fill(1, $cnt, array('s' => 0, 'r' => '')),
								);
								$steps += $cnt;
							}
						} else {
							$results[$key] = array(
								'g' => 1,
								's' => 0,
								'e' => 1,
								'r' => '',
							);
							$steps++;
						}
				}
				if ('custom' != $key && 'date' != $key && 'image' != $key) {
					$order[] = $key;
				}
			}
		}

		if (!empty($after)) {
			foreach ($after as $a => $b) {
				$aKey = array_search($a, $order);
				$bKey = array_search($b, $order);
				if ($aKey < $bKey) {
					for ($i = $aKey; $i < $bKey; $i++) {
						$order[$i] = $order[ $i + 1 ];
					}
					$order[$bKey] = $a;
				}
			}
		}
		//set date to end
		//$order[] = 'date';
		
		$vars['order'] = $order;
		$vars['steps'] = $steps;
		return $results;
	}
	public function updateResults( $id, $params = array() ) {
		$id = (int) $id;
		$columns = $params;
		if (!empty($columns['params'])) {
			$columns['params'] = WaicUtils::jsonEncode($columns['params'], true);
		}
		if (!empty($columns['results'])) {
			$columns['results'] = base64_encode(WaicUtils::jsonEncode($columns['results']));
		}

		$columns['updated'] = WaicUtils::getTimestampDB();
		$this->updateById($columns, $id);
		
		return $id;
	}
	public function startEtap( $id ) {
		$id = (int) $id;
		$t = WaicUtils::getTimestampDB();
		$params = array(
			'updated' => $t,
			'start' => $t,
			'end' => null,
			'status' => 2,
			'flag' => 1,
		);
		$this->updateById($params, $id);
		
		return $id;
	}
	public function stopEtap( $id ) {
		$id = (int) $id;
		$t = WaicUtils::getTimestampDB();
		$params = array(
			'updated' => $t,
			'start' => $t,
			'status' => 3,
		);
		return $id;
	}
	public function resetEtaps( $taskId ) {
		$this->update(array('flag' => 0), array('task_id' => $taskId));
		return true;
	}
	public function getWaitingPublish() {
		$taskId = WaicDb::get('SELECT min(task_id) FROM `@__posts_create` WHERE pub_mode=0 AND status=3', 'one');
		return $taskId ? (int) $taskId : 0;
	}
	public function getGeneratedSteps( $taskId ) {
		$steps = WaicDb::get('SELECT sum(step) FROM `@__posts_create` WHERE task_id=' . $taskId, 'one');
		return $steps ? (int) $steps : 0;
	}
	public function getCountSteps( $taskId ) {
		$taskId = (int) $taskId;
		$steps = WaicDb::get('SELECT sum(steps) FROM `@__posts_create` WHERE task_id=' . $taskId, 'one');
		return $steps ? (int) $steps : 0;
	}
	public function getAllCountSteps( $taskId ) {
		$all = WaicDb::get('SELECT sum(step) as step, sum(steps) as steps, count(*) as cnt FROM `@__posts_create` WHERE task_id=' . $taskId, 'row');
		return $all ? $all : array('step' => 0, 'steps' => 0, 'cnt' => 0);
	}
	public function doGeneration( $task, $aiProvider ) {
		$taskId = $task['id'];
		WaicFrame::_()->saveDebugLogging('Start generation for PostCreate task ID=' . $taskId);
		$result = false;
		$this->aiProvider = $aiProvider;

		$this->resetEtaps($taskId);
		do {
			$etap = $this->getEtapForGeneration($taskId);
			if ($etap) {
				$taskSteps = $this->getGeneratedSteps($taskId);
				$result = $this->doGenerationEtap($etap, $taskSteps);

				if (false === $result) {
					$result = 7;
					break;
				}
				if (8 === $result || 7 === $result) {
					break;
				}
			} else {
				$result = ( 18 === $result ? 8 : 3 );
				break;
			}
		} while (true);

		if ($this->isAllPublished($taskId)) {
			$result = 4;
		}
		$params = array('status' => $result);
		WaicFrame::_()->saveDebugLogging('Stop generation for PostCreate task ID=' . $taskId . '. Set status=' . $result);
		//$task['step'] = 
		//$this->updateResults($taskId, $params);
		return $params;
	}

	public function getEtapForGeneration( $taskId ) {
		$etap = $this->setWhere(array('task_id' => $taskId, 'flag' => 0))->addWhere('status IN (0,1,2,7,8)')
			->setSortOrder('id')->setLimit(1)->getFromTbl(array('return' => 'row'));

		return $etap ? $etap : false;
	}
	
	public function doGenerationEtap( $etap, $taskStep ) {
		$id = $etap['id'];
		WaicFrame::_()->saveDebugLogging('Start generation for post ID=' . $id);
		$this->startEtap($id);
		$taskId = $etap['task_id'];
		$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$workspace = WaicFrame::_()->getModule('workspace')->getModel('workspace');
		$readySteps = 0;
		$params = $etap['params'];
		$results = $etap['results'];
		$order = WaicUtils::getArrayValue($params, 'order', array(), 2);
		foreach ($order as $field) {
			$data = WaicUtils::getArrayValue($results, $field, array(), 2);
			// need generation
			if (WaicUtils::getArrayValue($data, 'g', 0, 1)) {
				// is ready
				if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1) {
					$cnt = WaicUtils::getArrayValue($data, 'e', 1, 1);
					$readySteps += $cnt;
					//$taskStep += $cnt;
				} else {
					$opts = array();

					$promptField = WaicUtils::getArrayValue($params, 'prompt_' . $field, $field);
					$r = WaicUtils::getArrayValue($data, 'r');
					$error = false;
					//if (is_array($r) && WaicUtils::getArrayValue($data, 'e', 1, 1) > 1) {
					//if (is_array($r) && 'body' == $field) {
					if (is_array($r) && 'sections' != $field) {
						foreach ($r as $i => $d) {
							if (!WaicUtils::getArrayValue($d, 's', 0, 1)) {
								$opts['prompt'] = $this->getFieldPrompt($promptField, $field, $params, $results, $i);
								$d = $this->generateResults( $d, $opts, $field);
								if (2 == $d['s']) {
									$error = true;
								} else {
									$readySteps++;
									$taskStep++;
									$taskModel->updateTask($taskId, array('step' => $taskStep));
								}
								$results[$field]['r'][$i] = $d;
								$forUpdate = array('results' => $results, 'step' => $readySteps);
								$this->updateResults($id, array('results' => $results, 'step' => $readySteps));
								if ($workspace->controlStop($taskId)) {
									return 8;
								}
							}
						}
						$results[$field]['s'] = $error ? 2 : 1;
						$this->updateResults($id, array('results' => $results));
					} else {
						$opts['prompt'] = $this->getFieldPrompt($promptField, $field, $params, $results);
						if ('image' == $field) {
							$opts['size'] = WaicUtils::getArrayValue($params, 'image_orientation');
							$opts['gemini_size'] = WaicUtils::getArrayValue($params, 'gemini_image_orientation');
						}
						
						$data = $this->generateResults($data, $opts, $field);
						$results[$field] = $data;
						if (2 == $data['s']) {
							$error = true;
						} else {
							$readySteps++;
							$taskStep++;
							$this->updateResults($id, array('results' => $results, 'step' => $readySteps));
							$taskModel->updateTask($taskId, array('step' => $taskStep));
						}
					}
					if ($error) {
						$this->updateResults($id, array('results' => $results, 'step' => $readySteps, 'status' => 7));
						return 7;
					}
					if (WaicUtils::getArrayValue($params, 'pause') == $field) {
						$this->updateResults($id, array('results' => $results, 'step' => $readySteps, 'status' => 8));
						// need a fictitious status so that bulk generation does not stop after each post - later we change it to standard status 8
						return 18;
					}
					if ($workspace->controlStop($taskId)) {
						return 8;
					}
				}
			}
		}
		WaicFrame::_()->saveDebugLogging('Stop generation for post ID=' . $id);
		$this->stopEtap($id);

		$this->updateResults($id, array('results' => $results, 'step' => $readySteps, 'steps' => $readySteps, 'status' => 3, 'end' => WaicUtils::getTimestampDB()));
		if ( 1 == $etap['pub_mode'] || ( 2 == $etap['pub_mode'] && $etap['publish'] <= WaicUtils::getTimestampDB() ) ) { 
			$feature = WaicUtils::getArrayValue($params, 'feature', 'postscreate');
			if ('productsfields' == $feature) {
				$featureModule = WaicFrame::_()->getModule($feature);
				if ($featureModule) {
					$featureModule->getModel()->publishResults($taskId, array($id));
				}
			} else {
				$this->publishResults($taskId, array($id));
			}
			WaicFrame::_()->saveDebugLogging('Published post ID=' . $id);
		}
		
		return 3;
	}
	public function generateResults( $data, $opts, $field ) {
		WaicFrame::_()->saveDebugLogging('Field: ' . $field);

		$result = 'image' == $field ? $this->aiProvider->getImage($opts) : $this->aiProvider->getText($opts);
		if ($result['error']) {
			$data['s'] = 2;
			$data['m'] = $result['msg'];
		} else {
			unset($data['m']);
			$data['s'] = 1;
			switch ($field) {
				case 'sections':
					$list = preg_split('/\r\n|\r|\n/', $result['data']);
					$i = 1;
					foreach ($list as $r) {
						if (WaicUtils::mbstrpos($r, $i . '.') === 0) {
							$res = trim(WaicUtils::mbsubstr($r, WaicUtils::mbstrlen($i . '.')));
							if (!empty($res) && isset($data['r'][$i])) {
								$data['r'][$i]['r'] = $this->controlResultText($res, $field);
								$data['r'][$i]['s'] = 1;
							}
						}
						$i++;
					}
					break;
				case 'image':
					$path = $this->controlResultText($result['data'], $field);
					if ($path) {
						$attrId = $this->saveImage(htmlspecialchars_decode($path, ENT_QUOTES), 'WAIC AI generated Image');
						$data['r'] = $attrId ? $attrId : 0;
					}
					break;
				default:
					$data['r'] = $this->controlResultText($result['data'], $field);
					break;
			}
		}
		return $data;
	}
	public function controlResultText( $str, $field, $punkt = true ) {
		switch ($field) {
			case 'title':
				$str = str_replace(array('"', "'"), array('', '&#039;'), $str);
				break;
			case 'body':
				if ($punkt) {
					$lastPunkt = WaicUtils::mbstrrpos($str, '.');
					if ($lastPunkt) {
						$str = WaicUtils::mbsubstr($str, 0, $lastPunkt) . '.'; 
					}
				}
				break;
			default:
				if (strpos($field, 'custom-') === 0) {
					$str = str_replace(array('"', "'"), array('', ''), $str);
				}
				break;
		}
		$str = str_replace(array('```html', '```'), array('', ''), $str);
		/*$str = str_replace(array('\\', '/', '"', "\r", "\n", "\b", "\f", "\t"), 
				array('\\\\', '\/', '\"', '\r', '<br>', '\b', '\f', '\t'), $str);*/
		//$str = str_replace(array("\r", "\n", "\b", "\f", "\t"), array('', '<br>', '', '', ''), $str);
		
		//$str = str_replace(array("\r", "\n", "\b", "\f", "\t"), array('', '', '', '', ''), $str);
		
		//$str = str_replace(array('<br><br>', '</h3><br>'), array('<br>', '</h3>'), $str);
		//$str = str_replace('</h3><br>', '</h3>', $str);
		return htmlspecialchars($str, ENT_QUOTES); 
		//return base64_encode($str);
		//return $str; 
	}
	public function editResults( $taskId, $edited, $refresh ) {
		//$steps = 0; // only for refresh
		if (!is_array($edited)) {
			$edited = array();
		}
		$resArray = array();
		foreach ($edited as $id => $blocks) {
			$results = $this->getPostResults($id, $taskId);
			if (empty($results)) {
				continue;
			}
			$blocks = json_decode(stripslashes($blocks), true);
			$update = array();
			$editParams = false;
			
			foreach ($blocks as $block => $data) {
				switch ($block) {
					case 'sections':
						$r = array();
						foreach ($data as $i => $value) {
							$r[$i + 1] = array('s' => 1, 'r' => $this->controlResultText($value, $block));
						}
						$cnt = $i + 1;
						if ($results['body']['e'] > $cnt) {
							for ($n = $cnt + 1; $n <= $results['body']['e']; $n++) {
								unset($results['body']['r'][$n]);
							}
							$results['body']['e'] = $cnt;
						}
						$results[$block]['r'] = $r;
						break;
					case 'body':
					case 'descriptionvars':
						foreach ($data as $n => $value) {
							if (isset($results[$block]['r'][$n])) {
								$results[$block]['r'][$n]['s'] = 1;
								$results[$block]['r'][$n]['r'] = $this->controlResultText($value, $block, false);
							}
						}
						break;
					case 'reviews':
						$params = $this->getPostParams($id, $taskId);
						$reviews = WaicUtils::getArrayValue($params, 'reviews', array(), 2);
						foreach ($data as $n => $values) {
							if (isset($values['r']) && isset($results[$block]['r'][$n])) {
								$results[$block]['r'][$n]['s'] = 1;
								$results[$block]['r'][$n]['r'] = $this->controlResultText($values['r'], $block, false);
							}
							if (isset($values['user']) && isset($reviews[$n]['user'])) {
								$reviews[$n]['user'] = (int) $values['user'];
								$editParams = true;
							}
							if (isset($values['rate']) && isset($reviews[$n]['rate'])) {
								$rate = (int) $values['rate'];
								if ($rate > 5) {
									$rate = 5;
								} else if ($rate < 1) {
									$rate = 1;
								}
								$reviews[$n]['rate'] = $rate;
								$editParams = true;
							}
							if (isset($values['date']) && isset($reviews[$n]['date'])) {
								$reviews[$n]['date'] = WaicUtils::convertDateTimeToDB($values['date']);
								$editParams = true;
							}
						}
						if ($editParams) {
							$params['reviews'] = $reviews;
							$update['params'] = $params;
						}
						break;
					case 'date':
						$dt = empty($data) ? '' : WaicUtils::convertDateTimeToDB($data);
						if (empty($dt) || is_null($dt)) {
							$dt = '';
							$update['pub_mode'] = 1;
							$update['publish'] = null;
						} else {
							$update['pub_mode'] = 2;
							$update['publish'] = $dt;
						}
						$results[$block]['r'] = $dt;
						break;
					default:
						$results[$block]['r'] = is_array($data) ? $data : $this->controlResultText($data, $block);
						break;
				}
			}
			$resArray[$id] = $results;
			if (!$this->updateResults($id, array_merge($update, array('results' => $results)))) {
				return false;
			}
		}
		if (!is_array($refresh)) {
			$refresh = array();
		}

		$refreshed = false;
		foreach ($refresh as $id => $blocks) {
			$results = isset($resArray[$id]) ? $resArray[$id] : $this->getPostResults($id, $taskId);
			if (empty($results)) {
				continue;
			}
			$cnt = 0;
			foreach ($blocks as $block => $data) {
				if (isset($results[$block]) && !empty($results[$block]['g'])) {

					$s = empty($results[$block]['s']) ? 0 : $results[$block]['s'];
					$results[$block]['s'] = 0;
					if (is_array($data)) {
						foreach ($data as $b) {
							if (isset($results[$block]['r'][$b])) {
								$results[$block]['r'][$b] = array('s' => 0, 'r' => '');
								$cnt++;
							}
						}
						continue;
					}
					if (( 'body' == $block || 'paragraph' == $block ) && 'all' != $data) {
						$b = (int) $data;

						if (isset($results[$block]['r'][$b])) {
							/*if (!empty($results[$block]['r'][$b]['s']) && $results[$block]['r'][$b]['s'] == 1) {
								$steps++;
							}*/
							$results[$block]['r'][$b] = array('s' => 0, 'r' => '');
							$cnt++;
						}
						continue;
					}
					if ('image' == $block) {
						$imgId = (int) $results[$block]['r'];
						if (!empty($imgId)) {
							wp_delete_attachment($imgId, true);
						}
					}
					if (is_array($results[$block]['r'])) {
						foreach ($results[$block]['r'] as $b => $d) {
							/*if (!empty($results[$block]['r'][$b]['s']) && $results[$block]['r'][$b]['s'] == 1) {
								$steps++;
							}*/
							$results[$block]['r'][$b] = array('s' => 0, 'r' => '');
						}
						$cnt += $results[$block]['e'];
					} else {
						/*if ($s == 1) {
							$steps++;
						}*/
						$results[$block]['r'] = '';
						$cnt++;
					}
				}
			}
			$refreshed = true;
			if ($cnt > 0) {
				$etap = $this->getById($id);
				if ($etap) {
					$step = $etap['step'] - $cnt;
					if (0 > $step) {
						$step = 0;
					}
					if (!$this->updateResults($id, array('results' => $results, 'status' => 1, 'step' => $step))) {
						return false;
					}
				}
			}
			
		}
		return $refreshed ? 1 : true;
	}
	
	public function publishResults( $taskId, $publish ) {
		if (!is_array($publish)) {
			if ('all' == $publish) {
				$publish = array();
				$posts = $this->setSelectFields('id')->setWhere(array('task_id' => $taskId, 'status' => 3, 'post_id' => 0))->getFromTbl();
				if ($posts && is_array($posts)) {
					foreach ($posts as $post) {
						$publish[] = $post['id'];
					}
				}
			} else {
				$publish = array();
			}
		}

		foreach ($publish as $id) {
			//$results = $this->getPostResults($id, $taskId);
			$postData = $this->getTaskResults($taskId, $id);
			if (empty($postData) || !empty($postData['post_id'])) {
				continue;
			}
			if (!$this->canPostPublish($postData['status'])) {
				continue;
			}
			$results = $postData['results'];
			$params = $postData['params'];
			$feature = WaicUtils::getArrayValue($params, 'feature', 'postscreate');
			/*$post = array(
				'post_title' => 'New post',
				'post_status' => 'publish',
				'post_type' => 'post',
				'post_author' => 1,
			);*/
			//$post = array('post_type' => 'post');
			$post = array();
			$attId = 0;
			$imgAlt = '';
			$needUpdate = false;
			foreach ($results as $field => $data) {
				if (empty($data)) {
					continue;
				}
				switch ($field) {
					case 'title':
						$post['post_title'] = htmlspecialchars_decode(WaicUtils::getArrayValue($data, 'r', 'New post'), ENT_QUOTES);
						break;
					case 'author':
						$post['post_author'] = WaicUtils::getArrayValue($data, 'r', 1, 1);
						break;
					case 'body':
						$content = '';
						if (is_array($data['r'])) {
							foreach ($data['r'] as $r) {
								if (isset($r['s']) && 1 == $r['s'] && !empty($r['r'])) {
									$content .= $r['r'];
								}
							}
						}
						if (!empty($params['addtext_body'])) {
							$content .= $params['addtext_body'];
						}
						$post['post_content'] = htmlspecialchars_decode($content, ENT_QUOTES);
						break;
					case 'excerpt':
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1) {
							$post['post_excerpt'] = htmlspecialchars_decode(WaicUtils::getArrayValue($data, 'r'), ENT_QUOTES);
						}
						break;
					case 'categories':
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1) {
							$termsIds = $this->createTaxonomies(WaicUtils::getArrayValue($data, 'r'), 'category');
							if (!empty($termsIds)) {
								$post['post_category'] = $termsIds;
							}
						}
						break;
					case 'tags':
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1) {
							$termsIds = $this->createTaxonomies(WaicUtils::getArrayValue($data, 'r'), 'post_tag');
							if (!empty($termsIds)) {
								$post['tags_input'] = $termsIds;
							}
						}
						break;
					case 'image':
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1 && !empty($data['r'])) {
							//$attId = $this->saveImage(htmlspecialchars_decode($data['r'], ENT_QUOTES), $post['post_title']);
							$attId = WaicUtils::getArrayValue($data, 'r', 0, 1);
							$needUpdate = true;
						}
						break;
					case 'image_alt':
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1 && !empty($data['r'])) {
							$imgAlt = WaicUtils::getArrayValue($data, 'r', '');
							$needUpdate = true;
						}
						break;
					default:
						if (WaicUtils::getArrayValue($data, 's', 0, 1) == 1 && strpos($field, 'custom-') === 0) {
							$slug = str_replace('custom-', '', $field);
							$termsIds = $this->createTaxonomies(WaicUtils::getArrayValue($data, 'r'), $slug, '');
							if (!empty($termsIds)) {
								if (empty($post['tax_input'])) {
									$post['tax_input'] = array();
								}
								$post['tax_input'][$slug] = $termsIds;
							}
						}
						break;
				}
			}
			$postId = WaicUtils::getArrayValue($params, 'post_id', 0, 1);
			if (!empty($post) || $needUpdate) {
				$post['post_type'] = 'post';
				if (empty($postId)) {
					$post['post_status'] = 'publish';
					if (empty($post['post_title'])) {
						$post['post_title'] = 'New post';
					}
					if (empty($post['post_author'])) {
						$post['post_author'] = 1;
					}
					$postId = wp_insert_post($post, true);
				} else {
					$post['ID'] = $postId;
					$categories = empty($post['post_category']) ? false : $post['post_category'];
					$tags = empty($post['tags_input']) ? false : $post['tags_input'];
					$custom = empty($post['tax_input']) ? false : $post['tax_input'];
					
					unset($post['post_category'], $post['tags_input'], $post['tax_input']);
					$postId = wp_update_post($post);
					if ($postId) {
						if (!empty($categories)) {
							wp_set_post_terms($postId, $categories, 'category', WaicUtils::getArrayValue($params, 'categories_append', 0, 1) == 1);
						}
						if (!empty($tags)) {
							wp_set_post_terms($postId, $tags, 'post_tag', WaicUtils::getArrayValue($params, 'tags_append', 0, 1) == 1);
						}
						if (!empty($custom)) {
							foreach ($custom as $taxonomy => $tags) {
								wp_set_post_terms($postId, $tags, $taxonomy, true);
							}
						}
						
						if (empty($attId) && !empty($imgAlt)) {
							$attId = (int) get_post_meta($postId, '_thumbnail_id', true);
						}
						if (empty($post['post_title'])) {
							$p = get_post( $postId );
							$post['post_title'] = $p ? $p->post_title : '';
						}
					}
				}
				if ( is_wp_error($postId) ) {
					WaicFrame::_()->pushError($postId->get_error_message());
					return false;
				}
			
				if (!empty($attId)) {
					$title = $post['post_title'];
					$attUpd = wp_update_post(
						array(
							'ID' => $attId,
							'post_title' => $title,
							'post_content' => $title,
							'post_excerpt' => $title,
						)
					);
					if (!is_wp_error($attUpd)) {
						update_post_meta($attId, '_wp_attachment_image_alt', empty($imgAlt) ? $title : $imgAlt);
						update_post_meta($postId, '_thumbnail_id', $attId);
					}
				}
			}
			$found = WaicDispatcher::applyFilters('publishResults_' . $feature, 0, array('task_id' => $taskId, 'id' => $id, 'post_id' => $postId, 'post_data' => $postData));
			if (0 === $found) {
				if (!$this->updateResults($id, array('status' => 4, 'post_id' => $postId))) {
					return false;
				}
			} else if (false === $found) {
				return false;
			}
		}
		return true;
	}

	public function saveImage( $imageUrl, $title = '' ) {
		global $wpdb;
		$result = array('status' => 'error', 'msg' => esc_html__('Can not save image to media', 'ai-copilot-content-generator'));
		if (!function_exists('wp_generate_attachment_metadata')) {
			include_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if (!function_exists('download_url')) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if (!function_exists('media_handle_sideload')) {
			include_once ABSPATH . 'wp-admin/includes/media.php';
		}
		$attId = 0;

		$array = explode('/', getimagesize($imageUrl)['mime']);
		$imageType = end($array);
		$uniqName = md5($imageUrl);
		$fileName = $uniqName . '.' . $imageType;
		$checkExist = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_value LIKE %s", '%/' . $wpdb->esc_like($fileName)));
		if ($checkExist) {
			$attId = $checkExist->post_id;
		} else {
			if (file_exists($imageUrl)) {
				$tmp = $imageUrl;
			} else {
				$tmp = download_url($imageUrl);
			}
			if (is_wp_error($tmp)) {
				WaicFrame::_()->pushError($tmp->get_error_message());
				return false;
			}
			$args = array(
				'name' => $fileName,
				'tmp_name' => $tmp,
			);
			$attId = media_handle_sideload($args, 0, '', array(
				'post_title' => $title,
				'post_content' => $title,
				'post_excerpt' => $title,
			));
			if (!is_wp_error($attId)) {
				update_post_meta($attId, '_wp_attachment_image_alt', $title);
				$imageNew = get_post( $attId );
				$fullSizePath = get_attached_file($imageNew->ID);
				$attData = wp_generate_attachment_metadata($attId, $fullSizePath);
				wp_update_attachment_metadata($attId, $attData);
			} else {
				WaicFrame::_()->pushError($attId->get_error_message());
				return false;
			}
		}
		return $attId;
	}
	
	public function createTaxonomies( $list, $taxonomy, $sep = ',' ) {
		if (is_array($list)) {
			return WaicUtils::controlNumericValues($list);
		}
		
		$ids = array();
		$terms = empty($sep) ? array($list) : explode($sep, $list);
		if (empty($terms) || !taxonomy_exists($taxonomy)) {
			return $ids;
		}
		foreach ($terms as $name) {
			$name = trim($name);
			$term = get_term_by('name', $name, $taxonomy);
			if ($term) {
				$ids[] = $term->term_id;
			} else {
				$term = wp_insert_term( $name, $taxonomy );
				if (!is_wp_error($term)) {
					$ids[] = $term['term_id'];
				}
			}
		}
		return $ids;
	}
	public function isAllPublished( $taskId ) {
		$result = $this->setSelectFields('id')->setWhere(array('task_id' => $taskId))->addWhere('status not in (4,9)')->setLimit(1)->getFromTbl(array('return' => 'one'));
		return empty($result) || is_null($result);
	}

	public function publishDelayedPosts( $workspace ) {
		if ($workspace->isRunningFlag($this->publishFlagId, $this->publishFlagTimeout)) {
			return true;
		}
		$workspace->setRunningFlag($this->publishFlagId);
		$result = true;
		$posts = $this->setSelectFields('task_id, id')->setWhere('status=3 AND post_id=0 AND pub_mode>=1 AND (pub_mode=1 OR (pub_mode=2 AND publish<=' . "'" . WaicUtils::getTimestampDB() . "'))")->getFromTbl();
		if ($posts && is_array($posts)) {
			$tasksModel = $workspace->getModule()->getModel('tasks');
			$publish = array();
			foreach ($posts as $post) {
				$taskId = $post['task_id'];
				if (!isset($publish[$taskId])) {
					$publish[$taskId] = array();
				}
				$publish[$taskId][] = $post['id'];
			}
			foreach ($publish as $taskId => $ids) {
				if (!$this->publishResults($taskId, $ids)) {
					$result = false;
					break;
				}
				if ($this->isAllPublished($taskId)) {
					if (!$tasksModel->updateTask($taskId, array('status' => 4))) {
						return false;
					}
				}
			}
		}
		
		$workspace->resetRunningFlag($this->publishFlagId);
		return $result;
	}

	public function getFieldPrompt( $name, $field, $params, $results, $nRes = 0 ) {
		$prompt = WaicFrame::_()->getModule('options')->getWithDefaults('prompts', $name);
		$maxSteps = 9;
		$varOptions = $this->getPromptVarOptions();
		foreach ($varOptions as $var => $typ) {
			$rep = '';
			switch ($typ) {
				case 0:
					if (strpos($var, '@')) {
						$var = str_replace('@', $field, $var);
					}
					if (strpos($var, '#')) {
						for ($i = 1; $i <= $maxSteps; $i++) {
							$varN = str_replace('#', '', $var) . $i;
							$rep = WaicUtils::getArrayValue($params, $varN);
							if (!empty($rep)) {
								$prompt = str_replace('{' . $varN . '}', $rep, $prompt);
							} else {
								break;
							}
						}
					} else {
						$rep = WaicUtils::getArrayValue($params, $var);
						if (strpos($field, 'custom-') === 0) {
							$var = str_replace($field, 'custom', $var);
						}
					}
					break;
				case 1:
					$nums = ( 'sections' == $var );
					$data = WaicUtils::getArrayValue(WaicUtils::getArrayValue($results, $var, array(), 2), 'r');
					if (is_array($data)) {
						foreach ($data as $i => $d) {
							if (isset($d['r'])) {
								$rep .= ( $nums ? $i . '. ' : '' ) . htmlspecialchars_decode($d['r'], ENT_QUOTES) . PHP_EOL;
							}
						}
					} else {
						$rep = $data;
					}

					if ('image' == $field) {
						$step = 0;
						if (WaicUtils::mbstrlen($rep) > 300) {
							$rep = WaicUtils::mbsubstr($rep, 0, 300);
						}
						
						/*while (WaicUtils::mbstrlen($rep) > 300) {
							$lastPunkt = WaicUtils::mbstrrpos($rep, '.', -1);
							if ($lastPunkt) {
								$rep = WaicUtils::mbsubstr($rep, 0, $lastPunkt);
							} else {
								break;
							}
							$step++;
							if ($step > 20) {
								break;
							}
						}*/
					} 
					if (empty($rep)) {
						if ('title' == $var) { 
							$rep = WaicUtils::getArrayValue($params, 'original_article_title');
						} else if ('body' == $var) { 
							$rep = WaicUtils::getArrayValue($params, 'original_article_body');
						}
					}
					break;
				case 2: 
					if ('section#' == $var && !empty($nRes)) {
						$data = WaicUtils::getArrayValue(WaicUtils::getArrayValue($results, 'sections', array(), 2), 'r');
						if (!empty($data[$nRes]['r'])) {
							$rep = htmlspecialchars_decode($data[$nRes]['r'], ENT_QUOTES);
						} 
					} else if (strpos($var, '#') && !empty($nRes)) {
						$data = WaicUtils::getArrayValue(WaicUtils::getArrayValue($results, str_replace('#', '', $var), array(), 2), 'r');
						if (!empty($data[$nRes]['r'])) {
							$rep = htmlspecialchars_decode($data[$nRes]['r'], ENT_QUOTES);
						}
					}
					break;
				case 3: 
					if (strpos($var, '#') && !empty($nRes)) {
						$rep = WaicUtils::getArrayValue($params, str_replace('#', $nRes, $var));
					}
					break;
				default:
					break;
			}
			$prompt = str_replace('{' . $var . '}', $rep, $prompt);
		}
		return $prompt;
	}
}
