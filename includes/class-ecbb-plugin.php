<?php
/**
 * Main plugin bootstrap (ECBB prefix: scripts, elements, AJAX).
 */
class ECBB_Plugin {

    public function __construct() {
        add_action('init', [$this, 'ecbb_register_elements'], 11);
        add_action('wp_enqueue_scripts', [$this, 'ecbb_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'ecbb_enqueue_builder_assets'], 25);
        add_action('wp_enqueue_scripts', [$this, 'ecbb_enqueue_builder_preview_styles'], 99);
        add_action('wp_ajax_ecbb_events_load_more', [$this, 'ecbb_ajax_events_load_more']);
        add_action('wp_ajax_nopriv_ecbb_events_load_more', [$this, 'ecbb_ajax_events_load_more']);
        add_filter('bricks/element/settings', [$this, 'ecbb_filter_events_loop_element_settings'], 10, 2);
        add_action('wp_ajax_bricks_save_post', [$this, 'ecbb_preflight_merge_events_loop_repeaters_on_bricks_save'], 0);
    }

    /**
     * Before Bricks reads $_POST content, merge inactive layout repeaters from the last saved data.
     * Hidden `required` repeaters are often sent empty/omitted when saving while another template is selected,
     * which cleared `parts_grid` / `parts_style*` in post meta after refresh.
     *
     * @return void
     */
    public function ecbb_preflight_merge_events_loop_repeaters_on_bricks_save() {
        if ( empty( $_POST['postId'] ) || ! class_exists( '\Bricks\Ajax' ) || ! class_exists( '\Bricks\Database' ) ) {
            return;
        }
        $post_id = (int) $_POST['postId'];
        if ( $post_id < 1 ) {
            return;
        }

        foreach ( [ 'content', 'header', 'footer' ] as $area ) {
            $post_key = $area;
            if ( empty( $_POST[ $post_key ] ) || ! is_string( $_POST[ $post_key ] ) ) {
                continue;
            }
            $merged = $this->ecbb_merge_events_loop_repeaters_into_posted_area( $_POST[ $post_key ], $post_id, $area );
            if ( is_string( $merged ) ) {
                $_POST[ $post_key ] = $merged;
            }
        }
    }

    /**
     * @param string $posted_json Raw POST JSON for a Bricks area.
     * @param int    $post_id     Post ID being saved.
     * @param string $area        content|header|footer
     * @return string|null        New JSON string, or null to keep original.
     */
    private function ecbb_merge_events_loop_repeaters_into_posted_area( $posted_json, $post_id, $area ) {
        $new_elements = \Bricks\Ajax::decode( $posted_json );
        if ( ! is_array( $new_elements ) || $new_elements === [] ) {
            return null;
        }

        $meta_key = \Bricks\Database::get_bricks_data_key( $area );
        $old_elements = get_post_meta( $post_id, $meta_key, true );
        if ( ! is_array( $old_elements ) || $old_elements === [] ) {
            return null;
        }

        $old_indexed = $this->ecbb_index_bricks_elements_by_id( $old_elements );
        if ( $old_indexed === [] ) {
            return null;
        }

        $merged = $this->ecbb_apply_inactive_part_repeater_preservation( $new_elements, $old_indexed );
        $json   = wp_json_encode( $merged );
        return is_string( $json ) ? $json : null;
    }

    /**
     * @param array<int,array<string,mixed>> $elements
     * @return array<string,array<string,mixed>>
     */
    private function ecbb_index_bricks_elements_by_id( array $elements ) {
        $out = [];
        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) || empty( $element['id'] ) ) {
                continue;
            }
            $out[ (string) $element['id'] ] = $element;
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>>    $new_elements
     * @param array<string,array<string,mixed>> $old_elements_indexed
     * @return array<int,array<string,mixed>>
     */
    private function ecbb_apply_inactive_part_repeater_preservation( array $new_elements, array $old_elements_indexed ) {
        if ( ! function_exists( 'ecbb_sanitize_list_item_style' ) || ! function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' ) ) {
            return $new_elements;
        }

        foreach ( $new_elements as $i => $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }
            if ( ( $element['name'] ?? '' ) !== 'ecbb-events-loop' || empty( $element['id'] ) ) {
                continue;
            }
            $id = (string) $element['id'];
            if ( ! isset( $old_elements_indexed[ $id ] ) || ! is_array( $old_elements_indexed[ $id ] ) ) {
                continue;
            }
            $old_settings = $old_elements_indexed[ $id ]['settings'] ?? [];
            if ( ! is_array( $old_settings ) ) {
                continue;
            }
            if ( ! isset( $new_elements[ $i ]['settings'] ) || ! is_array( $new_elements[ $i ]['settings'] ) ) {
                $new_elements[ $i ]['settings'] = [];
            }
            $new_settings = &$new_elements[ $i ]['settings'];

            $template = isset( $new_settings['layout_template'] ) ? (string) $new_settings['layout_template'] : 'list';
            if ( $template === 'carousel' ) {
                $template = 'list';
            }
            $template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';
            $style    = ecbb_sanitize_list_item_style( $new_settings['list_item_style'] ?? 'style-1' );

            foreach ( [ 'parts_style1', 'parts_style2', 'parts_grid' ] as $key ) {
                $is_active = false;
                if ( 'parts_grid' === $key ) {
                    $is_active = ( 'grid' === $template );
                } elseif ( 'parts_style1' === $key ) {
                    $is_active = ( 'list' === $template && 'style-1' === $style );
                } elseif ( 'parts_style2' === $key ) {
                    $is_active = ( 'list' === $template && 'style-2' === $style );
                }
                if ( $is_active ) {
                    continue;
                }

                $incoming = $new_settings[ $key ] ?? null;
                $incoming_empty = ! is_array( $incoming ) || ecbb_events_widget_parts_array_is_effectively_empty( $incoming );
                if ( ! $incoming_empty ) {
                    continue;
                }
                if ( ! isset( $old_settings[ $key ] ) || ! is_array( $old_settings[ $key ] ) ) {
                    continue;
                }
                if ( ecbb_events_widget_parts_array_is_effectively_empty( $old_settings[ $key ] ) ) {
                    continue;
                }
                $new_settings[ $key ] = $old_settings[ $key ];
            }
            unset( $new_settings );
        }

        return $new_elements;
    }

