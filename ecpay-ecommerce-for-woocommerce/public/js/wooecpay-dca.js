jQuery(function() {
    jQuery('#ecpay_dca').on('click', 'a.add', function() {
        var size = jQuery('#ecpay_dca').find('tbody .account').length;

        jQuery('<tr class="account">\
                <td class="sort"></td>\
                <td><input type="text" class="fieldPeriodType" name="periodType[' + size + ']" maxlength="1" required /></td>\
                <td><input type="number" class="fieldFrequency" name="frequency[' + size + ']" min="1" max="365" required /></td>\
                <td><input type="number" class="fieldExecTimes" name="execTimes[' + size + ']" min="2" max="999" required /></td>\
            </tr>').appendTo('#ecpay_dca table tbody');

        return false;
    });

    jQuery('#ecpay_dca').on('blur', 'input', function() {
        let field = this.value.trim();
        let indexStart = this.name.search(/[[]/g);
        let indexEnd = this.name.search(/[\]]/g);
        let fieldIndex = this.name.substring(indexStart + 1, indexEnd);
        let fieldPeriodType = document.getElementsByName('periodType[' + fieldIndex + ']')[0].value;

        if ((validateFields.periodType(field) === false && this.className === 'fieldPeriodType') ||
            (validateFields.frequency(fieldPeriodType, field) === false && this.className === 'fieldFrequency') ||
            (validateFields.execTimes(fieldPeriodType, field) === false && this.className === 'fieldExecTimes')) {
            this.value = '';
        }
    });

    jQuery('#ecpay_dca').on('blur', 'tbody', function() {
        fields.process();
    });

    jQuery('body').on('click', '#mainform', function() {
        fields.process();
    });
});

var data = {
    periodType: ['D', 'M', 'Y'],
    frequency: ['365', '12', '1'],
    execTimes: ['999', '99', '9'],
};

var fields = {
    get: function() {
        var field = jQuery('#ecpay_dca').find('tbody .account td input');
        var fieldsInput = [];
        var fieldsTmp = [];
        var i = 0;
        Object.keys(field).forEach(function(key) {
            if (field[key].value != null) {
                i++;
                if (i % 3 == 0) {
                    fieldsTmp.push(field[key].value);
                    fieldsInput.push(fieldsTmp);
                    fieldsTmp = [];
                } else {
                    fieldsTmp.push(field[key].value);
                }
            }
        });

        return fieldsInput;
    },
    check: function(inputs) {
        var errorFlag = 0;
        inputs.forEach(function(key1, index1) {
            inputs.forEach(function(key2, index2) {
                if (index1 !== index2) {
                    if (key1[0] === key2[0] && key1[1] === key2[1] && key1[2] === key2[2]) {
                        errorFlag++;
                    }
                }
            })
        });

        return errorFlag;
    },
    process: function() {
        if (fields.check(fields.get()) > 0) {
            document.getElementById('fieldsNotification').style = 'color: #ff0000;';
            document.querySelector('input[name="save"]').disabled = true;
        } else {
            document.getElementById('fieldsNotification').style = 'display: none;';
            document.querySelector('input[name="save"]').disabled = false;
        }
    }
}

var validateFields = {
    periodType: function(field) {
        return (data.periodType.indexOf(field) !== -1);
    },
    frequency: function(periodType, field) {
        let maxFrequency = parseInt(data.frequency[data.periodType.indexOf(periodType)], 10);
        return ((field > 0) && ((maxFrequency + 1) > field));
    },
    execTimes: function(periodType, field) {
        let maxExecTimes = parseInt(data.execTimes[data.periodType.indexOf(periodType)], 10);
        return ((field > 1) && ((maxExecTimes + 1) > field));
    }
};