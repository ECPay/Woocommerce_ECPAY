<?php

class Wooecpay_Gateway_Credit_Installment extends Wooecpay_Gateway_Base
{
    protected $payment_type;
    protected $min_amount;
    protected $number_of_periods;

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Credit_Installment';
        $this->payment_type         = 'Credit';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname(__FILE__)));
        $this->has_fields           = true;
        $this->method_title         = __('ECPay Credit Installment', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = '使用綠界信用卡(分期)付款';

        $this->title                = __('ECPay Credit Installment', 'ecpay-ecommerce-for-woocommerce');
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
            if ($number_of_periods == 30) {
                if ($total >= 20000) {
                    echo '<option value="' . esc_attr($number_of_periods) . '">' . wp_kses_post($number_of_periods) . '</option>';
                }
            } 
            else {
                echo '<option value="' . esc_attr($number_of_periods) . '">' . wp_kses_post($number_of_periods) . '</option>';
            }
        }
        echo '</select>';
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (isset($_POST['ecpay_number_of_periods'])) {

            $installmentDatas = get_option('woocommerce_Wooecpay_Gateway_Credit_Installment_settings', []);
            $allowed_periods = isset($installmentDatas['number_of_periods']) ? $installmentDatas['number_of_periods'] : [];

            // 取得前端傳來的期數
            $periods = isset($_POST['ecpay_number_of_periods']) ? (int) $_POST['ecpay_number_of_periods'] : 0;

            // 1. 驗證期數是否為後台啟用的項目
            if ($periods <= 0 || !in_array($periods, $allowed_periods)) {
                wc_add_notice(__('Invalid installment option. Please choose a different one.', 'ecpay-ecommerce-for-woocommerce'), 'error');
                return [
                    'result' => 'failure',
                ];
            }

            // 2. 驗證 30 期的金額限制（避免前端竄改 DOM 繞過）
            if ($periods === 30 && $order->get_total() < 20000) {
                wc_add_notice(__('A minimum order total of NT$ 20,000 is required for 30 installments.', 'ecpay-ecommerce-for-woocommerce'), 'error');
                return [
                    'result' => 'failure',
                ];
            }

            $order->update_meta_data('_ecpay_payment_number_of_periods', $periods);

        }

        $order->add_order_note(__('Pay via ECPay Credit(installment)', 'ecpay-ecommerce-for-woocommerce'));
        
        $order->save();

        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
