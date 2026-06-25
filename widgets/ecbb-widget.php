<?php
namespace ECBB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECBB_Widget extends \Bricks\Element {

	public $category = 'general';
	public $name     = 'ecbb-events-loop';
	public $icon     = 'ti-loop';
	public $nestable = false;

	public function get_label() {
		return esc_html__( 'Events Widget', 'ecbb' );
	}

	public function get_keywords() {
		return [ 'event', 'events', 'loop', 'query', 'tec', 'tribe', 'widget', 'calendar' ];
	}

	public function set_control_groups() {
		// Order: Layouts → Events Query → Elements → Dynamic Messages.
		$this->control_groups['layouts'] = [
			'title' => esc_html__( 'Layouts', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['event_query'] = [
			'title' => esc_html__( 'Events Query', 'ecbb' ),
			'tab'   => 'content',
		];

		$this->control_groups['elements'] = [
			'title' => esc_html__( 'Elements', 'ecbb' ),
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
		$allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' ];

		$tag = isset( $this->settings['no_events_tag'] ) ? strtolower( trim( (string) $this->settings['no_events_tag'] ) ) : '';
		if ( ! in_array( $tag, $allowed_tags, true ) ) {
			$tag = 'h3';
		}

		echo '<div class="ecbb-ev__empty" role="status">';
		echo '<' . esc_attr( $tag ) . ' class="ecbb-ev__empty-message">' . esc_html( $this->ecbb_get_no_events_message() ) . '</' . esc_attr( $tag ) . '>';
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
		if ( $anim !== '' && class_exists( 'ECBB_Markup', false ) ) {
			$blocks = \ECBB_Markup::ecbb_hover_anim_css( $sel, $anim );
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

	public function set_controls() {
		if ( class_exists( 'ECBB_Controls', false ) ) {
			\ECBB_Controls::ecbb_register_controls( $this );
		}
	}

	/**
	 * @param mixed $value Bricks color control value.
	 * @return string Normalized CSS color or empty string.
	 */
	private function ecbb_normalize_color_value( $value ) {
		return class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $value ) : '';
	}

	private function ecbb_get_instance_scope_class() {
		$id = property_exists( $this, 'id' ) && $this->id ? (string) $this->id : '';
		$id = preg_replace( '/[^a-zA-Z0-9\-_]/', '', $id );
		if ( $id === '' ) {
			$id = 'ecbb-' . substr( md5( wp_json_encode( $this->settings ) ), 0, 8 );
		}
		return 'ecbb-ev--' . $id;
	}

	private function ecbb_inline_style_attr( array $item, $allow_radius = false ) {
		if ( class_exists( 'ECBB_Styles', false ) ) {
			return \ECBB_Styles::ecbb_inline_style_attr( $item, $allow_radius );
		}
		return '';
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $skin '' or 'style2' (list style 2 shell).
	 * @return void
	 */
	private function ecbb_part_wrap_attrs( array $item, $idx, $style = '' ) {
		return class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_part_wrap_attrs( $item, $idx, $style )
			: ( $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '' );
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $skin '' or 'style2' (list style 2 shell).
	 * @return void
	 */
	private function ecbb_render_part( $post, $item, $idx = 0, $skin = '' ) {
		if ( class_exists( 'ECBB_Styles', false ) && is_array( $item ) ) {
			$item = \ECBB_Styles::ecbb_clean_part( $item );
		}
		$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';
		$idx  = absint( $idx );
		$skin = (string) $skin;
		$wrap = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_part_classes( $part, $idx, $skin, is_array( $item ) ? $item : [] )
			: ( 'ecbb-event-part ecbb-event-part--' . str_replace( '_', '-', $part ) . ' ecbb-p' . $idx );

		if ( class_exists( 'ECBB_Markup', false ) ) {
			$ext = \ECBB_Markup::ecbb_render_part_ext( $post, $item, $idx, $this->ecbb_inline_style_attr( $item ), $skin );
			if ( $ext !== false ) {
				echo $ext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in includes/markup.php
				return;
			}
		}

		if ( $part === 'image' ) {
			$thumb_id = (int) get_post_thumbnail_id( $post->ID );
			if ( ! $thumb_id ) {
				return;
			}

			$image_html = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_render_featured_img( $thumb_id, $item )
				: '';
			if ( $image_html === '' ) {
				$size = class_exists( 'ECBB_Markup', false )
					? \ECBB_Markup::ecbb_sanitize_image_size( $item['image_size'] ?? '', 'large' )
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
					]
				);
			}
			if ( ! $image_html ) {
				return;
			}

			$link = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
			$dual = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_image_dual_layer( $item );
			$part_classes = $wrap;
			if ( $dual ) {
				$part_classes .= ' ecbb-is-dual-img';
			}

			echo '<div class="' . esc_attr( $part_classes ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx ) . '>';
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

		if ( $part === 'categories' ) {
			$terms = get_the_terms( $post->ID, 'tribe_events_cat' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return;
			}

			$style = $this->ecbb_inline_style_attr( $item );
			$inner = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_terms_html( $terms, $item, $style, $skin, 'categories' )
				: '';
			if ( $inner === '' ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';
			echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in \ECBB_Markup::ecbb_terms_html().
			echo '</div>';
			return;
		}

		if ( $part === 'tags' ) {
			$terms = get_the_terms( $post->ID, 'post_tag' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return;
			}

			$style = $this->ecbb_inline_style_attr( $item );
			$inner = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_terms_html( $terms, $item, $style, $skin, 'tags' )
				: '';
			if ( $inner === '' ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';
			echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in \ECBB_Markup::ecbb_terms_html().
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

			$style = $this->ecbb_inline_style_attr( $item );
			echo '<div class="' . esc_attr( $wrap ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';
			echo wp_kses_post( $content );
			echo '</div>';
			return;
		}

		if ( $part === 'date' ) {
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

			$style = trim( (string) $this->ecbb_inline_style_attr( $item ) );
			$row_icon  = ( $time !== '' ) ? ' ecbb-has-row-icon' : '';
			echo '<div class="' . esc_attr( $wrap . $row_icon ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';
			echo '<span class="ecbb-event__date-day">' . esc_html( $day ) . '</span>';
			if ( $time !== '' ) {
				echo '<span class="ecbb-event__date-sep">,</span>';
				echo '<span class="ecbb-event__date-time">' . esc_html( $time ) . '</span>';
			}
			echo '</div>';
			return;
		}

		// Title: always h3; no link/hover when link or hover is off.
		$tag   = 'h3';
		$hover = ! class_exists( 'ECBB_Markup', false ) || \ECBB_Markup::ecbb_hover_style_active( $item );
		$link  = class_exists( 'ECBB_Markup', false )
			? ( \ECBB_Markup::ecbb_title_link_active( $item ) && $hover )
			: ( ! empty( $item['link'] ) && $hover );
		$style = $this->ecbb_inline_style_attr( $item );

		echo '<' . esc_attr( $tag ) . ' class="' . esc_attr( $wrap ) . '"' . $this->ecbb_part_wrap_attrs( $item, $idx, $style ) . '>';

		if ( $link ) {
			echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">';
			echo esc_html( get_the_title( $post->ID ) );
			echo '</a>';
		} else {
			echo '<span class="ecbb-event__title-text">' . esc_html( get_the_title( $post->ID ) ) . '</span>';
		}

		echo '</' . esc_attr( $tag ) . '>';
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
	private function ecbb_resolve_active_parts( $template, $item_chrome ) {
		if ( class_exists( 'ECBB_Markup', false ) ) {
			return \ECBB_Markup::ecbb_resolve_parts( $this->settings, $template, $item_chrome );
		}
		return [];
	}

	public function render() {
		$scope_class = $this->ecbb_get_instance_scope_class();
		$this->set_attribute( '_root', 'class', 'ecbb-ev' );
		$this->set_attribute( '_root', 'class', $scope_class );

		$settings = is_array( $this->settings ) ? $this->settings : [];
		$template = isset( $settings['layout_template'] ) ? (string) $settings['layout_template'] : 'list';
		if ( $template === 'carousel' ) {
			$template = 'list';
		}
		$template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';

		$item_chrome = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_sanitize_list_style( $this->settings['list_item_style'] ?? 'style-1' )
			: 'style-1';

		$use_style1_shell = ( $template === 'list' && $item_chrome === 'style-1' );
		$use_style2_shell = ( $template === 'list' && $item_chrome === 'style-2' );
		$use_grid_shell   = ( $template === 'grid' );

		$style1_date_format = 'default';
		if ( $use_style1_shell && class_exists( 'ECBB_List_1', false ) ) {
			$style1_date_format = \ECBB_List_1::ecbb_sanitize_date_fmt( $this->settings['date_format'] ?? 'default' );
		}

		$item_classes = $use_grid_shell
			? 'ecbb-ev__item ecbb-ev__item--grid repeater-item'
			: 'ecbb-ev__item ecbb-ev__item--' . $item_chrome . ' repeater-item';

		$parts_base      = $this->ecbb_resolve_active_parts( $template, $item_chrome );
		$parts_effective = is_array( $parts_base ) ? $parts_base : [];

		if ( $use_grid_shell && class_exists( 'ECBB_Grid', false ) ) {
			$parts_effective = \ECBB_Grid::ecbb_norm_parts( $parts_effective );
		} elseif ( $use_style1_shell && class_exists( 'ECBB_List_1', false ) ) {
			$parts_effective = \ECBB_List_1::ecbb_norm_parts( $parts_effective );
		} elseif ( $use_style2_shell && class_exists( 'ECBB_List_2', false ) ) {
			$parts_effective = \ECBB_List_2::ecbb_norm_parts( $parts_effective );
		} elseif (
			$parts_effective === []
			|| ( class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_parts_is_empty( $parts_effective ) )
		) {
			$parts_effective = [
				[ 'part' => 'title', 'link' => true ],
				[ 'part' => 'description', 'desc_source' => 'content' ],
				[ 'part' => 'date', 'date_text_transform' => 'uppercase' ],
			];
		}

		// Responsive gap + grid columns use scoped CSS (avoid inline --ecbb-gap; it blocks media queries).
		$style_css = [];
		$hover_css = [];
		if ( class_exists( 'ECBB_Styles', false ) ) {
			list( $style_css, $hover_css ) = \ECBB_Styles::ecbb_parts_css(
				$parts_effective,
				$scope_class,
				function ( $value ) {
					return $this->ecbb_normalize_color_value( $value );
				},
				$use_grid_shell ? 'grid' : $item_chrome
			);
		}

		$gap_css = class_exists( 'ECBB_Styles', false )
			? \ECBB_Styles::ecbb_gap_responsive_css( $settings, '.' . $scope_class )
			: '';

		$grid_css = ( $template === 'grid' && class_exists( 'ECBB_Styles', false ) )
			? \ECBB_Styles::ecbb_grid_cols_css( $settings, '.' . $scope_class )
			: '';

		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		$no_events_css = $this->ecbb_build_no_events_dynamic_style_css( $scope_class );
		$all_css       = array_filter(
			array_merge(
				$gap_css !== '' ? [ $gap_css ] : [],
				$grid_css !== '' ? [ $grid_css ] : [],
				$style_css,
				$hover_css,
				$no_events_css
			)
		);
		if ( ! empty( $all_css ) ) {
			$css = wp_strip_all_tags( str_replace( '</style', '<\/style', implode( "\n", $all_css ) ) );
			if ( wp_style_is( 'ecbb-events-widget-base', 'enqueued' ) || wp_style_is( 'ecbb-events-widget-base', 'done' ) ) {
				wp_add_inline_style( 'ecbb-events-widget-base', $css );
			} else {
				echo '<style>' . $css . '</style>';
			}
		}

		if ( ! function_exists( 'tribe_get_events' ) ) {
			if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
				echo '<div class="ecbb-event-placeholder">' . esc_html__( 'The Events Calendar is required to render Events Widget.', 'ecbb' ) . '</div>';
			}
			echo '</div>';
			return;
		}

		$events = class_exists( 'ECBB_Query', false )
			? \ECBB_Query::ecbb_fetch_events( $settings )
			: [];

		if ( empty( $events ) ) {
			$this->ecbb_render_no_events_message();
			echo '</div>';
			return;
		}

		global $post;
		$original_post = $post ?? null;

		$list_class        = 'ecbb-ev__list ecbb-ev__list--' . $template;
		$style2_last_month = null;
		$style2_show_month = class_exists( 'ECBB_List_2', false )
			? \ECBB_List_2::ecbb_month_headings_on( $this->settings )
			: false;

		if ( class_exists( 'ECBB_Markup', false ) ) {
			\ECBB_Markup::ecbb_print_scope_attrs( $settings );
		}

		echo '<div class="' . esc_attr( $list_class ) . '">';

		foreach ( $events as $event_post ) {
			if ( ! $event_post instanceof \WP_Post ) {
				continue;
			}

			$post = $event_post;
			setup_postdata( $post );

			if ( $use_style2_shell && class_exists( 'ECBB_List_2', false ) ) {
				echo \ECBB_List_2::ecbb_month_heading( $post->ID, $style2_last_month, $style2_show_month );
			}

			echo '<div class="' . esc_attr( $item_classes ) . '">';

			if ( $use_style1_shell && class_exists( 'ECBB_List_1', false ) ) {
				$self = $this;
				$emit = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx, 'style1' );
				};
				echo \ECBB_List_1::ecbb_item_inner( $post, $parts_effective, $emit, $style1_date_format );
			} elseif ( $use_style2_shell && class_exists( 'ECBB_List_2', false ) ) {
				$self = $this;
				$emit = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx, 'style2' );
				};
				echo \ECBB_List_2::ecbb_item_inner( $post, $parts_effective, $emit );
			} elseif ( $use_grid_shell && class_exists( 'ECBB_Grid', false ) ) {
				$self = $this;
				$emit = function ( $ev, $item, $idx ) use ( $self ) {
					$self->ecbb_render_part( $ev, $item, $idx );
				};
				echo \ECBB_Grid::ecbb_item_inner( $post, $parts_effective, $emit );
			} else {
				$part_idx = 0;
				foreach ( $parts_effective as $item ) {
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

		if ( class_exists( 'ECBB_Markup', false ) ) {
			\ECBB_Markup::ecbb_print_scope_attrs( [] );
		}

		echo '</div>';
	}
}

if ( ! class_exists( __NAMESPACE__ . '\\Element_ECBB_Events_Widget', false ) ) {
	class_alias( __NAMESPACE__ . '\\ECBB_Widget', __NAMESPACE__ . '\\Element_ECBB_Events_Widget' );
}

