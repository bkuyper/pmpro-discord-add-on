<?php
$upon_failed_payment  = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_payment_failed' ) ) );
$log_api_res          = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_log_api_response' ) ) );
$retry_failed_api			= sanitize_text_field( trim( get_option( 'ets_pmpro_retry_failed_api' ) ) );
$set_job_cnrc         = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_job_queue_concurrency' ) ) );
$set_job_q_batch_size = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_job_queue_batch_size' ) ) );
$retry_api_count = sanitize_text_field( trim( get_option( 'ets_pmpro_retry_api_count' ) ) );
$ets_pmpro_discord_send_expiration_warning_dm = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_send_expiration_warning_dm' ) ) );
?>
<form method="post" action="#">
<?php wp_nonce_field( 'save_discord_adv_settings', 'ets_discord_save_adv_settings' ); ?>
  <table class="form-table" role="presentation">
	<tbody>
	  <tr>
		<th scope="row"><?php echo __( 'Re-assign roles upon payment failure', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="upon_failed_payment" type="checkbox" id="upon_failed_payment" 
		<?php
		if ( $upon_failed_payment == true ) {
			echo 'checked="checked"'; }
		?>
		 value="1">
		</fieldset></td>
	  </tr>
    <tr>
		<th scope="row"><?php echo __( 'Send membership expiration warning message', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="ets_pmpro_discord_send_expiration_warning_dm" type="checkbox" id="ets_pmpro_discord_send_expiration_warning_dm" 
		<?php
		if ( $ets_pmpro_discord_send_expiration_warning_dm == true ) {
			echo 'checked="checked"'; }
		?>
		 value="1">
		</fieldset></td>
	  </tr>  
		<tr>
		<th scope="row"><?php echo __( 'Retry Failed API calls', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="retry_failed_api" type="checkbox" id="retry_failed_api" 
		<?php
		if ( $retry_failed_api == true ) {
			echo 'checked="checked"'; }
		?>
		 value="1">
		</fieldset></td>
	  </tr>
    <tr>
		<th scope="row"><?php echo __( 'How many times a failed API call should get re-try', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="ets_pmpro_retry_api_count" type="number" min="1" id="ets_pmpro_retry_api_count" value="<?php if ( isset( $retry_api_count ) ) { echo $retry_api_count; } else { echo 1; } ?>">
		</fieldset></td>
	  </tr> 
	  <tr>
		<th scope="row"><?php echo __( 'Set job queue concurrency', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="set_job_cnrc" type="number" min="1" id="set_job_cnrc" value="<?php if ( isset( $set_job_cnrc ) ) { echo $set_job_cnrc; } else { echo 1; } ?>">
		</fieldset></td>
	  </tr>
	  <tr>
		<th scope="row"><?php echo __( 'Set job queue batch size', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="set_job_q_batch_size" type="number" min="1" id="set_job_q_batch_size" value="<?php if ( isset( $set_job_q_batch_size ) ) { echo $set_job_q_batch_size; } else { echo 10; } ?>">
		</fieldset></td>
	  </tr>
    <tr>
		<th scope="row"><?php echo __( 'Log API calls response (For debugging purpose)', 'ets_pmpro_discord' ); ?></th>
		<td> <fieldset>
		<input name="log_api_res" type="checkbox" id="log_api_res" 
		<?php
		if ( $log_api_res == true ) {
			echo 'checked="checked"'; }
		?>
		 value="1">
		</fieldset></td>
	  </tr>
    
	</tbody>
  </table>
  <div class="bottom-btn">
	<button type="submit" name="adv_submit" value="ets_submit" class="ets-submit ets-bg-green">
	  <?php echo __( 'Save Settings', 'ets_pmpro_discord' ); ?>
	</button>
  </div>
</form>
