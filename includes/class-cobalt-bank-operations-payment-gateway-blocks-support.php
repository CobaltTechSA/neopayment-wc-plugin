<?php

/**
 * Blocks Support class for CBO Payment Gateway plugin.
 *
 * @package COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY
 */

namespace CobaltBankOperationsPaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks integration for the payment gateway.
 */
final class COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Blocks_Support {

	/**
	 * Initializes the blocks support hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Integrations for the CBO Standard and Telered Blocks payment methods.
		require_once COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'includes/blocks/class-cobalt-bank-operations-payment-gateway-standard-blocks.php';
		require_once COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'includes/blocks/class-cobalt-bank-operations-payment-gateway-telered-blocks.php';

		// Integrations registration.
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			array( __CLASS__, 'register_blocks' )
		);

		add_action( 'init', array( __CLASS__, 'register_scripts' ) );
		add_action( 'init', array( __CLASS__, 'register_styles' ) );
	}

	/**
	 * Initialize the payment gateway blocks.
	 *
	 * @param PaymentMethodRegistry $registry Registry for payment methods.
	 * @return void
	 */
	public static function register_blocks( PaymentMethodRegistry $registry ) {
		if ( class_exists( '\CobaltBankOperationsPaymentGateway\Blocks\COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Standard_Blocks' ) ) {
			$registry->register( new \CobaltBankOperationsPaymentGateway\Blocks\COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Standard_Blocks() );
		}
		if ( class_exists( '\CobaltBankOperationsPaymentGateway\Blocks\COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Telered_Blocks' ) ) {
			$registry->register( new \CobaltBankOperationsPaymentGateway\Blocks\COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Telered_Blocks() );
		}
	}

	/**
	 * Scripts.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		// Standard Payments.
		$standard_asset = include COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'build/cobalt-bank-operations-payment-gateway-standard.asset.php';
		wp_register_script(
			'cobalt-bank-operations-payment-gateway-standard-blocks-js',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_URL . 'build/cobalt-bank-operations-payment-gateway-standard.js',
			$standard_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$standard_asset['version'] ?? COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Constants::COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cobalt-bank-operations-payment-gateway-standard-blocks-js',
			'cobalt-bank-operations-payment-gateway',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'i18n'
		);

		wp_register_script(
			'cobalt-bank-operations-payment-gateway-3ds-popup',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_URL . 'assets/js/cobalt-bank-operations-payment-gateway-3ds-popup.js',
			array( 'jquery', 'cobalt-bank-operations-payment-gateway-standard-blocks-js' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'cobalt-bank-operations-payment-gateway-standard-blocks-js',
			'cobalt_bank_operations_payment_gateway_3DS',
			array(
				'url_ok'   => esc_url_raw( home_url( '/wc-api/cobalt_bank_operations_payment_gateway_standard_gateway_status' ) ),
				'url_ko'   => esc_url_raw( home_url( '/wc-api/cobalt_bank_operations_payment_gateway_standard_gateway_status' ) ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cobalt_bank_operations_payment_gateway_3ds_nonce' ),
			)
		);

		// Telered Payments.
		$telered_asset = include COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'build/cobalt-bank-operations-payment-gateway-telered.asset.php';
		wp_register_script(
			'cobalt-bank-operations-payment-gateway-telered-blocks-js',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_URL . 'build/cobalt-bank-operations-payment-gateway-telered.js',
			$telered_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$telered_asset['version'] ?? COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Constants::COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cobalt-bank-operations-payment-gateway-telered-blocks-js',
			'cobalt-bank-operations-payment-gateway',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_PATH . 'i18n'
		);
	}

	/**
	 * Styles.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_enqueue_style(
			'cobalt-bank-operations-payment-gateway-card-fields-style',
			COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_URL . 'assets/css/cobalt-bank-operations-payment-gateway-card-fields.css',
			array(),
			'1.0.0'
		);
	}
}

COBALT_BANK_OPERATIONS_PAYMENT_GATEWAY_Blocks_Support::init();
