const twqr_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Twqr_data', {});

const Twqr_Content = () => {
    return window.wp.htmlEntities.decodeEntities(twqr_settings.description || '');
};
const Twqr_Block_Gateway = {
    name: 'Wooecpay_Gateway_Twqr',
    label: window.wp.htmlEntities.decodeEntities(twqr_settings.title || ''),
    content: Object(window.wp.element.createElement)(Twqr_Content, null),
    edit: Object(window.wp.element.createElement)(Twqr_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(twqr_settings.title || ''),
    supports: {
        features: twqr_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Twqr_Block_Gateway);