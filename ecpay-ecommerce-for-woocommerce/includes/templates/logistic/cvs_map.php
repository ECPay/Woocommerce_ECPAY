<?php

defined('ABSPATH') || exit;

?>
<section>
    <h2>
        <?php echo( __('Ecpay CVS info', 'wooecpay')); ?>
    </h2>
    <table>
        <tbody>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store id', 'wooecpay')); ?>
                </td>
                <td><?php echo( __($order->get_meta('_ecpay_logistic_cvs_store_id'), 'wooecpay')); ?>
                </td>
            </tr>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store name', 'wooecpay')); ?>
                </td>
                <td><?php echo( $order->get_meta('_ecpay_logistic_cvs_store_name')); ?>
                </td>
            </tr>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store address', 'wooecpay')); ?>
                </td>
                <td><?php echo( $order->get_meta('_ecpay_logistic_cvs_store_address')); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>