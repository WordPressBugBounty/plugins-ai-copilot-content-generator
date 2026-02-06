<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicMcpModel extends WaicModel {
	private $tools = false;

	public function getToolsList() {
		$tools = $this->getTools();
		foreach ($tools as &$tool) {
			/*if (!isset($tool['category'])) {
				$tool['category'] = 'Core';
			}*/
			if (in_array($tool['name'], array('search', 'fetch'))) {
				$tool['category'] = 'Core: OpenAI';
			} elseif (strpos($tool['name'], 'workflow_') === 0) {
				$tool['category'] = 'Workflow';
			} else {
				$tool['category'] = 'Core';
			}
		}
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		/*if (strpos($userAgent, 'openai-mcp') === false) {
			unset($tools['search'], $tools['fetch']);
		}*/
		return array_values($tools);
	}
	public function getTools() {
		if (empty($this->tools)) {
			$tools = array(
				'mcp_ping' => array(
					'name' => 'mcp_ping',
					'description' => 'Simple connectivity check. Returns the current GMT time and the WordPress site name. Whenever a tool call fails (error or timeout), immediately invoke mcp_ping to verify the server; if mcp_ping itself does not respond, assume the server is temporarily unreachable and pause additional tool calls.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => (object) array(),
						'required' => array(),
					),
				),
				'wp_list_plugins' => array(
					'name' => 'wp_list_plugins',
					'description' => 'List installed plugins (returns array of {Name, Version}).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('search' => array('type' => 'string')),
						'required' => array(),
					),
				),
				'wp_get_users' => array(
					'name' => 'wp_get_users',
					'description' => 'Retrieve users (fields: ID, user_login, display_name, roles). If no limit supplied, returns 10. `paged` ignored if `offset` is used.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'search' => array('type' => 'string'),
							'role' => array('type' => 'string'),
							'limit' => array('type' => 'integer'),
							'offset' => array('type' => 'integer'),
							'paged' => array('type' => 'integer'),
						),
						'required' => array(),
					),
				),
				'wp_create_user' => array(
					'name' => 'wp_create_user',
					'description' => 'Create a user. Requires user_login and user_email. Optional: user_pass (random if omitted), display_name, role.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'user_login' => array('type' => 'string'),
							'user_email' => array('type' => 'string'),
							'user_pass' => array('type' => 'string'),
							'display_name' => array('type' => 'string'),
							'role' => array('type' => 'string'),
						),
						'required' => array('user_login', 'user_email'),
					),
				),
				'wp_update_user' => array(
					'name' => 'wp_update_user',
					'description' => 'Update a user – pass ID plus a “fields” object (user_email, display_name, user_pass, role).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'fields' => array(
								'type' => 'object',
								'properties' => array(
									'user_email' => array('type' => 'string'),
									'display_name' => array('type' => 'string'),
									'user_pass' => array('type' => 'string'),
									'role' => array('type' => 'string'),
								),
								'additionalProperties' => true,
							),
						),
						'required' => array('ID'),
					),
				),
				'wp_get_comments' => array(
					'name' => 'wp_get_comments',
					'description' => 'Retrieve comments (fields: comment_ID, comment_post_ID, comment_author, comment_content, comment_date, comment_approved). Returns 10 by default.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'post_id' => array('type' => 'integer'),
							'status' => array('type' => 'string'),
							'search' => array('type' => 'string'),
							'limit' => array('type' => 'integer'),
							'offset' => array('type' => 'integer'),
							'paged' => array('type' => 'integer'),
						),
						'required' => array(),
					),
				),
				'wp_create_comment' => array(
					'name' => 'wp_create_comment',
					'description' => 'Insert a comment. Requires post_id and comment_content. Optional author, author_email, author_url.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'post_id' => array('type' => 'integer'),
							'comment_content' => array('type' => 'string'),
							'comment_author' => array('type' => 'string'),
							'comment_author_email' => array('type' => 'string'),
							'comment_author_url' => array('type' => 'string'),
							'comment_approved' => array('type' => 'string'),
						),
						'required' => array('post_id', 'comment_content'),
					),
				),
				'wp_update_comment' => array(
					'name' => 'wp_update_comment',
					'description' => 'Update a comment – pass comment_ID plus fields (comment_content, comment_approved).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'comment_ID' => array('type' => 'integer'),
							'fields' => array(
								'type' => 'object',
								'properties' => array(
									'comment_content' => array('type' => 'string'),
									'comment_approved' => array('type' => 'string'),
								),
								'additionalProperties' => true,
							),
						),
						'required' => array('comment_ID'),
					),
				),
				'wp_delete_comment' => array(
					'name' => 'wp_delete_comment',
					'description' => 'Delete a comment. `force` true bypasses trash.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'comment_ID' => array('type' => 'integer'),
							'force' => array('type' => 'boolean'),
						),
						'required' => array('comment_ID'),
					),
				),
				'wp_get_option' => array(
					'name' => 'wp_get_option',
					'description' => 'Get a single WordPress option value (scalar or array) by key.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('key' => array('type' => 'string')),
						'required' => array('key'),
					),
				),
				'wp_update_option' => array(
					'name' => 'wp_update_option',
					'description' => 'Create or update a WordPress option (JSON-serialised if necessary).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'key' => array('type' => 'string'),
							'value' => array('type' => array('string', 'number', 'boolean', 'object')),
						),
						'required' => array('key', 'value'),
					),
				),
				'wp_count_posts' => array(
					'name' => 'wp_count_posts',
					'description' => 'Return counts of posts by status. Optional post_type (default post).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('post_type' => array('type' => 'string')),
						'required' => array(),
					),
				),
				'wp_count_terms' => array(
					'name' => 'wp_count_terms',
					'description' => 'Return total number of terms in a taxonomy.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('taxonomy' => array('type' => 'string')),
						'required' => array('taxonomy'),
					),
				),
				'wp_count_media' => array(
					'name' => 'wp_count_media',
					'description' => 'Return number of attachments (optionally after/before date).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'after' => array('type' => 'string'),
							'before' => array('type' => 'string'),
						),
						'required' => array(),
					),
				),
				'wp_get_post_types' => array(
					'name' => 'wp_get_post_types',
					'description' => 'List public post types (key, label).',
					'inputSchema' => array(
						'type' => 'object', 
						'properties' => (object) array(),
						'required' => array(),
					),
				),
				'wp_get_posts' => array(
					'name' => 'wp_get_posts',
					'description' => 'Retrieve posts (fields: ID, title, status, excerpt, link). No full content. **If no limit is supplied it returns 10 posts by default.** `paged` is ignored if `offset` is used.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'post_type' => array('type' => 'string'),
							'post_status' => array('type' => 'string'),
							'search' => array('type' => 'string'),
							'after' => array('type' => 'string'),
							'before' => array('type' => 'string'),
							'limit' => array('type' => 'integer'),
							'offset' => array('type' => 'integer'),
							'paged' => array('type' => 'integer'),
						),
						'required' => array(),
					),
				),
				'wp_get_post' => array(
					'name' => 'wp_get_post',
					'description' => 'Get a single post by ID (all fields inc. full content).',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('ID' => array('type' => 'integer')),
						'required' => array('ID'),
					),
				),
				'wp_create_post' => array(
					'name' => 'wp_create_post',
					'description' => 'Create a post or page – post_title required; Markdown accepted in post_content; defaults to draft post_status and post post_type; set categories later with wp_add_post_terms; meta_input is an associative array of custom-field key/value pairs.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'post_title' => array('type' => 'string'),
							'post_content' => array('type' => 'string'),
							'post_excerpt' => array('type' => 'string'),
							'post_status' => array('type' => 'string'),
							'post_type' => array('type' => 'string'),
							'post_name' => array('type' => 'string'),
							'meta_input' => array('type' => 'object', 'description' => 'Associative array of custom fields.'),
						),
						'required' => array('post_title'),
					),
				),
				'wp_update_post' => array(
					'name' => 'wp_update_post',
					'description' => 'Update a post – pass ID plus a “fields” object containing any post fields to update; meta_input adds/updates custom fields. post_category (array of term IDs) REPLACES existing categories; use wp_add_post_terms to append.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer', 'description' => 'The ID of the post to update.'),
							'fields' => array(
								'type' => 'object',
								'properties' => array(
									'post_title' => array('type' => 'string'),
									'post_content' => array('type' => 'string'),
									'post_status' => array('type' => 'string'),
									'post_name' => array('type' => 'string'),
									'post_excerpt' => array('type' => 'string'),
									'post_category' => array('type' => 'array', 'items' => array('type' => 'integer')),
								),
								'additionalProperties' => true,
							),
							'meta_input' => array(
								'type' => 'object',
								'description' => 'Associative array of custom fields.',
							),
						),
						'required' => array('ID'),
					),
				),
				'wp_delete_post' => array(
					'name' => 'wp_delete_post',
					'description' => 'Delete/trash a post.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'force' => array('type' => 'boolean'),
						),
						'required' => array('ID'),
					),
				),
				'wp_get_post_meta' => array(
					'name' => 'wp_get_post_meta',
					'description' => 'Retrieve post meta. Provide "key" to fetch a single value; omit to fetch all custom fields.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'key' => array('type' => 'string'),
						),
						'required' => array('ID'),
					),
				),
				'wp_update_post_meta' => array(
					'name' => 'wp_update_post_meta',
					'description' => 'Create or update one or more custom fields for a post.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'meta' => array('type' => 'object', 'description' => 'Key/value pairs to set. Alternative: provide "key" + "value".'),
							'key' => array('type' => 'string'),
							'value' => array('type' => array('string', 'number', 'boolean')),
						),
						'required' => array('ID'),
					),
				),
				'wp_delete_post_meta' => array(
					'name' => 'wp_delete_post_meta',
					'description' => 'Delete custom field(s) from a post. Provide value to remove a single row; omit value to delete all rows for the key.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'key' => array('type' => 'string'),
							'value' => array('type' => array('string', 'number', 'boolean')),
						),
						'required' => array('ID', 'key'),
					),
				),
				'wp_set_featured_image' => array(
					'name' => 'wp_set_featured_image',
					'description' => 'Attach or remove a featured image (thumbnail) for a post/page. Provide media_id to attach, omit or null to remove.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'post_id' => array('type' => 'integer'),
							'media_id' => array('type' => 'integer'),
						),
						'required' => array('post_id'),
					),
				),
				'wp_get_taxonomies' => array(
					'name' => 'wp_get_taxonomies',
					'description' => 'List taxonomies for a post type.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array('post_type' => array('type' => 'string')),
						'required' => array(),
					),
				),
				'wp_get_terms' => array(
					'name' => 'wp_get_terms',
					'description' => 'List terms of a taxonomy.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'taxonomy' => array('type' => 'string'),
							'search' => array('type' => 'string'),
							'parent' => array('type' => 'integer'),
							'limit' => array('type' => 'integer'),
						),
						'required' => array('taxonomy'),
					),
				),
				'wp_create_term' => array(
					'name' => 'wp_create_term',
					'description' => 'Create a term.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'taxonomy' => array('type' => 'string'),
							'term_name' => array('type' => 'string'),
							'slug' => array('type' => 'string'),
							'description' => array('type' => 'string'),
							'parent' => array('type' => 'integer'),
						),
						'required' => array('taxonomy', 'term_name'),
					),
				),
				'wp_update_term' => array(
					'name' => 'wp_update_term',
					'description' => 'Update a term.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'term_id' => array('type' => 'integer'),
							'taxonomy' => array('type' => 'string'),
							'name' => array('type' => 'string'),
							'slug' => array('type' => 'string'),
							'description' => array('type' => 'string'),
							'parent' => array('type' => 'integer'),
						),
						'required' => array('term_id', 'taxonomy'),
					),
				),
				'wp_delete_term' => array(
					'name' => 'wp_delete_term',
					'description' => 'Delete a term.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'term_id' => array('type' => 'integer'),
							'taxonomy' => array('type' => 'string'),
						),
						'required' => array('term_id', 'taxonomy'),
					),
				),
				'wp_get_post_terms' => array(
					'name' => 'wp_get_post_terms',
					'description' => 'Get terms attached to a post.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'taxonomy' => array('type' => 'string'),
						),
						'required' => array('ID'),
					),
				),
				'wp_add_post_terms' => array(
					'name' => 'wp_add_post_terms',
					'description' => 'Attach terms to a post.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'taxonomy' => array('type' => 'string'),
							'terms' => array('type' => 'array', 'items' => array('type' => 'integer')),
							'append' => array('type' => 'boolean'),
						),
						'required' => array('ID', 'terms'),
					),
				),
				'wp_get_media' => array(
					'name' => 'wp_get_media',
					'description' => 'List media items.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'search' => array('type' => 'string'),
							'after' => array('type' => 'string'),
							'before' => array('type' => 'string'),
							'limit' => array('type' => 'integer'),
						),
						'required' => array(),
					),
				),
				'wp_upload_media' => array(
					'name' => 'wp_upload_media',
					'description' => 'Download file from URL and add to Media Library.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'url' => array('type' => 'string'),
							'title' => array('type' => 'string'),
							'description' => array('type' => 'string'),
							'alt' => array('type' => 'string'),
						),
						'required' => array('url'),
					),
				),
				'wp_update_media' => array(
					'name' => 'wp_update_media',
					'description' => 'Update attachment meta.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'title' => array('type' => 'string'),
							'caption' => array('type' => 'string'),
							'description' => array('type' => 'string'),
							'alt' => array('type' => 'string'),
						),
						'required' => array('ID'),
					),
				),
				'wp_delete_media' => array(
					'name' => 'wp_delete_media',
					'description' => 'Delete/trash an attachment.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'ID' => array('type' => 'integer'),
							'force' => array('type' => 'boolean'),
						),
						'required' => array('ID'),
					),
				),
				'aiwu_image' => array(
					'name' => 'aiwu_image',
					'description' => 'Generate an image with AIWU Plugin and store it in the Media Library. Optional: title, caption, description, alt. Returns { id, url, title, caption, alt }.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'message' => array('type' => 'string', 'description' => 'Prompt describing the desired image.'),
							'postId' => array('type' => 'integer', 'description' => 'Optional post ID to attach the image to.'),
							'title' => array('type' => 'string'),
							'caption' => array('type' => 'string'),
							'description' => array('type' => 'string'),
							'alt' => array('type' => 'string'),
						),
						'required' => array('message'),
					),
				),
				'search' => array(
					'name' => 'search',
					'description' => 'Searches through all published posts and pages on the "' . get_bloginfo( 'name' ) . '" WordPress website' . ( get_bloginfo( 'description' ) ? ' - ' . get_bloginfo( 'description' ) : '' ) . '. This tool performs full-text search across titles and content to find relevant articles, blog posts, and static pages. The search results include article summaries and URLs for citation purposes. Use this to find information about topics covered on this WordPress site, including blog posts, tutorials, documentation, news, and any other content published on the website.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'query' => array('type' => 'string', 'description' => 'Search query to find relevant posts and pages. Can be keywords, phrases, or topics.'),
						),
						'required' => array('query'),
					),
				),
				'fetch' => array(
					'name' => 'fetch',
					'description' => 'Retrieves the complete content of a specific post or page from the "' . get_bloginfo( 'name' ) . '" WordPress website' . ( get_bloginfo( 'description' ) ? ' - ' . get_bloginfo( 'description' ) : '' ) . ' using its ID. This returns the full article text, metadata (author, publication date, categories, tags), and URL for proper citation. Use this after searching to get the complete content of relevant articles for deep analysis and comprehensive answers. The content is essential for providing accurate, detailed responses based on the actual information published on the website.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'string', 'description' => 'The WordPress post ID obtained from search results.'),
						),
						'required' => array('id'),
					),
				),
			// Workflow Tools
				'workflow_list' => array(
					'name' => 'workflow_list',
					'description' => 'List all workflows with their status, title, and basic info.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'status' => array('type' => 'string', 'description' => 'Filter: all, active, draft, stopped'),
							'limit' => array('type' => 'integer'),
						),
						'required' => array(),
					),
				),
				'workflow_get' => array(
					'name' => 'workflow_get',
					'description' => 'Get workflow details by task ID including nodes, edges, settings.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer', 'description' => 'Workflow task ID'),
						),
						'required' => array('id'),
					),
				),
				'workflow_blocks' => array(
					'name' => 'workflow_blocks',
					'description' => 'List available blocks (triggers, actions, logics) with settings schema.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'type' => array('type' => 'string', 'description' => 'Filter: trigger, action, logic'),
						),
						'required' => array(),
					),
				),
				'workflow_create' => array(
                    'name' => 'workflow_create',
                    'description' => 'Create workflow. Nodes: {id, type, position:{x,y}, data:{type,code,category,label,settings}}. Edges: {id, source, sourceHandle, target, targetHandle, type}.',
                    'inputSchema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'title' => array('type' => 'string'),
                            // ИСПРАВЛЕНИЕ: Добавлен параметр items
                            'nodes' => array(
                                'type' => 'array',
                                'items' => array('type' => 'object') 
                            ),
                            // ИСПРАВЛЕНИЕ: Добавлен параметр items
                            'edges' => array(
                                'type' => 'array',
                                'items' => array('type' => 'object')
                            ),
                            'settings' => array('type' => 'object'),
                        ),
                        'required' => array('title', 'nodes', 'edges'),
                    ),
                ),
				'workflow_publish' => array(
					'name' => 'workflow_publish',
					'description' => 'Publish/start a workflow.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer'),
						),
						'required' => array('id'),
					),
				),
				'workflow_stop' => array(
					'name' => 'workflow_stop',
					'description' => 'Stop a running workflow.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer'),
						),
						'required' => array('id'),
					),
				),
				'workflow_delete' => array(
					'name' => 'workflow_delete',
					'description' => 'Delete a workflow.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer'),
						),
						'required' => array('id'),
					),
				),
				'workflow_runs' => array(
					'name' => 'workflow_runs',
					'description' => 'Get workflow execution history.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer'),
							'limit' => array('type' => 'integer'),
						),
						'required' => array('id'),
					),
				),
				'workflow_run_manual' => array(
					'name' => 'workflow_run_manual',
					'description' => 'Manually trigger workflow with manual trigger.',
					'inputSchema' => array(
						'type' => 'object',
						'properties' => array(
							'id' => array('type' => 'integer'),
							'params' => array('type' => 'object'),
						),
						'required' => array('id'),
					),
				),
			);
			$this->tools = WaicDispatcher::applyFilters('mcp_tools', $tools);
		}
		return $this->tools;
	}
	private function addResultText( array &$r, string $text ): void {
		if (!isset($r['result']['content'])) {
			$r['result']['content'] = [];
		}
		$r['result']['content'][] = array('type' => 'text', 'text' => $text);
	}
	private function cleanHtml( string $v ): string {
		return wp_kses_post( wp_unslash( $v ) );
	}
	private function postExcerpt( WP_Post $p ): string {
		return wp_trim_words( wp_strip_all_tags( isset($p->post_excerpt) && !empty($p->post_excerpt) ? $p->post_excerpt : $p->post_content ), 55 );
	}
	public function dispatchTool( string $tool, array $a, $id ): array {
		$r = array('jsonrpc' => '2.0', 'id' => $id);

		switch ($tool) {
			case 'mcp_ping':
				$pingData = array(
					'time' => gmdate('Y-m-d H:i:s'),
					'name' => get_bloginfo('name'),
				);
				$this->addResultText($r, 'Ping successful: ' . wp_json_encode($pingData, JSON_PRETTY_PRINT));
				break;
			case 'wp_get_users':
				$q = array(
					'search' => '*' . esc_attr(WaicUtils::getArrayValue($a, 'search')) . '*',
					'role' => WaicUtils::getArrayValue($a, 'role'),
					'number' => max(1, intval(WaicUtils::getArrayValue($a, 'limit', 10, 1))),
				);
				if (isset($a['offset'])) {
					$q['offset'] = max(0, intval($a['offset']));
				}
				if (isset($a['paged'])) {
					$q['paged'] = max(1, intval($a['paged']));
				}
				$rows = array();
				foreach (get_users($q) as $u) {
					$rows[] = array(
						'ID' => $u->ID,
						'user_login' => $u->user_login,
						'display_name' => $u->display_name,
						'roles' => $u->roles,
					);
				}
				$this->addResultText($r, wp_json_encode($rows, JSON_PRETTY_PRINT));
				break;
			case 'wp_create_user':
				$data = array(
					'user_login' => sanitize_user($a['user_login']),
					'user_email' => sanitize_email($a['user_email']),
					'user_pass' => WaicUtils::getArrayValue($a, 'user_pass', wp_generate_password(12, true)),
					'display_name' => sanitize_text_field(WaicUtils::getArrayValue($a, 'display_name')),
					'role' => sanitize_key(WaicUtils::getArrayValue($a, 'role', get_option('default_role', 'subscriber'))),
				);
				$uid = wp_insert_user($data);
				if (is_wp_error($uid)) {
					$r['error'] = array('code' => $uid->get_error_code(), 'message' => $uid->get_error_message());
				} else {
					$this->addResultText($r, 'User created ID ' . $uid);
				}
				break;
			case 'wp_update_user':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$upd = array('ID' => intval($a['ID']));
				if (!empty($a['fields']) && is_array($a['fields'])) {
					foreach ($a['fields'] as $k => $v) {
						$upd[ $k ] = ( 'role' === $k ) ? sanitize_key($v) : sanitize_text_field($v);
					}
				}
				$u = wp_update_user($upd);
				if (is_wp_error($u)) {
					$r['error'] = array('code' => $u->get_error_code(), 'message' => $u->get_error_message());
				} else {
					$this->addResultText($r, 'User #' . $u . ' updated');
				}
				break;
			case 'wp_get_comments':
				$args = array(
					'post_id' => WaicUtils::getArrayValue($a, 'post_id', 0, 1),
					'status' => WaicUtils::getArrayValue($a, 'status', 'approve'),
					'search' => WaicUtils::getArrayValue($a, 'search'),
					'number' => max(1, WaicUtils::getArrayValue($a, 'limit', 10, 1)),
				);
				if (isset($a['offset'])) {
					$args['offset'] = max(0, intval($a['offset']));
				}
				if (isset($a['paged'])) {
					$args['paged'] = max(1, intval($a['paged']));
				}
				$list = array();
				foreach (get_comments($args) as $c) {
					$list[] = array(
						'comment_ID' => $c->comment_ID,
						'comment_post_ID' => $c->comment_post_ID,
						'comment_author' => $c->comment_author,
						'comment_content' => wp_trim_words(wp_strip_all_tags($c->comment_content), 40),
						'comment_date' => $c->comment_date,
						'comment_approved' => $c->comment_approved,
					);
				}
				$this->addResultText($r, wp_json_encode($list, JSON_PRETTY_PRINT));
				break;
			case 'wp_create_comment':
				if (empty($a['post_id']) || empty($a['comment_content'])) {
					$r['error'] = array('code' => -42602, 'message' => 'post_id & comment_content required');
					break;
				}
				$ins = array(
					'comment_post_ID' => intval($a['post_id']),
					'comment_content' => $this->cleanHtml($a['comment_content']),
					'comment_author' => sanitize_text_field(WaicUtils::getArrayValue($a, 'comment_author')),
					'comment_author_email' => sanitize_email(WaicUtils::getArrayValue($a, 'comment_author_email')),
					'comment_author_url' => esc_url_raw(WaicUtils::getArrayValue($a, 'comment_author_url')),
					'comment_approved' => WaicUtils::getArrayValue($a, 'comment_approved', 1),
				);
				$cid = wp_insert_comment($ins);
				if (is_wp_error($cid)) {
					$r['error'] = array('code' => $cid->get_error_code(), 'message' => $cid->get_error_message());
				} else {
					$this->addResultText($r, 'Comment created ID ' . $cid);
				}
				break;
			case 'wp_update_comment':
				if (empty($a['comment_ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'comment_ID required');
					break;
				}
				$c = array('comment_ID' => intval($a['comment_ID']));
				if (!empty($a['fields']) && is_array($a['fields'])) {
					foreach ($a['fields'] as $k => $v) {
						$c[$k] = ( 'comment_content' === $k ) ? $this->cleanHtml($v) : sanitize_text_field($v);
					}
				}
				$cid = wp_update_comment($c, true);
				if (is_wp_error($cid)) {
					$r['error'] = array('code' => $cid->get_error_code(), 'message' => $cid->get_error_message());
				} else {
					$this->addResultText($r, 'Comment #' . $cid . ' updated');
				}
				break;
			case 'wp_delete_comment':
				if (empty($a['comment_ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'comment_ID required');
					break;
				}
				$done = wp_delete_comment(intval($a['comment_ID']), !empty($a['force']));
				if ($done) {
					$this->addResultText($r, 'Comment #' . $a['comment_ID'] . ' deleted');
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Deletion failed');
				}
				break;
			case 'wp_get_option':
				if (empty($a['key'])) {
					$r['error'] = array('code' => -42602, 'message' => 'key required');
					break;
				}
				$val = get_option(sanitize_key($a['key']));
				$this->addResultText($r, wp_json_encode($val, JSON_PRETTY_PRINT));
				break;
			case 'wp_update_option':
				if (empty($a['key']) || !isset($a['value'])) {
					$r['error'] = array('code' => -42602, 'message' => 'key & value required');
					break;
				}
				$set = update_option(sanitize_key($a['key']), $a['value'], 'yes');
				if ($set) {
					$this->addResultText($r, 'Option "' . $a['key'] . '" updated' );
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Update failed');
				}
				break;
			case 'wp_count_posts':
				$pt = sanitize_key(WaicUtils::getArrayValue($a, 'post_type', 'post'));
				$obj = wp_count_posts($pt);
				$this->addResultText($r, wp_json_encode($obj, JSON_PRETTY_PRINT));
				break;
			case 'wp_count_terms':
				if (empty($a['taxonomy'])) {
					$r['error'] = array('code' => -42602, 'message' => 'taxonomy required');
					break;
				}
				$tax = sanitize_key($a['taxonomy']);
				$total = wp_count_terms($tax, array('hide_empty' => false));
				if (is_wp_error($total)) {
					$r['error'] = array('code' => $total->get_error_code(), 'message' => $total->get_error_message());
				} else {
					$this->addResultText($r, (string) $total);
				}
				break;
			case 'wp_count_media':
				$args = array('post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids');
				$d = array();
				if (!empty($a['after'])) {
					$d['after'] = $a['after'];
				}
				if (!empty($a['before'])) {
					$d['before'] = $a['before'];
				}
				if ($d) {
					$args['date_query'] = array($d);
				}
				$total = count(get_posts($args));
				$this->addResultText($r, (string) $total);
				break;
			case 'wp_get_post_types':
				$out = array();
				foreach (get_post_types(array('public' => true), 'objects') as $pt) {
					$out[] = array('key' => $pt->name, 'label' => $pt->label);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_list_plugins':
				if (!function_exists('get_plugins')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$search = sanitize_text_field(WaicUtils::getArrayValue($a, 'search'));
				$out = array();
				foreach (get_plugins() as $p) {
					if (!$search || stripos($p['Name'], $search ) !== false) {
						$out[] = array('Name' => $p['Name'], 'Version' => $p['Version']);
					}
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_get_posts':
				$q = array(
					'post_type' => sanitize_key(WaicUtils::getArrayValue($a, 'post_type', 'post')),
					'post_status' => sanitize_key(WaicUtils::getArrayValue($a, 'post_status', 'publish')),
					's' => sanitize_text_field(WaicUtils::getArrayValue($a, 'search')),
					'posts_per_page' => max(1, intval(WaicUtils::getArrayValue($a, 'limit', 10, 1))),
				);
				if (isset($a['offset'])) {
					$q['offset'] = max(0, intval($a['offset']));
				}
				if (isset($a['paged'])) {
					$q['paged'] = max(1, intval($a['paged']));
				}
				$date = array();
				if (!empty($a['after'])) {
					$date['after'] = $a['after'];
				}
				if (!empty($a['before'])) {
					$date['before'] = $a['before'];
				}
				if ($date) {
					$q['date_query'] = array($date);
				}
				$rows = array();
				foreach (get_posts($q) as $p) {
					$rows[] = array(
						'ID' => $p->ID,
						'post_title' => $p->post_title,
						'post_status' => $p->post_status,
						'post_excerpt' => $this->postExcerpt($p),
						'permalink' => get_permalink($p),
					);
				}
				$this->addResultText($r, wp_json_encode($rows, JSON_PRETTY_PRINT));
				break;
			case 'wp_get_post':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$p = get_post(intval($a['ID']));
				if (!$p) {
					$r['error'] = array('code' => -42600, 'message' => 'Post not found');
					break;
				}
				$out = array(
					'ID' => $p->ID,
					'post_title' => $p->post_title,
					'post_status' => $p->post_status,
					'post_content' => $this->cleanHtml($p->post_content),
					'post_excerpt' => $this->postExcerpt($p),
					'permalink' => get_permalink( $p ),
					'post_date' => $p->post_date,
					'post_modified' => $p->post_modified,
				);
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_create_post':
				if (empty($a['post_title'])) {
					$r['error'] = array('code' => -42602, 'message' => 'post_title required');
					break;
				}
				$ins = array(
					'post_title' => sanitize_text_field($a['post_title']),
					'post_status' => sanitize_key(WaicUtils::getArrayValue($a, 'post_status', 'draft')),
					'post_type' => sanitize_key(WaicUtils::getArrayValue($a, 'post_type', 'post')),
				);
				if (!empty($a['post_content'])) {
					$ins['post_content'] = WaicUtils::markdownToHtml($a['post_content']);
				}
				if (!empty($a['post_excerpt'])) {
					$ins['post_excerpt'] = $this->cleanHtml($a['post_excerpt']);
				}
				if (!empty($a['post_name'])) {
					$ins['post_name'] = sanitize_title($a['post_name']);
				}
				if (!empty($a['meta_input']) && is_array($a['meta_input'])) {
					$ins['meta_input'] = $a['meta_input'];
				}
				$new = wp_insert_post($ins, true);
				if (is_wp_error($new)) {
					$r['error'] = array('code' => $new->get_error_code(), 'message' => $new->get_error_message());
				} else {
					if (empty($ins['meta_input']) && !empty($a['meta_input']) && is_array($a['meta_input'])) {
						foreach ($a['meta_input'] as $k => $v) {
							update_post_meta($new, sanitize_key($k), maybe_serialize($v));
						}
					}
					$this->addResultText($r, 'Post created ID ' . $new);
				}
				break;
			case 'wp_update_post':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$c = array('ID' => intval($a['ID']));
				if (!empty($a['fields']) && is_array($a['fields'])) {
					foreach ($a['fields'] as $k => $v) {
						$c[$k] = in_array($k, array('post_content', 'post_excerpt'), true) ? $this->cleanHtml($v) : sanitize_text_field($v);
					}
				}
				$u = ( count($c) > 1 ) ? wp_update_post($c, true) : $c['ID'];
				if (is_wp_error($u)) {
					$r['error'] = array('code' => $u->get_error_code(), 'message' => $u->get_error_message());
					break;
				}
				if (!empty($a['meta_input']) && is_array($a['meta_input'])) {
					foreach ($a['meta_input'] as $k => $v) {
						update_post_meta($u, sanitize_key($k), maybe_serialize($v));
					}
				}
				$this->addResultText($r, 'Post #' . $u . ' updated');
				break;
			case 'wp_delete_post':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$del = wp_delete_post(intval($a['ID']), !empty($a['force']));
				if ($del) {
					$this->addResultText($r, 'Post #' . $a['ID'] . ' deleted');
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Deletion failed');
				}
				break;
			case 'wp_get_post_meta':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$pid = intval($a['ID']);
				$out = !empty($a['key']) ? get_post_meta($pid, sanitize_key($a['key']), true) : get_post_meta($pid);
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_update_post_meta':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$pid = intval($a['ID']);
				if (!empty($a['meta']) && is_array($a['meta'])) {
					foreach ($a['meta'] as $k => $v) {
						update_post_meta($pid, sanitize_key($k), maybe_serialize($v));
					}
				} elseif (isset($a['key'], $a['value'])) {
					update_post_meta($pid, sanitize_key($a['key']), maybe_serialize($a['value']));
				} else {
					$r['error'] = array('code' => -42602, 'message' => 'meta array or key/value required');
					break;
				}
				$this->addResultText($r, 'Meta updated for post #' . $pid);
				break;
			case 'wp_delete_post_meta':
				if (empty($a['ID']) || empty($a['key'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID & key required');
					break;
				}
				$pid = intval($a['ID']);
				$key = sanitize_key($a['key']);
				$done = isset($a['value']) ? delete_post_meta($pid, $key, maybe_serialize($a['value'])) : delete_post_meta($pid, $key);
				if ($done) {
					$this->addResultText($r, 'Meta deleted on post #' . $pid);
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Deletion failed');
				}
				break;
			case 'wp_set_featured_image':
				if (empty($a['post_id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'post_id required');
					break;
				}
				$postId = intval($a['post_id']);
				$mediaId = isset($a['media_id']) ? intval($a['media_id']) : 0;
				if ($mediaId) {
					$done = set_post_thumbnail($postId, $mediaId);
					if ($done) {
						$this->addResultText($r, 'Featured image set on post #' . $postId);
					} else {
						$r['error'] = array('code' => -42603, 'message' => 'Failed to set thumbnail');
					}
				} else {
					delete_post_thumbnail($postId);
					$this->addResultText($r, 'Featured image removed from post #' . $postId);
				}
				break;
			case 'wp_get_taxonomies':
				$pt = sanitize_key(WaicUtils::getArrayValue($a, 'post_type', 'post'));
				$out = array();
				foreach (get_object_taxonomies($pt, 'objects') as $t) {
					$out[] = array('key' => $t->name, 'label' => $t->label);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_get_terms':
				if (empty($a['taxonomy'])) {
					$r['error'] = array('code' => -42602, 'message' => 'taxonomy required');
					break;
				}
				$tax = sanitize_key($a['taxonomy']);
				$args = array(
					'taxonomy' => $tax,
					'hide_empty' => false,
					'number' => intval(WaicUtils::getArrayValue($a, 'limit', 0, 1)),
					'search' => WaicUtils::getArrayValue($a, 'search'),
				);
				if (isset($a['parent'])) {
					$args['parent'] = intval($a['parent']);
				}
				$out = array();
				foreach (get_terms($args) as $t) {
					$out[] = array('term_id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'count' => $t->count);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_create_term':
				if (empty($a['term_name'])) {
					$r['error'] = array('code' => -42602, 'message' => 'term_name required');
					break;
				}
				$tax = sanitize_key($a['taxonomy']);
				$args = array();
				if (!empty($a['slug'])) {
					$args['slug'] = sanitize_title($a['slug']);
				}
				if (!empty($a['description'])) {
					$args['description'] = sanitize_text_field($a['description']);
				}
				if (isset($a['parent'])) {
					$args['parent'] = intval($a['parent']);
				}
				$term = wp_insert_term(sanitize_text_field($a['term_name']), $tax, $args);
				if (is_wp_error($term)) {
					$r['error'] = array('code' => $term->get_error_code(), 'message' => $term->get_error_message());
				} else {
					$this->addResultText($r, 'Term ' . $term['term_id'] . ' created');
				}
				break;
			case 'wp_update_term':
				$tid = intval(WaicUtils::getArrayValue($a, 'term_id', 0, 1));
				if (!$tid || empty($a['taxonomy'])) {
					$r['error'] = array('code' => -42602, 'message' => 'term_id && taxonomy required');
					break;
				}
				$tax = sanitize_key($a['taxonomy']);
				$uargs = array();
				foreach (array('name', 'slug', 'description', 'parent') as $f) {
					if (isset($a[$f])) {
						$uargs[$f] = $a[$f];
					}
				}
				$t = wp_update_term($tid, $tax, $uargs);
				if (is_wp_error($t)) {
				$r['error'] = array('code' => $t->get_error_code(), 'message' => $t->get_error_message());
				} else {
					$this->addResultText($r, 'Term ' . $tid . ' updated');
				}
				break;
			case 'wp_delete_term':
				$tid = intval(WaicUtils::getArrayValue($a, 'term_id', 0, 1));
				if (!$tid || empty($a['taxonomy'])) {
					$r['error'] = array('code' => -42602, 'message' => 'term_id & taxonomy required');
					break;
				}
				$tax = sanitize_key($a['taxonomy']);
				$d = wp_delete_term($tid, $tax);
				if ($d) {
					$this->addResultText($r, 'Term ' . $tid . ' deleted');
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Deletion failed');
				}
				break;
			case 'wp_get_post_terms':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$tax = sanitize_key(WaicUtils::getArrayValue($a, 'taxonomy', 'category'));
				$out = array();
				foreach (wp_get_post_terms(intval($a['ID']), $tax, array('fields' => 'all')) as $t) {
					$out[] = array('term_id' => $t->term_id, 'name' => $t->name);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'wp_add_post_terms':
				if (empty($a['ID']) || empty($a['terms'])) {
					$r['error'] = array('code' => -32602, 'message' => 'ID & terms required');
					break;
				}
				$tax = sanitize_key(WaicUtils::getArrayValue($a, 'taxonomy', 'category'));
				$append = !isset($a['append']) || $a['append'];
				$set = wp_set_post_terms(intval($a['ID']), $a['terms'], $tax, $append);
				if (is_wp_error($set)) {
					$r['error'] = array('code' => $set->get_error_code(), 'message' => $set->get_error_message());
				} else {
					$this->addResultText($r, 'Terms set for post #' . $a['ID']);
				}
				break;
			case 'wp_get_media':
				$q = array(
					'post_type' => 'attachment',
					's' => WaicUtils::getArrayValue($a, 'search'),
					'posts_per_page' => max(1, intval(WaicUtils::getArrayValue($a, 'limit', 10, 1))),
					'post_status' => 'inherit',
				);
				$d = array();
				if (!empty($a['after'])) {
					$d['after'] = $a['after'];
				}
				if (!empty($a['before'])) {
					$d['before'] = $a['before'];
				}
				if ($d) {
					$q['date_query'] = array($d);
				}
				$list = array();
				foreach (get_posts($q) as $m) {
					$list[] = array('ID' => $m->ID, 'title' => $m->post_title, 'url' => wp_get_attachment_url($m->ID));
				}
				$this->addResultText($r, wp_json_encode($list, JSON_PRETTY_PRINT));
				break;
			case 'wp_upload_media':
				if (empty($a['url'])) {
					$r['error'] = array('code' => -42602, 'message' => 'url required');
					break;
				}
				try {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$tmp = download_url($a['url']);
					if (is_wp_error($tmp)) {
						throw new Exception($tmp->get_error_message(), $tmp->get_error_code());
					}
					$file = array('name' => basename(parse_url($a['url'], PHP_URL_PATH)), 'tmp_name' => $tmp);
					$id = media_handle_sideload($file, 0, WaicUtils::getArrayValue($a, 'description'));
					@unlink( $tmp );
					if (is_wp_error($id)) {
						throw new Exception($id->get_error_message(), $id->get_error_code());
					}
					if (!empty($a['title'])) {
						wp_update_post(array('ID' => $id, 'post_title' => sanitize_text_field($a['title'])));
					}
					if (!empty($a['alt'])) {
						update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($a['alt']));
					}
					$this->addResultText($r, wp_get_attachment_url($id));
				}
				catch ( \Throwable $e ) {
					$r['error'] = array('code' => $e->getCode() ?: -42603, 'message' => $e->getMessage());
				}
				break;
			case 'wp_update_media':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$upd = array('ID' => intval($a['ID']));
				if (!empty($a['title'])) {
					$upd['post_title'] = sanitize_text_field($a['title']);
				}
				if (!empty($a['caption'])) {
					$upd['post_excerpt'] = $this->cleanHtml($a['caption']);
				}
				if (!empty($a['description'])) {
					$upd['post_content'] = $this->cleanHtml($a['description']);
				}
				$u = wp_update_post($upd, true);
				if (is_wp_error($u)) {
					$r['error'] = array('code' => $u->get_error_code(), 'message' => $u->get_error_message());
				} else {
					if (!empty($a['alt'])) {
						update_post_meta($u, '_wp_attachment_image_alt', sanitize_text_field($a['alt']));
					}
					$this->addResultText($r, 'Media #' . $u . ' updated');
				}
				break;
			case 'wp_delete_media':
				if (empty($a['ID'])) {
					$r['error'] = array('code' => -42602, 'message' => 'ID required');
					break;
				}
				$d = wp_delete_post(intval($a['ID']), !empty($a['force']));
				if ($d) {
					$this->addResultText($r, 'Media #' . $a['ID'] . ' deleted');
				} else {
					$r['error'] = array('code' => -42603, 'message' => 'Deletion failed');
				}
				break;
			case 'aiwu_image':
				if (empty($a['message'])) {
					$r['error'] = array('code' => -42602, 'message' => 'message required');
					break;
				}
				$frame = WaicFrame::_();
				$apiOptions = $frame->getModule('options')->getModel()->getDefaults('api');
				$aiProvider = $frame->getModule('workspace')->getModel('aiprovider')->getInstance($apiOptions);
				if (!$aiProvider) {
					$r['error'] = array('code' => -42605, 'message' => 'not found AI provider');
					break;
				}
				$aiProvider->init();
				$aiProvider->setSaveError(false);
				$mid = 0;
				if ($aiProvider->setApiOptions(array())) {
					$opts = array('prompt' => $a['message']);
					$result = $aiProvider->getImage($opts);
					if ($result['error']) {
						$r['error'] = array('code' => -42605, 'message' => $result['msg']);
						break;
					}
					$path = empty($result['data']) ? '' : $result['data'];
					if ($path) {
						$mid = $frame->getModule('postscreate')->getModel()->saveImage(htmlspecialchars_decode($path, ENT_QUOTES), 'AIWU generated Image');
					} else {
						$r['error'] = array('code' => -42605, 'message' => 'Error by image creation');
						break;
					}
				} else {
					$r['error'] = array('code' => -42605, 'message' => $frame->getLastError());
					break;
				}
				if (empty($mid)) {
					$r['error'] = array('code' => -42605, 'message' => 'Error by image saving');
					break;
				}
				$upd = array('ID' => $mid);
				if (!empty($a['title'])) {
					$upd['post_title'] = sanitize_text_field($a['title']);
				}
				if (!empty($a['caption'])) {
					$upd['post_excerpt'] = $this->cleanHtml($a['caption']);
				}
				if (!empty($a['description'])) {
					$upd['post_content'] = $this->cleanHtml($a['description']);
				}
				if (count($upd) > 1) {
					wp_update_post($upd, true);
				}
				if (array_key_exists('alt', $a)) {
					update_post_meta($mid, '_wp_attachment_image_alt', sanitize_text_field((string) $a['alt']));
				}
				$media = array(
					'id' => $mid,
					'url' => wp_get_attachment_url($mid),
					'title' => get_the_title($mid),
					'caption' => wp_get_attachment_caption($mid),
					'alt' => get_post_meta($mid, '_wp_attachment_image_alt', true),
				);
				$this->addResultText($r, wp_json_encode($media, JSON_PRETTY_PRINT));
				break;
			case 'search':
				if (empty($a['query'])) {
					$r['error'] = array('code' => -42602, 'message' => 'query required');
					break;
				}
				$query = sanitize_text_field($a['query']);
				$args = array(
					's' => $query,
					'post_type' => array('post', 'page'),
					'post_status' => 'publish',
					'posts_per_page' => 20,
					'orderby' => 'relevance',
					'order' => 'DESC',
				);
				$searchQuery = new WP_Query($args);
				$results = [];

				if ($searchQuery->have_posts()) {
					while ($searchQuery->have_posts()) {
						$searchQuery->the_post();
						$post = get_post();
						$results[] = array(
							'id' => (string) $post->ID,
							'title' => get_the_title(),
							'text' => wp_trim_words(wp_strip_all_tags($post->post_content), 100),
							'url' => get_permalink(),
						);
					}
					wp_reset_postdata();
				}
				$this->addResultText($r, wp_json_encode($results, JSON_PRETTY_PRINT));
				// Return results in OpenAI's expected format
				// We need to return the raw result structure for OpenAI
				/*return array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'result' => array('results' => $results),
				);*/
				break;
			case 'fetch':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$post_id = intval($a['id']);
				$post = get_post($post_id);
				if (!$post || 'publish' !== $post->post_status) {
					$r['error'] = array('code' => -42603, 'message' => 'Resource not found or not published');
					break;
				}
				$content = apply_filters('the_content', $post->post_content);
				$content = wp_strip_all_tags( $content );
				$metadata = array(
					'author' => get_the_author_meta('display_name', $post->post_author),
					'date' => get_the_date( 'Y-m-d', $post ),
					'modified' => get_the_modified_date('Y-m-d', $post),
					'type' => $post->post_type,
				);
				if ('post' === $post->post_type) {
					$categories = wp_get_post_categories($post_id, array('fields' => 'names'));
					if (!empty($categories)) {
						$metadata['categories'] = implode(', ', $categories);
					}
					$tags = wp_get_post_tags($post_id, array('fields' => 'names'));
					if (!empty($tags)) {
							$metadata['tags'] = implode(', ', $tags);
					}
				}
				// Return in OpenAI's expected format
				// We need to return the raw result structure for OpenAI
				$result = array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'result' => array(
						'id' => (string) $post_id,
						'title' => get_the_title($post),
						'text' => $content,
						'url' => get_permalink($post),
						'metadata' => $metadata,
					),
				);
				$this->addResultText($r, wp_json_encode($result, JSON_PRETTY_PRINT));
				break;
				// === WORKFLOW TOOLS ===
			case 'workflow_list':
				$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
				$where = array('feature' => 'workflow');
				$statusFilter = WaicUtils::getArrayValue($a, 'status', 'all');
				if ('active' === $statusFilter) {
					$where['status'] = 4;
				} elseif ('draft' === $statusFilter) {
					$where['status'] = 0;
				} elseif ('stopped' === $statusFilter) {
					$where['status'] = 6;
				}
				$limit = max(1, intval(WaicUtils::getArrayValue($a, 'limit', 20, 1)));
				$tasks = WaicFrame::_()->getTable('tasks')->get('id, title, status, mode, created, modified', $where, '', 'id DESC', $limit);
				$statuses = $taskModel->getStatuses();
				$out = array();
				foreach ($tasks as $t) {
					$out[] = array(
						'id' => (int) $t['id'],
						'title' => $t['title'],
						'status' => (int) $t['status'],
						'status_label' => isset($statuses[$t['status']]) ? $statuses[$t['status']] : 'Unknown',
						'version' => (int) $t['mode'],
						'created' => $t['created'],
						'modified' => $t['modified'],
					);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'workflow_get':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
				$task = $taskModel->getTask((int) $a['id']);
				if (!$task || 'workflow' !== $task['feature']) {
					$r['error'] = array('code' => -42600, 'message' => 'Workflow not found');
					break;
				}
				$params = is_array($task['params']) ? $task['params'] : WaicUtils::jsonDecode($task['params']);
				$out = array(
					'id' => (int) $task['id'],
					'title' => $task['title'],
					'status' => (int) $task['status'],
					'version' => (int) $task['mode'],
					'nodes' => WaicUtils::getArrayValue($params, 'nodes', array(), 2),
					'edges' => WaicUtils::getArrayValue($params, 'edges', array(), 2),
					'settings' => WaicUtils::getArrayValue($params, 'settings', array(), 2),
				);
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'workflow_blocks':
				$wfModel = WaicFrame::_()->getModule('workflow')->getModel();
				$blocks = $wfModel->getAllBlocksSettings();
				$typeFilter = WaicUtils::getArrayValue($a, 'type');
				if (!empty($typeFilter)) {
					$key = $typeFilter . 's';
					$blocks = isset($blocks[$key]) ? array($key => $blocks[$key]) : array();
				}
				$this->addResultText($r, wp_json_encode($blocks, JSON_PRETTY_PRINT));
				break;
			case 'workflow_create':
				if (empty($a['title']) || empty($a['nodes']) || empty($a['edges'])) {
					$r['error'] = array('code' => -42602, 'message' => 'title, nodes, edges required');
					break;
				}
				$wfModel = WaicFrame::_()->getModule('workflow')->getModel();
				$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
				$params = array(
					'task_title' => sanitize_text_field($a['title']),
					'nodes' => $a['nodes'],
					'edges' => $a['edges'],
					'settings' => WaicUtils::getArrayValue($a, 'settings', array(), 2),
				);
				$error = '';
				$errNodes = array();
				if (!$wfModel->controlTaskParameters($params, $error, $errNodes, 0)) {
					$r['error'] = array('code' => -42604, 'message' => $error, 'nodes' => $errNodes);
					break;
				}
				$params = $wfModel->convertTaskParameters($params);
				$taskId = $taskModel->saveTask('workflow', 0, $params);
				if (empty($taskId)) {
					$r['error'] = array('code' => -42603, 'message' => WaicFrame::_()->getLastError() ?: 'Failed to create workflow');
				} else {
					$this->addResultText($r, wp_json_encode(array('id' => $taskId, 'message' => 'Workflow created'), JSON_PRETTY_PRINT));
				}
				break;
			case 'workflow_publish':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$wfModel = WaicFrame::_()->getModule('workflow')->getModel();
				$status = $wfModel->publishResults((int) $a['id'], false, true);
				if (empty($status)) {
					$r['error'] = array('code' => -42603, 'message' => WaicFrame::_()->getLastError() ?: 'Failed to publish');
				} else {
					$this->addResultText($r, wp_json_encode(array('id' => (int) $a['id'], 'status' => $status, 'message' => 'Workflow published'), JSON_PRETTY_PRINT));
				}
				break;
			case 'workflow_stop':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$wfModel = WaicFrame::_()->getModule('workflow')->getModel();
				$status = $wfModel->unpublishEtaps((int) $a['id'], true);
				if (empty($status)) {
					$r['error'] = array('code' => -42603, 'message' => WaicFrame::_()->getLastError() ?: 'Failed to stop');
				} else {
					$this->addResultText($r, wp_json_encode(array('id' => (int) $a['id'], 'status' => $status, 'message' => 'Workflow stopped'), JSON_PRETTY_PRINT));
				}
				break;
			case 'workflow_delete':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$taskId = (int) $a['id'];
				$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
				$task = $taskModel->getTask($taskId);
				if (!$task || 'workflow' !== $task['feature']) {
					$r['error'] = array('code' => -42600, 'message' => 'Workflow not found');
					break;
				}
				$wfModel = WaicFrame::_()->getModule('workflow')->getModel();
				$wfModel->unpublishEtaps($taskId, false);
				$wfModel->clearEtaps($taskId);
				WaicFrame::_()->getTable('tasks')->delete(array('id' => $taskId));
				$this->addResultText($r, wp_json_encode(array('id' => $taskId, 'message' => 'Workflow deleted'), JSON_PRETTY_PRINT));
				break;
			case 'workflow_runs':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$taskId = (int) $a['id'];
				$limit = max(1, intval(WaicUtils::getArrayValue($a, 'limit', 20, 1)));
				$runsModel = WaicFrame::_()->getModule('workflow')->getModel('flowruns');
				$statuses = $runsModel->getStatuses();
				$where = 'task_id=' . $taskId;
				$runs = WaicDb::get('SELECT id, fl_id, status, obj_id, tokens, added, started, ended, error FROM `@__flowruns` WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $limit);
				$out = array();
				foreach ($runs as $run) {
					$out[] = array(
						'run_id' => (int) $run['id'],
						'flow_id' => (int) $run['fl_id'],
						'status' => (int) $run['status'],
						'status_label' => isset($statuses[$run['status']]) ? $statuses[$run['status']] : 'Unknown',
						'obj_id' => (int) $run['obj_id'],
						'tokens' => (int) $run['tokens'],
						'added' => $run['added'],
						'started' => $run['started'],
						'ended' => $run['ended'],
						'error' => $run['error'],
					);
				}
				$this->addResultText($r, wp_json_encode($out, JSON_PRETTY_PRINT));
				break;
			case 'workflow_run_manual':
				if (empty($a['id'])) {
					$r['error'] = array('code' => -42602, 'message' => 'id required');
					break;
				}
				$taskId = (int) $a['id'];
				$taskModel = WaicFrame::_()->getModule('workspace')->getModel('tasks');
				$task = $taskModel->getTask($taskId);
				if (!$task || 'workflow' !== $task['feature']) {
					$r['error'] = array('code' => -42600, 'message' => 'Workflow not found');
					break;
				}
				if ($task['status'] != 4) {
					$r['error'] = array('code' => -42604, 'message' => 'Workflow must be published (status=4) to run manually');
					break;
				}
				$flows = WaicDb::get('SELECT id FROM `@__workflows` WHERE task_id=' . $taskId . ' AND status=1 AND tr_type=0 LIMIT 1', 'row');
				if (empty($flows)) {
					$r['error'] = array('code' => -42604, 'message' => 'No active manual trigger found for this workflow');
					break;
				}
				$flId = (int) $flows['id'];
				$runsModel = WaicFrame::_()->getModule('workflow')->getModel('flowruns');
				$params = WaicUtils::getArrayValue($a, 'params', array(), 2);
				$params['date'] = date('Y-m-d');
				$params['time'] = date('H:i:s');
				$runId = $runsModel->createRun($taskId, $flId, $params);
				if (empty($runId)) {
					$r['error'] = array('code' => -42603, 'message' => 'Failed to create run');
				} else {
					WaicFrame::_()->getModule('workflow')->getModel()->doFlowRuns();
					$this->addResultText($r, wp_json_encode(array('run_id' => $runId, 'message' => 'Workflow triggered'), JSON_PRETTY_PRINT));
				}
				break;
			default: $r['error'] = array('code' => -42609, 'message' => 'Unknown tool');
		}
		return $r;
	}
}