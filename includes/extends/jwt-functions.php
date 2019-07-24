<?php


use \Firebase\JWT\JWT;

add_filter( "jwt_auth_token_before_dispatch", "my_jwt_auth_token_before_dispatch", 10, 2 );
add_filter( "jwt_auth_token_before_sign", "my_jwt_auth_token_before_sign", 10, 2 );

function my_jwt_auth_token_before_sign($token, $user){
    global $wpdb;

	try{
        $user_id = $user->data->ID;

        if ($wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->base_prefix . "user_tokens
            SET
            user_id = %d,
            access_token_valid = %s
            ON DUPLICATE KEY UPDATE
            access_token_valid = %s",
            $user_id,
            $token['exp'],
            $token['exp'])) !== false)
        {
            return $token;
        }
        return $token;

    } catch(Exception $e){
        return new WP_Error(
            'save_token_failed',
            $e->getMessage(),
            array(
                'status' => 403,
            )
        );
    }

    return $token;
}

function my_jwt_auth_token_before_dispatch ( $data, $user )  {
    $data_reset = array();
    
    // foreach( array_keys( $data ) as $key) {
    //     $user_reset[$key] = $data[$key];
    // }

    $token = save_token($data, $user);

    if ($token) {
        $data_reset['wp_user']=$user;
        $data_reset['access_token']= $data['token'];
        $data_reset['expires_in']= DAY_IN_SECONDS * 7;
        $data_reset['refresh_token']=$token['refresh_token'];

        // $user_reset['user_id']=$user->data->ID;
    }
    return $data_reset;

}

function save_token($data, $user) {
    global $wpdb;

	try {
        //$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
        //$access_token = JWT::decode($data['token'], $secret_key);
        $refresh_token = $data['refresh_token'];
        if ($refresh_token == null) {
            $refresh_token = generate_refresh_token();
          }
        $user_id = $user->data->ID;

        if ($wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->base_prefix . "user_tokens
        SET
        user_id = %d,
        access_token = %s,
        refresh_token = %s
        ON DUPLICATE KEY UPDATE
        access_token = %s,
        refresh_token = %s",
        $user_id,
        $data['token'],
        $refresh_token,
        $data['token'],
        $refresh_token)) !== false)
        {
        return [
            'data' => $data,
            'user' => $user,
            'refresh_token' => $refresh_token
        ];
        }

        return false;
    } catch(Exception $e){
        return new WP_Error(
            'save_token_failed',
            $e->getMessage(),
            array(
                'status' => 403,
            )
        );
    }
}

function generate_refresh_token(){
    return bin2hex(openssl_random_pseudo_bytes(32));

}


