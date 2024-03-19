<?php
/**
 * @copyright Copyright (c) 2016 Green World FinTech Service Co., Ltd. (https://www.ecpay.com.tw)
 * @version 1.1.2403150
 *
 * Plugin Name: ECPay Ecommerce for WooCommerce
 * Plugin URI: https://www.ecpay.com.tw
 * Description: Ecpay Plug for WooCommerce
 * Version: 1.1.2403150
 * Author: ECPay Green World FinTech Service Co., Ltd.
 * Author URI: https://www.ecpay.com.tw
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 * WC requires at least: 8
 * WC tested up to: 8.4.0
 */

// 相關檢查
defined('ABSPATH') or exit;

// 相關常數定義
define('WOOECPAY_VERSION', '1.1.2403150');
define('REQUIREMENT_WOOCOMMERCE_VERSION', '8.3.0');
define('WOOECPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOOECPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOECPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WOOECPAY_PLUGIN_INCLUDE_DIR', WOOECPAY_PLUGIN_DIR . 'includes');
define('WOOECPAY_PLUGIN_LOG_DIR', WOOECPAY_PLUGIN_DIR . 'logs');

//
require_once WOOECPAY_PLUGIN_DIR . '/vendor/autoload.php';

// 相關載入程序
require plugin_dir_path(__FILE__) . 'admin/settings/class-wooecpay-setting.php';
require plugin_dir_path(__FILE__) . 'admin/order/class-wooecpay-order.php';

// 載入物流共用 helper
require plugin_dir_path(__FILE__) . 'includes/services/helpers/logistic/ecpay-logistic-helper.php';

// 載入金流共用 helper
require plugin_dir_path(__FILE__) . 'includes/services/helpers/payment/ecpay-payment-helper.php';

// 載入發票共用 helper
require plugin_dir_path(__FILE__) . 'includes/services/helpers/invoice/ecpay-invoice-helper.php';

// 資料庫處理程序
// register_activation_hook: 手動啟用外掛時觸發
// upgrader_process_complete: 更新外掛時觸發
// plugins_loaded: 用於版本檢查，以防 upgrader_process_complete 抓到舊版本程式的問題
require_once WOOECPAY_PLUGIN_DIR . 'includes/services/database/ecpay-db-process.php';
register_activation_hook(__FILE__, array('Wooecpay_Db_Process', 'ecpay_db_process'));
add_action('upgrader_process_complete', array('Wooecpay_Db_Process', 'ecpay_db_process'));
add_action('woocommerce_loaded', array('Wooecpay_Db_Process', 'ecpay_db_process'));

// Woocommerce版本判斷
add_action('admin_notices',
    function () {
        if (!defined('WC_VERSION') || version_compare(WC_VERSION, REQUIREMENT_WOOCOMMERCE_VERSION, '<')) {
            $notice = sprintf(
                __('<strong>%1$s</strong> is inactive. It require WooCommerce version %2$s or newer.', 'ecpay-ecommerce-for-woocommerce'),
                __('ECPay Ecommerce for WooCommerce', 'ecpay-ecommerce-for-woocommerce'),
                REQUIREMENT_WOOCOMMERCE_VERSION
            );
            printf('<div class="error"><p>%s</p></div>', $notice);
        }
    }
);

// 高效能宣告
add_action('before_woocommerce_init',
    function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
);

// 載入 log 功能
require WOOECPAY_PLUGIN_DIR . 'includes/services/helpers/logger/ecpay-logger.php';
function ecpay_log($content, $code = '', $order_id = '') {
    $logger = new Helpers\Logger\Wooecpay_Logger;
    return $logger->log($content, $code, $order_id);
}
function ecpay_log_replace_symbol($type, $data) {
    $logger = new Helpers\Logger\Wooecpay_Logger;
    return $logger->replace_symbol($type, $data);
}

$plugin_main  = new Wooecpay_Setting();
$plugin_order = new Wooecpay_Order();

if ('yes' === get_option('wooecpay_enabled_payment', 'no')) {
    require plugin_dir_path(__FILE__) . 'includes/services/payment/class-wooecpay-gateway.php';
    $plugin_payment = new Wooecpay_Gateway();
}

if ('yes' === get_option('wooecpay_enabled_logistic', 'no')) {
    require plugin_dir_path(__FILE__) . 'includes/services/logistic/class-wooecpay-logistic.php';
    $plugin_logistic = new Wooecpay_Logistic();
}

if ('yes' === get_option('wooecpay_enabled_invoice', 'no')) {
    require plugin_dir_path(__FILE__) . 'includes/services/invoice/class-wooecpay-invoice.php';
    $plugin_invoice = new Wooecpay_invoice();
}
