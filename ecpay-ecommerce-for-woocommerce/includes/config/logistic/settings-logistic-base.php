<?php
return [
    'title' => [
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'default' => $this->method_title,
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip' => true
    ],
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
    'cost' => [
        'title' => __('Shipping fee', 'ecpay-ecommerce-for-woocommerce'),
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