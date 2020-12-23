<?php
/**
 * Class to handle log of API errors
 */
class PMPro_Discord_Logs
{
	function __construct()
	{
		// Clear all existing logs.
		add_action( 'wp_ajax_ets_clear_logs', array($this, 'clear_logs') );
	}

	/**
	 * Description: Clear previous logs history 
	 * @param None
	 * @return None
	 */
	public function clear_logs() {
		try{
			if( fopen(ETS_PMPRO_DISCORD_PATH."logs.txt", "w") ) {
				$myfile = fopen(ETS_PMPRO_DISCORD_PATH."logs.txt", "w");
				$txt = current_time( 'mysql' )." => Clear logs Successfully\n";
				fwrite($myfile, $txt);
				fclose($myfile);
			} else {
				throw new Exception("Could not open the file!");
			}
		}catch (Exception $e) {
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
		$error = current_time( 'mysql' );
		if( array_key_exists('code', $responseArr) ) {
			$error .= "=>File:".$backtraceArr['file']."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$responseArr['code'].':'.$responseArr['message'];
		} else if( array_key_exists('error', $responseArr) ) {
			$error .= "=>File:".$backtraceArr['file']."::Line:".$backtraceArr['line']."::Function:".$backtraceArr['function']."::".$responseArr['error'];
		} else {
			$error .= print_r($responseArr, true);
		}
			
		file_put_contents(ETS_PMPRO_DISCORD_PATH.'logs.txt', $error.PHP_EOL , FILE_APPEND | LOCK_EX);
	}

}
$ets_pmpro_log_setting = new PMPro_Discord_Logs();
