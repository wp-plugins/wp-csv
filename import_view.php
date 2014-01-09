<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script type="text/javascript">

$( function( ) {

	$( '#progressbar' ).progressbar( { value: 0 } );

	var base_url = '<?php echo site_url( ); ?>';
	function getProgress( start, retpercent, lines ) {	
		$.ajax({
			url: base_url + '/wp-admin/admin-ajax.php?action=process_import',
			type: "GET",
			data: 'start=' + start + '&progress=' + retpercent + '&lines=' + lines,
			dataType : 'json',
			success: function( data ) {
				var percentage = parseFloat( data.percentagecomplete );
				if ( percentage < 100 ) {
					$( '#progressbar' ).progressbar( "value", percentage );
					getProgress( data.position, data.percentagecomplete, data.lines );
				} else {
					$( '#progressbar' ).progressbar( "value", percentage );
					jQuery( '#download_link' ).show( );
					location.search = '?page=wpcsv.php&action=report';
				}
			}
		});
	}

	jQuery( '#start_import' ).on( 'click', function( ) {
		jQuery( '#import_wrapper' ).hide( );
		jQuery( '#progressbar' ).removeClass( 'ui-widget-content' ).addClass( 'stripes' );
		getProgress( 1, 0, 0 );
	});
});

</script>
<strong class='red'><?php echo $error;?></strong>
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong>Import Uploaded File</strong></th></tr>
</thead>
<tbody>
<tr><th>Uploaded file:</th><td><strong><?php echo $file_name ?></strong></td></tr>
<tr><th>Progress:</th><td><div id='progressbar_holder'><div id="progressbar"></div></div></td></tr>
</tbody>
</table>
<br />
<div id="import_wrapper">
<div id="start_button_wrapper">
<input type='button' id='start_import' value='Import' />
</div>
</div>
