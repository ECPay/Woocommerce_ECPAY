<?php
return [

    [
        'title' => __('Enable ECPay method', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_main_options',
        'type' => 'title',
    ],
    [

        'name'     => __( 'Enable ECPay gateway method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay gateway method', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_ecpay_enabled_payment',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'name'     => __( 'Enable ECPay shipping method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay shipping method', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_ecpay_enabled_logistic',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'name'     => __( 'Enable ECPay invoice method', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay invoice method', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_ecpay_enabled_invoice',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'type' => 'sectionend',
    ],
];
