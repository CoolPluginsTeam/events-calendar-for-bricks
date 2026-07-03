<?php

namespace ECBB;

if (! defined('ABSPATH')) {
	exit;
}

class ECBB_Widget extends \Bricks\Element
{

	public $category = 'general';
	public $name     = 'ecbb-events-loop';
	public $icon     = 'ti-loop';
	public $nestable = false;

	public function get_label()
	{
		return esc_html__('Events Widget', 'events-calendar-for-bricks');
	}

	public function get_keywords()
	{
		return ['event', 'events', 'loop', 'query', 'tec', 'tribe', 'widget', 'calendar'];
	}

	public function set_control_groups()
	{
		// Order: Layouts → Events Query → Elements → Dynamic Messages → List Style 1.
		$this->control_groups['layouts'] = [
			'title' => esc_html__('Layouts', 'events-calendar-for-bricks'),
			'tab'   => 'content',
		];

		$this->control_groups['event_query'] = [
			'title' => esc_html__('Events Query', 'events-calendar-for-bricks'),
			'tab'   => 'content',
		];

		$this->control_groups['elements'] = [
			'title' => esc_html__('Elements', 'events-calendar-for-bricks'),
			'tab'   => 'content',
		];

		$this->control_groups['dynamic_messages'] = [
			'title' => esc_html__('Dynamic Messages', 'events-calendar-for-bricks'),
			'tab'   => 'content',
		];

		$this->control_groups['layout_appearance'] = [
			'title' => esc_html__( 'Event cards', 'events-calendar-for-bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['image_overlays'] = [
			'title' => esc_html__( 'Featured image overlays', 'events-calendar-for-bricks' ),
			'tab'   => 'style',
		];
	}

	/**
	 * Empty-state copy (Dynamic Messages → No events found).
	 *
	 * @return string
	 */
	private function ecbb_get_no_events_message()
	{
		$text = isset($this->settings['no_events_text']) ? trim((string) $this->settings['no_events_text']) : '';
		if ($text === '') {
			return __('No events found', 'events-calendar-for-bricks');
		}
		return $text;
	}

	/**
	 * Markup when the query returns zero events (front end + builder).
	 *
	 * @return void
	 */
	private function ecbb_render_no_events_message()
	{
		$tag = isset( $this->settings['no_events_tag'] ) ? (string) $this->settings['no_events_tag'] : 'h2';
		$tag = in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ], true ) ? $tag : 'h2';

