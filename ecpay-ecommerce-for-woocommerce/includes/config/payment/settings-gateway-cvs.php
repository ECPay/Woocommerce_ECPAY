<?php
return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce'),
        /* translators: %s: Gateway method title */
        'label' => sprintf(__('Enable %s', 'ecpay-ecommerce-for-woocommerce'), $this->method_title),
        'type' => 'checkbox',
        'default' => 'no',
    ],
    'description' => [
        'title' => __('Description', 'woocommerce'),
        'type' => 'text',
        'default' => $this->order_button_text,
        'desc_tip' => true,
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
    ],
    'min_amount' => [
        'title' => __('A minimum order amount', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'placeholder' => 0,
        'description' => __('0 to disable minimum amount limit.', 'ecpay-ecommerce-for-woocommerce'),
        'custom_attributes' => [
            'min' => 0,
            'step' => 1
        ]
    ],
    'max_amount' => [
        'title' => __('A maximum order amount', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'placeholder' => 0,
        'description' => __('0 to disable maximum amount limit.', 'ecpay-ecommerce-for-woocommerce'),
        'custom_attributes' => [
            'min' => 0,
            'step' => 1
        ]
    ],
    'expire_date' => [
        'title' => __('Payment deadline (minutes)', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 10080,
        'placeholder' => 10080,
        'description' => __('CVS allowable payment deadline from 1 minute to 30 days.', 'ecpay-ecommerce-for-woocommerce'),
        'custom_attributes' => [
            'min' => 1,
            'max' => 43200,
            'step' => 1
        ]
    ]
];
