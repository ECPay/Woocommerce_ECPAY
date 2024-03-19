<?php
namespace Helpers\Logistic;

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\AesService;

class Wooecpay_Logistic_Helper {
    /**
     * 產生物流訂單
     */
    public function send_logistic_order_action($order_id = '', $is_ajax = true) {
        // 產生物流訂單
        $ajaxReturn = [
            'code' => '0000',
            'msg'  => '',
        ];

        if ($order_id == '') {
            $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        }

        if ($order = wc_get_order($order_id)) {

            // 取得物流方式
            $shipping_method_id = $order->get_items('shipping');

            if (!empty($shipping_method_id)) {

                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();

                // 判斷是否為綠界物流 產生物流訂單
                if ($this->is_ecpay_logistics($shipping_method_id)) {

                    $LogisticsType     = $this->get_logistics_sub_type($shipping_method_id);
                    $api_logistic_info = $this->get_ecpay_logistic_api_info('create');
                    $MerchantTradeNo   = $this->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));

                    $sender_name      = get_option('wooecpay_logistic_sender_name');
                    $sender_cellphone = get_option('wooecpay_logistic_sender_cellphone');
                    $sender_zipcode   = get_option('wooecpay_logistic_sender_zipcode');
                    $sender_address   = get_option('wooecpay_logistic_sender_address');

                    $serverReplyURL = WC()->api_request_url('wooecpay_logistic_status_callback', true);

                    $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id');

                    if (!isset($CVSStoreID) || empty($CVSStoreID)) {
                        $ajaxReturn = [
                            'code' => '0003',
                            'msg'  => '查無超商資料',
                        ];
                    }

                    $payment_method = $order->get_payment_method();
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
                        if (strlen($item_name) > 50) {

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱超過綠界物流可允許長度強制改為:' . $item_name);
                            $order->save();
                        }

                        // 判斷特殊字元
                        if (preg_match('/[\^\'\[\]`!@#%\\\&*+\"<>|_]/', $item_name)) {

                            $item_name = $item_name_default;

                            $order->add_order_note('商品名稱存在綠界物流不允許的特殊字元強制改為:' . $item_name);
                            $order->save();
                        }

                    } else {
                        $item_name = $item_name_default;
                    }

                    if ($LogisticsType['type'] == 'HOME') {

                        // 重量計算
                        $goods_weight = $order->get_meta('_cart_weight', true);

                        $inputLogisticOrder = [
                            'MerchantID'            => $api_logistic_info['merchant_id'],
                            'MerchantTradeNo'       => $MerchantTradeNo,
                            'MerchantTradeDate'     => date_i18n('Y/m/d H:i:s'),
                            'LogisticsType'         => $LogisticsType['type'],
                            'LogisticsSubType'      => $LogisticsType['sub_type'],
                            'GoodsAmount'           => (int) $order->get_total(),
                            'GoodsName'             => $item_name,
                            'GoodsWeight'           => $goods_weight,
                            'SenderName'            => $sender_name,
                            'SenderCellPhone'       => $sender_cellphone,
                            'SenderZipCode'         => $sender_zipcode,
                            'SenderAddress'         => $sender_address,
                            'ReceiverName'          => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                            'ReceiverCellPhone'     => $order->get_billing_phone(),
                            'ReceiverZipCode'       => $order->get_shipping_postcode(),
                            'ReceiverAddress'       => $order->get_shipping_state() . $order->get_shipping_city() . $order->get_shipping_address_1() . $order->get_shipping_address_2(),
                            'Temperature'           => '0001',
                            'Distance'              => '00',
                            'Specification'         => '0001',
                            'ScheduledPickupTime'   => '4',
                            'ScheduledDeliveryTime' => '4',
                            'ServerReplyURL'        => $serverReplyURL,
                        ];

                    } else if ($LogisticsType['type'] == 'CVS') {

                        $inputLogisticOrder = [
                            'MerchantID'        => $api_logistic_info['merchant_id'],
                            'MerchantTradeNo'   => $MerchantTradeNo,
                            'MerchantTradeDate' => date_i18n('Y/m/d H:i:s'),
                            'LogisticsType'     => $LogisticsType['type'],
                            'LogisticsSubType'  => $LogisticsType['sub_type'],
                            'GoodsAmount'       => (int) $order->get_total(),
                            'GoodsName'         => $item_name,
                            'SenderName'        => $sender_name,
                            'SenderCellPhone'   => $sender_cellphone,
                            'ReceiverName'      => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                            'ReceiverCellPhone' => $order->get_billing_phone(),
                            'ReceiverStoreID'   => $CVSStoreID,
                            'IsCollection'      => $IsCollection,
                            'ServerReplyURL'    => $serverReplyURL,
                        ];
                    }

                    try {

                        $factory = new Factory([
                            'hashKey'    => $api_logistic_info['hashKey'],
                            'hashIv'     => $api_logistic_info['hashIv'],
                            'hashMethod' => 'md5',
                        ]);

                        $postService = $factory->create('PostWithCmvEncodedStrResponseService');

                        ecpay_log('送出產生物流訂單請求 ' . print_r(ecpay_log_replace_symbol('logistic', $inputLogisticOrder), true), 'B00001', $order_id);

                        $response = $postService->post($inputLogisticOrder, $api_logistic_info['action']);

                        ecpay_log('產生物流訂單結果回傳 ' . print_r(ecpay_log_replace_symbol('logistic', $response), true), 'B00013', $order_id);

                        if (isset($response['RtnCode']) &&
                            ($response['RtnCode'] == 300 || $response['RtnCode'] == 2001)) {

                            // 更新訂單
                            $order->update_meta_data('_wooecpay_logistic_merchant_trade_no', $response['MerchantTradeNo']);
                            $order->update_meta_data('_wooecpay_logistic_RtnCode', $response['RtnCode']);
                            $order->update_meta_data('_wooecpay_logistic_RtnMsg', $response['RtnMsg']);
                            $order->update_meta_data('_wooecpay_logistic_AllPayLogisticsID', $response['1|AllPayLogisticsID']);
                            $order->update_meta_data('_wooecpay_logistic_LogisticsType', $response['LogisticsType']);
                            $order->update_meta_data('_wooecpay_logistic_CVSPaymentNo', $response['CVSPaymentNo']);
                            $order->update_meta_data('_wooecpay_logistic_CVSValidationNo', $response['CVSValidationNo']);
                            $order->update_meta_data('_wooecpay_logistic_BookingNote', $response['BookingNote']);

                            $order->add_order_note('建立物流訂單-物流廠商交易編號:' . $response['MerchantTradeNo'] . '，狀態:' . $response['RtnMsg'] . '(' . $response['RtnCode'] . ')');
                            $order->save();

                            $ajaxReturn = [
                                'code' => '9999',
                                'msg'  => '成功',
                            ];
                            ecpay_log('產生物流訂單成功', 'B00002', $order_id);
                        } else {

                            // add note
                            $order->add_order_note(print_r($response, true));
                            $order->save();

                            ecpay_log('產生物流訂單失敗 ' . print_r($response, true), 'B00003', $order_id);
                        }

                        // var_dump($response);
                    } catch (RtnException $e) {
                        ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'B90001', $order_id);
                        echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                    }
                } else {

                    $ajaxReturn = [
                        'code' => '0002',
                        'msg'  => '非綠界物流方式',
                    ];
                }
            }
        } else {
            $ajaxReturn = [
                'code' => '0001',
                'msg'  => '查無訂單',
            ];
        }

        if ($is_ajax) {
            echo json_encode($ajaxReturn, true);
            wp_die();
        }
    }

    public function get_ecpay_logistic_api_info($action = '', $shipping_method_id = '') {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type', 'C2C');

        $api_info = [
            'merchant_id' => '',
            'hashKey'     => '',
            'hashIv'      => '',
            'action'      => '',
        ];

        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            if ($wooecpay_logistic_cvs_type == 'C2C') {

                $api_info = [
                    'merchant_id' => '2000933',
                    'hashKey'     => 'XBERn1YOvpM9nfZc',
                    'hashIv'      => 'h1ONHk4P4yqbl5LK',
                ];

            } else if ($wooecpay_logistic_cvs_type == 'B2C') {

                $api_info = [
                    'merchant_id' => '2000132',
                    'hashKey'     => '5294y06JbISpM5x9',
                    'hashIv'      => 'v77hoKGq4kWxNNIS',
                ];
            }

        } else {

            $merchant_id = get_option('wooecpay_logistic_mid');
            $hash_key    = get_option('wooecpay_logistic_hashkey');
            $hash_iv     = get_option('wooecpay_logistic_hashiv');

            $api_info = [
                'merchant_id' => $merchant_id,
                'hashKey'     => $hash_key,
                'hashIv'      => $hash_iv,
            ];
        }

        // URL位置判斷
        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            switch ($action) {

            case 'map':
                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map';
                break;

            case 'create':
                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create';
                break;

            case 'print':

                if ($wooecpay_logistic_cvs_type == 'C2C') {

                    switch ($shipping_method_id) {

                    case 'Wooecpay_Logistic_CVS_711':
                    case 'Wooecpay_Logistic_CVS_711_Outside':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Family':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Hilife':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Okmart':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_Home_Tcat':
                    case 'Wooecpay_Logistic_Home_Tcat_Outside':
                    case 'Wooecpay_Logistic_Home_Ecan':
                    case 'Wooecpay_Logistic_Home_Post':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument';
                        break;

                    default:
                        $api_info['action'] = '';
                        break;
                    }

                } else if ($wooecpay_logistic_cvs_type == 'B2C') {

                    switch ($shipping_method_id) {

                    case 'Wooecpay_Logistic_CVS_711':
                    case 'Wooecpay_Logistic_CVS_711_Outside':
                    case 'Wooecpay_Logistic_CVS_Family':
                    case 'Wooecpay_Logistic_CVS_Hilife':
                    case 'Wooecpay_Logistic_Home_Tcat':
                    case 'Wooecpay_Logistic_Home_Tcat_Outside':
                    case 'Wooecpay_Logistic_Home_Ecan':
                    case 'Wooecpay_Logistic_Home_Post':
                        $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument';
                        break;
                    default:
                        $api_info['action'] = '';
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
                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/map';
                break;

            case 'create':
                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/Create';
                break;

            case 'print':

                if ($wooecpay_logistic_cvs_type == 'C2C') {

                    switch ($shipping_method_id) {

                    case 'Wooecpay_Logistic_CVS_711':
                    case 'Wooecpay_Logistic_CVS_711_Outside':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Family':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Hilife':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_CVS_Okmart':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo';
                        break;

                    case 'Wooecpay_Logistic_Home_Tcat':
                    case 'Wooecpay_Logistic_Home_Tcat_Outside':
                    case 'Wooecpay_Logistic_Home_Ecan':
                    case 'Wooecpay_Logistic_Home_Post':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument';
                        break;

                    default:
                        $api_info['action'] = '';
                        break;
                    }

                } else if ($wooecpay_logistic_cvs_type == 'B2C') {

                    switch ($shipping_method_id) {

                    case 'Wooecpay_Logistic_CVS_711':
                    case 'Wooecpay_Logistic_CVS_711_Outside':
                    case 'Wooecpay_Logistic_CVS_Family':
                    case 'Wooecpay_Logistic_CVS_Hilife':
                    case 'Wooecpay_Logistic_Home_Tcat':
                    case 'Wooecpay_Logistic_Home_Tcat_Outside':
                    case 'Wooecpay_Logistic_Home_Ecan':
                    case 'Wooecpay_Logistic_Home_Post':
                        $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument';
                        break;
                    default:
                        $api_info['action'] = '';
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

    public function get_ecpay_all_logistics() {
        return [
            'Wooecpay_Logistic_CVS_711',
            'Wooecpay_Logistic_CVS_711_Outside',
            'Wooecpay_Logistic_CVS_Family',
            'Wooecpay_Logistic_CVS_Hilife',
            'Wooecpay_Logistic_CVS_Okmart',
            'Wooecpay_Logistic_Home_Tcat',
            'Wooecpay_Logistic_Home_Tcat_Outside',
            'Wooecpay_Logistic_Home_Post',
            'Wooecpay_Logistic_Home_Ecan',
        ];
    }

    public function get_ecpay_cvs_logistics() {
        return [
            'Wooecpay_Logistic_CVS_711',
            'Wooecpay_Logistic_CVS_711_Outside',
            'Wooecpay_Logistic_CVS_Family',
            'Wooecpay_Logistic_CVS_Hilife',
            'Wooecpay_Logistic_CVS_Okmart',
        ];
    }

    public function get_ecpay_home_logistics() {
        return [
            'Wooecpay_Logistic_Home_Tcat',
            'Wooecpay_Logistic_Home_Tcat_Outside',
            'Wooecpay_Logistic_Home_Post',
            'Wooecpay_Logistic_Home_Ecan',
        ];
    }

    public function get_logistics_sub_type($shipping_method_id) {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type', 'C2C');

        $logisticsType = [
            'type'     => '',
            'sub_type' => '',
        ];

        switch ($shipping_method_id) {
        case 'Wooecpay_Logistic_CVS_711':
        case 'Wooecpay_Logistic_CVS_711_Outside':

            $logisticsType['type'] = 'CVS';

            if ($wooecpay_logistic_cvs_type == 'C2C') {
                $logisticsType['sub_type'] = 'UNIMARTC2C';
            } else if ($wooecpay_logistic_cvs_type == 'B2C') {
                $logisticsType['sub_type'] = 'UNIMART';
            }

            break;

        case 'Wooecpay_Logistic_CVS_Family':

            $logisticsType['type'] = 'CVS';

            if ($wooecpay_logistic_cvs_type == 'C2C') {
                $logisticsType['sub_type'] = 'FAMIC2C';
            } else if ($wooecpay_logistic_cvs_type == 'B2C') {
                $logisticsType['sub_type'] = 'FAMI';
            }

            break;

        case 'Wooecpay_Logistic_CVS_Hilife':

            $logisticsType['type'] = 'CVS';

            if ($wooecpay_logistic_cvs_type == 'C2C') {
                $logisticsType['sub_type'] = 'HILIFEC2C';
            } else if ($wooecpay_logistic_cvs_type == 'B2C') {
                $logisticsType['sub_type'] = 'HILIFE';
            }

            break;

        case 'Wooecpay_Logistic_CVS_Okmart':

            $logisticsType['type'] = 'CVS';

            if ($wooecpay_logistic_cvs_type == 'C2C') {
                $logisticsType['sub_type'] = 'OKMARTC2C';
            }

            break;

        case 'Wooecpay_Logistic_Home_Tcat':
        case 'Wooecpay_Logistic_Home_Tcat_Outside':
            $logisticsType['type']     = 'HOME';
            $logisticsType['sub_type'] = 'TCAT';
            break;

        case 'Wooecpay_Logistic_Home_Ecan':
            $logisticsType['type']     = 'HOME';
            $logisticsType['sub_type'] = 'ECAN';
            break;

        case 'Wooecpay_Logistic_Home_Post':
            $logisticsType['type']     = 'HOME';
            $logisticsType['sub_type'] = 'POST';
            break;
        }

        return $logisticsType;
    }

    public function get_merchant_trade_no($order_id, $order_prefix = '') {
        $trade_no = $order_prefix . substr(str_pad($order_id, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5);
        return substr($trade_no, 0, 20);
    }

    public function get_order_id_by_merchant_trade_no($info) {
        $order_prefix = get_option('wooecpay_logistic_order_prefix');

        if (isset($info['MerchantTradeNo'])) {

            $order_id = substr($info['MerchantTradeNo'], strlen($order_prefix), strrpos($info['MerchantTradeNo'], 'SN'));
            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }

        return false;
    }

    public function get_item_name($order) {
        $item_name = '';

        if (count($order->get_items())) {
            foreach ($order->get_items() as $item) {
                $item_name .= trim($item->get_name()) . ' ';
            }
        }

        return $item_name;
    }

    public function get_available_state_home_tcat_outside() {
        return [
            '澎湖縣',
            '金門縣',
            '連江縣',
        ];
    }

    public function get_permalink($url) {
        // 產生符合永久連結格式的API URL
        $symbol = (strpos($url, '?') === false) ? '?' : '&';
        return $url . $symbol;
    }

    public function generate_ecpay_map_form($shipping_method_id, $order_id) {
        $api_logistic_info = $this->get_ecpay_logistic_api_info('map');
        $client_back_url   = WC()->api_request_url('wooecpay_change_logistic_map_callback', true);
        $MerchantTradeNo   = $this->get_merchant_trade_no($order_id, get_option('wooecpay_logistic_order_prefix'));
        $LogisticsType     = $this->get_logistics_sub_type($shipping_method_id);

        try {
            $factory = new Factory([
                'hashKey'    => $api_logistic_info['hashKey'],
                'hashIv'     => $api_logistic_info['hashIv'],
                'hashMethod' => 'md5',
            ]);
            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

            $inputMap = [
                'MerchantID'       => $api_logistic_info['merchant_id'],
                'MerchantTradeNo'  => $MerchantTradeNo,
                'LogisticsType'    => $LogisticsType['type'],
                'LogisticsSubType' => $LogisticsType['sub_type'],
                'IsCollection'     => 'Y',
                'ServerReplyURL'   => $client_back_url,
            ];

            $form_map = $autoSubmitFormService->generate($inputMap, $api_logistic_info['action'], 'ecpay_map');

            ecpay_log('組合地圖 FORM ' . print_r($inputMap, true), 'B00015', $order_id);

            return $form_map;
        } catch (RtnException $e) {
            return wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
        }
    }

    public function is_available_state_home_tcat($shipping_method_id, $state) {
        // 是否為黑貓允許離島
        $is_available_outside = in_array(str_replace(' ', '', $state), $this->get_available_state_home_tcat_outside());

        switch ($shipping_method_id) {
        case 'Wooecpay_Logistic_Home_Tcat':
            return !$is_available_outside;
        case 'Wooecpay_Logistic_Home_Tcat_Outside':
            return $is_available_outside;
        }
    }

    public function is_ecpay_logistics($shipping_method_id) {
        return in_array($shipping_method_id, $this->get_ecpay_all_logistics());
    }

    public function is_ecpay_cvs_logistics($shipping_method_id) {
        return in_array($shipping_method_id, $this->get_ecpay_cvs_logistics());
    }

    public function is_ecpay_home_logistics($shipping_method_id) {
        return in_array($shipping_method_id, $this->get_ecpay_home_logistics());
    }

    public function check_cvs_is_valid($shipping_method_id, $is_cvs_outside) {
        $is_valid = true;

        switch ($shipping_method_id) {
        case 'Wooecpay_Logistic_CVS_711':
            $is_valid = ($is_cvs_outside === '0') ? true : false;
            break;
        case 'Wooecpay_Logistic_CVS_711_Outside':
            $is_valid = ($is_cvs_outside === '0') ? false : true;
            break;
        }

        return $is_valid;
    }

    public function decrypt_order_id($encryption_order_id) {
        $api_logistic_info = $this->get_ecpay_logistic_api_info();
        $factory           = new Factory([
            'hashKey' => $api_logistic_info['hashKey'],
            'hashIv'  => $api_logistic_info['hashIv'],
        ]);

        $aes_service = $factory->create(AesService::class);
        $data        = $aes_service->decrypt($encryption_order_id);
        $order_id    = $data['orderId'];

        return $order_id;
    }

    public function encrypt_order_id($order_id) {
        $api_logistic_info = $this->get_ecpay_logistic_api_info();
        $factory           = new Factory([
            'hashKey' => $api_logistic_info['hashKey'],
            'hashIv'  => $api_logistic_info['hashIv'],
        ]);

        $aes_service         = $factory->create(AesService::class);
        $encryption_order_id = $aes_service->encrypt([
            'orderId' => $order_id,
        ]);

        return $encryption_order_id;
    }

    public function validate_shipping_field($type, $value) {
        $error_message = '';

        switch ($type) {
        case 'name':
            // 定義正則式
            $number_pattern         = '/\d/';
            $special_symbol_pattern = '/[\^\'`!@#%&*+\\""<>|_\[\]~$\-={}\/?;():︿‘‵！＠＃％＆＊＋＼＂＜＞｜＿［］～＄－＝｛｝／？；（）：]/u';
            $ascii_pattern          = '/[\x00-\x1F\x7F]/';

            // 驗證字串長度
            if (!$this->calculate_string_length($value)) {
                $error_message = $error_message . __('The name insufficient or exceeded character limit', 'ecpay-ecommerce-for-woocommerce') . '<br>';
            }

            // 驗證是否含有數字
            if (preg_match($number_pattern, $value)) {
                $error_message = $error_message . __('The name cannot contain numbers', 'ecpay-ecommerce-for-woocommerce') . '<br>';
            }

            // 驗證是否包含特殊符號
            if (preg_match($special_symbol_pattern, $value)) {
                $error_message = $error_message . __('The name contains special symbols that cannot be used', 'ecpay-ecommerce-for-woocommerce') . '<br>';
            }

            // 驗證是否包含ASCII
            if (preg_match($ascii_pattern, $value)) {
                $error_message = $error_message . __('The name contains ASCII that cannot be used', 'ecpay-ecommerce-for-woocommerce') . '<br>';
            }
            break;
        case 'phone':
            if (!preg_match('/^09\d{8}$/', $value)) {
                $error_message = $error_message . __('No special symbols are allowed in the phone number, it must be ten digits long and start with 09', 'ecpay-ecommerce-for-woocommerce') . '<br>';
            }
            break;
        }

        return $error_message;
    }

    private function calculate_string_length($value) {
        $length  = 0;
        $str_len = mb_strlen($value);
        for ($i = 0; $i < $str_len; $i++) {
            if (ctype_alpha($value[$i])) {
                // 英文字母算一個字
                $length += 1;
            } else {
                // 非英文字母字算兩個字符
                $length += 2;
            }
        }

        if ($length < 4 || $length > 10) {
            return false;
        } else {
            return true;
        }

    }
}
