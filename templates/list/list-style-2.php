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

	final class ECBB_List_2 {

		public static function ecbb_part_class( $part ) {
			$part = sanitize_key( (string) $part );
			return 'ecbb-style2-' . str_replace( '_', '-', $part );
		}

		public static function ecbb_default_parts() {
			$rows = [
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
				'style2',
				static function () {
					return self::ecbb_default_parts();
				}
			);
			if ( is_array( $upgraded ) ) {
				$parts = $upgraded;
			}

			$clean = \ECBB_Markup::ecbb_parts_clean( $parts );
			if ( $clean === [] ) {
				return \ECBB_Markup::ecbb_parts_preserve_bricks_rows( $parts, self::ecbb_default_parts() );
			}

			$clean = array_values(
				array_filter(
					$clean,
					static function ( $row ) {
						return ! is_array( $row ) || (string) ( $row['part'] ?? '' ) !== 'image';
					}
				)
			);

			$clean = array_map(
				static function ( $row ) {
					if ( ! is_array( $row ) ) {
						return $row;
					}
					if ( (string) ( $row['part'] ?? '' ) === 'venue' ) {
						$display = (string) ( $row['venue_display'] ?? '' );
						if ( $display === '' || $display === 'name_and_address' || $display === 'name_and_state' ) {
							$row['venue_display'] = 'name_and_city';
						}
					}
					if ( (string) ( $row['part'] ?? '' ) === 'date' && ! isset( $row['date_display'] ) ) {
						$row['date_display'] = 'time';
					}
					if ( (string) ( $row['part'] ?? '' ) === 'event_cost' && ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) ) {
						$row['cost_currency'] = 'default';
					}
					return $row;
				},
				$clean
			);

			return \ECBB_Markup::ecbb_parts_assign_ids( $clean );
		}

		public static function ecbb_date_bounds( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return [ false, false ];
			}
			$start_raw = (string) get_post_meta( $post_id, '_EventStartDate', true );
			$end_raw   = (string) get_post_meta( $post_id, '_EventEndDate', true );
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
			$show_date_badge = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_show_style2_date_badge( $settings )
				: true;

			$card_class = 'ecbb-event-card ecbb-style2 ecbb-ev__item-inner ecbb-ev__item-inner--style2';
			if ( ! $show_image ) {
				$card_class .= ' ecbb-event-card--no-image';
			}

			ob_start();

			echo '<div class="' . esc_attr( $card_class ) . '">';

			if ( $show_image && class_exists( 'ECBB_Markup', false ) ) {
				echo '<div class="ecbb-event-card__image-wrap">';
				if ( $show_date_badge ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo \ECBB_Markup::ecbb_list2_date_badge( $post, $settings );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECBB_Markup::ecbb_shell_featured_image( $post, 'style2', true, false );
				echo '</div>';
			}

			echo '<div class="ecbb-event-card__content">';

			if ( class_exists( 'ECBB_Markup', false ) ) {
				$emit_meta_cb = $emit_meta ?? static function ( $ev, $item, $idx, $price ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo \ECBB_Markup::ecbb_render_meta_li( $ev, $item, $idx, 'style2', 'style2', (bool) $price );
				};
				\ECBB_Markup::ecbb_render_layout_parts_sequence(
					$post,
					$parts,
					'style2',
					'style2',
					$emit_part,
					$emit_meta_cb
				);
			}

			echo '</div></div>';

			return ob_get_clean();
		}
	}
}
