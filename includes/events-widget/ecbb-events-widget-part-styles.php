<?php
/**
 * Event part styling helpers: responsive values, typography, spacing, legacy part normalization.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,string> breakpoint => media query (desktop is empty).
 */
function ecbb_events_widget_style_breakpoints() {
	return [
		'desktop' => '',
		'tablet'  => '@media (max-width:991px)',
		'mobile'  => '@media (max-width:767px)',
	];
}

/**
 * @param mixed  $value     Scalar or Bricks responsive array.
 * @param string $device    desktop|tablet|mobile.
 * @return mixed
 */
function ecbb_events_widget_responsive_pick( $value, $device = 'desktop' ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	if ( isset( $value['desktop'] ) || isset( $value['tablet'] ) || isset( $value['mobile'] ) ) {
		if ( isset( $value[ $device ] ) && $value[ $device ] !== '' && $value[ $device ] !== null ) {
			return $value[ $device ];
		}
		if ( $device === 'tablet' && isset( $value['desktop'] ) ) {
			return $value['desktop'];
		}
		if ( $device === 'mobile' ) {
			if ( isset( $value['tablet'] ) && $value['tablet'] !== '' ) {
				return $value['tablet'];
			}
			return $value['desktop'] ?? '';
		}
		return $value['desktop'] ?? '';
	}
	return $value;
}

/**
 * @param mixed  $value        CSS size value.
 * @param string $default_unit Unit for numeric values.
 * @return string
 */
function ecbb_events_widget_normalize_css_size( $value, $default_unit = 'px' ) {
	$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
	if ( $value === '' ) {
		return '';
	}
	if ( preg_match( '/^-?\d*\.?\d+(px|rem|em|%)$/', $value ) ) {
		return $value;
	}
	if ( preg_match( '/^-?\d*\.?\d+$/', $value ) ) {
		$unit = in_array( $default_unit, [ 'px', 'rem', 'em', '%' ], true ) ? $default_unit : 'px';
		return $value . $unit;
	}
	return '';
}

/**
 * @param mixed  $value        CSS size or unitless number.
 * @param string $default_unit Unit for numeric values when a unit is required.
 * @param bool   $unitless     Whether unitless numeric values are allowed.
 * @return string
 */
function ecbb_events_widget_sanitize_css_size_value( $value, $default_unit = 'px', $unitless = false ) {
	$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
	if ( '' === $value ) {
		return '';
	}
	if ( true === $unitless && preg_match( '/^-?\d*\.?\d+$/', $value ) ) {
		return $value;
	}
	return ecbb_events_widget_normalize_css_size( $value, $default_unit );
}

/**
 * @param mixed  $value        CSS shorthand containing one to four size values.
 * @param string $default_unit Unit for numeric values.
 * @return string
 */
function ecbb_events_widget_sanitize_css_size_shorthand( $value, $default_unit = 'px' ) {
	$value = is_string( $value ) || is_numeric( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value ) {
		return '';
	}

	$parts = preg_split( '/\s+/', $value );
	if ( ! is_array( $parts ) || count( $parts ) > 4 ) {
		return '';
	}

	$clean = [];
	foreach ( $parts as $part ) {
		$size = ecbb_events_widget_sanitize_css_size_value( $part, $default_unit, false );
		if ( '' === $size ) {
			return '';
		}
		$clean[] = $size;
	}

	return implode( ' ', $clean );
}

/**
 * @param mixed $value CSS font-family value.
 * @return string
 */
function ecbb_events_widget_sanitize_css_font_family( $value ) {
	$value = is_string( $value ) ? trim( wp_strip_all_tags( $value ) ) : '';
	if ( '' === $value || strlen( $value ) > 200 ) {
		return '';
	}

	$families = array_map( 'trim', explode( ',', $value ) );
	$clean    = [];
	foreach ( $families as $family ) {
		$family = trim( $family, " \t\n\r\0\x0B'\"" );
		if ( '' === $family || ! preg_match( '/^[a-zA-Z0-9 _-]+$/', $family ) ) {
			return '';
		}
		$clean[] = preg_match( '/\s/', $family ) ? '"' . $family . '"' : $family;
	}

	return implode( ', ', $clean );
}

/**
 * @param string $property CSS typography property.
 * @param mixed  $value    Saved control value.
 * @return string
 */
function ecbb_events_widget_sanitize_typography_value( $property, $value ) {
	$value = is_string( $value ) || is_numeric( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value ) {
		return '';
	}

	switch ( $property ) {
		case 'font-family':
			return ecbb_events_widget_sanitize_css_font_family( $value );
		case 'font-size':
		case 'letter-spacing':
			return ecbb_events_widget_sanitize_css_size_value( $value, 'px', false );
		case 'line-height':
			return ecbb_events_widget_sanitize_css_size_value( $value, 'px', true );
		case 'font-weight':
			if ( is_numeric( $value ) ) {
				$weight = (int) $value;
				return ( $weight >= 100 && $weight <= 900 ) ? (string) $weight : '';
			}
			return in_array( $value, [ 'normal', 'bold', 'bolder', 'lighter' ], true ) ? $value : '';
		case 'text-transform':
			return in_array( $value, [ 'none', 'capitalize', 'uppercase', 'lowercase' ], true ) ? $value : '';
		case 'text-align':
			return in_array( $value, [ 'left', 'center', 'right', 'justify' ], true ) ? $value : '';
		case 'text-decoration':
			return in_array( $value, [ 'none', 'underline', 'overline', 'line-through' ], true ) ? $value : '';
		default:
			return '';
	}
}

/**
 * @param string $border CSS border shorthand.
 * @return string
 */
function ecbb_events_widget_sanitize_border_shorthand( $border ) {
	$border = is_string( $border ) ? trim( $border ) : '';
	if ( '' === $border ) {
		return '';
	}

	$parts = preg_split( '/\s+/', $border );
	if ( ! is_array( $parts ) || count( $parts ) < 2 || count( $parts ) > 3 ) {
		return '';
	}

	$width = '';
	$style = '';
	$color = '';
	foreach ( $parts as $part ) {
		if ( '' === $width ) {
			$width = ecbb_events_widget_sanitize_css_size_value( $part, 'px', false );
			if ( '' !== $width ) {
				continue;
			}
		}
		if ( '' === $style && in_array( $part, [ 'solid', 'dashed', 'dotted', 'double', 'none', 'groove', 'ridge', 'inset', 'outset' ], true ) ) {
			$style = $part;
			continue;
		}
		if ( '' === $color ) {
			$color = function_exists( 'ecbb_normalize_bricks_color' ) ? ecbb_normalize_bricks_color( $part ) : '';
			if ( '' !== $color ) {
				continue;
			}
		}
		return '';
	}

	if ( '' === $width || '' === $style ) {
		return '';
	}

	return trim( $width . ' ' . $style . ' ' . $color );
}

