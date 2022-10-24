<?php

class Wooecpay_Logistic_CVS_Family extends Wooecpay_Logistic_Base
{
    public function __construct($instance_id = 0)
    {
        $this->id                   = 'Wooecpay_Logistic_CVS_Family'; // Id for your shipping method. Should be uunique.
        $this->instance_id          = absint($instance_id);
        $this->method_title         = __('Ecpay CVS Family', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = ''; // Description shown in admin

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        // 載入欄位
        $this->instance_form_fields = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/logistic/settings-logistic-base.php';

        parent::__construct();

    }

    public function is_available($package)
    {
        $is_available = true;

        $total = WC()->cart->cart_contents_total ;

        // 金額超過2萬元時不顯示綠界超商物流
        if($total >= 20000){
           $is_available = false ; 
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }  
}