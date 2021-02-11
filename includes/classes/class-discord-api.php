<?php
/**
 * Class to handle discord API calls
 */
class PMPro_Discord_API {
	function __construct() {
		//Add new button in pmpro profile
		add_action( 'pmpro_show_user_profile', array( $this, 'add_connect_discord_button' ) );

		//Discord api callback
		add_action( 'init', array( $this, 'discord_api_callback' ) );

		//change hook call on cancel and change
		add_action( 'pmpro_after_change_membership_level', array( $this, 'change_discord_role_from_pmpro' ), 10, 3);

		//Pmpro expiry
		add_action( 'pmpro_membership_post_membership_expiry', array( $this, 'pmpro_expiry_membership' ), 10 ,2);

		//front ajax function to disconnect from discord
		add_action( 'wp_ajax_disconnect_from_discord', array( $this, 'disconnect_from_discord' ) );

		//back ajax function to disconnect from discord
        add_action( 'wp_ajax_nopriv_disconnect_from_discord', array( $this, 'disconnect_from_discord' ) );

        //front ajax function to disconnect from discord
		add_action( 'wp_ajax_load_discord_roles', array( $this, 'load_discord_roles' ) );

		//back ajax function to disconnect from discord
        add_action( 'wp_ajax_nopriv_load_discord_roles', array( $this, 'load_discord_roles' ) );
	}
	
