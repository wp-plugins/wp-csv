<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script type="text/javascript">

$( function( ) {

	$( '#progressbar' ).progressbar( { value: 0 } );

	var retpercent = 0;
	var progress = 0;
	var base_url = '<?php echo site_url( ); ?>';
	function getProgress( progress, retpercent) {	
		$.ajax({
			url: base_url + '/wp-admin/admin-ajax.php?action=process_export',
			type: "GET",
			data: 'start=' + progress + '&progress=' + retpercent,
			dataType : 'json',
			success: function( data ) {
				var percentage = parseFloat( data.percentagecomplete );
				if ( percentage < 100 ) {
					$( '#progressbar' ).progressbar( "value", percentage );
					getProgress( data.position, data.percentagecomplete );
				} else {
					$( '#progressbar' ).progressbar( "value", percentage );
					jQuery( '#download_link' ).show( );
				}
			}
		});
	}

	jQuery( '#start_export' ).on( 'click', function( ) {
		jQuery( '#export_wrapper' ).hide( );
		jQuery( '#download_link' ).hide( );
		jQuery( '#progressbar' ).progressbar( "value", 0 );
		jQuery( '#progressbar' ).removeClass( 'ui-widget-content' ).addClass( 'stripes' );
		getProgress( 0, 0 );
	});
});

</script>
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong>Export To CSV</strong></th></tr>
</thead>
<tbody>
<tr><th>Progress</th><td><div id="progressbar_holder"><div id="progressbar"></div></div>
<div id="download_link">
<a href='<?php echo $export_link; ?>'>Download CSV File</a>
</div></td></tr>
</tbody>
</table>
<br />
<div id="export_wrapper">
<div id="start_button_wrapper">
<input type='button' id='start_export' value='Export' />
</div>
</div>
<br />
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong>Upload CSV File For Import</strong></th></tr>
</thead>
<tbody>
<tr><th>Select CSV File</th><td>
<form enctype="multipart/form-data" action="" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_bits ?>" />
<?php echo $nonce ?>
<input type="hidden" name="action" value="import" />
<input name="uploadedfile" type="file" />
</fieldset>
<strong class='red'><?php echo $error ?></strong></td></tr>
</tbody>
</table>
<br />
<input type='submit' value='Upload'/>
</form>
