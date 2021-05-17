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
function get_random_second( $range = array() ){
  if ( count($range) > 0 ){
    return rand( $range[0], $range[1] );
  }
  else {
    return rand( 5, 20 );
  }
}

