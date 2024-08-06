(()=>{"use strict";const e=window.React,a=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":2,"name":"ecpay-invoice-block/invoice-fields","version":"1.0.0","title":"ECPay Woocommerce Invoice Block","category":"woocommerce","parent":["woocommerce/checkout-contact-information-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}}},"textdomain":"ecpay-invoice-block","editorScript":"file:./build/index.js"}'),l=window.wp.components,i=(window.wp.i18n,window.wp.element),{registerCheckoutBlock:c}=wc.blocksCheckout;c({metadata:a,component:({children:a,checkoutExtensionData:c})=>{const{setExtensionData:o,getExtensionData:t}=c,[n,r]=(0,i.useState)("p"),[v,u]=(0,i.useState)("0"),[b,s]=(0,i.useState)([{label:"索取紙本",value:"0"},{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"自然人憑證",value:"2"},{label:"手機條碼",value:"3"}]),[p,d]=(0,i.useState)(""),[m,_]=(0,i.useState)(""),[h,k]=(0,i.useState)(""),[y,w]=(0,i.useState)("");let g="",f="enable";(0,i.useEffect)((()=>{null!==InvoiceData.DonateCode&&(g=InvoiceData.DonateCode,k(g),o("ecpay-invoice-block","invoice_love_code",g)),null!==InvoiceData.InvoicePapper&&(f=InvoiceData.InvoicePapper,"disable"==f&&s([{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"自然人憑證",value:"2"},{label:"手機條碼",value:"3"}]))}),[]),(0,i.useEffect)((()=>{b.length>0&&(u(b[0].value),o("ecpay-invoice-block","invoice_carruer_type",b[0].value))}),[b]);const C=(e,a)=>{if("invoice_type"===e)if("c"===a){var l=[{label:"索取紙本",value:"0"},{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"手機條碼",value:"3"}];"disable"==f&&(l=[{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"手機條碼",value:"3"}]),s(l)}else"p"===a?(l=[{label:"索取紙本",value:"0"},{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"自然人憑證",value:"2"},{label:"手機條碼",value:"3"}],"disable"==f&&(l=[{label:"雲端發票(中獎寄送紙本)",value:"1"},{label:"自然人憑證",value:"2"},{label:"手機條碼",value:"3"}]),s(l),u(l[0].value),o("ecpay-invoice-block","invoice_carruer_type",l[0].value)):s([]);d(""),_(""),k(g),w(""),o("ecpay-invoice-block","invoice_customer_company",g),o("ecpay-invoice-block","invoice_customer_identifier",g),o("ecpay-invoice-block","invoice_love_code",g),o("ecpay-invoice-block","invoice_carruer_num",g)},x=(0,i.useCallback)(((e,a)=>{switch(a){case"invoice_type":r(e),C(a,e);break;case"invoice_carruer_type":u(e),C(a,e);break;case"invoice_customer_company":d(e);break;case"invoice_customer_identifier":_(e);break;case"invoice_love_code":k(e);break;case"invoice_carruer_num":w(e)}o("ecpay-invoice-block",a,e)}),[r,u,d,_,k,w,o]);return(0,e.createElement)("div",{className:"ecpay_invoice_fields"},(0,e.createElement)(l.SelectControl,{label:"發票開立",value:n,options:[{label:"個人",value:"p"},{label:"公司",value:"c"},{label:"捐贈",value:"d"}],onChange:e=>x(e,"invoice_type"),required:!0,style:{height:"3rem"}}),"d"!==n&&(0,e.createElement)(l.SelectControl,{label:"載具類型",value:v,options:[...b],onChange:e=>x(e,"invoice_carruer_type"),required:!0,style:{height:"3rem"}}),"c"===n&&(0,e.createElement)(l.TextControl,{label:"公司行號",value:p,onChange:e=>x(e,"invoice_customer_company"),required:!0,style:{height:"3rem",width:"-webkit-fill-available",padding:"7px"}}),"c"===n&&(0,e.createElement)(l.TextControl,{label:"統一編號",value:m,onChange:e=>x(e,"invoice_customer_identifier"),required:!0,style:{height:"3rem",width:"-webkit-fill-available",padding:"7px"}}),"d"===n&&(0,e.createElement)(l.TextControl,{label:"捐贈碼",value:h,onChange:e=>x(e,"invoice_love_code"),required:!0,style:{height:"3rem",width:"-webkit-fill-available",padding:"7px"}}),("2"===v||"3"===v)&&(0,e.createElement)(l.TextControl,{label:"載具編號",value:y,onChange:e=>x(e,"invoice_carruer_num"),required:!0,style:{height:"3rem",width:"-webkit-fill-available",padding:"7px"}}))}})})();