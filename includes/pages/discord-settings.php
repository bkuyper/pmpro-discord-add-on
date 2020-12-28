<form method="post" action="#">
  	<div class="ets-input-group">
  		<label><?php echo __( "Client ID", "ets_pmpro_discord" );?> :</label>
  			<input type="text" class="ets-input" name="ets_discord_client_id" value="<?php if ( isset( $ets_discord_client_id ) )echo $ets_discord_client_id;?>" required placeholder="Discord Client ID">
  	</div>
  	<div class="ets-input-group">
  		<label><?php echo __(  "Client Secret", "ets_pmpro_discord" );?> :</label>
  			<input type="text" class="ets-input" name="ets_discord_client_secret" value="<?php if ( isset( $discord_client_secret) )echo $discord_client_secret;?>" required placeholder="Discord Client Secret">
  	</div>
  	<div class="ets-input-group">
  		<label><?php echo __(  "Bot Token", "ets_pmpro_discord" );?> :</label>
  			<input type="text" class="ets-input" name="ets_discord_bot_token" value="<?php if ( isset( $discord_bot_token ) )echo $discord_bot_token;?>" required placeholder="Discord Bot Token">
  	</div>
  	<div class="ets-input-group">
  		<label><?php echo __(  "Redirect URL", "ets_pmpro_discord" );?> :</label>
  			<input type="text" class="ets-input" name="ets_discord_redirect_url"
  			placeholder="Discord Redirect Url" value="<?php if ( isset( $ets_discord_redirect_url ) )echo $ets_discord_redirect_url;?>" required>
  			<p class="description"><?php echo __( "Registered discord app url", "ets_pmpro_discord" );?></p>
  	</div>
  	<div class="ets-input-group">
  		<label><?php echo __( "Guild Id", "ets_pmpro_discord" );?> :</label>
  			<input type="text" class="ets-input" name="ets_discord_guild_id"
  			placeholder="Discord Guild Id" value="<?php if ( isset( $ets_discord_guild_id ) )echo $ets_discord_guild_id;?>" required>
  	</div>
  	<div class="ets-input-group">
  		<label><?php echo __( "Discord Roles PMPRO-Level Mappings", "ets_pmpro_discord" );?> :</label>
			<textarea class="ets-input" name="ets_discord_role_mapping"
  			placeholder="Discord Roles PMPRO-Level Mappings" required><?php if ( isset( $ets_discord_roles ) )echo stripslashes( $ets_discord_roles );?></textarea>
  	</div>
  	<p>
  		<button type="submit" name="submit" value="ets_submit" class="ets-submit">
  			<?php echo __( "Submit", "ets_pmpro_discord" );?>
  		</button>
  	</p>
</form>