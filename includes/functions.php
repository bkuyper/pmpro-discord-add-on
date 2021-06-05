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