	/**
	 * Description: Show status of PMPro connection with user
	 * @param None
	 * @return None 
	 */
	public function add_connect_discord_button() {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}	
		$user_id = get_current_user_id();
		$access_token = get_user_meta( $user_id, "ets_discord_access_token", true );
		$curr_level_id = $this->get_current_level_id( $user_id );
		$default_role = get_option('ets_discord_default_role_id');
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		?>
		<label><?php echo __( "Discord connection", "ets_pmpro_discord" );?></label>
		<?php
		if ( $access_token && (array_key_exists('level_id_'.$curr_level_id, $ets_discord_role_mapping) || $default_role) ) {
			?>
			<a href="#" class="ets-btn btn-disconnect" id="disconnect-discord" data-user-id="<?php echo $user_id; ?>"><?php echo __( "Disconnect From Discord ", "ets_pmpro_discord" );?><i class='fab fa-discord'></i></a>
			<span class="ets-spinner"></span>
		<?php
		} else if ( !$default_role && !array_key_exists('level_id_'.$curr_level_id, $ets_discord_role_mapping) && pmpro_hasMembershipLevel()) {
		?>
		<div class="isa_error">
		   <i class="fa fa-times-circle"></i>
		   <?php echo __( "There is no discord role assigned for your level.", "ets_pmpro_discord" );?>
		</div>
		<?php if($access_token){ ?>
			<a href="#" class="ets-btn btn-disconnect" id="disconnect-discord" data-user-id="<?php echo $user_id; ?>"><?php echo __( "Disconnect From Discord ", "ets_pmpro_discord" );?><i class='fab fa-discord'></i></a>
			<span class="ets-spinner"></span>
		<?php } ?>
		<?php	
		} else if ( !pmpro_hasMembershipLevel()) {
		?>
		<div class="isa_info">
		    <i class="fa fa-info-circle"></i>
		    <?php echo __( "Buy any membership level.", "ets_pmpro_discord" );?>
		</div>
		<?php if($access_token){ ?>
			<a href="#" class="ets-btn btn-disconnect" id="disconnect-discord" data-user-id="<?php echo $user_id; ?>"><?php echo __( "Disconnect From Discord ", "ets_pmpro_discord" );?><i class='fab fa-discord'></i></a>
			<span class="ets-spinner"></span>
		<?php } ?>
		<?php
		} else {
		?>
			<a href="?action=discord-login" class="btn-connect ets-btn" ><?php echo __( "Connect To Discord", "ets_pmpro_discord" );?><i class='fab fa-discord'></i></a>
		<?php
		}
		
	}

	/**
	 * Description: get pmpro current level id
	 * @param int $user_id
	 * @return int $curr_level_id
	 */
	public function get_current_level_id( $user_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		if ( is_user_logged_in() && function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel() ) {
			global $current_user;
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
			$curr_level_id = $membership_level->ID;
			return $curr_level_id;
		}
	}

	/**
	 * Description: Create authentication token for discord API
	 * @param string $code
	 * @return object API response
	 */
	public function create_discord_auth_token( $code ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$discord_token_api_url = ETS_DISCORD_API_URL.'oauth2/token';
		$args = array(
			'method'=> 'POST',
		    'headers' => array(
		        'Content-Type' => 'application/x-www-form-urlencoded'
		    ),
		    'body' => array(
	    		'client_id' => get_option( 'ets_discord_client_id' ),
				  'client_secret' => get_option( 'ets_discord_client_secret' ),
				  'grant_type' => 'authorization_code',
				  'code' => $code,
				  'redirect_uri' =>  get_option( 'ets_discord_redirect_url' ),
				  'scope' => 'identify email connections'
		    )    
		);

		$response = wp_remote_post( $discord_token_api_url, $args );

		$responseArr = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
			if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
			}
		}
		return $response;
	}

	/**
	 * Description: Get Discord user details from API
	 * @param string $access_token
	 * @return object API response
	 */
	public function get_discord_current_user( $access_token ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$discord_cuser_api_url = ETS_DISCORD_API_URL.'users/@me';
		$param = array(
			'headers'      => array(
	        'Content-Type' => 'application/x-www-form-urlencoded',
	        'Authorization' => 'Bearer ' . $access_token
	    	)
	    );
		$user_response = wp_remote_get( $discord_cuser_api_url, $param );
		$responseArr = json_decode( wp_remote_retrieve_body( $user_response ), true );
		if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
			if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
			}
		}
		$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
		return $user_body;
	}

	/**
	 * Description: Add new member into discord guild
	 * @param int $ets_discord_user_id
	 * @param int $user_id
	 * @param string $access_token
	 * @return object API response
	 */
	public function add_discord_member_in_guild( $ets_discord_user_id, $user_id, $access_token ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$guild_id = get_option( 'ets_discord_guild_id' );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$default_role = get_option('ets_discord_default_role_id');
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$discord_role = '';
		$curr_level_id = $this->get_current_level_id( $user_id );
		if (array_key_exists('level_id_'.$curr_level_id, $ets_discord_role_mapping) ) {
			$discord_role = $ets_discord_role_mapping[ 'level_id_'.$curr_level_id ];
		}else if ( $discord_role = '' && $default_role ) {
			$discord_role = $default_role;
		}
		$guilds_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$ets_discord_user_id;
		$guild_args = array(
			'method'  => 'PUT',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    ),
		    'body' => json_encode(
		    	array(
					"access_token" => $access_token,
					"roles"        => [
				            $discord_role
				        ]
				)
	    	)
		);
		update_user_meta( $user_id, 'ets_discord_role_id', $discord_role );
		$guild_response = wp_remote_post( $guilds_memeber_api_url, $guild_args );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
			if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
			}
		}
		if( $discord_role ) {
			$change_response = $this->change_discord_role_api( $user_id, $discord_role );
		}
		if ( $default_role ) {
			$assigned_default_role = $this->change_discord_role_api( $user_id, $default_role );
		}
		return $guild_response;
	}

	/**
	 * Description: Add new member into discord guild
	 * @param int $ets_discord_user_id
	 * @param int $user_id
	 * @param string $access_token
	 * @return object API response
	 */
	public function load_discord_roles() {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$guild_id = get_option( 'ets_discord_guild_id' );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/roles';
		$guild_args = array(
			'method'  => 'GET',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    )   
		);
		$guild_response = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
			if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
			}
		}
		$responseArr['previous_mapping'] = get_option( 'ets_discord_role_mapping' );
		return wp_send_json($responseArr);
	}

	/**
	 * Description: For authorization process call discord API
	 * @param None
	 * @return object API response 
	 */
	public function discord_api_callback() {
		if( is_user_logged_in() ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] == "discord-login" ) {
				$params = array(
				    'client_id' => get_option( 'ets_discord_client_id' ),
				    'redirect_uri' => get_option( 'ets_discord_redirect_url' ),
				    'response_type' => 'code',
				    'scope' => 'identify email connections guilds guilds.join messages.read'
				  );
				$discord_authorise_api_url = ETS_DISCORD_API_URL."oauth2/authorize?".http_build_query( $params );

				header( 'Location: '.$discord_authorise_api_url );
				die();
			}

			if ( isset( $_GET['action'] ) && $_GET['action'] == "discord-connectToBot" ) {
				$params = array(
				    'client_id' => get_option( 'ets_discord_client_id' ),
				    'permissions' => ETS_DISCORD_BOT_PERMISSIONS,
				    'scope' => 'bot',
				    'guild_id' => get_option( 'ets_discord_guild_id' ),
				  );
				$discord_authorise_api_url = ETS_DISCORD_API_URL."oauth2/authorize?".http_build_query( $params );

				header( 'Location: '.$discord_authorise_api_url );
				die();
			}
			if ( isset( $_GET['code'] ) ) {
				$code = $_GET['code'];
				$user_id = get_current_user_id();
				$response = $this->create_discord_auth_token( $code );
				$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
				
				$discord_exist_user_id = get_user_meta( $user_id, "ets_discord_user_id", true );
				
				if ( array_key_exists('access_token', $res_body) ) {				
					$access_token = $res_body['access_token'];
					update_user_meta( $user_id, "ets_discord_access_token", $access_token );
					$user_body = $this->get_discord_current_user( $access_token );
					if ( array_key_exists('id', $user_body) ) {
						$ets_discord_user_id = $user_body['id'];
						if ( $discord_exist_user_id == $ets_discord_user_id ) {
							$role_delete = $this->delete_discord_role( $user_id );
						}
						update_user_meta( $user_id, "ets_discord_user_id", $ets_discord_user_id );
						$guild_response = $this->add_discord_member_in_guild( $ets_discord_user_id, $user_id,$access_token );
					}	
				}
			}
		}
	}

	/**
	 * Description: Delete existing user from guild
	 * @param int $user_id
	 * @return object API response
	 */
	public function delete_member_from_guild( $user_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$guild_id = get_option( 'ets_discord_guild_id' );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$ets_discord_user_id = get_user_meta( $user_id , 'ets_discord_user_id', true );
		$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$ets_discord_user_id;
		$guild_args = array(
			'method'  => 'DELETE',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    )   
		);
		$guild_response = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		delete_user_meta($user_id,'ets_discord_user_id');
		delete_user_meta($user_id,'ets_discord_access_token');
		delete_user_meta($user_id,'ets_discord_role_id');
		if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
			if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
			}
		}
		return $guild_response;
	}
	
	/**
	 * Description: API call to change discord user role
	 * @param int $user_id
	 * @param int $role_id
	 * @return object API response
	 */
	public function change_discord_role_api( $user_id, $role_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$access_token = get_user_meta( $user_id, "ets_discord_access_token", true );
		$previous_role = get_user_meta( $user_id, "ets_discord_role_id", true );
		$guild_id = get_option( 'ets_discord_guild_id' );
		$ets_discord_user_id = get_user_meta( $user_id, 'ets_discord_user_id', true );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$default_role = get_option('ets_discord_default_role_id');
    	$discord_change_role_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$ets_discord_user_id.'/roles/'.$role_id;
		if ( $access_token && $ets_discord_user_id ) {
			$param = array(
						'method'=> 'PUT',
					    'headers' => array(
					        'Content-Type' => 'application/json',
					        'Authorization' => 'Bot ' .$discord_bot_token,
					        'Content-Length' => 0
					    )
					);

			$response = wp_remote_get( $discord_change_role_api_url, $param);
			$responseArr = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
				if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
					$Logs = new PMPro_Discord_Logs();
					$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
				}
			}
			if( ($default_role != $role_id && $role_id != $previous_role) || empty($previous_role) ){
				update_user_meta( $user_id, 'ets_discord_role_id', $role_id );
			}
			return $response;
		}
	}

	/**
	 * Description: Call API to delete existing discord user role
	 * @param int $user_id
	 * @return object API response 
	 */
	public function delete_discord_role( $user_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$access_token = get_user_meta( $user_id, "ets_discord_access_token", true );
		$guild_id = get_option( 'ets_discord_guild_id' );
		$ets_discord_user_id = get_user_meta( $user_id, 'ets_discord_user_id', true );
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$ets_discord_role_id = get_user_meta( $user_id, 'ets_discord_role_id', true );
		$discord_delete_role_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$ets_discord_user_id.'/roles/'.$ets_discord_role_id;
    
		if ( $ets_discord_user_id ) {
			$param = array(
					'method'=> 'DELETE',
				    'headers' => array(
				        'Content-Type' => 'application/json',
				        'Authorization' => 'Bot ' .$discord_bot_token,
				        'Content-Length' => 0
				    )
				);
			
			$response = wp_remote_request( $discord_delete_role_api_url, $param );
			return $response;
		}
	}

	/**
	 * Description: Change discord role form pmpro role
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 * @return object API response
	 */
	public function change_discord_role_from_pmpro( $level_id, $user_id, $cancel_level ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$ets_discord_user_id = get_user_meta( $user_id, 'ets_discord_user_id',true );
		if ( $ets_discord_user_id ) {
			$role_delete = $this->delete_discord_role( $user_id );
			$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
			$discord_default_role = get_option( 'ets_discord_default_role_id' );
			$upon_cancel = get_option( 'ets_upon_cancel' );
			$role_id = '';
			$curr_level_id = $this->get_current_level_id( $user_id );
			if ( $level_id )
			{
				$role_id = $ets_discord_role_mapping['level_id_'.$level_id];
			}
			if ( $cancel_level && $discord_default_role && $upon_cancel == 'default') {

				$role_id = $discord_default_role;
			}

			if ($upon_cancel == 'kick') {
				$response = $this->delete_member_from_guild( $user_id );
			} else if ($upon_cancel == 'default') {
				$role_change = $this->change_discord_role_api( $user_id, $role_id );
			}
		}
	}

	/**
	 * Description:disconnect user from discord
	 * @param none
	 * @return Object json response
	 */
	public function disconnect_from_discord() {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$user_id = $_POST['user_id'];
		$response = $this->delete_member_from_guild( $user_id );
		$event_res = array(
			"status"  => 1,
			"message" => "Successfully disconnected"
		);
		echo json_encode( $event_res );
		die();
	}

	/**
	 * Description: set discord spectator role on pmpro expiry 
	 * @param int $user_id
	 * @param int $level_id
	 * @return None
	 */
	public function pmpro_expiry_membership( $user_id, $level_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}	
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$role_id = '';
		$role_id = get_option('ets_discord_default_role_id');
		$upon_expiry = get_option( 'ets_upon_expiry' );
		if ($upon_expiry == 'kick') {
			$response = $this->delete_member_from_guild( $user_id );
		} else if ($upon_expiry == 'default') {
			$role_delete = $this->delete_discord_role( $user_id );
			$response = $this->change_discord_role_api( $user_id, $role_id );
		}
	}
	

}
new PMPro_Discord_API();
