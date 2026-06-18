<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for Neopayment plugin.
 *
 * @package NEOPAYMENT
 */
class NEOPAYMENT_Helpers {

	/**
	 * Valid Luhn for card numbers.
	 *
	 * @param int|string $number Card number from user.
	 * @return bool
	 */
	public static function is_valid_luhn( $number ) {
		if ( empty( $number ) ) {
			return false;
		}

		$number = (string) $number;

		$sum_table = array(
			array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ),
			array( 0, 2, 4, 6, 8, 1, 3, 5, 7, 9 ),
		);

		$sum  = 0;
		$flip = 0;

		for ( $i = strlen( $number ) - 1; $i >= 0; $i-- ) {
			$digit = (int) $number[ $i ];
			$sum  += $sum_table[ $flip++ & 1 ][ $digit ];
		}

		return 0 === ( $sum % 10 );
	}

	/**
	 * Valid expiry date for cards.
	 *
	 * @param string $expiry_date Expire card date from user.
	 * @return bool
	 */
	public static function is_valid_expiry_date( $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return false;
		}

		settype( $expiry_date, 'string' );
		$date         = DateTime::createFromFormat( 'm/y', $expiry_date );
		$current_date = new DateTime();

		return $date > $current_date;
	}

	/**
	 * Valid card holder name for card data.
	 *
	 * @param string $card_holder The name of holder card.
	 * @return bool
	 */
	public static function is_valid_card_holder( $card_holder ) {
		if ( empty( $card_holder ) ) {
			return false;
		}

		if ( strlen( $card_holder ) < 3 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate strlen CVV for minimum of three.
	 *
	 * @param int|string $cvv Valid with three or four numbers.
	 * @return bool
	 */
	public static function is_valid_cvv( $cvv ) {
		if ( empty( $cvv ) ) {
			return false;
		}

		if ( strlen( $cvv ) < 3 ) {
			return false;
		}

		return true;
	}

	/**
	 * Normalize state to three characters for 3DS / API fields.
	 *
	 * @param string $state State string.
	 * @return string
	 */
	public static function parse_state( $state ) {
		$state = str_replace( '-', '', $state );
		if ( strlen( $state ) > 3 ) {
			$state = substr( $state, 0, 3 );
		} else {
			$state = str_pad( $state, 3, '-' );
		}
		return $state;
	}

	/**
	 * Reads and decodes the JSON request body from php://input.
	 *
	 * @return array Decoded and recursively sanitized array, or empty array on failure.
	 */
	public static function get_sanitized_json_input() {
		$raw_input = file_get_contents( 'php://input' );
		if ( ! is_string( $raw_input ) || '' === $raw_input ) {
			return array();
		}

		$decoded = json_decode( $raw_input, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		return self::recursive_sanitize( $decoded );
	}

	/**
	 * Allowed `payment_data` keys from WooCommerce Blocks checkout.
	 *
	 * @return string[]
	 */
	public static function get_allowed_blocks_payment_keys() {
		return array(
			'card_number',
			'card_expiry',
			'card_cvc',
			'card_holder',
			'browserJavaEnabled',
			'browserJavascriptEnabled',
			'browserLanguage',
			'browserColorDepth',
			'browserScreenWidth',
			'browserScreenHeight',
			'browserTZ',
			'browserUserAgent',
			'challengeWindowSize',
		);
	}

	/**
	 * Maps Blocks `payment_data` entries to a whitelist-keyed array.
	 *
	 * @param mixed $payment_data Raw `payment_data` from checkout JSON.
	 * @return array<string, string>
	 */
	public static function map_blocks_payment_data( $payment_data ) {
		if ( ! is_array( $payment_data ) ) {
			return array();
		}

		$allowed = array_flip( self::get_allowed_blocks_payment_keys() );
		$mapped  = array();

		foreach ( $payment_data as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['key'], $field['value'] ) ) {
				continue;
			}

			$key = is_string( $field['key'] ) ? $field['key'] : '';
			if ( '' === $key || ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$mapped[ $key ] = is_scalar( $field['value'] ) ? wc_clean( (string) $field['value'] ) : '';
		}

		return $mapped;
	}

	/**
	 * Recursively sanitizes values from `parse_str()` without lowercasing keys.
	 *
	 * @param array $data Parsed query-string style array.
	 * @return array
	 */
	public static function sanitize_parsed_str_array( array $data ) {
		$sanitized = array();

		foreach ( $data as $key => $item ) {
			if ( is_string( $key ) ) {
				$safe_key = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key );
				if ( '' === $safe_key ) {
					continue;
				}
			} elseif ( is_int( $key ) ) {
				$safe_key = $key;
			} else {
				continue;
			}

			if ( is_array( $item ) ) {
				$sanitized[ $safe_key ] = self::sanitize_parsed_str_array( $item );
			} elseif ( is_string( $item ) ) {
				$sanitized[ $safe_key ] = sanitize_text_field( $item );
			} elseif ( is_scalar( $item ) ) {
				$sanitized[ $safe_key ] = $item;
			}
		}

		return $sanitized;
	}

	/**
	 * Validates and normalizes a gateway transaction payload (API or webhook).
	 *
	 * @param array $transaction Raw transaction array.
	 * @return array|null Normalized data, or null when invalid.
	 */
	public static function parse_gateway_transaction( array $transaction ) {
		$known_statuses = array(
			'authorized',
			'notified',
			'refused',
			'failed',
			'pending',
			'authenticating',
		);

		if ( ! isset( $transaction['metadatas'] ) || ! is_array( $transaction['metadatas'] ) ) {
			return null;
		}

		$order_id = isset( $transaction['metadatas']['order_id'] ) ? absint( $transaction['metadatas']['order_id'] ) : 0;
		if ( $order_id <= 0 ) {
			return null;
		}

		$status = isset( $transaction['status'] ) ? sanitize_text_field( (string) $transaction['status'] ) : '';
		if ( '' === $status || ! in_array( $status, $known_statuses, true ) ) {
			return null;
		}

		return array(
			'order_id'              => $order_id,
			'status'                => $status,
			'response_code'         => isset( $transaction['response_code'] ) ? sanitize_text_field( (string) $transaction['response_code'] ) : '',
			'identifier'            => isset( $transaction['identifier'] ) ? sanitize_text_field( (string) $transaction['identifier'] ) : '',
			'authorization_number'  => isset( $transaction['authorization_number'] ) ? sanitize_text_field( (string) $transaction['authorization_number'] ) : '',
			'success_statuses'      => array( 'authorized', 'notified' ),
			'failure_statuses'      => array( 'refused', 'failed' ),
		);
	}

	/**
	 * Recursively sanitizes decoded JSON values.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	public static function recursive_sanitize( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $item ) {
				$sanitized_key            = is_string( $key ) ? sanitize_key( $key ) : $key;
				$sanitized[ $sanitized_key ] = self::recursive_sanitize( $item );
			}
			return $sanitized;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return '';
	}
}
