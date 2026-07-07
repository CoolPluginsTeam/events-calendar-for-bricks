<?php
/**
 * ECBB_Part_Fields service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Part_Fields', false ) ) {

	final class ECBB_Part_Fields {

			private static function ecbb_part_button_fields() {
			return [
			'btn_style' => [
				'label'    => esc_html__( 'Button styles', 'events-calendar-for-bricks' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => [ 'part', '=', ECBB_Hover_Controls::ecbb_btn_part_types() ],
			],
			'btn_sep_border' => [
				'label'    => esc_html__( 'Border & padding', 'events-calendar-for-bricks' ),
				'type'     => 'separator',
				'required' => ECBB_Hover_Controls::ecbb_req_btn_styled(),
			],
			'btn_border_type' => [
				'label'    => esc_html__( 'Border type', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					'solid'  => esc_html__( 'Solid', 'events-calendar-for-bricks' ),
					'dashed' => esc_html__( 'Dashed', 'events-calendar-for-bricks' ),
					'dotted' => esc_html__( 'Dotted', 'events-calendar-for-bricks' ),
					'double' => esc_html__( 'Double', 'events-calendar-for-bricks' ),
					'none'   => esc_html__( 'None', 'events-calendar-for-bricks' ),
				],
				'default'  => 'solid',
				'required' => ECBB_Hover_Controls::ecbb_req_btn_styled(),
			],
			'btn_border_width' => [
				'label'      => esc_html__( 'Border width', 'events-calendar-for-bricks' ),
				'type'       => 'number',
				'units'      => [ 'px' ],
				'unit'       => 'px',
				'placeholder' => '1',
				'responsive' => true,
				'required'   => ECBB_Hover_Controls::ecbb_req_btn_styled(),
			],
			'btn_border_color' => [
				'label'       => esc_html__( 'Border color', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#cccccc',
				'responsive'  => true,
				'required'    => ECBB_Hover_Controls::ecbb_req_btn_styled(),
			],
			'btn_padding' => [
				'label'    => esc_html__( 'Button padding', 'events-calendar-for-bricks' ),
				'type'     => 'spacing',
				'default'  => [
					'top'    => '10px',
					'right'  => '14px',
					'bottom' => '10px',
					'left'   => '14px',
				],
				'required' => ECBB_Hover_Controls::ecbb_req_btn_styled(),
				// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
				// Frontend: ecbb_button_decls() + build_parts_scoped_css(). Builder: assets/js/builder/*.js.
			],
			'btn_border_radius' => [
				'label'       => esc_html__( 'Border radius', 'events-calendar-for-bricks' ),
				'type'        => 'dimensions',
				'placeholder' => '0px',
				'responsive'  => true,
				'required'    => ECBB_Hover_Controls::ecbb_req_btn_styled(),
				// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
				// Frontend: ecbb_button_decls() + build_parts_scoped_css(). Builder: assets/js/builder/*.js.
			],
			];
		}

			private static function ecbb_part_hover_fields() {
			return [
			'ecbb_use_hover' => [
				'label'    => esc_html__( 'Enable hover Styling', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					'yes' => esc_html__( 'Yes', 'events-calendar-for-bricks' ),
					'no'  => esc_html__( 'No', 'events-calendar-for-bricks' ),
				],
				'default'  => 'yes',
				'rerender' => true,
				'required' => ECBB_Hover_Controls::ecbb_req_show_hover_toggle(),
			],
			'ecbb_sep_hover' => [
				'type'     => 'separator',
				'label'    => esc_html__( 'Hover effects', 'events-calendar-for-bricks' ),
				'required' => ECBB_Hover_Controls::ecbb_req_hover_controls(),
			],
			'ecbb_hover_color' => [
				'label'       => esc_html__( 'Hover color', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#000000',
				'required'    => ECBB_Hover_Controls::ecbb_req_hover_controls(),
				'css'         => class_exists( 'ECBB_Styles', false )
					? self::ecbb_field_css(
						'color',
						\ECBB_Styles::ecbb_repeater_hover_selector()
					)
					: self::ecbb_field_css(
						'color',
						'&, & .ecbb-event__link:hover, & a.event-button:hover, & a.ecbb-event-card__button:hover'
					),
			],
			'ecbb_hover_background' => [
				'label'       => esc_html__( 'Hover background', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#666666',
				'required'    => ECBB_Hover_Controls::ecbb_req_hover_interactive(),
				'css'         => class_exists( 'ECBB_Styles', false )
					? self::ecbb_field_css(
						'background-color',
						\ECBB_Styles::ecbb_repeater_hover_selector()
					)
					: self::ecbb_field_css(
						'background-color',
						'&, & .ecbb-event__link:hover, & a.event-button:hover, & a.ecbb-event-card__button:hover'
					),
			],
			'ecbb_hover_text_decoration' => [
				'label'    => esc_html__( 'Text decoration (hover)', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					''             => esc_html__( 'Default', 'events-calendar-for-bricks' ),
					'none'         => esc_html__( 'None', 'events-calendar-for-bricks' ),
					'underline'    => esc_html__( 'Underline', 'events-calendar-for-bricks' ),
					'overline'     => esc_html__( 'Overline', 'events-calendar-for-bricks' ),
					'line-through' => esc_html__( 'Line through', 'events-calendar-for-bricks' ),
				],
				'default'  => '',
				'required' => ECBB_Hover_Controls::ecbb_req_hover_interactive(),
			],
			'ecbb_hover_animation' => [
				'label'    => esc_html__( 'Hover animation', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => ECBB_Hover_Controls::ecbb_hover_animation_options( false, true ),
				'default'  => '',
				'required' => ECBB_Hover_Controls::ecbb_req_hover_controls(),
			],
			];
		}

			private static function ecbb_part_image_fields() {
			return [
				'image_size' => [
					'label'       => esc_html__( 'Image size', 'events-calendar-for-bricks' ),
					'type'        => 'select',
					'options'     => class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_image_size_opts() : [ 'large' => 'large', 'full' => 'full' ],
					'default'     => '',
					'placeholder' => esc_html__( 'Default (large)', 'events-calendar-for-bricks' ),
					'required'    => [ 'part', '=', 'image' ],
				],
				'image_link' => [
					'label'    => esc_html__( 'Link image to event', 'events-calendar-for-bricks' ),
					'type'     => 'checkbox',
					'default'  => true,
					'required' => [ 'part', '=', 'image' ],
				],
				'ecbb_image_width' => [
					'label'       => esc_html__( 'Image width', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => '100%',
					'responsive'  => true,
					'required'    => [ 'part', '=', 'image' ],
					'css'         => self::ecbb_field_css( 'width', '.ecbb-event__image' ),
				],
				'ecbb_image_height' => [
					'label'       => esc_html__( 'Image height', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => 'auto',
					'responsive'  => true,
					'required'    => [ 'part', '=', 'image' ],
					'css'         => self::ecbb_field_css( 'height', '.ecbb-event__image' ),
				],
			];
		}

			private static function ecbb_part_display_fields( array $venue_parts, array $cost_parts ) {
			return [
				'venue_display' => [
					'label'    => esc_html__( 'Venue display', 'events-calendar-for-bricks' ),
					'type'     => 'select',
					'options'  => [
						'full_details'   => esc_html__( 'Full venue details', 'events-calendar-for-bricks' ),
						'name_and_city'  => esc_html__( 'Venue name and city', 'events-calendar-for-bricks' ),
						'name_and_state' => esc_html__( 'Venue name and state', 'events-calendar-for-bricks' ),
						'name'           => esc_html__( 'Venue name only', 'events-calendar-for-bricks' ),
						'full_address'   => esc_html__( 'Full address only', 'events-calendar-for-bricks' ),
						'street'         => esc_html__( 'Street', 'events-calendar-for-bricks' ),
						'city'           => esc_html__( 'City', 'events-calendar-for-bricks' ),
						'state'          => esc_html__( 'State / province', 'events-calendar-for-bricks' ),
						'zip'            => esc_html__( 'ZIP / postal', 'events-calendar-for-bricks' ),
						'country'        => esc_html__( 'Country', 'events-calendar-for-bricks' ),
						'phone'          => esc_html__( 'Phone', 'events-calendar-for-bricks' ),
						'website'        => esc_html__( 'Website', 'events-calendar-for-bricks' ),
						'map_link'       => esc_html__( 'Map link', 'events-calendar-for-bricks' ),
					],
					'default'  => 'full_details',
					'required' => [ 'part', '=', $venue_parts ],
				],
				'organizer_display' => [
					'label'    => esc_html__( 'Organizer display', 'events-calendar-for-bricks' ),
					'type'     => 'select',
					'options'  => [
						'full_details' => esc_html__( 'Full organizer details', 'events-calendar-for-bricks' ),
						'name'         => esc_html__( 'Organizer name only', 'events-calendar-for-bricks' ),
						'email'        => esc_html__( 'Email', 'events-calendar-for-bricks' ),
						'phone'        => esc_html__( 'Phone', 'events-calendar-for-bricks' ),
						'website'      => esc_html__( 'Website', 'events-calendar-for-bricks' ),
					],
					'default'  => 'full_details',
					'required' => [ 'part', '=', 'organizer' ],
				],
				'cost_currency' => [
					'label'    => esc_html__( 'Cost currency', 'events-calendar-for-bricks' ),
					'type'     => 'select',
					'options'  => class_exists( 'ECBB_Markup', false )
						? \ECBB_Markup::ecbb_cost_currency_opts()
						: [
							'default' => esc_html__( 'Site default', 'events-calendar-for-bricks' ),
							'none'    => esc_html__( 'No currency symbol', 'events-calendar-for-bricks' ),
						],
					'default'  => 'default',
					'required' => [ 'part', '=', $cost_parts ],
				],
				'event_link_display' => [
					'label'    => esc_html__( 'Link type', 'events-calendar-for-bricks' ),
					'type'     => 'select',
					'options'  => [
						'website'  => esc_html__( 'Event website', 'events-calendar-for-bricks' ),
						'phone'    => esc_html__( 'Event phone', 'events-calendar-for-bricks' ),
						'map_link' => esc_html__( 'Map link', 'events-calendar-for-bricks' ),
					],
					'default'  => 'website',
					'required' => [ 'part', '=', 'event_link' ],
				],
			];
		}

		public static function ecbb_part_fields_for_style2() {
		$options = class_exists( 'ECBB_Styles', false )
			? \ECBB_Styles::ecbb_part_options_style2()
			: [];
		$fields = self::ecbb_part_fields( $options );
		$out    = [];

		foreach ( $fields as $key => $field ) {
			$out[ $key ] = $field;
			if ( $key === 'ecbb_background' ) {
				foreach ( self::ecbb_part_fields_style2_meta_icon() as $meta_key => $meta_field ) {
					$out[ $meta_key ] = $meta_field;
				}
			}
		}

		return $out;
	}

			public static function ecbb_part_fields( ?array $part_options = null ) {
			return array_merge(
				self::ecbb_part_content_fields( $part_options ),
				self::ecbb_part_typography_fields(),
				self::ecbb_part_button_fields(),
				self::ecbb_part_hover_fields(),
				self::ecbb_part_image_border_fields()
			);
		}

		public static function ecbb_part_fields_style2_meta_icon() {
		return [
			'ecbb_sep_meta_icon' => [
				'type'     => 'separator',
				'label'    => esc_html__( 'Meta icon', 'events-calendar-for-bricks' ),
				'required' => [ 'part', '=', ECBB_Hover_Controls::ecbb_style2_meta_icon_ui_parts() ],
			],
			'ecbb_meta_icon_color' => [
				'label'       => esc_html__( 'Icon color', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#0d55d8',
				'responsive'  => true,
				'rerender'    => true,
				'required'    => [ 'part', '=', ECBB_Hover_Controls::ecbb_style2_meta_icon_ui_parts() ],
			],
			'ecbb_meta_icon_background' => [
				'label'       => esc_html__( 'Icon background', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#eaf2ff',
				'responsive'  => true,
				'rerender'    => true,
				'required'    => [ 'part', '=', ECBB_Hover_Controls::ecbb_style2_meta_icon_ui_parts() ],
			],
		];
	}

			private static function ecbb_part_text_fields() {
			return [
				'link' => [
					'label'    => esc_html__( 'Link title to event', 'events-calendar-for-bricks' ),
					'type'     => 'checkbox',
					'required' => [ 'part', '=', 'title' ],
				],
				'tickets_link_text' => [
					'label'       => esc_html__( 'Tickets link text', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Tickets', 'events-calendar-for-bricks' ),
					'default'     => __( 'Tickets', 'events-calendar-for-bricks' ),
					'required'    => [ 'part', '=', 'event_tickets' ],
				],
				'rsvp_link_text' => [
					'label'       => esc_html__( 'RSVP link text', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'RSVP', 'events-calendar-for-bricks' ),
					'default'     => __( 'RSVP', 'events-calendar-for-bricks' ),
					'required'    => [ 'part', '=', 'event_rsvp' ],
				],
				'terms_separator' => [
					'label'       => esc_html__( 'Separator', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => ', ',
					'default'     => ', ',
					'required'    => [ 'part', '=', 'tags' ],
				],
				'detail_link_text' => [
					'label'       => esc_html__( 'Link label', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Open link', 'events-calendar-for-bricks' ),
					'required'    => [ 'part', '=', 'event_link' ],
				],
				'read_more_text' => [
					'label'       => esc_html__( 'Read more text', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'View Details', 'events-calendar-for-bricks' ),
					'required'    => [
						[ 'part', '=', 'read_more' ],
					],
				],
			];
		}

			private static function ecbb_part_typography_fields() {
			return [
			'ecbb_sep_style' => [
				'type'  => 'separator',
				'label' => esc_html__( 'Style', 'events-calendar-for-bricks' ),
			],
			'ecbb_typography' => [
				'label'      => esc_html__( 'Typography', 'events-calendar-for-bricks' ),
				'type'       => 'typography',
				'exclude'    => [ 'text-align' ],
				'responsive' => true,
				'required'   => [ 'part', '!=', 'image' ],
				'rerender'   => true,
				'css'        => class_exists( 'ECBB_Styles', false )
					? \ECBB_Styles::ecbb_repeater_type_css()
					: self::ecbb_field_css(
						'typography',
						'&, & .ecbb-event__term-chip, & .ecbb-event__link, & .ecbb-event__term'
					),
			],
			'ecbb_text_align' => [
				'label'      => esc_html__( 'Text align', 'events-calendar-for-bricks' ),
				'type'     => 'text-align',
				'responsive' => true,
				'required'   => [ 'part', '!=', 'image' ],
				'rerender'   => true,
				'css'        => self::ecbb_field_css(
					'text-align',
					class_exists( 'ECBB_Styles', false )
						? \ECBB_Styles::ecbb_repeater_align_selector()
						: '&'
				),
			],
			'ecbb_background' => [
				'label'       => esc_html__( 'Background', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => 'transparent',
				'responsive'  => true,
				'css'         => self::ecbb_field_css( 'background-color', '&' ),
			],
			'ecbb_margin' => [
				'label' => esc_html__( 'Margin', 'events-calendar-for-bricks' ),
				'type'  => 'spacing',
				'css'   => self::ecbb_field_css( 'margin' ),
			],
			'ecbb_padding' => [
				'label' => esc_html__( 'Padding', 'events-calendar-for-bricks' ),
				'type'  => 'spacing',
				'css'   => self::ecbb_field_css( 'padding' ),
			],
			];
		}

			public static function ecbb_part_fields_for_style1() {
			$options = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_part_options_style1()
				: [];

			return self::ecbb_part_fields( $options );
		}

			private static function ecbb_part_date_fields( array $date_parts, array $date_format_options ) {
			return [
				'date_display' => [
					'label'    => esc_html__( 'Visibility', 'events-calendar-for-bricks' ),
					'type'     => 'select',
					'options'  => [
						'day_time_range' => esc_html__( 'Time range', 'events-calendar-for-bricks' ),
						'range'          => esc_html__( 'Date range', 'events-calendar-for-bricks' ),
						'date'           => esc_html__( 'Date only', 'events-calendar-for-bricks' ),
						'time'           => esc_html__( 'Time only', 'events-calendar-for-bricks' ),
						'day'            => esc_html__( 'Day name', 'events-calendar-for-bricks' ),
					],
					'default'  => 'day_time_range',
					'rerender' => true,
					'required' => [ 'part', '=', $date_parts ],
				],
				'date_format_preset' => [
					'label'       => esc_html__( 'Date Format', 'events-calendar-for-bricks' ),
					'type'        => 'select',
					'options'     => $date_format_options,
					'default'     => '',
					'placeholder' => esc_html__( 'Default', 'events-calendar-for-bricks' ),
					'required'    => [
						[ 'part', '=', 'date' ],
						[ 'date_display', '=', [ 'date', 'range' ] ],
					],
				],
				'date_format_custom' => [
					'label'       => esc_html__( 'Custom PHP format', 'events-calendar-for-bricks' ),
					'type'        => 'text',
					'placeholder' => 'F j, Y',
					'required'    => [
						[ 'part', '=', 'date' ],
						[ 'date_display', '=', [ 'date', 'range' ] ],
						[ 'date_format_preset', '=', 'custom' ],
					],
				],
			];
		}

			private static function ecbb_part_content_fields( ?array $part_options = null ) {
			$date_format_options = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_date_options()
				: [];

			if ( $part_options === null ) {
				$part_options = class_exists( 'ECBB_Styles', false )
					? \ECBB_Styles::ecbb_part_options_shared()
					: [ 'title' => esc_html__( 'Title', 'events-calendar-for-bricks' ) ];
			}

			$venue_parts = [ 'venue' ];
			$date_parts  = [ 'date' ];
			$cost_parts  = [ 'event_cost' ];
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$venue_parts = array_merge( $venue_parts, \ECBB_Styles::ecbb_meta_combo_slugs_with_segment( 'venue' ) );
				$date_parts  = array_merge( $date_parts, \ECBB_Styles::ecbb_meta_combo_slugs_with_segment( 'time' ) );
				$cost_parts  = array_merge( $cost_parts, \ECBB_Styles::ecbb_meta_combo_slugs_with_segment( 'cost' ) );
			}

			return array_merge(
				self::ecbb_part_selector_fields( $part_options ),
				self::ecbb_part_date_fields( $date_parts, $date_format_options ),
				self::ecbb_part_display_fields( $venue_parts, $cost_parts ),
				self::ecbb_part_text_fields(),
				self::ecbb_part_image_fields()
			);
		}

			private static function ecbb_part_image_border_fields() {
			return [
			'ecbb_image_border' => [
				'label'      => esc_html__( 'Image border', 'events-calendar-for-bricks' ),
				'type'       => 'border',
				'responsive' => true,
				'required'   => [ 'part', '=', 'image' ],
				'css'        => self::ecbb_field_css(
					'border',
					'.ecbb-event__image'
				),
			],
			'ecbb_image_radius' => [
				'label'       => esc_html__( 'Image radius', 'events-calendar-for-bricks' ),
				'type'        => 'dimensions',
				'placeholder' => '0px',
				'responsive'  => true,
				'required'    => [ 'part', '=', 'image' ],
				'css'         => self::ecbb_field_css(
					'border-radius',
					'.ecbb-event__image'
				),
			],
			];
		}

			private static function ecbb_part_selector_fields( array $part_options ) {
			return [
				'part' => [
					'label'   => esc_html__( 'Part', 'events-calendar-for-bricks' ),
					'type'    => 'select',
					'options' => $part_options,
					'default' => 'title',
				],
			];
		}

			public static function ecbb_field_css( $css_property, $css_selector = '' ) {
			$css_rule = [ 'property' => (string) $css_property ];
			if ( $css_selector !== '' ) {
				$css_rule['selector'] = $css_selector;
			}
			return [ $css_rule ];
		}

	}
}
