<?php

defined('ABSPATH') || exit;

// var_dump($order->get_payment_method() );
// return ;


if ($order->get_payment_method() != 'Wooecpay_Gateway_Barcode') {
    return;
}

?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?php echo ( __('Payment info', 'ecpay-ecommerce-for-woocommerce')); ?>
    </h2>
    <table class="woocommerce-table woocommerce-table--payment-details payment_details">
        <tbody>
            <tr>
                <td>
                    <?php echo( __('Barcode 1', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td>
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode1')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Barcode 2', 'ecpay-ecommerce-for-woocommerce'));?>
                </td>
                <td>
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode2')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Barcode 3', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td>
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode3')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo( __('Payment deadline', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime( wp_kses_post($order->get_meta('_ecpay_barcode_ExpireDate'))) ;?>
                    <?php echo( sprintf(_x('%1$s %2$s', 'Datetime', 'ecpay-ecommerce-for-woocommerce'), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format()))); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>