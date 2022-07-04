<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\ArrayResponse;
use Ecpay\Sdk\Exceptions\RtnException;

class Wooecpay_Invoice_Response extends WC_Payment_Gateway
{
    public function __construct() {
        add_action('woocommerce_api_wooecpay_invoice_delay_issue_callback', array($this, 'delay_issue_response')); // 延遲發票開立 Response
    }

    public function delay_issue_response()
    {
        try {
            $factory = new Factory();
            $arrayResponse = $factory->create(ArrayResponse::class);

            if(isset($_POST['tsr']) && isset($_POST['invoicenumber'])){

                // 利用od_sob找出OrderId
                $order_id = $this->get_order_id($_POST) ;

                if ($order = wc_get_order($order_id)){

                    // 更新訂單
  
                    $order->update_meta_data( '_wooecpay_invoice_no', $_POST['invoicenumber'] );
                    $order->update_meta_data( '_wooecpay_invoice_date', $_POST['invoicedate'].' '.$_POST['invoicetime'] ); 
                    $order->update_meta_data( '_wooecpay_invoice_random_number', $_POST['invoicecode']); 

                    $order->add_order_note('延遲發票開立成功');
                    $order->save();

                    echo '1|OK';
                    exit;
                }
            }

        } catch (RtnException $e) {
            echo '(' . $e->getCode() . ')' . $e->getMessage() . PHP_EOL;
        }
    }

    // invoice
    protected function get_order_id($info)
    {
        $order_prefix = get_option('wooecpay_invoice_prefix') ;

        if (isset($info['od_sob'])) {

            $order_id = substr($info['od_sob'], strlen($order_prefix), strrpos($info['od_sob'], 'SN'));

            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }

        return false;
    }
}

return new Wooecpay_Invoice_Response();