/**
 * @param mixed $spacing Bricks spacing value.
 * @return string CSS shorthand or empty.
 */
function ecbb_events_widget_spacing_to_css( $spacing ) {
	if ( ! is_array( $spacing ) ) {
		return ecbb_events_widget_sanitize_css_size_shorthand( $spacing );
	}
	$top    = isset( $spacing['top'] ) ? ecbb_events_widget_sanitize_css_size_value( $spacing['top'] ) : '';
	$right  = isset( $spacing['right'] ) ? ecbb_events_widget_sanitize_css_size_value( $spacing['right'] ) : '';
	$bottom = isset( $spacing['bottom'] ) ? ecbb_events_widget_sanitize_css_size_value( $spacing['bottom'] ) : '';
	$left   = isset( $spacing['left'] ) ? ecbb_events_widget_sanitize_css_size_value( $spacing['left'] ) : '';
	if ( $top === '' && $right === '' && $bottom === '' && $left === '' ) {
		return '';
	}
	$top    = $top !== '' ? $top : '0';
	$right  = $right !== '' ? $right : $top;
	$bottom = $bottom !== '' ? $bottom : $top;
	$left   = $left !== '' ? $left : $right;
	return $top . ' ' . $right . ' ' . $bottom . ' ' . $left;
}

/**
 * @param mixed $radius Bricks border radius value.
 * @return string CSS border-radius or empty.
 */
function ecbb_events_widget_border_radius_to_css( $radius ) {
	if ( ! is_array( $radius ) ) {
		return ecbb_events_widget_sanitize_css_size_shorthand( $radius );
	}
	$box = [];
	foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
		if ( ! isset( $radius[ $side ] ) || $radius[ $side ] === '' || $radius[ $side ] === null ) {
			continue;
		}
		$box[ $side ] = ecbb_events_widget_normalize_css_size( $radius[ $side ], 'px' );
	}
	if ( empty( $box ) ) {
		return '';
	}
	return ecbb_events_widget_spacing_to_css( $box );
}

/**
 * @param mixed $border Bricks border control value.
 * @return string[] CSS declarations (border-*, border-radius).
 */
function ecbb_events_widget_border_declarations( $border ) {
	if ( ! is_array( $border ) ) {
		$border = ecbb_events_widget_sanitize_border_shorthand( $border );
		if ( '' !== $border ) {
			return [ 'border:' . $border ];
		}
		return [];
	}

	$style = isset( $border['style'] ) ? trim( (string) $border['style'] ) : 'solid';
	if ( ! in_array( $style, [ 'solid', 'dashed', 'dotted', 'double', 'none', 'groove', 'ridge', 'inset', 'outset' ], true ) ) {
		$style = 'solid';
	}

	$color = '';
	if ( isset( $border['color'] ) ) {
		$color = function_exists( 'ecbb_normalize_bricks_color' )
			? ecbb_normalize_bricks_color( $border['color'] )
			: ( is_string( $border['color'] ) ? trim( $border['color'] ) : '' );
	}
	if ( $color === '' ) {
		$color = 'currentColor';
	}

	$decl  = [];
	$width = $border['width'] ?? null;

	if ( is_array( $width ) ) {
		$sides  = [ 'top', 'right', 'bottom', 'left' ];
		$values = [];
		foreach ( $sides as $side ) {
			if ( ! array_key_exists( $side, $width ) ) {
				continue;
			}
			$values[ $side ] = ecbb_events_widget_normalize_css_size( $width[ $side ], 'px' );
		}
		if ( ! empty( $values ) ) {
			$nonzero = array_filter(
				$values,
				static function ( $w ) {
					return $w !== '' && $w !== '0' && $w !== '0px';
				}
			);
			if ( count( $nonzero ) === 4 && count( array_unique( $nonzero ) ) === 1 ) {
				$decl[] = 'border:' . reset( $nonzero ) . ' ' . $style . ' ' . $color;
			} else {
				foreach ( $sides as $side ) {
					if ( ! isset( $values[ $side ] ) || $values[ $side ] === '' || $values[ $side ] === '0' || $values[ $side ] === '0px' ) {
						continue;
					}
					$decl[] = 'border-' . $side . ':' . $values[ $side ] . ' ' . $style . ' ' . $color;
				}
			}
		}
	} elseif ( $width !== null && $width !== '' ) {
		$w = ecbb_events_widget_normalize_css_size( $width, 'px' );
		if ( $w !== '' && $w !== '0' && $w !== '0px' ) {
			$decl[] = 'border:' . $w . ' ' . $style . ' ' . $color;
		}
	}

	if ( ! empty( $border['radius'] ) ) {
		$radius_css = ecbb_events_widget_border_radius_to_css( $border['radius'] );
		if ( $radius_css !== '' ) {
			$decl[] = 'border-radius:' . $radius_css;
		}
	}

	return $decl;
}

/**
 * @param mixed $border Bricks border control value.
 * @return string border shorthand or empty.
 */
function ecbb_events_widget_border_to_css( $border ) {
	$decl = ecbb_events_widget_border_declarations( $border );
	foreach ( $decl as $d ) {
		if ( strpos( $d, 'border:' ) === 0 ) {
			return substr( $d, 7 );
		}
	}
	return '';
}

/**
 * Event parts whose box styles (background, padding) target each term chip, not the wrapper.
 *
 * @return string[]
 */
function ecbb_events_widget_part_chip_surface_part_slugs() {
	return [ 'categories' ];
}

/**
 * Selectors for per-term chip surfaces inside a part wrapper.
 *
 * @param string $scope_sel e.g. .ecbb-ev--abc .ecbb-p0
 * @return string Comma-separated selectors.
 */
function ecbb_events_widget_part_chip_surface_selectors( $scope_sel ) {
	return $scope_sel . ' .ecbb-event__term-chip,'
		. $scope_sel . ' > .ecbb-event__link,'
		. $scope_sel . ' > .ecbb-event__term';
}

/**
 * Relative selector for Bricks repeater typography / text-align (fieldId wrapper).
 *
 * @return string
 */
function ecbb_events_widget_repeater_typography_css_selector() {
	return '&, & .ecbb-event__term-chip, & .ecbb-event__link, & > .ecbb-event__link, & .ecbb-event__term, & > .ecbb-event__term';
}

