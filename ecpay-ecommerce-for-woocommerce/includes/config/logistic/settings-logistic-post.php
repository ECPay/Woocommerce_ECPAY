<?php
return [
    'tax_status' => [
        'title' => __('Tax status', 'woocommerce'),
        'type' => 'select',
        'default' => 'none',
        'options' => [
            'taxable' => __('Taxable', 'woocommerce'),
            'none' => _x('None', 'Tax status', 'woocommerce')
        ],
        'class' => 'wc-enhanced-select',
    ],
    'cost1' => [
        'title' => __('Shipping fee1', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'min' => 0,
        'step' => 1
    ],
    'cost2' => [
        'title' => __('Shipping fee2', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'min' => 0,
        'step' => 1
    ],
    'cost3' => [
        'title' => __('Shipping fee3', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'min' => 0,
        'step' => 1
    ],
    'cost4' => [
        'title' => __('Shipping fee4', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'number',
        'default' => 0,
        'min' => 0,
        'step' => 1
    ],
    'cost_requires' => [
        'title' => __('Free shipping requires...', 'woocommerce'),
        'type' => 'select',
        'default' => '',
        'options' => [
            '' => __('N/A', 'woocommerce'),
            'coupon'     => __('A valid free shipping coupon', 'woocommerce'),
            'min_amount' => __('A minimum order amount', 'woocommerce'),
            'min_amount_or_coupon'     => __('A minimum order amount OR a coupon', 'woocommerce'),
            'min_amount_and_coupon'       => __('A minimum order amount AND a coupon', 'woocommerce'),
        ],
        'class' => 'wc-enhanced-select'
    ],
    'min_amount' => [
        'title' => __('A minimum order amount', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'price',
        'default' => 0,
        'placeholder' => wc_format_localized_price(0),
        'description' => __('Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce'),
        'desc_tip' => true
    ]
];