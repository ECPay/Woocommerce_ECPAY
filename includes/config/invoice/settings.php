<?php
return [
    [
        'title' => __('E-Invoice setting', 'wooecpay'),
        'id' => 'wooecpay_invoice_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'wooecpay'),
        'id' => 'wooecpay_invoice_prefix',
        'type' => 'text',
        'desc' => __('Only letters and numbers allowed allowed.', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'name'     => __( 'Get mode', 'wooecpay' ),
        'type'     => 'select',
        'id'       => 'wooecpay_enabled_invoice_auto',
        'default'  => 'manual',
        'options' => [
            'manual' => __( 'manual', 'wooecpay' ),
            'auto_paid' => __( 'auto ( when order processing )', 'wooecpay' ),
        ]
    ],
    [
        'name'     => __( 'Invalid mode', 'wooecpay' ),
        'type'     => 'select',
        'id'       => 'wooecpay_enabled_cancel_invoice_auto',
        'default'  => 'manual',
        'options' => [
            'manual' => __( 'manual', 'wooecpay' ),
            'auto_cancel' => __( 'auto ( when order status cancelled OR refunded )', 'wooecpay' ),
        ]
    ],
    [
        'title' => __('Delay invoice date', 'wooecpay'),
        'id' => 'wooecpay_invoice_dalay_date',
        'type' => 'text',
        'desc' => __('Only numbers allowed.', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'title' => __('Donate unit', 'wooecpay'),
        'id' => 'wooecpay_invoice_donate',
        'type' => 'text',
        'desc' => __('Only numbers allowed.', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],

    [
        'title' => __('API settings', 'wooecpay'),
        'id' => 'wooecpay_invoice_options',
        'type' => 'title',
    ],
    [
        'name'     => __( 'ECPay sandbox', 'wooecpay' ),
        'type'     => 'checkbox',
        'desc'     => __( 'ECPay sandbox', 'wooecpay' ),
        'id'       => 'wooecpay_enabled_invoice_stage',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'title' => __('MerchantID', 'wooecpay'),
        'id' => 'wooecpay_invoice_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'wooecpay'),
        'id' => 'wooecpay_invoice_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'wooecpay'),
        'id' => 'wooecpay_invoice_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
