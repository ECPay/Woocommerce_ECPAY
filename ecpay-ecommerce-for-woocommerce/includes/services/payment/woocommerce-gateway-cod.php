<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;

class Wooecpay_Gateway_Cod extends Wooecpay_Gateway_Base
{

    public function __construct()
    {

        add_filter('woocommerce_checkout_fields',array( $this, 'cvs_info_process' ), 100);

        add_action('woocommerce_before_thankyou',array( $this, 'cvs_map' ));
        add_action( 'woocommerce_thankyou_cod',array( $this, 'thankyou_page' ));

        add_filter( 'woocommerce_cod_process_payment_order_status', array($this, 'woocommerce_cod_pending_payment_order_status'), 1, 2 );

        // parent::__construct();
    }

    public function woocommerce_cod_pending_payment_order_status($order_status, $order){
        
        // 物流方式
        $shipping_method_id = $order->get_items('shipping') ;
        $shipping_method_id = reset($shipping_method_id);    
        $shipping_method_id = $shipping_method_id->get_method_id() ;

        if(
            $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' 
        ) {
            $order_status = 'pending';    
        }

        return $order_status ;
    }


    // 修改必填欄位判斷
    public function cvs_info_process($fields)
    {

        $fields['shipping']['shipping_country']['required']     = false;
        $fields['shipping']['shipping_address_1']['required']   = false;
        $fields['shipping']['shipping_address_2']['required']   = false;
        $fields['shipping']['shipping_city']['required']        = false;
        $fields['shipping']['shipping_state']['required']       = false;
        $fields['shipping']['shipping_postcode']['required']    = false;

        return $fields;
    }

