<?php
return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce'),
        /* translators: %s: Gateway method title */
        'label' => sprintf(__('Enable %s', 'ecpay-ecommerce-for-woocommerce'), $this->method_title),
        'type' => 'checkbox',
        'default' => 'no',
    ],
    'title' => [
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'default' => $this->method_title,
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip' => true,
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
    'number_of_periods' => [
        'title' => __('Enable number of periods', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'css' => 'width: 400px;',
        'default' => '',
        'description' => '',
        'options' => [
            3   => sprintf(__('%d Periods', 'ecpay-ecommerce-for-woocommerce'), 3),
            6   => sprintf(__('%d Periods', 'ecpay-ecommerce-for-woocommerce'), 6),
            12  => sprintf(__('%d Periods', 'ecpay-ecommerce-for-woocommerce'), 12),
            18  => sprintf(__('%d Periods', 'ecpay-ecommerce-for-woocommerce'), 18),
            24  => sprintf(__('%d Periods', 'ecpay-ecommerce-for-woocommerce'), 24),
            30  => sprintf(__('%s', 'ecpay-ecommerce-for-woocommerce'), '圓夢計畫'),
        ],
        'desc_tip' => true,
        'custom_attributes' => [
            'data-placeholder' => _x('Number of periods', 'Gateway setting', 'ecpay-ecommerce-for-woocommerce'),
        ],
    ]
];


