<?php
/**
 * ECBB_Layout_Controls service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Layout_Controls', false ) ) {

	final class ECBB_Layout_Controls {

		private static function ecbb_register_template_controls( $element ) {
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
	}

		private static function ecbb_register_layout_display_controls( $element ) {
		$element->controls['show_event_image'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Hide featured image', 'events-calendar-for-bricks' ),
			'type'     => 'checkbox',
			'default'  => false,
			'rerender' => true,
		];

		ECBB_Card_Style_Controls::ecbb_register_category_badge_layout_toggle( $element, 'list1' );
		ECBB_Card_Style_Controls::ecbb_register_category_badge_layout_toggle( $element, 'grid' );

		$date_column_order_options = [
			'month_day' => esc_html__( 'Month above, date below', 'events-calendar-for-bricks' ),
			'day_month' => esc_html__( 'Date above, month below', 'events-calendar-for-bricks' ),
		];

		$element->controls['list1_show_date_column'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Hide date column', 'events-calendar-for-bricks' ),
			'type'     => 'checkbox',
			'default'  => false,
			'rerender' => true,
			'required' => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			],
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
				[ 'list1_show_date_column', '!=', true ],
			],
		];

		$element->controls['style2_show_date_badge'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Hide date badge on image', 'events-calendar-for-bricks' ),
			'type'     => 'checkbox',
			'default'  => false,
			'rerender' => true,
			'required' => [
				[ 'show_event_image', '!=', true ],
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
		[ 'show_event_image', '!=', true ],
		[ 'style2_show_date_badge', '!=', true ],
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
					'selector' => '&',
				],
			],
		];
	}

		public static function ecbb_register_layout_controls( $element ) {
		self::ecbb_register_template_controls( $element );
		self::ecbb_register_layout_display_controls( $element );
	}

	}
}
