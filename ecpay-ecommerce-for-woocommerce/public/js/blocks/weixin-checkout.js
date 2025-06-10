const weixin_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Weixin_data', {});

const Weixin_Content = () => {
    return window.wp.htmlEntities.decodeEntities(weixin_settings.description || '');
};

const Weixin_Block_Gateway = {
    name: 'Wooecpay_Gateway_Weixin',
    label: window.wp.htmlEntities.decodeEntities(weixin_settings.title || ''),
    content: Object(window.wp.element.createElement)(Weixin_Content, null),
    edit: Object(window.wp.element.createElement)(Weixin_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(weixin_settings.title || ''),
    supports: {
        features: weixin_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Weixin_Block_Gateway);