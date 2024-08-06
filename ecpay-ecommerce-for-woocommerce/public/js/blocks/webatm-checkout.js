const webatm_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Webatm_data', {});

const Webatm_Content = () => {
    return window.wp.htmlEntities.decodeEntities(webatm_settings.description || '');
};
const Webatm_Block_Gateway = {
    name: 'Wooecpay_Gateway_Webatm',
    label: window.wp.htmlEntities.decodeEntities(webatm_settings.title || ''),
    content: Object(window.wp.element.createElement)(Webatm_Content, null),
    edit: Object(window.wp.element.createElement)(Webatm_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(webatm_settings.title || ''),
    supports: {
        features: webatm_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Webatm_Block_Gateway);