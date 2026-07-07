<?php
/**
 * List/grid layout shell markup (dates, images, meta lists).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Layout_Shell', false ) ) {

	final class ECBB_Layout_Shell {
		/** Event start Unix timestamp. */
		public static function ecbb_event_start_timestamp( $post_id ) {
			list( $start_ts ) = self::date_bounds( $post_id );
			return $start_ts ? (int) $start_ts : false;
		}

		/** Start and end Unix timestamps for an event. */
		private static function date_bounds( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return [ false, false ];
			}
			if ( class_exists( 'ECBB_List_2', false ) ) {
				list( $start_ts, $end_ts ) = \ECBB_List_2::ecbb_date_bounds( $post_id );
				return [
					$start_ts ? (int) $start_ts : false,
					$end_ts ? (int) $end_ts : false,
				];
			}
			$start_raw = ECBB_Event_Data::ecbb_event_start_date_raw( $post_id );
			$end_raw   = ECBB_Event_Data::ecbb_event_end_date_raw( $post_id );
			$start_ts  = $start_raw ? strtotime( $start_raw ) : false;
			$end_ts    = $end_raw ? strtotime( $end_raw ) : $start_ts;
			return [ $start_ts, $end_ts ];
		}

		public static function ecbb_list1_date_column( $post, $settings = [] ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = self::ecbb_event_start_timestamp( $post->ID );
			if ( ! $start_ts ) {
				return '<div class="event-list-card__date"></div>';
			}
			$order = ECBB_Settings_Normalizer::ecbb_list1_date_column_order( is_array( $settings ) ? $settings : [] );
			$day   = '<span class="event-list-card__day">' . esc_html( date_i18n( 'd', $start_ts ) ) . '</span>';
			$month = '<span class="event-list-card__month">' . esc_html( date_i18n( 'M', $start_ts ) ) . '</span>';
			$inner = ( $order === 'day_month' ) ? $day . $month : $month . $day;
			return '<div class="event-list-card__date event-list-card__date--' . esc_attr( $order ) . '">'
				. '<div class="event-list-card__date-inner">' . $inner . '</div>'
				. '</div>';
		}

		public static function ecbb_list2_date_badge( $post, $settings = [] ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = self::ecbb_event_start_timestamp( $post->ID );
			if ( ! $start_ts ) {
				return '';
			}
			$order = ECBB_Settings_Normalizer::ecbb_style2_date_badge_order( is_array( $settings ) ? $settings : [] );
			$cls   = 'ecbb-event-card__date-badge ecbb-event-card__date-badge--' . $order;
			$label = date_i18n( 'F j, Y', $start_ts );
			$month = '<span>' . esc_html( strtoupper( date_i18n( 'M', $start_ts ) ) ) . '</span>';
			$day   = '<strong>' . esc_html( date_i18n( 'd', $start_ts ) ) . '</strong>';
			$inner = ( $order === 'day_month' ) ? $day . $month : $month . $day;
			return '<time class="' . esc_attr( $cls ) . '" datetime="' . esc_attr( wp_date( 'Y-m-d', $start_ts ) ) . '" aria-label="' . esc_attr( $label ) . '">'
				. $inner
				. '</time>';
		}

		public static function ecbb_grid_date_range_text( $post, array $item = [] ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			list( $start_ts, $end_ts ) = self::date_bounds( $post->ID );
			if ( ! $start_ts ) {
				return '';
			}
			if ( ! $end_ts ) {
				$end_ts = $start_ts;
			}

			$php = ECBB_Date_Formatter::ecbb_part_date_php_fmt( 'event_date', $item );
			if ( $php === '' ) {
				$php = 'd M, Y';
			}

			if ( date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) ) {
				return date_i18n( $php, $start_ts );
			}
			return date_i18n( $php, $start_ts ) . ' - ' . date_i18n( $php, $end_ts );
		}

		public static function ecbb_render_grid_date_flow( $post, array $item, $idx, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = self::ecbb_grid_date_range_text( $post, $item );
			if ( $text === '' ) {
				return '';
			}
			$wrap  = esc_attr(
				ECBB_Part_Chrome::ecbb_part_classes( 'date', $idx, $skin, $item ) . ' event-grid-card__date'
			);
			$attr  = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx );
			return '<span class="' . $wrap . '"' . $attr . '>' . esc_html( strtoupper( $text ) ) . '</span>';
		}

		/**
		 * Repeater category row (pill links) for list/grid layouts.
		 *
		 * @param string $wrap_prefix Layout wrapper class before part classes.
		 */
		private static function ecbb_render_layout_category_row( $post, array $item, $idx, $skin, $wrap_prefix ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ecbb_event_category_terms( $post->ID );
			if ( $terms === [] ) {
				return '';
			}

			$skin  = (string) $skin;
			$attr  = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx );
			$wrap  = esc_attr(
				trim(
					(string) $wrap_prefix . ' '
					. ECBB_Part_Chrome::ecbb_part_classes( 'categories', $idx, $skin, $item )
				)
			);

			$links = [];
			foreach ( $terms as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$links[] = '<a href="' . esc_url( $url ) . '" class="ecbb-event-card__category">'
					. esc_html( $term->name ) . '</a>';
			}
			if ( $links === [] ) {
				return '';
			}

			return '<div class="' . $wrap . '"' . $attr . '>' . implode( '', $links ) . '</div>';
		}

		public static function ecbb_render_style2_category( $post, array $item, $idx, $skin ) {
			return self::ecbb_render_layout_category_row( $post, $item, $idx, $skin, 'ecbb-event-card__top' );
		}

		public static function ecbb_render_grid_category( $post, array $item, $idx, $skin = '' ) {
			return self::ecbb_render_layout_category_row( $post, $item, $idx, $skin, 'event-grid-card__categories' );
		}

		public static function ecbb_render_layout_read_more( $post, array $item, $idx, $skin ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			if ( $label === '' ) {
				$label = __( 'View Details', 'events-calendar-for-bricks' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$skin      = (string) $skin;
			$skin_wrap = ( $skin === 'grid' ) ? '' : $skin;
			$wrap      = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'read_more', $idx, $skin_wrap, $item ) );
			$part_attr = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx );
			$url       = get_permalink( $post->ID );
			$link_cls  = esc_attr( trim( 'ecbb-event__link ' . ECBB_Part_Chrome::ecbb_layout_read_more_btn_class( $skin ) ) );
			$link      = '<a href="' . esc_url( $url ) . '" class="' . $link_cls . '">' . esc_html( $label ) . '</a>';
			$block     = '<div class="' . $wrap . '"' . $part_attr . '>' . $link . '</div>';

			if ( $skin === 'style2' ) {
				return '<div class="ecbb-event-card__divider"></div>'
					. '<div class="ecbb-event-card__footer">'
					. $block
					. '</div>';
			}

			return $block;
		}

		/**
		 * Shell read-more chrome around standard repeater part output (btn_style / hover / Style tab).
		 *
		 * @param \WP_Post $post
		 * @param array    $item
		 * @param int      $idx
		 * @param string   $skin
		 * @param callable $emit_part
		 * @return string
		 */
		public static function ecbb_render_layout_read_more_shell( $post, array $item, $idx, $skin, callable $emit_part ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$skin = (string) $skin;

			ob_start();
			$emit_part( $post, $item, $idx );
			$html = trim( (string) ob_get_clean() );
			if ( $html === '' ) {
				return self::ecbb_render_layout_read_more( $post, $item, $idx, $skin );
			}

			$html = ECBB_Part_Chrome::ecbb_read_more_merge_link_classes(
				$html,
				ECBB_Part_Chrome::ecbb_layout_read_more_btn_class( $skin )
			);

			if ( $skin === 'style2' ) {
				return '<div class="ecbb-event-card__divider"></div><div class="ecbb-event-card__footer">' . $html . '</div>';
			}

			return $html;
		}

		/**
		 * Render repeater rows in saved order (respects drag-and-drop + mixed flow/meta rows).
		 *
		 * @param \WP_Post $post
		 * @param array    $parts
		 * @param string   $layout style1|style2|grid
		 * @param string   $skin   style1|style2|'' (grid uses layout for read-more chrome)
		 * @param callable $emit_part  function( $post, $item, $idx )
		 * @param callable $emit_meta  function( $post, $item, $idx, $price )
		 * @return void
		 */
		public static function ecbb_render_layout_parts_sequence( $post, array $parts, $layout, $skin, callable $emit_part, callable $emit_meta ) {
			if ( ! $post instanceof \WP_Post ) {
				return;
			}

			$meta_rows = [];

			$flush_meta = static function () use ( $post, $layout, $skin, &$meta_rows, $emit_meta ) {
				if ( $meta_rows === [] ) {
					return;
				}
				self::ecbb_flush_meta_rows( $post, $meta_rows, $skin, $layout, $emit_meta );
				$meta_rows = [];
			};

			foreach ( $parts as $i => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$ui = (string) ( $item['part'] ?? '' );
				if ( $ui === '' ) {
					continue;
				}

				if ( $ui === 'read_more' ) {
					$flush_meta();
					$rm_skin = $layout === 'grid' ? 'grid' : (string) $skin;
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ecbb_render_layout_read_more_shell( $post, $item, (int) $i, $rm_skin, $emit_part );
					continue;
				}

				if ( self::ecbb_shell_skip_part( $ui ) ) {
					continue;
				}

				if ( $ui === 'categories' && ( $layout === 'style2' || $layout === 'grid' ) ) {
					$flush_meta();
					if ( $layout === 'grid' ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo self::ecbb_render_grid_category( $post, $item, (int) $i, $skin );
					} else {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo self::ecbb_render_style2_category( $post, $item, (int) $i, $skin );
					}
					continue;
				}

				if ( $layout === 'grid' && $ui === 'date' && (string) ( $item['date_display'] ?? '' ) === 'range' ) {
					$flush_meta();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ecbb_render_grid_date_flow( $post, $item, (int) $i, $skin );
					continue;
				}

				if ( self::ecbb_is_layout_meta_row( $item, $layout ) ) {
					$row_clean = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
					$slug      = (string) ( $row_clean['part'] ?? $ui );
					$meta_rows[] = [
						'idx'   => (int) $i,
						'item'  => $item,
						'price' => ( $ui === 'event_cost' || $slug === 'event_cost' )
							|| (
								class_exists( 'ECBB_Styles', false )
								&& \ECBB_Styles::ecbb_is_meta_combo_slug( $ui )
								&& \ECBB_Styles::ecbb_meta_combo_has_segment( $ui, 'cost' )
							),
					];
					continue;
				}

				$flush_meta();
				$emit_part( $post, $item, (int) $i );
			}

			$flush_meta();
		}

		public static function ecbb_render_meta_li( $post, array $item, $idx, $skin, $layout, $price = false ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$html = ECBB_Part_Renderer::ecbb_render_part_ext( $post, $item, $idx, '', $skin );
			if ( $html === '' || $html === false ) {
				return '';
			}

			$row_clean = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
			$part      = isset( $row_clean['part'] ) ? (string) $row_clean['part'] : '';
			$icon      = self::ecbb_part_uses_composite_inline_meta_icons( $part )
				? ''
				: self::ecbb_meta_icon_for_part( $part );

			if ( $layout === 'style2' ) {
				return '<li class="ecbb-event-card__meta-item">' . $icon . $html . '</li>';
			}

			$li_classes = [ 'ecbb-meta-list-row' ];
			if ( $price ) {
				$li_classes[] = 'price';
			}
			$li_class = ' class="' . esc_attr( implode( ' ', $li_classes ) ) . '"';
			return '<li' . $li_class . '>' . $icon . $html . '</li>';
		}

		/**
		 * Style 1 meta lists: keep repeater drag order; only split adjacent primary vs price runs.
		 *
		 * @param \WP_Post $post
		 * @param array<int,array{idx:int,item:array,price:bool}> $rows
		 * @param callable $emit_li function( $post, $item, $idx, $is_price )
		 * @return void
		 */
		public static function ecbb_render_style1_meta_lists( $post, array $rows, callable $emit_li ) {
			if ( $rows === [] ) {
				return;
			}

			$segments = [];
			$current  = null;

			foreach ( $rows as $row ) {
				$is_price = ! empty( $row['price'] );
				if ( $current === null || $current['price'] !== $is_price ) {
					if ( $current !== null ) {
						$segments[] = $current;
					}
					$current = [
						'price' => $is_price,
						'rows'  => [ $row ],
					];
					continue;
				}
				$current['rows'][] = $row;
			}

			if ( $current !== null ) {
				$segments[] = $current;
			}

			foreach ( $segments as $segment ) {
				$ul_class = 'event-meta event-meta--list';
				if ( $segment['price'] ) {
					$ul_class .= ' event-meta--list-price';
				}
				echo '<ul class="' . esc_attr( $ul_class ) . '">';
				foreach ( $segment['rows'] as $row ) {
					$emit_li( $post, $row['item'], $row['idx'], $segment['price'] );
				}
				echo '</ul>';
			}
		}

		public static function ecbb_render_meta_lists( $post, array $rows, $layout, callable $emit_li ) {
			if ( $rows === [] ) {
				return;
			}

			if ( $layout === 'grid' ) {
				$ul_class = 'event-meta event-meta--grid';
			} else {
				$ul_class = 'ecbb-event-card__meta';
			}

			echo '<ul class="' . esc_attr( $ul_class ) . '">';
			foreach ( $rows as $row ) {
				$is_price = ! empty( $row['price'] );
				$emit_li( $post, $row['item'], $row['idx'], $is_price );
			}
			echo '</ul>';
		}

		/**
		 * @param array<int,array{idx:int,item:array,price:bool}> $rows
		 */
		public static function ecbb_flush_meta_rows( $post, array $rows, $skin, $layout, callable $emit_meta ) {
			if ( $rows === [] ) {
				return;
			}

			if ( $layout === 'style1' ) {
				self::ecbb_render_style1_meta_lists(
					$post,
					$rows,
					static function ( $ev, $item, $idx, $is_price ) use ( $emit_meta ) {
						$emit_meta( $ev, $item, $idx, (bool) $is_price );
					}
				);
				return;
			}

			self::ecbb_render_meta_lists( $post, $rows, $layout, static function ( $ev, $item, $idx, $is_price ) use ( $emit_meta ) {
				$emit_meta( $ev, $item, $idx, (bool) $is_price );
			} );
		}

		public static function ecbb_shell_featured_image( $post, $layout, $link = true, $include_wrap = true ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$thumb_id = self::ecbb_event_thumbnail_id( $post->ID );
			$url      = get_permalink( $post->ID );
			$title    = esc_attr( wp_strip_all_tags( get_the_title( $post->ID ) ) );

			static $map = [
				'style1' => [
					'wrap'  => 'event-list-card__image-wrap',
					'link'  => 'event-list-card__image-link',
					'img'   => 'event-list-card__image',
				],
				'style2' => [
					'wrap'  => 'ecbb-event-card__image-wrap',
					'link'  => 'ecbb-event-card__image-link',
					'img'   => 'ecbb-event-card__image',
				],
				'grid'   => [
					'wrap'  => 'event-grid-card__image-wrap',
					'link'  => 'event-grid-card__image-link',
					'img'   => 'event-grid-card__image',
				],
			];
			if ( ! isset( $map[ $layout ] ) ) {
				return '';
			}
			$cls = $map[ $layout ];
			if ( $thumb_id < 1 ) {
				return $include_wrap ? '<div class="' . esc_attr( $cls['wrap'] ) . '"></div>' : '';
			}
			$size = ECBB_Part_Chrome::ecbb_sanitize_image_size( '', 'large' );
			$img  = wp_get_attachment_image(
				$thumb_id,
				$size,
				false,
				[
					'class'    => $cls['img'],
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => $title,
				]
			);
			if ( ! is_string( $img ) || $img === '' ) {
				return $include_wrap ? '<div class="' . esc_attr( $cls['wrap'] ) . '"></div>' : '';
			}
			$inner = $link
				? '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $cls['link'] ) . '">' . $img . '</a>'
				: $img;
			return $include_wrap
				? '<div class="' . esc_attr( $cls['wrap'] ) . '">' . $inner . '</div>'
				: $inner;
		}

		public static function ecbb_shell_category_badge( $post ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ecbb_event_category_terms( $post->ID );
			if ( $terms === [] ) {
				return '';
			}
			$links = [];
			foreach ( $terms as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$links[] = '<a href="' . esc_url( $url ) . '" class="event-badge--blue">'
					. esc_html( $term->name ) . '</a>';
			}
			if ( $links === [] ) {
				return '';
			}
			return '<div class="event-badge">' . implode( '', $links ) . '</div>';
		}

		/**
		 * Featured image attachment ID for an event (WP thumbnail + TEC fallback).
		 *
		 * @param int $post_id Event post ID.
		 * @return int Attachment ID or 0.
		 */
		public static function ecbb_event_thumbnail_id( $post_id ) {
			$post_id  = absint( $post_id );
			$thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $thumb_id > 0 ) {
				return $thumb_id;
			}
			if ( function_exists( 'tribe_get_event' ) ) {
				$event = tribe_get_event( $post_id );
				if ( $event && ! empty( $event->thumbnail_id ) ) {
					return (int) $event->thumbnail_id;
				}
			}
			return 0;
		}

		public static function ecbb_event_category_terms( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return [];
			}

			$by_id      = [];
			$taxonomies = apply_filters( 'ecbb_event_category_taxonomies', [ 'tribe_events_cat' ], $post_id );

			foreach ( (array) $taxonomies as $taxonomy ) {
				if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$raw = wp_get_object_terms(
					$post_id,
					$taxonomy,
					[
						'orderby' => 'name',
						'order'   => 'ASC',
					]
				);
				if ( is_wp_error( $raw ) || ! is_array( $raw ) ) {
					continue;
				}
				foreach ( $raw as $term ) {
					if ( $term instanceof \WP_Term ) {
						$by_id[ (int) $term->term_id ] = $term;
					}
				}
			}

			if ( $by_id === [] && function_exists( 'tribe_get_event' ) ) {
				$event = tribe_get_event( $post_id );
				if ( $event && ! empty( $event->categories ) && is_array( $event->categories ) ) {
					foreach ( $event->categories as $term ) {
						if ( $term instanceof \WP_Term ) {
							$by_id[ (int) $term->term_id ] = $term;
							continue;
						}
						if ( is_object( $term ) && ! empty( $term->term_id ) ) {
							$loaded = get_term( (int) $term->term_id );
							if ( $loaded instanceof \WP_Term && ! is_wp_error( $loaded ) ) {
								$by_id[ (int) $loaded->term_id ] = $loaded;
							}
						}
					}
				}
			}

			return array_values( $by_id );
		}

		/**
		 * Plain-text event description from full post content.
		 *
		 * @param \WP_Post            $post Event post.
		 * @param array<string,mixed> $item Repeater row (unused; kept for callers).
		 * @return string
		 */
		public static function ecbb_description_plain_text( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$raw = (string) $post->post_content;
			if ( $raw === '' ) {
				return '';
			}

			$html = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
			$html = do_shortcode( $html );

			return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
		}

		/**
		 * Grid description HTML with inline "Read more" when text exceeds the word cap.
		 *
		 * @param \WP_Post            $post Event post.
		 * @param array<string,mixed> $item Repeater row.
		 * @return string HTML (escaped fragments).
		 */
		public static function ecbb_grid_description_html( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$plain = self::ecbb_description_plain_text( $post, $item );
			if ( $plain === '' ) {
				return '';
			}

			$limit = self::ecbb_grid_description_word_limit( $item );
			if ( $limit < 1 ) {
				return esc_html( $plain );
			}

			$words = preg_split( '/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY );
			if ( ! is_array( $words ) || $words === [] ) {
				return '';
			}

			if ( count( $words ) <= $limit ) {
				return esc_html( $plain );
			}

			$excerpt = implode( ' ', array_slice( $words, 0, $limit ) );
			$label   = esc_html__( 'Read more', 'events-calendar-for-bricks' );
			$url     = get_permalink( $post->ID );

			return esc_html( $excerpt ) . '&hellip; '
				. '<a href="' . esc_url( $url ) . '" class="event-grid-card__desc-more ecbb-event__link">'
				. esc_html( $label ) . '</a>';
		}

		/**
		 * Grid card description word cap (default 20).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return int
		 */
		public static function ecbb_grid_description_word_limit( array $item ) {
			if ( isset( $item['desc_length'] ) && (string) $item['desc_length'] === 'full' ) {
				return 0;
			}
			if ( isset( $item['desc_words'] ) && (int) $item['desc_words'] > 0 ) {
				return max( 5, (int) $item['desc_words'] );
			}
			return 20;
		}

		public static function ecbb_layout_surface_class( $part, $skin ) {
			$part = (string) $part;
			$skin = (string) $skin;
			static $map = [
				'style1' => [
					'title'       => 'event-list-card__title',
					'description' => 'event-list-card__description',
				],
				'style2' => [
					'title'       => 'ecbb-event-card__title',
					'description' => 'ecbb-event-card__description',
					'categories'  => 'ecbb-event-card__category',
				],
				'grid'   => [
					'title'       => 'event-grid-card__title',
					'description' => 'event-grid-card__description',
					'categories'  => 'ecbb-event-card__category',
				],
			];
			return isset( $map[ $skin ][ $part ] ) ? $map[ $skin ][ $part ] : '';
		}

		public static function ecbb_shell_skip_part( $slug ) {
			$slug = (string) $slug;
			if ( in_array( $slug, [ 'read_more', 'event_date', 'event_day' ], true ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Whether a repeater row should render inside a layout meta list (uses cleaned slug).
		 *
		 * @param array<string,mixed> $item
		 * @param string              $layout style1|style2|grid
		 * @return bool
		 */
		public static function ecbb_is_layout_meta_row( array $item, $layout ) {
			$ui = (string) ( $item['part'] ?? '' );
			if ( in_array( $ui, [ 'title', 'description', 'read_more', 'image', 'categories' ], true ) ) {
				return false;
			}
			if ( $layout === 'grid' && $ui === 'date' && (string) ( $item['date_display'] ?? '' ) === 'range' ) {
				return false;
			}

			$row   = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
			$slug  = (string) ( $row['part'] ?? $ui );
			$slugs = array_merge(
				[
					'date', 'event_date', 'event_time', 'event_day', 'venue', 'organizer', 'event_cost',
					'tags', 'event_link', 'event_tickets', 'event_rsvp',
					'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip',
					'venue_country', 'venue_phone', 'venue_website', 'event_map_link',
					'organizer_email', 'organizer_phone', 'organizer_website',
				],
				class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_meta_combo_all_slugs() : [ 'venue_time', 'venue_time_cost' ]
			);

			return in_array( $slug, $slugs, true ) || in_array( $ui, $slugs, true );
		}

		public static function ecbb_meta_icon( $type ) {
			$type = (string) $type;
			static $svgs = [
				'clock' => '<path d="M12 7v5l3.4 2.2" /><circle cx="12" cy="12" r="8" />',
				'pin'   => '<path d="M12 21s6-5.1 6-11a6 6 0 0 0-12 0c0 5.9 6 11 6 11Z" /><circle cx="12" cy="10" r="2.4" />',
				'cost'  => '<path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v1.2a2.5 2.5 0 0 0-.9 4.8V15a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-1.2a2.5 2.5 0 0 0-.9-4.8V9Z" /><path d="M13 5v14" />',
			];
			if ( ! in_array( $type, [ 'clock', 'pin', 'cost' ], true ) ) {
				$type = 'clock';
			}
			$inner = $svgs[ $type ];
			return '<span class="ecbb-event-card__meta-icon" aria-hidden="true"><svg viewBox="0 0 24 24">' . $inner . '</svg></span>';
		}

		public static function ecbb_meta_icon_for_part( $slug ) {
			$slug = (string) $slug;
			if (
				$slug === 'event_cost'
				|| ( class_exists( 'ECBB_Styles', false ) && \ECBB_Styles::ecbb_is_meta_combo_slug( $slug ) && \ECBB_Styles::ecbb_meta_combo_has_segment( $slug, 'cost' ) )
			) {
				return self::ecbb_meta_icon( 'cost' );
			}
			if (
				in_array( $slug, [ 'venue', 'organizer', 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ], true )
				|| ( class_exists( 'ECBB_Styles', false ) && \ECBB_Styles::ecbb_is_meta_combo_slug( $slug ) && \ECBB_Styles::ecbb_meta_combo_has_segment( $slug, 'venue' ) )
			) {
				return self::ecbb_meta_icon( 'pin' );
			}
			return self::ecbb_meta_icon( 'clock' );
		}

		/**
		 * Part slugs that render inside a meta list with a leading icon (List 1 / Grid).
		 *
		 * @return string[]
		 */
		public static function ecbb_meta_list_icon_part_slugs() {
			$combo = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_meta_combo_all_slugs()
				: [ 'venue_time', 'venue_time_cost' ];

			return array_merge(
				[
					'date',
					'event_date',
					'event_time',
					'event_day',
					'venue',
					'organizer',
					'event_cost',
					'tags',
					'event_link',
					'event_tickets',
					'event_rsvp',
					'venue_full_address',
					'venue_street',
					'venue_city',
					'venue_state',
					'venue_zip',
					'venue_country',
					'venue_phone',
					'venue_website',
					'event_map_link',
					'organizer_email',
					'organizer_phone',
					'organizer_website',
				],
				$combo
			);
		}

		/**
		 * Composite meta rows render per-segment icons inside the part wrapper.
		 *
		 * @param string $part_slug Cleaned part slug.
		 * @return bool
		 */
		public static function ecbb_part_uses_composite_inline_meta_icons( $part_slug ) {
			return class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_is_meta_combo_slug( (string) $part_slug )
				: in_array( (string) $part_slug, [ 'venue_time', 'venue_time_cost' ], true );
		}

		/**
		 * Whether a resolved part slug renders with a Style 2 meta list icon.
		 *
		 * @param string $part_slug Cleaned part slug.
		 * @return bool
		 */
		public static function ecbb_part_shows_style2_meta_icon( $part_slug ) {
			$part_slug = (string) $part_slug;
			if ( class_exists( 'ECBB_Styles', false ) && \ECBB_Styles::ecbb_is_meta_combo_slug( $part_slug ) ) {
				return true;
			}
			if ( $part_slug === 'event_cost' ) {
				return true;
			}
			if ( in_array( $part_slug, [ 'date', 'event_date', 'event_time', 'event_day' ], true ) ) {
				return true;
			}
			$venue_slugs = [
				'venue',
				'venue_full_address',
				'venue_street',
				'venue_city',
				'venue_state',
				'venue_zip',
				'venue_country',
				'venue_phone',
			];

			return in_array( $part_slug, $venue_slugs, true );
		}

		/**
		 * @param string $part_slug Cleaned part slug.
		 * @return bool
		 */
		public static function ecbb_part_renders_meta_list_icon( $part_slug ) {
			return in_array( (string) $part_slug, self::ecbb_meta_list_icon_part_slugs(), true );
		}
	}
}
