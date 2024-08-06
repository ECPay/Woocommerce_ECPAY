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

        // 前台統一編號 載具資訊填寫
        add_filter('woocommerce_checkout_fields', array($this, 'wooecpay_show_invoice_fields' ), 10 ,1);

        // 欄位後端檢查(傳統結帳)		
        add_action('woocommerce_checkout_process', array($this,'wooecpay_check_invoice_fields' ));				
        add_action('woocommerce_checkout_create_order', array($this, 'wooecpay_save_invoice_fields'), 10, 2);

        // 欄位後端檢查並儲存(Woocommcer Block結帳)		
        add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'wooecpay_block_check_invoice_fields'), 10, 2);
        // 傳遞額外資訊到前端(Woocommcer Block結帳)		
        add_action('wp_enqueue_scripts', array($this, 'wooecpay_invoice_extra_data'));

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

        // 判斷是否顯示發票索取紙本選項
        $wooecpay_invoice_carruer_papper = get_option('wooecpay_invoice_carruer_papper', 'enable') ;
        $invoiceCarruerType = $this->invoiceHelper->invoiceCarruerType;
        if ($wooecpay_invoice_carruer_papper == 'disable') {
            $invoiceCarruerType = array_diff($invoiceCarruerType, array('索取紙本'));
        }
        
        // 載具資訊
        $fields['billing']['invoice_carruer_type'] = [
            'type'          => 'select',
            'label'         => '載具類別',
            'required'      => false,
            'priority'      => 200,
            'options'       => $invoiceCarruerType
        ];

        $fields['billing']['invoice_type'] = [
            'type'          => 'select',
            'label'         => '發票開立',
            'required'      => false,
            'priority'      => 210,
            'options'       => $this->invoiceHelper->invoiceType
        ];

        $fields['billing']['invoice_customer_identifier'] = [
            'type'          => 'text',
            'label'         => '統一編號',
            'required'      => false,
            'priority'      => 220,
        ];

        $fields['billing']['invoice_customer_company'] = [
            'type'          => 'text',
            'label'         => '公司行號',
            'required'      => false,
            'priority'      => 220,
        ];

        $fields['billing']['invoice_love_code'] = [
            'type'          => 'text',
            'label'         => '捐贈碼',
            'desc_tip'      => true,
            'required'      => false,
            'priority'      => 230,
        ];

        $fields['billing']['invoice_carruer_num'] = [
            'type'          => 'text',
            'label'         => '載具編號',
            'required'      => false,
            'priority'      => 240,
        ];

        return $fields;
    }

    /**
     * 結帳過程欄位檢查(傳統結帳)
     */
    public function wooecpay_check_invoice_fields()
    {
        $this->invoiceHelper->check_invoice_fields($_POST, $this->my_custom_features_switch);
    }

    /**
     * 發票欄位儲存(傳統結帳)
     */
    public function wooecpay_save_invoice_fields($order, $data)
    {
        $this->update_invoice_meta_data($order, $data);
    }

    /**
     * 結帳過程欄位檢查並儲存(Woocommerce Block結帳)
     */
    public function wooecpay_block_check_invoice_fields($order, $request) {
        $data = isset( $request['extensions']['ecpay-invoice-block'] ) ? $request['extensions']['ecpay-invoice-block'] : array();

        $result = $this->invoiceHelper->check_block_invoice_fields($data, $this->my_custom_features_switch);

        if ($result['code'] !== '1') {
            throw new Exception($result['error_msg']);
        }
        else {
            $this->update_invoice_meta_data($order, $data);
        }
    }

    /**
     * 更新訂單發票資訊
     */
    private function update_invoice_meta_data($order, $data) {
        $prefix = '_wooecpay_';
        $invoice_keys = [
            'invoice_type',
            'invoice_carruer_type',
            'invoice_carruer_num',
            'invoice_love_code',
            'invoice_customer_identifier',
            'invoice_customer_company'
        ];

        foreach ($invoice_keys as $key) {
            if (array_key_exists($key, $data)) {
                $order->update_meta_data($prefix . $key, $data[$key]);
            }
        }
    }

    /**
     * 傳遞預設捐贈碼到前端
     */
    public function wooecpay_invoice_extra_data() {
        wp_register_script('ecpay-invoice-block-param', '');
        wp_enqueue_script('ecpay-invoice-block-param');

        $donate_code = get_option('wooecpay_invoice_donate', '');
        $invoice_papper = get_option('wooecpay_invoice_carruer_papper', 'enable') ;
        wp_localize_script('ecpay-invoice-block-param', 'InvoiceData', array(
            'DonateCode' => $donate_code,
            'InvoicePapper' => $invoice_papper
        ));
    }
}