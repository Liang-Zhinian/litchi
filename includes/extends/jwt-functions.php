<?php



add_filter( "jwt_auth_token_before_dispatch", "my_jwt_auth_token_before_dispatch", 10, 2 );

function my_jwt_auth_token_before_dispatch ( $data, $user )  {
    $user_reset = array();
    
    foreach( array_keys( $data ) as $key) {
        $user_reset[$key] = $data[$key];
    }

	$user_reset['user_id']=$user->data->ID;

    return $user_reset;

}