function ecbb_events_widget_part_title_inner_selectors( $scope_sel ) {
	return $scope_sel . ' .ecbb-event__link,'
		. $scope_sel . ' > .ecbb-event__link,'
		. $scope_sel . ' .ecbb-event__title-text';
}

/**
 * Bricks repeater typography control CSS (live builder + frontend).
 *
 * Uses `typography` and explicit `color` so text color updates instantly like other
 * typography fields (the `font` shorthand omits color in repeater live preview).
 *
 * @return array<int,array<string,string>>
 */
function ecbb_events_widget_repeater_typography_control_css() {
	$selector = ecbb_events_widget_repeater_typography_css_selector();

	return [
		[
			'property' => 'typography',
			'selector' => $selector,
		],
		[
			'property' => 'color',
			'selector' => $selector,
		],
	];
}

/**
 * Selectors for typography on a scoped event part (frontend scoped CSS).
 *
 * @param string $scope_sel  e.g. .ecbb-ev--abc .ecbb-p0
 * @param string $part_type  Part slug.
 * @return string Comma-separated selectors.
 */
function ecbb_events_widget_part_typography_selectors( $scope_sel, $part_type ) {
	if ( function_exists( 'ecbb_events_widget_part_chip_surface_part_slugs' )
		&& in_array( $part_type, ecbb_events_widget_part_chip_surface_part_slugs(), true ) ) {
		return ecbb_events_widget_part_chip_surface_selectors( $scope_sel );
	}

	if ( function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' )
		&& in_array( $part_type, ecbb_events_widget_hover_child_link_part_slugs(), true ) ) {
		return $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' .ecbb-event__term,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__term';
	}

	return $scope_sel . ','
		. $scope_sel . ' .ecbb-event__link,'
		. $scope_sel . ' > .ecbb-event__link';
}

/**
 * Event parts that support the optional button-style row (read more, tickets, RSVP).
 *
 * @return string[]
 */
