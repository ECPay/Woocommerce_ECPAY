<?php

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
use Helpers\Logistic\Wooecpay_Logistic_Helper;
use Helpers\Payment\Wooecpay_Payment_Helper;

class Wooecpay_Gateway_Base extends WC_Payment_Gateway {
    protected $logisticHelper;
    protected $paymentHelper;

    public function __construct() {
        // 載入共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
        $this->paymentHelper  = new Wooecpay_Payment_Helper;

        if ($this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_wooecpay_logistic_redirect_map', array($this, 'redirect_map'));
        }

        // 感謝頁
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
    }

    public function receipt_page($order_id) {
        if ($order = wc_get_order($order_id)) {
            ecpay_log('前往付款', 'A00001', $order_id);

            // 判斷物流類型

            // 物流方式
            $shipping_method_id = $order->get_items('shipping');

            if (empty($shipping_method_id)) {
                $shippping_tag = false;

            } else {

                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();
                $shippping_tag      = true;
            }

            ecpay_log('物流方式-' . print_r($shipping_method_id, true), 'A00002', $order_id);

            if ($shippping_tag && $this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {

                // 執行地圖選擇

                // 不存在則走向地圖API
                $api_logistic_info = $this->logisticHelper->get_ecpay_logistic_api_info('map');
                $client_back_url   = WC()->api_request_url('wooecpay_logistic_map_callback', true);
                $MerchantTradeNo   = $this->logisticHelper->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));
                $LogisticsType     = $this->logisticHelper->get_logistics_sub_type($shipping_method_id);

                try {
                    $factory = new Factory([
                        'hashKey'    => $api_logistic_info['hashKey'],
                        'hashIv'     => $api_logistic_info['hashIv'],
                        'hashMethod' => 'md5',
                    ]);
                    $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

                    $input = [
                        'MerchantID'       => $api_logistic_info['merchant_id'],
                        'MerchantTradeNo'  => $MerchantTradeNo,
                        'LogisticsType'    => $LogisticsType['type'],
                        'LogisticsSubType' => $LogisticsType['sub_type'],
                        'IsCollection'     => 'Y',
                        'ServerReplyURL'   => $client_back_url,
                    ];

                    $form_map = $autoSubmitFormService->generate($input, $api_logistic_info['action']);

                    ecpay_log('轉導電子地圖 ' . print_r($input, true), 'A00003', $order_id);

                    echo $form_map;

                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'A90003', $order_id);
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }

            } else {

                $api_payment_info  = $this->paymentHelper->get_ecpay_payment_api_info('AioCheckOut');
                $merchant_trade_no = $this->paymentHelper->get_merchant_trade_no($order->get_id(), get_option('wooecpay_payment_order_prefix'));

                // 綠界訂單顯示商品名稱判斷
                if ('yes' === get_option('wooecpay_enabled_payment_disp_item_name', 'yes')) {

                    // 取出訂單品項
                    $item_name = $this->paymentHelper->get_item_name($order);
                } else {
                    $item_name = '網路商品一批';
                }

                $return_url      = WC()->api_request_url('wooecpay_payment_callback', true);
                $client_back_url = $this->get_return_url($order);

                // 紀錄訂單其他資訊
                $order->update_meta_data('_wooecpay_payment_order_prefix', get_option('wooecpay_payment_order_prefix')); // 前綴
                $order->update_meta_data('_wooecpay_payment_merchant_trade_no', $merchant_trade_no); //MerchantTradeNo
                $order->update_meta_data('_wooecpay_query_trade_tag', 0);

                $order->add_order_note(sprintf(__('Ecpay Payment Merchant Trade No %s', 'ecpay-ecommerce-for-woocommerce'), $merchant_trade_no));

                $order->save();

                // 紀錄訂單付款資訊進 DB
                $this->paymentHelper->insert_ecpay_orders_payment_status($order_id, $order->get_payment_method(), $merchant_trade_no);

                // 組合AIO參數
                try {
                    $factory = new Factory([
                        'hashKey' => $api_payment_info['hashKey'],
                        'hashIv'  => $api_payment_info['hashIv'],
                    ]);

                    $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

                    $input = [
                        'MerchantID'        => $api_payment_info['merchant_id'],
                        'MerchantTradeNo'   => $merchant_trade_no,
                        'MerchantTradeDate' => date_i18n('Y/m/d H:i:s'),
                        'PaymentType'       => 'aio',
                        'TotalAmount'       => (int) ceil($order->get_total()),
                        'TradeDesc'         => 'woocommerce_v2',
                        'ItemName'          => $item_name,
                        'ChoosePayment'     => $this->payment_type,
                        'EncryptType'       => 1,
                        'ReturnURL'         => $return_url,
                        'ClientBackURL'     => $client_back_url,
                        'PaymentInfoURL'    => $return_url,
                        'NeedExtraPaidInfo' => 'Y',
                    ];

                    $input = $this->paymentHelper->add_type_info($input, $order);

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

                    ecpay_log('轉導 AIO 付款頁 ' . print_r($input, true), 'A00004', $order_id);

                    $generateForm = $autoSubmitFormService->generate($input, $api_payment_info['action']);
                    // $generateForm = str_replace('document.getElementById("ecpay-form").submit();', '', $generateForm) ;

                    echo $generateForm;

                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'A90004', $order_id);
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }

                WC()->cart->empty_cart();
            }
        }
    }

    public function redirect_map() {
        $id       = str_replace(' ', '+', $_GET['id']);
        $order_id = $this->logisticHelper->decrypt_order_id($id);

        if (wc_get_order($order_id)) {
            $this->receipt_page($order_id);
        }

        exit;
    }

    // 感謝頁面
    public function thankyou_page($order_id) {
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
            ecpay_log('Thankyou page', 'A00020', $order_id);

            $args = array(
                'order' => $order,
            );

            wc_get_template($template_file, $args, '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');
        }
    }
}
