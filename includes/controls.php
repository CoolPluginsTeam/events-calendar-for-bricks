<?php
/**
 * Bricks element controls for ecbb-events-loop (layouts → query → elements → messages → style).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Controls', false ) ) {

	final class ECBB_Controls {

		public static function ecbb_hover_part_types() {
			return class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_hover_part_types()
				: [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp', 'image' ];
		}

		/**
		 * Interactive parts that support hover (title, chips, buttons). Excludes image.
		 *
		 * @return string[]
		 */
		public static function ecbb_hover_interactive_types() {
			return class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_hover_interactive_types()
				: [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		/**
		 * @return array{0:string,1:string,2:mixed}
		 */
		public static function ecbb_req_show_hover_toggle() {
			return [ 'part', '=', self::ecbb_hover_part_types() ];
		}

		/**
		 * Bricks `required` rule: hover toggle is on.
		 *
		 * @return array{0:string,1:string,2:array<int|string|bool>}
		 */
		public static function ecbb_req_hover_on() {
			return [
				'ecbb_use_hover',
				'=',
				class_exists( 'ECBB_Markup', false )
					? \ECBB_Markup::ecbb_hover_on_values()
					: [ 'yes', true, 1, '1' ],
			];
		}

		/**
		 * @return array<int,array{0:string,1:string,2:mixed>>
		 */
		public static function ecbb_req_hover_controls() {
			return [
				self::ecbb_req_show_hover_toggle(),
				self::ecbb_req_hover_on(),
			];
		}

		/**
		 * Hover fields for interactive parts only (title, chips, buttons — not image).
		 *
		 * @return array<int,array{0:string,1:string,2:mixed>>
		 */
		public static function ecbb_req_hover_interactive() {
			return [
				[ 'part', '=', self::ecbb_hover_interactive_types() ],
				self::ecbb_req_hover_on(),
			];
		}

		/**
		 * Repeater field keys gated by hover eligibility (builder tab CSS + JS).
		 *
		 * @return string[]
		 */
		public static function ecbb_hover_field_keys() {
			return [
				'ecbb_use_hover',
				'ecbb_sep_hover',
				'ecbb_hover_color',
				'ecbb_hover_background',
				'ecbb_hover_text_decoration',
				'ecbb_hover_animation',
				'image_size_hover',
				'ecbb_image_object_align_hover',
			];
		}

		/**
		 * Button parts that support optional button chrome.
		 *
		 * @return string[]
		 */
		public static function ecbb_btn_part_types() {
			return [ 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		/**
		 * @return array<int,array{0:string,1:string,2:mixed>>
		 */
		public static function ecbb_req_btn_styled() {
			return [
				[ 'part', '=', self::ecbb_btn_part_types() ],
				[ 'btn_style', '=', true ],
			];
		}

		/**
		 * Repeater field keys in the border & padding accordion (builder tab CSS + JS).
		 *
		 * @return string[]
		 */
		public static function ecbb_btn_border_keys() {
			return [
				'btn_sep_border',
				'btn_border_type',
				'btn_border_width',
				'btn_border_color',
				'btn_padding',
				'btn_border_radius',
			];
		}

		/**
		 * Style tab — category badge on image (List Style 1).
		 *
		 * @return array<int,array{0:string,1:string,2:mixed}>
		 */
		public static function ecbb_req_shell_category_style_list1() {
			return [
				[ 'show_event_image', '!=', 'hide' ],
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '!=', 'style-2' ],
				[ 'list1_show_category_badge', '!=', 'hide' ],
			];
		}

		/**
		 * Style tab — category badge on image (Grid).
		 *
		 * @return array<int,array{0:string,1:string,2:mixed}>
		 */
		public static function ecbb_req_shell_category_style_grid() {
			return [
				[ 'show_event_image', '!=', 'hide' ],
				[ 'layout_template', '=', 'grid' ],
				[ 'grid_show_category_badge', '!=', 'hide' ],
			];
		}

		/**
		 * Style tab — date badge on image (List Style 2).
		 *
		 * @return array<int,array{0:string,1:string,2:mixed}>
		 */
		public static function ecbb_req_shell_date_badge_style() {
			return [
				[ 'show_event_image', '!=', 'hide' ],
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
				[ 'style2_show_date_badge', '!=', 'hide' ],
			];
		}

		/**
		 * Bricks `css` rule for a repeater sub-field (live builder preview + frontend).
		 *
		 * @param string $css_property CSS property or Bricks shorthand (e.g. font, typography).
		 * @param string $css_selector Optional selector relative to the repeater field target.
		 * @return array<int,array<string,string>>
		 */
		public static function ecbb_field_css( $css_property, $css_selector = '' ) {
			$css_rule = [ 'property' => (string) $css_property ];
			if ( $css_selector !== '' ) {
				$css_rule['selector'] = $css_selector;
			}
			return [ $css_rule ];
		}

		/**
		 * Repeater sub-fields for event parts (content + grouped style controls).
		 *
		 * @return array<string,array<string,mixed>>
		 */
		public static function ecbb_part_fields() {
			$date_format_options = class_exists( 'ECBB_Styles', false )
				? \ECBB_Styles::ecbb_date_options()
				: [];

		return [
			'part' => [
				'label'   => esc_html__( 'Part', 'events-calendar-for-bricks' ),
				'type'    => 'select',
				'options' => class_exists( 'ECBB_Styles', false )
					? \ECBB_Styles::ecbb_part_options()
					: [ 'title' => esc_html__( 'Title', 'events-calendar-for-bricks' ) ],
				'default' => 'title',
			],
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
				'required' => [ 'part', '=', 'date' ],
			],
			'venue_display' => [
				'label'    => esc_html__( 'Venue display', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					'full_details'   => esc_html__( 'Full venue details', 'events-calendar-for-bricks' ),
					'name_and_city'  => esc_html__( 'Venue name and city', 'events-calendar-for-bricks' ),
					'name_and_state' => esc_html__( 'Venue name and state', 'events-calendar-for-bricks' ),
					'name'           => esc_html__( 'Venue name only', 'events-calendar-for-bricks' ),
					'full_address' => esc_html__( 'Full address only', 'events-calendar-for-bricks' ),
					'street'       => esc_html__( 'Street', 'events-calendar-for-bricks' ),
					'city'         => esc_html__( 'City', 'events-calendar-for-bricks' ),
					'state'        => esc_html__( 'State / province', 'events-calendar-for-bricks' ),
					'zip'          => esc_html__( 'ZIP / postal', 'events-calendar-for-bricks' ),
					'country'      => esc_html__( 'Country', 'events-calendar-for-bricks' ),
					'phone'        => esc_html__( 'Phone', 'events-calendar-for-bricks' ),
					'website'      => esc_html__( 'Website', 'events-calendar-for-bricks' ),
					'map_link'     => esc_html__( 'Map link', 'events-calendar-for-bricks' ),
				],
				'default'  => 'full_details',
				'required' => [ 'part', '=', 'venue' ],
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
				'required' => [ 'part', '=', 'event_cost' ],
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
			'link' => [
				'label'    => esc_html__( 'Link title to event', 'events-calendar-for-bricks' ),
				'type'     => 'checkbox',
				'required' => [ 'part', '=', 'title' ],
			],
			'desc_source' => [
				'label'    => esc_html__( 'Description source', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					'auto'    => esc_html__( 'Auto (excerpt → content)', 'events-calendar-for-bricks' ),
					'excerpt' => esc_html__( 'Excerpt', 'events-calendar-for-bricks' ),
					'content' => esc_html__( 'Full content', 'events-calendar-for-bricks' ),
				],
				'default'  => 'auto',
				'required' => [ 'part', '=', 'description' ],
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
			'image_aspect_ratio' => [
				'label'    => esc_html__( 'Aspect ratio', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					''     => esc_html__( 'Default', 'events-calendar-for-bricks' ),
					'1/1'  => '1:1',
					'4/3'  => '4:3',
					'3/2'  => '3:2',
					'16/9' => '16:9',
					'21/9' => '21:9',
				],
				'default'  => '',
				'required' => [ 'part', '=', 'image' ],
			],
			'image_size' => [
				'label'       => esc_html__( 'Image size', 'events-calendar-for-bricks' ),
				'type'        => 'select',
				'options'     => class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_image_size_opts() : [ 'large' => 'large', 'full' => 'full' ],
				'default'     => '',
				'placeholder' => esc_html__( 'Default (large)', 'events-calendar-for-bricks' ),
				'required'    => [ 'part', '=', 'image' ],
			],
			'image_size_hover' => [
				'label'    => esc_html__( 'Image size (hover)', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => array_merge(
					[ '' => esc_html__( 'Same as default', 'events-calendar-for-bricks' ) ],
					class_exists( 'ECBB_Markup', false )
						? array_diff_key( \ECBB_Markup::ecbb_image_size_opts(), [ '' => true ] )
						: [ 'large' => 'large', 'full' => 'full' ]
				),
				'default'  => '',
				'required' => [
					[ 'part', '=', 'image' ],
					self::ecbb_req_hover_on(),
				],
			],
			'ecbb_image_object_align' => [
				'label'      => esc_html__( 'Image alignment', 'events-calendar-for-bricks' ),
				'type'       => 'select',
				'options'    => class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_image_align_opts() : [],
				'default'    => '',
				'responsive' => true,
				'required'   => [ 'part', '=', 'image' ],
			],
			'ecbb_image_object_align_hover' => [
				'label'    => esc_html__( 'Image alignment (hover)', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => array_merge(
					[ '' => esc_html__( 'Same as default', 'events-calendar-for-bricks' ) ],
					class_exists( 'ECBB_Markup', false )
						? array_diff_key( \ECBB_Markup::ecbb_image_align_opts(), [ '' => true ] )
						: []
				),
				'default'  => '',
				'required' => [
					[ 'part', '=', 'image' ],
					self::ecbb_req_hover_on(),
				],
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
				'css'         => self::ecbb_field_css(
					'width',
					'.ecbb-event__image, .ecbb-event__img-stack'
				),
			],
			'ecbb_image_height' => [
				'label'       => esc_html__( 'Image height', 'events-calendar-for-bricks' ),
				'type'        => 'text',
				'placeholder' => 'auto',
				'responsive'  => true,
				'required'    => [ 'part', '=', 'image' ],
				'css'         => self::ecbb_field_css(
					'height',
					'.ecbb-event__image'
				),
			],
			'ecbb_image_fit' => [
				'label'      => esc_html__( 'Image fit', 'events-calendar-for-bricks' ),
				'type'       => 'select',
				'options'    => [
					''           => esc_html__( 'Default', 'events-calendar-for-bricks' ),
					'cover'      => 'cover',
					'contain'    => 'contain',
					'fill'       => 'fill',
					'none'       => 'none',
					'scale-down' => 'scale-down',
				],
				'default'    => '',
				'responsive' => true,
				'required'   => [ 'part', '=', 'image' ],
				'css'        => self::ecbb_field_css(
					'object-fit',
					'.ecbb-event__image'
				),
			],
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
						? \ECBB_Styles::ecbb_repeater_type_selector()
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
			'btn_style' => [
				'label'    => esc_html__( 'Button styles', 'events-calendar-for-bricks' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => [ 'part', '=', self::ecbb_btn_part_types() ],
			],
			'btn_sep_border' => [
				'label'    => esc_html__( 'Border & padding', 'events-calendar-for-bricks' ),
				'type'     => 'separator',
				'required' => self::ecbb_req_btn_styled(),
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
				'required' => self::ecbb_req_btn_styled(),
			],
			'btn_border_width' => [
				'label'      => esc_html__( 'Border width', 'events-calendar-for-bricks' ),
				'type'       => 'number',
				'units'      => [ 'px' ],
				'unit'       => 'px',
				'placeholder' => '1',
				'responsive' => true,
				'required'   => self::ecbb_req_btn_styled(),
			],
			'btn_border_color' => [
				'label'       => esc_html__( 'Border color', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#cccccc',
				'responsive'  => true,
				'required'    => self::ecbb_req_btn_styled(),
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
				'required' => self::ecbb_req_btn_styled(),
				// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
				// Frontend: ecbb_button_decls() + build_parts_scoped_css(). Builder: ecbb-builder.js.
			],
			'btn_border_radius' => [
				'label'       => esc_html__( 'Border radius', 'events-calendar-for-bricks' ),
				'type'        => 'dimensions',
				'placeholder' => '0px',
				'responsive'  => true,
				'required'    => self::ecbb_req_btn_styled(),
				// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
				// Frontend: ecbb_button_decls() + build_parts_scoped_css(). Builder: ecbb-builder.js.
			],
			'ecbb_use_hover' => [
				'label'    => esc_html__( 'Enable hover Styling', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					'yes' => esc_html__( 'Yes', 'events-calendar-for-bricks' ),
					'no'  => esc_html__( 'No', 'events-calendar-for-bricks' ),
				],
				'default'  => 'yes',
				'rerender' => true,
				'required' => self::ecbb_req_show_hover_toggle(),
			],
			'ecbb_sep_hover' => [
				'type'     => 'separator',
				'label'    => esc_html__( 'Hover effects', 'events-calendar-for-bricks' ),
				'required' => self::ecbb_req_hover_controls(),
			],
			'ecbb_hover_color' => [
				'label'       => esc_html__( 'Hover color', 'events-calendar-for-bricks' ),
				'type'        => 'color',
				'placeholder' => '#000000',
				'required'    => self::ecbb_req_hover_controls(),
				'css'         => class_exists( 'ECBB_Styles', false )
					? self::ecbb_field_css(
						'color',
						\ECBB_Styles::ecbb_repeater_hover_color_selector()
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
				'required'    => self::ecbb_req_hover_interactive(),
				'css'         => class_exists( 'ECBB_Styles', false )
					? self::ecbb_field_css(
						'background-color',
						\ECBB_Styles::ecbb_repeater_hover_bg_selector()
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
				'required' => self::ecbb_req_hover_interactive(),
			],
			'ecbb_hover_animation' => [
				'label'    => esc_html__( 'Hover animation', 'events-calendar-for-bricks' ),
				'type'     => 'select',
				'options'  => [
					''              => esc_html__( 'None', 'events-calendar-for-bricks' ),
					'fade_in_up'    => esc_html__( 'Fade in up', 'events-calendar-for-bricks' ),
					'fade_in_right' => esc_html__( 'Fade in right', 'events-calendar-for-bricks' ),
					'fade_in_down'  => esc_html__( 'Fade in down', 'events-calendar-for-bricks' ),
					'fade_in_left'  => esc_html__( 'Fade in left', 'events-calendar-for-bricks' ),
					'zoom_in'       => esc_html__( 'Zoom in', 'events-calendar-for-bricks' ),
					'zoom_out'      => esc_html__( 'Zoom out', 'events-calendar-for-bricks' ),
				],
				'default'  => '',
				'required' => self::ecbb_req_hover_controls(),
			],
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

	/**
	 * @return array<string,string>
	 */
	private static function ecbb_get_event_category_options() {
		$event_category_options = [];
		if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'tribe_events_cat' ) ) {
			$event_terms = get_terms(
				[
					'taxonomy'   => 'tribe_events_cat',
					'hide_empty' => false,
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

		return $event_category_options;
	}

	/**
	 * Category badge control keys/rules per layout (content toggle + style tab chrome).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function ecbb_category_badge_layout_configs() {
		return [
			'list1' => [
				'toggle_key'      => 'list1_show_category_badge',
				'toggle_required' => [
					[ 'show_event_image', '=', 'show' ],
					[ 'layout_template', '=', 'list' ],
					[ 'list_item_style', '=', 'style-1' ],
				],
				'sep_key'         => 'ecbb_sep_style_overlays',
				'background_key'  => 'ecbb_shell_category_background',
				'typography_key'  => 'ecbb_shell_category_typography',
				'style_required'  => self::ecbb_req_shell_category_style_list1(),
				'bg_description'  => esc_html__( 'List Style 1 — category pill over the featured image.', 'events-calendar-for-bricks' ),
			],
			'grid'  => [
				'toggle_key'      => 'grid_show_category_badge',
				'toggle_required' => [
					[ 'show_event_image', '=', 'show' ],
					[ 'layout_template', '=', 'grid' ],
				],
				'sep_key'         => 'ecbb_sep_style_overlays_grid',
				'background_key'  => 'ecbb_shell_category_background_grid',
				'typography_key'  => 'ecbb_shell_category_typography_grid',
				'style_required'  => self::ecbb_req_shell_category_style_grid(),
				'bg_description'  => esc_html__( 'Grid — category pill over the featured image.', 'events-calendar-for-bricks' ),
			],
		];
	}

	/**
	 * Content-tab show/hide toggle for a layout's category badge on the image.
	 *
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @param string            $layout list1|grid
	 * @return void
	 */
	private static function ecbb_register_category_badge_layout_toggle( $element, $layout ) {
		$configs = self::ecbb_category_badge_layout_configs();
		if ( ! isset( $configs[ $layout ] ) ) {
			return;
		}

		$c = $configs[ $layout ];
		$element->controls[ $c['toggle_key'] ] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Show category on image', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => [
				'show' => esc_html__( 'Show', 'events-calendar-for-bricks' ),
				'hide' => esc_html__( 'Hide', 'events-calendar-for-bricks' ),
			],
			'default'  => 'show',
			'inline'   => true,
			'rerender' => true,
			'required' => $c['toggle_required'],
		];
	}

	/**
	 * Style-tab separator, background, and typography for a layout's category badge.
	 *
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @param string            $layout list1|grid
	 * @return void
	 */
	private static function ecbb_register_category_badge_style_controls( $element, $layout ) {
		$configs = self::ecbb_category_badge_layout_configs();
		if ( ! isset( $configs[ $layout ] ) ) {
			return;
		}

		$c = $configs[ $layout ];

		$element->controls[ $c['sep_key'] ] = [
			'tab'      => 'style',
			'group'    => 'image_overlays',
			'label'    => esc_html__( 'Category badge (on image)', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => $c['style_required'],
		];

		$element->controls[ $c['background_key'] ] = [
			'tab'         => 'style',
			'group'       => 'image_overlays',
			'label'       => esc_html__( 'Category background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'responsive'  => true,
			'description' => $c['bg_description'],
			'required'    => $c['style_required'],
			'css'         => [
				[
					'property' => '--ecbb-shell-cat-bg',
					'selector' => '&',
				],
			],
		];

		$element->controls[ $c['typography_key'] ] = [
			'tab'        => 'style',
			'group'      => 'image_overlays',
			'label'      => esc_html__( 'Category typography', 'events-calendar-for-bricks' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => $c['style_required'],
			'css'        => [
				[
					'property' => 'typography',
					'selector' => '& .event-badge--blue',
				],
			],
		];
	}

	/**
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @return void
	 */
	private static function ecbb_register_layout_controls( $element ) {
		$element->controls['layout_template'] = [
			'tab'     => 'content',
			'group'   => 'layouts',
			'label'   => esc_html__( 'Template', 'events-calendar-for-bricks' ),
			'type'    => 'select',
			'options' => [
				'list' => esc_html__( 'List', 'events-calendar-for-bricks' ),
				'grid' => esc_html__( 'Grid', 'events-calendar-for-bricks' ),
			],
			'inline'  => true,
			'default' => 'list',
		];

		$element->controls['list_item_style'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'List style', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => [
				'style-1' => esc_html__( 'Style 1', 'events-calendar-for-bricks' ),
				'style-2' => esc_html__( 'Style 2', 'events-calendar-for-bricks' ),
			],
			'default'  => 'style-1',
			'required' => [ 'layout_template', '=', 'list' ],
		];

		$element->controls['show_event_image'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Show featured image', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => [
				'show' => esc_html__( 'Show', 'events-calendar-for-bricks' ),
				'hide' => esc_html__( 'Hide', 'events-calendar-for-bricks' ),
			],
			'default'  => 'show',
			'inline'   => true,
			'rerender' => true,
		];

		self::ecbb_register_category_badge_layout_toggle( $element, 'list1' );
		self::ecbb_register_category_badge_layout_toggle( $element, 'grid' );

		$date_column_order_options = [
			'month_day' => esc_html__( 'Month above, date below', 'events-calendar-for-bricks' ),
			'day_month' => esc_html__( 'Date above, month below', 'events-calendar-for-bricks' ),
		];

		$element->controls['list1_date_column_order'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Date column order', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => $date_column_order_options,
			'default'  => 'day_month',
			'inline'   => true,
			'rerender' => true,
			'required' => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
				[ 'show_event_image', '!=', 'hide' ],
			],
		];

		$element->controls['style2_show_date_badge'] = [
			'tab'         => 'content',
			'group'       => 'layouts',
			'label'       => esc_html__( 'Show date badge on image', 'events-calendar-for-bricks' ),
			'type'        => 'select',
			'options'     => [
				'show' => esc_html__( 'Show', 'events-calendar-for-bricks' ),
				'hide' => esc_html__( 'Hide', 'events-calendar-for-bricks' ),
			],
			'default'     => 'show',
			'inline'      => true,
			'rerender'    => true,
			'required'    => [
				[ 'show_event_image', '!=', 'hide' ],
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			],
		];

		$element->controls['style2_date_badge_order'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Date column order', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => $date_column_order_options,
			'default'  => 'month_day',
			'inline'   => true,
			'rerender' => true,
			'required' => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
				[ 'show_event_image', '!=', 'hide' ],
				[ 'style2_show_date_badge', '!=', 'hide' ],
			],
		];

		$element->controls['grid_cols'] = [
			'tab'         => 'content',
			'group'       => 'layouts',
			'label'       => esc_html__( 'Grid columns', 'events-calendar-for-bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'step'        => 1,
			'default'     => 3,
			'placeholder' => '3',
			'responsive'  => true,
			'rerender'    => true,
			'description' => esc_html__( 'Use device icons for different devices.', 'events-calendar-for-bricks' ),
			'required'    => [ 'layout_template', '=', 'grid' ],
			'css'         => [
				[
					'property' => '--ecbb-grid-cols',
					'selector' => '.ecbb-ev__list--grid',
				],
			],
		];

		$element->controls['item_gap'] = [
			'tab'         => 'content',
			'group'       => 'layouts',
			'label'       => esc_html__( 'Gap between events', 'events-calendar-for-bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'step'        => 1,
			'placeholder' => '24',
			'default'     => 24,
			'units'       => [
				'px'  => 'px',
				'rem' => 'rem',
				'em'  => 'em',
			],
			'unit'        => 'px',
			'responsive'  => true,
			'rerender'    => true,
			'description' => esc_html__( 'Space between each event card. Click the device icon on this control for tablet/mobile.', 'events-calendar-for-bricks' ),
			'css'         => [
				[
					'property' => '--ecbb-gap',
					'selector' => '.ecbb-ev__list',
				],
			],
		];
	}

	/**
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @param array<string,string> $event_category_options
	 * @return void
	 */
	private static function ecbb_register_query_controls( $element, array $event_category_options ) {
		// ── Events Query ──
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

	/**
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @return void
	 */
	private static function ecbb_register_messages_controls( $element ) {
		// ── Dynamic Messages (content) ──
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

	/**
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @return void
	 */
	private static function ecbb_register_style_controls( $element ) {
		// ── Style tab: event card + featured image overlay chrome ──
		$element->controls['ecbb_card_background'] = [
			'tab'         => 'style',
			'group'       => 'layout_appearance',
			'label'       => esc_html__( 'Event card background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'responsive'  => true,
			'description' => esc_html__( 'Background for each event card in the active template (List Style 1, List Style 2, or Grid).', 'events-calendar-for-bricks' ),
			'css'         => [
				[
					'property' => '--ecbb-card-bg',
					'selector' => '&',
				],
			],
		];

		self::ecbb_register_category_badge_style_controls( $element, 'list1' );
		self::ecbb_register_category_badge_style_controls( $element, 'grid' );

		$element->controls['ecbb_sep_style_date_badge'] = [
			'tab'      => 'style',
			'group'    => 'image_overlays',
			'label'    => esc_html__( 'Date badge (on image)', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => self::ecbb_req_shell_date_badge_style(),
		];

		$element->controls['ecbb_shell_date_background'] = [
			'tab'         => 'style',
			'group'       => 'image_overlays',
			'label'       => esc_html__( 'Date badge background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'responsive'  => true,
			'description' => esc_html__( 'List Style 2 — date badge over the featured image.', 'events-calendar-for-bricks' ),
			'required'    => self::ecbb_req_shell_date_badge_style(),
			'css'         => [
				[
					'property' => '--ecbb-shell-date-bg',
					'selector' => '&',
				],
			],
		];

		$element->controls['ecbb_shell_date_typography'] = [
			'tab'        => 'style',
			'group'      => 'image_overlays',
			'label'      => esc_html__( 'Date badge typography', 'events-calendar-for-bricks' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => self::ecbb_req_shell_date_badge_style(),
			'css'        => [
				[
					'property' => 'typography',
					'selector' => '& .ecbb-event-card__date-badge, & .ecbb-event-card__date-badge span, & .ecbb-event-card__date-badge strong',
				],
			],
		];
	}

	/**
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @return void
	 */
	private static function ecbb_register_parts_repeaters( $element ) {
		$part_repeater_fields = self::ecbb_part_fields();

		$element->controls['parts_style1'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'selector'      => 'fieldId',
			'label'         => esc_html__( 'Event parts — Style 1 (list)', 'events-calendar-for-bricks' ),
			'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'events-calendar-for-bricks' ),
			'titleProperty' => 'part',
			'placeholder'   => esc_html__( 'Add event part', 'events-calendar-for-bricks' ),
			'rerender'      => true,
			'required'      => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			],
			'default'       => class_exists( 'ECBB_List_1', false )
				? \ECBB_List_1::ecbb_default_parts()
				: [],
			'fields'        => $part_repeater_fields,
		];

		$element->controls['parts_style2'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'selector'      => 'fieldId',
			'label'         => esc_html__( 'Event parts — Style 2 (list)', 'events-calendar-for-bricks' ),
			'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'events-calendar-for-bricks' ),
			'titleProperty' => 'part',
			'placeholder'   => esc_html__( 'Add event part', 'events-calendar-for-bricks' ),
			'rerender'      => true,
			'required'      => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			],
			'default'       => class_exists( 'ECBB_List_2', false )
				? \ECBB_List_2::ecbb_default_parts()
				: [],
			'fields'        => $part_repeater_fields,
		];

		$element->controls['parts_grid'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'selector'      => 'fieldId',
			'label'         => esc_html__( 'Event parts — Grid', 'events-calendar-for-bricks' ),
			'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'events-calendar-for-bricks' ),
			'titleProperty' => 'part',
			'placeholder'   => esc_html__( 'Add event part', 'events-calendar-for-bricks' ),
			'rerender'      => true,
			'required'      => [ 'layout_template', '=', 'grid' ],
			'default'       => class_exists( 'ECBB_Grid', false )
				? \ECBB_Grid::ecbb_default_parts()
				: [],
			'fields'        => $part_repeater_fields,
		];
	}

	/**
	 * Register all element controls on the Bricks element instance.
	 *
	 * @param \ECBB\ECBB_Widget $element Element instance.
	 * @return void
	 */
	public static function ecbb_register_controls( $element ) {
		$event_category_options = self::ecbb_get_event_category_options();

		self::ecbb_register_layout_controls( $element );
		self::ecbb_register_query_controls( $element, $event_category_options );
		self::ecbb_register_messages_controls( $element );
		self::ecbb_register_style_controls( $element );
		self::ecbb_register_parts_repeaters( $element );
	}

	}

}