    // 電子地圖選擇
    public function cvs_map($order_id)
    {

        $order = wc_get_order($order_id) ;

        $payment_method = $order->get_payment_method(); // 付款方式

        // 物流方式
        $shipping_method_id = $order->get_items('shipping') ;

        if(empty($shipping_method_id)){
            $shippping_tag = false ;

        } else {

            $shipping_method_id = reset($shipping_method_id);   
            $shipping_method_id = $shipping_method_id->get_method_id() ;
            $shippping_tag = true ;
        }

        if(
            $payment_method == 'cod' && 
            $shippping_tag  && 
            ($shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
            $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart')
        ){

            // 取出商店代號
            $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;

            // 判斷是否存在

            // 不存在
            if(empty($CVSStoreID)){


                // 判斷是否有回傳資訊
                if(isset($_POST['CVSStoreID'])){

                    $CVSStoreID   = sanitize_text_field($_POST['CVSStoreID']);
                    $CVSStoreName = sanitize_text_field($_POST['CVSStoreName']);
                    $CVSAddress   = sanitize_text_field($_POST['CVSAddress']);
                    $CVSTelephone = sanitize_text_field($_POST['CVSTelephone']);
                    
                    // 驗證
                    if (mb_strlen( $CVSStoreName, "utf-8") > 10) {
                        $CVSStoreName = mb_substr($CVSStoreName, 0, 10, "utf-8");
                    }
                    if (mb_strlen( $CVSAddress, "utf-8") > 60) {
                        $CVSAddress = mb_substr($CVSAddress , 0, 60, "utf-8");
                    }
                    if (strlen($CVSTelephone) > 20) {
                        $CVSTelephone = substr($CVSTelephone  , 0, 20);
                    }
                    if (strlen($CVSStoreID) > 10) {
                        $CVSStoreID = substr($CVSTelephone , 0, 10);
                    }

                    $order->set_shipping_company('');
                    $order->set_shipping_address_2('');
                    $order->set_shipping_city('');
                    $order->set_shipping_state('');
                    $order->set_shipping_postcode('');
                    $order->set_shipping_address_1($_POST['CVSAddress']);

                    $order->update_meta_data( '_ecpay_logistic_cvs_store_id', $CVSStoreID ); 
                    $order->update_meta_data( '_ecpay_logistic_cvs_store_name', $CVSStoreName ); 
                    $order->update_meta_data( '_ecpay_logistic_cvs_store_address', $CVSAddress );  
                    $order->update_meta_data( '_ecpay_logistic_cvs_store_telephone', $CVSTelephone );

                    $order->add_order_note(sprintf(__('Change store %1$s (%2$s)', 'ecpay-ecommerce-for-woocommerce'),$CVSStoreName,$CVSStoreID));

                    $order->save();

                    $order->update_status('processing');

                    // 產生物流訂單
                    if ('yes' === get_option('wooecpay_enable_logistic_auto', 'yes')) {

                        // 是否已經開立
                        $wooecpay_logistic_AllPayLogisticsID = get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true );

                        if (empty($wooecpay_logistic_AllPayLogisticsID)) {
                            $this->send_logistic_order_action($order_id);
                        }
                    }
                } else {

                    // 不存在則走向地圖API
                    $api_logistic_info  = $this->get_ecpay_logistic_api_info();
                    $client_back_url    = $this->get_return_url($order);
                    $MerchantTradeNo    = $this->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));
                    $LogisticsType      = $this->get_logistics_sub_type($shipping_method_id) ;

                    try {
                        $factory = new Factory([
                            'hashKey'       => $api_logistic_info['hashKey'],
                            'hashIv'        => $api_logistic_info['hashIv'],
                            'hashMethod'    => 'md5',
                        ]);
                        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

                        $input = [
                            'MerchantID'        => $api_logistic_info['merchant_id'],
                            'MerchantTradeNo'   => $MerchantTradeNo,
                            'LogisticsType'     => $LogisticsType['type'],
                            'LogisticsSubType'  => $LogisticsType['sub_type'],
                            'IsCollection'      => 'Y',
                            'ServerReplyURL'    => $client_back_url,
                        ];

                        echo $autoSubmitFormService->generate($input, $api_logistic_info['action']);

                    } catch (RtnException $e) {
                        // echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
                    }
                } 
            }
        }
    }

    // 感謝頁面(超商資訊)
    public function thankyou_page($order_id)
    {
        if (empty($order_id)) {
            return;
        }

        if (!$order = wc_get_order($order_id)) { 
            return;
        }

        // 取出商店代號
        $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;

        if(!empty($CVSStoreID)){

            $template_file = 'logistic/cvs_map.php';

            if (isset($template_file)) {
                $args = array(
                    'order' => $order
                );

                wc_get_template($template_file, $args, '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');
            }
        }
    }

    protected function get_ecpay_logistic_api_info($action = '', $shipping_method_id = '' )
    {
        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

            if($wooecpay_logistic_cvs_type == 'C2C'){

                $api_info = [
                    'merchant_id'   => '2000933',
                    'hashKey'       => 'XBERn1YOvpM9nfZc',
                    'hashIv'        => 'h1ONHk4P4yqbl5LK',
                ] ;

            } else if($wooecpay_logistic_cvs_type == 'B2C'){

                $api_info = [
                    'merchant_id'   => '2000132',
                    'hashKey'       => '5294y06JbISpM5x9',
                    'hashIv'        => 'v77hoKGq4kWxNNIS',
                ] ;
            }

        } else {
            
            $merchant_id = get_option('wooecpay_logistic_mid');
            $hash_key    = get_option('wooecpay_logistic_hashkey');
            $hash_iv     = get_option('wooecpay_logistic_hashiv');

            $api_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ] ;
        }

        // URL位置判斷
        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            switch ($action) {
                
                case 'create':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create' ;
                break;

                default:
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map' ;
                break;
            }

        } else {

            switch ($action) {

                case 'create':
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/Create' ;
                break;

                default:
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/map' ;
                break;
            }
        }

        return $api_info;
    }

    protected function get_logistics_sub_type($shipping_method_id)
    {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

        $logisticsType = [
            'type'      => '',
            'sub_type'  => '',
        ] ;

        switch ($shipping_method_id) { 
            case 'Wooecpay_Logistic_CVS_711':

                $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                    $logisticsType['sub_type'] = 'UNIMARTC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                    $logisticsType['sub_type'] = 'UNIMART' ;
                }

            break;
            case 'Wooecpay_Logistic_CVS_Family':
                
                $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                    $logisticsType['sub_type'] = 'FAMIC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                    $logisticsType['sub_type'] = 'FAMI' ;
                }


            break;
            case 'Wooecpay_Logistic_CVS_Hilife':

                $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                    $logisticsType['sub_type'] = 'HILIFEC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                    $logisticsType['sub_type'] = 'HILIFE' ;
                }

            break;
            case 'Wooecpay_Logistic_CVS_Okmart':

                $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                    $logisticsType['sub_type'] = 'OKMARTC2C' ;
                }

            break;

            case 'Wooecpay_Logistic_Home_Tcat':
                $logisticsType['type'] = 'HOME' ;
                $logisticsType['sub_type'] = 'TCAT' ;
            break;

            case 'Wooecpay_Logistic_Home_Ecan':
                $logisticsType['type'] = 'HOME' ;
                $logisticsType['sub_type'] = 'ECAN' ;
            break;

            case 'Wooecpay_Logistic_Home_Post':
                $logisticsType['type'] = 'HOME' ;
                $logisticsType['sub_type'] = 'POST' ;
            break;
        }

        return $logisticsType;
    }

    protected function get_merchant_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_id . 'SN' .(string) time() ;
        return substr($trade_no, 0, 20);
    }

    /**
     * 產生物流訂單
     */
    public function send_logistic_order_action($order_id)
    {
        // 產生物流訂單
        if ($order = wc_get_order($order_id)) {

            // 取得物流方式
            $shipping_method_id = $order->get_items('shipping') ;

            if (!empty($shipping_method_id)) {

                $shipping_method_id = reset($shipping_method_id);    
                $shipping_method_id = $shipping_method_id->get_method_id() ;

                // 判斷是否為綠界物流 產生物流訂單
                if (
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' || 
                    $shipping_method_id == 'Wooecpay_Logistic_Home_Tcat' ||
                    $shipping_method_id == 'Wooecpay_Logistic_Home_Ecan' ||
                    $shipping_method_id == 'Wooecpay_Logistic_Home_Post' 
                ) {

                    $LogisticsType      = $this->get_logistics_sub_type($shipping_method_id) ;
                    $api_logistic_info  = $this->get_ecpay_logistic_api_info('create');
                    $MerchantTradeNo    = $this->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));

                    $sender_name        = get_option('wooecpay_logistic_sender_name') ;
                    $sender_cellphone   = get_option('wooecpay_logistic_sender_cellphone') ;
                    $sender_zipcode     = get_option('wooecpay_logistic_sender_zipcode') ;
                    $sender_address     = get_option('wooecpay_logistic_sender_address') ;

                    $serverReplyURL    = WC()->api_request_url('wooecpay_logistic_status_callback', true);

                    // 取得訂單資訊
                    // $order_data = $order->get_data();

                    $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;

                    if (!isset($CVSStoreID) || empty($CVSStoreID)) {
                        $ajaxReturn = [
                            'code'  => '0003',
                            'msg'   => '查無超商資料',
                        ];
                    }

                    $payment_method = $order->get_payment_method() ;
                    if ($payment_method == 'cod') {
                        $IsCollection = 'Y';
                    } else {
                        $IsCollection = 'N';
                    }

                    // 綠界訂單顯示商品名稱判斷
                    $item_name_default = '網路商品一批';

                    if ('yes' === get_option('wooecpay_enabled_logistic_disp_item_name', 'yes')) {

                        // 取出訂單品項
                        $item_name = $this->get_item_name($order);

                        // 判斷是否超過長度，如果超過長度改為預設文字
                        if (strlen($item_name) > 50 ) {

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱超過綠界物流可允許長度強制改為:'.$item_name);
                            $order->save();
                        }

                        // 判斷特殊字元
                        if (preg_match('/[\^\'\[\]`!@#%\\\&*+\"<>|_]/', $item_name)) {

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱存在綠界物流不允許的特殊字元強制改為:'.$item_name);
                            $order->save();
                        }

                    } else {
                      $item_name = $item_name_default;
                    }

                    if ($LogisticsType['type'] == 'HOME') {

                        // 重量計算
                        $goods_weight = get_post_meta( $order->get_id(), '_cart_weight', true ) ;

                        $inputLogisticOrder = [
                            'MerchantID'            => $api_logistic_info['merchant_id'],
                            'MerchantTradeNo'       => $MerchantTradeNo,
                            'MerchantTradeDate'     => date('Y/m/d H:i:s'),
                            'LogisticsType'         => $LogisticsType['type'],
                            'LogisticsSubType'      => $LogisticsType['sub_type'],
                            'GoodsAmount'           => $order->get_total(),
                            'GoodsName'             => $item_name,
                            'GoodsWeight'           => $goods_weight,
                            'SenderName'            => $sender_name,
                            'SenderCellPhone'       => $sender_cellphone,
                            'SenderZipCode'         => $sender_zipcode,
                            'SenderAddress'         => $sender_address,
                            'ReceiverName'          => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                            'ReceiverCellPhone'     => $order->get_billing_phone(),
                            'ReceiverZipCode'       => $order->get_shipping_postcode(),
                            'ReceiverAddress'       => $order->get_shipping_state().$order->get_shipping_city().$order->get_shipping_address_1().$order->get_shipping_address_2(),
                            'Temperature'           => '0001',
                            'Distance'              => '00',
                            'Specification'         => '0001',
                            'ScheduledPickupTime'   => '4',
                            'ScheduledDeliveryTime' => '4',
                            'ServerReplyURL'        => $serverReplyURL,
                        ];

                    } else if($LogisticsType['type'] == 'CVS') {

                        $inputLogisticOrder = [
                            'MerchantID'            => $api_logistic_info['merchant_id'],
                            'MerchantTradeNo'       => $MerchantTradeNo,
                            'MerchantTradeDate'     => date('Y/m/d H:i:s'),
                            'LogisticsType'         => $LogisticsType['type'],
                            'LogisticsSubType'      => $LogisticsType['sub_type'],
                            'GoodsAmount'           => $order->get_total(),
                            'GoodsName'             => $item_name,
                            'SenderName'            => $sender_name,
                            'SenderCellPhone'       => $sender_cellphone,
                            'ReceiverName'          => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                            'ReceiverCellPhone'     => $order->get_billing_phone(),
                            'ReceiverStoreID'       => $CVSStoreID,
                            'IsCollection'          => $IsCollection,
                            'ServerReplyURL'        => $serverReplyURL,
                        ];
                    }

                    try {

                        $factory = new Factory([
                            'hashKey'       => $api_logistic_info['hashKey'],
                            'hashIv'        => $api_logistic_info['hashIv'],
                            'hashMethod'    => 'md5',
                        ]);

                        $postService = $factory->create('PostWithCmvEncodedStrResponseService');
                        $response = $postService->post($inputLogisticOrder, $api_logistic_info['action']);

                        if (
                            isset($response['RtnCode']) &&
                            ( $response['RtnCode'] == 300 || $response['RtnCode'] == 2001 )
                        ) {

                            // 更新訂單
                            $order->update_meta_data( '_wooecpay_logistic_merchant_trade_no', $response['MerchantTradeNo'] ); 
                            $order->update_meta_data( '_wooecpay_logistic_RtnCode', $response['RtnCode'] ); 
                            $order->update_meta_data( '_wooecpay_logistic_RtnMsg', $response['RtnMsg'] ); 
                            $order->update_meta_data( '_wooecpay_logistic_AllPayLogisticsID', $response['1|AllPayLogisticsID'] );  
                            $order->update_meta_data( '_wooecpay_logistic_LogisticsType', $response['LogisticsType'] );
                            $order->update_meta_data( '_wooecpay_logistic_CVSPaymentNo', $response['CVSPaymentNo'] ); 
                            $order->update_meta_data( '_wooecpay_logistic_CVSValidationNo', $response['CVSValidationNo'] ); 
                            $order->update_meta_data( '_wooecpay_logistic_BookingNote', $response['BookingNote'] );  

                            $order->add_order_note('建立物流訂單-物流廠商交易編號:'.$response['MerchantTradeNo']. '，狀態:' . $response['RtnMsg'] . '('. $response['RtnCode'] . ')');
                            $order->save();

                            $ajaxReturn = [
                                'code'  => '9999',
                                'msg'   => '成功',
                            ];

                        } else {

                            // add note
                            $order->add_order_note(print_r($response, true));
                            $order->save();
                        }

                        // var_dump($response);
                    } catch (RtnException $e) {
                        echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
                    }
                }
            }
        }      
    }
}


$plugin_cod = new Wooecpay_Gateway_Cod();