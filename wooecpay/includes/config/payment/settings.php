<?php
return [
    [
        'title' => __('Gateway settings', 'wooecpay'),
        'id' => 'wooecpay_payment_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'wooecpay'),
        'id' => 'wooecpay_payment_order_prefix',
        'type' => 'text',
        'desc' => __('Only letters and numbers allowed.', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'name'     => __( 'Display order item name', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Display order item name', 'wooecpay' ),
        'id'       => 'wooecpay_enabled_payment_disp_item_name',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'name'     => __( 'Show payment info in email', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'Enabled payment disp email', 'wooecpay' ),
        'id'       => 'wooecpay_enabled_payment_disp_email',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'type' => 'sectionend',
    ],

    [
        'title' => __('API settings', 'wooecpay'),
        'id' => 'wooecpay_payment_options',
        'type' => 'title',
    ],
    [
        'name'     => __( 'ECPay sandbox', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'ECPay sandbox', 'wooecpay' ),
        'id'       => 'wooecpay_enabled_payment_stage',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'title' => __('MerchantID', 'wooecpay'),
        'id' => 'wooecpay_payment_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'wooecpay'),
        'id' => 'wooecpay_payment_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'wooecpay'),
        'id' => 'wooecpay_payment_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
