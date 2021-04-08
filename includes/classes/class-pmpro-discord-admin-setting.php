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

		//Add new button in pmpro profile
		add_action( 'pmpro_show_user_profile', array( $this, 'add_connect_discord_button' ) );

		//initiate cron event
    add_action( 'init', array( $this, 'schedule_cron_jobs' ) );

    add_filter( 'cron_schedules', array( $this, 'ets_cron_schedules' ) );

    //change hook call on cancel and change
		add_action( 'pmpro_after_change_membership_level', array( $this, 'on_cancel_add_member_into_queue' ), 10, 3);

		//Pmpro expiry
		add_action( 'pmpro_membership_post_membership_expiry', array( $this, 'pmpro_expiry_membership' ), 10 ,2);

		add_action( 'ets_cron_pmpro_reset_rate_limits', array( $this, 'ets_cron_pmpro_reset_rate_limits_hook' ) );
		add_action( 'pmpro_delete_membership_level', array( $this, 'ets_cron_pmpro_add_user_into_cancel_queue' ), 10, 8 );

	}

	/**
	 * Description: Create cron events  
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

	/**
	 * Description: time scheduler 
	 * @param int $user_id
	 * @param int $level_id
	 * @return array $schedules
	 */
	function ets_cron_schedules($schedules){
		$schedules['ets_discord_time_1'] = array(
	            'interval'  => ETS_CRON_TIME_1,
	            'display'   => __( ETS_CRON_NAME_1, 'ets_pmpro_discord' )
	    );
	    $schedules['ets_discord_time_2'] = array(
	            'interval'  => ETS_CRON_TIME_2,
	            'display'   => __( ETS_CRON_NAME_2, 'ets_pmpro_discord' )
	    );
	    $schedules['ets_discord_time_3'] = array(
	            'interval'  => ETS_CRON_TIME_3,
	            'display'   => __( ETS_CRON_NAME_3, 'ets_pmpro_discord' )
	    );
	    return $schedules;
	}

	/**
	 * Description: Show status of PMPro connection with user
	 * @param None
	 * @return None 
	 */
	public function add_connect_discord_button() {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}	
		$user_id = sanitize_text_field( trim( get_current_user_id() ) );

		$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
		$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) ); 
		$default_role = sanitize_text_field( trim( get_option('ets_discord_default_role_id') ) );
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		if ( $this->Check_saved_settings_status() ) {
			if ( $access_token ) {
				?>
				<label class="ets-connection-lbl"><?php echo __( "Discord connection", "ets_pmpro_discord" );?></label>
				<a href="#" class="ets-btn btn-disconnect" id="disconnect-discord" data-user-id="<?php echo $user_id; ?>"><?php echo __( "Disconnect From Discord ", "ets_pmpro_discord" );?><i class='fab fa-discord'></i></a>
				<span class="ets-spinner"></span>
			<?php
			} else if ( pmpro_hasMembershipLevel() || $allow_none_member == 'yes' ) {
			?>
				<label class="ets-connection-lbl"><?php echo __( "Discord connection", "ets_pmpro_discord" );?></label>
				<a href="?action=discord-login" class="btn-connect ets-btn" ><?php echo __( "Connect To Discord", "ets_pmpro_discord" );?> <i class='fab fa-discord'></i></a>
			<?php
			}
		}
		
	}

	/**
	 * Description: get pmpro current level id
	 * @param int $user_id
	 * @return int $curr_level_id
	 */
	public function get_current_level_id( $user_id ) {
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
			if ( $membership_level ) { 
				$curr_level_id = sanitize_text_field( trim( $membership_level->ID ) );
				return $curr_level_id;
			} else {
				return null;
			}
			
	}

	/**
	 * Description: Get user membership status by user_id
	 * @param int $user_id
	 * @return string $status
	 */
	public function ets_cron_pmpro_add_user_into_cancel_queue($level_id) {
		global $wpdb;
	    $table_name = $wpdb->prefix."pmpro_memberships_users";
	    $result = $wpdb->get_results( "SELECT `user_id` FROM $table_name WHERE `membership_id` = $level_id GROUP BY `user_id`" );
	    $ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
	    update_option('ets_admin_level_deleted', true);
		foreach ($result as $key => $ids) {
			$user_id = $ids->user_id;
			$existing_members_queue = sanitize_text_field( trim( get_option('ets_queue_of_pmpro_members') ) );
			$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
			if ( $existing_members_queue ) {
				$members_queue = unserialize($existing_members_queue);
			} else {
				$members_queue = [ "expired" => [], "cancelled" => [] ];
			}
			if ( !in_array($user_id, $members_queue["cancelled"]) && $access_token ) {
				array_push($members_queue["cancelled"], $user_id);
				$members_queue_sr = serialize($members_queue);
				$st = update_option('ets_queue_of_pmpro_members', $members_queue_sr);
			}
		}

		if ( array_key_exists('level_id_'.$level_id, $ets_discord_role_mapping) ) {
			$key = 'level_id_'.$level_id;
			unset( $ets_discord_role_mapping[$key] );
			$mapping = json_encode( $ets_discord_role_mapping );
			update_option( 'ets_discord_role_mapping', $mapping );
		}

	}

	/**
	 * Description: Save cancelled member details into members queue
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 * @return None
	 */
	public function on_cancel_add_member_into_queue( $level_id, $user_id, $cancel_level ) {
		$delete_level_status = get_option("ets_admin_level_deleted");

		$existing_members_queue = sanitize_text_field( trim( get_option('ets_queue_of_pmpro_members') ) );
		$membership_status = sanitize_text_field( trim( $this->ets_check_current_membership_status($user_id) ) );
		$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
		if ( $existing_members_queue ) {
			$members_queue = unserialize($existing_members_queue);
		} else {
			$members_queue = [ "expired" => [], "cancelled" => [] ];
		}
		if ( !empty($cancel_level) && $cancel_level != 0 ) {
			if ( !in_array($user_id, $members_queue["cancelled"]) && $access_token && ( $membership_status == 'cancelled' || $membership_status == 'admin_cancelled' ) ){
				if ( in_array($user_id, $members_queue["expired"]) ) {
					$key = array_search($user_id, $members_queue["expired"]);
					unset( $members_queue["expired"][$key] );
				}
				array_push($members_queue["cancelled"], $user_id);
				$members_queue_sr = serialize($members_queue);
				update_option('ets_queue_of_pmpro_members', $members_queue_sr);
			}	
		} else { 
			if ( !$delete_level_status ){
				if ( in_array($user_id, $members_queue["cancelled"]) ){
					$key = array_search($user_id, $members_queue["cancelled"]);
					unset( $members_queue["cancelled"][$key] );
					$members_queue_sr = serialize($members_queue);
					update_option('ets_queue_of_pmpro_members', $members_queue_sr);
				}
				if ( in_array($user_id, $members_queue["expired"]) ){
					$key = array_search($user_id, $members_queue["expired"]);
					unset( $members_queue["expired"][$key] );
					$members_queue_sr = serialize($members_queue);
					update_option('ets_queue_of_pmpro_members', $members_queue_sr);
				}
			} else {
				delete_option( 'ets_admin_level_deleted' );
			}
		}
	}

	/**
	 * Description: Save expired member details into members queue 
	 * @param int $user_id
	 * @param int $level_id
	 * @return None
	 */
	public function pmpro_expiry_membership( $user_id, $level_id ) {
		$existing_members_queue = sanitize_text_field( trim( get_option('ets_queue_of_pmpro_members') ) );
		$membership_status = sanitize_text_field( trim( $this->ets_check_current_membership_status($user_id) ) );
		$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
		if ( $existing_members_queue ) {
			$members_queue = unserialize($existing_members_queue);
		} else {
			$members_queue = [ "expired" => [], "cancelled" => [] ];
		}
		if ( !in_array($user_id, $members_queue["expired"]) && $membership_status == 'expired' && $access_token ) {
			if ( in_array($user_id, $members_queue["cancelled"]) ) {
				$key = array_search($user_id, $members_queue["cancelled"]);
				unset( $members_queue["cancelled"][$key] );
			}
			array_push($members_queue["expired"], $user_id);
			$members_queue_sr = serialize($members_queue);
			update_option('ets_queue_of_pmpro_members', $members_queue_sr);
		}
	}

	/**
	 * Description: Reset rate limits  
	 * @param None
	 * @return None
	 */
	public function ets_cron_pmpro_reset_rate_limits_hook() {
		$ets_discord_delete_member_rate_limit = sanitize_text_field( trim( get_option('ets_discord_delete_member_rate_limit') ) );
		$ets_discord_delete_role_rate_limit = sanitize_text_field( trim( get_option('ets_discord_delete_role_rate_limit') ) );
		$ets_discord_change_role_rate_limit = sanitize_text_field( trim( get_option('ets_discord_change_role_rate_limit') ) );

		if ( $ets_discord_delete_member_rate_limit <= 1 ) {
			delete_option( 'ets_discord_delete_member_rate_limit' );
		}

		if ( $ets_discord_delete_role_rate_limit <= 1 ) {
			delete_option( 'ets_discord_delete_role_rate_limit' );
		}

		if ( $ets_discord_change_role_rate_limit <= 1 ) {
			delete_option( 'ets_discord_change_role_rate_limit' );
		}
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
		    'ets_pmpro_font_awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css'
		); 
		wp_enqueue_style( 'ets_pmpro_font_awesome' );
	  
	    wp_register_script(
			'ets_pmpro_add_discord_script',
			ETS_PMPRO_DISCORD_URL . 'assets/js/ets-pmpro-add-discord-script.js',
			array( 'jquery' )
		);
        wp_enqueue_script( 'ets_pmpro_add_discord_script' );

        wp_register_script(
			'ets_fab_icon_script',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/js/all.min.js',
			array( 'jquery' )
		);
        wp_enqueue_script( 'ets_fab_icon_script' );

        wp_register_script(
			'jQuery_ui_script',
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
			array( 'jquery' )
		);
        wp_enqueue_script( 'jQuery_ui_script' );
		
	 	$script_params = array(
			'admin_ajax' 		=> admin_url( 'admin-ajax.php' ),
			'permissions_const' => ETS_DISCORD_BOT_PERMISSIONS,
			'is_admin' => is_admin()
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
	 * Description: Get user membership status by user_id
	 * @param int $user_id
	 * @return string $status
	 */
	public function ets_check_current_membership_status($user_id) {
		global $wpdb;
	    $table_name = $wpdb->prefix."pmpro_memberships_users";
	    $result = $wpdb->get_results( "SELECT `status` FROM $table_name WHERE `user_id`= $user_id ORDER BY `id` desc limit 1" );
		return $result[0]->status; 
	}

	/**
	 * Description: Define plugin settings rules
	 * @param None
	 * @return None 
	 */
	public function ets_setting_page() {
		if ( !current_user_can('administrator') ) {
			wp_send_json_error( 'You do not have sufficient rights', 404 );
			exit();
		}
		$ets_discord_client_id = isset( $_POST['ets_discord_client_id'] ) ? sanitize_text_field( trim( $_POST['ets_discord_client_id'] ) ) : '';

		$discord_client_secret = isset( $_POST['ets_discord_client_secret'] ) ? sanitize_text_field( trim( $_POST['ets_discord_client_secret'] ) ) : '';

		$discord_bot_token = isset( $_POST['ets_discord_bot_token'] ) ? sanitize_text_field( trim( $_POST['ets_discord_bot_token'] ) ) : '';

		$ets_discord_redirect_url = isset( $_POST['ets_discord_redirect_url'] ) ? sanitize_text_field( trim( $_POST['ets_discord_redirect_url'] ) ) : '';

		$ets_discord_guild_id = isset( $_POST['ets_discord_guild_id'] ) ? sanitize_text_field( trim( $_POST['ets_discord_guild_id'] ) ) : '';

		$ets_discord_roles = isset( $_POST['ets_discord_role_mapping'] ) ? sanitize_textarea_field( trim( $_POST['ets_discord_role_mapping'] ) ) : '';

		$ets_discord_default_role_id = isset( $_POST['defaultRole'] ) ? sanitize_textarea_field( trim( $_POST['defaultRole'] ) ) : '';

		$upon_expiry = isset( $_POST['upon_expiry'] ) ? sanitize_textarea_field( trim( $_POST['upon_expiry'] ) ) : '';

		$allow_none_member = isset( $_POST['allow_none_member'] ) ? sanitize_textarea_field( trim( $_POST['allow_none_member'] ) ) : '';

		if ( $ets_discord_default_role_id ) {
			update_option( 'ets_discord_default_role_id',$ets_discord_default_role_id );
		}
		
		if ( $ets_discord_client_id ) {
			update_option( 'ets_discord_client_id',$ets_discord_client_id );
		}
		
		if ( $discord_client_secret) {
			update_option( 'ets_discord_client_secret', $discord_client_secret);
		}
		
		if ( $discord_bot_token ) {
			update_option( 'ets_discord_bot_token', $discord_bot_token );
		}

		if ( $ets_discord_redirect_url ) {
			update_option( 'ets_discord_redirect_url', $ets_discord_redirect_url );
		}

		if ( $ets_discord_guild_id ) {
			update_option( 'ets_discord_guild_id', $ets_discord_guild_id );
		}

		if ( $upon_expiry ) {
			update_option( 'ets_upon_expiry', $upon_expiry );
		}

		if ( $allow_none_member ) {
			update_option( 'ets_allow_none_member', $allow_none_member );
		}


		if ( $ets_discord_roles ) {
			$ets_discord_roles = stripslashes( $ets_discord_roles );
			$save_mapping_status = update_option( 'ets_discord_role_mapping',$ets_discord_roles );
			if ( ($save_mapping_status || isset( $_POST['ets_discord_role_mapping'] ) ) && !isset( $_POST['flush'] ) ) {
			?>
			<div class="notice notice-success is-dismissible support-success-msg">
		        <p><?php echo __( 'Your mappings are saved successfully.', 'ets_pmpro_discord' ); ?></p>
		    </div>
		    <?php
			}
		}

		if ( isset( $_POST['flush'] ) ) {
			delete_option('ets_discord_role_mapping');
			delete_option('ets_discord_default_role_id');
			delete_option('ets_upon_expiry');
			delete_option('ets_allow_none_member');
		?>
		<div class="notice notice-success is-dismissible support-success-msg">
	        <p><?php echo __( 'Your settings flushed successfully.', 'ets_pmpro_discord' ); ?></p>
	    </div>
		<?php	
		}
		if ( isset( $_POST['submit'] ) && !isset( $_POST['ets_discord_role_mapping'] ) ) {
 		?>
	 		<div class="notice notice-success is-dismissible support-success-msg">
		        <p><?php echo __( 'Your settings are saved successfully.', 'ets_pmpro_discord' ); ?></p>
		    </div>
		<?php
		}
		$currUserName = "";
		$currentUser = wp_get_current_user();
		if ( $currentUser ) {
			$currUserName = sanitize_text_field( trim( $currentUser->user_login ) );
		}
		$ets_discord_client_id = sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) );
		$discord_client_secret = sanitize_text_field( trim( get_option( 'ets_discord_client_secret' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		$ets_discord_redirect_url = sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) );
		$ets_discord_roles = sanitize_text_field( trim( get_option( 'ets_discord_role_mapping' ) ) );
		$ets_discord_guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		?>
		<h1><?php echo __( "PMPRO Discord Add On Settings","ets_pmpro_discord" );?></h1>
		<div class="tab ets-tabs">

		  <button class="ets_tablinks" data-identity="settings" data-toggle="tab" data-event="ets_setting"><?php echo __( "Discord Settings", "ets_pmpro_discord" ); ?></button>
		  <?php if ( !empty($ets_discord_client_id) && !empty($discord_client_secret) && !empty($discord_bot_token) && !empty($ets_discord_redirect_url) && !empty($ets_discord_guild_id) ): ?>
		   <button class="ets_tablinks" data-identity="level-mapping" data-toggle="tab" data-event="ets_level_mapping"><?php echo __( "Role Mappings", "ets_pmpro_discord" ); ?></button>
		  <?php endif; ?>
		  <button class="ets_tablinks" data-identity="logs" data-toggle="tab" data-event="ets_logs"><?php echo __( "Logs", "ets_pmpro_discord" ); ?>	
		  </button>
		  <button class="ets_tablinks" data-identity="support" data-toggle="tab" data-event="ets_about_us"><?php echo __( "Support", "ets_pmpro_discord" ); ?>	
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
		if ( !current_user_can('administrator') ) {
			wp_send_json_error( 'You do not have sufficient rights', 404 );
			exit();
		}
		if ( isset( $_POST['save'] ) ) {
			$etsUserName 	= isset( $_POST['ets_user_name'] ) ? sanitize_text_field( trim( $_POST['ets_user_name'] ) ) : "";
			$etsUserEmail 	= isset( $_POST['ets_user_email'] ) ? sanitize_text_field( trim( $_POST['ets_user_email'] ) ) : "";
			$message  		= isset( $_POST['ets_support_msg'] ) ? sanitize_text_field( trim( $_POST['ets_support_msg'] ) ) : "";
			$sub  			= isset( $_POST['ets_support_subject'] ) ? sanitize_text_field( trim( $_POST['ets_support_subject'] ) ) : "";

			if ( $etsUserName && $etsUserEmail && $message && $sub ) {
				$subject 		= $sub;
				$to 			= 'contact@expresstechsoftwares.com';
				$content 		= "Name: " .$etsUserName."<br>";
				$content 		.= "Contact Email: " .$etsUserEmail."<br>";
				$content		.=  "Message: ".$message;
			  	$headers 		= array();
			  	$blogemail 	= get_bloginfo("admin_email");
				$headers[] 		= 'From: '.get_bloginfo("name") .' <'.$blogemail.'>'."\r\n";
				$mail = wp_mail( $to, $subject, $content, $headers );

				if ( $mail ) {
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

	/**
	 * Description: To check settings values saved or not
	 * @param None
	 * @return boolean $status 
	*/
	public function Check_saved_settings_status() {
		$ets_discord_client_id = get_option( 'ets_discord_client_id' );
		$ets_discord_client_secret = get_option( 'ets_discord_client_secret' );
		$ets_discord_bot_token = get_option( 'ets_discord_bot_token' );
		$ets_discord_redirect_url = get_option( 'ets_discord_redirect_url' );
		$ets_discord_guild_id = get_option( 'ets_discord_guild_id' );

		if ( $ets_discord_client_id && $ets_discord_client_secret && $ets_discord_bot_token && $ets_discord_redirect_url && $ets_discord_guild_id ) {
			$status = true;
		}else{
			$status = false;
		}

		return $status; 	
	}
}
new Ets_Pmpro_Admin_Setting();
