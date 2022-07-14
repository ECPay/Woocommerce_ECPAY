<?php

defined('ABSPATH') || exit;

// var_dump($order->get_payment_method() );
// return ;


if ($order->get_payment_method() != 'Wooecpay_Gateway_Atm') {
    return;
}

?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?php echo( __('Payment info', 'wooecpay'); ?>
    </h2>
    <table class="woocommerce-table woocommerce-table--payment-details payment_details">
        <tbody>
            <tr>
                <td>
                    <?php echo( __('Bank', 'wooecpay')); ?>
                </td>
                <td><?php echo( _x($order->get_meta('_ecpay_atm_BankCode'), 'Bank code', 'wooecpay')); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Bank code', 'wooecpay')); ?>
                </td>
                <td><?php echo( $order->get_meta('_ecpay_atm_BankCode'));  ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo(  __('ATM Bank account', 'wooecpay')); ?>
                </td>
                <td><?php echo( wordwrap($order->get_meta('_ecpay_atm_vAccount'), 4, '<span> </span>', true)); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Payment deadline', 'wooecpay')); ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_atm_ExpireDate')); ?>
                    <?php echo($expireDate->date_i18n(wc_date_format())); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
