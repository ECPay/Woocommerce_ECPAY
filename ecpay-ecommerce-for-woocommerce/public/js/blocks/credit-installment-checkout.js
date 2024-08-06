const credit_installment_settings = window.wc.wcSettings.getSetting('Wooecpay_Gateway_Credit_Installment_data', {});

const Credit_Installment_Content = () => {
    return window.wp.htmlEntities.decodeEntities(credit_installment_settings.description || '');
};
const Credit_Installment_Block_Gateway = {
    name: 'Wooecpay_Gateway_Credit_Installment',
    label: window.wp.htmlEntities.decodeEntities(credit_installment_settings.title || ''),
    content: Object(window.wp.element.createElement)(Credit_Installment_Content, null),
    edit: Object(window.wp.element.createElement)(Credit_Installment_Content, null),
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(credit_installment_settings.title || ''),
    supports: {
        features: credit_installment_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Credit_Installment_Block_Gateway);