import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import claveUrl from '../../images/clave.svg';
import ProcessPaymentHandler from './components/neopayment-process-payment-handler';

const Label         = ({ label }) => (
	<div className = "neopayment-payment-label">
	<span> {__( 'Clave Card', 'neopayment' )} </span>
	<div className = "neopayment-payment-label__icons">
		<img src   = {claveUrl} alt = "Clave" className = "neopayment-payment-label__icon"/>
	</div>
	</div>
);

const settings = {
	name: 'neopayment_telered_gateway',
	ariaLabel: __( 'Neopayment Telered Gateway', 'neopayment' ),
	label: <Label/>,
	canMakePayment: () => true,
	content: <ProcessPaymentHandler/> ,
	edit: <ProcessPaymentHandler/> ,
	paymentMethodId: 'neopayment_telered_gateway',
	supports: { features: ['products'] },
	placeOrderButtonLabel: __( 'Pay with Clave', 'neopayment' ),
}
registerPaymentMethod( settings );