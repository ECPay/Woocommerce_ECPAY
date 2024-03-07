<?php

class Wooecpay_Setting {

    public function __construct() {

        add_action('woocommerce_loaded', array($this, 'load_languages'));

        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 70);
        add_filter('woocommerce_get_settings_pages', array($this, 'set_more_sections'));

    }

    public function add_settings_tab($settings_tabs) {
        $settings_tabs['wooecpay_setting'] = __('Ecpay', 'ecpay-ecommerce-for-woocommerce');
        return $settings_tabs;
    }

    public function set_more_sections($settings) {

        $settings[] = include WOOECPAY_PLUGIN_DIR . 'admin/settings/class-wooecpay-setting-main.php';
        return $settings;
    }

    public function load_languages() {

        load_plugin_textdomain('ecpay-ecommerce-for-woocommerce', false, dirname(dirname(dirname(plugin_basename(__FILE__)))) . '/languages/');
    }
}