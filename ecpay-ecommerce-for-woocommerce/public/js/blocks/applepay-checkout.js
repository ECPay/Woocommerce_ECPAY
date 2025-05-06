const applepay_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Applepay_data', {});

const Applepay_Content = () => {
    return window.wp.htmlEntities.decodeEntities(applepay_settings.description || '');
};

const isIOS = () => {
    const ua = navigator.userAgent;
    const isIOSDevice = /iPad|iPhone|iPod/.test(ua);
    const isIPadOS = /Macintosh/.test(ua) && navigator.maxTouchPoints > 1;

    return isIOSDevice || isIPadOS;
};

const Applepay_Block_Gateway = {
    name: 'Wooecpay_Gateway_Applepay',
    label: window.wp.htmlEntities.decodeEntities(applepay_settings.title || ''),
    content: Object(window.wp.element.createElement)(Applepay_Content, null),
    edit: Object(window.wp.element.createElement)(Applepay_Content, null),
    canMakePayment: () => {
        return isIOS();
    },
    ariaLabel: window.wp.htmlEntities.decodeEntities(applepay_settings.title || ''),
    supports: {
        features: applepay_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Applepay_Block_Gateway);