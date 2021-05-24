<?php 
/*
* common functions file.
*/

/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_cancel_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 20 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_cancel_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 5;
		update_option( 'ets_cancel_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}


/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_expiry_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 25 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_expiry_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 6;
		update_option( 'ets_expiry_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}

/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_add_member_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 30 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_add_member_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 4;
		update_option( 'ets_add_member_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}


/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_delete_member_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 30 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_delete_member_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 8;
		update_option( 'ets_delete_member_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}



/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_change_role_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 30 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_change_role_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 3;
		update_option( 'ets_change_role_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}

/*
* return a number, which will be used a time parameter in ActionScheduler
*
* @param BOOL $get_from_db
* @param ARRAY $range
* @return INT
*/
function get_delete_role_seconds( $get_from_db = false, $range = array() ) {
  if ( count($range) > 0 && $get_from_db === false ) {
    return rand( $range[0], $range[1] );
  }
  else if ( $get_from_db === false ) {
    return rand( 5, 30 );
  }
  else {
		$ets_seconds_incrementer = get_option( 'ets_delete_role_seconds' );
		$ets_seconds_incrementer = $ets_seconds_incrementer + 2;
		update_option( 'ets_delete_role_seconds', $ets_seconds_incrementer );
		return $ets_seconds_incrementer;
  }
}


