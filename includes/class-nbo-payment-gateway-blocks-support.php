<?php

/**
 * Blocks Support class for NBO Payment Gateway plugin.
 *
 * @package NBO_PAYMENT_GATEWAY
 */

namespace NboPaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks integration for the payment gateway.
 */
final class NBO_PAYMENT_GATEWAY_Blocks_Support {

	/**
	 * Initializes the blocks support hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Integrations for the NBO Standard and Telered Blocks payment methods.
		require_once NBO_PAYMENT_GATEWAY_PATH . 'includes/blocks/class-nbo-payment-gateway-standard-blocks.php';
		require_once NBO_PAYMENT_GATEWAY_PATH . 'includes/blocks/class-nbo-payment-gateway-telered-blocks.php';

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
		if ( class_exists( '\NboPaymentGateway\Blocks\NBO_PAYMENT_GATEWAY_Standard_Blocks' ) ) {
			$registry->register( new \NboPaymentGateway\Blocks\NBO_PAYMENT_GATEWAY_Standard_Blocks() );
		}
		if ( class_exists( '\NboPaymentGateway\Blocks\NBO_PAYMENT_GATEWAY_Telered_Blocks' ) ) {
			$registry->register( new \NboPaymentGateway\Blocks\NBO_PAYMENT_GATEWAY_Telered_Blocks() );
		}
	}

	/**
	 * Scripts.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		// Standard Payments.
		$standard_asset = include NBO_PAYMENT_GATEWAY_PATH . 'build/nbo-payment-gateway-standard.asset.php';
		wp_register_script(
			'nbo-payment-gateway-standard-blocks-js',
			NBO_PAYMENT_GATEWAY_URL . 'build/nbo-payment-gateway-standard.js',
			$standard_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$standard_asset['version'] ?? NBO_PAYMENT_GATEWAY_Constants::NBO_PAYMENT_GATEWAY_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'nbo-payment-gateway-standard-blocks-js',
			'nbo-payment-gateway',
			NBO_PAYMENT_GATEWAY_PATH . 'i18n'
		);

		wp_register_script(
			'nbo-payment-gateway-3ds-popup',
			NBO_PAYMENT_GATEWAY_URL . 'assets/js/nbo-payment-gateway-3ds-popup.js',
			array( 'jquery', 'nbo-payment-gateway-standard-blocks-js' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'nbo-payment-gateway-standard-blocks-js',
			'nbo_payment_gateway_3DS',
			array(
				'url_ok'   => esc_url_raw( home_url( '/wc-api/nbo_payment_gateway_standard_gateway_status' ) ),
				'url_ko'   => esc_url_raw( home_url( '/wc-api/nbo_payment_gateway_standard_gateway_status' ) ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'nbo_payment_gateway_3ds_nonce' ),
			)
		);

		// Telered Payments.
		$telered_asset = include NBO_PAYMENT_GATEWAY_PATH . 'build/nbo-payment-gateway-telered.asset.php';
		wp_register_script(
			'nbo-payment-gateway-telered-blocks-js',
			NBO_PAYMENT_GATEWAY_URL . 'build/nbo-payment-gateway-telered.js',
			$telered_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$telered_asset['version'] ?? NBO_PAYMENT_GATEWAY_Constants::NBO_PAYMENT_GATEWAY_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'nbo-payment-gateway-telered-blocks-js',
			'nbo-payment-gateway',
			NBO_PAYMENT_GATEWAY_PATH . 'i18n'
		);
	}

	/**
	 * Styles.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_enqueue_style(
			'nbo-payment-gateway-card-fields-style',
			NBO_PAYMENT_GATEWAY_URL . 'assets/css/nbo-payment-gateway-card-fields.css',
			array(),
			'1.0.0'
		);
	}
}

NBO_PAYMENT_GATEWAY_Blocks_Support::init();
