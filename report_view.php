<h2>Import Results</h2>

<table class='widefat'>
<thead>
<tr><th>Type</th><th>Message</th></tr>
</thead>
<tbody>
<?php

if ( is_array( $messages ) && !empty( $messages ) ) {
	foreach( $messages as $message ) {
		echo "<tr><td>{$message->category}</td><td>{$message->msg}</td></tr>";
	} # End foreach
} else {
	echo "<tr><td colspan='2'>Imported successfully, with no errors.</td></tr>";
}

?>
</tbody>
</table>