function ecbb_events_widget_button_part_slugs() {
	return [ 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * Relative selector for button-style controls (border, padding, etc.).
 *
 * @return string
 */
function ecbb_events_widget_repeater_button_inner_css_selector() {
	return '& .ecbb-event__link, & > a, & .ecbb-event__plain';
}

/**
 * Scoped selectors for button chrome on the inner link (not the row wrapper).
 *
 * @param string $scope_sel e.g. .ecbb-ev--abc .ecbb-p0
 * @return string
 */
function ecbb_events_widget_part_button_inner_selectors( $scope_sel ) {
	return $scope_sel . ' .ecbb-event__link,'
		. $scope_sel . ' > a,'
		. $scope_sel . ' .ecbb-event__plain';
}

/**
 * Event parts whose hover styles target inner links/terms (not the wrapper).
 *
 * @return string[]
 */
function ecbb_events_widget_hover_child_link_part_slugs() {
	return [ 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * Build :hover selectors for a scoped event part.
 *
 * @param string $scope_sel  e.g. .ecbb-ev--abc .ecbb-p0
 * @param string $part_type  Part slug.
 * @return string Comma-separated selectors.
 */
function ecbb_events_widget_hover_interaction_selectors( $scope_sel, $part_type ) {
	if ( function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' )
		&& in_array( $part_type, ecbb_events_widget_hover_child_link_part_slugs(), true ) ) {
		return $scope_sel . ' .ecbb-event__term-chip:hover,'
			. $scope_sel . ' .ecbb-event__link:hover,'
			. $scope_sel . ' .ecbb-event__term:hover';
	}

	return $scope_sel . ':hover,'
		. $scope_sel . ':hover *,'
		. $scope_sel . ' a:hover,'
		. $scope_sel . ' a:hover *,'
		. $scope_sel . ' .ecbb-event__term:hover';
}

/**
 * Selector used for hover motion (animation base state).
 *
 * @param string $scope_sel
 * @param string $part_type
 * @return string
 */
function ecbb_events_widget_hover_animation_scope( $scope_sel, $part_type ) {
	if ( function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' )
		&& in_array( $part_type, ecbb_events_widget_hover_child_link_part_slugs(), true ) ) {
		return $scope_sel . ' .ecbb-event__term-chip,'
			. $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' .ecbb-event__term';
	}

	return $scope_sel;
}

/**
 * Internal date-preset keys mapped to PHP date() format strings.
 *
 * @return array<string,string>
 */
function ecbb_events_widget_date_preset_formats() {
	return [
		'MD,Y'  => 'M j, Y',
		'FD,Y'  => 'F j, Y',
		'DM'    => 'd/m/Y',
		'DML'   => 'j F l',
		'DF'    => 'j F',
		'MD'    => 'M j',
		'FD'    => 'F j',
		'MD,YT' => 'M j, Y',
		'jMl'   => 'j M l',
		'd.FY'  => 'd.m.Y',
		'd.F'   => 'd.m',
		'ldF'   => 'l j F',
		'Mdl'   => 'M j l',
		'd.Ml'  => 'd.m l',
		'dFT'   => 'j F',
		'D.j.F' => 'D, j. F',
	];
}

/**
 * Resolve a saved preset key to a PHP date format string.
 *
 * @param string $preset Preset key.
 * @param string $part   event_date|event_time.
 * @return string|null    PHP format, or null when not mapped (e.g. custom).
 */
function ecbb_events_widget_date_preset_php_format( $preset, $part = 'event_date' ) {
	$preset = (string) $preset;

	if ( $preset === '' || $preset === 'default' || $preset === 'full' ) {
		return $part === 'event_time'
			? (string) get_option( 'time_format' )
			: (string) get_option( 'date_format' );
	}

	if ( $preset === 'custom' ) {
		return null;
	}

	if ( in_array( $preset, [ 'sed', 'sedt', 'MD,YT', 'dFT' ], true ) ) {
		return $part === 'event_time'
			? (string) get_option( 'time_format' )
			: (string) get_option( 'date_format' );
	}

	$formats = ecbb_events_widget_date_preset_formats();

	return isset( $formats[ $preset ] ) ? $formats[ $preset ] : null;
}

/**
 * Date format presets shared by list query + repeater date parts.
 *
 * @return array<string,string>
 */
function ecbb_events_widget_date_format_preset_options() {
	return [
		''        => esc_html__( 'Default', 'ecbb' ),
		'default' => esc_html__( 'Default (01 January 2025)', 'ecbb' ),
		'MD,Y'    => 'Md,Y (Jan 01, 2025)',
		'FD,Y'    => 'Fd,Y (January 01, 2025)',
		'DM'      => 'dM (01 Jan)',
		'DML'     => 'dML (01 Jan Monday)',
		'DF'      => 'dF (01 January)',
		'MD'      => 'Md (Jan 01)',
		'FD'      => 'Fd (January 01)',
		'MD,YT'   => 'Md,YT (Jan 01, 2025 8:00am-5:00pm)',
		'full'    => 'Full (01 January 2025 8:00am-5:00pm)',
		'jMl'     => 'jMl (1 Jan Monday)',
		'd.FY'    => 'd.FY (01. January 2025)',
		'd.F'     => 'd.F (01. January)',
		'ldF'     => 'ldF (Monday 01 January)',
		'Mdl'     => 'Mdl (Jan 01 Monday)',
		'd.Ml'    => 'd.Ml (01. Jan Monday)',
		'dFT'     => 'dFT (01 January 8:00am-5:00pm)',
		'sed'     => 'SED (01 Jan - 02 Jan 2025)',
		'sedt'    => 'SEDT (01 Jan - 02 Jan 2025 8:00am-5:00pm)',
		'D.j.F'   => 'D.,j. F (Wed., 15. May)',
		'custom'  => esc_html__( 'Custom…', 'ecbb' ),
	];
}

/**
 * Consolidated part dropdown options (saved `part` key).
 *
 * @return array<string,string>
 */
function ecbb_events_widget_part_select_options() {
	return [
		'title'       => esc_html__( 'Title', 'ecbb' ),
		'description' => esc_html__( 'Description', 'ecbb' ),
		'date'        => esc_html__( 'Date & time', 'ecbb' ),
		'venue'       => esc_html__( 'Venue', 'ecbb' ),
		'organizer'   => esc_html__( 'Organizer', 'ecbb' ),
		'event_link'  => esc_html__( 'Event link', 'ecbb' ),
		'event_cost'  => esc_html__( 'Cost', 'ecbb' ),
		'event_tickets' => esc_html__( 'Tickets', 'ecbb' ),
		'event_rsvp'  => esc_html__( 'RSVP', 'ecbb' ),
		'read_more'   => esc_html__( 'Read more', 'ecbb' ),
		'categories'  => esc_html__( 'Categories', 'ecbb' ),
		'tags'        => esc_html__( 'Tags', 'ecbb' ),
		'image'       => esc_html__( 'Featured image', 'ecbb' ),
	];
}

/**
 * Map consolidated UI part + display dropdown to legacy render slug (backward compatible).
 *
 * @param array<string,mixed> $item Repeater row.
 * @return array<string,mixed>
 */
function ecbb_events_widget_normalize_part_item( array $item ) {
	$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';

	$legacy_venue = [
		'venue_full_address', 'venue_street', 'venue_city', 'venue_state',
		'venue_zip', 'venue_country', 'venue_phone', 'venue_website', 'event_map_link',
	];
	$legacy_organizer = [ 'organizer_email', 'organizer_phone', 'organizer_website' ];
	$legacy_date      = [ 'event_date', 'event_time', 'event_day' ];
	$legacy_links     = [ 'event_website', 'event_phone', 'event_map_link' ];

	if ( in_array( $part, array_merge( $legacy_venue, $legacy_organizer, $legacy_date, $legacy_links ), true ) ) {
		return $item;
	}

	if ( $part === 'venue' ) {
		$fmt = isset( $item['venue_display'] ) ? (string) $item['venue_display'] : 'full_details';
		if ( $fmt === 'name_and_address' ) {
			$fmt = 'full_details';
		}
		$map = [
			'full_details'   => 'venue',
			'name_and_state' => 'venue',
			'name'           => 'venue',
			'full_address'   => 'venue_full_address',
			'street'         => 'venue_street',
			'city'           => 'venue_city',
			'state'          => 'venue_state',
			'zip'            => 'venue_zip',
			'country'        => 'venue_country',
			'phone'          => 'venue_phone',
			'website'        => 'venue_website',
			'map_link'       => 'event_map_link',
		];
		if ( isset( $map[ $fmt ] ) ) {
			$item['part'] = $map[ $fmt ];
		}
	}

	if ( $part === 'date' ) {
		$fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
		$map = [
			'day_time_range' => 'date',
			'date'             => 'event_date',
			'time'             => 'event_time',
			'day'              => 'event_day',
		];
		if ( isset( $map[ $fmt ] ) ) {
			$item['part'] = $map[ $fmt ];
		}
	}

	if ( $part === 'organizer' ) {
		$fmt = isset( $item['organizer_display'] ) ? (string) $item['organizer_display'] : 'full_details';
		$map = [
			'full_details' => 'organizer',
			'name'         => 'organizer',
			'email'        => 'organizer_email',
			'phone'        => 'organizer_phone',
			'website'      => 'organizer_website',
		];
		if ( isset( $map[ $fmt ] ) ) {
			$item['part'] = $map[ $fmt ];
		}
	}

	if ( $part === 'event_link' ) {
		$fmt = isset( $item['event_link_display'] ) ? (string) $item['event_link_display'] : 'website';
		$map = [
			'website'  => 'event_website',
			'phone'    => 'event_phone',
			'map_link' => 'event_map_link',
		];
		if ( isset( $map[ $fmt ] ) ) {
			$item['part'] = $map[ $fmt ];
		}
	}

	return $item;
}

/**
 * @param array<string,mixed> $item
 * @return string
 */
function ecbb_events_widget_typography_color_from_item( array $item ) {
	if ( ! empty( $item['ecbb_typography'] ) && is_array( $item['ecbb_typography'] ) && ! empty( $item['ecbb_typography']['color'] ) ) {
		return function_exists( 'ecbb_normalize_bricks_color' )
			? ecbb_normalize_bricks_color( $item['ecbb_typography']['color'] )
			: '';
	}
	return '';
}

/**
 * Read a responsive number saved in Bricks flat key format (key, key:tablet_portrait, …).
 *
 * @param array<string,mixed> $settings Element settings.
 * @param string              $key      Control id (e.g. grid_cols).
 * @param array{desktop:int,tablet:int,mobile:int} $defaults
 * @return array{desktop:int,tablet:int,mobile:int}
 */
function ecbb_events_widget_read_responsive_number( array $settings, $key, array $defaults ) {
	$nested = $settings[ $key ] ?? null;
	if ( is_array( $nested ) && ( isset( $nested['desktop'] ) || isset( $nested['tablet'] ) || isset( $nested['mobile'] ) ) ) {
		return [
			'desktop' => max( 1, (int) ( ecbb_events_widget_responsive_pick( $nested, 'desktop' ) ?: $defaults['desktop'] ) ),
			'tablet'  => max( 1, (int) ( ecbb_events_widget_responsive_pick( $nested, 'tablet' ) ?: $defaults['tablet'] ) ),
			'mobile'  => max( 1, (int) ( ecbb_events_widget_responsive_pick( $nested, 'mobile' ) ?: $defaults['mobile'] ) ),
		];
	}

	$suffixes = [
		'desktop' => [ $key ],
		'tablet'  => [ "{$key}:tablet_portrait", "{$key}:tablet" ],
		'mobile'  => [ "{$key}:mobile_portrait", "{$key}:mobile_landscape", "{$key}:mobile" ],
	];

	$resolved = [];
	foreach ( $suffixes as $device => $candidates ) {
		$resolved[ $device ] = null;
		foreach ( $candidates as $candidate ) {
			if ( array_key_exists( $candidate, $settings ) && $settings[ $candidate ] !== '' && $settings[ $candidate ] !== null ) {
				$resolved[ $device ] = max( 1, (int) $settings[ $candidate ] );
				break;
			}
		}
	}

	if ( $resolved['desktop'] === null && is_scalar( $nested ) && $nested !== '' ) {
		$resolved['desktop'] = max( 1, (int) $nested );
	}

	if ( $resolved['desktop'] === null ) {
		return $defaults;
	}

	if ( $resolved['tablet'] === null ) {
		$resolved['tablet'] = $resolved['desktop'];
	}
	if ( $resolved['mobile'] === null ) {
		$resolved['mobile'] = $resolved['tablet'];
	}

	return [
		'desktop' => $resolved['desktop'],
		'tablet'  => $resolved['tablet'],
		'mobile'  => $resolved['mobile'],
	];
}

/**
 * Resolve grid column counts per breakpoint (supports legacy grid_cols_* keys).
 *
 * @param array<string,mixed> $settings Element settings.
 * @return array{desktop:int,tablet:int,mobile:int}
 */
function ecbb_events_widget_resolve_grid_cols_vars( array $settings ) {
	$defaults = [
		'desktop' => 3,
		'tablet'  => 2,
		'mobile'  => 1,
	];

	$has_new = array_key_exists( 'grid_cols', $settings ) && $settings['grid_cols'] !== '' && $settings['grid_cols'] !== null;
	if ( ! $has_new ) {
		foreach ( array_keys( $settings ) as $setting_key ) {
			if ( strpos( (string) $setting_key, 'grid_cols:' ) === 0 ) {
				$has_new = true;
				break;
			}
		}
	}

	if ( $has_new ) {
		return ecbb_events_widget_read_responsive_number( $settings, 'grid_cols', $defaults );
	}

	if ( isset( $settings['grid_cols_desktop'] ) || isset( $settings['grid_cols_tablet'] ) || isset( $settings['grid_cols_mobile'] ) ) {
		return [
			'desktop' => max( 1, (int) ( $settings['grid_cols_desktop'] ?? $defaults['desktop'] ) ),
			'tablet'  => max( 1, (int) ( $settings['grid_cols_tablet'] ?? $defaults['tablet'] ) ),
			'mobile'  => max( 1, (int) ( $settings['grid_cols_mobile'] ?? $defaults['mobile'] ) ),
		];
	}

	return $defaults;
}

/**
 * Responsive grid column CSS variable for list/grid root.
 *
 * @param array<string,mixed> $settings
 * @param string              $root_selector Scoped element selector (e.g. .ecbb-ev--abc).
 * @return string
 */
function ecbb_events_widget_build_grid_cols_responsive_css( array $settings, $root_selector ) {
	$cols  = ecbb_events_widget_resolve_grid_cols_vars( $settings );
	$rules = [];

	foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
		$val  = $cols[ $device ] ?? 3;
		$rule = $root_selector . ' .ecbb-ev__list--grid{--ecbb-grid-cols:' . (int) $val . ';}';
		$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
	}

	return implode( "\n", $rules );
}

/**
 * Read responsive spacing from a repeater row (Bricks keys: field, field:tablet_portrait, …).
 *
 * @param array<string,mixed> $item   Repeater row.
 * @param string              $key    Field id.
 * @param string              $device desktop|tablet|mobile.
 * @return array<string,mixed>|null
 */
function ecbb_events_widget_read_responsive_spacing( array $item, $key, $device = 'desktop' ) {
	$nested = $item[ $key ] ?? null;
	if ( is_array( $nested ) && ( isset( $nested['desktop'] ) || isset( $nested['tablet'] ) || isset( $nested['mobile'] ) ) ) {
		$picked = ecbb_events_widget_responsive_pick( $nested, $device );
		return is_array( $picked ) ? $picked : null;
	}

	$suffixes = [
		'desktop' => [ $key ],
		'tablet'  => [ "{$key}:tablet_portrait", "{$key}:tablet" ],
		'mobile'  => [ "{$key}:mobile_portrait", "{$key}:mobile_landscape", "{$key}:mobile" ],
	];

	$is_spacing = static function ( $value ) {
		return is_array( $value ) && ( isset( $value['top'] ) || isset( $value['right'] ) || isset( $value['bottom'] ) || isset( $value['left'] ) );
	};

	$devices = $device === 'mobile'
		? [ 'mobile', 'tablet', 'desktop' ]
		: ( $device === 'tablet' ? [ 'tablet', 'desktop' ] : [ 'desktop' ] );

	foreach ( $devices as $dev ) {
		foreach ( $suffixes[ $dev ] as $candidate ) {
			if ( isset( $item[ $candidate ] ) && $is_spacing( $item[ $candidate ] ) ) {
				return $item[ $candidate ];
			}
		}
		if ( $dev === 'desktop' && $is_spacing( $nested ) ) {
			return $nested;
		}
	}

	return null;
}

/**
 * Button style declarations for a repeater row at a given breakpoint.
 *
 * @param array<string,mixed> $item              Repeater row.
 * @param string              $device            desktop|tablet|mobile.
 * @param callable            $color_fn          function( $value ): string
 * @return string[]
 */
function ecbb_events_widget_button_declarations( array $item, $device, callable $color_fn ) {
	if ( empty( $item['btn_style'] ) ) {
		return [];
	}
	if ( function_exists( 'ecbb_event_part_button_style_active' ) && ! ecbb_event_part_button_style_active( $item ) ) {
		return [];
	}

	$styles = [];

	$bg_raw = ecbb_events_widget_responsive_pick( $item['btn_bg'] ?? '', $device );
	if ( $bg_raw === '' || $bg_raw === null ) {
		$bg_raw = ecbb_events_widget_responsive_pick( $item['ecbb_background'] ?? '', $device );
	}
	if ( $bg_raw !== '' && $bg_raw !== null ) {
		$bg = $color_fn( $bg_raw );
		if ( $bg !== '' ) {
			$styles[] = 'background-color:' . $bg . ' !important';
		}
	}

	$tc_raw = ecbb_events_widget_responsive_pick( $item['btn_text_color'] ?? '', $device );
	if ( ( $tc_raw === '' || $tc_raw === null ) && ! empty( $item['ecbb_typography'] ) && is_array( $item['ecbb_typography'] ) ) {
		$tc_raw = ecbb_events_widget_responsive_pick( $item['ecbb_typography']['color'] ?? '', $device );
	}
	if ( $tc_raw !== '' && $tc_raw !== null ) {
		$tc = $color_fn( $tc_raw );
		if ( $tc !== '' ) {
			$styles[] = 'color:' . $tc . ' !important';
		}
	}

	$border_color_raw = ecbb_events_widget_responsive_pick( $item['btn_border_color'] ?? '', $device );
	if ( ( $border_color_raw === '' || $border_color_raw === null ) && ! empty( $item['btn_border'] ) ) {
		$border_legacy = ecbb_events_widget_responsive_pick( $item['btn_border'], $device );
		if ( is_array( $border_legacy ) && ! empty( $border_legacy['color'] ) ) {
			$border_color_raw = $border_legacy['color'];
		}
	}
	if ( $border_color_raw !== '' && $border_color_raw !== null ) {
		$border_color = $color_fn( $border_color_raw );
		if ( $border_color !== '' ) {
			$styles[] = 'border:1px solid ' . $border_color;
			$styles[] = 'box-sizing:border-box';
		}
	}

	$padding_raw = ecbb_events_widget_read_responsive_spacing( $item, 'btn_padding', $device );
	$padding_css = '';
	if ( ! empty( $padding_raw ) && function_exists( 'ecbb_events_widget_spacing_to_css' ) ) {
		$padding_css = ecbb_events_widget_spacing_to_css( $padding_raw );
	}
	if ( $padding_css === '' ) {
		$py = isset( $item['btn_padding_y'] ) ? trim( (string) $item['btn_padding_y'] ) : '';
		$px = isset( $item['btn_padding_x'] ) ? trim( (string) $item['btn_padding_x'] ) : '';
		if ( $py !== '' || $px !== '' ) {
			$padding_css = ( $py !== '' ? $py : '10px' ) . ' ' . ( $px !== '' ? $px : '14px' );
		}
	}
	if ( $padding_css !== '' ) {
		$styles[] = 'padding:' . $padding_css;
	}

	return $styles;
}

/**
 * Build inline style attr for a part row (image sizing only; typography uses Bricks repeater CSS).
 *
 * @param array<string,mixed> $item
 * @param bool                $allow_radius Image part.
 * @return string
 */
function ecbb_events_widget_build_inline_style_attr( array $item, $allow_radius = false ) {
	if ( ! $allow_radius ) {
		return '';
	}

	$styles = [];

	if ( ! empty( $item['image_aspect_ratio'] ) && is_string( $item['image_aspect_ratio'] ) && preg_match( '/^\d+\/\d+$/', $item['image_aspect_ratio'] ) ) {
			$styles[] = 'aspect-ratio:' . $item['image_aspect_ratio'];
		}

		$border_css = '';
		if ( ! empty( $item['ecbb_image_border'] ) ) {
			$border_css = ecbb_events_widget_border_to_css( $item['ecbb_image_border'] );
		}
		if ( $border_css === '' ) {
			$bw = ecbb_events_widget_normalize_css_size( $item['ecbb_image_border_width'] ?? '', 'px' );
			$bc = function_exists( 'ecbb_normalize_bricks_color' ) ? ecbb_normalize_bricks_color( $item['ecbb_image_border_color'] ?? '' ) : '';
			$bs = isset( $item['ecbb_image_border_style'] ) ? (string) $item['ecbb_image_border_style'] : 'solid';
			$bs = in_array( $bs, [ 'solid', 'dashed', 'dotted' ], true ) ? $bs : 'solid';
			if ( $bw !== '' && $bw !== '0px' && $bc !== '' ) {
				$border_css = $bw . ' ' . $bs . ' ' . $bc;
			}
		}
		if ( $border_css !== '' ) {
			$styles[] = 'border:' . $border_css;
		}

		$radius = '';
		if ( ! empty( $item['ecbb_image_radius'] ) && is_array( $item['ecbb_image_radius'] ) ) {
			$radius = ecbb_events_widget_spacing_to_css( $item['ecbb_image_radius'] );
		}
		if ( $radius === '' ) {
			$radius = ecbb_events_widget_normalize_css_size( $item['ecbb_image_radius'] ?? '', 'px' );
		}
		if ( $radius !== '' ) {
			$styles[] = 'border-radius:' . $radius;
		}

		$w = ecbb_events_widget_normalize_css_size(
			ecbb_events_widget_responsive_pick( $item['ecbb_image_width'] ?? '', 'desktop' ),
			'%'
		);
		if ( $w !== '' ) {
			$styles[] = 'width:' . $w;
		}

		$h = ecbb_events_widget_normalize_css_size(
			ecbb_events_widget_responsive_pick( $item['ecbb_image_height'] ?? '', 'desktop' ),
			'px'
		);
		if ( $h !== '' ) {
			$styles[] = 'height:' . $h;
		}

		$fit = ecbb_events_widget_responsive_pick( $item['ecbb_image_fit'] ?? '', 'desktop' );
		$fit = is_string( $fit ) ? $fit : '';
		if ( $fit !== '' && in_array( $fit, [ 'cover', 'contain', 'fill', 'none', 'scale-down' ], true ) ) {
			$styles[] = 'object-fit:' . $fit;
		}

	return empty( $styles ) ? '' : implode( ';', $styles ) . ';';
}

/**
 * @param array<string,mixed> $typo
 * @param string              $device
 * @return string[]
 */
function ecbb_events_widget_typography_declarations( array $typo, $device = 'desktop' ) {
	$decl = [];

	$props = [
		'font-family'     => 'font-family',
		'font-size'       => 'font-size',
		'font-weight'     => 'font-weight',
		'line-height'     => 'line-height',
		'letter-spacing'  => 'letter-spacing',
		'text-transform'  => 'text-transform',
		'text-align'      => 'text-align',
		'text-decoration' => 'text-decoration',
	];

	foreach ( $props as $key => $css_prop ) {
		if ( empty( $typo[ $key ] ) ) {
			continue;
		}
		$val = ecbb_events_widget_responsive_pick( $typo[ $key ], $device );
		if ( is_string( $val ) && trim( $val ) !== '' ) {
			$val = ecbb_events_widget_sanitize_typography_value( $css_prop, $val );
			if ( $val !== '' ) {
				$decl[] = $css_prop . ':' . $val;
			}
		} elseif ( is_numeric( $val ) && $css_prop === 'font-weight' ) {
			$decl[] = $css_prop . ':' . (int) $val;
		}
	}

	if ( ! empty( $typo['color'] ) ) {
		$color_raw = ecbb_events_widget_responsive_pick( $typo['color'], $device );
		$color     = function_exists( 'ecbb_normalize_bricks_color' )
			? ecbb_normalize_bricks_color( $color_raw )
			: '';
		if ( $color !== '' ) {
			$decl[] = 'color:' . $color;
		}
	}

	return $decl;
}

/**
 * Scoped CSS for all event parts in a loop instance.
 *
 * @param array<int,array<string,mixed>> $parts
 * @param string                         $scope_class     e.g. ecbb-ev--abc
 * @param callable                       $color_fn        function( $value ): string
 * @param string                         $list_item_style style-1|style-2|grid
 * @return array{0:string[],1:string[]}  [base rules, hover rules]
 */
function ecbb_events_widget_build_parts_scoped_css( array $parts, $scope_class, callable $color_fn, $list_item_style = 'style-1' ) {
	$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
	$style_css   = [];
	$hover_css   = [];
	if ( $scope_class === '' ) {
		return [ $style_css, $hover_css ];
	}

	$idx = 0;

	foreach ( $parts as $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$p = ecbb_events_widget_normalize_part_item( $p );

		$idx_class = '.' . ( function_exists( 'ecbb_events_widget_part_idx_class' )
			? ecbb_events_widget_part_idx_class( absint( $idx ) )
			: 'ecbb-p' . absint( $idx ) );
		$scope_sel = '.' . $scope_class . ' ' . $idx_class;
		$part_type = isset( $p['part'] ) ? (string) $p['part'] : '';
		$hover_style_on = function_exists( 'ecbb_event_part_hover_style_active' ) && ecbb_event_part_hover_style_active( $p );
		$btn_style_on   = ! empty( $p['btn_style'] )
			&& $hover_style_on
			&& function_exists( 'ecbb_events_widget_button_part_slugs' )
			&& in_array( $part_type, ecbb_events_widget_button_part_slugs(), true );
		$chip_surface = function_exists( 'ecbb_events_widget_part_chip_surface_part_slugs' )
			&& in_array( $part_type, ecbb_events_widget_part_chip_surface_part_slugs(), true );
		$chip_sel = $chip_surface && function_exists( 'ecbb_events_widget_part_chip_surface_selectors' )
			? ecbb_events_widget_part_chip_surface_selectors( $scope_sel )
			: $scope_sel;

		foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$padding_raw = ecbb_events_widget_read_responsive_spacing( $p, 'ecbb_padding', $device );
				$padding     = ! empty( $padding_raw ) && function_exists( 'ecbb_events_widget_spacing_to_css' )
					? ecbb_events_widget_spacing_to_css( $padding_raw )
					: '';
				if ( $padding !== '' ) {
					$surface_rule = $chip_sel . '{padding:' . $padding . ';}';
					$style_css[]  = ( $mq !== '' ? $mq . '{' . $surface_rule . '}' : $surface_rule );
				}
			}

			if ( $btn_style_on ) {
				$btn_decls = ecbb_events_widget_button_declarations( $p, $device, $color_fn );
				if ( ! empty( $btn_decls ) ) {
					$btn_sel = function_exists( 'ecbb_events_widget_part_button_inner_selectors' )
						? ecbb_events_widget_part_button_inner_selectors( $scope_sel )
						: $scope_sel . ' .ecbb-event__link,' . $scope_sel . ' > a,' . $scope_sel . ' .ecbb-event__plain';
					$btn_rule = $btn_sel . '{'
						. implode( ';', $btn_decls )
						. ';display:inline-flex;align-items:center;justify-content:center;text-decoration:none}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $btn_rule . '}' : $btn_rule );
				}
			}
		}

		if ( $hover_style_on ) {
			$hover_sel = ecbb_events_widget_hover_interaction_selectors( $scope_sel, $part_type );

			$hover = $color_fn( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ) );
			if ( $hover !== '' ) {
				$hover_css[] = $hover_sel . '{color:' . $hover . ' !important;}';
			}

			$hover_bg = $color_fn( $p['ecbb_hover_background'] ?? '' );
			if ( $hover_bg !== '' ) {
				if ( function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' )
					&& in_array( $part_type, ecbb_events_widget_hover_child_link_part_slugs(), true ) ) {
					$hover_css[] = $scope_sel . ' .ecbb-event__term-chip:hover,'
						. $scope_sel . ' .ecbb-event__link:hover,'
						. $scope_sel . ' .ecbb-event__term:hover{background-color:' . $hover_bg . ' !important;}';
				} elseif ( $part_type === 'title' ) {
					$hover_css[] = $scope_sel . ' .ecbb-event__link:hover,'
						. $scope_sel . ' .ecbb-event__title-text:hover{background-color:' . $hover_bg . ' !important;}';
				} else {
					$hover_css[] = $scope_sel . ':hover{background-color:' . $hover_bg . ' !important;}';
				}
			}

			$hover_td = isset( $p['ecbb_hover_text_decoration'] ) ? (string) $p['ecbb_hover_text_decoration'] : '';
			if ( $hover_td !== '' && in_array( $hover_td, [ 'none', 'underline', 'overline', 'line-through' ], true ) ) {
				if ( function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' )
					&& in_array( $part_type, ecbb_events_widget_hover_child_link_part_slugs(), true ) ) {
					$hover_css[] = $scope_sel . ' .ecbb-event__term-chip:hover,'
						. $scope_sel . ' .ecbb-event__link:hover,'
						. $scope_sel . ' .ecbb-event__term:hover{text-decoration:' . $hover_td . ' !important;}';
				} else {
					$hover_css[] = $scope_sel . ':hover,'
						. $scope_sel . ' a:hover,'
						. $scope_sel . ' .ecbb-event__term:hover{text-decoration:' . $hover_td . ' !important;}';
				}
			}

			$hover_anim = isset( $p['ecbb_hover_animation'] ) ? (string) $p['ecbb_hover_animation'] : '';
			if ( $hover_anim !== '' && function_exists( 'ecbb_event_part_hover_animation_css' ) ) {
				$anim_scope  = ecbb_events_widget_hover_animation_scope( $scope_sel, $part_type );
				$anim_blocks = ecbb_event_part_hover_animation_css( $anim_scope, $hover_anim );
				if ( ! empty( $anim_blocks['base'] ) ) {
					$style_css[] = $anim_blocks['base'];
				}
				if ( ! empty( $anim_blocks['hover'] ) ) {
					$hover_css[] = $anim_blocks['hover'];
				}
			}
		}

		$bg_in = '';
		if ( $part_type === 'title' ) {
			foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
				$bg_raw = ecbb_events_widget_responsive_pick( $p['ecbb_background_inner'] ?? '', $device );
				$bg_in  = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg_in !== '' ) {
					$inner_sel  = function_exists( 'ecbb_events_widget_part_title_inner_selectors' )
						? ecbb_events_widget_part_title_inner_selectors( $scope_sel )
						: $scope_sel . ' .ecbb-event__link,' . $scope_sel . ' .ecbb-event__title-text';
					$inner_rule = $inner_sel . '{display:inline-block;width:fit-content;max-width:100%;background-color:' . $bg_in . ' !important;}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $inner_rule . '}' : $inner_rule );
				}
			}
		}

		foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$bg_raw = ecbb_events_widget_responsive_pick( $p['ecbb_background'] ?? '', $device );
				$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg !== '' ) {
					$chip_rule   = $chip_sel . '{background-color:' . $bg . ' !important;}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $chip_rule . '}' : $chip_rule );
				}
			} elseif ( $btn_style_on ) {
				$btn_bg_raw = ecbb_events_widget_responsive_pick( $p['btn_bg'] ?? '', $device );
				$bg_raw     = ecbb_events_widget_responsive_pick( $p['ecbb_background'] ?? '', $device );
				if ( ( $btn_bg_raw === '' || $btn_bg_raw === null ) && $bg_raw !== '' && $bg_raw !== null ) {
					$bg = $color_fn( $bg_raw );
					if ( $bg !== '' ) {
						$btn_inner   = ecbb_events_widget_part_button_inner_selectors( $scope_sel );
						$btn_bg_rule = $btn_inner . '{background-color:' . $bg . ' !important;}';
						$style_css[] = ( $mq !== '' ? $mq . '{' . $btn_bg_rule . '}' : $btn_bg_rule );
					}
				}
			} else {
				$bg_raw = ecbb_events_widget_responsive_pick( $p['ecbb_background'] ?? '', $device );
				$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg !== '' ) {
					$bg_rule     = $scope_sel . '{background-color:' . $bg . ' !important;}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $bg_rule . '}' : $bg_rule );
				}
			}
		}

		if ( $part_type === 'image' && $hover_style_on && function_exists( 'ecbb_loop_image_uses_dual_layer' ) && function_exists( 'ecbb_object_position_from_image_align' ) ) {
			if ( ! ecbb_loop_image_uses_dual_layer( $p ) ) {
				foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
					$align_b = ecbb_events_widget_responsive_pick( $p['ecbb_image_object_align'] ?? '', $device );
					$op_b    = ecbb_object_position_from_image_align( $align_b );
					$op_h    = ecbb_object_position_from_image_align( $p['ecbb_image_object_align_hover'] ?? '' );
					if ( $op_h !== '' && $op_h !== $op_b ) {
						$img_sel = $scope_sel . ' .ecbb-event__image';
						if ( $op_b !== '' ) {
							$base_rule   = $img_sel . '{object-position:' . $op_b . ';transition:object-position 0.35s ease;}';
							$style_css[] = ( $mq !== '' ? $mq . '{' . $base_rule . '}' : $base_rule );
						} else {
							$base_rule   = $img_sel . '{transition:object-position 0.35s ease;}';
							$style_css[] = ( $mq !== '' ? $mq . '{' . $base_rule . '}' : $base_rule );
						}
						$hover_css[] = $scope_sel . ':hover .ecbb-event__image{object-position:' . $op_h . ' !important;}';
					} elseif ( $op_b !== '' ) {
						$img_rule    = $scope_sel . ' .ecbb-event__image{object-position:' . $op_b . ';}';
						$style_css[] = ( $mq !== '' ? $mq . '{' . $img_rule . '}' : $img_rule );
					}
				}
			}
		}

		$idx++;
	}

	return [ $style_css, $hover_css ];
}

