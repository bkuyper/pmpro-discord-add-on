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
			wp_send_json_error( 'You do not have sufficient rights', 404 );
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
	 * @param array $responseArr
	 * @param array $backtraceArr
	 * @param string $error_type
	 * @return None
	 */
	public function write_api_response_logs( $responseArr,$backtraceArr,$error_type ) {
		if( !is_user_logged_in() ) {
			wp_send_json_error( 'Unauthorized user', 404 );
			exit();
		}
		$error = current_time( 'mysql' );
		$log_file_name = $this::$log_file_name;
		if ( array_key_exists('code', $responseArr) ) {
			$error .= "=>File:".$backtraceArr['file']."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$responseArr['code'].':'.$responseArr['message'];
		} elseif ( array_key_exists('error', $responseArr) ) {
			$error .= "=>File:".$backtraceArr['file']."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$responseArr['error'];
		} else {
			$error .= print_r($responseArr, true);
		}
			
		file_put_contents(ETS_PMPRO_DISCORD_PATH.$log_file_name, $error.PHP_EOL , FILE_APPEND | LOCK_EX);
	}

}
new PMPro_Discord_Logs();
