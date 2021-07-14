<?php
$ets_pmpro_discord_client_id    = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_client_id' ) ) );
$discord_client_secret    = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_client_secret' ) ) );
$discord_bot_token        = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_bot_token' ) ) );
$ets_pmpro_discord_redirect_url = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_redirect_url' ) ) );
$ets_discord_roles        = sanitize_text_field( trim( get_option( 'ets_pmpor_discord_role_mapping' ) ) );
$ets_pmpro_discord_guild_id     = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_guild_id' ) ) );
?>
<form method="post" action="<?php echo get_site_url().'/wp-admin/admin-post.php' ?>">
 <input type="hidden" name="action" value="pmpro_discord_save_application_details">
	<?php wp_nonce_field( 'save_discord_settings', 'ets_discord_save_settings' ); ?>
	<div class="ets-input-group">
	  <label><?php echo __( 'Client ID', 'ets_pmpro_discord' ); ?> :</label>
		<input type="text" class="ets-input" name="ets_pmpro_discord_client_id" value="<?php if ( isset( $ets_pmpro_discord_client_id ) ) { echo $ets_pmpro_discord_client_id;} ?>" required placeholder="Discord Client ID">
	</div>
	<div class="ets-input-group">
	  <label><?php echo __( 'Client Secret', 'ets_pmpro_discord' ); ?> :</label>
		<input type="text" class="ets-input" name="ets_pmpro_discord_client_secret" value="<?php if ( isset( $discord_client_secret ) ) { echo $discord_client_secret;} ?>" required placeholder="Discord Client Secret">
	</div>
	<div class="ets-input-group">
	  <label><?php echo __( 'Redirect URL', 'ets_pmpro_discord' ); ?> :</label>
		<input type="text" class="ets-input" name="ets_pmpro_discord_redirect_url"
		placeholder="Discord Redirect Url" value="<?php if ( isset( $ets_pmpro_discord_redirect_url ) ) { echo $ets_pmpro_discord_redirect_url;} ?>" required>
		<p class="description"><?php echo __( 'Registered discord app url', 'ets_pmpro_discord' ); ?></p>
	</div>
	<div class="ets-input-group">
	  <label><?php echo __( 'Bot Token', 'ets_pmpro_discord' ); ?> :</label>
		<input type="text" class="ets-input" name="ets_pmpro_discord_bot_token" value="<?php if ( isset( $discord_bot_token ) ) { echo $discord_bot_token;} ?>" required placeholder="Discord Bot Token">
	</div>
	<div class="ets-input-group">
	  <label><?php echo __( 'Server ID', 'ets_pmpro_discord' ); ?> :</label>
		<input type="text" class="ets-input" name="ets_pmpro_discord_guild_id"
		placeholder="Discord Server Id" value="<?php if ( isset( $ets_pmpro_discord_guild_id ) ) { echo $ets_pmpro_discord_guild_id;} ?>" required>
	</div>
	<?php if ( empty( $ets_pmpro_discord_client_id ) || empty( $discord_client_secret ) || empty( $discord_bot_token ) || empty( $ets_pmpro_discord_redirect_url ) || empty( $ets_pmpro_discord_guild_id ) ) { ?>
	  <p class="ets-danger-text description">
		<?php echo __( 'Please save your form', 'ets_pmpro_discord' ); ?>
	  </p>
	<?php } ?>
	<p>
	  <button type="submit" name="submit" value="ets_submit" class="ets-submit ets-bg-green">
		<?php echo __( 'Save Settings', 'ets_pmpro_discord' ); ?>
	  </button>
	  <?php if ( get_option( 'ets_pmpro_discord_client_id' ) ) : ?>
		<a href="?action=discord-connectToBot" class="ets-btn btn-connect-to-bot" id="connect-discord-bot"><?php echo __( 'Connect your Bot', 'ets_pmpro_discord' ); ?> <i class='fab fa-discord'></i></a>
	  <?php endif; ?>
	</p>
</form>
