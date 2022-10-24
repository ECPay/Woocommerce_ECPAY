<?php
/**
 * @copyright Copyright (c) 2016 Green World FinTech Service Co., Ltd. (https://www.ecpay.com.tw)
 * @version 1.0.110115
 *
 * Plugin Name: ECPay Ecommerce for WooCommerce
 * Plugin URI: https://www.ecpay.com.tw
 * Description: Ecpay Plug for WooCommerce
 * Version: 1.0.221019
 * Author: ECPay Green World FinTech Service Co., Ltd.
 * Author URI: https://www.ecpay.com.tw
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 * WC requires at least: 6
 * WC tested up to: 6.5.1
 */

// 相關檢查
defined( 'ABSPATH' ) or exit;

// 相關常數定義
define( 'WOOECPAY_VERSION', '1.0.220714' );
define( 'WOOECPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOECPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOECPAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WOOECPAY_PLUGIN_INCLUDE_DIR', WOOECPAY_PLUGIN_DIR.'includes' );

// 
require_once(WOOECPAY_PLUGIN_DIR . '/vendor/autoload.php');

// 相關載入程序
require plugin_dir_path( __FILE__ ) . 'admin/settings/class-wooecpay-setting.php';
require plugin_dir_path( __FILE__ ) . 'admin/order/class-wooecpay-order.php';

$plugin_main        = new Wooecpay_Setting();
$plugin_order       = new Wooecpay_Order();

if ('yes' === get_option('wooecpay_enabled_payment', 'yes')) {
    require plugin_dir_path( __FILE__ ) . 'includes/services/payment/class-wooecpay-gateway.php';
    $plugin_payment     = new Wooecpay_Gateway();  
}

if ('yes' === get_option('wooecpay_enabled_logistic', 'yes')) {
    require plugin_dir_path( __FILE__ ) . 'includes/services/logistic/class-wooecpay-logistic.php';
    $plugin_logistic    = new Wooecpay_Logistic();  
}

if ('yes' === get_option('wooecpay_enabled_invoice', 'yes')) {
    require plugin_dir_path( __FILE__ ) . 'includes/services/invoice/class-wooecpay-invoice.php';
    $plugin_invoice     = new Wooecpay_invoice();  
}


