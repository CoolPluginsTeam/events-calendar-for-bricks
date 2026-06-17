<?php
/**
 * Events Widget: shared TEC event part markup (date, tickets, images, hover helpers). List Style 2 shell:
 * `template/list/list-style-2.php`.
 *
 * File: `includes/events-widget/ecbb-events-widget-loop-markup.php`.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve Bricks color control values (palette id, raw, rgb object, hex) to a CSS color string.
 * Mirrors {@see \Bricks\Assets::generate_css_color()} when available.
 *
 * @param mixed $value Saved control value (array, object, string, JSON string).
 * @return string Usable CSS color or empty string.
 */
function ecbb_normalize_bricks_color( $value ) {
	if ( $value === null || $value === false ) {
		return '';
	}

	if ( is_object( $value ) ) {
		$value = (array) $value;
	}

	$candidate = '';

	if ( is_array( $value ) ) {
		if ( class_exists( '\Bricks\Assets' ) && method_exists( '\Bricks\Assets', 'generate_css_color' )
			&& ( isset( $value['id'] ) || isset( $value['raw'] ) || isset( $value['rgb'] ) || isset( $value['hex'] ) || isset( $value['rgba'] ) ) ) {
			$gen = \Bricks\Assets::generate_css_color( $value );
			if ( is_string( $gen ) && trim( $gen ) !== '' ) {
				$candidate = trim( $gen );
			}
		}
		if ( $candidate === '' && isset( $value['rgb'] ) && is_array( $value['rgb'] ) ) {
			$r = isset( $value['rgb']['r'] ) ? (int) $value['rgb']['r'] : 0;
			$g = isset( $value['rgb']['g'] ) ? (int) $value['rgb']['g'] : 0;
			$b = isset( $value['rgb']['b'] ) ? (int) $value['rgb']['b'] : 0;
			$a = isset( $value['rgb']['a'] ) ? (float) $value['rgb']['a'] : 1.0;
			$candidate = 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
		}
		if ( $candidate === '' ) {
			$tmp = $value['raw'] ?? $value['rgba'] ?? $value['hex'] ?? $value['value'] ?? '';
			$candidate = is_string( $tmp ) ? trim( $tmp ) : '';
		}
	} elseif ( is_string( $value ) ) {
		$candidate = trim( $value );
	} else {
		return '';
	}

	if ( $candidate === '' ) {
		return '';
	}

	if ( isset( $candidate[0] ) && ( $candidate[0] === '{' || $candidate[0] === '[' ) ) {
		$decoded = json_decode( $candidate, true );
		if ( is_array( $decoded ) ) {
			return ecbb_normalize_bricks_color( $decoded );
		}
	}

	$candidate = preg_replace( '/\s*!important\s*$/i', '', $candidate );
	$candidate = rtrim( trim( $candidate ), ';' );

	if ( preg_match( '/^var\\(--[a-zA-Z0-9\\-_]+(\\s*,\\s*[^\\)]+)?\\)$/', $candidate ) ) {
		return $candidate;
	}

	$lower = strtolower( $candidate );
	if ( in_array( $lower, [ 'transparent', 'currentcolor', 'inherit', 'initial', 'unset' ], true ) ) {
		return $candidate;
	}

	if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $candidate ) ) {
		return $candidate;
	}

	if ( preg_match( '/^rgba?\\(([^\\)]+)\\)$/', $candidate ) ) {
		return $candidate;
	}

	if ( preg_match( '/^hsla?\\(([^\\)]+)\\)$/', $candidate ) ) {
		return $candidate;
	}

	return '';
}

