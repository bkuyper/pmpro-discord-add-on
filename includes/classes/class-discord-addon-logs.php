<?php
/**
 * Class to handle log of API errors
 */
class PMPro_Discord_Logs {
	function __construct() {
		// Clear all existing logs.
		add_action( 'wp_ajax_ets_clear_logs', array( $this, 'clear_logs' ) );
	}

	/**
	 * Static property to define log file name
	 *
	 * @param None
	 * @return string $log_file_name
	 */
	public static $log_file_name = 'discord_api_logs.txt';

	/**
	 * Clear previous logs history
	 *
	 * @param None
	 * @return None
	 */
	public function clear_logs() {
		if ( ! is_user_logged_in() && ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( 'You do not have sufficient rights', 403 );
			exit();
		}
		// Check for nonce security
		if ( ! wp_verify_nonce( $_POST['ets_discord_nonce'], 'ets-discord-ajax-nonce' ) ) {
			wp_send_json_error( 'You do not have sufficient rights', 403 );
			exit();
		}
		try {
			$file_name = $this::$log_file_name;
			if ( fopen( ETS_PMPRO_DISCORD_PATH . $file_name, 'w' ) ) {
				$myfile = fopen( ETS_PMPRO_DISCORD_PATH . $file_name, 'w' );
				$txt    = current_time( 'mysql' ) . " => Clear logs Successfully\n";
				fwrite( $myfile, $txt );
				fclose( $myfile );
			} else {
				throw new Exception( 'Could not open the file!' );
			}
		} catch ( Exception $e ) {
			return wp_send_json(
				array(
					'error' => array(
						'msg'  => $e->getMessage(),
						'code' => $e->getCode(),
					),
				)
			);
		}
	}

	/**
	 * Add API error logs into log file
	 *
	 * @param array  $response_arr
	 * @param array  $backtrace_arr
	 * @param string $error_type
	 * @return None
	 */
	public function write_api_response_logs( $response_arr, $backtrace_arr, $error_type, $user_id ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$log_api_res_check = sanitize_text_field( trim( get_option( 'ets_pmpro_log_api_response' ) ) );
		$error        = current_time( 'mysql' );
		$user_details = '';
		if ( $user_id ) {
			$user_details = '::User Id:' . $user_id;
		}
		$log_file_name = $this::$log_file_name;
		if ( is_array( $response_arr ) && array_key_exists( 'code', $response_arr ) ) {
			$error .= '=>File:' . $backtrace_arr['file'] . $user_details . '::Line:' . $backtrace_arr['line'] . '::Function:' . $backtrace_arr['function'] . '::' . $response_arr['code'] . ':' . $response_arr['message'];
		} elseif ( is_array( $response_arr ) && array_key_exists( 'error', $response_arr ) ) {
			$error .= '=>File:' . $backtrace_arr['file'] . $user_details . '::Line:' . $backtrace_arr['line'] . '::Function:' . $backtrace_arr['function'] . '::' . $response_arr['error'];
		} else {
			$error .= print_r( $response_arr, true ).'::'.$user_id;
		}
		if ( $error_type == 'api_error' ) {
			if ( $log_api_res_check == true ) {
				file_put_contents( ETS_PMPRO_DISCORD_PATH . $log_file_name, $error . PHP_EOL, FILE_APPEND | LOCK_EX );
			}
		} else {
			file_put_contents( ETS_PMPRO_DISCORD_PATH . $log_file_name, $error . PHP_EOL, FILE_APPEND | LOCK_EX );
		}
	}
}
new PMPro_Discord_Logs();
