<?php

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
use Helpers\Invoice\Wooecpay_Invoice_Helper;
use Helpers\Logger\Wooecpay_Logger;
use Helpers\Logistic\Wooecpay_Logistic_Helper;
use Helpers\Payment\Wooecpay_Payment_Helper;

class Wooecpay_Order {
    protected $loggerHelper;
    protected $logisticHelper;
    protected $paymentHelper;
    protected $invoiceHelper;

    public function __construct() {
        // 載入共用
        $this->loggerHelper   = new Wooecpay_Logger;
        $this->logisticHelper = new Wooecpay_Logistic_Helper;
        $this->paymentHelper  = new Wooecpay_Payment_Helper;
        $this->invoiceHelper  = new Wooecpay_Invoice_Helper;

        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'wooecpay_register_scripts'));

            if ('yes' === get_option('wooecpay_enabled_payment', 'yes')) {
                add_action('woocommerce_admin_billing_fields', array($this, 'custom_order_meta'), 10, 1);
                add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_address_meta'), 10, 1);

                add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_payment_info'), 10, 1);
                add_action('woocommerce_admin_order_data_after_order_details', array($this, 'check_order_status_cancel'));
                add_action('woocommerce_admin_order_data_after_order_details', array($this, 'check_order_is_duplicate_payment'));

                add_action('manage_shop_order_posts_custom_column', array($this, 'custom_orders_list_column_content'), 20, 2);

                add_action('wp_ajax_duplicate_payment_complete', array($this, 'ajax_duplicate_payment_complete'));
            }

            if ('yes' === get_option('wooecpay_enabled_logistic', 'yes')) {
                add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'logistic_button_display'));
                add_action('wp_ajax_send_logistic_order_action', array($this, 'ajax_send_logistic_order_action'));

                add_action('woocommerce_process_shop_order_meta', array($this, 'order_update_sync_shipping_phone'), 60);

                if (in_array('Wooecpay_Logistic_Home_Tcat', get_option('wooecpay_enabled_logistic_outside', []))) {
                    add_action('pre_post_update', array($this, 'ecpay_validate_logistic_fields'), 10, 2);
                }
            }

            if ('yes' === get_option('wooecpay_enabled_invoice', 'yes')) {
                add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_invoice_meta'), 11, 1);

                // 手動開立
                add_action('wp_ajax_send_invoice_create', array($this, 'ajax_send_invoice_create'));

                // 手動作廢
                add_action('wp_ajax_send_invoice_invalid', array($this, 'ajax_send_invoice_invalid'));

                // 自動作廢
                if ('auto_cancel' === get_option('wooecpay_enabled_cancel_invoice_auto', 'manual')) {
                    add_action('woocommerce_order_status_cancelled', array($this, 'auto_invoice_invalid'));
                    add_action('woocommerce_order_status_refunded', array($this, 'auto_invoice_invalid'));
                }
            }

            // 清理 Log
            add_action('wp_ajax_clear_ecpay_debug_log', array($this, 'ajax_clear_ecpay_debug_log'));
        }

        if ('yes' === get_option('wooecpay_enabled_invoice', 'yes')) {
            // 自動開立
            if ('auto_paid' === get_option('wooecpay_enabled_invoice_auto', 'manual')) {
                add_action('woocommerce_order_status_processing', array($this, 'auto_invoice_create'));
            }
        }
    }

    /**
     * 訂單頁面新增完整地址
     */
    public function custom_order_meta($fields) {
        $fields['full-address'] = array(
            'label'         => __('Full address', 'ecpay-ecommerce-for-woocommerce'),
            'show'          => true,
            'wrapper_class' => 'form-field-wide full-address',
        );

        return $fields;
    }

    /**
     * 訂單頁面姓名欄位格式調整
     */
    public function add_address_meta($order) {
        echo '<style>.order_data_column:nth-child(2) .address p:first-child {display: none;}</style>';

        $shipping_method_id = $order->get_items('shipping');
        if (!empty($shipping_method_id)) {
            $shipping_method_id = reset($shipping_method_id);
            $shipping_method_id = $shipping_method_id->get_method_id();
        }
        if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
            echo '<style>.logistic_csv_info {display: inline-block;}</style>';
        } else {
            echo '<style>.logistic_button_display {display: inline-block;}</style>';
        }

        echo wp_kses_post('<p><strong>帳單姓名:<br/></strong>' . $order->get_meta('_billing_last_name', true) . ' ' . $order->get_meta('_billing_first_name', true) . '</p>');
    }

    /**
     * 訂單金流資訊回傳
     */
    public function add_payment_info($order) {

        $payment_method = $order->get_meta('_payment_method', true);

        echo '<p>&nbsp;</p>';
        echo '<h3>' . __('Gateway info', 'ecpay-ecommerce-for-woocommerce') . '</h3>';

        echo wp_kses_post('<p><strong>' . __('Payment Type', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_payment_method_title', true) . '</p>');

        switch ($payment_method) {
        case 'Wooecpay_Gateway_Credit':
            echo wp_kses_post('<p><strong>信用卡前六碼:&nbsp;</strong>' . $order->get_meta('_ecpay_card6no', true) . '</p>');
            echo wp_kses_post('<p><strong>信用卡後四碼:&nbsp;</strong>' . $order->get_meta('_ecpay_card4no', true) . '</p>');
            break;
        case 'Wooecpay_Gateway_Credit_Installment':
            echo wp_kses_post('<p><strong>期數:&nbsp;</strong>' . $order->get_meta('_ecpay_payment_number_of_periods', true) . '數</p>');
            break;
        case 'Wooecpay_Gateway_Atm':
            echo wp_kses_post('<p><strong>' . __('Bank code', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_atm_BankCode', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('ATM No', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_atm_vAccount', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('Payment deadline', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_atm_ExpireDate', true) . '</p>');
            break;
        case 'Wooecpay_Gateway_Cvs':
            echo wp_kses_post('<p><strong>' . __('CVS No', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_cvs_PaymentNo', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('Payment deadline', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_cvs_ExpireDate', true) . '</p>');
            break;
        case 'Wooecpay_Gateway_Barcode':
            echo wp_kses_post('<p><strong>' . __('barcode one', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_barcode_Barcode1', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('barcode two', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_barcode_Barcode2', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('barcode three', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_barcode_Barcode3', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('Payment deadline', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_barcode_ExpireDate', true) . '</p>');
            break;
        case 'Wooecpay_Gateway_Twqr':
            echo wp_kses_post('<p><strong>' . __('TWQR trade no', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_twqr_trad_no', true) . '</p>');
            break;
        case 'Wooecpay_Gateway_Bnpl':
            echo wp_kses_post('<p><strong>' . __('BNPL Trade No', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_bnpl_BNPLTradeNo', true) . '</p>');
            echo wp_kses_post('<p><strong>' . __('BNPL Installment', 'ecpay-ecommerce-for-woocommerce') . ':&nbsp;</strong>' . $order->get_meta('_ecpay_bnpl_BNPLInstallment', true) . '</p>');
            break;
        default:
            break;
        }
    }

    /**
     * 無效訂單狀態更新
     *
     * @return void
     */
    public function check_order_status_cancel($order) {

        $query_trade_tag = $order->get_meta('_wooecpay_query_trade_tag', true);

        if ($query_trade_tag == 0) {
            // 判斷訂單狀態
            $order_status = $order->get_status();
            if (
                $order_status == 'on-hold' ||
                $order_status == 'pending'
            ) {

                // 判斷金流方式
                $payment_method = $order->get_meta('_payment_method', true);

                if (
                    $payment_method == 'Wooecpay_Gateway_Credit' ||
                    $payment_method == 'Wooecpay_Gateway_Credit_Installment' ||
                    $payment_method == 'Wooecpay_Gateway_Webatm' ||
                    $payment_method == 'Wooecpay_Gateway_Atm' ||
                    $payment_method == 'Wooecpay_Gateway_Cvs' ||
                    $payment_method == 'Wooecpay_Gateway_Barcode' ||
                    $payment_method == 'Wooecpay_Gateway_Applepay' ||
                    $payment_method == 'Wooecpay_Gateway_Dca' ||
                    $payment_method == 'Wooecpay_Gateway_Twqr' ||
                    $payment_method == 'Wooecpay_Gateway_Bnpl'
                ) {
                    // 判斷是否超過指定時間或自訂的保留時間

                    // 計算訂單建立時間是否超過指定時間
                    if (
                        $payment_method == 'Wooecpay_Gateway_Credit' ||
                        $payment_method == 'Wooecpay_Gateway_Credit_Installment'
                    ) {
                        $offset = 60; // 信用卡
                    } else {
                        $offset = 30; // 非信用卡
                    }

                    // 若使用者自訂的保留時間 > 綠界時間，則使用使用者設定的時間
                    $hold_stock_minutes = empty(get_option('woocommerce_hold_stock_minutes')) ? 0 : get_option('woocommerce_hold_stock_minutes'); // 取得保留庫存時間

                    if ($hold_stock_minutes > $offset) {
                        $offset = $hold_stock_minutes;
                    }

                    $date_created = $order->get_date_created()->getTimestamp(); // 訂單建立時間
                    $dateCompare  = strtotime('- ' . $offset . ' minute');

                    // 反查綠界訂單記錄API
                    if ($date_created <= $dateCompare) {

                        $api_payment_query_trade_info = $this->paymentHelper->get_ecpay_payment_api_info('QueryTradeInfo');
                        $merchant_trade_no            = $order->get_meta('_wooecpay_payment_merchant_trade_no', true);

                        try {

                            $factory = new Factory([
                                'hashKey' => $api_payment_query_trade_info['hashKey'],
                                'hashIv'  => $api_payment_query_trade_info['hashIv'],
                            ]);

                            $postService = $factory->create('PostWithCmvVerifiedEncodedStrResponseService');

                            $input = [
                                'MerchantID'      => $api_payment_query_trade_info['merchant_id'],
                                'MerchantTradeNo' => $merchant_trade_no,
                                'TimeStamp'       => time(),
                            ];

                            $response = $postService->post($input, $api_payment_query_trade_info['action']);

                            // 逾期交易失敗
                            if (isset($response['TradeStatus']) && $response['TradeStatus'] == 10200095) {

                                // 更新訂單狀態/備註
                                $order->add_order_note('逾期訂單自動取消');

                                $order->update_status('cancelled');
                                $order->update_meta_data('_wooecpay_query_trade_tag', 1);

                                $order->save();
                            }
                        } catch (RtnException $e) {
                            echo '(' . $e->getCode() . ')' . $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }

    /**
     * 訂單發票資訊顯示
     */
    public function add_invoice_meta($order) {

        if ($order) {

            $wooecpay_invoice_carruer_type        = $order->get_meta('_wooecpay_invoice_carruer_type', true);
            $wooecpay_invoice_type                = $order->get_meta('_wooecpay_invoice_type', true);
            $wooecpay_invoice_customer_identifier = $order->get_meta('_wooecpay_invoice_customer_identifier', true);
            $wooecpay_invoice_customer_company    = $order->get_meta('_wooecpay_invoice_customer_company', true);
            $wooecpay_invoice_love_code           = $order->get_meta('_wooecpay_invoice_love_code', true);
            $wooecpay_invoice_carruer_num         = $order->get_meta('_wooecpay_invoice_carruer_num', true);

            $wooecpay_invoice_no            = $order->get_meta('_wooecpay_invoice_no', true);
            $wooecpay_invoice_date          = $order->get_meta('_wooecpay_invoice_date', true);
            $wooecpay_invoice_random_number = $order->get_meta('_wooecpay_invoice_random_number', true);

            $wooecpay_invoice_issue_type = $order->get_meta('_wooecpay_invoice_issue_type', true);
            $wooecpay_invoice_tsr        = $order->get_meta('_wooecpay_invoice_tsr', true);
            $wooecpay_invoice_process    = $order->get_meta('_wooecpay_invoice_process', true);

            $order_status = $order->get_status();

            // 開立發票按鈕顯示判斷
            $invoice_create_button = false;

            if (empty($wooecpay_invoice_process) &&
                ($order_status == 'processing' || $order_status == 'completed')
            ) {
                $invoice_create_button = true;
            }

            // 作廢發票按鈕顯示判斷
            $invoice_invalid_button = false;
            if (!empty($wooecpay_invoice_process) &&
                ($order_status == 'cancelled' || $order_status == 'refunded')
            ) {
                $invoice_invalid_button = true;
            }

            // 顯示
            echo '<div class="logistic_button_display">';
            echo '<h3>發票資訊</h3>';
            echo wp_kses_post('<p><strong>發票號碼:</strong>' . $wooecpay_invoice_no . '</p>');
            echo wp_kses_post('<p><strong>開立時間:</strong>' . $wooecpay_invoice_date . '</p>');
            echo wp_kses_post('<p><strong>隨機碼:</strong>' . $wooecpay_invoice_random_number . '</p>');

            switch ($wooecpay_invoice_issue_type) {
            case '1':
                $wooecpay_invoice_issue_type_dsp = '一般開立發票';
                echo wp_kses_post('<p><strong>開立方式:</strong>' . $wooecpay_invoice_issue_type_dsp . '</p>');
                break;

            case '2':
                $wooecpay_invoice_issue_type_dsp = '延遲開立發票';
                echo wp_kses_post('<p><strong>開立方式:</strong>' . $wooecpay_invoice_issue_type_dsp . '</p>');
                echo wp_kses_post('<p><strong>交易單號:</strong>' . $wooecpay_invoice_tsr . '</p>');
                break;
            default:
                break;
            }

            if (isset($this->invoiceHelper->invoiceCarruerType[$wooecpay_invoice_carruer_type])) {
                echo wp_kses_post('<p><strong>開立類型:</strong>' . $this->invoiceHelper->invoiceCarruerType[$wooecpay_invoice_carruer_type] . '</p>');
            }

            if (isset($this->invoiceHelper->invoiceType[$wooecpay_invoice_type])) {
                echo wp_kses_post('<p><strong>發票開立:</strong>' . $this->invoiceHelper->invoiceType[$wooecpay_invoice_type] . '</p>');
            }

            switch ($wooecpay_invoice_type) {
            case Wooecpay_Invoice_Helper::INVOICE_TYPE_PERSONAL:
                if (!empty($wooecpay_invoice_carruer_num)) {
                    echo wp_kses_post('<p><strong>載具編號:</strong>' . $wooecpay_invoice_carruer_num . '</p>');
                }
                break;

            case Wooecpay_Invoice_Helper::INVOICE_TYPE_COMPANY:
                echo wp_kses_post('<p><strong>公司行號:</strong>' . $wooecpay_invoice_customer_company . '</p>');
                echo wp_kses_post('<p><strong>統一編號:</strong>' . $wooecpay_invoice_customer_identifier . '</p>');

                // 公司發票存入載具
                if (isset($this->invoiceHelper->invoiceCarruerType[$wooecpay_invoice_carruer_type]) && $this->invoiceHelper->invoiceCarruerType[$wooecpay_invoice_carruer_type] == '手機條碼') {
                    echo wp_kses_post('<p><strong>載具編號:</strong>' . $wooecpay_invoice_carruer_num . '</p>');
                }
                break;

            case Wooecpay_Invoice_Helper::INVOICE_TYPE_DONATE:
                echo wp_kses_post('<p><strong>愛心碼:</strong>' . $wooecpay_invoice_love_code . '</p>');
                break;
            }

            // 開立發票按鈕顯示判斷
            if ($invoice_create_button) {
                echo '<input class=\'button\' type=\'button\' value=\'開立發票\' onclick=\'wooecpayCreateInvoice(' . $order->get_id() . ');\'>';
            }

            // 作廢發票按鈕顯示判斷
            if ($invoice_invalid_button) {
                echo '<input class=\'button\' type=\'button\' value=\'作廢發票\' onclick=\'wooecpayInvalidInvoice(' . $order->get_id() . ');\'>';
            }

            echo '</div>';
        }
    }

    /**
     * 複寫聯絡電話至收件人電話
     */
    public function order_update_sync_shipping_phone($post_id) {

        $shipping_phone = $order->get_meta('_shipping_phone', true);

        $order->update_meta_data('wooecpay_shipping_phone', $shipping_phone);
        $order->save();
    }

    public function ecpay_validate_logistic_fields($post_id, $data) {

        if ($order = wc_get_order($post_id)) {

            // 取得物流方式
            $shipping_method_id = $order->get_items('shipping');
            $shipping_method_id = reset($shipping_method_id);
            $shipping_method_id = $shipping_method_id->get_method_id();

            // 驗證運送方式的縣/市欄位
            $shipping_state = $_POST['_shipping_state'];

            // 宅配黑貓
            if (in_array($shipping_method_id, ['Wooecpay_Logistic_Home_Tcat', 'Wooecpay_Logistic_Home_Tcat_Outside'])) {
                // 比對運送方式與地址
                $result = $this->logisticHelper->is_available_state_home_tcat($shipping_method_id, $shipping_state);
                if (!$result) {
                    // 比對失敗，中斷執行程序不更新訂單
                    wp_die(__('The order update failed as the selected store does not match the chosen shipping method (Outlying Island/Main Island).', 'ecpay-ecommerce-for-woocommerce'), __('Order Save Error', 'ecpay-ecommerce-for-woocommerce'), array('response' => 400));
                }
            }
        }
    }

    /**
     * 註冊JS
     */
    public function wooecpay_register_scripts() {
        wp_register_script(
            'wooecpay_main',
            WOOECPAY_PLUGIN_URL . 'public/js/wooecpay-main.js',
            array(),
            '1.0.2',
            true
        );

        // 載入js
        wp_enqueue_script('wooecpay_main');
    }

    /**
     * 產生物流相關按鈕顯示
     */
    public function logistic_button_display($order) {
        if ($order) {

            // 取得物流方式
            $shipping_method_id = $order->get_items('shipping');

            if ($shipping_method_id) {
                $shipping_method_id = reset($shipping_method_id);
                $shipping_method_id = $shipping_method_id->get_method_id();

                $order_status = $order->get_status();

                // 地圖按鈕顯示判斷
                if (true) {
                    // 按鈕顯示旗標
                    $map_button = false;

                    // 判斷物流方式是否允許變更門市
                    if (
                        ($order_status == 'on-hold' || $order_status == 'processing') &&
                        $this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)
                    ) {
                        // 狀態判斷是否已經建立綠界物流單 AllPayLogisticsID
                        $ecpay_logistic_AllPayLogisticsID = $order->get_meta('_wooecpay_logistic_AllPayLogisticsID', true);

                        if (empty($ecpay_logistic_AllPayLogisticsID)) {
                            $map_button = true;
                        }
                    }
                }

                // 物流訂單按鈕判斷
                if (true) {
                    $logistic_order_button = true;

                    // 判斷是否為綠界物流
                    if ($this->logisticHelper->is_ecpay_logistics($shipping_method_id)) {
                        if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
                            // 狀態判斷 _ecpay_logistic_cvs_store_id門市代號不存在
                            $ecpay_logistic_cvs_store_id = $order->get_meta('_ecpay_logistic_cvs_store_id', true);

                            if (empty($ecpay_logistic_cvs_store_id)) {
                                $logistic_order_button = false;
                            }
                        }

                    } else {
                        $logistic_order_button = false;
                    }

                    // 已經存在AllPayLogisticsID 關閉按鈕
                    $AllPayLogisticsID = $order->get_meta('_wooecpay_logistic_AllPayLogisticsID', true);

                    if (!empty($AllPayLogisticsID)) {
                        $logistic_order_button = false;
                    }

                    if ($order_status != 'on-hold' && $order_status != 'processing') {
                        $logistic_order_button = false;
                    }
                }

                // 列印訂單按鈕判斷
                if (true) {
                    $logistic_print_button = false;

                    // 已經存在AllPayLogisticsID 關閉按鈕
                    $AllPayLogisticsID = $order->get_meta('_wooecpay_logistic_AllPayLogisticsID', true);

                    if (!empty($AllPayLogisticsID)) {
                        $logistic_print_button = true;
                    }
                }

                // 判斷是否為綠界物流
                if ($this->logisticHelper->is_ecpay_logistics($shipping_method_id)) {
                    // 判斷是否為超商取貨
                    if ($this->logisticHelper->is_ecpay_cvs_logistics($shipping_method_id)) {
                        echo '<div class="logistic_csv_info">';
                        echo '<h3>超商資訊</h3>';
                        echo wp_kses_post('<p><strong>超商編號:</strong>' . $order->get_meta('_ecpay_logistic_cvs_store_id', true) . '</p>');
                        echo wp_kses_post('<p><strong>超商名稱:</strong>' . $order->get_meta('_ecpay_logistic_cvs_store_name', true) . '</p>');

                        if ('yes' === get_option('wooecpay_keep_logistic_phone', 'yes')) {
                            echo wp_kses_post('<p><strong>收件人電話:</strong>' . $order->get_meta('wooecpay_shipping_phone', true) . '</p>');
                        }
                        echo '</div>';
                    }

                    echo '<div class="logistic_button_display">';
                    echo '<h3>物流單資訊</h3>';

                    if (true) {
                        echo wp_kses_post('<p><strong>廠商交易編號:</strong>' . $order->get_meta('_wooecpay_logistic_merchant_trade_no', true) . '</p>');
                        echo wp_kses_post('<p><strong>綠界物流編號:</strong>' . $order->get_meta('_wooecpay_logistic_AllPayLogisticsID', true) . '</p>');
                        echo wp_kses_post('<p><strong>寄貨編號:</strong>' . $order->get_meta('_wooecpay_logistic_CVSPaymentNo', true) . '</p>');
                        echo wp_kses_post('<p><strong>托運單號:</strong>' . $order->get_meta('_wooecpay_logistic_BookingNote', true) . '</p>');
                    }

                    // 產生地圖按鈕兒
                    if ($map_button) {
                        // 組合地圖FORM
                        $form_map = $this->logisticHelper->generate_ecpay_map_form($shipping_method_id, $order->get_id());
                        $form_map = str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_map);
                        $form_map = str_replace('</body></html>', '', $form_map);
                        $form_map = str_replace('<script type="text/javascript">document.getElementById("ecpay-form").submit();</script>', '', $form_map);

                        echo '</form>';
                        echo $form_map;
                        echo '<input class=\'button\' type=\'button\' onclick=\'ecpayChangeStore();\' value=\'變更門市\' />&nbsp;&nbsp;';
                    }

                    // 產生按鈕
                    if ($logistic_order_button) {
                        echo '<input class=\'button\' type=\'button\' value=\'建立物流訂單\' onclick=\'ecpayCreateLogisticsOrder(' . $order->get_id() . ');\'>';
                    }

                    // 列印訂單按鈕判斷
                    if ($logistic_print_button) {
                        $api_logistic_info = $this->logisticHelper->get_ecpay_logistic_api_info('print', $shipping_method_id);

                        $AllPayLogisticsID = $order->get_meta('_wooecpay_logistic_AllPayLogisticsID', true);
                        $CVSPaymentNo      = $order->get_meta('_wooecpay_logistic_CVSPaymentNo', true);
                        $CVSValidationNo   = $order->get_meta('_wooecpay_logistic_CVSValidationNo', true);

                        // 組合送綠界物流列印參數
                        $inputPrint['MerchantID']        = $api_logistic_info['merchant_id'];
                        $inputPrint['AllPayLogisticsID'] = $AllPayLogisticsID;

                        switch ($shipping_method_id) {

                        case 'Wooecpay_Logistic_CVS_711':
                        case 'Wooecpay_Logistic_CVS_711_Outside':
                            $inputPrint['CVSPaymentNo']    = $CVSPaymentNo;
                            $inputPrint['CVSValidationNo'] = $CVSValidationNo;
                            break;

                        case 'Wooecpay_Logistic_CVS_Family':
                        case 'Wooecpay_Logistic_CVS_Hilife':
                        case 'Wooecpay_Logistic_CVS_Okmart':
                            $inputPrint['CVSPaymentNo'] = $CVSPaymentNo;
                            break;

                        case 'Wooecpay_Logistic_Home_Tcat':
                        case 'Wooecpay_Logistic_Home_Tcat_Outside':
                        case 'Wooecpay_Logistic_Home_Ecan':
                        case 'Wooecpay_Logistic_Home_Post':
                            break;

                        default:
                            break;
                        }

                        try {
                            $factory = new Factory([
                                'hashKey'    => $api_logistic_info['hashKey'],
                                'hashIv'     => $api_logistic_info['hashIv'],
                                'hashMethod' => 'md5',
                            ]);

                            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

                            $form_print = $autoSubmitFormService->generate($inputPrint, $api_logistic_info['action'], '_Blank', 'ecpay_print');
                            $form_print = str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_print);
                            $form_print = str_replace('</body></html>', '', $form_print);
                            $form_print = str_replace('<script type="text/javascript">document.getElementById("ecpay_print").submit();</script>', '', $form_print);

                            echo '</form>';
                            echo $form_print;

                        } catch (RtnException $e) {
                            echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                        }

                        echo '<input class=\'button\' type=\'button\' onclick=\'ecpayLogisticPrint();\' value=\'列印物流單\' />&nbsp;&nbsp;';
                    }

                    echo '</div>';
                }
            }
        }
    }

    /**
     * 產生物流訂單
     */
    public function ajax_send_logistic_order_action() {
        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        ecpay_log('手動產生物流訂單', 'B00008', $order_id);

        $this->logisticHelper->send_logistic_order_action();
    }

    /**
     * 手動開立發票
     */
    public function ajax_send_invoice_create() {
        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

        if ($order = wc_get_order($order_id)) {
            ecpay_log('手動開立發票', 'C00001', $order_id);
            $this->invoiceHelper->invoice_create($order);
        }
    }

    /**
     * 自動開立發票
     */
    public function auto_invoice_create($order_id) {
        if ($order = wc_get_order($order_id)) {
            ecpay_log('自動開立發票', 'C00002', $order_id);
            $this->invoiceHelper->invoice_create($order);
        }

    }

    /**
     * 手動作廢發票
     */
    public function ajax_send_invoice_invalid() {
        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

        if ($order = wc_get_order($order_id)) {
            ecpay_log('手動作廢發票', 'C00003', $order_id);
            $this->invoiceHelper->invoice_invalid($order);
        }
    }

    /**
     * 自動作廢發票
     */
    public function auto_invoice_invalid($order_id) {
        if ($order = wc_get_order($order_id)) {
            ecpay_log('自動作廢發票', 'C00004', $order_id);
            $this->invoiceHelper->invoice_invalid($order);
        }

    }

    /**
     * 調整後台訂單列表頁欄位內容
     */
    public function custom_orders_list_column_content($column, $post_id) {
        switch ($column) {
        case 'order_number':
            if ($order = wc_get_order($post_id)) {
                // 檢查訂單是否可能有綠界訂單重複付款情形
                $is_duplicate_payment = $this->paymentHelper->check_order_is_duplicate_payment($order);
                if ($is_duplicate_payment['code'] === 1) {
                    // 顯示 Waring 小圖示
                    echo wp_kses_post('&nbsp;<span class="dashicons dashicons-warning" style="color: red;"></span>');
                }
            }
            break;
        }
    }

    /**
     * 檢查訂單是否重複付款
     */
    public function check_order_is_duplicate_payment($order) {
        $is_duplicate_payment = $this->paymentHelper->check_order_is_duplicate_payment($order);

        // 顯示提示訊息
        if ($is_duplicate_payment['code'] === 1) {
            echo wp_kses_post('<div><p style="color: red;"><span class="dashicons dashicons-warning"></span><strong>' . __('Please confirm the order. The system has detected that there may be duplicate payments for orders. (The order has multiple ecpay payment orders or cash on delivery has been selected.)', 'ecpay-ecommerce-for-woocommerce') . '</strong></p>');
            echo wp_kses_post('<p style="color: red;"><strong>' . __('Abnormal ecpay merchant trade no', 'ecpay-ecommerce-for-woocommerce') . ':</strong></p>');

            // 移除空值
            $merchant_trade_no_list = array_filter($is_duplicate_payment['merchant_trade_no'], function ($value, $key) {
                return !is_null($value) && $value !== '';
            }, ARRAY_FILTER_USE_BOTH);

            foreach ($merchant_trade_no_list as $merchant_trade_no) {
                echo wp_kses_post('<p style="color: red;">- ' . $merchant_trade_no . '</p>');
            }

            echo '<input class=\'button\' type=\'button\' onclick=\'wooecpayDuplicatePaymentComplete(' . $order->get_id() . ', ' . json_encode($merchant_trade_no_list) . ');\' value=\'標示已處理\' /></div>';
        }
    }

    /**
     * 綠界訂單重複付款提示標示為已處理
     */
    public function ajax_duplicate_payment_complete() {
        if ($order = wc_get_order($_POST['order_id'])) {
            $result = $this->paymentHelper->update_order_ecpay_orders_payment_status_complete($_POST['order_id']);

            if (isset($result)) {
                // 組合綠界金流特店交易編號
                $merchant_trade_no_list = '';
                foreach ($_POST['merchant_trade_no_list'] as $merchant_trade_no) {
                    $merchant_trade_no_list .= PHP_EOL . $merchant_trade_no;
                }

                $order->add_order_note(sprintf(__('Duplicate payments for ecpay orders are marked as processed.%s', 'ecpay-ecommerce-for-woocommerce'), $merchant_trade_no_list));
            }
        }
    }

    /**
     * 清理 Log
     */
    public function ajax_clear_ecpay_debug_log() {

        $this->loggerHelper->clear_log();

        wp_die();
    }
}