<?php
/**
 * ECBB_Meta_Combo service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Meta_Combo', false ) ) {

	final class ECBB_Meta_Combo {

		public static function ecbb_meta_combo_options_style2() {
			return self::ecbb_meta_combo_options( self::ecbb_meta_combo_slugs_style2() );
		}

		public static function ecbb_normalize_meta_combo_row( array $row ) {
			$part = (string) ( $row['part'] ?? '' );
			if ( ! self::ecbb_is_meta_combo_slug( $part ) ) {
				return $row;
			}

			if ( self::ecbb_meta_combo_has_segment( $part, 'venue' ) ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' || $display === 'name_and_state' ) {
					$row['venue_display'] = 'name_and_city';
				}
			}
			if ( self::ecbb_meta_combo_has_segment( $part, 'time' ) ) {
				$date_display = (string) ( $row['date_display'] ?? '' );
				if ( $date_display === '' ) {
					$row['date_display'] = 'time';
				}
			}
			if ( self::ecbb_meta_combo_has_segment( $part, 'cost' ) && ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) ) {
				$row['cost_currency'] = 'default';
			}

			return $row;
		}

		public static function ecbb_meta_combo_options_style1() {
			return self::ecbb_meta_combo_options( self::ecbb_meta_combo_slugs_style1() );
		}

		private static function ecbb_meta_combo_permutations( array $segments ) {
			$segments = array_values( $segments );
			$count    = count( $segments );
			if ( $count === 0 ) {
				return [];
			}
			if ( $count === 1 ) {
				return [ $segments ];
			}

			$out = [];
			foreach ( $segments as $i => $seg ) {
				$rest = $segments;
				array_splice( $rest, $i, 1 );
				foreach ( self::ecbb_meta_combo_permutations( $rest ) as $perm ) {
					$out[] = array_merge( [ $seg ], $perm );
				}
			}

			return $out;
		}

		public static function ecbb_meta_combo_label( array $order ) {
			$map = [
				'venue' => esc_html__( 'Venue', 'events-calendar-for-bricks' ),
				'time'  => esc_html__( 'Time', 'events-calendar-for-bricks' ),
				'cost'  => esc_html__( 'Cost', 'events-calendar-for-bricks' ),
			];
			$parts = [];
			foreach ( $order as $seg ) {
				if ( isset( $map[ $seg ] ) ) {
					$parts[] = $map[ $seg ];
				}
			}

			return implode( ' + ', $parts );
		}

		public static function ecbb_meta_combo_segments() {
			return [ 'venue', 'time', 'cost' ];
		}

		public static function ecbb_meta_combo_all_slugs() {
			return self::ecbb_meta_combo_slugs_style2();
		}

		public static function ecbb_meta_combo_has_segment( $slug, $segment ) {
			return in_array( (string) $segment, self::ecbb_meta_combo_order( $slug ), true );
		}

		public static function ecbb_meta_combo_slugs_style1() {
			return self::ecbb_meta_combo_slugs_for_segments( [ 'venue', 'time' ] );
		}

		public static function ecbb_meta_combo_options( array $slugs ) {
			$options = [];
			foreach ( $slugs as $slug ) {
				$order = self::ecbb_meta_combo_order( $slug );
				if ( $order !== [] ) {
					$options[ $slug ] = self::ecbb_meta_combo_label( $order );
				}
			}

			return $options;
		}

		public static function ecbb_is_meta_combo_slug( $slug ) {
			$order = self::ecbb_meta_combo_order( $slug );
			if ( count( $order ) < 2 ) {
				return false;
			}

			return self::ecbb_meta_combo_slug( $order ) === (string) $slug;
		}

		public static function ecbb_meta_combo_slugs_with_segment( $segment ) {
			$out = [];
			foreach ( self::ecbb_meta_combo_all_slugs() as $slug ) {
				if ( self::ecbb_meta_combo_has_segment( $slug, $segment ) ) {
					$out[] = $slug;
				}
			}

			return $out;
		}

		public static function ecbb_meta_combo_order( $slug ) {
			$slug  = (string) $slug;
			$order = [];
			$rest  = $slug;

			while ( $rest !== '' ) {
				$matched = false;
				foreach ( self::ecbb_meta_combo_segments() as $seg ) {
					if ( $rest === $seg ) {
						$order[] = $seg;
						$rest    = '';
						$matched = true;
						break;
					}
					$prefix = $seg . '_';
					if ( strpos( $rest, $prefix ) === 0 ) {
						$order[] = $seg;
						$rest    = substr( $rest, strlen( $prefix ) );
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					return [];
				}
			}

			return $order;
		}

		public static function ecbb_meta_combo_slugs_for_segments( array $segments ) {
			$slugs = [];
			foreach ( self::ecbb_meta_combo_permutations( $segments ) as $order ) {
				$slugs[] = self::ecbb_meta_combo_slug( $order );
			}

			return $slugs;
		}

		public static function ecbb_meta_combo_slugs_style2() {
			return array_values(
				array_unique(
					array_merge(
						self::ecbb_meta_combo_slugs_for_segments( [ 'venue', 'time', 'cost' ] ),
						self::ecbb_meta_combo_slugs_for_segments( [ 'venue', 'time' ] ),
						self::ecbb_meta_combo_slugs_for_segments( [ 'venue', 'cost' ] ),
						self::ecbb_meta_combo_slugs_for_segments( [ 'time', 'cost' ] )
					)
				)
			);
		}

		public static function ecbb_meta_combo_slug( array $order ) {
			return implode( '_', $order );
		}

	}
}
