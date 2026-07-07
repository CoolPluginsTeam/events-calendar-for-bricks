<?php
/**
 * ECBB_Date_Format_Presets service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Date_Format_Presets', false ) ) {

	final class ECBB_Date_Format_Presets {

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

		if ( in_array( $preset, [ 'sed', 'sedt' ], true ) ) {
			return $part === 'event_time'
			? (string) get_option( 'time_format' )
			: (string) get_option( 'date_format' );
		}

		$formats = self::ecbb_date_formats();

		return isset( $formats[ $preset ] ) ? $formats[ $preset ] : null;
		}

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

		public static function ecbb_date_options() {
			return [
			''        => esc_html__( 'Default', 'events-calendar-for-bricks' ),
			'default' => esc_html__( 'Default (01 January 2025)', 'events-calendar-for-bricks' ),
			'MD,Y'    => esc_html__( 'Md,Y (Jan 01, 2025)', 'events-calendar-for-bricks' ),
			'FD,Y'    => esc_html__( 'Fd,Y (January 01, 2025)', 'events-calendar-for-bricks' ),
			'DM'      => esc_html__( 'dM (01 Jan)', 'events-calendar-for-bricks' ),
			'DML'     => esc_html__( 'dML (01 Jan Monday)', 'events-calendar-for-bricks' ),
			'DF'      => esc_html__( 'dF (01 January)', 'events-calendar-for-bricks' ),
			'MD'      => esc_html__( 'Md (Jan 01)', 'events-calendar-for-bricks' ),
			'FD'      => esc_html__( 'Fd (January 01)', 'events-calendar-for-bricks' ),
			'MD,YT'   => esc_html__( 'Md,YT (Jan 01, 2025 8:00am-5:00pm)', 'events-calendar-for-bricks' ),
			'full'    => esc_html__( 'Full (01 January 2025 8:00am-5:00pm)', 'events-calendar-for-bricks' ),
			'jMl'     => esc_html__( 'jMl (1 Jan Monday)', 'events-calendar-for-bricks' ),
			'd.FY'    => esc_html__( 'd.FY (01. January 2025)', 'events-calendar-for-bricks' ),
			'd.F'     => esc_html__( 'd.F (01. January)', 'events-calendar-for-bricks' ),
			'ldF'     => esc_html__( 'ldF (Monday 01 January)', 'events-calendar-for-bricks' ),
			'Mdl'     => esc_html__( 'Mdl (Jan 01 Monday)', 'events-calendar-for-bricks' ),
			'd.Ml'    => esc_html__( 'd.Ml (01. Jan Monday)', 'events-calendar-for-bricks' ),
			'dFT'     => esc_html__( 'dFT (01 January 8:00am-5:00pm)', 'events-calendar-for-bricks' ),
			'sed'     => esc_html__( 'SED (01 Jan - 02 Jan 2025)', 'events-calendar-for-bricks' ),
			'sedt'    => esc_html__( 'SEDT (01 Jan - 02 Jan 2025 8:00am-5:00pm)', 'events-calendar-for-bricks' ),
			'D.j.F'   => esc_html__( 'D.,j. F (Wed., 15. May)', 'events-calendar-for-bricks' ),
			'custom'  => esc_html__( 'Custom…', 'events-calendar-for-bricks' ),
			];
		}

	}
}
