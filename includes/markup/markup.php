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
						'ecbb_sanitize_layout_template',
						'ecbb_parts_clean', 'ecbb_parts_preserve_bricks_rows',
						'ecbb_upgrade_layout_parts', 'ecbb_parts_is_empty', 'ecbb_parts_assign_ids',
						'ecbb_hover_interactive_types', 'ecbb_hover_part_types',
						'ecbb_hover_on_values', 'ecbb_norm_settings_hover', 'ecbb_resolve_parts',
						'ecbb_migrate_cost_currency', 'ecbb_layout_settings', 'ecbb_norm_layout_shell_settings',
						'ecbb_shell_style_root_classes', 'ecbb_show_event_image',
						'ecbb_show_shell_category_badge', 'ecbb_show_style2_date_badge', 'ecbb_show_list1_date_column',
					],
					'ECBB_Settings_Normalizer'
				),
				$bind(
					[
						'ecbb_cost_currency_opts',
					],
					'ECBB_Cost_Formatter'
				),
				$bind(
					[
						'ecbb_event_start_date_raw', 'ecbb_event_end_date_raw',
					],
					'ECBB_Event_Data'
				),
				$bind(
					[
						'ecbb_render_featured_img',
						'ecbb_render_part_ext',
					],
					'ECBB_Part_Renderer'
				),
				$bind(
					[
						'ecbb_list1_date_column', 'ecbb_list2_date_badge',
						'ecbb_render_layout_parts_sequence', 'ecbb_render_meta_li',
						'ecbb_shell_featured_image', 'ecbb_shell_category_badge',
						'ecbb_event_thumbnail_id', 'ecbb_event_category_terms', 'ecbb_description_plain_text',
						'ecbb_grid_description_html', 'ecbb_layout_surface_class', 'ecbb_part_shows_style2_meta_icon',
						'ecbb_part_renders_meta_list_icon', 'ecbb_part_uses_composite_inline_meta_icons',
					],
					'ECBB_Layout_Shell'
				),
				$bind(
					[
						'ecbb_part_classes', 'ecbb_part_wrap_attrs', 'ecbb_part_scope_selector',
						'ecbb_hover_anim_css', 'ecbb_hover_style_active', 'ecbb_title_link_active',
						'ecbb_btn_style_active', 'ecbb_image_size_opts', 'ecbb_sanitize_image_size', 'ecbb_terms_html',
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
