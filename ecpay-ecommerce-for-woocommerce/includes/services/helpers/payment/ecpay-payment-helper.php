<?php
namespace Helpers\Payment;

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;
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

    public function add_type_info($input, $order, $has_block = 'true')
    {
        $payment_type = $this->get_ChoosePayment($order->get_payment_method());

        switch ($payment_type) {
            case 'Credit':
                // 信用卡分期
                if (function_exists('is_checkout')) {
                    if ($has_block == 'true') {
                        // (Woocommerce Blocks)信用卡分期
                        if ($order->get_payment_method() == 'Wooecpay_Gateway_Credit_Installment') {
                            $installmentDatas = get_option('woocommerce_Wooecpay_Gateway_Credit_Installment_settings', []);
                            $number_of_periods = isset($installmentDatas['number_of_periods']) ? $installmentDatas['number_of_periods'] : [];

                            if (!empty($number_of_periods) && empty(array_diff($number_of_periods, [3, 6, 12, 18, 24, 30]))) {
                                // 替換圓夢分期參數
                                foreach($number_of_periods as $key => $number_of_period) {
                                    if ($number_of_period == 30) {
                                        if ((int)$order->get_total() >= 20000) {
                                            $number_of_periods[$key] = '30N';
                                        }
                                        else unset($number_of_periods[$key]);
                                    }
                                }
                                $input['CreditInstallment'] = implode(',', $number_of_periods);
                            }
                        }

                        // (Woocommerce Blocks)定期定額參數
                        if ($order->get_payment_method() == 'Wooecpay_Gateway_Dca') {
                            $dca_periodtype = $order->get_meta('_ecpay_payment_dca_periodtype');
                            $dca_frequency = $order->get_meta('_ecpay_payment_dca_frequency');
                            $dca_exectimes = $order->get_meta('_ecpay_payment_dca_exectimes');

                            if (in_array($dca_periodtype, ['Y', 'M', 'D']) && trim($dca_frequency) !== '' && trim($dca_exectimes) !== '') {
                                $input['PeriodType'] = $dca_periodtype;
                                $input['Frequency'] = (int)$dca_frequency;
                                $input['ExecTimes'] = (int)$dca_exectimes;
                                $input['PeriodAmount'] = $input['TotalAmount'];
                                $input['PeriodReturnURL'] = $input['ReturnURL'];
                            }
                        }
                    }
                    else {
                        // (傳統短代碼)信用卡分期
                        $number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
                        if (in_array($number_of_periods, [3, 6, 12, 18, 24, 30])) {
                            $input['CreditInstallment'] = ($number_of_periods == 30) ? '30N' : $number_of_periods;

                            // 防止 hook 重複執行導致訂單歷程重複寫入
                            if (!get_transient('wooecpay_payment_installment_' . $order->get_id())) {
                                $order->add_order_note(sprintf(__('Credit installment to %d', 'ecpay-ecommerce-for-woocommerce'), $number_of_periods));
                                $order->save();
                                set_transient('wooecpay_payment_installment_' . $order->get_id(), true, 3600);
                            }
                            else delete_transient('wooecpay_payment_installment_' . $order->get_id());
                        }

                        // (傳統短代碼)定期定額參數
                        if ($order->get_payment_method() == 'Wooecpay_Gateway_Dca') {
                            $dca = $order->get_meta('_ecpay_payment_dca');
                            $dcaInfo = explode('_', $dca);
                            if (count($dcaInfo) > 1) {
                                $input['PeriodType'] = $dcaInfo[0];
                                $input['Frequency'] = (int)$dcaInfo[1];
                                $input['ExecTimes'] = (int)$dcaInfo[2];
                                $input['PeriodAmount'] = $input['TotalAmount'];
                                $input['PeriodReturnURL'] = $input['ReturnURL'];
                            }
                        }
                    }
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
            $sql = $wpdb->prepare(
                "UPDATE $table_name
                SET
                payment_status = %d,
                MerchantID = %s,
                MerchantTradeNo = %s,
                StoreID = %s,
                RtnCode = %d,
                RtnMsg = %s,
                TradeNo = %s,
                TradeAmt = %d,
                PaymentDate = %s,
                PaymentType = %s,
                PaymentTypeChargeFee = %d,
                PlatformID = %s,
                TradeDate = %s,
                SimulatePaid = %d,
                CustomField1 = %s,
                CustomField2 = %s,
                CustomField3 = %s,
                CustomField4 = %s,
                CheckMacValue = %s,
                eci = %d,
                card4no = %s,
                card6no = %s,
                process_date = %s,
                auth_code = %s,
                stage = %d,
                stast = %d,
                red_dan = %d,
                red_de_amt = %d,
                red_ok_amt = %d,
                red_yet = %d,
                gwsr = %d,
                PeriodType = %s,
                Frequency = %d,
                ExecTimes = %d,
                amount = %d,
                ProcessDate = %s,
                AuthCode = %s,
                FirstAuthAmount = %d,
                TotalSuccessTimes = %d,
                BankCode = %s,
                vAccount = %s,
                ATMAccNo = %s,
                ATMAccBank = %s,
                WebATMBankName = %s,
                WebATMAccNo = %s,
                WebATMAccBank = %s,
                PaymentNo = %s,
                ExpireDate = %s,
                Barcode1 = %s,
                Barcode2 = %s,
                Barcode3 = %s,
                BNPLTradeNo = %s,
                BNPLInstallment = %s,
                TWQRTradeNo = %s,
                updated_at = CURRENT_TIMESTAMP
                WHERE order_id = %d AND merchant_trade_no = %s AND is_completed_duplicate = 0",
                $info['RtnCode'],
                $info['MerchantID'],
                $info['MerchantTradeNo'],
                $info['StoreID'] ?? null,
                $info['RtnCode'],
                $info['RtnMsg'] ?? null,
                $info['TradeNo'] ?? null,
                $info['TradeAmt'] ?? null,
                $info['PaymentDate'] ?? null,
                $info['PaymentType'] ?? null,
                $info['PaymentTypeChargeFee'] ?? null,
                $info['PlatformID'] ?? null,
                $info['TradeDate'] ?? null,
                $info['SimulatePaid'] ?? null,
                $info['CustomField1'] ?? null,
                $info['CustomField2'] ?? null,
                $info['CustomField3'] ?? null,
                $info['CustomField4'] ?? null,
                $info['CheckMacValue'] ?? null,
                $info['eci'] ?? null,
                $info['card4no'] ?? null,
                $info['card6no'] ?? null,
                $info['process_date'] ?? null,
                $info['auth_code'] ?? null,
                $info['stage'] ?? null,
                $info['stast'] ?? null,
                $info['red_dan'] ?? null,
                $info['red_de_amt'] ?? null,
                $info['red_ok_amt'] ?? null,
                $info['red_yet'] ?? null,
                $info['gwsr'] ?? null,
                $info['PeriodType'] ?? null,
                $info['Frequency'] ?? null,
                $info['ExecTimes'] ?? null,
                $info['amount'] ?? null,
                $info['ProcessDate'] ?? null,
                $info['AuthCode'] ?? null,
                $info['FirstAuthAmount'] ?? null,
                $info['TotalSuccessTimes'] ?? null,
                $info['BankCode'] ?? null,
                $info['vAccount'] ?? null,
                $info['ATMAccNo'] ?? null,
                $info['ATMAccBank'] ?? null,
                $info['WebATMBankName'] ?? null,
                $info['WebATMAccNo'] ?? null,
                $info['WebATMAccBank'] ?? null,
                $info['PaymentNo'] ?? null,
                $info['ExpireDate'] ?? null,
                $info['Barcode1'] ?? null,
                $info['Barcode2'] ?? null,
                $info['Barcode3'] ?? null,
                $info['BNPLTradeNo'] ?? null,
                $info['BNPLInstallment'] ?? null,
                $info['TWQRTradeNo'] ?? null,
                $order_id,
                $info['MerchantTradeNo']
            );

            $wpdb->query($sql);
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

    /**
     * 檢查定期定額付款資訊 TotalSuccessTimes 是否重複付款
     */
    public function check_dca_max_total_success_times($merchant_trade_no)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';

        // 查詢綠界資料表中是否有該 MerchantTradeNo 紀錄
        $total_success_times = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT TotalSuccessTimes
                FROM $table_name
                WHERE MerchantTradeNo = %s AND PaymentType = %s
                ORDER BY TotalSuccessTimes DESC
                LIMIT 1",
                $merchant_trade_no,
                'Wooecpay_Gateway_Dca'
            )
        );

        if ($total_success_times) return $total_success_times;
        else {
            // 尋找 wp_post 資料表中舊版訂單 note
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = '_wooecpay_payment_merchant_trade_no'
                    AND meta_value = %s",
                    $merchant_trade_no
                )
            );

            if ($post_id) {
                // 查詢相關備註
                $comments = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT comment_content
                        FROM {$wpdb->comments}
                        WHERE comment_post_ID = %d
                          AND comment_type = 'order_note'",
                        $post_id
                    )
                );

                // 包含 "定期定額付款第N次繳費成功" 的備註
                $max_n = 0;
                foreach ($comments as $comment) {
                    if (preg_match('/定期定額付款第(\d+)次繳費成功/', $comment->comment_content, $matches)) {
                        $n = (int)$matches[1];
                        if ($n > $max_n) {
                            $max_n = $n;
                        }
                    }
                }
                return $max_n;
            }
        }
        return 0;
    }
}
