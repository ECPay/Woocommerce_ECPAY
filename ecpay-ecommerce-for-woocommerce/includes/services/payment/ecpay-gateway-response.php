<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Response\VerifiedArrayResponse;

class Wooecpay_Gateway_Response
{
    public function __construct() {
        add_action('woocommerce_api_wooecpay_payment_callback', [$this, 'check_callback']);
    }

    // payment response
    public function check_callback()
    {
        $api_info = $this->get_ecpay_payment_api_info();

        try {
            $factory = new Factory([
                'hashKey'   => $api_info['hashKey'],
                'hashIv'    => $api_info['hashIv'],
            ]);
            
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);
            $info = $checkoutResponse->get($_POST);

            // 解析訂單編號
            $order_id = $this->get_order_id($info) ;

            // 取出訂單資訊
            if ($order = wc_get_order($order_id)) {

                // 取出訂單金額
                $order_total = $order->get_total();

                // 金額比對
                if($info['TradeAmt'] == $order_total ){

                    // 判斷狀態
                    switch ($info['RtnCode']) {
                        
                        // 付款完成
                        case 1:
                            
                            if(isset($info['SimulatePaid']) && $info['SimulatePaid'] == 0){

                                // 判斷付款完成旗標，如果旗標不存或為0則執行 僅允許綠界一次作動
                                $payment_complete_flag = get_post_meta( $order->get_id(), '_payment_complete_flag', true );
 
                                if(empty($payment_complete_flag)){

                                    $order->add_order_note(__('Payment completed', 'ecpay-ecommerce-for-woocommerce'));
                                    $order->payment_complete();

                                    $order->update_meta_data('_ecpay_card6no', $info['card6no']);
                                    $order->update_meta_data('_ecpay_card4no', $info['card4no']);
                                    $order->save_meta_data();

                                    // 產生物流訂單
                                    if ('yes' === get_option('wooecpay_enable_logistic_auto', 'yes')) {

                                        // 是否已經開立
                                        $wooecpay_logistic_AllPayLogisticsID = get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true );

                                        if(empty($wooecpay_logistic_AllPayLogisticsID)){
                                            $this->send_logistic_order_action($order_id);
                                        }
                                    }

                                    // 異動付款完成旗標為1
                                    $order->update_meta_data('_payment_complete_flag', 1);
                                    $order->save_meta_data();
                                }

                            } else {

                                // 模擬付款 僅執行備註寫入
                                $note = print_r($info, true);
                                $order->add_order_note('模擬付款/回傳參數：'. $note);
                            }

                        break;
                        
                        // ATM匯款帳號回傳
                        case 2:

                            if (!$order->is_paid()) {

                                $expireDate = new DateTime($info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

                                $order->update_meta_data('_ecpay_atm_BankCode', $info['BankCode']);
                                $order->update_meta_data('_ecpay_atm_vAccount', $info['vAccount']);
                                $order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                                $order->save_meta_data();

                                $order->update_status('on-hold');
                            }
                        break;

                        // 超商條代碼資訊回傳
                        case 10100073:

                            if (!$order->is_paid()) {
                                
                                $expireDate = new DateTime($info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

                                if ($info['PaymentType'] == 'CVS_CVS') {

                                    $order->update_meta_data('_ecpay_cvs_PaymentNo', $info['PaymentNo']);
                                    $order->update_meta_data('_ecpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                                } else {

                                    $order->update_meta_data('_ecpay_barcode_Barcode1', $info['Barcode1']);
                                    $order->update_meta_data('_ecpay_barcode_Barcode2', $info['Barcode2']);
                                    $order->update_meta_data('_ecpay_barcode_Barcode3', $info['Barcode3']);
                                    $order->update_meta_data('_ecpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
                                }

                                $order->save_meta_data();

                                $order->update_status('on-hold');
                            }

                        break;

                        // 付款失敗
                        case 10100058:

                            if ($order->is_paid()) {

                                $order->add_order_note(__('Payment failed within paid order', 'ecpay-ecommerce-for-woocommerce'));
                                $order->save();
                            } else {

                                $order->update_status('failed');
                            }

                        break;

                        default:

                        break;
                    }

                    echo '1|OK';
                    exit;
                }
            }

        } catch (RtnException $e) {
            echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
        }
    }

    /**
     * 產生物流訂單
     */
    public function send_logistic_order_action($order_id)
    {
        // 產生物流訂單
        if ($order = wc_get_order($order_id)){

            // 取得物流方式
            $shipping_method_id = $order->get_items('shipping') ;

            if(!empty($shipping_method_id)){

                $shipping_method_id = reset($shipping_method_id);    
                $shipping_method_id = $shipping_method_id->get_method_id() ;

                // 判斷是否為綠界物流 產生物流訂單
                if(
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
                    $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' || 
                    $shipping_method_id == 'Wooecpay_Logistic_Home_Tcat' ||
                    $shipping_method_id == 'Wooecpay_Logistic_Home_Ecan' 
                ){

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

                    if(!isset($CVSStoreID) || empty($CVSStoreID)){

                        $ajaxReturn = [
                            'code'  => '0003',
                            'msg'   => '查無超商資料',
                        ];
                    }

                    $payment_method = $order->get_payment_method() ;
                    if($payment_method == 'cod'){
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
                        if(strlen($item_name) > 50 ) {

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱超過綠界物流可允許長度強制改為:'.$item_name);
                            $order->save();
                        }

                        // 判斷特殊字元
                        if(preg_match('/[\^\'\[\]`!@#%\\\&*+\"<>|_]/', $item_name)){

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱存在綠界物流不允許的特殊字元強制改為:'.$item_name);
                            $order->save();
                        }

                    } else {
                      $item_name = $item_name_default;
                    }

                    if($LogisticsType['type'] == 'HOME'){

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
                            'SenderZipCode'         => $sender_zipcode,
                            'SenderAddress'         => $sender_address,
                            'ReceiverName'          => $order->get_shipping_first_name() . $order->get_shipping_last_name(),
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

                    } else if($LogisticsType['type'] == 'CVS'){

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
                            'ReceiverName'          => $order->get_shipping_first_name() . $order->get_shipping_last_name(),
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

                        if(
                            isset($response['RtnCode']) &&
                            ( $response['RtnCode'] == 300 || $response['RtnCode'] == 2001 )
                        ){

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

    // payment 
    // ---------------------------------------------------
    protected function get_ecpay_payment_api_info()
    {
        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            $api_info = [
                'merchant_id'   => '3002607',
                'hashKey'       => 'pwFHCqoQZGmho4w6',
                'hashIv'        => 'EkRm7iFT261dpevs',
            ] ;

        } else {
            
            $merchant_id    = get_option('wooecpay_payment_mid');
            $hash_key       = get_option('wooecpay_payment_hashkey');
            $hash_iv        = get_option('wooecpay_payment_hashiv');

            $api_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ] ;
        }

        return $api_info;
    }

    protected function get_order_id($info)
    {
        $order_prefix = get_option('wooecpay_payment_order_prefix') ;

        if (isset($info['MerchantTradeNo'])) {

            $order_id = substr($info['MerchantTradeNo'], strlen($order_prefix), strrpos($info['MerchantTradeNo'], 'SN'));

            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }

        return false;
    }

    // logistic
    // ---------------------------------------------------
    
    protected function get_ecpay_logistic_api_info($action = '', $shipping_method_id = '' )
    {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

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

                case 'map':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map' ;
                break;
                
                case 'create':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create' ;
                break;

                case 'print':

                    if($wooecpay_logistic_cvs_type == 'C2C'){

                        switch ($shipping_method_id) {

                            case 'Wooecpay_Logistic_CVS_711':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Family':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Hilife':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Okmart':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_Home_Tcat':
                            case 'Wooecpay_Logistic_Home_Ecan':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            
                            default:
                                $api_info['action'] = '' ;
                            break;
                        }

                    } else if($wooecpay_logistic_cvs_type == 'B2C'){

                        switch ($shipping_method_id) {

                            case 'Wooecpay_Logistic_CVS_711':
                            case 'Wooecpay_Logistic_CVS_Family':
                            case 'Wooecpay_Logistic_CVS_Hilife':
                            case 'Wooecpay_Logistic_Home_Tcat':
                            case 'Wooecpay_Logistic_Home_Ecan':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            default:
                                $api_info['action'] = '' ;
                            break;
                        }
                    }
                    
                break;

                default:
                break;
            }

        } else {

            switch ($action) {

                case 'map':
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/map' ;
                break;
                
                case 'create':
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/Create' ;
                break;

                case 'print':

                    if($wooecpay_logistic_cvs_type == 'C2C'){

                        switch ($shipping_method_id) {

                            case 'Wooecpay_Logistic_CVS_711':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Family':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Hilife':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_CVS_Okmart':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
                            break;

                            case 'Wooecpay_Logistic_Home_Tcat':
                            case 'Wooecpay_Logistic_Home_Ecan':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            
                            default:
                                $api_info['action'] = '' ;
                            break;
                        }

                    } else if($wooecpay_logistic_cvs_type == 'B2C'){

                        switch ($shipping_method_id) {

                            case 'Wooecpay_Logistic_CVS_711':
                            case 'Wooecpay_Logistic_CVS_Family':
                            case 'Wooecpay_Logistic_CVS_Hilife':
                            case 'Wooecpay_Logistic_Home_Tcat':
                            case 'Wooecpay_Logistic_Home_Ecan':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            default:
                                $api_info['action'] = '' ;
                            break;
                        }
                    }
                    
                break;

                default:
                break;
            }
        }

        return $api_info;
    }

    protected function get_merchant_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_id . 'SN' .(string) time() ;
        return substr($trade_no, 0, 20);
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
        }

        return $logisticsType;
    }

    protected function get_item_name($order)
    {
        $item_name = '';

        if ( count($order->get_items()) ) {
            foreach ($order->get_items() as $item) {
                $item_name .=  trim($item->get_name()) . ' ' ;
            }
        }

        return $item_name;
    }
}

return new Wooecpay_Gateway_Response();
