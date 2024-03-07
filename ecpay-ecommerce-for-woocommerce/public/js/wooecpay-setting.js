(function($) {
    // 當物流開啟時，同時開啟金流
    $('#wooecpay_enabled_logistic').on('change', function() {
        if (this.checked) $('#wooecpay_enabled_payment').prop('checked', true);
    })

    // 當金流關閉時，同時關閉物流
    var confirm_box_message = confirm_message.message;
    $('#wooecpay_enabled_payment').on('change', function() {
        if ($('#wooecpay_enabled_logistic').is(':checked') === true && this.checked === false) {
            if (confirm(confirm_box_message)) $('#wooecpay_enabled_logistic').prop('checked', false);
            else $('#wooecpay_enabled_payment').prop('checked', true);
        }
    })
})(jQuery);