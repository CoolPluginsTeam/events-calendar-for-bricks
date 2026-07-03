<?php
/**
 * HTML output for event repeater parts.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Part_Renderer', false ) ) {

	final class ECBB_Part_Renderer {

		/** Venue repeater row markup. */
		public static function ecbb_render_venue( $post, array $item, $idx, $style, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = ECBB_Event_Data::ecbb_venue_text( $post->ID, $item, $skin );
			if ( $text === '' ) {
				return '';
			}
			$idx     = absint( $idx );
			$skin    = (string) $skin;
			$attr    = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$classes = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'venue', $idx, $skin, $item ) . ' ecbb-has-row-icon' );
			return '<div class="' . $classes . '"' . $attr . '>' . esc_html( $text ) . '</div>';
		}

		/** Organizer repeater row markup. */
		public static function ecbb_render_organizer( $post, array $item, $idx, $style, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = ECBB_Event_Data::ecbb_organizer_text( $post->ID, $item, $skin );
			if ( $text === '' ) {
				return '';
			}
			$idx     = absint( $idx );
			$skin    = (string) $skin;
			$attr    = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$classes = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'organizer', $idx, $skin, $item ) );
			return '<div class="' . $classes . '"' . $attr . '>' . esc_html( $text ) . '</div>';
		}

		/** Featured image (single size or dual-size hover stack). */
		public static function ecbb_render_featured_img( $thumb_id, array $item ) {
			$thumb_id = (int) $thumb_id;
			if ( ! $thumb_id ) {
				return '';
			}
			if ( ! ECBB_Part_Chrome::ecbb_hover_style_active( $item ) ) {
				$item['image_size_hover']              = '';
				$item['ecbb_image_object_align_hover'] = '';
			}
			$size_base  = ECBB_Part_Chrome::ecbb_sanitize_image_size( $item['image_size'] ?? '', 'large' );
			$raw_hover  = isset( $item['image_size_hover'] ) ? trim( (string) $item['image_size_hover'] ) : '';
			$size_hover = $raw_hover !== '' ? ECBB_Part_Chrome::ecbb_sanitize_image_size( $raw_hover, $size_base ) : '';
			$dual       = ( $raw_hover !== '' && $size_hover !== $size_base );

			if ( ! $dual ) {
				$html = wp_get_attachment_image( $thumb_id, $size_base, false, [ 'class' => 'ecbb-event__image' ] );
				return is_string( $html ) ? $html : '';
			}

			$img_base  = wp_get_attachment_image( $thumb_id, $size_base, false, [ 'class' => 'ecbb-event__image ecbb-event__image--base' ] );
			$img_hover = wp_get_attachment_image( $thumb_id, $size_hover, false, [ 'class' => 'ecbb-event__image ecbb-event__image--hover' ] );
			if ( ! is_string( $img_base ) || ! is_string( $img_hover ) || $img_base === '' || $img_hover === '' ) {
				return is_string( $img_base ) ? $img_base : '';
			}
			return '<span class="ecbb-event__img-stack">' . $img_base . $img_hover . '</span>';
		}

		/** Detail-field part slugs handled by ecbb_render_part_detail(). */
		private static function detail_slugs() {
			return [
				'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country',
				'venue_phone', 'venue_website', 'event_map_link', 'event_website', 'event_phone',
				'organizer_email', 'organizer_phone', 'organizer_website',
			];
		}

		/** Part slug → render handler for ecbb_render_part_ext(). */
		private static function ext_dispatch() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}
			$map = [
				'venue'         => [ self::class, 'ecbb_render_venue' ],
				'organizer'     => [ self::class, 'ecbb_render_organizer' ],
				'date'          => [ self::class, 'ecbb_render_part_date' ],
				'event_date'    => [ self::class, 'ecbb_render_part_event_date' ],
				'event_time'    => [ self::class, 'ecbb_render_part_event_time' ],
				'event_day'     => [ self::class, 'ecbb_render_part_event_day' ],
				'event_cost'    => [ self::class, 'ecbb_render_part_event_cost' ],
				'event_tickets' => [ self::class, 'ecbb_render_part_event_tickets' ],
				'event_rsvp'    => [ self::class, 'ecbb_render_part_event_rsvp' ],
				'read_more'     => [ self::class, 'ecbb_render_part_read_more' ],
			];
			$detail = [ self::class, 'ecbb_render_part_detail' ];
			foreach ( self::detail_slugs() as $slug ) {
				$map[ $slug ] = $detail;
			}
			return $map;
		}

		/** Combined date part (day, time, day+time, or grid range). */
		public static function ecbb_render_part_date( $post, array $item, $idx, $style, $skin = '' ) {
			$fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
			if ( $fmt === 'range' ) {
				return ECBB_Layout_Shell::ecbb_render_grid_date_flow( $post, $item, $idx, $skin );
			}
			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'date', $idx, $skin, $item ) );
			$tp   = ECBB_Date_Formatter::ecbb_build_day_time_parts( $post->ID, $item );

			if ( $fmt === 'time' ) {
				$html = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
			} elseif ( $fmt === 'day' ) {
				$html = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
			} else {
				$day  = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
				$time = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
				if ( $day !== '' && $time !== '' ) {
					$html = $day . ', ' . $time;
				} elseif ( $time !== '' ) {
					$html = $time;
				} else {
					$html = $day;
				}
			}
			if ( $html === '' ) {
				return '';
			}
			return '<div class="' . $wrap . ' ecbb-has-row-icon"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_date( $post, array $item, $idx, $style, $skin = '' ) {
			$idx    = absint( $idx );
			$skin   = (string) $skin;
			$attr   = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap   = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_date', $idx, $skin, $item ) );
			$format = ECBB_Date_Formatter::ecbb_part_date_php_fmt( 'event_date', $item );
			$php    = $format !== '' ? $format : get_option( 'date_format' );

			if ( function_exists( 'tribe_get_start_date' ) ) {
				$html = (string) \tribe_get_start_date( $post->ID, false, $php );
			} else {
				$raw = ECBB_Event_Data::ecbb_event_start_date_raw( $post->ID );
				$ts  = $raw ? strtotime( $raw ) : false;
				$html = $ts ? date_i18n( $php, $ts ) : '';
			}
			$html = trim( wp_strip_all_tags( $html ) );
			if ( $html === '' ) {
				return '';
			}
			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_time( $post, array $item, $idx, $style, $skin = '' ) {
			$idx    = absint( $idx );
			$skin   = (string) $skin;
			$attr   = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap   = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_time', $idx, $skin, $item ) );
			$format = ECBB_Date_Formatter::ecbb_part_date_php_fmt( 'event_time', $item );
			$tp     = ECBB_Date_Formatter::ecbb_build_day_time_parts( $post->ID, $item );
			$html   = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';

			if ( $html === '' ) {
				$php = ECBB_Date_Formatter::ecbb_time_fmt_lower( $format !== '' ? $format : get_option( 'time_format' ) );
				if ( function_exists( 'tribe_get_start_time' ) ) {
					$html = (string) \tribe_get_start_time( $post->ID, $php );
				} elseif ( function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) \tribe_get_start_date( $post->ID, true, $php );
				} else {
					$raw  = ECBB_Event_Data::ecbb_event_start_date_raw( $post->ID );
					$ts   = $raw ? strtotime( $raw ) : false;
					$html = $ts ? date_i18n( $php, $ts ) : '';
				}
				$html = ECBB_Date_Formatter::ecbb_time_lower_am( trim( wp_strip_all_tags( $html ) ) );
			}
			if ( $html === '' ) {
				return '';
			}
			$icon = ( $skin !== 'style2' ) ? ' ecbb-has-row-icon' : '';
			return '<div class="' . $wrap . $icon . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_day( $post, array $item, $idx, $style, $skin = '' ) {
			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_day', $idx, $skin, $item ) );
			$pr   = ECBB_Date_Formatter::ecbb_build_day_time_parts( $post->ID, [] );
			$html = isset( $pr['day'] ) ? trim( (string) $pr['day'] ) : '';

			if ( $html === '' ) {
				$raw  = ECBB_Event_Data::ecbb_event_start_date_raw( $post->ID );
				$ts   = $raw ? strtotime( $raw ) : false;
				$html = $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
			}
			if ( $html === '' ) {
				return '';
			}
			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/** Venue/organizer/event detail field with links where appropriate. */
		public static function ecbb_render_part_detail( $post, array $item, $idx, $style, $skin = '' ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' || ! in_array( $part, self::detail_slugs(), true ) ) {
				return '';
			}
			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( $part, $idx, $skin, $item ) );
			$html = ECBB_Event_Data::ecbb_part_detail_text( $post->ID, $part );
			if ( $html === '' ) {
				return '';
			}

			$loc_parts = [ 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ];
			$loc_icon  = in_array( $part, $loc_parts, true ) ? ' ecbb-has-row-icon' : '';

			if ( $part === 'organizer_email' && is_email( $html ) ) {
				return '<div class="' . $wrap . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( 'mailto:' . $html ) . '">' . esc_html( $html ) . '</a></div>';
			}

			$url_parts = [ 'venue_website', 'event_website', 'organizer_website', 'event_map_link' ];
			if ( in_array( $part, $url_parts, true ) ) {
				$safe = esc_url_raw( $html );
				if ( ! $safe || ! preg_match( '#^https?://#i', $safe ) ) {
					return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
				}
				$label = isset( $item['detail_link_text'] ) ? trim( (string) $item['detail_link_text'] ) : '';
				if ( $label === '' ) {
					$defaults = [
						'event_map_link'    => __( 'Open map', 'events-calendar-for-bricks' ),
						'event_website'     => __( 'Event website', 'events-calendar-for-bricks' ),
						'venue_website'     => __( 'Venue website', 'events-calendar-for-bricks' ),
						'organizer_website' => __( 'Organizer website', 'events-calendar-for-bricks' ),
					];
					$label = $defaults[ $part ] ?? $safe;
				} else {
					$label = sanitize_text_field( $label );
				}
				return '<div class="' . $wrap . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( $safe ) . '" rel="noopener noreferrer" target="_blank">' . esc_html( $label ) . '</a></div>';
			}

			return '<div class="' . $wrap . $loc_icon . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_cost( $post, array $item, $idx, $style, $skin = '' ) {
			$cost = ECBB_Cost_Formatter::ecbb_layout_cost_label( $post->ID, $item );
			if ( $cost === '' ) {
				return '';
			}
			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_cost', $idx, $skin, $item ) );
			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $cost ) . '</div>';
		}

		public static function ecbb_render_part_event_tickets( $post, array $item, $idx, $style, $skin = '' ) {
			$url = '';
			if ( function_exists( 'tribe_get_event' ) ) {
				$ev = tribe_get_event( $post->ID );
				if ( $ev && ! empty( $ev->website ) ) {
					$url = esc_url_raw( (string) $ev->website );
				}
			}
			if ( $url === '' ) {
				$m   = get_post_meta( $post->ID, '_EventUrl', true );
				$url = $m ? esc_url_raw( (string) $m ) : '';
			}
			if ( $url === '' ) {
				return '';
			}
			$label = isset( $item['tickets_link_text'] ) ? trim( (string) $item['tickets_link_text'] ) : '';
			$label = $label === '' ? esc_html__( 'Tickets', 'events-calendar-for-bricks' ) : sanitize_text_field( $label );

			$idx      = absint( $idx );
			$skin     = (string) $skin;
			$attr     = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap     = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_tickets', $idx, $skin, $item ) );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, $url, $label, '', ' rel="noopener noreferrer" target="_blank"' );
			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		public static function ecbb_render_part_event_rsvp( $post, array $item, $idx, $style, $skin = '' ) {
			$url   = get_permalink( $post->ID );
			$label = isset( $item['rsvp_link_text'] ) ? trim( (string) $item['rsvp_link_text'] ) : '';
			$label = $label === '' ? __( 'RSVP', 'events-calendar-for-bricks' ) : sanitize_text_field( $label );

			if ( function_exists( 'tribe_events_has_tickets' ) && tribe_events_has_tickets( $post->ID ) ) {
				$url .= '#tribe-tickets__tickets-form';
			}

			$idx      = absint( $idx );
			$skin     = (string) $skin;
			$attr     = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap     = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'event_rsvp', $idx, $skin, $item ) );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, $url, $label );
			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		public static function ecbb_render_part_read_more( $post, array $item, $idx, $style, $skin = '' ) {
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			$label = $label === '' ? __( 'View Details', 'events-calendar-for-bricks' ) : sanitize_text_field( $label );

			$idx      = absint( $idx );
			$skin     = (string) $skin;
			$attr     = ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap     = esc_attr( ECBB_Part_Chrome::ecbb_part_classes( 'read_more', $idx, $skin, $item ) );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, get_permalink( $post->ID ), $label );
			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		/** Dispatch extended part slug to its renderer; false when not handled. */
		public static function ecbb_render_part_ext( $post, array $item, $idx, $style, $skin = '' ) {
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$item = \ECBB_Styles::ecbb_clean_part( $item );
			}
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' ) {
				return false;
			}
			$handlers = self::ext_dispatch();
			if ( ! isset( $handlers[ $part ] ) ) {
				return false;
			}
			return call_user_func( $handlers[ $part ], $post, $item, $idx, $style, $skin );
		}
	}
}
