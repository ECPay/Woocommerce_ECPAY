<?php

defined('ABSPATH') || exit;

// var_dump($order->get_payment_method() );
// return ;


if ($order->get_payment_method() != 'Wooecpay_Gateway_Cvs') {
    return;
}

?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?=__('Payment info', 'wooecpay') ?>
    </h2>
    <table class="woocommerce-table woocommerce-table--payment-details payment_details">
        <tbody>
            <tr>
                <td>
                    <?=__('CVS No', 'wooecpay') ?>
                </td>
                <td>
                    <?=$order->get_meta('_ecpay_cvs_PaymentNo') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?=__('Payment deadline', 'wooecpay') ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_cvs_ExpireDate')); ?>
                    <?=sprintf(_x('%1$s %2$s', 'Datetime', 'wooecpay'), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format())) ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
