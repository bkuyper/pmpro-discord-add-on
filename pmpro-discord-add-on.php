<?php
/**
 * Plugin Name: PaidMembershipsPro Discord Add-on
 * Plugin URI:  https://www.expresstechsoftwares.com
 * Description: Official PMPRO Add-on, it allows PMPRO Members to connect to any discord server.
 * Version: 1.0.0
 * Author: Strangers Studios & ExpressTech Software Solutions Pvt. Ltd.
 * Text Domain: ets_pmpro_discord
 */

if ( ! defined( 'ABSPATH' ) ) exit;

//create plugin url constant
define('ETS_PMPRO_DISCORD_URL', plugin_dir_url(__FILE__));

//create plugin path constant
define('ETS_PMPRO_DISCORD_PATH', plugin_dir_path(__FILE__));

//discord API url
define('ETS_DISCORD_API_URL', 'https://discordapp.com/api/v6/');

//discord Bot Permissions
define('ETS_DISCORD_BOT_PERMISSIONS', 1007414455);
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