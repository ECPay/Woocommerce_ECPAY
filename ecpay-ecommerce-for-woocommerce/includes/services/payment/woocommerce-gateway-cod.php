<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;
use Helpers\Logistic\Wooecpay_Logistic_Helper;

class Wooecpay_Gateway_Cod extends Wooecpay_Gateway_Base
{
    protected $logisticHelper;

    public function __construct()
    {
        add_action('woocommerce_api_wooecpay_logistic_redirect_cvs_map_cod',array($this, 'redirect_cvs_map_cod'));
        add_action('woocommerce_api_wooecpay_logistic_cancel_order_cod',array($this, 'cancel_order_cod'));
        add_action('woocommerce_ecpay_logistic_cvs_map_cod',array($this, 'cvs_map'), 10, 1);

        add_filter('woocommerce_checkout_fields',array( $this, 'cvs_info_process' ), 100);

        add_action('woocommerce_before_thankyou',array( $this, 'cvs_map' ));
        add_action( 'woocommerce_thankyou_cod',array( $this, 'thankyou_page' ));

        add_filter( 'woocommerce_cod_process_payment_order_status', array($this, 'woocommerce_cod_pending_payment_order_status'), 1, 2 );

        //載入物流共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;

        // parent::__construct();
    }

    public function woocommerce_cod_pending_payment_order_status($order_status, $order) {

        // 物流方式
        $shipping_method_id = $order->get_items('shipping') ;
        $shipping_method_id = reset($shipping_method_id);
        $shipping_method_id = $shipping_method_id->get_method_id() ;

        if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
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

        if (empty($shipping_method_id)) {
            $shippping_tag = false ;

        } else {

            $shipping_method_id = reset($shipping_method_id);
            $shipping_method_id = $shipping_method_id->get_method_id() ;
            $shippping_tag = true ;
        }

        if (
            $payment_method == 'cod' &&
            $shippping_tag  &&
            $this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)
        ) {
            // 取出商店代號
            $CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;

            // 判斷是否存在

            // 不存在
            if (empty($CVSStoreID)) {
                // 判斷是否有回傳資訊
                if (isset($_POST['CVSStoreID'])) {

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
                                $this->logisticHelper->send_logistic_order_action($order_id, false);
                            }
                        }
                    } else {
                        // 重導地圖API
                        $confirm_msg = __('The selected store does not match the chosen shipping method (Outlying Island/Main Island). Please select a different store or cancel the transaction and place a new order.', 'ecpay-ecommerce-for-woocommerce');
                        $encryption_order_id = $this->logisticHelper->encrypt_order_id($order_id);
                        $redirect_cvs_map_url = WC()->api_request_url('wooecpay_logistic_redirect_cvs_map_cod', true) . '&id=' . $encryption_order_id;
                        $canceled_url = WC()->api_request_url('wooecpay_logistic_cancel_order_cod', true) . '&id=' . $encryption_order_id;

                        // 提示訊息
                        echo '<script> ';
                        echo '    if (confirm("' . $confirm_msg . '")) {';
                        echo '        window.location.href = "' . $redirect_cvs_map_url . '"; ';
                        echo '    } else {';
                        echo '        window.location.href = "' . $canceled_url . '"; ';
                        echo '    }';
                        echo '</script>';
                    }
                } else {

                    // 不存在則走向地圖API
                    $client_back_url    = $this->get_return_url($order);
                    $api_logistic_info  = $this->logisticHelper->get_ecpay_logistic_api_info('map');
                    $MerchantTradeNo    = $this->logisticHelper->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));
                    $LogisticsType      = $this->logisticHelper->get_logistics_sub_type($shipping_method_id) ;

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

    // 取消訂單
    public function cancel_order_cod()
    {
        // 解析訂單編號
        $id = str_replace(' ', '+', $_GET['id']);
        $order_id = $this->logisticHelper->decrypt_order_id($id);

        if (!$order = wc_get_order($order_id)) {
            return;
        }

        if ($order->get_status() !== 'failed') {
            // 更新訂單狀態及備註
            $order->update_status('failed');
            // 提示文字
            $order->add_order_note(__('The selected store does not match the chosen shipping method (Outlying Island/Main Island).', 'ecpay-ecommerce-for-woocommerce'));
        }

        // 錯誤提示畫面
        $template_file = 'logistic/cvs_map_error.php';
        wc_get_template($template_file, ['back_url' => home_url()], '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');

        exit;
    }

    // 重導電子地圖
    public function redirect_cvs_map_cod()
    {
        $id = str_replace(' ', '+', $_GET['id']);
        $order_id = $this->logisticHelper->decrypt_order_id($id);

        do_action('woocommerce_ecpay_logistic_cvs_map_cod', $order_id);

        exit;
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

        if (!empty($CVSStoreID)) {

            $template_file = 'logistic/cvs_map.php';

            if (isset($template_file)) {
                $args = array(
                    'order' => $order
                );

                wc_get_template($template_file, $args, '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');
            }
        }
    }

}


$plugin_cod = new Wooecpay_Gateway_Cod();