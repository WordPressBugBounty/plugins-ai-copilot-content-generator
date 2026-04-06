<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Shell - class to work with $wpdb global object
 */
class WaicDb {
	public static $prepareQ = false;
	/**
	 * Execute query and return results
	 *
	 * @param string $query query to be executed
	 * @param string $get what must be returned - one value (one), one row (row), one col (col) or all results (all - by default)
	 * @param const $outputType type of returned data
	 * @return mixed data from DB
	 */
	public static $query = '';
	public static function get( $query, $get = 'all', $outputType = ARRAY_A, $args = array() ) {
		global $wpdb;
		$get = strtolower($get);
		$res = null;
		$query = self::prepareQuery($query, $args);
		self::$query = $query;
		$wpdb->waic_prepared_query = $wpdb->prepare($query, $args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		switch ($get) {
			case 'one':
				$res = $wpdb->get_var($wpdb->waic_prepared_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				break;
			case 'row':
				$res = $wpdb->get_row($wpdb->waic_prepared_query, $outputType); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				break;
			case 'col':
				$res = $wpdb->get_col($wpdb->waic_prepared_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				break;
			case 'all':
			default:
				$res = $wpdb->get_results($wpdb->waic_prepared_query, $outputType); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				break;
		}
		return $res;
	}
	/**
	 * Execute one query
	 *
	 * @return query results
	 */
	public static function query( $query, $affected = false, $args = array(1) ) {
		global $wpdb;
		$wpdb->waic_prepared_query = self::prepareQuery($query, $args);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $affected ? $wpdb->query($wpdb->waic_prepared_query) : ( $wpdb->query($wpdb->waic_prepared_query) === false ? false : true );
	}
	/**
	 * Get last insert ID
	 *
	 * @return int last ID
	 */
	public static function insertID() {
		global $wpdb;
		return $wpdb->insert_id;
	}
	/**
	 * Get number of rows returned by last query
	 *
	 * @return int number of rows
	 */
	public static function numRows() {
		global $wpdb;
		return $wpdb->num_rows;
	}
	/**
	 * Replace prefixes in custom query. Suported next prefixes:
	 * #__  Wordpress prefix
	 * ^__  Store plugin tables prefix (@see WAIC_DB_PREF if config.php)
	 *
	 * @__  Compared of WP table prefix + Store plugin prefix (@example wp_s_)
	 * @param string $query query to be executed
	 */
	public static function prepareQuery( $query, &$args = array(1) ) {
		global $wpdb;
		if (self::$prepareQ) {
			$query = $wpdb->prepare($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		if (empty($args)) {
			$args = array(1);
			$found = false;
			$q = strtolower($query);
			$where = strpos($q, ' where ') !== false;
			
			if (strpos($q, ' where ') !== false) {
				if (strpos($query, ' WHERE ') !== false) {
					$query = str_replace(' WHERE ', ' WHERE 1=%d AND ', $query);
				} else if (strpos($query, ' where ') !== false) {
					$query = str_replace(' where ', ' where 1=%d AND ', $query);
				}
			} else {
				$elements = array(' group by ' => ' GROUP BY ', ' order by ' => ' ORDER BY ', ' limit ' => ' LIMIT ');
				foreach ($elements as $l => $u) {
					if (strpos($q, $l) !== false) {
						if (strpos($query, $u) !== false) {
							$query = str_replace($u, ' WHERE 1=%d' . $u, $query);
							$found = true;
						} else if (strpos($query, $l) !== false) {
							$query = str_replace($l, ' where 1=%d' . $l, $query);
							$found = true;
						}
					}
					if ($found) {
						break;
					}
				}
				if (!$found) {
					$query .= ' WHERE 1=%d';
				}
			}
		}
		return str_replace(
				array('#__', '^__', '@__'), 
				array($wpdb->prefix, WAIC_DB_PREF, $wpdb->prefix . WAIC_DB_PREF),
				$query);
	}
	public static function getError() {
		global $wpdb;
		return $wpdb->last_error;
	}
	public static function lastID() {
		global $wpdb;        
		return $wpdb->insert_id;
	}
	public static function timeToDate( $timestamp = 0 ) {
		if ($timestamp) {
			if (!is_numeric($timestamp)) {
				$timestamp = waicDateToTimestamp($timestamp);
			}
			return gmdate('Y-m-d', $timestamp);
		} else {
			return gmdate('Y-m-d');
		}
	}
	public static function dateToTime( $date ) {
		if (empty($date)) {
			return '';
		}
		if (strpos($date, WAIC_DATE_DL)) {
			return waicDateToTimestamp($date);
		}
		$arr = explode('-', $date);
		return waicDateToTimestamp($arr[2] . WAIC_DATE_DL . $arr[1] . WAIC_DATE_DL . $arr[0]);
	}
	public static function exist( $table, $column = '', $value = '' ) {
		$table = self::controlTableName($table);
		if (empty($column) && empty($value)) {       //Check if table exist
			$args = array(1);
			$res = self::get('SHOW TABLES LIKE %s', 'one', ARRAY_A, array(self::prepareQuery($table, $args)));
		} elseif (empty($value)) {                   //Check if column exist
			$res = self::get('SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'one', ARRAY_A, array($column));
		} else {                                    //Check if value in column table exist
			$res = self::get("SELECT COUNT(*) AS total FROM `{$table}` WHERE {$column} = %s", 'one', ARRAY_A, array($value));
		}
		return !empty($res);
	}
	public static function prepareHtml( $d ) {
		if (is_array($d)) {
			foreach ($d as $i => $el) {
				$d[ $i ] = self::prepareHtml( $el );
			}
		} else {
			$d = esc_html($d);
		}
		return $d;
	}
	public static function prepareHtmlIn( $d ) {
		if (is_array($d)) {
			foreach ($d as $i => $el) {
				$d[ $i ] = self::prepareHtml( $el );
			}
		} else {
			$d = wp_filter_nohtml_kses($d);
		}
		return $d;
	}
	public static function escape( $data ) {
		global $wpdb;
		return $wpdb->_escape($data);
	}
	public static function getTableColumns( $table ) {
		$table = self::controlTableName($table);
		return self::get("SHOW COLUMNS FROM {$table}");
	}
	public static function getAutoIncrement( $table ) {
		$table = self::controlTableName($table);
		return (int) self::get('SELECT AUTO_INCREMENT
			FROM information_schema.tables
			WHERE table_name = %s
			AND table_schema = DATABASE( );', 'one', ARRAY_A, array($table));
	}
	public static function setAutoIncrement( $table, $autoIncrement ) {
		$table = self::controlTableName($table);
		return self::query("ALTER TABLE `{$table}` AUTO_INCREMENT = %d", false, array($autoIncrement));
	}
	public static function createTemporaryTable( $table, $sql, $strusture = false ) {
		$resultTable = $table;
		if (!self::query('DROP TEMPORARY TABLE IF EXISTS ' . $table )) {
			return false;
		}
		if (!empty($sql)) {
			$sql = str_replace('SQL_CALC_FOUND_ROWS', '', $sql);
			$orderPos = strpos($sql, 'ORDER');
			if ($orderPos) {
				$sql = substr($sql, 0, $orderPos);
			}
		}
		$query = 'CREATE TEMPORARY TABLE ' . $table .
			' (' . ( $strusture ? $strusture : 'index my_pkey (id)' ) . ')' .
			( empty($sql) ? '' : ' AS ' . $sql );
		if (self::query($query, false) === false ) {
			$resultTable = empty($sql) ? false : '(' . $sql . ')';
		}

		return $resultTable;
	}
	public static function existsTableColumn( $table, $column ) {
		$table = self::controlTableName($table);
		return self::get("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name=%s AND table_schema=DATABASE( ) AND column_name=%s", 'one', ARRAY_A, array($table, $column)) == 1;
	}
	public static function controlTableName( $table ) {
		global $wpdb;
		$table = str_replace(
			array('#__', '^__', '@__'), 
			array($wpdb->prefix, WAIC_DB_PREF, $wpdb->prefix . WAIC_DB_PREF),
			$table);
		return sanitize_key($table);
	}
}
