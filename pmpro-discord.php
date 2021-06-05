<?php
/**
 * Plugin Name: Paid Memberships Pro Discord Add-on
 * Plugin URI:  https://www.expresstechsoftwares.com/step-by-step-documentation-guide-on-how-to-connect-pmpro-and-discord-server-using-discord-addon
 * Description: Connect your PaidMebershipPro site to your discord server, enable your members to be part of your community.
 * Version: 1.0.0
 * Author: Strangers Studios & ExpressTech Software Solutions Pvt. Ltd.
 * Text Domain: ets_pmpro_discord
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// create plugin url constant
define( 'ETS_PMPRO_VERSION', '1.0.0' );

// create plugin url constant
define( 'ETS_PMPRO_DISCORD_URL', plugin_dir_url( __FILE__ ) );

// create plugin path constant
define( 'ETS_PMPRO_DISCORD_PATH', plugin_dir_path( __FILE__ ) );

// discord API url
define( 'ETS_DISCORD_API_URL', 'https://discordapp.com/api/v6/' );

// discord Bot Permissions
define( 'ETS_DISCORD_BOT_PERMISSIONS', 8 );

// discord api call scopes
define( 'ETS_DISCORD_OAUTH_SCOPES', 'identify email connections guilds guilds.join gdm.join rpc rpc.notifications.read rpc.voice.read rpc.voice.write rpc.activities.write bot webhook.incoming messages.read applications.builds.upload applications.builds.read applications.commands applications.store.update applications.entitlements activities.read activities.write relationships.read' );

/**
 * Class to connect discord app
 */
class Ets_Pmpro_Add_Discord {
	function __construct() {
		// Add internal classes
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-pmpro-discord-admin-setting.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-discord-api.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-discord-addon-logs.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'libraries/action-scheduler/action-scheduler.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/functions.php';
		
		// initiate cron event
		register_activation_hook( __FILE__, array( $this, 'ets_pmpro_discord_set_up_plugin' ) );

		register_uninstall_hook( __FILE__, array( $this, 'ets_pmpro_discord_plugin_deactivation' ) );
	}

	/**
	 * Description: set up the plugin upon activation.
	 *
	 * @param None
	 * @return None
	 */

	public function ets_pmpro_discord_set_up_plugin() {
		$this->set_redirect_url_on_pmpro_activation();
	}

	/**
	 * To to save redirect url
	 *
	 * @param None
	 * @return None
	 */
	public function set_redirect_url_on_pmpro_activation() {
		$ets_pre_saved_url         = get_option( 'ets_discord_redirect_url' );
		$ets_pmpro_profile_page_id = get_option( 'pmpro_member_profile_edit_page_id' );
		if ( $ets_pmpro_profile_page_id && empty( $ets_pre_saved_url ) ) {
			$ets_discord_redirect_url = get_permalink( $ets_pmpro_profile_page_id );
			update_option( 'ets_discord_redirect_url', $ets_discord_redirect_url );
		}
	}

	/**
	 * Check if the advanced settings allowed to clean up all DB data.
	 *
	 * @param None
	 * @return None
	 */
	public function ets_pmpro_discord_plugin_deactivation() {
		$deactivate_plugin = sanitize_text_field( trim( get_option( 'ets_discord_remove_data_on_uninstalling' ) ) );

		if ( $deactivate_plugin == true ) {
			// Remove API credetials.
			delete_option( 'ets_discord_client_id' );
			delete_option( 'ets_discord_client_secret' );
			delete_option( 'ets_discord_bot_token' );
			delete_option( 'ets_discord_redirect_url' );
			delete_option( 'ets_discord_guild_id' );

			// Remove role mapping settings.
			delete_option( 'ets_discord_role_mapping' );
			delete_option( 'ets_discord_default_role_id' );
			delete_option( 'ets_upon_expiry' );
			delete_option( 'ets_allow_none_member' );

			// Remove advance settings.
			delete_option( 'ets_pmpro_discord_payment_failed' );
			delete_option( 'ets_pmpro_log_api_response' );
			delete_option( 'ets_pmpro_job_queue' );
			delete_option( 'ets_pmpro_job_queue_batch_size' );
			delete_option( 'ets_discord_remove_data_on_uninstalling' );

			// Remove user meta
			$ets_discord_user_ids = get_users( 'fields=ID' );
			foreach ( $ets_discord_user_ids as $user_id ) {
					delete_user_meta( $user_id, 'ets_discord_user_id' );
					delete_user_meta( $user_id, 'ets_discord_access_token' );
					delete_user_meta( $user_id, 'ets_discord_refresh_token' );
					delete_user_meta( $user_id, 'ets_discord_role_id' );
					delete_user_meta( $user_id, 'ets_discord_default_role_id' );
					delete_user_meta( $user_id, 'ets_discord_username' );
					delete_user_meta( $user_id, 'ets_discord_expires_in' );
					delete_user_meta( $user_id, '_ets_pmpro_discord_join_date' );
			}
		}
	}
}
new Ets_Pmpro_Add_Discord();
