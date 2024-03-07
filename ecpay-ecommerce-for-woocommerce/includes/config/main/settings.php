<?php
return [
    [
        'title' => __('Enable ECPay method', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_main_options',
        'type' => 'title',
    ],
    [
        'name'     => __('Enable ECPay gateway method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __('Enable ECPay gateway method', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_enabled_payment',
        'default'  => 'no',
        'desc_tip' => true,
    ],
    [
        'name'     => __('Enable ECPay shipping method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => sprintf(__('Enable ECPay shipping method <br> If you want to use the cash on delivery service for your shop, you must enable the ECPay gateway method and <a href="%1$s">set up the delivery payment.</a>', 'ecpay-ecommerce-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=cod')),
        'id'       => 'wooecpay_enabled_logistic',
        'default'  => 'no',
        'desc_tip' => true,
    ],
    [
        'name'     => __('Enable ECPay invoice method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __('Enable ECPay invoice method', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_enabled_invoice',
        'default'  => 'no',
        'desc_tip' => true,
    ],
    [
        'type' => 'sectionend',
    ],
    [
        'title' => __('Tools', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_main_tool_options',
        'type' => 'title',
    ],
    [
        'name'     => __('Enable ECPay debug log', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __('Enable ECPay debug log', 'ecpay-ecommerce-for-woocommerce') . '<br>' . __('Due to the large size of the log file, please remember to disable this feature if not in use.', 'ecpay-ecommerce-for-woocommerce') . '<br>' . sprintf(__('Log folder : <code>%s</code>', 'ecpay-ecommerce-for-woocommerce'), WOOECPAY_PLUGIN_LOG_DIR) . '<br>' . __('<input class="button" style="margin-top: 5px;" type="button" value="Clear Log" onclick="wooecpayClearEcpayDebugLog();">', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_enabled_debug_log',
        'default'  => 'no',
        'desc_tip' => true,
    ],
    [
        'type' => 'sectionend',
    ],
];
