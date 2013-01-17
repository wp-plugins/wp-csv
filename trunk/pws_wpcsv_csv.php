<?php
if ( !class_exists( 'pws_wpcsv_csv' ) ) {

class pws_wpcsv_csv {

	var $error = '';
	var $delimiter = ',';
	var $enclosure = '"';
	var $encoding = "UTF-8";


# Was needed to support PHP4 previously, but should refactor in future
  function fputcsv(&$handle, $fields = array( ) ) {
    $str = '';
    $escape_char = '\\';
    foreach ($fields as $value) {
      if (strpos($value, $this->delimiter) !== false ||
          strpos($value, $this->enclosure) !== false ||
          strpos($value, "\n") !== false ||
          strpos($value, "\r") !== false ||
          strpos($value, "\t") !== false ||
          strpos($value, ' ') !== false) {
        $str2 = $this->enclosure;
        $escaped = 0;
        $len = strlen($value);
        for ($i=0;$i<$len;$i++) {
          if ($value[$i] == $escape_char) {
            $escaped = 1;
          } else if (!$escaped && $value[$i] == $this->enclosure) {
            $str2 .= $this->enclosure;
          } else {
            $escaped = 0;
          }
          $str2 .= $value[$i];
        }
        $str2 .= $this->enclosure;
        $str .= $str2.$this->delimiter;
      } else {
        $str .= $value.$this->delimiter;
      }
    }
    $str = substr($str,0,-1);
    $str .= "\n";
    $str = mb_convert_encoding( $str, $this->encoding, 'UTF-8' );
    return fwrite($handle, $str);
  }

	function saveToFile( $csv_data = array( ), $filename = 'csvdata', $path = '/tmp' ) {

		if ( isset( $csv_data[0] ) ) {
			
			$fullpath = $path . '/' . $filename . '.csv';
			$fp = @fopen( $fullpath, 'w+b' );
			if ( $fp ) {
				foreach ( $csv_data as $cd ) {
					$fwrite = $this->fputcsv( $fp, $cd );
				}
			} else {
				echo "<p><strong>Error: unable to create a new file ($fullpath).  Please check your folder permissions. <a href='http://codex.wordpress.org/Changing_File_Permissions'>More information.</a></strong></p>"; 
			}
			return ( @fclose( $fp ) );
		}
		return FALSE;
	}

	function loadFromFile( $file_object ) {
		ini_set('auto_detect_line_endings', true);
		iconv_set_encoding( 'input_encoding', $this->encoding );

		$csv_data = array( );
		if ( $this->valid_file( $file_object ) ) {
			if ( ( $handle = fopen( $file_object['tmp_name'], "r") ) ) {
				if ( $title_row = fgetcsv( $handle, 100000, $this->delimiter, $this->enclosure ) ) {

					// Intercept 'id' field and change to 'ID'.  Needs to be 'id' to prevent an excel bug, but ID is preferable to match the posts table.
					if ( $title_row[0] == 'id' ) { $title_row[0] = 'ID'; }

					// TODO: fgetcsv is not handling double apostrophes the way I want, but don't have time to code a fgets solution yet.
					while ( $row = fgetcsv( $handle, 100000, $this->delimiter, $this->enclosure ) ) {
						$assoc_array = array( );
						foreach( $row as $key => $val ) {
							$fieldname = $title_row[$key];
							$assoc_array[$fieldname] = $val;
						}
						$csv_data[] = $assoc_array;
					}
				}
				fclose( $handle );
			}
		}
		return $csv_data;
	}

	function valid_file( $file_object ) {
		$title_row = '';
		$first_row = '';
		ini_set('auto_detect_line_endings', true);
		// TODO: Improve security for the upload!
		if ( !is_uploaded_file( $file_object['tmp_name'] ) && $file_object['error'] ) {
			$this->error = "Invalid file!";
			return FALSE;
		}
		if ( $handle = fopen( $file_object['tmp_name'], "r") ) {
			if ( ( $title_row = fgetcsv( $handle, 100000, $this->delimiter, $this->enclosure ) ) && ( $first_row = fgetcsv( $handle, 100000, $this->delimiter, $this->enclosure ) ) ) {
				if ( count( $title_row ) == 1 || ( count( $title_row ) != count( $first_row ) ) ) {
					$this->error = "Number of fields in first and second row don't match!";
					return FALSE;
				}
			} else {
				$this->error = "One of the first two lines appear to be incorrectly formatted!";
				return FALSE;
			}
			return ( fclose($handle) );
		} else {
			$this->error = "Unable to open file!";
			return FALSE;
		}
	}

}
}

?>
