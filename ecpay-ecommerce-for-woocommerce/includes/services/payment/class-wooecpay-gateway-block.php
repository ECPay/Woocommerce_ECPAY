<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Wooecpay_Gateway_Block extends AbstractPaymentMethodType {

    protected $gateway;
    protected $name;

    public function __construct(string $name) {
        $this->name = $name;
        $this->settings = get_option('woocommerce_' . $this->name . '_settings', false);
    }

    public function initialize() {
        $this->gateway = new $this->name();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $js_url = '';
        switch ($this->name) {
            case 'Wooecpay_Gateway_Credit':
			    $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/credit-checkout.js';
                break;
            case 'Wooecpay_Gateway_Credit_Installment':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/credit-installment-checkout.js';
                break;
            case 'Wooecpay_Gateway_Webatm':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/webatm-checkout.js';
                break;
            case 'Wooecpay_Gateway_Atm':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/atm-checkout.js';
                break;
            case 'Wooecpay_Gateway_Cvs':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/cvs-checkout.js';
                break;
            case 'Wooecpay_Gateway_Barcode':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/barcode-checkout.js';
                break;
            case 'Wooecpay_Gateway_Applepay':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/applepay-checkout.js';
                break;
            case 'Wooecpay_Gateway_Bnpl':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/bnpl-checkout.js';
                break;
            case 'Wooecpay_Gateway_Twqr':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/twqr-checkout.js';
                break;
            case 'Wooecpay_Gateway_Dca':
                $js_url = WOOECPAY_PLUGIN_URL . 'public/js/blocks/dca-checkout.js';
                break;
        }

        wp_register_script(
            $this->name . '-blocks-integration',
            $js_url,
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if(function_exists('wp_set_script_translations')) {
            wp_set_script_translations($this->name . '-blocks-integration');
        }
        return [$this->name . '-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'id' => $this->gateway->id,
        ];
    }
}
