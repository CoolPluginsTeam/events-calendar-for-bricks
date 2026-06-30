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

if ( ! class_exists( 'ECBB_Styles', false ) ) {

	final class ECBB_Styles {

		public static function ecbb_breakpoints() {
			return [
			'desktop' => '',
			'tablet'  => '@media (max-width:1024px)',
			'mobile'  => '@media (max-width:767px)',
			];
		}

		/**
		* @param mixed  $value     Scalar or Bricks responsive array.
		* @param string $device    desktop|tablet|mobile.
		* @return mixed
		*/

		public static function ecbb_device_value( $value, $device = 'desktop' ) {
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

		public static function ecbb_css_size( $value, $default_unit = 'px' ) {
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

		public static function ecbb_clean_css_size( $value, $default_unit = 'px', $unitless = false ) {
			$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
			if ( '' === $value ) {
				return '';
			}
		if ( true === $unitless && preg_match( '/^-?\d*\.?\d+$/', $value ) ) {
			return $value;
		}
		return self::ecbb_css_size( $value, $default_unit );
		}

		/**
		* @param mixed  $value        CSS shorthand containing one to four size values.
		* @param string $default_unit Unit for numeric values.
		* @return string
		*/

		public static function ecbb_clean_css_box( $value, $default_unit = 'px' ) {
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
			$size = self::ecbb_clean_css_size( $part, $default_unit, false );
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

		public static function ecbb_clean_font_family( $value ) {
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

		public static function ecbb_clean_type_value( $property, $value ) {
			$value = is_string( $value ) || is_numeric( $value ) ? trim( (string) $value ) : '';
			if ( '' === $value ) {
				return '';
			}

		switch ( $property ) {
			case 'font-family':
			return self::ecbb_clean_font_family( $value );
			case 'font-size':
			case 'letter-spacing':
			return self::ecbb_clean_css_size( $value, 'px', false );
			case 'line-height':
			return self::ecbb_clean_css_size( $value, 'px', true );
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

		public static function ecbb_clean_border( $border ) {
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
				$width = self::ecbb_clean_css_size( $part, 'px', false );
				if ( '' !== $width ) {
					continue;
				}
		}
		if ( '' === $style && in_array( $part, [ 'solid', 'dashed', 'dotted', 'double', 'none', 'groove', 'ridge', 'inset', 'outset' ], true ) ) {
			$style = $part;
			continue;
		}
		if ( '' === $color ) {
			$color = class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $part ) : '';
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

		public static function ecbb_spacing_css( $spacing ) {
			if ( ! is_array( $spacing ) ) {
				return self::ecbb_clean_css_box( $spacing );
			}
		$top    = isset( $spacing['top'] ) ? self::ecbb_clean_css_size( $spacing['top'] ) : '';
		$right  = isset( $spacing['right'] ) ? self::ecbb_clean_css_size( $spacing['right'] ) : '';
		$bottom = isset( $spacing['bottom'] ) ? self::ecbb_clean_css_size( $spacing['bottom'] ) : '';
		$left   = isset( $spacing['left'] ) ? self::ecbb_clean_css_size( $spacing['left'] ) : '';
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

		public static function ecbb_radius_css( $radius ) {
			if ( ! is_array( $radius ) ) {
				return self::ecbb_clean_css_box( $radius );
			}
		$box = [];
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			if ( ! isset( $radius[ $side ] ) || $radius[ $side ] === '' || $radius[ $side ] === null ) {
				continue;
			}
		$box[ $side ] = self::ecbb_css_size( $radius[ $side ], 'px' );
		}
		if ( empty( $box ) ) {
			return '';
		}
		return self::ecbb_spacing_css( $box );
		}

		/**
		* @param mixed $border Bricks border control value.
		* @return string[] CSS declarations (border-*, border-radius).
		*/

		public static function ecbb_border_decls( $border ) {
			if ( ! is_array( $border ) ) {
				$border = self::ecbb_clean_border( $border );
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
			$color = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_norm_color( $border['color'] )
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
			$values[ $side ] = self::ecbb_css_size( $width[ $side ], 'px' );
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
		$w = self::ecbb_css_size( $width, 'px' );
		if ( $w !== '' && $w !== '0' && $w !== '0px' ) {
			$decl[] = 'border:' . $w . ' ' . $style . ' ' . $color;
		}
		}

		if ( ! empty( $border['radius'] ) ) {
			$radius_css = self::ecbb_radius_css( $border['radius'] );
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

		public static function ecbb_border_css( $border ) {
			$decl = self::ecbb_border_decls( $border );
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

		public static function ecbb_chip_parts() {
			return [ 'categories' ];
		}

		/**
		* Selectors for per-term chip surfaces inside a part wrapper.
		*
		* @param string $scope_sel e.g. .ecbb-ev--abc .ecbb-p0
		* @return string Comma-separated selectors.
		*/

		public static function ecbb_chip_selectors( $scope_sel ) {
			return $scope_sel . ' .ecbb-event__term-chip,'
			. $scope_sel . ' .ecbb-event__term-chip > .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event-card__category,'
			. $scope_sel . ' .ecbb-event-card__category,'
			. $scope_sel . ' > .ecbb-event__term';
		}

		/**
		* Relative selector for Bricks repeater typography / text-align (fieldId wrapper).
		*
		* @return string
		*/

		public static function ecbb_date_inner_type_selector() {
			return '& .ecbb-event__date-day, & .ecbb-event__date-time, & .ecbb-event__date-sep';
		}

		public static function ecbb_repeater_type_selector() {
			return '&, & .ecbb-event__term-chip, & .ecbb-event__link, & > .ecbb-event__link, & .ecbb-event__term, & > .ecbb-event__term, '
			. '& .ecbb-event-card__category, & > .ecbb-event-card__category, '
			. '& a.event-button, & > a.event-button, & a.ecbb-event-card__button, & > a.ecbb-event-card__button, '
			. self::ecbb_date_inner_type_selector();
		}

		public static function ecbb_date_type_selectors( $scope_sel ) {
			return $scope_sel . ','
			. $scope_sel . ' .ecbb-event__date-day,'
			. $scope_sel . ' .ecbb-event__date-time,'
			. $scope_sel . ' .ecbb-event__date-sep';
		}

		public static function ecbb_title_inner_selectors( $scope_sel ) {
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

		public static function ecbb_repeater_type_css() {
			$selector = self::ecbb_repeater_type_selector();

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

		public static function ecbb_type_selectors( $scope_sel, $part_type ) {
			if ( 'date' === $part_type ) {
				return self::ecbb_date_type_selectors( $scope_sel );
			}

		if ( in_array( $part_type, self::ecbb_chip_parts(), true ) ) {
			return self::ecbb_chip_selectors( $scope_sel );
		}

		if ( in_array( $part_type, self::ecbb_button_parts(), true ) ) {
			return self::ecbb_button_inner_selectors( $scope_sel );
		}

		if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
			return $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' a.event-button,'
			. $scope_sel . ' > a.event-button,'
			. $scope_sel . ' a.ecbb-event-card__button,'
			. $scope_sel . ' > a.ecbb-event-card__button,'
			. $scope_sel . ' .ecbb-event__term,'
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

		public static function ecbb_button_parts() {
			return [ 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		/**
		* Scoped selectors for button chrome on the inner link (not the row wrapper).
		*
		* @param string $scope_sel e.g. .ecbb-ev--abc .ecbb-p0
		* @return string
		*/

		public static function ecbb_button_inner_selectors( $scope_sel ) {
			return $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > a,'
			. $scope_sel . ' .ecbb-event__plain,'
			. $scope_sel . ' a.event-button,'
			. $scope_sel . ' a.ecbb-event-card__button';
		}

		/**
		* Event parts whose hover styles target inner links/terms (not the wrapper).
		*
		* @return string[]
		*/

		public static function ecbb_link_hover_parts() {
			return [ 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		/**
		* Build :hover selectors for a scoped event part.
		*
		* @param string $scope_sel  e.g. .ecbb-ev--abc .ecbb-p0
		* @param string $part_type  Part slug.
		* @return string Comma-separated selectors.
		*/

		public static function ecbb_hover_selectors( $scope_sel, $part_type ) {
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				return $scope_sel . ' .ecbb-event__term-chip:hover,'
				. $scope_sel . ' .ecbb-event__link:hover,'
				. $scope_sel . ' > .ecbb-event__link:hover,'
				. $scope_sel . ' a.event-button:hover,'
				. $scope_sel . ' > a.event-button:hover,'
				. $scope_sel . ' a.ecbb-event-card__button:hover,'
				. $scope_sel . ' > a.ecbb-event-card__button:hover,'
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

		public static function ecbb_hover_anim_scope( $scope_sel, $part_type ) {
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				return $scope_sel . ' .ecbb-event__term-chip,'
				. $scope_sel . ' .ecbb-event__link,'
				. $scope_sel . ' > .ecbb-event__link,'
				. $scope_sel . ' a.event-button,'
				. $scope_sel . ' > a.event-button,'
				. $scope_sel . ' a.ecbb-event-card__button,'
				. $scope_sel . ' > a.ecbb-event-card__button,'
				. $scope_sel . ' .ecbb-event__term';
			}

		return $scope_sel;
		}

		/**
		* Internal date-preset keys mapped to PHP date() format strings.
		*
		* @return array<string,string>
		*/

		public static function ecbb_date_formats() {
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

		public static function ecbb_date_php_format( $preset, $part = 'event_date' ) {
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

		$formats = self::ecbb_date_formats();

		return isset( $formats[ $preset ] ) ? $formats[ $preset ] : null;
		}

		/**
		* Date format presets shared by list query + repeater date parts.
		*
		* @return array<string,string>
		*/

		public static function ecbb_date_options() {
			return [
			''        => esc_html__( 'Default', 'events-calendar-for-bricks' ),
			'default' => esc_html__( 'Default (01 January 2025)', 'events-calendar-for-bricks' ),
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
			'custom'  => esc_html__( 'Custom…', 'events-calendar-for-bricks' ),
			];
		}

		/**
		* Consolidated part dropdown options (saved `part` key).
		*
		* @return array<string,string>
		*/

		public static function ecbb_part_options() {
			return [
			'title'       => esc_html__( 'Title', 'events-calendar-for-bricks' ),
			'description' => esc_html__( 'Description', 'events-calendar-for-bricks' ),
			'date'        => esc_html__( 'Date & time', 'events-calendar-for-bricks' ),
			'venue'       => esc_html__( 'Venue', 'events-calendar-for-bricks' ),
			'organizer'   => esc_html__( 'Organizer', 'events-calendar-for-bricks' ),
			'event_link'  => esc_html__( 'Event link', 'events-calendar-for-bricks' ),
			'event_cost'  => esc_html__( 'Cost', 'events-calendar-for-bricks' ),
			'event_tickets' => esc_html__( 'Tickets', 'events-calendar-for-bricks' ),
			'event_rsvp'  => esc_html__( 'RSVP', 'events-calendar-for-bricks' ),
			'read_more'   => esc_html__( 'Read more', 'events-calendar-for-bricks' ),
			'categories'  => esc_html__( 'Categories', 'events-calendar-for-bricks' ),
			'tags'        => esc_html__( 'Tags', 'events-calendar-for-bricks' ),
			'image'       => esc_html__( 'Featured image', 'events-calendar-for-bricks' ),
			];
		}

		/**
		* Map consolidated UI part + display dropdown to legacy render slug (backward compatible).
		*
		* @param array<string,mixed> $item Repeater row.
		* @return array<string,mixed>
		*/

		public static function ecbb_clean_part( array $item ) {
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
		'name_and_city'  => 'venue',
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
			if ( $fmt === 'range' ) {
				return $item;
			}
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
		* Read a responsive number saved in Bricks flat key format (key, key:tablet_portrait, …).
		*
		* @param array<string,mixed> $settings Element settings.
		* @param string              $key      Control id (e.g. grid_cols).
		* @param array{desktop:int,tablet:int,mobile:int} $defaults
		* @param int|float           $min      Minimum allowed value (0 for gap, 1 for grid columns).
		* @return array{desktop:int,tablet:int,mobile:int}
		*/

		public static function ecbb_responsive_number( array $settings, $key, array $defaults, $min = 1 ) {
			$clamp = static function ( $value, $device = 'desktop' ) use ( $min, $defaults ) {
				if ( $value === '' || $value === null ) {
					$value = $defaults[ $device ] ?? $min;
				}
			$num = is_numeric( $value ) ? (float) $value : (float) ( $defaults[ $device ] ?? $min );
			if ( (float) $min <= 0 ) {
				return max( 0.0, $num );
			}
		return (int) max( (int) $min, (int) round( $num ) );
		};

		$nested = $settings[ $key ] ?? null;
		if ( is_array( $nested ) && ( isset( $nested['desktop'] ) || isset( $nested['tablet'] ) || isset( $nested['mobile'] ) ) ) {
			return [
			'desktop' => $clamp( self::ecbb_device_value( $nested, 'desktop' ), 'desktop' ),
			'tablet'  => $clamp( self::ecbb_device_value( $nested, 'tablet' ), 'tablet' ),
			'mobile'  => $clamp( self::ecbb_device_value( $nested, 'mobile' ), 'mobile' ),
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
					$resolved[ $device ] = $clamp( $settings[ $candidate ], $device );
					break;
				}
		}
		}

		if ( $resolved['desktop'] === null && is_scalar( $nested ) && $nested !== '' ) {
			$resolved['desktop'] = $clamp( $nested, 'desktop' );
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

		public static function ecbb_grid_cols_vars( array $settings ) {
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
			return self::ecbb_responsive_number( $settings, 'grid_cols', $defaults );
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

		public static function ecbb_grid_cols_css( array $settings, $root_selector ) {
			$cols  = self::ecbb_grid_cols_vars( $settings );
			$rules = [];

			foreach ( self::ecbb_breakpoints() as $device => $mq ) {
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

		public static function ecbb_responsive_spacing( array $item, $key, $device = 'desktop' ) {
			$nested = $item[ $key ] ?? null;
			if ( is_array( $nested ) && ( isset( $nested['desktop'] ) || isset( $nested['tablet'] ) || isset( $nested['mobile'] ) ) ) {
				$picked = self::ecbb_device_value( $nested, $device );
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

		public static function ecbb_button_decls( array $item, $device, callable $color_fn ) {
			if ( class_exists( 'ECBB_Markup', false ) && ! \ECBB_Markup::ecbb_btn_style_active( $item ) ) {
				return [];
			}
			if ( empty( $item['btn_style'] ) && ! class_exists( 'ECBB_Markup', false ) ) {
				return [];
			}

		$styles = [];

		$bg_raw = self::ecbb_device_value( $item['btn_bg'] ?? '', $device );
		if ( $bg_raw === '' || $bg_raw === null ) {
			$bg_raw = self::ecbb_device_value( $item['ecbb_background'] ?? '', $device );
		}
		if ( $bg_raw !== '' && $bg_raw !== null ) {
			$bg = $color_fn( $bg_raw );
			if ( $bg !== '' ) {
				$styles[] = 'background-color:' . $bg . ' !important';
			}
		}

		$tc_raw = self::ecbb_device_value( $item['btn_text_color'] ?? '', $device );
		if ( ( $tc_raw === '' || $tc_raw === null ) && ! empty( $item['ecbb_typography'] ) && is_array( $item['ecbb_typography'] ) ) {
			$tc_raw = self::ecbb_device_value( $item['ecbb_typography']['color'] ?? '', $device );
		}
		if ( $tc_raw !== '' && $tc_raw !== null ) {
			$tc = $color_fn( $tc_raw );
			if ( $tc !== '' ) {
				$styles[] = 'color:' . $tc . ' !important';
			}
		}

		$border_color_raw = self::ecbb_device_value( $item['btn_border_color'] ?? '', $device );
		$border_style     = self::ecbb_device_value( $item['btn_border_type'] ?? '', $device );
		$border_width_raw = self::ecbb_device_value( $item['btn_border_width'] ?? '', $device );
		if ( ( $border_color_raw === '' || $border_color_raw === null ) && ! empty( $item['btn_border'] ) ) {
			$border_legacy = self::ecbb_device_value( $item['btn_border'], $device );
			if ( is_array( $border_legacy ) ) {
				if ( ! empty( $border_legacy['color'] ) ) {
					$border_color_raw = $border_legacy['color'];
				}
			if ( ( $border_style === '' || $border_style === null ) && ! empty( $border_legacy['style'] ) ) {
				$border_style = $border_legacy['style'];
			}
		if ( ( $border_width_raw === '' || $border_width_raw === null ) && isset( $border_legacy['width'] ) ) {
			$border_width_raw = $border_legacy['width'];
		}
		}
		}
		if ( ! is_string( $border_style ) || $border_style === '' ) {
			$border_style = isset( $item['btn_border_type'] ) ? trim( (string) $item['btn_border_type'] ) : 'solid';
		}
		if ( $border_style === '' ) {
			$border_style = 'solid';
		}
		if ( ! in_array( $border_style, [ 'solid', 'dashed', 'dotted', 'double', 'none' ], true ) ) {
			$border_style = 'solid';
		}

		$border_width = '';
		if ( is_array( $border_width_raw ) ) {
			$border_width = self::ecbb_spacing_css( $border_width_raw );
		} elseif ( $border_width_raw !== '' && $border_width_raw !== null ) {
		$border_width = self::ecbb_css_size( $border_width_raw, 'px' );
		}

		if ( $border_style === 'none' ) {
			$styles[] = 'border:none';
			$styles[] = 'box-sizing:border-box';
		} elseif ( $border_color_raw !== '' && $border_color_raw !== null ) {
		$border_color = $color_fn( $border_color_raw );
		if ( $border_color !== '' ) {
			if ( $border_width === '' ) {
				$border_width = '1px';
			}
		$styles[] = 'border:' . $border_width . ' ' . $border_style . ' ' . $border_color;
		$styles[] = 'box-sizing:border-box';
		}
		}

		$padding_raw = self::ecbb_responsive_spacing( $item, 'btn_padding', $device );
		$padding_css = '';
		if ( ! empty( $padding_raw ) ) {
			$padding_css = self::ecbb_spacing_css( $padding_raw );
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

		$radius_raw = self::ecbb_responsive_spacing( $item, 'btn_border_radius', $device );
		$radius_css = '';
		if ( ! empty( $radius_raw ) ) {
			$radius_css = self::ecbb_radius_css( $radius_raw );
		}
		if ( $radius_css === '' ) {
			$radius_pick = self::ecbb_device_value( $item['btn_border_radius'] ?? '', $device );
			if ( is_array( $radius_pick ) ) {
				$radius_css = self::ecbb_radius_css( $radius_pick );
			} elseif ( is_string( $radius_pick ) && $radius_pick !== '' ) {
			$radius_css = self::ecbb_clean_css_box( $radius_pick );
		}
		}
		if ( $radius_css !== '' ) {
			$styles[] = 'border-radius:' . $radius_css;
		}

		return $styles;
		}

		/**
		* CSS declarations for a featured-image part (scoped CSS; not inline on `<img>`).
		*
		* @param array<string,mixed> $item
		* @param string              $device
		* @return string[]
		*/

		public static function ecbb_image_decls( array $item, $device = 'desktop' ) {
			$styles = [];

			if ( ! empty( $item['image_aspect_ratio'] ) && is_string( $item['image_aspect_ratio'] ) && preg_match( '/^\d+\/\d+$/', $item['image_aspect_ratio'] ) ) {
				$styles[] = 'aspect-ratio:' . $item['image_aspect_ratio'];
			}

		$border_css = '';
		if ( ! empty( $item['ecbb_image_border'] ) ) {
			$border_css = self::ecbb_border_css( $item['ecbb_image_border'] );
		}
		if ( $border_css === '' ) {
			$bw = self::ecbb_css_size( $item['ecbb_image_border_width'] ?? '', 'px' );
			$bc = class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $item['ecbb_image_border_color'] ?? '' ) : '';
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
			$radius = self::ecbb_spacing_css( $item['ecbb_image_radius'] );
		}
		if ( $radius === '' ) {
			$radius = self::ecbb_css_size( $item['ecbb_image_radius'] ?? '', 'px' );
		}
		if ( $radius !== '' ) {
			$styles[] = 'border-radius:' . $radius;
		}

		$w = self::ecbb_css_size(
		self::ecbb_device_value( $item['ecbb_image_width'] ?? '', $device ),
		'%'
		);
		if ( $w !== '' ) {
			$styles[] = 'width:' . $w;
		}

		$h = self::ecbb_css_size(
		self::ecbb_device_value( $item['ecbb_image_height'] ?? '', $device ),
		'px'
		);
		if ( $h !== '' ) {
			$styles[] = 'height:' . $h;
		}

		$fit = self::ecbb_device_value( $item['ecbb_image_fit'] ?? '', $device );
		$fit = is_string( $fit ) ? $fit : '';
		if ( $fit !== '' && in_array( $fit, [ 'cover', 'contain', 'fill', 'none', 'scale-down' ], true ) ) {
			$styles[] = 'object-fit:' . $fit;
		}

		return $styles;
		}

		/**
		* Build inline style attr for a part row (image sizing only; typography uses Bricks repeater CSS).
		*
		* @deprecated Use scoped CSS via ecbb_image_decls().
		* @param array<string,mixed> $item
		* @param bool                $allow_radius Image part.
		* @return string
		*/

		public static function ecbb_inline_style_attr( array $item, $allow_radius = false ) {
			if ( ! $allow_radius ) {
				return '';
			}

		$decls = self::ecbb_image_decls( $item, 'desktop' );
		return empty( $decls ) ? '' : implode( ';', $decls ) . ';';
		}

		/**
		* @param array<string,mixed> $typo
		* @param string              $device
		* @return string[]
		*/

		public static function ecbb_type_decls( array $typo, $device = 'desktop' ) {
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
			$val = self::ecbb_device_value( $typo[ $key ], $device );
			if ( is_string( $val ) && trim( $val ) !== '' ) {
				$val = self::ecbb_clean_type_value( $css_prop, $val );
				if ( $val !== '' ) {
					$decl[] = $css_prop . ':' . $val;
				}
		} elseif ( is_numeric( $val ) && $css_prop === 'font-weight' ) {
		$decl[] = $css_prop . ':' . (int) $val;
		}
		}

		if ( ! empty( $typo['color'] ) ) {
			$color_raw = self::ecbb_device_value( $typo['color'], $device );
			$color     = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_norm_color( $color_raw )
			: '';
			if ( $color !== '' ) {
				$decl[] = 'color:' . $color;
			}
		}

		return $decl;
		}

		/**
		* Append !important to each CSS declaration (layout button chrome overrides repeater Style tab).
		*
		* @param string[] $decls
		* @return string[]
		*/

		public static function ecbb_decls_important( array $decls ) {
			$out = [];
			foreach ( $decls as $decl ) {
				$decl = trim( (string) $decl );
				if ( $decl === '' ) {
					continue;
				}
				if ( ! preg_match( '/!important\s*$/i', $decl ) ) {
					$decl .= ' !important';
				}
				$out[] = $decl;
			}
			return $out;
		}

		/**
		* Clear reference layout button chrome so repeater Style tab rules can paint the CTA.
		*
		* @param string $scope_sel Scoped row selector.
		* @return string CSS rule or empty.
		*/

		public static function ecbb_layout_btn_reset_rule( $scope_sel ) {
			$inner = self::ecbb_button_inner_selectors( $scope_sel );
			return $inner . '{'
				. 'background:unset!important;'
				. 'border:unset!important;'
				. 'box-shadow:unset!important;'
				. 'color:unset!important;'
				. 'padding:unset!important;'
				. 'font-size:unset!important;'
				. 'font-weight:unset!important;'
				. 'line-height:unset!important;'
				. 'letter-spacing:unset!important;'
				. 'text-transform:unset!important;'
				. 'min-height:unset!important;'
				. 'width:unset!important;'
				. 'max-width:unset!important;'
				. 'display:inline-flex!important;'
				. 'align-items:center!important;'
				. 'justify-content:center!important;'
				. 'box-sizing:border-box!important;'
				. 'text-decoration:none!important;'
				. '}';
		}

		/**
		* Whether a repeater row has any Style-tab surface rules (typography, background, padding).
		*
		* @param array<string,mixed> $item Repeater row.
		* @return bool
		*/

		public static function ecbb_part_has_surface_styles( array $item ) {
			if ( ! empty( $item['ecbb_typography'] ) && is_array( $item['ecbb_typography'] ) ) {
				return true;
			}
			if ( ! empty( $item['ecbb_background'] ) ) {
				return true;
			}
			if ( ! empty( $item['ecbb_padding'] ) ) {
				return true;
			}
			return false;
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

		public static function ecbb_parts_css( array $parts, $scope_class, callable $color_fn, $list_item_style = 'style-1' ) {
			$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
			$style_css   = [];
			$hover_css   = [];
			if ( $scope_class === '' ) {
				return [ $style_css, $hover_css ];
			}

		$idx = 0;

		foreach ( $parts as $p ) {
			if ( ! is_array( $p ) ) {
				++$idx;
				continue;
			}

			$scope_sel = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_part_scope_selector( $scope_class, $p, $idx )
				: '.' . $scope_class . ' .ecbb-p' . absint( $idx );

			if ( $scope_sel === '' ) {
				++$idx;
				continue;
			}

		$p = self::ecbb_clean_part( $p );
		$part_type = isset( $p['part'] ) ? (string) $p['part'] : '';
		$hover_style_on = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_hover_style_active( $p );
		$btn_style_on   = class_exists( 'ECBB_Markup', false )
		&& \ECBB_Markup::ecbb_btn_style_active( $p )
		&& in_array( $part_type, self::ecbb_button_parts(), true );
		$layout_btn_part = in_array( $part_type, self::ecbb_button_parts(), true );
		$chip_surface = in_array( $part_type, self::ecbb_chip_parts(), true );
		$chip_sel = $chip_surface
		? self::ecbb_chip_selectors( $scope_sel )
		: $scope_sel;

		if ( $layout_btn_part && ! $btn_style_on && self::ecbb_part_has_surface_styles( $p ) ) {
			$reset_rule  = self::ecbb_layout_btn_reset_rule( $scope_sel );
			$style_css[] = $reset_rule;
		}

		// Emit repeater typography/spacing on the frontend (Bricks fieldId misses nested date text nodes).
		if ( 'image' !== $part_type ) {
			foreach ( self::ecbb_breakpoints() as $device => $mq ) {
				if ( ! empty( $p['ecbb_typography'] ) && is_array( $p['ecbb_typography'] ) ) {
					$typo_decls = self::ecbb_type_decls( $p['ecbb_typography'], $device );
					if ( ! empty( $typo_decls ) ) {
						if ( $layout_btn_part && ! $btn_style_on ) {
							$typo_decls = self::ecbb_decls_important( $typo_decls );
						}
						$typo_sel = self::ecbb_type_selectors( $scope_sel, $part_type );
						$typo_rule = $typo_sel . '{' . implode( ';', $typo_decls ) . '}';
						$style_css[] = ( $mq !== '' ? $mq . '{' . $typo_rule . '}' : $typo_rule );
					}
			}

		$margin_raw = self::ecbb_responsive_spacing( $p, 'ecbb_margin', $device );
		$margin     = ! empty( $margin_raw ) ? self::ecbb_spacing_css( $margin_raw ) : '';
		if ( $margin !== '' ) {
			$margin_rule = $scope_sel . '{margin:' . $margin . ' !important;}';
			$style_css[] = ( $mq !== '' ? $mq . '{' . $margin_rule . '}' : $margin_rule );
		}

		if ( ! $chip_surface ) {
			$pad_raw = self::ecbb_responsive_spacing( $p, 'ecbb_padding', $device );
			$padding = ! empty( $pad_raw ) ? self::ecbb_spacing_css( $pad_raw ) : '';
			if ( $padding !== '' ) {
				if ( $layout_btn_part && ! $btn_style_on ) {
					$pad_sel     = self::ecbb_button_inner_selectors( $scope_sel );
					$wrapper_pad = $scope_sel . '{padding:0!important;}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $wrapper_pad . '}' : $wrapper_pad );
				} else {
					$pad_sel = $scope_sel;
				}
				$pad_rule    = $pad_sel . '{padding:' . $padding . ' !important;}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $pad_rule . '}' : $pad_rule );
			}
		}

		$align_raw = self::ecbb_device_value( $p['ecbb_text_align'] ?? '', $device );
		if ( is_string( $align_raw ) && in_array( $align_raw, [ 'left', 'center', 'right', 'justify' ], true ) ) {
			$align_sel  = self::ecbb_type_selectors( $scope_sel, $part_type );
			$align_rule = $align_sel . '{text-align:' . $align_raw . ' !important;}';
			$style_css[] = ( $mq !== '' ? $mq . '{' . $align_rule . '}' : $align_rule );
		}
		}
		}

		foreach ( self::ecbb_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$padding_raw = self::ecbb_responsive_spacing( $p, 'ecbb_padding', $device );
				$padding     = ! empty( $padding_raw )
				? self::ecbb_spacing_css( $padding_raw )
				: '';
				if ( $padding !== '' ) {
					$wrapper_pad  = $scope_sel . '{padding:0!important;}';
					$surface_rule = $chip_sel . '{padding:' . $padding . ' !important;}';
					$style_css[]  = ( $mq !== '' ? $mq . '{' . $wrapper_pad . $surface_rule . '}' : $wrapper_pad . $surface_rule );
				}
		}

		if ( $btn_style_on ) {
			$wrapper_reset = $scope_sel . '{padding:0!important;border:none!important;background-color:transparent!important}';
			$style_css[]   = ( $mq !== '' ? $mq . '{' . $wrapper_reset . '}' : $wrapper_reset );

			$btn_decls = self::ecbb_button_decls( $p, $device, $color_fn );
			if ( ! empty( $btn_decls ) ) {
				$btn_sel = self::ecbb_button_inner_selectors( $scope_sel );
				$btn_rule = $btn_sel . '{' . implode( ';', $btn_decls ) . '}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $btn_rule . '}' : $btn_rule );
			}
		}
		}

		if ( $hover_style_on ) {
			$hover_sel = self::ecbb_hover_selectors( $scope_sel, $part_type );

			$hover = $color_fn( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ) );
			if ( $hover !== '' ) {
				$hover_css[] = $hover_sel . '{color:' . $hover . ' !important;}';
			}

		$hover_bg = $color_fn( $p['ecbb_hover_background'] ?? '' );
		if ( $hover_bg !== '' ) {
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				$hover_css[] = $scope_sel . ' .ecbb-event__term-chip:hover,'
				. $scope_sel . ' .ecbb-event__link:hover,'
				. $scope_sel . ' > .ecbb-event__link:hover,'
				. $scope_sel . ' .ecbb-event-card__category:hover,'
				. $scope_sel . ' > .ecbb-event-card__category:hover,'
				. $scope_sel . ' a.event-button:hover,'
				. $scope_sel . ' > a.event-button:hover,'
				. $scope_sel . ' a.ecbb-event-card__button:hover,'
				. $scope_sel . ' > a.ecbb-event-card__button:hover,'
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
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				$hover_css[] = $scope_sel . ' .ecbb-event__term-chip:hover,'
				. $scope_sel . ' .ecbb-event__link:hover,'
				. $scope_sel . ' > .ecbb-event__link:hover,'
				. $scope_sel . ' .ecbb-event-card__category:hover,'
				. $scope_sel . ' > .ecbb-event-card__category:hover,'
				. $scope_sel . ' a.event-button:hover,'
				. $scope_sel . ' > a.event-button:hover,'
				. $scope_sel . ' a.ecbb-event-card__button:hover,'
				. $scope_sel . ' > a.ecbb-event-card__button:hover,'
				. $scope_sel . ' .ecbb-event__term:hover{text-decoration:' . $hover_td . ' !important;}';
			} else {
			$hover_css[] = $scope_sel . ':hover,'
			. $scope_sel . ' a:hover,'
			. $scope_sel . ' .ecbb-event__term:hover{text-decoration:' . $hover_td . ' !important;}';
		}
		}

		$hover_anim = isset( $p['ecbb_hover_animation'] ) ? (string) $p['ecbb_hover_animation'] : '';
		if ( $hover_anim !== '' && class_exists( 'ECBB_Markup', false ) ) {
			$anim_scope  = self::ecbb_hover_anim_scope( $scope_sel, $part_type );
			$anim_blocks = \ECBB_Markup::ecbb_hover_anim_css( $anim_scope, $hover_anim );
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
			foreach ( self::ecbb_breakpoints() as $device => $mq ) {
				$bg_raw = self::ecbb_device_value( $p['ecbb_background_inner'] ?? '', $device );
				$bg_in  = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg_in !== '' ) {
					$inner_sel  = self::ecbb_title_inner_selectors( $scope_sel );
					$inner_rule = $inner_sel . '{background-color:' . $bg_in . ' !important;}';
					$style_css[] = ( $mq !== '' ? $mq . '{' . $inner_rule . '}' : $inner_rule );
				}
		}
		}

		foreach ( self::ecbb_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$bg_raw = self::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
				$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg !== '' ) {
					$wrapper_reset = $scope_sel . '{background-color:transparent!important;}';
					$chip_rule     = $chip_sel . '{background-color:' . $bg . ' !important;}';
					$style_css[]   = ( $mq !== '' ? $mq . '{' . $wrapper_reset . $chip_rule . '}' : $wrapper_reset . $chip_rule );
				}
		} elseif ( $btn_style_on ) {
		$btn_bg_raw = self::ecbb_device_value( $p['btn_bg'] ?? '', $device );
		$bg_raw     = self::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
		if ( ( $btn_bg_raw === '' || $btn_bg_raw === null ) && $bg_raw !== '' && $bg_raw !== null ) {
			$bg = $color_fn( $bg_raw );
			if ( $bg !== '' ) {
				$btn_inner   = self::ecbb_button_inner_selectors( $scope_sel );
				$btn_bg_rule = $btn_inner . '{background-color:' . $bg . ' !important;}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $btn_bg_rule . '}' : $btn_bg_rule );
			}
		}
		} else {
		$bg_raw = self::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
		$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
		if ( $bg !== '' ) {
			if ( $layout_btn_part && ! $btn_style_on ) {
				$inner_sel   = self::ecbb_button_inner_selectors( $scope_sel );
				$bg_rule     = $inner_sel . '{background-color:' . $bg . ' !important;}';
				$wrapper_bg  = $scope_sel . '{background-color:transparent!important;}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $wrapper_bg . $bg_rule . '}' : $wrapper_bg . $bg_rule );
			} else {
				$bg_rule     = $scope_sel . '{background-color:' . $bg . ' !important;}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $bg_rule . '}' : $bg_rule );
			}
		}
		}
		}

		if ( $part_type === 'image' ) {
			$dual = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_image_dual_layer( $p );

			foreach ( self::ecbb_breakpoints() as $device => $mq ) {
				$img_decls = self::ecbb_image_decls( $p, $device );
				if ( ! empty( $img_decls ) ) {
					if ( $dual ) {
						$img_sel = $scope_sel . ' .ecbb-event__image--base,'
						. $scope_sel . ' .ecbb-event__image--hover';
					} else {
					$img_sel = $scope_sel . ' .ecbb-event__image';
				}
			$img_rule    = $img_sel . '{' . implode( ';', $img_decls ) . '}';
			$style_css[] = ( $mq !== '' ? $mq . '{' . $img_rule . '}' : $img_rule );
		}

		if ( ! class_exists( 'ECBB_Markup', false ) ) {
			continue;
		}

		if ( $dual ) {
			$op_b = \ECBB_Markup::ecbb_align_to_position(
			self::ecbb_device_value( $p['ecbb_image_object_align'] ?? '', $device )
			);
			$op_h = \ECBB_Markup::ecbb_align_to_position( $p['ecbb_image_object_align_hover'] ?? '' );
			if ( $op_b !== '' ) {
				$base_rule   = $scope_sel . ' .ecbb-event__image--base{object-position:' . $op_b . ';}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $base_rule . '}' : $base_rule );
			}
		if ( $op_h !== '' ) {
			$hover_rule  = $scope_sel . ' .ecbb-event__image--hover{object-position:' . $op_h . ';}';
			$style_css[] = ( $mq !== '' ? $mq . '{' . $hover_rule . '}' : $hover_rule );
		}
		continue;
		}

		$align_b = self::ecbb_device_value( $p['ecbb_image_object_align'] ?? '', $device );
		$op_b    = \ECBB_Markup::ecbb_align_to_position( $align_b );
		$op_h    = \ECBB_Markup::ecbb_align_to_position( $p['ecbb_image_object_align_hover'] ?? '' );

		if ( $hover_style_on && $op_h !== '' && $op_h !== $op_b ) {
			if ( $op_b !== '' ) {
				$base_rule   = $scope_sel . ' .ecbb-event__image{object-position:' . $op_b . ';transition:object-position 0.35s ease;}';
				$style_css[] = ( $mq !== '' ? $mq . '{' . $base_rule . '}' : $base_rule );
			} else {
			$base_rule   = $scope_sel . ' .ecbb-event__image{transition:object-position 0.35s ease;}';
			$style_css[] = ( $mq !== '' ? $mq . '{' . $base_rule . '}' : $base_rule );
		}
		$hover_css[] = $scope_sel . ':hover .ecbb-event__image{object-position:' . $op_h . ' !important;}';
		} elseif ( $op_b !== '' ) {
		$img_rule    = $scope_sel . ' .ecbb-event__image{object-position:' . $op_b . ';}';
		$style_css[] = ( $mq !== '' ? $mq . '{' . $img_rule . '}' : $img_rule );
		}
		}
		}

		$idx++;
		}

		return [ $style_css, $hover_css ];
		}

		/**
		* Resolve gap between events per breakpoint (Bricks flat keys + nested arrays).
		*
		* @param array<string,mixed> $settings Element settings.
		* @return array{desktop:float,tablet:float,mobile:float}
		*/

		public static function ecbb_gap_vars( array $settings ) {
			$defaults = [
			'desktop' => 24.0,
			'tablet'  => 24.0,
			'mobile'  => 24.0,
			];

			$has = array_key_exists( 'item_gap', $settings ) && $settings['item_gap'] !== '' && $settings['item_gap'] !== null;
			if ( ! $has ) {
				foreach ( array_keys( $settings ) as $setting_key ) {
					if ( strpos( (string) $setting_key, 'item_gap:' ) === 0 ) {
						$has = true;
						break;
					}
			}
		}

		if ( ! $has ) {
			return $defaults;
		}

		return self::ecbb_responsive_number( $settings, 'item_gap', $defaults, 0 );
		}

		/**
		* Unit for gap between events (Bricks number control unit + legacy item_gap_unit).
		*
		* @param array<string,mixed> $settings Element settings.
		* @return string px|rem|em
		*/

		public static function ecbb_gap_unit( array $settings ) {
			$allowed = [ 'px', 'rem', 'em' ];

			if ( isset( $settings['item_gap_unit'] ) ) {
				$legacy = (string) $settings['item_gap_unit'];
				if ( in_array( $legacy, $allowed, true ) ) {
					return $legacy;
				}
		}

		foreach ( [ 'item_gap:unit', 'item_gap_unit' ] as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$unit = (string) $settings[ $key ];
				if ( in_array( $unit, $allowed, true ) ) {
					return $unit;
				}
		}
		}

		if ( isset( $settings['item_gap'] ) && is_array( $settings['item_gap'] ) && isset( $settings['item_gap']['unit'] ) ) {
			$nested = (string) $settings['item_gap']['unit'];
			if ( in_array( $nested, $allowed, true ) ) {
				return $nested;
			}
		}

		return 'px';
		}

		/**
		* Responsive gap CSS variables for list/grid root.
		*
		* @param array<string,mixed> $settings
		* @return string Raw CSS rules targeting a selector.
		*/

		public static function ecbb_gap_responsive_css( array $settings, $root_selector ) {
			$unit = self::ecbb_gap_unit( $settings );
			$gaps = self::ecbb_gap_vars( $settings );

			$rules = [];
			foreach ( self::ecbb_breakpoints() as $device => $mq ) {
				$val   = $gaps[ $device ] ?? 24;
				$val   = is_numeric( $val ) ? (float) $val : 24;
				$rule  = $root_selector . '{--ecbb-gap:' . $val . $unit . ';}';
				$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
			}
		return implode( "\n", $rules );
		}

	}

}

