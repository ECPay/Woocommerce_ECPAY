<?php


use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Helpers\Logistic\Wooecpay_Logistic_Helper;

class Wooecpay_Logistic_Response
{
    protected $logisticHelper;

    public function __construct()
    {
        add_action('woocommerce_api_wooecpay_logistic_map_callback', array($this, 'map_response'));                 // 前台選擇門市 Response
        add_action('woocommerce_api_wooecpay_change_logistic_map_callback', array($this, 'change_map_response'));   // 後台變更門市 Response
        add_action('woocommerce_api_wooecpay_logistic_status_callback', array($this, 'logistic_status_response'));  // 貨態回傳

        // 載入物流共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
    }

    public function map_response()
    {
        if (isset($_POST['MerchantTradeNo'])) {

            $order_id = $this->get_order_id($_POST);

            if ($order = wc_get_order($order_id)) {

                // 物流相關程序
                // -----------------------------------------------------------------------------------------------

                // 物流方式
                $shipping_method_id = $order->get_items('shipping');
                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();

                // 判斷是否為超商取貨
                if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
                    // 是否啟用超商離島物流
                    if (in_array('Wooecpay_Logistic_CVS_711', get_option('wooecpay_enabled_logistic_outside', []))) {

                        // 門市檢查
                        $is_valid = $this->logisticHelper->check_cvs_is_valid($shipping_method_id, $_POST['CVSOutSide']);
                        if (!$is_valid) {
                            $confirm_msg = __('The selected store does not match the chosen shipping method (Outlying Island/Main Island). Please select a different store or cancel the transaction and place a new order.', 'ecpay-ecommerce-for-woocommerce');
                            $encryption_order_id = $this->logisticHelper->encrypt_order_id($order_id);
                            $url = WC()->api_request_url('wooecpay_logistic_redirect_map', true) . '&id=' . $encryption_order_id;

                            // 提示訊息
                            echo '<script>';
                            echo '    if (confirm("' . $confirm_msg . '")) {';
                            echo '        window.location.href = "' . $url . '"; ';
                            echo '    } else {';
                            echo '        window.location.href = "' . wc_get_page_permalink('checkout') . '"; ';
                            echo '    }';
                            echo '</script>';

                            exit;
                        }
                    }

                    // 取出商店代號
                    $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id');

                    // 不存在
                    if (empty($CVSStoreID)) {

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
                        $order->set_shipping_address_1($CVSAddress);

                        $order->update_meta_data( '_ecpay_logistic_cvs_store_id', $CVSStoreID );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_name', $CVSStoreName );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_address', $CVSAddress );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_telephone', $CVSTelephone );

                        $order->add_order_note(sprintf(__('CVS store %1$s (%2$s)', 'ecpay-ecommerce-for-woocommerce'), $CVSStoreName, $CVSStoreID));

                        $order->save();
                    }
                }

                // 金流相關程序
                // -----------------------------------------------------------------------------------------------

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
                $client_back_url = $order->get_checkout_order_received_url();

                // 紀錄訂單其他資訊
                $order->update_meta_data('_wooecpay_payment_order_prefix', get_option('wooecpay_payment_order_prefix')); // 前綴
                $order->update_meta_data('_wooecpay_payment_merchant_trade_no', $merchant_trade_no); // MerchantTradeNo
                $order->update_meta_data('_wooecpay_query_trade_tag', 0);

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
                        'TradeDesc'         => 'woocommerce_v2',
                        'ItemName'          => $item_name,
                        'ChoosePayment'     => $this->get_ChoosePayment($order->get_payment_method()),
                        'EncryptType'       => 1,
                        'ReturnURL'         => $return_url,
                        'ClientBackURL'     => $client_back_url,
                        'PaymentInfoURL'    => $return_url,
                        'NeedExtraPaidInfo' => 'Y',
                    ];

                    $input = $this->add_type_info($input, $order);

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

