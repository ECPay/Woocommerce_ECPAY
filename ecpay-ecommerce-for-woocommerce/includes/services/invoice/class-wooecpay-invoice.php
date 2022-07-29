<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;

class Wooecpay_invoice {

	public function __construct()
	{
        
        add_filter('woocommerce_checkout_fields', array($this, 'wooecpay_show_invoice_fields' ), 10 ,1);		// 前台統一編號 載具資訊填寫
        add_action('woocommerce_checkout_process', array($this,'wooecpay_check_invoice_fields' ));				// 欄位後端檢查
        add_action('woocommerce_checkout_create_order', array($this, 'wooecpay_save_invoice_fields'), 10, 2); 


        // 功能開關設定
        $this->my_custom_features_switch = array(
            'billing_love_code_api_check' 	=> false,
            'billing_carruer_num_api_check' => false
        );
	}

	/**
     * 統一編號 捐贈捐贈碼 填寫
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
        wp_localize_script( 'wooecpay_invoice', 'wooecpay_invoice_script_var', $translation_array );


       	wp_enqueue_script( 'wooecpay_invoice');

        // 載具資訊
        $fields['billing']['wooecpay_invoice_carruer_type'] = [
            'type'      => 'select',
            'label'         => '載具類別',
            'required'      => false,
            'priority'      => 200,
            'options'   => [
                '0' => '索取紙本',
                '1' => '雲端發票(中獎寄送紙本)',
                '2' => '自然人憑證',
                '3' => '手機條碼'
            ]
        ];

        $fields['billing']['wooecpay_invoice_type'] = [
            'type'          => 'select',
            'label'         => '發票開立',
            'required'      => false,
            'priority'      => 210,
            'options'   => [
                'p' => '個人',
                'c' => '公司',
                'd' => '捐贈'
            ]
        ];

        $fields['billing']['wooecpay_invoice_customer_identifier'] = [
            'type'          => 'text',
            'label'         => '統一編號',
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
        if( 
        	isset($_POST['wooecpay_invoice_type']) &&
         	sanitize_text_field($_POST['wooecpay_invoice_type']) == 'c' &&
         	sanitize_text_field($_POST['wooecpay_invoice_customer_identifier']) == '' )
        {
            wc_add_notice( __( 'Please input Unified Business NO', 'ecpay-ecommerce-for-woocommerce' ), 'error');
        }

        if(
         	isset($_POST['billing_invoice_type']) &&
          	sanitize_text_field($_POST['billing_invoice_type']) == 'c' &&
           	sanitize_text_field($_POST['billing_company']) == '' ) 
        {
            wc_add_notice( __( 'Please input the company name', 'ecpay-ecommerce-for-woocommerce' ), 'error');
        }

        if( 
        	isset($_POST['wooecpay_invoice_type']) && 
        	sanitize_text_field($_POST['wooecpay_invoice_type']) == 'd' && 
        	sanitize_text_field($_POST['wooecpay_invoice_love_code']) == '' )
        {
            wc_add_notice( __( 'Please input Donate number', 'ecpay-ecommerce-for-woocommerce' ), 'error');
        }

        if( 
        	isset($_POST['wooecpay_invoice_carruer_type']) && 
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_type']) == '2' && 
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_num']) == '' )
        {
            wc_add_notice( __( 'Please input Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), 'error' );
        }

        if( 
        	isset($_POST['wooecpay_invoice_carruer_type']) &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_type']) == '3' &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_num']) == '' )
        {
            wc_add_notice( __( 'Please input phone barcode', 'ecpay-ecommerce-for-woocommerce'), 'error' );
        }

        // 統一編號格式判斷
        if( 
        	isset($_POST['wooecpay_invoice_type']) &&
        	sanitize_text_field($_POST['wooecpay_invoice_type']) == 'c' &&
        	sanitize_text_field($_POST['wooecpay_invoice_customer_identifier']) != '' )
        {

            if( !preg_match('/^[0-9]{8}$/', sanitize_text_field($_POST['wooecpay_invoice_customer_identifier'])) ) {
                wc_add_notice( __( 'Invalid tax ID number', 'ecpay-ecommerce-for-woocommerce'), 'error' );
            }

            if(empty(sanitize_text_field($_POST['billing_company']))){
            	wc_add_notice( __( 'Please input the company name', 'ecpay-ecommerce-for-woocommerce'), 'error' );
            }
        }

        // 捐贈碼格式判斷
        if( 
        	isset($_POST['wooecpay_invoice_type']) &&
        	sanitize_text_field($_POST['wooecpay_invoice_type']) == 'd' &&
        	sanitize_text_field($_POST['wooecpay_invoice_love_code']) != '' ) 
        {

            if( !preg_match('/^([xX]{1}[0-9]{2,6}|[0-9]{3,7})$/', sanitize_text_field($_POST['wooecpay_invoice_love_code'])) ) {
                wc_add_notice( __( 'Invalid Donate number', 'ecpay-ecommerce-for-woocommerce'), 'error' );

            } else {

                // 呼叫SDK 捐贈碼驗證
                if($this->my_custom_features_switch['billing_love_code_api_check']) {

	            	$api_payment_info = $this->get_ecpay_invoice_api_info('check_Love_code');

	            	try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);

					    $postService = $factory->create('PostWithAesJsonResponseService');

					    $data = [
					        'MerchantID' 	=> $api_payment_info['merchant_id'],
					        'LoveCode' 		=> sanitize_text_field($_POST['wooecpay_invoice_love_code']),
					    ];
					    $input = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'RqHeader' => [
					            'Timestamp' => time(),
					            'Revision' => '3.0.0',
					        ],
					        'Data' => $data,
					    ];
					    
					    $response = $postService->post($input, $api_payment_info['action']);
					    // var_dump($response);

					    if(!isset($response['RtnCode']) || $response['RtnCode'] != 1 || $response['IsExist'] == 'N') {
	                        wc_add_notice( __( 'Please Check Donate number', 'ecpay-ecommerce-for-woocommerce'), 'error' );
	                    }

					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}
				}
            }
        }

        // 自然人憑證格式判斷
        if( 
        	isset($_POST['wooecpay_invoice_carruer_type']) &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_type']) == '2' &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_num']) != '' )
        {

            if( !preg_match('/^[a-zA-Z]{2}\d{14}$/', sanitize_text_field($_POST['wooecpay_invoice_carruer_num'])) ) {
                wc_add_notice( __( 'Invalid Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), 'error' );
            }
        }

        // 手機載具格式判斷
        if( 
        	isset($_POST['wooecpay_invoice_carruer_type']) &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_type']) == '3' &&
        	sanitize_text_field($_POST['wooecpay_invoice_carruer_num']) != '' )
        {

            if( !preg_match('/^\/{1}[0-9a-zA-Z+-.]{7}$/', sanitize_text_field($_POST['wooecpay_invoice_carruer_num'])) ) {
                wc_add_notice( __( 'Invalid phone barcode', 'ecpay-ecommerce-for-woocommerce'), 'error' );

            } else {

                // 呼叫SDK 手機條碼驗證
            	if($this->my_custom_features_switch['billing_carruer_num_api_check']) {

	                $api_payment_info = $this->get_ecpay_invoice_api_info('check_barcode');

	            	try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);

					    $postService = $factory->create('PostWithAesJsonResponseService');

					    $data = [
					        'MerchantID' 	=> $api_payment_info['merchant_id'],
					        'BarCode' 		=> sanitize_text_field($_POST['wooecpay_invoice_carruer_num']),  
					    ];

					    $input = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'RqHeader' => [
					            'Timestamp' => time(),
					            'Revision' => '3.0.0',
					        ],
					        'Data' => $data,
					    ];
					    
					    $response = $postService->post($input, $api_payment_info['action']);

					    // var_dump($response);
					    if(
					    	!isset($response['RtnCode']) ||
					    	$response['RtnCode'] != 1 ||
					    	$response['IsExist'] == 'N'
					    ) {
	                    	wc_add_notice( __( 'Please Check phone barcode', 'ecpay-ecommerce-for-woocommerce'), 'error' );
	                    }

					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}
				}
            }
        }
    }

	public function wooecpay_save_invoice_fields($order, $data)
    {
        $order->update_meta_data('_wooecpay_invoice_type', isset($data['wooecpay_invoice_type']) ? $data['wooecpay_invoice_type'] : '');
        $order->update_meta_data('_wooecpay_invoice_carruer_type', isset($data['wooecpay_invoice_carruer_type']) ? $data['wooecpay_invoice_carruer_type'] : '');
        $order->update_meta_data('_wooecpay_invoice_carruer_num', isset($data['wooecpay_invoice_carruer_num']) ? $data['wooecpay_invoice_carruer_num'] : '');
        $order->update_meta_data('_wooecpay_invoice_love_code', isset($data['wooecpay_invoice_love_code']) ? $data['wooecpay_invoice_love_code'] : '');
        $order->update_meta_data('_wooecpay_invoice_customer_identifier', isset($data['wooecpay_invoice_customer_identifier']) ? $data['wooecpay_invoice_customer_identifier'] : '');
    }


    // invoice 
    // ---------------------------------------------------


    protected function get_ecpay_invoice_api_info($action = '')
    {
        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_invoice_stage', 'yes')) {

           	$api_info = [
                'merchant_id'   => '2000132',
                'hashKey'       => 'ejCk326UnaZWKisg ',
                'hashIv'        => 'q9jcZX8Ib9LM8wYk',
            ] ;

        } else {
            
            $merchant_id = get_option('wooecpay_invoice_mid');
            $hash_key    = get_option('wooecpay_invoice_hashkey');
            $hash_iv     = get_option('wooecpay_invoice_hashiv');

            $api_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ] ;
        }

        if ('yes' === get_option('wooecpay_enabled_invoice_stage', 'yes')) {

            switch ($action) {

                case 'check_Love_code':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode' ;
                break;

                case 'check_barcode':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode' ;
                break;

                default:
                break;
            }

        } else {

            switch ($action) {

                case 'check_Love_code':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode' ;
                break;
                
                case 'check_barcode':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode' ;
                break;
               
                default:
                break;
            }
        }

        return $api_info;
    }
}