<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;

class Wooecpay_Gateway_Base extends WC_Payment_Gateway
{
    public function __construct()
    {
        if ($this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ));
        }

        // 感謝頁
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ));

    }

    public function receipt_page($order_id)
    {
        if ($order = wc_get_order($order_id)) {

            // 判斷物流類型

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
                $shippping_tag  && ($shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart')
            ){

                // 執行地圖選擇

                // 取出商店代號
                $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;
                
                if(empty($CVSStoreID)){

                    // 不存在則走向地圖API
                    $api_logistic_info  = $this->get_ecpay_logistic_api_info();
                    $client_back_url    = WC()->api_request_url('wooecpay_logistic_map_callback', true);
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

                        $form_map = $autoSubmitFormService->generate($input, $api_logistic_info['action']);

                        echo $form_map ;

                    } catch (RtnException $e) {
                        echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
                    }
                }


            } else {

                $api_payment_info = $this->get_ecpay_payment_api_info();
                $merchant_trade_no = $this->generate_trade_no($order->get_id(), get_option('wooecpay_payment_order_prefix'));
                
                // 綠界訂單顯示商品名稱判斷
                if ('yes' === get_option('wooecpay_enabled_payment_disp_item_name', 'yes')) {

                    // 取出訂單品項
                    $item_name = $this->get_item_name($order);
                } else {
                    $item_name = '網路商品一批';
                }

                $return_url = WC()->api_request_url('wooecpay_payment_callback', true);
                $client_back_url = $this->get_return_url($order);

                // 紀錄訂單其他資訊
                $order->update_meta_data( '_wooecpay_payment_order_prefix', get_option('wooecpay_payment_order_prefix') ); // 前綴
                $order->update_meta_data( '_wooecpay_payment_merchant_trade_no', $merchant_trade_no ); //MerchantTradeNo 
                $order->add_order_note(sprintf(__('Ecpay Payment Merchant Trade No %s', 'ecpay-ecommerce-for-woocommerce'), $merchant_trade_no));

                $order->save();

                // 組合AIO參數
                try {
                    $factory = new Factory([
                        'hashKey'   => $api_payment_info['hashKey'],
                        'hashIv'    => $api_payment_info['hashIv'],
                    ]);

                    $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

                    $input = [
                        'MerchantID'        => $api_payment_info['merchant_id'],
                        'MerchantTradeNo'   => $merchant_trade_no ,
                        'MerchantTradeDate' => date('Y/m/d H:i:s'),
                        'PaymentType'       => 'aio',
                        'TotalAmount'       => (int) ceil($order->get_total()),
                        'TradeDesc'         => UrlService::ecpayUrlEncode(get_bloginfo('name')),
                        'ItemName'          => $item_name,
                        'ChoosePayment'     => $this->payment_type,
                        'EncryptType'       => 1,
                        'ReturnURL'         => $return_url,
                        'ClientBackURL'     => $client_back_url,
                        'PaymentInfoURL'    => $return_url,
                        'NeedExtraPaidInfo' => 'Y',
                    ];

                    $input = $this->add_type_info($input, $order) ;

                    switch (get_locale()) {
                        case 'zh_HK':
                        case 'zh_TW':
                        break;
                        case 'ko_KR':
                            $input['Language'] = 'KOR';
                        break;
                        case 'ja':
                            $input['Language'] = 'JPN';
                        break;
                        case 'zh_CN':
                            $input['Language'] = 'CHI';
                        break;
                        case 'en_US':
                        case 'en_AU':
                        case 'en_CA':
                        case 'en_GB':
                        default:
                            $input['Language'] = 'ENG';
                        break;
                    }

                    $generateForm = $autoSubmitFormService->generate($input, $api_payment_info['action']);
                    // $generateForm = str_replace('document.getElementById("ecpay-form").submit();', '', $generateForm) ;

                    echo $generateForm ;

                } catch (RtnException $e) {
                    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
                }

                WC()->cart->empty_cart();
            }  
        }
    }

    // payment 
    // ---------------------------------------------------

    // 感謝頁面
    public function thankyou_page($order_id) 
    {
        // var_dump($order->get_payment_method());
        // var_dump($order->get_meta('wooecpay_payment_order_prefix'));

        if (empty($order_id)) {
            return;
        }

        if (!$order = wc_get_order($order_id)) { 
            return;
        }

        switch ($order->get_payment_method()) {
            case 'Wooecpay_Gateway_Atm':
                $template_file = 'payment/atm.php';
            break;

            case 'Wooecpay_Gateway_Cvs':
                $template_file = 'payment/cvs.php';
            break;

            case 'Wooecpay_Gateway_Barcode':
                $template_file = 'payment/barcode.php';
            break;   
        }

        if (isset($template_file)) {
            $args = array(
                'order' => $order
            );

            wc_get_template($template_file, $args, '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');
        }
    }

    protected function get_item_name($order)
    {
        $item_name = '';

        if ( count($order->get_items()) ) {
            foreach ($order->get_items() as $item) {
                $item_name .= str_replace('#', '', trim($item->get_name())) . '#';
            }
        }
        $item_name = rtrim($item_name, '#');
        return $item_name;
    }

    protected function get_ecpay_payment_api_info()
    {
        $api_payment_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            $api_payment_info = [
                'merchant_id'   => '3002607',
                'hashKey'       => 'pwFHCqoQZGmho4w6',
                'hashIv'        => 'EkRm7iFT261dpevs',
                'action'        => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
            ] ;

        } else {
            
            $merchant_id    = get_option('wooecpay_payment_mid');
            $hash_key       = get_option('wooecpay_payment_hashkey');
            $hash_iv        = get_option('wooecpay_payment_hashiv');

            $api_payment_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
                'action'        => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
            ] ;
        }

        return $api_payment_info;
    }

    protected function generate_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_id . 'SN' .(string) time() ;
        return substr($trade_no, 0, 20);
    }

    protected function add_type_info($input, $order)
    {
        switch ($this->payment_type) { 

            case 'Credit':

                $number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
                if (in_array($number_of_periods, [3, 6, 12, 18, 24, 30])) {

                    $input['CreditInstallment'] = ( $number_of_periods == 30 ) ? '30N' : $number_of_periods;
                    $order->add_order_note(sprintf(__('Credit installment to %d', 'ecpay-ecommerce-for-woocommerce'), $number_of_periods));

                    $order->save();
                }

                break;

            case 'ATM':

                $input['ExpireDate'] = $this->expire_date;
                $order->update_meta_data( '_wooecpay_payment_expire_date', $this->expire_date ); 
                $order->save();

                break;

            case 'BARCODE':
            case 'CVS':

                $input['StoreExpireDate'] = $this->expire_date;
                $order->update_meta_data( '_wooecpay_payment_expire_date', $this->expire_date );
                $order->save();

                break;
        }

        return $input;
    }

    // logistic 
    // ---------------------------------------------------

    protected function get_ecpay_logistic_api_info()
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
                    'action'        => 'https://logistics-stage.ecpay.com.tw/Express/map',
                ] ;

            } else if($wooecpay_logistic_cvs_type == 'B2C'){

                $api_info = [
                    'merchant_id'   => '2000132',
                    'hashKey'       => '5294y06JbISpM5x9',
                    'hashIv'        => 'v77hoKGq4kWxNNIS',
                    'action'        => 'https://logistics-stage.ecpay.com.tw/Express/map',
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
                'action'        => 'https://logistics.ecpay.com.tw/Express/map',
            ] ;
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
}
