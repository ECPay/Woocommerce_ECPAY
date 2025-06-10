import {ValidatedTextInput} from '@woocommerce/blocks-checkout';
import {SelectControl, TextControl} from '@wordpress/components';
import {
    useBlockProps,
    InspectorControls,
} from '@wordpress/block-editor';
import {PanelBody} from '@wordpress/components';

import {__} from '@wordpress/i18n'; 

export const Edit = ({attributes, setAttributes}) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps}>
            <div className={'ecpay_invoice_fields'}> 
                <SelectControl
                    label={__('Invoice Type', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    options={
                        [
                            {label: __('Personal', 'ecpay-ecommerce-for-woocommerce'), value: 'p'},
                            {label: __('Company', 'ecpay-ecommerce-for-woocommerce'), value: 'c'},
                            {label: __('Donation', 'ecpay-ecommerce-for-woocommerce'), value: 'd'},
                        ]
                    }
                    required={true}
                    style={{height: '3rem'}}
                />
                <SelectControl
                    label={__('Carrier Type', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    options={
                        [
                            {label: __('Paper Invoice', 'ecpay-ecommerce-for-woocommerce'), value: '1'},
                            {label: __('Cloud Invoice (Paper sent if winning)', 'ecpay-ecommerce-for-woocommerce'), value: '2'},
                            {label: __('Citizen Digital Certificate', 'ecpay-ecommerce-for-woocommerce'), value: '3'},
                            {label: __('Mobile Barcode', 'ecpay-ecommerce-for-woocommerce'), value: '4'}
                        ]
                   }
                    required={true}
                    style={{height: '3rem'}}
                />
                <TextControl
                    label={__('Company Name', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label={__('Uniform Numbers', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label={__('Donation Code', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label={__('Carrier Number', 'ecpay-ecommerce-for-woocommerce')}
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
            </div>
        </div>
    );
};