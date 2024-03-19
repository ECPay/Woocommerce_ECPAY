<?php
namespace Helpers\Payment;

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Services\AesService;

class Wooecpay_Payment_Helper
{
    public function get_merchant_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . substr(str_pad($order_id, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5);
        return substr($trade_no, 0, 20);
    }

    public function get_order_id_by_merchant_trade_no($info)
    {
        $order_prefix = get_option('wooecpay_payment_order_prefix') ;

        if (isset($info['MerchantTradeNo'])) {

            $order_id = substr($info['MerchantTradeNo'], strlen($order_prefix), strrpos($info['MerchantTradeNo'], 'SN'));
            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }

        return false;
    }

    public function get_ecpay_payment_api_info($action = '')
    {
        $api_payment_info = [
            'merchant_id'   => '',
            'hashKey'       => '',
            'hashIv'        => '',
            'action'        => '',
        ];

        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            $api_payment_info = [
                'merchant_id'   => '3002607',
                'hashKey'       => 'pwFHCqoQZGmho4w6',
                'hashIv'        => 'EkRm7iFT261dpevs',
            ];

        } else {

            $merchant_id    = get_option('wooecpay_payment_mid');
            $hash_key       = get_option('wooecpay_payment_hashkey');
            $hash_iv        = get_option('wooecpay_payment_hashiv');

            $api_payment_info = [
                'merchant_id'   => $merchant_id,
                'hashKey'       => $hash_key,
                'hashIv'        => $hash_iv,
            ];
        }

        // URL位置判斷
        if ('yes' === get_option('wooecpay_enabled_payment_stage', 'yes')) {

            switch ($action) {

                case 'QueryTradeInfo':
                    $api_payment_info['action'] = 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
                    break;

                case 'AioCheckOut':
                    $api_payment_info['action'] = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';
                    break;

                default:
                    break;
            }

        } else {

            switch ($action) {

                case 'QueryTradeInfo':
                    $api_payment_info['action'] = 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
                    break;

                case 'AioCheckOut':
                    $api_payment_info['action'] = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';
                    break;

                default:
                    break;
            }
        }

