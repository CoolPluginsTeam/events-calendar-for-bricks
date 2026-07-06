<?php
/**
 * Event date/time formatting for part output.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Date_Formatter', false ) ) {

	final class ECBB_Date_Formatter {

		/** @var array<int,array{day:string,time:string}> */
		private static $day_time_parts_cache = [];

		/** PHP date() format string for a part row (preset, custom, or site default). */
		public static function ecbb_part_date_php_fmt( $part, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			$custom = isset( $item['date_format_custom'] ) ? trim( (string) $item['date_format_custom'] ) : '';
			$custom = preg_replace( '/[\x00-\x1F\x7F<>]/', '', wp_strip_all_tags( $custom ) );
			$part   = (string) $part;

			if ( $part === 'event_time' ) {
				if ( $preset === 'custom' && $custom !== '' ) {
					return $custom;
				}
				if ( class_exists( 'ECBB_Styles', false ) ) {
					$mapped = \ECBB_Styles::ecbb_date_php_format( $preset, 'event_time' );
					if ( $mapped !== null && $mapped !== '' ) {
						return $mapped;
					}
				}
				return (string) get_option( 'time_format' );
			}
			if ( $preset === 'custom' ) {
				return $custom;
			}
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$mapped = \ECBB_Styles::ecbb_date_php_format( $preset, $part );
				if ( $mapped !== null && $mapped !== '' ) {
					return $mapped;
				}
			}
			if ( $preset === 'site_date' ) {
				return (string) get_option( 'date_format' );
			}
			if ( $preset === 'site_time' ) {
				return (string) get_option( 'time_format' );
			}
			if ( $preset === 'site_date_time' ) {
				return (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' );
			}
			if ( $part === 'event_date' ) {
				return (string) get_option( 'date_format' );
			}
			return '';
		}

		/** Lowercase am/pm in a PHP time format string. */
		public static function ecbb_time_fmt_lower( $format ): string {
			$format = (string) $format;
			return $format === '' ? '' : str_replace( 'A', 'a', $format );
		}

		/** Lowercase AM/PM in formatted time text. */
		public static function ecbb_time_lower_am( $time_str ): string {
			$time_str = trim( (string) $time_str );
			if ( $time_str === '' ) {
				return '';
			}
			return (string) preg_replace_callback(
				'/\b(AM|PM)\b/u',
				static function ( $m ) {
					return strtolower( $m[0] );
				},
				$time_str
			);
		}

		/** Day name + time range for the “day & time” part. */
		public static function ecbb_build_day_time_parts( $post_id, array $item ): array {
			unset( $item );
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return [ 'day' => '', 'time' => '' ];
			}
			if ( isset( self::$day_time_parts_cache[ $post_id ] ) ) {
				return self::$day_time_parts_cache[ $post_id ];
			}

			$store = static function ( array $result ) use ( $post_id ) {
				self::$day_time_parts_cache[ $post_id ] = $result;
				return $result;
			};

			$dates = ECBB_Event_Data::ecbb_event_meta_dates( $post_id );
			$start = $dates['start'] ? strtotime( $dates['start'] ) : false;
			if ( ! $start ) {
				return $store( [ 'day' => '', 'time' => '' ] );
			}
			$end = $dates['end'] ? strtotime( $dates['end'] ) : $start;
			if ( ! $end ) {
				$end = $start;
			}
			if ( function_exists( 'tribe_event_is_all_day' ) && tribe_event_is_all_day( $post_id ) ) {
				$day = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );
				return $store( [ 'day' => $day, 'time' => '' ] );
			}

			$fmt = self::ecbb_time_fmt_lower( (string) get_option( 'time_format' ) );
			$day = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );

			$t0 = function_exists( 'tribe_get_start_time' ) ? (string) tribe_get_start_time( $post_id, $fmt ) : '';
			if ( $t0 === '' && function_exists( 'tribe_get_start_date' ) ) {
				$t0 = (string) tribe_get_start_date( $post_id, true, $fmt );
			}
			if ( $t0 === '' ) {
				$t0 = date_i18n( $fmt, $start );
			}
			$t1 = function_exists( 'tribe_get_end_time' ) ? (string) tribe_get_end_time( $post_id, $fmt ) : '';
			if ( $t1 === '' && function_exists( 'tribe_get_end_date' ) ) {
				$t1 = (string) tribe_get_end_date( $post_id, true, $fmt );
			}
			if ( $t1 === '' ) {
				$t1 = date_i18n( $fmt, $end );
			}
			$t0 = self::ecbb_time_lower_am( trim( wp_strip_all_tags( $t0 ) ) );
			$t1 = self::ecbb_time_lower_am( trim( wp_strip_all_tags( $t1 ) ) );
			if ( $t0 === '' ) {
				return $store( [ 'day' => $day, 'time' => '' ] );
			}
			if ( $t1 === '' || $t0 === $t1 ) {
				return $store( [ 'day' => $day, 'time' => $t0 ] );
			}
			return $store( [ 'day' => $day, 'time' => $t0 . ' - ' . $t1 ] );
		}

		/**
		 * Plain text for a part row's Visibility (date_display) setting.
		 *
		 * @param int                 $post_id Event post ID.
		 * @param array<string,mixed> $item    Repeater row.
		 * @return string
		 */
		public static function ecbb_part_visibility_plain_text( $post_id, array $item ) {
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return '';
			}

			$fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
			if ( $fmt === '' ) {
				$fmt = 'day_time_range';
			}

			if ( $fmt === 'range' && class_exists( 'ECBB_Layout_Shell', false ) ) {
				$post = get_post( $post_id );
				return $post instanceof \WP_Post
					? ECBB_Layout_Shell::ecbb_grid_date_range_text( $post, $item )
					: '';
			}

			if ( $fmt === 'date' ) {
				$format = self::ecbb_part_date_php_fmt( 'event_date', $item );
				$php    = $format !== '' ? $format : (string) get_option( 'date_format' );
				if ( function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) tribe_get_start_date( $post_id, false, $php );
				} else {
					$raw  = ECBB_Event_Data::ecbb_event_start_date_raw( $post_id );
					$ts   = $raw ? strtotime( $raw ) : false;
					$html = $ts ? date_i18n( $php, $ts ) : '';
				}

				return trim( wp_strip_all_tags( $html ) );
			}

			$tp = self::ecbb_build_day_time_parts( $post_id, $item );

			if ( $fmt === 'time' ) {
				$html = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
				if ( $html !== '' ) {
					return $html;
				}

				$format = self::ecbb_part_date_php_fmt( 'event_time', $item );
				$php    = self::ecbb_time_fmt_lower( $format !== '' ? $format : (string) get_option( 'time_format' ) );
				if ( function_exists( 'tribe_get_start_time' ) ) {
					$html = (string) tribe_get_start_time( $post_id, $php );
				} elseif ( function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) tribe_get_start_date( $post_id, true, $php );
				} else {
					$raw  = ECBB_Event_Data::ecbb_event_start_date_raw( $post_id );
					$ts   = $raw ? strtotime( $raw ) : false;
					$html = $ts ? date_i18n( $php, $ts ) : '';
				}

				return self::ecbb_time_lower_am( trim( wp_strip_all_tags( $html ) ) );
			}

			if ( $fmt === 'day' ) {
				$html = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
				if ( $html !== '' ) {
					return $html;
				}
				$raw = ECBB_Event_Data::ecbb_event_start_date_raw( $post_id );
				$ts  = $raw ? strtotime( $raw ) : false;

				return $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
			}

			$day  = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
			$time = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
			if ( $day !== '' && $time !== '' ) {
				return $day . ', ' . $time;
			}
			if ( $time !== '' ) {
				return $time;
			}

			return $day;
		}
	}
}
