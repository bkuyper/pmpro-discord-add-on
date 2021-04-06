<?php
/**
 * Class to handle discord API calls
 */
class PMPro_Discord_API extends Ets_Pmpro_Admin_Setting {
	function __construct() {
		//Discord api callback
		add_action( 'init', array( $this, 'discord_api_callback' ) );

		//front ajax function to disconnect from discord
		add_action( 'wp_ajax_disconnect_from_discord', array( $this, 'disconnect_from_discord' ) );
		
    //front ajax function to disconnect from discord
		add_action( 'wp_ajax_load_discord_roles', array( $this, 'load_discord_roles' ) );
        
    add_action( 'ets_cron_pmpro_expired_members', array( $this, 'ets_cron_pmpro_expired_members_hook' ) );
        
    add_action( 'ets_cron_pmpro_cancelled_members', array( $this, 'ets_cron_pmpro_cancelled_members_hook' ) );
	}

	/**
	 * Description: Create authentication token for discord API
	 * @param string $code
	 * @return object API response
	 */
	public function create_discord_auth_token( $code, $user_id ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$response = '';
		$refresh_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_refresh_token", true ) ) );
		$token_expiry_time = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_expires_in", true ) ) );
		$discord_token_api_url = ETS_DISCORD_API_URL.'oauth2/token';
		if ( $refresh_token ) {
			$date = new DateTime();
			$current_timestamp = $date->getTimestamp();
			if ( $current_timestamp > $token_expiry_time ) {
				$args = array(
					'method'=> 'POST',
				    'headers' => array(
				        'Content-Type' => 'application/x-www-form-urlencoded'
				    ),
				    'body' => array(
			    		'client_id' => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
						  'client_secret' => sanitize_text_field( trim( get_option( 'ets_discord_client_secret' ) ) ),
						  'grant_type' => 'refresh_token',
						  'refresh_token' => $refresh_token,
						  'redirect_uri' => sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
						  'scope' => 'identify email connections'
				    )    
				);
				$response = wp_remote_post( $discord_token_api_url, $args );
			}
		} else {
			$args = array(
				'method'=> 'POST',
			    'headers' => array(
			        'Content-Type' => 'application/x-www-form-urlencoded'
			    ),
			    'body' => array(
		    		'client_id' => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
					  'client_secret' => sanitize_text_field( trim( get_option( 'ets_discord_client_secret' ) ) ),
					  'grant_type' => 'authorization_code',
					  'code' => $code,
					  'redirect_uri' =>  sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
					  'scope' => 'identify email connections'
			    )    
			);
			$response = wp_remote_post( $discord_token_api_url, $args );
		}
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
			wp_send_json_error( 'Unauthorized user', 401 );
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
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) ); 

		if ( !pmpro_hasMembershipLevel() && $allow_none_member == 'no' ) {
			return;
		}
		$guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		$default_role = sanitize_text_field( trim( get_option('ets_discord_default_role_id') ) );
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$discord_role = '';
		$curr_level_id = sanitize_text_field( trim( $this->get_current_level_id( $user_id ) ) );
		if ( array_key_exists('level_id_'.$curr_level_id, $ets_discord_role_mapping) ) {
			$discord_role = sanitize_text_field( trim( $ets_discord_role_mapping[ 'level_id_'.$curr_level_id ] ) );
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
			$this->change_discord_role_api( $user_id, $discord_role );
		}
		if ( $default_role ) {
			$this->change_discord_role_api( $user_id, $default_role );
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
		if ( !current_user_can('administrator') ) {
			wp_send_json_error( 'You do not have sufficient rights', 404 );
			exit();
		}
		$guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
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
				    'client_id' => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
				    'redirect_uri' => sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
				    'response_type' => 'code',
				    'scope' => 'identify email connections guilds guilds.join messages.read'
				  );
				$discord_authorise_api_url = ETS_DISCORD_API_URL."oauth2/authorize?".http_build_query( $params );

				header( 'Location: '.$discord_authorise_api_url );
				die();
			}

			if ( isset( $_GET['action'] ) && $_GET['action'] == "discord-connectToBot" ) {
				$params = array(
				    'client_id' => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
				    'permissions' => ETS_DISCORD_BOT_PERMISSIONS,
				    'scope' => 'bot',
				    'guild_id' => sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) ),
				  );
				$discord_authorise_api_url = ETS_DISCORD_API_URL."oauth2/authorize?".http_build_query( $params );

				header( 'Location: '.$discord_authorise_api_url );
				die();
			}
			if ( isset( $_GET['code'] ) ) {
				$code = sanitize_text_field( trim( $_GET['code'] ) );
				$user_id = get_current_user_id();
				$response = $this->create_discord_auth_token( $code, $user_id );
				if ( !empty($response ) ) {
					try {
						$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
					} catch ( Exception $e ) {
						$errorArr = array('error' => $e->getMessage());
					  	$Logs = new PMPro_Discord_Logs();
				  		$Logs->write_api_response_logs( $errorArr, debug_backtrace()[0], 'api_error' );
					}
					$discord_exist_user_id = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_user_id", true ) ) );
					
					if ( array_key_exists('access_token', $res_body) ) {				
						$access_token = sanitize_text_field( trim( $res_body['access_token'] ) );
						update_user_meta( $user_id, "ets_discord_access_token", $access_token );
						if ( array_key_exists('refresh_token', $res_body) ) {
							$refresh_token = sanitize_text_field( trim( $res_body['refresh_token'] ) );
							update_user_meta( $user_id, "ets_discord_refresh_token", $refresh_token );
						}
						if ( array_key_exists('expires_in', $res_body) ) {
							$expires_in = $res_body['expires_in'];
							$date = new DateTime();
							$date->add(DateInterval::createFromDateString(''.$expires_in.' seconds')); 
							$token_expiry_time = $date->getTimestamp();
							update_user_meta( $user_id, "ets_discord_expires_in", $token_expiry_time );
						}
						$user_body = $this->get_discord_current_user( $access_token );
						if ( array_key_exists('id', $user_body) ) {
							$ets_discord_user_id = sanitize_text_field( trim( $user_body['id'] ) );
							if ( $discord_exist_user_id == $ets_discord_user_id ) {
								$this->delete_discord_role( $user_id );
							}
							update_user_meta( $user_id, "ets_discord_user_id", $ets_discord_user_id );
							$this->add_discord_member_in_guild( $ets_discord_user_id, $user_id,$access_token );
						}	
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
		$guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		$ets_discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id , 'ets_discord_user_id', true ) ) );
		$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$ets_discord_user_id;
		$guild_args = array(
			'method'  => 'DELETE',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    )   
		);
		$guild_response = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
		update_option( 'ets_discord_delete_member_rate_limit', $guild_response['headers']['x-ratelimit-limit'] );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		delete_user_meta($user_id,'ets_discord_user_id');
		delete_user_meta($user_id,'ets_discord_access_token');
		delete_user_meta($user_id,'ets_discord_refresh_token');
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
		$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
		$previous_role = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_role_id", true ) ) );
		$guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$ets_discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		$default_role = sanitize_text_field( trim( get_option('ets_discord_default_role_id') ) );
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
			try {
				update_option( 'ets_discord_change_role_rate_limit', $response['headers']['x-ratelimit-limit'] );
				$responseArr = json_decode( wp_remote_retrieve_body( $response ), true );
			} catch ( Exception $e ) {
				$errorArr = array('error' => $e->getMessage());
			  	$Logs = new PMPro_Discord_Logs();
		  		$Logs->write_api_response_logs( $errorArr, debug_backtrace()[0], 'api_error' );
			}
			if ( is_array( $responseArr ) && ! empty( $responseArr ) ) {
				if ( array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr) ) {
					$Logs = new PMPro_Discord_Logs();
					$Logs->write_api_response_logs( $responseArr, debug_backtrace()[0], 'api_error' );
				}
			}
			if( ($default_role != $role_id && $role_id != $previous_role) || empty($previous_role) ) {
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
		$access_token = sanitize_text_field( trim( get_user_meta( $user_id, "ets_discord_access_token", true ) ) );
		$guild_id = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$ets_discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		$ets_discord_role_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
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
			update_option( 'ets_discord_delete_role_rate_limit', $response['headers']['x-ratelimit-limit'] );
			return $response;
		}
	}

	/**
	 * Description:disconnect user from discord
	 * @param none
	 * @return Object json response
	 */
	public function disconnect_from_discord() {
		if ( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$user_id = sanitize_text_field( trim( $_POST['user_id'] ) );
		$this->delete_member_from_guild( $user_id );
		$event_res = array(
			"status"  => 1,
			"message" => "Successfully disconnected"
		);
		echo json_encode( $event_res );
		die();
	}

	/**
	 * Description: callback for expired members cron events  
	 * @param None
	 * @return None
	 */
	public function ets_cron_pmpro_expired_members_hook() {
		$ets_members_queue = unserialize(get_option('ets_queue_of_pmpro_members'));
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$role_id = '';
		$role_id = sanitize_text_field( trim( get_option('ets_discord_default_role_id') ) );
		$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		if ( $ets_members_queue ) {
			foreach ($ets_members_queue['expired'] as $key => $user_id) {
				$ets_discord_delete_member_rate_limit = sanitize_text_field( trim( get_option('ets_discord_delete_member_rate_limit') ) );
				$ets_discord_delete_role_rate_limit = sanitize_text_field( trim( get_option('ets_discord_delete_role_rate_limit') ) );
				$ets_discord_change_role_rate_limit = sanitize_text_field( trim( get_option('ets_discord_change_role_rate_limit') ) );
				if ($allow_none_member == 'no') {
					if ( empty($ets_discord_delete_member_rate_limit) || $ets_discord_delete_member_rate_limit > 1 ) {
						$this->delete_member_from_guild( $user_id );
						unset( $ets_members_queue['expired'][$key] );
						$reset_queue = serialize( $ets_members_queue );
						update_option('ets_queue_of_pmpro_members', $reset_queue);
					} else {
						break;
					}
				} else if ($allow_none_member == 'yes' && !empty($role_id) ) {
					if (empty($ets_discord_delete_role_rate_limit) || $ets_discord_delete_role_rate_limit > 1) {
						$this->delete_discord_role( $user_id );
						unset( $ets_members_queue['expired'][$key] );
						$reset_queue = serialize($ets_members_queue);
						update_option('ets_queue_of_pmpro_members', $reset_queue);
					} else {
						break;
					}
					
					if (empty($ets_discord_change_role_rate_limit) || $ets_discord_change_role_rate_limit > 1) {
						$this->change_discord_role_api( $user_id, $role_id );
						unset( $ets_members_queue['expired'][$key] );
						$reset_queue = serialize($ets_members_queue);
						update_option('ets_queue_of_pmpro_members', $reset_queue);
					} else {
						break;
					}
				}
			}
		}
	}

	/**
	 * Description: callback for cancelled members cron events  
	 * @param None
	 * @return None
	 */
	public function ets_cron_pmpro_cancelled_members_hook() {
		$ets_members_queue = unserialize(get_option('ets_queue_of_pmpro_members'));
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$discord_default_role = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
		$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		if ( $ets_members_queue ) {
			foreach ($ets_members_queue['cancelled'] as $key => $user_id) {
				$ets_discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id',true ) ) );
				if ( $ets_discord_user_id ) {
					$role_delete = $this->delete_discord_role( $user_id );
					$role_id = '';
					if ( $discord_default_role ) {

						$role_id = $discord_default_role;
					}

					$ets_discord_delete_member_rate_limit = sanitize_text_field( trim( get_option('ets_discord_delete_member_rate_limit') ) );
					$ets_discord_change_role_rate_limit = sanitize_text_field( trim( get_option('ets_discord_change_role_rate_limit') ) );

					if ( $allow_none_member == 'no' ) {
						if ( empty($ets_discord_delete_member_rate_limit) || $ets_discord_delete_member_rate_limit > 1) {
							$this->delete_member_from_guild( $user_id );
							unset( $ets_members_queue['cancelled'][$key] );
							$reset_queue = serialize($ets_members_queue);
							update_option('ets_queue_of_pmpro_members', $reset_queue);
						} else {
							break;
						}
					} else if ( $allow_none_member == 'yes' ) {
						if ( empty($ets_discord_change_role_rate_limit) || $ets_discord_change_role_rate_limit > 1) {
							$this->change_discord_role_api( $user_id, $role_id );
							unset( $ets_members_queue['cancelled'][$key] );
							$reset_queue = serialize($ets_members_queue);
							update_option('ets_queue_of_pmpro_members', $reset_queue);
						} else {
							break;
						}
					}
				}
			}
		}
	}
}
new PMPro_Discord_API();
