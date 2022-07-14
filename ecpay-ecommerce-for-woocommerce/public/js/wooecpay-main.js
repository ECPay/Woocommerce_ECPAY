// (物流)變更門市-後台訂單頁
function ecpayChangeStore() {
    var changeStore = document.getElementById('ecpay-form');
    console.log('testsetse');
    console.log(changeStore);
    map = window.open('','ecpay_map',config='height=790px,width=1020px');

    console.log(map)
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