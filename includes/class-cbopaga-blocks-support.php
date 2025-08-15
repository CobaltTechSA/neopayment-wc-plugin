<?php
/**
 * Blocks Support class for CBO Payment Gateway plugin.
 *
 * @package CBO_Payment_Gateway
 */

namespace CBO\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks integration for the payment gateway.
 */
final class CBOPAGA_Blocks_Support {

	/**
	 * Initializes the blocks support hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Integrations for the CBO Standard and Telered Blocks payment methods.
		require_once CBOPAGA_PATH . 'includes/blocks/class-cbopaga-standard-blocks.php';
		require_once CBOPAGA_PATH . 'includes/blocks/class-cbopaga-telered-blocks.php';

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
		if ( class_exists( '\CBO\Blocks\CBOPAGA_Standard_Blocks' ) ) {
			$registry->register( new \CBO\Blocks\CBOPAGA_Standard_Blocks() );
		}
		if ( class_exists( '\CBO\Blocks\CBOPAGA_Telered_Blocks' ) ) {
			$registry->register( new \CBO\Blocks\CBOPAGA_Telered_Blocks() );
		}
	}

	/**
	 * Scripts.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		// Standard Payments.
		$standard_asset = include CBOPAGA_PATH . 'build/cbo-standard.asset.php';
		wp_register_script(
			'cbo-standard-blocks-js',
			CBOPAGA_URL . 'build/cbo-standard.js',
			$standard_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$standard_asset['version'] ?? CBOPAGA_Constants::PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cbo-standard-blocks-js',
			'class-cbopaga-payment-gateway',
			CBOPAGA_PATH . 'i18n'
		);

		wp_register_script(
			'cbo-3ds-popup',
			CBOPAGA_URL . 'assets/js/cbo-3ds-popup.js',
			array( 'jquery', 'cbo-standard-blocks-js' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'cbo-standard-blocks-js',
			'CBOPAGA3DS',
			array(
				'url_ok'   => esc_url_raw( home_url( '/wc-api/cbopaga_standard_gateway_status' ) ),
				'url_ko'   => esc_url_raw( home_url( '/wc-api/cbopaga_standard_gateway_status' ) ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cbopaga_3ds_nonce' ),
			)
		);

		// Telered Payments.
		$telered_asset = include CBOPAGA_PATH . 'build/cbo-telered.asset.php';
		wp_register_script(
			'cbo-telered-blocks-js',
			CBOPAGA_URL . 'build/cbo-telered.js',
			$telered_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$telered_asset['version'] ?? CBOPAGA_Constants::PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cbo-telered-blocks-js',
			'class-cbopaga-payment-gateway',
			CBOPAGA_PATH . 'i18n'
		);

	}

	/**
	 * Styles.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_enqueue_style(
			'cbo-card-fields-style',
			CBOPAGA_URL . 'assets/css/cbo-card-fields.css',
			array(),
			'1.0.0'
		);
	}
}

CBOPAGA_Blocks_Support::init();
