<?php
/**
 * Main plugin bootstrap (ECBB prefix: scripts, elements).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'ECBB_Plugin', false ) ) {

	final class ECBB_Plugin {

		/**
		 * Layout template files (loaded on demand — never globally).
		 *
		 * @var array<int,string>
		 */
		const ECBB_LAYOUT_FILES = [
			'templates/class-ecbb-layout-base.php',
			'templates/list/list-style-1.php',
			'templates/list/list-style-2.php',
			'templates/grid/grid.php',
		];

		public function __construct() {
			add_action( 'init', [ $this, 'ecbb_register_elements' ], 11 );
			add_action( 'wp_enqueue_scripts', [ $this, 'ecbb_enqueue_scripts' ], 25 );
			add_filter( 'bricks/element/settings', [ $this, 'ecbb_filter_events_loop_element_settings' ], 10, 2 );
			add_action( 'wp_ajax_bricks_save_post', [ $this, 'ecbb_preflight_merge_events_loop_repeaters_on_bricks_save' ], 0 );
		}

		/**
		* Full render stack (query, markup, styles, controls) for widget output.
		*
		* Lighter paths use {@see self::ecbb_load_markup_dependencies()} or
		* {@see self::ecbb_load_builder_panel_dependencies()} instead. Layout
		* templates in {@see self::ECBB_LAYOUT_FILES} are loaded separately via
		* {@see self::ecbb_load_layouts()}. Idempotent via require_once.
		*
		* @return void
		*/
		public static function ecbb_load_render_dependencies() {
			foreach ( [ 'includes/query.php', 'includes/markup/markup.php', 'includes/styles.php', 'includes/controls.php' ] as $relative_path ) {
				self::ecbb_require_file( $relative_path );
			}
		}

		/**
		* Markup helpers only (AJAX save merge, settings filter).
		*
		* @return void
		*/
		private static function ecbb_load_markup_dependencies() {
			self::ecbb_require_file( 'includes/markup/markup.php' );
		}

		/**
		* Builder panel localization (controls delegate to markup for hover types).
		*
		* @return void
		*/
		private static function ecbb_load_builder_panel_dependencies() {
			self::ecbb_load_markup_dependencies();
			self::ecbb_require_file( 'includes/controls.php' );
		}

		/**
		* @param string $relative_path Path relative to ECBB_DIR.
		* @return void
		*/
		private static function ecbb_require_file( $relative_path ) {
			require_once ECBB_DIR . $relative_path;
		}

		/**
		* Load the layout template files on demand (never globally).
		*
		* The per-layout normalizers cross-reference each other to detect and
		* upgrade stacks left over from a different template, so all three are
		* loaded together once any layout is in play. Idempotent via require_once.
		*
		* @return void
		*/
		public static function ecbb_load_layouts() {
			foreach ( self::ECBB_LAYOUT_FILES as $relative_path ) {
				self::ecbb_require_file( $relative_path );
			}
		}

		/**
		* Whether a layout-specific parts repeater is the one currently selected in the UI.
		*
		* @param string $parts_repeater_key parts_style1|parts_style2|parts_grid
		* @param string $layout_template    list|grid
		* @param string $list_item_style    style-1|style-2
		* @return bool
		*/
		private static function ecbb_is_active_parts_repeater( $parts_repeater_key, $layout_template, $list_item_style ) {
			if ( 'parts_grid' === $parts_repeater_key ) {
				return 'grid' === $layout_template;
			}
			if ( 'parts_style1' === $parts_repeater_key ) {
				return 'list' === $layout_template && 'style-1' === $list_item_style;
			}
			if ( 'parts_style2' === $parts_repeater_key ) {
				return 'list' === $layout_template && 'style-2' === $list_item_style;
			}
			return false;
		}

		/**
		* Before Bricks reads $_POST content, merge inactive layout repeaters from the last saved data.
		* Hidden `required` repeaters are often sent empty/omitted when saving while another template is selected,
		* which cleared `parts_grid` / `parts_style*` in post meta after refresh.
		*
		* @return void
		*/
		public function ecbb_preflight_merge_events_loop_repeaters_on_bricks_save() {
			if ( ! isset( $_POST['postId'] ) || ! class_exists( '\Bricks\Ajax' ) || ! class_exists( '\Bricks\Database' ) ) {
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

			self::ecbb_load_markup_dependencies();

			foreach ( [ 'content', 'header', 'footer' ] as $area ) {
				if ( ! isset( $_POST[ $area ] ) || ! is_string( $_POST[ $area ] ) || $_POST[ $area ] === '' ) {
					continue;
				}
				// Bricks element-tree JSON; decoded via Bricks\Ajax::decode() below.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$posted_json = wp_unslash( $_POST[ $area ] );
				$merged      = $this->ecbb_merge_events_loop_repeaters_into_posted_area( $posted_json, $post_id, $area );
				if ( is_string( $merged ) ) {
					$_POST[ $area ] = $merged;
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
			$elements_by_id = [];
			foreach ( $elements as $element ) {
				if ( ! is_array( $element ) || empty( $element['id'] ) ) {
					continue;
				}
				$elements_by_id[ (string) $element['id'] ] = $element;
			}
			return $elements_by_id;
		}

		/**
		* @param array<int,array<string,mixed>>    $new_elements
		* @param array<string,array<string,mixed>> $old_elements_indexed
		* @return array<int,array<string,mixed>>
		*/
		private function ecbb_apply_inactive_part_repeater_preservation( array $new_elements, array $old_elements_indexed ) {
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

				$layout          = \ECBB_Markup::ecbb_sanitize_layout_template( $new_settings );
				$layout_template = $layout['template'];
				$list_item_style = $layout['item_chrome'];

				foreach ( [ 'parts_style1', 'parts_style2', 'parts_grid' ] as $parts_repeater_key ) {
					if ( self::ecbb_is_active_parts_repeater( $parts_repeater_key, $layout_template, $list_item_style ) ) {
						continue;
					}

					$posted_parts = $new_settings[ $parts_repeater_key ] ?? null;
					$posted_parts_empty = ! is_array( $posted_parts ) || \ECBB_Markup::ecbb_parts_is_empty( $posted_parts );
					if ( ! $posted_parts_empty ) {
						continue;
					}
					if ( ! isset( $old_settings[ $parts_repeater_key ] ) || ! is_array( $old_settings[ $parts_repeater_key ] ) ) {
						continue;
					}
					if ( \ECBB_Markup::ecbb_parts_is_empty( $old_settings[ $parts_repeater_key ] ) ) {
						continue;
					}
					$new_settings[ $parts_repeater_key ] = $old_settings[ $parts_repeater_key ];
				}
				unset( $new_settings );
			}

			return $new_elements;
		}

		/**
		* Coerce layout-specific repeaters when another layout's row stack was left on disk
		* (e.g. Grid defaults still stored on `parts_style1` after switching to List Style 1).
		* Empty repeaters are left unchanged so {@see \ECBB_Markup::ecbb_resolve_parts()}
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

			self::ecbb_load_markup_dependencies();

			$settings = \ECBB_Markup::ecbb_norm_settings_hover( $settings );

			$settings = \ECBB_Markup::ecbb_migrate_cost_currency( $settings );

			$settings = \ECBB_Markup::ecbb_norm_layout_shell_settings( $settings );

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

			$file  = ECBB_DIR . 'includes/class-ecbb-widget.php';
			$class = 'ECBB\\Element_ECBB_Events_Widget';

			if ( ! is_readable( $file ) ) {
				return;
			}

			\Bricks\Elements::register_element( $file, 'ecbb-events-loop', $class );
		}

		/**
		* Register widget styles globally; enqueue only when the element renders
		* ({@see ECBB_Widget::enqueue_scripts()}), like ECT loads CSS per shortcode.
		*/
		public function ecbb_enqueue_scripts() {
			self::ecbb_register_events_widget_styles();

			if ( function_exists( 'bricks_is_builder_main' ) && bricks_is_builder_main() ) {
				$this->ecbb_enqueue_builder_panel_assets();
			}
		}

		/**
		* Register front-end CSS for the Events Widget (base + list/grid templates).
		*
		* @return void
		*/
		public static function ecbb_register_events_widget_styles() {
			$styles = [
				[ 'ecbb-events-widget-base', 'assets/css/events-widget/ecbb-events-widget-base.css', [] ],
				[ 'ecbb-featured-image-shell', 'assets/css/events-widget/ecbb-featured-image-shell.css', [ 'ecbb-events-widget-base' ] ],
				[ 'ecbb-list-1', 'assets/css/events-widget/list-style-1.css', [ 'ecbb-events-widget-base', 'ecbb-featured-image-shell' ] ],
				[ 'ecbb-list-2', 'assets/css/events-widget/list-style-2.css', [ 'ecbb-events-widget-base', 'ecbb-featured-image-shell' ] ],
				[ 'ecbb-events-widget-grid', 'assets/css/events-widget/ecbb-events-widget-grid.css', [ 'ecbb-events-widget-base', 'ecbb-featured-image-shell' ] ],
			];

			foreach ( $styles as $style ) {
				list( $handle, $relative_path, $deps ) = $style;
				if ( wp_style_is( $handle, 'registered' ) ) {
					continue;
				}
				$disk_path = ECBB_DIR . $relative_path;
				$version   = file_exists( $disk_path ) ? (string) filemtime( $disk_path ) : ECBB_VERSION;
				wp_register_style( $handle, ECBB_URL . $relative_path, $deps, $version );
			}
		}

		/**
		* Enqueue front-end widget styles (called from the Bricks element only).
		*
		* @return void
		*/
		public static function ecbb_enqueue_events_widget_styles() {
			self::ecbb_register_events_widget_styles();
			foreach ( [ 'ecbb-events-widget-base', 'ecbb-featured-image-shell', 'ecbb-list-1', 'ecbb-list-2', 'ecbb-events-widget-grid' ] as $handle ) {
				if ( ! wp_style_is( $handle, 'enqueued' ) ) {
					wp_enqueue_style( $handle );
				}
			}
		}

		/**
		* Bricks main builder only: pill tabs for Event parts repeater (CONTENT | STYLE).
		*/
		private function ecbb_enqueue_builder_panel_assets() {
			self::ecbb_load_builder_panel_dependencies();

			$builder_css_path = ECBB_DIR . 'assets/css/ecbb-builder.css';
			wp_enqueue_style(
				'ecbb-builder',
				ECBB_URL . 'assets/css/ecbb-builder.css',
				[ 'bricks-builder' ],
				file_exists( $builder_css_path ) ? (string) filemtime( $builder_css_path ) : ECBB_VERSION
			);

			$builder_dir   = ECBB_DIR . 'assets/js/builder/';
			$builder_url   = ECBB_URL . 'assets/js/builder/';
			$prev_handle   = 'bricks-builder';
			$localize_on   = '';

			foreach (
				[
					'ecbb-builder-core'     => 'core.js',
					'ecbb-builder-tabs'     => 'tabs.js',
					'ecbb-builder-controls' => 'controls.js',
					'ecbb-builder-sync'     => 'sync.js',
					'ecbb-builder-preview'  => 'preview.js',
					'ecbb-builder-button'   => 'button.js',
					'ecbb-builder-main'     => 'main.js',
				] as $handle => $file
			) {
				$path = $builder_dir . $file;
				wp_enqueue_script(
					$handle,
					$builder_url . $file,
					[ $prev_handle ],
					file_exists( $path ) ? (string) filemtime( $path ) : ECBB_VERSION,
					true
				);
				if ( $localize_on === '' ) {
					$localize_on = $handle;
				}
				$prev_handle = $handle;
			}

			wp_localize_script( $localize_on, 'ECBBBuilder', [
				'tabContent'          => __( 'CONTENT', 'events-calendar-for-bricks' ),
				'tabStyle'            => __( 'STYLE', 'events-calendar-for-bricks' ),
				'hoverParts'          => \ECBB_Controls::ecbb_hover_part_types(),
				'hoverKeys'           => \ECBB_Controls::ecbb_hover_field_keys(),
				'btnBorderKeys'       => \ECBB_Controls::ecbb_btn_border_keys(),
				'style2MetaIconParts' => \ECBB_Controls::ecbb_style2_meta_icon_ui_parts(),
			] );
		}
	}

}
