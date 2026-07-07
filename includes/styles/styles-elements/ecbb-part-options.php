<?php
/**
 * ECBB_Part_Options service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Part_Options', false ) ) {

	final class ECBB_Part_Options {

		public static function ecbb_part_options_style1() {
			$options = self::ecbb_part_options_shared();

			return array_merge( $options, ECBB_Meta_Combo::ecbb_meta_combo_options_style1() );
		}

		public static function ecbb_part_options() {
			$options = self::ecbb_part_options_shared();

			return array_merge( $options, ECBB_Meta_Combo::ecbb_meta_combo_options_style2() );
		}

		public static function ecbb_part_options_shared() {
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

		public static function ecbb_clean_part( array $item ) {
			if ( ! empty( $item['_ecbb_clean'] ) ) {
				return $item;
			}

			$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';

			if ( ECBB_Meta_Combo::ecbb_is_meta_combo_slug( $part ) ) {
				$item['_ecbb_clean'] = true;
				return $item;
			}

			$legacy_parts = [
			'venue_full_address', 'venue_street', 'venue_city', 'venue_state',
			'venue_zip', 'venue_country', 'venue_phone', 'venue_website', 'event_map_link',
				'organizer_email', 'organizer_phone', 'organizer_website',
				'event_date', 'event_time', 'event_day',
				'event_website', 'event_phone',
			];

			if ( in_array( $part, $legacy_parts, true ) ) {
				$item['_ecbb_clean'] = true;
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

		$item['_ecbb_clean'] = true;
		return $item;
		}

		public static function ecbb_part_options_style2() {
			$options = self::ecbb_part_options_shared();

			return array_merge( $options, ECBB_Meta_Combo::ecbb_meta_combo_options_style2() );
		}

		public static function ecbb_part_options_grid() {
			return self::ecbb_part_options_shared();
		}

	}
}
