<?php
return [
    [
        'title' => __('Shipping settings', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_options',
        'type' => 'title',
    ],
    [
        'title' => __('Order no prefix', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_order_prefix',
        'type' => 'text',
        'desc' => __('Only a maximum of 5-character letters and numbers are allowed.', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true,
        'custom_attributes' => [
            'pattern' => '^[a-zA-Z0-9]{0,5}$',
        ]
    ],
    [
        'title' => __( 'Display order item name', 'ecpay-ecommerce-for-woocommerce' ),
        'id' => 'wooecpay_enabled_logistic_disp_item_name',
        'type' => 'checkbox',
        'default' => 'no',
        'desc'     => __( 'Display order item name', 'ecpay-ecommerce-for-woocommerce' ),
        'desc_tip' => false
    ],
    [
        'title' => __('CVS type', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => __('C2C', 'Cvs type', 'ecpay-ecommerce-for-woocommerce'),
            'B2C' => __('B2C', 'Cvs type', 'ecpay-ecommerce-for-woocommerce'),
        ]
    ],
    [
        'title' => __('Auto get shipping payment no', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_enable_logistic_auto',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Auto get shipping payment no when payment complete ( only for ecpay gateway )', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => false
    ],
    [
        'title' => __('Sender name', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_sender_name',
        'type' => 'text',
        'desc' => __('Name length between 1 to 10 letter', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true
    ],
    [
        'title' => __('Sender phone', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_sender_phone',
        'type' => 'text',
        'desc' => __('Phone format (0x)xxxxxxx#xx', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true,
        'placeholder' => '(0x)xxxxxxx#xx',
        'custom_attributes' => [
            'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
        ]
    ],
    [
        'title' => __('Sender cellphone', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_sender_cellphone',
        'type' => 'text',
        'desc' => __('Cellphone format 09xxxxxxxx', 'ecpay-ecommerce-for-woocommerce'),
        'desc_tip' => true,
        'placeholder' => '09xxxxxxxx',
        'custom_attributes' => [
            'pattern' => '09\d{8}',
        ]
    ],
    [
        'title' => __('Sender zipcode', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_sender_zipcode',
        'type' => 'text'
    ],
    [
        'title' => __('Sender address', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_sender_address',
        'type' => 'text'
    ],
    [
        'title' => __('Keep shipping phone', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_keep_logistic_phone',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Keep shipping phone', 'ecpay-ecommerce-for-woocommerce')
    ],
    [
        'title' => __('Enable ECPay outlying islands shipping', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_enabled_logistic_outside',
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'default' => '',
        'desc' => __('Notice：<br>1. The option will only appear in the 「Shipping Method」 after activation.<br>2. If 「Ecpay CVS 7-11」 outlying islands shipping is not enabled, there will be no distinction between outlying island and main island shipments; shipping settings will be shared.<br>3. If 「Ecpay CVS 7-11」 outlying islands shipping is enabled, after selecting a store, it will verify if the chosen store aligns with the selected shipping method.<br>4. If 「Ecpay Home Tcat」 outlying islands shipping is not enabled, there will be no distinction between outlying island and main island shipments; shipping settings will be shared.<br>5. If 「Ecpay Home Tcat」 outlying islands shipping is enabled, it will verify before checkout whether the shipping destination matches the selected shipping method.', 'ecpay-ecommerce-for-woocommerce'),
        'options' => [
            'Wooecpay_Logistic_CVS_711'   => __('Ecpay CVS 7-11', 'ecpay-ecommerce-for-woocommerce'),
            'Wooecpay_Logistic_Home_Tcat' => __('Ecpay Home Tcat', 'ecpay-ecommerce-for-woocommerce'),
        ],
        'custom_attributes' => [
            'data-placeholder' => __('Ecpay shipping methods', 'ecpay-ecommerce-for-woocommerce'),
        ],
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
        'name'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce'),
        'type'     => 'checkbox',
        'desc'     => __( 'ECPay sandbox', 'ecpay-ecommerce-for-woocommerce'),
        'id'       => 'wooecpay_enabled_logistic_stage',
        'default'  => 'yes',
        'desc' => __('ECPay sandbox', 'ecpay-ecommerce-for-woocommerce')
    ],
    [
        'title' => __('MerchantID', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_mid',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashKey', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_hashkey',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'title' => __('HashIV', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_logistic_hashiv',
        'type' => 'text',
        'desc_tip' => true
    ],
    [
        'type' => 'sectionend',
    ],
];
