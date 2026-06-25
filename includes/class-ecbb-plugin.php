<?php
/**
 * Main plugin bootstrap (ECBB prefix: scripts, elements).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'ECBB_WidgetClass', false ) ) {

final class ECBB_WidgetClass {

    public function __construct() {
        add_action( 'init', [ $this, 'ecbb_register_elements' ], 11 );
        add_action( 'wp_enqueue_scripts', [ $this, 'ecbb_enqueue_scripts' ], 25 );
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
        $post_id = absint( wp_unslash( $_POST['postId'] ) );
        if ( $post_id < 1 ) {
            return;
        }
        if ( false === check_ajax_referer( 'bricks-nonce', 'nonce', false ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( [ 'content', 'header', 'footer' ] as $area ) {
            $post_key = $area;
            if ( empty( $_POST[ $post_key ] ) || ! is_string( $_POST[ $post_key ] ) ) {
                continue;
            }
            $posted_json = wp_unslash( $_POST[ $post_key ] );
            $merged      = $this->ecbb_merge_events_loop_repeaters_into_posted_area( $posted_json, $post_id, $area );
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
        if ( ! function_exists( 'ecbb_sanitize_list_item_style' ) || ! function_exists( 'ecbb_parts_array_is_effectively_empty' ) ) {
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

            if ( function_exists( 'ecbb_normalize_element_parts_hover_toggles' ) ) {
                $new_settings = ecbb_normalize_element_parts_hover_toggles( $new_settings );
            }

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
                $incoming_empty = ! is_array( $incoming ) || ecbb_parts_array_is_effectively_empty( $incoming );
                if ( ! $incoming_empty ) {
                    continue;
                }
                if ( ! isset( $old_settings[ $key ] ) || ! is_array( $old_settings[ $key ] ) ) {
                    continue;
                }
                if ( ecbb_parts_array_is_effectively_empty( $old_settings[ $key ] ) ) {
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
     * Empty repeaters are left unchanged so {@see ecbb_resolve_event_parts_for_context()}
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

        if ( function_exists( 'ecbb_normalize_element_parts_hover_toggles' ) ) {
            $settings = ecbb_normalize_element_parts_hover_toggles( $settings );
        }

        $template = isset( $settings['layout_template'] ) ? (string) $settings['layout_template'] : 'list';
        if ( $template === 'carousel' ) {
            $template = 'list';
        }
        $template = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';
        $item_chrome = ecbb_sanitize_list_item_style( $settings['list_item_style'] ?? 'style-1' );

        if ( $template === 'list' && $item_chrome === 'style-1' ) {
            $p = isset( $settings['parts_style1'] ) && is_array( $settings['parts_style1'] ) ? $settings['parts_style1'] : [];
            if ( function_exists( 'ecbb_parts_array_is_effectively_empty' )
                && ! ecbb_parts_array_is_effectively_empty( $p )
                && function_exists( 'ecbb_list1_normalize_parts' ) ) {
                $settings['parts_style1'] = ecbb_list1_normalize_parts( $p );
            }
        } elseif ( $template === 'list' && $item_chrome === 'style-2' ) {
            $p = isset( $settings['parts_style2'] ) && is_array( $settings['parts_style2'] ) ? $settings['parts_style2'] : [];
            if ( function_exists( 'ecbb_parts_array_is_effectively_empty' )
                && ! ecbb_parts_array_is_effectively_empty( $p ) ) {
                $settings['parts_style2'] = ecbb_list2_normalize_parts( $p );
            }
        } elseif ( $template === 'grid' ) {
            $p = isset( $settings['parts_grid'] ) && is_array( $settings['parts_grid'] ) ? $settings['parts_grid'] : [];
            if ( function_exists( 'ecbb_parts_array_is_effectively_empty' )
                && ! ecbb_parts_array_is_effectively_empty( $p )
                && function_exists( 'ecbb_grid_normalize_parts' ) ) {
                $settings['parts_grid'] = ecbb_grid_normalize_parts( $p );
            }
        }

        if ( function_exists( 'ecbb_migrate_event_cost_currency_into_repeaters' ) ) {
            $settings = ecbb_migrate_event_cost_currency_into_repeaters( $settings );
        }

        return $settings;
    }

    /**
     * Register custom elements
     */
    public function ecbb_register_elements() {
        if ( ! class_exists( '\Bricks\Elements' ) || ! class_exists( '\Bricks\Element' ) ) {
            return;
        }

        if ( isset( \Bricks\Elements::$elements['ecbb-events-loop'] ) ) {
            return;
        }

        $file  = ECBB_DIR . 'widgets/ecbb-widget.php';
        $class = 'ECBB\\Element_ECBB_Events_Widget';

        if ( ! is_readable( $file ) ) {
            return;
        }

        \Bricks\Elements::register_element( $file, 'ecbb-events-loop', $class );
    }

    /**
     * Enqueue front-end widget styles, builder panel assets, and iframe preview CSS.
     */
    public function ecbb_enqueue_scripts() {
        $this->ecbb_enqueue_events_widget_styles();

        if ( function_exists( 'bricks_is_builder_main' ) && bricks_is_builder_main() ) {
            $this->ecbb_enqueue_builder_panel_assets();
        }

        if ( function_exists( 'bricks_is_builder_iframe' ) && bricks_is_builder_iframe()
            && ! wp_style_is( 'ecbb-events-widget-base', 'enqueued' )
            && ! wp_style_is( 'ecbb-events-widget-base', 'done' ) ) {
            $this->ecbb_enqueue_events_widget_styles();
        }
    }

    /**
     * Front-end CSS for the Events Widget (base + list styles under template/list + grid).
     */
    private function ecbb_enqueue_events_widget_styles() {
        $this->ecbb_enqueue_style( 'ecbb-events-widget-base', 'assets/css/events-widget/ecbb-events-widget-base.css' );
        $this->ecbb_enqueue_style( 'ecbb-list-1', 'assets/css/events-widget/template/list/list-style-1.css', [ 'ecbb-events-widget-base' ] );
        $this->ecbb_enqueue_style( 'ecbb-list-2', 'assets/css/events-widget/template/list/list-style-2.css', [ 'ecbb-events-widget-base' ] );
        $this->ecbb_enqueue_style( 'ecbb-events-widget-grid', 'assets/css/events-widget/grid/ecbb-events-widget-grid.css', [ 'ecbb-events-widget-base' ] );
    }

    /**
     * Enqueue a plugin stylesheet from a single relative path.
     *
     * Resolves the disk path (for cache-busting via filemtime) and the public URL
     * from one relative path, so the path is never duplicated at the call site.
     *
     * @param string        $handle        Unique style handle.
     * @param string        $relative_path Path relative to the plugin root (e.g. assets/css/foo.css).
     * @param array<string> $deps          Handles this style depends on.
     * @return void
     */
    private function ecbb_enqueue_style( $handle, $relative_path, array $deps = [] ) {
        $disk_path = ECBB_DIR . $relative_path;
        $version   = file_exists( $disk_path ) ? (string) filemtime( $disk_path ) : ECBB_VERSION;

        wp_enqueue_style( $handle, ECBB_URL . $relative_path, $deps, $version );
    }

    /**
     * Bricks main builder only: pill tabs for Event parts repeater (CONTENT | STYLE).
     */
    private function ecbb_enqueue_builder_panel_assets() {
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
            'hoverParts'  => function_exists( 'ecbb_repeater_hover_part_slugs' )
                ? ecbb_repeater_hover_part_slugs()
                : [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp', 'image' ],
            'interactiveHoverParts' => function_exists( 'ecbb_repeater_interactive_hover_part_slugs' )
                ? ecbb_repeater_interactive_hover_part_slugs()
                : [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ],
            'interactiveHoverKeys' => [
                'ecbb_sep_hover',
                'ecbb_hover_color',
                'ecbb_hover_background',
                'ecbb_hover_text_decoration',
                'ecbb_hover_animation',
            ],
            'hoverKeys'   => function_exists( 'ecbb_repeater_hover_control_keys' )
                ? ecbb_repeater_hover_control_keys()
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
            'btnBorderKeys' => function_exists( 'ecbb_repeater_btn_border_control_keys' )
                ? ecbb_repeater_btn_border_control_keys()
                : [
                    'btn_sep_border',
                    'btn_border_type',
                    'btn_border_width',
                    'btn_border_color',
                    'btn_padding',
                    'btn_border_radius',
                ],
        ] );
    }
}

}

if ( ! class_exists( 'ECBB_Plugin', false ) ) {
	class_alias( 'ECBB_WidgetClass', 'ECBB_Plugin' );
}
