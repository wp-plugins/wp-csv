<h2>Step 3: Import Your CSV File</h2> 
<form enctype="multipart/form-data" action="" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_bits ?>" />
<?php echo $nonce ?>
<input type="hidden" name="action" value="report" />
<br/>
Choose a CSV file to upload (32 Mb max size): <input name="uploadedfile" type="file" />
<strong><?php echo $error ?></strong>
<br/>
<br/>
<input type="submit" value="Next" />
</form>
<br/>
