<?php
return [
    [
        'title' => __('Shipping settings', 'wooecpay'),
        'id' => 'wooecpay_logistic_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'wooecpay'),
        'id' => 'wooecpay_logistic_order_prefix',
        'type' => 'text',
        'desc' => __('Only letters and numbers allowed.', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'title' => __('CVS type', 'wooecpay'),
        'id' => 'wooecpay_logistic_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => __('C2C', 'Cvs type', 'wooecpay'),
            'B2C' => __('B2C', 'Cvs type', 'wooecpay'),
        ]
    ],
    [
        'title' => __('Auto get shipping payment no', 'wooecpay'),
        'id' => 'wooecpay_enable_logistic_auto',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Auto get shipping payment no when payment complete ( only for ecpay gateway )', 'wooecpay'),
        'desc_tip' => false
    ],
    [
        'title' => __('Sender name', 'wooecpay'),
        'id' => 'wooecpay_logistic_sender_name',
        'type' => 'text',
        'desc' => __('Name length between 1 to 10 letter', 'wooecpay'),
        'desc_tip' => true
    ],
    [
        'title' => __('Sender phone', 'wooecpay'),
        'id' => 'wooecpay_logistic_sender_phone',
        'type' => 'text',
        'desc' => __('Phone format (0x)xxxxxxx#xx', 'wooecpay'),
        'desc_tip' => true,
        'placeholder' => '(0x)xxxxxxx#xx',
        'custom_attributes' => [
            'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
        ]
    ],
    [
        'title' => __('Sender cellphone', 'wooecpay'),
        'id' => 'wooecpay_logistic_sender_cellphone',
        'type' => 'text',
        'desc' => __('Cellphone format 09xxxxxxxx', 'wooecpay'),
        'desc_tip' => true,
        'placeholder' => '09xxxxxxxx',
        'custom_attributes' => [
            'pattern' => '09\d{8}',
        ]
    ],
    [
        'title' => __('Sender zipcode', 'wooecpay'),
        'id' => 'wooecpay_logistic_sender_zipcode',
        'type' => 'text'
    ],
    [
        'title' => __('Sender address', 'wooecpay'),
        'id' => 'wooecpay_logistic_sender_address',
        'type' => 'text'
    ],
    [
        'title' => __('Keep shipping phone', 'wooecpay'),
        'id' => 'wooecpay_keep_logistic_phone',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Keep shipping phone', 'wooecpay')
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
        'id'       => 'wooecpay_enabled_logistic_stage', 
        'default'  => 'no',
        'desc' => __('ECPay sandbox', 'wooecpay')
    ],
    [
        'title' => __('MerchantID', 'wooecpay'),
        'id' => 'wooecpay_logistic_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'wooecpay'),
        'id' => 'wooecpay_logistic_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'wooecpay'),
        'id' => 'wooecpay_logistic_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
