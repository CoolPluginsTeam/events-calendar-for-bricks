<?php
/**
 * ECBB_Query_Controls service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Query_Controls', false ) ) {

	final class ECBB_Query_Controls {

		public static function ecbb_register_query_controls( $element, array $event_category_options ) {
		// â”€â”€ Events Query â”€â”€
		$element->controls['event_type'] = [
			'tab'     => 'content',
			'group'   => 'event_query',
			'label'   => esc_html__( 'Types of events', 'events-calendar-for-bricks' ),
			'type'    => 'select',
			'options' => [
				'past'   => esc_html__( 'Past', 'events-calendar-for-bricks' ),
				'future' => esc_html__( 'Future', 'events-calendar-for-bricks' ),
				'all'    => esc_html__( 'All', 'events-calendar-for-bricks' ),
			],
			'inline'  => true,
			'default' => 'all',
		];

		$element->controls['event_categories'] = [
			'tab'         => 'content',
			'group'       => 'event_query',
			'label'       => esc_html__( 'Event categories', 'events-calendar-for-bricks' ),
			'type'        => 'select',
			'options'     => $event_category_options,
			'multiple'    => true,
			'placeholder' => esc_html__( 'All categories', 'events-calendar-for-bricks' ),
		];

		$element->controls['event_time_mode'] = [
			'tab'     => 'content',
			'group'   => 'event_query',
			'label'   => esc_html__( 'Events time', 'events-calendar-for-bricks' ),
			'type'    => 'select',
			'options' => [
				'all'     => esc_html__( 'All', 'events-calendar-for-bricks' ),
				'between' => esc_html__( 'Between date range', 'events-calendar-for-bricks' ),
			],
			'inline'  => true,
			'default' => 'all',
		];

		$element->controls['event_range_start'] = [
			'tab'      => 'content',
			'group'    => 'event_query',
			'label'    => esc_html__( 'Range start', 'events-calendar-for-bricks' ),
			'type'     => 'datepicker',
			'required' => [ 'event_time_mode', '=', 'between' ],
		];

		$element->controls['event_range_end'] = [
			'tab'      => 'content',
			'group'    => 'event_query',
			'label'    => esc_html__( 'Range end', 'events-calendar-for-bricks' ),
			'type'     => 'datepicker',
			'required' => [ 'event_time_mode', '=', 'between' ],
		];

		$element->controls['posts_per_page'] = [
			'tab'         => 'content',
			'group'       => 'event_query',
			'label'       => esc_html__( 'Number of events', 'events-calendar-for-bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 100,
			'step'        => 1,
			'default'     => 10,
			'placeholder' => '10',
		];

		$element->controls['order'] = [
			'tab'     => 'content',
			'group'   => 'event_query',
			'label'   => esc_html__( 'Events order', 'events-calendar-for-bricks' ),
			'type'    => 'select',
			'options' => [
				'ASC'  => 'ASC',
				'DESC' => 'DESC',
			],
			'inline'  => true,
			'default' => 'ASC',
		];
	}

		public static function ecbb_register_messages_controls( $element ) {
		// â”€â”€ Dynamic Messages (content) â”€â”€
		$element->controls['no_events_text'] = [
			'tab'         => 'content',
			'group'       => 'dynamic_messages',
			'label'       => esc_html__( 'No events found text', 'events-calendar-for-bricks' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'No events found', 'events-calendar-for-bricks' ),
			'default'     => __( 'No events found', 'events-calendar-for-bricks' ),
		];

		$element->controls['no_events_tag'] = [
			'tab'     => 'content',
			'group'   => 'dynamic_messages',
			'label'   => esc_html__( 'HTML tag', 'events-calendar-for-bricks' ),
			'type'    => 'select',
			'options' => [
				'h1'  => 'h1',
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
				'p'   => 'p',
				'div' => 'div',
			],
			'default' => 'h2',
			'inline'  => true,
		];
	}

		public static function ecbb_get_event_category_options() {
		static $cached = null;
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$event_category_options = [];
		if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'tribe_events_cat' ) ) {
			$max_terms = (int) apply_filters( 'ecbb_event_category_options_limit', 500 );
			$event_terms = get_terms(
				[
					'taxonomy'   => 'tribe_events_cat',
					'hide_empty' => false,
					'number'     => max( 1, $max_terms ),
				]
			);
			if ( ! is_wp_error( $event_terms ) && is_array( $event_terms ) ) {
				foreach ( $event_terms as $event_term ) {
					if ( $event_term instanceof \WP_Term ) {
						$event_category_options[ $event_term->slug ] = esc_html( $event_term->name );
					}
				}
			}
		}

		$cached = $event_category_options;
		return $cached;
	}

	}
}
