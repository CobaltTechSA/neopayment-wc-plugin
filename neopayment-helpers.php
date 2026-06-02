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
