<?php

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Helpers\Logistic\Wooecpay_Logistic_Helper;
use Helpers\Payment\Wooecpay_Payment_Helper;

class Wooecpay_Gateway_Response {
    protected $logisticHelper;
    protected $paymentHelper;

    public function __construct() {
        add_action('woocommerce_api_wooecpay_payment_callback', [$this, 'check_callback']);

        // 載入共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
        $this->paymentHelper  = new Wooecpay_Payment_Helper;
    }

    // payment response
    public function check_callback() {
        $api_info = $this->paymentHelper->get_ecpay_payment_api_info();

        try {
            $factory = new Factory([
                'hashKey' => $api_info['hashKey'],
                'hashIv'  => $api_info['hashIv'],
            ]);

            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);
            $info             = $checkoutResponse->get($_POST);

            // 解析訂單編號
            $order_id = $this->paymentHelper->get_order_id_by_merchant_trade_no($info);

            ecpay_log('AIO 付款結果 ' . print_r($_POST, true), 'A00007', $order_id);

            // 取出訂單資訊
            if ($order = wc_get_order($order_id)) {

                // 取出訂單金額
                $order_total = $order->get_total();

                // 金額比對
                if ($info['TradeAmt'] == $order_total) {

                    // 更新訂單付款結果
                    $this->paymentHelper->update_order_ecpay_orders_payment_status($order_id, $info);

                    // 判斷狀態
                    switch ($info['RtnCode']) {

                    // 付款完成
                    case 1:
                        if (isset($info['SimulatePaid']) && $info['SimulatePaid'] == 0) {
                            // 定期定額付款回傳(非第一次)
                            if ($info['PeriodType'] == 'Y' && $info['TotalSuccessTimes'] > 1) {
                                $order = $this->create_cda_new_order($info, $order_id);
                            }

                            // 判斷回傳的綠界金流特店交易編號是否已付款
                            $is_ecpay_paid = $this->paymentHelper->is_ecpay_order_paid($order_id, $info['MerchantTradeNo']);

                            if (!$is_ecpay_paid) {
                                $order->add_order_note(__('Payment completed', 'ecpay-ecommerce-for-woocommerce'));

                                $order->update_meta_data('_ecpay_card6no', $info['card6no']);
                                $order->update_meta_data('_ecpay_card4no', $info['card4no']);

                                $order->payment_complete();

                                // 加入TWQR參數
                                if (isset($info['TWQRTradeNo'])) {
                                    $order->update_meta_data('_ecpay_twqr_trad_no', $info['TWQRTradeNo']);
                                }

                                $order->save_meta_data();

                                ecpay_log('綠界訂單付款完成', 'A00009', $order_id);

                                // 產生物流訂單
                                if ('yes' === get_option('wooecpay_enable_logistic_auto', 'yes')) {
                                    ecpay_log('自動產生物流訂單', 'A00014', $order_id);
                                    $this->logisticHelper->send_logistic_order_action($order->get_id(), false);
                                }
                            }
                        } else {
                            // 模擬付款 僅執行備註寫入
                            $note = print_r($info, true);
                            $order->add_order_note('模擬付款/回傳參數：' . $note);

                            ecpay_log('綠界訂單模擬付款', 'A00008', $order_id);
                        }

                        break;

                    // ATM匯款帳號回傳、無卡分期申請回傳
                    case 2:

                        if (!$order->is_paid()) {

                            if ($info['PaymentType'] == 'BNPL_URICH') {
                                $order->update_meta_data('_ecpay_bnpl_BNPLTradeNo', $info['BNPLTradeNo']);
                                $order->update_meta_data('_ecpay_bnpl_BNPLInstallment', $info['BNPLInstallment']);
                            } else {
                                $expireDate = new DateTime($info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

                                $order->update_meta_data('_ecpay_atm_BankCode', $info['BankCode']);
                                $order->update_meta_data('_ecpay_atm_vAccount', $info['vAccount']);
                                $order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                            }

                            $order->save_meta_data();

                            $order->update_status('on-hold');

                            ecpay_log('綠界訂單取號或申請成功', 'A00010', $order_id);
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

                            ecpay_log('綠界訂單超商條代碼取得成功', 'A00011', $order_id);
                        }

                        break;

                    // 付款失敗
                    case 10100058:

                        if ($order->is_paid()) {

                            $order->add_order_note(__('Payment failed within paid order', 'ecpay-ecommerce-for-woocommerce'));
                            $order->save();

                            ecpay_log('綠界訂單付款失敗', 'A00012', $order_id);
                        } else {

                            $order->update_status('failed');

                            ecpay_log('綠界訂單付款失敗', 'A00013', $order_id);
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
            ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'A90007', $order_id ?: $_POST['MerchantTradeNo']);
            echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
        }
    }

    public function create_cda_new_order($info, $order_id) {
        // 原始訂單
        $source_order = wc_get_order($order_id);
        if (!$source_order) {
            return;
        }
        // 取得原始訂單設定data
        $source_meta_data = $source_order->get_meta_data();

        // 建立新訂單
        $new_order = wc_create_order([
            'customer_id' => $source_order->get_customer_id(),
            'status'      => 'pending',
        ]);

        // 新訂單加入meta data
        $invoice_keys  = ['_wooecpay_invoice_type', '_wooecpay_invoice_carruer_type', '_wooecpay_invoice_carruer_num', '_wooecpay_invoice_love_code', '_wooecpay_invoice_customer_identifier', '_wooecpay_invoice_customer_company'];
        $shipping_keys = ['is_vat_exempt', '_cart_weight'];
        $payment_keys  = ['_ecpay_payment_dca', '_wooecpay_payment_order_prefix', '_wooecpay_query_trade_tag'];
        foreach ($source_meta_data as $meta_data) {
            if (in_array($meta_data->key, $invoice_keys)
                || in_array($meta_data->key, $shipping_keys)
                || in_array($meta_data->key, $payment_keys)) {
                $new_order->update_meta_data($meta_data->key, $meta_data->value);
            }
        }

        // 加入產品
        foreach ($source_order->get_items() as $item) {
            $new_order->add_product(
                $item->get_product(),
                $item->get_quantity(),
                [
                    'subtotal' => $item->get_subtotal(),
                    'total'    => $item->get_total(),
                ]
            );
        }

        // 加入帳單、運送地址
        $new_order->set_address($source_order->get_address('billing'), 'billing');
        $new_order->set_address($source_order->get_address('shipping'), 'shipping');

        // 加入付款方式
        $new_order->set_payment_method($source_order->get_payment_method());
        $new_order->set_payment_method_title($source_order->get_payment_method_title());

        // 加入總額
        $new_order->set_total($source_order->get_total());

        // 加入運送內容
        $shipping_items = new WC_Order_Item_Shipping();
        foreach ($source_order->get_items('shipping') as $item) {
            $shipping_items->set_method_title($item->get_method_title());
            $shipping_items->set_method_id($item->get_method_id());
            $shipping_items->set_total($item->get_total());
        }
        $new_order->add_item($shipping_items);

        // 設定超商資訊
        if (!empty($source_order->get_meta('_ecpay_logistic_cvs_store_id'))) {
            $new_order->update_meta_data('_ecpay_logistic_cvs_store_id', $source_order->get_meta('_ecpay_logistic_cvs_store_id'));
            $new_order->update_meta_data('_ecpay_logistic_cvs_store_name', $source_order->get_meta('_ecpay_logistic_cvs_store_name'));
            $new_order->update_meta_data('_ecpay_logistic_cvs_store_address', $source_order->get_meta('_ecpay_logistic_cvs_store_address'));
            $new_order->update_meta_data('_ecpay_logistic_cvs_store_telephone', $source_order->get_meta('_ecpay_logistic_cvs_store_telephone'));
        }

        // 寫入新訂單備註
        $new_order->add_order_note('定期定額付款第' . $info['TotalSuccessTimes'] . '次繳費成功，原始訂單編號: ' . $order_id);
        $new_order->update_status('processing');
        $new_order->save();

        // 寫入舊訂單備註
        $source_order->add_order_note('定期定額付款第' . $info['TotalSuccessTimes'] . '次繳費成功，新訂單號: ' . $new_order->get_id());
        $source_order->save();

        return $new_order;
    }
}

return new Wooecpay_Gateway_Response();
