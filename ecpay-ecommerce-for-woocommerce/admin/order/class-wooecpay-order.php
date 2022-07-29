<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Exceptions\RtnException;

class Wooecpay_Order {

	public function __construct() {

        if (is_admin()) {

        	// wp_enqueue_style('wooecpay_barcode_css', WOOECPAY_PLUGIN_URL . 'public/css/style.css');
            add_action('admin_enqueue_scripts' , array( $this, 'wooecpay_register_scripts' ));

			if ('yes' === get_option('wooecpay_ecpay_enabled_payment', 'yes')) {
				add_action( 'woocommerce_admin_billing_fields', array($this,'custom_order_meta'), 10, 1 ); 
				add_action( 'woocommerce_admin_order_data_after_billing_address', array($this,'add_address_meta'), 10, 1 );

				add_action( 'woocommerce_admin_order_data_after_order_details', array($this,'add_payment_info'), 10, 1 );
			}

			if ('yes' === get_option('wooecpay_ecpay_enabled_logistic', 'yes')) {
				add_action( 'woocommerce_admin_order_data_after_shipping_address', array($this,'logistic_button_display'));	
				add_action( 'wp_ajax_send_logistic_order_action', array( $this, 'ajax_send_logistic_order_action' ) );
			}

			if ('yes' === get_option('wooecpay_ecpay_enabled_invoice', 'yes')) {
				add_action( 'woocommerce_admin_order_data_after_billing_address', array($this,'add_invoice_meta'), 11, 1 );

				// 手動開立
				add_action( 'wp_ajax_send_invoice_create', array( $this, 'ajax_send_invoice_create' ) );
				
				// 手動作廢
				add_action( 'wp_ajax_send_invoice_invalid', array( $this, 'ajax_send_invoice_invalid' ) );

				// 自動作廢
				if ('auto_cancel' === get_option('wooecpay_enabled_cancel_invoice_auto', 'auto_cancel')) {

					add_action('woocommerce_order_status_cancelled', array( $this, 'invoice_invalid' ));
	                add_action('woocommerce_order_status_refunded', array( $this, 'invoice_invalid' ));
				}
			}
        }

        if ('yes' === get_option('wooecpay_ecpay_enabled_invoice', 'yes')) {
			
			// 自動開立
			if ('auto_paid' === get_option('wooecpay_enabled_invoice_auto', 'auto_paid')) {
				add_action('woocommerce_order_status_processing', array( $this, 'invoice_create' ));
			}
		}
	}

	/**
	 * 訂單頁面新增完整地址
	 */
	public function custom_order_meta($fields) {
		
		$fields['full-address'] = array(
			'label'         => __( 'Full address', 'ecpay-ecommerce-for-woocommerce' ),
			'show'          => true,
			'wrapper_class' => 'form-field-wide full-address',
		);

		return $fields;
	}

	/**
	 * 訂單頁面姓名欄位格式調整
	 */
	public function add_address_meta($order) {

		echo '<style>.order_data_column:nth-child(2) .address p:first-child {display: none;}</style>';
		echo wp_kses_post('<p><strong>帳單姓名:<br/></strong>' . get_post_meta( $order->get_id(), '_billing_last_name', true ) . ' ' . get_post_meta( $order->get_id(), '_billing_first_name', true ) . '</p>');
	}

