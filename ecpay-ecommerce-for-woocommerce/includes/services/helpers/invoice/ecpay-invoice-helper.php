<?php
namespace Helpers\Invoice;

use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Factories\Factory;

class Wooecpay_Invoice_Helper {
    /**
     * 發票開立方式代碼-個人
     */
    const INVOICE_TYPE_PERSONAL = 'p';

    /**
     * 發票開立方式代碼-公司
     */
    const INVOICE_TYPE_COMPANY = 'c';

    /**
     * 發票開立方式代碼-捐贈
     */
    const INVOICE_TYPE_DONATE = 'd';

    /**
     * 載具類別代碼-索取紙本
     */
    const INVOICE_CARRUER_TYPE_PAPER = '0';

    /**
     * 載具類別代碼-雲端發票(中獎寄送紙本)
     */
    const INVOICE_CARRUER_TYPE_CLOUD = '1';

    /**
     * 載具類別代碼-自然人憑證
     */
    const INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID = '2';

    /**
     * 載具類別代碼-手機條碼
     */
    const INVOICE_CARRUER_TYPE_MOBILE_BARCODE = '3';

    /**
     * 發票開立方式
     *
     * @var array
     */
    public $invoiceType = [
        self::INVOICE_TYPE_PERSONAL => '個人',
        self::INVOICE_TYPE_COMPANY  => '公司',
        self::INVOICE_TYPE_DONATE   => '捐贈',
    ];

    /**
     * 載具類別
     *
     * @var array
     */
    public $invoiceCarruerType = [
        self::INVOICE_CARRUER_TYPE_PAPER             => '索取紙本',
        self::INVOICE_CARRUER_TYPE_CLOUD             => '雲端發票(中獎寄送紙本)',
        self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID => '自然人憑證',
        self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE    => '手機條碼',
    ];

    /**
     * 取得綠界發票 API 介接資訊
     *
     * @param  string $action
     * @return array  $api_info
     */
    public function get_ecpay_invoice_api_info($action = '') {
        $api_info = [
            'merchant_id' => '',
            'hashKey'     => '',
            'hashIv'      => '',
            'action'      => '',
        ];

        // 介接資訊
        if ('yes' === get_option('wooecpay_enabled_invoice_stage', 'yes')) {
            $api_info = [
                'merchant_id' => '2000132',
                'hashKey'     => 'ejCk326UnaZWKisg ',
                'hashIv'      => 'q9jcZX8Ib9LM8wYk',
            ];
        } else {
            $merchant_id = get_option('wooecpay_invoice_mid');
            $hash_key    = get_option('wooecpay_invoice_hashkey');
            $hash_iv     = get_option('wooecpay_invoice_hashiv');

            $api_info = [
                'merchant_id' => $merchant_id,
                'hashKey'     => $hash_key,
                'hashIv'      => $hash_iv,
            ];
        }

        // API URL
        if ('yes' === get_option('wooecpay_enabled_invoice_stage', 'yes')) {
            switch ($action) {
            case 'check_Love_code':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode';
                break;
            case 'check_barcode':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode';
                break;
            case 'issue':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue';
                break;
            case 'delay_issue':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue';
                break;
            case 'invalid':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid';
                break;
            case 'cancel_delay_issue':
                $api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
                break;
            default:
                break;
            }
        } else {
            switch ($action) {
            case 'check_Love_code':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode';
                break;
            case 'check_barcode':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode';
                break;
            case 'issue':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue';
                break;
            case 'delay_issue':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue';
                break;
            case 'invalid':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid';
                break;
            case 'cancel_delay_issue':
                $api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
                break;
            default:
                break;
            }
        }

        return $api_info;
    }

    /**
     * 利用 od_sob 找出 order id
     *
     * @param  array    $info
     * @return int|bool
     */
    public function get_order_id($info) {
        $order_prefix = get_option('wooecpay_invoice_prefix');

        if (isset($info['od_sob'])) {

            $order_id = substr($info['od_sob'], strlen($order_prefix), strrpos($info['od_sob'], 'SN'));

            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }

        return false;
    }

