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


// Define Cron Time Schedulers
define( 'ETS_CRON_NAME_1', 'Discord Cron hourly cron' );
define( 'ETS_CRON_NAME_2', 'Discord Cron half-hourly cron' );
define( 'ETS_CRON_NAME_3', 'Discord Cron five minutes cron' );
define( 'ETS_CRON_TIME_1', 3600 );
define( 'ETS_CRON_TIME_2', 1800 );
define( 'ETS_CRON_TIME_3', 300 );

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
		// Add cron schedules
		add_filter( 'cron_schedules', array( $this, 'ets_cron_schedules' ) );

		// initiate cron event
		register_activation_hook( __FILE__, array( $this, 'set_up_plugin' ) );

	}
  
	/**
	 * Description: set up the plugin upon activation.
	 *
	 * @param None
	 * @return None
	 */

	public function set_up_plugin() {
		$this->schedule_cron_jobs();
		$this->set_redirect_url_on_pmpro_activation();
	}

	/**
	 * Description: To to save redirect url
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
	 * Description: time scheduler
	 *
	 * @param int $user_id
	 * @param int $level_id
	 * @return array $schedules
	 */
	public function ets_cron_schedules( $schedules ) {
		$schedules['ets_discord_time_1'] = array(
			'interval' => ETS_CRON_TIME_1,
			'display'  => __( ETS_CRON_NAME_1, 'ets_pmpro_discord' ),
		);
		$schedules['ets_discord_time_2'] = array(
			'interval' => ETS_CRON_TIME_2,
			'display'  => __( ETS_CRON_NAME_2, 'ets_pmpro_discord' ),
		);
		$schedules['ets_discord_time_3'] = array(
			'interval' => ETS_CRON_TIME_3,
			'display'  => __( ETS_CRON_NAME_3, 'ets_pmpro_discord' ),
		);
		return $schedules;
	}

	/**
	 * Description: Create cron events
	 *
	 * @param None
	 * @return None
	 */
	public static function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'ets_cron_pmpro_cancelled_members' ) ) {
			wp_schedule_event( time(), 'ets_discord_time_1', 'ets_cron_pmpro_cancelled_members' );
		}
		if ( ! wp_next_scheduled( 'ets_cron_pmpro_expired_members' ) ) {
			wp_schedule_event( time(), 'ets_discord_time_2', 'ets_cron_pmpro_expired_members' );
		}
		if ( ! wp_next_scheduled( 'ets_cron_pmpro_reset_rate_limits' ) ) {
			wp_schedule_event( time(), 'ets_discord_time_3', 'ets_cron_pmpro_reset_rate_limits' );
		}
	}


}
new Ets_Pmpro_Add_Discord();
