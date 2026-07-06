<?php
/**
 * Events Widget — Grid template (event-grid-card reference layout).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Grid', false ) ) {

	final class ECBB_Grid extends ECBB_Layout_Base {

		protected static function ecbb_layout_key() {
			return 'grid';
		}

		protected static function ecbb_default_rows() {
			return [
				[
					'part'         => 'date',
					'date_display' => 'range',
				],
				[
					'part' => 'title',
					'link' => true,
				],
				[
					'part'        => 'description',
					'desc_length' => 'custom',
					'desc_words'  => 20,
				],
				[
					'part'          => 'venue',
					'venue_display' => 'name',
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

		protected static function ecbb_normalize_row( array $row ) {
			$part = (string) ( $row['part'] ?? '' );
			if ( $part === 'venue' ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' || $display === 'name_and_state' ) {
					$row['venue_display'] = 'name';
				}
			}
			if ( $part === 'date' && ! isset( $row['date_display'] ) ) {
				$row['date_display'] = 'day_time_range';
			}
			if ( $part === 'description' ) {
				if ( ! isset( $row['desc_length'] ) || (string) $row['desc_length'] === '' ) {
					$row['desc_length'] = 'custom';
				}
				if ( ! isset( $row['desc_words'] ) || (int) $row['desc_words'] < 1 ) {
					$row['desc_words'] = 20;
				}
			}
			if ( $part === 'event_cost' && ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) ) {
				$row['cost_currency'] = 'default';
			}

			return $row;
		}

		protected static function ecbb_card_base_class() {
			return 'event-grid-card ecbb-ev__item-inner ecbb-ev__item-inner--grid';
		}

		protected static function ecbb_no_image_class() {
			return 'event-grid-card--no-image';
		}

		protected static function ecbb_layout_skin() {
			return 'grid';
		}

		protected static function ecbb_part_skin() {
			return '';
		}

		protected static function ecbb_render_image_shell( $post, array $settings ) {
			$show_category = \ECBB_Markup::ecbb_show_shell_category_badge( $settings );

			echo '<div class="event-grid-card__image-wrap">';
			if ( $show_category ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_shell_category_badge( $post );
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_Markup::ecbb_shell_featured_image( $post, 'grid', true, false );
			echo '</div>';
		}

		protected static function ecbb_open_content( $post, array $settings, $show_image ) {
			unset( $post, $settings, $show_image );
			echo '<div class="event-grid-card__content">';
		}

		protected static function ecbb_close_content() {
			echo '</div>';
		}
	}
}
