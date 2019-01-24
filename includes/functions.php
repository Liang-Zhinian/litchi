<?php



/**
 * Get store page url of a seller
 *
 * @param int $user_id
 * @return string
 */
function litchi_get_store_url( $user_id ) {
    $userdata         = get_userdata( $user_id );
    $user_nicename    = ( ! false == $userdata ) ? $userdata->user_nicename : '';
    $custom_store_url = litchi_get_option( 'custom_store_url', 'dokan_general', 'store' );

    return sprintf( '%s/%s/', home_url( '/' . $custom_store_url ), $user_nicename );
}


/**
 * Get the value of a settings field
 *
 * @param string $option settings field name
 * @param string $section the section name this field belongs to
 * @param string $default default text if it's not found
 * @return mixed
 */
function litchi_get_option( $option, $section, $default = '' ) {

    $options = get_option( $section );

    if ( isset( $options[ $option ] ) ) {
        return $options[ $option ];
    }

    return $default;
}