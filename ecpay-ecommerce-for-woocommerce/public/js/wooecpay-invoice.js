
var $ = jQuery.noConflict();

$(function() {
    // init發票欄位內容及顯示狀態
    refreshFields();
    $('#wooecpay_invoice_carruer_type').val('0');  
    $('#wooecpay_invoice_type').val('p');
});

// 監聽發票類別
$('#wooecpay_invoice_type').on('change', function() {
    // 欄位預設值
    refreshFields(this.id);
    switch (this.value) {
        case 'p':
            slideField(['wooecpay_invoice_customer_identifier_field', 'wooecpay_invoice_customer_company_field', 'wooecpay_invoice_love_code_field']);
            slideField(['wooecpay_invoice_carruer_type_field'], 'down');
            break;
        case 'c':
            slideField(['wooecpay_invoice_love_code_field', 'wooecpay_invoice_carruer_num_field']);
            slideField(['wooecpay_invoice_carruer_type_field', 'wooecpay_invoice_customer_identifier_field', 'wooecpay_invoice_customer_company_field'], 'down');
            break;
        case 'd':
            slideField(['wooecpay_invoice_customer_identifier_field', 'wooecpay_invoice_customer_company_field', 'wooecpay_invoice_carruer_num_field', 'wooecpay_invoice_carruer_type_field']);
            slideField(['wooecpay_invoice_love_code_field'], 'down');

            // 填入預設捐贈碼
            if($('#wooecpay_invoice_love_code').val() == '') {
                $('#wooecpay_invoice_love_code').val(wooecpay_invoice_script_var.wooecpay_invoice_donate);
            }
            break;
    }
});

// 監聽載具類別
$('#wooecpay_invoice_carruer_type').on('change', function() {
    // 欄位預設值
    refreshFields(this.id);
    if (this.value == '0' || this.value == '1') {
        // 無載具、紙本發票
        slideField(['wooecpay_invoice_carruer_num_field']);
    } 
    else if (this.value == '2' || this.value == '3') {
        // 自然人憑證、手機載具
        slideField(['wooecpay_invoice_carruer_num_field'], 'down');
    } 
});

// 調整欄位值
function refreshFields(id = null) {
    // 初始載入預設值
    if (id == null) {
        // 發票預設選項
        $('#wooecpay_invoice_type').val('p');
        $('#wooecpay_invoice_carruer_type').val('0');       
        // 顯示欄位
        slideField(['wooecpay_invoice_customer_company_field', 'wooecpay_invoice_customer_identifier_field', 'wooecpay_invoice_love_code_field', 'wooecpay_invoice_carruer_num_field']);
        // 隱藏欄位
        slideField(['wooecpay_invoice_carruer_type_field'], 'down');
    }

    if ($('#wooecpay_invoice_type').val() == 'c') {
        $.each($('#wooecpay_invoice_carruer_type option'), function(i, el) {
            if ($(el).text() == '自然人憑證') $(el).hide();
            else $(el).show();
        });
    }
    else {
        $.each($('#wooecpay_invoice_carruer_type option'), function(i, el) {
            $(el).show();
        });
    }

    // 發票類別變動時，載具類別刷新
    if (id == 'wooecpay_invoice_type') $('#wooecpay_invoice_carruer_type').val('0');

    // 欄位預設值
    $('#wooecpay_love_code').val('');                           // 捐贈碼
    $('#wooecpay_invoice_customer_company').val('');            // 公司行號
    $('#wooecpay_invoice_customer_identifier').val('');         // 統一編號
    $('#wooecpay_invoice_carruer_num').val('');                 // 載具編號   
}

// 調整欄位顯示狀態
function slideField(fields, type = 'up') {
    if (type == 'up') {
        $.each(fields, function(i, el) {
            $('#' + el).slideUp();
        });
    }
    else {
        $.each(fields, function(i, el) {
            $('#' + el).slideDown();
        });
    }
}