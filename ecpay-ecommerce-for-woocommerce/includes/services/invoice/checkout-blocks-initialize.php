<?php

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

add_action(
    'woocommerce_blocks_loaded',
    function() {
        require_once plugin_dir_path(__FILE__) . 'class-blocks-integration.php';
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function( $integration_registry ) {
                $integration_registry->register( new Blocks_Integration_Invoice_Dev() );
            }
        );

        if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
            woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CheckoutSchema::IDENTIFIER,
                    'namespace'       => 'ecpay-invoice-block',
                    'data_callback'   => 'checkout_block_data_callback',
                    'schema_callback' => 'checkout_block_schema_callback',
                    'schema_type'     => ARRAY_A,
                )
            );
        }
    }
);


/**
 * Callback function to register endpoint data for blocks.
 *
 * @return array
 */
function checkout_block_data_callback() {
    return array(
        'invoice_type' => 'p',
        'invoice_carruer_type' => '0',
        'invoice_customer_company' => '',
        'invoice_customer_identifier' => '',
        'invoice_love_code' => '',
        'invoice_carruer_num' => '',
    );
}

/**
 * Callback function to register schema for data.
 *
 * @return array
 */
function checkout_block_schema_callback() {
    return array(
        'invoice_type'  => array(
            'description' => __('Invoice Type', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
        'invoice_carruer_type'  => array(
            'description' => __('Carruer Type', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
        'invoice_customer_company'  => array(
            'description' => __('Company Name', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
        'invoice_customer_identifier'  => array(
            'description' => __('Uniform Numbers', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
        'invoice_love_code'  => array(
            'description' => __('Donation', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
        'invoice_carruer_num'  => array(
            'description' => __('Barcode', 'ecpay-ecommerce-for-woocommerce'),
            'type'        => array('string', 'null'),
            'readonly'    => true,
        ),
    );
}