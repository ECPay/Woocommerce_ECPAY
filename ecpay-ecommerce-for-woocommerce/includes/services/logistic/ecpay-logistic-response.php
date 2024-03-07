<?php

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Helpers\Logistic\Wooecpay_Logistic_Helper;
use Helpers\Payment\Wooecpay_Payment_Helper;

class Wooecpay_Logistic_Response {
    protected $logisticHelper;
    protected $paymentHelper;

    public function __construct() {
        add_action('woocommerce_api_wooecpay_logistic_map_callback', array($this, 'map_response')); // 前台選擇門市 Response
        add_action('woocommerce_api_wooecpay_change_logistic_map_callback', array($this, 'change_map_response')); // 後台變更門市 Response
        add_action('woocommerce_api_wooecpay_logistic_status_callback', array($this, 'logistic_status_response')); // 貨態回傳

        // 載入共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
        $this->paymentHelper  = new Wooecpay_Payment_Helper;
    }

    public function map_response() {
        if (isset($_POST['MerchantTradeNo'])) {

            $order_id = $this->logisticHelper->get_order_id_by_merchant_trade_no($_POST);

            ecpay_log('選擇超商結果回傳 ' . print_r($_POST, true), 'B00005', $order_id);

            if ($order = wc_get_order($order_id)) {

                // 物流相關程序
                // -----------------------------------------------------------------------------------------------

                // 物流方式
                $shipping_method_id = $order->get_items('shipping');
                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();

                // 判斷是否為超商取貨
                if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {

                    // 判斷是否有回傳資訊
                    if (isset($_POST['CVSStoreID'])) {

                        // 是否啟用超商離島物流
                        if (in_array('Wooecpay_Logistic_CVS_711', get_option('wooecpay_enabled_logistic_outside', []))) {

                            // 門市檢查
                            $is_valid = $this->logisticHelper->check_cvs_is_valid($shipping_method_id, $_POST['CVSOutSide']);
                            if (!$is_valid) {
                                $confirm_msg         = __('The selected store does not match the chosen shipping method (Outlying Island/Main Island). Please select a different store or cancel the transaction and place a new order.', 'ecpay-ecommerce-for-woocommerce');
                                $encryption_order_id = $this->logisticHelper->encrypt_order_id($order_id);
                                $url                 = WC()->api_request_url('wooecpay_logistic_redirect_map', true) . '&id=' . $encryption_order_id;

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

                        $CVSStoreID   = sanitize_text_field($_POST['CVSStoreID']);
                        $CVSStoreName = sanitize_text_field($_POST['CVSStoreName']);
                        $CVSAddress   = sanitize_text_field($_POST['CVSAddress']);
                        $CVSTelephone = sanitize_text_field($_POST['CVSTelephone']);

                        // 驗證
                        if (mb_strlen($CVSStoreName, "utf-8") > 10) {
                            $CVSStoreName = mb_substr($CVSStoreName, 0, 10, "utf-8");
                        }
                        if (mb_strlen($CVSAddress, "utf-8") > 60) {
                            $CVSAddress = mb_substr($CVSAddress, 0, 60, "utf-8");
                        }
                        if (strlen($CVSTelephone) > 20) {
                            $CVSTelephone = substr($CVSTelephone, 0, 20);
                        }
                        if (strlen($CVSStoreID) > 10) {
                            $CVSStoreID = substr($CVSTelephone, 0, 10);
                        }

                        $order->set_shipping_company('');
                        $order->set_shipping_address_2('');
                        $order->set_shipping_city('');
                        $order->set_shipping_state('');
                        $order->set_shipping_postcode('');
                        $order->set_shipping_address_1($CVSAddress);

                        $order->update_meta_data('_ecpay_logistic_cvs_store_id', $CVSStoreID);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_name', $CVSStoreName);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_address', $CVSAddress);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_telephone', $CVSTelephone);

                        $order->add_order_note(sprintf(__('CVS store %1$s (%2$s)', 'ecpay-ecommerce-for-woocommerce'), $CVSStoreName, $CVSStoreID));

                        $order->save();
                    }
                }

                // 金流相關程序
                // -----------------------------------------------------------------------------------------------

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
                $client_back_url = $order->get_checkout_order_received_url();

                // 紀錄訂單其他資訊
                $order->update_meta_data('_wooecpay_payment_order_prefix', get_option('wooecpay_payment_order_prefix')); // 前綴
                $order->update_meta_data('_wooecpay_payment_merchant_trade_no', $merchant_trade_no); // MerchantTradeNo
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
                        'ChoosePayment'     => $this->paymentHelper->get_ChoosePayment($order->get_payment_method()),
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

                    $generateForm = $autoSubmitFormService->generate($input, $api_payment_info['action']);

                    ecpay_log('轉導 AIO 付款頁 ' . print_r($input, true), 'A00006', $order_id);

                    echo $generateForm;
                    exit;

                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'B90005', $order_id ?: $_POST['MerchantTradeNo']);
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
                WC()->cart->empty_cart();
            }
        }
        exit;
    }

