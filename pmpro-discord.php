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
		require_once ETS_PMPRO_DISCORD_PATH . 'libraries/action-scheduler/action-scheduler.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/functions.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-pmpro-discord-admin-setting.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-discord-api.php';
		require_once ETS_PMPRO_DISCORD_PATH . 'includes/classes/class-discord-addon-logs.php';
		
		// initiate cron event
		register_activation_hook( __FILE__, array( $this, 'ets_pmpro_discord_set_up_plugin' ) );
	}

	/**
	 * Description: set up the plugin upon activation.
	 *
	 * @param None
	 * @return None
	 */

	public function ets_pmpro_discord_set_up_plugin() {
		$this->set_redirect_url_on_pmpro_activation();
		$this->set_default_setting_values();
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
			$ets_discord_redirect_url = get_formated_discord_redirect_url (get_permalink( $ets_pmpro_profile_page_id ) );
			update_option( 'ets_discord_redirect_url', $ets_discord_redirect_url );
		}
	}
	/**
	 * Set default settings on activation 
	*/
	public function set_default_setting_values(){
		update_option( 'ets_pmpro_discord_payment_failed', false );
		update_option( 'ets_pmpro_log_api_response', false );
		update_option( 'ets_discord_remove_data_on_uninstalling', false );
		update_option( 'ets_pmpro_job_queue', 1 );
		update_option( 'ets_pmpro_job_queue_batch_size', 10 );
	}

}
new Ets_Pmpro_Add_Discord();
