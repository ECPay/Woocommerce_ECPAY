<?php

class Wooecpay_Setting_Main extends WC_Settings_Page {

	public function __construct() {
		$this->id = 'wooecpay_setting';

		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	public function get_sections() {
		$sections['ecpay_main']    	= __( 'General settings', 'wooecpay' );

		$sections['ecpay_payment'] 	= __( 'Gateway settings', 'wooecpay' );
		$sections['ecpay_logistic']	= __( 'Shipping settings', 'wooecpay' );
		$sections['ecpay_invoice']  = __( 'E-Invoice setting', 'wooecpay' );

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
		}
		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		global $current_section;
		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}
}

return new Wooecpay_Setting_Main();