<?php
$btn_color                          = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_btn_color' ) ) );
$btn_text                        	= sanitize_text_field( trim( get_option( 'ets_pmpro_discord_loggedout_btn_text' ) ) );
$loggedin_btn_text                  = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_loggedin_btn_text' ) ) );
?>
<form method="post" action="<?php echo get_site_url().'/wp-admin/admin-post.php' ?>">
 <input type="hidden" name="action" value="pmpro_discord_save_appearance_settings">
<?php wp_nonce_field( 'save_discord_aprnc_settings', 'ets_discord_save_aprnc_settings' ); ?>
  <table class="form-table" role="presentation">
	<tbody>
    <tr>
		<th scope="row"><?php echo __( 'Button color', 'pmpro-discord-add-on' ); ?></th>
		<td> <fieldset>
		<input name="ets_pmpro_btn_color" type="text" id="ets_pmpro_btn_color" value="<?php if ( $btn_color ) {echo $btn_color; }?>" data-default-color="#ffffff">
		</fieldset></td> 
	</tr>
	<tr>
		<th scope="row"><?php echo __( 'TEXT over to the Button for logged-in users', 'pmpro-discord-add-on' ); ?></th>
		<td> <fieldset>
		<input name="ets_pmpro_loggedin_btn_text" type="text" id="ets_pmpro_loggedin_btn_text" value="<?php if ( $loggedin_btn_text ) {echo $loggedin_btn_text; }?>">
		</fieldset></td> 
	</tr>
	<tr>
		<th scope="row"><?php echo __( 'TEXT over to the Button for non-login users', 'pmpro-discord-add-on' ); ?></th>
		<td> <fieldset>
		<input name="ets_pmpro_loggedout_btn_text" type="text" id="ets_pmpro_loggedout_btn_text" value="<?php if ( $btn_text ) { echo $btn_text; } ?>">
		</fieldset></td> 
	</tr>	
	</tbody>
  </table>
  <div class="bottom-btn">
	<button type="submit" name="apr_submit" value="ets_submit" class="ets-submit ets-bg-green">
	  <?php echo __( 'Save Settings', 'pmpro-discord-add-on' ); ?>
	</button>
  </div>
</form>
