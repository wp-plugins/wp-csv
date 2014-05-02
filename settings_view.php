<form method='post'>
<input type='hidden' name='action' value='export'/>
<?php echo $nonce ?>
<p><strong><?php _e( 'First Time Users', 'wp-csv' ); ?>:</strong> Read the <a href='http://cpkwebsolutions.com/plugins/wp-csv/quick-start-guide'>Quick Start Guide</a>.</p>
<p><input type="submit" value="<?php _e( 'Save and Go To Import/Export', 'wp-csv' ); ?>" /></p>
<strong class='red'><?php echo $error;?></strong>
<table class='widefat'>
<thead>
<tr><th colspan='2'><strong><?php _e( 'Settings', 'wp-csv' ); ?></strong></th></tr>
</thead>
<tbody>
<tr><th><?php _e( 'Delimiter', 'wp-csv' ); ?>:</th><td><input name="delimiter" type="text" length="1" value="<?php echo htmlentities($delimiter); ?>"/><span class='description'><strong> <?php _e( "(European users will usually need to change this from ',' to ';')", 'wp-csv' ); ?></strong><span/></td></tr>
<tr><th><?php _e( 'Enclosure', 'wp-csv' ); ?>:</th><td><input name="enclosure" type="text" length="1" value="<?php echo htmlentities($enclosure); ?>"/><span class='description'></td></tr>
<tr><th><?php _e( 'Import Date format', 'wp-csv' ); ?>:</th><td><select name="date_format"><option <?php if ($date_format == 'US' ) echo 'selected';?> value="US">US (MM/DD/YYYY)</option><option <?php if ($date_format == 'English' ) echo 'selected';?> value="English">English (DD/MM/YYYY)</option></select><span class='description'><strong> <?php _e( "(Dates are always exported as 'YYYY-MM-DD HH:MM:SS')", 'wp-csv' ); ?></strong><span/></td></tr></td></tr>
<tr><th><?php _e( 'Encoding', 'wp-csv' ); ?>:</th><td><select name="encoding">
<option <?php if ($encoding == 'UTF-8' ) echo 'selected';?> value="UTF-8">UTF-8</option>
<option <?php if ($encoding == 'UTF-8-BOM' ) echo 'selected';?> value="UTF-8-BOM">UTF-8 (with BOM)</option>
</select></td></tr>
<?php 
	$checked = ( htmlentities( $export_hidden_custom_fields ) ) ? 'checked ' : '';
?>
<tr><th><?php _e( "Export 'Hidden' Custom Fields", 'wp-csv' ); ?>:</th><td><input name="export_hidden_custom_fields" type="checkbox" <?php echo $checked; ?>/></td></tr>
<tr><th><?php _e( "Include Fields", 'wp-csv' ); ?>:</th><td><textarea name="include_field_list" cols="70" rows="5" /><?php echo implode( ',', $include_field_list ); ?></textarea>
<blockquote><i><?php _e( "Control which fields are included in the export file.  You can enter the full field name or a pattern such as '*' (for everything), 'start*' (for fields starting with 'start'), or '*end' (for fields ending with 'end'). Separate field rules with a comma.  NOTE: Some fields are mandatory and will appear no matter what rules you add.  Excluded fields will not appear.", 'wp-csv' ); ?></i></blockquote></td></tr>
<tr><th><?php _e( "Exclude Fields", 'wp-csv' ); ?>:</th><td><textarea name="exclude_field_list" cols="70" rows="5" /><?php echo implode( ',', $exclude_field_list ); ?></textarea>
<blockquote><i><?php _e( "Control which fields are excluded from the export file.  You can enter a pattern such as 'start*' (for fields starting with 'start'), or '*end' (for fields ending with 'end'). NOTE: Some fields are mandatory and will appear no matter what you enter.  Excluded fields take precedence over included fields so you can include 'start*' and then exclude 'start_useless_field'. Separate field rules with a comma.", 'wp-csv' ); ?></i></blockquote></td></tr>
<tr><th><?php _e( 'Post Type Filter', 'wp-csv' ); ?>:</th><td>
<input type="radio" name="custom_post" value="" checked> <?php _e( 'All (No Filtering)', 'wp-csv' ); ?> <br />
<?php
$post_types = get_post_types();
$exclude_post_types = Array( 'attachment', 'revision', 'nav_menu_item', 'wp-types-group' );
foreach ( $post_types as $custom_post_type ) {
	if ( !in_array( $custom_post_type, $exclude_post_types ) ) {
		$label = get_post_type_object( $custom_post_type )->labels->name;
		$checked = ( $post_type == $custom_post_type ) ? ' checked' : NULL;
		echo "<input type='radio' name='custom_post' value='{$custom_post_type}'{$checked}> {$label}<br />";
	}
}
?></td></tr>
<tr><th><?php _e( 'Post Status Filter', 'wp-csv' ); ?>:</th><td>
<select name="post_status">
<?php
echo "<option value=''>All (No Filtering)</option>";
foreach ( $post_status_list as $status ) {
	$selected = ( $status == $post_status ) ? ' selected' : '';
	if ( !in_array( $status, Array( 'auto-draft', 'inherit', 'trash' ) ) ) { # Exclude these for now
		echo "<option value='{$status}'{$selected}>{$status}</option>";
	}
}
?></select></td></tr>
<?php
if ( current_user_can( 'manage_options' ) ):
?>
<tr><th><?php _e( 'Minimum Access Level', 'wp-csv' ); ?>:</th><td><select name="access_level">
<option <?php if ($access_level == 'manage_options' ) echo 'selected';?> value="manage_options">Administrator</option>
<option <?php if ($access_level == 'edit_pages' ) echo 'selected';?> value="edit_pages">Editor</option>
<option <?php if ($access_level == 'publish_posts' ) echo 'selected';?> value="publish_posts">Author</option>
<option <?php if ($access_level == 'edit_posts' ) echo 'selected';?> value="edit_posts">Contributor</option>
<option <?php if ($access_level == 'read' ) echo 'selected';?> value="read">Subscriber</option>
</select></td></tr>
<?php
endif;
?>
</tbody>
</table>
<p><input type="submit" value="<?php _e( 'Save and Go To Import/Export', 'wp-csv' ); ?>" /></p>
</form>
