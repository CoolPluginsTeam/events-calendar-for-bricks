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
			return esc_html__('No events found', 'events-calendar-for-bricks');
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
		$tag = isset($this->settings['no_events_tag']) ? (string) $this->settings['no_events_tag'] : 'h2';

		echo '<div class="ecbb-ev__empty" role="status">';
		echo '<' . esc_attr($tag) . ' class="ecbb-ev__empty-message">' . esc_html($this->ecbb_get_no_events_message()) . '</' . esc_attr($tag) . '>';
		echo '</div>';
	}

	/**
	 * Load render helpers + layout templates on demand (not at file include).
	 *
	 * @return void
	 */
	private function ecbb_ensure_widget_dependencies() {
		if ( ! class_exists( '\ECBB_WidgetClass', false ) ) {
			return;
		}
		\ECBB_WidgetClass::ecbb_load_render_dependencies();
		\ECBB_WidgetClass::ecbb_load_layouts();
	}

	/**
	 * Bricks calls this when the element is on the page (front end or builder iframe).
	 * Mirrors ECT loading CSS only when a shortcode/block actually renders.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( class_exists( '\ECBB_WidgetClass', false ) ) {
			\ECBB_WidgetClass::ecbb_enqueue_events_widget_styles();
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
	private function ecbb_normalize_color_value($value)
	{
		return class_exists('ECBB_Markup', false) ? \ECBB_Markup::ecbb_norm_color($value) : '';
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

	private function ecbb_inline_style_attr(array $item, $allow_radius = false)
	{
		if (class_exists('ECBB_Styles', false)) {
			return \ECBB_Styles::ecbb_inline_style_attr($item, $allow_radius);
		}
		return '';
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $style
	 * @return string
	 */
	private function ecbb_part_surface_class( $part, $skin ) {
		if ( ! class_exists( 'ECBB_Markup', false ) ) {
			return '';
		}
		$layout_skin = (string) $skin;
		if ( $layout_skin === '' ) {
			$layout_skin = 'grid';
		}
		return \ECBB_Markup::ecbb_layout_surface_class( $part, $layout_skin );
	}

	private function ecbb_part_wrap_attrs( array $item, $idx, $style = '' ) {
		return class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_part_wrap_attrs( $item, $idx, $style )
			: ( $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '' );
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
		$wrap = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_part_classes( $part, $idx, $skin, is_array( $item ) ? $item : [] )
			: ( 'ecbb-event-part ecbb-event-part--' . str_replace( '_', '-', $part ) . ' ecbb-p' . $idx );

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
	 * @param string   $skin '' or 'style2' (list style 2 shell).
	 * @return void
	 */
	/**
	 * Meta row inside layout shells (list / grid reference markup).
	 *
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $skin
	 * @param string              $layout style1|style2|grid
	 * @param bool                $price  List 1 price row modifier.
	 * @return void
	 */
	private function ecbb_render_layout_meta_li( $post, array $item, $idx, $skin, $layout, $price = false ) {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		ob_start();
		$this->ecbb_render_part( $post, $item, $idx, $skin );
		$inner = trim( (string) ob_get_clean() );
		if ( $inner === '' ) {
			return;
		}

		$row_clean = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
		$part      = isset( $row_clean['part'] ) ? (string) $row_clean['part'] : '';
		$icon      = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_meta_icon_for_part( $part )
			: '';

		if ( $layout === 'style2' ) {
			echo '<li class="ecbb-event-card__meta-item">' . $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$li_class = $price ? ' class="price"' : '';
			echo '<li' . $li_class . '>' . $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</li>';
	}

	private function ecbb_render_part( $post, $item, $idx = 0, $skin = '' ) {
		$ctx = $this->ecbb_prepare_part( $item, $idx, $skin );

		if ( class_exists( 'ECBB_Markup', false ) ) {
			$ext = \ECBB_Markup::ecbb_render_part_ext(
				$post,
				$ctx['item'],
				$ctx['idx'],
				$this->ecbb_inline_style_attr( $ctx['item'] ),
				$ctx['skin']
			);
			if ( $ext !== false ) {
				echo $ext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in includes/markup.php
				return;
			}
		}

		switch ( $ctx['part'] ) {
			case 'image':
				$this->ecbb_render_part_image( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'] );
				break;
			case 'categories':
				$this->ecbb_render_part_categories( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'] );
				break;
			case 'tags':
				$this->ecbb_render_part_tags( $post, $ctx['item'], $ctx['idx'], $ctx['wrap'], $ctx['skin'] );
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
		$thumb_id = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_event_thumbnail_id( $post->ID )
			: (int) get_post_thumbnail_id( $post->ID );
		if ( ! $thumb_id ) {
			return;
		}

		$image_html = '';
		if ( class_exists( 'ECBB_Markup', false ) ) {
			$image_html = \ECBB_Markup::ecbb_render_featured_img( $thumb_id, $item );
			if ( $image_html === '' ) {
				$size       = \ECBB_Markup::ecbb_sanitize_image_size( $item['image_size'] ?? '', 'large' );
				$image_html = wp_get_attachment_image(
					$thumb_id,
					$size !== '' ? $size : 'large',
					false,
					[ 'class' => 'ecbb-event__image' ]
				);
			}
		} else {
			$size = isset( $item['image_size'] ) ? trim( (string) $item['image_size'] ) : 'large';
			if ( $size === '' ) {
				$size = 'large';
			}
			$image_html = wp_get_attachment_image(
				$thumb_id,
				$size,
				false,
				[ 'class' => 'ecbb-event__image' ]
			);
		}

		if ( ! $image_html ) {
			return;
		}

		$link         = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
		$dual         = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_image_dual_layer( $item );
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
	 * @return void
	 */
	private function ecbb_render_part_categories( $post, array $item, $idx, $wrap, $skin ) {
		$this->ecbb_render_part_taxonomy( $post, $item, $idx, $wrap, $skin, 'tribe_events_cat', 'categories' );
	}

	/**
	 * @param \WP_Post            $post
	 * @param array<string,mixed> $item
	 * @param int                 $idx
	 * @param string              $wrap
	 * @param string              $skin
	 * @return void
	 */
	private function ecbb_render_part_tags( $post, array $item, $idx, $wrap, $skin ) {
		$this->ecbb_render_part_taxonomy( $post, $item, $idx, $wrap, $skin, 'post_tag', 'tags' );
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
		$terms = ( $taxonomy === 'tribe_events_cat' && class_exists( 'ECBB_Markup', false ) )
			? \ECBB_Markup::ecbb_event_category_terms( $post->ID )
			: get_the_terms( $post->ID, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		$style = $this->ecbb_inline_style_attr( $item );
		$inner = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_terms_html( $terms, $item, $style, $skin, $part_key )
			: '';
		if ( $inner === '' ) {
			return;
		}

		$this->ecbb_print_part_open_tag( 'div', $wrap, $item, $idx, $style );
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

		if ( $layout_skin === 'grid' && class_exists( 'ECBB_Markup', false ) ) {
			$content = \ECBB_Markup::ecbb_grid_description_html( $post, $item );
			if ( $content === '' ) {
				return;
			}

			$style   = $this->ecbb_inline_style_attr( $item );
			$surface = trim( $this->ecbb_part_surface_class( 'description', $skin ) );
			$classes = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );
			$this->ecbb_print_part_open_tag( 'div', $classes, $item, $idx, $style );
			echo wp_kses_post( $content );
			echo '</div>';
			return;
		}

		$source = $item['desc_source'] ?? 'auto';
		$source = in_array( $source, [ 'auto', 'excerpt', 'content' ], true ) ? $source : 'auto';
		$len_mode = isset( $item['desc_length'] ) ? (string) $item['desc_length'] : 'short';
		$len_mode = in_array( $len_mode, [ 'short', 'full', 'custom' ], true ) ? $len_mode : 'short';
		$words    = isset( $item['desc_words'] ) ? max( 5, (int) $item['desc_words'] ) : 55;

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

		$style = $this->ecbb_inline_style_attr( $item );
		$surface = trim( $this->ecbb_part_surface_class( 'description', $skin ) );
		$tag     = $surface !== '' ? 'div' : 'p';
		$classes = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );
		$this->ecbb_print_part_open_tag( $tag, $classes, $item, $idx, $style );
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
		$day  = '';
		$time = '';
		if ( class_exists( 'ECBB_Markup', false ) ) {
			$parts_out = \ECBB_Markup::ecbb_build_day_time_parts( $post->ID, $item );
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

		$style    = trim( (string) $this->ecbb_inline_style_attr( $item ) );
		$row_icon = ( $time !== '' ) ? ' ecbb-has-row-icon' : '';
		$this->ecbb_print_part_open_tag( 'div', $wrap . $row_icon, $item, $idx, $style );
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
		$hover = ! class_exists( 'ECBB_Markup', false ) || \ECBB_Markup::ecbb_hover_style_active( $item );
		$link  = class_exists( 'ECBB_Markup', false )
			? ( \ECBB_Markup::ecbb_title_link_active( $item ) && $hover )
			: ( ! empty( $item['link'] ) && $hover );
		$style = $this->ecbb_inline_style_attr( $item );

		$this->ecbb_print_part_open_tag( 'h3', trim( $wrap . ' ' . $this->ecbb_part_surface_class( 'title', $skin ) ), $item, $idx, $style );
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
	 * Active Event parts for the current layout + list style.
	 *
	 * Layout-specific repeaters (`parts_style1`, `parts_style2`, `parts_grid`)
	 * are resolved by {@see \ECBB_Markup::ecbb_resolve_parts()}.
	 * The legacy `parts` key is still read when a dedicated repeater is empty so
	 * older elements keep working.
	 *
	 * @param string $template    list|grid.
	 * @param string $item_chrome style-1|style-2 (list only).
	 * @return array<int,array<string,mixed>>
	 */
	private function ecbb_resolve_active_parts($template, $item_chrome)
	{
		if (class_exists('ECBB_Markup', false)) {
			return \ECBB_Markup::ecbb_resolve_parts($this->settings, $template, $item_chrome);
		}
		return [];
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
		$settings = is_array( $this->settings ) ? $this->settings : [];
		$layout   = \ECBB_Markup::ecbb_sanitize_layout_template( $settings );
		$template = $layout['template'];
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
			'template'           => $template,
			'item_chrome'        => $item_chrome,
			'use_style1_shell'   => $use_style1_shell,
			'use_style2_shell'   => $use_style2_shell,
			'use_grid_shell'     => $use_grid_shell,
			'item_classes'       => $item_classes,
		];
	}

	/**
	 * @param string              $template
	 * @param string              $item_chrome
	 * @param array<string,mixed> $layout
	 * @return array<int,array<string,mixed>>
	 */
	private function ecbb_get_parts_effective( $template, $item_chrome, array $layout ) {
		$parts_effective = $this->ecbb_resolve_active_parts( $template, $item_chrome );

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
			|| ( class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_parts_is_empty( $parts_effective ) )
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
					return $this->ecbb_normalize_color_value( $value );
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

		$all_css = array_filter(
			array_merge(
				$gap_css !== '' ? [ $gap_css ] : [],
				$grid_css !== '' ? [ $grid_css ] : [],
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
	 * @param \WP_Post[]          $events
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed> $layout
	 * @return void
	 */
	private function ecbb_render_event_items( array $events, array $parts_effective, array $layout ) {
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

		$settings = is_array( $this->settings ) ? $this->settings : [];
		if ( class_exists( 'ECBB_Markup', false ) ) {
			$settings = \ECBB_Markup::ecbb_norm_layout_shell_settings( $settings );
			\ECBB_Markup::ecbb_active_widget_settings( $settings );
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

		if ( class_exists( 'ECBB_Markup', false ) ) {
			\ECBB_Markup::ecbb_active_widget_settings( [] );
		}
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
			if ( class_exists( 'ECBB_Markup', false ) ) {
				$settings = \ECBB_Markup::ecbb_norm_layout_shell_settings( $settings );
			}
		}

		if ( $layout['use_style1_shell'] && class_exists( 'ECBB_List_1', false ) ) {
			$widget = $this;
			$emit   = static function ( $ev, $item, $idx ) use ( $widget ) {
				$widget->ecbb_render_part( $ev, $item, $idx, 'style1' );
			};
			$emit_meta = static function ( $ev, $item, $idx, $price = false ) use ( $widget ) {
				$widget->ecbb_render_layout_meta_li( $ev, $item, $idx, 'style1', 'style1', (bool) $price );
			};
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_List_1::ecbb_item_inner( $post, $parts_effective, $emit, $settings, $emit_meta );
			return;
		}

		if ( $layout['use_style2_shell'] && class_exists( 'ECBB_List_2', false ) ) {
			$widget = $this;
			$emit   = static function ( $ev, $item, $idx ) use ( $widget ) {
				$widget->ecbb_render_part( $ev, $item, $idx, 'style2' );
			};
			$emit_meta = static function ( $ev, $item, $idx, $price = false ) use ( $widget ) {
				$widget->ecbb_render_layout_meta_li( $ev, $item, $idx, 'style2', 'style2', (bool) $price );
			};
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_List_2::ecbb_item_inner( $post, $parts_effective, $emit, $settings, $emit_meta );
			return;
		}

		if ( $layout['use_grid_shell'] && class_exists( 'ECBB_Grid', false ) ) {
			$widget = $this;
			$emit   = static function ( $ev, $item, $idx ) use ( $widget ) {
				$widget->ecbb_render_part( $ev, $item, $idx );
			};
			$emit_meta = static function ( $ev, $item, $idx, $price = false ) use ( $widget ) {
				$widget->ecbb_render_layout_meta_li( $ev, $item, $idx, '', 'grid', (bool) $price );
			};
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_Grid::ecbb_item_inner( $post, $parts_effective, $emit, $settings, $emit_meta );
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
		$this->ecbb_ensure_widget_dependencies();

		if ( class_exists( 'ECBB_Markup', false ) ) {
			$this->settings = \ECBB_Markup::ecbb_norm_layout_shell_settings( is_array( $this->settings ) ? $this->settings : [] );
			\ECBB_Markup::ecbb_active_widget_settings( $this->settings );
		}

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

		$this->ecbb_render_event_items( $events, $parts, $layout );

		echo '</div>';

		$this->ecbb_reset_active_widget_settings();
	}

	/**
	 * Clear per-render active settings snapshot.
	 *
	 * @return void
	 */
	private function ecbb_reset_active_widget_settings() {
		if ( class_exists( 'ECBB_Markup', false ) ) {
			\ECBB_Markup::ecbb_active_widget_settings( [] );
		}
	}
}

if (! class_exists(__NAMESPACE__ . '\\Element_ECBB_Events_Widget', false)) {
	class_alias(__NAMESPACE__ . '\\ECBB_Widget', __NAMESPACE__ . '\\Element_ECBB_Events_Widget');
}
