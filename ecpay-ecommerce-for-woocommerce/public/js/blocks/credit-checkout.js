const credit_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Credit_data', {});

const Credit_Content = () => {
    return window.wp.htmlEntities.decodeEntities(credit_settings.description || '');
};
const Credit_Block_Gateway = {
    name: 'Wooecpay_Gateway_Credit',
    label: window.wp.htmlEntities.decodeEntities(credit_settings.title || ''),
    content: Object(window.wp.element.createElement)(Credit_Content, null),
    edit: Object(window.wp.element.createElement)(Credit_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(credit_settings.title || ''),
    supports: {
        features: credit_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Credit_Block_Gateway);