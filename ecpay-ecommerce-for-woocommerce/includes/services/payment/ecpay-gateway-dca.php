<?php

class Wooecpay_Gateway_Dca extends Wooecpay_Gateway_Base
{
    protected $payment_type;
    protected $min_amount;
    protected $ecpay_dca_payment;
    protected $ecpay_dca_options;

    public function __construct()
    {
        $this->id                   = 'Wooecpay_Gateway_Dca';
        $this->payment_type         = 'Credit';
        $this->icon                 = plugins_url('images/icon.png', dirname(dirname( __FILE__ )));
        $this->has_fields           = true;
        $this->method_title         = __('ECPay DCA', 'ecpay-ecommerce-for-woocommerce');
        $this->method_description   = '使用綠界定期定額付款';

        $this->enabled              = $this->get_option('enabled');
        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->min_amount           = (int) $this->get_option('min_amount', 0);
        $this->max_amount           = (int) $this->get_option('max_amount', 0);

        $this->form_fields          = include WOOECPAY_PLUGIN_INCLUDE_DIR . '/config/payment/settings-gateway-dca.php' ;

        // 預設情定額付款方式
        $this->ecpay_dca_payment = $this->get_ecpay_dca_payment();
        $this->ecpay_dca_options = get_option('woocommerce_ecpay_dca',
            array(
                array(
                    'periodType' => $this->get_option('periodType'),
                    'frequency'  => $this->get_option('frequency'),
                    'execTimes'  => $this->get_option('execTimes'),
                ),
            )
        );

        $this->init_settings();

        parent::__construct();

        add_action('admin_enqueue_scripts' , array($this, 'wooecpay_register_scripts'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_dca_details'));
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    /**
     * 前台 - 顯示定期定額付款選項
     */
    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();
            if ($total > 0) {
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 && $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    /**
     * 前台 - 顯示定期定額付款方式select
     */
    public function payment_fields()
    {
        parent::payment_fields();
        $total = $this->get_order_total();

        if (is_checkout() && !is_wc_endpoint_url('order-pay') ) {
            $data = array(
                'ecpay_dca_options'  => get_option('woocommerce_ecpay_dca', []),
                'cart_total' => $total
            );
            echo $this->show_ecpay_dca_payment_fields($data);
        }
    }

    /**
     * 前台 - 結帳定期定額付款選項
     *
     * @param  array $data
     * @return void
     */
    public function show_ecpay_dca_payment_fields($data)
    {
        // 宣告變數
        $ecpay_dca_options  = $data['ecpay_dca_options'];
        $cart_total = $data['cart_total'];
        $periodTypeMethod = [
            'Y' => ' ' . __('year', 'ecpay-ecommerce-for-woocommerce'),
            'M' => ' ' . __('month', 'ecpay-ecommerce-for-woocommerce'),
            'D' => ' ' . __('day', 'ecpay-ecommerce-for-woocommerce')
        ];

        // Html
        $szHtml  = '';
        $szHtml .= '<select id="ecpay_dca_payment" name="ecpay_dca_payment">';
        $szHtml .= '<option>------</option>';

        // 避免初始預設欄位為空
        if (count($ecpay_dca_options) > 0) {
            foreach ($ecpay_dca_options as $dca_option) {
                $option = sprintf(
                    __('NT$ %d / %s %s, up to a maximun of %s', 'ecpay-ecommerce-for-woocommerce'),
                    $cart_total,
                    $dca_option['frequency'],
                    $periodTypeMethod[$dca_option['periodType']],
                    $dca_option['execTimes']
                );
                $szHtml .= $this->generate_option($dca_option['periodType'] . '_' . $dca_option['frequency'] . '_' . $dca_option['execTimes'], $option);
            }
        }
        
        $szHtml .= '</select>';

        $szHtml .= '<div id="ecpay_dca_show"></div>';
        $szHtml .= '<hr style="margin: 12px 0px;background-color: #eeeeee;">';
        $szHtml .= '<p style="font-size: 0.8em;color: #c9302c;">';
        $szHtml .= '你將使用<strong>綠界科技定期定額信用卡付款</strong>，請留意你所購買的商品為<strong>非單次扣款</strong>商品。';
        $szHtml .= '</p>';

        return $szHtml;
    }

    /**
     * 前台 - 整理定期定額付款選項option格式
     *
     * @param  string $value
     * @param  string $data
     * @return string $szHtml
     */
    private function generate_option($value, $data)
    {
        $szHtml  = '';
        $szHtml .= '<option value="' . esc_attr($value) . '">';
        $szHtml .=      esc_html($data);
        $szHtml .= '</option>';

        return $szHtml;
    }

    /**
     * 後台 - 載入定期定額JS後產生後台設定表格
     *
     * @return void
     */
    public function generate_ecpay_dca_html()
    {
        ob_start();

        // 指定到 settings-gateway-dca.php 的欄位 type(ecpay_dca)
        $data = array(
            'ecpay_dca_options' => $this->ecpay_dca_options
        );
        echo $this->show_ecpay_dca_table($data);

        return ob_get_clean();
    }

    /**
     * 後台 - 載入js
     *
     * @return void
     */
    public function wooecpay_register_scripts()
    {
        wp_register_script(
            'wooecpay_dca',
            WOOECPAY_PLUGIN_URL . 'public/js/wooecpay-dca.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script('wooecpay_dca');
    }

    /**
     * 後台 - 定期定額設定頁表格
     *
     * @param  array $data
     * @return void
     */
    public function show_ecpay_dca_table($data)
    {
        // 宣告參數
        $options = $data['ecpay_dca_options'];
        ?>
        <!-- Html -->
        <tr valign="top">
            <th scope="row" class="titledesc"><?php echo __('ECPay Paid Automatically Details', 'ecpay-ecommerce-for-woocommerce'); ?></th>
            <td class="forminp" id="ecpay_dca">
                <table class="widefat wc_input_table sortable" cellspacing="0" style="width: 600px;">
                    <thead>
                        <tr>
                            <th class="sort">&nbsp;</th>
                            <th><?php echo __('Peroid Type', 'ecpay-ecommerce-for-woocommerce'); ?></th>
                            <th><?php echo __('Frequency', 'ecpay-ecommerce-for-woocommerce'); ?></th>
                            <th><?php echo __('Execute Times', 'ecpay-ecommerce-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="accounts">
                        <?php
                            if (
                                sizeof($options) === 1
                                && $options[0]["periodType"] === ''
                                && $options[0]["frequency"] === ''
                                && $options[0]["execTimes"] === ''
                            ) {
                                // 初始預設定期定額方式
                                $options = [
                                    [
                                        'periodType' => "Y",
                                        'frequency' => "1",
                                        'execTimes' => "6",
                                    ],
                                    [
                                        'periodType' => "M",
                                        'frequency' => "1",
                                        'execTimes' => "12",
                                    ],
                                ];
                            }

                            $i = -1;
                            if ( is_array($options) ) {
                                foreach ( $options as $option ) {
                                    $i++;
                                    echo '<tr class="account">
                                        <td class="sort"></td>
                                        <td><input type="text" class="fieldPeriodType" value="' . esc_attr($option['periodType']) . '" name="periodType[' . $i . ']" maxlength="1" required /></td>
                                        <td><input type="number" class="fieldFrequency" value="' . esc_attr($option['frequency']) . '" name="frequency[' . $i . ']"  min="1" max="365" required /></td>
                                        <td><input type="number" class="fieldExecTimes" value="' . esc_attr($option['execTimes']) . '" name="execTimes[' . $i . ']"  min="2" max="999" required /></td>
                                    </tr>';
                                }
                            }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">
                                <a href="#" class="add button"><?php echo __('add', 'ecpay-ecommerce-for-woocommerce'); ?></a>
                                <a href="#" class="remove_rows button"><?php echo __('remove', 'ecpay-ecommerce-for-woocommerce'); ?></a>
                            </th>
                        </tr>
                    </tfoot>
                </table>
                <p class="description"><?php echo __('Don\'t forget to save modify', 'ecpay-ecommerce-for-woocommerce'); ?></p>
                <p id="fieldsNotification" style="display: none;">
                    <?php echo __('ECPay paid automatically details has been repeatedly, please confirm again', 'ecpay-ecommerce-for-woocommerce'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * 後台 - 儲存定期定額設定
     *
     * @return void
     */
    public function save_dca_details()
    {
        $ecpayDcaOptions = array();

        if (isset( $_POST['periodType'])) {

            $periodTypes = array_map('wc_clean', $_POST['periodType']);
            $frequencys  = array_map('wc_clean', $_POST['frequency']);
            $execTimes   = array_map('wc_clean', $_POST['execTimes']);

            foreach ($periodTypes as $i => $name) {
                if (!isset($periodTypes[$i])) {
                    continue;
                }

                $ecpayDcaOptions[] = array(
                    'periodType' => sanitize_text_field($periodTypes[$i]),
                    'frequency'  => sanitize_text_field($frequencys[$i]),
                    'execTimes'  => sanitize_text_field($execTimes[$i]),
                );
            }
        }

        update_option('woocommerce_ecpay_dca', $ecpayDcaOptions);
    }

    /**
     * 整理定期定額參數成 Array
     *
     * @param  array $data
     * @return void
     */
    public function get_ecpay_dca_payment()
    {
        $ecpayDcaOptions = get_option('woocommerce_ecpay_dca');
        $dcaPaymentList = [];
        if (is_array($ecpayDcaOptions)) {
            foreach ($ecpayDcaOptions as $option) {
                array_push($dcaPaymentList, $option['periodType'] . '_' . $option['frequency'] . '_' . $option['execTimes']);
            }
        }

        return $dcaPaymentList;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via ECPay DCA', 'ecpay-ecommerce-for-woocommerce'));

        if (isset($_POST['ecpay_dca_payment'])) {
            $order->update_meta_data('_ecpay_payment_dca', $_POST['ecpay_dca_payment']);
        }
        $order->save();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }
}
