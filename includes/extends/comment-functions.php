

add_filter('rest_prepare_comment', 'rest_prepare_comment');
function rest_prepare_comment($response){
	// my_log_file($response, 'my_rest_prepare_comment: $response');
	$data = $response->get_data();
	$response->data['meta'] = get_comment_meta($data['id']);
	return $response;
}

add_action('rest_after_insert_comment', 'my_rest_after_insert_comment', 10, 3);
function my_rest_after_insert_comment($comment, $request, $creating){
	my_log_file($comment, 'my_rest_after_insert_comment: $comment');
	my_log_file($request, 'my_rest_after_insert_comment: $request');

	$comment_ID = $comment -> comment_ID;
	$metas = $request['meta'];
	my_log_file($comment_ID, 'my_rest_after_insert_comment: $comment_ID');
	my_log_file($metas, 'my_rest_after_insert_comment: $metas');
	foreach ( array_keys( $metas ) as $key ) {
		my_log_file($key, 'my_rest_after_insert_comment: $key');
		$value = sanitize_text_field( $metas[$key][0] );
		my_log_file($value, 'my_rest_after_insert_comment: $value');
		if ($creating) {
		    add_comment_meta($comment_ID, $key, $value);
		} else {
			update_comment_meta($comment_ID, $key, $value);
		}
    }

	return $request;
}