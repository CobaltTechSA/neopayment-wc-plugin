import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
// import { CardFields } from './components/CardFields';   

const CBOStandardForm = () =>
    createElement('div', { id: 'cbo-standard-form' });

registerPaymentMethod({
    name: 'cbo_standard_gateway',
    label: __('Tarjeta (Visa/Mastercard)', 'cbo-payment-gateway'),
    ariaLabel: __('Pasarela CBO Standard', 'cbo-payment-gateway'),

    content: createElement(CBOStandardForm, null),
    edit: createElement(CBOStandardForm, null),

    // content: CardFields,   
    //edit:    CardFields,

    canMakePayment: () => true,
    supports: { features: ['products'] },
});
