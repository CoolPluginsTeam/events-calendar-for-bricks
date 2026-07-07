<?php
/**
 * Event cost labels and currency formatting.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Cost_Formatter', false ) ) {

	final class ECBB_Cost_Formatter {

		/** @return array<string,string> */
		private static function symbols(): array {
			return [
				'USD' => '$', 'EUR' => "\u{20AC}", 'GBP' => "\u{00A3}", 'CAD' => '$', 'AUD' => '$',
				'INR' => "\u{20B9}", 'JPY' => "\u{00A5}", 'CNY' => "\u{00A5}", 'CHF' => 'Fr',
				'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr', 'NZD' => '$', 'ZAR' => 'R',
				'BRL' => 'R$', 'MXN' => '$', 'SGD' => '$', 'HKD' => '$',
				'AED' => "\u{062F}.\u{0625}", 'SAR' => "\u{FDFC}",
			];
		}

		/** @return array<string,string> */
		private static function currency_labels(): array {
			return [
				'USD' => 'US Dollar',
				'EUR' => 'Euro',
				'GBP' => 'British Pound',
				'CAD' => 'Canadian Dollar',
				'AUD' => 'Australian Dollar',
				'INR' => 'Indian Rupee',
				'JPY' => 'Japanese Yen',
				'CNY' => 'Chinese Yuan',
				'CHF' => 'Swiss Franc',
				'SEK' => 'Swedish Krona',
				'NOK' => 'Norwegian Krone',
				'DKK' => 'Danish Krone',
				'NZD' => 'New Zealand Dollar',
				'ZAR' => 'South African Rand',
				'BRL' => 'Brazilian Real',
				'MXN' => 'Mexican Peso',
				'SGD' => 'Singapore Dollar',
				'HKD' => 'Hong Kong Dollar',
				'AED' => 'UAE Dirham',
				'SAR' => 'Saudi Riyal',
			];
		}

		/** Currency codes for the cost part control. */
		public static function ecbb_cost_currency_opts(): array {
			$options = [
				'default' => esc_html__( 'Site default', 'events-calendar-for-bricks' ),
				'none'    => esc_html__( 'No currency symbol', 'events-calendar-for-bricks' ),
			];
			$symbols = self::symbols();
			foreach ( array_keys( self::currency_labels() ) as $code ) {
				$options[ $code ] = $code . ' (' . $symbols[ $code ] . ')';
			}
			return $options;
		}

		/** Normalize saved currency code to a known option key. */
		public static function ecbb_sanitize_cost_currency( $value ): string {
			$code = is_string( $value ) ? strtoupper( trim( $value ) ) : '';
			if ( $code === '' ) {
				return 'default';
			}
			$aliases = [ 'DEFAULT' => 'default', 'SYMBOL' => 'default', 'NONE' => 'none' ];
			if ( isset( $aliases[ $code ] ) ) {
				return $aliases[ $code ];
			}
			return isset( self::symbols()[ $code ] ) ? $code : 'default';
		}

		/** True when token is zero or a common “free” label. */
		public static function ecbb_cost_is_free( $token ): bool {
			$t = trim( wp_strip_all_tags( html_entity_decode( (string) $token, ENT_QUOTES, 'UTF-8' ) ) );
			if ( $t === '' ) {
				return false;
			}
			if ( in_array( strtolower( $t ), [ 'free', 'gratis', 'no cost', 'nocost', 'included' ], true ) ) {
				return true;
			}
			$num = preg_replace( '/^[\p{Sc}\s]+/u', '', $t );
			$num = preg_replace( '/[\p{Sc}\s]+$/u', '', $num );
			return $num !== '' && (bool) preg_match( '/^0(?:\.0+)?$/', $num );
		}

		/** Display symbol for a sanitized currency code (empty for default/none). */
		public static function ecbb_cost_currency_symbol( $currency_code ): string {
			$map  = self::symbols();
			$code = self::ecbb_sanitize_cost_currency( $currency_code );
			return $map[ $code ] ?? '';
		}

		/** Strip currency symbols from one cost fragment. */
		public static function ecbb_strip_cost_symbols( $token ): string {
			$t = trim( wp_strip_all_tags( html_entity_decode( (string) $token, ENT_QUOTES, 'UTF-8' ) ) );
			if ( $t === '' ) {
				return '';
			}
			$t = preg_replace( '/^[\p{Sc}\s]+/u', '', $t );
			$t = preg_replace( '/[\p{Sc}\s]+$/u', '', $t );
			return trim( (string) $t );
		}

		/** Apply currency symbol to one cost token. */
		public static function ecbb_format_cost_token( $token, $currency_code ): string {
			$token = trim( wp_strip_all_tags( html_entity_decode( (string) $token, ENT_QUOTES, 'UTF-8' ) ) );
			if ( $token === '' ) {
				return '';
			}
			if ( self::ecbb_cost_is_free( $token ) ) {
				return __( 'Free', 'events-calendar-for-bricks' );
			}
			$currency_code = self::ecbb_sanitize_cost_currency( $currency_code );
			if ( $currency_code === 'default' ) {
				return $token;
			}
			if ( $currency_code === 'none' ) {
				$plain = self::ecbb_strip_cost_symbols( $token );
				return $plain !== '' ? $plain : $token;
			}
			$symbol = self::ecbb_cost_currency_symbol( $currency_code );
			$amount = self::ecbb_strip_cost_symbols( $token );
			if ( $amount === '' ) {
				return $token;
			}
			return $symbol !== '' ? $symbol . $amount : $amount;
		}

		/** Format a full cost string (single value or min–max range). */
		public static function ecbb_apply_cost_currency( $cost_text, $currency_code ): string {
			$cost_text = trim( wp_strip_all_tags( html_entity_decode( (string) $cost_text, ENT_QUOTES, 'UTF-8' ) ) );
			if ( $cost_text === '' ) {
				return '';
			}
			$currency_code = self::ecbb_sanitize_cost_currency( $currency_code );
			if ( $currency_code === 'default' ) {
				return $cost_text;
			}
			if ( self::ecbb_cost_is_free( $cost_text ) ) {
				return __( 'Free', 'events-calendar-for-bricks' );
			}
			if ( preg_match( '/^(.+?)([-\x{2013}\x{2014}])(.+)$/u', $cost_text, $m ) ) {
				$left  = self::ecbb_format_cost_token( trim( $m[1] ), $currency_code );
				$right = self::ecbb_format_cost_token( trim( $m[3] ), $currency_code );
				if ( $left !== '' && $right !== '' ) {
					return strcasecmp( $left, $right ) === 0 ? $left : $left . ' - ' . $right;
				}
			}
			return self::ecbb_format_cost_token( $cost_text, $currency_code );
		}

		/** Currency code for one cost row (row setting → widget fallback). */
		public static function ecbb_resolve_cost_currency( array $item = [] ): string {
			if ( isset( $item['cost_currency'] ) && (string) $item['cost_currency'] !== '' ) {
				return self::ecbb_sanitize_cost_currency( $item['cost_currency'] );
			}
			$settings = ECBB_Markup::ecbb_active_widget_settings();
			if ( isset( $settings['event_cost_currency'] ) ) {
				return self::ecbb_sanitize_cost_currency( $settings['event_cost_currency'] );
			}
			return 'default';
		}

		/** Plain-text cost for an event (Free / amount / range). */
		public static function ecbb_format_cost_display( $post_id, array $item = [] ): string {
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return '';
			}
			$currency = self::ecbb_resolve_cost_currency( $item );
			$with_sym = $currency === 'default';

			$formatted = '';
			if ( function_exists( 'tribe_get_cost' ) ) {
				$formatted = trim( wp_strip_all_tags( html_entity_decode( (string) tribe_get_cost( $post_id, $with_sym ), ENT_QUOTES, 'UTF-8' ) ) );
			}
			$cost = $formatted;
			if ( $cost === '' ) {
				$cost = trim( wp_strip_all_tags( html_entity_decode( (string) get_post_meta( $post_id, '_EventCost', true ), ENT_QUOTES, 'UTF-8' ) ) );
			}

			if ( $cost === '' || self::ecbb_cost_is_free( $cost ) ) {
				return __( 'Free', 'events-calendar-for-bricks' );
			}
			if ( preg_match( '/^(.+?)([-\x{2013}\x{2014}])(.+)$/u', $cost, $m ) ) {
				$left  = trim( $m[1] );
				$right = trim( $m[3] );
				if ( $left !== '' && $right !== '' ) {
					if ( strcasecmp( $left, $right ) === 0 ) {
						$cost = self::ecbb_cost_is_free( $left ) ? __( 'Free', 'events-calendar-for-bricks' ) : $left;
					} elseif ( self::ecbb_cost_is_free( $left ) && self::ecbb_cost_is_free( $right ) ) {
						$cost = __( 'Free', 'events-calendar-for-bricks' );
					} else {
						$cost = $left . ' - ' . $right;
					}
				}
			}
			return self::ecbb_apply_cost_currency( $cost, $currency );
		}

		/** List-style cost label with “From …” prefix when needed. */
		public static function ecbb_layout_cost_label( $post_id, array $item = [] ): string {
			$cost = self::ecbb_format_cost_display( $post_id, $item );
			if ( $cost === '' ) {
				return '';
			}
			if ( self::ecbb_cost_is_free( $cost ) ) {
				return __( 'Free', 'events-calendar-for-bricks' );
			}
			if ( stripos( $cost, 'from' ) !== 0 ) {
				return sprintf(
					/* translators: %s: event price */
					__( 'From %s', 'events-calendar-for-bricks' ),
					$cost
				);
			}
			return $cost;
		}
	}
}
