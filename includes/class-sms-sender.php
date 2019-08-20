<?php


/**
 * Litchi Vendor
 *
 * @since 2.6.10
 */
class Litchi_Sms_Sender {

	protected $app_key = '01785d4a4d9a4c3d56a2802eaeaaa52c';

	public function __construct()
	{

	}

	public function sendMessage($mobile, $tpl_id = '179044', $tpl_value='#code#=1234&#company#=聚合数据'){
		$sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL

		$smsConf = array(
			'key'   => $this->app_key, //您申请的APPKEY
			'mobile'    => $mobile, //接受短信的用户手机号码
			'tpl_id'    => $tpl_id, //您申请的短信模板ID，根据实际情况修改
			'tpl_value' =>$tpl_value //您设置的模板变量，根据实际情况修改
		);

		$content = $this->juhecurl($sendUrl,$smsConf,1); //请求发送短信
		if($content){
			$result = json_decode($content,true);
			$error_code = $result['error_code'];
			if($error_code == 0){
				//状态为0，说明短信发送成功
				// echo "短信发送成功,短信ID：".$result['result']['sid'];
				// 
				$this->create_sms_db($mobile, $tpl_id, $tpl_value, $result['result']['sid'], $vercode, 'message');
			}else{
				//状态非0，说明失败
				$msg = $result['reason'];
				//echo "短信发送失败(".$error_code.")：".$msg;
			}
			return $result;
		}else{
			//返回内容异常，以下可根据业务逻辑自行修改
			// 			return "请求发送短信失败";
			return new WP_Error( 'rest_arguments_invalid', __( '请求发送短信失败', 'litchi' ), array( 'status' => 401 ) );
		}
	}

	public function sendVerCode($mobile, $tpl_id = '179045', $vercode = '1234') {
		$sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
		// 		$sendUrl = 'http://v.juhe.cn/vercodesms/send.php'; //验证码短信接口的URL
		$tpl_value='#code#=' . $vercode . '&#company#=聚合数据';
		$smsConf = array(
			'key'   => $this->app_key, //您申请的APPKEY
			'mobile'    => $mobile, //接受短信的用户手机号码
			'tpl_id'    => $tpl_id, //您申请的短信模板ID，根据实际情况修改
			'tpl_value' =>$tpl_value //您设置的模板变量，根据实际情况修改
		);

		$content = $this->juhecurl($sendUrl,$smsConf,1); //请求发送短信

		if($content){
			$result = json_decode($content,true);
			$error_code = $result['error_code'];
			if($error_code == 0){
				//状态为0，说明短信发送成功
				// echo "短信发送成功,短信ID：".$result['result']['sid'];
				// 
				$this->create_sms_db($mobile, $tpl_id, $tpl_value, $result['result']['sid'], $vercode, 'vercode');
			}else{
				//状态非0，说明失败
				$msg = $result['reason'];
				//echo "短信发送失败(".$error_code.")：".$msg;
			}
			return $result;
		}else{
			//返回内容异常，以下可根据业务逻辑自行修改
			// 			return "请求发送短信失败";
			return new WP_Error( 'rest_arguments_invalid', __( '请求发送短信失败', 'litchi' ), array( 'status' => 401 ) );
		}
	}


	/**
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $params [请求的参数]
     * @param  int $ipost [是否采用POST形式]
     * @return  string
     */
	private function juhecurl($url,$params=false,$ispost=0){
		$httpInfo = array();
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
		curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		if( $ispost )
		{
			curl_setopt( $ch , CURLOPT_POST , true );
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
			curl_setopt( $ch , CURLOPT_URL , $url );
		}
		else
		{
			if($params){
				curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
			}else{
				curl_setopt( $ch , CURLOPT_URL , $url);
			}
		}

		$response = curl_exec( $ch );
		if ($response === FALSE) {
			//echo "cURL Error: " . curl_error($ch);
			return false;
		}
		$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
		$httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
		curl_close( $ch );
		return $response;
	}
	
	public function get_sms_db($mobile, $vercode='', $group='vercode') {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms';
		$sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE mobile = %s and vercode = %s and `group` = %s ",
			$mobile, $vercode, $group
		);
		$db_sms_row = $wpdb->get_row($sql);

		return $db_sms_row;
	}

	public function create_sms_db($mobile, $tpl_id, $tpl_value, $sid, $vercode='', $group='default')
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms';

		$db = $wpdb->insert(
			$table_name,
			array(
				'created_time' => current_time('mysql'),
				'mobile' => $mobile,
				'tpl_id' => $tpl_id,
				'tpl_value' => $tpl_value,
				'sid' => $sid,
				'vercode' => $vercode,
				'group' => $group,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			));

			return $db;
	}
}