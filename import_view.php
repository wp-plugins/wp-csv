<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script type="text/javascript">

jQuery( function( ) {

	jQuery( '#progressbar' ).progressbar( { value: 0 } );

	var base_url = '<?php echo site_url( ); ?>';
	function getProgress( start, retpercent, lines ) {	
		jQuery.ajax({
			url: base_url + '/wp-admin/admin-ajax.php?action=process_import',
			type: "GET",
			data: 'start=' + start + '&progress=' + retpercent + '&lines=' + lines,
			dataType : 'json',
			success: function( data ) {
				var percentage = parseFloat( data.percentagecomplete );
				if ( percentage < 100 ) {
					jQuery( '#progressbar' ).progressbar( "value", percentage );
					jQuery( '#percent' ).text( '(' + percentage + '%)' );
					getProgress( data.position, data.percentagecomplete, data.lines );
				} else {
					jQuery( '#progressbar' ).progressbar( "value", percentage );
					jQuery( '#percent' ).text( '(' + percentage + '%)' );
					location.search = '?page=wp-csv.php&action=report';
				}
			},
			error: function( data ) {
				alert( 'Import failed due to a server error.  Check the error log.' );
				jQuery( '#import_wrapper' ).show( );
				jQuery( '#progressbar' ).addClass( 'ui-widget-content' ).removeClass( 'stripes' );
				window.clearInterval( interval_id );
				jQuery( '#timer' ).text( '00:00:00' );
				jQuery( '#percent' ).text( '(0%)' );
				return;
			}
		});
	}

	jQuery( '#start_import' ).on( 'click', function( ) {
		interval_id = window.setInterval( function() {
			jQuery("#timer").timer();
		}, 1000);
		
		jQuery( '#import_wrapper' ).hide( );
		jQuery( '#progressbar' ).removeClass( 'ui-widget-content' ).addClass( 'stripes' );
		getProgress( 1, 0, 0 );
	});

	String.prototype.toHHMMSS = function () {
		var sec_num = parseInt(this, 10); // don't forget the second param
		var hours   = Math.floor(sec_num / 3600);
		var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
		var seconds = sec_num - (hours * 3600) - (minutes * 60);

		if (hours   < 10) {hours   = "0"+hours;}
		if (minutes < 10) {minutes = "0"+minutes;}
		if (seconds < 10) {seconds = "0"+seconds;}
		var time    = hours+':'+minutes+':'+seconds;
		return time;
	}

	var seconds = 0;

	jQuery.fn.timer = function() {
		seconds++;		
		jQuery( this ).text( seconds.toString( ).toHHMMSS( ) );
	} // timer function end
		

});

</script>
<strong class='red'><?php echo $error;?></strong>
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong><?php _e( 'Import Uploaded File', 'wp-csv' ); ?></strong></th></tr>
</thead>
<tbody>
<tr><th><?php _e( 'Uploaded file', 'wp-csv' ); ?>:</th><td><strong><?php echo $file_name ?></strong></td></tr>
<tr><th><?php _e( 'Progress', 'wp-csv' ); ?>:</th><td><div id='progressbar_holder'><div id="progressbar"></div></div><span id='percent'>(0%)</span></td></tr>
<tr><th><?php _e( 'Elapsed Time', 'wp-csv' ); ?>:</th><td><p id="timer">00:00:00</p></td></tr>
</tbody>
</table>
<br />
<div id="import_wrapper">
<div id="start_button_wrapper">
<input type='button' id='start_import' value='Import' />
</div>
</div>
