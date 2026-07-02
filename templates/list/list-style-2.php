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
					'part'        => 'description',
					'desc_source' => 'content',
				],
				[
					'part'          => 'venue',
					'venue_display' => 'name_and_city',
				],
				[
					'part'         => 'date',
					'date_display' => 'time',
				],
				[
					'part'          => 'event_cost',
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

		protected static function ecbb_filter_parts( array $clean ) {
			return array_values(
				array_filter(
					$clean,
					static function ( $row ) {
						return ! is_array( $row ) || (string) ( $row['part'] ?? '' ) !== 'image';
					}
				)
			);
		}

		protected static function ecbb_normalize_row( array $row ) {
			$part = (string) ( $row['part'] ?? '' );
			if ( $part === 'venue' ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' || $display === 'name_and_state' ) {
					$row['venue_display'] = 'name_and_city';
				}
			}
			if ( $part === 'date' && ! isset( $row['date_display'] ) ) {
				$row['date_display'] = 'time';
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
			$start_raw = \ECBB_Markup::ecbb_event_start_date_raw( $post_id );
			$end_raw   = \ECBB_Markup::ecbb_event_end_date_raw( $post_id );
			$start_ts  = $start_raw ? strtotime( $start_raw ) : false;
			$end_ts    = $end_raw ? strtotime( $end_raw ) : $start_ts;
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

		protected static function ecbb_part_skin() {
			return 'style2';
		}

		protected static function ecbb_layout_skin() {
			return 'style2';
		}

		protected static function ecbb_render_image_shell( $post, array $settings ) {
			$show_date_badge = \ECBB_Markup::ecbb_show_style2_date_badge( $settings );

			echo '<div class="ecbb-event-card__image-wrap">';
			if ( $show_date_badge ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_list2_date_badge( $post, $settings );
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_Markup::ecbb_shell_featured_image( $post, 'style2', true, false );
			echo '</div>';
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
