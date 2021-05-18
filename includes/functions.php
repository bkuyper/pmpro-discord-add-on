<?php 
/*
* common functions file.
*/

/*
* return a random number, which will be used a time parameter
* 
* @param ARRAY 
* @return INT
*/
function get_random_second( $range = array(), $get_from_db = false ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 20 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_seconds_incrementer' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 10;
		update_option( 'ets_seconds_incrementer', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}

