<?php

class Wooecpay_Gateway_Applepay extends Wooecpay_Gateway_Base
{
    protected $payment_type;
    protected $min_amount;

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Applepay';
        $this->payment_type         = 'ApplePay';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname( __FILE__ )) );
        $this->has_fields           = false;
        $this->method_title         = __('ECPay ApplePay', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = '使用綠界ApplePay付款';

        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->min_amount           = (int) $this->get_option('min_amount', 0);

        $this->form_fields          = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings-gateway-applepay.php' ;
        $this->init_settings();

        parent::__construct();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($this->min_amount > 0 and $total < $this->min_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id) 
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via ECPay Applepay', 'ecpay-ecommerce-for-woocommerce'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ]; 
    }
}
