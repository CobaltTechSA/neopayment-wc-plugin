<?php

/**
 * Blocks Support class for Neopayment plugin.
 *
 * @package NEOPAYMENT
 */

namespace NboPaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks integration for the payment gateway.
 */
final class NEOPAYMENT_Blocks_Support {

	/**
	 * Initializes the blocks support hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Integrations for the NEOPAYMENT Standard and Telered Blocks payment methods.
		require_once NEOPAYMENT_PATH . 'includes/blocks/class-neopayment-standard-blocks.php';
		require_once NEOPAYMENT_PATH . 'includes/blocks/class-neopayment-telered-blocks.php';

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
		if ( class_exists( '\NboPaymentGateway\Blocks\NEOPAYMENT_Standard_Blocks' ) ) {
			$registry->register( new \NboPaymentGateway\Blocks\NEOPAYMENT_Standard_Blocks() );
		}
		if ( class_exists( '\NboPaymentGateway\Blocks\NEOPAYMENT_Telered_Blocks' ) ) {
			$registry->register( new \NboPaymentGateway\Blocks\NEOPAYMENT_Telered_Blocks() );
		}
	}

	/**
	 * Scripts.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		// Standard Payments.
		$standard_asset = include NEOPAYMENT_PATH . 'build/neopayment-standard.asset.php';
		wp_register_script(
			'neopayment-standard-blocks-js',
			NEOPAYMENT_URL . 'build/neopayment-standard.js',
			$standard_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$standard_asset['version'] ?? NEOPAYMENT_Constants::NEOPAYMENT_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'neopayment-standard-blocks-js',
			'neopayment',
			NEOPAYMENT_PATH . 'i18n'
		);

		wp_register_script(
			'neopayment-3ds-popup',
			NEOPAYMENT_URL . 'assets/js/neopayment-3ds-popup.js',
			array( 'jquery', 'neopayment-standard-blocks-js' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'neopayment-standard-blocks-js',
			'neopayment_3DS',
			array(
				'url_ok'   => esc_url_raw( home_url( '/wc-api/neopayment_standard_gateway_status' ) ),
				'url_ko'   => esc_url_raw( home_url( '/wc-api/neopayment_standard_gateway_status' ) ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'neopayment_3ds_nonce' ),
			)
		);

		// Telered Payments.
		$telered_asset = include NEOPAYMENT_PATH . 'build/neopayment-telered.asset.php';
		wp_register_script(
			'neopayment-telered-blocks-js',
			NEOPAYMENT_URL . 'build/neopayment-telered.js',
			$telered_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$telered_asset['version'] ?? NEOPAYMENT_Constants::NEOPAYMENT_PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'neopayment-telered-blocks-js',
			'neopayment',
			NEOPAYMENT_PATH . 'i18n'
		);
	}

	/**
	 * Styles.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_enqueue_style(
			'neopayment-card-fields-style',
			NEOPAYMENT_URL . 'assets/css/neopayment-card-fields.css',
			array(),
			'1.0.0'
		);
	}
}

NEOPAYMENT_Blocks_Support::init();
