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

	final class ECBB_List_1 {

		public static function ecbb_default_parts() {
			$rows = [
				[
					'part' => 'title',
					'link' => true,
				],
				[
					'part'        => 'description',
					'desc_source' => 'content',
				],
				[
					'part'         => 'date',
					'date_display' => 'time',
				],
				[
					'part'          => 'venue',
					'venue_display' => 'name_and_city',
				],
				[
					'part'          => 'event_cost',
					'cost_currency' => 'default',
				],
				[
					'part'           => 'read_more',
					'read_more_text' => esc_html__( 'View Details', 'events-calendar-for-bricks' ),
				],
			];

			return class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_parts_assign_ids( $rows )
				: $rows;
		}

		public static function ecbb_norm_parts( array $parts ) {
			if ( ! class_exists( 'ECBB_Markup', false ) ) {
				return $parts;
			}

			$upgraded = \ECBB_Markup::ecbb_upgrade_layout_parts(
				$parts,
				'style1',
				static function () {
					return self::ecbb_default_parts();
				}
			);
			if ( is_array( $upgraded ) ) {
				$parts = $upgraded;
			}

			$clean = \ECBB_Markup::ecbb_parts_clean( $parts );
			if ( $clean === [] ) {
				return self::ecbb_default_parts();
			}

			$clean = array_map(
				static function ( $row ) {
					if ( ! is_array( $row ) ) {
						return $row;
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
				},
				$clean
			);

			return \ECBB_Markup::ecbb_parts_assign_ids( $clean );
		}

		/**
		 * @param \WP_Post $post       Event post.
		 * @param array    $parts      Repeater rows.
		 * @param callable $emit_part  Part renderer.
		 * @param array    $settings   Widget settings.
		 * @param callable|null $emit_meta Meta row renderer (optional).
		 * @return string
		 */
		public static function ecbb_item_inner( $post, array $parts, callable $emit_part, $settings = [], callable $emit_meta = null ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				return '';
			}

			$settings = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_layout_settings( is_array( $settings ) ? $settings : [] )
				: ( is_array( $settings ) ? $settings : [] );
			$show_image = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_show_event_image( $settings )
				: true;
			$show_category = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_show_shell_category_badge( $settings )
				: true;

			$card_class = 'event-list-card ecbb-ev__item-inner ecbb-ev__item-inner--style1';
			if ( ! $show_image ) {
				$card_class .= ' event-list-card--no-image';
			}

			ob_start();

			echo '<div class="' . esc_attr( $card_class ) . '">';

			if ( $show_image && class_exists( 'ECBB_Markup', false ) ) {
				echo '<div class="event-list-card__image-wrap">';
				if ( $show_category ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo \ECBB_Markup::ecbb_shell_category_badge( $post );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_shell_featured_image( $post, 'style1', true, false );
				echo '</div>';
			}

			echo '<div class="event-list-card__content">';
			if ( $show_image && class_exists( 'ECBB_Markup', false ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_list1_date_column( $post );
			}

			echo '<div class="event-list-card__body">';

			if ( class_exists( 'ECBB_Markup', false ) ) {
				$emit_meta_cb = $emit_meta ?? static function ( $ev, $item, $idx, $price ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo \ECBB_Markup::ecbb_render_meta_li( $ev, $item, $idx, 'style1', 'style1', (bool) $price );
				};
				\ECBB_Markup::ecbb_render_layout_parts_sequence(
					$post,
					$parts,
					'style1',
					'style1',
					$emit_part,
					$emit_meta_cb
				);
			}

			echo '</div></div></div>';

			return ob_get_clean();
		}
	}
}
