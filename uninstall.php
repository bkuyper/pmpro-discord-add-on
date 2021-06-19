<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://www.expresstechsoftwares.com/
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( defined( 'WP_UNINSTALL_PLUGIN' )
		&& $_REQUEST['plugin'] == 'pmpro-discord/pmpro-discord.php'
		&& $_REQUEST['slug'] == 'paid-memberships-pro-discord-add-on'
	&& wp_verify_nonce( $_REQUEST['_ajax_nonce'], 'updates' )
  ) {
    ets_pmpro_discord_clenup_before_uninstall();
	// 		// Remove API credetials.
	// 		delete_option( 'ets_pmpro_discord_client_id' );
	// 		delete_option( 'ets_pmpro_discord_client_secret' );
	// 		delete_option( 'ets_pmpro_discord_bot_token' );
	// 		delete_option( 'ets_pmpro_discord_redirect_url' );
	// 		delete_option( 'ets_pmpro_discord_guild_id' );
	// 		delete_option( 'ets_pmpro_discord_all_roles' );

	// 		// Remove role mapping settings.
	// 		delete_option( 'ets_pmpor_discord_role_mapping' );
	// 		delete_option( '_ets_pmpro_discord_default_role_id' );
	// 		delete_option( 'ets_pmpro_allow_none_member' );

	// 		// Remove advance settings.
	// 		delete_option( 'ets_pmpro_discord_payment_failed' );
	// 		delete_option( 'ets_pmpro_discord_log_api_response' );
	// 		delete_option( 'ets_pmpro_discord_job_queue_concurrency' );
	// 		delete_option( 'ets_pmpro_discord_job_queue_batch_size' );

	// 		// Remove user meta
	// 		$_ets_pmpro_discord_user_ids = get_users( 'fields=ID' );
	// foreach ( $_ets_pmpro_discord_user_ids as $user_id ) {
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_user_id' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_access_token' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_refresh_token' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_role_id' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_default_role_id' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_username' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_expires_in' );
	// 		delete_user_meta( $user_id, '_ets_pmpro_discord_join_date' );
	// }
}

