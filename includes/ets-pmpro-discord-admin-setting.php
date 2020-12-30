<?php
/**
 * Admin setting
 */
class Ets_Pmpro_Admin_Setting {
	function __construct() {
		// Add new menu option in the admin menu.
		add_action( 'admin_menu', array( $this, 'ets_add_new_menu' ) );

		// Add script for back end.	
		add_action( 'admin_enqueue_scripts', array( $this, 'ets_add_script' ) );

		// Add script for front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'ets_add_script' ) );

	}

	/**
	 * Description: Localized script and style 
	 * @param None
	 * @return None
	 */
	public function ets_add_script() {

		wp_register_style(
		    'ets_pmpro_add_discord_style',
		    ETS_PMPRO_DISCORD_URL. 'assets/css/ets-pmpro-discord-style.css'
		); 
		wp_enqueue_style( 'ets_pmpro_add_discord_style' );

		wp_register_style(
		    'ets_pmpro_font_awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'
		); 
		wp_enqueue_style( 'ets_pmpro_font_awesome' );
	  
	    wp_register_script(
			'ets_pmpro_add_discord_script',
			ETS_PMPRO_DISCORD_URL . 'assets/js/ets-pmpro-add-discord-script.js',
			array( 'jquery' )
		);
        wp_enqueue_script( 'ets_pmpro_add_discord_script' );

        wp_register_script(
			'jQuery_ui_script',
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
			array( 'jquery' )
		);
        wp_enqueue_script( 'jQuery_ui_script' );
		
	 	$script_params = array(
			'admin_ajax' 		=> admin_url( 'admin-ajax.php' )
		);  

	  	wp_localize_script( 'ets_pmpro_add_discord_script', 'etsPmproParams', $script_params ); 
	}

	/**
	 * Description: Add menu in PmPro membership dashboard sub-menu
	 * @param None
	 * @return None
	 */
	public function ets_add_new_menu() {
		//Add sub-menu into PmPro main-menus list
		add_submenu_page( 'pmpro-dashboard', __( 'Discord Settings', 'paid-memberships-pro' ), __( 'Discord Settings', 'paid-memberships-pro' ), 'manage_options', 'discord-options', array( $this, 'ets_setting_page' ) );
	}

