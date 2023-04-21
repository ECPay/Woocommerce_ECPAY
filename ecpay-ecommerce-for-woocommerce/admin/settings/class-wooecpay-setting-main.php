<?php

class Wooecpay_Setting_Main extends WC_Settings_Page {

	public function __construct() {
		$this->id = 'wooecpay_setting';

		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		add_action('admin_enqueue_scripts' , array( $this, 'wooecpay_register_scripts' ));
	}

	public function get_sections() {
		$sections['ecpay_main']    	= __( 'General settings', 'ecpay-ecommerce-for-woocommerce' );

		$sections['ecpay_payment'] 	= __( 'Gateway settings', 'ecpay-ecommerce-for-woocommerce' );
		$sections['ecpay_logistic']	= __( 'Shipping settings', 'ecpay-ecommerce-for-woocommerce' );
		$sections['ecpay_invoice']  = __( 'E-Invoice setting', 'ecpay-ecommerce-for-woocommerce' );

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	public function get_settings( $section = null ) {

		switch ( $section ) {

			case 'ecpay_main':
				$settings = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/main/settings.php';
				return $settings ;
			break;
			case 'ecpay_payment':
				$settings = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings.php';
				return $settings ;
			break;
			case 'ecpay_logistic':
				$settings = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/logistic/settings.php';
				return $settings ;
			break;
			case 'ecpay_invoice':
				$settings = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/invoice/settings.php';
				return $settings ;
			break;

			default:
			break;
		}
	}

	public function output() {
		global $current_section ;

		if ( $current_section == '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wooecpay_setting&section=ecpay_main' ) );
			exit;
		}
		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		global $current_section;
		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}

	public function wooecpay_register_scripts() {
		
		wp_register_script(
			'wooecpay_setting',
			WOOECPAY_PLUGIN_URL . 'public/js/wooecpay-setting.js',
			array(),
			'1.0.0',
			true
		);

		// 載入js
		wp_enqueue_script('wooecpay_setting');
		$translation_array = array('message' => __('When you disable the ECPay gateway method, the ECPay shipping method will be closed at the same time. Are you sure you want to disable the ECPay gateway method?', 'ecpay-ecommerce-for-woocommerce'));
		wp_localize_script('wooecpay_setting', 'confirm_message', $translation_array);
	}
}

return new Wooecpay_Setting_Main();
