<?php
/**
 * Blocks Telered class for Neopayment plugin.
 *
 * @package NEOPAYMENT
 */

namespace NeopaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration for the NEOPAYMENT Telered Blocks payment method.
 */
final class NEOPAYMENT_Telered_Blocks extends AbstractPaymentMethodType {

	/**
	 * Payment method name for the NEOPAYMENT Telered gateway.
	 *
	 * @var string
	 */
	protected $name = 'neopayment_telered_gateway';

	/**
	 * Initializes the blocks telered hooks.
	 *
	 * @return void
	 */
	public function initialize() {}

	/**
	 * Gets payment method script handles for WooCommerce Blocks.
	 *
	 * @return array Block Telered JS.
	 */
	public function get_payment_method_script_handles() {
		return array( 'neopayment-telered-blocks-js' );
	}

	/**
	 * Gets payment method data for WooCommerce Blocks.
	 *
	 * @return array Payment method data.
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => __( 'Clave Card', 'neopayment' ),
			'description' => __( 'Pay securely with your card', 'neopayment' ),
			'supports'    => array( 'products' ),
		);
	}
}