    public function change_map_response() {
        if (isset($_POST['MerchantTradeNo'])) {

            $order_id = $this->logisticHelper->get_order_id_by_merchant_trade_no($_POST);

            ecpay_log('後台變更門市結果回傳 ' . print_r($_POST, true), 'B00010', $order_id);

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
                        if (mb_strlen($CVSStoreName, "utf-8") > 10) {
                            $CVSStoreName = mb_substr($CVSStoreName, 0, 10, "utf-8");
                        }
                        if (mb_strlen($CVSAddress, "utf-8") > 60) {
                            $CVSAddress = mb_substr($CVSAddress, 0, 60, "utf-8");
                        }
                        if (strlen($CVSTelephone) > 20) {
                            $CVSTelephone = substr($CVSTelephone, 0, 20);
                        }
                        if (strlen($CVSStoreID) > 10) {
                            $CVSStoreID = substr($CVSTelephone, 0, 10);
                        }

                        $order->update_meta_data('_ecpay_logistic_cvs_store_id', $CVSStoreID);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_name', $CVSStoreName);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_address', $CVSAddress);
                        $order->update_meta_data('_ecpay_logistic_cvs_store_telephone', $CVSTelephone);

                        $order->add_order_note(sprintf(__('Change store %1$s (%2$s)', 'ecpay-ecommerce-for-woocommerce'), $CVSStoreName, $CVSStoreID));

                        $order->save();

                        ecpay_log('後台變更門市成功', 'B00011', $order_id);

                        echo '<section>';
                        echo '<h2>變更後門市資訊:</h2>';
                        echo '<table>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td>超商店舖編號:</td>';
                        echo wp_kses_post('<td>' . $CVSStoreID . '</td>');
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td>超商店舖名稱:</td>';
                        echo wp_kses_post('<td>' . $CVSStoreName . '</td>');
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td>超商店舖地址:</td>';
                        echo wp_kses_post('<td>' . $CVSAddress . '</td>');
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '</section>';
                    } else {
                        ecpay_log('後台變更門市失敗', 'B00012', $order_id);

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

    public function logistic_status_response() {
        $api_logistic_info = $this->logisticHelper->get_ecpay_logistic_api_info();

        try {
            $factory = new Factory([
                'hashKey'    => $api_logistic_info['hashKey'],
                'hashIv'     => $api_logistic_info['hashIv'],
                'hashMethod' => 'md5',
            ]);
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);

            if (isset($_POST['MerchantTradeNo'])) {

                $order_id = $this->logisticHelper->get_order_id_by_merchant_trade_no($_POST);

                ecpay_log('接收物流貨態回傳 ' . print_r($_POST, true), 'B00020', $order_id);

                if ($order = wc_get_order($order_id)) {

                    // 物流方式
                    $shipping_method_id = $order->get_items('shipping');
                    $shipping_method_id = reset($shipping_method_id);
                    $shipping_method_id = $shipping_method_id->get_method_id();

                    // 判斷是否為綠界物流
                    if ($this->logisticHelper->is_ecpay_logistics($shipping_method_id)) {

                        $RtnMsg  = sanitize_text_field($_POST['RtnMsg']);
                        $RtnCode = sanitize_text_field($_POST['RtnCode']);

                        $order->add_order_note('物流貨態回傳:' . $RtnMsg . ' (' . $RtnCode . ')');
                        $order->save();

                        echo '1|OK';
                    }
                }
            }

        } catch (RtnException $e) {
            ecpay_log('[Exception] ' . '(' . $e->getCode() . ')' . $e->getMessage() . print_r($_POST, true), 'B90020', $order_id ?: $_POST['MerchantTradeNo']);
            echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
        }

        exit;
    }
}

return new Wooecpay_Logistic_Response();
