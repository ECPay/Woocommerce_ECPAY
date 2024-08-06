<?php
return [
    [
        'title' => __('E-Invoice setting', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_prefix',
        'type' => 'text',
        'desc' => __('Only a maximum of 5-character letters and numbers are allowed.', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true,
        'custom_attributes' => [
            'pattern' => '^[a-zA-Z0-9]{0,5}$',
        ]
    ],
    [
        'name'     => __( 'Get mode', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'select',
        'id'       => 'wooecpay_enabled_invoice_auto',
        'default'  => 'manual',
        'options' => [
            'manual' => __( 'manual', 'ecpay-ecommerce-for-woocommerce' ),
            'auto_paid' => __( 'auto ( when order processing )', 'ecpay-ecommerce-for-woocommerce' ),
        ]
    ],
    [
        'name'     => __( 'Invalid mode', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'select',
        'id'       => 'wooecpay_enabled_cancel_invoice_auto',
        'default'  => 'manual',
        'options' => [
            'manual' => __( 'manual', 'ecpay-ecommerce-for-woocommerce' ),
            'auto_cancel' => __( 'auto ( when order status cancelled OR refunded )', 'ecpay-ecommerce-for-woocommerce' ),
        ]
    ],
    [
        'title' => __('Delay invoice date', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_dalay_date',
        'type' => 'text',
        'desc' => __('Only numbers allowed.', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true
    ],
    [
        'title' => __('Donate unit', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_donate',
        'type' => 'text',
        'desc' => __('Only numbers allowed.', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true
    ],
    [
        'name'     => __( 'Invoice carruer papper option', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'select',
        'id'       => 'wooecpay_invoice_carruer_papper',
        'default'  => 'enable',
        'options' => [
            'enable' => __( 'enable', 'ecpay-ecommerce-for-woocommerce' ),
            'disable' => __( 'disable', 'ecpay-ecommerce-for-woocommerce' ),
        ]
    ],
    [
        'type' => 'sectionend',
    ],

    [
        'title' => __('API settings', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_options',
        'type' => 'title',
    ],
    [
        'name'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce' ),
        'type'     => 'checkbox',
        'desc'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce' ),
        'id'       => 'wooecpay_enabled_invoice_stage',
        'default'  => 'no',
        'desc_tip' => true,  
    ],
    [
        'title' => __('MerchantID', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_invoice_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
