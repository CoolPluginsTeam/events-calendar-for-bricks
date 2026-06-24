<?php
/**
 * Bricks element controls for ecbb-events-loop (layouts → query → elements → messages).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string[]
 */

if ( ! class_exists( 'ECBB_Controls', false ) ) {

	final class ECBB_Controls {

	public static function ecbb_repeater_hover_part_slugs() {
	return function_exists( 'ecbb_event_part_types_with_hover_style_controls' )
		? ecbb_event_part_types_with_hover_style_controls()
		: [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp', 'image' ];
}

/**
 * Interactive parts that support hover (title, chips, buttons). Excludes image.
 *
 * @return string[]
 */

	public static function ecbb_repeater_interactive_hover_part_slugs() {
	return [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed}>|array{0:string,1:string,2:mixed}
 */

	public static function ecbb_repeater_required_interactive_hover() {
	return [ 'part', '=', self::ecbb_repeater_interactive_hover_part_slugs() ];
}

/**
 * @return array{0:string,1:string,2:mixed}
 */

	public static function ecbb_repeater_required_hover_toggle_visible() {
	return [ 'part', '=', self::ecbb_repeater_hover_part_slugs() ];
}

/**
 * Bricks `required` rule: hover toggle is on.
 *
 * @return array{0:string,1:string,2:array<int|string|bool>}
 */

	public static function ecbb_repeater_required_hover_on() {
	return [
		'ecbb_use_hover',
		'=',
		function_exists( 'ecbb_hover_toggle_on_values' )
			? ecbb_hover_toggle_on_values()
			: [ 'yes', true, 1, '1' ],
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_hover_style_group() {
	return [
		[ 'part', '=', self::ecbb_repeater_hover_part_slugs() ],
		self::ecbb_repeater_required_hover_on(),
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_hover_details() {
	return self::ecbb_repeater_required_hover_style_group();
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_hover_background() {
	return [
		[ 'part', '=', self::ecbb_repeater_hover_background_part_slugs() ],
		self::ecbb_repeater_required_hover_on(),
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_hover_text_decoration() {
	return [
		[ 'part', '=', self::ecbb_repeater_hover_text_decoration_part_slugs() ],
		self::ecbb_repeater_required_hover_on(),
	];
}

/**
 * Repeater field keys gated by hover eligibility (builder tab CSS + JS).
 *
 * @return string[]
 */

	public static function ecbb_repeater_hover_control_keys() {
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

	public static function ecbb_repeater_button_part_slugs() {
	return [ 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_btn_style_on() {
	return [
		[ 'part', '=', self::ecbb_repeater_button_part_slugs() ],
		[ 'btn_style', '=', true ],
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_btn_style_group() {
	return self::ecbb_repeater_required_btn_style_on();
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */

	public static function ecbb_repeater_required_btn_border_group() {
	return self::ecbb_repeater_required_btn_style_on();
}

/**
 * Repeater field keys in the border & padding accordion (builder tab CSS + JS).
 *
 * @return string[]
 */

	public static function ecbb_repeater_btn_border_control_keys() {
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
 * Repeater field keys in the button fill / text accordion.
 *
 * @return string[]
 */

	public static function ecbb_repeater_btn_style_control_keys() {
	return [
		'btn_sep_style',
		'btn_bg',
		'btn_text_color',
	];
}

/**
 * @return string[]
 */

	public static function ecbb_repeater_hover_text_decoration_part_slugs() {
	return self::ecbb_repeater_interactive_hover_part_slugs();
}

/**
 * Part types that show hover background.
 *
 * @return string[]
 */

	public static function ecbb_repeater_hover_background_part_slugs() {
	return self::ecbb_repeater_interactive_hover_part_slugs();
}

/**
 * @return array<int,array{0:string,1:string,2:mixed}>
 */

	public static function ecbb_repeater_required_inner_background() {
	return [ [ 'part', '=', 'title' ] ];
}

/**
 * Bricks `css` rule for a repeater sub-field (live builder preview + frontend).
 *
 * @param string $property CSS property or Bricks shorthand (e.g. font, typography).
 * @param string $selector Optional selector relative to the repeater field target.
 * @return array<int,array<string,string>>
 */

	public static function ecbb_repeater_control_css( $property, $selector = '' ) {
	$rule = [ 'property' => (string) $property ];
	if ( $selector !== '' ) {
		$rule['selector'] = $selector;
	}
	return [ $rule ];
}

/**
 * Repeater sub-fields for event parts (content + grouped style controls).
 *
 * @return array<string,array<string,mixed>>
 */

	public static function ecbb_get_repeater_fields() {
	$date_formats = function_exists( 'ecbb_date_format_preset_options' )
		? ecbb_date_format_preset_options()
		: [];

	return [
		'part' => [
			'label'   => esc_html__( 'Part', 'ecbb' ),
			'type'    => 'select',
			'options' => function_exists( 'ecbb_part_select_options' )
				? ecbb_part_select_options()
				: [ 'title' => esc_html__( 'Title', 'ecbb' ) ],
			'default' => 'title',
		],
		'date_display' => [
			'label'    => esc_html__( 'Visibility', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'day_time_range' => esc_html__( 'Time range', 'ecbb' ),
				'date'           => esc_html__( 'Date only', 'ecbb' ),
				'time'           => esc_html__( 'Time only', 'ecbb' ),
				'day'            => esc_html__( 'Day name', 'ecbb' ),
			],
			'default'  => 'day_time_range',
			'required' => [ 'part', '=', 'date' ],
		],
		'venue_display' => [
			'label'    => esc_html__( 'Venue display', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'full_details'   => esc_html__( 'Full venue details', 'ecbb' ),
				'name_and_state' => esc_html__( 'Venue name and state', 'ecbb' ),
				'name'           => esc_html__( 'Venue name only', 'ecbb' ),
				'full_address' => esc_html__( 'Full address only', 'ecbb' ),
				'street'       => esc_html__( 'Street', 'ecbb' ),
				'city'         => esc_html__( 'City', 'ecbb' ),
				'state'        => esc_html__( 'State / province', 'ecbb' ),
				'zip'          => esc_html__( 'ZIP / postal', 'ecbb' ),
				'country'      => esc_html__( 'Country', 'ecbb' ),
				'phone'        => esc_html__( 'Phone', 'ecbb' ),
				'website'      => esc_html__( 'Website', 'ecbb' ),
				'map_link'     => esc_html__( 'Map link', 'ecbb' ),
			],
			'default'  => 'full_details',
			'required' => [ 'part', '=', 'venue' ],
		],
		'organizer_display' => [
			'label'    => esc_html__( 'Organizer display', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'full_details' => esc_html__( 'Full organizer details', 'ecbb' ),
				'name'         => esc_html__( 'Organizer name only', 'ecbb' ),
				'email'        => esc_html__( 'Email', 'ecbb' ),
				'phone'        => esc_html__( 'Phone', 'ecbb' ),
				'website'      => esc_html__( 'Website', 'ecbb' ),
			],
			'default'  => 'full_details',
			'required' => [ 'part', '=', 'organizer' ],
		],
		'cost_currency' => [
			'label'    => esc_html__( 'Cost currency', 'ecbb' ),
			'type'     => 'select',
			'options'  => function_exists( 'ecbb_event_cost_currency_options' )
				? ecbb_event_cost_currency_options()
				: [
					'default' => esc_html__( 'Site default', 'ecbb' ),
					'none'    => esc_html__( 'No currency symbol', 'ecbb' ),
				],
			'default'  => 'default',
			'required' => [ 'part', '=', 'event_cost' ],
		],
		'event_link_display' => [
			'label'    => esc_html__( 'Link type', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'website'  => esc_html__( 'Event website', 'ecbb' ),
				'phone'    => esc_html__( 'Event phone', 'ecbb' ),
				'map_link' => esc_html__( 'Map link', 'ecbb' ),
			],
			'default'  => 'website',
			'required' => [ 'part', '=', 'event_link' ],
		],
		'link' => [
			'label'    => esc_html__( 'Link title to event', 'ecbb' ),
			'type'     => 'checkbox',
			'required' => [ 'part', '=', 'title' ],
		],
		'desc_source' => [
			'label'    => esc_html__( 'Description source', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'auto'    => esc_html__( 'Auto (excerpt → content)', 'ecbb' ),
				'excerpt' => esc_html__( 'Excerpt', 'ecbb' ),
				'content' => esc_html__( 'Full content', 'ecbb' ),
			],
			'default'  => 'auto',
			'required' => [ 'part', '=', 'description' ],
		],
		'date_format_preset' => [
			'label'       => esc_html__( 'Date Format', 'ecbb' ),
			'type'        => 'select',
			'options'     => $date_formats,
			'default'     => '',
			'placeholder' => esc_html__( 'Default', 'ecbb' ),
			'required'    => [
				[ 'part', '=', 'date' ],
				[ 'date_display', '=', [ 'date', 'time' ] ],
			],
		],
		'date_format_custom' => [
			'label'       => esc_html__( 'Custom PHP format', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => 'F j, Y g:i a',
			'required'    => [
				[ 'part', '=', 'date' ],
				[ 'date_display', '=', [ 'date', 'time' ] ],
				[ 'date_format_preset', '=', 'custom' ],
			],
		],
		'tickets_link_text' => [
			'label'       => esc_html__( 'Tickets link text', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Tickets', 'ecbb' ),
			'default'     => esc_html__( 'Tickets', 'ecbb' ),
			'required'    => [ 'part', '=', 'event_tickets' ],
		],
		'rsvp_link_text' => [
			'label'       => esc_html__( 'RSVP link text', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'RSVP', 'ecbb' ),
			'default'     => esc_html__( 'RSVP', 'ecbb' ),
			'required'    => [ 'part', '=', 'event_rsvp' ],
		],
		'terms_separator' => [
			'label'       => esc_html__( 'Separator', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => ', ',
			'default'     => ', ',
			'required'    => [ 'part', '=', 'tags' ],
		],
		'detail_link_text' => [
			'label'       => esc_html__( 'Link label', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Open link', 'ecbb' ),
			'required'    => [ 'part', '=', 'event_link' ],
		],
		'btn_style' => [
			'label'    => esc_html__( 'Button styles', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
		],
		'btn_sep_style' => [
			'label'    => esc_html__( 'Button styling', 'ecbb' ),
			'type'     => 'separator',
			'required' => self::ecbb_repeater_required_btn_style_group(),
		],
		'btn_bg' => [
			'label'       => esc_html__( 'Button background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#2271b1',
			'responsive'  => true,
			'required'    => self::ecbb_repeater_required_btn_style_group(),
		],
		'btn_text_color' => [
			'label'       => esc_html__( 'Button text color', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#ffffff',
			'responsive'  => true,
			'required'    => self::ecbb_repeater_required_btn_style_group(),
		],
		'btn_sep_border' => [
			'label'    => esc_html__( 'Border & padding', 'ecbb' ),
			'type'     => 'separator',
			'required' => self::ecbb_repeater_required_btn_border_group(),
		],
		'btn_border_type' => [
			'label'    => esc_html__( 'Border type', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'solid'  => esc_html__( 'Solid', 'ecbb' ),
				'dashed' => esc_html__( 'Dashed', 'ecbb' ),
				'dotted' => esc_html__( 'Dotted', 'ecbb' ),
				'double' => esc_html__( 'Double', 'ecbb' ),
				'none'   => esc_html__( 'None', 'ecbb' ),
			],
			'default'  => 'solid',
			'required' => self::ecbb_repeater_required_btn_border_group(),
		],
		'btn_border_width' => [
			'label'      => esc_html__( 'Border width', 'ecbb' ),
			'type'       => 'number',
			'units'      => [ 'px' ],
			'unit'       => 'px',
			'placeholder' => '1',
			'responsive' => true,
			'required'   => self::ecbb_repeater_required_btn_border_group(),
		],
		'btn_border_color' => [
			'label'       => esc_html__( 'Border color', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#cccccc',
			'responsive'  => true,
			'required'    => self::ecbb_repeater_required_btn_border_group(),
		],
		'btn_padding' => [
			'label'    => esc_html__( 'Button padding', 'ecbb' ),
			'type'     => 'spacing',
			'default'  => [
				'top'    => '10px',
				'right'  => '14px',
				'bottom' => '10px',
				'left'   => '14px',
			],
			'required' => self::ecbb_repeater_required_btn_border_group(),
			// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
			// Frontend: ecbb_button_declarations() + build_parts_scoped_css(). Builder: ecbb-builder.js.
		],
		'btn_border_radius' => [
			'label'       => esc_html__( 'Border radius', 'ecbb' ),
			'type'        => 'dimensions',
			'placeholder' => '0px',
			'responsive'  => true,
			'required'    => self::ecbb_repeater_required_btn_border_group(),
			// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
			// Frontend: ecbb_button_declarations() + build_parts_scoped_css(). Builder: ecbb-builder.js.
		],
		'read_more_text' => [
			'label'       => esc_html__( 'Read more text', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'More Details', 'ecbb' ),
			'required'    => [
				[ 'part', '=', 'read_more' ],
			],
		],
		'image_aspect_ratio' => [
			'label'    => esc_html__( 'Aspect ratio', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				''     => esc_html__( 'Default', 'ecbb' ),
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
			'label'       => esc_html__( 'Image size', 'ecbb' ),
			'type'        => 'select',
			'options'     => function_exists( 'ecbb_get_image_size_control_options' ) ? ecbb_get_image_size_control_options() : [ 'large' => 'large', 'full' => 'full' ],
			'default'     => '',
			'placeholder' => esc_html__( 'Default (large)', 'ecbb' ),
			'required'    => [ 'part', '=', 'image' ],
		],
		'image_size_hover' => [
			'label'    => esc_html__( 'Image size (hover)', 'ecbb' ),
			'type'     => 'select',
			'options'  => array_merge(
				[ '' => esc_html__( 'Same as default', 'ecbb' ) ],
				function_exists( 'ecbb_get_image_size_control_options' )
					? array_diff_key( ecbb_get_image_size_control_options(), [ '' => true ] )
					: [ 'large' => 'large', 'full' => 'full' ]
			),
			'default'  => '',
			'required' => [
				[ 'part', '=', 'image' ],
				self::ecbb_repeater_required_hover_on(),
			],
		],
		'ecbb_image_object_align' => [
			'label'      => esc_html__( 'Image alignment', 'ecbb' ),
			'type'       => 'select',
			'options'    => function_exists( 'ecbb_get_image_object_align_control_options' ) ? ecbb_get_image_object_align_control_options() : [],
			'default'    => '',
			'responsive' => true,
			'required'   => [ 'part', '=', 'image' ],
		],
		'ecbb_image_object_align_hover' => [
			'label'    => esc_html__( 'Image alignment (hover)', 'ecbb' ),
			'type'     => 'select',
			'options'  => array_merge(
				[ '' => esc_html__( 'Same as default', 'ecbb' ) ],
				function_exists( 'ecbb_get_image_object_align_control_options' )
					? array_diff_key( ecbb_get_image_object_align_control_options(), [ '' => true ] )
					: []
			),
			'default'  => '',
			'required' => [
				[ 'part', '=', 'image' ],
				self::ecbb_repeater_required_hover_on(),
			],
		],
		'image_link' => [
			'label'    => esc_html__( 'Link image to event', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'part', '=', 'image' ],
		],
		'ecbb_image_width' => [
			'label'       => esc_html__( 'Image width', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => '100%',
			'responsive'  => true,
			'required'    => [ 'part', '=', 'image' ],
			'css'         => self::ecbb_repeater_control_css(
				'width',
				'.ecbb-event__image, .ecbb-event__img-stack'
			),
		],
		'ecbb_image_height' => [
			'label'       => esc_html__( 'Image height', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => 'auto',
			'responsive'  => true,
			'required'    => [ 'part', '=', 'image' ],
			'css'         => self::ecbb_repeater_control_css(
				'height',
				'.ecbb-event__image'
			),
		],
		'ecbb_image_fit' => [
			'label'      => esc_html__( 'Image fit', 'ecbb' ),
			'type'       => 'select',
			'options'    => [
				''           => esc_html__( 'Default', 'ecbb' ),
				'cover'      => 'cover',
				'contain'    => 'contain',
				'fill'       => 'fill',
				'none'       => 'none',
				'scale-down' => 'scale-down',
			],
			'default'    => '',
			'responsive' => true,
			'required'   => [ 'part', '=', 'image' ],
			'css'        => self::ecbb_repeater_control_css(
				'object-fit',
				'.ecbb-event__image'
			),
		],
		'ecbb_sep_style' => [
			'type'  => 'separator',
			'label' => esc_html__( 'Style', 'ecbb' ),
		],
		'ecbb_typography' => [
			'label'      => esc_html__( 'Typography', 'ecbb' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => [ 'part', '!=', 'image' ],
			'css'        => function_exists( 'ecbb_repeater_typography_control_css' )
				? ecbb_repeater_typography_control_css()
				: self::ecbb_repeater_control_css(
					'typography',
					'&, & .ecbb-event__term-chip, & .ecbb-event__link, & .ecbb-event__term'
				),
		],
		'ecbb_text_align' => [
			'label'      => esc_html__( 'Text align', 'ecbb' ),
			'type'     => 'text-align',
			'responsive' => true,
			'required'   => [ 'part', '!=', 'image' ],
			'css'        => self::ecbb_repeater_control_css(
				'text-align',
				function_exists( 'ecbb_repeater_typography_css_selector' )
					? ecbb_repeater_typography_css_selector()
					: '&'
			),
		],
		'ecbb_background' => [
			'label'       => esc_html__( 'Background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
			'responsive'  => true,
			'css'         => self::ecbb_repeater_control_css( 'background-color', '&' ),
		],
		'ecbb_background_inner' => [
			'label'       => esc_html__( 'Inner background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
			'responsive'  => true,
			'required'    => self::ecbb_repeater_required_inner_background(),
			// No Bricks `css` rule: child selectors are ignored on repeater fieldId targets.
			// Frontend: ecbb_build_parts_scoped_css(). Builder: assets/js/ecbb-builder.js.
		],
		'ecbb_margin' => [
			'label' => esc_html__( 'Margin', 'ecbb' ),
			'type'  => 'spacing',
			'css'   => self::ecbb_repeater_control_css( 'margin' ),
		],
		'ecbb_padding' => [
			'label' => esc_html__( 'Padding', 'ecbb' ),
			'type'  => 'spacing',
			'css'   => self::ecbb_repeater_control_css( 'padding' ),
		],
		'ecbb_use_hover' => [
			'label'    => esc_html__( 'Enable hover effects', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'yes' => esc_html__( 'Yes', 'ecbb' ),
				'no'  => esc_html__( 'No', 'ecbb' ),
			],
			'default'  => 'yes',
			'required' => self::ecbb_repeater_required_hover_toggle_visible(),
		],
		'ecbb_sep_hover' => [
			'type'     => 'separator',
			'label'    => esc_html__( 'Hover effects', 'ecbb' ),
			'required' => self::ecbb_repeater_required_hover_style_group(),
		],
		'ecbb_hover_color' => [
			'label'       => esc_html__( 'Hover color', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#000000',
			'required'    => self::ecbb_repeater_required_hover_details(),
		],
		'ecbb_hover_background' => [
			'label'       => esc_html__( 'Hover background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
			'required'    => self::ecbb_repeater_required_hover_background(),
		],
		'ecbb_hover_text_decoration' => [
			'label'    => esc_html__( 'Text decoration (hover)', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				''             => esc_html__( 'Default', 'ecbb' ),
				'none'         => esc_html__( 'None', 'ecbb' ),
				'underline'    => esc_html__( 'Underline', 'ecbb' ),
				'overline'     => esc_html__( 'Overline', 'ecbb' ),
				'line-through' => esc_html__( 'Line through', 'ecbb' ),
			],
			'default'  => '',
			'required' => self::ecbb_repeater_required_hover_text_decoration(),
		],
		'ecbb_hover_animation' => [
			'label'    => esc_html__( 'Hover animation', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				''              => esc_html__( 'None', 'ecbb' ),
				'fade_in_up'    => esc_html__( 'Fade in up', 'ecbb' ),
				'fade_in_right' => esc_html__( 'Fade in right', 'ecbb' ),
				'fade_in_down'  => esc_html__( 'Fade in down', 'ecbb' ),
				'fade_in_left'  => esc_html__( 'Fade in left', 'ecbb' ),
				'zoom_in'       => esc_html__( 'Zoom in', 'ecbb' ),
				'zoom_out'      => esc_html__( 'Zoom out', 'ecbb' ),
			],
			'default'  => '',
			'required' => self::ecbb_repeater_required_hover_details(),
		],
		'ecbb_image_border' => [
			'label'      => esc_html__( 'Image border', 'ecbb' ),
			'type'       => 'border',
			'responsive' => true,
			'required'   => [ 'part', '=', 'image' ],
			'css'        => self::ecbb_repeater_control_css(
				'border',
				'.ecbb-event__image'
			),
		],
		'ecbb_image_radius' => [
			'label'       => esc_html__( 'Image radius', 'ecbb' ),
			'type'        => 'dimensions',
			'placeholder' => '0px',
			'responsive'  => true,
			'required'    => [ 'part', '=', 'image' ],
			'css'         => self::ecbb_repeater_control_css(
				'border-radius',
				'.ecbb-event__image'
			),
		],
	];
}

/**
 * Register all element controls on the Bricks element instance.
 *
 * @param \ECBB\ECBB_Widget $element Element instance.
 * @return void
 */

	public static function ecbb_element_set_controls( $element ) {
	$event_cat_options = [];
	if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'tribe_events_cat' ) ) {
		$terms = get_terms(
			[
				'taxonomy'   => 'tribe_events_cat',
				'hide_empty' => false,
			]
		);
		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term ) {
					$event_cat_options[ $term->slug ] = $term->name;
				}
			}
		}
	}

	// ── Layouts (first) ──
	$element->controls['layout_template'] = [
		'tab'     => 'content',
		'group'   => 'layouts',
		'label'   => esc_html__( 'Template', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'list' => esc_html__( 'List', 'ecbb' ),
			'grid' => esc_html__( 'Grid', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'list',
	];

	$element->controls['list_item_style'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'List style', 'ecbb' ),
		'type'     => 'select',
		'options'  => [
			'style-1' => esc_html__( 'Style 1', 'ecbb' ),
			'style-2' => esc_html__( 'Style 2', 'ecbb' ),
		],
		'default'  => 'style-1',
		'required' => [ 'layout_template', '=', 'list' ],
	];

	$element->controls['style2_show_month_headings'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'Show month header', 'ecbb' ),
		'type'     => 'checkbox',
		'inline'   => true,
		'default'  => false,
		'required' => [
			[ 'layout_template', '=', 'list' ],
			[ 'list_item_style', '=', 'style-2' ],
		],
	];

	$element->controls['grid_cols'] = [
		'tab'         => 'content',
		'group'       => 'layouts',
		'label'       => esc_html__( 'Grid columns', 'ecbb' ),
		'type'        => 'number',
		'min'         => 1,
		'step'        => 1,
		'default'     => 3,
		'placeholder' => '3',
		'responsive'  => true,
		'rerender'    => true,
		'description' => esc_html__( 'Use device icons for different devices.', 'ecbb' ),
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
		'label'       => esc_html__( 'Gap between events', 'ecbb' ),
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
		'description' => esc_html__( 'Space between each event card. Click the device icon on this control for tablet/mobile.', 'ecbb' ),
		'css'         => [
			[
				'property' => '--ecbb-gap',
				'selector' => '.ecbb-ev',
			],
		],
	];

	$element->controls['date_format'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'List Style 1 — date column format', 'ecbb' ),
		'type'     => 'select',
		'default'  => 'default',
		'required' => [
			[ 'layout_template', '=', 'list' ],
			[ 'list_item_style', '=', 'style-1' ],
		],
		'options'  => function_exists( 'ecbb_date_format_preset_options' )
			? ecbb_date_format_preset_options()
			: [ 'default' => esc_html__( 'Default', 'ecbb' ) ],
	];

	// ── Events Query ──
	$element->controls['event_type'] = [
		'tab'     => 'content',
		'group'   => 'event_query',
		'label'   => esc_html__( 'Types of events', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'past'   => esc_html__( 'Past', 'ecbb' ),
			'future' => esc_html__( 'Future', 'ecbb' ),
			'all'    => esc_html__( 'All', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'all',
	];

	$element->controls['event_categories'] = [
		'tab'         => 'content',
		'group'       => 'event_query',
		'label'       => esc_html__( 'Event categories', 'ecbb' ),
		'type'        => 'select',
		'options'     => $event_cat_options,
		'multiple'    => true,
		'placeholder' => esc_html__( 'All categories', 'ecbb' ),
	];

	$element->controls['event_time_mode'] = [
		'tab'     => 'content',
		'group'   => 'event_query',
		'label'   => esc_html__( 'Events time', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'all'     => esc_html__( 'All', 'ecbb' ),
			'between' => esc_html__( 'Between date range', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'all',
	];

	$element->controls['event_range_start'] = [
		'tab'      => 'content',
		'group'    => 'event_query',
		'label'    => esc_html__( 'Range start', 'ecbb' ),
		'type'     => 'datepicker',
		'required' => [ 'event_time_mode', '=', 'between' ],
	];

	$element->controls['event_range_end'] = [
		'tab'      => 'content',
		'group'    => 'event_query',
		'label'    => esc_html__( 'Range end', 'ecbb' ),
		'type'     => 'datepicker',
		'required' => [ 'event_time_mode', '=', 'between' ],
	];

	$element->controls['posts_per_page'] = [
		'tab'         => 'content',
		'group'       => 'event_query',
		'label'       => esc_html__( 'Number of events', 'ecbb' ),
		'type'        => 'number',
		'min'         => -1,
		'step'        => 1,
		'default'     => 10,
		'placeholder' => '10',
	];

	$element->controls['order'] = [
		'tab'     => 'content',
		'group'   => 'event_query',
		'label'   => esc_html__( 'Events order', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'ASC'  => 'ASC',
			'DESC' => 'DESC',
		],
		'inline'  => true,
		'default' => 'ASC',
	];

	// ── Dynamic Messages (content) ──
	$element->controls['no_events_text'] = [
		'tab'         => 'content',
		'group'       => 'dynamic_messages',
		'label'       => esc_html__( 'No events found text', 'ecbb' ),
		'type'        => 'text',
		'placeholder' => esc_html__( 'No events found', 'ecbb' ),
		'default'     => esc_html__( 'No events found', 'ecbb' ),
	];

	$element->controls['no_events_tag'] = [
		'tab'     => 'content',
		'group'   => 'dynamic_messages',
		'label'   => esc_html__( 'HTML tag', 'ecbb' ),
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

	$element->controls['load_more_sep'] = [
		'tab'   => 'content',
		'group' => 'dynamic_messages',
		'type'  => 'separator',
		'label' => esc_html__( 'Load more', 'ecbb' ),
	];

	$element->controls['load_more'] = [
		'tab'         => 'content',
		'group'       => 'dynamic_messages',
		'label'       => esc_html__( 'Enable load more', 'ecbb' ),
		'type'        => 'checkbox',
		'default'     => false,
		'description' => esc_html__( 'Uses “Number of events” as the batch size per click. Set a positive number in Events Query.', 'ecbb' ),
	];

	$element->controls['load_more_text'] = [
		'tab'         => 'content',
		'group'       => 'dynamic_messages',
		'label'       => esc_html__( 'Button text', 'ecbb' ),
		'type'        => 'text',
		'default'     => esc_html__( 'Load more', 'ecbb' ),
		'placeholder' => esc_html__( 'Load more', 'ecbb' ),
		'required'    => [ 'load_more', '=', true ],
	];

	$element->controls['load_more_loading_text'] = [
		'tab'         => 'content',
		'group'       => 'dynamic_messages',
		'label'       => esc_html__( 'Loading text', 'ecbb' ),
		'type'        => 'text',
		'default'     => esc_html__( 'Loading...', 'ecbb' ),
		'placeholder' => esc_html__( 'Loading...', 'ecbb' ),
		'required'    => [ 'load_more', '=', true ],
	];

	$element->controls['load_more_no_more_text'] = [
		'tab'         => 'content',
		'group'       => 'dynamic_messages',
		'label'       => esc_html__( 'No more events text', 'ecbb' ),
		'type'        => 'text',
		'default'     => esc_html__( 'No more events', 'ecbb' ),
		'placeholder' => esc_html__( 'No more events', 'ecbb' ),
		'required'    => [ 'load_more', '=', true ],
	];

	// ── Dynamic Messages (style) — grouped ──
	$element->controls['no_events_sep'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'type'  => 'separator',
		'label' => esc_html__( 'No events found', 'ecbb' ),
	];

	$element->controls['no_events_align'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Horizontal align', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'flex-start' => esc_html__( 'Left', 'ecbb' ),
			'center'     => esc_html__( 'Center', 'ecbb' ),
			'flex-end'   => esc_html__( 'Right', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'center',
		'css'     => [
			[
				'property' => 'justify-content',
				'selector' => '& .ecbb-ev__empty',
			],
		],
	];

	$element->controls['no_events_align_items'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Vertical align', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'flex-start' => esc_html__( 'Top', 'ecbb' ),
			'center'     => esc_html__( 'Center', 'ecbb' ),
			'flex-end'   => esc_html__( 'Bottom', 'ecbb' ),
			'stretch'    => esc_html__( 'Stretch', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'center',
		'css'     => [
			[
				'property' => 'align-items',
				'selector' => '& .ecbb-ev__empty',
			],
		],
	];

	$element->controls['no_events_margin'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Wrapper margin', 'ecbb' ),
		'type'  => 'spacing',
		'css'   => [
			[
				'property' => 'margin',
				'selector' => '& .ecbb-ev__empty',
			],
		],
		'default' => [
			'top'    => '24px',
			'right'  => '0',
			'bottom' => '0',
			'left'   => '0',
		],
	];

	$element->controls['no_events_wrapper_padding'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Wrapper padding', 'ecbb' ),
		'type'  => 'spacing',
		'css'   => [
			[
				'property' => 'padding',
				'selector' => '& .ecbb-ev__empty',
			],
		],
	];

	$element->controls['no_events_min_height'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Wrapper min height', 'ecbb' ),
		'type'        => 'number',
		'units'       => true,
		'placeholder' => '120px',
		'css'         => [
			[
				'property' => 'min-height',
				'selector' => '& .ecbb-ev__empty',
			],
		],
	];

	$element->controls['no_events_sep_typography'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'type'  => 'separator',
		'label' => esc_html__( 'Message typography & box', 'ecbb' ),
	];

	$element->controls['no_events_typography'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Typography', 'ecbb' ),
		'type'  => 'typography',
		'css'   => [
			[
				'property' => 'font',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_bg'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Background', 'ecbb' ),
		'type'        => 'color',
		'placeholder' => 'transparent',
		'css'         => [
			[
				'property' => 'background-color',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_border'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Border', 'ecbb' ),
		'type'  => 'border',
		'css'   => [
			[
				'property' => 'border',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_padding'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Padding', 'ecbb' ),
		'type'  => 'spacing',
		'css'   => [
			[
				'property' => 'padding',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
		'default' => [
			'top'    => '16px',
			'right'  => '24px',
			'bottom' => '16px',
			'left'   => '24px',
		],
	];

	$element->controls['no_events_max_width'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Message max width', 'ecbb' ),
		'type'        => 'number',
		'units'       => true,
		'placeholder' => '640px',
		'css'         => [
			[
				'property' => 'max-width',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_width'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Message width', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'auto' => esc_html__( 'Auto', 'ecbb' ),
			'100%' => esc_html__( 'Full width', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'auto',
		'css'     => [
			[
				'property' => 'width',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_box_shadow'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Box shadow', 'ecbb' ),
		'type'  => 'box-shadow',
		'css'   => [
			[
				'property' => 'box-shadow',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_display'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Message display', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'inline-block' => esc_html__( 'Inline block', 'ecbb' ),
			'block'        => esc_html__( 'Block', 'ecbb' ),
		],
		'inline'  => true,
		'default' => 'inline-block',
		'css'     => [
			[
				'property' => 'display',
				'selector' => '& .ecbb-ev__empty-message',
			],
		],
	];

	$element->controls['no_events_sep_hover'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'type'  => 'separator',
		'label' => esc_html__( 'Hover', 'ecbb' ),
	];

	$element->controls['no_events_color_hover'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Text color (hover)', 'ecbb' ),
		'type'        => 'color',
		'placeholder' => '#111111',
		'css'         => [
			[
				'property' => 'color',
				'selector' => '& .ecbb-ev__empty-message:hover',
			],
		],
	];

	$element->controls['no_events_bg_hover'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Background (hover)', 'ecbb' ),
		'type'        => 'color',
		'placeholder' => 'transparent',
		'css'         => [
			[
				'property' => 'background-color',
				'selector' => '& .ecbb-ev__empty-message:hover',
			],
		],
	];

	$element->controls['no_events_border_hover'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Border (hover)', 'ecbb' ),
		'type'  => 'border',
		'css'   => [
			[
				'property' => 'border',
				'selector' => '& .ecbb-ev__empty-message:hover',
			],
		],
	];

	$element->controls['no_events_box_shadow_hover'] = [
		'tab'   => 'style',
		'group' => 'dynamic_messages_style',
		'label' => esc_html__( 'Box shadow (hover)', 'ecbb' ),
		'type'  => 'box-shadow',
		'css'   => [
			[
				'property' => 'box-shadow',
				'selector' => '& .ecbb-ev__empty-message:hover',
			],
		],
	];

	$element->controls['no_events_transform_hover'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Hover motion (transform)', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'none'       => esc_html__( 'None', 'ecbb' ),
			'lift'       => esc_html__( 'Lift up', 'ecbb' ),
			'scale_up'   => esc_html__( 'Scale up', 'ecbb' ),
			'scale_down' => esc_html__( 'Scale down', 'ecbb' ),
		],
		'inline'   => true,
		'default'  => 'none',
		'required' => [
			[ 'no_events_hover_animation', '=', '' ],
		],
	];

	$element->controls['no_events_hover_animation'] = [
		'tab'     => 'style',
		'group'   => 'dynamic_messages_style',
		'label'   => esc_html__( 'Hover animation', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			''              => esc_html__( 'None (use transform above)', 'ecbb' ),
			'fade_in_up'    => esc_html__( 'Fade in up', 'ecbb' ),
			'fade_in_right' => esc_html__( 'Fade in right', 'ecbb' ),
			'fade_in_down'  => esc_html__( 'Fade in down', 'ecbb' ),
			'fade_in_left'  => esc_html__( 'Fade in left', 'ecbb' ),
			'zoom_in'       => esc_html__( 'Zoom in', 'ecbb' ),
			'zoom_out'      => esc_html__( 'Zoom out', 'ecbb' ),
		],
		'default' => '',
	];

	$element->controls['no_events_opacity_hover'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Opacity on hover', 'ecbb' ),
		'type'        => 'number',
		'min'         => 0,
		'max'         => 1,
		'step'        => 0.05,
		'placeholder' => '1',
	];

	$element->controls['no_events_transition_duration'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Transition duration (ms)', 'ecbb' ),
		'type'        => 'number',
		'min'         => 0,
		'step'        => 50,
		'placeholder' => '200',
		'default'     => 200,
	];

	$element->controls['load_more_style_sep'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'type'     => 'separator',
		'label'    => esc_html__( 'Load more button', 'ecbb' ),
		'required' => [ 'load_more', '=', true ],
	];

	$element->controls['load_more_typography'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Typography', 'ecbb' ),
		'type'     => 'typography',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'typography',
				'selector' => '& .ecbb-load-more__btn',
			],
		],
	];

	$element->controls['load_more_color'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Text color', 'ecbb' ),
		'type'        => 'color',
		'placeholder' => '#ffffff',
		'required'    => [ 'load_more', '=', true ],
		'css'         => [
			[
				'property' => 'color',
				'selector' => '& .ecbb-load-more__btn',
			],
		],
	];

	$element->controls['load_more_bg'] = [
		'tab'         => 'style',
		'group'       => 'dynamic_messages_style',
		'label'       => esc_html__( 'Background', 'ecbb' ),
		'type'        => 'color',
		'placeholder' => '#111827',
		'required'    => [ 'load_more', '=', true ],
		'css'         => [
			[
				'property' => 'background-color',
				'selector' => '& .ecbb-load-more__btn',
			],
		],
	];

	$element->controls['load_more_border'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Border', 'ecbb' ),
		'type'     => 'border',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'border',
				'selector' => '& .ecbb-load-more__btn',
			],
		],
	];

	$element->controls['load_more_padding'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Padding', 'ecbb' ),
		'type'     => 'spacing',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'padding',
				'selector' => '& .ecbb-load-more__btn',
			],
		],
	];

	$element->controls['load_more_margin'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Wrapper margin', 'ecbb' ),
		'type'     => 'spacing',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'margin',
				'selector' => '& .ecbb-load-more',
			],
		],
	];

	$element->controls['load_more_color_hover'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Text color (hover)', 'ecbb' ),
		'type'     => 'color',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'color',
				'selector' => '& .ecbb-load-more__btn:hover',
			],
		],
	];

	$element->controls['load_more_bg_hover'] = [
		'tab'      => 'style',
		'group'    => 'dynamic_messages_style',
		'label'    => esc_html__( 'Background (hover)', 'ecbb' ),
		'type'     => 'color',
		'required' => [ 'load_more', '=', true ],
		'css'      => [
			[
				'property' => 'background-color',
				'selector' => '& .ecbb-load-more__btn:hover',
			],
		],
	];

	$repeater_fields = self::ecbb_get_repeater_fields();

	$element->controls['parts_style1'] = [
		'tab'           => 'content',
		'group'         => 'elements',
		'type'          => 'repeater',
		'selector'      => 'fieldId',
		'label'         => esc_html__( 'Event parts — Style 1 (list)', 'ecbb' ),
		'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'ecbb' ),
		'titleProperty' => 'part',
		'placeholder'   => esc_html__( 'Add event part', 'ecbb' ),
		'required'      => [
			[ 'layout_template', '=', 'list' ],
			[ 'list_item_style', '=', 'style-1' ],
		],
		'default'       => function_exists( 'ecbb_list1_default_parts_rows' )
			? ecbb_list1_default_parts_rows()
			: [],
		'fields'        => $repeater_fields,
	];

	$element->controls['parts_style2'] = [
		'tab'           => 'content',
		'group'         => 'elements',
		'type'          => 'repeater',
		'selector'      => 'fieldId',
		'label'         => esc_html__( 'Event parts — Style 2 (list)', 'ecbb' ),
		'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'ecbb' ),
		'titleProperty' => 'part',
		'placeholder'   => esc_html__( 'Add event part', 'ecbb' ),
		'required'      => [
			[ 'layout_template', '=', 'list' ],
			[ 'list_item_style', '=', 'style-2' ],
		],
		'default'       => function_exists( 'ecbb_list2_default_parts_rows' )
			? ecbb_list2_default_parts_rows()
			: [],
		'fields'        => $repeater_fields,
	];

	$element->controls['parts_grid'] = [
		'tab'           => 'content',
		'group'         => 'elements',
		'type'          => 'repeater',
		'selector'      => 'fieldId',
		'label'         => esc_html__( 'Event parts — Grid', 'ecbb' ),
		'description'   => esc_html__( 'Card image and framed date are fixed; this repeater drives the body column.', 'ecbb' ),
		'titleProperty' => 'part',
		'placeholder'   => esc_html__( 'Add event part', 'ecbb' ),
		'required'      => [ 'layout_template', '=', 'grid' ],
		'default'       => function_exists( 'ecbb_grid_default_parts_rows' )
			? ecbb_grid_default_parts_rows()
			: [],
		'fields'        => $repeater_fields,
	];
}

	}

}

if ( ! function_exists( 'ecbb_repeater_hover_part_slugs' ) ) {
	function ecbb_repeater_hover_part_slugs( ...$args ) {
		return ECBB_Controls::ecbb_repeater_hover_part_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_interactive_hover_part_slugs' ) ) {
	function ecbb_repeater_interactive_hover_part_slugs( ...$args ) {
		return ECBB_Controls::ecbb_repeater_interactive_hover_part_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_interactive_hover' ) ) {
	function ecbb_repeater_required_interactive_hover( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_interactive_hover( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_toggle_visible' ) ) {
	function ecbb_repeater_required_hover_toggle_visible( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_toggle_visible( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_on' ) ) {
	function ecbb_repeater_required_hover_on( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_on( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_style_group' ) ) {
	function ecbb_repeater_required_hover_style_group( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_style_group( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_details' ) ) {
	function ecbb_repeater_required_hover_details( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_details( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_background' ) ) {
	function ecbb_repeater_required_hover_background( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_background( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_hover_text_decoration' ) ) {
	function ecbb_repeater_required_hover_text_decoration( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_hover_text_decoration( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_hover_control_keys' ) ) {
	function ecbb_repeater_hover_control_keys( ...$args ) {
		return ECBB_Controls::ecbb_repeater_hover_control_keys( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_button_part_slugs' ) ) {
	function ecbb_repeater_button_part_slugs( ...$args ) {
		return ECBB_Controls::ecbb_repeater_button_part_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_btn_style_on' ) ) {
	function ecbb_repeater_required_btn_style_on( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_btn_style_on( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_btn_style_group' ) ) {
	function ecbb_repeater_required_btn_style_group( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_btn_style_group( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_btn_border_group' ) ) {
	function ecbb_repeater_required_btn_border_group( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_btn_border_group( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_btn_border_control_keys' ) ) {
	function ecbb_repeater_btn_border_control_keys( ...$args ) {
		return ECBB_Controls::ecbb_repeater_btn_border_control_keys( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_btn_style_control_keys' ) ) {
	function ecbb_repeater_btn_style_control_keys( ...$args ) {
		return ECBB_Controls::ecbb_repeater_btn_style_control_keys( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_hover_text_decoration_part_slugs' ) ) {
	function ecbb_repeater_hover_text_decoration_part_slugs( ...$args ) {
		return ECBB_Controls::ecbb_repeater_hover_text_decoration_part_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_hover_background_part_slugs' ) ) {
	function ecbb_repeater_hover_background_part_slugs( ...$args ) {
		return ECBB_Controls::ecbb_repeater_hover_background_part_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_required_inner_background' ) ) {
	function ecbb_repeater_required_inner_background( ...$args ) {
		return ECBB_Controls::ecbb_repeater_required_inner_background( ...$args );
	}
}
if ( ! function_exists( 'ecbb_repeater_control_css' ) ) {
	function ecbb_repeater_control_css( ...$args ) {
		return ECBB_Controls::ecbb_repeater_control_css( ...$args );
	}
}
if ( ! function_exists( 'ecbb_get_repeater_fields' ) ) {
	function ecbb_get_repeater_fields( ...$args ) {
		return ECBB_Controls::ecbb_get_repeater_fields( ...$args );
	}
}
if ( ! function_exists( 'ecbb_element_set_controls' ) ) {
	function ecbb_element_set_controls( ...$args ) {
		return ECBB_Controls::ecbb_element_set_controls( ...$args );
	}
}
