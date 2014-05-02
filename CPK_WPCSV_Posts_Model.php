<?php

if ( !class_exists( 'CPK_WPCSV_Posts_Model' ) ) {

class CPK_WPCSV_Posts_Model {

	private $db;

	public function __construct( ) {
		global $wpdb;
		$this->db = $wpdb;
	}

	private function build_query( $fields, $post_type, $post_status, $post_id_list = NULL ) {
		$post_type_filter = ( $post_type ) ?  "AND post_type = '{$post_type}'" : '';
		$post_status_filter = ( $post_status ) ?  "AND post_status = '{$post_status}'" : '';
		$post_id_filter = ( isset( $post_id_list ) ) ? "AND ID IN ( " . implode( ',', $post_id_list ) . " )" : '';
		$sql = "SELECT DISTINCT {$fields} FROM {$this->db->posts} WHERE post_status in ('publish','future','private','draft', 'pending') {$post_type_filter} {$post_id_filter} {$post_status_filter} ORDER BY post_modified DESC";
		return $sql;
	}

	public function get_post_ids( $post_type = NULL, $post_status = NULL ) {
		$sql = $this->build_query( 'ID,post_modified', $post_type, $post_status );
		$results = mysql_query( $sql, $this->db->dbh );
		$post_ids = Array( );
		if ( $results ) {
			while ( $result = mysql_fetch_array( $results, MYSQL_ASSOC ) ) {
				$post_ids[] = (int)$result['ID'];
			} # End while
			mysql_free_result( $results );
		}

		return $post_ids;
	}

	public function get_posts( Array $fields, $post_type = NULL, $post_status = NULL, $post_ids = Array( ) ) {
		$field_list = '`' . implode( '`,`', $fields ) . '`';
		$sql = $this->build_query( $field_list, $post_type, $post_status, $post_ids );
		$results = $this->db->get_results( $sql, ARRAY_A );
		return (Array)$results;
	}

	public function get_custom_field_list( $all = FALSE ) {
		$sql = "SELECT DISTINCT meta_key FROM {$this->db->postmeta}";
		if ( !$all ) $sql .= " WHERE meta_key NOT LIKE '\_%'";
		return $this->db->get_col( $sql );
	}
	
	public function get_custom_field_by_post_id( $post_id ) {
		$sql = "SELECT DISTINCT meta_key, meta_value FROM {$this->db->postmeta}";
		$sql .= " WHERE post_id = '{$post_id}'";
		$results = $this->db->get_results( $sql, ARRAY_A );
		
		if ( is_array( $results ) && !empty( $results ) ) {
			foreach( $results as $result ) {
				$custom_field_values[ $result['meta_key'] ] = $result['meta_value'];
			} # End foreach
		} # End if

		return $custom_field_values;
	}

	public function get_custom_field_values( $post_id, $field_list, $custom_field_list ) {

		$custom_field_values = Array( );

		$meta_values = $this->get_custom_field_by_post_id( $post_id );

		foreach ( $custom_field_list as $cf ) {
			if ( !in_array( $cf, $field_list ) ) continue;
			$val = ( isset( $meta_values[ $cf ] ) ) ? $meta_values[ $cf ] : '';
			if ( unserialize( $val ) && function_exists( 'json_encode' ) ) {
				$val = json_encode( unserialize( $val ) );
			}
			$custom_field_values[] = $val;
		}
		
		return $custom_field_values;
	}


	public function exclude_filter( Array $elements, Array $rules, Array $mandatory_fields ) {

		$filtered_array = $elements;

		if ( is_array( $elements ) && !empty( $elements ) ) {
			foreach( $elements as $key => $val ) {
				if ( $this->rule_match( $key, $rules ) ) {
					if ( !in_array( $key, $mandatory_fields ) ) unset( $filtered_array[ $key ] );
				} 
			} # End foreach
		} # End if

		return $filtered_array;
	}

	public function include_filter( Array $elements, Array $rules, Array $mandatory_fields ) {

		$filtered_array = Array( );
		
		if ( is_array( $elements ) && !empty( $elements ) ) {
			foreach( $elements as $key => $val ) {
				if ( $this->rule_match( $key, $rules ) || in_array( $key, $mandatory_fields ) ) {
					$filtered_array[ $key ] = $val;
				}
			} # End foreach
		} # End if

		return $filtered_array;

	}
	
	private function rule_match( $value, Array $rules ) {
		
		if ( is_array( $rules ) && !empty( $rules ) ) {
			foreach( $rules as $rule ) {
				if ( $rule == $value ) return TRUE;
				if ( $rule == '*' ) return TRUE; 
				if ( substr( $rule, 0, 1 ) == '*' && preg_match( '/' . substr( $rule, 1 ) . '$/', $value ) ) return TRUE;
				if ( substr( $rule, -1, 1 ) == '*' && preg_match( '/^' . substr( $rule, 0, -1 ) . '/', $value ) ) return TRUE;
			} # End foreach
		} # End if

	}

} # End class CPK_WPCSV_Posts_Model

} # End if
