<?php
/**
 * Events Widget — List template / Style 1 (event-list-card reference layout).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_List_1', false ) ) {

	final class ECBB_List_1 extends ECBB_Layout_Base {

		protected static function ecbb_layout_key() {
			return 'style1';
		}

		protected static function ecbb_default_rows() {
			return [
			[
			'part' => 'title',
			'link' => true,
			],
			[
					'part' => 'description',
				],
				[
					'part'          => 'venue_time',
					'venue_display' => 'name_and_city',
					'date_display'  => 'time',
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

		protected static function ecbb_normalize_row( array $row ) {
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$row = \ECBB_Styles::ecbb_normalize_meta_combo_row( $row );
			}

			$part = (string) ( $row['part'] ?? '' );
			if ( $part === 'venue' ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' || $display === 'full_details' ) {
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

		protected static function ecbb_filter_parts( array $clean ) {
			$blocked = class_exists( 'ECBB_Styles', false )
				? array_values( array_diff( \ECBB_Styles::ecbb_meta_combo_all_slugs(), \ECBB_Styles::ecbb_meta_combo_slugs_style1() ) )
				: [ 'venue_time_cost' ];

			return static::ecbb_filter_blocked_parts( $clean, $blocked );
		}

		protected static function ecbb_card_base_class() {
			return 'event-list-card ecbb-ev__item-inner ecbb-ev__item-inner--style1';
		}

		protected static function ecbb_no_image_class() {
			return 'event-list-card--no-image';
		}

		protected static function ecbb_no_date_class() {
			return 'event-list-card--no-date';
		}

		protected static function ecbb_render_image_shell( $post, array $settings ) {
			$show_category = \ECBB_Markup::ecbb_show_shell_category_badge( $settings );

			echo '<div class="event-list-card__image-wrap">';
			if ( $show_category ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_shell_category_badge( $post );
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_Markup::ecbb_shell_featured_image( $post, 'style1', true, false );
			echo '</div>';
		}

		protected static function ecbb_open_content( $post, array $settings, $show_image ) {
			unset( $show_image );
			$show_date = \ECBB_Markup::ecbb_show_list1_date_column( $settings );

			echo '<div class="event-list-card__content">';
			if ( $show_date ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_list1_date_column( $post, $settings );
			}
			echo '<div class="event-list-card__body">';
		}

		protected static function ecbb_close_content() {
			echo '</div></div>';
		}
	}
}
