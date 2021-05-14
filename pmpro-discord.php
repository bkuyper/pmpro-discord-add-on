<?php
/**
 * Plugin Name: PaidMembershipsPro Discord Add-on
 * Plugin URI:  https://www.expresstechsoftwares.com/step-by-step-documentation-guide-on-how-to-connect-pmpro-and-discord-server-using-discord-addon
 * Description: Connect your PaidMebershipPro site to your discord server, enable your members to be part of your community.
 * Version: 1.0.0
 * Author: Strangers Studios & ExpressTech Software Solutions Pvt. Ltd.
 * Text Domain: ets_pmpro_discord
 */

if ( ! defined( 'ABSPATH' ) ) exit;

//create plugin url constant
define('ETS_PMPRO_VERSION', '1.0.0');

//create plugin url constant
define('ETS_PMPRO_DISCORD_URL', plugin_dir_url(__FILE__));

//create plugin path constant
define('ETS_PMPRO_DISCORD_PATH', plugin_dir_path(__FILE__));

//discord API url
define('ETS_DISCORD_API_URL', 'https://discordapp.com/api/v6/');

//discord Bot Permissions
define('ETS_DISCORD_BOT_PERMISSIONS', 8);

//discord api call scopes
define('ETS_DISCORD_OAUTH_SCOPES', 'identify email connections guilds guilds.join gdm.join rpc rpc.notifications.read rpc.voice.read rpc.voice.write rpc.activities.write bot webhook.incoming messages.read applications.builds.upload applications.builds.read applications.commands applications.store.update applications.entitlements activities.read activities.write relationships.read');


//Define Cron Time Schedulers
define('ETS_CRON_NAME_1','Discord Cron hourly cron');
define('ETS_CRON_NAME_2','Discord Cron half-hourly cron');
define('ETS_CRON_NAME_3','Discord Cron five minutes cron');
define('ETS_CRON_TIME_1',3600);
define('ETS_CRON_TIME_2',1800);
define('ETS_CRON_TIME_3',300);

/**
 * Class to connect discord app
 */
class Ets_Pmpro_Add_Discord {
	function __construct() {
		require_once ETS_PMPRO_DISCORD_PATH.'includes/classes/class-pmpro-discord-admin-setting.php';
		require_once ETS_PMPRO_DISCORD_PATH.'includes/classes/class-discord-api.php';
		require_once ETS_PMPRO_DISCORD_PATH.'includes/classes/class-discord-addon-logs.php';
	}
}
new Ets_Pmpro_Add_Discord();