		echo '<div class="ecbb-ev__empty" role="status">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is allow-listed.
		echo '<' . tag_escape( $tag ) . ' class="ecbb-ev__empty-message">' . esc_html( $this->ecbb_get_no_events_message() ) . '</' . tag_escape( $tag ) . '>';
		echo '</div>';
	}

	/**
	 * Load render helpers + layout templates on demand (not at file include).
	 *
	 * @return void
	 */
	private function ecbb_ensure_widget_dependencies() {
		if ( ! class_exists( '\ECBB_Plugin', false ) ) {
			return;
		}
		\ECBB_Plugin::ecbb_load_render_dependencies();
		\ECBB_Plugin::ecbb_load_layouts();
	}

	/**
	 * Ensure render helpers are loaded before calling ECBB_Markup methods.
	 *
	 * @return bool
	 */
	private function ecbb_has_markup_dependencies() {
		$this->ecbb_ensure_widget_dependencies();
		return class_exists( 'ECBB_Markup', false );
	}

	/**
	 * Builder-only placeholder when core render helpers are unavailable.
	 *
	 * @return void
	 */
	private function ecbb_render_missing_dependency_notice() {
		if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
			echo '<div class="ecbb-event-placeholder">' . esc_html__( 'Events Widget dependencies could not be loaded.', 'events-calendar-for-bricks' ) . '</div>';
		}
	}

	/**
	 * Bricks calls this when the element is on the page (front end or builder iframe).
	 * Mirrors ECT loading CSS only when a shortcode/block actually renders.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( class_exists( '\ECBB_Plugin', false ) ) {
			\ECBB_Plugin::ecbb_enqueue_events_widget_styles();
		}
	}

	public function set_controls()
	{
		$this->ecbb_ensure_widget_dependencies();
		if (class_exists('ECBB_Controls', false)) {
			\ECBB_Controls::ecbb_register_controls($this);
		}
	}

	/**
	 * @param mixed $value Bricks color control value.
	 * @return string Normalized CSS color or empty string.
	 */
	private function ecbb_norm_color( $value ) {
		return \ECBB_Markup::ecbb_norm_color( $value );
	}

	private function ecbb_get_instance_scope_class()
	{
		$id = property_exists($this, 'id') && $this->id ? (string) $this->id : '';
		$id = preg_replace('/[^a-zA-Z0-9\-_]/', '', $id);
		if ($id === '') {
			$id = 'ecbb-' . substr(md5(wp_json_encode($this->settings)), 0, 8);
		}
		return 'ecbb-ev--' . $id;
	}

	private function ecbb_part_surface_class( $part, $skin ) {
		$skin = (string) $skin;
		return \ECBB_Markup::ecbb_layout_surface_class( $part, $skin !== '' ? $skin : 'grid' );
	}

	private function ecbb_part_wrap_attrs( array $item, $idx, $style = '' ) {
		return \ECBB_Markup::ecbb_part_wrap_attrs( $item, $idx, $style );
	}

	/**
	 * Print an Event part opening tag.
	 *
	 * @param string              $tag   Allow-listed tag name.
	 * @param string              $class Wrapper class names.
	 * @param array<string,mixed> $item  Repeater row.
	 * @param int                 $idx   Row index.
	 * @param string              $style Inline style declaration string.
	 * @return void
	 */
	private function ecbb_print_part_open_tag( $tag, $class, array $item, $idx, $style = '' ) {
		$tag = in_array( $tag, [ 'div', 'h3', 'p' ], true ) ? $tag : 'div';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is allow-listed; ecbb_part_wrap_attrs() returns escaped attribute fragments.
		echo '<' . tag_escape( $tag ) . ' class="' . esc_attr( $class ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';
	}

	/**
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $skin
	 * @return array{part:string,idx:int,skin:string,wrap:string,item:array<string,mixed>}
	 */
	private function ecbb_prepare_part( $item, $idx, $skin ) {
		if ( class_exists( 'ECBB_Styles', false ) && is_array( $item ) ) {
			$item = \ECBB_Styles::ecbb_clean_part( $item );
		}

		$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';
		$idx  = absint( $idx );
		$skin = (string) $skin;
		$wrap = \ECBB_Markup::ecbb_part_classes( $part, $idx, $skin, is_array( $item ) ? $item : [] );

		return [
			'part' => $part,
			'idx'  => $idx,
			'skin' => $skin,
			'wrap' => $wrap,
			'item' => is_array( $item ) ? $item : [],
		];
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $skin
	 * @return void
	 */
	private function ecbb_render_part( $post, $item, $idx = 0, $skin = '' ) {
		$ctx = $this->ecbb_prepare_part( $item, $idx, $skin );

		$ext = \ECBB_Markup::ecbb_render_part_ext(
			$post,
			$ctx['item'],
			$ctx['idx'],
			'',
			$ctx['skin']
		);
		if ( $ext !== false ) {
			echo $ext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in includes/markup/markup.php
			return;
		}

		switch ( $ctx['part'] ) {
			case 'image':
				$this->ecbb_render_part_image( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'] );
				break;
			case 'categories':
				$this->ecbb_render_part_taxonomy( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'], 'tribe_events_cat', 'categories' );
				break;
			case 'tags':
				$this->ecbb_render_part_taxonomy( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'], 'post_tag', 'tags' );
				break;
			case 'description':
				$this->ecbb_render_part_description( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'] );
				break;
			case 'date':
				$this->ecbb_render_part_date( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'] );
				break;
			default:
				$this->ecbb_render_part_title( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'] );
				break;
		}
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @return void
	 */
	private function ecbb_render_part_image( $post, array $item, $idx, $wrap ) {
		$thumb_id = \ECBB_Markup::ecbb_event_thumbnail_id( $post->ID );
		if ( ! $thumb_id ) {
			return;
		}

		$image_html = \ECBB_Markup::ecbb_render_featured_img( $thumb_id, $item );
		if ( $image_html === '' ) {
			$size = \ECBB_Markup::ecbb_sanitize_image_size( $item['image_size'] ?? '', 'large' );
			$image_html = wp_get_attachment_image(
				$thumb_id,
				$size !== '' ? $size : 'large',
				false,
				[ 'class' => 'ecbb-event__image' ]
			);
		}

		if ( ! $image_html ) {
			return;
		}

		$link         = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
		$dual         = \ECBB_Markup::ecbb_image_dual_layer( $item );
		$part_classes = $dual ? $wrap . ' ecbb-is-dual-img' : $wrap;

		$this->ecbb_print_part_open_tag( 'div', $part_classes, $item, $idx );
		if ( $link ) {
			echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">';
		}
		echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $link ) {
			echo '</a>';
		}
		echo '</div>';
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @param string              $skin
	 * @param string              $taxonomy
	 * @param string              $part_key
	 * @return void
	 */
	private function ecbb_render_part_taxonomy( $post, array $item, $idx, $wrap, $skin, $taxonomy, $part_key ) {
		$terms = ( $taxonomy === 'tribe_events_cat' )
			? \ECBB_Markup::ecbb_event_category_terms( $post->ID )
			: get_the_terms( $post->ID, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		$inner = \ECBB_Markup::ecbb_terms_html( $terms, $item, '', $skin, $part_key );
		if ( $inner === '' ) {
			return;
		}

		$this->ecbb_print_part_open_tag( 'div', $wrap, $item, $idx );
		echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in \ECBB_Markup::ecbb_terms_html().
		echo '</div>';
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @return void
	 */
	private function ecbb_render_part_description( $post, array $item, $idx, $wrap, $skin = '' ) {
		$layout_skin = (string) $skin;
		if ( $layout_skin === '' ) {
			$layout_skin = 'grid';
		}

		if ( $layout_skin === 'grid' ) {
			$content = \ECBB_Markup::ecbb_grid_description_html( $post, $item );
			if ( $content === '' ) {
				return;
			}

			$surface = trim( $this->ecbb_part_surface_class( 'description', $skin ) );
			$classes = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );
			$this->ecbb_print_part_open_tag( 'div', $classes, $item, $idx );
			echo wp_kses_post( $content );
			echo '</div>';
			return;
		}

		$len_mode = isset( $item['desc_length'] ) ? (string) $item['desc_length'] : 'short';
		$len_mode = in_array( $len_mode, [ 'short', 'full', 'custom' ], true ) ? $len_mode : 'short';
		$words    = isset( $item['desc_words'] ) ? max( 5, (int) $item['desc_words'] ) : 55;

		$plain = \ECBB_Markup::ecbb_description_plain_text( $post, $item );
		if ( $plain === '' ) {
			return;
		}

		if ( $len_mode === 'short' ) {
			$content = wpautop( wp_trim_words( $plain, 55 ) );
		} elseif ( $len_mode === 'custom' ) {
			$content = wpautop( wp_trim_words( $plain, $words ) );
		} else {
			$content = wpautop( $plain );
		}

		$surface = trim( $this->ecbb_part_surface_class( 'description', $skin ) );
		$tag     = $surface !== '' ? 'div' : 'p';
		$classes = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );
		$this->ecbb_print_part_open_tag( $tag, $classes, $item, $idx );
		echo wp_kses_post( $content );
		echo '</' . tag_escape( $tag ) . '>';
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @return void
	 */
	private function ecbb_render_part_date( $post, array $item, $idx, $wrap ) {
		$parts_out = \ECBB_Markup::ecbb_build_day_time_parts( $post->ID, $item );
		$day       = isset( $parts_out['day'] ) ? (string) $parts_out['day'] : '';
		$time      = isset( $parts_out['time'] ) ? (string) $parts_out['time'] : '';

		$day  = trim( wp_strip_all_tags( (string) $day ) );
		$time = trim( wp_strip_all_tags( (string) $time ) );

		if ( $day === '' && $time === '' ) {
			return;
		}

		$row_icon = ( $time !== '' ) ? ' ecbb-has-row-icon' : '';
		$this->ecbb_print_part_open_tag( 'div', $wrap . $row_icon, $item, $idx );
		echo '<span class="ecbb-event__date-day">' . esc_html( $day ) . '</span>';
		if ( $time !== '' ) {
			echo '<span class="ecbb-event__date-sep">,</span>';
			echo '<span class="ecbb-event__date-time">' . esc_html( $time ) . '</span>';
		}
		echo '</div>';
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @return void
	 */
	private function ecbb_render_part_title( $post, array $item, $idx, $wrap, $skin = '' ) {
		$hover = \ECBB_Markup::ecbb_hover_style_active( $item );
		$link  = \ECBB_Markup::ecbb_title_link_active( $item ) && $hover;
		$this->ecbb_print_part_open_tag( 'h3', trim( $wrap . ' ' . $this->ecbb_part_surface_class( 'title', $skin ) ), $item, $idx );
		if ( $link ) {
			echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">';
			echo esc_html( get_the_title( $post->ID ) );
			echo '</a>';
		} else {
			echo '<span class="ecbb-event__title-text">' . esc_html( get_the_title( $post->ID ) ) . '</span>';
		}
		echo '</h3>';
	}

	/**
	 * @return array{
	 *     template:string,
	 *     item_chrome:string,
	 *     use_style1_shell:bool,
	 *     use_style2_shell:bool,
	 *     use_grid_shell:bool,
	 *     item_classes:string
	 * }
	 */
	private function ecbb_get_layout_context() {
		$settings    = is_array( $this->settings ) ? $this->settings : [];
		$layout      = \ECBB_Markup::ecbb_sanitize_layout_template( $settings );
		$template    = $layout['template'];
		$item_chrome = $layout['item_chrome'];

		$use_style1_shell = ( $template === 'list' && $item_chrome === 'style-1' );
		$use_style2_shell = ( $template === 'list' && $item_chrome === 'style-2' );
		$use_grid_shell   = ( $template === 'grid' );

		if ( $use_style1_shell ) {
			$item_classes = 'ecbb-ev__item ecbb-ev__item--style-1 repeater-item';
		} elseif ( $use_style2_shell ) {
			$item_classes = 'ecbb-ev__item ecbb-ev__item--style-2 repeater-item';
		} elseif ( $use_grid_shell ) {
			$item_classes = 'ecbb-ev__item ecbb-ev__item--grid repeater-item';
		} else {
			$item_classes = 'ecbb-ev__item ecbb-ev__item--' . $item_chrome . ' repeater-item';
		}

		return [
			'template'         => $template,
			'item_chrome'      => $item_chrome,
			'use_style1_shell' => $use_style1_shell,
			'use_style2_shell' => $use_style2_shell,
			'use_grid_shell'   => $use_grid_shell,
			'item_classes'     => $item_classes,
		];
	}

	/**
	 * @param string              $template
	 * @param string              $item_chrome
	 * @param array<string,mixed> $layout
	 * @return array<int,array<string,mixed>>
	 */
	private function ecbb_get_parts_effective( $template, $item_chrome, array $layout ) {
		$parts_effective = \ECBB_Markup::ecbb_resolve_parts( $this->settings, $template, $item_chrome );

		if ( $layout['use_grid_shell'] && class_exists( 'ECBB_Grid', false ) ) {
			return \ECBB_Grid::ecbb_norm_parts( $parts_effective );
		}
		if ( $layout['use_style1_shell'] && class_exists( 'ECBB_List_1', false ) ) {
			return \ECBB_List_1::ecbb_norm_parts( $parts_effective );
		}
		if ( $layout['use_style2_shell'] && class_exists( 'ECBB_List_2', false ) ) {
			return \ECBB_List_2::ecbb_norm_parts( $parts_effective );
		}
		if (
			$parts_effective === []
			|| \ECBB_Markup::ecbb_parts_is_empty( $parts_effective )
		) {
			return [
				[ 'part' => 'title', 'link' => true ],
				[ 'part' => 'description', 'desc_source' => 'content' ],
				[ 'part' => 'date', 'date_text_transform' => 'uppercase' ],
			];
		}

		return is_array( $parts_effective ) ? $parts_effective : [];
	}

	/**
	 * Build scoped CSS for one widget instance (gap, grid cols, repeater part styles).
	 *
	 * @param string              $scope_class
	 * @param array<string,mixed> $settings
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed> $layout
	 * @return string Sanitized CSS or empty string.
	 */
	private function ecbb_build_widget_css( $scope_class, array $settings, array $parts_effective, array $layout ) {
		$style_css = [];
		$hover_css = [];
		if ( class_exists( 'ECBB_Styles', false ) ) {
			list( $style_css, $hover_css ) = \ECBB_Styles::ecbb_parts_css(
				$parts_effective,
				$scope_class,
				function ( $value ) {
					return $this->ecbb_norm_color( $value );
				},
				$layout['use_grid_shell'] ? 'grid' : $layout['item_chrome']
			);
		}

		$gap_css = class_exists( 'ECBB_Styles', false )
			? \ECBB_Styles::ecbb_gap_responsive_css( $settings, '.' . $scope_class )
			: '';

		$grid_css = ( $layout['template'] === 'grid' && class_exists( 'ECBB_Styles', false ) )
			? \ECBB_Styles::ecbb_grid_cols_css( $settings, '.' . $scope_class )
			: '';

		$shell_css = class_exists( 'ECBB_Styles', false )
			? \ECBB_Styles::ecbb_layout_shell_css(
				$settings,
				$scope_class,
				function ( $value ) {
					return $this->ecbb_norm_color( $value );
				}
			)
			: '';

		$all_css = array_filter(
			array_merge(
				$gap_css !== '' ? [ $gap_css ] : [],
				$grid_css !== '' ? [ $grid_css ] : [],
				$shell_css !== '' ? [ $shell_css ] : [],
				$style_css,
				$hover_css
			)
		);

		if ( empty( $all_css ) ) {
			return '';
		}

		return wp_strip_all_tags( str_replace( '</style', '<\/style', implode( "\n", $all_css ) ) );
	}

	/**
	 * Output scoped CSS inline with the element (Bricks renders after wp_head, so
	 * wp_add_inline_style() on enqueued handles is often too late).
	 *
	 * @param string              $scope_class
	 * @param array<string,mixed> $settings
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed> $layout
	 * @return void
	 */
	private function ecbb_print_widget_css( $scope_class, array $settings, array $parts_effective, array $layout ) {
		$css = $this->ecbb_build_widget_css( $scope_class, $settings, $parts_effective, $layout );
		if ( $css === '' ) {
			return;
		}

		$style_id = sanitize_html_class( $scope_class . '-css' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is stripped; id is sanitized.
		echo '<style id="' . esc_attr( $style_id ) . '">' . $css . '</style>';
	}

	/**
	 * @param \WP_Post[]                       $events
	 * @param array<int,array<string,mixed>>   $parts_effective
	 * @param array<string,mixed>              $layout
	 * @param array<string,mixed>              $settings Normalized element settings from {@see render()}.
	 * @return void
	 */
	private function ecbb_render_event_items( array $events, array $parts_effective, array $layout, array $settings ) {
		global $post;
		$original_post = $post ?? null;

		$list_class = 'ecbb-ev__list ecbb-ev__list--' . $layout['template'];
		if ( $layout['use_style1_shell'] ) {
			$list_class .= ' event-list';
		} elseif ( $layout['use_style2_shell'] ) {
			$list_class .= ' ecbb-list';
		} elseif ( $layout['use_grid_shell'] ) {
			$list_class .= ' event-grid';
		}

		echo '<div class="' . esc_attr( $list_class ) . '">';

		foreach ( $events as $event_post ) {
			if ( ! $event_post instanceof \WP_Post ) {
				continue;
			}

			$post = $event_post;
			setup_postdata( $post );

			echo '<div class="' . esc_attr( $layout['item_classes'] ) . '">';
			$this->ecbb_render_event_item_inner( $post, $parts_effective, $layout, $settings );
			echo '</div>';
		}

		echo '</div>';

		wp_reset_postdata();
		$post = $original_post;

		\ECBB_Markup::ecbb_active_widget_settings( [] );
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed> $layout
	 * @return void
	 */
	private function ecbb_render_event_item_inner( $post, array $parts_effective, array $layout, array $settings = [] ) {
		if ( $settings === [] ) {
			$settings = is_array( $this->settings ) ? $this->settings : [];
		}

		$shells = [
			'use_style1_shell' => [ \ECBB_List_1::class, 'style1', 'style1' ],
			'use_style2_shell' => [ \ECBB_List_2::class, 'style2', 'style2' ],
			'use_grid_shell'   => [ \ECBB_Grid::class, '', 'grid' ],
		];
		foreach ( $shells as $flag => [ $class, $skin, $layout_skin ] ) {
			if ( ! $layout[ $flag ] || ! class_exists( $class, false ) ) {
				continue;
			}
			$widget = $this;
			$emit   = static function ( $ev, $item, $idx ) use ( $widget, $skin ) {
				$widget->ecbb_render_part( $ev, $item, $idx, $skin );
			};
			$emit_meta = static function ( $ev, $item, $idx, $price = false ) use ( $skin, $layout_skin ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in \ECBB_Markup::ecbb_render_meta_li().
				echo \ECBB_Markup::ecbb_render_meta_li( $ev, $item, $idx, $skin, $layout_skin, (bool) $price );
			};
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $class::ecbb_item_inner( $post, $parts_effective, $emit, $settings, $emit_meta );
			return;
		}

		$part_idx = 0;
		foreach ( $parts_effective as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$this->ecbb_render_part( $post, $item, $part_idx );
			++$part_idx;
		}
	}

	public function render() {
		if ( ! $this->ecbb_has_markup_dependencies() ) {
			$this->set_attribute( '_root', 'class', 'ecbb-ev' );
			echo '<div ' . $this->render_attributes( '_root' ) . '>';
			$this->ecbb_render_missing_dependency_notice();
			echo '</div>';
			return;
		}

		$this->settings = \ECBB_Markup::ecbb_norm_layout_shell_settings( is_array( $this->settings ) ? $this->settings : [] );
		\ECBB_Markup::ecbb_active_widget_settings( $this->settings );

		$scope_class = $this->ecbb_get_instance_scope_class();
		$this->set_attribute( '_root', 'class', 'ecbb-ev' );
		$this->set_attribute( '_root', 'class', $scope_class );

		$settings = is_array( $this->settings ) ? $this->settings : [];
		$layout   = $this->ecbb_get_layout_context();
		$parts    = $this->ecbb_get_parts_effective( $layout['template'], $layout['item_chrome'], $layout );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks render_attributes() returns the element's escaped attribute string.
		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		$this->ecbb_print_widget_css( $scope_class, $settings, $parts, $layout );

		if ( ! function_exists( 'tribe_get_events' ) ) {
			if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
				echo '<div class="ecbb-event-placeholder">' . esc_html__( 'The Events Calendar is required to render Events Widget.', 'events-calendar-for-bricks' ) . '</div>';
			}
			echo '</div>';
			$this->ecbb_reset_active_widget_settings();
			return;
		}

		$events = class_exists( 'ECBB_Query', false )
			? \ECBB_Query::ecbb_fetch_events( $settings )
			: [];

		if ( empty( $events ) ) {
			$this->ecbb_render_no_events_message();
			echo '</div>';
			$this->ecbb_reset_active_widget_settings();
			return;
		}

		$this->ecbb_render_event_items( $events, $parts, $layout, $settings );

		echo '</div>';

		$this->ecbb_reset_active_widget_settings();
	}

	/**
	 * Clear per-render active settings snapshot.
	 *
	 * @return void
	 */
	private function ecbb_reset_active_widget_settings() {
		\ECBB_Markup::ecbb_active_widget_settings( [] );
	}
}

if (! class_exists(__NAMESPACE__ . '\\Element_ECBB_Events_Widget', false)) {
	class_alias(__NAMESPACE__ . '\\ECBB_Widget', __NAMESPACE__ . '\\Element_ECBB_Events_Widget');
}