	/**
	 * 訂單金流資訊回傳
	 */
	public function add_payment_info($order) {

		$payment_method = get_post_meta( $order->get_id(), '_payment_method', true ) ;

		echo '<p>&nbsp;</p>';
		echo '<h3>'.__('Gateway info', 'ecpay-ecommerce-for-woocommerce').'</h3>';

		echo wp_kses_post('<p><strong>'.__('Payment Type', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_payment_method_title', true ) . '</p>') ;

		switch ( $payment_method) {

			case 'Wooecpay_Gateway_Credit':
				
				echo wp_kses_post('<p><strong>信用卡前六碼:&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_card6no', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>信用卡後四碼:&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_card4no', true ) . '</p>') ;

			break;

			case 'Wooecpay_Gateway_Credit_Installment':
				
				echo wp_kses_post('<p><strong>期數:&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_payment_number_of_periods', true ) . '數</p>') ;

			break;

			case 'Wooecpay_Gateway_Atm':
				
				echo wp_kses_post('<p><strong>'.__('Bank code', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_atm_BankCode', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('ATM No', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_atm_vAccount', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('Payment deadline', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_atm_ExpireDate', true ) . '</p>') ;

			break;

			case 'Wooecpay_Gateway_Cvs':
				
				echo wp_kses_post('<p><strong>'.__('CVS No', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_cvs_PaymentNo', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('Payment deadline', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_cvs_ExpireDate', true ) . '</p>') ;

			break;

			case 'Wooecpay_Gateway_Barcode':
				
				echo wp_kses_post('<p><strong>'.__('barcode one', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_barcode_Barcode1', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('barcode two', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_barcode_Barcode2', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('barcode three', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_barcode_Barcode3', true ) . '</p>') ;
				echo wp_kses_post('<p><strong>'.__('Payment deadline', 'ecpay-ecommerce-for-woocommerce').':&nbsp;</strong>'. get_post_meta( $order->get_id(), '_ecpay_barcode_ExpireDate', true ) . '</p>') ;

			break;
			
			default:
			break;
		}
	}

	/**
	 * 訂單發票資訊顯示
	 */
	public function add_invoice_meta($order) {

		if ($order) {

			$wooecpay_invoice_carruer_type 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_type', true ) ;
			$wooecpay_invoice_type 					= get_post_meta( $order->get_id(), '_wooecpay_invoice_type', true ) ;
			$billing_company 						= get_post_meta( $order->get_id(), '_billing_company', true ) ;
			$wooecpay_invoice_customer_identifier 	= get_post_meta( $order->get_id(), '_wooecpay_invoice_customer_identifier', true ) ;
			$wooecpay_invoice_love_code 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_love_code', true ) ;
			$wooecpay_invoice_carruer_num 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_num', true ) ;
			
			$wooecpay_invoice_no 					= get_post_meta( $order->get_id(), '_wooecpay_invoice_no', true ) ;
			$wooecpay_invoice_date 					= get_post_meta( $order->get_id(), '_wooecpay_invoice_date', true ) ;
			$wooecpay_invoice_random_number 		= get_post_meta( $order->get_id(), '_wooecpay_invoice_random_number', true ) ;

			$wooecpay_invoice_issue_type 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_issue_type', true ) ;
			$wooecpay_invoice_tsr 					= get_post_meta( $order->get_id(), '_wooecpay_invoice_tsr', true ) ;
			$wooecpay_invoice_process 				= get_post_meta( $order->get_id(), '_wooecpay_invoice_process', true ) ;

			$order_status = $order->get_status();

			// 開立發票按鈕顯示判斷
			$invoice_create_button = false ;

        	if(empty($wooecpay_invoice_process) && 
        		( $order_status == 'processing' || $order_status == 'completed')
        	){
        		$invoice_create_button = true ;
        	}

        	// 作廢發票按鈕顯示判斷
        	$invoice_invalid_button = false ;
        	if(!empty($wooecpay_invoice_process) && 
        		( $order_status == 'cancelled' || $order_status == 'refunded'))
        	{
        		$invoice_invalid_button = true ;
        	}

			// 顯示
			$invoiceType = [
				'p'	=> '個人',
				'c'	=> '公司',
				'd'	=> '捐贈',
			] ;

			$invoiceCarruerType = [
				'0'	=> '索取紙本',
				'1'	=> '雲端發票(中獎寄送紙本)',
				'2'	=> '自然人憑證',
				'3'	=> '手機條碼',
			] ;

			echo '<div class="logistic_button_display">';
			echo '<h3>發票資訊</h3>';
			echo wp_kses_post('<p><strong>發票號碼:</strong>'. $wooecpay_invoice_no . '</p>') ;	
			echo wp_kses_post('<p><strong>開立時間:</strong>'. $wooecpay_invoice_date . '</p>') ;	
			echo wp_kses_post('<p><strong>隨機碼:</strong>'. $wooecpay_invoice_random_number . '</p>') ;

			switch ($wooecpay_invoice_issue_type) {
				
				case '1':
					$wooecpay_invoice_issue_type_dsp = '一般開立發票';
					echo wp_kses_post('<p><strong>開立方式:</strong>'. $wooecpay_invoice_issue_type_dsp . '</p>') ;
					break;

				case '2':
					$wooecpay_invoice_issue_type_dsp = '延遲開立發票';
					echo wp_kses_post('<p><strong>開立方式:</strong>'. $wooecpay_invoice_issue_type_dsp . '</p>') ;
					echo wp_kses_post('<p><strong>交易單號:</strong>'. $wooecpay_invoice_tsr . '</p>') ;
					break;
				default:
					break;
			}
			


			if(isset($invoiceCarruerType[$wooecpay_invoice_carruer_type])){
				echo wp_kses_post('<p><strong>開立類型:</strong>'. $invoiceCarruerType[$wooecpay_invoice_carruer_type] . '</p>') ;
			}

			if(isset($invoiceType[$wooecpay_invoice_type])){
				echo wp_kses_post('<p><strong>發票開立:</strong>'. $invoiceType[$wooecpay_invoice_type] . '</p>') ;
			}

			switch ($wooecpay_invoice_type) {
				case 'p':
					if(!empty($wooecpay_invoice_carruer_num)){
						echo wp_kses_post('<p><strong>載具編號:</strong>'. $wooecpay_invoice_carruer_num . '</p>') ;
					}
				break;
				
				case 'c':
					echo wp_kses_post('<p><strong>公司名稱:</strong>'. $billing_company . '</p>') ;
					echo wp_kses_post('<p><strong>統一編號:</strong>'. $wooecpay_invoice_customer_identifier . '</p>') ;
				break;

				case 'd':
					echo wp_kses_post('<p><strong>愛心碼:</strong>'. $wooecpay_invoice_love_code . '</p>') ;
				break;
			}


			// 開立發票按鈕顯示判斷
        	if($invoice_create_button){
        		echo '<input class=\'button\' type=\'button\' value=\'開立發票\' onclick=\'wooecpayCreateInvoice('. $order->get_id().');\'>';	
        	}

        	// 作廢發票按鈕顯示判斷
        	if($invoice_invalid_button){
        		echo '<input class=\'button\' type=\'button\' value=\'作廢發票\' onclick=\'wooecpayInvalidInvoice('. $order->get_id().');\'>';	
        	}


			echo '</div>';
		}
	}
	
	/**
	 * 註冊JS
	 */
    function wooecpay_register_scripts() {
       
		wp_register_script(
			'wooecpay_main',
			WOOECPAY_PLUGIN_URL . 'public/js/wooecpay-main.js',
			array(),
			'1.0.0',
			true
       	);

       	// 載入js
        wp_enqueue_script('wooecpay_main');
    }

	/**
	 * 產生物流相關按鈕顯示
	 */
	public function logistic_button_display($order) {

        if ($order) {

        	// 取得訂單資訊
        	// $order_data = $order->get_data();


        	// 取得物流方式
			$shipping_method_id = $order->get_items('shipping') ;
			
			if($shipping_method_id) {
			
				$shipping_method_id = reset($shipping_method_id);    
				$shipping_method_id = $shipping_method_id->get_method_id() ;

				$order_status = $order->get_status();

	        	// 地圖按鈕顯示判斷
	        	if(true){

	        		// 按鈕顯示旗標
	        		$map_button = false ;

	        		// 判斷物流方式是否允許變更門市
			        if(
			        	( $order_status == 'on-hold' || $order_status == 'processing') && 
			        	(
			        		$shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' 
			        	)
			        ){

			        	// 狀態判斷是否已經建立綠界物流單 AllPayLogisticsID
			        	$ecpay_logistic_AllPayLogisticsID = get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true );

			        	if(empty($ecpay_logistic_AllPayLogisticsID) ){
			        		$map_button = true ;
				        }
			        }
	        	}

	        	// 物流訂單按鈕判斷
	        	if(true){

	        		$logistic_order_button = true ;

	        		// 判斷是否為綠界物流
		            if(
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' || 
		                $shipping_method_id == 'Wooecpay_Logistic_Home_Tcat' ||
		                $shipping_method_id == 'Wooecpay_Logistic_Home_Ecan' 
		            ){
		            	if(
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
			                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' 
			            ){
			            	
			            	// 狀態判斷 _ecpay_logistic_cvs_store_id門市代號不存在
			        		$ecpay_logistic_cvs_store_id = get_post_meta( $order->get_id(), '_ecpay_logistic_cvs_store_id', true );

			        		if(empty($ecpay_logistic_cvs_store_id)){
			        			$logistic_order_button = false ;
			        		}
			            }

		            } else {

		            	$logistic_order_button = false ;
		            }

	        		// 已經存在AllPayLogisticsID 關閉按鈕
	        		$AllPayLogisticsID = get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true );

	        		if(!empty($AllPayLogisticsID)){
	        			$logistic_order_button = false ;
	        		}

	        		if($order_status != 'on-hold' && $order_status != 'processing'){
	        			$logistic_order_button = false ;
	        		}
	        	}

	        	// 列印訂單按鈕判斷
	        	if(true){

	        		$logistic_print_button = false ;

	        		// 已經存在AllPayLogisticsID 關閉按鈕
	        		$AllPayLogisticsID = get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true );

	        		if(!empty($AllPayLogisticsID)){
	        			$logistic_print_button = true ;
	        		}
	        	}

	            // 判斷是否為綠界物流
	            if(
	                $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
	                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
	                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
	                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' || 
	                $shipping_method_id == 'Wooecpay_Logistic_Home_Tcat' ||
	                $shipping_method_id == 'Wooecpay_Logistic_Home_Ecan' 
	            ){
		        	// 判斷是否為超商取貨
		            if(
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
		                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' 
		            ){
		            	echo wp_kses_post('<p><strong>超商編號:</strong>'. get_post_meta( $order->get_id(), '_ecpay_logistic_cvs_store_id', true ) . '</p>') ;	
		            	echo wp_kses_post('<p><strong>超商名稱:</strong>'. get_post_meta( $order->get_id(), '_ecpay_logistic_cvs_store_name', true ) . '</p>') ;

		            	if ('yes' === get_option('wooecpay_keep_logistic_phone', 'yes')) {
		            		echo wp_kses_post('<p><strong>收件人電話:</strong>'. get_post_meta( $order->get_id(), 'wooecpay_shipping_phone', true ) . '</p>') ;
		            	}	
		            }

		            echo '<div class="logistic_button_display">';
		        	echo '<h3>物流單資訊</h3>' ;

		        	if(true){
		        		echo wp_kses_post('<p><strong>廠商交易編號:</strong>'. get_post_meta( $order->get_id(), '_wooecpay_logistic_merchant_trade_no', true ) . '</p>') ;	 
		        		echo wp_kses_post('<p><strong>綠界物流編號:</strong>'. get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true ) . '</p>') ;		
		        		echo wp_kses_post('<p><strong>寄貨編號:</strong>'. get_post_meta( $order->get_id(), '_wooecpay_logistic_CVSPaymentNo', true ) . '</p>') ;		
		        		echo wp_kses_post('<p><strong>托運單號:</strong>'. get_post_meta( $order->get_id(), '_wooecpay_logistic_BookingNote', true ) . '</p>') ;	
		        	}

		        	// 產生地圖按鈕兒
		        	if($map_button){
		        		
		        		// 組合地圖FORM

						$api_logistic_info  = $this->get_ecpay_logistic_api_info('map');
						$client_back_url    = WC()->api_request_url('wooecpay_change_logistic_map_callback', true);
						$MerchantTradeNo    = $this->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));
						$LogisticsType   	= $this->get_logistics_sub_type($shipping_method_id) ;

		        		try {
		                        $factory = new Factory([
		                            'hashKey'       => $api_logistic_info['hashKey'],
		                            'hashIv'        => $api_logistic_info['hashIv'],
		                            'hashMethod'    => 'md5',
		                        ]);
		                        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

		                        $inputMap = [
		                            'MerchantID'        => $api_logistic_info['merchant_id'],
		                            'MerchantTradeNo'   => $MerchantTradeNo,
									'LogisticsType'     => $LogisticsType['type'],
									'LogisticsSubType'  => $LogisticsType['sub_type'],
		                            'IsCollection'      => 'Y',
		                            'ServerReplyURL'    => $client_back_url,
		                        ];

		                        $form_map = $autoSubmitFormService->generate($inputMap, $api_logistic_info['action'], 'ecpay_map');

		                        $form_map =  str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_map) ;
		                        $form_map =  str_replace('</body></html>', '', $form_map) ;
		                        $form_map =  str_replace('<script type="text/javascript">document.getElementById("ecpay-form").submit();</script>', '', $form_map) ;
								
								echo '</form>';
								echo $form_map ;


		                    } catch (RtnException $e) {
		                        echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
		                    }

		        		echo '<input class=\'button\' type=\'button\' onclick=\'ecpayChangeStore();\' value=\'變更門市\' />&nbsp;&nbsp;';
		        	}

		        	// 產生按鈕
		        	if($logistic_order_button){
		        		echo '<input class=\'button\' type=\'button\' value=\'建立物流訂單\' onclick=\'ecpayCreateLogisticsOrder('. $order->get_id().');\'>';	
		        	}

		        	// 列印訂單按鈕判斷
		        	if($logistic_print_button){
		        		
						$api_logistic_info  = $this->get_ecpay_logistic_api_info('print', $shipping_method_id);
						
						$AllPayLogisticsID	= get_post_meta( $order->get_id(), '_wooecpay_logistic_AllPayLogisticsID', true ) ;
						$CVSPaymentNo   	= get_post_meta( $order->get_id(), '_wooecpay_logistic_CVSPaymentNo', true )  ;
						$CVSValidationNo   	= get_post_meta( $order->get_id(), '_wooecpay_logistic_CVSValidationNo', true )  ;

						// 組合送綠界物流列印參數
						$inputPrint['MerchantID'] 			= $api_logistic_info['merchant_id'] ;
						$inputPrint['AllPayLogisticsID'] 	= $AllPayLogisticsID ;

						switch ($shipping_method_id) {

							case 'Wooecpay_Logistic_CVS_711':
								$inputPrint['CVSPaymentNo'] = $CVSPaymentNo ;
								$inputPrint['CVSValidationNo'] = $CVSValidationNo ;
							break;

							case 'Wooecpay_Logistic_CVS_Family':
							case 'Wooecpay_Logistic_CVS_Hilife':
							case 'Wooecpay_Logistic_CVS_Okmart':
								$inputPrint['CVSPaymentNo'] = $CVSPaymentNo ;
							break;

							case 'Wooecpay_Logistic_Home_Tcat':
							case 'Wooecpay_Logistic_Home_Ecan':

							break;
							
							default:
							break;
						}

		        		try {
		                        $factory = new Factory([
		                            'hashKey'       => $api_logistic_info['hashKey'],
		                            'hashIv'        => $api_logistic_info['hashIv'],
		                            'hashMethod'    => 'md5',
		                        ]);
		                        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

		                        $form_print =  $autoSubmitFormService->generate($inputPrint, $api_logistic_info['action'], '_Blank','ecpay_print');
		                        $form_print =  str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_print) ;
		                        $form_print =  str_replace('</body></html>', '', $form_print) ;
		                        $form_print =  str_replace('<script type="text/javascript">document.getElementById("ecpay_print").submit();</script>', '', $form_print) ;
								
								echo '</form>';
								echo $form_print ;

		                    } catch (RtnException $e) {
		                        echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
		                    }

		        		echo '<input class=\'button\' type=\'button\' onclick=\'ecpayLogisticPrint();\' value=\'列印物流單\' />&nbsp;&nbsp;';
		        	}
		        	
		        	echo '</div>';
		        }
	       	
	       	}
       	}
	}

