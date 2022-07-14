
var $ = jQuery.noConflict();

$( document ).ready(function() {

    $("#wooecpay_love_code").val("");            // 捐贈碼
    $("#wooecpay_invoice_invoice_customer_identifier").val("");      // 統一編號
    $("#wooecpay_invoice_carruer_num").val("");              // 載具編號
    $("#wooecpay_invoice_carruer_type").val("1");            // 載具類別
    $("#wooecpay_invoice_invoice_type").val("p");            // 發票開立類型

    $("#wooecpay_invoice_customer_identifier_field").slideUp();
    $("#wooecpay_invoice_love_code_field").slideUp();
    $("#wooecpay_invoice_carruer_num_field").slideUp();
    $("#wooecpay_invoice_carruer_type_field").slideDown();

    $("#wooecpay_invoice_type").change(function() {

        invoice_type = $("#wooecpay_invoice_type").val();

        if (invoice_type == 'p') {

            $("#wooecpay_invoice_customer_identifier_field").slideUp();
            $("#wooecpay_invoice_love_code_field").slideUp();
            $("#wooecpay_invoice_carruer_type_field").slideDown();

            $("#wooecpay_invoice_customer_identifier").val("");
            $("#wooecpay_invoice_love_code").val("");

        } else if (invoice_type == 'c') {

            $("#wooecpay_invoice_customer_identifier_field").slideDown();
            $("#wooecpay_invoice_love_code_field").slideUp();
            $("#wooecpay_invoice_carruer_num_field").slideUp();
            $("#wooecpay_invoice_carruer_type_field").slideUp();

            $("#wooecpay_invoice_carruer_num").val("");
            $("#wooecpay_invoice_love_code").val("");
            $("#wooecpay_invoice_carruer_type").val("0");

        } else if (invoice_type == 'd') {

            $("#wooecpay_invoice_customer_identifier_field").slideUp();
            $("#wooecpay_invoice_love_code_field").slideDown();
            $("#wooecpay_invoice_carruer_num_field").slideUp();
            $("#wooecpay_invoice_carruer_type_field").slideUp();

            $("#wooecpay_invoice_customer_identifier").val("");
            $("#wooecpay_invoice_carruer_num").val("");
            $("#wooecpay_invoice_carruer_type").val("0");

            if($("#wooecpay_invoice_love_code").val() == '') {
                 $("#wooecpay_invoice_love_code").val(wooecpay_invoice_script_var.wooecpay_invoice_donate);            // 捐贈碼
            }
        }
    });

    // 載具類別判斷
    $("#wooecpay_invoice_carruer_type").change(function() {

        carruer_type = $("#wooecpay_invoice_carruer_type").val();
        invoice_type = $("#wooecpay_invoice_type").val();
        identifier = $("#wooecpay_invoice_customer_identifier").val();

        // 無載具
        if (carruer_type == '0' || carruer_type == '1') {

            $("#wooecpay_invoice_carruer_num_field").slideUp();
            $("#wooecpay_invoice_carruer_num").val("");

        } else if (carruer_type == '2') {

            $("#wooecpay_invoice_carruer_num_field").slideDown();

        } else if (carruer_type == '3') {

            $("#wooecpay_invoice_carruer_num_field").slideDown();
        }
    });
});
