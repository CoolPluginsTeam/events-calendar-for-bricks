<?php
/**
 * ECBB_Shell_Css_Generator service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Shell_Css_Generator', false ) ) {

	final class ECBB_Shell_Css_Generator {

		private static function ecbb_layout_shell_date_column_rules( array $settings, $root, callable $color_fn ) {
			$rules = [];
			if ( ! class_exists( 'ECBB_Markup', false ) || ! \ECBB_Markup::ecbb_show_list1_date_column( $settings ) ) {
				return $rules;
			}

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$date_bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings['ecbb_list1_content_background'] ?? '', $device );
				$date_bg     = $date_bg_raw !== '' && $date_bg_raw !== null ? $color_fn( $date_bg_raw ) : '';
				if ( $date_bg !== '' ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule(
						$mq,
						$root . ' .event-list-card__date-inner{background-color:' . $date_bg . ' !important;}'
					);
				}

				$divider_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings['ecbb_list1_date_column_border'] ?? '', $device );
				$divider     = $divider_raw !== '' && $divider_raw !== null ? $color_fn( $divider_raw ) : '';
				if ( $divider !== '' ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule(
						$mq,
						$root . ' .event-list-card__date{border-right-color:' . $divider . ' !important;}'
					);
				}

				$date_pad_raw = ECBB_Parts_Css_Generator::ecbb_responsive_spacing( $settings, 'ecbb_list1_date_inner_padding', $device );
				$date_pad     = ! empty( $date_pad_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $date_pad_raw ) : '';
				if ( $date_pad !== '' ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule(
						$mq,
						$root . ' .event-list-card__date-inner{padding:' . $date_pad . ' !important;}'
					);
				}

				$date_margin_raw = ECBB_Parts_Css_Generator::ecbb_responsive_spacing( $settings, 'ecbb_list1_date_inner_margin', $device );
				$date_margin     = ! empty( $date_margin_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $date_margin_raw ) : '';
				if ( $date_margin !== '' ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule(
						$mq,
						$root . ' .event-list-card__date-inner{margin:' . $date_margin . ' !important;}'
					);
				}

				$date_border = ECBB_Css_Value_Sanitizer::ecbb_border_decls( $settings['ecbb_list1_date_inner_border'] ?? [] );
				if ( ! empty( $date_border ) ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule(
						$mq,
						$root . ' .event-list-card__date-inner{' . implode( ';', ECBB_Parts_Css_Generator::ecbb_decls_important( $date_border ) ) . '}'
					);
				}
			}

			return array_merge(
				$rules,
				self::ecbb_layout_shell_typography_rules(
					$settings,
					'ecbb_list1_date_typography',
					[
						$root . ' .event-list-card__date-inner',
						$root . ' .event-list-card__date .event-list-card__day',
						$root . ' .event-list-card__date .event-list-card__month',
					],
					$color_fn
				)
			);
		}

		private static function ecbb_layout_shell_category_badge_hover_rules( array $settings, array $layout, $root, $show_category_shell, callable $color_fn ) {
			$rules = [];
			if ( ! $show_category_shell ) {
				return $rules;
			}

			$cat_hover_color_key = $layout['template'] === 'grid'
				? 'ecbb_shell_category_hover_color_grid'
				: 'ecbb_shell_category_hover_color';
			$cat_hover_bg_key = $layout['template'] === 'grid'
				? 'ecbb_shell_category_hover_background_grid'
				: 'ecbb_shell_category_hover_background';
			$cat_hover_td_key = $layout['template'] === 'grid'
				? 'ecbb_shell_category_hover_text_decoration_grid'
				: 'ecbb_shell_category_hover_text_decoration';
			$badge_hover_sel = $root . ' a.event-badge--blue:hover,' . $root . ' .event-badge--blue:hover';

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$hover_decls = [];

				$hover_color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings[ $cat_hover_color_key ] ?? '', $device );
				$hover_color     = $hover_color_raw !== '' && $hover_color_raw !== null ? $color_fn( $hover_color_raw ) : '';
				if ( $hover_color !== '' ) {
					$hover_decls[] = 'color:' . $hover_color . ' !important';
				}

				$hover_bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings[ $cat_hover_bg_key ] ?? '', $device );
				$hover_bg     = $hover_bg_raw !== '' && $hover_bg_raw !== null ? $color_fn( $hover_bg_raw ) : '';
				if ( $hover_bg !== '' ) {
					$hover_decls[] = 'background-color:' . $hover_bg . ' !important';
				}

				$hover_td = (string) ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings[ $cat_hover_td_key ] ?? '', $device );
				if ( in_array( $hover_td, [ 'none', 'underline', 'overline', 'line-through' ], true ) ) {
					$hover_decls[] = 'text-decoration:' . $hover_td . ' !important';
				}

				if ( $hover_decls !== [] ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule( $mq, $badge_hover_sel . '{' . implode( ';', $hover_decls ) . '}' );
				}
			}

			return $rules;
		}

		public static function ecbb_layout_shell_css( array $settings, $scope_class, callable $color_fn ) {
			$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
			if ( $scope_class === '' ) {
				return '';
			}

			$show_category_shell = class_exists( 'ECBB_Markup', false )
				&& \ECBB_Markup::ecbb_show_shell_category_badge( $settings );
			$show_date_shell     = class_exists( 'ECBB_Markup', false )
				&& \ECBB_Markup::ecbb_show_style2_date_badge( $settings );
			$layout = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_sanitize_layout_template( $settings )
				: [ 'template' => '', 'item_chrome' => '' ];

			$var_keys = [
				'ecbb_card_background' => '--ecbb-card-bg',
			];
			if ( class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_show_list1_date_column( $settings ) ) {
				$var_keys['ecbb_list1_content_background'] = '--ecbb-list1-date-bg';
			}
			if ( $show_category_shell ) {
				if ( $layout['template'] === 'grid' ) {
					$var_keys['ecbb_shell_category_background_grid'] = '--ecbb-shell-cat-bg';
				} else {
					$var_keys['ecbb_shell_category_background'] = '--ecbb-shell-cat-bg';
				}
			}
			if ( $show_date_shell ) {
				$var_keys['ecbb_shell_date_background'] = '--ecbb-shell-date-bg';
			}

			$root       = '.' . $scope_class;
			$body_sel   = $root . ' .event-list-card__body,' . $root . ' .ecbb-event-card__content,' . $root . ' .event-grid-card__content';
			$rules      = self::ecbb_layout_shell_root_color_rules( $settings, $var_keys, $root, $body_sel, $color_fn );
			$rules      = array_merge( $rules, self::ecbb_layout_shell_date_column_rules( $settings, $root, $color_fn ) );
			$rules      = array_merge(
				$rules,
				self::ecbb_layout_shell_category_badge_hover_rules( $settings, $layout, $root, $show_category_shell, $color_fn )
			);

			return implode( "\n", $rules );
		}

		private static function ecbb_layout_shell_typography_rules( array $settings, $setting_key, array $selectors, callable $color_fn ) {
			$typo = $settings[ $setting_key ] ?? null;
			if ( ! is_array( $typo ) || $typo === [] || $selectors === [] ) {
				return [];
			}

			$selector = implode( ',', $selectors );
			$rules    = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$decls = ECBB_Parts_Css_Generator::ecbb_type_decls( $typo, $device );
				$color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $typo['color'] ?? '', $device );
				if ( $color_raw !== '' && $color_raw !== null ) {
					$color = $color_fn( $color_raw );
					if ( $color !== '' ) {
						$decls[] = 'color:' . $color;
					}
				}
				$decls = ECBB_Parts_Css_Generator::ecbb_decls_important( $decls );
				if ( $decls === [] ) {
					continue;
				}
				$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule( $mq, $selector . '{' . implode( ';', $decls ) . '}' );
			}

			return $rules;
		}

		private static function ecbb_layout_shell_root_color_rules( array $settings, array $var_keys, $root, $body_sel, callable $color_fn ) {
			$rules      = [];
			$var_values = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$decls = [];
				foreach ( $var_keys as $setting_key => $css_var ) {
					$raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings[ $setting_key ] ?? '', $device );
					if ( $raw === '' || $raw === null ) {
						continue;
					}
					$color = $color_fn( $raw );
					if ( $color === '' ) {
						continue;
					}
					$var_values[ $device ][ $css_var ] = $color;
				}
				if ( ! empty( $var_values[ $device ] ) ) {
					foreach ( $var_values[ $device ] as $css_var => $color ) {
						$decls[] = $css_var . ':' . $color;
					}
				}

				$fg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $settings['ecbb_card_text_color'] ?? '', $device );
				$fg     = $fg_raw !== '' && $fg_raw !== null ? $color_fn( $fg_raw ) : '';
				if ( $fg !== '' ) {
					$decls[] = '--ecbb-card-fg:' . $fg;
				}

				if ( $decls !== [] ) {
					$rule    = $root . '{' . implode( ';', $decls ) . '}';
					$rules[] = $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
				}

				if ( $fg !== '' ) {
					$rules[] = ECBB_Parts_Css_Generator::ecbb_mq_css_rule( $mq, $body_sel . '{color:' . $fg . ' !important;}' );
				}
			}

			return $rules;
		}

	}
}