    /**
     * 取得發票自訂編號
     *
     * @param  string $order_id
     * @param  string $order_prefix
     * @return string
     */
    public function get_relate_number($order_id, $order_prefix = '') {
        $relate_no = $order_prefix . substr(str_pad($order_id, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5);
        return substr($relate_no, 0, 20);
    }

    /**
     * 結帳過程欄位檢查
     *
     * @param  array  $fields
     * @param  array  $switch
     */
    public function check_invoice_fields($fields, $switch) {
        // 發票開立方式
        if (isset($fields['wooecpay_invoice_type'])) {
            $wooecpay_invoice_type = sanitize_text_field($fields['wooecpay_invoice_type']);

            switch ($wooecpay_invoice_type) {
            case self::INVOICE_TYPE_COMPANY:
                // 公司
                $wooecpay_invoice_customer_identifier = sanitize_text_field($fields['wooecpay_invoice_customer_identifier']);
                $wooecpay_invoice_customer_company    = sanitize_text_field($fields['wooecpay_invoice_customer_company']);
                $wooecpay_invoice_carruer_type        = sanitize_text_field($fields['wooecpay_invoice_carruer_type']);

                $response = $this->check_customer_identifier($wooecpay_invoice_customer_identifier, $wooecpay_invoice_carruer_type, $wooecpay_invoice_customer_company);

                if ($response['code'] !== '1') {
                    wc_add_notice($response['msg'], 'error');
                }
                break;
            case self::INVOICE_TYPE_DONATE:
                // 捐贈
                $wooecpay_invoice_love_code = sanitize_text_field($fields['wooecpay_invoice_love_code']);

                $response = $this->check_love_code($wooecpay_invoice_love_code, $switch['billing_love_code_api_check']);

                if ($response['code'] !== '1') {
                    wc_add_notice($response['msg'], 'error');
                }
                break;
            }
        }

        // 載具類別
        if (isset($fields['wooecpay_invoice_carruer_type'])) {
            $wooecpay_invoice_carruer_type = sanitize_text_field($fields['wooecpay_invoice_carruer_type']);
            $wooecpay_invoice_carruer_num  = sanitize_text_field($fields['wooecpay_invoice_carruer_num']);

            switch ($wooecpay_invoice_carruer_type) {
            case self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID:
                // 自然人憑證驗證
                $response = $this->check_citizen_digital_certificate($wooecpay_invoice_carruer_num);
                if ($response['code'] !== '1') {
                    wc_add_notice($response['msg'], 'error');
                }
                break;
            case self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE:
                // 手機載具驗證
                $response = $this->check_phone_barcode($wooecpay_invoice_carruer_num, $switch['billing_carruer_num_api_check']);
                if ($response['code'] !== '1') {
                    wc_add_notice($response['msg'], 'error');
                }
                break;
            }
        }
    }

    /**
     * 統一編號驗證
     *
     * @param  string $customer_identifier
     * @param  string $carruer_type
     * @param  string $customer_company
     * @return array  $result
     */
    public function check_customer_identifier($customer_identifier, $carruer_type, $customer_company) {
        // 預設驗證結果
        $result = [
            'code' => '1',
            'msg'  => '',
        ];

        if ($customer_identifier == '') {
            $result['code'] = '1010';
            $result['msg']  = __('Please input Unified Business NO', 'ecpay-ecommerce-for-woocommerce');
        } else {
            if (!preg_match('/^[0-9]{8}$/', $customer_identifier)) {
                $result['code'] = '1011';
                $result['msg']  = __('Invalid tax ID number', 'ecpay-ecommerce-for-woocommerce');
            }

            if ($carruer_type == '0' && empty($customer_company)) {
                $result['code'] = '1012';
                $result['msg']  = __('Please input the company name', 'ecpay-ecommerce-for-woocommerce');
            }
        }

        return $result;
    }

    /**
     * 捐贈碼驗證
     *
     * @param  string $love_code
     * @param  bool   $switch
     * @return array  $result
     */
    public function check_love_code($love_code, $switch) {
        // 預設驗證結果
        $result = [
            'code' => '1',
            'msg'  => '',
        ];

        if ($love_code == '') {
            $result['code'] = '1020';
            $result['msg']  = __('Please input Donate number', 'ecpay-ecommerce-for-woocommerce');
        } else {
            if (!preg_match('/^([xX]{1}[0-9]{2,6}|[0-9]{3,7})$/', $love_code)) {
                $result['code'] = '1021';
                $result['msg']  = __('Invalid Donate number', 'ecpay-ecommerce-for-woocommerce');
            } else {
                // 呼叫 SDK 捐贈碼驗證
                if ($switch) {
                    $api_payment_info = $this->get_ecpay_invoice_api_info('check_Love_code');

                    try {
                        $factory = new Factory([
                            'hashKey' => $api_payment_info['hashKey'],
                            'hashIv'  => $api_payment_info['hashIv'],
                        ]);

                        $postService = $factory->create('PostWithAesJsonResponseService');

                        $data = [
                            'MerchantID' => $api_payment_info['merchant_id'],
                            'LoveCode'   => $love_code,
                        ];
                        $input = [
                            'MerchantID' => $api_payment_info['merchant_id'],
                            'RqHeader'   => [
                                'Timestamp' => time(),
                                'Revision'  => '3.0.0',
                            ],
                            'Data'       => $data,
                        ];

                        $response = $postService->post($input, $api_payment_info['action']);

                        // 呼叫財政部API失敗
                        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
                            $result['code'] = '1022';
                            $result['msg']  = __('Ministry of Finance system is currently under maintenance, unable to verify the carrier. Please choose another invoice mode.', 'ecpay-ecommerce-for-woocommerce');
                        }

                        // SDK 捐贈碼驗證失敗
                        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
                            $result['code'] = '1023';
                            $result['msg']  = __('Please Check Donate number', 'ecpay-ecommerce-for-woocommerce');
                        }
                    } catch (RtnException $e) {
                        $result['code'] = '1029';
                        $result['msg']  = wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 自然人憑證驗證
     *
     * @param  string $carruer_num
     * @return array  $result
     */
    public function check_citizen_digital_certificate($carruer_num) {
        // 預設驗證結果
        $result = [
            'code' => '1',
            'msg'  => '',
        ];

        if ($carruer_num == '') {
            $result['code'] = '1030';
            $result['msg']  = __('Please input Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce');
        } else {
            if (!preg_match('/^[a-zA-Z]{2}\d{14}$/', $carruer_num)) {
                $result['code'] = '1031';
                $result['msg']  = __('Invalid Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce');
            }
        }

        return $result;
    }

    /**
     * 手機條碼驗證
     *
     * @param  string $carruer_num
     * @param  bool   $switch
     * @return array  $result
     */
    public function check_phone_barcode($carruer_num, $switch) {
        // 預設驗證結果
        $result = [
            'code' => '1',
            'msg'  => '',
        ];

        if ($carruer_num == '') {
            $result['code'] = '1040';
            $result['msg']  = __('Please input phone barcode', 'ecpay-ecommerce-for-woocommerce');
        } else {
            if (!preg_match('/^\/{1}[0-9a-zA-Z+-.]{7}$/', $carruer_num)) {
                $result['code'] = '1041';
                $result['msg']  = __('Invalid phone barcode', 'ecpay-ecommerce-for-woocommerce');
            } else {
                // 呼叫 SDK 手機條碼驗證
                if ($switch) {
                    $api_payment_info = $this->get_ecpay_invoice_api_info('check_barcode');

                    try {
                        $factory = new Factory([
                            'hashKey' => $api_payment_info['hashKey'],
                            'hashIv'  => $api_payment_info['hashIv'],
                        ]);

                        $postService = $factory->create('PostWithAesJsonResponseService');

                        $data = [
                            'MerchantID' => $api_payment_info['merchant_id'],
                            'BarCode'    => $carruer_num,
                        ];

                        $input = [
                            'MerchantID' => $api_payment_info['merchant_id'],
                            'RqHeader'   => [
                                'Timestamp' => time(),
                                'Revision'  => '3.0.0',
                            ],
                            'Data'       => $data,
                        ];

                        $response = $postService->post($input, $api_payment_info['action']);

                        // 呼叫財政部API失敗
                        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
                            $result['code'] = '1042';
                            $result['msg']  = __('Ministry of Finance system is currently under maintenance, unable to verify the carrier. Please choose another invoice mode.', 'ecpay-ecommerce-for-woocommerce');
                        }

                        // SDK 手機條碼驗證失敗
                        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
                            $result['code'] = '1043';
                            $result['msg']  = __('Please Check phone barcode', 'ecpay-ecommerce-for-woocommerce');
                        }
                    } catch (RtnException $e) {
                        $result['code'] = '1049';
                        $result['msg']  = wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 開立發票程序
     *
     * @param  WC_Order $order
     * @return void
     */
    public function invoice_create($order) {

        // 判斷發票是否存在，不存在則開立
        $wooecpay_invoice_process = $order->get_meta('_wooecpay_invoice_process', true);

        if (empty($wooecpay_invoice_process)) {
            $wooecpay_invoice_dalay_date = get_option('wooecpay_invoice_dalay_date');
            $wooecpay_invoice_dalay_date = (int) $wooecpay_invoice_dalay_date;

            // 取得付款方式，判斷是否紀錄發票備註
            $payment_method = $order->get_meta('_payment_method', true);

            if (empty($wooecpay_invoice_dalay_date)) {
                // 立即開立
                $api_payment_info = $this->get_ecpay_invoice_api_info('issue');
                $relateNumber     = $this->get_relate_number($order->get_id(), get_option('wooecpay_invoice_prefix'));

                try {
                    $factory = new Factory([
                        'hashKey' => $api_payment_info['hashKey'],
                        'hashIv'  => $api_payment_info['hashIv'],
                    ]);

                    $postService = $factory->create('PostWithAesJsonResponseService');

                    $items = [];
                    foreach ($order->get_items() as $item) {

                        $item_price  = round(($item->get_total() + $item->get_total_tax()) / $item->get_quantity(), 4);
                        $item_amount = round($item_price * $item->get_quantity(), 2);
                        $items[]     = [
                            'ItemName'    => mb_substr($item->get_name(), 0, 100),
                            'ItemCount'   => $item->get_quantity(),
                            'ItemWord'    => '批',
                            'ItemPrice'   => $item_price,
                            'ItemTaxType' => '1',
                            'ItemAmount'  => $item_amount,
                        ];
                    }

                    // 物流費用
                    $shipping_fee = $order->get_shipping_total() + $order->get_shipping_tax();
                    if ($shipping_fee != 0) {

                        $items[] = [
                            'ItemName'    => __('Shipping fee', 'ecpay-ecommerce-for-woocommerce'),
                            'ItemCount'   => 1,
                            'ItemWord'    => '批',
                            'ItemPrice'   => $shipping_fee,
                            'ItemTaxType' => '1',
                            'ItemAmount'  => $shipping_fee,
                        ];
                    }

                    $country      = $order->get_billing_country();
                    $countries    = WC()->countries->get_countries();
                    $full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

                    $state      = $order->get_billing_state();
                    $states     = WC()->countries->get_states($country);
                    $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

                    $data = [
                        'MerchantID'    => $api_payment_info['merchant_id'],
                        'RelateNumber'  => $relateNumber,
                        'CustomerID'    => '',
                        'CustomerName'  => $order->get_billing_last_name() . $order->get_billing_first_name(),
                        'CustomerAddr'  => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
                        'CustomerPhone' => $order->get_billing_phone(),
                        'CustomerEmail' => $order->get_billing_email(),
                        'Print'         => '0',
                        'Donation'      => '0',
                        'LoveCode'      => '',
                        'CarrierType'   => '',
                        'CarrierNum'    => '',
                        'TaxType'       => '1',
                        'SalesAmount'   => intval(round($order->get_total(), 0)),
                        'Items'         => $items,
                        'InvType'       => '07',
                    ];

                    // 記錄發票備註，卡號末四碼
                    if (in_array($payment_method, array('Wooecpay_Gateway_Credit_Installment', 'Wooecpay_Gateway_Credit', 'Wooecpay_Gateway_Dca'))) {
                        $data['InvoiceRemark'] = '信用卡末四碼' . $order->get_meta('_ecpay_card4no', true);
                    }

                    $wooecpay_invoice_type         = $order->get_meta('_wooecpay_invoice_type', true);
                    $wooecpay_invoice_carruer_type = $order->get_meta('_wooecpay_invoice_carruer_type', true);

                    switch ($wooecpay_invoice_type) {
                    case self::INVOICE_TYPE_PERSONAL:
                        switch ($wooecpay_invoice_carruer_type) {
                        case self::INVOICE_CARRUER_TYPE_CLOUD:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_CLOUD;
                            break;
                        case self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID;
                            $data['CarrierNum']  = $order->get_meta('_wooecpay_invoice_carruer_num', true);
                            break;
                        case self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE;
                            $data['CarrierNum']  = $order->get_meta('_wooecpay_invoice_carruer_num', true);
                            break;
                        default:
                            $data['Print'] = '1';
                            break;
                        }
                        break;
                    case self::INVOICE_TYPE_COMPANY:
                        $data['Print']              = '1';
                        $data['CustomerIdentifier'] = $order->get_meta('_wooecpay_invoice_customer_identifier', true);
                        $company                    = $order->get_meta('_wooecpay_invoice_customer_company', true);
                        if ($company) {
                            $data['CustomerName'] = $company;
                        }
                        switch ($wooecpay_invoice_carruer_type) {
                        case self::INVOICE_CARRUER_TYPE_CLOUD:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_CLOUD;
                            break;
                        case self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE;
                            $data['CarrierNum']  = $order->get_meta('_wooecpay_invoice_carruer_num', true);
                            break;
                        }
                        break;
                    case self::INVOICE_TYPE_DONATE:
                        $data['Donation'] = '1';
                        $data['LoveCode'] = $order->get_meta('_wooecpay_invoice_love_code', true);
                        break;
                    }

                    $input = [
                        'MerchantID' => $api_payment_info['merchant_id'],
                        'RqHeader'   => [
                            'Timestamp' => time(),
                            'Revision'  => '3.0.0',
                        ],
                        'Data'       => $data,
                    ];

                    ecpay_log('送出立即開立發票請求 ' . print_r(ecpay_log_replace_symbol('invoice', $input), true), 'C00005', $order->get_id());

                    $response = $postService->post($input, $api_payment_info['action']);

                    ecpay_log('立即開立發票結果回傳 ' . print_r(ecpay_log_replace_symbol('invoice', $response), true), 'C00020', $order->get_id());

                    if ($response['TransCode'] == 1) {
                        if ($response['Data']['RtnCode'] == 1) {
                            // 更新訂單
                            $order->update_meta_data('_wooecpay_invoice_relate_number', $relateNumber);
                            $order->update_meta_data('_wooecpay_invoice_RtnCode', $response['Data']['RtnCode']);
                            $order->update_meta_data('_wooecpay_invoice_RtnMsg', $response['Data']['RtnMsg']);
                            $order->update_meta_data('_wooecpay_invoice_no', $response['Data']['InvoiceNo']);
                            $order->update_meta_data('_wooecpay_invoice_date', $response['Data']['InvoiceDate']);
                            $order->update_meta_data('_wooecpay_invoice_random_number', $response['Data']['RandomNumber']);

                            $order->update_meta_data('_wooecpay_invoice_process', 1); // 執行開立完成
                            $order->update_meta_data('_wooecpay_invoice_issue_type', 1); // 開立類型 1.立即開立 2.延遲開立

                            $order->add_order_note('發票開立成功:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                            $order->save();

                            ecpay_log('立即開立發票成功', 'C00006', $order->get_id());
                        } else {
                            $order->add_order_note('發票開立失敗:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                            $order->save();

                            ecpay_log('立即開立發票失敗', 'C00007', $order->get_id());
                        }
                    } else {
                        $order->add_order_note('發票開立失敗:狀態:' . $response['TransMsg'] . '(' . $response['TransCode'] . ')');
                        $order->save();

                        ecpay_log('立即開立發票失敗', 'C00008', $order->get_id());
                    }
                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'C90005', $order->get_id());
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
            } else {
                // 延遲開立
                $api_payment_info = $this->get_ecpay_invoice_api_info('delay_issue');
                $relateNumber     = $this->get_relate_number($order->get_id(), get_option('wooecpay_invoice_prefix'));

                try {
                    $factory = new Factory([
                        'hashKey' => $api_payment_info['hashKey'],
                        'hashIv'  => $api_payment_info['hashIv'],
                    ]);

                    $postService = $factory->create('PostWithAesJsonResponseService');

                    $items = [];

                    foreach ($order->get_items() as $item) {

                        $items[] = [
                            'ItemName'    => mb_substr($item->get_name(), 0, 100),
                            'ItemCount'   => $item->get_quantity(),
                            'ItemWord'    => '批',
                            'ItemPrice'   => round($item->get_total() / $item->get_quantity(), 4),
                            'ItemTaxType' => '1',
                            'ItemAmount'  => round($item->get_total(), 2),
                        ];
                    }

                    // 物流費用
                    $shipping_fee = $order->get_shipping_total();
                    if ($shipping_fee != 0) {
                        $items[] = [
                            'ItemName'    => __('Shipping fee', 'ecpay-ecommerce-for-woocommerce'),
                            'ItemCount'   => 1,
                            'ItemWord'    => '批',
                            'ItemPrice'   => $shipping_fee,
                            'ItemTaxType' => '1',
                            'ItemAmount'  => $shipping_fee,
                        ];
                    }

                    $country      = $order->get_billing_country();
                    $countries    = WC()->countries->get_countries();
                    $full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

                    $state      = $order->get_billing_state();
                    $states     = WC()->countries->get_states($country);
                    $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

                    $data = [
                        'MerchantID'    => $api_payment_info['merchant_id'],
                        'RelateNumber'  => $relateNumber,
                        'CustomerID'    => '',
                        'CustomerName'  => $order->get_billing_last_name() . $order->get_billing_first_name(),
                        'CustomerAddr'  => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
                        'CustomerPhone' => $order->get_billing_phone(),
                        'CustomerEmail' => $order->get_billing_email(),
                        'Print'         => '0',
                        'Donation'      => '0',
                        'LoveCode'      => '',
                        'CarrierType'   => '',
                        'CarrierNum'    => '',
                        'TaxType'       => '1',
                        'SalesAmount'   => intval(round($order->get_total(), 0)),
                        'Items'         => $items,
                        'InvType'       => '07',

                        'DelayFlag'     => '1',
                        'DelayDay'      => $wooecpay_invoice_dalay_date,
                        'Tsr'           => $relateNumber,
                        'PayType'       => '2',
                        'PayAct'        => 'ECPAY',
                        'NotifyURL'     => WC()->api_request_url('wooecpay_invoice_delay_issue_callback', true),
                    ];

                    // 記錄發票備註，卡號末四碼
                    if (in_array($payment_method, array('Wooecpay_Gateway_Credit_Installment', 'Wooecpay_Gateway_Credit', 'Wooecpay_Gateway_Dca'))) {
                        $data['InvoiceRemark'] = '信用卡末四碼' . $order->get_meta('_ecpay_card4no', true);
                    }

                    $wooecpay_invoice_type         = $order->get_meta('_wooecpay_invoice_type', true);
                    $wooecpay_invoice_carruer_type = $order->get_meta('_wooecpay_invoice_carruer_type', true);

                    switch ($wooecpay_invoice_type) {
                    case self::INVOICE_TYPE_PERSONAL:
                        switch ($wooecpay_invoice_carruer_type) {
                        case self::INVOICE_CARRUER_TYPE_CLOUD:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_CLOUD;
                            break;
                        case self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_NATURAL_PERSON_ID;
                            $data['CarrierNum']  = $order->get_meta('_wooecpay_invoice_carruer_num', true);
                            break;
                        case self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE:
                            $data['CarrierType'] = self::INVOICE_CARRUER_TYPE_MOBILE_BARCODE;
                            $data['CarrierNum']  = $order->get_meta('_wooecpay_invoice_carruer_num', true);
                            break;
                        default:
                            $data['Print'] = '1';
                            break;
                        }
                        break;
                    case self::INVOICE_TYPE_COMPANY:
                        $data['Print']              = '1';
                        $data['CustomerIdentifier'] = $order->get_meta('_wooecpay_invoice_customer_identifier', true);
                        $company                    = $order->get_meta('_wooecpay_invoice_customer_company', true);
                        if ($company) {
                            $data['CustomerName'] = $company;
                        }
                        break;
                    case self::INVOICE_TYPE_DONATE:
                        $data['Donation'] = '1';
                        $data['LoveCode'] = $order->get_meta('_wooecpay_invoice_love_code', true);
                        break;
                    }

                    $input = [
                        'MerchantID' => $api_payment_info['merchant_id'],
                        'RqHeader'   => [
                            'Timestamp' => time(),
                            'Revision'  => '3.0.0',
                        ],
                        'Data'       => $data,
                    ];

                    ecpay_log('送出延遲開立發票請求 ' . print_r(ecpay_log_replace_symbol('invoice', $input), true), 'C00009', $order->get_id());

                    $response = $postService->post($input, $api_payment_info['action']);

                    ecpay_log('延遲開立發票結果回傳 ' . print_r($response, true), 'C00021', $order->get_id());

                    if ($response['TransCode'] == 1) {
                        if ($response['Data']['RtnCode'] == 1) {
                            // 更新訂單
                            $order->update_meta_data('_wooecpay_invoice_relate_number', $relateNumber);
                            $order->update_meta_data('_wooecpay_invoice_RtnCode', $response['Data']['RtnCode']);
                            $order->update_meta_data('_wooecpay_invoice_RtnMsg', $response['Data']['RtnMsg']);

                            $order->update_meta_data('_wooecpay_invoice_process', 1); // 執行開立完成
                            $order->update_meta_data('_wooecpay_invoice_issue_type', 2); // 開立類型 1.立即開立 2.延遲開立
                            $order->update_meta_data('_wooecpay_invoice_tsr', $relateNumber); // 交易單號

                            $order->add_order_note('發票開立成功:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                            $order->save();

                            ecpay_log('延遲開立發票成功', 'C00010', $order->get_id());
                        } else {
                            $order->add_order_note('發票開立失敗:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                            $order->save();

                            ecpay_log('延遲開立發票失敗', 'C00011', $order->get_id());
                        }
                    } else {
                        $order->add_order_note('發票開立失敗:狀態:' . $response['TransMsg'] . '(' . $response['TransCode'] . ')');
                        $order->save();

                        ecpay_log('延遲開立發票失敗', 'C00012', $order->get_id());
                    }
                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'C90009', $order->get_id());
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
            }
        }
    }

    /**
     * 作廢發票程序
     *
     * @param  WC_Order $order
     * @return void
     */
    public function invoice_invalid($order) {
        // 判斷發票是否存在，存在則才可以執行作廢
        $wooecpay_invoice_process = $order->get_meta('_wooecpay_invoice_process', true);

        if ($wooecpay_invoice_process == 1) {
            // 取得開立類型(1.立即開立 2.延遲開立)
            $wooecpay_invoice_issue_type = $order->get_meta('_wooecpay_invoice_issue_type', true);

            if ($wooecpay_invoice_issue_type == 1) {
                $api_payment_info = $this->get_ecpay_invoice_api_info('invalid');

                $wooecpay_invoice_no   = $order->get_meta('_wooecpay_invoice_no', true);
                $wooecpay_invoice_date = $order->get_meta('_wooecpay_invoice_date', true);

                // 作廢發票
                try {
                    $factory = new Factory([
                        'hashKey' => $api_payment_info['hashKey'],
                        'hashIv'  => $api_payment_info['hashIv'],
                    ]);

                    $postService = $factory->create('PostWithAesJsonResponseService');

                    $data = [
                        'MerchantID'  => $api_payment_info['merchant_id'],
                        'InvoiceNo'   => $wooecpay_invoice_no,
                        'InvoiceDate' => $wooecpay_invoice_date,
                        'Reason'      => __('Invalid invoice', 'ecpay-ecommerce-for-woocommerce'),
                    ];

                    $input = [
                        'MerchantID' => $api_payment_info['merchant_id'],
                        'RqHeader'   => [
                            'Timestamp' => time(),
                            'Revision'  => '3.0.0',
                        ],
                        'Data'       => $data,
                    ];

                    ecpay_log('送出立即開立發票作廢請求 ' . print_r(ecpay_log_replace_symbol('invoice', $input), true), 'C00013', $order->get_id());

                    $response = $postService->post($input, $api_payment_info['action']);

                    ecpay_log('立即開立發票作廢結果回傳 ' . print_r(ecpay_log_replace_symbol('invoice', $response), true), 'C00022', $order->get_id());

                    if ($response['Data']['RtnCode'] == 1 || $response['Data']['RtnCode'] == 5070453) {
                        // 更新訂單
                        $order->update_meta_data('_wooecpay_invoice_relate_number', '');
                        $order->update_meta_data('_wooecpay_invoice_RtnCode', '');
                        $order->update_meta_data('_wooecpay_invoice_RtnMsg', '');
                        $order->update_meta_data('_wooecpay_invoice_no', '');
                        $order->update_meta_data('_wooecpay_invoice_date', '');
                        $order->update_meta_data('_wooecpay_invoice_random_number', '');

                        $order->update_meta_data('_wooecpay_invoice_process', 0); // 執行開立完成
                        $order->update_meta_data('_wooecpay_invoice_issue_type', ''); // 開立類型 1.立即開立 2.延遲開立

                        $order->add_order_note('發票作廢成功: 發票號碼:' . $wooecpay_invoice_no . ' 狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                        $order->save();

                        ecpay_log('立即開立發票作廢成功', 'C00014', $order->get_id());
                    } else {
                        $order->add_order_note('發票作廢失敗:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                        $order->save();

                        ecpay_log('立即開立發票作廢失敗', 'C00015', $order->get_id());
                    }
                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'C90013', $order->get_id());
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
            } else if ($wooecpay_invoice_issue_type == 2) {
                $api_payment_info     = $this->get_ecpay_invoice_api_info('cancel_delay_issue');
                $wooecpay_invoice_tsr = $order->get_meta('_wooecpay_invoice_tsr', true);

                try {
                    $factory = new Factory([
                        'hashKey' => $api_payment_info['hashKey'],
                        'hashIv'  => $api_payment_info['hashIv'],
                    ]);

                    $postService = $factory->create('PostWithAesJsonResponseService');
                    $data        = [
                        'MerchantID' => $api_payment_info['merchant_id'],
                        'Tsr'        => $wooecpay_invoice_tsr,
                    ];

                    $input = [
                        'MerchantID' => $api_payment_info['merchant_id'],
                        'RqHeader'   => [
                            'Timestamp' => time(),
                            'Revision'  => '3.0.0',
                        ],
                        'Data'       => $data,
                    ];

                    ecpay_log('送出延遲開立發票作廢請求 ' . print_r(ecpay_log_replace_symbol('invoice', $input), true), 'C00016', $order->get_id());

                    $response = $postService->post($input, $api_payment_info['action']);

                    ecpay_log('延遲開立發票作廢結果回傳 ' . print_r(ecpay_log_replace_symbol('invoice', $input), true), 'C00023', $order->get_id());

                    if ($response['Data']['RtnCode'] == 1) {
                        // 更新訂單
                        $order->update_meta_data('_wooecpay_invoice_relate_number', '');
                        $order->update_meta_data('_wooecpay_invoice_RtnCode', '');
                        $order->update_meta_data('_wooecpay_invoice_RtnMsg', '');
                        $order->update_meta_data('_wooecpay_invoice_no', '');
                        $order->update_meta_data('_wooecpay_invoice_date', '');
                        $order->update_meta_data('_wooecpay_invoice_random_number', '');
                        $order->update_meta_data('_wooecpay_invoice_tsr', ''); // 交易單號

                        $order->update_meta_data('_wooecpay_invoice_process', 0); // 執行開立完成
                        $order->update_meta_data('_wooecpay_invoice_issue_type', ''); // 開立類型 1.立即開立 2.延遲開立

                        $order->add_order_note('發票作廢成功: 交易單號:' . $wooecpay_invoice_tsr . ' 狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');

                        $order->save();

                        ecpay_log('延遲開立發票作廢成功', 'C00017', $order->get_id());
                    } else {
                        $order->add_order_note('發票作廢失敗:狀態:' . $response['Data']['RtnMsg'] . '(' . $response['Data']['RtnCode'] . ')');
                        $order->save();

                        ecpay_log('延遲開立發票作廢失敗', 'C00018', $order->get_id());
                    }
                } catch (RtnException $e) {
                    ecpay_log('[Exception] (' . $e->getCode() . ')' . $e->getMessage(), 'C90016', $order->get_id());
                    echo wp_kses_post('(' . $e->getCode() . ')' . $e->getMessage()) . PHP_EOL;
                }
            }
        }
    }
}
