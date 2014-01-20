<?php
if ( !class_exists( 'CPK_WPCSV_CSV' ) ) {

class CPK_WPCSV_CSV {

	public $errors = Array( );
	public $delimiter = ',';
	public $enclosure = '"';

	const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

	public function __construct( ) {
		ini_set( 'auto_detect_line_endings', TRUE );
		iconv_set_encoding( 'input_encoding', self::DEFAULT_CHARACTER_ENCODING );
		$this->log_model = new CPK_WPCSV_Log_Model( );
	}

	public function save( $csv_data = Array( ), $filename = 'csvdata', $path = '/tmp' ) {

		if ( empty( $csv_data ) ) return FALSE;

		$file_path = $path . '/' . $filename . '.csv';

		$error_message = "Unable to write to file (location: '{$file_path}').  Perhaps try checking the file and folder via FTP.  If there's no file, it may be a permissions issue.  If there's a file, but it's only partially complete, then make sure you haven't run out of disk space.";

		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {

			$file = new SplFileObject( $file_path, 'ab' );

			foreach ( $csv_data as $csv_row ) {
				$write_successful = $file->fputcsv( $csv_row, $this->delimiter, $this->enclosure );
				if ( !$write_successful ) {
					$this->errors[] = $error_message;
					break;
				}
			}
		} else {
			$file = fopen( $file_path, 'ab' );
			foreach( $csv_data as $csv_row ) {
				$write_successful = fputcsv( $file, $csv_row, $this->delimiter, $this->enclosure );
				if ( !$write_successful ) {
					$this->errors[] = $error_message;
					break;
				}
			}
			fclose( $file );
		}
			
		return FALSE;
	}

	public function line_count( $file_path, $offset = -1 ) {

		# This section assumes that there are line endings (as should be the case with CSV) 
		# and will not fail gracefully for a big file without them.

		$linecount = 0;
		$handle = fopen( $file_path, "r" );
		while( $row = fgetcsv( $handle ) ) {
			if ( is_array( $row ) && count( $row ) > 1 ) {
				$linecount++;
			}
		}
		fclose( $handle );
		return $linecount + $offset; # Don't count CSV header row
	}

	public function load( $file_path, $start = 1, $limit = 500 ) {

		if ( !$this->file_valid( $file_path ) ) return FALSE;

		$file = new SplFileObject( $file_path, 'r' );

		$file->setFlags( SplFileObject::READ_AHEAD );
		$file->setFlags( SplFileObject::SKIP_EMPTY );
		$file->setFlags( SplFileObject::DROP_NEW_LINE );
		$file->setFlags( SplFileObject::READ_CSV );

		$csv_data = Array( );

		if ( $title_row = $file->fgetcsv( $this->delimiter, $this->enclosure ) ) {

			// Intercept 'id' field and change to 'ID'.  Needs to be 'id' to prevent an excel bug, but ID is preferable to match the posts table.
			if ( $title_row[0] == 'id' ) $title_row[0] = 'ID';

			$file->seek( $start );

			$count = 0;

			while ( !$file->eof( ) ) {
				if ( $count >= $limit ) break;
				$row = $file->current( );
				if ( is_array( $row ) && count( $row ) == count( $title_row ) ) {
					$csv_data[] = array_combine( $title_row, $row );
					$count++;
				}
				$file->next( );
			}
		}

		return $csv_data;
	}

	public function file_valid( $file_path ) {

		$file = new SplFileObject( $file_path, 'r' );

		$title_row = $file->fgetcsv( $this->delimiter, $this->enclosure );

		if ( $title_row === FALSE ) {
			$this->log_model->add_message( "Unable to read first line of file (location:{$file_path})." );
			$this->log_model->store_messages( );
			return FALSE;
		}
		
		if ( is_array( $title_row ) && empty( $title_row ) ) {
			$this->log_model->add_message( "File seems to be empty." );
			$this->log_model->store_messages( );
			return FALSE;
		}
		if ( count( $title_row ) == 1 ) {
			$this->log_model->add_message( "Only one column found.  Are you sure your spreadsheet saved this file with the correct delimiter and enclosure characters (must match WP CSV Settings)?" );
			$this->log_model->store_messages( );
			return FALSE;
		}

		$first_row = $file->fgetcsv( $this->delimiter, $this->enclosure );

		if ( count( $title_row ) <> count( $first_row ) ) {
			$this->log_model->add_message( "Different number of columns found in first and second rows.  Operation aborted to prevent data corruption." );
			$this->log_model->store_messages( );
			return FALSE;
		}

		return TRUE;
	}

}
}
