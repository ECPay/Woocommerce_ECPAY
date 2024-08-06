import metadata from './block.json';
import {SelectControl, TextControl} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {useEffect, useState, useCallback} from '@wordpress/element';

const {registerCheckoutBlock} = wc.blocksCheckout;

const ecpayInvoiceBlock = ({children, checkoutExtensionData}) => {
    const {setExtensionData, getExtensionData} = checkoutExtensionData;
    const [invoiceTypeValue, setInvoiceTypeValue] = useState('p');
    const [carruerTypeValue, setCarruerTypeValue] = useState('0');
    const [carruerTypeOptions, setCarruerTypeOptions] = useState([
        {label: '索取紙本', value: '0'},
        {label: '雲端發票(中獎寄送紙本)', value: '1'},
        {label: '自然人憑證', value: '2'},
        {label: '手機條碼', value: '3'}
    ]);
    const [companyNameValue, setCompanyNameValue] = useState('');
    const [companyIdentifierValue, setCompanyIdentifierValue] = useState('');
    const [loveCodeValue, setLoveCodeValue] = useState('');
    const [carruerNumValue, setCarruerNumValue] = useState('');

    let donateCode = '';
    let invoicePapper = 'enable';

    // 最後一個參數留空陣列，表示首次渲染才執行
    useEffect(() => {
        // 預設捐贈碼
        if (InvoiceData.DonateCode !== null) {
            donateCode = InvoiceData.DonateCode            
            setLoveCodeValue(donateCode)
            setExtensionData('ecpay-invoice-block', 'invoice_love_code', donateCode);
        }
        if (InvoiceData.InvoicePapper !== null) {
            invoicePapper = InvoiceData.InvoicePapper
            if (invoicePapper == 'disable') {
                setCarruerTypeOptions([
                    {label: '雲端發票(中獎寄送紙本)', value: '1'},
                    {label: '自然人憑證', value: '2'},
                    {label: '手機條碼', value: '3'}
                ])
            }
        }
    }, []);

    useEffect(() => {
        if (carruerTypeOptions.length > 0) {
            setCarruerTypeValue(carruerTypeOptions[0].value);
            setExtensionData('ecpay-invoice-block', 'invoice_carruer_type', carruerTypeOptions[0].value);
        }
    }, [carruerTypeOptions]);

    const refreshFields = (type, value) => {
        if (type === 'invoice_type') {
            // 重設載具Options
            if (value === 'c') {
                var data = [
                    {label: '索取紙本', value: '0'},
                    {label: '雲端發票(中獎寄送紙本)', value: '1'},
                    {label: '手機條碼', value: '3'},
                ]

                if (invoicePapper == 'disable') {
                    data = [
                        {label: '雲端發票(中獎寄送紙本)', value: '1'},
                        {label: '手機條碼', value: '3'},
                    ]
                }
                setCarruerTypeOptions(data);
            } else if (value === 'p') {
                var data = [
                    {label: '索取紙本', value: '0'},
                    {label: '雲端發票(中獎寄送紙本)', value: '1'},
                    {label: '自然人憑證', value: '2'},
                    {label: '手機條碼', value: '3'}
                ]

                if (invoicePapper == 'disable') {
                    data = [
                        {label: '雲端發票(中獎寄送紙本)', value: '1'},
                        {label: '自然人憑證', value: '2'},
                        {label: '手機條碼', value: '3'}
                    ]
                }
                setCarruerTypeOptions(data);

                // 重設載具類型
                setCarruerTypeValue(data[0].value);
                setExtensionData('ecpay-invoice-block', 'invoice_carruer_type', data[0].value);

            } else {
                setCarruerTypeOptions([]);
            }
        }

        // 重設發票欄位值
        setCompanyNameValue('');
        setCompanyIdentifierValue('');
        setLoveCodeValue(donateCode);
        setCarruerNumValue('');
        setExtensionData('ecpay-invoice-block', 'invoice_customer_company', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_customer_identifier', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_love_code', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_carruer_num', donateCode);
    }

    // 監聽欄位變動
    const handleDataChange = useCallback((value, type) => {
        switch (type) {
            case 'invoice_type':
                setInvoiceTypeValue(value);
                refreshFields(type, value);
                break;
            case 'invoice_carruer_type':
                setCarruerTypeValue(value);
                refreshFields(type, value);
                break;
            case 'invoice_customer_company':
                setCompanyNameValue(value);
                break;
            case 'invoice_customer_identifier':
                setCompanyIdentifierValue(value);
                break;
            case 'invoice_love_code':
                setLoveCodeValue(value);
                break;
            case 'invoice_carruer_num':
                setCarruerNumValue(value);
                break;
        }

        // 設定拋往後端值
        setExtensionData('ecpay-invoice-block', type, value);
    },
    [
        setInvoiceTypeValue, 
        setCarruerTypeValue, 
        setCompanyNameValue, 
        setCompanyIdentifierValue,
        setLoveCodeValue,
        setCarruerNumValue,
        setExtensionData
    ])

    return (
        <div className="ecpay_invoice_fields">
            <SelectControl
                label="發票開立"
                value={invoiceTypeValue}
                options={[
                    {label: '個人', value: 'p'},
                    {label: '公司', value: 'c'},
                    {label: '捐贈', value: 'd'},
                ]}
                onChange={(value) => handleDataChange(value, 'invoice_type')}
                required={true}
                style={{height: '3rem'}}
            />
            {invoiceTypeValue !== 'd' && (
                <SelectControl
                    label="載具類型"
                    value={carruerTypeValue}
                    options={[
                        ...carruerTypeOptions
                    ]}
                    onChange={(value) => handleDataChange(value, 'invoice_carruer_type')}
                    required={true}
                    style={{height: '3rem'}}
                />
            )}
            {invoiceTypeValue === 'c' && (
                <TextControl
                    label="公司行號"
                    value={companyNameValue}
                    onChange={(value) => handleDataChange(value, 'invoice_customer_company')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {invoiceTypeValue === 'c' && (
                <TextControl
                    label="統一編號"
                    value={companyIdentifierValue}
                    onChange={(value) => handleDataChange(value, 'invoice_customer_identifier')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {invoiceTypeValue === 'd' && (
                <TextControl
                    label="捐贈碼"
                    value={loveCodeValue}
                    onChange={(value) => handleDataChange(value, 'invoice_love_code')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {(carruerTypeValue === '2' || carruerTypeValue === '3') && (
                <TextControl
                    label="載具編號"
                    value={carruerNumValue}
                    onChange={(value) => handleDataChange(value, 'invoice_carruer_num')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
        </div>
    )
}

const ecpayInvoiceOptions = {
	metadata,
	component: ecpayInvoiceBlock
};

registerCheckoutBlock(ecpayInvoiceOptions);