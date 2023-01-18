<?php

namespace PedLibraries;

abstract class Table {

	const TABLE_NAME = '';

	protected static $instance = [];
	private $db = null;
	private $table;

	private function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $this->db->prefix . static::TABLE_NAME;
	}

	public static function getInstance() {
		$class = get_called_class();

		if ( ! isset ( self::$instance[ $class ] ) ) {
			self::$instance[ $class ] = new $class();
		}

		return self::$instance[ $class ];
	}

	public static function init() {
		add_action( 'after_switch_theme', [ get_called_class(), 'createTable' ] );
	}

	abstract public static function createTable();

	/**
	 * Insert data into the current data
	 *
	 * @param array $data - Data to enter into the database table
	 *
	 * @return int Object
	 *
	 * $table->insert( array('id' => 1, 'name' => 'John') );
	 */

	public function insert( array $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$this->db->insert( $this->table, $data );

		return $this->db->insert_id;
	}

	/**
	 * Get all from the selected table
	 *
	 * @param String $orderBy - Order by column name
	 *
	 * @return object Table
	 */

	public function get_all( $orderBy = null ) {
		$sql = 'SELECT * FROM `' . $this->table . '`';

		if ( ! empty( $orderBy ) ) {
			$sql .= ' ORDER BY ' . $orderBy;
		}

		$all = $this->db->get_results( $sql );

		return $all;
	}

	/**
	 * Get a value by a condition
	 *
	 * @param Array $conditionValue - A key value pair of the conditions you want to search on
	 * @param String $condition - A string value for the condition of the query default to equals
	 *
	 * @return mixed object|false
	 */

	public function get_by( array $conditionValue, $condition = '=', $returnSingleRow = false, $select = '*', $limit = 50 ) {
		try {
			$sql = 'SELECT ' . $select . ' FROM `' . $this->table . '` WHERE ';

			$conditionCounter = 1;
			foreach ( $conditionValue as $field => $value ) {
				if ( $conditionCounter > 1 ) {
					$sql .= ' AND ';
				}

				switch ( strtolower( $condition ) ) {
					case 'in':
						if ( ! is_array( $value ) ) {
							throw new \Exception( "Values for IN query must be an array.", 1 );
						}

						$sql .= $this->db->prepare( '`%s` IN (%s)', $field, implode( ',', $value ) );
						break;

					default:
						$sql .= $this->db->prepare( '`' . $field . '` ' . $condition . ' %s', $value );
						break;
				}

				$conditionCounter ++;
			}

			$sql    .= $this->db->prepare( 'LIMIT %d', $limit );
			$result = $this->db->get_results( $sql );

			// As this will always return an array of results
			// if you only want to return one record make $returnSingleRow TRUE
			if ( count( $result ) == 1 && $returnSingleRow ) {
				$result = $result[0];
			}

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}


	/**
	 * Update a table record in the database
	 *
	 * @param array $data - Array of data to be updated
	 * @param array $conditionValue - Key value pair for the where clause of the query
	 *
	 * $updated = $table->update( array('name' => 'Fred'), array('name' => 'John') );
	 *
	 * @return object|false updated
	 */

	public function update( array $data, array $conditionValue ) {
		if ( empty( $data ) ) {
			return false;
		}

		$updated = $this->db->update( $this->table, $data, $conditionValue );

		return $updated;
	}

	/**
	 * Delete row on the database table
	 *
	 * @param array $conditionValue - Key value pair for the where clause of the query
	 *
	 * @return Int - Num rows deleted
	 */

	public function delete( array $conditionValue ) {
		$deleted = $this->db->delete( $this->table, $conditionValue );

		return $deleted;
	}

	/**
	 * Get $wpdb object
	 *
	 * @return \wpdb
	 */
	public function getDB() {
		return $this->db;
	}

	/**
	 * Get the table name
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
}
