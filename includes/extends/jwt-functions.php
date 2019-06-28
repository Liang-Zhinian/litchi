<?php


use \Firebase\JWT\JWT;

add_filter( "jwt_auth_token_before_dispatch", "my_jwt_auth_token_before_dispatch", 10, 2 );

function my_jwt_auth_token_before_dispatch ( $data, $user )  {
    $data_reset = array();
    
    // foreach( array_keys( $data ) as $key) {
    //     $user_reset[$key] = $data[$key];
    // }

    $data_reset['wp_user']=$user;
    $data_reset['access_token']=$data['token'];
    $data_reset['expires_in']=$data;
    $data_reset['refresh_token']=$data['token'];

	// $user_reset['user_id']=$user->data->ID;

    return $data_reset;

}

