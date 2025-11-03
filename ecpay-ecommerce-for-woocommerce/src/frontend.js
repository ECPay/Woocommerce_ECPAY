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
        {label: __('Paper Invoice', 'ecpay-ecommerce-for-woocommerce'), value: '0'},
        {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
        {label: __('Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), value: '2'},
        {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
    ]);
    const [companyNameValue, setCompanyNameValue] = useState('');
    const [companyIdentifierValue, setCompanyIdentifierValue] = useState('');
    const [loveCodeValue, setLoveCodeValue] = useState('');
    const [carruerNumValue, setCarruerNumValue] = useState('');

    let donateCode = '';
    let invoicePapper = 'enable';

    useEffect(() => {
        setExtensionData('ecpay-invoice-block', 'invoice_type', invoiceTypeValue);
        setExtensionData('ecpay-invoice-block', 'invoice_carruer_type', carruerTypeValue);

        if (InvoiceData.DonateCode !== null) {
            donateCode = InvoiceData.DonateCode;
            setLoveCodeValue(donateCode);
            setExtensionData('ecpay-invoice-block', 'invoice_love_code', donateCode);
        }
        if (InvoiceData.InvoicePapper !== null) {
            invoicePapper = InvoiceData.InvoicePapper;
            if (invoicePapper == 'disable') {
                setCarruerTypeOptions([
                    {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                    {label: __('Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), value: '2'},
                    {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
                ]);
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
            if (value === 'c') {
                var data = [
                    {label: __('Paper Invoice', 'ecpay-ecommerce-for-woocommerce'), value: '0'},
                    {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                    {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
                ];

                if (invoicePapper == 'disable') {
                    data = [
                        {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                        {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
                    ];
                }
                setCarruerTypeOptions(data);
            } else if (value === 'p') {
                var data = [
                    {label: __('Paper Invoice', 'ecpay-ecommerce-for-woocommerce'), value: '0'},
                    {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                    {label: __('Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), value: '2'},
                    {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
                ];

                if (invoicePapper == 'disable') {
                    data = [
                        {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                        {label: __('Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), value: '2'},
                        {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '3'}
                    ];
                }
                setCarruerTypeOptions(data);

                setCarruerTypeValue(data[0].value);
                setExtensionData('ecpay-invoice-block', 'invoice_carruer_type', data[0].value);
            } else {
                setCarruerTypeOptions([]);
            }
        }

        setCompanyNameValue('');
        setCompanyIdentifierValue('');
        setLoveCodeValue(donateCode);
        setCarruerNumValue('');
        setExtensionData('ecpay-invoice-block', 'invoice_customer_company', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_customer_identifier', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_love_code', donateCode);
        setExtensionData('ecpay-invoice-block', 'invoice_carruer_num', donateCode);
    };

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

        setExtensionData('ecpay-invoice-block', type, value);
    }, [
        setInvoiceTypeValue,
        setCarruerTypeValue,
        setCompanyNameValue,
        setCompanyIdentifierValue,
        setLoveCodeValue,
        setCarruerNumValue,
        setExtensionData
    ]);

    return (
        <div className="ecpay_invoice_fields">
            <SelectControl
                label={__('Invoice Type', 'ecpay-ecommerce-for-woocommerce')}
                value={invoiceTypeValue}
                options={[
                    {label: __('Personal', 'ecpay-ecommerce-for-woocommerce'), value: 'p'},
                    {label: __('Company', 'ecpay-ecommerce-for-woocommerce'), value: 'c'},
                    {label: __('Donation', 'ecpay-ecommerce-for-woocommerce'), value: 'd'}
                ]}
                onChange={(value) => handleDataChange(value, 'invoice_type')}
                required={true}
                style={{height: '3rem'}}
            />
            {invoiceTypeValue !== 'd' && (
                <SelectControl
                    label={__('Carrier Type', 'ecpay-ecommerce-for-woocommerce')}
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
                    label={__('Company Name', 'ecpay-ecommerce-for-woocommerce')}
                    value={companyNameValue}
                    onChange={(value) => handleDataChange(value, 'invoice_customer_company')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {invoiceTypeValue === 'c' && (
                <TextControl
                    label={__('Uniform Numbers', 'ecpay-ecommerce-for-woocommerce')}
                    value={companyIdentifierValue}
                    onChange={(value) => handleDataChange(value, 'invoice_customer_identifier')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {invoiceTypeValue === 'd' && (
                <TextControl
                    label={__('Donation Code', 'ecpay-ecommerce-for-woocommerce')}
                    value={loveCodeValue}
                    onChange={(value) => handleDataChange(value, 'invoice_love_code')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
            {(carruerTypeValue === '2' || carruerTypeValue === '3') && (
                <TextControl
                    label={__('Carrier Number', 'ecpay-ecommerce-for-woocommerce')}
                    value={carruerNumValue}
                    onChange={(value) => handleDataChange(value, 'invoice_carruer_num')}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            )}
        </div>
    );
};

const ecpayInvoiceOptions = {
    metadata,
    component: ecpayInvoiceBlock
};

registerCheckoutBlock(ecpayInvoiceOptions);