<h2>Done!</h2>
<ul>
<li><strong>CREATED: </strong><?php echo count($stats['Insert']); ?></li>
<li><strong>MODIFIED: </strong><?php echo count($stats['Update']); ?></li>
<li><strong>DELETED (Moved to trash): </strong><?php echo count($stats['Delete']); ?></li>
</ul>
<br/>
<h2>Backup</h2>
<p>This backup was created before you made any changes.  This is your last chance to download and save it!</p>
<blockquote>
<?php echo "<a href='$backup_link'>Download Backup</a>"; ?>
</blockquote>
<p>You may find this useful, but it is NOT a full or proper backup of your database so please don't rely on it for that purpose.</p>
<br/>

<?php 

if ( count($stats['Error']) > 0 ): ?>

<h2 class="ecsvi_red">Errors</h2>

<table class="widefat" style="width: 95%" cellspacing="0" cellpadding="0">
<thead>
<tr><th>ID</th><th>Message</th></tr>
</thead>
<tbody>
<?php
	$rows = '';
	foreach ( $stats['Error'] as $error ) {
		extract( $error );
		switch ( $error_id ) {
			case ERROR_MISSING_POST_ID:
				$message = 'Unable to find a post with this ID.  Have you previously deleted it?  Try exporting to CSV to get an accurate list of posts.';
				break;
			case ERROR_MISSING_POST_PARENT:
				$message = "Post parent does not exist.  To avoid problems, you'll need to either re-import with a valid post_parent or create a new page with a matching id.";
				break;
			case ERROR_INVALID_AUTHOR:
				$message = "Author could not be found.  Make sure you use the login id for the author not the display name.";
				break;
		}
		$rows .= sprintf( "<tr><td>%s</td><td>%s</td></tr>", $id, $message );
	}
	echo $rows;
?>
</tbody>
</table>
<br/>

<?php endif; ?>



