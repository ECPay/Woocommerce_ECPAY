const cvs_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Cvs_data', {});

const Cvs_Content = () => {
    return window.wp.htmlEntities.decodeEntities(cvs_settings.description || '');
};
const Cvs_Block_Gateway = {
    name: 'Wooecpay_Gateway_Cvs',
    label: window.wp.htmlEntities.decodeEntities(cvs_settings.title || ''),
    content: Object(window.wp.element.createElement)(Cvs_Content, null),
    edit: Object(window.wp.element.createElement)(Cvs_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(cvs_settings.title || ''),
    supports: {
        features: cvs_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Cvs_Block_Gateway);