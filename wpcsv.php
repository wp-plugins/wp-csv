<?php
/*
Plugin Name: WP CSV
Plugin URI: http://cpkwebsolutions.com/plugins/wp-csv
Description: A powerful, yet easy to use, CSV Importer/Exporter for Wordpress posts and pages. 
Version: 1.5.0
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
spl_autoload_register( 'spl_autoload_classes' );

function spl_autoload_classes( $name ) {

	if ( class_exists( $name ) ) return FALSE;

	$folders = Array( '' );
	
	if ( is_array( $folders ) && !empty( $folders ) ) {
		foreach( $folders as $folder ) {
			$file = dirname( __FILE__ ) . "/{$folder}{$name}.php";
			if ( file_exists( $file ) ) {
				require_once( $file );
			}
		} # End foreach
	} # End if

}

// Initialise main class
if ( !class_exists( 'CPK_WPCSV' ) ) {

	class CPK_WPCSV {

		private $view;
		private $csv;
		private $wpcsv;
		private $backup_url;
		private $settings;
		private $option_name = '_pws_wpcsv_settings';


		const IMPORT_FILE_NAME = 'wpcsv-import.csv';
		const EXPORT_FILE_NAME = 'wpcsv-export.csv';

		const ERROR_MISSING_POST_ID = 1;
		const ERROR_MISSING_POST_PARENT = 2;
		const ERROR_MISSING_AUTHOR = 3;
		const ERROR_INVALID_TAXONOMY_TERM = 4;

		public function __construct( ) { // Constructor

			ob_start( ); # For download... TODO: refactor MVC framework so that view functions run earlier

			if ( !session_id( ) ) session_start( );
			$this->view = new CPK_WPCSV_View( );
			$this->csv = new CPK_WPCSV_CSV( );
			$this->log = new CPK_WPCSV_Log_Model( );

			$backup_url = '';

			$settings = Array( 
				'delimiter' => ',',
				'enclosure' => '"',
				'date_format' => 'US',
				'encoding' => 'UTF-8',
				'csv_path' => $this->get_csv_folder( ),
				'export_hidden_custom_fields' => '1',
				'include_field_list' => Array( '*' ),
				'exclude_field_list' => Array( ),
				'post_type' => NULL,
				'limit' => 3000,
				'post_fields' => Array( 'ID', 'post_date', 'post_status', 'post_title', 'post_content', 'post_excerpt', 'post_parent', 'post_name', 'post_type', 'ping_status', 'comment_status', 'menu_order', 'post_author' ),
				'mandatory_fields' => Array( 'ID', 'post_date', 'post_title' )
			);

			add_option( $this->option_name, $settings ); // Does nothing if already exists

			$this->settings = get_option( $this->option_name );
			$this->settings['version'] = '1.5.0';

			$current_keys = array_keys( $this->settings );
			foreach( array_keys( $settings ) as $key ) {
				if ( !in_array( $key, $current_keys ) || is_null( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = $settings[$key];
				}
			}
			
			$this->wpcsv = new CPK_WPCSV_Engine( $this->settings );

			$this->save_settings( );

			$this->csv->delimiter = $this->settings['delimiter'];
			$this->csv->enclosure = $this->settings['enclosure'];
			$this->csv->encoding = $this->settings['encoding'];
			
			add_action( 'wp_ajax_process_export', Array( $this, 'process_export' ) );
			add_action( 'wp_ajax_process_import', Array( $this, 'process_import' ) );

		}

		public function set_settings( $settings ) {
			$this->settings = $settings;
			$this->save_settings( );
		}

		public function folder_writable( $path ) {
			return ( is_dir( $path ) && is_writable( $path ) );
		}

		public function add_htaccess( $path ) {
			if ( $this->folder_writable( $path ) ) {
				return file_put_contents( "{$path}/.htaccess", 'Deny from all' );
			}
		}

		public function get_csv_folder( ) {

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

		public function admin_pages( ) {

			if ( $_POST['action'] == 'import' && $_FILES['uploadedfile']['name'] == '' ) {
				$error = 'You must select a file to upload and import.';
				$_POST['action'] = 'export';
				$_REQUEST['action'] = 'export';
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
				$this->settings['post_type'] = $_POST['custom_post'];

				$this->save_settings();
			}

			$subdir = '/uploads';
			$filename = self::EXPORT_FILE_NAME;

			switch ( $_REQUEST['action'] ) {
				case 'checkfailed':
					$this->view->page( 'checkfailed', array( ) );
				case 'import':
					move_uploaded_file( $_FILES['uploadedfile']['tmp_name'], $this->settings['csv_path'] . '/' . self::IMPORT_FILE_NAME );
					$options['file_name'] = $_FILES['uploadedfile']['name'];
					$options['error'] = $error;
					$this->view->page( 'import', $options );
					break;
				case 'report':
					$options = array_merge( Array( 'messages' => $this->log->get_message_list( ), $this->settings ) );
					$options['error'] =  $error;
					$this->view->page( 'report', $options );
					break;
				case 'export':
					$this->prepare_export( );
					$enc = $this->settings['encoding'];
					$url = site_url( ) . "/wp-admin/tools.php?page=wpcsv.php&action=download&file=$filename&enc=$enc";
					$options = array_merge( Array( 'export_link' => $url ), $this->settings );
					$options['error'] = $error;
					$this->view->page( 'export', $options );
					break;
				case 'download':
					$this->view->page( 'download', $this->settings );
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

		public function save_settings( ) {
			update_option( $this->option_name, $this->settings );
			// A bit ugly but necessary, refactor later
			$this->csv->delimiter = $this->settings['delimiter'];
			$this->csv->enclosure = $this->settings['enclosure'];
			$this->csv->encoding = $this->settings['encoding'];

			$this->wpcsv->settings = $this->settings;
		}

		public function prepare_export( ) {
			$this->wpcsv->prepare( );
		}
		
		public function process_export( ) {
			$start 	= isset( $_GET['start'] ) ? $_GET['start'] : 0;

			$total = $this->wpcsv->get_total( );

			$include_headings = ( $start == 0 );

			$number_processed = $this->wpcsv->export( $include_headings );

			$position = $start + $number_processed;
			$ret_percentage = round( ( ( $position - 1 ) / $total ) * 100 );

			echo json_encode( Array( 'position' => $position, 'percentagecomplete' => $ret_percentage ) );
			die( );
		}
		
		public function process_import( ) {

			$file = $this->settings['csv_path'] . '/' . self::IMPORT_FILE_NAME;
			
			$start = $_GET['start'];

			$total = ( $_GET['lines'] == 0 ) ? $this->csv->line_count( $file ) : $_GET['lines'];

			$rows = $this->csv->load( $file, $start, $this->settings['limit'] );
			
			$number_processed = $this->wpcsv->import( $rows );

			$position = $start + $number_processed;

			$ret_percentage = round( ( ( $position - 1 ) / $total ) * 100 );

			echo json_encode( Array( 'position' => $position, 'percentagecomplete' => $ret_percentage, 'lines' => $total ) );
			die( );
		}

	}
}

// Instantiate

if ( !function_exists( "pws_wpcsv_admin_page" ) ) {
	function pws_wpcsv_admin_page( ) {
		global $cpk_wpcsv;
		if ( !isset( $cpk_wpcsv ) ) {
			return;
		}
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'tools.php', __( 'WP CSV' ), __( 'WP CSV' ), 'administrator', basename(__FILE__), array( &$cpk_wpcsv, 'admin_pages' ) );
		}
	}	
}

if ( !function_exists( "pws_wpcsv_header" ) ) {
	function pws_wpcsv_header( ) {
		$ecsvi_url = plugins_url( '/css/cpk_wpcsv.css', __FILE__ );
		echo '<link type="text/css" rel="stylesheet" href="' . $ecsvi_url . '" />' . "\n";
	}
}

//Actions and Filters	
if ( class_exists( "CPK_WPCSV" ) ) {
	global $cpk_wpcsv;
	$cpk_wpcsv = new CPK_WPCSV( );

	add_action( 'admin_menu', 'pws_wpcsv_admin_page' );
	add_action( 'admin_head', 'pws_wpcsv_header' );
}
