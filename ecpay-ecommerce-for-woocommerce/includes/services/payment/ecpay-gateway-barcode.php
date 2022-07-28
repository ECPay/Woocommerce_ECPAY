<?php

class Wooecpay_Gateway_Barcode extends Wooecpay_Gateway_Base
{

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Barcode';
        $this->payment_type         = 'BARCODE';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname( __FILE__ )) );
        $this->has_fields           = false;
        $this->method_title         = '綠界超商條碼';
        $this->method_description   = '使用綠界超商條碼付款';

        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->expire_date          = (int) $this->get_option('expire_date', 7);
        $this->min_amount           = (int) $this->get_option('min_amount', 0);
        $this->max_amount           = (int) $this->get_option('max_amount', 0);

        $this->form_fields          = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings-gateway-barcode.php' ;
        $this->init_settings();

        parent::__construct();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if (is_checkout() || is_view_order_page()) {
            // wp_enqueue_style('wooecpay_barcode_css', WOOECPAY_PLUGIN_URL . 'public/css/style.css');
        }
    }

    public function process_admin_options()
    {
        $_POST['woocommerce_Wooecpay_Gateway_Barcode_expire_date'] = (int) $_POST['woocommerce_Wooecpay_Gateway_Barcode_expire_date'];
        if ($_POST['woocommerce_Wooecpay_Gateway_Barcode_expire_date'] < 1 || (int) $_POST['woocommerce_Wooecpay_Gateway_Barcode_expire_date'] > 60) {
            
            $_POST['woocommerce_Wooecpay_Gateway_Barcode_expire_date'] = 7;
            WC_Admin_Settings::add_error(__('BARCODE payment deadline out of range. Set as default value.', 'ecpay-ecommerce-for-woocommerce'));
        }

        $_POST['woocommerce_Wooecpay_Gateway_Barcode_min_amount'] = (int) $_POST['woocommerce_Wooecpay_Gateway_Barcode_min_amount'];
        if ($_POST['woocommerce_Wooecpay_Gateway_Barcode_min_amount'] > 0 && (int) $_POST['woocommerce_Wooecpay_Gateway_Barcode_min_amount'] < 30) {
            
            $_POST['woocommerce_Wooecpay_Gateway_Barcode_min_amount'] = 0;
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ecpay-ecommerce-for-woocommerce'), $this->method_title));
        }

        $_POST['woocommerce_Wooecpay_Gateway_Barcode_max_amount'] = (int) $_POST['woocommerce_Wooecpay_Gateway_Barcode_max_amount'];
        if ($_POST['woocommerce_Wooecpay_Gateway_Barcode_max_amount'] > 20000) {

            WC_Admin_Settings::add_message(sprintf(__('%1$s maximum amount more then normal maximum (%2$d).', 'ecpay-ecommerce-for-woocommerce'), $this->method_title, 20000));
        }

        parent::process_admin_options();
    }
    

    // 購物車金流顯示判斷
    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($total < 30) {
                    return false;
                }
                if ($this->min_amount > 0 and $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 and $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    // 處理點擊結帳按鈕後的行為
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via ECPay BARCODE', 'ecpay-ecommerce-for-woocommerce'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ]; 
    }
}
