import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import CardFields from './components/nbo-payment-gateway-card-fields';
import visaUrl from '../../images/visa.svg';
import mcUrl from '../../images/mastercard.svg';
import {
  validateLuhn,
  validateExpiry,
  validateCvc
} from '../includes/nbo-payment-gateway-validators';

const Label = ({ label }) => (
  <div className="nbo-payment-label">
    <span>{ __( 'Card (Visa/Mastercard)', 'nbo-payment-gateway' ) }</span>
    <div className="nbo-payment-label__icons">
      <img
        src={ visaUrl }
        alt="Visa"
        className="nbo-payment-label__icon"
      />
      <img
        src={ mcUrl }
        alt="Mastercard"
        className="nbo-payment-label__icon"
      />
    </div>
  </div>
);

const POPUP_WIDTH  = 400;
const POPUP_HEIGHT = 600;

const getBrowserData = () => ({
  browserJavaEnabled: navigator.javaEnabled ? '1' : '0',
  browserJavascriptEnabled: '1',
  browserLanguage: navigator.language,
  browserColorDepth: window.screen.colorDepth.toString(),
  browserScreenWidth: POPUP_WIDTH.toString(),
  browserScreenHeight: POPUP_HEIGHT.toString(),
  browserTZ: new Date().getTimezoneOffset().toString(),
  browserUserAgent: navigator.userAgent,
  challengeWindowSize: POPUP_WIDTH.toString(),
});

function PaymentMethod({
  eventRegistration,
  emitResponse,
  billingAddress = {},
  shippingAddress = {},
}) {
  const [cardData, setCardData] = useState({
    card_number: '', card_expiry: '', card_cvc: '', card_holder: ''
  });

  useEffect(() => {
    const unsubscribe = eventRegistration.onPaymentSetup(() => {

      const rawNumber = cardData.card_number;
      const cleanNumber = rawNumber.replace(/\s+/g, '');
      const { card_number, card_expiry, card_cvc, card_holder } = cardData;

      // validate card data
      if (!validateLuhn(cleanNumber)) {
        return {
          type: emitResponse.responseTypes.ERROR,
          message: __('Invalid card number', 'nbo-payment-gateway'),
        };
      }
      if (!validateExpiry(card_expiry)) {
        return {
          type: emitResponse.responseTypes.ERROR,
          message: __('Invalid date', 'nbo-payment-gateway'),
        };
      }
      if (!validateCvc(card_cvc)) {
        return {
          type: emitResponse.responseTypes.ERROR,
          message: __('Invalid CVC', 'nbo-payment-gateway'),
        };
      }
      if (!card_holder) {
        return {
          type: emitResponse.responseTypes.ERROR,
          message: __('Holder name is required', 'nbo-payment-gateway'),
        };
      }
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            card_number: cleanNumber,
            card_expiry,
            card_cvc,
            card_holder,
            ...getBrowserData(),
          },
        },
      };
    });
    return () => unsubscribe();
  }, [eventRegistration, emitResponse, billingAddress, shippingAddress, cardData]);
  return <CardFields onChange={setCardData} />;
};

registerPaymentMethod({
  name: 'nbo_payment_gateway_standard_gateway',
  label: <Label />,
  ariaLabel: __('NBO Standard Gateway', 'nbo-payment-gateway'),
  canMakePayment: () => true,
  content: <PaymentMethod />,
  edit: <PaymentMethod />,
  supports: { features: ['products'] },
});