    /**
     * Coerce layout-specific repeaters when another layout's row stack was left on disk
     * (e.g. Grid defaults still stored on `parts_style1` after switching to List Style 1).
     * Empty repeaters are left unchanged so {@see ecbb_events_widget_resolve_event_parts_for_context()}
     * can still fall back to legacy `parts`.
     *
     * @param array<string,mixed> $settings Element settings.
     * @param \Bricks\Element       $element  Bricks element instance.
     * @return array<string,mixed>
     */
    public function ecbb_filter_events_loop_element_settings( $settings, $element ) {
        if ( ! is_array( $settings ) || ! is_object( $element ) ) {
            return $settings;
        }
        if ( ! isset( $element->name ) || $element->name !== 'ecbb-events-loop' ) {
            return $settings;
        }
        if ( ! function_exists( 'ecbb_sanitize_list_item_style' ) ) {
            return $settings;
        }

        $template = isset( $settings['layout_template'] ) ? (string) $settings['layout_template'] : 'list';
        if ( $template === 'carousel' ) {
            $template = 'list';
        }
        $template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';
        $item_chrome = ecbb_sanitize_list_item_style( $settings['list_item_style'] ?? 'style-1' );

        if ( $template === 'list' && $item_chrome === 'style-1' ) {
            $p = isset( $settings['parts_style1'] ) && is_array( $settings['parts_style1'] ) ? $settings['parts_style1'] : [];
            if ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' )
                && ! ecbb_events_widget_parts_array_is_effectively_empty( $p )
                && function_exists( 'ecbb_list1_normalize_parts' ) ) {
                $settings['parts_style1'] = ecbb_list1_normalize_parts( $p );
            }
        } elseif ( $template === 'list' && $item_chrome === 'style-2' ) {
            $p = isset( $settings['parts_style2'] ) && is_array( $settings['parts_style2'] ) ? $settings['parts_style2'] : [];
            if ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' )
                && ! ecbb_events_widget_parts_array_is_effectively_empty( $p ) ) {
                $settings['parts_style2'] = ecbb_list2_normalize_parts( $p );
            }
        } elseif ( $template === 'grid' ) {
            $p = isset( $settings['parts_grid'] ) && is_array( $settings['parts_grid'] ) ? $settings['parts_grid'] : [];
            if ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' )
                && ! ecbb_events_widget_parts_array_is_effectively_empty( $p )
                && function_exists( 'ecbb_events_widget_grid_normalize_parts' ) ) {
                $settings['parts_grid'] = ecbb_events_widget_grid_normalize_parts( $p );
            }
        }

        return $settings;
    }

    /**
     * Register custom elements
     */
    public function ecbb_register_elements() {
        if ( ! class_exists( '\Bricks\Elements' ) ) {
            return;
        }

        $elements = [
            [
                'file'  => ECBB_DIR . 'includes/elements/class-element-ecbb-events-widget.php',
                'name'  => 'ecbb-events-loop',
                'class' => 'ECBB\\Element_ECBB_Events_Widget',
            ],
        ];

        foreach ( $elements as $element ) {
            if ( file_exists( $element['file'] ) ) {
                \Bricks\Elements::register_element( $element['file'], $element['name'], $element['class'] );
            }
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function ecbb_enqueue_scripts() {
        $this->ecbb_enqueue_events_widget_styles();
    }

    /**
     * Bricks builder iframe preview: ensure loop front-end CSS is available (Style 2, etc.).
     */
    public function ecbb_enqueue_builder_preview_styles() {
        if ( ! function_exists( 'bricks_is_builder_iframe' ) || ! bricks_is_builder_iframe() ) {
            return;
        }
        if ( wp_style_is( 'ecbb-events-widget-base', 'enqueued' ) || wp_style_is( 'ecbb-events-widget-base', 'done' ) ) {
            return;
        }
        $this->ecbb_enqueue_events_widget_styles();
    }

    /**
     * Front-end CSS for the Events Widget (base + list styles under template/list + grid).
     */
    private function ecbb_enqueue_events_widget_styles() {
        $base_path = ECBB_DIR . 'assets/css/events-widget/ecbb-events-widget-base.css';
        wp_enqueue_style(
            'ecbb-events-widget-base',
            ECBB_URL . 'assets/css/events-widget/ecbb-events-widget-base.css',
            [],
            file_exists( $base_path ) ? (string) filemtime( $base_path ) : ECBB_VERSION
        );

        $list1_path = ECBB_DIR . 'assets/css/events-widget/template/list/list-style-1.css';
        wp_enqueue_style(
            'ecbb-list-1',
            ECBB_URL . 'assets/css/events-widget/template/list/list-style-1.css',
            [ 'ecbb-events-widget-base' ],
            file_exists( $list1_path ) ? (string) filemtime( $list1_path ) : ECBB_VERSION
        );

        $list2_path = ECBB_DIR . 'assets/css/events-widget/template/list/list-style-2.css';
        wp_enqueue_style(
            'ecbb-list-2',
            ECBB_URL . 'assets/css/events-widget/template/list/list-style-2.css',
            [ 'ecbb-events-widget-base' ],
            file_exists( $list2_path ) ? (string) filemtime( $list2_path ) : ECBB_VERSION
        );

        $grid_path = ECBB_DIR . 'assets/css/events-widget/grid/ecbb-events-widget-grid.css';
        wp_enqueue_style(
            'ecbb-events-widget-grid',
            ECBB_URL . 'assets/css/events-widget/grid/ecbb-events-widget-grid.css',
            [ 'ecbb-events-widget-base', 'ecbb-list-1' ],
            file_exists( $grid_path ) ? (string) filemtime( $grid_path ) : ECBB_VERSION
        );
    }

    /**
     * Bricks main builder only: pill tabs for Event parts repeater (CONTENT | STYLE).
     */
    public function ecbb_enqueue_builder_assets() {
        if ( ! function_exists( 'bricks_is_builder_main' ) || ! bricks_is_builder_main() ) {
            return;
        }

        $builder_css_path = ECBB_DIR . 'assets/css/ecbb-builder.css';
        wp_enqueue_style(
            'ecbb-builder',
            ECBB_URL . 'assets/css/ecbb-builder.css',
            [ 'bricks-builder' ],
            file_exists( $builder_css_path ) ? (string) filemtime( $builder_css_path ) : ECBB_VERSION
        );

        $builder_js_path = ECBB_DIR . 'assets/js/ecbb-builder.js';
        wp_enqueue_script(
            'ecbb-builder',
            ECBB_URL . 'assets/js/ecbb-builder.js',
            [],
            file_exists( $builder_js_path ) ? (string) filemtime( $builder_js_path ) : ECBB_VERSION,
            true
        );

        wp_localize_script( 'ecbb-builder', 'ECBBBuilder', [
            'tabContent'  => esc_html__( 'CONTENT', 'ecbb' ),
            'tabStyle'    => esc_html__( 'STYLE', 'ecbb' ),
            'hoverParts'  => function_exists( 'ecbb_events_widget_repeater_hover_part_slugs' )
                ? ecbb_events_widget_repeater_hover_part_slugs()
                : [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp', 'image' ],
            'interactiveHoverParts' => function_exists( 'ecbb_events_widget_repeater_interactive_hover_part_slugs' )
                ? ecbb_events_widget_repeater_interactive_hover_part_slugs()
                : [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ],
            'interactiveHoverKeys' => [
                'ecbb_sep_hover',
                'ecbb_hover_color',
                'ecbb_hover_background',
                'ecbb_hover_text_decoration',
                'ecbb_hover_animation',
            ],
            'hoverKeys'   => function_exists( 'ecbb_events_widget_repeater_hover_control_keys' )
                ? ecbb_events_widget_repeater_hover_control_keys()
                : [
                    'ecbb_sep_hover',
                    'ecbb_use_hover',
                    'ecbb_hover_color',
                    'ecbb_hover_background',
                    'ecbb_hover_text_decoration',
                    'ecbb_hover_animation',
                    'image_size_hover',
                    'ecbb_image_object_align_hover',
                ],
        ] );
    }

    private function ecbb_normalize_color_value( $value ) {
        return function_exists( 'ecbb_normalize_bricks_color' ) ? ecbb_normalize_bricks_color( $value ) : '';
    }

    private function ecbb_normalize_css_size( $value, $default_unit = 'px' ) {
        $value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );
        if ( $value === '' ) {
            return '';
        }

        if ( preg_match( '/^-?\\d*\\.?\\d+(px|rem|em|%)$/', $value ) ) {
            return $value;
        }

        if ( preg_match( '/^-?\\d*\\.?\\d+$/', $value ) ) {
            $unit = in_array( $default_unit, [ 'px', 'rem', 'em', '%' ], true ) ? $default_unit : 'px';
            return $value . $unit;
        }

        return '';
    }

    private function ecbb_build_inline_style_attr( array $item, $allow_radius = false ) {
        if ( function_exists( 'ecbb_events_widget_build_inline_style_attr' ) ) {
            return ecbb_events_widget_build_inline_style_attr( $item, $allow_radius );
        }
        return '';
    }

    private function ecbb_render_part_html( \WP_Post $post, array $item, int $idx, string $skin = '' ): string {
        if ( function_exists( 'ecbb_events_widget_normalize_part_item' ) ) {
            $item = ecbb_events_widget_normalize_part_item( $item );
        }
        $part = isset( $item['part'] ) ? (string) $item['part'] : 'title';
        $skin = (string) $skin;
        $wrap = function_exists( 'ecbb_events_widget_part_wrap_classes' )
            ? ecbb_events_widget_part_wrap_classes( $part, $idx, $skin )
            : ( 'ecbb-event-part ecbb-event-part--' . str_replace( '_', '-', $part ) . ' ecbb-p' . absint( $idx ) );

        if ( function_exists( 'ecbb_event_part_extended_markup' ) ) {
            $ext = ecbb_event_part_extended_markup( $post, $item, $idx, $this->ecbb_build_inline_style_attr( $item ), $skin );
            if ( $ext !== false ) {
                return $ext;
            }
        }

        if ( $part === 'image' ) {
            $thumb_id = (int) get_post_thumbnail_id( $post->ID );
            if ( ! $thumb_id ) {
                return '';
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
                return '';
            }

            $link = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
            $dual = function_exists( 'ecbb_loop_image_uses_dual_layer' ) && ecbb_loop_image_uses_dual_layer( $item );
            $part_classes = $wrap;
            if ( $dual ) {
                $part_classes .= ' ecbb-is-dual-img';
            }

            $out  = '<div class="' . esc_attr( $part_classes ) . '">';
            if ( $link ) {
                $out .= '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">';
            }
            $out .= $image_html;
            if ( $link ) {
                $out .= '</a>';
            }
            $out .= '</div>';
            return $out;
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
                return '';
            }

            $style = $this->ecbb_build_inline_style_attr( $item );
            $link_enabled = ! empty( $item['venue_link'] );
            $url = '';
            if ( $link_enabled && function_exists( 'tribe_get_venue_link' ) ) {
                $url = (string) \tribe_get_venue_link( $post->ID );
            }
            $classes = $wrap . ' ecbb-has-row-icon';
            if ( $link_enabled && $url ) {
                return '<div class="' . esc_attr( $classes ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '><span class="ecbb-event__link-wrapper">' . wp_kses_post( $url ) . '</span></div>';
            }

            return '<div class="' . esc_attr( $classes ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . esc_html( $venue ) . '</div>';
        }

        if ( $part === 'categories' ) {
            $terms = get_the_terms( $post->ID, 'tribe_events_cat' );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                return '';
            }

            $style      = $this->ecbb_build_inline_style_attr( $item );
            $link_style = $style ? ' style="' . esc_attr( $style ) . '"' : '';
            $sep        = isset( $item['terms_separator'] ) ? (string) $item['terms_separator'] : ', ';
            $sep        = $sep !== '' ? $sep : ', ';
            $link_terms = ! array_key_exists( 'terms_link', $item ) ? true : (bool) $item['terms_link'];
            $links = [];
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
                return '';
            }

            return '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . wp_kses_post( implode( esc_html( $sep ), $links ) ) . '</div>';
        }

        if ( $part === 'tags' ) {
            $terms = get_the_terms( $post->ID, 'post_tag' );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                return '';
            }

            $style      = $this->ecbb_build_inline_style_attr( $item );
            $link_style = $style ? ' style="' . esc_attr( $style ) . '"' : '';
            $sep        = isset( $item['terms_separator'] ) ? (string) $item['terms_separator'] : ', ';
            $sep        = $sep !== '' ? $sep : ', ';
            $link_terms = ! array_key_exists( 'terms_link', $item ) ? true : (bool) $item['terms_link'];
            $links = [];
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
                return '';
            }

            return '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . wp_kses_post( implode( esc_html( $sep ), $links ) ) . '</div>';
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
            return '<div class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . wp_kses_post( $content ) . '</div>';
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
                return '';
            }

            $style = $this->ecbb_build_inline_style_attr( $item );
            $tt    = isset( $item['date_text_transform'] ) ? strtolower( trim( (string) $item['date_text_transform'] ) ) : 'capitalize';
            if ( ! in_array( $tt, [ 'capitalize', 'none', 'uppercase', 'lowercase' ], true ) ) {
                $tt = 'capitalize';
            }
            $style = trim( (string) $style );
            $day_style = $tt !== '' ? 'text-transform:' . $tt . ';' : '';
            $row_icon  = ( $time !== '' ) ? ' ecbb-has-row-icon' : '';
            $out  = '<div class="' . esc_attr( $wrap . $row_icon ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
            $out .= '<span class="ecbb-event__date-day"' . ( $day_style ? ' style="' . esc_attr( $day_style ) . '"' : '' ) . '>' . esc_html( $day ) . '</span>';
            if ( $time !== '' ) {
                $out .= '<span class="ecbb-event__date-sep">,</span>';
                $out .= '<span class="ecbb-event__date-time">' . esc_html( $time ) . '</span>';
            }
            $out .= '</div>';
            return $out;
        }

        // Title.
        $tag  = isset( $item['tag'] ) ? (string) $item['tag'] : 'h3';
        $tag  = class_exists( '\Bricks\Helpers' ) ? \Bricks\Helpers::sanitize_html_tag( $tag, 'h3' ) : $tag;
        $link = ! empty( $item['link'] );
        $style = $this->ecbb_build_inline_style_attr( $item );

        $out = '<' . esc_attr( $tag ) . ' class="' . esc_attr( $wrap ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
        if ( $link ) {
            $out .= '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
        }
        $out .= esc_html( get_the_title( $post->ID ) );
        if ( $link ) {
            $out .= '</a>';
        }
        $out .= '</' . esc_attr( $tag ) . '>';
        return $out;
    }

    public function ecbb_ajax_events_load_more() {
        check_ajax_referer( 'ecbb_events_load_more', 'nonce' );

        $raw_settings = isset( $_POST['ecbb_settings'] ) ? wp_unslash( (string) $_POST['ecbb_settings'] ) : '';
        $settings     = $raw_settings !== '' ? json_decode( $raw_settings, true ) : null;
        $offset       = isset( $_POST['ecbb_offset'] ) ? absint( $_POST['ecbb_offset'] ) : 0;
        $limit        = isset( $_POST['ecbb_limit'] ) ? absint( $_POST['ecbb_limit'] ) : 6;

        if ( ! is_array( $settings ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'Invalid settings.', 'ecbb' ),
                ],
                400
            );
        }

        if ( function_exists( 'ecbb_events_widget_sanitize_load_more_settings' ) ) {
            $settings = ecbb_events_widget_sanitize_load_more_settings( $settings );
        }

        $item_chrome = function_exists( 'ecbb_sanitize_list_item_style' )
            ? ecbb_sanitize_list_item_style( $settings['list_item_style'] ?? 'style-1' )
            : 'style-1';

        $template = isset( $settings['layout_template'] ) ? (string) $settings['layout_template'] : 'list';
        if ( $template === 'carousel' ) {
            $template = 'list';
        }
        $template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';

        $use_style1 = ( $template === 'list' && $item_chrome === 'style-1' );
        $use_style2 = ( $template === 'list' && $item_chrome === 'style-2' );
        $use_grid   = ( $template === 'grid' );

        $item_classes = $use_grid
            ? 'ecbb-ev__item ecbb-ev__item--grid'
            : 'ecbb-ev__item ecbb-ev__item--' . $item_chrome;

        $parts_to_use = function_exists( 'ecbb_events_widget_resolve_event_parts_for_context' )
            ? ecbb_events_widget_resolve_event_parts_for_context( $settings, $template, $item_chrome )
            : [];
        if ( ! is_array( $parts_to_use ) ) {
            $parts_to_use = [];
        }
        if ( $use_grid && function_exists( 'ecbb_events_widget_grid_normalize_parts' ) ) {
            $parts_to_use = ecbb_events_widget_grid_normalize_parts( $parts_to_use );
        } elseif ( $use_style1 && function_exists( 'ecbb_list1_normalize_parts' ) ) {
            $parts_to_use = ecbb_list1_normalize_parts( $parts_to_use );
        } elseif ( $use_style2 ) {
            $parts_to_use = ecbb_list2_normalize_parts( $parts_to_use );
        } elseif (
            $parts_to_use === []
            || ( function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' ) && ecbb_events_widget_parts_array_is_effectively_empty( $parts_to_use ) )
        ) {
            $parts_to_use = [
                [ 'part' => 'title', 'tag' => 'h3', 'link' => true ],
                [ 'part' => 'description', 'desc_source' => 'content' ],
                [ 'part' => 'date', 'date_text_transform' => 'uppercase' ],
            ];
        }

        if ( $limit < 1 ) {
            $limit = 6;
        }

        $base = function_exists( 'ecbb_events_widget_query_tribe_args' ) ? ecbb_events_widget_query_tribe_args( $settings ) : [];
        if ( ! is_array( $base ) ) {
            $base = [];
        }
        $base['post_type']           = 'tribe_events';
        $base['post_status']         = 'publish';
        $base['posts_per_page']      = $limit + 1; // fetch one extra to know if more exist
        $base['offset']              = $offset;
        $base['no_found_rows']       = true;
        $base['ignore_sticky_posts'] = true;

        $q = new \WP_Query( $base );

        $posts = $q->posts;
        $has_more = count($posts) > $limit;
        if ( $has_more ) {
            array_pop($posts);
        }

        $style2_last_month = null;
        $style2_show_month = ecbb_list2_month_headings_enabled( $settings );

        $style1_date_fmt = 'default';
        if ( $use_style1 && function_exists( 'ecbb_list1_sanitize_date_format' ) ) {
            $style1_date_fmt = ecbb_list1_sanitize_date_format( $settings['date_format'] ?? 'default' );
        }

        $html_items = '';
        foreach ( $posts as $p ) {
            if ( ! $p instanceof \WP_Post ) {
                continue;
            }

            if ( $use_style2 ) {
                $html_items .= ecbb_list2_maybe_month_heading_html( $p->ID, $style2_last_month, $style2_show_month );
            }

            $html_items .= '<div class="' . esc_attr( $item_classes ) . '">';

            if ( $use_style1 && function_exists( 'ecbb_list1_item_inner_markup' ) ) {
                $gap_inner = 'display:flex;flex-direction:column;gap:12px;';
                $self      = $this;
                $html_items .= ecbb_list1_item_inner_markup(
                    $p,
                    $parts_to_use,
                    $gap_inner,
                    function ( $ev, $item, $idx ) use ( $self ) {
                        echo $self->ecbb_render_part_html( $ev, $item, $idx, 'style1' );
                    },
                    $style1_date_fmt
                );
            } elseif ( $use_style2 ) {
                $gap_inner = ecbb_list2_body_stack_gap_style( 12, 'px' );
                $self      = $this;
                $html_items .= ecbb_list2_item_inner_markup(
                    $p,
                    $parts_to_use,
                    $gap_inner,
                    function ( $ev, $item, $idx ) use ( $self ) {
                        echo $self->ecbb_render_part_html( $ev, $item, $idx, 'style2' );
                    }
                );
            } elseif ( $use_grid && function_exists( 'ecbb_events_widget_grid_item_inner_markup' ) ) {
                $gap_inner = 'display:flex;flex-direction:column;gap:8px;';
                $self      = $this;
                $html_items .= ecbb_events_widget_grid_item_inner_markup(
                    $p,
                    $parts_to_use,
                    $gap_inner,
                    function ( $ev, $item, $idx ) use ( $self ) {
                        echo $self->ecbb_render_part_html( $ev, $item, $idx );
                    }
                );
            } else {
                $part_idx   = 0;
                $part_html = '';
                foreach ( $parts_to_use as $item ) {
                    if ( ! is_array( $item ) ) {
                        continue;
                    }
                    $part_html .= $this->ecbb_render_part_html( $p, $item, $part_idx );
                    $part_idx++;
                }
                $html_items .= $part_html;
            }

            $html_items .= '</div>';
        }

        wp_send_json_success([
            'html'      => $html_items,
            'nextOffset'=> $offset + count($posts),
            'hasMore'   => $has_more,
        ]);
    }
}