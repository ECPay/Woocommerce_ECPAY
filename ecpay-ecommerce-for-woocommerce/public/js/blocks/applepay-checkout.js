const applepay_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Applepay_data', {});

const Applepay_Content = () => {
    return window.wp.htmlEntities.decodeEntities(applepay_settings.description || '');
};
const Applepay_Block_Gateway = {
    name: 'Wooecpay_Gateway_Applepay',
    label: window.wp.htmlEntities.decodeEntities(applepay_settings.title || ''),
    content: Object(window.wp.element.createElement)(Applepay_Content, null),
    edit: Object(window.wp.element.createElement)(Applepay_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(applepay_settings.title || ''),
    supports: {
        features: applepay_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Applepay_Block_Gateway);