const bnpl_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Bnpl_data', {});

const Bnpl_Content = () => {
    return window.wp.htmlEntities.decodeEntities(bnpl_settings.description || '');
};
const Bnpl_Block_Gateway = {
    name: 'Wooecpay_Gateway_Bnpl',
    label: window.wp.htmlEntities.decodeEntities(bnpl_settings.title || ''),
    content: Object(window.wp.element.createElement)(Bnpl_Content, null),
    edit: Object(window.wp.element.createElement)(Bnpl_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(bnpl_settings.title || ''),
    supports: {
        features: bnpl_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Bnpl_Block_Gateway);