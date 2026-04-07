import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import claveUrl from '../../images/clave.svg';
import ProcessPaymentHandler from './components/nbo-payment-gateway-process-payment-handler';

const Label         = ({ label }) => (
	<div className = "nbo-payment-label">
	<span> {__( 'Clave Card', 'nbo-payment-gateway' )} </span>
	<div className = "nbo-payment-label__icons">
		<img src   = {claveUrl} alt = "Clave" className = "nbo-payment-label__icon"/>
	</div>
	</div>
);

const settings = {
	name: 'nbo_payment_gateway_telered_gateway',
	ariaLabel: __( 'NBO Telered Gateway', 'nbo-payment-gateway' ),
	label: <Label/>,
	canMakePayment: () => true,
	content: <ProcessPaymentHandler/> ,
	edit: <ProcessPaymentHandler/> ,
	paymentMethodId: 'nbo_payment_gateway_telered_gateway',
	supports: { features: ['products'] },
	placeOrderButtonLabel: __( 'Pay with Clave', 'nbo-payment-gateway' ),
}
registerPaymentMethod( settings );