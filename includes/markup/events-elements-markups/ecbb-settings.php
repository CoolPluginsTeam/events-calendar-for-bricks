<?php
/**
 * Widget settings normalization and parts resolution.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Settings_Normalizer', false ) ) {

	final class ECBB_Settings_Normalizer {

		/** list|grid; defaults to list. */
		public static function ecbb_sanitize_template( $template ) {
			$template = is_string( $template ) ? trim( $template ) : 'list';
			return in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';
		}

		/** style-1|style-2; defaults to style-1. */
		public static function ecbb_sanitize_list_style( $value ) {
			$v = is_string( $value ) ? trim( $value ) : '';
			return in_array( $v, [ 'style-1', 'style-2' ], true ) ? $v : 'style-1';
		}

		/** Normalized layout_template + list_item_style pair. */
		public static function ecbb_sanitize_layout_template( array $settings ) {
			return [
				'template'    => self::ecbb_sanitize_template( $settings['layout_template'] ?? 'list' ),
				'item_chrome' => self::ecbb_sanitize_list_style( $settings['list_item_style'] ?? 'style-1' ),
			];
		}

		/** Drop empty rows and legacy link/prefix fields. */
		public static function ecbb_parts_clean( array $parts ) {
			$out = [];
			foreach ( $parts as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['part'] ) || trim( (string) $row['part'] ) === '' ) {
					continue;
				}
				unset( $row['venue_link'], $row['organizer_link'], $row['cost_prefix'], $row['cost_suffix'] );
				$out[] = $row;
			}
			return $out;
		}

		/** Part slugs from cleaned repeater rows. */
		public static function ecbb_parts_slugs( array $parts ) {
			$out = [];
			foreach ( self::ecbb_parts_clean( $parts ) as $row ) {
				$out[] = isset( $row['part'] ) ? (string) $row['part'] : '';
			}
			return $out;
		}

		/** Copy Bricks row ids and style fields from saved rows onto defaults. */
		public static function ecbb_parts_preserve_bricks_rows( array $saved, array $defaults ) {
			$style_keys = [
				'id', 'ecbb_typography', 'ecbb_text_align', 'ecbb_background', 'ecbb_background_inner',
				'ecbb_meta_icon_color', 'ecbb_meta_icon_background',
				'ecbb_margin', 'ecbb_padding', 'ecbb_use_hover', 'ecbb_hover_color', 'ecbb_hover_background',
				'ecbb_hover_text_decoration', 'ecbb_hover_animation', 'btn_style', 'btn_bg', 'btn_text_color',
				'btn_border_type', 'btn_border_width', 'btn_border_color', 'btn_padding', 'btn_border_radius',
			];
			foreach ( $defaults as $i => $row ) {
				if ( ! is_array( $row ) || ! isset( $saved[ $i ] ) || ! is_array( $saved[ $i ] ) ) {
					continue;
				}
				$saved_row = $saved[ $i ];
				if ( (string) ( $saved_row['part'] ?? '' ) !== (string) ( $row['part'] ?? '' ) ) {
					continue;
				}
				foreach ( $style_keys as $key ) {
					if ( array_key_exists( $key, $saved_row ) ) {
						$defaults[ $i ][ $key ] = $saved_row[ $key ];
					}
				}
			}
			return $defaults;
		}

		/** Replace empty or legacy factory stacks with layout defaults; null keeps saved rows. */
		public static function ecbb_upgrade_layout_parts( array $parts, $layout, callable $default_fn ) {
			if ( self::ecbb_parts_is_empty( $parts ) ) {
				return $default_fn();
			}

			$slugs  = self::ecbb_parts_slugs( $parts );
			$layout = (string) $layout;

			$legacy_by_layout = [
				'style1' => [
					[ 'title', 'description', 'date', 'venue', 'event_cost', 'read_more' ],
					[ 'title', 'description', 'date', 'venue', 'read_more' ],
				],
				'style2' => [
					[ 'categories', 'title', 'description', 'venue', 'date', 'event_cost', 'read_more' ],
					[ 'categories', 'title', 'date', 'venue', 'description', 'read_more' ],
				],
			];

			if ( isset( $legacy_by_layout[ $layout ] ) ) {
				foreach ( $legacy_by_layout[ $layout ] as $signature ) {
					if ( $slugs === $signature ) {
						return self::ecbb_parts_preserve_bricks_rows( $parts, $default_fn() );
					}
				}
			}

			$factory = [ 'categories', 'title', 'date', 'venue', 'description', 'read_more' ];
			if ( $slugs === $factory ) {
				return self::ecbb_parts_preserve_bricks_rows( $parts, $default_fn() );
			}

			return null;
		}

		public static function ecbb_parts_is_empty( array $parts ) {
			return self::ecbb_parts_clean( $parts ) === [];
		}

		/** Assign Bricks repeater row ids when missing. */
		public static function ecbb_parts_assign_ids( array $rows ) {
			foreach ( $rows as $index => $row ) {
				if ( ! is_array( $row ) || ! empty( $row['id'] ) ) {
					continue;
				}
				if ( class_exists( '\Bricks\Helpers' ) && method_exists( '\Bricks\Helpers', 'generate_random_id' ) ) {
					$rows[ $index ]['id'] = \Bricks\Helpers::generate_random_id( false );
				} else {
					$rows[ $index ]['id'] = 'ecbb-part-' . absint( $index );
				}
			}
			return $rows;
		}

		public static function ecbb_hover_interactive_types() {
			return [ 'title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		public static function ecbb_hover_part_types() {
			return array_merge( self::ecbb_hover_interactive_types(), [ 'image' ] );
		}

		public static function ecbb_part_has_hover( $part ) {
			return in_array( (string) $part, self::ecbb_hover_part_types(), true );
		}

		public static function ecbb_hover_on_values() {
			return [ 'yes', true, 1, '1' ];
		}

		/** True when hover is enabled (empty/null counts as on). */
		public static function ecbb_hover_is_on( $value ) {
			if ( in_array( $value, self::ecbb_hover_on_values(), true ) ) {
				return true;
			}
			if ( $value === false || $value === 0 || $value === '0' || $value === 'no' ) {
				return false;
			}
			if ( $value === null || $value === '' ) {
				return true;
			}
			return ECBB_Markup::ecbb_is_truthy( $value, true );
		}

		/** Normalize ecbb_use_hover on one repeater row. */
		public static function ecbb_norm_hover_row( array $row ) {
			if ( ! array_key_exists( 'ecbb_use_hover', $row ) ) {
				return $row;
			}
			if ( $row['ecbb_use_hover'] === null || $row['ecbb_use_hover'] === '' ) {
				$row['ecbb_use_hover'] = 'yes';
				return $row;
			}
			if ( ECBB_Part_Chrome::ecbb_hover_has_custom_styles( $row ) ) {
				$row['ecbb_use_hover'] = 'yes';
				return $row;
			}
			$row['ecbb_use_hover'] = self::ecbb_hover_is_on( $row['ecbb_use_hover'] ) ? 'yes' : 'no';
			return $row;
		}

		public static function ecbb_norm_parts_hover( array $rows ) {
			foreach ( $rows as $index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$part = isset( $row['part'] ) ? (string) $row['part'] : '';
				if ( $part === '' || ! self::ecbb_part_has_hover( $part ) ) {
					continue;
				}
				$rows[ $index ] = self::ecbb_norm_hover_row( $row );
			}
			return $rows;
		}

		public static function ecbb_norm_settings_hover( array $settings ) {
			foreach ( [ 'parts_style1', 'parts_style2', 'parts_grid', 'parts' ] as $key ) {
				if ( empty( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
					continue;
				}
				$settings[ $key ] = self::ecbb_norm_parts_hover( $settings[ $key ] );
			}
			return $settings;
		}

		/** Active parts stack for layout (grid / style-2 / style-1, then legacy parts). */
		public static function ecbb_resolve_parts( array $settings, $template, $item_chrome ) {
			$prepare = static function ( $parts ) {
				if ( ! is_array( $parts ) ) {
					return [];
				}
				$parts = self::ecbb_parts_assign_ids( $parts );
				return self::ecbb_norm_parts_hover( $parts );
			};

			$template    = self::ecbb_sanitize_template( is_string( $template ) ? trim( $template ) : '' );
			$item_chrome = self::ecbb_sanitize_list_style( $item_chrome );

			$non_empty = static function ( $key ) use ( $settings ) {
				$v = $settings[ $key ] ?? null;
				if ( ! is_array( $v ) || $v === [] || self::ecbb_parts_is_empty( $v ) ) {
					return null;
				}
				return $v;
			};

			if ( $template === 'grid' ) {
				$g = $non_empty( 'parts_grid' ) ?? $non_empty( 'parts' );
				return $g ? $prepare( $g ) : [];
			}
			if ( $item_chrome === 'style-2' ) {
				$s2 = $non_empty( 'parts_style2' ) ?? $non_empty( 'parts' );
				return $s2 ? $prepare( $s2 ) : [];
			}
			$s1 = $non_empty( 'parts_style1' ) ?? $non_empty( 'parts' );
			return $s1 ? $prepare( $s1 ) : [];
		}

		/** Copy widget-level event_cost_currency onto event_cost rows. */
		public static function ecbb_migrate_cost_currency( array $settings ) {
			if ( ! isset( $settings['event_cost_currency'] ) ) {
				return $settings;
			}
			$currency = ECBB_Cost_Formatter::ecbb_sanitize_cost_currency( $settings['event_cost_currency'] );
			foreach ( [ 'parts_style1', 'parts_style2', 'parts_grid', 'parts' ] as $key ) {
				if ( empty( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
					continue;
				}
				foreach ( $settings[ $key ] as $index => $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$part = (string) ( $row['part'] ?? '' );
					if ( $part !== 'event_cost' && $part !== 'venue_time_cost' ) {
						continue;
					}
					if ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) {
						$settings[ $key ][ $index ]['cost_currency'] = $currency;
					}
				}
			}
			return $settings;
		}

		/** Merge caller settings with active widget render context. */
		public static function ecbb_layout_settings( array $settings = [] ) {
			$active = ECBB_Markup::ecbb_active_widget_settings();
			if ( ! is_array( $active ) || $active === [] ) {
				return $settings;
			}
			if ( $settings === [] ) {
				return $active;
			}
			return array_replace( $active, $settings );
		}

		/** Normalize shell show/hide settings for Bricks save/render. */
		public static function ecbb_norm_layout_shell_settings( array $settings ) {
			if (
				! array_key_exists( 'list1_show_category_badge', $settings )
				&& ! array_key_exists( 'grid_show_category_badge', $settings )
				&& array_key_exists( 'shell_show_category_badge', $settings )
			) {
				$legacy_val = ECBB_Markup::ecbb_parse_bricks_checkbox( $settings['shell_show_category_badge'] ) ? 'show' : 'hide';
				$settings['list1_show_category_badge'] = $legacy_val;
				$settings['grid_show_category_badge']  = $legacy_val;
			}

			if ( array_key_exists( 'show_event_image', $settings ) ) {
				$raw = $settings['show_event_image'];
				if ( is_bool( $raw ) ) {
					$settings['show_event_image'] = $raw ? 'show' : 'hide';
				} elseif ( $raw === true || $raw === 1 || $raw === '1' || $raw === 'yes' || $raw === 'on' ) {
					$settings['show_event_image'] = 'show';
				} elseif ( $raw === false || $raw === 0 || $raw === '0' || $raw === 'no' || $raw === 'off' || $raw === '' || $raw === null ) {
					$settings['show_event_image'] = 'hide';
				}
			}

			foreach ( [ 'list1_show_category_badge', 'grid_show_category_badge', 'style2_show_date_badge' ] as $key ) {
				if ( ! array_key_exists( $key, $settings ) ) {
					continue;
				}
				$raw = $settings[ $key ];
				if ( $raw === 'show' || $raw === 'hide' ) {
					continue;
				}
				$settings[ $key ] = ECBB_Markup::ecbb_parse_bricks_checkbox( $raw ) ? 'show' : 'hide';
			}

			return $settings;
		}

		/**
		 * Root CSS classes for shell card/image hover animation presets.
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @return string[]
		 */
		public static function ecbb_shell_hover_root_classes( array $settings ) {
			if ( ! class_exists( 'ECBB_Controls', false ) ) {
				return [];
			}

			$classes = [];
			$card    = \ECBB_Controls::ecbb_sanitize_hover_animation_slug( $settings['ecbb_card_hover_animation'] ?? '' );
			if ( $card !== '' ) {
				$classes[] = 'ecbb-card-hover--' . sanitize_html_class( $card );
			}
			$image = \ECBB_Controls::ecbb_sanitize_hover_animation_slug( $settings['ecbb_image_hover_animation'] ?? '' );
			if ( $image !== '' ) {
				$classes[] = 'ecbb-img-hover--' . sanitize_html_class( $image );
			}

			return $classes;
		}

		/** True when a shell show/hide select (or legacy checkbox) is on. */
		public static function ecbb_shell_select_on( array $settings, $key, $default = 'show' ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				return $default !== 'hide';
			}
			$raw = $settings[ $key ];
			if ( $raw === 'hide' || $raw === 'no' ) {
				return false;
			}
			if ( $raw === 'show' || $raw === 'yes' ) {
				return true;
			}
			return ECBB_Markup::ecbb_parse_bricks_checkbox( $raw );
		}

		public static function ecbb_show_event_image( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			if ( ! array_key_exists( 'show_event_image', $settings ) ) {
				return true;
			}
			$raw = $settings['show_event_image'];
			if ( $raw === 'hide' || $raw === 'no' ) {
				return false;
			}
			if ( $raw === 'show' || $raw === 'yes' ) {
				return true;
			}
			return ECBB_Markup::ecbb_parse_bricks_checkbox( $raw );
		}

		public static function ecbb_show_shell_category_badge( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			if ( ! self::ecbb_show_event_image( $settings ) ) {
				return false;
			}
			$layout = self::ecbb_sanitize_layout_template( $settings );
			if ( $layout['template'] === 'grid' ) {
				return self::ecbb_shell_select_on( $settings, 'grid_show_category_badge', 'show' );
			}
			if ( $layout['template'] === 'list' && $layout['item_chrome'] === 'style-1' ) {
				return self::ecbb_shell_select_on( $settings, 'list1_show_category_badge', 'show' );
			}
			return false;
		}

		public static function ecbb_show_style2_date_badge( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			if ( ! self::ecbb_show_event_image( $settings ) ) {
				return false;
			}
			$layout = self::ecbb_sanitize_layout_template( $settings );
			if ( $layout['template'] !== 'list' || $layout['item_chrome'] !== 'style-2' ) {
				return false;
			}
			return self::ecbb_shell_select_on( $settings, 'style2_show_date_badge', 'show' );
		}

		public static function ecbb_style2_date_badge_order( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			return self::ecbb_date_column_order_value( $settings['style2_date_badge_order'] ?? '', 'month_day' );
		}

		public static function ecbb_list1_date_column_order( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			return self::ecbb_date_column_order_value( $settings['list1_date_column_order'] ?? '', 'day_month' );
		}

		/** month_day|day_month with fallback. */
		private static function ecbb_date_column_order_value( $raw, $default = 'month_day' ) {
			$order    = is_string( $raw ) ? $raw : '';
			$fallback = in_array( $default, [ 'month_day', 'day_month' ], true ) ? $default : 'month_day';
			return in_array( $order, [ 'month_day', 'day_month' ], true ) ? $order : $fallback;
		}
	}
}
