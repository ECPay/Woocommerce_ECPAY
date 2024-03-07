// (物流)變更門市-後台訂單頁
function ecpayChangeStore() {
    var changeStore = document.getElementById('ecpay-form');

    map = window.open('','ecpay_map',config='height=790px,width=1020px');

    if (map) {
        changeStore.submit();
    }
}

// (物流)建立訂單-後台訂單頁
function ecpayCreateLogisticsOrder(order_id) {

    query = {
        action: 'send_logistic_order_action',
        order_id: order_id
    };

    jQuery.blockUI({ message: null });

    jQuery.post(ajaxurl, query, function(response) {

        var response_info = jQuery.parseJSON(response);
        window.location.reload();

        jQuery.unblockUI()
    });
}

// (物流)列印繳款單-後台訂單頁
function ecpayLogisticPrint() {
    document.getElementById('ecpay_print').submit();
}

// (發票)開立發票
function wooecpayCreateInvoice(order_id) {

    query = {
        action: 'send_invoice_create',
        order_id: order_id
    };

    jQuery.blockUI({ message: null });

    jQuery.post(ajaxurl, query, function(response) {

        var response_info = jQuery.parseJSON(response);
        window.location.reload();

        jQuery.unblockUI()
    });
}

// (發票)作廢發票
function wooecpayInvalidInvoice(order_id) {

    query = {
        action: 'send_invoice_invalid',
        order_id: order_id
    };

    jQuery.blockUI({ message: null });

    jQuery.post(ajaxurl, query, function(response) {

        var response_info = jQuery.parseJSON(response);
        window.location.reload();

        jQuery.unblockUI()
    });
}

// (金流)標示綠界重複付款訂單已處理
function wooecpayDuplicatePaymentComplete(order_id, merchant_trade_no_list) {

    query = {
        action: 'duplicate_payment_complete',
        order_id: order_id,
        merchant_trade_no_list: merchant_trade_no_list
    };

    jQuery.blockUI({ message: null });

    jQuery.post(ajaxurl, query, function(response) {

        var response_info = jQuery.parseJSON(response);
        window.location.reload();

        jQuery.unblockUI()
    });
}

// (工具)清理 Log
function wooecpayClearEcpayDebugLog() {

    query = {
        action: 'clear_ecpay_debug_log'
    };

    jQuery.post(ajaxurl, query, function(response) {
        //
    })
    .success (function() {
        alert('Log 已清空!');
    })
    .error (function () {
        console.log('清理Log失敗')
    });
}