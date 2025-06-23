import { createElement, useEffect } from '@wordpress/element';
import { usePaymentFormContext } from '@woocommerce/blocks-checkout';

export const CardFields = () => {
    const { emitResponse, setValidationErrors } = usePaymentFormContext();

    useEffect( () => {
        emitResponse( {
            isValid: true,
            errors: [],
        } );

        const handleValidationErrors = ( errors ) => {
            setValidationErrors( errors );
        };

        window.addEventListener( 'cbo_validation_errors', handleValidationErrors );

        return () => {
            window.removeEventListener( 'cbo_validation_errors', handleValidationErrors );
        };
    }, [] );

    return (
        <div className="cbo-card-fields">
            <input type="text" name="cbo_card_number" placeholder="•••• •••• •••• ••••" />
            <input type="text" name="cbo_card_expiry"  placeholder="MM / AA" />
            <input type="text" name="cbo_card_cvc"     placeholder="CVC" />
        </div>
    );
};
