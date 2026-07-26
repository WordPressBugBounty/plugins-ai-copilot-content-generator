<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WaicTableHistory extends WaicTable {
	public function __construct() {
		$this->_table = '@__history';
		$this->_id = 'id';     /*Let's associate it with posts*/
		$this->_alias = 'waic_history';
		$this->_addField('id', 'text', 'int')
			 ->_addField('task_id', 'text', 'int')
			 ->_addField('feature', 'text', 'text')
			 ->_addField('operation', 'text', 'text')
			 ->_addField('user_id', 'text', 'int')
			 ->_addField('session_id', 'text', 'text')
			 ->_addField('ip', 'text', 'text')
			 ->_addField('engine', 'text', 'text')
			 ->_addField('model', 'text', 'text')
			 ->_addField('mode', 'text', 'int')
			 ->_addField('created', 'text', 'text')
			 ->_addField('status', 'text', 'int')
			 ->_addField('duration_ms', 'text', 'int')
			 ->_addField('tokens', 'text', 'int')
			 ->_addField('input_tokens', 'text', 'int')
			 ->_addField('output_tokens', 'text', 'int')
			 ->_addField('reasoning_tokens', 'text', 'int')
			 ->_addField('cached_tokens', 'text', 'int')
			 ->_addField('cache_write_tokens', 'text', 'int')
			 ->_addField('cost', 'text', 'decimal')
			 ->_addField('cost_micro_usd', 'text', 'int')
			 ->_addField('est_flags', 'text', 'int')
			 ->_addField('meta', 'text', 'text')
			 ->_addField('best_score_x1000', 'text', 'int')
			 ->_addField('tool_calls_count', 'text', 'int');
	}
}
