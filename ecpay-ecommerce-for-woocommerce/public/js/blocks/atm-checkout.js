const atm_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Atm_data', {});

const Atm_Content = () => {
    return window.wp.htmlEntities.decodeEntities(atm_settings.description || '');
};
const Atm_Block_Gateway = {
    name: 'Wooecpay_Gateway_Atm',
    label: window.wp.htmlEntities.decodeEntities(atm_settings.title || ''),
    content: Object(window.wp.element.createElement)(Atm_Content, null),
    edit: Object(window.wp.element.createElement)(Atm_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(atm_settings.title || ''),
    supports: {
        features: atm_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Atm_Block_Gateway);