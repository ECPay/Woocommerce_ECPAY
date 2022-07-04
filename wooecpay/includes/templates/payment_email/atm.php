<?php

defined('ABSPATH') || exit;

if ($order->get_payment_method() != 'Wooecpay_Gateway_Atm') {
    return;
}

?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?=__('Payment info', 'wooecpay') ?>
    </h2>

    <table id="addresses" cellspacing="0" cellpadding="0" border="0" style="width: 100%;vertical-align: top;margin-bottom: 40px;padding: 0">
        <tbody>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=__('Bank', 'wooecpay') ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=_x($order->get_meta('_ecpay_atm_BankCode'), 'Bank code', 'wooecpay') ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=__('Bank code', 'wooecpay') ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=$order->get_meta('_ecpay_atm_BankCode')  ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=__('ATM No', 'wooecpay') ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=wordwrap($order->get_meta('_ecpay_atm_vAccount'), 4, '<span> </span>', true) ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?=__('Payment deadline', 'wooecpay') ?>
                </td>
                <td class="td" scope="col" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_atm_ExpireDate')); ?>
                    <?=$expireDate->date_i18n(wc_date_format()); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
