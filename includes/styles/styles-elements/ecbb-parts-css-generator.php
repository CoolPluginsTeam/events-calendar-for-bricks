<?php
/**
 * ECBB_Parts_Css_Generator service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Parts_Css_Generator', false ) ) {

	final class ECBB_Parts_Css_Generator {

		private static function ecbb_btn_radius_decl( array $item, $device ) {
		$radius_raw = self::ecbb_responsive_spacing( $item, 'btn_border_radius', $device );
			$radius_css = ! empty( $radius_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_radius_css( $radius_raw ) : '';
		if ( $radius_css === '' ) {
			$radius_pick = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['btn_border_radius'] ?? '', $device );
			if ( is_array( $radius_pick ) ) {
				$radius_css = ECBB_Css_Value_Sanitizer::ecbb_radius_css( $radius_pick );
			} elseif ( is_string( $radius_pick ) && $radius_pick !== '' ) {
			$radius_css = ECBB_Css_Value_Sanitizer::ecbb_clean_css_box( $radius_pick );
		}
		}
			return $radius_css !== '' ? [ 'border-radius:' . $radius_css ] : [];
		}

		private static function ecbb_parts_hover_rules( array $ctx ) {
			$p               = $ctx['part'];
			$part_type       = $ctx['part_type'];
			$scope_sel       = $ctx['scope_sel'];
			$hover_style_on  = $ctx['hover_style_on'];
			$list_item_style = $ctx['list_item_style'];
			$color_fn        = $ctx['color_fn'];
			$style_rules     = [];
			$hover_rules     = [];
			if ( ! $hover_style_on ) {
				return [ 'style' => $style_rules, 'hover' => $hover_rules ];
			}
			$has_custom_hover = self::ecbb_part_has_custom_hover_colors( $p );
			if (
				in_array( $list_item_style, [ 'style-2', 'grid' ], true )
				&& $part_type === 'categories'
				&& ! $has_custom_hover
			) {
				$hover_rules = array_merge( $hover_rules, self::ecbb_layout_style2_category_hover_rules( $scope_sel, $p, true ) );
			}
			$hover_sel = ECBB_Selector_Factory::ecbb_hover_selectors( $scope_sel, $part_type );
			$hover = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_norm_hover_paint_color( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ) )
				: $color_fn( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ) );
			if ( $hover !== '' ) {
				$hover_rules[] = $hover_sel . '{color:' . $hover . ' !important;}';
			}
			$hover_bg = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_norm_hover_paint_color( $p['ecbb_hover_background'] ?? '' )
				: $color_fn( $p['ecbb_hover_background'] ?? '' );
		if ( $hover_bg !== '' ) {
			if ( in_array( $part_type, ECBB_Selector_Factory::ecbb_link_hover_parts(), true ) ) {
					$hover_rules[] = $hover_sel . '{background-color:' . $hover_bg . ' !important;}';
			} elseif ( $part_type === 'title' ) {
					$hover_rules[] = $scope_sel . ' .ecbb-event__link:hover,' . $scope_sel . ' .ecbb-event__title-text:hover{background-color:' . $hover_bg . ' !important;}';
		} else {
					$hover_rules[] = $scope_sel . ':hover{background-color:' . $hover_bg . ' !important;}';
		}
		}
		$hover_td = isset( $p['ecbb_hover_text_decoration'] ) ? (string) $p['ecbb_hover_text_decoration'] : '';
		if ( $hover_td !== '' && in_array( $hover_td, [ 'none', 'underline', 'overline', 'line-through' ], true ) ) {
			if ( in_array( $part_type, ECBB_Selector_Factory::ecbb_link_hover_parts(), true ) ) {
					$hover_rules[] = $hover_sel . '{text-decoration:' . $hover_td . ' !important;}';
			} else {
					$hover_rules[] = $scope_sel . ':hover,' . $scope_sel . ' a:hover,' . $scope_sel . ' .ecbb-event__term:hover{text-decoration:' . $hover_td . ' !important;}';
		}
		}
		$hover_anim = isset( $p['ecbb_hover_animation'] ) ? (string) $p['ecbb_hover_animation'] : '';
			if ( $hover_anim !== '' && $hover_anim !== 'none' && class_exists( 'ECBB_Markup', false ) ) {
			$anim_scope  = ECBB_Selector_Factory::ecbb_hover_anim_scope( $scope_sel, $part_type );
			$anim_blocks = \ECBB_Markup::ecbb_hover_anim_css( $anim_scope, $hover_anim );
			if ( ! empty( $anim_blocks['base'] ) ) {
					$style_rules[] = $anim_blocks['base'];
			}
		if ( ! empty( $anim_blocks['hover'] ) ) {
					$hover_rules[] = $anim_blocks['hover'];
				}
			}
			return [ 'style' => $style_rules, 'hover' => $hover_rules ];
		}

		private static function ecbb_part_has_custom_hover_colors( array $item ) {
			if ( class_exists( 'ECBB_Part_Chrome', false ) && ECBB_Part_Chrome::ecbb_hover_has_custom_styles( $item ) ) {
				return true;
			}

			foreach ( [ 'ecbb_hover_color', 'hover_color', 'ecbb_hover_background' ] as $key ) {
				if ( ! array_key_exists( $key, $item ) || $item[ $key ] === '' || $item[ $key ] === null ) {
					continue;
				}
				if (
					class_exists( 'ECBB_Markup', false )
					&& \ECBB_Markup::ecbb_norm_hover_paint_color( $item[ $key ] ) !== ''
				) {
					return true;
				}
			}

			return false;
		}

		private static function ecbb_parts_hover_var_rules( array $ctx ) {
			if ( ! $ctx['hover_style_on'] ) {
				return [];
			}

			$p         = $ctx['part'];
			$scope_sel = $ctx['scope_sel'];
			$color_fn  = $ctx['color_fn'];
			$rules     = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$vars      = [];
				$hover_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_hover_color'] ?? ( $p['hover_color'] ?? '' ), $device );
				$hover_fg  = class_exists( 'ECBB_Markup', false )
					? \ECBB_Markup::ecbb_norm_hover_paint_color( $hover_raw )
					: $color_fn( $hover_raw );
				if ( $hover_fg !== '' ) {
					$vars[] = '--ecbb-hover-fg:' . $hover_fg;
				}

				$hover_bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_hover_background'] ?? '', $device );
				$hover_bg     = class_exists( 'ECBB_Markup', false )
					? \ECBB_Markup::ecbb_norm_hover_paint_color( $hover_bg_raw )
					: $color_fn( $hover_bg_raw );
				if ( $hover_bg !== '' ) {
					$vars[] = '--ecbb-hover-bg:' . $hover_bg;
				}

				if ( $vars !== [] ) {
					$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{' . implode( ';', $vars ) . ';}' );
				}
			}

			return $rules;
		}

		public static function ecbb_layout_style2_category_hover_rules( $scope_sel, array $item, $hover_style_on ) {
			if ( ! $hover_style_on || $scope_sel === '' ) {
				return [];
			}
			if ( (string) ( $item['part'] ?? '' ) !== 'categories' ) {
				return [];
			}

			$chip_hover = $scope_sel . ' a.ecbb-event-card__category:hover,'
				. $scope_sel . ' > a.ecbb-event-card__category:hover';

			return [
				$chip_hover . '{color:var(--ecbb-accent,#0d55d8)!important;background-color:var(--ecbb-accent-soft,#d4e4ff)!important;}',
			];
		}

		private static function ecbb_btn_bg_decl( array $item, $device, callable $color_fn ) {
			$bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['ecbb_background'] ?? '', $device );
			if ( $bg_raw === '' || $bg_raw === null ) {
			return [];
		}
			$bg = $color_fn( $bg_raw );
			return $bg !== '' ? [ 'background-color:' . $bg . ' !important' ] : [];
		}

		public static function ecbb_parts_css( array $parts, $scope_class, callable $color_fn, $list_item_style = 'style-1' ) {
			$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
			$style_css   = [];
			$hover_css   = [];
			if ( $scope_class === '' ) {
				return [ $style_css, $hover_css ];
			}
			foreach ( $parts as $idx => $p ) {
				if ( ! is_array( $p ) ) {
					continue;
				}
				$scope_sel = class_exists( 'ECBB_Markup', false )
					? \ECBB_Markup::ecbb_part_scope_selector( $scope_class, $p, $idx )
					: '.' . $scope_class . ' .ecbb-p' . absint( $idx );
				if ( $scope_sel === '' ) {
					continue;
				}
				$hover_style_on  = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_hover_style_active( $p );
				$p               = ECBB_Part_Options::ecbb_clean_part( $p );
				$part_type       = isset( $p['part'] ) ? (string) $p['part'] : '';
				$btn_style_on    = class_exists( 'ECBB_Markup', false ) && \ECBB_Markup::ecbb_btn_style_active( $p ) && in_array( $part_type, ECBB_Selector_Factory::ecbb_button_parts(), true );
				$layout_btn_part = in_array( $part_type, ECBB_Selector_Factory::ecbb_button_parts(), true );
				$chip_surface    = in_array( $part_type, ECBB_Selector_Factory::ecbb_chip_parts(), true );
				$chip_sel        = $chip_surface ? ECBB_Selector_Factory::ecbb_chip_selectors( $scope_sel ) : $scope_sel;
				$ctx = [
					'part'            => $p,
					'part_type'       => $part_type,
					'scope_sel'       => $scope_sel,
					'hover_style_on'  => $hover_style_on,
					'btn_style_on'    => $btn_style_on,
					'layout_btn_part' => $layout_btn_part,
					'chip_surface'    => $chip_surface,
					'chip_sel'        => $chip_sel,
					'list_item_style' => $list_item_style,
					'color_fn'        => $color_fn,
				];
				$style_css = array_merge( $style_css, self::ecbb_parts_typography_spacing_rules( $ctx ) );
				$style_css = array_merge( $style_css, self::ecbb_parts_hover_var_rules( $ctx ) );
				$style_css = array_merge( $style_css, self::ecbb_parts_chip_button_rules( $ctx ) );
				$hover_bundle = self::ecbb_parts_hover_rules( $ctx );
				$style_css    = array_merge( $style_css, $hover_bundle['style'] );
				$hover_css    = array_merge( $hover_css, $hover_bundle['hover'] );
				$style_css    = array_merge( $style_css, self::ecbb_parts_background_rules( $ctx ) );
				$style_css    = array_merge( $style_css, self::ecbb_parts_meta_list_unified_rules( $ctx ) );
				$style_css    = array_merge( $style_css, self::ecbb_parts_meta_icon_rules( $ctx ) );
				$image_bundle = self::ecbb_parts_image_rules( $ctx );
				$style_css    = array_merge( $style_css, $image_bundle );
			}
		return [ $style_css, $hover_css ];
		}

		private static function ecbb_ctx_meta_list_block_margin( array $ctx ) {
			if ( self::ecbb_ctx_meta_list_unified_row( $ctx ) ) {
				return true;
			}
			$list = (string) ( $ctx['list_item_style'] ?? '' );
			if ( $list !== 'style-2' || ! class_exists( 'ECBB_Markup', false ) ) {
				return false;
			}

			return \ECBB_Markup::ecbb_part_shows_style2_meta_icon( (string) ( $ctx['part_type'] ?? '' ) );
		}

		public static function ecbb_image_decls( array $item, $device = 'desktop' ) {
			$styles = [];

		$border_css = '';
		if ( ! empty( $item['ecbb_image_border'] ) ) {
			$border_css = ECBB_Css_Value_Sanitizer::ecbb_border_css( $item['ecbb_image_border'] );
		}
		if ( $border_css !== '' ) {
			$styles[] = 'border:' . $border_css;
		}

		$radius = '';
		if ( ! empty( $item['ecbb_image_radius'] ) && is_array( $item['ecbb_image_radius'] ) ) {
			$radius = ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $item['ecbb_image_radius'] );
		}
		if ( $radius !== '' ) {
			$styles[] = 'border-radius:' . $radius;
		}

		$w = ECBB_Css_Value_Sanitizer::ecbb_css_size(
		ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['ecbb_image_width'] ?? '', $device ),
		'%'
		);
		if ( $w !== '' ) {
			$styles[] = 'width:' . $w;
		}

		$h = ECBB_Css_Value_Sanitizer::ecbb_css_size(
		ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['ecbb_image_height'] ?? '', $device ),
		'px'
		);
		if ( $h !== '' ) {
			$styles[] = 'height:' . $h;
		}

		return $styles;
		}

		private static function ecbb_btn_text_decl( array $item, $device, callable $color_fn ) {
			$tc_raw = '';
			if ( ! empty( $item['ecbb_typography'] ) && is_array( $item['ecbb_typography'] ) ) {
			$tc_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['ecbb_typography']['color'] ?? '', $device );
		}
			if ( $tc_raw === '' || $tc_raw === null ) {
				return [];
			}
			$tc = $color_fn( $tc_raw );
			return $tc !== '' ? [ 'color:' . $tc . ' !important' ] : [];
		}

		private static function ecbb_parts_background_rules( array $ctx ) {
			$p               = $ctx['part'];
			$part_type       = $ctx['part_type'];
			$scope_sel       = $ctx['scope_sel'];
			$chip_surface    = $ctx['chip_surface'];
			$chip_sel        = $ctx['chip_sel'];
			$btn_style_on    = $ctx['btn_style_on'];
			$layout_btn_part = $ctx['layout_btn_part'];
			$color_fn        = $ctx['color_fn'];
			$rules           = [];
		if ( $part_type === 'title' ) {
			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
					$bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
					$bg_in = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg_in !== '' ) {
						$inner_sel = ECBB_Selector_Factory::ecbb_title_inner_selectors( $scope_sel );
						$rules[]   = self::ecbb_mq_css_rule( $mq, $inner_sel . '{background-color:' . $bg_in . ' !important;}' );
				}
		}
		}
		foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
				$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg !== '' ) {
						$wrapper_reset = $scope_sel . '{background-color:transparent!important;}';
						$chip_rule     = $chip_sel . '{background-color:' . $bg . ' !important;}';
						$rules[]       = self::ecbb_mq_css_rule( $mq, $wrapper_reset . $chip_rule );
					}
				} elseif ( ! $btn_style_on ) {
					if ( self::ecbb_ctx_meta_list_unified_row( $ctx ) ) {
						continue;
					}
		$bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
		$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
		if ( $bg !== '' ) {
						if ( $layout_btn_part && ! $btn_style_on ) {
							$inner_sel  = ECBB_Selector_Factory::ecbb_button_inner_selectors( $scope_sel );
							$bg_rule    = $inner_sel . '{background-color:' . $bg . ' !important;}';
							$wrapper_bg = $scope_sel . '{background-color:transparent!important;}';
							$rules[]    = self::ecbb_mq_css_rule( $mq, $wrapper_bg . $bg_rule );
						} elseif ( $part_type !== 'title' ) {
							$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{background-color:' . $bg . ' !important;}' );
						}
					}
				}
			}
			return $rules;
		}

		private static function ecbb_parts_align_rules( array $ctx ) {
			$p         = $ctx['part'];
			$part_type = $ctx['part_type'];
			$scope_sel = $ctx['scope_sel'];
			$rules     = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
		$align_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_text_align'] ?? '', $device );
				if ( ! is_string( $align_raw ) || ! in_array( $align_raw, [ 'left', 'center', 'right', 'justify' ], true ) ) {
					continue;
				}
				$align_sel = ( in_array( $part_type, ECBB_Selector_Factory::ecbb_button_parts(), true ) || 'title' === $part_type )
					? $scope_sel
					: ECBB_Selector_Factory::ecbb_type_selectors( $scope_sel, $part_type );
				$rules[] = self::ecbb_mq_css_rule( $mq, $align_sel . '{text-align:' . $align_raw . ' !important;}' );
			}

			return $rules;
		}

		private static function ecbb_parts_chip_button_rules( array $ctx ) {
			$p            = $ctx['part'];
			$scope_sel    = $ctx['scope_sel'];
			$chip_surface = $ctx['chip_surface'];
			$chip_sel     = $ctx['chip_sel'];
			$btn_style_on = $ctx['btn_style_on'];
			$color_fn     = $ctx['color_fn'];
			$rules        = [];
		foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
			if ( $chip_surface ) {
				$padding_raw = self::ecbb_responsive_spacing( $p, 'ecbb_padding', $device );
					$padding     = ! empty( $padding_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $padding_raw ) : '';
				if ( $padding !== '' ) {
						$wrapper_pad  = $scope_sel . '{padding:0!important;}';
						$surface_rule = $chip_sel . '{padding:' . $padding . ' !important;}';
						$rules[]      = self::ecbb_mq_css_rule( $mq, $wrapper_pad . $surface_rule );
				}
		}
		if ( $btn_style_on ) {
			$wrapper_reset = $scope_sel . '{padding:0!important;border:none!important;background-color:transparent!important}';
					$rules[]       = self::ecbb_mq_css_rule( $mq, $wrapper_reset );
			$btn_decls = self::ecbb_button_decls( $p, $device, $color_fn );
			if ( ! empty( $btn_decls ) ) {
				$btn_sel = ECBB_Selector_Factory::ecbb_button_inner_selectors( $scope_sel );
						$rules[] = self::ecbb_mq_css_rule( $mq, $btn_sel . '{' . implode( ';', $btn_decls ) . '}' );
					}
				}
			}
			return $rules;
		}

		public static function ecbb_mq_css_rule( $mq, $rule ) {
			return $mq !== '' ? $mq . '{' . $rule . '}' : $rule;
		}

		private static function ecbb_parts_image_rules( array $ctx ) {
			$p              = $ctx['part'];
			$part_type      = $ctx['part_type'];
			$scope_sel      = $ctx['scope_sel'];
			$style_rules    = [];
			if ( $part_type !== 'image' ) {
				return $style_rules;
			}
			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$img_decls = self::ecbb_image_decls( $p, $device );
				if ( ! empty( $img_decls ) ) {
					$style_rules[] = self::ecbb_mq_css_rule(
						$mq,
						$scope_sel . ' .ecbb-event__image{' . implode( ';', $img_decls ) . '}'
					);
				}
			}
			return $style_rules;
		}

		private static function ecbb_btn_border_decls( array $item, $device, callable $color_fn ) {
		$border_color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['btn_border_color'] ?? '', $device );
		$border_style     = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['btn_border_type'] ?? '', $device );
		$border_width_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $item['btn_border_width'] ?? '', $device );

		if ( ! is_string( $border_style ) || $border_style === '' ) {
				$border_style = isset( $item['btn_border_type'] ) ? trim( (string) $item['btn_border_type'] ) : '';
		}
		if ( ! in_array( $border_style, [ 'solid', 'dashed', 'dotted', 'double', 'none' ], true ) ) {
			$border_style = 'solid';
		}

		$border_width = '';
		if ( is_array( $border_width_raw ) ) {
			$border_width = ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $border_width_raw );
		} elseif ( $border_width_raw !== '' && $border_width_raw !== null ) {
		$border_width = ECBB_Css_Value_Sanitizer::ecbb_css_size( $border_width_raw, 'px' );
		}

		if ( $border_style === 'none' ) {
				return [ 'border:none', 'box-sizing:border-box' ];
			}
			if ( $border_color_raw === '' || $border_color_raw === null ) {
				return [];
			}

		$border_color = $color_fn( $border_color_raw );
			if ( $border_color === '' ) {
				return [];
			}
			if ( $border_width === '' ) {
				$border_width = '1px';
			}

			return [
				'border:' . $border_width . ' ' . $border_style . ' ' . $border_color,
				'box-sizing:border-box',
			];
		}

		private static function ecbb_btn_padding_decl( array $item, $device ) {
		$padding_raw = self::ecbb_responsive_spacing( $item, 'btn_padding', $device );
			$padding_css = ! empty( $padding_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $padding_raw ) : '';
			return $padding_css !== '' ? [ 'padding:' . $padding_css ] : [];
		}

		private static function ecbb_parts_typography_spacing_rules( array $ctx ) {
			if ( 'image' === ( $ctx['part_type'] ?? '' ) ) {
				return [];
			}

			return array_merge(
				self::ecbb_parts_typo_var_rules( $ctx ),
				self::ecbb_parts_typo_decls_rules( $ctx ),
				self::ecbb_parts_spacing_rules( $ctx ),
				self::ecbb_parts_align_rules( $ctx )
			);
		}

		public static function ecbb_type_decls( array $typo, $device = 'desktop' ) {
			$decl = [];

			if ( empty( $typo['font-size'] ) && ! empty( $typo['fontSize'] ) ) {
				$typo['font-size'] = $typo['fontSize'];
			}
			if ( empty( $typo['line-height'] ) && ! empty( $typo['lineHeight'] ) ) {
				$typo['line-height'] = $typo['lineHeight'];
			}
			if ( empty( $typo['letter-spacing'] ) && ! empty( $typo['letterSpacing'] ) ) {
				$typo['letter-spacing'] = $typo['letterSpacing'];
			}
			if ( empty( $typo['font-weight'] ) && ! empty( $typo['fontWeight'] ) ) {
				$typo['font-weight'] = $typo['fontWeight'];
			}
			if ( empty( $typo['text-transform'] ) && ! empty( $typo['textTransform'] ) ) {
				$typo['text-transform'] = $typo['textTransform'];
			}

			$props = [
			'font-family'     => 'font-family',
			'font-size'       => 'font-size',
			'font-weight'     => 'font-weight',
			'line-height'     => 'line-height',
			'letter-spacing'  => 'letter-spacing',
			'text-transform'  => 'text-transform',
			'text-align'      => 'text-align',
			'text-decoration' => 'text-decoration',
			];

			foreach ( $props as $key => $css_prop ) {
				if ( empty( $typo[ $key ] ) ) {
					continue;
				}
			$val = ECBB_Css_Value_Sanitizer::ecbb_device_value( $typo[ $key ], $device );
			if ( is_array( $val ) || ( is_string( $val ) && trim( $val ) !== '' ) ) {
				$val = ECBB_Css_Value_Sanitizer::ecbb_clean_type_value( $css_prop, $val );
				if ( $val !== '' ) {
					$decl[] = $css_prop . ':' . $val;
				}
			} elseif ( is_numeric( $val ) && in_array( $css_prop, [ 'font-weight', 'font-size', 'line-height', 'letter-spacing' ], true ) ) {
				$val = ECBB_Css_Value_Sanitizer::ecbb_clean_type_value( $css_prop, (string) $val );
				if ( $val !== '' ) {
					$decl[] = $css_prop . ':' . $val;
				}
			}
		}

		if ( ! empty( $typo['color'] ) && ! ECBB_Selector_Factory::ecbb_is_builder_preview() ) {
			$color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $typo['color'], $device );
			$color     = class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_norm_color( $color_raw )
			: '';
			if ( $color !== '' ) {
				$decl[] = 'color:' . $color;
			}
		}

		return $decl;
		}

		private static function ecbb_parts_spacing_rules( array $ctx ) {
			$p               = $ctx['part'];
			$scope_sel       = $ctx['scope_sel'];
			$chip_surface    = $ctx['chip_surface'];
			$layout_btn_part = $ctx['layout_btn_part'];
			$btn_style_on    = $ctx['btn_style_on'];
			$meta_unified    = self::ecbb_ctx_meta_list_unified_row( $ctx );
			$meta_list_block = self::ecbb_ctx_meta_list_block_margin( $ctx );
			$row_sel         = $meta_list_block ? ECBB_Selector_Factory::ecbb_meta_row_selector( $scope_sel ) : '';
			$list_sel        = $meta_list_block ? ECBB_Selector_Factory::ecbb_meta_list_selector( $scope_sel ) : '';
			$rules           = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
		$margin_raw = self::ecbb_responsive_spacing( $p, 'ecbb_margin', $device );
		$margin     = ! empty( $margin_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $margin_raw ) : '';
		if ( $margin !== '' ) {
					if ( $meta_list_block && $row_sel !== '' ) {
						$rules[] = self::ecbb_mq_css_rule( $mq, $row_sel . '{margin:' . $margin . ' !important;}' );
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{margin:0!important;}' );
						if ( $list_sel !== '' ) {
							$rules[] = self::ecbb_mq_css_rule( $mq, $list_sel . '{margin:0!important;}' );
						}
					} elseif ( $meta_list_block && $list_sel !== '' ) {
						$rules[] = self::ecbb_mq_css_rule( $mq, $list_sel . '{margin:' . $margin . ' !important;}' );
						if ( $row_sel !== '' ) {
							$rules[] = self::ecbb_mq_css_rule( $mq, $row_sel . '{margin:0!important;}' );
						}
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{margin:0!important;}' );
					} else {
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{margin:' . $margin . ' !important;}' );
					}
				}
				if ( $chip_surface || $meta_unified ) {
					continue;
				}
			$pad_raw = self::ecbb_responsive_spacing( $p, 'ecbb_padding', $device );
			$padding = ! empty( $pad_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $pad_raw ) : '';
				if ( $padding === '' ) {
					continue;
				}
				if ( $layout_btn_part && ! $btn_style_on ) {
					$pad_sel = ECBB_Selector_Factory::ecbb_button_inner_selectors( $scope_sel );
					$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{padding:0!important;}' );
					$rules[] = self::ecbb_mq_css_rule( $mq, $pad_sel . '{padding:' . $padding . ' !important;}' );
				} else {
					$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{padding:' . $padding . ' !important;}' );
				}
			}

			return $rules;
		}

		public static function ecbb_decls_important( array $decls ) {
			$out = [];
			foreach ( $decls as $decl ) {
				$decl = trim( (string) $decl );
				if ( $decl === '' ) {
					continue;
				}
				if ( ! preg_match( '/!important\s*$/i', $decl ) ) {
					$decl .= ' !important';
				}
				$out[] = $decl;
			}
			return $out;
		}

		private static function ecbb_parts_typo_var_rules( array $ctx ) {
			$p               = $ctx['part'];
			$scope_sel       = $ctx['scope_sel'];
			$chip_surface    = $ctx['chip_surface'];
			$layout_btn_part = $ctx['layout_btn_part'];
			$btn_style_on    = $ctx['btn_style_on'];
			$rules           = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				if ( $chip_surface && ! empty( $p['ecbb_typography'] ) && is_array( $p['ecbb_typography'] ) ) {
					$chip_fg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_typography']['color'] ?? '', $device );
					$chip_fg     = class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $chip_fg_raw ) : '';
					if ( $chip_fg !== '' ) {
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{--ecbb-chip-fg:' . $chip_fg . ';}' );
					}
				}
				if ( $layout_btn_part && ! empty( $p['ecbb_typography'] ) && is_array( $p['ecbb_typography'] ) ) {
					$btn_fg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_typography']['color'] ?? '', $device );
					$btn_fg     = class_exists( 'ECBB_Markup', false ) ? \ECBB_Markup::ecbb_norm_color( $btn_fg_raw ) : '';
					if ( $btn_fg !== '' ) {
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{--ecbb-btn-fg:' . $btn_fg . ';}' );
					}
				}
				if ( $layout_btn_part && ! $btn_style_on && ! empty( $p['ecbb_typography'] ) && is_array( $p['ecbb_typography'] ) ) {
					$btn_var_decls = [];
					$font_raw      = ECBB_Css_Value_Sanitizer::ecbb_device_value(
						$p['ecbb_typography']['font-size'] ?? ( $p['ecbb_typography']['fontSize'] ?? '' ),
						$device
					);
					$font_size     = $font_raw !== '' && $font_raw !== null ? ECBB_Css_Value_Sanitizer::ecbb_clean_type_value( 'font-size', $font_raw ) : '';
					if ( $font_size !== '' ) {
						$btn_var_decls[] = '--ecbb-btn-font-size:' . $font_size;
					}
					$lh_raw      = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_typography']['line-height'] ?? '', $device );
					$line_height = $lh_raw !== '' && $lh_raw !== null ? ECBB_Css_Value_Sanitizer::ecbb_clean_type_value( 'line-height', $lh_raw ) : '';
					if ( $line_height !== '' ) {
						$btn_var_decls[] = '--ecbb-btn-line-height:' . $line_height;
					}
					if ( ! empty( $btn_var_decls ) ) {
						$rules[] = self::ecbb_mq_css_rule( $mq, $scope_sel . '{' . implode( ';', $btn_var_decls ) . ';}' );
					}
				}
			}

			return $rules;
		}

		private static function ecbb_ctx_meta_list_unified_row( array $ctx ) {
			$list = (string) ( $ctx['list_item_style'] ?? '' );
			if ( $list !== 'style-1' && $list !== 'grid' ) {
				return false;
			}
			if ( ! class_exists( 'ECBB_Markup', false ) ) {
				return false;
			}

			return \ECBB_Markup::ecbb_part_renders_meta_list_icon( (string) ( $ctx['part_type'] ?? '' ) );
		}

		private static function ecbb_parts_meta_icon_rules( array $ctx ) {
			$p               = $ctx['part'];
			$part_type       = $ctx['part_type'];
			$scope_sel       = $ctx['scope_sel'];
			$list_item_style = $ctx['list_item_style'];
			$color_fn        = $ctx['color_fn'];
			$rules           = [];

			if ( $list_item_style !== 'style-2' ) {
				return $rules;
			}
			if ( ! class_exists( 'ECBB_Markup', false ) || ! \ECBB_Markup::ecbb_part_shows_style2_meta_icon( $part_type ) ) {
				return $rules;
			}

			$icon_sel = ECBB_Selector_Factory::ecbb_resolved_meta_icon_selector( $scope_sel, $part_type, $list_item_style );
			if ( $icon_sel === '' ) {
				return $rules;
			}

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$icon_color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_meta_icon_color'] ?? '', $device );
				$icon_color     = $icon_color_raw !== '' && $icon_color_raw !== null ? $color_fn( $icon_color_raw ) : '';
				if ( $icon_color !== '' ) {
					$rules[] = self::ecbb_mq_css_rule( $mq, $icon_sel . '{color:' . $icon_color . ' !important;}' );
				}

				$icon_bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_meta_icon_background'] ?? '', $device );
				$icon_bg     = $icon_bg_raw !== '' && $icon_bg_raw !== null ? $color_fn( $icon_bg_raw ) : '';
				if ( $icon_bg !== '' ) {
					$rules[] = self::ecbb_mq_css_rule( $mq, $icon_sel . '{background-color:' . $icon_bg . ' !important;}' );
				}
			}

			return $rules;
		}

		private static function ecbb_parts_typo_decls_rules( array $ctx ) {
			$p               = $ctx['part'];
			$part_type       = $ctx['part_type'];
			$scope_sel       = $ctx['scope_sel'];
			$layout_btn_part = $ctx['layout_btn_part'];
			$btn_style_on    = $ctx['btn_style_on'];
			$meta_unified    = self::ecbb_ctx_meta_list_unified_row( $ctx );
			$rules           = [];

			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				if ( empty( $p['ecbb_typography'] ) || ! is_array( $p['ecbb_typography'] ) ) {
					continue;
				}
				if ( $meta_unified ) {
					continue;
				}
				$typo_decls = self::ecbb_type_decls( $p['ecbb_typography'], $device );
				if ( empty( $typo_decls ) ) {
					continue;
				}
				if ( $layout_btn_part && ! $btn_style_on ) {
					$typo_decls = self::ecbb_decls_important( $typo_decls );
					$rules[]    = self::ecbb_mq_css_rule(
						$mq,
						$scope_sel . '{font-size:0!important;line-height:0!important;}'
					);
				}
						$typo_sel = ECBB_Selector_Factory::ecbb_type_selectors( $scope_sel, $part_type );
				$rules[]  = self::ecbb_mq_css_rule( $mq, $typo_sel . '{' . implode( ';', $typo_decls ) . '}' );
			}

			return $rules;
		}

		public static function ecbb_button_decls( array $item, $device, callable $color_fn ) {
			if ( class_exists( 'ECBB_Markup', false ) && ! \ECBB_Markup::ecbb_btn_style_active( $item ) ) {
				return [];
			}
			if ( empty( $item['btn_style'] ) && ! class_exists( 'ECBB_Markup', false ) ) {
				return [];
			}

			return array_merge(
				self::ecbb_btn_bg_decl( $item, $device, $color_fn ),
				self::ecbb_btn_text_decl( $item, $device, $color_fn ),
				self::ecbb_btn_border_decls( $item, $device, $color_fn ),
				self::ecbb_btn_padding_decl( $item, $device ),
				self::ecbb_btn_radius_decl( $item, $device )
			);
		}

		private static function ecbb_parts_meta_list_unified_rules( array $ctx ) {
			if ( ! self::ecbb_ctx_meta_list_unified_row( $ctx ) ) {
				return [];
			}

			$p         = $ctx['part'];
			$scope_sel = $ctx['scope_sel'];
			$color_fn  = $ctx['color_fn'];
			$row_sel  = ECBB_Selector_Factory::ecbb_meta_row_selector( $scope_sel );
			$icon_sel = ECBB_Selector_Factory::ecbb_resolved_meta_icon_selector(
				$scope_sel,
				(string) ( $ctx['part_type'] ?? '' ),
				(string) ( $ctx['list_item_style'] ?? '' )
			);
			if ( $row_sel === '' ) {
				return [];
			}

			$inherit_reset = $scope_sel . '{margin:0!important;padding:0!important;background-color:transparent!important;}';
			$icon_reset    = $icon_sel !== ''
				? $icon_sel . '{width:auto!important;height:auto!important;flex:0 0 auto!important;padding:0!important;background:transparent!important;border-radius:0!important;color:inherit!important;font-size:inherit!important;line-height:inherit!important;}'
				: '';

			$rules = [];
			foreach ( ECBB_Css_Value_Sanitizer::ecbb_breakpoints() as $device => $mq ) {
				$row_decls = [
					'display:inline-flex',
					'align-items:center',
					'gap:8px',
					'width:fit-content',
					'max-width:100%',
					'border-radius:10px',
				];

				$bg_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_background'] ?? '', $device );
				$bg     = $bg_raw !== '' && $bg_raw !== null ? $color_fn( $bg_raw ) : '';
				if ( $bg !== '' ) {
					$row_decls[] = 'background-color:' . $bg . ' !important';
				}

				if ( ! empty( $p['ecbb_typography'] ) && is_array( $p['ecbb_typography'] ) ) {
					$typo_color_raw = ECBB_Css_Value_Sanitizer::ecbb_device_value( $p['ecbb_typography']['color'] ?? '', $device );
					$typo_color     = class_exists( 'ECBB_Markup', false )
						? \ECBB_Markup::ecbb_norm_color( $typo_color_raw )
						: $color_fn( $typo_color_raw );
					if ( $typo_color !== '' ) {
						$row_decls[] = 'color:' . $typo_color . ' !important';
					}
					$typo_decls = self::ecbb_type_decls( $p['ecbb_typography'], $device );
					foreach ( $typo_decls as $decl ) {
						if ( strpos( (string) $decl, 'color:' ) === 0 ) {
							continue;
						}
						$row_decls[] = $decl;
					}
				}

				$pad_raw = self::ecbb_responsive_spacing( $p, 'ecbb_padding', $device );
				$padding = ! empty( $pad_raw ) ? ECBB_Css_Value_Sanitizer::ecbb_spacing_css( $pad_raw ) : '';
				if ( $padding !== '' ) {
					$row_decls[] = 'padding:' . $padding . ' !important';
				}

				$rules[] = self::ecbb_mq_css_rule( $mq, $inherit_reset );
				if ( $icon_reset !== '' ) {
					$rules[] = self::ecbb_mq_css_rule( $mq, $icon_reset );
				}
				$rules[] = self::ecbb_mq_css_rule( $mq, $row_sel . '{' . implode( ';', $row_decls ) . '}' );
			}

			return $rules;
		}

		public static function ecbb_responsive_spacing( array $item, $key, $device = 'desktop' ) {
			$nested = $item[ $key ] ?? null;
			if ( is_array( $nested ) && ( isset( $nested['desktop'] ) || isset( $nested['tablet'] ) || isset( $nested['mobile'] ) ) ) {
				$picked = ECBB_Css_Value_Sanitizer::ecbb_device_value( $nested, $device );
				return is_array( $picked ) ? $picked : null;
			}

		$suffixes = [
		'desktop' => [ $key ],
		'tablet'  => [ "{$key}:tablet_portrait", "{$key}:tablet" ],
		'mobile'  => [ "{$key}:mobile_portrait", "{$key}:mobile_landscape", "{$key}:mobile" ],
		];

		$is_spacing = static function ( $value ) {
			return is_array( $value ) && ( isset( $value['top'] ) || isset( $value['right'] ) || isset( $value['bottom'] ) || isset( $value['left'] ) );
		};

		$devices = $device === 'mobile'
		? [ 'mobile', 'tablet', 'desktop' ]
		: ( $device === 'tablet' ? [ 'tablet', 'desktop' ] : [ 'desktop' ] );

		foreach ( $devices as $dev ) {
			foreach ( $suffixes[ $dev ] as $candidate ) {
				if ( isset( $item[ $candidate ] ) && $is_spacing( $item[ $candidate ] ) ) {
					return $item[ $candidate ];
				}
		}
		if ( $dev === 'desktop' && $is_spacing( $nested ) ) {
			return $nested;
		}
		}

		return null;
		}

	}
}
