<?php
/**
 * Class to handle discord API calls
 */
class PMPro_Discord_API extends Ets_Pmpro_Admin_Setting {
	function __construct() {
		// Discord api callback
		add_action( 'init', array( $this, 'discord_api_callback' ) );

		// front ajax function to disconnect from discord
		add_action( 'wp_ajax_disconnect_from_discord', array( $this, 'disconnect_from_discord' ) );

		// front ajax function to disconnect from discord
		add_action( 'wp_ajax_load_discord_roles', array( $this, 'ets_load_discord_roles' ) );

		add_action( 'pmpro_after_change_membership_level', array( $this, 'ets_change_discord_role_from_pmpro' ), 10, 4 );

    add_action( 'ets_as_handle_pmpro_expiry', array( $this, 'ets_as_handler_pmpro_expiry'), 10, 2 );

		add_action( 'ets_as_handle_pmpro_cancel', array( $this, 'ets_as_handler_pmpro_cancel'), 10, 3 );

    add_action( 'ets_as_handle_add_member_to_guild', array( $this, 'ets_as_handler_add_member_to_guild'), 10, 3 );

    add_action( 'ets_as_schedule_delete_member', array( $this, 'ets_as_handler_delete_member_from_guild'), 10, 1 );

    add_action( 'ets_as_schedule_member_change_role', array( $this, 'ets_as_handler_change_memberrole'), 10, 2 );

    add_action( 'ets_as_schedule_delete_role', array( $this, 'ets_as_handler_delete_memberrole'), 10, 2 );
	}

