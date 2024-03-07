<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;
use Helpers\Invoice\Wooecpay_Invoice_Helper;

class Wooecpay_invoice {

    protected $my_custom_features_switch;
    protected $invoiceHelper;

	public function __construct()
	{
        // 載入共用
        $this->invoiceHelper = new Wooecpay_Invoice_Helper;

        add_filter('woocommerce_checkout_fields', array($this, 'wooecpay_show_invoice_fields' ), 10 ,1);		// 前台統一編號 載具資訊填寫
        add_action('woocommerce_checkout_process', array($this,'wooecpay_check_invoice_fields' ));				// 欄位後端檢查
        add_action('woocommerce_checkout_create_order', array($this, 'wooecpay_save_invoice_fields'), 10, 2);


        // 功能開關設定
        $this->my_custom_features_switch = array(
            'billing_love_code_api_check' 	=> true,
            'billing_carruer_num_api_check' => true
        );
	}

	/**
     * 前台結帳頁發票欄位
     */
    public function wooecpay_show_invoice_fields($fields)
    {
        // 載入相關JS
        wp_register_script(
			'wooecpay_invoice',
			WOOECPAY_PLUGIN_URL . 'public/js/wooecpay-invoice.js',
			array(),
			'1.0.0',
			true
       	);

        $wooecpay_invoice_donate = get_option('wooecpay_invoice_donate') ;

        $translation_array = array(
            'wooecpay_invoice_donate' => $wooecpay_invoice_donate
       );
        wp_localize_script('wooecpay_invoice', 'wooecpay_invoice_script_var', $translation_array);


       	wp_enqueue_script('wooecpay_invoice');

        // 載具資訊
        $fields['billing']['wooecpay_invoice_carruer_type'] = [
            'type'          => 'select',
            'label'         => '載具類別',
            'required'      => false,
            'priority'      => 200,
            'options'       => $this->invoiceHelper->invoiceCarruerType
        ];

        $fields['billing']['wooecpay_invoice_type'] = [
            'type'          => 'select',
            'label'         => '發票開立',
            'required'      => false,
            'priority'      => 210,
            'options'       => $this->invoiceHelper->invoiceType
        ];

        $fields['billing']['wooecpay_invoice_customer_identifier'] = [
            'type'          => 'text',
            'label'         => '統一編號',
            'required'      => false,
            'priority'      => 220,
        ];

        $fields['billing']['wooecpay_invoice_customer_company'] = [
            'type'          => 'text',
            'label'         => '公司行號',
            'required'      => false,
            'priority'      => 220,
        ];

        $fields['billing']['wooecpay_invoice_love_code'] = [
            'type'          => 'text',
            'label'         => '捐贈碼',
            'desc_tip'      => true,
            'required'      => false,
            'priority'      => 230,
        ];

        $fields['billing']['wooecpay_invoice_carruer_num'] = [
            'type'          => 'text',
            'label'         => '載具編號',
            'required'      => false,
            'priority'      => 240,
        ];

        return $fields;
    }

    /**
     * 結帳過程欄位檢查
     */
    public function wooecpay_check_invoice_fields()
    {
        $this->invoiceHelper->check_invoice_fields($_POST, $this->my_custom_features_switch);
    }

	public function wooecpay_save_invoice_fields($order, $data)
    {
        $order->update_meta_data('_wooecpay_invoice_type', isset($data['wooecpay_invoice_type']) ? $data['wooecpay_invoice_type'] : '');
        $order->update_meta_data('_wooecpay_invoice_carruer_type', isset($data['wooecpay_invoice_carruer_type']) ? $data['wooecpay_invoice_carruer_type'] : '');
        $order->update_meta_data('_wooecpay_invoice_carruer_num', isset($data['wooecpay_invoice_carruer_num']) ? $data['wooecpay_invoice_carruer_num'] : '');
        $order->update_meta_data('_wooecpay_invoice_love_code', isset($data['wooecpay_invoice_love_code']) ? $data['wooecpay_invoice_love_code'] : '');
        $order->update_meta_data('_wooecpay_invoice_customer_identifier', isset($data['wooecpay_invoice_customer_identifier']) ? $data['wooecpay_invoice_customer_identifier'] : '');
        $order->update_meta_data('_wooecpay_invoice_customer_company', isset($data['wooecpay_invoice_customer_company']) ? $data['wooecpay_invoice_customer_company'] : '');
    }
}