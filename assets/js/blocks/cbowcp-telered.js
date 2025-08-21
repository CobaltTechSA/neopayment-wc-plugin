import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import claveUrl from '../../images/clave.svg';
import ProcessPaymentHandler from './components/ProcessPaymentHandler';

const Label         = ({ label }) => (
	< div className = "cbowcp-payment-label" >
	< span > {__( 'Clave Card', 'class-cbowcp-payment-gateway' )} < / span >
	< div className = "cbowcp-payment-label__icons" >
		< img src   = {claveUrl} alt = "Visa" className = "cbowcp-payment-label__icon" / >
	< / div >
	< / div >
);

const settings = {
	name: 'cbowcp_telered_gateway',
	ariaLabel: __( 'CBO Telered Gateway', 'class-cbowcp-payment-gateway' ),
	label: < Label / > ,
	canMakePayment: () => true,
	content: < ProcessPaymentHandler / > ,
	edit: < ProcessPaymentHandler / > ,
	paymentMethodId: 'cbowcp_telered_gateway',
	supports: { features: ['products'] },
	placeOrderButtonLabel: __( 'Pay with Clave', 'class-cbowcp-payment-gateway' ),
}
registerPaymentMethod( settings );