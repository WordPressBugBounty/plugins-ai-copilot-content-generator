<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicPostsfieldsModel extends WaicModel {

	public function isDeleteByCancel() {
		return true;
	}
	public function canUnpublish() {
		return false;
	}
	
	public function addPostFields( $fields ) {
		$fields['title']['modes'] = array(
			'gen_by_body' => __('Generate based on article', 'ai-copilot-content-generator'),
		);
		$fields['title']['modes_tooltip'] = __('This setting determines how the article title will be generated.', 'ai-copilot-content-generator');
		$fields['title']['required'] = false;
		
		$fields['image']['modes'] =array(
			'generate' => __('Generate a new featured image', 'ai-copilot-content-generator'),
			'no' => __('Do not generate', 'ai-copilot-content-generator'),
		);
		$fields['categories']['append'] = 1;
		$fields['tags']['append'] = 1;
		$fields['image']['modes_tooltip'] = __('Enable to generate a new Featured Image, replacing the existing one.', 'ai-copilot-content-generator');
		unset($fields['body'], $fields['date']);

		return $fields;
	}
	public function searchPostsList( $params ) {
		$length = WaicUtils::getArrayValue($params, 'length', 10, 1);
		$start = WaicUtils::getArrayValue($params, 'start', 0, 1);
		//$search = WaicUtils::getArrayValue(WaicUtils::getArrayValue($params, 'search', array(), 2), 'value');

		/*if (!empty($search)) {
			$model->addWhere(array('additionalCondition' => "title like '%" . $search . "%'"));
		}*/
		$order = WaicUtils::getArrayValue($params, 'order', array(), 2);
		$orderBy = 0;
		$sortOrder = 'DESC';
		$orders = array('id', 'id', 'title', 'feature', 'cnt', 'status', 'created', 'author');
		if (isset($order[0])) {
			$orderBy = WaicUtils::getArrayValue($order[0], 'column', $orderBy, 1);
			$sortOrder = WaicUtils::getArrayValue($order[0], 'dir', $sortOrder);
		}

		// Get total pages count for current request
		$args = array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page' => $length,
			'offset' => $start,
			'tax_query' => array(),
		);
		$title = WaicUtils::getArrayValue($params, 'filter_title');
		if (!empty($title)) {
			$args['waic_post_title'] = $title;
			add_filter('posts_where', array($this, 'addSearchByTitle'), 10, 2 );
		}
		$categories = WaicUtils::getArrayValue($params, 'filter_categories', array(), 2);
		if (!empty($categories)) {
			$args['tax_query'][] = array(
				'taxonomy' => 'category',
				'field' => 'term_id',
				'terms' => $categories,
				'operator' => 'IN',
				'include_children' => false,
			);
		}
		$tags = WaicUtils::getArrayValue($params, 'filter_tags', array(), 2);
		if (!empty($tags)) {
			$args['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field' => 'term_id',
				'terms' => $tags,
				'operator' => 'IN',
				'include_children' => false,
			);
		}
		if (!empty($args['tax_query'])) {
			$args['tax_query']['relation'] = 'AND'; 
		}
		//category__in
		$result = new WP_Query($args);
		//var_dump($result);
		$totalCount = 0;
		$rows = array();
		if ($result->have_posts() ) {
			$totalCount = $result->found_posts;
			foreach ($result->posts as $post) {
				$id = $post->ID;
				$authorId = $post->post_author;
				
				$rows[] = array(
					'<input type="checkbox" class="waicCheckOne" data-id="' . $id . '">',
					'<a href="' . esc_url(get_post_permalink($id)) . '" target="_blank" class="waic-post-link">' . esc_html($post->post_title) . '</a>',
					$this->getTaxonomyTermsList($id, 'category'),
					$this->getTaxonomyTermsList($id, 'post_tag'),
					get_the_author_meta( 'display_name' , $authorId ),
					esc_html($post->post_date),
				);
			}
		}
		wp_reset_query();
		
		return array(
			'data' => $rows,
			'total' => $totalCount,
		);
	}
	public function getTaxonomyTermsList( $postId, $taxonomy ) {
		$list = '';
		$terms = get_the_terms( $postId, $taxonomy );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$list .= $term->name . ', ';
			}
			$list = substr($list, 0, -2);
		}
		return $list;
	}
	public function addSearchByTitle( $where, $wp_query ) {
		global $wpdb;
		if (!empty($wp_query->get( 'waic_post_title' ))) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $wp_query->get( 'waic_post_title' ) ) ) . '%\'';
		}
		return $where;
	}
	
	
	public function convertTaskParameters( $params, $toDB = true ) {
		$params['common']['mode'] = 'bulk';
		if ($toDB && isset($params['posts'])) {
			$params['posts'] = json_decode(stripslashes($params['posts']));
		}
		return $params;
	}
	public function controlTaskParameters( $params, &$error = '' ) {
		$e = __('Not all required fields are specified', 'ai-copilot-content-generator');
		if (empty($params['posts'])) {
			$error = $e . ': ' . __('Select posts', 'ai-copilot-content-generator');
			return false;
		}
		
		if (empty($params['fields'])) {
			$error = $e . ': ' . __('Select acticle fields', 'ai-copilot-content-generator');
			return false;
		}
		
		if (isset($params['fields']) && isset($params['fields']['custom']) && is_array($params['fields']['custom'])) {
			foreach ($params['fields']['custom'] as $field => $data) {
				if (WaicUtils::getArrayValue($data, 'mode') == 'generate' && empty($data['prompt'])) {
					$error = $e . ': ' . __('Description for custom field', 'ai-copilot-content-generator') . ' ' . $field;
					return false;
				}
			}
		}
		return empty($error);
	}
	/*public function addTaskColumns( $columns, $params ) {
		$rss = WaicUtils::getArrayValue($params, 'rss', array(), 2);
		$cycle = 0;
		if (!empty($rss)) {
			if (WaicReq::getVar('typ', 'post') == 'save') {
				$columns['status'] = 8;
			} else {
				$mode = WaicUtils::getArrayValue($rss, 'mode', '');
				if ('one' != $mode) {
					$cycle = WaicUtils::getArrayValue($rss, 'frequency', 0, 1) * 60;
				}
			}
		}
		$columns['cycle'] = $cycle;
		return $columns;
	}
	public function activedSchedule( $taskId, $isOn) {
		$taskId = (int) $taskId;
		if (empty($taskId)) {
			return true;
		}
		$model = WaicFrame::_()->getModule('workspace')->getModel('tasks');
		$task = $model->getTask($taskId);

		if (!empty($task)) {
			$cycle = 0;
			if ($isOn) {
				$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
				if (empty($params)) {
					return true;
				}
				$rss = WaicUtils::getArrayValue($params, 'rss', array(), 2);
				$mode = WaicUtils::getArrayValue($rss, 'mode');
				if ('one' != $mode) {
					$cycle = WaicUtils::getArrayValue($rss, 'frequency', 0, 1) * 60;
				}
			}
			$status = $task['status'];
			if (empty($cycle)) {
				if (5 == $status) {
					$status = $this->isAllPublished($taskId) ? 4 : 3;
				}
			} else if ( 3 == $status || 4 == $status ) {
				$status = 5;
			}

			$model->updateById(array('cycle' => $cycle, 'status' => $status), $taskId);
			
		}
	}*/
	
	public function getBulkResultsList( $params, $param = false ) {
		$taskId = WaicUtils::getArrayValue($params, 'task_id', 0, 1);
		$length = WaicUtils::getArrayValue($params, 'length', 10, 1);
		$start = WaicUtils::getArrayValue($params, 'start', 0, 1);

		$orderBy = 'id';
		$sortOrder = 'ASC';
		$model = WaicFrame::_()->getModule('postscreate')->getModel();
		$model->setWhere(array('task_id' => $taskId));

		// Get total pages count for current request
		$totalCount = $model->getCount(array('clear' => array('selectFields')));
		if ($length > 0) {
			if ($start >= $totalCount) {
				$start = 0;
			}
			$this->setLimit($start . ', ' . $length);
		}

		$model->setOrderBy($orderBy)->setSortOrder($sortOrder);
		$data = $model->getFromTbl();
		
		if (empty($data)) {
			$data = array();
		} else {
			$frame = WaicFrame::_();
			$data = $this->prepareBulkResultsForTbl($data);
		}
		return array(
			'data' => $data,
			'total' => $totalCount,
		);
	}
	
	public function prepareBulkResultsForTbl( $posts ) {
		$rows = array();
		$loading = esc_html__('Loading...', 'ai-copilot-content-generator');
		$error = esc_html__('Error', 'ai-copilot-content-generator');
		$preview = esc_html__('preview', 'ai-copilot-content-generator');
		$view = esc_html__('view', 'ai-copilot-content-generator');
		$model = WaicFrame::_()->getModule('postscreate')->getModel();
		
		$statuses = $model->getStatuses();
		foreach ($posts as $post) {
			$id = $post['id'];
			$status = $post['status'];
			$newId = $post['post_id'];
			$params = WaicUtils::jsonDecode($post['params']);
			$results = WaicUtils::jsonDecode($post['results']);
			//$title = WaicUtils::getArrayValue($results, 'title');
			$postId = WaicUtils::getArrayValue($params, 'post_id');
			$postObj = get_post($postId);
			if (!$postObj) {
				continue;
			}
			$rows[] = array(
				'<input type="checkbox" class="waicCheckOne" data-post="' . esc_attr($id) . '">',
				'<a href="' . esc_url(get_post_permalink($postId)) . '" target="_blank" class="waic-post-link">' . esc_html($postObj->post_title) . '</a>',
				'<a href="#" class="waic-result-preview" data-post="' . esc_attr($id) . '" data-post-status="' . esc_attr($status) . '" data-can-publish="' . ( $model->canPostPublish($status) ? 1 : 0 ) . '">' . $preview . '</a>',
				empty($statuses[$status]) ? '' : $statuses[$status],
				empty($newId) ? '' : '<a href="' . esc_url(get_post_permalink($newId)) . '" target="_blank" class="waic-post-link">' . esc_html($view) . '</a>',
			);
		}
		return $rows;
	}
	/*public function getSocialPost( $taskId, $postId, $social ) {
		$postData = WaicFrame::_()->getModule('postscreate')->getModel()->getTaskResults($taskId, $postId);
		if (empty($postData)) {
			WaicFrame::_()->pushError(esc_html__('Post not found', 'ai-copilot-content-generator'));
			return false;
		}
		if (empty($postData['post_id'])) {
			WaicFrame::_()->pushError(esc_html__('Article is not published', 'ai-copilot-content-generator'));
			return false;
		}
		$postUrl = get_post_permalink($postData['post_id']);
		$result = WaicUtils::getArrayValue($postData['results'], $social, array(), 2);
		if (empty($result) || ( WaicUtils::getArrayValue($result, 's', 0, 1) != 1 ) || empty(WaicUtils::getArrayValue($result, 'r'))) {
			WaicFrame::_()->pushError(esc_html__('Social post is not renerated', 'ai-copilot-content-generator'));
			return false;
		}
		$text = str_replace("\n", '<br>', WaicUtils::getArrayValue($result, 'r'));
		if ($postUrl) {
			//$text = str_replace('{article_url}', '<a href="' . esc_url($postUrl) . '">' . esc_html__('link', 'ai-copilot-content-generator') . '</a>', $text);
			$text = str_replace('{article_url}', $postUrl, $text);
		}
		return $text;
	}*/
	public function clearEtaps( $taskId, $ids = false, $withContent = true ) {
		$taskId = (int) $taskId;
		$model = WaicFrame::_()->getModule('postscreate')->getModel();
		if ($withContent) {
			//$model->unpublishEtaps($taskId, $ids);
			$model->deleteImages($taskId, $ids);
		}
		if (empty($ids)) {
			$model->delete(array('task_id' => $taskId));
		} else {
			$model->removeGroup($ids);
		}
		return true;
	}
	public function cancelTaskEtaps( $taskId, $ids ) {
		$taskId = (int) $taskId;
		if (empty($taskId) || !is_array($ids)) {
			return true;
		}
		$workspace = WaicFrame::_()->getModule('workspace');
		$tasksModel = $workspace->getModel('tasks');
		$task = $tasksModel->getById($taskId);
		if (!empty($task)) {
			$model = WaicFrame::_()->getModule('postscreate')->getModel();
			foreach ($ids as $id) {
				$model->updateById(array('post_id' => 0, 'status' => 9, 'pub_mode' => 0), $id);
			}
			$task = $workspace->getModel()->controlSteps($task, $model);
		}
		
		return true;
	}
	
	public function prepareGeneration( $task ) {
		$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
		if (empty($params)) {
			WaicFrame::_()->pushError(esc_html__('Tasks parameters empty', 'ai-copilot-content-generator'));
			return false;
		}
		$taskId = $task['id'];
		$common = WaicUtils::getArrayValue($params, 'common', array(), 2);
		$fields = WaicUtils::getArrayValue($params, 'fields', array(), 2);

		$topics = array();
		$vars = array();
		if (!empty($fields['title']) && is_array($fields['title'])) {
			$vars['prompt_title'] = 'title_body';
		}
		$vars['pub_mode'] = ( 'now' == WaicUtils::getArrayValue($common, 'publish') ? 1 : 0 );
		$params['without_topic'] = 1;

		$posts = WaicUtils::getArrayValue($params, 'posts', array(), 2);
		foreach ($posts as $p) {
			$postId = isset($p[0]) ? trim($p[0]) : '';
			if (empty($postId)) {
				continue;
			}
			$postObj = get_post($postId);
			if ($postObj) {
				$body = $postObj->post_content;
				$title = $postObj->post_title;
			} else {
				continue;
			}
			
			$topic = isset($p[1]) ? trim($p[1]) : '';
			$keywords = isset($p[2]) ? trim($p[2]) : '';
			$postVars = array(
				'post_id' => $postId,
				'original_article_body' => $body,
				'original_article_title' => $title,
			);
			
			$topics[] = array($topic, $keywords, array_merge($postVars, $vars));
		}
		//$params['common']['mode'] = 'bulk';
		$params['bulk'] = array('topics' => $topics);
		
		return WaicFrame::_()->getModule('postscreate')->getModel()->prepareGeneration($task, false, $params);
	}
	public function doGeneration( $task, $aiProvider ) {
		$taskId = $task['id'];
		WaicFrame::_()->saveDebugLogging('Start generation for PostFields task ID=' . $taskId);
		
		$model = WaicFrame::_()->getModule('postscreate')->getModel();
		$task = WaicFrame::_()->getModule('workspace')->getModel()->controlSteps($task, $model);
		
		$result = false;
		
		$model->setAiProvider($aiProvider);
		$model->resetEtaps($taskId);
		do {
			$etap = $model->getEtapForGeneration($taskId);
			if ($etap) {
				$taskSteps = $model->getGeneratedSteps($taskId);
				$result = $model->doGenerationEtap($etap, $taskSteps);

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
		WaicFrame::_()->saveDebugLogging('Stop generation for PostFields task ID=' . $taskId . '. Set status=' . $result);
		return $params;
	}


	public function getAllCountSteps( $taskId ) {
		return WaicFrame::_()->getModule('postscreate')->getModel()->getAllCountSteps($taskId);
		//$all = WaicDb::get('SELECT sum(step) as step, sum(steps) as steps, count(*) as cnt FROM `@__posts_create` WHERE task_id=' . $taskId, 'row');
		//return $all ? $all : array('step' => 0, 'steps' => 0, 'cnt' => 0);
	}
	public function editResults( $taskId, $edited, $refresh ) {
		return WaicFrame::_()->getModule('postscreate')->getModel()->editResults($taskId, $edited, $refresh);
	}
	public function getGeneratedSteps( $taskId ) {
		return WaicFrame::_()->getModule('postscreate')->getModel()->getGeneratedSteps($taskId);
	}
	public function isAllPublished( $taskId ) {
		return WaicFrame::_()->getModule('postscreate')->getModel()->isAllPublished($taskId);
	}
	public function publishResults( $taskId, $publish ) {
		return WaicFrame::_()->getModule('postscreate')->getModel()->publishResults($taskId, $publish);
	}
	/*public function publishSocials( $taskId, $postId, $social ) {
		
	}*/
}
