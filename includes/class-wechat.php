<?php
    /*
        微信支付类
    */
    class Litchi_WeChat {
        private $APP_ID             = ''; //公众账号ID
        private $MCH_ID             = ''; //商户号
        private $APP_SECRET             = ''; //APP秘钥
        private $API_SECRET = '';
        const     URL             = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        private $_config         = array();

        public function __construct($APPID, $MCHID, $KEY, $APPSECRET, $info) {
            $this -> APP_ID = $APPID;
            $this -> MCH_ID = $MCHID;
            $this -> API_SECRET = $KEY;
            $this -> APP_SECRET = $APPSECRET;
            //生成配置参数
            $this->_makeConfig($info);
            

        }

        /*
            运行方法
        */
        public function run() {

            //将config 转换为xml格式数据
            $xml_str             = '';
            $xml_str             = '<xml>';
            foreach ($this->_config as $k => $v) {
                $xml_str         .= '<'.$k.'>' . $v . '</'.$k.'>';
            }
            $xml_str             .= '</xml>';
            //$xml_str = $this -> _arrayToXml($this->_config);
            

            $xml_str =  $this->_postXmlCurl($xml_str,self::URL);
            $array = XMLDataParse($xml_str);
            //$array['timeStamp'] = time();
            //$array['sign']     = $this->_makeSign();

            $json = array();
            //返回的结果进行判断。
            if($array['return_code'] == "SUCCESS" && $array['result_code'] == "SUCCESS"){
                //根据微信支付返回的结果进行二次签名
                //二次签名所需的随机字符串
                $array["nonce_str"] = $this -> _createNoncestr();
                //二次签名所需的时间戳
                $array['timeStamp'] = time()."";
                //二次签名剩余参数的补充
                $secondSignArray = array(
                    "appid"=>$array['appid'],
                    "noncestr"=>$array['nonce_str'],
                    "package"=>"Sign=WXPay",
                    "prepayid"=>$array['prepay_id'],
                    "partnerid"=>$array['mch_id'],
                    "timestamp"=>$array['timeStamp'],
                );
                //$json['success'] = 1;
                //$json['ordersn'] = $array["out_trade_no"]; //订单号
                //$json['order_arr'] = $secondSignArray; //返给前台APP的预支付订单信息
                $array['sign'] = $this -> _appgetSign($secondSignArray); //预支付订单签名
                $array['data'] = "预支付完成";
                //预支付完成,在下方进行自己内部的业务逻辑
                /*****************************/
                //return json_encode($json);

            }
            else{
                $array['success'] = 0;
                $array['error'] = $array['return_msg'];
                //return json_encode($json);

            }
    

            return $array;
        }
        
        private function _arrayToXml($arr) {
            $xml = "<xml>";
            foreach ($arr as $key=>$val) {
                if (is_numeric($val)) {
                    $xml.="<".$key.">".$val."</".$key.">";
                }
                else {
                    $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
            }
            $xml.="</xml>";
            return $xml;
        }

        /*
            生成配置文件
        */
        private function _makeConfig($info) {
            if(!is_array($info))
                exit('非法传参');

            //固定参数
            $fix_config         = array(
                'appid'                 => strtolower($this -> APP_ID),
                'mch_id'                 => strtolower($this -> MCH_ID),
                'nonce_str'             => strtolower($this->_createNoncestr()),
                'spbill_create_ip'         => strtolower($_SERVER["REMOTE_ADDR"]/*get_client_ip()*/),
                'trade_type'             => 'APP',
            );

            $tmp_config         = array_merge($fix_config, $info);

            $this->_config         =  $this->_sortConfig($tmp_config);

            
            $this->_config['sign']     = $this->_makeSign();
            
        }

        //随机字符串
        private function _createNoncestr( $length = 32 ) {
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
            $str ="";
            for ( $i = 0; $i < $length; $i++ ) {
                $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
            }
            return $str;
        }

        /*
            对配置文件进行排序
        */
        private function _sortConfig($arr) {
            $new_arr             = array();
            foreach ($arr as $key => $value) {
                if(empty($value))
                    continue;

                $new_arr[$key]     = $value;
            }
            ksort($new_arr);
            return $new_arr;
        }

        /*
            生成签名
        */
        private function _makeSign() {
            //第一步，将config参数生成 & 分割的字符串
            $str_config             = '';
            foreach ($this->_config as $key => $value) {
                if(empty($value))
                    continue;

                $str_config         .= $key . '=' . $value . '&';
            }

            //拼接API密钥
            $str_config             .= 'key=' . $this -> API_SECRET;

            //md5 加密，并转为大写
            $sign_info                  = strtoupper(md5($str_config));

            return $sign_info;

            //$this->_config['sign']     = $sign_info;
        }
        
        private function _appgetSign($Obj) { 
            foreach ($Obj as $k => $v) 
            { 
                $Parameters[$k] = $v; 
            }

            //签名步骤一：按字典序排序参数        
            ksort($Parameters);

            $String = $this->formatBizQueryParaMap($Parameters, false);


            //签名步骤二：在string后加入KEY
            $String = $String."&key=" . $this -> API_SECRET;

            //签名步骤三：MD5加密

            $String = md5($String);


            //签名步骤四：所有字符转为大写
            $result_ = strtoupper($String);

            $this->_config['sign']     = $result_;

            return $result_;
        }

        private function formatBizQueryParaMap($paraMap, $urlencode) {
 
            $buff = "";

            ksort($paraMap);

            foreach ($paraMap as $k => $v)
            {
                if($urlencode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";

            }

            $reqPar;
            if (strlen($buff) > 0) {
                $reqPar = substr($buff, 0, strlen($buff)-1);
            }
            return $reqPar;
        }


        /**
         * 以post方式提交xml到对应的接口url
         * 
         * @param string $xml  需要post的xml数据
         * @param string $url  url
         * @param bool $useCert 是否需要证书，默认不需要
         * @param int $second   url执行超时时间，默认30s
         * @throws WxPayException
        */
        private function _postXmlCurl($xml, $url, $useCert = false, $second = 30){        
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, $second);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);//严格校验
            //设置header
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
            if($useCert == true){
                //设置证书
                //使用证书：cert 与 key 分别属于两个.pem文件
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
            }
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            //运行curl
            $data = curl_exec($ch);

            //返回结果
            if($data){
                curl_close($ch);
                return $data;

            } else { 
                $error = curl_errno($ch);
                curl_close($ch);
                
                return false;
            }
        }
        /*
        // 发送请求
        private function _sendPrePayCurl($xml,$second=30) {
            $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
            //设置header
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            //运行curl
            $data = curl_exec($ch);
            curl_close($ch);
            $data_xml_arr = XMLDataParse($data);
            if($data_xml_arr)
            {
                return $data_xml_arr;
            }
            else 
            {
                $error = curl_errno($ch);
                echo "curl出错，错误码:$error"."<br>";
                echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
                curl_close($ch);
                return false;
            }
        }*/
    }

    
    // xml格式数据解析函数
    function XMLDataParse($data) {
        $xml = simplexml_load_string($data,NULL,LIBXML_NOCDATA);
        $array=json_decode(json_encode($xml),true);
        return $array;
    }