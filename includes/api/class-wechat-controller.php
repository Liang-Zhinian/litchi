<?php

/**
* Cart API Controller
*
* @package litchi
*
* @author 
*/

class Litchi_REST_WeChat_Controller extends WP_REST_Controller {

    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'litchi/v1';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'wx';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'wx';

    /**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
    public function __construct() {
        # code...
        
$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
require_once $inc_dir. 'log.php';                  
$this->logger = Logger::Init( Logger::DefaultLogFileHandler(), 15);
    }

    /**
     * Register all routes releated with stores
     *
     * @return void
     */
    public function register_routes() {
        // GET: /wp-json/litchi/v1/wx/pay/unifiedorder
        register_rest_route( $this->namespace, '/' . $this->base . '/pay/unifiedorder', array(
			'args' => array(
				'body' => array(
					'description' => __( '商品描述', 'woocommerce' ),
                    'required' => true,
                    'type'     => 'string',
				),
				'out_trade_no' => array(
					'description' => __( '商户订单号', 'woocommerce' ),
                    'required' => true,
                    'type'     => 'string',
				),
				'total_fee' => array(
					'description' => __( '总金额', 'woocommerce' ),
                    'required' => true,
                    'type'     => 'float',
				),
				// 'attach' => array(
				// 	'description' => __( '商品描述', 'woocommerce' ),
                //     'required' => true,
                //     'type'     => 'string',
				// ),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'unifiedorder' ),
				// 'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
        ) );

        // POST: /wp-json/litchi/v1/wx/pay/notify
        register_rest_route( $this->namespace, '/' . $this->base . '/pay/notify', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'notify' ),
                // 'permission_callback' => array( $this, 'get_product_summary_permissions_check' ),,
            ),
        ) );
    } // register_routes()

    public function notify(){                
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify: start");          
        //获取返回的xml         
        $testxml  = file_get_contents("php://input");         
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): testxml => ".$testxml );          
        //将xml转化为json格式         
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));         
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): jsonxml => ".$jsonxml );          
        //转成数组         
        $result = json_decode($jsonxml, true);         
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): result => ".$result );          
        if($result){                  
            //如果成功返回了                  
            if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){                  
                //进行改变订单状态等操作。。。。                   

                
                $cash_fee = $result["cash_fee"];
                $fee_type = $result["fee_type"];
                $is_subscribe = $result["is_subscribe"];
                $mch_id = $result["mch_id"];
                $nonce_str = $result["nonce_str"];
                $openid = $result["openid"];
                $out_trade_no = $result["out_trade_no"];
                $result_code = $result["result_code"];
                $return_code = $result["return_code"];
                $sign = $result["sign"];
                $time_end = $result["time_end"];
                $total_fee = $result["total_fee"];
                $transaction_id = $result['transaction_id'];

                
                $order = new WC_Order( $out_trade_no );
                if ($order) {
                    $order -> set_payment_method( "wx" );
                    $order -> set_payment_method_title( "Wechat Pay" );
                    $order -> set_transaction_id( $transaction_id );
                    $order -> add_order_note( __('Wechat payment completed', 'woothemes') );
    
                    // Mark as on-hold (we're awaiting the cheque)
                    $order -> payment_complete();
                

                    Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): ORDER PAID" ); 
                    exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
                }
       
                Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): ORDER NOT FOUND" );                         
                exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");      
            }
        	
	        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): WX POST DATA ERROR" ); 
            exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
        }          
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): WX POST EMPTY DATA" ); 

    }

    //微信支付回调
    public function order_notice(){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //将服务器返回的XML数据转化为数组
        $data = $this->FromXml($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        //$sign = self::makeSign($data);
        $sign = $this->makeSign($data);


        // 判断签名是否正确  判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            //获取服务器返回的数据
            $order_num = $data['out_trade_no'];         //订单单号
            $openid = $data['openid'];                  //付款人openID
            $total_fee = $data['total_fee'];            //付款金额
            $transaction_id = $data['transaction_id'];  //微信支付流水号

            
            $cash_fee = $data["cash_fee"];
            $fee_type = $data["fee_type"];
            $is_subscribe = $data["is_subscribe"];
            $mch_id = $data["mch_id"];
            $nonce_str = $data["nonce_str"];
            $openid = $data["openid"];
            $out_trade_no = $data["out_trade_no"];
            $result_code = $data["result_code"];
            $return_code = $data["return_code"];
            $sign = $data["sign"];
            $time_end = $data["time_end"];
            $total_fee = $data["total_fee"];
            $transaction_id = $data['transaction_id'];

            $result = 0;

            // $res = $this->order_notice_data_deal($order_num,$openid,$total_fee,$transaction_id);
            // if (!$res) {
            //     $result = -2;
            // } else {
            //     $result = 0;
            // }
        }else{
            $result = -1;
        }
        // 返回状态给微信服务器
        if ($result == 0) { // 成功之后不会再回调
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } elseif ($result == -1){ // 失败后会继续发送几次回调
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        } elseif ($result == -2) { // 失败后会继续发送几次回调
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[更改状态失败]]></return_msg></xml>';
        }

        exit($str);
    }

    
	/**
	 * unifiedorder.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $request
	 * @return  WP_REST_Response
	 */
	public function unifiedorder( $request = array() ) {

        $params = $request->get_params();

        $order_info = array(
                'body'             => $params['body'],//商品名
                'out_trade_no'     => $params['out_trade_no'],//订单号
                // 'total_fee'     => $need_info['price_total'] * 100,//价格，分
                'total_fee'     => $params['total_fee'] * 100,
                // 'attach'         => $params['attach'],
                'notify_url'     => 'https://www.sureiot.com/wp-json/litchi/v1/wx/pay/notify', //异步通知页面url
            );
            

        $wx = new Litchi_WeChat($order_info);

        $wx_return_data     = $wx -> run();
        
		return new WP_REST_Response( $wx_return_data, 200 );
    } // END unifiedorder()

    /////////////////////////////////////////////////////////////////////////////
    /// HELPERS
    /////////////////////////////////////////////////////////////////////////////

    public function ToXml($array){
        if(!is_array($array)|| count($array) <= 0){
            return ;
        }
        $xml = '<xml version="1.0">';
        foreach ($array as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    public function FromXml($xml){
        if(!$xml){
            // 人工抛出错误
            throw new Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    public function MakeSign($data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->ToUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".C('WEIXIN_PAY_KEY');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    public function ToUrlParams($array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }


   

    // createNonceStr
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    
}