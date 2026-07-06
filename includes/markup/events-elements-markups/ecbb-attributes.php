<?php
/**
 * Part DOM ids, classes, hover styles, and shared chrome.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Part_Chrome', false ) ) {

	final class ECBB_Part_Chrome {
		/** Merge layout/skin classes onto the first anchor in read-more markup. */
		public static function ecbb_read_more_merge_link_classes( $html, $extra_classes ) {
			$extra_classes = trim( (string) $extra_classes );
			if ( $extra_classes === '' || strpos( $html, '<a ' ) === false ) {
				return $html;
			}

			if ( preg_match( '#(<a\s[^>]*\sclass=")([^"]*)(")#', $html, $matches ) ) {
				$merged = trim( $matches[2] . ' ' . $extra_classes );
				return preg_replace(
					'#(<a\s[^>]*\sclass=")([^"]*)(")#',
					'$1' . $merged . '$3',
					$html,
					1
				);
			}

			return preg_replace(
				'#<a\s#',
				'<a class="' . esc_attr( $extra_classes ) . '" ',
				$html,
				1
			);
		}

		public static function ecbb_part_dom_id(array $item, $idx)
		{
			if (! empty($item['id'])) {
				return (string) $item['id'];
			}
		return (string) absint($idx);
		}

		public static function ecbb_part_dom_id_attr(array $item, $idx)
		{
			$id = self::ecbb_part_dom_id($item, $idx);
			if ($id === '') {
				return '';
			}
		return ' data-field-id="' . esc_attr($id) . '"';
		}

		public static function ecbb_part_classes($part, $idx, $skin = '', array $item = [])
		{
			$idx_c = self::ecbb_part_index_class($idx);
			if ((string) $skin === 'style2') {
				$part_cls = class_exists('ECBB_List_2', false)
				? \ECBB_List_2::ecbb_part_class($part)
				: 'ecbb-style2-' . str_replace('_', '-', (string) $part);
				$classes = 'ecbb-event-part ' . $part_cls . ' ' . $idx_c;
			} else {
			$bem     = 'ecbb-event-part--' . str_replace('_', '-', (string) $part);
			$classes = 'ecbb-event-part ' . $bem . ' ' . $idx_c;
		}
		if ($item !== []) {
			$row = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
			$ui_part = isset($item['part']) ? (string) $item['part'] : (string) $part;
			if (
			$ui_part === 'title'
			&& ! self::ecbb_title_link_active($row)
			) {
				$classes .= ' ecbb-no-hover';
			} elseif (
		ECBB_Settings_Normalizer::ecbb_part_has_hover($ui_part)
		&& ! self::ecbb_hover_style_active($item)
		) {
			$classes .= ' ecbb-no-hover';
		}
		if (
		self::ecbb_btn_style_active($row)
		&& class_exists( 'ECBB_Styles', false )
		&& in_array($ui_part, \ECBB_Styles::ecbb_button_parts(), true)
		) {
			$classes .= ' ecbb-has-btn';
		}
		if ( self::ecbb_row_has_typography_color( $item ) ) {
			$classes .= ' ecbb-has-typo-fg';
		}
		if ( self::ecbb_row_has_typography_font_size( $item ) ) {
			$classes .= ' ecbb-has-typo-size';
		}
		if ( self::ecbb_hover_style_active( $item ) ) {
			$hover_fg = ECBB_Markup::ecbb_norm_hover_paint_color( $item['ecbb_hover_color'] ?? ( $item['hover_color'] ?? '' ) );
			if ( $hover_fg !== '' ) {
				$classes .= ' ecbb-has-hover-fg';
			}
			$hover_bg = ECBB_Markup::ecbb_norm_hover_paint_color( $item['ecbb_hover_background'] ?? '' );
			if ( $hover_bg !== '' ) {
				$classes .= ' ecbb-has-hover-bg';
			}
		}
		}
		return $classes;
		}

		public static function ecbb_part_index_class($idx)
		{
			return 'ecbb-p' . absint($idx);
		}

		public static function ecbb_part_wrap_attrs(array $item, $idx, $style = '')
		{
			$attrs = self::ecbb_part_dom_id_attr($item, $idx);
			$hover = self::ecbb_hover_inline_vars( $item );
			if ( $hover !== '' ) {
				$style = trim( (string) $style );
				$style = $style !== '' ? $style . ';' . $hover : $hover;
			}
			if ($style !== '') {
				$attrs .= ' style="' . esc_attr($style) . '"';
			}
		return $attrs;
		}

		/**
		 * CSS scope selector for a repeater row (prefers Bricks data-field-id over index).
		 *
		 * @param string              $scope_class Widget instance scope class.
		 * @param array<string,mixed> $item        Repeater row (before clean).
		 * @param int                 $idx         Row index fallback.
		 * @return string
		 */
		public static function ecbb_part_scope_selector( $scope_class, array $item, $idx ) {
			$scope_class = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) $scope_class );
			if ( $scope_class === '' ) {
				return '';
			}
			if ( ! empty( $item['id'] ) ) {
				return '.' . $scope_class . ' [data-field-id="' . esc_attr( (string) $item['id'] ) . '"]';
			}
			return '.' . $scope_class . ' .' . self::ecbb_part_index_class( absint( $idx ) );
		}

		/**
		 * Whether a repeater row has saved hover styling (colors, decoration, animation).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		public static function ecbb_hover_has_custom_styles( array $item ) {
			foreach ( [ 'ecbb_hover_color', 'ecbb_hover_background' ] as $key ) {
				if ( ! array_key_exists( $key, $item ) || $item[ $key ] === '' || $item[ $key ] === null ) {
					continue;
				}
				if ( ECBB_Markup::ecbb_norm_hover_paint_color( $item[ $key ] ) !== '' ) {
					return true;
				}
			}

			$hover_td = isset( $item['ecbb_hover_text_decoration'] ) ? (string) $item['ecbb_hover_text_decoration'] : '';
			if ( $hover_td !== '' ) {
				return true;
			}

			$hover_anim = isset( $item['ecbb_hover_animation'] ) ? (string) $item['ecbb_hover_animation'] : '';
			if ( $hover_anim === 'none' ) {
				return false;
			}
			return $hover_anim !== '';
		}

		public static function ecbb_hover_anim_css($scope_sel, $anim)
		{
			$anim = is_string($anim) ? $anim : '';
			$dur  = '0.38s';
			$ease = 'ease';
			$transforms = [
				'fade_in_up'    => 'translateY(-8px)',
				'fade_in_right' => 'translateX(8px)',
				'fade_in_down'  => 'translateY(8px)',
				'fade_in_left'  => 'translateX(-8px)',
				'zoom_in'       => 'scale(1.06)',
				'zoom_out'      => 'scale(0.94)',
			];
			if ( ! isset( $transforms[ $anim ] ) ) {
				return ['base' => '', 'hover' => ''];
			}

		$scope_sel = str_replace(['{', '}', '<', '>'], '', (string) $scope_sel);
		$base      = "{$scope_sel}{transition:transform {$dur} {$ease};transform:none;transform-origin:center center;}";
		$hover     = self::ecbb_hover_state_selectors($scope_sel);

		return [
			'base'  => $base,
			'hover' => $hover . '{transform:' . $transforms[ $anim ] . ';}',
		];
		}

		/**
		 * Inline CSS custom properties for repeater hover paint on the part wrapper.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string Declaration string (no style="" wrapper), or empty.
		 */
		public static function ecbb_hover_inline_vars( array $item ) {
			if ( ! self::ecbb_hover_style_active( $item ) ) {
				return '';
			}

			$decls = [];
			$fg    = ECBB_Markup::ecbb_norm_hover_paint_color( $item['ecbb_hover_color'] ?? ( $item['hover_color'] ?? '' ) );
			if ( $fg !== '' ) {
				$decls[] = '--ecbb-hover-fg:' . $fg;
			}
			$bg = ECBB_Markup::ecbb_norm_hover_paint_color( $item['ecbb_hover_background'] ?? '' );
			if ( $bg !== '' ) {
				$decls[] = '--ecbb-hover-bg:' . $bg;
			}

			return $decls !== [] ? implode( ';', $decls ) . ';' : '';
		}

		public static function ecbb_hover_state_selectors($scope_sel)
		{
			$scope_sel = trim((string) $scope_sel);
			if ($scope_sel === '') {
				return '';
			}
		if (strpos($scope_sel, ',') === false) {
			return $scope_sel . ':hover';
		}
		$parts = array_filter(array_map('trim', explode(',', $scope_sel)));
		if (empty($parts)) {
			return $scope_sel . ':hover';
		}
		return implode(
		',',
		array_map(
		static function ($part) {
			return $part . ':hover';
		},
		$parts
		)
		);
		}

		public static function ecbb_hover_style_active(array $item)
		{
			$ui_part = isset($item['part']) ? (string) $item['part'] : '';
			if (! ECBB_Settings_Normalizer::ecbb_part_has_hover($ui_part)) {
				return false;
			}
		if (
		$ui_part === 'title'
		&& ! self::ecbb_title_link_active($item)
		) {
			return false;
		}
		if ( self::ecbb_hover_has_custom_styles( $item ) ) {
			return true;
		}
		if (! array_key_exists('ecbb_use_hover', $item)) {
			return true;
		}
		if ($item['ecbb_use_hover'] === null || $item['ecbb_use_hover'] === '') {
			return true;
		}
		return ECBB_Settings_Normalizer::ecbb_hover_is_on($item['ecbb_use_hover']);
		}

		public static function ecbb_title_link_active(array $item)
		{
			return ECBB_Markup::ecbb_is_truthy($item['link'] ?? false, false);
		}

		public static function ecbb_btn_style_active( array $item ) {
			return ECBB_Markup::ecbb_parse_bricks_checkbox( $item['btn_style'] ?? false );
		}

		/**
		 * Reference layout button class for read-more per skin.
		 *
		 * @param string $skin style1|style2|grid
		 * @return string
		 */
		public static function ecbb_layout_read_more_btn_class( $skin ) {
			$skin = (string) $skin;
			if ( $skin === 'style2' ) {
				return 'ecbb-event-card__button';
			}
			if ( $skin === 'grid' ) {
				return 'event-button event-button--filled';
			}
			return 'event-button event-button--outline';
		}

		/**
		 * Whether a repeater row defines any typography color value.
		 *
		 * Used to suppress layout fallback button hover colors when the user already
		 * chose a custom text color in the Typography control.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ecbb_row_has_typography_color( array $item ) {
			if ( empty( $item['ecbb_typography'] ) || ! is_array( $item['ecbb_typography'] ) ) {
				return false;
			}

			$stack = [ $item['ecbb_typography'] ];
			while ( $stack !== [] ) {
				$current = array_pop( $stack );
				if ( ! is_array( $current ) ) {
					continue;
				}

				foreach ( $current as $key => $value ) {
					if ( is_array( $value ) ) {
						$stack[] = $value;
						continue;
					}

					if ( strpos( (string) $key, 'color' ) !== false && trim( (string) $value ) !== '' ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Whether a repeater row defines any typography font-size value.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ecbb_row_has_typography_font_size( array $item ) {
			if ( empty( $item['ecbb_typography'] ) || ! is_array( $item['ecbb_typography'] ) ) {
				return false;
			}

			$stack = [ $item['ecbb_typography'] ];
			while ( $stack !== [] ) {
				$current = array_pop( $stack );
				if ( ! is_array( $current ) ) {
					continue;
				}

				foreach ( $current as $key => $value ) {
					if ( is_array( $value ) ) {
						if (
							in_array( (string) $key, [ 'font-size', 'fontSize' ], true )
							&& isset( $value['size'] )
							&& trim( (string) $value['size'] ) !== ''
						) {
							return true;
						}
						$stack[] = $value;
						continue;
					}

					if (
						in_array( (string) $key, [ 'font-size', 'fontSize' ], true )
						&& trim( (string) $value ) !== ''
					) {
						return true;
					}
				}
			}

			return false;
		}

		public static function ecbb_image_size_opts()
		{
			$opts = [
			'' => esc_html__('Default', 'events-calendar-for-bricks'),
			];
			$subs = function_exists('wp_get_registered_image_subsizes') ? wp_get_registered_image_subsizes() : [];
			foreach ($subs as $slug => $data) {
				if (! is_string($slug) || $slug === '') {
					continue;
				}
			$w = isset($data['width']) ? (int) $data['width'] : 0;
			$h = isset($data['height']) ? (int) $data['height'] : 0;
			$opts[$slug] = $slug . ' (' . $w . "\u{00D7}" . $h . ')';
		}
		if (function_exists('get_intermediate_image_sizes')) {
			foreach (get_intermediate_image_sizes() as $slug) {
				if (is_string($slug) && $slug !== '' && ! isset($opts[$slug])) {
					$opts[$slug] = $slug;
				}
		}
		}
		$opts['full'] = esc_html__('Full', 'events-calendar-for-bricks');
		return $opts;
		}

		public static function ecbb_sanitize_image_size($slug, $fallback = 'large')
		{
			$slug = is_string($slug) ? trim($slug) : '';
			if ($slug === '') {
				return $fallback;
			}
		if ($slug === 'full') {
			return 'full';
		}
		$reg = function_exists('wp_get_registered_image_subsizes') ? wp_get_registered_image_subsizes() : [];
		if (isset($reg[$slug])) {
			return $slug;
		}
		$intermediate = function_exists('get_intermediate_image_sizes') ? get_intermediate_image_sizes() : [];
		if (is_array($intermediate) && in_array($slug, $intermediate, true)) {
			return $slug;
		}
		if (preg_match('/^[a-z0-9_\\-]+$/i', $slug)) {
			return $slug;
		}
		return $fallback;
		}

		public static function ecbb_image_align_opts()
		{
			return [
			''   => esc_html__('Default', 'events-calendar-for-bricks'),
			'tl' => esc_html__('Top left', 'events-calendar-for-bricks'),
			'tc' => esc_html__('Top center', 'events-calendar-for-bricks'),
			'tr' => esc_html__('Top right', 'events-calendar-for-bricks'),
			'ml' => esc_html__('Middle left', 'events-calendar-for-bricks'),
			'mc' => esc_html__('Middle center', 'events-calendar-for-bricks'),
			'mr' => esc_html__('Middle right', 'events-calendar-for-bricks'),
			'bl' => esc_html__('Bottom left', 'events-calendar-for-bricks'),
			'bc' => esc_html__('Bottom center', 'events-calendar-for-bricks'),
			'br' => esc_html__('Bottom right', 'events-calendar-for-bricks'),
			];
		}

		public static function ecbb_align_to_position($key)
		{
			$key = is_string($key) ? strtolower(trim($key)) : '';
			$map = [
			'tl' => 'left top',
			'tc' => 'center top',
			'tr' => 'right top',
			'ml' => 'left center',
			'mc' => 'center center',
			'mr' => 'right center',
			'bl' => 'left bottom',
			'bc' => 'center bottom',
			'br' => 'right bottom',
			];
			return isset($map[$key]) ? $map[$key] : '';
		}

		public static function ecbb_image_dual_layer( array $item ) {
			return false;
		}

		public static function ecbb_action_link_html( array $item, $href, $label, $link_attr = '', $extra_attrs = '' ) {
			$part       = isset( $item['part'] ) ? (string) $item['part'] : '';
			$force_link = in_array( $part, [ 'read_more', 'event_tickets', 'event_rsvp' ], true );

			if ( ! $force_link && ! self::ecbb_hover_style_active( $item ) && ! self::ecbb_btn_style_active( $item ) ) {
				return '<span class="ecbb-event__plain">' . esc_html( $label ) . '</span>';
			}

			return '<a class="ecbb-event__link" href="' . esc_url( $href ) . '"' . $extra_attrs . $link_attr . '>' . esc_html( $label ) . '</a>';
		}

		public static function ecbb_terms_html(array $terms, array $item, $style_attr = '', $skin = '', $part = 'categories')
		{
			$style_attr = (string) $style_attr;
			$skin       = (string) $skin;
			$part       = sanitize_key((string) $part);
			$link_style = $style_attr !== '' ? ' style="' . esc_attr($style_attr) . '"' : '';
			$chip_each  = ('style1' === $skin && 'categories' === $part);
			$link_terms = self::ecbb_hover_style_active($item);

			$sep = isset($item['terms_separator']) ? sanitize_text_field((string) $item['terms_separator']) : ', ';
			$sep = $sep !== '' ? $sep : ', ';
			if ($chip_each) {
				$sep = '';
			}

		$links = [];
		foreach ($terms as $t) {
			if (! $t instanceof \WP_Term) {
				continue;
			}

		if ($link_terms) {
			$url = get_term_link($t);
			if (is_wp_error($url)) {
				continue;
			}
		$inner = '<a class="ecbb-event__link" href="' . esc_url($url) . '"' . $link_style . '>' . esc_html($t->name) . '</a>';
		} else {
		$inner = '<span class="ecbb-event__term">' . esc_html($t->name) . '</span>';
		}

		if ($chip_each) {
			$links[] = '<span class="ecbb-event__term-chip">' . $inner . '</span>';
		} else {
		$links[] = $inner;
		}
		}

		if ($links === []) {
			return '';
		}

		return wp_kses_post(implode(esc_html($sep), $links));
		}
	}
}
