<?php

/**
 * Events Widget: shared TEC event part markup (date, tickets, images, hover helpers).
 *
 * @package ECBB
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Resolve Bricks color control values (palette id, raw, rgb object, hex) to a CSS color string.
 * Mirrors {@see \Bricks\Assets::generate_css_color()} when available.
 *
 * @param mixed $value Saved control value (array, object, string, JSON string).
 * @return string Usable CSS color or empty string.
 */

if (! class_exists('ECBB_Markup', false)) {

	final class ECBB_Markup
	{

		// --- Shared utilities ---

		public static function ecbb_is_truthy($value, $default = false)
		{
			if ($value === null) {
				return $default;
			}
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return (int) $value === 1;
		}
		if (is_string($value)) {
			$value = strtolower(trim($value));
			if ($value === '') {
				return false;
			}
		if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
			return true;
		}
		if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
			return false;
		}
		}
		return (bool) $value;
		}

		/**
		 * Resolve Bricks color control values (palette id, raw, rgb object, hex) to a CSS color string.
		 *
		 * @param mixed $value Saved control value.
		 * @return string Usable CSS color or empty string.
		 */
		public static function ecbb_norm_color($value)
		{
			if ($value === null || $value === false) {
				return '';
			}

		if (is_object($value)) {
			$value = (array) $value;
		}

		$color = '';

		if (is_array($value)) {
			if (
			class_exists('\Bricks\Assets') && method_exists('\Bricks\Assets', 'generate_css_color')
			&& (isset($value['id']) || isset($value['raw']) || isset($value['rgb']) || isset($value['hex']) || isset($value['rgba']))
			) {
				$gen = \Bricks\Assets::generate_css_color($value);
				if (is_string($gen) && trim($gen) !== '') {
					$color = trim($gen);
				}
		}
		if ($color === '' && isset($value['rgb']) && is_array($value['rgb'])) {
			$r = isset($value['rgb']['r']) ? (int) $value['rgb']['r'] : 0;
			$g = isset($value['rgb']['g']) ? (int) $value['rgb']['g'] : 0;
			$b = isset($value['rgb']['b']) ? (int) $value['rgb']['b'] : 0;
			$a = isset($value['rgb']['a']) ? (float) $value['rgb']['a'] : 1.0;
			$color = 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
		}
		if ($color === '') {
			$tmp = $value['raw'] ?? $value['rgba'] ?? $value['hex'] ?? $value['value'] ?? '';
			$color = is_string($tmp) ? trim($tmp) : '';
		}
		} elseif (is_string($value)) {
		$color = trim($value);
		} else {
		return '';
		}

		if ($color === '') {
			return '';
		}

		if (isset($color[0]) && ($color[0] === '{' || $color[0] === '[')) {
				$decoded = json_decode($color, true);
				if (is_array($decoded)) {
					return self::ecbb_norm_color($decoded);
				}
		}

		$color = preg_replace('/\s*!important\s*$/i', '', $color);
		$color = rtrim(trim($color), ';');

		if (preg_match('/^var\\(--[a-zA-Z0-9\\-_]+(\\s*,\\s*[^\\)]+)?\\)$/', $color)) {
			return $color;
		}

		$lower = strtolower($color);
		if (in_array($lower, ['transparent', 'currentcolor', 'inherit', 'initial', 'unset'], true)) {
			return $color;
		}

		if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
			return $color;
		}

		if (preg_match('/^rgba?\\(([^\\)]+)\\)$/', $color)) {
			return $color;
		}

		if (preg_match('/^hsla?\\(([^\\)]+)\\)$/', $color)) {
			return $color;
		}

		return '';
		}

		/**
		 * Normalize a hover paint color (excludes transparent / inherit / currentColor).
		 *
		 * @param mixed $value Saved hover color control value.
		 * @return string CSS color or empty when not a visible hover paint.
		 */
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

		// --- Layout template & parts (settings / save / templates) ---

		public static function ecbb_sanitize_template( $template ) {
			$template = is_string( $template ) ? trim( $template ) : 'list';
			return in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';
		}

		/**
		 * Normalize layout template + list style from element settings.
		 *
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array{template:string,item_chrome:string}
		 */

		public static function ecbb_sanitize_list_style($value)
		{
			$v = is_string($value) ? trim($value) : '';
			return in_array($v, ['style-1', 'style-2'], true) ? $v : 'style-1';
		}


		/**
		* Repeater rows with a non-empty `part` slug.
		*
		* @param array $parts Raw Bricks repeater rows.
		* @return array<int,array>
		*/

		public static function ecbb_sanitize_layout_template( array $settings ) {
			return [
				'template'    => self::ecbb_sanitize_template( $settings['layout_template'] ?? 'list' ),
				'item_chrome' => self::ecbb_sanitize_list_style( $settings['list_item_style'] ?? 'style-1' ),
			];
		}

		/**
		 * @param mixed $value Saved control value.
		 * @return string style-1 or style-2
		 */

		public static function ecbb_parts_clean(array $parts)
		{
			$out = [];
			foreach ($parts as $row) {
				if (! is_array($row) || ! isset($row['part']) || trim((string) $row['part']) === '') {
					continue;
				}
			unset($row['venue_link'], $row['organizer_link'], $row['cost_prefix'], $row['cost_suffix']);
			$out[] = $row;
		}
		return $out;
		}

		/**
		* Ordered `part` slugs from cleaned repeater rows (layout fingerprint).
		*
		* @param array $parts Raw repeater rows.
		* @return string[]
		*/

		public static function ecbb_parts_slugs(array $parts)
		{
			$clean = self::ecbb_parts_clean($parts);
			$out   = [];
			foreach ($clean as $row) {
				if (! is_array($row)) {
					continue;
				}
			$out[] = isset($row['part']) ? (string) $row['part'] : '';
		}
		return $out;
		}

		/**
		 * Preserve Bricks repeater row ids (and style fields) when replacing a stack with layout defaults.
		 *
		 * @param array<int,array<string,mixed>> $saved
		 * @param array<int,array<string,mixed>> $defaults
		 * @return array<int,array<string,mixed>>
		 */
		public static function ecbb_parts_preserve_bricks_rows( array $saved, array $defaults ) {
			$style_keys = [
				'id', 'ecbb_typography', 'ecbb_text_align', 'ecbb_background', 'ecbb_background_inner',
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

		/**
		 * Upgrade empty / foreign / legacy stacks to layout defaults while keeping Bricks row ids + styles.
		 *
		 * @param array    $parts       Raw repeater rows.
		 * @param string   $layout      style1|style2|grid
		 * @param callable $default_fn  Returns default rows for the layout.
		 * @return array|null           Replacement stack, or null to keep saved rows.
		 */
		public static function ecbb_upgrade_layout_parts( array $parts, $layout, callable $default_fn ) {
			if ( self::ecbb_parts_is_empty( $parts ) ) {
				return $default_fn();
			}

			// Only replace the old Bricks factory stack (categories + image-era rows), not user stacks.
			$factory = [ 'categories', 'title', 'date', 'venue', 'description', 'read_more' ];
			if ( self::ecbb_parts_slugs( $parts ) === $factory ) {
				return self::ecbb_parts_preserve_bricks_rows( $parts, $default_fn() );
			}

			return null;
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
		 * Whether a repeater row should render inside a layout meta list (uses cleaned slug).
		 *
		 * @param array<string,mixed> $item
		 * @param string              $layout style1|style2|grid
		 * @return bool
		 */
		public static function ecbb_is_layout_meta_row( array $item, $layout ) {
			$ui = (string) ( $item['part'] ?? '' );
			if ( in_array( $ui, [ 'title', 'description', 'read_more', 'image', 'categories' ], true ) ) {
				return false;
			}
			if ( $layout === 'grid' && $ui === 'date' && (string) ( $item['date_display'] ?? '' ) === 'range' ) {
				return false;
			}

			$row   = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
			$slug  = (string) ( $row['part'] ?? $ui );
			$slugs = [
				'date', 'event_date', 'event_time', 'event_day', 'venue', 'organizer', 'event_cost',
				'tags', 'event_link', 'event_tickets', 'event_rsvp',
				'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip',
				'venue_country', 'venue_phone', 'venue_website', 'event_map_link',
				'organizer_email', 'organizer_phone', 'organizer_website',
			];

			return in_array( $slug, $slugs, true ) || in_array( $ui, $slugs, true );
		}

		/**
		 * @param array<int,array{idx:int,item:array,price:bool}> $rows
		 */
		public static function ecbb_flush_meta_rows( $post, array $rows, $skin, $layout, callable $emit_meta ) {
			if ( $rows === [] ) {
				return;
			}

			if ( $layout === 'style1' ) {
				$primary = [];
				$price   = [];
				foreach ( $rows as $row ) {
					if ( ! empty( $row['price'] ) ) {
						$price[] = $row;
					} else {
						$primary[] = $row;
					}
				}
				self::ecbb_render_meta_lists( $post, $primary, $price, $skin, $layout, static function ( $ev, $item, $idx, $is_price ) use ( $emit_meta ) {
					$emit_meta( $ev, $item, $idx, (bool) $is_price );
				} );
				return;
			}

			$ul_class = ( $layout === 'grid' ) ? 'event-meta event-meta--grid' : 'ecbb-event-card__meta';
			echo '<ul class="' . esc_attr( $ul_class ) . '">';
			foreach ( $rows as $row ) {
				$emit_meta( $post, $row['item'], $row['idx'], false );
			}
			echo '</ul>';
		}

		/**
		 * Render repeater rows in saved order (respects drag-and-drop + mixed flow/meta rows).
		 *
		 * @param \WP_Post $post
		 * @param array    $parts
		 * @param string   $layout style1|style2|grid
		 * @param string   $skin   style1|style2|'' (grid uses layout for read-more chrome)
		 * @param callable $emit_part  function( $post, $item, $idx )
		 * @param callable $emit_meta  function( $post, $item, $idx, $price )
		 * @return void
		 */
		public static function ecbb_render_layout_parts_sequence( $post, array $parts, $layout, $skin, callable $emit_part, callable $emit_meta ) {
			if ( ! $post instanceof \WP_Post ) {
				return;
			}

			$meta_rows = [];
			$read_more = null;

			$flush_meta = static function () use ( $post, $layout, $skin, &$meta_rows, $emit_meta ) {
				if ( $meta_rows === [] ) {
					return;
				}
				self::ecbb_flush_meta_rows( $post, $meta_rows, $skin, $layout, $emit_meta );
				$meta_rows = [];
			};

			foreach ( $parts as $i => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$ui = (string) ( $item['part'] ?? '' );
				if ( $ui === '' ) {
					continue;
				}

				if ( $ui === 'read_more' ) {
					$flush_meta();
					$read_more = [ 'idx' => (int) $i, 'item' => $item ];
					continue;
				}

				if ( self::ecbb_shell_skip_part( $ui, $layout ) ) {
					continue;
				}

				if ( $ui === 'categories' && $layout === 'style2' ) {
					$flush_meta();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ecbb_render_style2_category( $post, $item, (int) $i, $skin );
					continue;
				}

				if ( $layout === 'grid' && $ui === 'date' && (string) ( $item['date_display'] ?? '' ) === 'range' ) {
					$flush_meta();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ecbb_render_grid_date_flow( $post, $item, (int) $i, $skin );
					continue;
				}

				if ( self::ecbb_is_layout_meta_row( $item, $layout ) ) {
					$row_clean = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
					$slug      = (string) ( $row_clean['part'] ?? $ui );
					$meta_rows[] = [
						'idx'   => (int) $i,
						'item'  => $item,
						'price' => ( $ui === 'event_cost' || $slug === 'event_cost' ),
					];
					continue;
				}

				$flush_meta();
				$emit_part( $post, $item, (int) $i );
			}

			$flush_meta();

			if ( $read_more !== null ) {
				$rm_skin = $layout === 'grid' ? 'grid' : (string) $skin;
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo self::ecbb_render_layout_read_more_shell( $post, $read_more['item'], $read_more['idx'], $rm_skin, $emit_part );
			}
		}

		/**
		 * Shell read-more chrome around standard repeater part output (btn_style / hover / Style tab).
		 *
		 * @param \WP_Post $post
		 * @param array    $item
		 * @param int      $idx
		 * @param string   $skin
		 * @param callable $emit_part
		 * @return string
		 */
		public static function ecbb_render_layout_read_more_shell( $post, array $item, $idx, $skin, callable $emit_part ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$skin = (string) $skin;

			ob_start();
			$emit_part( $post, $item, $idx );
			$html = trim( (string) ob_get_clean() );
			if ( $html === '' ) {
				return self::ecbb_render_layout_read_more( $post, $item, $idx, $skin );
			}

			$html = self::ecbb_read_more_merge_link_classes(
				$html,
				self::ecbb_layout_read_more_btn_class( $skin )
			);

			if ( $skin === 'style2' ) {
				return '<div class="ecbb-event-card__divider"></div><div class="ecbb-event-card__footer">' . $html . '</div>';
			}

			return $html;
		}

		/**
		* True when every row is empty / missing `part` (Bricks sometimes saves blank rows).
		*
		* @param array $parts Raw repeater rows.
		* @return bool
		*/

		public static function ecbb_parts_is_empty(array $parts)
		{
			return self::ecbb_parts_clean($parts) === [];
		}

		/**
		* Assign Bricks repeater row ids when missing (new defaults + legacy rows).
		*
		* @param array<int,array<string,mixed>> $rows Repeater rows.
		* @return array<int,array<string,mixed>>
		*/
		public static function ecbb_parts_assign_ids(array $rows)
		{
			foreach ($rows as $index => $row) {
				if (! is_array($row) || ! empty($row['id'])) {
					continue;
				}
			if (class_exists('\Bricks\Helpers') && method_exists('\Bricks\Helpers', 'generate_random_id')) {
				$rows[$index]['id'] = \Bricks\Helpers::generate_random_id(false);
			} else {
			$rows[$index]['id'] = 'ecbb-part-' . absint($index);
		}
		}
		return $rows;
		}

		// --- Hover normalization ---

		public static function ecbb_hover_interactive_types()
		{
			return ['title', 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp'];
		}

		/**
		* Part types that show Style-tab hover controls (links, buttons, image swap).
		*
		* Non-interactive parts (description, date/time, venue text, cost, etc.) are excluded.
		*
		* @return string[]
		*/

		public static function ecbb_hover_part_types()
		{
			return array_merge(
			self::ecbb_hover_interactive_types(),
			['image']
			);
		}

		/**
		* @param string $part Part slug.
		* @return bool
		*/

		public static function ecbb_part_has_hover($part)
		{
			return in_array((string) $part, self::ecbb_hover_part_types(), true);
		}

		/**
		* Saved values that mean hover styling is enabled (checkbox legacy + select).
		*
		* @return array<int|string|bool>
		*/

		public static function ecbb_hover_on_values()
		{
			return ['yes', true, 1, '1'];
		}

		/**
		* Whether a saved hover-toggle value means hover is on.
		*
		* @param mixed $value Raw `ecbb_use_hover` from a repeater row.
		* @return bool
		*/

		public static function ecbb_hover_is_on($value)
		{
			if (in_array($value, self::ecbb_hover_on_values(), true)) {
				return true;
			}
		if ($value === false || $value === 0 || $value === '0' || $value === 'no') {
			return false;
		}
		if ($value === null || $value === '') {
			return true;
		}
		return self::ecbb_is_truthy($value, true);
		}

		/**
		* Coerce legacy checkbox values to yes/no so Bricks always persists hover state.
		*
		* @param array<string,mixed> $row Repeater row.
		* @return array<string,mixed>
		*/

		public static function ecbb_norm_hover_row(array $row)
		{
			if (! array_key_exists('ecbb_use_hover', $row)) {
				return $row;
			}
		if ($row['ecbb_use_hover'] === null || $row['ecbb_use_hover'] === '') {
			$row['ecbb_use_hover'] = 'yes';
			return $row;
		}
		if ( self::ecbb_hover_has_custom_styles( $row ) ) {
			$row['ecbb_use_hover'] = 'yes';
			return $row;
		}
		$row['ecbb_use_hover'] = self::ecbb_hover_is_on($row['ecbb_use_hover']) ? 'yes' : 'no';
		return $row;
		}

		/**
		* @param array<int,array<string,mixed>> $rows Repeater rows.
		* @return array<int,array<string,mixed>>
		*/

		public static function ecbb_norm_parts_hover(array $rows)
		{
			foreach ($rows as $index => $row) {
				if (! is_array($row)) {
					continue;
				}
			$part = isset($row['part']) ? (string) $row['part'] : '';
			if (
			$part === ''
			|| ! self::ecbb_part_has_hover($part)
			) {
				continue;
			}
		$rows[$index] = self::ecbb_norm_hover_row($row);
		}
		return $rows;
		}

		/**
		* @param array<string,mixed> $settings Element settings.
		* @return array<string,mixed>
		*/

		public static function ecbb_norm_settings_hover(array $settings)
		{
			foreach (['parts_style1', 'parts_style2', 'parts_grid', 'parts'] as $key) {
				if (empty($settings[$key]) || ! is_array($settings[$key])) {
					continue;
				}
			$settings[$key] = self::ecbb_norm_parts_hover($settings[$key]);
		}
		return $settings;
		}

		/**
		* Resolve Event parts repeater rows for the active layout + list style.
		*
		* Reads `parts_grid` | `parts_style1` | `parts_style2` first, then falls back to
		* legacy `parts` so older saved elements keep working.
		*
		* @param array  $settings    Element or AJAX settings.
		* @param string $template    `layout_template`: list|grid.
		* @param mixed  $item_chrome `list_item_style`: style-1|style-2 (ignored when grid).
		* @return array<int,array<string,mixed>>
		*/
		public static function ecbb_resolve_parts(array $settings, $template, $item_chrome)
		{
			$prepare = static function ($parts) {
				if (! is_array($parts)) {
					return [];
				}
			$parts = self::ecbb_parts_assign_ids($parts);
			$parts = self::ecbb_norm_parts_hover($parts);
			return $parts;
		};

		$template = is_string($template) ? trim($template) : '';
		$template = self::ecbb_sanitize_template( $template );

		$item_chrome = self::ecbb_sanitize_list_style($item_chrome);

		$non_empty = static function ($key) use ($settings) {
			$v = $settings[$key] ?? null;
			if (! is_array($v) || [] === $v) {
				return null;
			}
		if ( self::ecbb_parts_is_empty($v) ) {
			return null;
		}
		return $v;
		};

		if ('grid' === $template) {
			$g = $non_empty('parts_grid');
			if (null !== $g) {
				return $prepare($g);
			}
		$legacy = $non_empty('parts');
		if (null !== $legacy) {
			return $prepare($legacy);
		}
		return [];
		}

		if ('style-2' === $item_chrome) {
			$s2 = $non_empty('parts_style2');
			if (null !== $s2) {
				return $prepare($s2);
			}
		$legacy = $non_empty('parts');
		if (null !== $legacy) {
			return $prepare($legacy);
		}
		return [];
		}

		$s1 = $non_empty('parts_style1');
		if (null !== $s1) {
			return $prepare($s1);
		}
		$legacy = $non_empty('parts');
		if (null !== $legacy) {
			return $prepare($legacy);
		}
		return [];
		}

		/**
		* Copy legacy widget-level event_cost_currency onto Event cost repeater rows (once per row).
		*
		* @param array<string,mixed> $settings Element settings.
		* @return array<string,mixed>
		*/
		public static function ecbb_migrate_cost_currency(array $settings)
		{
			if (! isset($settings['event_cost_currency'])) {
				return $settings;
			}

		$currency = self::ecbb_sanitize_cost_currency($settings['event_cost_currency']);

		foreach (['parts_style1', 'parts_style2', 'parts_grid', 'parts'] as $key) {
			if (empty($settings[$key]) || ! is_array($settings[$key])) {
				continue;
			}
		foreach ($settings[$key] as $index => $row) {
			if (! is_array($row) || (string) ($row['part'] ?? '') !== 'event_cost') {
				continue;
			}
		if (! isset($row['cost_currency']) || (string) $row['cost_currency'] === '') {
			$settings[$key][$index]['cost_currency'] = $currency;
		}
		}
		}

		return $settings;
		}

		// --- Render context ---

		/**
		* Store or read active widget settings during render (for cost currency fallback).
		*
		* Pass an array to set; pass nothing to read the current value.
		*
		* @param array<string,mixed>|null $settings Settings to store, or null to read only.
		* @return array<string,mixed>
		*/
		public static function ecbb_active_widget_settings( $settings = null ) {
			static $active = [];

			if ( is_array( $settings ) ) {
				$active = $settings;
			}

			return $active;
		}

		// --- Cost ---

		/**
		* Currency choices for the Event cost part (per repeater row).
		*
		* @return array<string,string>
		*/
		public static function ecbb_cost_currency_opts()
		{
			return [
			'default' => esc_html__('Site default', 'events-calendar-for-bricks'),
			'none'    => esc_html__('No currency symbol', 'events-calendar-for-bricks'),
			'USD'     => 'USD ($)',
			'EUR'     => 'EUR (â‚¬)',
			'GBP'     => 'GBP (Â£)',
			'CAD'     => 'CAD ($)',
			'AUD'     => 'AUD ($)',
			'INR'     => 'INR (â‚¹)',
			'JPY'     => 'JPY (Â¥)',
			'CNY'     => 'CNY (Â¥)',
			'CHF'     => 'CHF (Fr)',
			'SEK'     => 'SEK (kr)',
			'NOK'     => 'NOK (kr)',
			'DKK'     => 'DKK (kr)',
			'NZD'     => 'NZD ($)',
			'ZAR'     => 'ZAR (R)',
			'BRL'     => 'BRL (R$)',
			'MXN'     => 'MXN ($)',
			'SGD'     => 'SGD ($)',
			'HKD'     => 'HKD ($)',
			'AED'     => 'AED (Ø¯.Ø¥)',
			'SAR'     => 'SAR (ï·¼)',
			];
		}

		/**
		* @param mixed $value Saved control value.
		* @return string One of {@see self::ecbb_cost_currency_opts()}.
		*/

		public static function ecbb_sanitize_cost_currency($value)
		{
			$code = is_string($value) ? strtoupper(trim($value)) : '';
			if ($code === '') {
				return 'default';
			}
		$aliases = [ 'DEFAULT' => 'default', 'SYMBOL' => 'default', 'NONE' => 'none' ];
		if ( isset( $aliases[ $code ] ) ) {
			return $aliases[ $code ];
		}
		return array_key_exists($code, self::ecbb_cost_currency_opts()) ? $code : 'default';
		}

		/**
		* Whether one cost token is "free" (zero or common free labels).
		*
		* @param string $token Single price fragment or whole cost.
		* @return bool
		*/
		public static function ecbb_cost_is_free($token)
		{
			$t = trim(wp_strip_all_tags(html_entity_decode((string) $token, ENT_QUOTES, 'UTF-8')));
			if ($t === '') {
				return false;
			}
		$lower = strtolower($t);
		$labels = ['free', 'gratis', 'no cost', 'nocost', 'included'];
		foreach ($labels as $w) {
			if ($lower === $w) {
				return true;
			}
		}
		// Strip leading/trailing currency symbols and whitespace; test numeric zero.
		$num = preg_replace('/^[\p{Sc}\s]+/u', '', $t);
		$num = preg_replace('/[\p{Sc}\s]+$/u', '', $num);
		if ($num === '') {
			return false;
		}
		return (bool) preg_match('/^0(?:\.0+)?$/', $num);
		}

		/**
		* Display symbol for a widget currency code.
		*
		* @param string $currency_code Sanitized currency code.
		* @return string Empty when none / default should not force a symbol.
		*/
		public static function ecbb_cost_currency_symbol($currency_code)
		{
			$map = [
			'USD' => '$',
			'EUR' => 'â‚¬',
			'GBP' => 'Â£',
			'CAD' => '$',
			'AUD' => '$',
			'INR' => 'â‚¹',
			'JPY' => 'Â¥',
			'CNY' => 'Â¥',
			'CHF' => 'Fr',
			'SEK' => 'kr',
			'NOK' => 'kr',
			'DKK' => 'kr',
			'NZD' => '$',
			'ZAR' => 'R',
			'BRL' => 'R$',
			'MXN' => '$',
			'SGD' => '$',
			'HKD' => '$',
			'AED' => 'Ø¯.Ø¥',
			'SAR' => 'ï·¼',
			];
			$currency_code = self::ecbb_sanitize_cost_currency($currency_code);
			return isset($map[$currency_code]) ? $map[$currency_code] : '';
		}

		/**
		* Strip currency symbols from a single cost token.
		*
		* @param string $token Cost fragment.
		* @return string
		*/

		public static function ecbb_strip_cost_symbols($token)
		{
			$t = trim(wp_strip_all_tags(html_entity_decode((string) $token, ENT_QUOTES, 'UTF-8')));
			if ($t === '') {
				return '';
			}
		$t = preg_replace('/^[\p{Sc}\s]+/u', '', $t);
		$t = preg_replace('/[\p{Sc}\s]+$/u', '', $t);
		return trim((string) $t);
		}

		/**
		* Apply one currency symbol to a single cost token.
		*
		* @param string $token         Cost fragment.
		* @param string $currency_code Widget currency code.
		* @return string
		*/

		public static function ecbb_format_cost_token($token, $currency_code)
		{
			$token = trim(wp_strip_all_tags(html_entity_decode((string) $token, ENT_QUOTES, 'UTF-8')));
			if ($token === '') {
				return '';
			}
		if (self::ecbb_cost_is_free($token)) {
			return __('Free', 'events-calendar-for-bricks');
		}

		$currency_code = self::ecbb_sanitize_cost_currency($currency_code);
		if ($currency_code === 'default') {
			return $token;
		}
		if ($currency_code === 'none') {
			$plain = self::ecbb_strip_cost_symbols($token);
			return $plain !== '' ? $plain : $token;
		}

		$symbol = self::ecbb_cost_currency_symbol($currency_code);
		$amount = self::ecbb_strip_cost_symbols($token);
		if ($amount === '') {
			return $token;
		}
		if ($symbol === '') {
			return $amount;
		}

		return $symbol . $amount;
		}

		/**
		* Apply widget currency formatting to a cost label.
		*
		* @param string $cost_text     Plain cost label.
		* @param string $currency_code Widget currency code.
		* @return string
		*/

		public static function ecbb_apply_cost_currency($cost_text, $currency_code)
		{
			$cost_text = trim(wp_strip_all_tags(html_entity_decode((string) $cost_text, ENT_QUOTES, 'UTF-8')));
			if ($cost_text === '') {
				return '';
			}

		$currency_code = self::ecbb_sanitize_cost_currency($currency_code);
		if ($currency_code === 'default') {
			return $cost_text;
		}

		if (self::ecbb_cost_is_free($cost_text)) {
			return __('Free', 'events-calendar-for-bricks');
		}

		if (preg_match('/^(.+?)([-\x{2013}\x{2014}])(.+)$/u', $cost_text, $m)) {
			$left  = self::ecbb_format_cost_token(trim($m[1]), $currency_code);
			$right = self::ecbb_format_cost_token(trim($m[3]), $currency_code);
			if ($left !== '' && $right !== '') {
				if (strcasecmp($left, $right) === 0) {
					return $left;
				}
			return $left . ' - ' . $right;
		}
		}

		return self::ecbb_format_cost_token($cost_text, $currency_code);
		}

		/**
		* Resolve cost currency for one Event cost repeater row.
		*
		* @param array<string,mixed> $item Repeater row.
		* @return string
		*/
		public static function ecbb_resolve_cost_currency(array $item = [])
		{
			if (isset($item['cost_currency']) && (string) $item['cost_currency'] !== '') {
				return self::ecbb_sanitize_cost_currency($item['cost_currency']);
			}

		$settings = self::ecbb_active_widget_settings();
		if (isset($settings['event_cost_currency'])) {
			return self::ecbb_sanitize_cost_currency($settings['event_cost_currency']);
		}

		return 'default';
		}

		/**
		* Build cost label: "Free", a single amount, or "min – max" for ranges.
		*
		* @param int                 $post_id Event post ID.
		* @param array<string,mixed> $item    Repeater row (legacy fallback only).
		* @return string Plain text (escaped by caller).
		*/
		public static function ecbb_format_cost_display($post_id, array $item = [])
		{
			$post_id = (int) $post_id;
			if ($post_id < 1) {
				return '';
			}

		$currency_code = self::ecbb_resolve_cost_currency($item);
		$with_currency = $currency_code === 'default';

		$formatted = '';
		if (function_exists('tribe_get_cost')) {
			$formatted = trim(wp_strip_all_tags(html_entity_decode((string) \tribe_get_cost($post_id, $with_currency), ENT_QUOTES, 'UTF-8')));
		}

		$raw = trim(wp_strip_all_tags(html_entity_decode((string) get_post_meta($post_id, '_EventCost', true), ENT_QUOTES, 'UTF-8')));

		$cost = $formatted !== '' ? $formatted : $raw;
		if ($cost === '') {
			// No cost set on the event; treat as free (matches TEC "no cost" behaviour).
			return __('Free', 'events-calendar-for-bricks');
		}

		if (self::ecbb_cost_is_free($cost)) {
			return __('Free', 'events-calendar-for-bricks');
		}

		if (preg_match('/^(.+?)([-\x{2013}\x{2014}])(.+)$/u', $cost, $m)) {
			$left  = trim($m[1]);
			$right = trim($m[3]);
			if ($left !== '' && $right !== '') {
				if (strcasecmp($left, $right) === 0) {
					$cost = self::ecbb_cost_is_free($left)
					? __('Free', 'events-calendar-for-bricks')
					: $left;
				} elseif (self::ecbb_cost_is_free($left) && self::ecbb_cost_is_free($right)) {
				$cost = __('Free', 'events-calendar-for-bricks');
			} else {
			$cost = $left . ' - ' . $right;
		}
		}
		}

		return self::ecbb_apply_cost_currency($cost, $currency_code);
		}

		public static function ecbb_layout_cost_label($post_id, array $item = [])
		{
			$cost = self::ecbb_format_cost_display($post_id, $item);
			if ($cost === '') {
				return '';
			}
			if (self::ecbb_cost_is_free($cost)) {
				return __('Free', 'events-calendar-for-bricks');
			}
			if (stripos($cost, 'from') !== 0) {
				return sprintf(
					/* translators: %s: event price */
					__('From %s', 'events-calendar-for-bricks'),
					$cost
				);
			}
			return $cost;
		}

		/**
		* Build inner HTML for taxonomy terms (categories / tags).
		*
		* @param \WP_Term[] $terms      Terms to render.
		* @param array      $item       Part settings row.
		* @param string     $style_attr Inline CSS declarations (no style= wrapper).
		* @param string     $skin       List skin: style1, style2, or empty.
		* @param string     $part       Part slug: categories|tags.
		* @return string HTML or empty when no terms.
		*/

		// --- Event field text (venue / organizer / link details) ---

		/**
		 * Part slug => resolver for {@see ecbb_part_detail_text()}.
		 *
		 * @return array<string, callable(int):string>
		 */
		private static function ecbb_part_detail_resolver_map() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}

			$map = [
				'venue_full_address'  => [ self::class, 'ecbb_resolve_detail_venue_full_address' ],
				'venue_street'        => [ self::class, 'ecbb_resolve_detail_venue_street' ],
				'venue_city'          => [ self::class, 'ecbb_resolve_detail_venue_city' ],
				'venue_state'         => [ self::class, 'ecbb_resolve_detail_venue_state' ],
				'venue_zip'           => [ self::class, 'ecbb_resolve_detail_venue_zip' ],
				'venue_country'       => [ self::class, 'ecbb_resolve_detail_venue_country' ],
				'venue_phone'         => [ self::class, 'ecbb_resolve_detail_venue_phone' ],
				'venue_website'       => [ self::class, 'ecbb_resolve_detail_venue_website' ],
				'event_map_link'      => [ self::class, 'ecbb_resolve_detail_event_map_link' ],
				'event_website'       => [ self::class, 'ecbb_resolve_detail_event_website' ],
				'event_phone'         => [ self::class, 'ecbb_resolve_detail_event_phone' ],
				'organizer_email'     => [ self::class, 'ecbb_resolve_detail_organizer_email' ],
				'organizer_phone'     => [ self::class, 'ecbb_resolve_detail_organizer_phone' ],
				'organizer_website'   => [ self::class, 'ecbb_resolve_detail_organizer_website' ],
			];

			return $map;
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_full_address( $event_id ) {
			return self::ecbb_venue_full_address_text( $event_id );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_street( $event_id ) {
			if ( function_exists( 'tribe_get_address' ) ) {
				$t = trim( (string) \tribe_get_address( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, '_VenueAddress', true ) );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_city( $event_id ) {
			if ( function_exists( 'tribe_get_city' ) ) {
				$t = trim( (string) \tribe_get_city( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, '_VenueCity', true ) );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_state( $event_id ) {
			if ( function_exists( 'tribe_get_province' ) ) {
				$t = trim( (string) \tribe_get_province( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			if ( function_exists( 'tribe_get_state' ) ) {
				$t = trim( (string) \tribe_get_state( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			$s = get_post_meta( $vid, '_VenueStateProvince', true );
			if ( $s === '' || $s === null ) {
				$s = get_post_meta( $vid, '_VenueState', true );
			}
			return trim( (string) $s );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_zip( $event_id ) {
			if ( function_exists( 'tribe_get_zip' ) ) {
				$t = trim( (string) \tribe_get_zip( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, '_VenueZip', true ) );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_country( $event_id ) {
			if ( function_exists( 'tribe_get_country' ) ) {
				$t = trim( (string) \tribe_get_country( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, '_VenueCountry', true ) );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_phone( $event_id ) {
			if ( function_exists( 'tribe_get_phone' ) ) {
				$t = trim( (string) \tribe_get_phone( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, '_VenuePhone', true ) );
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_venue_website( $event_id ) {
			if ( function_exists( 'tribe_get_venue_website_url' ) ) {
				$t = trim( (string) \tribe_get_venue_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid > 0 ) {
				$t = trim( (string) get_post_meta( $vid, '_VenueURL', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_event_map_link( $event_id ) {
			if ( function_exists( 'tribe_get_map_link_url' ) ) {
				return trim( (string) \tribe_get_map_link_url( $event_id ) );
			}
			if ( function_exists( 'tribe_get_map_link' ) ) {
				$raw = (string) \tribe_get_map_link( $event_id );
				if ( preg_match( '/href=[\"\\\']([^\"\\\']+)[\"\\\']/', $raw, $m ) ) {
					return trim( $m[1] );
				}
			}
			return '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_event_website( $event_id ) {
			if ( function_exists( 'tribe_get_event_website_url' ) ) {
				$t = trim( (string) \tribe_get_event_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$m = get_post_meta( $event_id, '_EventUrl', true );
			return $m ? trim( (string) $m ) : '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_event_phone( $event_id ) {
			$m = get_post_meta( $event_id, '_EventPhone', true );
			return $m ? trim( wp_strip_all_tags( (string) $m ) ) : '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_organizer_email( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_email' ) ) {
				$t = trim( (string) \tribe_get_organizer_email( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerEmail', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_organizer_phone( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_phone' ) ) {
				$t = trim( (string) \tribe_get_organizer_phone( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerPhone', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/** @param int $event_id Event post ID. @return string */
		private static function ecbb_resolve_detail_organizer_website( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_website_url' ) ) {
				$t = trim( (string) \tribe_get_organizer_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerWebsite', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/**
		 * Plain-text value for a venue/organizer/event detail part slug.
		 *
		 * @param int    $event_id Event post ID.
		 * @param string $part     Detail part slug.
		 * @return string Unescaped plain text; caller must escape for HTML.
		 */
		public static function ecbb_part_detail_text( $event_id, $part ) {
			$event_id = (int) $event_id;
			$part     = (string) $part;
			if ( $event_id < 1 ) {
				return '';
			}

			$resolvers = self::ecbb_part_detail_resolver_map();
			if ( ! isset( $resolvers[ $part ] ) ) {
				return '';
			}

			return (string) call_user_func( $resolvers[ $part ], $event_id );
		}

		/**
		* PHP date() format with lowercase am/pm (`a`) instead of uppercase (`A`).
		*
		* @param string $format PHP date format string.
		* @return string
		*/

		// --- Venue ---

		/** @var array<int,int> */
		private static $ecbb_meta_venue_id_cache = array();

		/** @var array<int,int> */
		private static $ecbb_meta_organizer_id_cache = array();

		/**
		 * Cached `_EventVenueID` for an event (one meta read per event per request).
		 *
		 * @param int $event_id Event post ID.
		 * @return int Venue post ID or 0.
		 */
		private static function ecbb_event_meta_venue_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$ecbb_meta_venue_id_cache ) ) {
				self::$ecbb_meta_venue_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventVenueID', true );
			}
			return self::$ecbb_meta_venue_id_cache[ $event_id ];
		}

		/**
		 * Cached `_EventOrganizerID` for an event (one meta read per event per request).
		 *
		 * @param int $event_id Event post ID.
		 * @return int Organizer post ID or 0.
		 */
		private static function ecbb_event_meta_organizer_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$ecbb_meta_organizer_id_cache ) ) {
				self::$ecbb_meta_organizer_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			}
			return self::$ecbb_meta_organizer_id_cache[ $event_id ];
		}

		public static function ecbb_venue_id($event_id)
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return 0;
			}
		if (function_exists('tribe_get_venue_id')) {
			$venue_id = (int) \tribe_get_venue_id($event_id);
			if ($venue_id > 0) {
				return $venue_id;
			}
		}
		return self::ecbb_event_meta_venue_id( $event_id );
		}

		/**
		* Full venue address (TEC helper or street/city/state/zip/country parts).
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_venue_name($event_id)
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

		$venue = '';
		if (function_exists('tribe_get_venue')) {
			$venue = trim((string) \tribe_get_venue($event_id));
		}
		if ($venue === '') {
			$venue_id = self::ecbb_event_meta_venue_id( $event_id );
			if ($venue_id) {
				$venue = trim((string) get_the_title($venue_id));
			}
		}

		return $venue;
		}

		/**
		* Linked venue post ID for an event.
		*
		* @param int $event_id Event post ID.
		* @return int Venue post ID or 0.
		*/

		private static function ecbb_venue_full_address_text( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}

			$address_ids = [ $event_id ];
			$venue_id    = self::ecbb_venue_id( $event_id );
			if ( $venue_id > 0 ) {
				array_unshift( $address_ids, $venue_id );
			}
			$address_ids = array_values( array_unique( $address_ids ) );

			if ( function_exists( 'tribe_get_full_address' ) ) {
				foreach ( $address_ids as $try_id ) {
					$raw = (string) \tribe_get_full_address( $try_id );
					$raw = preg_replace( '/<br\s*\/?>/i', ', ', $raw );
					$t   = trim( wp_strip_all_tags( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ) ) );
					if ( $t !== '' ) {
						return $t;
					}
				}
			}

			$bits = array_filter(
				array_map(
					'trim',
					[
						self::ecbb_part_detail_text( $event_id, 'venue_street' ),
						self::ecbb_part_detail_text( $event_id, 'venue_city' ),
						trim(
							self::ecbb_part_detail_text( $event_id, 'venue_state' )
							. ' '
							. self::ecbb_part_detail_text( $event_id, 'venue_zip' )
						),
						self::ecbb_part_detail_text( $event_id, 'venue_country' ),
					]
				)
			);

			return implode( ', ', $bits );
		}

		/**
		* Plain-text full venue address for an event.
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_venue_address($event_id)
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

		$address_ids = [$event_id];
		$venue_id    = self::ecbb_venue_id($event_id);
		if ($venue_id > 0) {
			array_unshift($address_ids, $venue_id);
		}
		$address_ids = array_values(array_unique($address_ids));

		if (function_exists('tribe_get_full_address')) {
			foreach ($address_ids as $try_id) {
				$raw = (string) \tribe_get_full_address($try_id);
				$raw = preg_replace('/<br\s*\/?>/i', ', ', $raw);
				$t   = trim(wp_strip_all_tags(html_entity_decode($raw, ENT_QUOTES, 'UTF-8')));
				if ($t !== '') {
					$name = self::ecbb_venue_name($event_id);
					if ($name !== '' && strcasecmp($t, $name) === 0) {
						continue;
					}
				if ($name !== '' && stripos($t, $name) === 0) {
					$t = trim(preg_replace('/^' . preg_quote($name, '/') . '\s*,\s*/i', '', $t));
				}
			if ($t !== '') {
				return $t;
			}
		}
		}
		}

		if ($venue_id > 0) {
			$state = get_post_meta($venue_id, '_VenueStateProvince', true);
			if ($state === '' || $state === null) {
				$state = get_post_meta($venue_id, '_VenueState', true);
			}
		$bits = array_filter(
		array_map(
		'trim',
		[
		(string) get_post_meta($venue_id, '_VenueAddress', true),
		(string) get_post_meta($venue_id, '_VenueCity', true),
		trim((string) $state . ' ' . (string) get_post_meta($venue_id, '_VenueZip', true)),
		(string) get_post_meta($venue_id, '_VenueCountry', true),
		]
		)
		);
		if ($bits !== []) {
			return implode(', ', $bits);
		}
		}

		return self::ecbb_venue_full_address_text( $event_id );
		}

		/**
		* Plain-text venue name with full address for an event.
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_venue_name_addr($event_id)
		{
			$name    = self::ecbb_venue_name($event_id);
			$address = self::ecbb_venue_address($event_id);

			if ($name === '' && $address === '') {
				return '';
			}
		if ($name === '') {
			return $address;
		}
		if ($address === '') {
			return $name;
		}

		return $name . ', ' . $address;
		}

		/**
		* Plain-text venue name with state / province for an event.
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_venue_name_state($event_id)
		{
			$name  = self::ecbb_venue_name($event_id);
			$state = self::ecbb_part_detail_text($event_id, 'venue_state');
			$name  = trim((string) $name);
			$state = trim((string) $state);

			if ($name === '' && $state === '') {
				return '';
			}
		if ($name === '') {
			return $state;
		}
		if ($state === '') {
			return $name;
		}

		return $name . ', ' . $state;
		}

		public static function ecbb_venue_name_city($event_id)
		{
			$name = self::ecbb_venue_name($event_id);
			$city = self::ecbb_part_detail_text($event_id, 'venue_city');
			$name = trim((string) $name);
			$city = trim((string) $city);

			if ($name === '' && $city === '') {
				return '';
			}
			if ($name === '') {
				return $city;
			}
			if ($city === '') {
				return $name;
			}

			return $name . ', ' . $city;
		}

		/**
		* Resolved venue display format for a repeater row (Style 2 defaults to name + state).
		*
		* @param array<string,mixed> $item Repeater row.
		* @param string              $skin Loop skin: '' or 'style1' or 'style2'.
		* @return string
		*/

		public static function ecbb_venue_display_key(array $item, $skin = '')
		{
			$display = isset($item['venue_display']) ? (string) $item['venue_display'] : '';
			if ($display === '' || $display === 'name_and_address') {
				$skin = (string) $skin;
				if ($skin === 'style1' || $skin === 'style2') {
					return 'name_and_city';
				}
				return 'name';
			}

		return $display;
		}

		/**
		* Whether the venue repeater row should render name + full address.
		*
		* @param array<string,mixed> $item Repeater row.
		* @param string              $skin Loop skin: '' or 'style1' or 'style2'.
		* @return bool
		*/

		public static function ecbb_venue_uses_full(array $item, $skin = '')
		{
			$display = self::ecbb_venue_display_key($item, $skin);

			return in_array($display, ['full_details', 'name_and_address'], true);
		}

		/**
		* Plain-text venue output for the consolidated venue repeater row.
		*
		* @param int                 $event_id Event post ID.
		* @param array<string,mixed> $item     Repeater row.
		* @param string              $skin     Loop skin: '' or 'style1' or 'style2'.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_venue_text($event_id, array $item, $skin = '')
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

		if (self::ecbb_venue_uses_full($item, $skin)) {
			return self::ecbb_venue_name_addr($event_id);
		}

		$display = self::ecbb_venue_display_key($item, $skin);
		if ($display === 'name_and_state') {
			return self::ecbb_venue_name_state($event_id);
		}
		if ($display === 'name_and_city') {
			return self::ecbb_venue_name_city($event_id);
		}

		return self::ecbb_venue_name($event_id);
		}

		/**
		* Markup for the consolidated venue repeater row (`part` = venue).
		*
		* @param \WP_Post            $post  Event post.
		* @param array<string,mixed> $item  Repeater row.
		* @param int                 $idx   Row index.
		* @param string              $style Inline style attribute value (contents only), or empty.
		* @param string              $skin  Loop skin: '' or 'style1' or 'style2'.
		* @return string Empty when nothing to show.
		*/

		// --- Organizer ---

		public static function ecbb_organizer_name($event_id)
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

		$organizer = '';
		if (function_exists('tribe_get_organizer')) {
			$organizer = trim((string) \tribe_get_organizer($event_id));
		}
		if ($organizer === '') {
			$organizer_id = self::ecbb_event_meta_organizer_id( $event_id );
			if ($organizer_id) {
				$organizer = trim((string) get_the_title($organizer_id));
			}
		}

		return $organizer;
		}

		/**
		* Plain-text organizer name with email, phone, and website for an event.
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_organizer_full($event_id)
		{
			$bits = array_filter(
			[
			self::ecbb_organizer_name($event_id),
			self::ecbb_part_detail_text($event_id, 'organizer_email'),
			self::ecbb_part_detail_text($event_id, 'organizer_phone'),
			self::ecbb_part_detail_text($event_id, 'organizer_website'),
			]
			);
			$bits = array_map('trim', $bits);
			$bits = array_filter($bits);

			return implode(', ', $bits);
		}

		/**
		* Whether the organizer repeater row should render full organizer details.
		*
		* @param array<string,mixed> $item Repeater row.
		* @param string              $skin Loop skin: '' or 'style1' or 'style2'.
		* @return bool
		*/

		public static function ecbb_organizer_uses_full(array $item, $skin = '')
		{
			$display = isset($item['organizer_display']) ? (string) $item['organizer_display'] : 'full_details';
			if ($display === '') {
				$display = 'full_details';
			}

		return $display === 'full_details';
		}

		/**
		* Plain-text organizer output for the consolidated organizer repeater row.
		*
		* @param int                 $event_id Event post ID.
		* @param array<string,mixed> $item     Repeater row.
		* @param string              $skin     Loop skin: '' or 'style1' or 'style2'.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_organizer_text($event_id, array $item, $skin = '')
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

		if (self::ecbb_organizer_uses_full($item, $skin)) {
			return self::ecbb_organizer_full($event_id);
		}

		return self::ecbb_organizer_name($event_id);
		}

		/**
		* Markup for the consolidated organizer repeater row (`part` = organizer).
		*
		* @param \WP_Post            $post  Event post.
		* @param array<string,mixed> $item  Repeater row.
		* @param int                 $idx   Row index.
		* @param string              $style Inline style attribute value (contents only), or empty.
		* @param string              $skin  Loop skin: '' or 'style1' or 'style2'.
		* @return string Empty when nothing to show.
		*/

		// --- Date & time ---

		public static function ecbb_part_date_php_fmt($part, array $item)
		{
			$preset = isset($item['date_format_preset']) ? (string) $item['date_format_preset'] : '';
			$custom = isset($item['date_format_custom']) ? trim((string) $item['date_format_custom']) : '';
			$custom = preg_replace('/[\x00-\x1F\x7F<>]/', '', wp_strip_all_tags($custom));
			$part   = (string) $part;

			if ($part === 'event_time') {
				if ($preset === 'custom' && $custom !== '') {
					return $custom;
				}
				if (class_exists('ECBB_Styles', false)) {
					$mapped = \ECBB_Styles::ecbb_date_php_format($preset, 'event_time');
					if ($mapped !== null && $mapped !== '') {
						return $mapped;
					}
				}
				return (string) get_option('time_format');
			}

			if ($preset === 'custom') {
				return $custom;
			}

		if (class_exists('ECBB_Styles', false)) {
			$mapped = \ECBB_Styles::ecbb_date_php_format($preset, $part);
			if ($mapped !== null && $mapped !== '') {
				return $mapped;
			}
		}

		$site_formats = [
			'site_date' => 'date_format',
			'site_time' => 'time_format',
		];
		if ( isset( $site_formats[ $preset ] ) ) {
			return (string) get_option( $site_formats[ $preset ] );
		}

		if ($preset === 'site_date_time') {
			return (string) get_option('date_format') . ' ' . (string) get_option('time_format');
		}

		if ($part === 'event_date') {
			return (string) get_option('date_format');
		}

		return '';
		}

		/**
		* Plain-text venue name for an event.
		*
		* @param int $event_id Event post ID.
		* @return string Unescaped plain text; caller must escape for HTML.
		*/

		public static function ecbb_time_fmt_lower($format)
		{
			$format = (string) $format;
			if ($format === '') {
				return '';
			}

		return str_replace('A', 'a', $format);
		}

		/**
		* Lowercase AM/PM markers in a formatted time string (day names unchanged).
		*
		* @param string $time_str Formatted time or time range.
		* @return string
		*/

		public static function ecbb_time_lower_am($time_str)
		{
			$time_str = trim((string) $time_str);
			if ($time_str === '') {
				return '';
			}

		return (string) preg_replace_callback(
		'/\b(AM|PM)\b/u',
		static function ($matches) {
			return strtolower($matches[0]);
		},
		$time_str
		);
		}

		/**
		* Structured values for the "Day & time range" part so CSS can style day vs time.
		*
		* @param int   $post_id Event post ID.
		* @param array $item    Unused (signature kept for callers).
		* @return array{day:string,time:string} Both may be empty strings.
		*/

		public static function ecbb_build_day_time_parts($post_id, array $item)
		{
			$post_id = (int) $post_id;
			$start_raw = (string) get_post_meta($post_id, '_EventStartDate', true);
			$end_raw   = (string) get_post_meta($post_id, '_EventEndDate', true);
			$start_ts  = $start_raw ? strtotime($start_raw) : false;
			if (! $start_ts) {
				return ['day' => '', 'time' => ''];
			}
		$end_ts = $end_raw ? strtotime($end_raw) : $start_ts;
		if (! $end_ts) {
			$end_ts = $start_ts;
		}

		$all_day = function_exists('tribe_event_is_all_day') && \tribe_event_is_all_day($post_id);

		$day_fmt  = 'l';
		$time_fmt = self::ecbb_time_fmt_lower((string) get_option('time_format'));

		$day_str = date_i18n($day_fmt, $start_ts);
		$day_str = trim(wp_strip_all_tags($day_str));
		if ($day_str === '') {
			return ['day' => '', 'time' => ''];
		}

		if ($all_day) {
			return ['day' => $day_str, 'time' => ''];
		}

		$t_start = '';
		$t_end   = '';

		if (function_exists('tribe_get_start_time')) {
			$t_start = (string) \tribe_get_start_time($post_id, $time_fmt);
		}
		if ($t_start === '' && function_exists('tribe_get_start_date')) {
			$t_start = (string) \tribe_get_start_date($post_id, true, $time_fmt);
		}
		if ($t_start === '') {
			$t_start = date_i18n($time_fmt, $start_ts);
		}

		if (function_exists('tribe_get_end_time')) {
			$t_end = (string) \tribe_get_end_time($post_id, $time_fmt);
		}
		if ($t_end === '' && function_exists('tribe_get_end_date')) {
			$t_end = (string) \tribe_get_end_date($post_id, true, $time_fmt);
		}
		if ($t_end === '') {
			$t_end = date_i18n($time_fmt, $end_ts);
		}

		$t_start = self::ecbb_time_lower_am(trim(wp_strip_all_tags($t_start)));
		$t_end   = self::ecbb_time_lower_am(trim(wp_strip_all_tags($t_end)));

		if ($t_start === '') {
			return ['day' => $day_str, 'time' => ''];
		}
		if ($t_end === '' || $t_start === $t_end) {
			return ['day' => $day_str, 'time' => $t_start];
		}

		return ['day' => $day_str, 'time' => $t_start . ' - ' . $t_end];
		}

		/**
		* Whether button chrome applies (btn_style toggle).
		*
		* @param array<string,mixed> $item Repeater row.
		* @return bool
		*/

		// --- Part DOM (classes, ids, wrap attrs) ---

		public static function ecbb_part_index_class($idx)
		{
			return 'ecbb-p' . absint($idx);
		}

		/**
		* Whether the title row links to the event (Bricks checkbox).
		*
		* @param array<string,mixed> $item Repeater row.
		* @return bool
		*/

		public static function ecbb_part_dom_id(array $item, $idx)
		{
			if (! empty($item['id'])) {
				return (string) $item['id'];
			}
		return (string) absint($idx);
		}

		/**
		* data-field-id attribute for Bricks live repeater styling in the builder.
		*
		* @param array<string,mixed> $item Repeater row.
		* @param int                 $idx  Row index.
		* @return string HTML attribute fragment (leading space + data-field-id), or empty.
		*/

		public static function ecbb_part_dom_id_attr(array $item, $idx)
		{
			$id = self::ecbb_part_dom_id($item, $idx);
			if ($id === '') {
				return '';
			}
		return ' data-field-id="' . esc_attr($id) . '"';
		}

		/**
		* Optional inline style + Bricks field id attributes for a part wrapper.
		*
		* @param array<string,mixed> $item  Repeater row.
		* @param int                 $idx   Row index.
		* @param string              $style Inline style declaration string (no style="" wrapper).
		* @return string HTML attribute fragment.
		*/

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
		* Assign Bricks repeater row ids when missing (new defaults + legacy rows).
		*
		* @param array<int,array<string,mixed>> $rows Repeater rows.
		* @return array<int,array<string,mixed>>
		*/

		public static function ecbb_title_link_active(array $item)
		{
			return self::ecbb_is_truthy($item['link'] ?? false, false);
		}

		/**
		* Outer classes for a rendered part wrapper.
		*
		* @param string              $part  Part slug.
		* @param int                 $idx   Row index.
		* @param string              $skin  ''|style1|style2.
		* @param array<string,mixed> $item  Repeater row (optional; used for hover class).
		* @return string        Space-separated classes (not escaped).
		*/

		public static function ecbb_btn_style_active( array $item ) {
			return self::ecbb_parse_bricks_checkbox( $item['btn_style'] ?? false );
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
		 * Merge layout/skin classes onto the first anchor in read-more markup.
		 *
		 * @param string $html          Part HTML.
		 * @param string $extra_classes Classes to append.
		 * @return string
		 */
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

		/**
		* Inner markup for read more / tickets / RSVP.
		*
		* Renders a clickable link when hover effects or button styles are on; otherwise plain text.
		*
		* @param array<string,mixed> $item         Repeater row.
		* @param string              $href         Destination URL.
		* @param string              $label        Visible label.
		* @param string              $link_attr    Inline attributes for the link.
		* @param string              $extra_attrs  Extra attributes before the link attr (e.g. target, rel).
		* @return string HTML (not escaped as a whole).
		*/

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
		self::ecbb_part_has_hover($ui_part)
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
		if ( self::ecbb_hover_style_active( $item ) ) {
			$hover_fg = self::ecbb_norm_hover_paint_color( $item['ecbb_hover_color'] ?? ( $item['hover_color'] ?? '' ) );
			if ( $hover_fg !== '' ) {
				$classes .= ' ecbb-has-hover-fg';
			}
			$hover_bg = self::ecbb_norm_hover_paint_color( $item['ecbb_hover_background'] ?? '' );
			if ( $hover_bg !== '' ) {
				$classes .= ' ecbb-has-hover-bg';
			}
		}
		}
		return $classes;
		}

		/**
		* Stable repeater row id for Bricks fieldId CSS (matches Bricks\Assets::generate_inline_css_from_repeater).
		*
		* @param array<string,mixed> $item Repeater row.
		* @param int                 $idx  Row index fallback.
		* @return string
		*/

		// --- Hover runtime (classes & CSS) ---

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
				if ( self::ecbb_norm_hover_paint_color( $item[ $key ] ) !== '' ) {
					return true;
				}
			}

			$hover_td = isset( $item['ecbb_hover_text_decoration'] ) ? (string) $item['ecbb_hover_text_decoration'] : '';
			if ( $hover_td !== '' ) {
				return true;
			}

			$hover_anim = isset( $item['ecbb_hover_animation'] ) ? (string) $item['ecbb_hover_animation'] : '';
			return $hover_anim !== '';
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
			$fg    = self::ecbb_norm_hover_paint_color( $item['ecbb_hover_color'] ?? ( $item['hover_color'] ?? '' ) );
			if ( $fg !== '' ) {
				$decls[] = '--ecbb-hover-fg:' . $fg;
			}
			$bg = self::ecbb_norm_hover_paint_color( $item['ecbb_hover_background'] ?? '' );
			if ( $bg !== '' ) {
				$decls[] = '--ecbb-hover-bg:' . $bg;
			}

			return $decls !== [] ? implode( ';', $decls ) . ';' : '';
		}

		public static function ecbb_hover_style_active(array $item)
		{
			$ui_part = isset($item['part']) ? (string) $item['part'] : '';
			if (! self::ecbb_part_has_hover($ui_part)) {
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
		return self::ecbb_hover_is_on($item['ecbb_use_hover']);
		}

		/**
		* Normalize Bricks checkbox / toggle values saved on repeater rows.
		*
		* @param mixed $value   Raw setting.
		* @param bool  $default Default when value is null (not when key is absent).
		* @return bool
		*/

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

		/**
		* Scoped CSS for hover motion that animates in place (rest = natural position).
		*
		* @param string $scope_sel Full selector (e.g. .scope .ecbb-p0).
		* @param string $anim      One of fade_in_up, fade_in_right, â€¦
		* @return array{base:string, hover:string}
		*/

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
		 * @param mixed $value Saved `layout_template` control value.
		 * @return string list|grid
		 */

		// --- Images ---

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
			$opts[$slug] = $slug . ' (' . $w . 'Ã—' . $h . ')';
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

		/**
		* @param string $slug   Saved size slug or empty for fallback.
		* @param string $fallback Used when empty or invalid.
		* @return string
		*/

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

		/**
		* Nine-point alignment (Bricks-style) for object-position on images.
		*
		* @return array<string, string>
		*/

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

		/**
		* @param string $key Short key (tl, mc, â€¦) or empty.
		* @return string CSS object-position value or empty when default.
		*/

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

		/**
		* Interactive parts with hover controls (title, chips, buttons). Excludes image.
		*
		* @return string[]
		*/

		public static function ecbb_image_dual_layer(array $item)
		{
			if (! self::ecbb_hover_style_active($item)) {
				return false;
			}
		$base = self::ecbb_sanitize_image_size($item['image_size'] ?? '', 'large');
		$raw  = isset($item['image_size_hover']) ? trim((string) $item['image_size_hover']) : '';
		if ($raw === '') {
			return false;
		}
		$hover = self::ecbb_sanitize_image_size($raw, $base);
		return $hover !== $base;
		}

		/**
		* Markup for featured image (single or dual size for hover).
		* Sizing and object-position are output via scoped CSS, not inline on `<img>`.
		*
		* @param int                $thumb_id Attachment ID.
		* @param array<string,mixed> $item    Repeater row.
		* @return string HTML or empty.
		*/

		// --- Links & terms ---

		public static function ecbb_action_link_html( array $item, $href, $label, $link_attr = '', $extra_attrs = '' ) {
			$part       = isset( $item['part'] ) ? (string) $item['part'] : '';
			$force_link = in_array( $part, [ 'read_more', 'event_tickets', 'event_rsvp' ], true );

			if ( ! $force_link && ! self::ecbb_hover_style_active( $item ) && ! self::ecbb_btn_style_active( $item ) ) {
				return '<span class="ecbb-event__plain">' . esc_html( $label ) . '</span>';
			}

			return '<a class="ecbb-event__link" href="' . esc_url( $href ) . '"' . $extra_attrs . $link_attr . '>' . esc_html( $label ) . '</a>';
		}

		/**
		* Whether one cost token is "free" (zero or common free labels).
		*
		* @param string $token Single price fragment or whole cost.
		* @return bool
		*/

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

		// --- Part rendering (front end) ---

		/**
		* Markup for the consolidated venue repeater row (`part` = venue).
		*
		* @param \WP_Post            $post  Event post.
		* @param array<string,mixed> $item  Repeater row.
		* @param int                 $idx   Row index.
		* @param string              $style Inline style attribute value (contents only), or empty.
		* @param string              $skin  Loop skin: '' or 'style1' or 'style2'.
		* @return string Empty when nothing to show.
		*/
		public static function ecbb_render_venue($post, array $item, $idx, $style, $skin = '')
		{
			if (! $post instanceof \WP_Post) {
				return '';
			}

		$text = self::ecbb_venue_text($post->ID, $item, $skin);
		if ($text === '') {
			return '';
		}

		$idx  = absint($idx);
		$skin = (string) $skin;
		$attr = self::ecbb_part_wrap_attrs($item, $idx, $style);
		$classes = esc_attr(
		self::ecbb_part_classes('venue', $idx, $skin, $item) . ' ecbb-has-row-icon'
		);

		return '<div class="' . $classes . '"' . $attr . '>' . esc_html($text) . '</div>';
		}

		/**
		* Markup for the consolidated organizer repeater row (`part` = organizer).
		*
		* @param \WP_Post            $post  Event post.
		* @param array<string,mixed> $item  Repeater row.
		* @param int                 $idx   Row index.
		* @param string              $style Inline style attribute value (contents only), or empty.
		* @param string              $skin  Loop skin: '' or 'style1' or 'style2'.
		* @return string Empty when nothing to show.
		*/
		public static function ecbb_render_organizer($post, array $item, $idx, $style, $skin = '')
		{
			if (! $post instanceof \WP_Post) {
				return '';
			}

		$text = self::ecbb_organizer_text($post->ID, $item, $skin);
		if ($text === '') {
			return '';
		}

		$idx  = absint($idx);
		$skin = (string) $skin;
		$attr = self::ecbb_part_wrap_attrs($item, $idx, $style);
		$classes = esc_attr(self::ecbb_part_classes('organizer', $idx, $skin, $item));

		return '<div class="' . $classes . '"' . $attr . '>' . esc_html($text) . '</div>';
		}

		/**
		* Markup for featured image (single or dual size for hover).
		*
		* @param int                 $thumb_id Attachment ID.
		* @param array<string,mixed> $item     Repeater row.
		* @return string HTML or empty.
		*/
		public static function ecbb_render_featured_img($thumb_id, array $item)
		{
			$thumb_id = (int) $thumb_id;
			if (! $thumb_id) {
				return '';
			}

		if (! self::ecbb_hover_style_active($item)) {
			$item['image_size_hover']              = '';
			$item['ecbb_image_object_align_hover'] = '';
		}

		$size_base = self::ecbb_sanitize_image_size($item['image_size'] ?? '', 'large');
		$raw_hover = isset($item['image_size_hover']) ? trim((string) $item['image_size_hover']) : '';
		$size_hover = $raw_hover !== '' ? self::ecbb_sanitize_image_size($raw_hover, $size_base) : '';
		$dual       = ($raw_hover !== '' && $size_hover !== $size_base);

		if (! $dual) {
			$html = wp_get_attachment_image(
			$thumb_id,
			$size_base,
			false,
			[
			'class' => 'ecbb-event__image',
			]
			);
			return is_string($html) ? $html : '';
		}

		$img_base = wp_get_attachment_image(
		$thumb_id,
		$size_base,
		false,
		[
		'class' => 'ecbb-event__image ecbb-event__image--base',
		]
		);
		$img_hover = wp_get_attachment_image(
		$thumb_id,
		$size_hover,
		false,
		[
		'class' => 'ecbb-event__image ecbb-event__image--hover',
		]
		);
		if (! is_string($img_base) || ! is_string($img_hover) || $img_base === '' || $img_hover === '') {
			return is_string($img_base) ? $img_base : '';
		}

		return '<span class="ecbb-event__img-stack">' . $img_base . $img_hover . '</span>';
		}

		/**
		 * Detail-field part slugs routed through {@see ecbb_render_part_detail()}.
		 *
		 * @return string[]
		 */
		private static function ecbb_part_ext_detail_slugs() {
			return [
				'venue_full_address',
				'venue_street',
				'venue_city',
				'venue_state',
				'venue_zip',
				'venue_country',
				'venue_phone',
				'venue_website',
				'event_map_link',
				'event_website',
				'event_phone',
				'organizer_email',
				'organizer_phone',
				'organizer_website',
			];
		}

		/**
		 * Part slug => render handler for {@see ecbb_render_part_ext()}.
		 *
		 * @return array<string, callable>
		 */
		private static function ecbb_part_ext_dispatch_map() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}

			$map = [
				'venue'         => [ self::class, 'ecbb_render_venue' ],
				'organizer'     => [ self::class, 'ecbb_render_organizer' ],
				'date'          => [ self::class, 'ecbb_render_part_date' ],
				'event_date'    => [ self::class, 'ecbb_render_part_event_date' ],
				'event_time'    => [ self::class, 'ecbb_render_part_event_time' ],
				'event_day'     => [ self::class, 'ecbb_render_part_event_day' ],
				'event_cost'    => [ self::class, 'ecbb_render_part_event_cost' ],
				'event_tickets' => [ self::class, 'ecbb_render_part_event_tickets' ],
				'event_rsvp'    => [ self::class, 'ecbb_render_part_event_rsvp' ],
				'read_more'     => [ self::class, 'ecbb_render_part_read_more' ],
			];

			$detail_handler = [ self::class, 'ecbb_render_part_detail' ];
			foreach ( self::ecbb_part_ext_detail_slugs() as $slug ) {
				$map[ $slug ] = $detail_handler;
			}

			return $map;
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_date( $post, array $item, $idx, $style, $skin = '' ) {
			$fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
			if ( $fmt === 'range' ) {
				return self::ecbb_render_grid_date_flow( $post, $item, $idx, $skin );
			}

			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( self::ecbb_part_classes( 'date', $idx, $skin, $item ) );
			$tp   = self::ecbb_build_day_time_parts( $post->ID, $item );
			$html = '';

			if ( $fmt === 'time' ) {
				$html = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
			} elseif ( $fmt === 'day' ) {
				$html = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
			} else {
				$day  = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
				$time = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
				if ( $day !== '' && $time !== '' ) {
					$html = $day . ', ' . $time;
				} elseif ( $time !== '' ) {
					$html = $time;
				} else {
					$html = $day;
				}
			}

			if ( $html === '' ) {
				return '';
			}

			return '<div class="' . $wrap . ( $html !== '' ? ' ecbb-has-row-icon' : '' ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_date( $post, array $item, $idx, $style, $skin = '' ) {
			$idx    = absint( $idx );
			$skin   = (string) $skin;
			$attr   = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap   = esc_attr( self::ecbb_part_classes( 'event_date', $idx, $skin, $item ) );
			$format = self::ecbb_part_date_php_fmt( 'event_date', $item );
			$html   = '';

			if ( function_exists( 'tribe_get_start_date' ) ) {
				$php  = $format !== '' ? $format : get_option( 'date_format' );
				$html = (string) \tribe_get_start_date( $post->ID, false, $php );
			} else {
				$raw  = (string) get_post_meta( $post->ID, '_EventStartDate', true );
				$ts   = $raw ? strtotime( $raw ) : false;
				$php  = $format !== '' ? $format : get_option( 'date_format' );
				$html = $ts ? date_i18n( $php, $ts ) : '';
			}

			$html = trim( wp_strip_all_tags( $html ) );
			if ( $html === '' ) {
				return '';
			}

			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_time( $post, array $item, $idx, $style, $skin = '' ) {
			$idx    = absint( $idx );
			$skin   = (string) $skin;
			$attr   = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap   = esc_attr( self::ecbb_part_classes( 'event_time', $idx, $skin, $item ) );
			$format = self::ecbb_part_date_php_fmt( 'event_time', $item );
			$tp     = self::ecbb_build_day_time_parts( $post->ID, $item );
			$html   = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';

			if ( $html === '' ) {
				$php = $format !== '' ? $format : get_option( 'time_format' );
				$php = self::ecbb_time_fmt_lower( $php );
				if ( function_exists( 'tribe_get_start_time' ) ) {
					$html = (string) \tribe_get_start_time( $post->ID, $php );
				} elseif ( function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) \tribe_get_start_date( $post->ID, true, $php );
				} else {
					$raw  = (string) get_post_meta( $post->ID, '_EventStartDate', true );
					$ts   = $raw ? strtotime( $raw ) : false;
					$html = $ts ? date_i18n( $php, $ts ) : '';
				}
				$html = self::ecbb_time_lower_am( trim( wp_strip_all_tags( $html ) ) );
			}

			if ( $html === '' ) {
				return '';
			}

			return '<div class="' . $wrap . ( $skin !== 'style2' && $html !== '' ? ' ecbb-has-row-icon' : '' ) . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_day( $post, array $item, $idx, $style, $skin = '' ) {
			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( self::ecbb_part_classes( 'event_day', $idx, $skin, $item ) );
			$pr   = self::ecbb_build_day_time_parts( $post->ID, [] );
			$html = isset( $pr['day'] ) ? trim( (string) $pr['day'] ) : '';

			if ( $html === '' ) {
				$raw  = (string) get_post_meta( $post->ID, '_EventStartDate', true );
				$ts   = $raw ? strtotime( $raw ) : false;
				$html = $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
			}

			if ( $html === '' ) {
				return '';
			}

			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_detail( $post, array $item, $idx, $style, $skin = '' ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' || ! in_array( $part, self::ecbb_part_ext_detail_slugs(), true ) ) {
				return '';
			}

			$idx       = absint( $idx );
			$skin      = (string) $skin;
			$attr      = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$link_attr = '';
			$wrap      = esc_attr( self::ecbb_part_classes( $part, $idx, $skin, $item ) );
			$html      = self::ecbb_part_detail_text( $post->ID, $part );

			if ( $html === '' ) {
				return '';
			}

			$venue_physical = [ 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ];
			$loc_icon       = in_array( $part, $venue_physical, true ) ? ' ecbb-has-row-icon' : '';

			if ( $part === 'organizer_email' && is_email( $html ) ) {
				return '<div class="' . $wrap . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( 'mailto:' . $html ) . '"' . $link_attr . '>' . esc_html( $html ) . '</a></div>';
			}

			$url_parts = [ 'venue_website', 'event_website', 'organizer_website', 'event_map_link' ];
			if ( in_array( $part, $url_parts, true ) ) {
				$safe = esc_url_raw( $html );
				if ( ! $safe || ! preg_match( '#^https?://#i', $safe ) ) {
					return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $html ) . '</div>';
				}

				$label = isset( $item['detail_link_text'] ) ? trim( (string) $item['detail_link_text'] ) : '';
				if ( $label === '' ) {
					$defaults = [
						'event_map_link'    => __( 'Open map', 'events-calendar-for-bricks' ),
						'event_website'     => __( 'Event website', 'events-calendar-for-bricks' ),
						'venue_website'     => __( 'Venue website', 'events-calendar-for-bricks' ),
						'organizer_website' => __( 'Organizer website', 'events-calendar-for-bricks' ),
					];
					$label = isset( $defaults[ $part ] ) ? $defaults[ $part ] : $safe;
				} else {
					$label = sanitize_text_field( $label );
				}

				return '<div class="' . $wrap . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url( $safe ) . '" rel="noopener noreferrer" target="_blank"' . $link_attr . '>' . esc_html( $label ) . '</a></div>';
			}

			return '<div class="' . $wrap . $loc_icon . '"' . $attr . '>' . esc_html( $html ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_cost( $post, array $item, $idx, $style, $skin = '' ) {
			$cost = self::ecbb_layout_cost_label( $post->ID, $item );
			if ( $cost === '' ) {
				return '';
			}

			$idx  = absint( $idx );
			$skin = (string) $skin;
			$attr = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$wrap = esc_attr( self::ecbb_part_classes( 'event_cost', $idx, $skin, $item ) );

			return '<div class="' . $wrap . '"' . $attr . '>' . esc_html( $cost ) . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_tickets( $post, array $item, $idx, $style, $skin = '' ) {
			$url = '';
			if ( function_exists( 'tribe_get_event' ) ) {
				$ev = tribe_get_event( $post->ID );
				if ( $ev && ! empty( $ev->website ) ) {
					$url = esc_url_raw( (string) $ev->website );
				}
			}
			if ( $url === '' ) {
				$m   = get_post_meta( $post->ID, '_EventUrl', true );
				$url = $m ? esc_url_raw( (string) $m ) : '';
			}
			if ( $url === '' ) {
				return '';
			}

			$label = isset( $item['tickets_link_text'] ) ? trim( (string) $item['tickets_link_text'] ) : '';
			if ( $label === '' ) {
				$label = esc_html__( 'Tickets', 'events-calendar-for-bricks' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$idx       = absint( $idx );
			$skin      = (string) $skin;
			$attr      = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$link_attr = '';
			$wrap      = esc_attr( self::ecbb_part_classes( 'event_tickets', $idx, $skin, $item ) );
			$inner_el  = self::ecbb_action_link_html(
				$item,
				$url,
				$label,
				$link_attr,
				' rel="noopener noreferrer" target="_blank"'
			);

			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_event_rsvp( $post, array $item, $idx, $style, $skin = '' ) {
			$url   = get_permalink( $post->ID );
			$label = isset( $item['rsvp_link_text'] ) ? trim( (string) $item['rsvp_link_text'] ) : '';
			if ( $label === '' ) {
				$label = __( 'RSVP', 'events-calendar-for-bricks' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$frag = '#tribe-tickets__tickets-form';
			if ( function_exists( 'tribe_events_has_tickets' ) && tribe_events_has_tickets( $post->ID ) ) {
				$url = $url . $frag;
			}

			$idx       = absint( $idx );
			$skin      = (string) $skin;
			$attr      = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$link_attr = '';
			$wrap      = esc_attr( self::ecbb_part_classes( 'event_rsvp', $idx, $skin, $item ) );
			$inner_el  = self::ecbb_action_link_html( $item, $url, $label, $link_attr );

			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		/**
		 * @param \WP_Post            $post  Event post.
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @param string              $style Inline style attribute value (contents only), or empty.
		 * @param string              $skin  Loop skin.
		 * @return string
		 */
		public static function ecbb_render_part_read_more( $post, array $item, $idx, $style, $skin = '' ) {
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			if ( $label === '' ) {
				$label = __( 'View Details', 'events-calendar-for-bricks' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$idx       = absint( $idx );
			$skin      = (string) $skin;
			$attr      = self::ecbb_part_wrap_attrs( $item, $idx, $style );
			$link_attr = '';
			$wrap      = esc_attr( self::ecbb_part_classes( 'read_more', $idx, $skin, $item ) );
			$inner_el  = self::ecbb_action_link_html(
				$item,
				get_permalink( $post->ID ),
				$label,
				$link_attr
			);

			return '<div class="' . $wrap . '"' . $attr . '>' . $inner_el . '</div>';
		}

		/**
		* @param \WP_Post $post      Event post.
		* @param array    $item     Repeater row settings.
		* @param int      $idx      Row index (for CSS class).
		* @param string   $style    Inline style attribute value (contents only), or empty.
		* @param string   $skin     Loop skin: '' or 'style2'.
		* @return string|false      Markup, empty string when nothing to show, false if not an extended part.
		*/
		public static function ecbb_render_part_ext($post, array $item, $idx, $style, $skin = '')
		{
			if ( class_exists( 'ECBB_Styles', false ) ) {
				$item = \ECBB_Styles::ecbb_clean_part( $item );
			}

			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' ) {
				return false;
			}

			$handlers = self::ecbb_part_ext_dispatch_map();
			if ( ! isset( $handlers[ $part ] ) ) {
				return false;
			}

			return call_user_func( $handlers[ $part ], $post, $item, $idx, $style, $skin );
		}

		// --- Layout shell helpers (List 1 / List 2 / Grid reference markup) ---

		/**
		 * Parse a Bricks checkbox value when the setting key is present.
		 *
		 * @param mixed $value Raw saved value.
		 * @return bool
		 */
		public static function ecbb_parse_bricks_checkbox( $value ) {
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

		/**
		 * Merge passed settings with the active widget render context.
		 *
		 * @param array<string,mixed> $settings Partial settings from a template caller.
		 * @return array<string,mixed>
		 */
		public static function ecbb_layout_settings( array $settings = [] ) {
			$active = self::ecbb_active_widget_settings();
			if ( ! is_array( $active ) || $active === [] ) {
				return $settings;
			}
			if ( $settings === [] ) {
				return $active;
			}
			return array_replace( $active, $settings );
		}

		/**
		 * Normalize layout shell checkbox settings for Bricks save/render.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string,mixed>
		 */
		public static function ecbb_norm_layout_shell_settings( array $settings ) {
			if (
				! array_key_exists( 'list1_show_category_badge', $settings )
				&& ! array_key_exists( 'grid_show_category_badge', $settings )
				&& array_key_exists( 'shell_show_category_badge', $settings )
			) {
				$legacy = self::ecbb_parse_bricks_checkbox( $settings['shell_show_category_badge'] );
				$legacy_val = $legacy ? 'show' : 'hide';
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
				$settings[ $key ] = self::ecbb_parse_bricks_checkbox( $raw ) ? 'show' : 'hide';
			}

			return $settings;
		}

		/**
		 * Layout shell select (show/hide) or legacy Bricks checkbox value.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @param string              $key      Setting key.
		 * @param string              $default  show|hide when the key is absent.
		 * @return bool
		 */
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
			return self::ecbb_parse_bricks_checkbox( $raw );
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

			return self::ecbb_parse_bricks_checkbox( $raw );
		}

		public static function ecbb_show_shell_category_badge( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			if ( ! self::ecbb_show_event_image( $settings ) ) {
				return false;
			}
			$layout   = self::ecbb_sanitize_layout_template( $settings );

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
			$layout   = self::ecbb_sanitize_layout_template( $settings );
			if ( $layout['template'] !== 'list' || $layout['item_chrome'] !== 'style-2' ) {
				return false;
			}
			return self::ecbb_shell_select_on( $settings, 'style2_show_date_badge', 'show' );
		}

		public static function ecbb_style2_date_badge_order( $settings ) {
			$settings = self::ecbb_layout_settings( is_array( $settings ) ? $settings : [] );
			$order = isset( $settings['style2_date_badge_order'] ) ? (string) $settings['style2_date_badge_order'] : 'month_day';
			return in_array( $order, [ 'month_day', 'day_month' ], true ) ? $order : 'month_day';
		}

		/**
		 * Featured image attachment ID for an event (WP thumbnail + TEC fallback).
		 *
		 * @param int $post_id Event post ID.
		 * @return int Attachment ID or 0.
		 */
		public static function ecbb_event_thumbnail_id( $post_id ) {
			$post_id  = absint( $post_id );
			$thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $thumb_id > 0 ) {
				return $thumb_id;
			}
			if ( function_exists( 'tribe_get_event' ) ) {
				$event = tribe_get_event( $post_id );
				if ( $event && ! empty( $event->thumbnail_id ) ) {
					return (int) $event->thumbnail_id;
				}
			}
			return 0;
		}

		public static function ecbb_shell_skip_part( $slug, $layout ) {
			$slug = (string) $slug;
			if ( in_array( $slug, [ 'image', 'read_more', 'event_date', 'event_day' ], true ) ) {
				return true;
			}
			if ( in_array( $slug, [ 'categories' ], true ) && in_array( $layout, [ 'style1', 'grid' ], true ) ) {
				return true;
			}
			return false;
		}

		public static function ecbb_layout_surface_class( $part, $skin ) {
			$part = (string) $part;
			$skin = (string) $skin;
			static $map = [
				'style1' => [
					'title'       => 'event-list-card__title',
					'description' => 'event-list-card__description',
				],
				'style2' => [
					'title'       => 'ecbb-event-card__title',
					'description' => 'ecbb-event-card__description',
					'categories'  => 'ecbb-event-card__category',
				],
				'grid'   => [
					'title'       => 'event-grid-card__title',
					'description' => 'event-grid-card__description',
				],
			];
			return isset( $map[ $skin ][ $part ] ) ? $map[ $skin ][ $part ] : '';
		}

		/**
		 * Plain-text event description for length checks (excerpt / content).
		 *
		 * @param \WP_Post            $post Event post.
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		public static function ecbb_description_plain_text( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$source = isset( $item['desc_source'] ) ? (string) $item['desc_source'] : 'auto';
			$source = in_array( $source, [ 'auto', 'excerpt', 'content' ], true ) ? $source : 'auto';

			$html = '';
			if ( $source === 'excerpt' ) {
				$html = (string) $post->post_excerpt;
			} elseif ( $source === 'content' || ( $source === 'auto' && $post->post_excerpt === '' ) ) {
				$raw  = (string) $post->post_content;
				$html = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
				$html = do_shortcode( $html );
			} elseif ( $post->post_excerpt !== '' ) {
				$html = wpautop( (string) $post->post_excerpt );
			}

			if ( $html === '' && $post->post_content !== '' ) {
				$raw  = (string) $post->post_content;
				$html = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
				$html = do_shortcode( $html );
			}

			return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
		}

		/**
		 * Grid card description word cap (default 20).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return int
		 */
		public static function ecbb_grid_description_word_limit( array $item ) {
			if ( isset( $item['desc_length'] ) && (string) $item['desc_length'] === 'full' ) {
				return 0;
			}
			if ( isset( $item['desc_words'] ) && (int) $item['desc_words'] > 0 ) {
				return max( 5, (int) $item['desc_words'] );
			}
			return 20;
		}

		/**
		 * Grid description HTML with inline "Read more" when text exceeds the word cap.
		 *
		 * @param \WP_Post            $post Event post.
		 * @param array<string,mixed> $item Repeater row.
		 * @return string HTML (escaped fragments).
		 */
		public static function ecbb_grid_description_html( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$plain = self::ecbb_description_plain_text( $post, $item );
			if ( $plain === '' ) {
				return '';
			}

			$limit = self::ecbb_grid_description_word_limit( $item );
			if ( $limit < 1 ) {
				return esc_html( $plain );
			}

			$words = preg_split( '/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY );
			if ( ! is_array( $words ) || $words === [] ) {
				return '';
			}

			if ( count( $words ) <= $limit ) {
				return esc_html( $plain );
			}

			$excerpt = implode( ' ', array_slice( $words, 0, $limit ) );
			$label   = esc_html__( 'Read more', 'events-calendar-for-bricks' );
			$url     = get_permalink( $post->ID );

			return esc_html( $excerpt ) . '&hellip; '
				. '<a href="' . esc_url( $url ) . '" class="event-grid-card__desc-more ecbb-event__link">'
				. esc_html( $label ) . '</a>';
		}

		public static function ecbb_meta_icon( $type ) {
			$type = (string) $type;
			static $svgs = [
				'clock' => '<path d="M12 7v5l3.4 2.2" /><circle cx="12" cy="12" r="8" />',
				'pin'   => '<path d="M12 21s6-5.1 6-11a6 6 0 0 0-12 0c0 5.9 6 11 6 11Z" /><circle cx="12" cy="10" r="2.4" />',
				'cost'  => '<path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v1.2a2.5 2.5 0 0 0-.9 4.8V15a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-1.2a2.5 2.5 0 0 0-.9-4.8V9Z" /><path d="M13 5v14" />',
			];
			if ( ! in_array( $type, [ 'clock', 'pin', 'cost' ], true ) ) {
				$type = 'clock';
			}
			$inner = $svgs[ $type ];
			return '<span class="ecbb-event-card__meta-icon" aria-hidden="true"><svg viewBox="0 0 24 24">' . $inner . '</svg></span>';
		}

		public static function ecbb_meta_icon_for_part( $slug ) {
			$slug = (string) $slug;
			if ( in_array( $slug, [ 'venue', 'organizer', 'venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone' ], true ) ) {
				return self::ecbb_meta_icon( 'pin' );
			}
			if ( $slug === 'event_cost' ) {
				return self::ecbb_meta_icon( 'cost' );
			}
			return self::ecbb_meta_icon( 'clock' );
		}

		public static function ecbb_render_meta_li( $post, array $item, $idx, $skin, $layout, $price = false ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$html = self::ecbb_render_part_ext( $post, $item, $idx, '', $skin );
			if ( $html === '' || $html === false ) {
				return '';
			}

			$row_clean = class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_clean_part( $item ) : $item;
			$part      = isset( $row_clean['part'] ) ? (string) $row_clean['part'] : '';
			$icon      = self::ecbb_meta_icon_for_part( $part );

			if ( $layout === 'style2' ) {
				return '<li class="ecbb-event-card__meta-item">' . $icon . $html . '</li>';
			}

			$li_class = $price ? ' class="price"' : '';
			return '<li' . $li_class . '>' . $icon . $html . '</li>';
		}

		public static function ecbb_render_meta_lists( $post, array $meta_primary, array $meta_price, $skin, $layout, callable $emit_li ) {
			if ( $layout === 'style1' && ( $meta_primary !== [] || $meta_price !== [] ) ) {
				if ( $meta_primary !== [] ) {
					echo '<ul class="event-meta event-meta--list">';
			foreach ( $meta_primary as $row ) {
					$emit_li( $post, $row['item'], $row['idx'], false );
				}
					echo '</ul>';
				}
				if ( $meta_price !== [] ) {
					echo '<ul class="event-meta event-meta--list">';
					foreach ( $meta_price as $row ) {
						$emit_li( $post, $row['item'], $row['idx'], true );
					}
					echo '</ul>';
				}
				return;
			}

			$all = array_merge( $meta_primary, $meta_price );
			if ( $all === [] ) {
				return;
			}

			$ul_class = ( $layout === 'grid' ) ? 'event-meta event-meta--grid' : 'ecbb-event-card__meta';
			echo '<ul class="' . esc_attr( $ul_class ) . '">';
			foreach ( $all as $row ) {
				$emit_li( $post, $row['item'], $row['idx'], false );
			}
			echo '</ul>';
		}

		public static function ecbb_event_category_terms( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return [];
			}

			$by_id      = [];
			$taxonomies = apply_filters( 'ecbb_event_category_taxonomies', [ 'tribe_events_cat' ], $post_id );

			foreach ( (array) $taxonomies as $taxonomy ) {
				if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$raw = wp_get_object_terms(
					$post_id,
					$taxonomy,
					[
						'orderby' => 'name',
						'order'   => 'ASC',
					]
				);
				if ( is_wp_error( $raw ) || ! is_array( $raw ) ) {
					continue;
				}
				foreach ( $raw as $term ) {
					if ( $term instanceof \WP_Term ) {
						$by_id[ (int) $term->term_id ] = $term;
					}
				}
			}

			if ( $by_id === [] && function_exists( 'tribe_get_event' ) ) {
				$event = tribe_get_event( $post_id );
				if ( $event && ! empty( $event->categories ) && is_array( $event->categories ) ) {
					foreach ( $event->categories as $term ) {
						if ( $term instanceof \WP_Term ) {
							$by_id[ (int) $term->term_id ] = $term;
							continue;
						}
						if ( is_object( $term ) && ! empty( $term->term_id ) ) {
							$loaded = get_term( (int) $term->term_id );
							if ( $loaded instanceof \WP_Term && ! is_wp_error( $loaded ) ) {
								$by_id[ (int) $loaded->term_id ] = $loaded;
							}
						}
					}
				}
			}

			return array_values( $by_id );
		}

		public static function ecbb_shell_category_badge( $post ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ecbb_event_category_terms( $post->ID );
			if ( $terms === [] ) {
				return '';
			}
			$links = [];
			foreach ( $terms as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$links[] = '<a href="' . esc_url( $url ) . '" class="event-badge--blue">'
					. esc_html( $term->name ) . '</a>';
			}
			if ( $links === [] ) {
				return '';
			}
			return '<div class="event-badge">' . implode( '', $links ) . '</div>';
		}

		public static function ecbb_shell_featured_image( $post, $layout, $link = true, $include_wrap = true ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$thumb_id = self::ecbb_event_thumbnail_id( $post->ID );
			$url      = get_permalink( $post->ID );
			$title    = esc_attr( wp_strip_all_tags( get_the_title( $post->ID ) ) );

			static $map = [
				'style1' => [
					'wrap'  => 'event-list-card__image-wrap',
					'link'  => 'event-list-card__image-link',
					'img'   => 'event-list-card__image',
				],
				'style2' => [
					'wrap'  => 'ecbb-event-card__image-wrap',
					'link'  => 'ecbb-event-card__image-link',
					'img'   => 'ecbb-event-card__image',
				],
				'grid'   => [
					'wrap'  => 'event-grid-card__image-wrap',
					'link'  => 'event-grid-card__image-link',
					'img'   => 'event-grid-card__image',
				],
			];
			if ( ! isset( $map[ $layout ] ) ) {
				return '';
			}
			$cls = $map[ $layout ];
			if ( $thumb_id < 1 ) {
				return $include_wrap ? '<div class="' . esc_attr( $cls['wrap'] ) . '"></div>' : '';
			}
			$size = self::ecbb_sanitize_image_size( '', 'large' );
			$img  = wp_get_attachment_image(
				$thumb_id,
				$size,
				false,
				[
					'class'    => $cls['img'],
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => $title,
				]
			);
			if ( ! is_string( $img ) || $img === '' ) {
				return $include_wrap ? '<div class="' . esc_attr( $cls['wrap'] ) . '"></div>' : '';
			}
			$inner = $link
				? '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $cls['link'] ) . '">' . $img . '</a>'
				: $img;
			return $include_wrap
				? '<div class="' . esc_attr( $cls['wrap'] ) . '">' . $inner . '</div>'
				: $inner;
		}

		public static function ecbb_list1_date_column( $post ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = false;
			if ( class_exists( 'ECBB_List_2', false ) ) {
				list( $start_ts ) = \ECBB_List_2::ecbb_date_bounds( $post->ID );
			} else {
				$raw      = (string) get_post_meta( $post->ID, '_EventStartDate', true );
				$start_ts = $raw ? strtotime( $raw ) : false;
			}
			if ( ! $start_ts ) {
				return '<div class="event-list-card__date"></div>';
			}
			$order = 'day_month';
			$day   = '<span class="event-list-card__day">' . esc_html( date_i18n( 'd', $start_ts ) ) . '</span>';
			$month = '<span class="event-list-card__month">' . esc_html( date_i18n( 'M', $start_ts ) ) . '</span>';
			$inner = $day . $month;
			return '<div class="event-list-card__date event-list-card__date--' . esc_attr( $order ) . '">' . $inner . '</div>';
		}

		public static function ecbb_list2_date_badge( $post, $settings = [] ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = false;
			if ( class_exists( 'ECBB_List_2', false ) ) {
				list( $start_ts ) = \ECBB_List_2::ecbb_date_bounds( $post->ID );
			} else {
				$raw      = (string) get_post_meta( $post->ID, '_EventStartDate', true );
				$start_ts = $raw ? strtotime( $raw ) : false;
			}
			if ( ! $start_ts ) {
				return '';
			}
			$order = self::ecbb_style2_date_badge_order( is_array( $settings ) ? $settings : [] );
			$cls   = 'ecbb-event-card__date-badge ecbb-event-card__date-badge--' . $order;
			$label = date_i18n( 'F j, Y', $start_ts );
			$month = '<span>' . esc_html( strtoupper( date_i18n( 'M', $start_ts ) ) ) . '</span>';
			$day   = '<strong>' . esc_html( date_i18n( 'd', $start_ts ) ) . '</strong>';
			$inner = ( $order === 'day_month' ) ? $day . $month : $month . $day;
			return '<time class="' . esc_attr( $cls ) . '" datetime="' . esc_attr( wp_date( 'Y-m-d', $start_ts ) ) . '" aria-label="' . esc_attr( $label ) . '">'
				. $inner
				. '</time>';
		}

		public static function ecbb_grid_date_range_text( $post, array $item = [] ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = false;
			$end_ts   = false;
			if ( class_exists( 'ECBB_List_2', false ) ) {
				list( $start_ts, $end_ts ) = \ECBB_List_2::ecbb_date_bounds( $post->ID );
			} else {
				$raw      = (string) get_post_meta( $post->ID, '_EventStartDate', true );
				$raw_end  = (string) get_post_meta( $post->ID, '_EventEndDate', true );
				$start_ts = $raw ? strtotime( $raw ) : false;
				$end_ts   = $raw_end ? strtotime( $raw_end ) : $start_ts;
			}
			if ( ! $start_ts ) {
				return '';
			}
			if ( ! $end_ts ) {
				$end_ts = $start_ts;
			}

			$php = self::ecbb_part_date_php_fmt( 'event_date', $item );
			if ( $php === '' ) {
				$php = 'd M, Y';
			}

			if ( date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) ) {
				return date_i18n( $php, $start_ts );
			}
			return date_i18n( $php, $start_ts ) . ' - ' . date_i18n( $php, $end_ts );
		}

		public static function ecbb_render_grid_date_flow( $post, array $item, $idx, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = self::ecbb_grid_date_range_text( $post, $item );
			if ( $text === '' ) {
				return '';
			}
			$wrap  = esc_attr(
				self::ecbb_part_classes( 'date', $idx, $skin, $item ) . ' event-grid-card__date'
			);
			$attr  = self::ecbb_part_wrap_attrs( $item, $idx );
			return '<span class="' . $wrap . '"' . $attr . '>' . esc_html( strtoupper( $text ) ) . '</span>';
		}

		public static function ecbb_render_style2_category( $post, array $item, $idx, $skin ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ecbb_event_category_terms( $post->ID );
			if ( $terms === [] ) {
				return '';
			}

			$skin  = (string) $skin;
			$attr  = self::ecbb_part_wrap_attrs( $item, $idx );
			$wrap  = esc_attr(
				trim(
					'ecbb-event-card__top '
					. self::ecbb_part_classes( 'categories', $idx, $skin, $item )
				)
			);

			$links = [];
			foreach ( $terms as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$links[] = '<a href="' . esc_url( $url ) . '" class="ecbb-event-card__category">'
					. esc_html( $term->name ) . '</a>';
			}
			if ( $links === [] ) {
				return '';
			}

			return '<div class="' . $wrap . '"' . $attr . '>' . implode( '', $links ) . '</div>';
		}

		public static function ecbb_render_layout_read_more( $post, array $item, $idx, $skin ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			if ( $label === '' ) {
				$label = __( 'View Details', 'events-calendar-for-bricks' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$skin      = (string) $skin;
			$skin_wrap = ( $skin === 'grid' ) ? '' : $skin;
			$wrap      = esc_attr( self::ecbb_part_classes( 'read_more', $idx, $skin_wrap, $item ) );
			$part_attr = self::ecbb_part_wrap_attrs( $item, $idx );
			$url       = get_permalink( $post->ID );
			$link_cls  = esc_attr( trim( 'ecbb-event__link ' . self::ecbb_layout_read_more_btn_class( $skin ) ) );
			$link      = '<a href="' . esc_url( $url ) . '" class="' . $link_cls . '">' . esc_html( $label ) . '</a>';
			$block     = '<div class="' . $wrap . '"' . $part_attr . '>' . $link . '</div>';

			if ( $skin === 'style2' ) {
				return '<div class="ecbb-event-card__divider"></div>'
					. '<div class="ecbb-event-card__footer">'
					. $block
					. '</div>';
			}

			return $block;
		}

	}
}