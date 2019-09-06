<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly


/**
 * WeChatPay Payment Gateway
 *
 * Provides an WeChatPay Payment Gateway.
 *
 * @class       Litchi_WeChat_Payment_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0
 * @auther      Liang Zhinian
 * @mail        
 */


define('Litchi_WC_WeChat_DIR',rtrim(plugin_dir_path(__FILE__),'/'));
define('Litchi_WC_WeChat_URL',rtrim(plugin_dir_url(__FILE__),'/'));

class Litchi_WeChat_Payment_Gateway extends WC_Payment_Gateway {
	private $config;
	private $wechatpay_appID;
	private $wechatpay_mchId;
	private $wechatpay_key;
	private $debug;
	private $exchange_rate;
	private $current_currency;
	private $multi_currency_enabled;
	private $supported_currencies;
	private $lib_path;
	private $charset;

	public function __construct(){
		// support refunds
		array_push($this->supports,'refunds');

		$this->current_currency = get_option('woocommerce_currency');
		$this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && get_option('icl_enable_multi_currency') == 'yes';
		$this->supported_currencies = array('RMB', 'CNY');
		$this->lib_path = plugin_dir_path(__FILE__) . 'includes/vendor/WxPay';
		$this->charset = strtolower(get_bloginfo('charset'));
		if (!in_array($this->charset, array('gbk', 'utf-8'))) {
			$this->charset = 'utf-8';
		}
		$this->include_files();

		$this->id = 'wxpay';
		$this->icon = plugins_url('admin/images/wechatpay.png', __FILE__);
		$this->has_fields = false;
		$this->method_title = __('微信支付', 'litchi');   //checkout option title
		$this->method_description='支持微信原生支付、微信退款等功能。';

		// $this->order_button_text = __('Proceed to WeChatPay', 'wechatpay');

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->wechatpay_appID = $this->get_option('wechatpay_appID');
		$this->wechatpay_mchId = $this->get_option('wechatpay_mchId');
		$this->wechatpay_key = $this->get_option('wechatpay_key');
		$this->debug = $this->get_option('debug');
		$this->form_submission_method = $this->get_option('form_submission_method') == 'yes' ? true : false;
		$this->order_title_format = $this->get_option('order_title_format');
		$this->exchange_rate = $this->get_option('exchange_rate');
		$this->order_prefix = $this->get_option('order_prefix');
		$this->notify_url = WC()->api_request_url('WC_WeChatPay'); // get_option('wechatpay_notify_url'); //
		$this->ipn = null;


		$SSLCERT_PATH = LITCHI_CERT_DIR . '/wx/apiclient_cert.pem';
		$SSLKEY_PATH = LITCHI_CERT_DIR . '/wx/apiclient_key.pem';

		$this->config =new WxPayConfig ($this->wechatpay_appID,  
										$this->wechatpay_mchId, 
										$this->wechatpay_key,
										null,
										$SSLCERT_PATH,
										$SSLKEY_PATH);

		//$this->logger = Log::Init(new CLogFileHandler(plugin_dir_path(__FILE__) . "logs/" . date('Y-m-d') . '.log'), 15);;
		/*
        if ('yes' == $this->debug) {
            $this->log = new WC_Logger();
        }*/

		$inc_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		require_once $inc_dir. 'log.php';
		Logger::Init( Logger::DefaultLogFileHandler(), 15);

		//Logger::DEBUG(" Litchi -> Litchi_WeChat_Payment_Gateway constuct." );

		add_action('admin_notices', array($this, 'requirement_checks'));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')); // WC >= 2.0
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));


		add_action( 'wp_ajax_Litchi_WECHAT_PAYMENT_GET_ORDER', array($this, "get_order_status" ) );
		add_action( 'wp_ajax_nopriv_Litchi_WECHAT_PAYMENT_GET_ORDER', array($this, "get_order_status") );
		add_action( 'woocommerce_receipt_'.$this->id, array($this, 'receipt_page'));


		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action( 'wp_enqueue_scripts',array($this,'enqueue_script_onCheckout')  );


		// approve refund request automatically such as stripe connect
		// 当商家提交退款申请时
		add_action( 'dokan_after_refund_request', [ $this, 'dokan_process_refund_request' ], 10, 2);
		//       add_action( 'wp_ajax_nopriv_dokan_refund_request', array( $this, 'dokan_refund_request') );
	}


	/**
     * Check if requirements are met and display notices
     *
     * @access public
     * @return void
     */
	function requirement_checks()
	{
		if (!in_array($this->current_currency, array('RMB', 'CNY')) && !$this->exchange_rate) {
			echo '<div class="error"><p>' . sprintf(__('WeChatPay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.', 'litchi'), admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_wechatpay#woocommerce_wechatpay_exchange_rate'), $this->current_currency) . '</p></div>';
		}
	}

	public function woocommerce_wechatpay_add_gateway( $methods ) {

		$methods[] = $this;
		return $methods;
	}

	public function process_payment($order_id) {
		$order = new WC_Order ( $order_id );
		return array (
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url ( true )
		);
	}

	function enqueue_script_onCheckout()
	{
		$orderId = get_query_var ( 'order-pay' );
		$order = new WC_Order ( $orderId );
		$payment_method = method_exists($order, 'get_payment_method')?$order->get_payment_method():$order->payment_method;
		if ($this->id == $payment_method) {
			if (is_checkout_pay_page () && ! isset ( $_GET ['pay_for_order'] )) {

				wp_enqueue_script ( 'Litchi_WECHAT_JS_QRCODE', Litchi_WC_WeChat_URL. '/admin/js/qrcode.js', array (), Litchi_WC_WeChat_VERSION );
				wp_enqueue_script ( 'Litchi_WECHAT_JS_CHECKOUT', Litchi_WC_WeChat_URL. '/admin/js/checkout.js', array ('jquery','Litchi_WECHAT_JS_QRCODE' ), Litchi_WC_WeChat_VERSION );

			}
		}
	}

	function enqueue_scripts()
	{
		wp_enqueue_script('Woo_WX_Setting', plugins_url('/admin/js/WX_Setting.js',__FILE__) , array('jquery'));
	}

	function  include_files()
	{
		$lib = $this->lib_path;
		include_once($lib . '/phpqrcode/phpqrcode.php');
		include_once($lib . '/WxPay.Data.php');
		include_once($lib . '/WxPay.Api.php');
		include_once($lib . '/WxPay.Exception.php');
		include_once($lib . '/WxPay.Notify.php');
		include_once($lib . '/WxPay.Config.php');
		//include_once($lib . '/log.php');
		/*
        if(!class_exists('ILogHandler')){
            include_once ($lib . '/log.php');
        }*/
	}

	function init_form_fields()
	{

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'litchi'),
				'type' => 'checkbox',
				'label' => __('Enable WeChat Payment', 'litchi'),
				'default' => 'no'
			),
			'title' => array(
				'title' => __('Title', 'litchi'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'litchi'),
				'default' => __('WeChatPay', 'litchi'),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __('Description', 'litchi'),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', 'litchi'),
				'default' => __("Pay via WeChatPay, if you don't have an WeChatPay account, you can also pay with your debit card or credit card", 'litchi'),
				'desc_tip' => true,
			),
			'wechatpay_appID' => array(
				'title' => __('Application ID', 'litchi'),
				'type' => 'text',
				'description' => __('Please enter the Application ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'litchi'),
				'css' => 'width:400px'
			),
			'wechatpay_mchId' => array(
				'title' => __('Merchant ID', 'litchi'),
				'type' => 'text',
				'description' => __('Please enter the Merchant ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'litchi'),
				'css' => 'width:400px'
			),
			'wechatpay_key' => array(
				'title' => __('WeChatPay Key', 'litchi'),
				'type' => 'text',
				'description' => __('Please enter your WeChatPay Key; this is needed in order to take payment.', 'litchi'),
				'css' => 'width:400px',
				'desc_tip' => true,
			),
			'wechatpay_notify_url' => array(
				'title' => __('WeChatPay Notify Url', 'litchi'),
				'type' => 'text',
				'description' => __('Please enter your WeChatPay Notify Url; this is needed in order to take payment.', 'litchi'),
				'css' => 'width:400px',
				'desc_tip' => true,
			),
			'exchange_rate'=> array (
				'title' => __ ( 'Exchange Rate', 'litchi' ),
				'type' => 'text',
				'default'=>1,
				'description' =>  __ ( "Please set current currency against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19", 'wechatpay' ),
				'css' => 'width:400px;',
				'desc_tip' => true
			)
			/*'order_prefix' => array(
                'title' => __('Order No. Prefix', 'litchi'),
                'type' => 'text',
                'description' => __('eg.WC-. If you <strong>use your WeChatPay account for multiple stores</strong>, Please enter this prefix and make sure it is unique as WeChatPay will not allow orders with the same merchant order number.', 'litchi'),
                'default' => 'WC-'
        ),*//*
            'WX_EnableProxy' => array(
                'title' => __('Enable Proxy', 'litchi'),
                'type' => 'checkbox',
                'id' => 'Woo_WX_EnableProxy',
                'label' => __('Enable Proxy', 'litchi'),
                'default' => 'no',
                'description' => __('If you are behind firewall or behind company network, you can  enable proxy to make the plugin works.', 'litchi')
            ),
            'WX_ProxyHost' => array(
                'title' => __('Proxy Host', 'litchi'),
                'type' => 'text',
                'id' => 'Woo_WX_ProxyHost',
                'default' => '',
                'desc_tip' => __('Please set proxy host.', 'litchi')
            ),
            'WX_ProxyPort' => array(
                'title' => __('Proxy Port', 'wechatpay'),
                'type' => 'text',
                'default' => '',
                'id' => 'Woo_WX_ProxyPort',
                'desc_tip' => __('Please set proxy port.', 'wechatpay')
            ),
            'WX_debug' => array(
                'title' => __('Debug Log', 'wechatpay'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'wechatpay'),
                'default' => 'no',
                'description' => __('Log WeChatPay events, such as trade status, inside <code>/plugins/weChatPay-for-woocommerce/logs/</code>', 'wechatpay')
            )*/
		);
		/*        if (function_exists('wc_get_log_file_path')) {
                    $this->form_fields['WX_debug']['description'] = sprintf(__('Log WeChatPay events, such as trade status, inside <code>%s</code>', 'wechatpay'), plugin_dir_path(__FILE__) . 'logs/');
                }*/
		if (!in_array($this->current_currency, array('RMB', 'CNY', 'CNY (¥)'))) {

			$this->form_fields['exchange_rate'] = array(
				'title' => __('Exchange Rate', 'litchi'),
				'type' => 'text',
				'description' => sprintf(__("Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19", 'litchi'), $this->current_currency),
				'css' => 'width:80px;',
				'desc_tip' => true,
			);
		}

	}


	/**
	 * 
	 * @param WC_Order $order
	 * @param number $limit
	 * @param string $trimmarker
	 */
	public  function get_order_title($order,$limit=32,$trimmarker='...'){
		$id = method_exists($order, 'get_id')?$order->get_id():$order->id;
		$title="#{$id}|".get_option('blogname');

		$order_items =$order->get_items();
		if($order_items&&count($order_items)>0){
			$title="#{$id}|";
			$index=0;
			foreach ($order_items as $item_id =>$item){
				$title.= $item['name'];
				if($index++>0){
					$title.='...';
					break;
				}
			}    
		}

		return apply_filters('litchi_wechat_wc_get_order_title', mb_strimwidth ( $title, 0,32, '...','utf-8'));
	}

	public function get_order_status() {
		$order_id = isset($_POST ['orderId'])?$_POST ['orderId']:'';
		$order = new WC_Order ( $order_id );
		$isPaid = ! $order->needs_payment ();

		echo json_encode ( array (
			'status' =>$isPaid? 'paid':'unpaid',
			'url' => $this->get_return_url ( $order )
		));

		exit;
	}

	// 无论是网页付款还是App付款，付款完成后都统一调用此方法
	public function check_wechatpay_response() {
		if(defined('WP_USE_THEMES')&&!WP_USE_THEMES){
			return;
		}

		$xml = isset($GLOBALS ['HTTP_RAW_POST_DATA'])?$GLOBALS ['HTTP_RAW_POST_DATA']:'';	
		if(empty($xml)){
			$xml = file_get_contents("php://input");
		}

		if(empty($xml)){
			return ;
		}

		$xml = trim($xml);
		if(substr($xml, 0,4) !='<xml'){
			return;
		}

		//排除非微信回调
		if(strpos($xml, 'transaction_id')===false
		   ||strpos($xml, 'appid')===false
		   ||strpos($xml, 'mch_id')===false){
			return;
		}
		// 如果返回成功则验证签名
		try {
			$result = WxPayResults::Init ( $xml, $this->config );
			if (!$result||! isset($result['transaction_id'])) {
				return;
			}

			$transaction_id=$result ["transaction_id"];
			$order_id = $result['out_trade_no'];

			$input = new WxPayOrderQuery ();
			$input->SetTransaction_id ( $transaction_id );
			$query_result = WxPayApi::orderQuery ( $input, $this->config );
			if ($query_result['result_code'] == 'FAIL' || $query_result['return_code'] == 'FAIL') {
				throw new Exception(sprintf("return_msg:%s ;err_code_des:%s "), $query_result['return_msg'], $query_result['err_code_des']);
			}

			if(!(isset($query_result['trade_state'])&& $query_result['trade_state']=='SUCCESS')){
				throw new Exception("order not paid!");
			}

			$order = new WC_Order ( $order_id );
			if($order->needs_payment()){
				$order->payment_complete ($transaction_id); // 订单状态变为“正在处理”
				$order -> add_order_note( __('Wechat payment completed via wxpay gateway.', 'litchi') );
				$order->update_status( 'paid' ); // 订单状态变为“已付款”
				$order->update_status( 'awaiting-shipment' ); // 订单状态变为“等待装运”
			}

			$reply = new WxPayNotifyReply ();
			$reply->SetReturn_code ( "SUCCESS" );
			$reply->SetReturn_msg ( "OK" );

			WxpayApi::replyNotify ( $reply->ToXml () );
			exit;
		} catch ( WxPayException $e ) {
			return;
		}
	}

	

	function update_refund_request_method( $refund_request_id, $method = 'false' ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dokan_refund
            SET method = %s WHERE id = %d",
            $method, $refund_request_id
        ) );
    }

	public function dokan_process_refund_request( $refund_id, $data ) {
		Logger::DEBUG(" Litchi -> dokan_process_refund_request: " . $refund_id);

		if ( ! $data['order_id'] ) {
			return wp_send_json( __( 'No refund data to be processed', 'dokan' ) );
		}

		$order_id         = $data['order_id'];
		$vendor_id        = dokan_get_seller_id_by_order( $order_id );
		
		$order = new WC_Order ( $order_id );
		$payment_method = method_exists($order, 'get_payment_method')?$order->get_payment_method():$order->payment_method;
		if ($this->id == $payment_method) {
			$this->update_refund_request_method(refund_id, 'true');
			
		}
	}

	public function process_refund( $order_id, $amount = null, $reason = ''){		

		Logger::DEBUG(" Litchi -> process_refund: " . $reason);
		$order = new WC_Order ($order_id );
		if(!$order){
			return new WP_Error( 'invalid_order','错误的订单' );
		}

		$trade_no =$order->get_transaction_id();
		if (empty ( $trade_no )) {
			return new WP_Error( 'invalid_order', '未找到微信支付交易号或订单未支付' );
		}

		$total = $order->get_total();
		//$amount = $amount;
		$preTotal = $total;
		$preAmount = $amount;

		$exchange_rate = floatval($this->get_option('exchange_rate'));
		if($exchange_rate<=0){
			$exchange_rate=1;
		}

		$total = round ( $total * $exchange_rate, 2 );
		$amount = round ( $amount * $exchange_rate, 2 );

		$total = ( int ) ( $total  * 100);
		$amount = ( int ) ($amount * 100);

		if($amount<=0||$amount>$total){
			return new WP_Error( 'invalid_order',__('Invalid refunded amount!' ,'litchi') );
		}

		$transaction_id = $trade_no;
		$total_fee = $total;
		$refund_fee = $amount;

		$input = new WxPayRefund ();
		$input->SetTransaction_id ( $transaction_id );
		$input->SetTotal_fee ( $refund_fee /*$total_fee*/ );
		$input->SetRefund_fee ( $refund_fee );

		$input->SetOut_refund_no ( $order_id.time());
		$input->SetOp_user_id ( $this->config->getMCHID());

		try {
			$result = WxPayApi::refund ( $input,60 ,$this->config);
			if ($result ['result_code'] == 'FAIL' || $result ['return_code'] == 'FAIL') {
				Logger::DEBUG ( " WxPayApi::orderQuery:" . json_encode ( $result ) );
				$app_id = $this->config->getAPPID();
				$fee_summary = $refund_fee . '/' . $total_fee;
				throw new Exception ("return_msg:". $result ['return_msg'].';app_id:' . $app_id . ';fee:'.$fee_summary.';err_code_des:'. $result ['err_code_des'] );
			}


			Logger::DEBUG( 'Refund Result: ' . wc_print_r( $result, true ) );

			// 			switch ( strtolower( $result->ACK ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			// 				case 'success':
			// 				case 'successwithwarning':
			// 					$order->add_order_note(
			// 						/* translators: 1: Refund amount, 2: Refund ID */
			// 						sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'woocommerce' ), $result->GROSSREFUNDAMT, $result->REFUNDTRANSACTIONID ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			// 					);
			// 					return true;
			// 			}

		} catch ( Exception $e ) {
			return new WP_Error( 'invalid_order',$e->getMessage ());
		}

		return true;
	}

	function get_notify_url(){
		return $this->get_option('wechatpay_notify_url');
	}

	// 设置客户支付页面，生成微信二维码
	function receipt_page($order_id) {
		$order = new WC_Order($order_id);
		if(!$order||$order->is_paid()){
			return;
		}

		$input = new WxPayUnifiedOrder ();
		$input->SetBody ($this->get_order_title($order) );

		$input->SetAttach ( $order_id );
		$input->SetOut_trade_no ( $order_id/*md5(date ( "YmdHis" ).$order_id )*/);    
		$total = $order->get_total ();

		$exchange_rate = floatval($this->exchange_rate);
		if($exchange_rate<=0){
			$exchange_rate=1;
		}

		$total = round ($total * $exchange_rate, 2 );
		$totalFee = ( int ) ($total * 100);

		$input->SetTotal_fee ( $totalFee );

		$date = new DateTime ();
		$date->setTimezone ( new DateTimeZone ( 'Asia/Shanghai' ) );
		$startTime = $date->format ( 'YmdHis' );
		$input->SetTime_start ( $startTime );
		$input->SetNotify_url ($this->get_notify_url() );
		// 		$input->SetNotify_url ( 'https://www.what2book.com.cn/wp-json/litchi/v1/wx/pay/notify' );


		$input->SetTrade_type ( "NATIVE" );
		$input->SetProduct_id ($order_id );
		try {
			Logger::DEBUG($input);
			$result = WxPayApi::unifiedOrder ( $input, 60, $this->config );
		} catch (Exception $e) {
			echo $e->getMessage();
			return;
		}
		$error_msg=null;
		if((isset($result['result_code'])&& $result['result_code']=='FAIL')
		   ||
		   (isset($result['return_code'])&&$result['return_code']=='FAIL')){

			$error_msg =  "return_msg:".$result['return_msg'].";notify_url:".($this->get_notify_url())." ;err_code_des: ".$result['err_code_des'];

		}

		$url =isset($result['code_url'])? $result ["code_url"]:'';

		echo  '<input type="hidden" id="litchi-wechat-payment-pay-url" value="'.$url.'"/>';
	// 如果要启用扫码支付，把下面的display: none;改成display: block;
?>
		
<style type="text/css">

	.pay-weixin-design{ display: none;background: #fff;/*padding:100px;*/overflow: hidden;}
	.page-wrap {padding: 50px 0;min-height: auto !important;  }
	.pay-weixin-design #WxQRCode{width:196px;height:auto}
	.pay-weixin-design .p-w-center{ display: block;overflow: hidden;margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;}
	.pay-weixin-design .p-w-center h3{    font-family: Arial,微软雅黑;margin: 0 auto 10px;display: block;overflow: hidden;}
	.pay-weixin-design .p-w-center h3 font{ display: block;font-size: 14px;font-weight: bold;    float: left;margin: 10px 10px 0 0;}
	.pay-weixin-design .p-w-center h3 strong{position: relative;text-align: center;line-height: 40px;border: 2px solid #3879d1;display: block;font-weight: normal;width: 130px;height: 44px; float: left;}
	.pay-weixin-design .p-w-center h3 strong #img1{margin-top: 10px;display: inline-block;width: 22px;vertical-align: top;}
	.pay-weixin-design .p-w-center h3 strong span{    display: inline-block;font-size: 14px;vertical-align: top;}
	.pay-weixin-design .p-w-center h3 strong #img2{    position: absolute;right: 0;bottom: 0;}
	.pay-weixin-design .p-w-center h4{font-family: Arial,微软雅黑;      margin: 0; font-size: 14px;color: #666;}
	.pay-weixin-design .p-w-left{ display: block;overflow: hidden;float: left;}
	.pay-weixin-design .p-w-left p{ display: block;width:196px;background:#00c800;color: #fff;text-align: center;line-height:2.4em; font-size: 12px; }
	.pay-weixin-design .p-w-left img{ margin-bottom: 10px;}
	.pay-weixin-design .p-w-right{ margin-left: 50px; display: block;float: left;}
</style>

<div class="pay-weixin-design">

	<div class="p-w-center">
		<h3>
			<font>支付方式已选择微信支付</font>
			<strong>
				<img id="img1" src="<?php print Litchi_WC_WeChat_URL?>/admin/images/weixin.png">
				<span>微信支付</span>
				<img id="img2" src="<?php print Litchi_WC_WeChat_URL?>/admin/images/ep_new_sprites1.png">
			</strong>
		</h3>
		<h4>通过微信首页右上角扫一扫，或者在“发现-扫一扫”扫描二维码支付。本页面将在支付完成后自动刷新。</h4>
		<span style="color:red;"><?php print $error_msg?></span>
	</div>

	<div class="p-w-left">		  
		<div  id="litchi-wechat-payment-pay-img" style="width:200px;height:200px;padding:10px;" data-oid="<?php echo $order_id;?>"></div>
		<p>使用微信扫描二维码进行支付</p>

	</div>

	<div class="p-w-right">

		<img src="<?php print Litchi_WC_WeChat_URL?>/admin/images/ep_sys_wx_tip.jpg">
	</div>

</div>


<?php 
	}
}

?>
