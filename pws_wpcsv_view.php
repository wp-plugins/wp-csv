<?php
if ( !class_exists( 'pws_wpcsv_view' ) ) {
	class pws_wpcsv_view {

		function page( $page_name, $options ) {
			if ( is_array( $options ) ) extract( $options ); // Variabalise for easier access in the view

			$inner_page = "${page_name}_view.php";
			require_once( 'layout.php' );
		}

	}
}

?>
