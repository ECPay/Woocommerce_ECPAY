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
    'dca_woo_blocks_line' => [
        'title' => '<hr>',
        'type'  => 'title',
        'id'    => 'wooecpay_dca_woo_blocks_line',
    ],
    'dca_woo_blocks_caption' => [
        'title' => '',
        'type'  => 'title',
        'description' => __('There are two section fields for DCA settings: WooCommerce Blocks and Woocommerce Shortcode. Please fill out the section that matches your current page configuration. If you are uncertain about which page configuration you are using, input the identical setting in both sections.', 'ecpay-ecommerce-for-woocommerce'),
        'id'    => 'dca_woo_blocks_caption',
    ],
    'dca_woo_blocks_title' => [
        'title' => __('DCA (Support Woocommerce Blocks)', 'ecpay-ecommerce-for-woocommerce'),
        'description' => __('The following settings support the WooCommerce Blocks checkout page and do not support the use of the traditional shortcode-based checkout. Please configure carefully', 'ecpay-ecommerce-for-woocommerce'),
        'type'  => 'title',
        'id'    => 'wooecpay_dca_woo_blocks_title',
    ],
    'dca_periodType' => [
        'title' => __('Dca payment periodType', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_dca_periodtype',
        'type' => 'select',
        'default' => 'Y',
        'placeholder' => 0,
        'description' => __('Support woocommerce checkout blocks', 'ecpay-ecommerce-for-woocommerce'),
        'options' => [
            'Y' => __('Year', 'ecpay-ecommerce-for-woocommerce'),
            'M' => __('Month', 'ecpay-ecommerce-for-woocommerce'),
            'D' => __('Day', 'ecpay-ecommerce-for-woocommerce'),
        ]
    ],
    'dca_frequency' => [
        'title' => __('Dca payment frequency', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_dca_frequency',
        'type' => 'number',
        'default' => 1,
        'placeholder' => 0,
        'description' => __('Support woocommerce checkout blocks', 'ecpay-ecommerce-for-woocommerce'),
        'custom_attributes' => [
            'min' => 1,
            'step' => 1
        ]
    ],
    'dca_execTimes' => [
        'title' => __('Dca payment execTimes', 'ecpay-ecommerce-for-woocommerce'),
        'id' => 'wooecpay_dca_exectimes',
        'type' => 'number',
        'default' => 2,
        'placeholder' => 0,
        'description' => __('Support woocommerce checkout blocks', 'ecpay-ecommerce-for-woocommerce'),
        'custom_attributes' => [
            'min' => 2,
            'step' => 1
        ]
    ],
    'ecpay_dca_line' => [
        'title' => '<hr>',
        'type'  => 'title',
        'id'    => 'ecpay_dca_line',
    ],
    'ecpay_dca_title' => [
        'title' => __('DCA (Support Woocommerce Shortcode)', 'ecpay-ecommerce-for-woocommerce'),
        'description' => __('The following settings support the traditional shortcode-based checkout page and do not support the use of the WooCommerce Blocks checkout. Please configure carefully', 'ecpay-ecommerce-for-woocommerce'),
        'type'  => 'title',
        'id'    => 'ecpay_dca_title',
    ],
    'ecpay_dca' => [
        'title' => __('DCA', 'ecpay-ecommerce-for-woocommerce'),
        'type' => 'ecpay_dca',
        'default' => '',
        'description' => '',
        'desc_tip' => true,
    ],
];
