<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;


class Wooecpay_Logistic_Base extends WC_Shipping_Method
{
    protected $min_amount;
    protected $cost;
    protected $cost_requires;

    public function __construct()
    {
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        $this->title            = $this->get_option('title');
        $this->tax_status       = $this->get_option('tax_status');
        $this->cost             = $this->get_option('cost');
        $this->cost_requires    = $this->get_option('cost_requires');
        $this->min_amount       = $this->get_option('min_amount', 0);

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }


    // 購物車物流費用計算
    public function calculate_shipping($package = [])
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $this->cost,
            'package' => $package,
        ];

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

    protected function check_has_coupon($requires, $check_requires_list)
    {
        if (in_array($requires, $check_requires_list)) {
            $coupons = WC()->cart->get_coupons();
            if ($coupons) {
                foreach ($coupons as $code => $coupon) {
                    if ($coupon->is_valid() && $coupon->get_free_shipping()) {
                        return true;
                        break;
                    }
                }
            }
        }

        return false;
    }

    protected function check_has_min_amount($requires, $check_requires_list, $original = false)
    {
        if (in_array($requires, $check_requires_list)) {

            $total = WC()->cart->get_displayed_subtotal();
            if ($original === false) {
                if ('incl' === WC()->cart->get_tax_price_display_mode) {
                    $total = round($total - (WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total()), wc_get_price_decimals());
                } else {
                    $total = round($total - WC()->cart->get_cart_discount_total(), wc_get_price_decimals());
                }
            }

            if ($total >= $this->min_amount) {
                return true;
            }
        }
        return false;
    }
}