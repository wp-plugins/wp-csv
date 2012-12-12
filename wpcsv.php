<?php
/*
Plugin Name: WP CSV
Plugin URI: http://cpkwebsolutions.com/plugins/wp-csv
Description: A powerful, yet easy to use, CSV Importer/Exporter for Wordpress posts and pages. 
Version: 1.3
Author: CPK Web Solutions
Author URI: http://cpkwebsolutions.com

	LICENSE

	Copyright 2012  Paul's Web Solutions  (email : paul@paulswebsolutions.com )

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

// Global constants
define( 'ERROR_MISSING_POST_ID', 1 );
define( 'ERROR_MISSING_POST_PARENT', 2 );

// Initialise main class
if ( !class_exists( 'pws_wpcsv' ) ) {

	class pws_wpcsv {

		var $view;
		var $csv;
		var $wpcsv;
		var $backup_url;
		var $settings;
		var $option_name = '_pws_wpcsv_settings';

		function __construct( ) { // Constructor
			$this->view = new pws_wpcsv_view( );
			$this->csv = new pws_wpcsv_CSV( );

			$backup_url = '';

			$settings = array( 
				'version' => '1.3',
				'delimiter' => ',',
				'enclosure' => '"',
				'date_format' => 'US',
				'encoding' => 'UTF-8',
				'csv_path' => sys_get_temp_dir( )
			);

			add_option( $this->option_name, $settings ); // Does nothing if already exists

			$this->settings = get_option( $this->option_name );

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
				if ( $this->csv_path_valid( $_POST['csv_path'] ) ) {
					$this->settings['csv_path'] = $_POST['csv_path'];
				} else {
					$_POST['action'] = 'settings';
					$error = "ERROR - CSV Path does not exist, is not writable, or was publicly accessible (insecure)!";
				}
				$this->settings['delimiter'] = substr( stripslashes( $_POST['delimiter'] ), 0, 1 );
				$this->settings['enclosure'] = substr( stripslashes( $_POST['enclosure'] ), 0, 1 );

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
					$options = array_merge( array( 'export_link' => $this->getExportLink( $filename ) ), $this->settings );
					$this->view->page( 'export', $options );
					$_SESSION['csv_path'] = $this->settings['csv_path'];
					break;
				default:
					$options = $this->settings;
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

			$this->pws_wpcsv->settings = $this->settings;
		}

		function getReport( $file ) {
			$rows = $this->csv->loadFromFile( $file );
			return $this->wpcsv->import( $rows );
		}

		function getExportLink( $filename ) {
			$csv_data = $this->wpcsv->export( );
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

		private function csv_path_valid( $path ) {
			# Make sure the folder exists, is accessible to the web server, and not accessible to the public

			if ( !is_dir( $path ) ) return FALSE;

			if ( !is_writable( $path ) ) return FALSE;

			$web_root = addcslashes( $_SERVER['DOCUMENT_ROOT'], '/' );

			if ( preg_match( '/' . $web_root . '/', $path ) ) return FALSE;

			return TRUE;
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



?>
