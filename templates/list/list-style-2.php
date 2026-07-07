<?php
/**
 * Events Widget — List template / Style 2 (ecbb-event-card reference layout).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_List_2', false ) ) {

	final class ECBB_List_2 extends ECBB_Layout_Base {

		public static function ecbb_part_class( $part ) {
			$part = sanitize_key( (string) $part );
			return 'ecbb-style2-' . str_replace( '_', '-', $part );
		}

		protected static function ecbb_layout_key() {
			return 'style2';
		}

		protected static function ecbb_default_rows() {
			return [
				[
					'part' => 'categories',
				],
				[
					'part' => 'title',
					'link' => true,
				],
				[
					'part' => 'description',
				],
				[
					'part'          => 'venue_time_cost',
					'venue_display' => 'name_and_city',
					'date_display'  => 'time',
					'cost_currency' => 'default',
				],
				[
					'part'           => 'read_more',
					'read_more_text' => __( 'View Details', 'events-calendar-for-bricks' ),
				],
			];
		}

		protected static function ecbb_empty_parts_fallback( array $parts ) {
			return \ECBB_Markup::ecbb_parts_preserve_bricks_rows( $parts, self::ecbb_default_parts() );
		}

		protected static function ecbb_normalize_row( array $row ) {
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$row = \ECBB_Styles::ecbb_normalize_meta_combo_row( $row );
			}

			$part = (string) ( $row['part'] ?? '' );
			if ( $part === 'venue' ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' || $display === 'name_and_state' ) {
					$row['venue_display'] = 'name_and_city';
				}
			}
			if ( $part === 'date' ) {
				$date_display = (string) ( $row['date_display'] ?? '' );
				if ( $date_display === '' ) {
					$row['date_display'] = 'time';
				}
			}
			if ( $part === 'event_cost' && ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) ) {
				$row['cost_currency'] = 'default';
			}

			return $row;
		}

		public static function ecbb_date_bounds( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return [ false, false ];
			}
			$dates    = ECBB_Event_Data::ecbb_event_meta_dates( $post_id );
			$start_ts = ! empty( $dates['start'] ) ? strtotime( $dates['start'] ) : false;
			$end_ts   = ! empty( $dates['end'] ) ? strtotime( $dates['end'] ) : $start_ts;
			if ( ! $start_ts ) {
				return [ false, false ];
			}
			if ( ! $end_ts ) {
				$end_ts = $start_ts;
			}
			return [ $start_ts, $end_ts ];
		}

		protected static function ecbb_card_base_class() {
			return 'ecbb-event-card ecbb-style2 ecbb-ev__item-inner ecbb-ev__item-inner--style2';
		}

		protected static function ecbb_no_image_class() {
			return 'ecbb-event-card--no-image';
		}

		protected static function ecbb_image_wrap_class() {
			return 'ecbb-event-card__image-wrap';
		}

		protected static function ecbb_image_shell_badge_html( $post, array $settings ) {
			if ( ! \ECBB_Markup::ecbb_show_style2_date_badge( $settings ) ) {
				return '';
			}
			return \ECBB_Markup::ecbb_list2_date_badge( $post, $settings );
		}

		protected static function ecbb_image_shell_featured_skin() {
			return 'style2';
		}

		protected static function ecbb_open_content( $post, array $settings, $show_image ) {
			unset( $post, $settings, $show_image );
			echo '<div class="ecbb-event-card__content">';
		}

		protected static function ecbb_close_content() {
			echo '</div>';
		}
	}
}
