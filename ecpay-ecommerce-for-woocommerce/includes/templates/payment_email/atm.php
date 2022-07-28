<?php

defined('ABSPATH') || exit;

if ($order->get_payment_method() != 'Wooecpay_Gateway_Atm') {
    return;
}

?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?php echo( __('Payment info', 'ecpay-ecommerce-for-woocommerce')); ?>
    </h2>

    <table id="addresses" cellspacing="0" cellpadding="0" border="0" style="width: 100%;vertical-align: top;margin-bottom: 40px;padding: 0">
        <tbody>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('Bank', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( _x( wp_kses_post($order->get_meta('_ecpay_atm_BankCode')), 'Bank code', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('Bank code', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo wp_kses_post( $order->get_meta('_ecpay_atm_BankCode')); ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('ATM No', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( wordwrap( wp_kses_post($order->get_meta('_ecpay_atm_vAccount')), 4, '<span> </span>', true)); ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('Payment deadline', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php $expireDate = wc_string_to_datetime( wp_kses_post($order->get_meta('_ecpay_atm_ExpireDate')));?>
                    <?php echo( $expireDate->date_i18n(wc_date_format())); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
