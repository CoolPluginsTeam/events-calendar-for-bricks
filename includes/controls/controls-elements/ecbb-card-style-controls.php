<?php
/**
 * ECBB_Card_Style_Controls service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Card_Style_Controls', false ) ) {

	final class ECBB_Card_Style_Controls {

		private static function ecbb_style_card_selectors() {
		return [
			'& .event-list-card',
			'& .ecbb-event-card',
			'& .event-grid-card',
		];
	}

		private static function ecbb_register_events_card_style_controls( $element ) {
		$card_sel = self::ecbb_style_card_selector();

		$element->controls['ecbb_card_background'] = [
			'tab'         => 'style',
			'group'       => 'events_card',
			'label'       => esc_html__( 'Background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#ffffff',
			'responsive'  => true,
			'css'         => [
				[
					'property' => '--ecbb-card-bg',
					'selector' => '&',
				],
				[
					'property' => 'background-color',
					'selector' => $card_sel,
				],
			],
		];

		$element->controls['ecbb_card_text_color'] = [
			'tab'         => 'style',
			'group'       => 'events_card',
			'label'       => esc_html__( 'Text color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '',
			'responsive'  => true,
			'css'         => [
				[
					'property' => '--ecbb-card-fg',
					'selector' => '&',
				],
				[
					'property' => 'color',
					'selector' => '& .event-list-card__body, & .ecbb-event-card__content, & .event-grid-card__content',
				],
			],
		];

		$element->controls['ecbb_sep_card_border'] = [
			'tab'   => 'style',
			'group' => 'events_card',
			'label' => esc_html__( 'Border', 'events-calendar-for-bricks' ),
			'type'  => 'separator',
		];

		$element->controls['ecbb_card_border_width'] = [
			'tab'         => 'style',
			'group'       => 'events_card',
			'label'       => esc_html__( 'Border width', 'events-calendar-for-bricks' ),
			'type'        => 'number',
			'units'       => [ 'px' ],
			'unit'        => 'px',
			'placeholder' => '1',
			'responsive'  => true,
			'css'         => ECBB_Part_Fields::ecbb_field_css( 'border-width', $card_sel ),
		];

		$element->controls['ecbb_card_border_color'] = [
			'tab'         => 'style',
			'group'       => 'events_card',
			'label'       => esc_html__( 'Border color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#e5eaf2',
			'responsive'  => true,
			'css'         => ECBB_Part_Fields::ecbb_field_css( 'border-color', $card_sel ),
		];

		$element->controls['ecbb_card_padding'] = [
			'tab'        => 'style',
			'group'      => 'events_card',
			'label'      => esc_html__( 'Padding', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'padding', $card_sel ),
		];

		$element->controls['ecbb_card_margin'] = [
			'tab'        => 'style',
			'group'      => 'events_card',
			'label'      => esc_html__( 'Margin', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'margin', '& .ecbb-ev__item' ),
		];

		$element->controls['ecbb_sep_card_hover'] = [
			'tab'   => 'style',
			'group' => 'events_card',
			'label' => esc_html__( 'Card hover', 'events-calendar-for-bricks' ),
			'type'  => 'separator',
		];

		$element->controls['ecbb_card_hover_animation'] = [
			'tab'      => 'style',
			'group'    => 'events_card',
			'label'    => esc_html__( 'Animation', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => ECBB_Hover_Controls::ecbb_hover_animation_options( true, true ),
			'default'  => '',
			'rerender' => true,
		];

		$element->controls['ecbb_sep_style2_divider'] = [
			'tab'      => 'style',
			'group'    => 'events_card',
			'label'    => esc_html__( 'Separator', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => ECBB_Hover_Controls::ecbb_req_style2_divider_style(),
		];

		$element->controls['ecbb_style2_divider_color'] = [
			'tab'         => 'style',
			'group'       => 'events_card',
			'label'       => esc_html__( 'Separator color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#edf1f7',
			'responsive'  => true,
			'required'    => ECBB_Hover_Controls::ecbb_req_style2_divider_style(),
			'css'         => [
				[
					'property' => 'background-color',
					'selector' => '& .ecbb-event-card__divider',
				],
			],
		];
	}

		private static function ecbb_style_card_selector() {
		return implode( ', ', self::ecbb_style_card_selectors() );
	}

		private static function ecbb_register_category_badge_style_controls( $element, $layout ) {
		$configs = self::ecbb_category_badge_layout_configs();
		if ( ! isset( $configs[ $layout ] ) ) {
			return;
		}

		$c        = $configs[ $layout ];
		$required = $c['style_required'];
		$badge    = '& .event-badge--blue';
		$badge_h  = '& a.event-badge--blue:hover, & .event-badge--blue:hover, & .event-list-card:hover a.event-badge--blue:hover, & .event-grid-card:hover a.event-badge--blue:hover';
		$wrap     = '& .event-badge';

		$element->controls[ $c['sep_key'] ] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Image category', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => $required,
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'typography' ) ] = [
			'tab'        => 'style',
			'group'      => 'featured_image',
			'label'      => esc_html__( 'Typography', 'events-calendar-for-bricks' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => $required,
			'css'        => [
				[
					'property' => 'typography',
					'selector' => $badge,
				],
			],
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'background' ) ] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#244ee7',
			'responsive'  => true,
			'required'    => $required,
			'css'         => [
				[
					'property' => '--ecbb-shell-cat-bg',
					'selector' => '&',
				],
				[
					'property' => 'background-color',
					'selector' => $badge,
				],
			],
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'padding' ) ] = [
			'tab'        => 'style',
			'group'      => 'featured_image',
			'label'      => esc_html__( 'Padding', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'required'   => $required,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'padding', $badge ),
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'margin' ) ] = [
			'tab'        => 'style',
			'group'      => 'featured_image',
			'label'      => esc_html__( 'Margin', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'required'   => $required,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'margin', $wrap ),
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'sep_hover' ) ] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Category hover', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => $required,
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'hover_color' ) ] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Hover color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#ffffff',
			'responsive'  => true,
			'required'    => $required,
			'css'         => ECBB_Part_Fields::ecbb_field_css( 'color', $badge_h ),
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'hover_background' ) ] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Hover background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#1a3fc4',
			'responsive'  => true,
			'required'    => $required,
			'css'         => ECBB_Part_Fields::ecbb_field_css( 'background-color', $badge_h ),
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'hover_text_decoration' ) ] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
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
			'required' => $required,
			'css'      => ECBB_Part_Fields::ecbb_field_css( 'text-decoration', $badge_h ),
		];

		$element->controls[ self::ecbb_shell_category_setting_key( $c, 'hover_animation' ) ] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Hover animation', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => ECBB_Hover_Controls::ecbb_hover_animation_options( false, true ),
			'default'  => '',
			'rerender' => true,
			'required' => $required,
		];
	}

		public static function ecbb_register_category_badge_layout_toggle( $element, $layout ) {
		$configs = self::ecbb_category_badge_layout_configs();
		if ( ! isset( $configs[ $layout ] ) ) {
			return;
		}

		$c = $configs[ $layout ];
		$element->controls[ $c['toggle_key'] ] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Hide category on image', 'events-calendar-for-bricks' ),
			'type'     => 'checkbox',
			'default'  => false,
			'rerender' => true,
			'required' => $c['toggle_required'],
		];
	}

		private static function ecbb_register_image_dimension_controls( $element, array $image_required ) {
		$shared_number = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'type'        => 'number',
			'min'         => 0,
			'step'        => 1,
			'units'       => [
				'px' => 'px',
				'vh' => 'vh',
			],
			'unit'        => 'px',
			'responsive'  => true,
			'required'    => $image_required,
		];

		$element->controls['ecbb_featured_image_min_height'] = array_merge(
			$shared_number,
			[
				'label'       => esc_html__( 'Min height', 'events-calendar-for-bricks' ),
				'placeholder' => '220',
				'css'         => [
					[
						'property' => '--ecbb-featured-image-min-height',
						'selector' => '&',
					],
				],
			]
		);

		$element->controls['ecbb_featured_image_height'] = array_merge(
			$shared_number,
			[
				'label'       => esc_html__( 'Height', 'events-calendar-for-bricks' ),
				'placeholder' => '178',
				'css'         => [
					[
						'property' => '--ecbb-featured-image-height',
						'selector' => '&',
					],
				],
			]
		);
	}

		private static function ecbb_register_vignette_controls( $element, array $image_required ) {
		$element->controls['ecbb_sep_featured_image_vignette'] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Overlay', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => $image_required,
		];

		$element->controls['ecbb_featured_image_vignette'] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Pattern', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => [
				'none'          => esc_html__( 'None', 'events-calendar-for-bricks' ),
				'radial-center' => esc_html__( 'Radial vignette', 'events-calendar-for-bricks' ),
				'bottom-fade'   => esc_html__( 'Bottom fade', 'events-calendar-for-bricks' ),
				'top-fade'      => esc_html__( 'Top fade', 'events-calendar-for-bricks' ),
				'left-fade'     => esc_html__( 'Left fade', 'events-calendar-for-bricks' ),
				'right-fade'    => esc_html__( 'Right fade', 'events-calendar-for-bricks' ),
				'tint'          => esc_html__( 'Color tint', 'events-calendar-for-bricks' ),
			],
			'default'  => 'none',
			'rerender' => true,
			'required' => $image_required,
		];

		$element->controls['ecbb_featured_image_vignette_color'] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Overlay color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#0f172a',
			'responsive'  => true,
			'required'    => ECBB_Hover_Controls::ecbb_req_featured_image_vignette(),
			'css'         => [
				[
					'property' => '--ecbb-vignette-color',
					'selector' => '&',
				],
			],
		];

		$element->controls['ecbb_featured_image_vignette_opacity'] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Overlay strength', 'events-calendar-for-bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 100,
			'step'        => 1,
			'placeholder' => '45',
			'default'     => 45,
			'responsive'  => true,
			'required'    => ECBB_Hover_Controls::ecbb_req_featured_image_vignette(),
			'css'         => [
				[
					'property' => '--ecbb-vignette-opacity',
					'selector' => '&',
				],
			],
		];
	}

		private static function ecbb_shell_category_setting_key( array $config, $name ) {
		$prefix = isset( $config['key_prefix'] ) ? (string) $config['key_prefix'] : 'ecbb_shell_category';
		$suffix = isset( $config['key_suffix'] ) ? (string) $config['key_suffix'] : '';
		return $prefix . '_' . $name . $suffix;
	}

		private static function ecbb_register_featured_image_style_controls( $element ) {
		$image_required = ECBB_Hover_Controls::ecbb_req_featured_image_style();
		self::ecbb_register_image_dimension_controls( $element, $image_required );
		self::ecbb_register_image_hover_controls( $element, $image_required );
		self::ecbb_register_vignette_controls( $element, $image_required );

		self::ecbb_register_category_badge_style_controls( $element, 'list1' );
		self::ecbb_register_category_badge_style_controls( $element, 'grid' );

		$element->controls['ecbb_sep_style_date_badge'] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Image date', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => ECBB_Hover_Controls::ecbb_req_shell_date_badge_style(),
		];

		$element->controls['ecbb_shell_date_background'] = [
			'tab'         => 'style',
			'group'       => 'featured_image',
			'label'       => esc_html__( 'Background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#2147c7',
			'responsive'  => true,
			'required'    => ECBB_Hover_Controls::ecbb_req_shell_date_badge_style(),
			'css'         => [
				[
					'property' => '--ecbb-shell-date-bg',
					'selector' => '&',
				],
				[
					'property' => 'background-color',
					'selector' => '& .ecbb-event-card__date-badge',
				],
			],
		];

		$element->controls['ecbb_shell_date_typography'] = [
			'tab'        => 'style',
			'group'      => 'featured_image',
			'label'      => esc_html__( 'Typography', 'events-calendar-for-bricks' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => ECBB_Hover_Controls::ecbb_req_shell_date_badge_style(),
			'css'        => [
				[
					'property' => 'typography',
					'selector' => '& .ecbb-event-card__date-badge, & .ecbb-event-card__date-badge span, & .ecbb-event-card__date-badge strong',
				],
			],
		];

		$element->controls['ecbb_shell_date_padding'] = [
			'tab'        => 'style',
			'group'      => 'featured_image',
			'label'      => esc_html__( 'Padding', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'required'   => ECBB_Hover_Controls::ecbb_req_shell_date_badge_style(),
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'padding', '& .ecbb-event-card__date-badge' ),
		];

	}

		private static function ecbb_category_badge_layout_configs() {
		return [
		'list1' => [
			'toggle_key'      => 'list1_show_category_badge',
			'toggle_required' => [
				[ 'hide_event_image', '!=', true ],
					[ 'layout_template', '=', 'list' ],
					[ 'list_item_style', '=', 'style-1' ],
				],
				'sep_key'         => 'ecbb_sep_style_image_category_list1',
				'key_prefix'      => 'ecbb_shell_category',
				'style_required'  => ECBB_Hover_Controls::ecbb_req_shell_category_style_list1(),
			],
		'grid'  => [
			'toggle_key'      => 'grid_show_category_badge',
			'toggle_required' => [
				[ 'hide_event_image', '!=', true ],
					[ 'layout_template', '=', 'grid' ],
				],
				'sep_key'         => 'ecbb_sep_style_image_category_grid',
				'key_prefix'      => 'ecbb_shell_category',
				'key_suffix'      => '_grid',
				'style_required'  => ECBB_Hover_Controls::ecbb_req_shell_category_style_grid(),
			],
		];
	}

		private static function ecbb_register_style1_date_style_controls( $element ) {
		$date_required = ECBB_Hover_Controls::ecbb_req_list1_date_column_style();
		$date_typo_sel  = '& .event-list-card__date-inner, & .event-list-card__date .event-list-card__day, & .event-list-card__date .event-list-card__month';
		$date_inner_sel = '& .event-list-card__date-inner';

		$element->controls['ecbb_list1_content_background'] = [
			'tab'         => 'style',
			'group'       => 'style1_date',
			'label'       => esc_html__( 'Inner background', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => 'transparent',
			'responsive'  => true,
			'required'    => $date_required,
			'css'         => [
				[
					'property' => '--ecbb-list1-date-bg',
					'selector' => '&',
				],
				[
					'property' => 'background-color',
					'selector' => '& .event-list-card__date-inner',
				],
			],
		];

		$element->controls['ecbb_list1_date_align'] = [
			'tab'      => 'style',
			'group'    => 'style1_date',
			'label'    => esc_html__( 'Alignment', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => [
				'top'    => esc_html__( 'Top', 'events-calendar-for-bricks' ),
				'center' => esc_html__( 'Middle', 'events-calendar-for-bricks' ),
				'bottom' => esc_html__( 'Bottom', 'events-calendar-for-bricks' ),
			],
			'default'  => 'top',
			'inline'   => true,
			'rerender' => true,
			'required' => $date_required,
		];

		$element->controls['ecbb_list1_date_typography'] = [
			'tab'        => 'style',
			'group'      => 'style1_date',
			'label'      => esc_html__( 'Typography', 'events-calendar-for-bricks' ),
			'type'       => 'typography',
			'exclude'    => [ 'text-align' ],
			'responsive' => true,
			'required'   => $date_required,
			'css'        => [
				[
					'property' => 'typography',
					'selector' => $date_typo_sel,
				],
			],
		];

		$element->controls['ecbb_list1_date_inner_padding'] = [
			'tab'        => 'style',
			'group'      => 'style1_date',
			'label'      => esc_html__( 'Inner padding', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'required'   => $date_required,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'padding', $date_inner_sel ),
		];

		$element->controls['ecbb_list1_date_inner_margin'] = [
			'tab'        => 'style',
			'group'      => 'style1_date',
			'label'      => esc_html__( 'Inner margin', 'events-calendar-for-bricks' ),
			'type'       => 'spacing',
			'responsive' => true,
			'required'   => $date_required,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'margin', $date_inner_sel ),
		];

		$element->controls['ecbb_list1_date_inner_border'] = [
			'tab'        => 'style',
			'group'      => 'style1_date',
			'label'      => esc_html__( 'Inner border', 'events-calendar-for-bricks' ),
			'type'       => 'border',
			'responsive' => true,
			'required'   => $date_required,
			'css'        => ECBB_Part_Fields::ecbb_field_css( 'border', $date_inner_sel ),
		];

		$element->controls['ecbb_list1_date_column_border'] = [
			'tab'         => 'style',
			'group'       => 'style1_date',
			'label'       => esc_html__( 'Divider color', 'events-calendar-for-bricks' ),
			'type'        => 'color',
			'placeholder' => '#e5eaf2',
			'responsive'  => true,
			'required'    => $date_required,
			'css'         => [
				[
					'property' => 'border-color',
					'selector' => '& .event-list-card__date',
				],
			],
		];
	}

		public static function ecbb_register_style_controls( $element ) {
		self::ecbb_register_events_card_style_controls( $element );
		self::ecbb_register_style1_date_style_controls( $element );
		self::ecbb_register_featured_image_style_controls( $element );
	}

		private static function ecbb_register_image_hover_controls( $element, array $image_required ) {
		$element->controls['ecbb_sep_featured_image_hover'] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Image hover', 'events-calendar-for-bricks' ),
			'type'     => 'separator',
			'required' => $image_required,
		];

		$element->controls['ecbb_image_hover_animation'] = [
			'tab'      => 'style',
			'group'    => 'featured_image',
			'label'    => esc_html__( 'Animation', 'events-calendar-for-bricks' ),
			'type'     => 'select',
			'options'  => ECBB_Hover_Controls::ecbb_hover_animation_options( true, true ),
			'default'  => '',
			'rerender' => true,
			'required' => $image_required,
		];
	}

	}
}
