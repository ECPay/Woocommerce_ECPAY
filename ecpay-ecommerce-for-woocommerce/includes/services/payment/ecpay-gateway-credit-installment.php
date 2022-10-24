<?php

class Wooecpay_Gateway_Credit_Installment extends Wooecpay_Gateway_Base
{

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Credit_Installment';
        $this->payment_type         = 'Credit';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname( __FILE__ )) );
        $this->has_fields           = true;
        $this->method_title         = '綠界信用卡(分期)';
        $this->method_description   = '使用綠界信用卡(分期)付款';

        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->min_amount           = (int) $this->get_option('min_amount', 0);
        $this->number_of_periods    = $this->get_option('number_of_periods', []);

        $this->form_fields          = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings-gateway-credit-installment.php' ;
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

            if (empty($this->number_of_periods)) {
                return false;
            }
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function payment_fields()
    {
        parent::payment_fields();

        $total = $this->get_order_total();

        echo '<p>' . _x('Number of periods', 'Checkout info', 'ecpay-ecommerce-for-woocommerce');
        echo '<select name="ecpay_number_of_periods">';
        
        foreach ($this->number_of_periods as $number_of_periods) {

            // 圓夢分期有2W的限制
            if($number_of_periods == 30){

                if($total >= 20000){
                    echo '<option value="' . esc_attr($number_of_periods) . '">' . wp_kses_post($number_of_periods) . '</option>';
                }

            } else {
                echo '<option value="' . esc_attr($number_of_periods) . '">' . wp_kses_post($number_of_periods) . '</option>';
            }
        }

        echo '</select>';
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via ECPay Credit(installment)', 'ecpay-ecommerce-for-woocommerce'));

        if (isset($_POST['ecpay_number_of_periods'])) {
            $order->update_meta_data('_ecpay_payment_number_of_periods', (int) $_POST['ecpay_number_of_periods']);
        }
        $order->save();

        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