/**
 * Gap between events with optional responsive values.
 *
 * @param array<string,mixed> $settings Element settings.
 * @return string CSS value e.g. 24px
 */
function ecbb_events_widget_resolve_item_gap_css( array $settings ) {
	$gap = $settings['item_gap'] ?? 24;
	if ( is_array( $gap ) ) {
		$gap = ecbb_events_widget_responsive_pick( $gap, 'desktop' );
	}
	$gap = is_numeric( $gap ) ? (float) $gap : 24;

	$unit = isset( $settings['item_gap_unit'] ) ? (string) $settings['item_gap_unit'] : 'px';
	$unit = in_array( $unit, [ 'px', 'rem', 'em' ], true ) ? $unit : 'px';

	return $gap . $unit;
}

/**
 * Responsive gap CSS variables for list/grid root.
 *
 * @param array<string,mixed> $settings
 * @return string Raw CSS rules targeting a selector.
 */
function ecbb_events_widget_build_gap_responsive_css( array $settings, $root_selector ) {
	$unit = isset( $settings['item_gap_unit'] ) ? (string) $settings['item_gap_unit'] : 'px';
	$unit = in_array( $unit, [ 'px', 'rem', 'em' ], true ) ? $unit : 'px';
	$gap  = $settings['item_gap'] ?? 24;

	$rules = [];
	foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
		$val = is_array( $gap )
			? ecbb_events_widget_responsive_pick( $gap, $device )
			: $gap;
		$val = is_numeric( $val ) ? (float) $val : 24;
		$rule = $root_selector . '{--ecbb-gap:' . $val . $unit . ';}';
		$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
	}
	return implode( "\n", $rules );
}
