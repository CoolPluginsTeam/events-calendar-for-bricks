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
function ecbb_events_widget_repeater_hover_part_slugs() {
	return function_exists( 'ecbb_event_part_types_with_hover_style_controls' )
		? ecbb_event_part_types_with_hover_style_controls()
		: [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp', 'image' ];
}

/**
 * Interactive parts that support hover (title, chips, buttons). Excludes image.
 *
 * @return string[]
 */
function ecbb_events_widget_repeater_interactive_hover_part_slugs() {
	return [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed}>|array{0:string,1:string,2:mixed}
 */
function ecbb_events_widget_repeater_required_interactive_hover() {
	return [ 'part', '=', ecbb_events_widget_repeater_interactive_hover_part_slugs() ];
}

/**
 * @return array{0:string,1:string,2:mixed}
 */
function ecbb_events_widget_repeater_required_hover_toggle_visible() {
	return [ 'part', '=', ecbb_events_widget_repeater_hover_part_slugs() ];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */
function ecbb_events_widget_repeater_required_hover_style_group() {
	return [
		[ 'part', '=', ecbb_events_widget_repeater_hover_part_slugs() ],
		[ 'ecbb_use_hover', '=', true ],
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */
function ecbb_events_widget_repeater_required_hover_details() {
	return ecbb_events_widget_repeater_required_hover_style_group();
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */
function ecbb_events_widget_repeater_required_hover_background() {
	return [
		[ 'part', '=', ecbb_events_widget_repeater_hover_background_part_slugs() ],
		[ 'ecbb_use_hover', '=', true ],
	];
}

/**
 * @return array<int,array{0:string,1:string,2:mixed>>
 */
function ecbb_events_widget_repeater_required_hover_text_decoration() {
	return [
		[ 'part', '=', ecbb_events_widget_repeater_hover_text_decoration_part_slugs() ],
		[ 'ecbb_use_hover', '=', true ],
	];
}

/**
 * Repeater field keys gated by hover eligibility (builder tab CSS + JS).
 *
 * @return string[]
 */
function ecbb_events_widget_repeater_hover_control_keys() {
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
 * @return string[]
 */
function ecbb_events_widget_repeater_hover_text_decoration_part_slugs() {
	return ecbb_events_widget_repeater_interactive_hover_part_slugs();
}

/**
 * Part types that show hover background.
 *
 * @return string[]
 */
function ecbb_events_widget_repeater_hover_background_part_slugs() {
	return ecbb_events_widget_repeater_interactive_hover_part_slugs();
}

/**
 * @return array<int,array{0:string,1:string,2:mixed}>
 */
function ecbb_events_widget_repeater_required_inner_background() {
	return [ [ 'part', '=', 'title' ] ];
}

/**
 * Repeater sub-fields for event parts (content + grouped style controls).
 *
 * @return array<string,array<string,mixed>>
 */
function ecbb_events_widget_get_repeater_fields() {
	$date_formats = function_exists( 'ecbb_events_widget_date_format_preset_options' )
		? ecbb_events_widget_date_format_preset_options()
		: [];

	return [
		'part' => [
			'label'   => esc_html__( 'Part', 'ecbb' ),
			'type'    => 'select',
			'options' => function_exists( 'ecbb_events_widget_part_select_options' )
				? ecbb_events_widget_part_select_options()
				: [ 'title' => esc_html__( 'Title', 'ecbb' ) ],
			'default' => 'title',
		],
		'date_display' => [
			'label'    => esc_html__( 'Date format', 'ecbb' ),
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
			'label'    => esc_html__( 'Venue field', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'name'         => esc_html__( 'Venue name', 'ecbb' ),
				'full_address' => esc_html__( 'Full address', 'ecbb' ),
				'street'       => esc_html__( 'Street', 'ecbb' ),
				'city'         => esc_html__( 'City', 'ecbb' ),
				'state'        => esc_html__( 'State / province', 'ecbb' ),
				'zip'          => esc_html__( 'ZIP / postal', 'ecbb' ),
				'country'      => esc_html__( 'Country', 'ecbb' ),
				'phone'        => esc_html__( 'Phone', 'ecbb' ),
				'website'      => esc_html__( 'Website', 'ecbb' ),
				'map_link'     => esc_html__( 'Map link', 'ecbb' ),
			],
			'default'  => 'name',
			'required' => [ 'part', '=', 'venue' ],
		],
		'organizer_display' => [
			'label'    => esc_html__( 'Organizer field', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'name'    => esc_html__( 'Name', 'ecbb' ),
				'email'   => esc_html__( 'Email', 'ecbb' ),
				'phone'   => esc_html__( 'Phone', 'ecbb' ),
				'website' => esc_html__( 'Website', 'ecbb' ),
			],
			'default'  => 'name',
			'required' => [ 'part', '=', 'organizer' ],
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
		'tag' => [
			'label'    => esc_html__( 'Title HTML tag', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'h1'  => 'h1',
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
				'div' => 'div',
			],
			'default'  => 'h3',
			'required' => [ 'part', '=', 'title' ],
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
			'label'       => esc_html__( 'PHP date preset', 'ecbb' ),
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
		'date_text_transform' => [
			'label'    => esc_html__( 'Text transform', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'capitalize' => esc_html__( 'Capitalize', 'ecbb' ),
				'none'       => esc_html__( 'None', 'ecbb' ),
				'uppercase'  => esc_html__( 'Uppercase', 'ecbb' ),
				'lowercase'  => esc_html__( 'Lowercase', 'ecbb' ),
			],
			'default'  => 'capitalize',
			'required' => [
				[ 'part', '=', 'date' ],
				[ 'date_display', '=', 'day_time_range' ],
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
			'required'    => [ 'part', '=', [ 'categories', 'tags' ] ],
		],
		'terms_link' => [
			'label'    => esc_html__( 'Link terms', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'part', '=', [ 'categories', 'tags' ] ],
		],
		'venue_link' => [
			'label'    => esc_html__( 'Link to venue', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [
				[ 'part', '=', 'venue' ],
				[ 'venue_display', '=', [ 'name', 'full_address', 'street', 'city', 'state', 'zip', 'country', 'phone' ] ],
			],
		],
		'detail_link_text' => [
			'label'       => esc_html__( 'Link label', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Open link', 'ecbb' ),
			'required'    => [
				[ 'part', '=', [ 'organizer', 'event_link' ] ],
			],
		],
		'organizer_link' => [
			'label'    => esc_html__( 'Link to organizer', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [
				[ 'part', '=', 'organizer' ],
				[ 'organizer_display', '=', 'name' ],
			],
		],
		'cost_currency' => [
			'label'    => esc_html__( 'Currency', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				'symbol' => esc_html__( 'Symbol', 'ecbb' ),
				'none'   => esc_html__( 'None', 'ecbb' ),
			],
			'default'  => 'symbol',
			'required' => [ 'part', '=', 'event_cost' ],
		],
		'cost_prefix' => [
			'label'       => esc_html__( 'Prefix', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'From', 'ecbb' ),
			'required'    => [ 'part', '=', 'event_cost' ],
		],
		'cost_suffix' => [
			'label'       => esc_html__( 'Suffix', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'per person', 'ecbb' ),
			'required'    => [ 'part', '=', 'event_cost' ],
		],
		'btn_style' => [
			'label'    => esc_html__( 'Button styles', 'ecbb' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
		],
		'btn_bg' => [
			'label'       => esc_html__( 'Button background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#2271b1',
			'required'    => [
				[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
				[ 'btn_style', '=', true ],
			],
		],
		'btn_text_color' => [
			'label'       => esc_html__( 'Button text color', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#ffffff',
			'required'    => [
				[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
				[ 'btn_style', '=', true ],
			],
		],
		'btn_border' => [
			'label'    => esc_html__( 'Button border', 'ecbb' ),
			'type'     => 'border',
			'required' => [
				[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
				[ 'btn_style', '=', true ],
			],
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
			'required' => [
				[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
				[ 'btn_style', '=', true ],
			],
		],
		'read_more_text' => [
			'label'       => esc_html__( 'Read more text', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'More Details', 'ecbb' ),
			'required'    => [
				[ 'part', '=', 'read_more' ],
			],
		],
		'ecbb_use_hover' => [
			'label'    => esc_html__( 'Enable hover effects', 'ecbb' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'default'  => true,
			'required' => ecbb_events_widget_repeater_required_hover_toggle_visible(),
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
			'required' => [ 'part', '=', 'image' ],
		],
		'ecbb_image_object_align' => [
			'label'    => esc_html__( 'Image alignment', 'ecbb' ),
			'type'     => 'select',
			'options'  => function_exists( 'ecbb_get_image_object_align_control_options' ) ? ecbb_get_image_object_align_control_options() : [],
			'default'  => '',
			'required' => [ 'part', '=', 'image' ],
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
			'required' => [ 'part', '=', 'image' ],
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
		],
		'ecbb_image_height' => [
			'label'       => esc_html__( 'Image height', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => 'auto',
			'responsive'  => true,
			'required'    => [ 'part', '=', 'image' ],
		],
		'ecbb_image_fit' => [
			'label'    => esc_html__( 'Image fit', 'ecbb' ),
			'type'     => 'select',
			'options'  => [
				''           => esc_html__( 'Default', 'ecbb' ),
				'cover'      => 'cover',
				'contain'    => 'contain',
				'fill'       => 'fill',
				'none'       => 'none',
				'scale-down' => 'scale-down',
			],
			'default'  => '',
			'required' => [ 'part', '=', 'image' ],
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
		],
		'ecbb_text_align' => [
			'label'      => esc_html__( 'Text align', 'ecbb' ),
			'type'     => 'text-align',
			'responsive' => true,
			'required'   => [ 'part', '!=', 'image' ],
		],
		'ecbb_background' => [
			'label'       => esc_html__( 'Background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
		],
		'ecbb_background_inner' => [
			'label'       => esc_html__( 'Inner background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
			'required'    => ecbb_events_widget_repeater_required_inner_background(),
		],
		'ecbb_margin' => [
			'label'      => esc_html__( 'Margin', 'ecbb' ),
			'type'       => 'spacing',
			'responsive' => true,
		],
		'ecbb_padding' => [
			'label'      => esc_html__( 'Padding', 'ecbb' ),
			'type'       => 'spacing',
			'responsive' => true,
		],
		'ecbb_sep_hover' => [
			'type'     => 'separator',
			'label'    => esc_html__( 'Hover effects', 'ecbb' ),
			'required' => ecbb_events_widget_repeater_required_hover_style_group(),
		],
		'ecbb_hover_color' => [
			'label'       => esc_html__( 'Hover color', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#000000',
			'required'    => ecbb_events_widget_repeater_required_hover_details(),
		],
		'ecbb_hover_background' => [
			'label'       => esc_html__( 'Hover background', 'ecbb' ),
			'type'        => 'color',
			'placeholder' => '#666666',
			'required'    => ecbb_events_widget_repeater_required_hover_background(),
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
			'required' => ecbb_events_widget_repeater_required_hover_text_decoration(),
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
			'required' => ecbb_events_widget_repeater_required_hover_details(),
		],
		'ecbb_image_border' => [
			'label'    => esc_html__( 'Image border', 'ecbb' ),
			'type'     => 'border',
			'required' => [ 'part', '=', 'image' ],
		],
		'ecbb_image_radius' => [
			'label'       => esc_html__( 'Image radius', 'ecbb' ),
			'type'        => 'dimensions',
			'placeholder' => '0px',
			'required'    => [ 'part', '=', 'image' ],
		],
	];
}

/**
 * Register all element controls on the Bricks element instance.
 *
 * @param \ECBB\Element_ECBB_Events_Widget $element Element instance.
 * @return void
 */
function ecbb_events_widget_element_set_controls( $element ) {
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

	$element->controls['grid_cols_desktop'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'Grid columns (desktop)', 'ecbb' ),
		'type'     => 'number',
		'min'      => 1,
		'step'     => 1,
		'default'  => 3,
		'required' => [ 'layout_template', '=', 'grid' ],
	];

	$element->controls['grid_cols_tablet'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'Grid columns (tablet)', 'ecbb' ),
		'type'     => 'number',
		'min'      => 1,
		'step'     => 1,
		'default'  => 2,
		'required' => [ 'layout_template', '=', 'grid' ],
	];

	$element->controls['grid_cols_mobile'] = [
		'tab'      => 'content',
		'group'    => 'layouts',
		'label'    => esc_html__( 'Grid columns (mobile)', 'ecbb' ),
		'type'     => 'number',
		'min'      => 1,
		'step'     => 1,
		'default'  => 1,
		'required' => [ 'layout_template', '=', 'grid' ],
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
		'responsive'  => true,
		'description' => esc_html__( 'Space between each event card. Use device icons for tablet/mobile.', 'ecbb' ),
	];

	$element->controls['item_gap_unit'] = [
		'tab'     => 'content',
		'group'   => 'layouts',
		'label'   => esc_html__( 'Gap unit', 'ecbb' ),
		'type'    => 'select',
		'options' => [
			'px'  => 'px',
			'rem' => 'rem',
			'em'  => 'em',
		],
		'inline'  => true,
		'default' => 'px',
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
		'options'  => function_exists( 'ecbb_events_widget_date_format_preset_options' )
			? ecbb_events_widget_date_format_preset_options()
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
		'default' => 'future',
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

	$repeater_fields = ecbb_events_widget_get_repeater_fields();

	$element->controls['parts_style1'] = [
		'tab'           => 'content',
		'group'         => 'elements',
		'type'          => 'repeater',
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
		'label'         => esc_html__( 'Event parts — Grid', 'ecbb' ),
		'description'   => esc_html__( 'Card image and framed date are fixed; this repeater drives the body column.', 'ecbb' ),
		'titleProperty' => 'part',
		'placeholder'   => esc_html__( 'Add event part', 'ecbb' ),
		'required'      => [ 'layout_template', '=', 'grid' ],
		'default'       => function_exists( 'ecbb_events_widget_grid_default_parts_rows' )
			? ecbb_events_widget_grid_default_parts_rows()
			: [],
		'fields'        => $repeater_fields,
	];
}
