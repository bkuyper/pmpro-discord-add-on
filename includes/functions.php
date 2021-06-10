<?php
/*
* common functions file.
*/


	/**
	 * This method parse url and append a query param to it.
	 *
	 * @param string $url
	 * @return string $url
	 */
function get_formated_discord_redirect_url( $url ) {
	$parsed = parse_url( $url, PHP_URL_QUERY );
	if ( $parsed === null ) {
		return $url .= '?via=discord';
	} else {
		if ( stristr( $url, 'via=discord' ) !== false ) {
			return $url;
		} else {
			return $url .= '&via=discord';
		}
	}
}

  /**
   * Log API call response
   */
function ets_pmpro_discord_log_api_response( $user_id, $api_url = '', $api_args = array(), $api_response = '' ) {
	  $log_api_response = get_option( 'ets_pmpro_log_api_response' );
	if ( $log_api_response == true ) {
		$log_string  = '==>' . $api_url;
		$log_string .= '-::-' . serialize( $api_args );
		$log_string .= '-::-' . serialize( $api_response );

		$logs = new PMPro_Discord_Logs();
		$logs->write_api_response_logs( $log_string, array(), $user_id );
	}
}

/**
   * Log api erros
   */
	function ets_pmpro_discord_log_api_errors( $api_res = [] ) {
	if ( is_array( $api_res ) ) {
		
		if ($api_res['code'] == 429 || array_key_exists( 'WP_Error', $api_res ) ) {
			return true;
		}
	}
}
