<div class="error-log">
<?php
	$filename = PMPro_Discord_Logs::$log_file_name;
	$handle = fopen(ETS_PMPRO_DISCORD_PATH.$filename, "a+");
	while ( ! feof($handle) ) {
	  echo fgets($handle). "<br />";
	 }
	fclose($handle);
?>
</div>
<img id="image-loader" src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/Spin-Preloader.gif;"?> >
<div class="clrbtndiv">
	<input type="button" class="clrbtn btn btn-sm btn-danger" id="clrbtn" name="clrbtn" value="Clear Logs !">
	<input type="button" value="Refresh" onClick="window.location.reload();">
</div>