                    echo $generateForm;
                    exit;

                } catch (RtnException $e) {
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
                WC()->cart->empty_cart();
            }
        }
    }

    public function change_map_response()
    {
        if (isset($_POST['MerchantTradeNo'])) {

            $order_id = $this->get_order_id($_POST);

            if ($order = wc_get_order($order_id)) {

                // 物流相關程序
                // -----------------------------------------------------------------------------------------------

                // 物流方式
                $shipping_method_id = $order->get_items('shipping');
                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();

                // 判斷是否為超商取貨
                if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
                    $is_valid = true;

                    // 是否啟用超商離島物流
                    if (in_array('Wooecpay_Logistic_CVS_711', get_option('wooecpay_enabled_logistic_outside', []))) {
                        // 門市檢查
                        $is_valid = $this->logisticHelper->check_cvs_is_valid($shipping_method_id, $_POST['CVSOutSide']);
                    }

                    if ($is_valid) {

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

                        $order->update_meta_data( '_ecpay_logistic_cvs_store_id', $CVSStoreID );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_name', $CVSStoreName );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_address', $CVSAddress );
                        $order->update_meta_data( '_ecpay_logistic_cvs_store_telephone', $CVSTelephone );

                        $order->add_order_note(sprintf(__('Change store %1$s (%2$s)', 'ecpay-ecommerce-for-woocommerce'),$CVSStoreName,$CVSStoreID));

                        $order->save();

                        echo '<section>';
                        echo '<h2>變更後門市資訊:</h2>';
                        echo '<table>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td>超商店舖編號:</td>';
                        echo wp_kses_post('<td>'. $CVSStoreID.'</td>');
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td>超商店舖名稱:</td>';
                        echo wp_kses_post('<td>'. $CVSStoreName.'</td>');
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td>超商店舖地址:</td>';
                        echo wp_kses_post('<td>'. $CVSAddress.'</td>');
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '</section>';
                    } else {
                        // 組合地圖FORM
                        $form_map = $this->logisticHelper->generate_ecpay_map_form($shipping_method_id, $order->get_id());
                        $form_map = str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_map);
                        $form_map = str_replace('</body></html>', '', $form_map);
                        $form_map = str_replace('<script type="text/javascript">document.getElementById("ecpay-form").submit();</script>', '', $form_map);

                        echo '</form>';
                        echo $form_map;
						echo '<p>選擇門市地點與運送方式不符(離島/本島)，若要重選門市請點擊「變更門市」按鈕。</p>';
						echo '<input class=\'button\' type=\'button\' onclick=\'document.getElementById("ecpay-form").submit();\' value=\'變更門市\' />&nbsp;&nbsp;';
                    }
                }
            }
        }
        exit;
    }

    public function logistic_status_response()
    {
        $api_logistic_info  = $this->get_ecpay_logistic_api_info();

        try {
            $factory = new Factory([
                'hashKey'       => $api_logistic_info['hashKey'],
                'hashIv'        => $api_logistic_info['hashIv'],
                'hashMethod'    => 'md5',
            ]);
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);

            if (isset($_POST['MerchantTradeNo'])) {

                $order_id = $this->get_order_id($_POST);

                if ($order = wc_get_order($order_id)) {

                    // 物流方式
                    $shipping_method_id = $order->get_items('shipping');
                    $shipping_method_id = reset($shipping_method_id);
                    $shipping_method_id = $shipping_method_id->get_method_id();

                    // 判斷是否為綠界物流
                    if ($this->logisticHelper->is_ecpay_logistics($shipping_method_id)) {

                        $RtnMsg  = sanitize_text_field($_POST['RtnMsg']);
                        $RtnCode = sanitize_text_field($_POST['RtnCode']);

                        $order->add_order_note('物流貨態回傳:'.$RtnMsg.' ('.$RtnCode.')');
                        $order->save();

                        echo '1|OK';
                    }
                }
            }

        } catch (RtnException $e) {
            echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
        }

        exit;
    }

    // payment
    // ---------------------------------------------------

    protected function get_item_name($order)
    {
        $item_name = '';

        if (count($order->get_items())) {
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
        ];

        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            $api_payment_info = [
                'merchant_id'   => '3002607',
                'hashKey'       => 'pwFHCqoQZGmho4w6',
                'hashIv'        => 'EkRm7iFT261dpevs',
                'action'        => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
            ];

        } else {

            $merchant_id    = get_option('wooecpay_payment_mid');
            $hash_key       = get_option('wooecpay_payment_hashkey');
            $hash_iv        = get_option('wooecpay_payment_hashiv');

            $api_payment_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
                'action'        => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
            ];
        }

        return $api_payment_info;
    }

    protected function generate_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . substr(str_pad($order_id, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5);
        return substr($trade_no, 0, 20);
    }

    protected function add_type_info($input, $order)
    {
        $payment_type = $this->get_ChoosePayment($order->get_payment_method());
        switch ($payment_type) {

            case 'Credit':

                $number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
                if (in_array($number_of_periods, [3, 6, 12, 18, 24, 30])) {

                    $input['CreditInstallment'] = ( $number_of_periods == 30 ) ? '30N' : $number_of_periods;
                    $order->add_order_note(sprintf(__('Credit installment to %d', 'ecpay-ecommerce-for-woocommerce'),$number_of_periods));

                    $order->save();
                }

                break;

            case 'ATM':
                $input['ExpireDate'] = get_option('_wooecpay_payment_expire_date', 3);
                break;

            case 'BARCODE':
                $input['StoreExpireDate'] = get_option('_wooecpay_payment_expire_date', 3);
                break;

            case 'CVS':
                $input['StoreExpireDate'] = get_option('_wooecpay_payment_expire_date', 86400);
                break;
        }

        return $input;
    }

    protected function get_ChoosePayment($payment_method)
    {
        $choose_payment = '';

        switch ($payment_method) {
            case 'Wooecpay_Gateway_Credit':
            case 'Wooecpay_Gateway_Credit_Installment':
                $choose_payment = 'Credit';
                break;
            case 'Wooecpay_Gateway_Webatm':
                $choose_payment = 'WebATM';
                break;
            case 'Wooecpay_Gateway_Atm':
                $choose_payment = 'ATM';
                break;
            case 'Wooecpay_Gateway_Cvs':
                $choose_payment = 'CVS';
                break;
            case 'Wooecpay_Gateway_Barcode':
                $choose_payment = 'BARCODE';
                break;
            case 'Wooecpay_Gateway_Applepay':
                $choose_payment = 'ApplePay';
                break;
        }

        return $choose_payment;
    }

    // logistic
    // ---------------------------------------------------

    protected function get_order_id($info)
    {
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

    protected function get_ecpay_logistic_api_info($action = '')
    {
        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ];

        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

            if ($wooecpay_logistic_cvs_type == 'C2C') {

                $api_info = [
                    'merchant_id'   => '2000933',
                    'hashKey'       => 'XBERn1YOvpM9nfZc',
                    'hashIv'        => 'h1ONHk4P4yqbl5LK',
                ];

            } else if ($wooecpay_logistic_cvs_type == 'B2C') {

                $api_info = [
                    'merchant_id'   => '2000132',
                    'hashKey'       => '5294y06JbISpM5x9',
                    'hashIv'        => 'v77hoKGq4kWxNNIS',
                ];
            }

        } else {

            $merchant_id = get_option('wooecpay_logistic_mid');
            $hash_key    = get_option('wooecpay_logistic_hashkey');
            $hash_iv     = get_option('wooecpay_logistic_hashiv');

            $api_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ];
        }

        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            switch ($action) {
                case 'map':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map';
                    break;
                case 'create':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create';
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
                default:
                    break;
            }
        }

        return $api_info;
    }
}

return new Wooecpay_Logistic_Response();
