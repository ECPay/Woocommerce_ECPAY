<?php
return [

    [
        'title' => __('Enable ECPay method', 'wooecpay'),
        'id' => 'wooecpay_main_options',
        'type' => 'title',
    ],
    [

        'name'     => __( 'Enable ECPay gateway method', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay gateway method', 'wooecpay' ),
        'id'       => 'wooecpay_ecpay_enabled_payment',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'name'     => __( 'Enable ECPay shipping method', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay shipping method', 'wooecpay' ),
        'id'       => 'wooecpay_ecpay_enabled_logistic',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'name'     => __( 'Enable ECPay invoice method', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Enable ECPay invoice method', 'wooecpay' ),
        'id'       => 'wooecpay_ecpay_enabled_invoice',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [

        'type' => 'sectionend',
    ],
];
