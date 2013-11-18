<?php
/*
Plugin Name: WP CSV
Plugin URI: http://cpkwebsolutions.com/plugins/wp-csv
Description: A powerful, yet easy to use, CSV Importer/Exporter for Wordpress posts and pages. 
Version: 1.4.5
Author: CPK Web Solutions
Author URI: http://cpkwebsolutions.com

	LICENSE

	Copyright 2012  CPK Web Solutions  (email : paul@cpkwebsolutions.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Load libraries
require_once( 'pws_wpcsv_view.php' );
require_once( 'pws_wpcsv_csv.php' );
require_once( 'pws_wpcsv_engine.php' );

// Initialise main class
if ( !class_exists( 'pws_wpcsv' ) ) {

	class pws_wpcsv {

		var $view;
		var $csv;
		var $wpcsv;
		var $backup_url;
		var $settings;
		var $option_name = '_pws_wpcsv_settings';

		const ERROR_MISSING_POST_ID = 1;
		const ERROR_MISSING_POST_PARENT = 2;
		const ERROR_MISSING_AUTHOR = 3;
		const ERROR_INVALID_TAXONOMY_TERM = 4;

		function __construct( ) { // Constructor
			if ( !session_id( ) ) session_start( );
			$this->view = new pws_wpcsv_view( );
			$this->csv = new pws_wpcsv_CSV( );

			$backup_url = '';

			$settings = array( 
				'delimiter' => ',',
				'enclosure' => '"',
				'date_format' => 'US',
				'encoding' => 'UTF-8',
				'csv_path' => $this->get_csv_folder( ),
				'export_hidden_custom_fields' => '1',
				'include_field_list' => Array( '*' ),
				'exclude_field_list' => Array( ),
				'limit' => 1000,
				'offset' => 1
			);

			add_option( $this->option_name, $settings ); // Does nothing if already exists

			$this->settings = get_option( $this->option_name );
			$this->settings['version'] = '1.4.5';

			$current_keys = array_keys( $this->settings );
			foreach( array_keys( $settings ) as $key ) {
				if ( !in_array( $key, $current_keys ) ) {
					$this->settings[$key] = $settings[$key];
				}
			}
			
			$this->wpcsv = new pws_wpcsv_engine( $this->settings );

			$this->save_settings( );

			$this->csv->delimiter = $this->settings['delimiter'];
			$this->csv->enclosure = $this->settings['enclosure'];
			$this->csv->encoding = $this->settings['encoding'];

		}

		function folder_writable( $path ) {
			return ( is_dir( $path ) && is_writable( $path ) );
		}

		function add_htaccess( $path ) {
			if ( $this->folder_writable( $path ) ) {
				return file_put_contents( "{$path}/.htaccess", 'Deny from all' );
			}
		}

		function get_csv_folder( ) {

			$wp_csv_folder = '/wpcsv_backups';

			# In order of preference
			$paths = Array( 
				sys_get_temp_dir( ),
				ABSPATH,
				WP_CONTENT_DIR,
				WP_CONTENT_DIR . '/uploads'
			);

			foreach( $paths as $p ) {
				$p .= $wp_csv_folder;
				if ( ( !file_exists( $p ) && mkdir( $p, 0755 ) ) || $this->folder_writable( $p ) ) {
					$chosen_folder = $p;
					break;
				}
			}

			# This will create .htaccess files below the web root (ie sys_temp, but shouldn't cause any harm)
			if ( $chosen_folder && $this->add_htaccess( $chosen_folder ) ) {
				return $chosen_folder;
			}

		}

		function admin_pages( ) {

			if ( $_POST['action'] == 'report' && $_FILES['uploadedfile']['name'] == '' ) {
				$error = 'Invalid file';
				$_POST['action'] = 'import';
			}

			if ( $_POST['action'] == 'export' ) {
				$_POST['imagefolder'] = trim( $_POST['imagefolder'], '/ ' );
				$imagefolder = WP_CONTENT_DIR . '/uploads/' . $_POST['imagefolder'];
				if ( is_dir( $imagefolder ) ) {
					$this->settings['imagefolder'] = $_POST['imagefolder'];
				} else {
					$_POST['action'] = 'settings';
					$error = "ERROR - Folder could not be opened: $imagefolder";
					$imagefolder = $_POST['imagefolder'];
				}
				$this->settings['date_format'] = $_POST['date_format'];
				$this->settings['encoding'] = $_POST['encoding'];
				if ( $this->folder_writable( $_POST['csv_path'] ) ) {
					$this->settings['csv_path'] = $_POST['csv_path'];
				} else {
					$this->settings['csv_path'] = $this->get_csv_folder( );
					if ( !$this->settings['csv_path'] ) {
						$_POST['action'] = 'settings';
						$error = "ERROR - Unable to find a folder to store your CSV files in.  Please refer to the <a href='http://cpkwebsolutions.com/plugins/wp-csv/faq'>FAQ</a> for a solution.";
					}
				}
				$this->settings['delimiter'] = substr( stripslashes( $_POST['delimiter'] ), 0, 1 );
				$this->settings['enclosure'] = substr( stripslashes( $_POST['enclosure'] ), 0, 1 );

				if ( isset( $_POST['export_hidden_custom_fields'] ) ) {
					$this->settings['export_hidden_custom_fields'] = 1;
				} else {
					$this->settings['export_hidden_custom_fields'] = 0;
				}
				$this->settings['include_field_list'] = preg_split( '/(,|\s)/', $_POST['include_field_list'] );
				
				$this->settings['exclude_field_list'] =  preg_split( '/(,|\s)/', $_POST['exclude_field_list'] );
				$this->settings['limit'] = $_POST['limit'];
				$this->settings['offset'] = ( $_POST['offset'] > 0 ) ? $_POST['offset'] - 1 : 1;

				$this->save_settings();
			}

			$subdir = '/uploads';
			$filename = 'wpcsv-export-' . date('YmdHis');

			switch ( $_POST['action'] ) {
				case 'checkfailed':
					$this->view->page( 'checkfailed', array( ) );
				case 'import':
					$options = array_merge( array( 'max_bits' => 268435456, 'nonce' => wp_nonce_field( 'pws_wpcsv_upload' ), 'error' => $error ), $this->settings );
					$this->view->page( 'import', $options );
					break;
				case 'report':
					$options = array_merge( array( 'stats' => $this->getReport( $_FILES['uploadedfile'] ), 'backup_link' => get_post_meta( 1, '_pws_wpcsv_backup', TRUE ) ), $this->settings );
					$this->view->page( 'report', $options );
					break;
				case 'export':
					$options = array_merge( array( 'export_link' => $this->getExportLink( $filename, ( $_POST['custom_post'] != '' ? $_POST['custom_post'] : NULL ) ) ), $this->settings );
					$this->view->page( 'export', $options );
					$_SESSION['csvimp']['csv_path'] = $this->settings['csv_path'];
				
					break;
				default:
					$options = $this->settings;
					global $wpdb;
					$sql = "SELECT count(ID) FROM {$wpdb->posts} WHERE post_status IN ( 'publish', 'draft', 'future' )";
					$options['total_rows'] = $wpdb->get_var( $sql );
					$options['error'] =  $error;
					$this->view->page( 'settings', $options );
			}
		}

		function save_settings( ) {
			update_option( $this->option_name, $this->settings );
			// A bit ugly but necessary, refactor later
			$this->csv->delimiter = $this->settings['delimiter'];
			$this->csv->enclosure = $this->settings['enclosure'];
			$this->csv->encoding = $this->settings['encoding'];

			$this->wpcsv->settings = $this->settings;
		}

		function getReport( $file ) {
			$rows = $this->csv->loadFromFile( $file );
			return $this->wpcsv->import( $rows );
		}

		function getExportLink( $filename, $post_type = NULL ) {
			$csv_data = $this->wpcsv->export( $post_type );
			// Intercept 'ID' field and change to 'id' to prevent an excel bug.  Must reverse when importing too.
			if ( $csv_data[0][0] == 'ID' ) { $csv_data[0][0] = 'id'; }

			if ( $this->csv->saveToFile( $csv_data, $filename, $this->settings['csv_path'] ) ) {
				$plugin_dir = basename( dirname( __FILE__ ) );
				$enc = $this->settings['encoding'];
				$url = WP_PLUGIN_URL . "/$plugin_dir/download.php?file=$filename.csv&enc=$enc";
				update_post_meta( 1, '_pws_wpcsv_backup', $url );
			} else {
				$url = FALSE;
			}
			return $url;
		}

	}
}

// Instantiate

if (!function_exists("pws_wpcsv_admin_page")) {
	function pws_wpcsv_admin_page() {
		global $pws_wpcsv;
		if (!isset($pws_wpcsv)) {
			return;
		}
		if (function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'tools.php', __( 'WP CSV' ), __( 'WP CSV' ), 'administrator', basename(__FILE__), array( &$pws_wpcsv, 'admin_pages' ) );
		}
	}	
}

if ( !function_exists( "pws_wpcsv_header" ) ) {
	function pws_wpcsv_header( ) {
		$ecsvi_url = plugins_url( '/css/pws_wpcsv.css', __FILE__ );
		echo '<link type="text/css" rel="stylesheet" href="' . $ecsvi_url . '" />' . "\n";
	}
}

//Actions and Filters	
if (class_exists("pws_wpcsv")) {

	$pws_wpcsv = new pws_wpcsv();

	add_action( 'admin_menu', 'pws_wpcsv_admin_page' );
	add_action( 'admin_head', 'pws_wpcsv_header');

}
