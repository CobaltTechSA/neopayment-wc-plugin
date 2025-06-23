import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { CardFields } from './components/CardFields';   

registerPaymentMethod( {
    name: 'cbo_telered_gateway',
    ariaLabel: __( 'Pasarela CBO Telered', 'cbo-payment-gateway' ),
    label: createElement( 'span', null, __( 'Tarjeta Clave', 'cbo-payment-gateway' ) ),
    content: CardFields,   
    edit:    CardFields,
    canMakePayment: () => true,
    supports: { features: [ 'products' ] },
} );