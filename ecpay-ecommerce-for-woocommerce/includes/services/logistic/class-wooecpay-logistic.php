<?php

class Wooecpay_Logistic {

	public function __construct()
	{

		$this->load_api();

		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_method' ) );
		add_action( 'woocommerce_shipping_init', array( $this, 'load_logistic_logistic' ) );

		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'gateway_disable_for_shipping_rate') );

		if ('yes' === get_option('wooecpay_keep_logistic_phone', 'yes')) {
			add_filter('woocommerce_checkout_fields', array($this, 'wooecpay_show_logistic_fields' ), 11 ,1);		// 收件人手機
			add_action('woocommerce_checkout_create_order', array($this, 'wooecpay_save_logistic_fields'), 11, 2); 
		}
	}

	/**
     * 載入回傳相關設定
     * @var array
     */
	private function load_api()
	{
		require_once WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-response.php';
		require_once WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/invoice/ecpay-invoice-response.php';
	}

	/**
     * 載入物流方式
     * @var array
     */
	public function load_logistic_logistic()
	{

		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-base.php';
		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-711.php';
		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-hilife.php';
		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-family.php';

		if ( get_option('wooecpay_logistic_cvs_type') == 'C2C') {
			include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-okmart.php';
		}
		
		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-home-tcat.php';
		// include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-home-ecan.php';
	}

	public function add_method($methods)
    {   
        $methods['Wooecpay_Logistic_CVS_711']       = 'Wooecpay_Logistic_CVS_711';
        $methods['Wooecpay_Logistic_CVS_Hilife']    = 'Wooecpay_Logistic_CVS_Hilife';
        $methods['Wooecpay_Logistic_CVS_Family']    = 'Wooecpay_Logistic_CVS_Family';

        if ( get_option('wooecpay_logistic_cvs_type') == 'C2C') {
        	$methods['Wooecpay_Logistic_CVS_Okmart']    = 'Wooecpay_Logistic_CVS_Okmart';
    	}

        $methods['Wooecpay_Logistic_Home_Tcat']     = 'Wooecpay_Logistic_Home_Tcat';
        // $methods['Wooecpay_Logistic_Home_Ecan']     = 'Wooecpay_Logistic_Home_Ecan';

        return $methods;
    }

    // 限制綠界物流僅能使用綠界金流
    function gateway_disable_for_shipping_rate( $available_gateways ) {
       
        if ( ! is_admin() ) {

			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping = $chosen_methods[0];

			if(!empty($chosen_shipping)){

				// 限制綠界物流僅能使用綠界金流
				if(
					strpos( $chosen_shipping, 'Wooecpay_Logistic_CVS' ) !== false ||
					strpos( $chosen_shipping, 'Wooecpay_Logistic_Home' ) !== false
				){

					foreach($available_gateways as $key => $value){

						if(
							$key == 'Wooecpay_Gateway_Credit' ||
							$key == 'Wooecpay_Gateway_Credit_Installment' ||
							$key == 'Wooecpay_Gateway_Webatm' ||
							$key == 'Wooecpay_Gateway_Atm' ||
							$key == 'Wooecpay_Gateway_Cvs' ||
							$key == 'Wooecpay_Gateway_Barcode' ||
							$key == 'cod'
						){

						} else {
							unset( $available_gateways[$key] );
						}
					}
				}	
			}	
       	}

       	return $available_gateways;
    }

	/**
     * 額外增加物流欄位
     */
    public function wooecpay_show_logistic_fields($fields)
    {
        $fields['shipping']['wooecpay_shipping_phone'] = [
            'type'          => 'text',
            'label'         => '收寄人電話',
            'required'      => false,
            'priority'      => 220,
        ];

        return $fields;
    }

    public function wooecpay_save_logistic_fields($order, $data)
    {
        
    	if(
    		isset($data['billing_phone']) &&
    		isset($data['wooecpay_shipping_phone']) &&
    		(empty($data['wooecpay_shipping_phone']))
    	){
    		$order->update_meta_data('wooecpay_shipping_phone', $data['billing_phone']);
    	}
    }
}