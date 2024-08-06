<?php

class Wooecpay_Gateway {

    public function __construct() {

        $this->load_api();

        add_filter('woocommerce_payment_gateways', array($this, 'add_method'));
        add_action('woocommerce_loaded', array($this, 'load_payment_gateway'));
        add_action( 'woocommerce_blocks_loaded', array($this, 'load_payment_gateway_block'));

        // Email發送內容
        if (get_option('wooecpay_enabled_payment_disp_email') == 'yes') {
            add_action('woocommerce_email_after_order_table', array($this, 'add_email_payment_info'), 10, 4);
        }
    }

    /**
     * 載入回傳相關設定
     * @var array
     */
    private function load_api() {
        require_once WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-response.php';
    }

    /**
     * 載入付款方式
     * @var array
     */
    public function load_payment_gateway() {

        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-base.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-credit.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-credit-installment.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-atm.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-webatm.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-barcode.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-cvs.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-applepay.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-dca.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-twqr.php';
        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/ecpay-gateway-bnpl.php';

        include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/woocommerce-gateway-cod.php'; // 貨到付款相關程序
    }

    public function add_method($methods) {
        $methods[] = 'Wooecpay_Gateway_Credit';
        $methods[] = 'Wooecpay_Gateway_Credit_Installment';
        $methods[] = 'Wooecpay_Gateway_Webatm';
        $methods[] = 'Wooecpay_Gateway_Atm';
        $methods[] = 'Wooecpay_Gateway_Cvs';
        $methods[] = 'Wooecpay_Gateway_Barcode';
        $methods[] = 'Wooecpay_Gateway_Applepay';
        $methods[] = 'Wooecpay_Gateway_Dca';
        $methods[] = 'Wooecpay_Gateway_Twqr';
        $methods[] = 'Wooecpay_Gateway_Bnpl';

        return $methods;
    }

    /**
     * 宣告付款方式支援 Woocommerce Block
     */
    public function load_payment_gateway_block() {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/payment/class-wooecpay-gateway-block.php';
        
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Credit'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Credit_Installment'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Webatm'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Atm'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Cvs'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Barcode'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Applepay'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Dca'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Twqr'));
                    $payment_method_registry->register(new Wooecpay_Gateway_Block('Wooecpay_Gateway_Bnpl'));
                }
            );
        }
    }

    // EMAIL 顯示付款資訊樣板
    public function add_email_payment_info($order, $sent_to_admin, $plain_text, $email) {

        if ($email->id == 'customer_on_hold_order') {

            switch ($order->get_payment_method()) {
            case 'Wooecpay_Gateway_Atm':
                $template_file = 'payment_email/atm.php';
                break;

            case 'Wooecpay_Gateway_Cvs':
                $template_file = 'payment_email/cvs.php';
                break;

            case 'Wooecpay_Gateway_Barcode':
                $template_file = 'payment_email/barcode.php';
                break;
            }

            if (isset($template_file)) {

                $args = array(
                    'order'         => $order,
                    'sent_to_admin' => $sent_to_admin,
                    'plain_text'    => $plain_text,
                    'email'         => $email,
                );

                wc_get_template($template_file, $args, '', WOOECPAY_PLUGIN_INCLUDE_DIR . '/templates/');
            }
        }
    }
}
