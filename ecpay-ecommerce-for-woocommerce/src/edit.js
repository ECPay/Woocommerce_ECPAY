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
					label="發票開立"
					value={''}
					options={
						[
							{label: '個人', value: 'p'},
							{label: '公司', value: 'c'},
							{label: '捐贈', value: 'd'},
						]
					}
					required={true}
					style={{height: '3rem'}}
				/>
				<SelectControl
                    label="載具類型"
                    value={''}
                    options={
                        [
                            {label: '索取紙本', value: '1'},
							{label: '雲端發票(中獎寄送紙本)', value: '2'},
							{label: '自然人憑證', value: '3'},
							{label: '手機條碼', value: '4'}
                        ]
                   }
                    required={true}
                    style={{height: '3rem'}}
                />
                <TextControl
                    label="公司行號"
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label="統一編號"
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label="捐贈碼"
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
                <TextControl
                    label="載具編號"
                    value={''}
                    required={true}
                    style={{height: '3rem', width: '-webkit-fill-available', padding: '7px'}}
                />
			</div>
		</div>
	);
};
