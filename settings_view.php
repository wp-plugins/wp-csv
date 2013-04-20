<h2> CSV Settings</h2>
<strong class='red'><?php echo $error;?></strong>
<form method='post'>
<input type='hidden' name='action' value='export'/>
<?php echo $nonce ?>
<p>Delimiter: <input name="delimiter" type="text" length="1" value="<?php echo htmlentities($delimiter); ?>"/><strong> Don't use '~' or ':' if you use advanced category definition (ie 'parent~slug:name')</strong><br/>
<blockquote><i>This is a comma by convention in most countries, but you may wish to change to ';' or some other character.  Please use caution!  This setting should always be the same for export and import!</i></blockquote></p>
<p>Enclosure: <input name="enclosure" type="text" length="1" value="<?php echo htmlentities($enclosure); ?>"/><br/>
<blockquote><i>This character will be used to further define each field.  If your delimiter is a comma, but it appears between two enclosure characters, it will be ignored, thereby allowing you to use it.  Please use caution!  This setting should always be the same for export and import!</i></blockquote></p>
<p>Date format: <select name="date_format"><option <?php if ($date_format == 'US' ) echo 'selected';?> value="US">US (MM/DD/YYYY)</option><option <?php if ($date_format == 'English' ) echo 'selected';?> value="English">English (DD/MM/YYYY)</option></select><br/>
<blockquote><i>Dates are exported as 'YYYY-MM-DD HH:MM:SS' but your spreadsheet will change them automatically.  Please specify the format dates will be in when you re-import a CSV file.</i></blockquote></p>
<p>Encoding: <select name="encoding">
<option <?php if ($encoding == 'UTF-8' ) echo 'selected';?> value="UTF-8">UTF-8</option>
<option <?php if ($encoding == 'UTF-8-BOM' ) echo 'selected';?> value="UTF-8-BOM">UTF-8 (with BOM)</option>
<option <?php if ($encoding == 'ISO-8859-1' ) echo 'selected';?> value="ISO-8859-1">ISO-8859-1 (Latin 1)</option>
<option <?php if ($encoding == 'ISO-8859-2' ) echo 'selected';?> value="ISO-8859-2">ISO-8859-2 (Latin 2)</option>
<option <?php if ($encoding == 'ISO-8859-3' ) echo 'selected';?> value="ISO-8859-3">ISO-8859-3 (Latin 3)</option>
<option <?php if ($encoding == 'ISO-8859-4' ) echo 'selected';?> value="ISO-8859-4">ISO-8859-4 (Baltic)</option>
<option <?php if ($encoding == 'ISO-8859-5' ) echo 'selected';?> value="ISO-8859-5">ISO-8859-5 (Cyrillic)</option>
<option <?php if ($encoding == 'ISO-8859-6' ) echo 'selected';?> value="ISO-8859-6">ISO-8859-6 (Arabic)</option>
<option <?php if ($encoding == 'ISO-8859-8' ) echo 'selected';?> value="ISO-8859-8">ISO-8859-8 (Hebrew)</option>
<option <?php if ($encoding == 'ISO-8859-9' ) echo 'selected';?> value="ISO-8859-9">ISO-8859-9 (Turkish)</option>
<option <?php if ($encoding == 'ISO-8859-15' ) echo 'selected';?> value="ISO-8859-15">ISO-8859-15 (Latin 9)</option>
<option <?php if ($encoding == 'Windows-1252' ) echo 'selected';?> value="Windows-1252">WINDOWS-1252</option>
</select><br/>
<blockquote><i>Please leave this set to UTF-8 unless you're having problems with non-english characters not displaying correctly in old versions of Excel.  Excel 2007+ should be able to open UTF-8 correctly.</i></blockquote></p>
<h2>Backup Your Database!</h2>
<p>If your data is important, then you should always have a full database backup so you can quickly recover if something goes wrong.  I have taken every precaution to protect your data, but it's impossible for me to test every combination of plugins and themes.  If you're unsure how to backup your database, then please talk to your web hosting provider.</p>
<h2>First Time Users</h2>
<p>The best way to start is to export your current pages and posts so you can see what column headings you need and what values Wordpress expects in each column.</p>
<h2>Custom Post Type Filter</h2>
<p><input type="radio" name="custom_post" value="" checked> All (No Filtering)
<?php
$post_types = get_post_types();
$exclude_post_types = Array( 'page', 'post', 'attachment', 'revision', 'nav_menu_item', 'wp-types-group' );
foreach ( $post_types as $post_type ) {
	if ( !in_array( $post_type, $exclude_post_types ) ) {
		$label = get_post_type_object( $post_type )->labels->name;
		echo "<input type='radio' name='custom_post' value='{$post_type}'> {$label}";
	}
}
?>
<br/>
<p><input type="submit" value="Next" />
</form>
