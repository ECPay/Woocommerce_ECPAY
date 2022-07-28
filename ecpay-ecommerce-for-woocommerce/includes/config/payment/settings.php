<?php
return [
    [
        'title' => __('Gateway settings', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_order_prefix',
        'type' => 'text',
        'desc' => __('Only letters and numbers allowed.', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true
    ],
    [
        'name'     => __( 'Display order item name', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Display order item name', 'ecpay-ecommerce-for-woocommerce' ),
        'id'       => 'wooecpay_enabled_payment_disp_item_name',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'name'     => __( 'Show payment info in email', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Enabled payment disp email', 'ecpay-ecommerce-for-woocommerce' ),
        'id'       => 'wooecpay_enabled_payment_disp_email',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'type' => 'sectionend',
    ],

    [
        'title' => __('API settings', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_options',
        'type' => 'title',
    ],
    [
        'name'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'checkbox',
        'desc'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce' ),
        'id'       => 'wooecpay_enabled_payment_stage',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'title' => __('MerchantID', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_payment_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
