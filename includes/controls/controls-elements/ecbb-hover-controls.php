<?php
/**
 * ECBB_Hover_Controls service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Hover_Controls', false ) ) {

	final class ECBB_Hover_Controls {

			public static function ecbb_hover_animation_options( $include_default = false, $include_none = true ) {
			$options = [];
			if ( $include_default ) {
				$options[''] = esc_html__( 'Default', 'events-calendar-for-bricks' );
			}
			if ( $include_none ) {
				$options['none'] = esc_html__( 'None', 'events-calendar-for-bricks' );
			}
			return array_merge(
				$options,
				[
					'fade_in_up'    => esc_html__( 'Fade in up', 'events-calendar-for-bricks' ),
					'fade_in_right' => esc_html__( 'Fade in right', 'events-calendar-for-bricks' ),
					'fade_in_down'  => esc_html__( 'Fade in down', 'events-calendar-for-bricks' ),
					'fade_in_left'  => esc_html__( 'Fade in left', 'events-calendar-for-bricks' ),
					'zoom_in'       => esc_html__( 'Zoom in', 'events-calendar-for-bricks' ),
					'zoom_out'      => esc_html__( 'Zoom out', 'events-calendar-for-bricks' ),
				]
			);
		}

			public static function ecbb_req_list1_date_column_style() {
			return [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			[ 'list1_show_date_column', '!=', true ],
		];
	}

			public static function ecbb_req_hover_on() {
			return [
				'ecbb_use_hover',
				'=',
				class_exists( 'ECBB_Settings_Normalizer', false )
					? \ECBB_Settings_Normalizer::ecbb_hover_on_values()
					: [ 'yes', true, 1, '1' ],
			];
		}

			public static function ecbb_hover_part_types() {
			return class_exists( 'ECBB_Settings_Normalizer', false )
				? \ECBB_Settings_Normalizer::ecbb_hover_part_types()
				: [];
		}

			public static function ecbb_hover_field_keys() {
			return [
				'ecbb_use_hover',
				'ecbb_sep_hover',
				'ecbb_hover_color',
				'ecbb_hover_background',
				'ecbb_hover_text_decoration',
				'ecbb_hover_animation',
			];
		}

			public static function ecbb_req_style2_divider_style() {
			return [
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			];
		}

			public static function ecbb_style2_meta_icon_ui_parts() {
			if ( class_exists( 'ECBB_Styles', false ) ) {
				return array_values(
					array_unique(
						array_merge(
							[ 'venue', 'date', 'event_cost' ],
							\ECBB_Styles::ecbb_meta_combo_slugs_style2()
						)
					)
				);
			}

			return [ 'venue', 'date', 'event_cost', 'venue_time_cost' ];
		}

		public static function ecbb_req_featured_image_style() {
		return [
			[ 'show_event_image', '!=', true ],
		];
	}

		public static function ecbb_req_shell_category_style_grid() {
		return [
			[ 'show_event_image', '!=', true ],
				[ 'layout_template', '=', 'grid' ],
				[ 'grid_show_category_badge', '!=', true ],
			];
		}

			public static function ecbb_btn_border_keys() {
			return [
				'btn_sep_border',
				'btn_border_type',
				'btn_border_width',
				'btn_border_color',
				'btn_padding',
				'btn_border_radius',
			];
		}

		public static function ecbb_req_featured_image_vignette() {
		return [
			[ 'show_event_image', '!=', true ],
				[ 'ecbb_featured_image_vignette', '!=', '' ],
				[ 'ecbb_featured_image_vignette', '!=', 'none' ],
			];
		}

		public static function ecbb_req_shell_category_style_list1() {
		return [
			[ 'show_event_image', '!=', true ],
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '!=', 'style-2' ],
				[ 'list1_show_category_badge', '!=', true ],
			];
		}

			public static function ecbb_hover_animation_slugs() {
			return [
				'fade_in_up',
				'fade_in_right',
				'fade_in_down',
				'fade_in_left',
				'zoom_in',
				'zoom_out',
			];
		}

		public static function ecbb_req_shell_date_badge_style() {
		return [
			[ 'show_event_image', '!=', true ],
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			[ 'style2_show_date_badge', '!=', true ],
		];
	}

			public static function ecbb_req_hover_controls() {
			return [
				self::ecbb_req_show_hover_toggle(),
				self::ecbb_req_hover_on(),
			];
		}

			public static function ecbb_sanitize_hover_animation_slug( $value ) {
			$slug = sanitize_key( (string) $value );
			if ( $slug === '' || $slug === 'default' ) {
				return '';
			}
			if ( $slug === 'none' || in_array( $slug, self::ecbb_hover_animation_slugs(), true ) ) {
				return $slug;
			}
			return '';
		}

			public static function ecbb_req_hover_interactive() {
			return [
				[ 'part', '=', self::ecbb_hover_interactive_types() ],
				self::ecbb_req_hover_on(),
			];
		}

			public static function ecbb_hover_interactive_types() {
			return class_exists( 'ECBB_Settings_Normalizer', false )
				? \ECBB_Settings_Normalizer::ecbb_hover_interactive_types()
				: [];
		}

			public static function ecbb_btn_part_types() {
			return [ 'read_more', 'event_tickets', 'event_rsvp' ];
		}

			public static function ecbb_req_show_hover_toggle() {
			return [ 'part', '=', self::ecbb_hover_part_types() ];
		}

			public static function ecbb_req_btn_styled() {
			return [
				[ 'part', '=', self::ecbb_btn_part_types() ],
				[ 'btn_style', '=', true ],
			];
		}

	}
}
