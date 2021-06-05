<?php

/**
 * Fired when the plugin is uninstalled.
 * @link       https://www.expresstechsoftwares.com/
 * @since      1.0.0
 *
 */

// If uninstall not called from WordPress, then exit.
if ( 
		defined( 'WP_UNINSTALL_PLUGIN' ) 
		&& $_REQUEST['plugin'] == 'pmpro-discord/pmpro-discord.php'
		&& $_REQUEST['slug'] == 'paid-memberships-pro-discord-add-on'
		&& wp_verify_nonce( $_REQUEST['_ajax_nonce'], 'bulk-plugins' )
  ) {
	update_option('debugg', 'uninstall');
}
