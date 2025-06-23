<?php
namespace CBO\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integración de CBO Telered/Clave Gateway con Cart & Checkout Blocks
 */
final class CBO_Telered_Blocks extends AbstractPaymentMethodType {

    protected $name = 'cbo_telered_gateway';

    public function initialize() {}

    public function get_payment_method_script_handles() {
        return [ 'cbo-telered-blocks-js' ];
    }

    public function get_payment_method_data() {
        return [
            'title'       => __( 'Tarjeta Clave', 'cbo-payment-gateway' ),
            'description' => __( 'Paga con tu tarjeta Clave.', 'cbo-payment-gateway' ),
            'supports'    => [ 'products' ],
        ];
    }
}
