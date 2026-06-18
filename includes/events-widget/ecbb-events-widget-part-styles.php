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
 * Date format presets shared by list query + repeater date parts.
 *
 * @return array<string,string>
 */
function ecbb_events_widget_date_format_preset_options() {
	return [
		''      => esc_html__( 'Default', 'ecbb' ),
		'default' => esc_html__( 'Default (01 January 2025)', 'ecbb' ),
		'MD,Y'  => 'Md,Y (Jan 01, 2025)',
		'FD,Y'  => 'Fd,Y (January 01, 2025)',
		'DM'    => 'dM (01 Jan)',
		'DML'   => 'dML (01 Jan Monday)',
		'DF'    => 'dF (01 January)',
		'MD'    => 'Md (Jan 01)',
		'FD'    => 'Fd (January 01)',
		'MD,YT' => 'Md,YT (Jan 01, 2025 8:00am-5:00pm)',
		'full'  => 'Full (01 January 2025 8:00am-5:00pm)',
		'jMl'   => 'jMl (1 Jan Monday)',
		'd.FY'  => 'd.FY (01. January 2025)',
		'd.F'   => 'd.F (01. January)',
		'ldF'   => 'ldF (Monday 01 January)',
		'Mdl'   => 'Mdl (Jan 01 Monday)',
		'd.Ml'  => 'd.Ml (01. Jan Monday)',
		'dFT'   => 'dFT (01 January 8:00am-5:00pm)',
		'sed'   => 'SED (01 Jan - 02 Jan 2025)',
		'sedt'  => 'SEDT (01 Jan - 02 Jan 2025 8:00am-5:00pm)',
		'D.j.F' => 'D.,j. F (Wed., 15. May)',
		'custom' => esc_html__( 'Custom…', 'ecbb' ),
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
		$fmt = isset( $item['venue_display'] ) ? (string) $item['venue_display'] : 'name';
		$map = [
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
		$fmt = isset( $item['organizer_display'] ) ? (string) $item['organizer_display'] : 'name';
		$map = [
			'name'    => 'organizer',
			'email'   => 'organizer_email',
			'phone'   => 'organizer_phone',
			'website' => 'organizer_website',
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

		$w = ecbb_events_widget_normalize_css_size( $item['ecbb_image_width'] ?? '', '%' );
		if ( $w !== '' ) {
			$styles[] = 'width:' . $w;
		}

		$h = ecbb_events_widget_normalize_css_size( $item['ecbb_image_height'] ?? '', 'px' );
		if ( $h !== '' ) {
			$styles[] = 'height:' . $h;
		}

		$fit = isset( $item['ecbb_image_fit'] ) ? (string) $item['ecbb_image_fit'] : '';
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

	return $decl;
}

/**
 * Scoped CSS for all event parts in a loop instance.
 *
 * @param array<int,array<string,mixed>> $parts
 * @param string                         $scope_class e.g. ecbb-ev--abc
 * @param callable                       $color_fn    function( $value ): string
 * @return array{0:string[],1:string[]}  [base rules, hover rules]
 */
function ecbb_events_widget_build_parts_scoped_css( array $parts, $scope_class, callable $color_fn ) {
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
		$chip_surface = function_exists( 'ecbb_events_widget_part_chip_surface_part_slugs' )
			&& in_array( $part_type, ecbb_events_widget_part_chip_surface_part_slugs(), true );
		$chip_sel = $chip_surface && function_exists( 'ecbb_events_widget_part_chip_surface_selectors' )
			? ecbb_events_widget_part_chip_surface_selectors( $scope_sel )
			: $scope_sel;

		foreach ( ecbb_events_widget_style_breakpoints() as $device => $mq ) {
			$padding = '';
			if ( ! empty( $p['ecbb_padding'] ) ) {
				$padding = ecbb_events_widget_spacing_to_css(
					ecbb_events_widget_responsive_pick( $p['ecbb_padding'], $device )
				);
			}
			if ( $padding !== '' ) {
				if ( $chip_surface ) {
					$surface_rule = $chip_sel . '{padding:' . $padding . ';}';
					$style_css[]  = ( $mq !== '' ? $mq . '{' . $surface_rule . '}' : $surface_rule );
				} else {
					$rule        = $scope_sel . '{padding:' . $padding . ';}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $rule . '}' : $rule );
				}
			}
		}

		$hover_style_on = function_exists( 'ecbb_event_part_hover_style_active' ) && ecbb_event_part_hover_style_active( $p );

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
					$hover_css[] = $scope_sel . ':hover,'
						. $scope_sel . ' .ecbb-event__link:hover{background-color:' . $hover_bg . ' !important;}';
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
			$bg_in = $color_fn( $p['ecbb_background_inner'] ?? '' );
			if ( $bg_in === '' ) {
				$bg_in = $color_fn( $p['ecbb_hover_background_inner'] ?? '' );
			}
		}
		if ( $bg_in !== '' ) {
			$style_css[] = $scope_sel . ' > .ecbb-event__link,'
				. $scope_sel . ' .ecbb-event__link,'
				. $scope_sel . ' .ecbb-event__link-wrapper,'
				. $scope_sel . ' .ecbb-event__link-wrapper a,'
				. $scope_sel . ' > a{background-color:' . $bg_in . ' !important;}';
		}

		if ( $chip_surface ) {
			$bg = $color_fn( $p['ecbb_background'] ?? '' );
			if ( $bg !== '' ) {
				$style_css[] = $chip_sel . '{background-color:' . $bg . ' !important;}';
			}
		} else {
			$bg = $color_fn( $p['ecbb_background'] ?? '' );
			if ( $bg !== '' ) {
				$style_css[] = $scope_sel . '{background-color:' . $bg . ' !important;}';
			}
		}

		if ( $part_type === 'image' && $hover_style_on && function_exists( 'ecbb_loop_image_uses_dual_layer' ) && function_exists( 'ecbb_object_position_from_image_align' ) ) {
			if ( ! ecbb_loop_image_uses_dual_layer( $p ) ) {
				$op_b = ecbb_object_position_from_image_align( $p['ecbb_image_object_align'] ?? '' );
				$op_h = ecbb_object_position_from_image_align( $p['ecbb_image_object_align_hover'] ?? '' );
				if ( $op_h !== '' && $op_h !== $op_b ) {
					$img_sel = $scope_sel . ' .ecbb-event__image';
					if ( $op_b !== '' ) {
						$style_css[] = $img_sel . '{object-position:' . $op_b . ';transition:object-position 0.35s ease;}';
					} else {
						$style_css[] = $img_sel . '{transition:object-position 0.35s ease;}';
					}
					$hover_css[] = $scope_sel . ':hover .ecbb-event__image{object-position:' . $op_h . ' !important;}';
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
