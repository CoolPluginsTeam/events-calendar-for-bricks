<?php
/**
 * ECBB_Markup facade — delegates to markup service classes.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dir = ECBB_DIR . 'includes/markup/events-elements-markups/';
foreach (
	[
		'ecbb-settings.php',
		'ecbb-cost.php',
		'ecbb-event-data.php',
		'ecbb-dates-format.php',
		'ecbb-repeater-parts.php',
		'ecbb-layout-cards.php',
		'ecbb-attributes.php',
	] as $file
) {
	require_once $dir . $file;
}
unset( $dir, $file );

if ( ! class_exists( 'ECBB_Markup', false ) ) {

	final class ECBB_Markup {

		/** @var array<string,string>|null */
		private static $delegates = null;

		/** Build method => service class map (cached). */
		private static function delegates(): array {
			if ( is_array( self::$delegates ) ) {
				return self::$delegates;
			}
			$bind = static function ( array $methods, string $class ): array {
				return array_fill_keys( $methods, $class );
			};
			self::$delegates = array_merge(
				$bind(
					[
						'ecbb_sanitize_template', 'ecbb_sanitize_list_style', 'ecbb_sanitize_layout_template',
						'ecbb_parts_clean', 'ecbb_parts_slugs', 'ecbb_parts_preserve_bricks_rows',
						'ecbb_upgrade_layout_parts', 'ecbb_parts_is_empty', 'ecbb_parts_assign_ids',
						'ecbb_hover_interactive_types', 'ecbb_hover_part_types', 'ecbb_part_has_hover',
						'ecbb_hover_on_values', 'ecbb_hover_is_on', 'ecbb_norm_hover_row',
						'ecbb_norm_parts_hover', 'ecbb_norm_settings_hover', 'ecbb_resolve_parts',
						'ecbb_migrate_cost_currency', 'ecbb_layout_settings', 'ecbb_norm_layout_shell_settings',
						'ecbb_shell_select_on', 'ecbb_show_event_image', 'ecbb_show_shell_category_badge',
						'ecbb_show_style2_date_badge', 'ecbb_style2_date_badge_order', 'ecbb_list1_date_column_order',
					],
					'ECBB_Settings_Normalizer'
				),
				$bind(
					[
						'ecbb_cost_currency_opts', 'ecbb_sanitize_cost_currency', 'ecbb_cost_is_free',
						'ecbb_cost_currency_symbol', 'ecbb_strip_cost_symbols', 'ecbb_format_cost_token',
						'ecbb_apply_cost_currency', 'ecbb_resolve_cost_currency', 'ecbb_format_cost_display',
						'ecbb_layout_cost_label',
					],
					'ECBB_Cost_Formatter'
				),
				$bind(
					[
						'ecbb_part_detail_text', 'ecbb_event_start_date_raw', 'ecbb_event_end_date_raw',
						'ecbb_venue_id', 'ecbb_venue_name', 'ecbb_venue_address', 'ecbb_venue_name_addr',
						'ecbb_venue_name_state', 'ecbb_venue_name_city', 'ecbb_venue_display_key',
						'ecbb_venue_uses_full', 'ecbb_venue_text', 'ecbb_organizer_name', 'ecbb_organizer_full',
						'ecbb_organizer_uses_full', 'ecbb_organizer_text',
					],
					'ECBB_Event_Data'
				),
				$bind(
					[
						'ecbb_part_date_php_fmt', 'ecbb_time_fmt_lower', 'ecbb_time_lower_am',
						'ecbb_build_day_time_parts',
					],
					'ECBB_Date_Formatter'
				),
				$bind(
					[
						'ecbb_render_venue', 'ecbb_render_organizer', 'ecbb_render_featured_img',
						'ecbb_render_part_date', 'ecbb_render_part_event_date', 'ecbb_render_part_event_time',
						'ecbb_render_part_event_day', 'ecbb_render_part_detail', 'ecbb_render_part_event_cost',
						'ecbb_render_part_event_tickets', 'ecbb_render_part_event_rsvp', 'ecbb_render_part_read_more',
						'ecbb_render_part_ext',
					],
					'ECBB_Part_Renderer'
				),
				$bind(
					[
						'ecbb_event_start_timestamp', 'ecbb_list1_date_column', 'ecbb_list2_date_badge',
						'ecbb_grid_date_range_text', 'ecbb_render_grid_date_flow', 'ecbb_render_style2_category',
						'ecbb_render_layout_read_more', 'ecbb_render_layout_read_more_shell',
						'ecbb_render_layout_parts_sequence', 'ecbb_render_meta_li', 'ecbb_render_meta_lists',
						'ecbb_flush_meta_rows', 'ecbb_shell_featured_image', 'ecbb_shell_category_badge',
						'ecbb_event_thumbnail_id', 'ecbb_event_category_terms', 'ecbb_description_plain_text',
						'ecbb_grid_description_html', 'ecbb_grid_description_word_limit', 'ecbb_layout_surface_class',
						'ecbb_shell_skip_part', 'ecbb_is_layout_meta_row', 'ecbb_meta_icon', 'ecbb_meta_icon_for_part',
						'ecbb_meta_list_icon_part_slugs', 'ecbb_part_shows_style2_meta_icon',
						'ecbb_part_renders_meta_list_icon',
					],
					'ECBB_Layout_Shell'
				),
				$bind(
					[
						'ecbb_read_more_merge_link_classes', 'ecbb_part_dom_id', 'ecbb_part_dom_id_attr',
						'ecbb_part_classes', 'ecbb_part_index_class', 'ecbb_part_wrap_attrs', 'ecbb_part_scope_selector',
						'ecbb_hover_has_custom_styles', 'ecbb_hover_anim_css', 'ecbb_hover_inline_vars',
						'ecbb_hover_state_selectors', 'ecbb_hover_style_active', 'ecbb_title_link_active',
						'ecbb_btn_style_active', 'ecbb_layout_read_more_btn_class', 'ecbb_image_size_opts',
						'ecbb_sanitize_image_size', 'ecbb_image_align_opts', 'ecbb_align_to_position',
						'ecbb_image_dual_layer', 'ecbb_action_link_html', 'ecbb_terms_html',
					],
					'ECBB_Part_Chrome'
				)
			);
			return self::$delegates;
		}

		public static function __callStatic( $name, $args ) {
			$map = self::delegates();
			if ( isset( $map[ $name ] ) ) {
				return forward_static_call_array( [ $map[ $name ], $name ], $args );
			}
			throw new BadMethodCallException( 'ECBB_Markup::' . $name . ' is not defined.' );
		}

		/** Shared bool coercion for generic values and Bricks checkbox controls. */
		private static function ecbb_to_bool( $value, $default = false, $mode = 'generic' ) {
			if ( $mode === 'checkbox' ) {
				if ( $value === false || $value === 0 || $value === '0' || $value === 'no' || $value === 'off' ) {
					return false;
				}
				if ( $value === true || $value === 1 || $value === '1' || $value === 'yes' || $value === 'on' ) {
					return true;
				}
				if ( is_array( $value ) && $value === [] ) {
					return false;
				}
				if ( $value === null || $value === '' ) {
					return false;
				}
				if ( is_string( $value ) ) {
					$s = strtolower( sanitize_text_field( $value ) );
					if ( in_array( $s, [ 'no', 'off', 'false', '0', 'hide', 'hidden' ], true ) ) {
						return false;
					}
					if ( in_array( $s, [ 'yes', 'true', '1', 'show', 'on' ], true ) ) {
						return true;
					}
				}
				return (bool) $value;
			}

			if ( $value === null ) {
				return $default;
			}
			if ( is_bool( $value ) ) {
				return $value;
			}
			if ( is_numeric( $value ) ) {
				return (int) $value === 1;
			}
			if ( is_string( $value ) ) {
				$value = strtolower( trim( $value ) );
				if ( $value === '' ) {
					return false;
				}
				if ( in_array( $value, [ '1', 'true', 'yes', 'on' ], true ) ) {
					return true;
				}
				if ( in_array( $value, [ '0', 'false', 'no', 'off' ], true ) ) {
					return false;
				}
			}
			return (bool) $value;
		}

		/** True for 1, yes, on, true (string or scalar). */
		public static function ecbb_is_truthy( $value, $default = false ) {
			return self::ecbb_to_bool( $value, $default, 'generic' );
		}

		/** Bricks color control value → CSS color string, or empty. */
		public static function ecbb_norm_color( $value ) {
			if ( $value === null || $value === false ) {
				return '';
			}
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			$color = '';
			if ( is_array( $value ) ) {
				if (
					class_exists( '\Bricks\Assets' ) && method_exists( '\Bricks\Assets', 'generate_css_color' )
					&& ( isset( $value['id'] ) || isset( $value['raw'] ) || isset( $value['rgb'] ) || isset( $value['hex'] ) || isset( $value['rgba'] ) )
				) {
					$gen = \Bricks\Assets::generate_css_color( $value );
					if ( is_string( $gen ) && trim( $gen ) !== '' ) {
						$color = trim( $gen );
					}
				}
				if ( $color === '' && isset( $value['rgb'] ) && is_array( $value['rgb'] ) ) {
					$r = isset( $value['rgb']['r'] ) ? (int) $value['rgb']['r'] : 0;
					$g = isset( $value['rgb']['g'] ) ? (int) $value['rgb']['g'] : 0;
					$b = isset( $value['rgb']['b'] ) ? (int) $value['rgb']['b'] : 0;
					$a = isset( $value['rgb']['a'] ) ? (float) $value['rgb']['a'] : 1.0;
					$color = 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
				}
				if ( $color === '' ) {
					$tmp   = $value['raw'] ?? $value['rgba'] ?? $value['hex'] ?? $value['value'] ?? '';
					$color = is_string( $tmp ) ? trim( $tmp ) : '';
				}
			} elseif ( is_string( $value ) ) {
				$color = trim( $value );
			} else {
				return '';
			}
			if ( $color === '' ) {
				return '';
			}
			if ( isset( $color[0] ) && ( $color[0] === '{' || $color[0] === '[' ) ) {
				$decoded = json_decode( $color, true );
				if ( is_array( $decoded ) ) {
					return self::ecbb_norm_color( $decoded );
				}
			}
			$color = preg_replace( '/\s*!important\s*$/i', '', $color );
			$color = rtrim( trim( $color ), ';' );
			if ( preg_match( '/^var\\(--[a-zA-Z0-9\\-_]+(\\s*,\\s*[^\\)]+)?\\)$/', $color ) ) {
				return $color;
			}
			$lower = strtolower( $color );
			if ( in_array( $lower, [ 'transparent', 'currentcolor', 'inherit', 'initial', 'unset' ], true ) ) {
				return $color;
			}
			if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
				return $color;
			}
			if ( preg_match( '/^rgba?\\(([^\\)]+)\\)$/', $color ) ) {
				return $color;
			}
			if ( preg_match( '/^hsla?\\(([^\\)]+)\\)$/', $color ) ) {
				return $color;
			}
			return '';
		}

		/** Visible hover paint only (drops transparent / inherit / zero-alpha). */
		public static function ecbb_norm_hover_paint_color( $value ) {
			$color = self::ecbb_norm_color( $value );
			if ( $color === '' ) {
				return '';
			}
			$lower = strtolower( trim( $color ) );
			if ( in_array( $lower, [ 'transparent', 'currentcolor', 'inherit', 'initial', 'unset' ], true ) ) {
				return '';
			}
			if ( preg_match( '/^rgba?\(([^)]+)\)$/i', $color, $m ) ) {
				$parts = array_map( 'trim', explode( ',', $m[1] ) );
				if ( count( $parts ) >= 4 && (float) $parts[3] <= 0 ) {
					return '';
				}
			}
			if ( preg_match( '/^hsla?\(([^)]+)\)$/i', $color, $m ) ) {
				$parts = array_map( 'trim', explode( ',', $m[1] ) );
				if ( count( $parts ) >= 4 && (float) $parts[3] <= 0 ) {
					return '';
				}
			}
			return $color;
		}

		/** Set active widget settings (pass array) or read current render context. */
		public static function ecbb_active_widget_settings( $settings = null ) {
			static $active = [];
			if ( is_array( $settings ) ) {
				$active = $settings;
			}
			return $active;
		}

		/** Bricks checkbox / show-hide saved value → bool. */
		public static function ecbb_parse_bricks_checkbox( $value ) {
			return self::ecbb_to_bool( $value, false, 'checkbox' );
		}
	}
}
