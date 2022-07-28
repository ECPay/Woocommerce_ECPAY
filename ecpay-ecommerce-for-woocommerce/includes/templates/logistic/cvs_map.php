<?php

defined('ABSPATH') || exit;

?>
<section>
    <h2>
        <?php echo( __('Ecpay CVS info', 'ecpay-ecommerce-for-woocommerce')); ?>
    </h2>
    <table>
        <tbody>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store id', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo( __( wp_kses_post($order->get_meta('_ecpay_logistic_cvs_store_id')), 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
            </tr>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store name', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo wp_kses_post( $order->get_meta('_ecpay_logistic_cvs_store_name')); ?>
                </td>
            </tr>
            <tr>
                <td>
                   <?php echo( __('Ecpay CVS store address', 'ecpay-ecommerce-for-woocommerce')); ?>
                </td>
                <td><?php echo wp_kses_post( $order->get_meta('_ecpay_logistic_cvs_store_address')); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>