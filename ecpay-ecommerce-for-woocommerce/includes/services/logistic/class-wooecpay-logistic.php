<?php

use Helpers\Logistic\Wooecpay_Logistic_Helper;

class Wooecpay_Logistic
{
	protected $logisticHelper;

	public function __construct()
	{
		$this->load_api();

		// 載入物流共用
        $this->logisticHelper = new Wooecpay_Logistic_Helper;

		add_filter('woocommerce_shipping_methods', array($this, 'add_method'));
		add_action('woocommerce_shipping_init', array($this, 'load_logistic_logistic'));

		add_filter('woocommerce_available_payment_gateways', array($this, 'gateway_disable_for_shipping_rate'), 1);

		if ('yes' === get_option('wooecpay_keep_logistic_phone', 'yes')) {
			add_filter('woocommerce_checkout_fields', array($this, 'wooecpay_show_logistic_fields'), 11 ,1); // 收件人手機
			add_action('woocommerce_checkout_create_order', array($this, 'wooecpay_save_logistic_fields'), 11, 2);
		}

		add_action('woocommerce_checkout_update_order_meta', array($this, 'save_weight_order'));

		if (in_array('Wooecpay_Logistic_Home_Tcat', get_option('wooecpay_enabled_logistic_outside', []))) {
			// 前台結帳頁欄位檢查 Hook
			add_action('woocommerce_after_checkout_validation', array($this, 'wooecpay_check_logistic_home_tcat_fields'), 10, 2);
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

		if (get_option('wooecpay_logistic_cvs_type') == 'C2C') {
			include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-okmart.php';
		}

		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-home-tcat.php';
		include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-home-post.php';

		if (in_array('Wooecpay_Logistic_CVS_711', get_option('wooecpay_enabled_logistic_outside', []))) {
			include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-cvs-711-outside.php';
		}
		if (in_array('Wooecpay_Logistic_Home_Tcat', get_option('wooecpay_enabled_logistic_outside', []))) {
			include WOOECPAY_PLUGIN_INCLUDE_DIR . '/services/logistic/ecpay-logistic-home-tcat-outside.php';
		}
	}

	public function add_method($methods)
    {
        $methods['Wooecpay_Logistic_CVS_711']       = 'Wooecpay_Logistic_CVS_711';
        $methods['Wooecpay_Logistic_CVS_Hilife']    = 'Wooecpay_Logistic_CVS_Hilife';
        $methods['Wooecpay_Logistic_CVS_Family']    = 'Wooecpay_Logistic_CVS_Family';

        if (get_option('wooecpay_logistic_cvs_type') == 'C2C') {
        	$methods['Wooecpay_Logistic_CVS_Okmart']    = 'Wooecpay_Logistic_CVS_Okmart';
    	}

        $methods['Wooecpay_Logistic_Home_Tcat']     = 'Wooecpay_Logistic_Home_Tcat';
        $methods['Wooecpay_Logistic_Home_Post']     = 'Wooecpay_Logistic_Home_Post';

		if (in_array('Wooecpay_Logistic_CVS_711', get_option('wooecpay_enabled_logistic_outside', []))) {
			$methods['Wooecpay_Logistic_CVS_711_Outside'] = 'Wooecpay_Logistic_CVS_711_Outside';
		}
		if (in_array('Wooecpay_Logistic_Home_Tcat', get_option('wooecpay_enabled_logistic_outside', []))) {
			$methods['Wooecpay_Logistic_Home_Tcat_Outside'] = 'Wooecpay_Logistic_Home_Tcat_Outside';
		}

        return $methods;
    }

	/**
	 * wc_get_chosen_shipping_method_ids
	 */
	function get_chosen_shipping_method_ids()
	{
		$method_ids     = array();
		$chosen_methods = is_null(WC()->session) ? [] : WC()->session->get('chosen_shipping_methods', array());
		foreach ($chosen_methods as $chosen_method) {
			$chosen_method = explode(':', $chosen_method);
			$method_ids[]  = current($chosen_method);
		}
		return $method_ids;
	}

	/**
	 * 限制綠界物流僅能使用綠界金流
	 */
    function gateway_disable_for_shipping_rate($available_gateways)
	{
        if (!is_admin()) {

        	$chosen_shipping_tmp = $this->get_chosen_shipping_method_ids();
        	$chosen_shipping = (empty($chosen_shipping_tmp)) ? '' : $chosen_shipping_tmp[0];

			if (!empty($chosen_shipping)) {

				// 限制綠界物流僅能使用綠界金流
				if (
					strpos($chosen_shipping, 'Wooecpay_Logistic_CVS') !== false ||
					strpos($chosen_shipping, 'Wooecpay_Logistic_Home') !== false
				) {

					foreach ($available_gateways as $key => $value) {

						if (
							$key == 'Wooecpay_Gateway_Credit' ||
							$key == 'Wooecpay_Gateway_Credit_Installment' ||
							$key == 'Wooecpay_Gateway_Webatm' ||
							$key == 'Wooecpay_Gateway_Atm' ||
							$key == 'Wooecpay_Gateway_Cvs' ||
							$key == 'Wooecpay_Gateway_Barcode' ||
							$key == 'Wooecpay_Gateway_Applepay' ||
							$key == 'cod'
						) {

						} else {
							unset($available_gateways[$key]);
						}
					}
				}

				// 限制貨到付款僅適用超商物流
				$chosen_methods = WC()->session->get('chosen_shipping_methods');
				$chosen_shipping = $chosen_methods[0];

				if (isset($available_gateways['cod']) && (
					0 === strpos($chosen_shipping, 'Wooecpay_Logistic_Home_Tcat') ||
					0 === strpos($chosen_shipping, 'Wooecpay_Logistic_Home_Tcat_Outside') ||
					0 === strpos($chosen_shipping, 'Wooecpay_Logistic_Home_Post')
				)) {
					unset($available_gateways['cod']);
				}
			}
       	}

       	return $available_gateways;
    }

	/**
	 * 前台結帳頁欄位檢查 - 黑貓
	 */
	public function wooecpay_check_logistic_home_tcat_fields($data, $errors)
	{
		// 取得當前選擇的運送方式
		$chosen_shipping = $this->get_chosen_shipping_method_ids();
		$chosen_shipping = (empty($chosen_shipping)) ? '' : $chosen_shipping[0];

		// 當前選擇的運送方式是否為綠界宅配黑貓
		if (in_array($chosen_shipping, ['Wooecpay_Logistic_Home_Tcat', 'Wooecpay_Logistic_Home_Tcat_Outside'])) {
			// 比對運送方式與地址
			$result = $this->logisticHelper->is_available_state_home_tcat($chosen_shipping, $data['shipping_state']);
			if (!$result) {
				// 比對失敗，顯示錯誤訊息
				$errors->add('validation', __('The selected shipping method does not match the shipping address. Please choose again.', 'ecpay-ecommerce-for-woocommerce'));
			}
		}
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

    	if (
    		isset($data['billing_phone']) &&
    		isset($data['wooecpay_shipping_phone']) &&
    		(empty($data['wooecpay_shipping_phone']))
    	) {
    		$order->update_meta_data('wooecpay_shipping_phone', $data['billing_phone']);
    	}
    }

    /**
     * 額外增加訂單重量欄位
     */
    public function save_weight_order($order_id)
	{
        $weight = WC()->cart->get_cart_contents_weight();
        update_post_meta($order_id, '_cart_weight', $weight);
    }
}