        return $api_payment_info;
    }

    public function get_item_name($order)
    {
        $item_name = '';

        if (count($order->get_items())) {

            foreach ($order->get_items() as $item) {
                $item_name .= str_replace('#', '', trim($item->get_name())) . '#';
            }
        }

        $item_name = rtrim($item_name, '#');

        return $item_name;
    }

    public function add_type_info($input, $order)
    {
        $payment_type = $this->get_ChoosePayment($order->get_payment_method());

        switch ($payment_type) {

            case 'Credit':

                // 信用卡分期
                $number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
                if (in_array($number_of_periods, [3, 6, 12, 18, 24, 30])) {
                    $input['CreditInstallment'] = ($number_of_periods == 30) ? '30N' : $number_of_periods;
                    $order->add_order_note(sprintf(__('Credit installment to %d', 'ecpay-ecommerce-for-woocommerce'), $number_of_periods));

                    $order->save();
                }

                // 定期定額
                $dca = $order->get_meta('_ecpay_payment_dca');
                $dcaInfo = explode('_', $dca);
                if (count($dcaInfo) > 1) {
                    $input['PeriodAmount'] = $input['TotalAmount'];
                    $input['PeriodType'] = $dcaInfo[0];
                    $input['Frequency'] = (int)$dcaInfo[1];
                    $input['ExecTimes'] = (int)$dcaInfo[2];
                    $input['PeriodReturnURL'] = $input['ReturnURL'];
                }

                break;

            case 'ATM':

                $settings = get_option('woocommerce_Wooecpay_Gateway_Atm_settings', false);

                if(isset($settings['expire_date'])){
                    $expire_date = (int)$settings['expire_date'];
                } else {
                    $expire_date = 3;
                }

                $input['ExpireDate'] = $expire_date;

            break;

            case 'BARCODE':

                $settings = get_option('woocommerce_Wooecpay_Gateway_Barcode_settings', false);

                if(isset($settings['expire_date'])){
                    $expire_date = (int)$settings['expire_date'];
                } else {
                    $expire_date = 3;
                }

                $input['StoreExpireDate'] = $expire_date;

            break;

            case 'CVS':

                $settings = get_option('woocommerce_Wooecpay_Gateway_Cvs_settings', false);

                if(isset($settings['expire_date'])){
                    $expire_date = (int)$settings['expire_date'];
                } else {
                    $expire_date = 10080;
                }

                $input['StoreExpireDate'] = $expire_date;

            break;
        }

        return $input;
    }

    public function get_ChoosePayment($payment_method)
    {
        $choose_payment = '';

        switch ($payment_method) {
            case 'Wooecpay_Gateway_Credit':
            case 'Wooecpay_Gateway_Credit_Installment':
            case 'Wooecpay_Gateway_Dca':
                    $choose_payment = 'Credit';
                break;
            case 'Wooecpay_Gateway_Webatm':
                $choose_payment = 'WebATM';
                break;
            case 'Wooecpay_Gateway_Atm':
                $choose_payment = 'ATM';
                break;
            case 'Wooecpay_Gateway_Cvs':
                $choose_payment = 'CVS';
                break;
            case 'Wooecpay_Gateway_Barcode':
                $choose_payment = 'BARCODE';
                break;
            case 'Wooecpay_Gateway_Applepay':
                $choose_payment = 'ApplePay';
                break;
            case 'Wooecpay_Gateway_Twqr':
                $choose_payment = 'TWQR';
                break;
            case 'Wooecpay_Gateway_Bnpl':
                $choose_payment = 'BNPL';
                break;
        }

        return $choose_payment;
    }

    /**
     * 新增訂單付款資訊
     *
     * @param  int    $order_id
     * @param  string $payment_method
     * @param  string $merchant_trade_no
     * @param  int    $payment_status
     * @return void
     */
    public function insert_ecpay_orders_payment_status($order_id, $payment_method, $merchant_trade_no, $payment_status = 0)
    {
        global $wpdb;

        $is_exist        = false;
        $table_name      = $wpdb->prefix . 'ecpay_orders_payment_status';
        $isTableExists   = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        // Table 存在才能新增資料
        if ($isTableExists) {

            // 檢查資料是否存在
            if ($payment_method === 'cod') {
                $is_exist = $this->is_cod_payment_status_exist($order_id);
            } else {
                $is_exist = $this->is_ecpay_orders_payment_status_exist($order_id, $merchant_trade_no);
            }

            // 資料存在不新增
            if (!$is_exist) {
                $insert = [
                    'order_id'          => $order_id,
                    'payment_method'    => $payment_method,
                    'merchant_trade_no' => $merchant_trade_no,
                    'payment_status'    => $payment_status
                ];

                $format = [
                    '%d',
                    '%s',
                    '%s',
                    '%d'
                ];

                $wpdb->insert($table_name, $insert, $format);
            }
        }
    }

    /**
     * 取得重複付款訂單的綠界金流特店交易編號
     *
     * @param  string $order_id
     * @return array
     */
    public function get_duplicate_payment_orders_merchant_trade_no($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT merchant_trade_no
                FROM $table_name
                WHERE order_id = %d AND payment_status = 1 AND is_completed_duplicate = 0
                ORDER BY id DESC",
                $order_id
            )
        );

        if (!empty($results)) {
            $merchant_trade_no_list = [];
            foreach ($results as $result) {
				array_push($merchant_trade_no_list, $result->merchant_trade_no);
			}
            return $merchant_trade_no_list;
        } else {
            return [];
        }
    }

    /**
     * 檢查是否已存在貨到付款紀錄
     *
     * @param  string $order_id
     * @return bool
     */
    public function is_cod_payment_status_exist($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(merchant_trade_no)
                FROM $table_name
                WHERE order_id = %d AND payment_method = %s",
                $order_id,
                'cod'
            )
        );

        return ($count > 0);
    }

    /**
     * 檢查綠界金流特店交易編號是否已存在
     *
     * @param  string $order_id
     * @param  string $merchant_trade_no
     * @return bool
     */
    public function is_ecpay_orders_payment_status_exist($order_id, $merchant_trade_no) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(merchant_trade_no)
                FROM $table_name
                WHERE order_id = %d AND merchant_trade_no = %s",
                $order_id, $merchant_trade_no
            )
        );

        return ($count > 0);
    }

    /**
     * 檢查綠界金流特店交易編號是否已付款
     *
     * @param  string $order_id
     * @param  string $merchant_trade_no
     * @return bool
     */
    public function is_ecpay_order_paid($order_id, $merchant_trade_no) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT payment_status
                FROM $table_name
                WHERE order_id = %d AND merchant_trade_no = %s
                LIMIT 1",
                $order_id, $merchant_trade_no
            )
        );

        if (!empty($results)) {
            return ($results[0]->payment_status === 1);
        } else {
            return false;
        }
    }

    /**
     * 標示重複付款訂單已處理
     *
     * @param  string            $order_id
     * @return array|object|null
     */
    public function update_order_ecpay_orders_payment_status_complete($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        $result = $wpdb->get_results(
            $wpdb->prepare(
                "UPDATE $table_name
                SET is_completed_duplicate = 1, updated_at = CURRENT_TIMESTAMP
                WHERE order_id = %d AND is_completed_duplicate = 0",
                $order_id
            )
        );

        return $result;
    }

    /**
     * 更新訂單付款結果
     *
     * @param  string $order_id
     * @param  array  $info
     * @return void
     */
    public function update_order_ecpay_orders_payment_status($order_id, $info) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        // 模擬付款不更新付款狀態
        if (isset($info['SimulatePaid']) && $info['SimulatePaid'] == 0) {
            $wpdb->get_results(
                $wpdb->prepare(
                    "UPDATE $table_name
                    SET payment_status = %d, updated_at = CURRENT_TIMESTAMP
                    WHERE order_id = %d AND merchant_trade_no = %s AND is_completed_duplicate = 0",
                    $info['RtnCode'],
                    $order_id,
                    $info['MerchantTradeNo'],
                )
            );
        }
    }

    /**
     * 取得綠界金流
     *
     * @return array
     */
    public function get_ecpay_payment_method()
    {
        return [
            'Wooecpay_Gateway_Credit',
			'Wooecpay_Gateway_Credit_Installment',
			'Wooecpay_Gateway_Webatm',
			'Wooecpay_Gateway_Atm',
			'Wooecpay_Gateway_Cvs',
			'Wooecpay_Gateway_Barcode',
			'Wooecpay_Gateway_Applepay',
			'Wooecpay_Gateway_Dca',
			'Wooecpay_Gateway_Twqr',
			'Wooecpay_Gateway_Bnpl'
        ];
    }

    /**
     * 判斷是否為綠界金流
     *
     * @param  string $payment_method
     * @return bool
     */
    public function is_ecpay_payment_method($payment_method)
    {
        return in_array($payment_method, $this->get_ecpay_payment_method());
    }

    /**
	 * 檢查訂單是否重複付款
	 *
     * @param  WC_Order $order
	 * @return array
	 */
	public function check_order_is_duplicate_payment($order)
	{
        $is_duplicate_payment = 0; // 0:沒有重複付款紀錄、1:有重複付款紀錄
        $merchant_trade_no_list = [];

		// 取得訂單付款方式
		$payment_method = $order->get_payment_method();

        // 取得重複付款訂單的綠界金流特店交易編號
        $merchant_trade_no_list = $this->get_duplicate_payment_orders_merchant_trade_no($order->get_id());
        $count_merchant_trade_no = count($merchant_trade_no_list);

        // 僅檢查付款方式是綠界金流或貨到付款的訂單
		if ($this->is_ecpay_payment_method($payment_method) || $payment_method === 'cod') {
			// 超過 1 筆已付款的紀錄
			if ($count_merchant_trade_no > 1) {
				$is_duplicate_payment = 1;
			}
		}

        return [
            'code' => $is_duplicate_payment,
            'merchant_trade_no'  => $merchant_trade_no_list
        ];
	}
}
