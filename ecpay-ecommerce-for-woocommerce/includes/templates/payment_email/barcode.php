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
        <?php echo( __('Payment info', 'ecpay-ecommerce-for-woocommerce')); ?>
    </h2>
    <table id="addresses" cellspacing="0" cellpadding="0" border="0" style="width: 100%;vertical-align: top;margin-bottom: 40px;padding: 0">
        <tbody>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('barcode one', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode1')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('barcode two', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode2')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('barcode three', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <span class="code39">*<?php echo wp_kses_post( $order->get_meta('_ecpay_barcode_Barcode3')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php echo( __('Payment deadline', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_barcode_ExpireDate')); ?>
                    <?php echo( sprintf(_x('%1$s %2$s', 'Datetime', 'ecpay-ecommerce-for-woocommerce'), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format()))); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>