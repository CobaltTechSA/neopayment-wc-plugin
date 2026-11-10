<?php
/**
 * Logger helper for Neopayment plugin.
 *
 * @package NEOPAYMENT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce logs for the payment gateway.
 */
class NEOPAYMENT_Log {

	/**
	 * Logger instance.
	 *
	 * @var \WC_Logger|\Psr\Log\LoggerInterface|null
	 */
	private static $instance = null;

	/**
	 * Returns the shared logger instance.
	 *
	 * @return \WC_Logger|\Psr\Log\LoggerInterface Logger instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = wc_get_logger();
		}

		return self::$instance;
	}

	/**
	 * Writes a message to the WooCommerce logger with the given level.
	 *
	 * @param string             $level   Log level.
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function log( $level, $message ) {
		$logger  = self::get_instance();
		$context = array( 'source' => 'neopayment' );
		$logger->{$level}( self::redact_text( (string) $message ), $context );
	}

	/**
	 * Logs a filtered API payload.
	 *
	 * @param string $level   Log level.
	 * @param string $label   Prefix.
	 * @param mixed  $payload Payload.
	 * @return void
	 */
	public static function payload( $level, $label, $payload ) {
		self::log(
			$level,
			self::redact_text( (string) $label ) . wp_json_encode( self::operational_fields( $payload ) )
		);
	}

	/**
	 * Cleans free text before it is stored.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function redact_text( $text ) {
		$text    = (string) $text;
		$cleaned = preg_replace( '/(?<!\d)(?:\d[ \-]?){12,18}\d(?!\d)/', '[removed]', $text );
		if ( ! is_string( $cleaned ) ) {
			return '';
		}

		$cleaned = preg_replace( '/\b(cvv2?|cvc2?|cid)\b\D{0,12}\d{3,4}\b/i', '$1 [removed]', $cleaned );

		return is_string( $cleaned ) ? $cleaned : '';
	}

	/**
	 * Filters a payload down to the fields that are stored.
	 *
	 * @param mixed $payload Payload.
	 * @return array
	 */
	private static function operational_fields( $payload ) {
		if ( ! is_array( $payload ) ) {
			return array();
		}

		$keep       = array(
			'code'                 => true,
			'status'               => true,
			'message'              => true,
			'error'                => true,
			'identifier'           => true,
			'id'                   => true,
			'tid'                  => true,
			'response_code'        => true,
			'authorization_number' => true,
			'order_id'             => true,
			'slug'                 => true,
			'expires_in'           => true,
			'result'               => true,
		);
		$containers = array(
			'body'      => true,
			'data'      => true,
			'metadatas' => true,
			'metas'     => true,
			'errors'    => true,
			'error'     => true,
			'message'   => true,
		);
		$out = array();
		foreach ( $payload as $key => $value ) {
			if ( is_int( $key ) ) {
				if ( is_array( $value ) ) {
					$child = self::operational_fields( $value );
					if ( ! empty( $child ) ) {
						$out[] = $child;
					}
				}
				continue;
			}

			$name = strtolower( (string) $key );
			if ( is_array( $value ) && isset( $containers[ $name ] ) ) {
				$child = self::operational_fields( $value );
				if ( ! empty( $child ) ) {
					$out[ $key ] = $child;
				}
				continue;
			}

			if ( ! isset( $keep[ $name ] ) || ( ! is_scalar( $value ) && null !== $value ) ) {
				continue;
			}

			if ( is_string( $value ) ) {
				$value = self::redact_text( $value );
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$as_text = self::redact_text( (string) $value );
				if ( $as_text !== (string) $value ) {
					$value = $as_text;
				}
			}

			$out[ $key ] = $value;
		}

		return $out;
	}

	/**
	 * Logs a debug message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function debug( $message ) {
		self::log( 'debug', $message );
	}

	/**
	 * Logs an info message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function info( $message ) {
		self::log( 'info', $message );
	}

	/**
	 * Logs a notice message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function notice( $message ) {
		self::log( 'notice', $message );
	}

	/**
	 * Logs a warning message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function warning( $message ) {
		self::log( 'warning', $message );
	}

	/**
	 * Logs an error message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function error( $message ) {
		self::log( 'error', $message );
	}

	/**
	 * Logs a critical message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function critical( $message ) {
		self::log( 'critical', $message );
	}

	/**
	 * Logs an alert message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function alert( $message ) {
		self::log( 'alert', $message );
	}

	/**
	 * Logs an emergency message.
	 *
	 * @param string|\Stringable $message Message to write.
	 * @return void
	 */
	public static function emergency( $message ) {
		self::log( 'emergency', $message );
	}
}
