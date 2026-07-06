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

		/** Cast idx/skin and build wrapper attrs + class list for a part row. */
		private static function ecbb_part_shell( $part, array $item, $idx, $style, $skin, $class_suffix = '' ) {
			$idx  = absint( $idx );
			$skin = (string) $skin;
			return [
				'idx'  => $idx,
				'skin' => $skin,
				'attr' => ECBB_Part_Chrome::ecbb_part_wrap_attrs( $item, $idx, $style ),
				'wrap' => esc_attr( ECBB_Part_Chrome::ecbb_part_classes( $part, $idx, $skin, $item ) . $class_suffix ),
			];
		}

		/** Venue repeater row markup. */
		public static function ecbb_render_venue( $post, array $item, $idx, $style, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = ECBB_Event_Data::ecbb_venue_text( $post->ID, $item, $skin );
			if ( $text === '' ) {
				return '';
			}
			$shell = self::ecbb_part_shell( 'venue', $item, $idx, $style, $skin, ' ecbb-has-row-icon' );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $text ) . '</div>';
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
			$shell = self::ecbb_part_shell( 'organizer', $item, $idx, $style, $skin );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $text ) . '</div>';
		}

		/** Featured image (single size; hover via premade animation). */
		public static function ecbb_render_featured_img( $thumb_id, array $item ) {
			$thumb_id = (int) $thumb_id;
			if ( ! $thumb_id ) {
				return '';
			}

			$size_base = ECBB_Part_Chrome::ecbb_sanitize_image_size( $item['image_size'] ?? '', 'large' );
			$html      = wp_get_attachment_image( $thumb_id, $size_base, false, [ 'class' => 'ecbb-event__image' ] );

			return is_string( $html ) ? $html : '';
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
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$map[ \ECBB_Styles::ecbb_part_slug_venue_time() ]       = [ self::class, 'ecbb_render_venue_time' ];
				$map[ \ECBB_Styles::ecbb_part_slug_venue_time_cost() ] = [ self::class, 'ecbb_render_venue_time_cost' ];
			} else {
				$map['venue_time']       = [ self::class, 'ecbb_render_venue_time' ];
				$map['venue_time_cost'] = [ self::class, 'ecbb_render_venue_time_cost' ];
			}
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
			$shell = self::ecbb_part_shell( 'date', $item, $idx, $style, $skin );
			$tp    = ECBB_Date_Formatter::ecbb_build_day_time_parts( $post->ID, $item );

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
			return '<div class="' . $shell['wrap'] . ' ecbb-has-row-icon"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_date( $post, array $item, $idx, $style, $skin = '' ) {
			$shell  = self::ecbb_part_shell( 'event_date', $item, $idx, $style, $skin );
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
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_time( $post, array $item, $idx, $style, $skin = '' ) {
			$shell  = self::ecbb_part_shell( 'event_time', $item, $idx, $style, $skin );
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
			$icon = ( $shell['skin'] !== 'style2' ) ? ' ecbb-has-row-icon' : '';
			return '<div class="' . $shell['wrap'] . $icon . '"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_day( $post, array $item, $idx, $style, $skin = '' ) {
			$shell = self::ecbb_part_shell( 'event_day', $item, $idx, $style, $skin );
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
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
		}

		/** Venue/organizer/event detail field with links where appropriate. */
		public static function ecbb_render_part_detail( $post, array $item, $idx, $style, $skin = '' ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' || ! in_array( $part, self::detail_slugs(), true ) ) {
				return '';
			}
			$shell = self::ecbb_part_shell( $part, $item, $idx, $style, $skin );
			$html = ECBB_Event_Data::ecbb_part_detail_text( $post->ID, $part );
			if ( $html === '' ) {
				return '';
			}

			$loc_parts = [ 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ];
			$loc_icon  = in_array( $part, $loc_parts, true ) ? ' ecbb-has-row-icon' : '';

			if ( $part === 'organizer_email' && is_email( $html ) ) {
				return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '><a class="ecbb-event__link" href="' . esc_url( 'mailto:' . $html ) . '">' . esc_html( $html ) . '</a></div>';
			}

			$url_parts = [ 'venue_website', 'event_website', 'organizer_website', 'event_map_link' ];
			if ( in_array( $part, $url_parts, true ) ) {
				$safe = esc_url_raw( $html );
				if ( ! $safe || ! preg_match( '#^https?://#i', $safe ) ) {
					return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
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
				return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '><a class="ecbb-event__link" href="' . esc_url( $safe ) . '" rel="noopener noreferrer" target="_blank">' . esc_html( $label ) . '</a></div>';
			}

			return '<div class="' . $shell['wrap'] . $loc_icon . '"' . $shell['attr'] . '>' . esc_html( $html ) . '</div>';
		}

		public static function ecbb_render_part_event_cost( $post, array $item, $idx, $style, $skin = '' ) {
			$cost = ECBB_Cost_Formatter::ecbb_layout_cost_label( $post->ID, $item );
			if ( $cost === '' ) {
				return '';
			}
			$shell = self::ecbb_part_shell( 'event_cost', $item, $idx, $style, $skin );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . esc_html( $cost ) . '</div>';
		}

		/** Plain event time string for composite meta rows. */
		private static function ecbb_part_time_plain_text( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$time_item = $item;
			if ( ! isset( $time_item['date_display'] ) || (string) $time_item['date_display'] === '' ) {
				$time_item['date_display'] = 'time';
			}

			$format = ECBB_Date_Formatter::ecbb_part_date_php_fmt( 'event_time', $time_item );
			$tp     = ECBB_Date_Formatter::ecbb_build_day_time_parts( $post->ID, $time_item );
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

			return $html;
		}

		/**
		 * Meta icon for a composite segment.
		 *
		 * @param string $type  Icon key.
		 * @param bool   $boxed Use full Style 2 meta icon chrome (not inline).
		 */
		private static function ecbb_composite_segment_icon( $type, $boxed = false ) {
			if ( ! class_exists( 'ECBB_Layout_Shell', false ) ) {
				return '';
			}
			$icon = ECBB_Layout_Shell::ecbb_meta_icon( (string) $type );
			if ( $icon === '' ) {
				return '';
			}
			if ( $boxed ) {
				return $icon;
			}

			return str_replace(
				'class="ecbb-event-card__meta-icon"',
				'class="ecbb-event-card__meta-icon ecbb-event-card__meta-icon--inline"',
				$icon
			);
		}

		/**
		 * @param array<int,array{class:string,text:string,icon?:string}> $segments
		 * @param string                                                  $skin     Layout skin (style1|style2|grid).
		 */
		private static function ecbb_render_composite_meta_segments( array $segments, $skin = '' ) {
			$boxed_icons = ( (string) $skin === 'style2' );
			$chunks      = [];
			foreach ( $segments as $segment ) {
				$text = isset( $segment['text'] ) ? trim( (string) $segment['text'] ) : '';
				if ( $text === '' ) {
					continue;
				}
				$class = isset( $segment['class'] ) ? (string) $segment['class'] : 'ecbb-event__meta-segment';
				$icon  = isset( $segment['icon'] ) ? self::ecbb_composite_segment_icon( (string) $segment['icon'], $boxed_icons ) : '';
				$chunks[] = '<span class="' . esc_attr( $class ) . ' ecbb-event__meta-segment">'
					. $icon
					. '<span class="ecbb-event__meta-text">' . esc_html( $text ) . '</span>'
					. '</span>';
			}

			if ( $chunks === [] ) {
				return '';
			}

			return '<span class="ecbb-event__meta-group">' . implode( '', $chunks ) . '</span>';
		}

		/** Style 1 composite: venue + time in one meta row. */
		public static function ecbb_render_venue_time( $post, array $item, $idx, $style, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$venue = ECBB_Event_Data::ecbb_venue_text( $post->ID, $item, $skin );
			$time  = self::ecbb_part_time_plain_text( $post, $item );
			$inner = self::ecbb_render_composite_meta_segments(
				[
					[
						'class' => 'ecbb-event__meta-venue',
						'icon'  => 'pin',
						'text'  => $venue,
					],
					[
						'class' => 'ecbb-event__meta-time',
						'icon'  => 'clock',
						'text'  => $time,
					],
				],
				$skin
			);
			if ( $inner === '' ) {
				return '';
			}

			$part_slug = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_part_slug_venue_time()
				: 'venue_time';
			$shell     = self::ecbb_part_shell( $part_slug, $item, $idx, $style, $skin );
			$icon      = ( $shell['skin'] !== 'style2' ) ? ' ecbb-has-row-icon' : '';

			return '<div class="' . $shell['wrap'] . $icon . '"' . $shell['attr'] . '>' . $inner . '</div>';
		}

		/** Style 2 composite: venue + time + cost in one meta row. */
		public static function ecbb_render_venue_time_cost( $post, array $item, $idx, $style, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$venue = ECBB_Event_Data::ecbb_venue_text( $post->ID, $item, $skin );
			$time  = self::ecbb_part_time_plain_text( $post, $item );
			$cost  = ECBB_Cost_Formatter::ecbb_layout_cost_label( $post->ID, $item );
			$inner = self::ecbb_render_composite_meta_segments(
				[
					[
						'class' => 'ecbb-event__meta-venue',
						'icon'  => 'pin',
						'text'  => $venue,
					],
					[
						'class' => 'ecbb-event__meta-time',
						'icon'  => 'clock',
						'text'  => $time,
					],
					[
						'class' => 'ecbb-event__meta-cost',
						'icon'  => 'cost',
						'text'  => $cost,
					],
				],
				$skin
			);
			if ( $inner === '' ) {
				return '';
			}

			$part_slug = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_part_slug_venue_time_cost()
				: 'venue_time_cost';
			$shell     = self::ecbb_part_shell( $part_slug, $item, $idx, $style, $skin );

			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $inner . '</div>';
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

			$shell    = self::ecbb_part_shell( 'event_tickets', $item, $idx, $style, $skin );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, $url, $label, '', ' rel="noopener noreferrer" target="_blank"' );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $inner_el . '</div>';
		}

		public static function ecbb_render_part_event_rsvp( $post, array $item, $idx, $style, $skin = '' ) {
			$url   = get_permalink( $post->ID );
			$label = isset( $item['rsvp_link_text'] ) ? trim( (string) $item['rsvp_link_text'] ) : '';
			$label = $label === '' ? __( 'RSVP', 'events-calendar-for-bricks' ) : sanitize_text_field( $label );

			if ( function_exists( 'tribe_events_has_tickets' ) && tribe_events_has_tickets( $post->ID ) ) {
				$url .= '#tribe-tickets__tickets-form';
			}

			$shell    = self::ecbb_part_shell( 'event_rsvp', $item, $idx, $style, $skin );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, $url, $label );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $inner_el . '</div>';
		}

		public static function ecbb_render_part_read_more( $post, array $item, $idx, $style, $skin = '' ) {
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			$label = $label === '' ? __( 'View Details', 'events-calendar-for-bricks' ) : sanitize_text_field( $label );

			$shell    = self::ecbb_part_shell( 'read_more', $item, $idx, $style, $skin );
			$inner_el = ECBB_Part_Chrome::ecbb_action_link_html( $item, get_permalink( $post->ID ), $label );
			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $inner_el . '</div>';
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
