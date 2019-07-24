<?php

/* 
	由于rest api: 
	/wp-json/wp/v2/comments
	不能返回、保存或者修改meta，因此需要修复这个缺陷
*/

// 查询时返回meta数据
add_filter('rest_prepare_comment', 'rest_prepare_comment');
function rest_prepare_comment($response){
	$data = $response->get_data();
	$response->data['meta'] = get_comment_meta($data['id']);
	return $response;
}

// 保存或者修改meta
add_action('rest_after_insert_comment', 'my_rest_after_insert_comment', 10, 3);
function my_rest_after_insert_comment($comment, $request, $creating){

	$comment_ID = $comment -> comment_ID;
	$metas = $request['meta'];
	
	foreach ( array_keys( $metas ) as $key ) {
		$value = sanitize_text_field( $metas[$key][0] );
		
		if ($creating) {
		    add_comment_meta($comment_ID, $key, $value);
		} else {
			update_comment_meta($comment_ID, $key, $value);
		}
    }

	return $request;
}