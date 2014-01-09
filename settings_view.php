<form method='post'>
<input type='hidden' name='action' value='export'/>
<?php echo $nonce ?>
<p><strong>First Time Users:</strong> Read the <a href='http://cpkwebsolutions.com/plugins/wp-csv/quick-start-guide'>Quick Start Guide</a>.</p>
<strong class='red'><?php echo $error;?></strong>
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong>Settings</strong></th></tr>
</thead>
<tbody>
<tr><th>Delimiter:</th><td><input name="delimiter" type="text" length="1" value="<?php echo htmlentities($delimiter); ?>"/><span class='description'><strong> (European users will usually need to change this from ',' to ';')</strong><span/></td></tr>
<tr><th>Enclosure:</th><td><input name="enclosure" type="text" length="1" value="<?php echo htmlentities($enclosure); ?>"/><span class='description'></td></tr>
<tr><th>Date format:</th><td><select name="date_format"><option <?php if ($date_format == 'US' ) echo 'selected';?> value="US">US (MM/DD/YYYY)</option><option <?php if ($date_format == 'English' ) echo 'selected';?> value="English">English (DD/MM/YYYY)</option></select></td></tr>
<tr><th>Encoding:</th><td><select name="encoding">
<option <?php if ($encoding == 'UTF-8' ) echo 'selected';?> value="UTF-8">UTF-8</option>
<option <?php if ($encoding == 'UTF-8-BOM' ) echo 'selected';?> value="UTF-8-BOM">UTF-8 (with BOM)</option>
</select></td></tr>
<?php 
	$checked = ( htmlentities( $export_hidden_custom_fields ) ) ? 'checked ' : '';
?>
<tr><th>Export 'Hidden' Custom Fields:</th><td><input name="export_hidden_custom_fields" type="checkbox" <?php echo $checked; ?>/></td></tr>
<tr><th>Include Fields:</th><td><textarea name="include_field_list" cols="70" rows="5" /><?php echo implode( ',', $include_field_list ); ?></textarea>
<blockquote><i>Control which fields are included in the export file.  You can enter the full field name or a pattern such as '*' (for everything), 'start*' (for fields starting with 'start'), or '*end' (for fields ending with 'end'). Separate field rules with a comma.  NOTE: Some fields are mandatory and will appear no matter what rules you add.  Excluded fields will not appear.</i></blockquote></td></tr>
<tr><th>Exclude Fields:</th><td><textarea name="exclude_field_list" cols="70" rows="5" /><?php echo implode( ',', $exclude_field_list ); ?></textarea>
<blockquote><i>Control which fields are excluded from the export file.  You can enter a pattern such as 'start*' (for fields starting with 'start'), or '*end' (for fields ending with 'end'). NOTE: Some fields are mandatory and will appear no matter what you enter.  Excluded fields take precedence over included fields so you can include 'start*' and then exclude 'start_useless_field'. Separate field rules with a comma.</i></blockquote></td></tr>
<tr><th>Post Type Filter</th><td>
<input type="radio" name="custom_post" value="" checked> All (No Filtering) <br />
<?php
$post_types = get_post_types();
$exclude_post_types = Array( 'attachment', 'revision', 'nav_menu_item', 'wp-types-group' );
foreach ( $post_types as $post_type ) {
	if ( !in_array( $post_type, $exclude_post_types ) ) {
		$label = get_post_type_object( $post_type )->labels->name;
		echo "<input type='radio' name='custom_post' value='{$post_type}'> {$label}<br />";
	}
}
?></td></tr>
</tbody>
</table>
<p><input type="submit" value="Save and Export" /></p>
</form>