function ecbb_event_part_resolve_php_format( $part, array $item ) {
	$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
	$custom = isset( $item['date_format_custom'] ) ? trim( (string) $item['date_format_custom'] ) : '';

	if ( $preset === 'custom' ) {
		return $custom;
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

	// If a raw PHP format string was chosen from the dropdown.
	if ( $preset !== '' ) {
		return $preset;
	}

	// Default per part.
	if ( $part === 'event_time' ) {
		return (string) get_option( 'time_format' );
	}

	if ( $part === 'event_date' ) {
		return (string) get_option( 'date_format' );
	}

	return '';
}

/**
 * Plain-text detail for extra venue / organizer / event fields (repeater parts).
 *
 * Uses The Events Calendar template helpers when available, otherwise venue post meta.
 *
 * @param int    $event_id Event post ID.
 * @param string $part     Part slug (e.g. venue_city, organizer_email).
 * @return string          Unescaped plain text; caller must escape for HTML.
 */
function ecbb_events_widget_event_part_detail_plain( $event_id, $part ) {
	$event_id = (int) $event_id;
	$part     = (string) $part;
	if ( $event_id < 1 ) {
		return '';
	}

	switch ( $part ) {
		case 'venue_full_address':
			if ( function_exists( 'tribe_get_full_address' ) ) {
				$raw = (string) \tribe_get_full_address( $event_id );
				$raw = preg_replace( '/<br\s*\/?>/i', ', ', $raw );
				$t   = trim( wp_strip_all_tags( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ) ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$bits = array_filter(
				[
					ecbb_events_widget_event_part_detail_plain( $event_id, 'venue_street' ),
					ecbb_events_widget_event_part_detail_plain( $event_id, 'venue_city' ),
					trim(
						ecbb_events_widget_event_part_detail_plain( $event_id, 'venue_state' )
						. ' '
						. ecbb_events_widget_event_part_detail_plain( $event_id, 'venue_zip' )
					),
					ecbb_events_widget_event_part_detail_plain( $event_id, 'venue_country' ),
				]
			);
			$bits = array_map( 'trim', $bits );
			$bits = array_filter( $bits );
			return implode( ', ', $bits );

		case 'venue_street':
			if ( function_exists( 'tribe_get_address' ) ) {
				$t = trim( (string) \tribe_get_address( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_city':
			if ( function_exists( 'tribe_get_city' ) ) {
				$t = trim( (string) \tribe_get_city( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_state':
			if ( function_exists( 'tribe_get_province' ) ) {
				$t = trim( (string) \tribe_get_province( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			if ( function_exists( 'tribe_get_state' ) ) {
				$t = trim( (string) \tribe_get_state( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_zip':
			if ( function_exists( 'tribe_get_zip' ) ) {
				$t = trim( (string) \tribe_get_zip( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_country':
			if ( function_exists( 'tribe_get_country' ) ) {
				$t = trim( (string) \tribe_get_country( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_phone':
			if ( function_exists( 'tribe_get_phone' ) ) {
				$t = trim( (string) \tribe_get_phone( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			break;

		case 'venue_website':
			if ( function_exists( 'tribe_get_venue_website_url' ) ) {
				$t = trim( (string) \tribe_get_venue_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = (int) get_post_meta( $event_id, '_EventVenueID', true );
			if ( $vid > 0 ) {
				$t = trim( (string) get_post_meta( $vid, '_VenueURL', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';

		case 'event_map_link':
			if ( function_exists( 'tribe_get_map_link_url' ) ) {
				return trim( (string) \tribe_get_map_link_url( $event_id ) );
			}
			if ( function_exists( 'tribe_get_map_link' ) ) {
				$raw = (string) \tribe_get_map_link( $event_id );
				if ( preg_match( '/href=[\"\']([^\"\']+)[\"\']/', $raw, $m ) ) {
					return trim( $m[1] );
				}
			}
			return '';

		case 'event_website':
			if ( function_exists( 'tribe_get_event_website_url' ) ) {
				$t = trim( (string) \tribe_get_event_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$m = get_post_meta( $event_id, '_EventUrl', true );
			return $m ? trim( (string) $m ) : '';

		case 'event_phone':
			$m = get_post_meta( $event_id, '_EventPhone', true );
			return $m ? trim( wp_strip_all_tags( (string) $m ) ) : '';

		case 'organizer_email':
			if ( function_exists( 'tribe_get_organizer_email' ) ) {
				$t = trim( (string) \tribe_get_organizer_email( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerEmail', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';

		case 'organizer_phone':
			if ( function_exists( 'tribe_get_organizer_phone' ) ) {
				$t = trim( (string) \tribe_get_organizer_phone( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerPhone', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';

		case 'organizer_website':
			if ( function_exists( 'tribe_get_organizer_website_url' ) ) {
				$t = trim( (string) \tribe_get_organizer_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerWebsite', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';

		default:
			return '';
	}

	$vid = (int) get_post_meta( $event_id, '_EventVenueID', true );
	if ( $vid < 1 ) {
		return '';
	}
	switch ( $part ) {
		case 'venue_street':
			return trim( (string) get_post_meta( $vid, '_VenueAddress', true ) );
		case 'venue_city':
			return trim( (string) get_post_meta( $vid, '_VenueCity', true ) );
		case 'venue_state':
			$s = get_post_meta( $vid, '_VenueStateProvince', true );
			if ( $s === '' || $s === null ) {
				$s = get_post_meta( $vid, '_VenueState', true );
			}
			return trim( (string) $s );
		case 'venue_zip':
			return trim( (string) get_post_meta( $vid, '_VenueZip', true ) );
		case 'venue_country':
			return trim( (string) get_post_meta( $vid, '_VenueCountry', true ) );
		case 'venue_phone':
			return trim( (string) get_post_meta( $vid, '_VenuePhone', true ) );
		default:
			return '';
	}
}

/**
 * Structured values for the "Day & time range" part so CSS can style day vs time.
 *
 * @param int   $post_id Event post ID.
 * @param array $item    Unused (signature kept for callers).
 * @return array{day:string,time:string} Both may be empty strings.
 */
function ecbb_event_part_build_day_time_range_parts( $post_id, array $item ) {
	$post_id = (int) $post_id;
	$start_raw = (string) get_post_meta( $post_id, '_EventStartDate', true );
	$end_raw   = (string) get_post_meta( $post_id, '_EventEndDate', true );
	$start_ts  = $start_raw ? strtotime( $start_raw ) : false;
	if ( ! $start_ts ) {
		return [ 'day' => '', 'time' => '' ];
	}
	$end_ts = $end_raw ? strtotime( $end_raw ) : $start_ts;
	if ( ! $end_ts ) {
		$end_ts = $start_ts;
	}

	$all_day = function_exists( 'tribe_event_is_all_day' ) && \tribe_event_is_all_day( $post_id );

	$day_fmt  = 'l';
	$time_fmt = (string) get_option( 'time_format' );

	$day_str = date_i18n( $day_fmt, $start_ts );
	$day_str = trim( wp_strip_all_tags( $day_str ) );
	if ( $day_str === '' ) {
		return [ 'day' => '', 'time' => '' ];
	}

	if ( $all_day ) {
		return [ 'day' => $day_str, 'time' => '' ];
	}

	$t_start = '';
	$t_end   = '';

	if ( function_exists( 'tribe_get_start_time' ) ) {
		$t_start = (string) \tribe_get_start_time( $post_id, $time_fmt );
	}
	if ( $t_start === '' && function_exists( 'tribe_get_start_date' ) ) {
		$t_start = (string) \tribe_get_start_date( $post_id, true, $time_fmt );
	}
	if ( $t_start === '' ) {
		$t_start = date_i18n( $time_fmt, $start_ts );
	}

	if ( function_exists( 'tribe_get_end_time' ) ) {
		$t_end = (string) \tribe_get_end_time( $post_id, $time_fmt );
	}
	if ( $t_end === '' && function_exists( 'tribe_get_end_date' ) ) {
		$t_end = (string) \tribe_get_end_date( $post_id, true, $time_fmt );
	}
	if ( $t_end === '' ) {
		$t_end = date_i18n( $time_fmt, $end_ts );
	}

	$t_start = trim( wp_strip_all_tags( $t_start ) );
	$t_end   = trim( wp_strip_all_tags( $t_end ) );

	if ( $t_start === '' ) {
		return [ 'day' => $day_str, 'time' => '' ];
	}
	if ( $t_end === '' || $t_start === $t_end ) {
		return [ 'day' => $day_str, 'time' => $t_start ];
	}

	return [ 'day' => $day_str, 'time' => $t_start . ' - ' . $t_end ];
}

function ecbb_event_part_button_style_attr( array $item ) {
	if ( empty( $item['btn_style'] ) ) {
		return '';
	}

	$styles = [];

	if ( ! empty( $item['btn_bg'] ) ) {
		$bg = function_exists( 'ecbb_normalize_bricks_color' )
			? ecbb_normalize_bricks_color( $item['btn_bg'] )
			: (string) $item['btn_bg'];
		if ( $bg !== '' ) {
			$styles[] = 'background-color:' . esc_attr( $bg );
		}
	}

	if ( ! empty( $item['btn_text_color'] ) ) {
		$tc = function_exists( 'ecbb_normalize_bricks_color' )
			? ecbb_normalize_bricks_color( $item['btn_text_color'] )
			: (string) $item['btn_text_color'];
		if ( $tc !== '' ) {
			$styles[] = 'color:' . esc_attr( $tc ) . ' !important';
		}
	}

	$has_border_radius = false;
	if ( ! empty( $item['btn_border'] ) && function_exists( 'ecbb_events_widget_border_declarations' ) ) {
		$border_decls = ecbb_events_widget_border_declarations( $item['btn_border'] );
		if ( ! empty( $border_decls ) ) {
			foreach ( $border_decls as $decl ) {
				$styles[] = $decl;
				if ( strpos( $decl, 'border-radius:' ) === 0 ) {
					$has_border_radius = true;
				}
			}
			$styles[] = 'box-sizing:border-box';
		}
	} elseif ( ! empty( $item['btn_border'] ) && function_exists( 'ecbb_events_widget_border_to_css' ) ) {
		$border = ecbb_events_widget_border_to_css( $item['btn_border'] );
		if ( $border !== '' ) {
			$styles[] = 'border:' . esc_attr( $border );
			$styles[] = 'box-sizing:border-box';
		}
	}

	$radius = isset( $item['btn_radius'] ) ? trim( (string) $item['btn_radius'] ) : '';
	if ( $radius !== '' && ! $has_border_radius ) {
		$styles[] = 'border-radius:' . esc_attr( $radius );
	}

	$padding_css = '';
	if ( ! empty( $item['btn_padding'] ) && function_exists( 'ecbb_events_widget_spacing_to_css' ) ) {
		$padding_css = ecbb_events_widget_spacing_to_css( $item['btn_padding'] );
	}
	if ( $padding_css === '' ) {
		$py = isset( $item['btn_padding_y'] ) ? trim( (string) $item['btn_padding_y'] ) : '';
		$px = isset( $item['btn_padding_x'] ) ? trim( (string) $item['btn_padding_x'] ) : '';
		if ( $py !== '' || $px !== '' ) {
			$padding_css = ( $py !== '' ? $py : '10px' ) . ' ' . ( $px !== '' ? $px : '14px' );
		}
	}
	if ( $padding_css !== '' ) {
		$styles[] = 'padding:' . esc_attr( $padding_css );
	}

	$styles[] = 'display:inline-flex';
	$styles[] = 'align-items:center';
	$styles[] = 'justify-content:center';
	$styles[] = 'text-decoration:none';

	return empty( $styles ) ? '' : ' style="' . implode( ';', $styles ) . ';"';
}

/**
 * Whether one cost token is "free" (zero or common free labels).
 *
 * @param string $token Single price fragment or whole cost.
 * @return bool
 */
function ecbb_events_widget_cost_token_is_free( $token ) {
	$t = trim( wp_strip_all_tags( html_entity_decode( (string) $token, ENT_QUOTES, 'UTF-8' ) ) );
	if ( $t === '' ) {
		return false;
	}
	$lower = strtolower( $t );
	$labels = [ 'free', 'gratis', 'no cost', 'nocost', 'included' ];
	foreach ( $labels as $w ) {
		if ( $lower === $w ) {
			return true;
		}
	}
	// Strip leading/trailing currency symbols and whitespace; test numeric zero.
	$num = preg_replace( '/^[\p{Sc}\s]+/u', '', $t );
	$num = preg_replace( '/[\p{Sc}\s]+$/u', '', $num );
	if ( $num === '' ) {
		return false;
	}
	return (bool) preg_match( '/^0(?:\.0+)?$/', $num );
}

/**
 * Build cost label: "Free", a single amount, or "min – max" for ranges.
 *
 * @param int   $post_id Event post ID.
 * @param array $item    Repeater row (cost_currency).
 * @return string Plain text (escaped by caller).
 */
function ecbb_events_widget_format_event_cost_display( $post_id, array $item ) {
	$post_id = (int) $post_id;
	if ( $post_id < 1 ) {
		return '';
	}

	$with_currency = ! isset( $item['cost_currency'] ) || (string) $item['cost_currency'] === 'symbol';

	$formatted = '';
	if ( function_exists( 'tribe_get_cost' ) ) {
		$formatted = trim( wp_strip_all_tags( html_entity_decode( (string) \tribe_get_cost( $post_id, $with_currency ), ENT_QUOTES, 'UTF-8' ) ) );
	}

	$raw = trim( wp_strip_all_tags( html_entity_decode( (string) get_post_meta( $post_id, '_EventCost', true ), ENT_QUOTES, 'UTF-8' ) ) );

	$candidate = $formatted !== '' ? $formatted : $raw;
	if ( $candidate === '' ) {
		// No cost set on the event — treat as free (matches TEC "no cost" behaviour).
		return __( 'Free', 'ecbb' );
	}

	if ( ecbb_events_widget_cost_token_is_free( $candidate ) ) {
		return __( 'Free', 'ecbb' );
	}

	if ( preg_match( '/^(.+?)([-–—])(.+)$/u', $candidate, $m ) ) {
		$left  = trim( $m[1] );
		$right = trim( $m[3] );
		if ( $left !== '' && $right !== '' ) {
			if ( strcasecmp( $left, $right ) === 0 ) {
				return ecbb_events_widget_cost_token_is_free( $left )
					? __( 'Free', 'ecbb' )
					: $left;
			}
			if ( ecbb_events_widget_cost_token_is_free( $left ) && ecbb_events_widget_cost_token_is_free( $right ) ) {
				return __( 'Free', 'ecbb' );
			}
			return $left . ' – ' . $right;
		}
	}

	return $candidate;
}

/**
 * Short per-row class for Bricks-generated CSS (scoped under .ecbb-ev--{id}).
 *
 * @param int $idx Row index.
 * @return string
 */
function ecbb_events_widget_part_idx_class( $idx ) {
	return 'ecbb-p' . absint( $idx );
}

/**
 * Outer classes for a rendered part wrapper.
 *
 * @param string $part  Part slug.
 * @param int    $idx   Row index.
 * @param string $skin  '' or 'style2'.
 * @return string        Space-separated classes (not escaped).
 */
function ecbb_events_widget_part_wrap_classes( $part, $idx, $skin = '' ) {
	$idx_c = ecbb_events_widget_part_idx_class( $idx );
	if ( (string) $skin === 'style2' ) {
		return ecbb_list2_part_class( $part ) . ' ' . $idx_c;
	}
	$bem = 'ecbb-event-part--' . str_replace( '_', '-', (string) $part );
	return 'ecbb-event-part ' . $bem . ' ' . $idx_c;
}

/**
 * @param \WP_Post $post      Event post.
 * @param array    $item     Repeater row settings.
 * @param int      $idx      Row index (for CSS class).
 * @param string   $style    Inline style attribute value (contents only), or empty.
 * @param string   $skin     Loop skin: '' or 'style2'.
 * @return string|false      Markup, empty string when nothing to show, false if not an extended part.
 */
function ecbb_event_part_extended_markup( $post, array $item, $idx, $style, $skin = '' ) {
	if ( function_exists( 'ecbb_events_widget_normalize_part_item' ) ) {
		$item = ecbb_events_widget_normalize_part_item( $item );
	}
	$part = isset( $item['part'] ) ? (string) $item['part'] : '';
	$idx  = absint( $idx );
	$skin = (string) $skin;
	$attr      = $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '';
	$link_attr = $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '';
	$detail_parts = [
		'venue_full_address',
		'venue_street',
		'venue_city',
		'venue_state',
		'venue_zip',
		'venue_country',
		'venue_phone',
		'venue_website',
		'event_map_link',
		'event_website',
		'event_phone',
		'organizer_email',
		'organizer_phone',
		'organizer_website',
	];
	$extended = [ 'event_date', 'event_time', 'event_day', 'event_cost', 'event_tickets', 'event_rsvp', 'read_more' ];
	if ( ! in_array( $part, $extended, true ) && ! in_array( $part, $detail_parts, true ) ) {
		return false;
	}

	$wrap = function ( $slug ) use ( $skin, $idx ) {
		return esc_attr( ecbb_events_widget_part_wrap_classes( $slug, $idx, $skin ) );
	};

	$format = ( $part === 'event_date' || $part === 'event_time' )
		? ecbb_event_part_resolve_php_format( $part, $item )
		: '';

	if ( $part === 'event_date' ) {
		$html = '';
		if ( function_exists( 'tribe_get_start_date' ) ) {
			$php = $format !== '' ? $format : get_option( 'date_format' );
			$html = (string) \tribe_get_start_date( $post->ID, false, $php );
		} else {
			$raw = (string) get_post_meta( $post->ID, '_EventStartDate', true );
			$ts  = $raw ? strtotime( $raw ) : false;
			$php = $format !== '' ? $format : get_option( 'date_format' );
			$html = $ts ? date_i18n( $php, $ts ) : '';
		}
		$html = trim( wp_strip_all_tags( $html ) );
		if ( $html === '' ) {
			return '';
		}
		return '<div class="' . $wrap( 'event_date' ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
	}

	if ( $part === 'event_time' ) {
		$html = '';
		if ( function_exists( 'tribe_get_start_time' ) ) {
			$php  = $format !== '' ? $format : get_option( 'time_format' );
			$html = (string) \tribe_get_start_time( $post->ID, $php );
		} elseif ( function_exists( 'tribe_get_start_date' ) ) {
			$php  = $format !== '' ? $format : get_option( 'time_format' );
			$html = (string) \tribe_get_start_date( $post->ID, true, $php );
		} else {
			$raw = (string) get_post_meta( $post->ID, '_EventStartDate', true );
			$ts  = $raw ? strtotime( $raw ) : false;
			$php = $format !== '' ? $format : get_option( 'time_format' );
			$html = $ts ? date_i18n( $php, $ts ) : '';
		}
		$html = trim( wp_strip_all_tags( $html ) );
		if ( $html === '' ) {
			return '';
		}
		return '<div class="' . $wrap( 'event_time' ) . ( $skin !== 'style2' && $html !== '' ? ' ecbb-has-row-icon' : '' ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
	}

	if ( $part === 'event_day' ) {
		$html = '';
		if ( function_exists( 'ecbb_event_part_build_day_time_range_parts' ) ) {
			$pr = ecbb_event_part_build_day_time_range_parts( $post->ID, [] );
			$html = isset( $pr['day'] ) ? trim( (string) $pr['day'] ) : '';
		}
		if ( $html === '' ) {
			$raw = (string) get_post_meta( $post->ID, '_EventStartDate', true );
			$ts  = $raw ? strtotime( $raw ) : false;
			$html = $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
		}
		if ( $html === '' ) {
			return '';
		}
		return '<div class="' . $wrap( 'event_day' ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
	}

	if ( in_array( $part, $detail_parts, true ) ) {
		$html = ecbb_events_widget_event_part_detail_plain( $post->ID, $part );
		if ( $html === '' ) {
			return '';
		}
		$venue_physical = [ 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ];
		$loc_icon       = in_array( $part, $venue_physical, true ) ? ' ecbb-has-row-icon' : '';
		$use_venue_link = ! empty( $item['venue_link'] ) && in_array( $part, $venue_physical, true );
		$venue_url      = '';
		if ( $use_venue_link ) {
			$vid = (int) get_post_meta( $post->ID, '_EventVenueID', true );
			if ( $vid > 0 ) {
				$venue_url = get_permalink( $vid );
			}
		}
		$venue_url = $venue_url ? esc_url( $venue_url ) : '';

		if ( $part === 'organizer_email' && is_email( $html ) ) {
			return '<div class="' . $wrap( $part ) . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( 'mailto:' . $html ) . '"' . $link_attr . '>' . esc_html( $html ) . '</a></div>';
		}

		$url_parts = [ 'venue_website', 'event_website', 'organizer_website', 'event_map_link' ];
		if ( in_array( $part, $url_parts, true ) ) {
			$safe = esc_url_raw( $html );
			if ( ! $safe || ! preg_match( '#^https?://#i', $safe ) ) {
				return '<div class="' . $wrap( $part ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
			}
			$label = isset( $item['detail_link_text'] ) ? trim( (string) $item['detail_link_text'] ) : '';
			if ( $label === '' ) {
				$defaults = [
					'event_map_link'    => __( 'Open map', 'ecbb' ),
					'event_website'     => __( 'Event website', 'ecbb' ),
					'venue_website'     => __( 'Venue website', 'ecbb' ),
					'organizer_website' => __( 'Organizer website', 'ecbb' ),
				];
				$label = isset( $defaults[ $part ] ) ? $defaults[ $part ] : $safe;
			} else {
				$label = sanitize_text_field( $label );
			}
			return '<div class="' . $wrap( $part ) . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( $safe ) . '" rel="noopener noreferrer" target="_blank"' . $link_attr . '>' . esc_html( $label ) . '</a></div>';
		}

		if ( $use_venue_link && $venue_url ) {
			return '<div class="' . $wrap( $part ) . $loc_icon . '"' . $attr . '><a class="ecbb-event__link" href="' . $venue_url . '"' . $link_attr . '>' . esc_html( $html ) . '</a></div>';
		}

		return '<div class="' . $wrap( $part ) . $loc_icon . '"' . $attr . '>' . esc_html( $html ) . '</div>';
	}

	if ( $part === 'event_cost' ) {
		$cost = function_exists( 'ecbb_events_widget_format_event_cost_display' )
			? ecbb_events_widget_format_event_cost_display( $post->ID, $item )
			: '';
		if ( $cost === '' ) {
			return '';
		}
		$prefix = isset( $item['cost_prefix'] ) ? (string) $item['cost_prefix'] : '';
		$suffix = isset( $item['cost_suffix'] ) ? (string) $item['cost_suffix'] : '';
		return '<div class="' . $wrap( 'event_cost' ) . '"' . $attr . '>' . esc_html( $prefix . $cost . $suffix ) . '</div>';
	}

	if ( $part === 'event_tickets' ) {
		$url = '';
		if ( function_exists( 'tribe_get_event' ) ) {
			$ev = tribe_get_event( $post->ID );
			if ( $ev && ! empty( $ev->website ) ) {
				$url = esc_url_raw( (string) $ev->website );
			}
		}
		if ( $url === '' ) {
			$m = get_post_meta( $post->ID, '_EventUrl', true );
			$url = $m ? esc_url_raw( (string) $m ) : '';
		}
		if ( $url === '' ) {
			return '';
		}
		$label = isset( $item['tickets_link_text'] ) ? trim( (string) $item['tickets_link_text'] ) : '';
		if ( $label === '' ) {
			$label = esc_html__( 'Tickets', 'ecbb' );
		} else {
			$label = sanitize_text_field( $label );
		}
		$btn_attr = ecbb_event_part_button_style_attr( $item );
		return '<div class="' . $wrap( 'event_tickets' ) . '"' . $attr . '>'
			. '<a class="ecbb-event__link" href="' . esc_url( $url ) . '" rel="noopener noreferrer" target="_blank"' . ( $btn_attr !== '' ? $btn_attr : $link_attr ) . '>' . esc_html( $label ) . '</a>'
			. '</div>';
	}

	if ( $part === 'event_rsvp' ) {
		$url   = get_permalink( $post->ID );
		$label = isset( $item['rsvp_link_text'] ) ? trim( (string) $item['rsvp_link_text'] ) : '';
		if ( $label === '' ) {
			$label = esc_html__( 'RSVP', 'ecbb' );
		} else {
			$label = sanitize_text_field( $label );
		}
		$frag = '#tribe-tickets__tickets-form';
		if ( function_exists( 'tribe_events_has_tickets' ) && tribe_events_has_tickets( $post->ID ) ) {
			$url = $url . $frag;
		}
		$btn_attr = ecbb_event_part_button_style_attr( $item );
		return '<div class="' . $wrap( 'event_rsvp' ) . '"' . $attr . '>'
			. '<a class="ecbb-event__link" href="' . esc_url( $url ) . '"' . ( $btn_attr !== '' ? $btn_attr : $link_attr ) . '>' . esc_html( $label ) . '</a>'
			. '</div>';
	}

	if ( $part === 'read_more' ) {
		$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
		if ( $label === '' ) {
			if ( $skin === 'style2' ) {
				$label = esc_html__( 'More Details', 'ecbb' );
			} else {
				$label = esc_html__( 'Find Out More', 'ecbb' );
			}
		} else {
			$label = sanitize_text_field( $label );
		}
		$btn_attr = ecbb_event_part_button_style_attr( $item );
		return '<div class="' . $wrap( 'read_more' ) . '"' . $attr . '>'
			. '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '"' . ( $btn_attr !== '' ? $btn_attr : $link_attr ) . '>' . esc_html( $label ) . '</a>'
			. '</div>';
	}

	return false;
}

/**
 * Bricks-style labels for featured image size dropdowns (matches WP registered sizes).
 *
 * @return array<string, string> Slug => label.
 */
function ecbb_get_image_size_control_options() {
	$opts = [
		'' => esc_html__( 'Default', 'ecbb' ),
	];
	$subs = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : [];
	foreach ( $subs as $slug => $data ) {
		if ( ! is_string( $slug ) || $slug === '' ) {
			continue;
		}
		$w = isset( $data['width'] ) ? (int) $data['width'] : 0;
		$h = isset( $data['height'] ) ? (int) $data['height'] : 0;
		$opts[ $slug ] = $slug . ' (' . $w . '×' . $h . ')';
	}
	if ( function_exists( 'get_intermediate_image_sizes' ) ) {
		foreach ( get_intermediate_image_sizes() as $slug ) {
			if ( is_string( $slug ) && $slug !== '' && ! isset( $opts[ $slug ] ) ) {
				$opts[ $slug ] = $slug;
			}
		}
	}
	$opts['full'] = esc_html__( 'Full', 'ecbb' );
	return $opts;
}

/**
 * @param string $slug   Saved size slug or empty for fallback.
 * @param string $fallback Used when empty or invalid.
 * @return string
 */
function ecbb_sanitize_attachment_image_size( $slug, $fallback = 'large' ) {
	$slug = is_string( $slug ) ? trim( $slug ) : '';
	if ( $slug === '' ) {
		return $fallback;
	}
	if ( $slug === 'full' ) {
		return 'full';
	}
	$reg = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : [];
	if ( isset( $reg[ $slug ] ) ) {
		return $slug;
	}
	$intermediate = function_exists( 'get_intermediate_image_sizes' ) ? get_intermediate_image_sizes() : [];
	if ( is_array( $intermediate ) && in_array( $slug, $intermediate, true ) ) {
		return $slug;
	}
	if ( preg_match( '/^[a-z0-9_\\-]+$/i', $slug ) ) {
		return $slug;
	}
	return $fallback;
}

/**
 * Nine-point alignment (Bricks-style) for object-position on images.
 *
 * @return array<string, string>
 */
function ecbb_get_image_object_align_control_options() {
	return [
		''   => esc_html__( 'Default', 'ecbb' ),
		'tl' => esc_html__( 'Top left', 'ecbb' ),
		'tc' => esc_html__( 'Top center', 'ecbb' ),
		'tr' => esc_html__( 'Top right', 'ecbb' ),
		'ml' => esc_html__( 'Middle left', 'ecbb' ),
		'mc' => esc_html__( 'Middle center', 'ecbb' ),
		'mr' => esc_html__( 'Middle right', 'ecbb' ),
		'bl' => esc_html__( 'Bottom left', 'ecbb' ),
		'bc' => esc_html__( 'Bottom center', 'ecbb' ),
		'br' => esc_html__( 'Bottom right', 'ecbb' ),
	];
}

/**
 * @param string $key Short key (tl, mc, …) or empty.
 * @return string CSS object-position value or empty when default.
 */
function ecbb_object_position_from_image_align( $key ) {
	$key = is_string( $key ) ? strtolower( trim( $key ) ) : '';
	$map = [
		'tl' => 'left top',
		'tc' => 'center top',
		'tr' => 'right top',
		'ml' => 'left center',
		'mc' => 'center center',
		'mr' => 'right center',
		'bl' => 'left bottom',
		'bc' => 'center bottom',
		'br' => 'right bottom',
	];
	return isset( $map[ $key ] ) ? $map[ $key ] : '';
}

/**
 * Load-more button markup (AJAX appends into `.ecbb-ev__list`).
 *
 * @param array $settings  Full element settings (JSON-encoded on the button).
 * @param int   $offset    Next query offset.
 * @param int   $limit     Batch size.
 * @param bool  $has_more  Whether more events exist beyond the current list.
 * @return string HTML or empty string.
 */
function ecbb_events_widget_render_load_more_markup( array $settings, $offset, $limit, $has_more ) {
	if ( ! $has_more || $limit < 1 ) {
		return '';
	}
	if ( ! function_exists( 'ecbb_events_widget_load_more_enabled' ) || ! ecbb_events_widget_load_more_enabled( $settings ) ) {
		return '';
	}

	$text = isset( $settings['load_more_text'] ) ? trim( (string) $settings['load_more_text'] ) : '';
	if ( $text === '' ) {
		$text = __( 'Load more', 'ecbb' );
	}

	$loading = isset( $settings['load_more_loading_text'] ) ? trim( (string) $settings['load_more_loading_text'] ) : '';
	if ( $loading === '' ) {
		$loading = __( 'Loading...', 'ecbb' );
	}

	$no_more = isset( $settings['load_more_no_more_text'] ) ? trim( (string) $settings['load_more_no_more_text'] ) : '';
	if ( $no_more === '' ) {
		$no_more = __( 'No more events', 'ecbb' );
	}

	$hide_ms = isset( $settings['load_more_done_hide_ms'] ) ? max( 300, (int) $settings['load_more_done_hide_ms'] ) : 1500;

	$settings_json = wp_json_encode( $settings );
	if ( ! is_string( $settings_json ) ) {
		$settings_json = '{}';
	}

	$html  = '<div class="ecbb-load-more">';
	$html .= '<button type="button" class="ecbb-load-more__btn"';
	$html .= ' data-settings="' . esc_attr( $settings_json ) . '"';
	$html .= ' data-limit="' . esc_attr( (string) $limit ) . '"';
	$html .= ' data-offset="' . esc_attr( (string) (int) $offset ) . '"';
	$html .= ' data-text="' . esc_attr( $text ) . '"';
	$html .= ' data-loading="' . esc_attr( $loading ) . '"';
	$html .= ' data-no-more="' . esc_attr( $no_more ) . '"';
	$html .= ' data-hide-ms="' . esc_attr( (string) $hide_ms ) . '"';
	$html .= '>' . esc_html( $text ) . '</button>';
	$html .= '<span class="ecbb-load-more__done" style="display:none" aria-live="polite"></span>';
	$html .= '</div>';

	return $html;
}

/**
 * Interactive parts with hover controls (title, chips, buttons). Excludes image.
 *
 * @return string[]
 */
function ecbb_event_part_interactive_hover_part_slugs() {
	return [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * Part types that show Style-tab hover controls (links, buttons, image swap).
 *
 * Non-interactive parts (description, date/time, venue text, cost, etc.) are excluded.
 *
 * @return string[]
 */
function ecbb_event_part_types_with_hover_style_controls() {
	return array_merge(
		ecbb_event_part_interactive_hover_part_slugs(),
		[ 'image' ]
	);
}

/**
 * @param string $part Part slug.
 * @return bool
 */
function ecbb_event_part_supports_hover_style_controls( $part ) {
	return in_array( (string) $part, ecbb_event_part_types_with_hover_style_controls(), true );
}

/**
 * Whether hover styling is enabled for this row (legacy rows without the key stay on).
 *
 * @param array $item Repeater row.
 * @return bool
 */
function ecbb_event_part_hover_style_active( array $item ) {
	if ( ! ecbb_event_part_supports_hover_style_controls( $item['part'] ?? '' ) ) {
		return false;
	}
	if ( ! array_key_exists( 'ecbb_use_hover', $item ) ) {
		return true;
	}
	$v = $item['ecbb_use_hover'];
	if ( is_bool( $v ) ) {
		return $v;
	}
	if ( $v === 'true' || $v === 1 || $v === '1' ) {
		return true;
	}
	if ( $v === 'false' || $v === 0 || $v === '0' || $v === '' ) {
		return false;
	}
	return (bool) $v;
}

/**
 * Whether the part uses two attachment sizes (crossfade on hover).
 *
 * @param array $item Repeater row.
 * @return bool
 */
function ecbb_loop_image_uses_dual_layer( array $item ) {
	if ( function_exists( 'ecbb_event_part_hover_style_active' ) && ! ecbb_event_part_hover_style_active( $item ) ) {
		return false;
	}
	$base = ecbb_sanitize_attachment_image_size( $item['image_size'] ?? '', 'large' );
	$raw  = isset( $item['image_size_hover'] ) ? trim( (string) $item['image_size_hover'] ) : '';
	if ( $raw === '' ) {
		return false;
	}
	$hover = ecbb_sanitize_attachment_image_size( $raw, $base );
	return $hover !== $base;
}

/**
 * Markup for featured image (single or dual size for hover).
 *
 * @param int    $thumb_id     Attachment ID.
 * @param array  $item         Repeater row.
 * @param string $shared_style CSS declarations for both images (no trailing object-position).
 * @return string HTML or empty.
 */
function ecbb_render_loop_featured_images( $thumb_id, array $item, $shared_style ) {
	$thumb_id = (int) $thumb_id;
	if ( ! $thumb_id ) {
		return '';
	}

	if ( function_exists( 'ecbb_event_part_hover_style_active' ) && ! ecbb_event_part_hover_style_active( $item ) ) {
		$item['image_size_hover']              = '';
		$item['ecbb_image_object_align_hover'] = '';
	}

	$size_base = ecbb_sanitize_attachment_image_size( $item['image_size'] ?? '', 'large' );
	$raw_hover = isset( $item['image_size_hover'] ) ? trim( (string) $item['image_size_hover'] ) : '';
	$size_hover = $raw_hover !== '' ? ecbb_sanitize_attachment_image_size( $raw_hover, $size_base ) : '';
	$dual       = ( $raw_hover !== '' && $size_hover !== $size_base );

	$op_base = ecbb_object_position_from_image_align( $item['ecbb_image_object_align'] ?? '' );
	$op_hov  = ecbb_object_position_from_image_align( $item['ecbb_image_object_align_hover'] ?? '' );
	if ( $op_hov === '' ) {
		$op_hov = $op_base;
	}

	$shared_style = is_string( $shared_style ) ? trim( $shared_style ) : '';
	if ( $shared_style !== '' && substr( $shared_style, -1 ) !== ';' ) {
		$shared_style .= ';';
	}

	$style_base = $shared_style . ( $op_base !== '' ? 'object-position:' . $op_base . ';' : '' );
	$style_hov  = $shared_style . ( $op_hov !== '' ? 'object-position:' . $op_hov . ';' : '' );

	if ( ! $dual ) {
		$html = wp_get_attachment_image(
			$thumb_id,
			$size_base,
			false,
			[
				'class' => 'ecbb-event__image',
				'style' => $style_base,
			]
		);
		return is_string( $html ) ? $html : '';
	}

	$img_base = wp_get_attachment_image(
		$thumb_id,
		$size_base,
		false,
		[
			'class' => 'ecbb-event__image ecbb-event__image--base',
			'style' => $style_base,
		]
	);
	$img_hover = wp_get_attachment_image(
		$thumb_id,
		$size_hover,
		false,
		[
			'class' => 'ecbb-event__image ecbb-event__image--hover',
			'style' => $style_hov,
		]
	);
	if ( ! is_string( $img_base ) || ! is_string( $img_hover ) || $img_base === '' || $img_hover === '' ) {
		return is_string( $img_base ) ? $img_base : '';
	}

	return '<span class="ecbb-event__img-stack">' . $img_base . $img_hover . '</span>';
}

/**
 * Append :hover to one or more comma-separated selectors.
 *
 * @param string $scope_sel
 * @return string
 */
function ecbb_event_part_hover_state_selectors( $scope_sel ) {
	$scope_sel = trim( (string) $scope_sel );
	if ( $scope_sel === '' ) {
		return '';
	}
	if ( strpos( $scope_sel, ',' ) === false ) {
		return $scope_sel . ':hover';
	}
	$parts = array_filter( array_map( 'trim', explode( ',', $scope_sel ) ) );
	if ( empty( $parts ) ) {
		return $scope_sel . ':hover';
	}
	return implode(
		',',
		array_map(
			static function ( $part ) {
				return $part . ':hover';
			},
			$parts
		)
	);
}

/**
 * Scoped CSS for hover motion that animates in place (rest = natural position).
 *
 * @param string $scope_sel Full selector (e.g. .scope .ecbb-p0).
 * @param string $anim      One of fade_in_up, fade_in_right, …
 * @return array{base:string, hover:string}
 */
function ecbb_event_part_hover_animation_css( $scope_sel, $anim ) {
	$anim = is_string( $anim ) ? $anim : '';
	$dur  = '0.38s';
	$ease = 'ease';
	$allowed = [ 'fade_in_up', 'fade_in_right', 'fade_in_down', 'fade_in_left', 'zoom_in', 'zoom_out' ];
	if ( ! in_array( $anim, $allowed, true ) ) {
		return [ 'base' => '', 'hover' => '' ];
	}

	$base  = "{$scope_sel}{transition:transform {$dur} {$ease};transform:none;transform-origin:center center;}";
	$hover = ecbb_event_part_hover_state_selectors( $scope_sel );

	switch ( $anim ) {
		case 'fade_in_up':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:translateY(-8px);}',
			];
		case 'fade_in_right':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:translateX(8px);}',
			];
		case 'fade_in_down':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:translateY(8px);}',
			];
		case 'fade_in_left':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:translateX(-8px);}',
			];
		case 'zoom_in':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:scale(1.06);}',
			];
		case 'zoom_out':
			return [
				'base'  => $base,
				'hover' => $hover . '{transform:scale(0.94);}',
			];
		default:
			return [ 'base' => '', 'hover' => '' ];
	}
}

/**
 * @param mixed $value Saved control value.
 * @return string style-1 or style-2
 */
function ecbb_sanitize_list_item_style( $value ) {
	$v = is_string( $value ) ? trim( $value ) : '';
	return in_array( $v, [ 'style-1', 'style-2' ], true ) ? $v : 'style-1';
}


/**
 * Repeater rows with a non-empty `part` slug.
 *
 * @param array $parts Raw Bricks repeater rows.
 * @return array<int,array>
 */
function ecbb_events_widget_parts_rows_clean( array $parts ) {
	$out = [];
	foreach ( $parts as $row ) {
		if ( is_array( $row ) && isset( $row['part'] ) && trim( (string) $row['part'] ) !== '' ) {
			$out[] = $row;
		}
	}
	return $out;
}

/**
 * Ordered `part` slugs from cleaned repeater rows (layout fingerprint).
 *
 * @param array $parts Raw repeater rows.
 * @return string[]
 */
function ecbb_events_widget_parts_slug_stack( array $parts ) {
	$clean = ecbb_events_widget_parts_rows_clean( $parts );
	$out   = [];
	foreach ( $clean as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$out[] = isset( $row['part'] ) ? (string) $row['part'] : '';
	}
	return $out;
}

/**
 * Whether raw repeater rows match a layout default stack (order-sensitive slug fingerprint).
 *
 * @param array $parts        Raw Bricks repeater rows.
 * @param array $default_rows Rows from an `ecbb_*_default_parts_rows()` helper.
 * @return bool
 */
function ecbb_events_widget_parts_stack_matches_defaults( array $parts, array $default_rows ) {
	return ecbb_events_widget_parts_slug_stack( $parts ) === ecbb_events_widget_parts_slug_stack( $default_rows );
}

/**
 * True when every row is empty / missing `part` (Bricks sometimes saves blank rows).
 *
 * @param array $parts Raw repeater rows.
 * @return bool
 */
function ecbb_events_widget_parts_array_is_effectively_empty( array $parts ) {
	return ecbb_events_widget_parts_rows_clean( $parts ) === [];
}

/**
 * Resolve Event parts repeater rows for the active layout + list style.
 *
 * Reads `parts_grid` | `parts_style1` | `parts_style2` first, then falls back to
 * legacy `parts` so older saved elements keep working.
 *
 * @param array  $settings    Element or AJAX settings.
 * @param string $template    `layout_template`: list|grid.
 * @param mixed  $item_chrome `list_item_style`: style-1|style-2 (ignored when grid).
 * @return array<int,array<string,mixed>>
 */
function ecbb_events_widget_resolve_event_parts_for_context( array $settings, $template, $item_chrome ) {
	$template = is_string( $template ) ? trim( $template ) : '';
	if ( $template === 'carousel' ) {
		$template = 'list';
	}
	if ( ! in_array( $template, [ 'list', 'grid' ], true ) ) {
		$template = 'list';
	}

	$item_chrome = function_exists( 'ecbb_sanitize_list_item_style' )
		? ecbb_sanitize_list_item_style( $item_chrome )
		: ( in_array( (string) $item_chrome, [ 'style-1', 'style-2' ], true ) ? (string) $item_chrome : 'style-1' );

	$non_empty = static function ( $key ) use ( $settings ) {
		$v = $settings[ $key ] ?? null;
		if ( ! is_array( $v ) || [] === $v ) {
			return null;
		}
		if ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' )
			&& ecbb_events_widget_parts_array_is_effectively_empty( $v ) ) {
			return null;
		}
		return $v;
	};

	if ( 'grid' === $template ) {
		$g = $non_empty( 'parts_grid' );
		if ( null !== $g ) {
			return $g;
		}
		$legacy = $non_empty( 'parts' );
		if ( null !== $legacy ) {
			return $legacy;
		}
		return [];
	}

	if ( 'style-2' === $item_chrome ) {
		$s2 = $non_empty( 'parts_style2' );
		if ( null !== $s2 ) {
			return $s2;
		}
		$legacy = $non_empty( 'parts' );
		if ( null !== $legacy ) {
			return $legacy;
		}
		return [];
	}

	$s1 = $non_empty( 'parts_style1' );
	if ( null !== $s1 ) {
		return $s1;
	}
	$legacy = $non_empty( 'parts' );
	if ( null !== $legacy ) {
		return $legacy;
	}
	return [];
}

/**
 * @param array  $parts Clean rows (see ecbb_events_widget_parts_rows_clean).
 * @param string $slug  Part slug.
 * @return bool
 */
function ecbb_events_widget_parts_has_part( array $parts, $slug ) {
	$slug = (string) $slug;
	foreach ( $parts as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		if ( isset( $row['part'] ) && (string) $row['part'] === $slug ) {
			return true;
		}
	}
	return false;
}

