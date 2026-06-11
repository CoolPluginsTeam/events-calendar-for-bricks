<?php
namespace ECBB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Element_ECBB_Events_Widget extends \Bricks\Element {

	public $category = 'general';
	public $name     = 'ecbb-events-loop';
	public $icon     = 'ti-loop';
	public $nestable = false;

	public function get_label() {
		return esc_html__( 'Events Widget', 'ecbb' );
	}

	public function get_keywords() {
		return [ 'event', 'events', 'loop', 'query', 'tec', 'tribe' ];
	}

	public function set_control_groups() {
		// Order: Elements → Events Query → Layouts → Dynamic Messages (Content); Style: Dynamic Messages only.
		$this->control_groups['elements'] = [
			'title' => esc_html__( 'Elements', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['event_query'] = [
			'title' => esc_html__( 'Events Query', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['layouts'] = [
			'title' => esc_html__( 'Layouts', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['dynamic_messages'] = [
			'title' => esc_html__( 'Dynamic Messages', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['dynamic_messages_style'] = [
			'title' => esc_html__( 'Dynamic Messages', 'ecbb' ),
			'tab'   => 'style',
		];
	}

	/**
	 * Empty-state copy (Dynamic Messages → No events found).
	 *
	 * @return string
	 */
	private function ecbb_get_no_events_message() {
		$text = isset( $this->settings['no_events_text'] ) ? trim( (string) $this->settings['no_events_text'] ) : '';
		if ( $text === '' ) {
			return esc_html__( 'No events found', 'ecbb' );
		}
		return $text;
	}

	/**
	 * Markup when the query returns zero events (front end + builder).
	 *
	 * @return void
	 */
	private function ecbb_render_no_events_message() {
		echo '<div class="ecbb-ev__empty" role="status">';
		echo '<p class="ecbb-ev__empty-message">' . esc_html( $this->ecbb_get_no_events_message() ) . '</p>';
		echo '</div>';
	}

	/**
	 * Scoped motion CSS for the empty-state message (transitions, transform presets, fade animations).
	 *
	 * @param string $scope_class Instance scope class (e.g. ecbb-ev--abc).
	 * @return string[]           Raw CSS rules.
	 */
	private function ecbb_build_no_events_dynamic_style_css( $scope_class ) {
		$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
		if ( $scope_class === '' ) {
			return [];
		}

		$sel = '.' . $scope_class . ' .ecbb-ev__empty-message';
		$out = [];

		$dur_ms = isset( $this->settings['no_events_transition_duration'] ) ? (float) $this->settings['no_events_transition_duration'] : 0;
		if ( $dur_ms > 0 ) {
			$d     = $dur_ms . 'ms';
			$out[] = $sel . '{transition:opacity ' . $d . ' ease,transform ' . $d . ' ease,background-color ' . $d . ' ease,color ' . $d . ' ease,border-color ' . $d . ' ease,box-shadow ' . $d . ' ease;}';
		}

		$anim = isset( $this->settings['no_events_hover_animation'] ) ? (string) $this->settings['no_events_hover_animation'] : '';
		if ( $anim !== '' && function_exists( 'ecbb_event_part_hover_animation_css' ) ) {
			$blocks = ecbb_event_part_hover_animation_css( $sel, $anim );
			if ( ! empty( $blocks['base'] ) ) {
				$out[] = $blocks['base'];
			}
			if ( ! empty( $blocks['hover'] ) ) {
				$out[] = $blocks['hover'];
			}
		} else {
			$transform = isset( $this->settings['no_events_transform_hover'] ) ? (string) $this->settings['no_events_transform_hover'] : 'none';
			$hover_tf  = '';
			switch ( $transform ) {
				case 'lift':
					$hover_tf = 'transform:translateY(-3px);';
					break;
				case 'scale_up':
					$hover_tf = 'transform:scale(1.03);';
					break;
				case 'scale_down':
					$hover_tf = 'transform:scale(0.98);';
					break;
				case 'none':
				default:
					$hover_tf = '';
					break;
			}
			if ( $hover_tf !== '' ) {
				$out[] = $sel . ':hover{' . $hover_tf . '}';
			}
		}

		if ( isset( $this->settings['no_events_opacity_hover'] ) && $this->settings['no_events_opacity_hover'] !== '' ) {
			$op = (float) $this->settings['no_events_opacity_hover'];
			if ( $op >= 0 && $op <= 1 ) {
				$out[] = $sel . ':hover{opacity:' . $op . ';}';
			}
		}

		return $out;
	}

	/**
	 * Part slugs that expose hover styling in the repeater Style tab.
	 *
	 * @return string[]
	 */
	private function ecbb_repeater_hover_part_slugs() {
		return function_exists( 'ecbb_event_part_types_with_hover_style_controls' )
			? ecbb_event_part_types_with_hover_style_controls()
			: [ 'title', 'read_more', 'event_tickets', 'event_rsvp', 'venue', 'categories', 'tags', 'image' ];
	}

	/**
	 * @return array<int,array{0:string,1:string,2:mixed}>
	 */
	private function ecbb_repeater_required_hover_toggle_visible() {
		return [ [ 'part', '=', $this->ecbb_repeater_hover_part_slugs() ] ];
	}

	/**
	 * @return array<int,array{0:string,1:string,2:mixed}>
	 */
	private function ecbb_repeater_required_hover_details() {
		return [
			[ 'part', '=', $this->ecbb_repeater_hover_part_slugs() ],
			[ 'ecbb_use_hover', '=', true ],
		];
	}

	/**
	 * Parts where text-decoration on hover applies (excludes featured image).
	 *
	 * @return string[]
	 */
	private function ecbb_repeater_hover_text_decoration_part_slugs() {
		return [ 'title', 'read_more', 'event_tickets', 'event_rsvp', 'venue', 'categories', 'tags' ];
	}

	/**
	 * @return array<int,array{0:string,1:string,2:mixed}>
	 */
	private function ecbb_repeater_required_hover_text_decoration() {
		return [
			[ 'part', '=', $this->ecbb_repeater_hover_text_decoration_part_slugs() ],
			[ 'ecbb_use_hover', '=', true ],
		];
	}

	public function set_controls() {
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

		$this->controls['event_type'] = [
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

		$this->controls['event_categories'] = [
			'tab'         => 'content',
			'group'       => 'event_query',
			'label'       => esc_html__( 'Event categories', 'ecbb' ),
			'type'        => 'select',
			'options'     => $event_cat_options,
			'multiple'    => true,
			'placeholder' => esc_html__( 'All categories', 'ecbb' ),
		];

		$this->controls['event_time_mode'] = [
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

		$this->controls['event_range_start'] = [
			'tab'      => 'content',
			'group'    => 'event_query',
			'label'    => esc_html__( 'Range start', 'ecbb' ),
			'type'     => 'datepicker',
			'required' => [ 'event_time_mode', '=', 'between' ],
		];

		$this->controls['event_range_end'] = [
			'tab'      => 'content',
			'group'    => 'event_query',
			'label'    => esc_html__( 'Range end', 'ecbb' ),
			'type'     => 'datepicker',
			'required' => [ 'event_time_mode', '=', 'between' ],
		];

		$this->controls['posts_per_page'] = [
			'tab'         => 'content',
			'group'       => 'event_query',
			'label'       => esc_html__( 'Number of events', 'ecbb' ),
			'type'        => 'number',
			'min'         => -1,
			'step'        => 1,
			'default'     => 10,
			'placeholder' => 10,
		];

		$this->controls['order'] = [
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

		$this->controls['item_gap'] = [
			'tab'         => 'content',
			'group'       => 'event_query',
			'label'       => esc_html__( 'Gap between events', 'ecbb' ),
			'type'        => 'number',
			'min'         => 0,
			'step'        => 1,
			'placeholder' => '24',
			'default'     => 24,
			'description' => esc_html__( 'Space between each event card in the list or grid.', 'ecbb' ),
		];

		$this->controls['item_gap_unit'] = [
			'tab'     => 'content',
			'group'   => 'event_query',
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

		$this->controls['date_format'] = [
			'tab'      => 'content',
			'group'    => 'event_query',
			'label'    => esc_html__( 'Date formats', 'ecbb' ),
			'type'     => 'select',
			'default'  => 'default',
			'required' => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			],
			'attributes' => [
				'style' => 'width: 50%;',
			],
			'options'  => [
				'default' => esc_html__( 'Default (01 January 2025)', 'ecbb' ),
				'MD,Y'    => esc_html__( 'Md,Y (Jan 01, 2025)', 'ecbb' ),
				'FD,Y'    => esc_html__( 'Fd,Y (January 01, 2025)', 'ecbb' ),
				'DM'      => esc_html__( 'dM (01 Jan)', 'ecbb' ),
				'DML'     => esc_html__( 'dML (01 Jan Monday)', 'ecbb' ),
				'DF'      => esc_html__( 'dF (01 January)', 'ecbb' ),
				'MD'      => esc_html__( 'Md (Jan 01)', 'ecbb' ),
				'FD'      => esc_html__( 'Fd (January 01)', 'ecbb' ),
				'MD,YT'   => esc_html__( 'Md,YT (Jan 01, 2025 8:00am-5:00pm)', 'ecbb' ),
				'full'    => esc_html__( 'Full (01 January 2025 8:00am-5:00pm)', 'ecbb' ),
				'jMl'     => esc_html__( 'jMl (1 Jan Monday)', 'ecbb' ),
				'd.FY'    => esc_html__( 'd.FY (01. January 2025)', 'ecbb' ),
				'd.F'     => esc_html__( 'd.F (01. January)', 'ecbb' ),
				'ldF'     => esc_html__( 'ldF (Monday 01 January)', 'ecbb' ),
				'Mdl'     => esc_html__( 'Mdl (Jan 01 Monday)', 'ecbb' ),
				'd.Ml'    => esc_html__( 'd.Ml (01. Jan Monday)', 'ecbb' ),
				'dFT'     => esc_html__( 'dFT (01 January 8:00am-5:00pm)', 'ecbb' ),
				'sed'     => esc_html__( 'SED (01 Jan - 02 Jan 2025)', 'ecbb' ),
				'sedt'    => esc_html__( 'SEDT (01 Jan - 02 Jan 2025 8:00am-5:00pm)', 'ecbb' ),
				'D.j.F'   => esc_html__( 'D.,j. F (Wed., 15. May)', 'ecbb' ),
			],
		];

		$this->controls['layout_template'] = [
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

		$this->controls['list_item_style'] = [
			'tab'         => 'content',
			'group'       => 'layouts',
			'label'       => esc_html__( 'Select style', 'ecbb' ),
			'type'        => 'select',
			'options'     => [
				'style-1' => esc_html__( 'Style 1', 'ecbb' ),
				'style-2' => esc_html__( 'Style 2', 'ecbb' ),
			],
			'default'     => 'style-1',
			'required'    => [ 'layout_template', '=', 'list' ],
		];

		$this->controls['style2_show_month_headings'] = [
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

		$this->controls['grid_cols_desktop'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Grid columns (desktop)', 'ecbb' ),
			'type'     => 'number',
			'min'      => 1,
			'step'     => 1,
			'default'  => 3,
			'required' => [ 'layout_template', '=', 'grid' ],
		];

		$this->controls['grid_cols_tablet'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Grid columns (tablet)', 'ecbb' ),
			'type'     => 'number',
			'min'      => 1,
			'step'     => 1,
			'default'  => 2,
			'required' => [ 'layout_template', '=', 'grid' ],
		];

		$this->controls['grid_cols_mobile'] = [
			'tab'      => 'content',
			'group'    => 'layouts',
			'label'    => esc_html__( 'Grid columns (mobile)', 'ecbb' ),
			'type'     => 'number',
			'min'      => 1,
			'step'     => 1,
			'default'  => 1,
			'required' => [ 'layout_template', '=', 'grid' ],
		];

		// ── Dynamic Messages — No events found ──

		$this->controls['no_events_text'] = [
			'tab'         => 'content',
			'group'       => 'dynamic_messages',
			'label'       => esc_html__( 'No events found text', 'ecbb' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'No events found', 'ecbb' ),
			'default'     => esc_html__( 'No events found', 'ecbb' ),
		];

		// ── Dynamic Messages — Style (empty state) ──

		$this->controls['no_events_sep'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'type'  => 'separator',
			'label' => esc_html__( 'No events found', 'ecbb' ),
		];

		$this->controls['no_events_align'] = [
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

		$this->controls['no_events_align_items'] = [
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

		$this->controls['no_events_margin'] = [
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

		$this->controls['no_events_max_width'] = [
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

		$this->controls['no_events_sep_typography'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'type'  => 'separator',
			'label' => esc_html__( 'Typography', 'ecbb' ),
		];

		$this->controls['no_events_typography'] = [
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

		$this->controls['no_events_color'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Text color', 'ecbb' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_bg'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Background', 'ecbb' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_border'] = [
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

		$this->controls['no_events_radius'] = [
			'tab'         => 'style',
			'group'       => 'dynamic_messages_style',
			'label'       => esc_html__( 'Border radius', 'ecbb' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '8px',
			'css'         => [
				[
					'property' => 'border-radius',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_padding'] = [
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

		$this->controls['no_events_text_align'] = [
			'tab'     => 'style',
			'group'   => 'dynamic_messages_style',
			'label'   => esc_html__( 'Text align', 'ecbb' ),
			'type'    => 'text-align',
			'inline'  => true,
			'default' => 'center',
			'css'     => [
				[
					'property' => 'text-align',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_text_transform'] = [
			'tab'     => 'style',
			'group'   => 'dynamic_messages_style',
			'label'   => esc_html__( 'Text transform', 'ecbb' ),
			'type'    => 'select',
			'options' => [
				'none'       => esc_html__( 'None', 'ecbb' ),
				'uppercase'  => esc_html__( 'Uppercase', 'ecbb' ),
				'lowercase'  => esc_html__( 'Lowercase', 'ecbb' ),
				'capitalize' => esc_html__( 'Capitalize', 'ecbb' ),
			],
			'inline'  => true,
			'default' => 'none',
			'css'     => [
				[
					'property' => 'text-transform',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_letter_spacing'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Letter spacing', 'ecbb' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'letter-spacing',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_box_shadow'] = [
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

		$this->controls['no_events_width'] = [
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

		$this->controls['no_events_min_height'] = [
			'tab'         => 'style',
			'group'       => 'dynamic_messages_style',
			'label'       => esc_html__( 'Wrapper min height', 'ecbb' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '',
			'css'         => [
				[
					'property' => 'min-height',
					'selector' => '& .ecbb-ev__empty',
				],
			],
		];

		$this->controls['no_events_wrapper_padding'] = [
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

		$this->controls['no_events_line_height'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Line height', 'ecbb' ),
			'type'  => 'number',
			'step'  => 0.1,
			'css'   => [
				[
					'property' => 'line-height',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_display'] = [
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

		$this->controls['no_events_cursor'] = [
			'tab'     => 'style',
			'group'   => 'dynamic_messages_style',
			'label'   => esc_html__( 'Cursor', 'ecbb' ),
			'type'    => 'select',
			'options' => [
				'default' => esc_html__( 'Default', 'ecbb' ),
				'pointer' => esc_html__( 'Pointer', 'ecbb' ),
				'text'    => esc_html__( 'Text', 'ecbb' ),
			],
			'inline'  => true,
			'default' => 'default',
			'css'     => [
				[
					'property' => 'cursor',
					'selector' => '& .ecbb-ev__empty-message',
				],
			],
		];

		$this->controls['no_events_sep_hover'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'type'  => 'separator',
			'label' => esc_html__( 'Hover', 'ecbb' ),
		];

		$this->controls['no_events_color_hover'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Text color (hover)', 'ecbb' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '& .ecbb-ev__empty-message:hover',
				],
			],
		];

		$this->controls['no_events_bg_hover'] = [
			'tab'   => 'style',
			'group' => 'dynamic_messages_style',
			'label' => esc_html__( 'Background (hover)', 'ecbb' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '& .ecbb-ev__empty-message:hover',
				],
			],
		];

		$this->controls['no_events_border_hover'] = [
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

		$this->controls['no_events_box_shadow_hover'] = [
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

		$this->controls['no_events_text_decoration_hover'] = [
			'tab'     => 'style',
			'group'   => 'dynamic_messages_style',
			'label'   => esc_html__( 'Text decoration (hover)', 'ecbb' ),
			'type'    => 'select',
			'options' => [
				''             => esc_html__( 'Default', 'ecbb' ),
				'none'         => esc_html__( 'None', 'ecbb' ),
				'underline'    => esc_html__( 'Underline', 'ecbb' ),
				'overline'     => esc_html__( 'Overline', 'ecbb' ),
				'line-through' => esc_html__( 'Line through', 'ecbb' ),
			],
			'inline'  => true,
			'default' => '',
			'css'     => [
				[
					'property' => 'text-decoration',
					'selector' => '& .ecbb-ev__empty-message:hover',
				],
			],
		];

		$this->controls['no_events_transform_hover'] = [
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
			'inline'  => true,
			'default' => 'none',
			'required' => [
				[ 'no_events_hover_animation', '=', '' ],
			],
		];

		$this->controls['no_events_hover_animation'] = [
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

		$this->controls['no_events_opacity_hover'] = [
			'tab'         => 'style',
			'group'       => 'dynamic_messages_style',
			'label'       => esc_html__( 'Opacity on hover', 'ecbb' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 1,
			'step'        => 0.05,
			'placeholder' => '',
		];

		$this->controls['no_events_transition_duration'] = [
			'tab'         => 'style',
			'group'       => 'dynamic_messages_style',
			'label'       => esc_html__( 'Transition duration (ms)', 'ecbb' ),
			'type'        => 'number',
			'min'         => 0,
			'step'        => 50,
			'placeholder' => '200',
			'default'     => 200,
		];

		$repeater_fields = [
			'part'  => [
				'label'   => esc_html__( 'Part', 'ecbb' ),
				'type'    => 'select',
				'options' => [
					'title'        => esc_html__( 'Title', 'ecbb' ),
					'description'  => esc_html__( 'Description', 'ecbb' ),
					'date'         => esc_html__( 'Day & time range', 'ecbb' ),
					'event_date'   => esc_html__( 'Date', 'ecbb' ),
					'event_time'   => esc_html__( 'Time', 'ecbb' ),
					'event_day'    => esc_html__( 'Day', 'ecbb' ),
					'event_cost'   => esc_html__( 'Cost', 'ecbb' ),
					'event_tickets' => esc_html__( 'Tickets', 'ecbb' ),
					'event_rsvp'   => esc_html__( 'RSVP', 'ecbb' ),
					'read_more'    => esc_html__( 'Read more', 'ecbb' ),
					'venue'        => esc_html__( 'Venue', 'ecbb' ),
					'venue_full_address' => esc_html__( 'Venue — full address', 'ecbb' ),
					'venue_street'       => esc_html__( 'Venue — street', 'ecbb' ),
					'venue_city'         => esc_html__( 'Venue — city', 'ecbb' ),
					'venue_state'        => esc_html__( 'Venue — state / province', 'ecbb' ),
					'venue_zip'          => esc_html__( 'Venue — ZIP / postal', 'ecbb' ),
					'venue_country'      => esc_html__( 'Venue — country', 'ecbb' ),
					'venue_phone'        => esc_html__( 'Venue — phone', 'ecbb' ),
					'venue_website'      => esc_html__( 'Venue — website', 'ecbb' ),
					'event_map_link'     => esc_html__( 'Map link', 'ecbb' ),
					'event_website'      => esc_html__( 'Event website', 'ecbb' ),
					'event_phone'        => esc_html__( 'Event phone', 'ecbb' ),
					'organizer'    => esc_html__( 'Organizer', 'ecbb' ),
					'organizer_email'   => esc_html__( 'Organizer — email', 'ecbb' ),
					'organizer_phone'   => esc_html__( 'Organizer — phone', 'ecbb' ),
					'organizer_website' => esc_html__( 'Organizer — website', 'ecbb' ),
					'categories'   => esc_html__( 'Categories', 'ecbb' ),
					'tags'         => esc_html__( 'Tags', 'ecbb' ),
					'image'        => esc_html__( 'Featured image', 'ecbb' ),
				],
				'default' => 'title',
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
			'title_above_link' => [
				'label'    => esc_html__( 'Show link above title', 'ecbb' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => [ 'part', '=', 'title' ],
			],
			'title_above_link_text' => [
				'label'       => esc_html__( 'Above title link text', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => esc_html__( 'View event', 'ecbb' ),
				'default'     => esc_html__( 'View event', 'ecbb' ),
				'required'    => [
					[ 'part', '=', 'title' ],
					[ 'title_above_link', '=', true ],
				],
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
			'desc_length' => [
				'label'    => esc_html__( 'Content length', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					'short'  => esc_html__( 'Short', 'ecbb' ),
					'full'   => esc_html__( 'Full', 'ecbb' ),
					'custom' => esc_html__( 'Custom words', 'ecbb' ),
				],
				'default'  => 'short',
				'required' => [ 'part', '=', 'description' ],
			],
			'desc_words' => [
				'label'       => esc_html__( 'Words', 'ecbb' ),
				'type'        => 'number',
				'min'         => 5,
				'step'        => 1,
				'placeholder' => 55,
				'default'     => 55,
				'required'    => [
					[ 'part', '=', 'description' ],
					[ 'desc_length', '=', 'custom' ],
				],
			],
			'date_format_preset' => [
				'label'       => esc_html__( 'Date / time format', 'ecbb' ),
				'type'        => 'select',
				'options'     => [
					''                 => esc_html__( 'Default', 'ecbb' ),

									'MD,Y'    => 'Md,Y (Jan 01, 2025)',
									'FD,Y'    => 'Fd,Y (January 01, 2025)',
									'DM'      => 'dM (01 Jan)',
									'DML'     => 'dML (01 Jan Monday)',
									'DF'      => 'dF (01 January)',
									'MD'      => 'Md (Jan 01)',
									'FD'      => 'Fd (January 01)',
									'MD,YT'   => 'Md,YT (Jan 01, 2025 8:00am-5:00pm)',
									'full'    => 'Full (01 January 2025 8:00am-5:00pm)',
									'jMl'     => 'jMl (1 Jan Monday)',
									'd.FY'    => 'd.FY (01. January 2025)',
									'd.F'     => 'd.F (01. January)',
									'ldF'     => 'ldF (Monday 01 January)',
									'Mdl'     => 'Mdl (Jan 01 Monday)',
									'd.Ml'    => 'd.Ml (01. Jan Monday)',
									'dFT'     => 'dFT (01 January 8:00am-5:00pm)',
									'sed'     => 'SED (01 Jan - 02 Jan 2025)',
									'sedt'    => 'SEDT (01 Jan - 02 Jan 2025 8:00am-5:00pm)',
									'D.j.F'   => 'D.,j. F (Wed., 15. May)',		
					'custom'           => esc_html__( 'Custom…', 'ecbb' ),
				],
				'default'     => '',
				'required'    => [ 'part', '=', [ 'event_date', 'event_time' ] ],
			],
			'date_format_custom' => [
				'label'       => esc_html__( 'Custom format', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => 'F j, Y g:i a',
				'required'    => [
					[ 'part', '=', [ 'event_date', 'event_time' ] ],
					[ 'date_format_preset', '=', 'custom' ],
				],
			],
			'date_text_transform' => [
				'label'       => esc_html__( 'Text transform', 'ecbb' ),
				'type'        => 'select',
				'options'     => [
					'capitalize' => esc_html__( 'Capitalize', 'ecbb' ),
					'none'       => esc_html__( 'None', 'ecbb' ),
					'uppercase'  => esc_html__( 'Uppercase', 'ecbb' ),
					'lowercase'  => esc_html__( 'Lowercase', 'ecbb' ),
				],
				'default'     => 'capitalize',
				'required'    => [ 'part', '=', 'date' ],
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
			'read_more_text' => [
				'label'       => esc_html__( 'Read more text', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => esc_html__( 'Read more', 'ecbb' ),
				'default'     => esc_html__( 'Read more', 'ecbb' ),
				'required'    => [ 'part', '=', 'read_more' ],
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
					'part',
					'=',
					[
						'venue',
						'venue_full_address',
						'venue_street',
						'venue_city',
						'venue_state',
						'venue_zip',
						'venue_country',
						'venue_phone',
					],
				],
			],
			'detail_link_text' => [
				'label'       => esc_html__( 'Link label', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => esc_html__( 'Open map', 'ecbb' ),
				'required'    => [
					'part',
					'=',
					[ 'venue_website', 'event_website', 'organizer_website', 'event_map_link' ],
				],
			],
			'organizer_link' => [
				'label'    => esc_html__( 'Link to organizer', 'ecbb' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => [ 'part', '=', 'organizer' ],
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
				'label'    => esc_html__( 'Prefix', 'ecbb' ),
				'type'     => 'text',
				'default'  => '',
				'required' => [ 'part', '=', 'event_cost' ],
			],
			'cost_suffix' => [
				'label'    => esc_html__( 'Suffix', 'ecbb' ),
				'type'     => 'text',
				'default'  => '',
				'required' => [ 'part', '=', 'event_cost' ],
			],
			'btn_style' => [
				'label'    => esc_html__( 'Button styles', 'ecbb' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => [ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
			],
			'btn_bg' => [
				'label'    => esc_html__( 'Button background', 'ecbb' ),
				'type'     => 'color',
				'required' => [
					[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
					[ 'btn_style', '=', true ],
				],
			],
			'btn_text_color' => [
				'label'    => esc_html__( 'Button text color', 'ecbb' ),
				'type'     => 'color',
				'required' => [
					[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
					[ 'btn_style', '=', true ],
				],
			],
			'btn_radius' => [
				'label'       => esc_html__( 'Button radius', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '8px',
				'default'     => '8px',
				'required'    => [
					[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
					[ 'btn_style', '=', true ],
				],
			],
			'btn_padding_y' => [
				'label'       => esc_html__( 'Button padding Y', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '10px',
				'default'     => '10px',
				'required'    => [
					[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
					[ 'btn_style', '=', true ],
				],
			],
			'btn_padding_x' => [
				'label'       => esc_html__( 'Button padding X', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '14px',
				'default'     => '14px',
				'required'    => [
					[ 'part', '=', [ 'event_tickets', 'event_rsvp', 'read_more' ] ],
					[ 'btn_style', '=', true ],
				],
			],
			'image_aspect_ratio' => [
				'label'    => esc_html__( 'Aspect ratio', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					''      => esc_html__( 'Default', 'ecbb' ),
					'1/1'   => '1:1',
					'4/3'   => '4:3',
					'3/2'   => '3:2',
					'16/9'  => '16:9',
					'21/9'  => '21:9',
				],
				'default'  => '',
				'required' => [ 'part', '=', 'image' ],
			],
			'ecbb_image_border_width' => [
				'label'       => esc_html__( 'Border width', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '0px',
				'default'     => '0px',
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_image_border_style' => [
				'label'    => esc_html__( 'Border style', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					'solid'  => esc_html__( 'Solid', 'ecbb' ),
					'dashed' => esc_html__( 'Dashed', 'ecbb' ),
					'dotted' => esc_html__( 'Dotted', 'ecbb' ),
				],
				'default'  => 'solid',
				'required' => [ 'part', '=', 'image' ],
			],
			'ecbb_image_border_color' => [
				'label'    => esc_html__( 'Border color', 'ecbb' ),
				'type'     => 'color',
				'required' => [ 'part', '=', 'image' ],
			],
			'ecbb_text_align' => [
				'label'    => esc_html__( 'Text align', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					''       => esc_html__( 'Default', 'ecbb' ),
					'left'   => esc_html__( 'Left', 'ecbb' ),
					'center' => esc_html__( 'Center', 'ecbb' ),
					'right'  => esc_html__( 'Right', 'ecbb' ),
				],
				'default'  => '',
				'required' => [ 'part', '!=', 'image' ],
			],
			'ecbb_line_height' => [
				'label'       => esc_html__( 'Line height', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '1',
				'required'    => [ 'part', '!=', 'image' ],
			],
			'ecbb_letter_spacing' => [
				'label'       => esc_html__( 'Letter spacing', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '0px',
				'required'    => [ 'part', '!=', 'image' ],
			],
			'image_size' => [
				'label'       => esc_html__( 'Image size', 'ecbb' ),
				'type'        => 'select',
				'options'     => function_exists( 'ecbb_get_image_size_control_options' ) ? ecbb_get_image_size_control_options() : [ 'large' => 'large', 'full' => 'full' ],
				'default'     => '',
				'placeholder' => esc_html__( 'Default', 'ecbb' ),
				'required'    => [ 'part', '=', 'image' ],
			],
			'image_size_hover' => [
				'label'       => esc_html__( 'Image size (hover)', 'ecbb' ),
				'type'        => 'select',
				'options'     => array_merge(
					[ '' => esc_html__( 'Same as default', 'ecbb' ) ],
					function_exists( 'ecbb_get_image_size_control_options' )
						? array_diff_key( ecbb_get_image_size_control_options(), [ '' => true ] )
						: [ 'large' => 'large', 'full' => 'full' ]
				),
				'default'     => '',
				'required'    => [
					[ 'part', '=', 'image' ],
					[ 'ecbb_use_hover', '=', true ],
				],
			],
			'ecbb_image_object_align' => [
				'label'       => esc_html__( 'Image alignment', 'ecbb' ),
				'type'        => 'select',
				'options'     => function_exists( 'ecbb_get_image_object_align_control_options' ) ? ecbb_get_image_object_align_control_options() : [],
				'default'     => '',
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_image_object_align_hover' => [
				'label'       => esc_html__( 'Image alignment (hover)', 'ecbb' ),
				'type'        => 'select',
				'options'     => array_merge(
					[ '' => esc_html__( 'Same as default', 'ecbb' ) ],
					function_exists( 'ecbb_get_image_object_align_control_options' )
						? array_diff_key( ecbb_get_image_object_align_control_options(), [ '' => true ] )
						: []
				),
				'default'     => '',
				'required'    => [
					[ 'part', '=', 'image' ],
					[ 'ecbb_use_hover', '=', true ],
				],
			],
			'image_link' => [
				'label'       => esc_html__( 'Link image to event', 'ecbb' ),
				'type'        => 'checkbox',
				'default'     => true,
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_color' => [
				'label' => esc_html__( 'Color', 'ecbb' ),
				'type'  => 'color',
			],
			'ecbb_background' => [
				'label'       => esc_html__( 'Background color', 'ecbb' ),
				'type'        => 'color',
			],
			'ecbb_background_inner' => [
				'label'       => esc_html__( 'Inner background color', 'ecbb' ),
				'type'        => 'color',
			],
			'ecbb_use_hover' => [
				'label'    => esc_html__( 'Enable hover styles', 'ecbb' ),
				'type'     => 'checkbox',
				'inline'   => true,
				'default'  => false,
				'required' => $this->ecbb_repeater_required_hover_toggle_visible(),
			],
			'ecbb_hover_color' => [
				'label'         => esc_html__( 'Hover color', 'ecbb' ),
				'type'          => 'color',
				'required'      => $this->ecbb_repeater_required_hover_details(),
			],
			'ecbb_hover_text_decoration' => [
				'label'    => esc_html__( 'Text decoration (hover)', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					''           => esc_html__( 'Default', 'ecbb' ),
					'none'       => esc_html__( 'None', 'ecbb' ),
					'underline'  => esc_html__( 'Underline', 'ecbb' ),
					'overline'   => esc_html__( 'Overline', 'ecbb' ),
					'line-through' => esc_html__( 'Line through', 'ecbb' ),
				],
				'default'  => '',
				'required' => $this->ecbb_repeater_required_hover_text_decoration(),
			],
			'ecbb_hover_animation' => [
				'label'       => esc_html__( 'Fade in animation (hover)', 'ecbb' ),
				'type'        => 'select',
				'options'     => [
					''             => esc_html__( 'Default', 'ecbb' ),
					'fade_in_up'   => esc_html__( 'Fade in up', 'ecbb' ),
					'fade_in_right' => esc_html__( 'Fade in right', 'ecbb' ),
					'fade_in_down' => esc_html__( 'Fade in down', 'ecbb' ),
					'fade_in_left' => esc_html__( 'Fade in left', 'ecbb' ),
					'zoom_in'      => esc_html__( 'Zoom in', 'ecbb' ),
					'zoom_out'     => esc_html__( 'Zoom out', 'ecbb' ),
				],
				'default'     => '',
				'required'    => $this->ecbb_repeater_required_hover_details(),
			],
			'ecbb_font_size' => [
				'label'       => esc_html__( 'Font size', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '16px',
				'required'    => [ 'part', '!=', 'image' ],
			],
			'ecbb_font_weight' => [
				'label'       => esc_html__( 'Font weight', 'ecbb' ),
				'type'        => 'number',
				'min'         => 100,
				'max'         => 900,
				'step'        => 100,
				'placeholder' => 400,
				'required'    => [ 'part', '!=', 'image' ],
			],
			'ecbb_image_radius' => [
				'label'       => esc_html__( 'Image radius', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '0px',
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_image_width' => [
				'label'       => esc_html__( 'Image width', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '100%',
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_image_height' => [
				'label'       => esc_html__( 'Image height', 'ecbb' ),
				'type'        => 'text',
				'placeholder' => '',
				'required'    => [ 'part', '=', 'image' ],
			],
			'ecbb_image_fit' => [
				'label'    => esc_html__( 'Image fit', 'ecbb' ),
				'type'     => 'select',
				'options'  => [
					''        => esc_html__( 'Default', 'ecbb' ),
					'cover'   => 'cover',
					'contain' => 'contain',
					'fill'    => 'fill',
					'none'    => 'none',
					'scale-down' => 'scale-down',
				],
				'default'  => '',
				'required' => [ 'part', '=', 'image' ],
			],
		];

		$this->controls['parts_style1'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'label'         => esc_html__( 'Event parts — Style 1 (list)', 'ecbb' ),
			'description'   => esc_html__( 'Shown when Template is List and Select style is Style 1. Drag rows to reorder.', 'ecbb' ),
			'titleProperty' => 'part',
			'required'      => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			],
			'default'       => function_exists( 'ecbb_list1_default_parts_rows' )
				? ecbb_list1_default_parts_rows()
				: [
					[
						'part'            => 'categories',
						'terms_link'      => true,
						'terms_separator' => ', ',
					],
					[
						'part' => 'title',
						'tag'  => 'h3',
						'link' => true,
					],
					[
						'part'                => 'date',
						'date_text_transform' => 'none',
					],
					[
						'part' => 'venue',
					],
					[
						'part'        => 'description',
						'desc_source' => 'content',
						'desc_length' => 'short',
					],
					[
						'part'           => 'read_more',
						'read_more_text' => esc_html__( 'Find Out More', 'ecbb' ),
					],
				],
			'fields'        => $repeater_fields,
		];

		$this->controls['parts_style2'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'label'         => esc_html__( 'Event parts — Style 2 (list)', 'ecbb' ),
			'description'   => esc_html__( 'Shown when Template is List and Select style is Style 2. Drag rows to reorder.', 'ecbb' ),
			'titleProperty' => 'part',
			'required'      => [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			],
			'default'       => function_exists( 'ecbb_list2_default_parts_rows' )
				? ecbb_list2_default_parts_rows()
				: [
					[
						'part'                  => 'date',
						'date_text_transform'   => 'uppercase',
						'ecbb_color'            => '',
						'ecbb_background'       => '',
						'ecbb_background_inner' => '',
					],
					[
						'part'       => 'title',
						'tag'        => 'h3',
						'link'       => true,
						'ecbb_color' => '',
					],
					[
						'part'       => 'venue',
						'ecbb_color' => '',
					],
					[
						'part'        => 'description',
						'desc_source' => 'content',
						'ecbb_color'  => '',
					],
					[
						'part'           => 'read_more',
						'read_more_text' => esc_html__( 'More Details', 'ecbb' ),
					],
				],
			'fields'        => $repeater_fields,
		];

		$this->controls['parts_grid'] = [
			'tab'           => 'content',
			'group'         => 'elements',
			'type'          => 'repeater',
			'label'         => esc_html__( 'Event parts — Grid', 'ecbb' ),
			'description'   => esc_html__( 'Shown when Template is Grid. The card image and framed date are fixed; this repeater drives the right column (time, title, venue, cost by default). Image / calendar-only rows are skipped in the grid body.', 'ecbb' ),
			'titleProperty' => 'part',
			'required'      => [ 'layout_template', '=', 'grid' ],
			'default'       => function_exists( 'ecbb_events_widget_grid_default_parts_rows' )
				? ecbb_events_widget_grid_default_parts_rows()
				: [
					[
						'part'                => 'date',
						'date_text_transform' => 'none',
					],
					[
						'part' => 'title',
						'tag'  => 'h3',
						'link' => true,
					],
					[
						'part' => 'venue',
					],
					[
						'part'            => 'event_cost',
						'cost_currency'   => 'symbol',
					],
				],
			'fields'        => $repeater_fields,
		];
	}

	/**
	 * @param mixed $value Bricks color control value.
	 * @return string Normalized CSS color or empty string.
	 */
	private function ecbb_normalize_color_value( $value ) {
		return function_exists( 'ecbb_normalize_bricks_color' ) ? ecbb_normalize_bricks_color( $value ) : '';
	}

	private function ecbb_normalize_css_size( $value, $default_unit = 'px' ) {
		$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
		if ( $value === '' ) {
			return '';
		}

		// If user provided unit, keep it.
		if ( preg_match( '/^-?\\d*\\.?\\d+(px|rem|em|%)$/', $value ) ) {
			return $value;
		}

		// If only number, default to px.
		if ( preg_match( '/^-?\\d*\\.?\\d+$/', $value ) ) {
			$unit = in_array( $default_unit, [ 'px', 'rem', 'em', '%' ], true ) ? $default_unit : 'px';
			return $value . $unit;
		}

		return '';
	}

	private function ecbb_get_instance_scope_class() {
		$id = property_exists( $this, 'id' ) && $this->id ? (string) $this->id : '';
		$id = preg_replace( '/[^a-zA-Z0-9\-_]/', '', $id );
		if ( $id === '' ) {
			$id = 'ecbb-' . substr( md5( wp_json_encode( $this->settings ) ), 0, 8 );
		}
		return 'ecbb-ev--' . $id;
	}

	private function ecbb_build_inline_style_attr( array $item, $allow_radius = false ) {
		$styles = [];

		// Text color is output in scoped <style> (see render()) so :hover rules can override.
		// Do not set color here with !important — inline important beats stylesheet hover.

		$size = $this->ecbb_normalize_css_size( $item['ecbb_font_size'] ?? '', 'px' );
		if ( $size !== '' ) {
			$styles[] = 'font-size:' . $size;
		}

		if ( isset( $item['ecbb_font_weight'] ) && $item['ecbb_font_weight'] !== '' && is_numeric( $item['ecbb_font_weight'] ) ) {
			$styles[] = 'font-weight:' . (int) $item['ecbb_font_weight'];
		}

		if ( ! empty( $item['ecbb_text_align'] ) && is_string( $item['ecbb_text_align'] ) && in_array( $item['ecbb_text_align'], [ 'left', 'center', 'right' ], true ) ) {
			$styles[] = 'text-align:' . $item['ecbb_text_align'];
		}

		if ( isset( $item['ecbb_line_height'] ) && is_string( $item['ecbb_line_height'] ) && trim( $item['ecbb_line_height'] ) !== '' ) {
			$styles[] = 'line-height:' . trim( $item['ecbb_line_height'] );
		}

		if ( isset( $item['ecbb_letter_spacing'] ) && is_string( $item['ecbb_letter_spacing'] ) && trim( $item['ecbb_letter_spacing'] ) !== '' ) {
			$styles[] = 'letter-spacing:' . trim( $item['ecbb_letter_spacing'] );
		}

		if ( $allow_radius ) {
			if ( ! empty( $item['image_aspect_ratio'] ) && is_string( $item['image_aspect_ratio'] ) && preg_match( '/^\\d+\\/\\d+$/', $item['image_aspect_ratio'] ) ) {
				$styles[] = 'aspect-ratio:' . $item['image_aspect_ratio'];
			}

			$radius = $this->ecbb_normalize_css_size( $item['ecbb_image_radius'] ?? '', 'px' );
			if ( $radius !== '' ) {
				$styles[] = 'border-radius:' . $radius;
			}

			$bw = $this->ecbb_normalize_css_size( $item['ecbb_image_border_width'] ?? '', 'px' );
			$bc = $this->ecbb_normalize_color_value( $item['ecbb_image_border_color'] ?? '' );
			$bs = isset( $item['ecbb_image_border_style'] ) ? (string) $item['ecbb_image_border_style'] : 'solid';
			$bs = in_array( $bs, [ 'solid', 'dashed', 'dotted' ], true ) ? $bs : 'solid';
			if ( $bw !== '' && $bw !== '0px' && $bc !== '' ) {
				$styles[] = 'border:' . $bw . ' ' . $bs . ' ' . $bc;
			}

			$w = $this->ecbb_normalize_css_size( $item['ecbb_image_width'] ?? '', '%' );
			if ( $w !== '' ) {
				$styles[] = 'width:' . $w;
			}

			$h = $this->ecbb_normalize_css_size( $item['ecbb_image_height'] ?? '', 'px' );
			if ( $h !== '' ) {
				$styles[] = 'height:' . $h;
			}

			$fit = isset( $item['ecbb_image_fit'] ) ? (string) $item['ecbb_image_fit'] : '';
			if ( $fit !== '' && in_array( $fit, [ 'cover', 'contain', 'fill', 'none', 'scale-down' ], true ) ) {
				$styles[] = 'object-fit:' . $fit;
			}
		}

		return empty( $styles ) ? '' : implode( ';', $styles ) . ';';
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $skin '' or 'style2' (list style 2 shell).
	 * @return void
	 */
	private function ecbb_render_part( $post, $item, $idx = 0, $skin = '' ) {
		$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';
		$idx  = absint( $idx );
		$skin = (string) $skin;
		$wrap = function_exists( 'ecbb_events_widget_part_wrap_classes' )
			? ecbb_events_widget_part_wrap_classes( $part, $idx, $skin )
			: ( 'ecbb-event-part ecbb-event-part--' . str_replace( '_', '-', $part ) . ' ecbb-p' . $idx );

		if ( function_exists( 'ecbb_event_part_extended_markup' ) ) {
			$ext = ecbb_event_part_extended_markup( $post, $item, $idx, $this->ecbb_build_inline_style_attr( $item ), $skin );
			if ( $ext !== false ) {
				echo $ext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in includes/events-widget/ecbb-events-widget-loop-markup.php
				return;
			}
		}

		if ( $part === 'image' ) {
			$thumb_id = (int) get_post_thumbnail_id( $post->ID );
			if ( ! $thumb_id ) {
				return;
			}

			$img_style = $this->ecbb_build_inline_style_attr( $item, true );
			$image_html = function_exists( 'ecbb_render_loop_featured_images' )
				? ecbb_render_loop_featured_images( $thumb_id, $item, $img_style )
				: '';
			if ( $image_html === '' ) {
				$size = function_exists( 'ecbb_sanitize_attachment_image_size' )
					? ecbb_sanitize_attachment_image_size( $item['image_size'] ?? '', 'large' )
					: ( isset( $item['image_size'] ) ? trim( (string) $item['image_size'] ) : 'large' );
				if ( $size === '' ) {
					$size = 'large';
				}
				$image_html = wp_get_attachment_image(
					$thumb_id,
					$size,
					false,
					[
						'class' => 'ecbb-event__image',
						'style' => $img_style,
					]
				);
			}
			if ( ! $image_html ) {
				return;
			}

			$link = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
			$dual = function_exists( 'ecbb_loop_image_uses_dual_layer' ) && ecbb_loop_image_uses_dual_layer( $item );
			$part_classes = $wrap;
			if ( $dual ) {
				$part_classes .= ' ecbb-is-dual-img';
			}

			echo '<div class="' . esc_attr( $part_classes ) . '">';
			if ( $link ) {
				echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">';
			}
			echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( $link ) {
				echo '</a>';
			}
			echo '</div>';
			return;
		}

		if ( $part === 'venue' ) {
			$venue = '';
			if ( function_exists( 'tribe_get_venue' ) ) {
				$venue = \tribe_get_venue( $post->ID );
			}
			if ( ! $venue ) {
				$venue_id = (int) get_post_meta( $post->ID, '_EventVenueID', true );
				if ( $venue_id ) {
					$venue = get_the_title( $venue_id );
				}
			}
			$venue = trim( (string) $venue );
			if ( $venue === '' ) {
				return;
			}

			$style = $this->ecbb_build_inline_style_attr( $item );
			$link_enabled = ! empty( $item['venue_link'] );
			$url = '';
			if ( $link_enabled && function_exists( 'tribe_get_venue_link' ) ) {
				$url = (string) \tribe_get_venue_link( $post->ID );
			}
			$icon    = ' ecbb-has-row-icon';
			$classes = $wrap . $icon;
			// Location pin is gated in CSS on `.ecbb-has-row-icon` (Style 2 list, Style 1, grid).
			echo '<div class="' . esc_attr( $classes ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			if ( $link_enabled && $url ) {
				echo '<span class="ecbb-event__link-wrapper">' . wp_kses_post( $url ) . '</span>';
			} else {
				echo esc_html( $venue );
			}
			echo '</div>';
			return;
		}

		if ( $part === 'organizer' ) {
			$organizer = '';

			if ( function_exists( 'tribe_get_organizer' ) ) {
				$organizer = \tribe_get_organizer( $post->ID );
			}

			if ( ! $organizer ) {
				$organizer_id = (int) get_post_meta( $post->ID, '_EventOrganizerID', true );
				if ( $organizer_id ) {
					$organizer = get_the_title( $organizer_id );
				}
			}

			if ( ! $organizer ) {
				return;
			}

			$style = $this->ecbb_build_inline_style_attr( $item );
			echo '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			$link_enabled = ! empty( $item['organizer_link'] );
			$url = '';
			if ( $link_enabled && function_exists( 'tribe_get_organizer_link' ) ) {
				$url = (string) \tribe_get_organizer_link( $post->ID );
			}
			if ( $link_enabled && $url ) {
				echo '<span class="ecbb-event__link-wrapper">' . wp_kses_post( $url ) . '</span>';
			} else {
				echo esc_html( $organizer );
			}
			echo '</div>';
			return;
		}

		if ( $part === 'categories' ) {
			$terms = get_the_terms( $post->ID, 'tribe_events_cat' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return;
			}

			$links      = [];
			$style      = $this->ecbb_build_inline_style_attr( $item );
			$link_style = $style ? ' style="' . esc_attr( $style ) . '"' : '';
			$sep        = isset( $item['terms_separator'] ) ? (string) $item['terms_separator'] : ', ';
			$sep        = $sep !== '' ? $sep : ', ';
			$link_terms = ! array_key_exists( 'terms_link', $item ) ? true : (bool) $item['terms_link'];
			foreach ( $terms as $t ) {
				if ( $link_terms ) {
					$url = get_term_link( $t );
					if ( is_wp_error( $url ) ) {
						continue;
					}
					$links[] = '<a class="ecbb-event__link" href="' . esc_url( $url ) . '"' . $link_style . '>' . esc_html( $t->name ) . '</a>';
				} else {
					$links[] = '<span class="ecbb-event__term"' . $link_style . '>' . esc_html( $t->name ) . '</span>';
				}
			}

			if ( empty( $links ) ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			echo wp_kses_post( implode( esc_html( $sep ), $links ) );
			echo '</div>';
			return;
		}

		if ( $part === 'tags' ) {
			// TEC event tags use the default WP taxonomy: post_tag.
			$terms = get_the_terms( $post->ID, 'post_tag' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return;
			}

			$links      = [];
			$style      = $this->ecbb_build_inline_style_attr( $item );
			$link_style = $style ? ' style="' . esc_attr( $style ) . '"' : '';
			$sep        = isset( $item['terms_separator'] ) ? (string) $item['terms_separator'] : ', ';
			$sep        = $sep !== '' ? $sep : ', ';
			$link_terms = ! array_key_exists( 'terms_link', $item ) ? true : (bool) $item['terms_link'];
			foreach ( $terms as $t ) {
				if ( $link_terms ) {
					$url = get_term_link( $t );
					if ( is_wp_error( $url ) ) {
						continue;
					}
					$links[] = '<a class="ecbb-event__link" href="' . esc_url( $url ) . '"' . $link_style . '>' . esc_html( $t->name ) . '</a>';
				} else {
					$links[] = '<span class="ecbb-event__term"' . $link_style . '>' . esc_html( $t->name ) . '</span>';
				}
			}

			if ( empty( $links ) ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			echo wp_kses_post( implode( esc_html( $sep ), $links ) );
			echo '</div>';
			return;
		}

		if ( $part === 'description' ) {
			$source = $item['desc_source'] ?? 'auto';
			$source = in_array( $source, [ 'auto', 'excerpt', 'content' ], true ) ? $source : 'auto';
			$len_mode = isset( $item['desc_length'] ) ? (string) $item['desc_length'] : 'short';
			$len_mode = in_array( $len_mode, [ 'short', 'full', 'custom' ], true ) ? $len_mode : 'short';
			$words = isset( $item['desc_words'] ) ? max( 5, (int) $item['desc_words'] ) : 55;

			$content = '';
			if ( $source === 'content' || ( $source === 'auto' && $post->post_excerpt === '' ) ) {
				$raw     = $post->post_content;
				$content = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
				$content = do_shortcode( $content );
			} elseif ( $post->post_excerpt ) {
				$content = wpautop( $post->post_excerpt );
			}
			if ( $len_mode === 'short' ) {
				$content = wpautop( wp_trim_words( wp_strip_all_tags( $content !== '' ? $content : $post->post_content ), 55 ) );
			} elseif ( $len_mode === 'custom' ) {
				$content = wpautop( wp_trim_words( wp_strip_all_tags( $content !== '' ? $content : $post->post_content ), $words ) );
			}

			$style = $this->ecbb_build_inline_style_attr( $item );
			echo '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			echo wp_kses_post( $content );
			echo '</div>';
			return;
		}

		if ( $part === 'date' ) {
			$day  = '';
			$time = '';
			if ( function_exists( 'ecbb_event_part_build_day_time_range_parts' ) ) {
				$parts_out = ecbb_event_part_build_day_time_range_parts( $post->ID, $item );
				$day       = isset( $parts_out['day'] ) ? (string) $parts_out['day'] : '';
				$time      = isset( $parts_out['time'] ) ? (string) $parts_out['time'] : '';
			} elseif ( function_exists( 'tribe_get_start_date' ) ) {
				$day = trim( wp_strip_all_tags( (string) \tribe_get_start_date( $post->ID, true ) ) );
			} else {
				$raw = get_post_meta( $post->ID, '_EventStartDate', true );
				$ts  = $raw ? strtotime( (string) $raw ) : false;
				$day = $ts
					? trim( wp_strip_all_tags( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) ) )
					: '';
			}

			$day  = trim( wp_strip_all_tags( (string) $day ) );
			$time = trim( wp_strip_all_tags( (string) $time ) );

			if ( $day === '' && $time === '' ) {
				return;
			}

			$tt    = isset( $item['date_text_transform'] ) ? strtolower( trim( (string) $item['date_text_transform'] ) ) : 'capitalize';
			if ( ! in_array( $tt, [ 'capitalize', 'none', 'uppercase', 'lowercase' ], true ) ) {
				$tt = 'capitalize';
			}
			$style = trim( (string) $this->ecbb_build_inline_style_attr( $item ) );
			$day_style = $tt !== '' ? 'text-transform:' . $tt . ';' : '';
			$row_icon  = ( $time !== '' ) ? ' ecbb-has-row-icon' : '';
			echo '<div class="' . esc_attr( $wrap . $row_icon ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
			echo '<span class="ecbb-event__date-day"' . ( $day_style ? ' style="' . esc_attr( $day_style ) . '"' : '' ) . '>' . esc_html( $day ) . '</span>';
			if ( $time !== '' ) {
				echo '<span class="ecbb-event__date-sep">,</span>';
				echo '<span class="ecbb-event__date-time">' . esc_html( $time ) . '</span>';
			}
			echo '</div>';
			return;
		}

		// Title default.
		$tag  = ! empty( $item['tag'] ) ? \Bricks\Helpers::sanitize_html_tag( (string) $item['tag'], 'h3' ) : 'h3';
		$link = ! empty( $item['link'] );
		$style = $this->ecbb_build_inline_style_attr( $item );

		echo '<' . esc_attr( $tag ) . ' class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';

		if ( $link ) {
			echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
		}

		echo esc_html( get_the_title( $post->ID ) );

		if ( $link ) {
			echo '</a>';
		}

		echo '</' . esc_attr( $tag ) . '>';
	}

	/**
	 * Build TEC query args based on element settings.
	 *
	 * @return array<string, mixed>
	 */
	private function ecbb_get_tec_query_args() {
		return ecbb_events_widget_query_tribe_args( is_array( $this->settings ) ? $this->settings : [] );
	}

	/**
	 * Active Event parts for the current layout + list style.
	 *
	 * Layout-specific repeaters (`parts_style1`, `parts_style2`, `parts_grid`)
	 * are resolved by {@see ecbb_events_widget_resolve_event_parts_for_context()}.
	 * The legacy `parts` key is still read when a dedicated repeater is empty so
	 * older elements keep working.
	 *
	 * @param string $template    list|grid.
	 * @param string $item_chrome style-1|style-2 (list only).
	 * @return array<int,array<string,mixed>>
	 */
	private function ecbb_resolve_active_parts( $template, $item_chrome ) {
		if ( function_exists( 'ecbb_events_widget_resolve_event_parts_for_context' ) ) {
			return ecbb_events_widget_resolve_event_parts_for_context( $this->settings, $template, $item_chrome );
		}
		return [];
	}

	public function render() {
		$scope_class = $this->ecbb_get_instance_scope_class();
		$this->set_attribute( '_root', 'class', 'ecbb-ev' );
		$this->set_attribute( '_root', 'class', $scope_class );

		$item_gap      = isset( $this->settings['item_gap'] ) ? (float) $this->settings['item_gap'] : 24;
		$item_gap_unit = isset( $this->settings['item_gap_unit'] ) ? (string) $this->settings['item_gap_unit'] : 'px';
		$item_gap_unit = in_array( $item_gap_unit, [ 'px', 'rem', 'em' ], true ) ? $item_gap_unit : 'px';
		$template = isset( $this->settings['layout_template'] ) ? (string) $this->settings['layout_template'] : 'list';
		if ( $template === 'carousel' ) {
			$template = 'list';
		}
		$template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';

		$item_chrome = function_exists( 'ecbb_sanitize_list_item_style' )
			? ecbb_sanitize_list_item_style( $this->settings['list_item_style'] ?? 'style-1' )
			: 'style-1';

		$use_style1_shell = ( $template === 'list' && $item_chrome === 'style-1' );
		$use_style2_shell = ( $template === 'list' && $item_chrome === 'style-2' );
		$use_grid_shell   = ( $template === 'grid' );

		$style1_date_format = 'default';
		if ( $use_style1_shell && function_exists( 'ecbb_list1_sanitize_date_format' ) ) {
			$style1_date_format = ecbb_list1_sanitize_date_format( $this->settings['date_format'] ?? 'default' );
		}

		$item_classes = $use_grid_shell
			? 'ecbb-ev__item ecbb-ev__item--grid'
			: 'ecbb-ev__item ecbb-ev__item--' . $item_chrome;

		$parts_base      = $this->ecbb_resolve_active_parts( $template, $item_chrome );
		$parts_effective = is_array( $parts_base ) ? $parts_base : [];

		if ( $use_grid_shell && function_exists( 'ecbb_events_widget_grid_normalize_parts' ) ) {
			$parts_effective = ecbb_events_widget_grid_normalize_parts( $parts_effective );
		} elseif ( $use_style1_shell && function_exists( 'ecbb_list1_normalize_parts' ) ) {
			$parts_effective = ecbb_list1_normalize_parts( $parts_effective );
		} elseif ( $use_style2_shell ) {
			$parts_effective = ecbb_list2_normalize_parts( $parts_effective );
		} elseif (
			$parts_effective === []
			|| ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' ) && ecbb_events_widget_parts_array_is_effectively_empty( $parts_effective ) )
		) {
			$parts_effective = [
				[ 'part' => 'title', 'tag' => 'h3', 'link' => true ],
				[ 'part' => 'description', 'desc_source' => 'content' ],
				[ 'part' => 'date', 'date_text_transform' => 'uppercase' ],
			];
		}

		$cols_desktop = isset( $this->settings['grid_cols_desktop'] ) ? max( 1, (int) $this->settings['grid_cols_desktop'] ) : 3;
		$cols_tablet  = isset( $this->settings['grid_cols_tablet'] ) ? max( 1, (int) $this->settings['grid_cols_tablet'] ) : 2;
		$cols_mobile  = isset( $this->settings['grid_cols_mobile'] ) ? max( 1, (int) $this->settings['grid_cols_mobile'] ) : 1;

		// CSS variables enable responsive grid without per-instance <style> tags.
		$vars = '--ecbb-gap:' . $item_gap . $item_gap_unit . ';'
			. '--ecbb-grid-cols:' . $cols_desktop . ';'
			. '--ecbb-grid-cols-tablet:' . $cols_tablet . ';'
			. '--ecbb-grid-cols-mobile:' . $cols_mobile . ';';
		$this->set_attribute( '_root', 'style', $vars );

		// Build per-part CSS (colors/sizes/hover must be CSS to be reliable).
		$style_css = [];
		$hover_css = [];
		if ( is_array( $parts_effective ) ) {
			$idx = 0;
			foreach ( $parts_effective as $p ) {
				if ( ! is_array( $p ) ) {
					continue;
				}
				$idx_class = '.' . ecbb_events_widget_part_idx_class( absint( $idx ) );
				$scope_sel = '.' . $scope_class . ' ' . $idx_class;

				$color = $this->ecbb_normalize_color_value( $p['ecbb_color'] ?? ( $p['color'] ?? '' ) );
				if ( $color !== '' ) {
					// Apply to the element and descendants to override theme text colors.
					$style_css[] = $scope_sel . ', ' . $scope_sel . ' * { color: ' . $color . ' !important; }';
				}

				$fs = $this->ecbb_normalize_css_size( $p['ecbb_font_size'] ?? '', 'px' );
				if ( $fs !== '' ) {
					$style_css[] = $scope_sel . ' { font-size: ' . $fs . '; }';
				}

				if ( isset( $p['ecbb_font_weight'] ) && $p['ecbb_font_weight'] !== '' && is_numeric( $p['ecbb_font_weight'] ) ) {
					$style_css[] = $scope_sel . ' { font-weight: ' . (int) $p['ecbb_font_weight'] . '; }';
				}

				$part_type      = isset( $p['part'] ) ? (string) $p['part'] : '';
				$hover_style_on = function_exists( 'ecbb_event_part_hover_style_active' ) && ecbb_event_part_hover_style_active( $p );

				if ( $hover_style_on ) {
					$hover = $this->ecbb_normalize_color_value( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ) );
					if ( $hover !== '' ) {
						$hover_css[] = $scope_sel . ':hover,'
							. $scope_sel . ':hover *,'
							. $scope_sel . ' a:hover,'
							. $scope_sel . ' a:hover *{color:' . $hover . ' !important;}';
					}

					$hover_td = isset( $p['ecbb_hover_text_decoration'] ) ? (string) $p['ecbb_hover_text_decoration'] : '';
					if ( $hover_td !== '' && in_array( $hover_td, [ 'none', 'underline', 'overline', 'line-through' ], true ) ) {
						$hover_css[] = $scope_sel . ':hover,'
							. $scope_sel . ' a:hover{text-decoration:' . $hover_td . ' !important;}';
					}

					$hover_anim = isset( $p['ecbb_hover_animation'] ) ? (string) $p['ecbb_hover_animation'] : '';
					if ( $hover_anim !== '' && function_exists( 'ecbb_event_part_hover_animation_css' ) ) {
						$anim_blocks = ecbb_event_part_hover_animation_css( $scope_sel, $hover_anim );
						if ( ! empty( $anim_blocks['base'] ) ) {
							$style_css[] = $anim_blocks['base'];
						}
						if ( ! empty( $anim_blocks['hover'] ) ) {
							$hover_css[] = $anim_blocks['hover'];
						}
					}
				}

				$bg = $this->ecbb_normalize_color_value( $p['ecbb_background'] ?? '' );
				if ( $bg === '' ) {
					$bg = $this->ecbb_normalize_color_value( $p['ecbb_hover_background'] ?? '' );
				}
				if ( $bg !== '' ) {
					$style_css[] = $scope_sel . '{background-color:' . $bg . ' !important;}';
				}

				$bg_in = $this->ecbb_normalize_color_value( $p['ecbb_background_inner'] ?? '' );
				if ( $bg_in === '' ) {
					$bg_in = $this->ecbb_normalize_color_value( $p['ecbb_hover_background_inner'] ?? '' );
				}
				if ( $bg_in !== '' ) {
					$style_css[] = $scope_sel . ' > .ecbb-event__link,'
						. $scope_sel . ' .ecbb-event__link,'
						. $scope_sel . ' .ecbb-event__link-wrapper,'
						. $scope_sel . ' .ecbb-event__link-wrapper a,'
						. $scope_sel . ' > a{background-color:' . $bg_in . ' !important;}';
				}

				if ( $part_type === 'image' && $hover_style_on && function_exists( 'ecbb_loop_image_uses_dual_layer' ) && function_exists( 'ecbb_object_position_from_image_align' ) ) {
					if ( ! ecbb_loop_image_uses_dual_layer( $p ) ) {
						$op_b = ecbb_object_position_from_image_align( $p['ecbb_image_object_align'] ?? '' );
						$op_h = ecbb_object_position_from_image_align( $p['ecbb_image_object_align_hover'] ?? '' );
						if ( $op_h !== '' && $op_h !== $op_b ) {
							$img_sel = $scope_sel . ' .ecbb-event__image';
							if ( $op_b !== '' ) {
								$style_css[] = $img_sel . '{object-position:' . $op_b . ';transition:object-position 0.35s ease;}';
							} else {
								$style_css[] = $img_sel . '{transition:object-position 0.35s ease;}';
							}
							$hover_css[] = $scope_sel . ':hover .ecbb-event__image{object-position:' . $op_h . ' !important;}';
						}
					}
				}

				$idx++;
			}
		}

		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		$no_events_css = $this->ecbb_build_no_events_dynamic_style_css( $scope_class );
		if ( ! empty( $style_css ) || ! empty( $hover_css ) || ! empty( $no_events_css ) ) {
			echo '<style>' . implode( "\n", array_merge( $style_css, $hover_css, $no_events_css ) ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! function_exists( 'tribe_get_events' ) ) {
			if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
				echo '<div class="ecbb-event-placeholder">' . esc_html__( 'The Events Calendar is required to render Events Widget.', 'ecbb' ) . '</div>';
			}
			echo '</div>';
			return;
		}

		$events = \tribe_get_events( $this->ecbb_get_tec_query_args() );

		if ( empty( $events ) ) {
			$this->ecbb_render_no_events_message();
			echo '</div>';
			return;
		}

		global $post;
		$original_post = $post ?? null;

		$list_class        = 'ecbb-ev__list ecbb-ev__list--' . $template;
		$style2_last_month = null;
		$style2_show_month = ecbb_list2_month_headings_enabled( $this->settings );

		echo '<div class="' . esc_attr( $list_class ) . '">';

		foreach ( $events as $event_post ) {
			if ( ! $event_post instanceof \WP_Post ) {
				continue;
			}

			$post = $event_post;
			setup_postdata( $post );

			$parts = $parts_effective;

			if ( $use_style2_shell ) {
				echo ecbb_list2_maybe_month_heading_html( $post->ID, $style2_last_month, $style2_show_month );
			}

			echo '<div class="' . esc_attr( $item_classes ) . '">';

			if ( $use_style1_shell && function_exists( 'ecbb_list1_item_inner_markup' ) ) {
				$gap_inner = 'display:flex;flex-direction:column;gap:12px;';
				$self      = $this;
				$emit      = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx );
				};
				echo ecbb_list1_item_inner_markup( $post, $parts, $gap_inner, $emit, $style1_date_format );
			} elseif ( $use_style2_shell ) {
				$gap_inner = ecbb_list2_body_stack_gap_style( 12, 'px' );
				$self      = $this;
				$emit      = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx, 'style2' );
				};
				echo ecbb_list2_item_inner_markup( $post, $parts, $gap_inner, $emit );
			} elseif ( $use_grid_shell && function_exists( 'ecbb_events_widget_grid_item_inner_markup' ) ) {
				$gap_inner = 'display:flex;flex-direction:column;gap:8px;';
				$self      = $this;
				$emit      = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx );
				};
				echo ecbb_events_widget_grid_item_inner_markup( $post, $parts, $gap_inner, $emit );
			} else {
				$part_idx = 0;
				foreach ( $parts as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$this->ecbb_render_part( $post, $item, $part_idx );
					$part_idx++;
				}
			}

			echo '</div>';
		}

		echo '</div>';

		wp_reset_postdata();
		$post = $original_post;

		echo '</div>';
	}
}

