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

		add_action( 'ets_pmpro_discord_as_handle_pmpro_expiry', array( $this, 'ets_as_handler_pmpro_expiry' ), 10, 2 );

		add_action( 'ets_pmpro_discord_as_handle_pmpro_cancel', array( $this, 'ets_as_handler_pmpro_cancel' ), 10, 3 );

		add_action( 'ets_pmpro_discord_as_handle_add_member_to_guild', array( $this, 'ets_as_handler_add_member_to_guild' ), 10, 3 );

		add_action( 'ets_pmpro_discord_as_schedule_delete_member', array( $this, 'ets_as_handler_delete_member_from_guild' ), 10, 2 );

		add_action( 'ets_pmpro_discord_as_schedule_member_put_role', array( $this, 'ets_as_handler_put_memberrole' ), 10, 3 );

		add_action( 'ets_pmpro_discord_as_schedule_delete_role', array( $this, 'ets_as_handler_delete_memberrole' ), 10, 3 );

		add_action( 'wp_ajax_ets_discord_member_table_run_api', array( $this, 'ets_discord_member_table_run_api' ) );

		add_action( 'pmpro_stripe_subscription_deleted', array( $this, 'ets_pmpro_stripe_subscription_deleted' ), 10, 1 );

		add_action( 'pmpro_subscription_payment_failed', array( $this, 'ets_pmpro_subscription_payment_failed' ), 10, 1 );

		add_action( 'action_scheduler_failed_execution', array( $this, 'ets_pmpro_discord_reschedule_failed_action' ), 10, 3 );

	}

	/**
	 * Check if the failed action is the PMPRO Discord Add-on and re-schedule it
	 *
	 * @param INT            $action_id
	 * @param OBJECT         $e
	 * @param OBJECT context
	 * @return NONE
	 */
	public function ets_pmpro_discord_reschedule_failed_action( $action_id, $e, $context ) {
		// First check if the action is for PMPRO discord.
		$action_data      = ets_pmpro_discord_as_get_action_data( $action_id );
		$hook             = $action_data['hook'];
		$args             = json_decode( $action_data['args'] );
		$retry_failed_api = sanitize_text_field( trim( get_option( 'ets_pmpro_retry_failed_api' ) ) );
		if ( $retry_failed_api == true && $action_data['as_group'] == ETS_DISCORD_AS_GROUP_NAME && $action_data['status'] = 'failed' ) {

			as_schedule_single_action( ets_pmpro_discord_get_random_timestamp( ets_pmpro_discord_get_highest_last_attempt_timestamp() ), $hook, array_values( $args ), 'ets-pmpro-discord' );
		}

	}
	/**
	 * Create authentication token for discord API
	 *
	 * @param STRING $code
	 * @param INT    $user_id
	 * @return OBJECT API response
	 */
	public function create_discord_auth_token( $code, $user_id ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		// stop users who having the direct URL of discord Oauth.
		// We must check IF NONE members is set to NO and user having no active membership.
		$allow_none_member = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		$curr_level_id     = sanitize_text_field( trim( $this->get_current_level_id( $user_id ) ) );
		if ( $curr_level_id == null && $allow_none_member == 'no' ) {
			return;
		}
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
				ets_pmpro_discord_log_api_response( $user_id, $discord_token_api_url, $args, $response );
				if ( ets_pmpro_discord_check_api_errors( $response ) ) {
					$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
						if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
							$logs = new PMPro_Discord_Logs();
							$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
						}
					}
				}
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
			ets_pmpro_discord_log_api_response( $user_id, $discord_token_api_url, $args, $response );
			if ( ets_pmpro_discord_check_api_errors( $response ) ) {
				$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
					if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
						$logs = new PMPro_Discord_Logs();
						$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
					}
				}
			}
		}
		return $response;
	}

	/**
	 * Get Discord user details from API
	 *
	 * @param STRING $access_token
	 * @return OBJECT REST API response
	 */
	public function get_discord_current_user( $access_token ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$user_id = get_current_user_id();

		$discord_cuser_api_url = ETS_DISCORD_API_URL . 'users/@me';
		$param                 = array(
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . $access_token,
			),
		);
		$user_response         = wp_remote_get( $discord_cuser_api_url, $param );
		ets_pmpro_discord_log_api_response( $user_id, $discord_cuser_api_url, $param, $user_response );

		$response_arr = json_decode( wp_remote_retrieve_body( $user_response ), true );

		if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
			if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
				$logs = new PMPro_Discord_Logs();
				$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
			}
		}

		$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
		return $user_body;

	}

	/**
	 * Add new member into discord guild
	 *
	 * @param INT    $ets_discord_user_id
	 * @param INT    $user_id
	 * @param STRING $access_token
	 * @return NONE
	 */
	public function add_discord_member_in_guild( $ets_discord_user_id, $user_id, $access_token ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$curr_level_id = sanitize_text_field( trim( $this->get_current_level_id( $user_id ) ) );
		if ( $curr_level_id !== null ) {
			// It is possible that we may exhaust API rate limit while adding members to guild, so handling off the job to queue.
			as_schedule_single_action( ets_pmpro_discord_get_random_timestamp( ets_pmpro_discord_get_highest_last_attempt_timestamp() ), 'ets_pmpro_discord_as_handle_add_member_to_guild', array( $ets_discord_user_id, $user_id, $access_token ), ETS_DISCORD_AS_GROUP_NAME );
		}
	}

	/**
	 * Method to add new members to discord guild.
	 *
	 * @param INT    $ets_discord_user_id
	 * @param INT    $user_id
	 * @param STRING $access_token
	 * @return NONE
	 */
	public function ets_as_handler_add_member_to_guild( $ets_discord_user_id, $user_id, $access_token ) {
		// Since we using a queue to delay the API call, there may be a condition when a member is delete from DB. so put a check.
		if ( get_userdata( $user_id ) === false ) {
			return;
		}
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

		$guilds_memeber_api_url = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $ets_discord_user_id;
		$guild_args             = array(
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
		$guild_response         = wp_remote_post( $guilds_memeber_api_url, $guild_args );

		ets_pmpro_discord_log_api_response( $user_id, $guilds_memeber_api_url, $guild_args, $guild_response );
		if ( ets_pmpro_discord_check_api_errors( $guild_response ) ) {

			$response_arr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
			if ( ! is_wp_error( $response_arr ) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
				}
			}
			// this should be catch by Action schedule failed action.
			throw new Exception( 'Failed in function ets_as_handler_add_member_to_guild' );
		}

		update_user_meta( $user_id, 'ets_discord_role_id', $discord_role );
		if ( $discord_role && $discord_role != 'none' && isset( $user_id ) ) {
			$this->put_discord_role_api( $user_id, $discord_role );
		}

		if ( $default_role && $default_role != 'none' && isset( $user_id ) ) {
			$this->put_discord_role_api( $user_id, $default_role );
		}
		if ( empty( get_user_meta( $user_id, '_ets_pmpro_discord_join_date', true ) ) ) {
			update_user_meta( $user_id, '_ets_pmpro_discord_join_date', current_time( 'Y-m-d H:i:s' ) );
		}

	}
	/**
	 * Add new member into discord guild
	 *
	 * @return OBJECT REST API response
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

		$guild_id          = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
		if ( $guild_id && $discord_bot_token ) {
			$discod_server_roles_api = ETS_DISCORD_API_URL . 'guilds/' . $guild_id . '/roles';
			$guild_args              = array(
				'method'  => 'GET',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bot ' . $discord_bot_token,
				),
			);
			$guild_response          = wp_remote_post( $discod_server_roles_api, $guild_args );

			ets_pmpro_discord_log_api_response( $user_id, $discod_server_roles_api, $guild_args, $guild_response );

			$response_arr = json_decode( wp_remote_retrieve_body( $guild_response ), true );

			if ( is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
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

	}

	/**
	 * For authorization process call discord API
	 *
	 * @param NONE
	 * @return OBJECT REST API response
	 */
	public function discord_api_callback() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
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

				if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
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
									if ( ! empty( $ets_discord_role_id ) && $ets_discord_role_id != 'none' ) {
										$this->delete_discord_role( $user_id, $ets_discord_role_id );
									}
								}
								update_user_meta( $user_id, 'ets_discord_user_id', $ets_discord_user_id );
								$this->add_discord_member_in_guild( $ets_discord_user_id, $user_id, $access_token );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Schedule delete existing user from guild
	 *
	 * @param INT  $user_id
	 * @param BOOL $is_schedule
	 * @param NONE
	 */
	public function delete_member_from_guild( $user_id, $is_schedule = true ) {
		if ( $is_schedule && isset( $user_id ) ) {

			as_schedule_single_action( ets_pmpro_discord_get_random_timestamp( ets_pmpro_discord_get_highest_last_attempt_timestamp() ), 'ets_pmpro_discord_as_schedule_delete_member', array( $user_id, $is_schedule ), ETS_DISCORD_AS_GROUP_NAME );
		} else {
			if ( isset( $user_id ) ) {
				$this->ets_as_handler_delete_member_from_guild( $user_id, $is_schedule );
			}
		}
	}

	/**
	 * AS Handling member delete from huild
	 *
	 * @param INT  $user_id
	 * @param BOOL $is_schedule
	 * @return OBJECT API response
	 */
	public function ets_as_handler_delete_member_from_guild( $user_id, $is_schedule ) {
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
		ets_pmpro_discord_log_api_response( $user_id, $guilds_delete_memeber_api_url, $guild_args, $guild_response );
		if ( ets_pmpro_discord_check_api_errors( $guild_response ) ) {
			$response_arr = json_decode( wp_remote_retrieve_body( $guild_response ), true );

			if ( ! is_wp_error( $response_arr ) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
				if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
					$logs = new PMPro_Discord_Logs();
					$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
				}
			}
			if ( $is_schedule ) {
				// this exception should be catch by action scheduler.
				throw new Exception( 'Failed in function ets_as_handler_delete_member_from_guild' );
			}
		}

		/*Delete all usermeta related to discord connection*/
		delete_user_meta( $user_id, 'ets_discord_user_id' );
		delete_user_meta( $user_id, 'ets_discord_access_token' );
		delete_user_meta( $user_id, 'ets_discord_refresh_token' );
		delete_user_meta( $user_id, 'ets_discord_role_id' );
		delete_user_meta( $user_id, 'ets_discord_default_role_id' );
		delete_user_meta( $user_id, 'ets_discord_username' );
		delete_user_meta( $user_id, 'ets_discord_expires_in' );

	}

	/**
	 * API call to change discord user role
	 *
	 * @param INT  $user_id
	 * @param INT  $role_id
	 * @param BOOL $is_schedule
	 * @return object API response
	 */
	public function put_discord_role_api( $user_id, $role_id, $is_schedule = true ) {
		if ( $is_schedule ) {
			as_schedule_single_action( ets_pmpro_discord_get_random_timestamp( ets_pmpro_discord_get_highest_last_attempt_timestamp() ), 'ets_pmpro_discord_as_schedule_member_put_role', array( $user_id, $role_id, $is_schedule ), ETS_DISCORD_AS_GROUP_NAME );
		} else {
			$this->ets_as_handler_put_memberrole( $user_id, $role_id, $is_schedule );
		}
	}

	/**
	 * Action Schedule handler for mmeber change role discord.
	 *
	 * @param INT  $user_id
	 * @param INT  $role_id
	 * @param BOOL $is_schedule
	 * @return object API response
	 */
	public function ets_as_handler_put_memberrole( $user_id, $role_id, $is_schedule ) {
		$access_token                = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_access_token', true ) ) );
		$guild_id                    = sanitize_text_field( trim( get_option( 'ets_discord_guild_id' ) ) );
		$ets_discord_user_id         = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_user_id', true ) ) );
		$discord_bot_token           = sanitize_text_field( trim( get_option( 'ets_discord_bot_token' ) ) );
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
			ets_pmpro_discord_log_api_response( $user_id, $discord_change_role_api_url, $param, $response );
			if ( ets_pmpro_discord_check_api_errors( $response ) ) {
				$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! is_wp_error( $response ) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
					if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
						$logs = new PMPro_Discord_Logs();
						$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
					}
				}
				if ( $is_schedule ) {
					// this exception should be catch by action scheduler.
					throw new Exception( 'Failed in function ets_as_handler_put_memberrole' );
				}
			}
		}
	}

	/**
	 * Schedule delete discord role for a member
	 *
	 * @param INT  $user_id
	 * @param INT  $ets_role_id
	 * @param BOOL $is_schedule
	 * @return OBJECT API response
	 */
	public function delete_discord_role( $user_id, $ets_role_id, $is_schedule = true ) {
		if ( $is_schedule ) {
			as_schedule_single_action( ets_pmpro_discord_get_random_timestamp( ets_pmpro_discord_get_highest_last_attempt_timestamp() ), 'ets_pmpro_discord_as_schedule_delete_role', array( $user_id, $ets_role_id, $is_schedule ), ETS_DISCORD_AS_GROUP_NAME );
		} else {
			$this->ets_as_handler_delete_memberrole( $user_id, $ets_role_id, $is_schedule );
		}
	}

	/**
	 * Action Schedule handler to process delete role of a member.
	 *
	 * @param INT  $user_id
	 * @param INT  $ets_role_id
	 * @param BOOL $is_schedule
	 * @return OBJECT API response
	 */
	public function ets_as_handler_delete_memberrole( $user_id, $ets_role_id, $is_schedule = true ) {

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
			ets_pmpro_discord_log_api_response( $user_id, $discord_delete_role_api_url, $param, $response );
			if ( ets_pmpro_discord_check_api_errors( $response ) ) {
				$response_arr = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! is_wp_error( $response ) && is_array( $response_arr ) && ! empty( $response_arr ) ) {
					if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
						$logs = new PMPro_Discord_Logs();
						$logs->write_api_response_logs( $response_arr, debug_backtrace()[0], $user_id );
					}
				}
				if ( $is_schedule ) {
					// this exception should be catch by action scheduler.
					throw new Exception( 'Failed in function ets_as_handler_delete_memberrole' );
				}
			}
			return $response;
		}
	}

	/**
	 * Disconnect user from discord
	 *
	 * @param NONE
	 * @return OBJECT JSON response
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
		if ( $user_id ) {
			$this->delete_member_from_guild( $user_id, false );
			delete_user_meta( $user_id, 'ets_discord_access_token' );
		}
		$event_res = array(
			'status'  => 1,
			'message' => 'Successfully disconnected',
		);
		echo json_encode( $event_res );
		die();
	}

	/**
	 * Manage user roles api calls
	 *
	 * @param NONE
	 * @return OBJECT JSON response
	 */
	public function ets_discord_member_table_run_api() {
		if ( ! is_user_logged_in() && current_user_can( 'edit_user' ) ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}

		// Check for nonce security
		if ( ! wp_verify_nonce( $_POST['ets_discord_nonce'], 'ets-discord-ajax-nonce' ) ) {
				wp_send_json_error( 'You do not have sufficient rights', 403 );
				exit();
		}
		$user_id = sanitize_text_field( $_POST['user_id'] );
		$this->ets_pmpro_discord_set_member_roles( $user_id, false, false, false );

		$event_res = array(
			'status'  => 1,
			'message' => __( 'success', 'ets_pmpro_discord' ),
		);
		return wp_send_json( $event_res );
	}

	/**
	 * Method to adjust level mapped and default role of a member.
	 *
	 * @param INT  $user_id
	 * @param INT  $expired_level_id
	 * @param INT  $cancel_level_id
	 * @param BOOL $is_schedule
	 */
	private function ets_pmpro_discord_set_member_roles( $user_id, $expired_level_id = false, $cancel_level_id = false, $is_schedule = true ) {
		$allow_none_member        = sanitize_text_field( trim( get_option( 'ets_allow_none_member' ) ) );
		$default_role             = sanitize_text_field( trim( get_option( 'ets_discord_default_role_id' ) ) );
		$ets_discord_role_id      = sanitize_text_field( trim( get_user_meta( $user_id, 'ets_discord_role_id', true ) ) );
		$ets_discord_role_mapping = json_decode( get_option( 'ets_discord_role_mapping' ), true );
		$curr_level_id            = sanitize_text_field( trim( $this->get_current_level_id( $user_id ) ) );
		$previous_default_role    = get_user_meta( $user_id, 'ets_discord_default_role_id', true );
		if ( $expired_level_id ) {
			$curr_level_id = $expired_level_id;
		}
		if ( $cancel_level_id ) {
			$curr_level_id = $cancel_level_id;
		}
		// delete already assigned role.
		if ( isset( $ets_discord_role_id ) && $ets_discord_role_id != '' && $ets_discord_role_id != 'none' ) {
			  $this->delete_discord_role( $user_id, $ets_discord_role_id, $is_schedule );
			  delete_user_meta( $user_id, 'ets_discord_role_id', true );
		}
		if ( $curr_level_id !== null ) {
			// Assign role which is mapped to the mmebership level.
			if ( is_array( $ets_discord_role_mapping ) && array_key_exists( 'level_id_' . $curr_level_id, $ets_discord_role_mapping ) ) {
				$mapped_role_id = sanitize_text_field( trim( $ets_discord_role_mapping[ 'level_id_' . $curr_level_id ] ) );
				if ( $mapped_role_id && $expired_level_id == false && $cancel_level_id == false ) {
					$this->put_discord_role_api( $user_id, $mapped_role_id, $is_schedule );
					update_user_meta( $user_id, 'ets_discord_role_id', $mapped_role_id );
				}
			}
		}
		// Assign role which is saved as default.
		if ( $default_role != 'none' ) {
			if ( isset( $previous_default_role ) && $previous_default_role != '' && $previous_default_role != 'none' ) {
					$this->delete_discord_role( $user_id, $previous_default_role, $is_schedule );
			}
			delete_user_meta( $user_id, 'ets_discord_default_role_id', true );
			$this->put_discord_role_api( $user_id, $default_role, $is_schedule );
			update_user_meta( $user_id, 'ets_discord_default_role_id', $default_role );
		} elseif ( $default_role == 'none' ) {
			if ( isset( $previous_default_role ) && $previous_default_role != '' && $previous_default_role != 'none' ) {
				$this->delete_discord_role( $user_id, $previous_default_role, $is_schedule );
			}
			update_user_meta( $user_id, 'ets_discord_default_role_id', $default_role );
		}

		if ( isset( $user_id ) && $allow_none_member == 'no' && $curr_level_id == null ) {
			$this->delete_member_from_guild( $user_id, false );
		}

	}
	/**
	 * Manage user roles on cancel payment
	 *
	 * @param INT $user_id
	 */
	public function ets_pmpro_stripe_subscription_deleted( $user_id ) {
		if ( isset( $user_id ) ) {
			$this->ets_pmpro_discord_set_member_roles( $user_id, false, false, true );
		}
	}

	/**
	 * Manage user roles on subscription  payment failed
	 *
	 * @param ARRAY $old_order
	 */
	public function ets_pmpro_subscription_payment_failed( $old_order ) {
		$user_id         = $old_order->user_id;
		$ets_payment_fld = sanitize_text_field( trim( get_option( 'ets_pmpro_discord_payment_failed' ) ) );

		if ( $ets_payment_fld == true && isset( $user_id ) ) {
			$this->ets_pmpro_discord_set_member_roles( $user_id, false, false, true );
		}
	}

	/*
	* Action scheduler method to process expired pmpro members.
	* @param INT $user_id
	* @param INT $expired_level_id
	*/
	public function ets_as_handler_pmpro_expiry( $user_id, $expired_level_id ) {
		$this->ets_pmpro_discord_set_member_roles( $user_id, $expired_level_id, false, true );
	}

	/*
	* Method to process queue of canceled pmpro members.
	*
	* @param INT $user_id
	* @param INT $level_id
	* @param INT $cancel_level_id
	* @return NONE
	*/
	public function ets_as_handler_pmpro_cancel( $user_id, $level_id, $cancel_level_id ) {
		$this->ets_pmpro_discord_set_member_roles( $user_id, false, $cancel_level_id, true );
	}

	/**
	 * Change discord role from admin user edit.
	 *
	 * @param INT $level_id
	 * @param INT $user_id
	 * @param INT $cancel_level
	 * @return NONE
	 */
	public function ets_change_discord_role_from_pmpro( $level_id, $user_id, $cancel_level ) {
		$this->ets_pmpro_discord_set_member_roles( $user_id, false, false, true );
	}
}
new PMPro_Discord_API();