	/**
	 * Create authentication token for discord API
	 *
	 * @param string $code
	 * @return object API response
	 */
	public function create_discord_auth_token( $code, $user_id ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		try {
			$response              = '';
			$refresh_token         = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_refresh_token', true ) ) );
			$token_expiry_time     = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_expires_in', true ) ) );
			$discord_token_api_url = ETS_DISCORD_API_URL . 'oauth2/token';
			if ( $refresh_token ) {
				$date              = new DateTime();
				$current_timestamp = $date->getTimestamp();
				if ( $current_timestamp > $token_expiry_time ) {
					$args     = array(
						'method'  => 'POST',
						'headers' => array(
							'Content-Type' => 'application/x-www-form-urlencoded',
						),
						'body'    => array(
							'client_id'     => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
							'client_secret' => sanitize_text_field( trim( get_option( 'ets_discord_client_secret' ) ) ),
							'grant_type'    => 'refresh_token',
							'refresh_token' => $refresh_token,
							'redirect_uri'  => sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
							'scope'         => ETS_DISCORD_OAUTH_SCOPES,
						),
					);
					$response = wp_remote_post( $discord_token_api_url, $args );
				}
			} else {
				$args     = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
					'body'    => array(
						'client_id'     => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
						'client_secret' => sanitize_text_field( trim( get_option( 'ets_discord_client_secret' ) ) ),
						'grant_type'    => 'authorization_code',
						'code'          => $code,
						'redirect_uri'  => sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
						'scope'         => ETS_DISCORD_OAUTH_SCOPES,
					),
				);
				$response = wp_remote_post( $discord_token_api_url, $args );
			}
			$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
				}
			}
			return $response;
		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}

	}

	/**
	 * Get Discord user details from API
	 *
	 * @param string $access_token
	 * @return object API response
	 */
	public function get_discord_current_user( $access_token ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$user_id = get_current_user_id();
		try {
			$discord_cuser_api_url = ETS_DISCORD_API_URL . 'users/@me';
			$param                 = array(
				'headers' => array(
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Authorization' => 'Bearer ' . $access_token,
				),
			);
			$user_response         = wp_remote_get( $discord_cuser_api_url, $param );
			$response_arr          = json_decode( wp_remote_retrieve_body( $user_response ), true );

			if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
				}
			}

			$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
			return $user_body;
		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}
	}

	/**
	 * Add new member into discord guild
	 *
	 * @param int    $ets_discord_user_id
	 * @param int    $user_id
	 * @param string $access_token
	 * @return object API response
	 */
	public function add_discord_member_in_guild( $ets_discord_user_id, $user_id, $access_token ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		try {
			$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );

			if ( ! pmpro_hasMembershipLevel() && $allow_none_member == 'no' ) {
				return;
			}

      // It is possible that we amy exhaust API rate limit while adding members to guild, so handling off the job to queue.
      as_schedule_single_action( strtotime('now') + get_add_member_seconds( true ), 'ets_as_handle_add_member_to_guild' , array ( $ets_discord_user_id, $user_id, $access_token ) );

		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}

	}

  /**
   * Method
  */
  public function ets_as_handler_add_member_to_guild($ets_discord_user_id, $user_id, $access_token ) {
    $guild_id                 = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
    $discord_bot_token        = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
    $default_role             = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
    $ets_discord_role_mapping = json_decode( get_option( 'ets_discord_role_mapping' ), true );
    $discord_role             = '';
    $curr_level_id            = sanitize_text_field( trim( $this->get_current_level_id( $user_id ) ) );

    if ( is_array( $ets_discord_role_mapping ) && array_key_exists( 'level_id_' . $curr_level_id, $ets_discord_role_mapping ) ) {
      $discord_role = sanitize_text_field( trim( $ets_discord_role_mapping[ 'level_id_' . $curr_level_id ] ) );
    } elseif ( $discord_role = '' && $default_role ) {
      $discord_role = $default_role;
    }

    $guilds_memeber_api_url   = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $ets_discord_user_id;
    $guild_args               = array(
      'method'  => 'PUT',
      'headers' => array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bot ' . $discord_bot_token,
      ),
      'body'    => json_encode(
        array(
          'access_token' => $access_token,
          'roles'        => array(
            $discord_role,
          ),
        )
      ),
    );
    $guild_response = wp_remote_post( $guilds_memeber_api_url, $guild_args );
    $response_arr   = json_decode( wp_remote_retrieve_body( $guild_response ), true );

    if ( ! is_wp_error($response_arr) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
      if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
        $logs = new PMPro_Discord_Logs();
        $logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
      }
    }
    update_user_meta( $user_id, 'ets_discord_role_id', $discord_role );
    if ( $discord_role && $discord_role != 'none' ) {
      $this->change_discord_role_api( $user_id, $discord_role );
    }
    if ( $default_role && $default_role != 'none'  ) {
      $this->change_discord_role_api( $user_id, $default_role );
    }

  }
	/**
	 * Description: Add new member into discord guild
	 *
	 * @param int    $ets_discord_user_id
	 * @param int    $user_id
	 * @param string $access_token
	 * @return object API response
	 */
	public function ets_load_discord_roles() {
		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( 'You do not have sufficient rights', 403 );
			exit();
		}
		// Check for nonce security
		if ( ! wp_verify_nonce( $_POST['ets_discord_nonce'], 'ets-discord-ajax-nonce' ) ) {
			wp_send_json_error( 'You do not have sufficient rights', 403 );
			exit();
		}
		$user_id = get_current_user_id();
		try {
			$guild_id          = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
			$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
			if ( $guild_id && $discord_bot_token ) {
				$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/roles';
				$guild_args                    = array(
					'method'  => 'GET',
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bot ' . $discord_bot_token,
					),
				);
				$guild_response                = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
				$response_arr                  = json_decode( wp_remote_retrieve_body( $guild_response ), true );

				if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
					if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
						$logs = new PMPro_Discord_Logs();
						$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
					} else {
						$response_arr['previous_mapping'] = get_option( 'ets_discord_role_mapping' );

						$discord_roles = array();
						foreach ( $response_arr as $key => $value ) {
							$isbot = false;
							if ( is_array( $value ) ) {
								if ( array_key_exists( 'tags', $value ) ) {
									if ( array_key_exists( 'bot_id', $value['tags'] ) ) {
										$isbot = true;
									}
								}
							}
							if ( $key != 'previous_mapping' && $isbot == false && isset( $value['name'] ) && $value['name'] != '@everyone' ) {
								$discord_roles[ $value['id'] ] = $value['name'];
							}
						}
						update_option( 'ets_discord_all_roles', serialize( $discord_roles ) );
					}
				}
				return wp_send_json( $response_arr );
			}
		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}
	}

	/**
	 * Description: For authorization process call discord API
	 *
	 * @param None
	 * @return object API response
	 */
	public function discord_api_callback() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			try {
				if ( isset( $_GET['action'] ) && $_GET['action'] == 'discord-login' ) {
					$params                    = array(
						'client_id'     => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
						'redirect_uri'  => sanitize_text_field( trim( get_option( 'ets_discord_redirect_url' ) ) ),
						'response_type' => 'code',
						'scope'         => 'identify email connections guilds guilds.join messages.read',
					);
					$discord_authorise_api_url = ETS_DISCORD_API_URL . 'oauth2/authorize?' . http_build_query( $params );

          wp_redirect( $discord_authorise_api_url, 302, get_site_url() );
					exit;
				}

				if ( isset( $_GET['action'] ) && $_GET['action'] == 'discord-connectToBot' ) {
					$params                    = array(
						'client_id'   => sanitize_text_field( trim( get_option( 'ets_discord_client_id' ) ) ),
						'permissions' => ETS_DISCORD_BOT_PERMISSIONS,
						'scope'       => 'bot',
						'guild_id'    => sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) ),
					);
					$discord_authorise_api_url = ETS_DISCORD_API_URL . 'oauth2/authorize?' . http_build_query( $params );

					wp_redirect( $discord_authorise_api_url, 302, get_site_url() );
					exit;
				}
				if ( isset( $_GET['code'] ) && isset( $_GET['via'] ) ) {
					$code     = sanitize_text_field( trim( $_GET['code'] ) );
					$response = $this->create_discord_auth_token( $code, $user_id );

					if ( ! empty( $response ) && ! is_wp_error($response) ) {
						$res_body              = json_decode( wp_remote_retrieve_body( $response ), true );
						$discord_exist_user_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
						if ( is_array( $res_body ) ) {
							if ( array_key_exists( 'access_token', $res_body ) ) {
								$access_token = sanitize_text_field( trim( $res_body['access_token'] ) );
								update_user_meta( $user_id, 'ets_discord_access_token', $access_token );
								if ( array_key_exists( 'refresh_token', $res_body ) ) {
									$refresh_token = sanitize_text_field( trim( $res_body['refresh_token'] ) );
									update_user_meta( $user_id, 'ets_discord_refresh_token', $refresh_token );
								}
								if ( array_key_exists( 'expires_in', $res_body ) ) {
									$expires_in = $res_body['expires_in'];
									$date       = new DateTime();
									$date->add( DateInterval::createFromDateString( '' . $expires_in . ' seconds' ) );
									$token_expiry_time = $date->getTimestamp();
									update_user_meta( $user_id, 'ets_discord_expires_in', $token_expiry_time );
								}
								$user_body = $this->get_discord_current_user( $access_token );

								if ( array_key_exists( 'discriminator', $user_body ) ) {
									$discord_user_number           = $user_body['discriminator'];
									$discord_user_name             = $user_body['username'];
									$discord_user_name_with_number = $discord_user_name . '#' . $discord_user_number;
									update_user_meta( $user_id, 'ets_discord_username', $discord_user_name_with_number );
								}
								if ( is_array( $user_body ) && array_key_exists( 'id', $user_body ) ) {
									$ets_discord_user_id = sanitize_text_field( trim( $user_body['id'] ) );
									if ( $discord_exist_user_id == $ets_discord_user_id ) {
										$ets_discord_role_id = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
										$this->delete_discord_role( $user_id, $ets_discord_role_id );
									}
									update_user_meta( $user_id, 'ets_discord_user_id', $ets_discord_user_id );
									$this->add_discord_member_in_guild( $ets_discord_user_id, $user_id, $access_token );
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {
				$error_arr = array( 'error' => $e->getMessage() );
				$logs      = new PMPro_Discord_Logs();
				$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
			}
		}
	}

	/**
	 * Schedule delete existing user from guild
	 *
	 * @param int $user_id
	 */
	public function delete_member_from_guild( $user_id, $is_schedule = true ) {
    if ( $is_schedule ){
      as_schedule_single_action( strtotime('now') + get_delete_member_seconds( true ), 'ets_as_schedule_delete_member' , array ( $user_id ) );
    }
    else {
      $this->ets_as_handler_delete_member_from_guild( $user_id );
    }
	}

  /**
   * AS Handling member delete from huild 
   * @param int $user_id
	 * @return object API response
   */
  public function ets_as_handler_delete_member_from_guild( $user_id ) {

		try {
			$guild_id                      = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
			$discord_bot_token             = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
			$ets_discord_user_id           = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
			$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $ets_discord_user_id;
			$guild_args                    = array(
				'method'  => 'DELETE',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bot ' . $discord_bot_token,
				),
			);
			$guild_response                = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
			$response_arr = json_decode( wp_remote_retrieve_body( $guild_response ), true );

			if ( ! is_wp_error($response_arr) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
				}
			} else {
					/*Delete all usermeta related to discord connection*/
					delete_user_meta( $user_id, 'ets_discord_user_id' );
					delete_user_meta( $user_id, 'ets_discord_access_token' );
					delete_user_meta( $user_id, 'ets_discord_refresh_token' );
					delete_user_meta( $user_id, 'ets_discord_role_id' );
					delete_user_meta( $user_id, 'ets_discord_default_role_id' );
					delete_user_meta( $user_id, 'ets_discord_username' );
					delete_user_meta( $user_id, 'ets_discord_expires_in' );
			}
		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}
  }

	/**
	 * Description: API call to change discord user role
	 *
	 * @param int $user_id
	 * @param int $role_id
	 * @return object API response
	 */
	public function change_discord_role_api( $user_id, $role_id, $is_schedule = true ) {
    if ( $is_schedule ) {
      as_schedule_single_action( strtotime('now') + get_change_role_seconds( true ), 'ets_as_schedule_member_change_role' , array ( $user_id, $role_id ) );
    }
    else { 
      $this->ets_as_handler_change_memberrole( $user_id, $role_id );
    }
	}

  /**
   * Action Schedule handler for mmeber change role discord.
   * 
   * @param int $user_id
	 * @param int $role_id
	 * @return object API response
   */
  public function ets_as_handler_change_memberrole( $user_id, $role_id ) {
    try {
			$access_token                = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_access_token', true ) ) );
			$previous_role               = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
			$guild_id                    = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
			$ets_discord_user_id         = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
			$discord_bot_token           = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
			$default_role                = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
			$discord_change_role_api_url = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $ets_discord_user_id . '/roles/' . $role_id;

			if ( $access_token && $ets_discord_user_id ) {
				$param = array(
					'method'  => 'PUT',
					'headers' => array(
						'Content-Type'   => 'application/json',
						'Authorization'  => 'Bot ' . $discord_bot_token,
						'Content-Length' => 0,
					),
				);

				$response = wp_remote_get( $discord_change_role_api_url, $param );
				$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! is_wp_error( $response ) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
					if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
						$logs = new PMPro_Discord_Logs();
						$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], 'api_error', $user_id );
					}
				}
				if ( ( $default_role != $role_id && $role_id != $previous_role ) || empty( $previous_role ) ) {
					update_user_meta( $user_id, 'ets_discord_role_id', $role_id );
				}
				if ( $default_role == $role_id ) {
					update_user_meta( $user_id, 'ets_discord_default_role_id', $default_role );
				}
			}
		} catch ( Exception $e ) {
				$error_arr = array( 'error' => $e->getMessage() );
				$logs      = new PMPro_Discord_Logs();
				$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}
  }

	/**
	 * Schedule delete discord role for a member
	 *
	 * @param int     $user_id
	 * @param varchar $ets_role_id
	 * @return object API response
	 */
	public function delete_discord_role( $user_id, $ets_role_id, $is_schedule = true ) {
    if ( $is_schedule ) {
      as_schedule_single_action( strtotime('now') + get_delete_role_seconds( true ), 'ets_as_schedule_delete_role' , array ( $user_id, $ets_role_id ) );
    }
    else {
      $this->ets_as_handler_delete_memberrole( $user_id, $ets_role_id );
    }
	}

  /**
   * Action Schedule handler to process delete role of a member.
   * @param int     $user_id
	 * @param varchar $ets_role_id
	 * @return object API response
  */
  public function ets_as_handler_delete_memberrole( $user_id, $ets_role_id ) {
    try {
			$guild_id                    = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
			$ets_discord_user_id         = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
			$discord_bot_token           = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
			$discord_delete_role_api_url = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $ets_discord_user_id . '/roles/' . $ets_role_id;
			if ( $ets_discord_user_id ) {
				$param = array(
					'method'  => 'DELETE',
					'headers' => array(
						'Content-Type'   => 'application/json',
						'Authorization'  => 'Bot ' . $discord_bot_token,
						'Content-Length' => 0,
					),
				);

				$response = wp_remote_request( $discord_delete_role_api_url, $param );
        // Delete user_meta.
        delete_user_meta( $user_id, 'ets_discord_role_id' );
				return $response;
			}
		} catch ( Exception $e ) {
			$error_arr = array( 'error' => $e->getMessage() );
			$logs      = new PMPro_Discord_Logs();
			$logs->write_api_response_logs( $error_arr, debug_backtrace()[0], 'api_error', $user_id );
		}
  }

	/**
	 * Disconnect user from discord
	 *
	 * @param none
	 * @return Object json response
	 */
	public function disconnect_from_discord() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}

		// Check for nonce security
		if ( ! wp_verify_nonce( $_POST['ets_discord_nonce'], 'ets-discord-ajax-nonce' ) ) {
				wp_send_json_error( 'You do not have sufficient rights', 403 );
				exit();
		}
		$user_id = sanitize_text_field( trim( $_POST['user_id'] ) );
		$this->delete_member_from_guild( $user_id, false );
    delete_user_meta( $user_id, 'ets_discord_access_token' );
		$event_res = array(
			'status'  => 1,
			'message' => 'Successfully disconnected',
		);
		echo json_encode( $event_res );
		die();
	}

  /*
  * Action scheduler method to process expired pmpro members.
  */
  public function ets_as_handler_pmpro_expiry( $user_id, $level_id ) {
    $ets_discord_role_mapping = json_decode( get_option( 'ets_discord_role_mapping' ), true );
		$role_id                  = '';
		$role_id                  = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
		$allow_none_member        = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		$ets_discord_role_id      = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
		
		// Case when members are not allowed in guild after expiry.
		if ( $allow_none_member == 'no' ) {
				$this->delete_member_from_guild( $user_id ,false );
		}
		if ( $allow_none_member == 'yes' && ! empty( $ets_discord_role_id ) ) {
      $this->delete_discord_role( $user_id, $ets_discord_role_id, false );
      if ( $role_id!='none' ) {
        $this->change_discord_role_api( $user_id, $role_id, false );
      }
		}
	}

	/*
	* Method to process queue of canceled pmpro members.
	*/
	public function ets_as_handler_pmpro_cancel( $user_id, $level_id , $cancel_level ) {
		$ets_discord_role_mapping = json_decode( get_option( 'ets_discord_role_mapping' ), true );
		$discord_default_role     = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
		$allow_none_member        = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		$ets_discord_role_id      = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
		$ets_discord_user_id      = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
		if ( $ets_discord_user_id ) {
			$this->delete_discord_role( $user_id, $ets_discord_role_id );
			$role_id                = '';
			if ( $discord_default_role ) {
				$role_id = $discord_default_role;
			}
			if ( $allow_none_member == 'no' ) {
				$this->delete_member_from_guild( $user_id );
			}
			if ( $allow_none_member == 'yes' && $role_id != 'none' ) {
				$this->change_discord_role_api( $user_id, $role_id );
			}
		}
	}

	/**
	 * Change discord role from admin user edit.
	 *
	 * @param $level_id
	 * @param $user_id
	 * @param $cancel_level
	 * @return none
	 */
	public function ets_change_discord_role_from_pmpro( $level_id, $user_id, $cancel_level ) {
		// This if condition check if the method is being called from Admin interface
		if( current_user_can( 'edit_user', $user_id ) && isset( $_POST['user_id'] ) ){
			$ets_discord_user_id   = get_user_meta( $user_id, 'ets_discord_user_id', true );
			$ets_discord_role_id   = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
			$default_role          = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
			$previous_default_role = get_user_meta( $user_id, 'ets_discord_default_role_id', true );
			
      // If user is connected to discord.
			if ( $ets_discord_user_id && empty( $cancel_level ) ) {
				if ( isset( $ets_discord_role_id ) && $ets_discord_role_id != '' && $ets_discord_role_id != $default_role ) {
					$this->delete_discord_role( $user_id, $ets_discord_role_id );
				}
				if ( isset( $previous_default_role ) && $previous_default_role != 'none' && $previous_default_role != $default_role && $default_role != 'none' ) {
					$this->delete_discord_role( $user_id, $previous_default_role );
					if ( $default_role != 'none' ) {
						$this->change_discord_role_api( $user_id, $default_role );
					}
				} elseif ( isset( $previous_default_role ) && $previous_default_role != 'none' && $default_role == 'none' ) {
					$this->delete_discord_role( $user_id, $previous_default_role );
					update_user_meta( $user_id, 'ets_discord_default_role_id', $default_role );
				}
				$ets_discord_role_mapping = json_decode( get_option( 'ets_discord_role_mapping' ), true );
				$role_id                  = '';
				$curr_level_id            = $this->get_current_level_id( $user_id );
				if ( $level_id ) {
					if ( is_array( $ets_discord_role_mapping ) && array_key_exists( 'level_id_' . $level_id, $ets_discord_role_mapping ) ) {
						$role_id = $ets_discord_role_mapping[ 'level_id_' . $level_id ];
					}
				}
				if ( ! empty( $role_id ) && $role_id != 'none' && $role_id != '' ) {
					$this->change_discord_role_api( $user_id, $role_id );
				}
			}
		}
	}
}
new PMPro_Discord_API();
