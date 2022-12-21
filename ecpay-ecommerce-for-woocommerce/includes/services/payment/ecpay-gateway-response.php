<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Helpers\Logistic\Wooecpay_Logistic_Helper;

class Wooecpay_Gateway_Response
{
    protected $logisticHelper;

    public function __construct() {
        add_action('woocommerce_api_wooecpay_payment_callback', [$this, 'check_callback']);

        //載入物流共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
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
                                            $this->logisticHelper->send_logistic_order_action($order_id, false);
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

}

return new Wooecpay_Gateway_Response();