	/**
	 * 產生物流訂單
	 */
	public function ajax_send_logistic_order_action()
	{

		// 產生物流訂單
		$ajaxReturn = [
			'code' 	=> '0000',
			'msg'	=> '',
		];

		$order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id'])	: '' ;

		if ($order = wc_get_order($order_id)){

			// 取得物流方式
			$shipping_method_id = $order->get_items('shipping') ;
			$shipping_method_id = reset($shipping_method_id);    
			$shipping_method_id = $shipping_method_id->get_method_id() ;

			// 判斷是否為綠界物流 產生物流訂單
            if(
                $shipping_method_id == 'Wooecpay_Logistic_CVS_711' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Family' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Hilife' || 
                $shipping_method_id == 'Wooecpay_Logistic_CVS_Okmart' || 
                $shipping_method_id == 'Wooecpay_Logistic_Home_Tcat' ||
                $shipping_method_id == 'Wooecpay_Logistic_Home_Ecan' 
            ){

            	$LogisticsType 		= $this->get_logistics_sub_type($shipping_method_id) ;
            	$api_logistic_info  = $this->get_ecpay_logistic_api_info('create');
            	$MerchantTradeNo    = $this->get_merchant_trade_no($order->get_id(), get_option('wooecpay_logistic_order_prefix'));

            	$sender_name 		= get_option('wooecpay_logistic_sender_name') ;
            	$sender_cellphone 	= get_option('wooecpay_logistic_sender_cellphone') ;
            	$sender_zipcode 	= get_option('wooecpay_logistic_sender_zipcode') ;
            	$sender_address 	= get_option('wooecpay_logistic_sender_address') ;

            	$serverReplyURL    = WC()->api_request_url('wooecpay_logistic_status_callback', true);


            	// 取得訂單資訊
        		// $order_data = $order->get_data();

				$CVSStoreID = $order->get_meta('_ecpay_logistic_cvs_store_id') ;
				if(!isset($CVSStoreID) || empty($CVSStoreID)){

					$ajaxReturn = [
						'code' 	=> '0003',
						'msg'	=> '查無超商資料',
					];
				}

				$payment_method = $order->get_payment_method() ;
				if($payment_method == 'cod'){
					$IsCollection = 'Y';
				} else {
					$IsCollection = 'N';
				}

				$item_name = $this->get_item_name($order) ;

            	if($LogisticsType['type'] == 'HOME'){

            		$inputLogisticOrder = [
				        'MerchantID'        	=> $api_logistic_info['merchant_id'],
				        'MerchantTradeNo' 		=> $MerchantTradeNo,
				        'MerchantTradeDate' 	=> date('Y/m/d H:i:s'),
				        'LogisticsType' 		=> $LogisticsType['type'],
				        'LogisticsSubType' 		=> $LogisticsType['sub_type'],
				        'GoodsAmount' 			=> $order->get_total(),
				        'GoodsName'				=> $item_name,
				        'SenderName' 			=> $sender_name,
				        'SenderCellPhone' 		=> $sender_cellphone,
				        'SenderZipCode' 		=> $sender_zipcode,
				        'SenderAddress' 		=> $sender_address,
				        'ReceiverName' 			=> $order->get_shipping_first_name() . $order->get_shipping_last_name(),
				        'ReceiverCellPhone' 	=> $order->get_billing_phone(),
				        'ReceiverZipCode' 		=> $order->get_shipping_postcode(),
				        'ReceiverAddress' 		=> $order->get_shipping_state().$order->get_shipping_city().$order->get_shipping_address_1().$order->get_shipping_address_2(),
				        'Temperature' 			=> '0001',
				        'Distance' 				=> '00',
				        'Specification' 		=> '0001',
				        'ScheduledPickupTime' 	=> '4',
				        'ScheduledDeliveryTime' => '4',
				        'ServerReplyURL' 		=> $serverReplyURL,
				    ];

            	} else if($LogisticsType['type'] == 'CVS'){

            		$inputLogisticOrder = [
				        'MerchantID'        	=> $api_logistic_info['merchant_id'],
				        'MerchantTradeNo' 		=> $MerchantTradeNo,
				        'MerchantTradeDate' 	=> date('Y/m/d H:i:s'),
				        'LogisticsType' 		=> $LogisticsType['type'],
				        'LogisticsSubType' 		=> $LogisticsType['sub_type'],
				        'GoodsAmount' 			=> $order->get_total(),
				        'GoodsName'				=> $item_name,
				        'SenderName' 			=> $sender_name,
				        'SenderCellPhone' 		=> $sender_cellphone,
				        'ReceiverName' 			=> $order->get_shipping_first_name() . $order->get_shipping_last_name(),
				        'ReceiverCellPhone' 	=> $order->get_billing_phone(),
				        'ReceiverStoreID' 		=> $CVSStoreID,
				        'IsCollection'			=> $IsCollection,
				        'ServerReplyURL' 		=> $serverReplyURL,
				    ];
            		
            	}

            	try {
				    $factory = new Factory([
				        'hashKey'       => $api_logistic_info['hashKey'],
                        'hashIv'        => $api_logistic_info['hashIv'],
				        'hashMethod' 	=> 'md5',
				    ]);

				    $postService = $factory->create('PostWithCmvEncodedStrResponseService');
				    $response = $postService->post($inputLogisticOrder, $api_logistic_info['action']);

				    if(isset($response['RtnCode']) && ( $response['RtnCode'] == 300 || $response['RtnCode'] == 2001 ) ){

				    	// 更新訂單
						$order->update_meta_data( '_wooecpay_logistic_merchant_trade_no', $response['MerchantTradeNo'] ); 
						$order->update_meta_data( '_wooecpay_logistic_RtnCode', $response['RtnCode'] ); 
						$order->update_meta_data( '_wooecpay_logistic_RtnMsg', $response['RtnMsg'] ); 
						$order->update_meta_data( '_wooecpay_logistic_AllPayLogisticsID', $response['1|AllPayLogisticsID'] );  
						$order->update_meta_data( '_wooecpay_logistic_LogisticsType', $response['LogisticsType'] );
						$order->update_meta_data( '_wooecpay_logistic_CVSPaymentNo', $response['CVSPaymentNo'] ); 
						$order->update_meta_data( '_wooecpay_logistic_CVSValidationNo', $response['CVSValidationNo'] ); 
						$order->update_meta_data( '_wooecpay_logistic_BookingNote', $response['BookingNote'] );  

						$order->add_order_note('建立物流訂單-物流廠商交易編號:'.$response['MerchantTradeNo']. '，狀態:' . $response['RtnMsg'] . '('. $response['RtnCode'] . ')');
						$order->save();

						$ajaxReturn = [
							'code' 	=> '9999',
							'msg'	=> '成功',
						];

				    } else {

				    	// add note
				    	$order->add_order_note(print_r($response, true));
						$order->save();
				    }

				    // var_dump($response);
				} catch (RtnException $e) {
				    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
				}

            } else {

            	$ajaxReturn = [
					'code' 	=> '0002',
					'msg'	=> '非綠界物流方式',
				];
            }

		} else {

			$ajaxReturn = [
				'code' 	=> '0001',
				'msg'	=> '查無訂單',
			];

		}

		echo json_encode($ajaxReturn, true);

		wp_die();
	}

