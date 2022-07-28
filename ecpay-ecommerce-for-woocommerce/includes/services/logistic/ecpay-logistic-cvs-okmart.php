<?php

class Wooecpay_Logistic_Cvs_Okmart extends Wooecpay_Logistic_Base
{
    public static $logistics_type       = 'CVS';
    public static $logistics_sub_type   = 'OKMART';

    public function __construct($instance_id = 0)
    {
        $this->id                   = 'Wooecpay_Logistic_CVS_Okmart'; // Id for your shipping method. Should be uunique.
        $this->instance_id          = absint($instance_id);
        $this->method_title         = __('Ecpay CVS Okmart', 'ecpay-ecommerce-for-woocommerce');
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
}