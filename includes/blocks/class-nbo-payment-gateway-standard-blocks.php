<?php
/**
 * Blocks Standard class for NBO Payment Gateway plugin.
 *
 * @package NBO_PAYMENT_GATEWAY
 */

namespace NboPaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration for the NBO Standard Blocks payment method.
 */
final class NBO_PAYMENT_GATEWAY_Standard_Blocks extends AbstractPaymentMethodType {

	/**
	 * Payment method name for the NBO Standard gateway.
	 *
	 * @var string
	 */
	protected $name = 'nbo_payment_gateway_standard_gateway';


	/**
	 * Initializes the blocks standard hooks.
	 *
	 * @return void
	 */
	public function initialize() {}

	/**
	 * Handles the payment method type.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		return array( 'nbo-payment-gateway-standard-blocks-js' );
	}

	/**
	 * Data to be passed to the payment method block.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $this->name ];

		return array(
			'title'       => $gateway->title,
			'description' => $gateway->description,
			'supports'    => $gateway->supports,
			'icons'       => $this->nbo_payment_gateway_get_icons(),
			'testmode'    => $gateway->testmode,
		);
	}

	/**
	 * Card icons.
	 */
	protected function nbo_payment_gateway_get_icons() {
		return array(
			array(
				'id'  => 'visa',
				'src' => NBO_PAYMENT_GATEWAY_URL . 'assets/images/visa.svg',
				'alt' => __( 'Visa', 'nbo-payment-gateway' ),
			),
			array(
				'id'  => 'mastercard',
				'src' => NBO_PAYMENT_GATEWAY_URL . 'assets/images/mastercard.svg',
				'alt' => __( 'Mastercard', 'nbo-payment-gateway' ),
			),
		);
	}
}
