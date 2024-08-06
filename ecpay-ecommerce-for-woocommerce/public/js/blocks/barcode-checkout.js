const barcode_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Barcode_data', {});

const Barcode_Content = () => {
    return window.wp.htmlEntities.decodeEntities(barcode_settings.description || '');
};
const Barcode_Block_Gateway = {
    name: 'Wooecpay_Gateway_Barcode',
    label: window.wp.htmlEntities.decodeEntities(barcode_settings.title || ''),
    content: Object(window.wp.element.createElement)(Barcode_Content, null),
    edit: Object(window.wp.element.createElement)(Barcode_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(barcode_settings.title || ''),
    supports: {
        features: barcode_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Barcode_Block_Gateway);