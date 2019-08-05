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

    
    const     APP_ID             = 'wx31b39f96354ce469'; //公众账号ID
    const     MCH_ID             = '1533699341'; //商户号
    const     APP_SECRET             = 'e016d201ac78738732643adf5f105e6a'; //APP秘钥
    const     API_SECRET = 'EFOJJXFTSCTRWZJUIE3DTCOMCJETESOL';

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

        
        // GET: /wp-json/litchi/v1/wx/pay/orders
        register_rest_route( $this->namespace, '/' . $this->base . '/pay/orders', array(
			'args' => array(
				'transaction_id' => array(
					'description' => __( 'transaction_id', 'litchi' ),
                    'required' => false,
                    'type'     => 'string',
                ),
                'out_trade_no' => array(
					'description' => __( 'out_trade_no', 'litchi' ),
                    'required' => false,
                    'type'     => 'string',
                ),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'queryOrder' ),
				// 'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
        ) );
    } // register_routes()

    public function notify(){                
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify: start");          
        //获取返回的xml         
        $testxml  = file_get_contents("php://input");         

        //将xml转化为json格式         
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));         
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): jsonxml => ".$jsonxml );

        //转成数组         
        $result = json_decode($jsonxml, true);

        if($result){                  
            //如果成功返回了                  
            if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){  
                if ($result['trade_state'] == 'SUCCESS') {                
                    //进行改变订单状态等操作。。。。                   

                    $order = $this -> setOrderAsPaid($result);

                    if ($order) {
                        
                        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): ORDER PAID");
                        exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
                    }
                    Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): ORDER NOT FOUND" );                         
                    exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>"); 
                }

            }
        	
	        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): WX POST DATA ERROR" ); 
            exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
        }          
        Logger::DEBUG(" Litchi_REST_WeChat_Controller -> notify(): WX POST EMPTY DATA" ); 
        exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
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
                  

        $wx = new Litchi_WeChat(self::APP_ID, self::MCH_ID, self::API_SECRET, self::APP_SECRET, $order_info);



        $wx_return_data     = $wx -> run();
        
		return new WP_REST_Response( $wx_return_data, 200 );
    } // END unifiedorder()

    public function queryOrder( $request = array() ) {
        
        $inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
        require_once $inc_dir. 'vendor/WxPay/WxPay.Api.php';                  
        require_once $inc_dir. 'vendor/WxPay/WxPay.Config.php';            
        require_once $inc_dir. 'vendor/WxPay/WxPay.Data.php';

        $params = $request->get_params();
        $transaction_id = $params['transaction_id'];
        $out_trade_no = $params['out_trade_no'];

        $config = new WxPayConfig(self::APP_ID, self::MCH_ID, self::API_SECRET);
        $input = new WxPayOrderQuery();

        if (!empty($transaction_id))
            $input->SetTransaction_id($transaction_id);
        else if  (!empty($out_trade_no))
            $input->SetOut_trade_no($out_trade_no);

        $result = WxPayApi::orderQuery($input, $config);

        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            if ($result['trade_state'] == 'SUCCESS') { 
                $this -> setOrderAsPaid($result);
            }
        }

        return $result;
    }

    private function setOrderAsPaid( $wx_order = 0) {
        try {
            $cash_fee = $wx_order["cash_fee"];
            $fee_type = $wx_order["fee_type"];
            $is_subscribe = $wx_order["is_subscribe"];
            $mch_id = $wx_order["mch_id"];
            $nonce_str = $wx_order["nonce_str"];
            $openid = $wx_order["openid"];
            $out_trade_no = $wx_order["out_trade_no"];
            $result_code = $wx_order["result_code"];
            $return_code = $wx_order["return_code"];
            $sign = $wx_order["sign"];
            $time_end = $wx_order["time_end"];
            $total_fee = $wx_order["total_fee"];
            $transaction_id = $wx_order['transaction_id'];
            
            $order = new WC_Order( $out_trade_no );
            if ($order) {

                Logger::DEBUG(" Litchi_REST_WeChat_Controller -> setOrderAsPaid(): ORDER STATUS " . $order->status);

                if ( 'pending' == $order->status ) {
                    $order -> set_payment_method( __("wxpay", 'litchi') );
                    $order -> set_payment_method_title( __("Wechat Payment", 'litchi') );
                    $order -> set_transaction_id( $transaction_id );
                    $order -> add_order_note( __('Wechat payment completed', 'litchi') );
                    $order->update_status( 'paid' );
                    $order->update_status( 'awaiting-shipment' );
                    Logger::DEBUG(" Litchi_REST_WeChat_Controller -> setOrderAsPaid(): ORDER PAID");
                }

                // Mark as on-hold (we're awaiting the cheque)
                //$order -> payment_complete();
            
                //exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");

                return $order;
            }
            Logger::DEBUG(" Litchi_REST_WeChat_Controller -> setOrderAsPaid(): ORDER NOT FOUND" );                         
            //exit("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>"); 

        } catch (Exception $e) {
            Logger::DEBUG(" Litchi_REST_WeChat_Controller -> setOrderAsPaid(): GET ORDER ERROR => ".$e->getMessage());
            exit();
        }
    }
    
}