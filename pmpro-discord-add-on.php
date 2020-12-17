<?php
/**
 * Plugin Name: PaidMembershipsPro Discord Add-on
 * Plugin URI:  https://www.expresstechsoftwares.com
 * Description: 
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

/**
 * Class to connect discord app
 */
class Ets_Pmpro_Add_Discord
{
	function __construct()
	{
		require_once ETS_PMPRO_DISCORD_PATH.'includes/ets-pmpro-discord-admin-setting.php';
	}
}

$ets_pmpro_add_discord = new Ets_Pmpro_Add_Discord();