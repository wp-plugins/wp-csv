<h2>Step 1: Export To CSV</h2>
<?php
	if ( !$export_link ) {
		echo "No posts or pages found.  Please create at least one post or page and try again.";
	} else {
		echo "<a href='$export_link'>Click here to download</a>";
	}
?>

<h2>Step 2: Edit The CSV File</h2>
<ul>
<li><strong>CREATE:</strong> Just leave the ID field blank, fill in the other fields, and a new page or post will be created.</li>
<li><strong>MODIFY:</strong> Keep the ID as it is and change the other fields.</li>
<li><strong>DELETE:</strong> Add a minus.  ie to delete post number '123', the ID field should be '-123'.  Don't worry, the post will just be moved to trash, and you'll have 30 days before it gets permanently deleted.</li>
</ul>
<br/>

<form method='post'>
<input type='hidden' name='action' value='import'/>
<input type='submit' value='Next'/>
</fieldset>
</form>
<br/>
