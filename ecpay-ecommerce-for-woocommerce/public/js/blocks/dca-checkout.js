const dca_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Dca_data', {});

const Dca_Content = () => {
    return window.wp.htmlEntities.decodeEntities(dca_settings.description || '');
};
const Dca_Block_Gateway = {
    name: 'Wooecpay_Gateway_Dca',
    label: window.wp.htmlEntities.decodeEntities(dca_settings.title || ''),
    content: Object(window.wp.element.createElement)(Dca_Content, null),
    edit: Object(window.wp.element.createElement)(Dca_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(dca_settings.title || ''),
    supports: {
        features: dca_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Dca_Block_Gateway);