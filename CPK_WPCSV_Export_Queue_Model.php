<?php

if ( !class_exists( 'CPK_WPCSV_Export_Queue_Model' ) ) {

class CPK_WPCSV_Export_Queue_Model {

	private $db;
	private $table_name;

	const TABLE_SUFFIX = 'cpk_wpcsv_export_queue';

	public function __construct( ) {
		global $wpdb;
		$this->db = $wpdb;

		$this->table_name = $this->db->prefix . self::TABLE_SUFFIX;
		if ( !$this->table_exists( $this->table_name ) ) {
			$this->create_table( $this->table_name );
		}
	}

	private function table_exists( $name ) {
		$sql = "SHOW TABLES LIKE '{$this->table_name}'";
		return (boolean)$this->db->get_results( $sql );
	}

	private function create_table( $name ) {
		$sql =	"
			CREATE TABLE IF NOT EXISTS `{$name}` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`post_id` int(11) NOT NULL,
			`done` boolean NOT NULL DEFAULT 0,
			`msg` varchar(255) NULL,
			PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;
			";

		$this->db->query( $sql );
	}

	public function empty_table( ) {
		$sql =	"TRUNCATE TABLE `{$this->table_name}`";
		$this->db->query( $sql );
	}	
	
	public function drop_table( ) {
		$sql =	"DROP TABLE `{$this->table_name}`";
		$this->db->query( $sql );
	}

	private function wrap_post_ids( &$element ) {
		$element = "('{$element}')";
	}

	public function add_post_ids( Array $post_ids ) {
		if ( !empty( $post_ids ) ) {
			array_walk( $post_ids, Array( $this, 'wrap_post_ids' ) );
			$post_id_sql = implode( ',', $post_ids );
			$sql = "INSERT INTO {$this->table_name} ( `post_id` ) VALUES {$post_id_sql}";
			$this->db->query( $sql );
		}
	}

	public function get_post_id_list( $limit = 100 ) {
		$sql =	"SELECT post_id FROM {$this->table_name} WHERE done = '0' LIMIT {$limit}";
		return $this->db->get_col( $sql );
	}

	public function get_count( ) {
		$sql =	"SELECT COUNT(*) FROM {$this->table_name}";
		return $this->db->get_var( $sql );
	}

	public function mark_done( Array $post_ids ) {
		$post_id_list = implode( ',', $post_ids );
		$sql =	"UPDATE {$this->table_name} SET done = 1 WHERE post_id IN ( {$post_id_list} )";
		$this->db->query( $sql );
	}
	
} # End class CPK_WPCSV_Export_Queue_Model

} # End if
