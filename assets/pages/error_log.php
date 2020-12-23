<div class="error-log">
<?php
	$filename = ETS_PMPRO_DISCORD_PATH.'logs.txt';
	$handle = fopen($filename, "r");
	while(! feof($handle))
	  {
	  echo fgets($handle). "<br />";
	  }
	fclose($handle);
?>
</div>
<div class="clrbtndiv">
	<input type="button" class="clrbtn btn btn-sm btn-danger" id="clrbtn" name="clrbtn" value="Clear Logs !">
</div>