	/**
	 * Description: Define plugin settings rules
	 * @param None
	 * @return None 
	 */
	public function ets_setting_page() {
		$ets_discord_client_id = isset( $_POST['ets_discord_client_id'] ) ? sanitize_text_field( trim( $_POST['ets_discord_client_id'] ) ) : '';

		$discord_client_secret = isset( $_POST['ets_discord_client_secret'] ) ? sanitize_text_field( trim( $_POST['ets_discord_client_secret'] ) ) : '';

		$discord_bot_token = isset( $_POST['ets_discord_bot_token'] ) ? sanitize_text_field( trim( $_POST['ets_discord_bot_token'] ) ) : '';

		$ets_discord_redirect_url = isset( $_POST['ets_discord_redirect_url'] ) ? sanitize_text_field( trim( $_POST['ets_discord_redirect_url'] ) ) : '';

		$ets_discord_guild_id = isset( $_POST['ets_discord_guild_id'] ) ? sanitize_text_field( trim( $_POST['ets_discord_guild_id'] ) ) : '';

		$ets_discord_roles = isset( $_POST['ets_discord_role_mapping'] ) ? sanitize_textarea_field( trim( $_POST['ets_discord_role_mapping'] ) ) : '';
		
		if ( $ets_discord_client_id )
			update_option( 'ets_discord_client_id',$ets_discord_client_id );
		
		if ( $discord_client_secret)
			update_option( 'ets_discord_client_secret', $discord_client_secret);
		
		if ( $discord_bot_token ) {
			update_option( 'ets_discord_bot_token', $discord_bot_token );
		}

		if ( $ets_discord_redirect_url ) {
			update_option( 'ets_discord_redirect_url', $ets_discord_redirect_url );
		}

		if ( $ets_discord_guild_id ) {
			update_option( 'discord_guild_id', $ets_discord_guild_id );
		}

		if ( $ets_discord_roles ) {
			$ets_discord_roles = stripslashes( $ets_discord_roles );
			update_option( 'ets_discord_role_mapping',$ets_discord_roles );
		}

		$currUserName = "";
		$currentUser = wp_get_current_user();
		if ( $currentUser ) {
			$currUserName = $currentUser->user_login;
		}
		$ets_discord_client_id = get_option( 'ets_discord_client_id' );
		$discord_client_secret = get_option( 'ets_discord_client_secret' );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$ets_discord_redirect_url = get_option( 'ets_discord_redirect_url' );
		$ets_discord_roles = get_option( 'ets_discord_role_mapping' );
		$ets_discord_guild_id = get_option( 'discord_guild_id' );
		?>
		<h1><?php echo __( "PMPRO Discord Add On Settings","ets_pmpro_discord" );?></h1>
		<div class="tab ets-tabs">

		  <button class="ets_tablinks" data-identity="settings" data-toggle="tab" onclick="openTab(event, 'ets_setting' )"><?php echo __( "Discord Settings", "ets_pmpro_discord" ); ?></button>
		   <button class="ets_tablinks" data-identity="level-mapping" data-toggle="tab" onclick="openTab(event, 'ets_level_mapping' )"><?php echo __( "Discord Settings", "ets_pmpro_discord" ); ?></button>
		  <button class="ets_tablinks" data-identity="support" data-toggle="tab" onclick="openTab(event, 'ets_about_us' )"><?php echo __( "Support", "ets_pmpro_discord" ); ?>	
		  </button>
		  <button class="ets_tablinks" data-identity="logs" data-toggle="tab" onclick="openTab(event, 'ets_logs' )"><?php echo __( "Logs", "ets_pmpro_discord" ); ?>	
		  </button>  
		</div>

		<div id="ets_setting" class="ets_tabcontent">
			<?php include( ETS_PMPRO_DISCORD_PATH.'includes/pages/discord-settings.php' ); ?>
		</div>
		<div id="ets_about_us" class="ets_tabcontent">
			<?php include( ETS_PMPRO_DISCORD_PATH.'includes/pages/get-support.php' ); ?>
		</div>
		<div id="ets_logs" class="ets_tabcontent">
			<?php include( ETS_PMPRO_DISCORD_PATH.'includes/pages/error_log.php' ); ?>
		</div>
		<div id="ets_level_mapping" class="ets_tabcontent">
			<?php include( ETS_PMPRO_DISCORD_PATH.'includes/pages/discord-role-level-map.php' ); ?>
		</div>

		<?php
		$this->get_Support_Data();
	}

	/**
	 * Description: Send mail to support form current user
	 * @param None
	 * @return None 
	*/
	public function get_Support_Data() {
		if ( isset( $_POST['save'] ) ) {
			$etsUserName 	= isset( $_POST['ets_user_name'] ) ? sanitize_text_field( trim( $_POST['ets_user_name'] ) ) : "";
			$etsUserEmail 	= isset( $_POST['ets_user_email'] ) ? sanitize_text_field( trim( $_POST['ets_user_email'] ) ) : "";
			$message  		= isset( $_POST['ets_support_msg'] ) ? sanitize_text_field( trim( $_POST['ets_support_msg'] ) ) : "";
			$sub  			= isset( $_POST['ets_support_subject'] ) ? sanitize_text_field( trim( $_POST['ets_support_subject'] ) ) : "";

			if ( $etsUserName && $etsUserEmail && $message && $sub) {
				$subject 		= $sub;
				$to 			= 'contact@expresstechsoftwares.com';
				$content 		= "Name: " .$etsUserName."<br>";
				$content 		.= "Contact Email: " .$etsUserEmail."<br>";
				$content		.=  "Message: ".$message;
			  $headers 		= array();
			  $blogemail 	= get_bloginfo("admin_email");
				$headers[] 		= 'From: '.get_bloginfo("name") .' <'.$blogemail.'>'."\r\n";
				$mail = wp_mail( $to, $subject, $content, $headers );

				if($mail){
				//general admin notice
				?>
				<div class="notice notice-success is-dismissible support-success-msg">
			        <p><?php echo __( 'Your request have been successfully submitted!', 'ets_pmpro_discord' ); ?></p>
			    </div>
				<?php
				}
			} 	
		}
	}

	
}
new Ets_Pmpro_Admin_Setting();
