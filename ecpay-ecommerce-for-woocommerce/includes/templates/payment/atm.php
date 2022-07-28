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
        <?php echo( __('Payment info', 'ecpay-ecommerce-for-woocommerce')); ?>
    </h2>
    <table class="woocommerce-table woocommerce-table--payment-details payment_details">
        <tbody>
            <tr>
                <td>
                    <?php echo( __('Bank', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo( _x( wp_kses_post($order->get_meta('_ecpay_atm_BankCode')), 'Bank code', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Bank code', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo wp_kses_post($order->get_meta('_ecpay_atm_BankCode'));  ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo(  __('ATM Bank account', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo( wordwrap($order->get_meta('_ecpay_atm_vAccount'), 4, '<span> </span>', true)); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Payment deadline', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime( wp_kses_post($order->get_meta('_ecpay_atm_ExpireDate'))); ?>
                    <?php echo($expireDate->date_i18n(wc_date_format())); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
