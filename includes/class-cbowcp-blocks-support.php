<?php
/**
 * Blocks Support class for CBO Payment Gateway plugin.
 *
 * @package CBOWPC_Payment_Gateway
 */

namespace CBO\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks integration for the payment gateway.
 */
final class CBOWCP_Blocks_Support {

	/**
	 * Initializes the blocks support hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Integrations for the CBO Standard and Telered Blocks payment methods.
		require_once CBOWCP_PATH . 'includes/blocks/class-cbowcp-standard-blocks.php';
		require_once CBOWCP_PATH . 'includes/blocks/class-cbowcp-telered-blocks.php';

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
		if ( class_exists( '\CBO\Blocks\CBOWCP_Standard_Blocks' ) ) {
			$registry->register( new \CBO\Blocks\CBOWCP_Standard_Blocks() );
		}
		if ( class_exists( '\CBO\Blocks\CBOWCP_Telered_Blocks' ) ) {
			$registry->register( new \CBO\Blocks\CBOWCP_Telered_Blocks() );
		}
	}

	/**
	 * Scripts.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		// Standard Payments.
		$standard_asset = include CBOWCP_PATH . 'build/cbowcp-standard.asset.php';
		wp_register_script(
			'cbowcp-standard-blocks-js',
			CBOWCP_URL . 'build/cbowcp-standard.js',
			$standard_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$standard_asset['version'] ?? CBOWCP_Constants::PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cbowcp-standard-blocks-js',
			'class-cbowcp-payment-gateway',
			CBOWCP_PATH . 'i18n'
		);

		wp_register_script(
			'cbowcp-3ds-popup',
			CBOWCP_URL . 'assets/js/cbowcp-3ds-popup.js',
			array( 'jquery', 'cbowcp-standard-blocks-js' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'cbowcp-standard-blocks-js',
			'CBOWCP3DS',
			array(
				'url_ok'   => esc_url_raw( home_url( '/wc-api/cbowcp_standard_gateway_status' ) ),
				'url_ko'   => esc_url_raw( home_url( '/wc-api/cbowcp_standard_gateway_status' ) ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cbowcp_3ds_nonce' ),
			)
		);

		// Telered Payments.
		$telered_asset = include CBOWCP_PATH . 'build/cbowcp-telered.asset.php';
		wp_register_script(
			'cbowcp-telered-blocks-js',
			CBOWCP_URL . 'build/cbowcp-telered.js',
			$telered_asset['dependencies'] ?? array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n',
			),
			$telered_asset['version'] ?? CBOWCP_Constants::PLUGIN_VERSION,
			true
		);

		wp_set_script_translations(
			'cbowcp-telered-blocks-js',
			'class-cbowcp-payment-gateway',
			CBOWCP_PATH . 'i18n'
		);

	}

	/**
	 * Styles.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_enqueue_style(
			'cbowcp-card-fields-style',
			CBOWCP_URL . 'assets/css/cbowcp-card-fields.css',
			array(),
			'1.0.0'
		);
	}
}

CBOWCP_Blocks_Support::init();