	/**
	 * 開立發票
	 */
	public function ajax_send_invoice_create()
	{
		$order_id = isset($_POST['order_id'])	? sanitize_text_field($_POST['order_id'])	: '' ;
		$this->invoice_create($order_id);
	}

	/**
	 * 開立發票程序
	 */
	public function invoice_create($order_id)
	{

		if ($order = wc_get_order($order_id)){

			// 判斷發票是否存在 不存在則開立

			$wooecpay_invoice_process = get_post_meta( $order->get_id(), '_wooecpay_invoice_process', true ) ;

			if(empty($wooecpay_invoice_process)){

				$wooecpay_invoice_dalay_date = get_option('wooecpay_invoice_dalay_date') ;
				$wooecpay_invoice_dalay_date = (int) $wooecpay_invoice_dalay_date ;

				if(empty($wooecpay_invoice_dalay_date)){
					
					// 立即開立
					$api_payment_info 	= $this->get_ecpay_invoice_api_info('issue');
					$RelateNumber 		= $this->get_relate_number($order->get_id(), get_option('wooecpay_invoice_prefix'));
					
					try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);
					    $postService = $factory->create('PostWithAesJsonResponseService');

					    $items = [] ;

					    foreach ( $order->get_items() as $item_id => $item ) {

					    	$Items[] = [
								'ItemName' 		=> mb_substr($item->get_name(), 0, 100),
								'ItemCount' 	=> $item->get_quantity(),
								'ItemWord' 		=> '批',
								'ItemPrice' 	=>  round($item->get_total() / $item->get_quantity(), 4),
								'ItemTaxType' 	=> '1',
								'ItemAmount' 	=> round($item->get_total(), 2),
					    	] ;
					    }

					    // 物流費用
					    $shipping_fee = $order->get_shipping_total();
				        if ($shipping_fee != 0) {

				        	$Items[] = [
								'ItemName' => __('Shipping fee', 'ecpay-ecommerce-for-woocommerce'),
								'ItemCount' 	=> 1,
								'ItemWord' 		=> '批',
								'ItemPrice' => $shipping_fee,
				                'ItemTaxType' => '1',
				                'ItemAmount' => $shipping_fee,
					    	] ;
				        }

						$country = $order->get_billing_country();
						$countries = WC()->countries->get_countries();
						$full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

						$state = $order->get_billing_state();
						$states = WC()->countries->get_states($country);
						$full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

					    $data = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'RelateNumber' => $RelateNumber,
					        'CustomerID' => '',
					        'CustomerName' => $order->get_billing_last_name() . $order->get_billing_first_name(),
           					'CustomerAddr' => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
					        'CustomerPhone' => $order->get_billing_phone(),
					        'CustomerEmail' => $order->get_billing_email(),
					        'Print' => '0',
				            'Donation' => '0',
				            'LoveCode' => '',
				            'CarrierType' => '',
				            'CarrierNum' => '',
					        'TaxType' => '1',
					        'SalesAmount' => intval(round($order->get_total(), 0)),
					        'Items' => $Items,
					        'InvType' => '07'
					    ];

					    $wooecpay_invoice_type 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_type', true ) ;
					   	$wooecpay_invoice_carruer_type 	= get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_type', true ) ;

					    switch ($wooecpay_invoice_type) {
				            
				            case 'p':
				                switch ($wooecpay_invoice_carruer_type ) {

				                    case '1':
				                        $data['CarrierType'] = '1';
				                        break;
				                    case '2':
				                        $data['CarrierType'] = '2';
				                        $data['CarrierNum'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_num', true ) ;
				                        break;
				                    case '3':
				                        $data['CarrierType'] = '3';
				                        $data['CarrierNum'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_num', true ) ;
				                        break;
				                    default:
				                        $data['Print'] = '1';
				                        break;
				                }

				                break;

				            case 'c':
				                $data['Print'] = '1';
				                $data['CustomerIdentifier'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_customer_identifier', true ) ;
				                $company = $order->get_billing_company();
				                if ($company) {
				                    $data['CustomerName'] = $company;
				                }
				                break;

				            case 'd':
				                $data['Donation'] = '1';
				                $data['LoveCode'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_love_code', true ) ;
				                break;
				        }


					    $input = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'RqHeader' => [
					            'Timestamp' => time(),
					            'Revision' => '3.0.0',
					        ],
					        'Data' => $data,
					    ];

					    $response = $postService->post($input, $api_payment_info['action']);

					    if($response['TransCode'] == 1){

						    if($response['Data']['RtnCode'] == 1){

						    	// 更新訂單
								$order->update_meta_data( '_wooecpay_invoice_relate_number', $RelateNumber ); 
								$order->update_meta_data( '_wooecpay_invoice_RtnCode', $response['Data']['RtnCode'] ); 
								$order->update_meta_data( '_wooecpay_invoice_RtnMsg', $response['Data']['RtnMsg'] );  
								$order->update_meta_data( '_wooecpay_invoice_no', $response['Data']['InvoiceNo'] );
								$order->update_meta_data( '_wooecpay_invoice_date', $response['Data']['InvoiceDate'] ); 
								$order->update_meta_data( '_wooecpay_invoice_random_number', $response['Data']['RandomNumber']); 

								$order->update_meta_data( '_wooecpay_invoice_process', 1); // 執行開立完成
								$order->update_meta_data( '_wooecpay_invoice_issue_type', 1); // 開立類型 1.立即開立 2.延遲開立

								$order->add_order_note('發票開立成功:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
								$order->save();

						    } else {

						    	$order->add_order_note('發票開立失敗:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
								$order->save();
						    }

					    } else {

					    	$order->add_order_note('發票開立失敗:狀態:' . $response['TransMsg'] . '('. $response['TransCode'] . ')');
							$order->save();
					    }

					    $ajaxReturn = [
							'code' 	=> '9999',
							'msg'	=> '成功',
						];

					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}

				} else {
					
					// 延遲開立

					$api_payment_info 	= $this->get_ecpay_invoice_api_info('delay_issue');
					$RelateNumber 		= $this->get_relate_number($order->get_id(), get_option('wooecpay_invoice_prefix'));
					
					try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);
					    $postService = $factory->create('PostWithAesJsonResponseService');

					    $items = [] ;

					    foreach ( $order->get_items() as $item_id => $item ) {

					    	$Items[] = [
								'ItemName' 		=> mb_substr($item->get_name(), 0, 100),
								'ItemCount' 	=> $item->get_quantity(),
								'ItemWord' 		=> '批',
								'ItemPrice' 	=>  round($item->get_total() / $item->get_quantity(), 4),
								'ItemTaxType' 	=> '1',
								'ItemAmount' 	=> round($item->get_total(), 2),
					    	] ;
					    }

					    // 物流費用
					    $shipping_fee = $order->get_shipping_total();
				        if ($shipping_fee != 0) {

				        	$Items[] = [
								'ItemName' => __('Shipping fee', 'ecpay-ecommerce-for-woocommerce'),
								'ItemCount' 	=> 1,
								'ItemWord' 		=> '批',
								'ItemPrice' => $shipping_fee,
				                'ItemTaxType' => '1',
				                'ItemAmount' => $shipping_fee,
					    	] ;
				        }

						$country = $order->get_billing_country();
						$countries = WC()->countries->get_countries();
						$full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

						$state = $order->get_billing_state();
						$states = WC()->countries->get_states($country);
						$full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

					    $data = [
					        'MerchantID' 	=> $api_payment_info['merchant_id'],
					        'RelateNumber' 	=> $RelateNumber,
					        'CustomerID' 	=> '',
					        'CustomerName' 	=> $order->get_billing_last_name() . $order->get_billing_first_name(),
           					'CustomerAddr' 	=> $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
					        'CustomerPhone' => $order->get_billing_phone(),
					        'CustomerEmail' => $order->get_billing_email(),
					        'Print' 		=> '0',
				            'Donation' 		=> '0',
				            'LoveCode' 		=> '',
				            'CarrierType' 	=> '',
				            'CarrierNum' 	=> '',
					        'TaxType' 		=> '1',
					        'SalesAmount' 	=> intval(round($order->get_total(), 0)),
					        'Items' 		=> $Items,
					        'InvType' 		=> '07',

							'DelayFlag' 	=> '1',
							'DelayDay' 		=> $wooecpay_invoice_dalay_date,
							'Tsr' 			=> $RelateNumber,
							'PayType' 		=> '2',
							'PayAct' 		=> 'ECPAY',
							'NotifyURL' 	=> WC()->api_request_url('wooecpay_invoice_delay_issue_callback', true),
					    ];

					    $wooecpay_invoice_type 			= get_post_meta( $order->get_id(), '_wooecpay_invoice_type', true ) ;
					   	$wooecpay_invoice_carruer_type 	= get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_type', true ) ;

					    switch ($wooecpay_invoice_type) {
				            
				            case 'p':
				                switch ($wooecpay_invoice_carruer_type ) {

				                    case '1':
				                        $data['CarrierType'] = '1';
				                        break;
				                    case '2':
				                        $data['CarrierType'] = '2';
				                        $data['CarrierNum'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_num', true ) ;
				                        break;
				                    case '3':
				                        $data['CarrierType'] = '3';
				                        $data['CarrierNum'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_carruer_num', true ) ;
				                        break;
				                    default:
				                        $data['Print'] = '1';
				                        break;
				                }

				                break;

				            case 'c':
				                $data['Print'] = '1';
				                $data['CustomerIdentifier'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_customer_identifier', true ) ;
				                $company = $order->get_billing_company();
				                if ($company) {
				                    $data['CustomerName'] = $company;
				                }
				                break;

				            case 'd':
				                $data['Donation'] = '1';
				                $data['LoveCode'] = get_post_meta( $order->get_id(), '_wooecpay_invoice_love_code', true ) ;
				                break;
				        }


					    $input = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'RqHeader' => [
					            'Timestamp' => time(),
					            'Revision' => '3.0.0',
					        ],
					        'Data' => $data,
					    ];

					    $response = $postService->post($input, $api_payment_info['action']);
					    
						if($response['TransCode'] == 1){
						    
						    if($response['Data']['RtnCode'] == 1){

						    	// 更新訂單
								$order->update_meta_data( '_wooecpay_invoice_relate_number', $RelateNumber ); 
								$order->update_meta_data( '_wooecpay_invoice_RtnCode', $response['Data']['RtnCode'] ); 
								$order->update_meta_data( '_wooecpay_invoice_RtnMsg', $response['Data']['RtnMsg'] );

								$order->update_meta_data( '_wooecpay_invoice_process', 1); // 執行開立完成
								$order->update_meta_data( '_wooecpay_invoice_issue_type', 2); // 開立類型 1.立即開立 2.延遲開立
								$order->update_meta_data( '_wooecpay_invoice_tsr', $RelateNumber); // 交易單號 
		
								$order->add_order_note('發票開立成功:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
								$order->save();

						    } else {

						    	$order->add_order_note('發票開立失敗:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
								$order->save();
						    }

						} else {

							$order->add_order_note('發票開立失敗:狀態:' . $response['TransMsg'] . '('. $response['TransCode'] . ')');
							$order->save();
						}

					    $ajaxReturn = [
							'code' 	=> '9999',
							'msg'	=> '成功',
						];

					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}
				}
			}
		}
	}

	/**
	 * 作廢發票
	 */
	public function ajax_send_invoice_invalid()
	{
		$order_id = isset($_POST['order_id'])	? sanitize_text_field($_POST['order_id'])	: '' ;
		$this->invoice_invalid($order_id);
	}

	/**
	 * 作廢發票程序
	 */
	public function invoice_invalid($order_id)
	{

		if ($order = wc_get_order($order_id)){

			// 判斷發票是否存在 存在則才可以執行作廢
			$wooecpay_invoice_process = get_post_meta( $order->get_id(), '_wooecpay_invoice_process', true ) ;

			if($wooecpay_invoice_process == 1){

				$wooecpay_invoice_issue_type = get_post_meta( $order->get_id(), '_wooecpay_invoice_issue_type', true ) ;

				if($wooecpay_invoice_issue_type == 1){

					$api_payment_info 	= $this->get_ecpay_invoice_api_info('invalid');

					$wooecpay_invoice_no = get_post_meta( $order->get_id(), '_wooecpay_invoice_no', true ) ;
					$wooecpay_invoice_date = get_post_meta( $order->get_id(), '_wooecpay_invoice_date', true ) ;

					// 作廢發票
					try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);
					    $postService = $factory->create('PostWithAesJsonResponseService');

					    $data = [
					        'MerchantID' 	=> $api_payment_info['merchant_id'],
					        'InvoiceNo' 	=> $wooecpay_invoice_no,
					        'InvoiceDate' 	=> $wooecpay_invoice_date,
					        'Reason' 		=> __('Invalid invoice', 'ecpay-ecommerce-for-woocommerce'),
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
					    if($response['Data']['RtnCode'] == 1 || $response['Data']['RtnCode'] == 5070453){

					    	// 更新訂單
							$order->update_meta_data( '_wooecpay_invoice_relate_number', ''); 
							$order->update_meta_data( '_wooecpay_invoice_RtnCode', ''); 
							$order->update_meta_data( '_wooecpay_invoice_RtnMsg', '' );  
							$order->update_meta_data( '_wooecpay_invoice_no', '' ); 
							$order->update_meta_data( '_wooecpay_invoice_date', '' ); 
							$order->update_meta_data( '_wooecpay_invoice_random_number', ''); 

							$order->update_meta_data( '_wooecpay_invoice_process', 0); // 執行開立完成
							$order->update_meta_data( '_wooecpay_invoice_issue_type', ''); // 開立類型 1.立即開立 2.延遲開立

							$order->add_order_note('發票作廢成功: 發票號碼:' .$wooecpay_invoice_no . ' 狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
							$order->save();
						} else {

					    	$order->add_order_note('發票作廢失敗:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
							$order->save();
					    }


					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}

				} else if($wooecpay_invoice_issue_type == 2){

					$api_payment_info 	= $this->get_ecpay_invoice_api_info('cancel_delay_issue');
					$wooecpay_invoice_tsr = get_post_meta( $order->get_id(), '_wooecpay_invoice_tsr', true ) ;

					try {
					    $factory = new Factory([
					        'hashKey' 	=> $api_payment_info['hashKey'],
					        'hashIv' 	=> $api_payment_info['hashIv'],
					    ]);
					    $postService = $factory->create('PostWithAesJsonResponseService');
					    $data = [
					        'MerchantID' => $api_payment_info['merchant_id'],
					        'Tsr' => $wooecpay_invoice_tsr,
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
					    
					     if($response['Data']['RtnCode'] == 1){

					    	// 更新訂單
							$order->update_meta_data( '_wooecpay_invoice_relate_number', ''); 
							$order->update_meta_data( '_wooecpay_invoice_RtnCode', ''); 
							$order->update_meta_data( '_wooecpay_invoice_RtnMsg', '' );  
							$order->update_meta_data( '_wooecpay_invoice_no', '' ); 
							$order->update_meta_data( '_wooecpay_invoice_date', '' ); 
							$order->update_meta_data( '_wooecpay_invoice_random_number', ''); 
							$order->update_meta_data( '_wooecpay_invoice_tsr', ''); // 交易單號 

							$order->update_meta_data( '_wooecpay_invoice_process', 0); // 執行開立完成
							$order->update_meta_data( '_wooecpay_invoice_issue_type', ''); // 開立類型 1.立即開立 2.延遲開立

							$order->add_order_note('發票作廢成功: 交易單號:' .$wooecpay_invoice_tsr . ' 狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');

							$order->save();
						} else {

					    	$order->add_order_note('發票作廢失敗:狀態:' . $response['Data']['RtnMsg'] . '('. $response['Data']['RtnCode'] . ')');
							$order->save();
					    }

					} catch (RtnException $e) {
					    echo wp_kses_post( '(' . $e->getCode() . ')' . $e->getMessage() ) . PHP_EOL;
					}

				}
			}
		}
	}

	// logistic 
    // ---------------------------------------------------

    protected function get_ecpay_logistic_api_info($action = '', $shipping_method_id = '' )
    {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

        $api_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ] ;

        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

            if($wooecpay_logistic_cvs_type == 'C2C'){

                $api_info = [
                    'merchant_id'   => '2000933',
                    'hashKey'       => 'XBERn1YOvpM9nfZc',
                    'hashIv'        => 'h1ONHk4P4yqbl5LK',
                ] ;

            } else if($wooecpay_logistic_cvs_type == 'B2C'){

                $api_info = [
                    'merchant_id'   => '2000132',
                    'hashKey'       => '5294y06JbISpM5x9',
                    'hashIv'        => 'v77hoKGq4kWxNNIS',
                ] ;
            }

        } else {
            
            $merchant_id = get_option('wooecpay_logistic_mid');
            $hash_key    = get_option('wooecpay_logistic_hashkey');
            $hash_iv     = get_option('wooecpay_logistic_hashiv');

            $api_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ] ;
        }


        // URL位置判斷
        if ('yes' === get_option('wooecpay_enabled_logistic_stage', 'yes')) {

	        switch ($action) {

	        	case 'map':
	        		$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map' ;
	        	break;
	        	
	        	case 'create':
	        		$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create' ;
	        	break;

	        	case 'print':

	        		if($wooecpay_logistic_cvs_type == 'C2C'){

		        		switch ($shipping_method_id) {

		        			case 'Wooecpay_Logistic_CVS_711':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Family':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Hilife':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Okmart':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_Home_Tcat':
		        			case 'Wooecpay_Logistic_Home_Ecan':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
		        			break;
		        			
		        			default:
		        				$api_info['action'] = '' ;
		        			break;
		        		}

		        	} else if($wooecpay_logistic_cvs_type == 'B2C'){

		        		switch ($shipping_method_id) {

		        			case 'Wooecpay_Logistic_CVS_711':
		        			case 'Wooecpay_Logistic_CVS_Family':
		        			case 'Wooecpay_Logistic_CVS_Hilife':
		        			case 'Wooecpay_Logistic_Home_Tcat':
		        			case 'Wooecpay_Logistic_Home_Ecan':
		        				$api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
		        			break;
		        			default:
		        				$api_info['action'] = '' ;
		        			break;
		        		}
		        	}
	        		
	        	break;

	        	default:
	        	break;
	        }

	    } else {

	    	switch ($action) {

	        	case 'map':
	        		$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/map' ;
	        	break;
	        	
	        	case 'create':
	        		$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/Create' ;
	        	break;

	        	case 'print':

	        		if($wooecpay_logistic_cvs_type == 'C2C'){

		        		switch ($shipping_method_id) {

		        			case 'Wooecpay_Logistic_CVS_711':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Family':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Hilife':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_CVS_Okmart':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
		        			break;

		        			case 'Wooecpay_Logistic_Home_Tcat':
		        			case 'Wooecpay_Logistic_Home_Ecan':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
		        			break;
		        			
		        			default:
		        				$api_info['action'] = '' ;
		        			break;
		        		}

		        	} else if($wooecpay_logistic_cvs_type == 'B2C'){

		        		switch ($shipping_method_id) {

		        			case 'Wooecpay_Logistic_CVS_711':
		        			case 'Wooecpay_Logistic_CVS_Family':
		        			case 'Wooecpay_Logistic_CVS_Hilife':
		        			case 'Wooecpay_Logistic_Home_Tcat':
		        			case 'Wooecpay_Logistic_Home_Ecan':
		        				$api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
		        			break;
		        			default:
		        				$api_info['action'] = '' ;
		        			break;
		        		}
		        	}
	        		
	        	break;

	        	default:
	        	break;
	        }
	    }

        return $api_info;
    }

    protected function get_merchant_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_id . 'SN' .(string) time() ;
        return substr($trade_no, 0, 20);
    }

    protected function get_logistics_sub_type($shipping_method_id)
    {
        $wooecpay_logistic_cvs_type = get_option('wooecpay_logistic_cvs_type');

        $logisticsType = [
        	'type' 		=> '',
        	'sub_type' 	=> '',
        ] ;

        switch ($shipping_method_id) { 
            case 'Wooecpay_Logistic_CVS_711':

            	$logisticsType['type'] = 'CVS' ;

            	if($wooecpay_logistic_cvs_type == 'C2C'){
                	$logisticsType['sub_type'] = 'UNIMARTC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                	$logisticsType['sub_type'] = 'UNIMART' ;
                }

            break;
            case 'Wooecpay_Logistic_CVS_Family':
                
                $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                	$logisticsType['sub_type'] = 'FAMIC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                	$logisticsType['sub_type'] = 'FAMI' ;
                }


            break;
            case 'Wooecpay_Logistic_CVS_Hilife':

	            $logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                	$logisticsType['sub_type'] = 'HILIFEC2C' ;
                } else if($wooecpay_logistic_cvs_type == 'B2C'){
                	$logisticsType['sub_type'] = 'HILIFE' ;
                }

            break;
            case 'Wooecpay_Logistic_CVS_Okmart':

            	$logisticsType['type'] = 'CVS' ;

                if($wooecpay_logistic_cvs_type == 'C2C'){
                	$logisticsType['sub_type'] = 'OKMARTC2C' ;
                }

            break;

            case 'Wooecpay_Logistic_Home_Tcat':
            	$logisticsType['type'] = 'HOME' ;
            	$logisticsType['sub_type'] = 'TCAT' ;
            break;

            case 'Wooecpay_Logistic_Home_Ecan':
            	$logisticsType['type'] = 'HOME' ;
            	$logisticsType['sub_type'] = 'ECAN' ;
            break;
        }

        return $logisticsType;
    }

	protected function get_item_name($order)
    {
        $item_name = '';

        if ( count($order->get_items()) ) {
            foreach ($order->get_items() as $item) {
                $item_name .=  trim($item->get_name()) . ' ' ;
            }
        }

        return $item_name;
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

                case 'issue':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue' ;
                break;

                case 'delay_issue':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue' ;
                break;

                case 'invalid':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid' ;
                break;

                case 'cancel_delay_issue':
                    $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue' ;
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

                case 'issue':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue' ;
                break;

                case 'delay_issue':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue' ;
                break;
               	
               	case 'invalid':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid' ;
                break;

                case 'cancel_delay_issue':
                    $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue' ;
                break;

                default:
                break;
            }
        }

        return $api_info;
    }

	protected function get_relate_number($order_id, $order_prefix = '')
    {
        $relate_no = $order_prefix . $order_id . 'SN' .(string) time() ;
        return substr($relate_no, 0, 20);
    }  

}



