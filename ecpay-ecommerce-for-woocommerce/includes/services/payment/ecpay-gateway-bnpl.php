<?php

class Wooecpay_Gateway_Bnpl extends Wooecpay_Gateway_Base
{
    protected $payment_type;
    protected $min_amount;

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Bnpl';
        $this->payment_type         = 'BNPL';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname( __FILE__ )) );
        $this->has_fields           = false;
        $this->method_title         = __('ECPay BNPL', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = '使用綠界無卡分期付款';

        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->min_amount           = (int) $this->get_option('min_amount', 0);
        $this->max_amount           = (int) $this->get_option('max_amount', 0);

        $this->form_fields          = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings-gateway-bnpl.php' ;
        $this->init_settings();

        parent::__construct();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    // 後台參數設定後端驗證
    public function process_admin_options()
    {
        // 最小值判斷
        $_POST['woocommerce_' . $this->id . '_min_amount'] = (int) $_POST['woocommerce_' . $this->id . '_min_amount'];

        if ($_POST['woocommerce_' . $this->id . '_min_amount'] < 3000) {
            $_POST['woocommerce_' . $this->id . '_min_amount'] = 3000;
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ecpay-ecommerce-for-woocommerce'), $this->method_title));
        }

        parent::process_admin_options();
    }

    // 購物車金流顯示判斷
    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                // 訂單金額至少 3000 才開放無卡分期
                if ($this->min_amount > 0 and ($total < $this->min_amount || $total < 3000)) {
                    return false;
                }

                if ($this->max_amount > 0 && $total > $this->max_amount) {
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
        $order->add_order_note(__('Pay via ECPay BNPL', 'ecpay-ecommerce-for-woocommerce'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
