<?php
namespace CBO\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integración de CBO Standard Gateway con Cart & Checkout Blocks
 */
final class CBO_Standard_Blocks extends AbstractPaymentMethodType {

    protected $name = 'cbo_standard_gateway';


    public function initialize() {
       
    }

    /**
     * Handles de los scripts JS registrados en wp_register_script()
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        return [ 'cbo-standard-blocks-js' ];
    }

    /**
     * Data que se expone en window.wc.wcSettings.paymentMethods
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => __( 'Tarjeta (Visa/Mastercard)', 'cbo-payment-gateway' ),
            'description' => __( 'Paga de forma segura con tu tarjeta.', 'cbo-payment-gateway' ),
            'supports'    => [ 'products' ],
            'icons'       => [], 
        ];
    }
}
