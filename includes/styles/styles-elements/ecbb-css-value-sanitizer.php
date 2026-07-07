<?php
/**
 * ECBB_Css_Value_Sanitizer service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Css_Value_Sanitizer', false ) ) {

	final class ECBB_Css_Value_Sanitizer {

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
		if ( in_array( $part, [ 'solid', 'dashed', 'dotted', 'double', 'none', 'groove', 'ridge', 'inset', 'outset' ], true ) ) {
			$style = $part;
			continue;
		}
			$color = class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $part ) : '';
			if ( '' !== $color ) {
				continue;
		}
		return '';
		}

		if ( '' === $width || '' === $style ) {
			return '';
		}

		return trim( $width . ' ' . $style . ' ' . $color );
		}

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

		public static function ecbb_border_css( $border ) {
			$decl = self::ecbb_border_decls( $border );
			foreach ( $decl as $d ) {
				if ( strpos( $d, 'border:' ) === 0 ) {
					return substr( $d, 7 );
				}
		}
		return '';
		}

		public static function ecbb_clean_type_value( $property, $value ) {
			if ( is_array( $value ) ) {
				switch ( $property ) {
					case 'font-size':
					case 'letter-spacing':
						return self::ecbb_clean_css_size( $value, 'px', false );
					case 'line-height':
						return self::ecbb_clean_css_size( $value, 'px', true );
					default:
						return '';
				}
			}

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

		public static function ecbb_clean_css_size( $value, $default_unit = 'px', $unitless = false ) {
			if ( is_array( $value ) ) {
				return self::ecbb_css_size( $value, $default_unit );
			}

			$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
			if ( '' === $value ) {
				return '';
			}
		if ( true === $unitless && preg_match( '/^-?\d*\.?\d+$/', $value ) ) {
			return $value;
		}
		return self::ecbb_css_size( $value, $default_unit );
		}

		public static function ecbb_breakpoints() {
			return [
			'desktop' => '',
			'tablet'  => '@media (max-width:1024px)',
			'mobile'  => '@media (max-width:767px)',
			];
		}

		public static function ecbb_border_decls( $border ) {
			if ( ! is_array( $border ) ) {
				$border = self::ecbb_clean_border( $border );
				if ( '' !== $border ) {
					return [ 'border:' . $border ];
				}
			return [];
		}

		$style = isset( $border['style'] ) ? trim( (string) $border['style'] ) : '';
		if ( ! in_array( $style, [ 'solid', 'dashed', 'dotted', 'double', 'none', 'groove', 'ridge', 'inset', 'outset' ], true ) ) {
			$style = 'solid';
		}

			$color = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_norm_color( $border['color'] ?? '' )
			: trim( (string) ( $border['color'] ?? '' ) );
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

		public static function ecbb_css_size( $value, $default_unit = 'px' ) {
			if ( is_array( $value ) ) {
				$size = isset( $value['size'] ) ? trim( (string) $value['size'] ) : '';
				$unit = isset( $value['unit'] ) ? trim( (string) $value['unit'] ) : $default_unit;
				if ( $size === '' ) {
					return '';
				}
				if ( preg_match( '/^-?\d*\.?\d+(px|rem|em|%)$/', $size ) ) {
					return $size;
				}
				if ( preg_match( '/^-?\d*\.?\d+$/', $size ) ) {
					$unit = in_array( $unit, [ 'px', 'rem', 'em', '%' ], true ) ? $unit : $default_unit;
					return $size . $unit;
				}
				return '';
			}

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

	}
}
