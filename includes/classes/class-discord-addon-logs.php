<?php
/**
 * Class to handle log of API errors
 */
class PMPro_Discord_Logs {
	function __construct() {
		// Clear all existing logs.
		add_action( 'wp_ajax_ets_clear_logs', array($this, 'clear_logs') );
	}

	/**
	 * Description: Static property to define log file name 
	 * @param None
	 * @return string $log_file_name
	 */
	public static $log_file_name = 'discord_api_logs.txt';

	/**
	 * Description: Clear previous logs history 
	 * @param None
	 * @return None
	 */
	public function clear_logs() {
		if( !is_user_logged_in() && !current_user_can('administrator') ) {
			wp_send_json_error( 'You do not have sufficient rights', 403 );
			exit();
		}
		try {
			$file_name = $this::$log_file_name;
			if( fopen(ETS_PMPRO_DISCORD_PATH.$file_name, "w") ) {
				$myfile = fopen(ETS_PMPRO_DISCORD_PATH.$file_name, "w");
				$txt = current_time( 'mysql' )." => Clear logs Successfully\n";
				fwrite($myfile, $txt);
				fclose($myfile);
			} else {
				throw new Exception("Could not open the file!");
			}
		} catch (Exception $e) {
		   return wp_send_json(array(
		        'error' => array(
		            'msg' => $e->getMessage(),
		            'code' => $e->getCode(),
		        ),
		    ));
		}
	}

	/**
	 * Description: Add API error logs into log file 
	 * @param array $response_arr
	 * @param array $backtraceArr
	 * @param string $error_type
	 * @return None
	 */
	public function write_api_response_logs( $response_arr,$backtraceArr,$error_type ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 401 );
			exit();
		}
		$error = current_time( 'mysql' );
		$userid = get_current_user_id();
		$user_details = '';
		if ( $userid ) {
			$user_details = "::User Id:".$userid;
		}
		$log_file_name = $this::$log_file_name;
		if ( array_key_exists('code', $response_arr) ) {
			$error .= "=>File:".$backtraceArr['file'].$user_details."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$response_arr['code'].':'.$response_arr['message'];
		} elseif ( array_key_exists('error', $response_arr) ) {
			$error .= "=>File:".$backtraceArr['file'].$user_details."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$response_arr['error'];
		} else {
			$error .= print_r($response_arr, true);
		}
			
		file_put_contents(ETS_PMPRO_DISCORD_PATH.$log_file_name, $error.PHP_EOL , FILE_APPEND | LOCK_EX);
	}
}
new PMPro_Discord_Logs();
