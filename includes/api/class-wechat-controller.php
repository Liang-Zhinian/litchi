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

        register_rest_route( $this->namespace, '/' . $this->base . '/test', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'test' ),
                // 'permission_callback' => array( $this, 'get_product_summary_permissions_check' ),
            ),
        ) );
    } // register_routes()

    public function test(){

        return "hello";
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
                'notify_url'     => 'http://192.168.0.194:8080/wordpress/wp-json/litchi/v1/wx/test', //异步通知页面url
            );
            
        my_log_file($order_info, 'unifiedorder: $order_info');

        $wx = new Litchi_WeChat($order_info);

        $wx_return_data     = $wx -> run();
        my_log_file($wx_return_data, 'unifiedorder: $wx_return_data');
        
		return new WP_REST_Response( $wx_return_data, 200 );
    } // END unifiedorder()
    
}