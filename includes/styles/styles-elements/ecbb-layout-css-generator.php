<?php
/**
 * ECBB_Layout_Css_Generator service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Layout_Css_Generator', false ) ) {

	final class ECBB_Layout_Css_Generator {

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
			'desktop' => $clamp( ECBB_Css_Value_Sanitizer::ecbb_device_value( $nested, 'desktop' ), 'desktop' ),
			'tablet'  => $clamp( ECBB_Css_Value_Sanitizer::ecbb_device_value( $nested, 'tablet' ), 'tablet' ),
			'mobile'  => $clamp( ECBB_Css_Value_Sanitizer::ecbb_device_value( $nested, 'mobile' ), 'mobile' ),
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

		public static function ecbb_gap_responsive_css( array $settings, $root_selector ) {
			$unit = self::ecbb_gap_unit( $settings );
			$gaps = self::ecbb_gap_vars( $settings );

			$rules = [];
			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$val   = $gaps[ $device ] ?? 24;
				$val   = is_numeric( $val ) ? (float) $val : 24;
				$rule  = $root_selector . '{--ecbb-gap:' . $val . $unit . ';}';
				$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
			}
		return implode( "\n", $rules );
		}

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

		public static function ecbb_grid_cols_css( array $settings, $root_selector ) {
			$cols  = self::ecbb_grid_cols_vars( $settings );
			$rules = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$val  = $cols[ $device ] ?? 3;
				$rule = $root_selector . ' .ecbb-ev__list--grid{--ecbb-grid-cols:' . (int) $val . ';}';
				$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
			}

		return implode( "\n", $rules );
		}

	}
}
