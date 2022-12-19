<?php

class Wooecpay_Logistic_Home_Post extends Wooecpay_Logistic_Base
{
    public function __construct($instance_id = 0)
    {
        $this->id                   = 'Wooecpay_Logistic_Home_Post'; // Id for your shipping method. Should be uunique.
        $this->instance_id          = absint($instance_id);
        $this->method_title         = __('Ecpay Home POST', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = ''; // Description shown in admin

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        // 載入欄位
        $this->instance_form_fields = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/logistic/settings-logistic-post.php';

        parent::__construct();

    }

    // 重量超過2KG不顯示中華郵政
    public function is_available($package)
    {
        $is_available = true;

        $total_weight = WC()->cart->get_cart_contents_weight();

        if ($total_weight > 20) {
           $is_available = false ; 
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }  

    // 購物車物流費用計算
    public function calculate_shipping($package = [])
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => 0,
            'package' => $package,
        ];

        // 計算重量運費
        $rate = $this->calculate_shipping_weight($rate);

        $has_coupon = $this->check_has_coupon($this->cost_requires, ['coupon', 'min_amount_or_coupon', 'min_amount_and_coupon']);
        $has_min_amount = $this->check_has_min_amount($this->cost_requires, ['min_amount', 'min_amount_or_coupon', 'min_amount_and_coupon']);

        switch ($this->cost_requires) {
            case 'coupon':
                $set_cost_zero = $has_coupon;
                break;
            case 'min_amount':
                $set_cost_zero = $has_min_amount;
                break;
            case 'min_amount_or_coupon':
                $set_cost_zero = $has_min_amount || $has_coupon;
                break;
            case 'min_amount_and_coupon':
                $set_cost_zero = $has_min_amount && $has_coupon;
                break;
            default:
                $set_cost_zero = false;
                break;
        }

        if ($set_cost_zero) {
            $rate['cost'] = 0;
        }

        $this->add_rate($rate);
        do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
    }


    public function calculate_shipping_weight($rate)
    {

        $total_weight = WC()->cart->get_cart_contents_weight();
        
        $cost1 = $this->get_option('cost1');
        $cost2 = $this->get_option('cost2');
        $cost3 = $this->get_option('cost3');
        $cost4 = $this->get_option('cost4');

        if ($total_weight <= 5) {
            $rate['cost'] = $cost1;

        } else if ($total_weight > 5 && $total_weight <= 10) {
            $rate['cost'] = $cost2;

        } else if ($total_weight > 10 && $total_weight <= 15) {
            $rate['cost'] = $cost3;

        } else if ($total_weight > 15 && $total_weight <= 20) {
            $rate['cost'] = $cost4;
        }

        return $rate ;
    }
}