<?php
if ( isset( $_GET['file'] ) ) {
	extract( $_GET );
	$file = strtolower( $file );
	$path = $csv_path . '/' . $file;
	$csv_check = substr( $file, -4 ); # Make sure it's a csv file for security
	if ( file_exists( $path ) && $csv_check == '.csv' ) {
		ob_end_clean( );
		downloadFile( $path, $enc );
		die( );
	} else {
		wp_redirect( site_url( ) . '/wp-admin/tools.php?page=wpcsv.php&action=export' );
		die( );
	}
}

function downloadFile( $fullPath, $encoding ){

	// Must be fresh start
	if( headers_sent() )
		die('Headers Sent');

	// Required for some browsers
	if ( ini_get( 'zlib.output_compression' ) ) {
		ini_set( 'zlib.output_compression', 'Off' );
	}

	ini_set( 'auto_detect_line_endings', true );

	// File Exists?
	if ( file_exists( $fullPath ) ) {
		
		ob_clean( );

		# Encoding combos:
		#
		# UTF-8, BOM: EF BB BF
		# UTF-16LE, FF FE, 'little endian' 
		# UTF-16BE, FE FF, 'big endian' 
		#

		switch ( $encoding ) {
			case 'UTF-16LE':
				$bom = "\xFF\xFE";
				break;
			case 'UTF-16BE':
				$bom = "\xFE\xFF";
				break;
			case 'UTF-8':
				$bom = '';
				break;
			case 'UTF-8-BOM':
				$encoding = 'UTF-8';
				$bom = "\xEF\xBB\xBF";
				break;
			default:
				$bom = '';
		}	
		
		iconv_set_encoding( 'output_encoding', $encoding );

		// Parse Info / Get Extension
		$fsize = filesize( $fullPath );
		$path_parts = pathinfo( $fullPath );
		$ext = strtolower( $path_parts['extension'] );
		 
		// Determine Content Type
		switch ( $ext ) {
			case "csv": $ctype="application/octet-stream"; break;
			case "pdf": $ctype="application/pdf"; break;
			case "exe": $ctype="application/octet-stream"; break;
			case "zip": $ctype="application/zip"; break;
			case "doc": $ctype="application/msword"; break;
			case "xls": $ctype="application/vnd.ms-excel"; break;
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
			case "gif": $ctype="image/gif"; break;
			case "png": $ctype="image/png"; break;
			case "jpeg":
			case "jpg": $ctype="image/jpg"; break;
			default: $ctype="application/force-download";
		}

		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers
		header("Content-Type: $ctype; charset=$encoding");
		header("Content-Disposition: attachment; filename=\"".basename($fullPath)."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$fsize);

		echo $bom;
		
		readfile( $fullPath );
		die( );

	} else {
		die('File Not Found');
	}
}
