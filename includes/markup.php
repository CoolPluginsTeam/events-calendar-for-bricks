<?php

/**
 * Events Widget: shared TEC event part markup (date, tickets, images, hover helpers). List Style 2 shell:
 * `widgets/layouts/ecbb-list-2.php`.
 *
 * File: `includes/markup.php`.
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

		public static function ecbb_normalize_bricks_color($value)
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
					return ecbb_normalize_bricks_color($decoded);
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

		public static function ecbb_event_part_resolve_php_format($part, array $item)
		{
			$preset = isset($item['date_format_preset']) ? (string) $item['date_format_preset'] : '';
			$custom = isset($item['date_format_custom']) ? trim((string) $item['date_format_custom']) : '';

			if ($preset === 'custom') {
				return $custom;
			}

			if (function_exists('ecbb_date_preset_php_format')) {
				$mapped = ecbb_date_preset_php_format($preset, (string) $part);
				if ($mapped !== null && $mapped !== '') {
					return $mapped;
				}
			}

			if ($preset === 'site_date') {
				return (string) get_option('date_format');
			}

			if ($preset === 'site_time') {
				return (string) get_option('time_format');
			}

			if ($preset === 'site_date_time') {
				return (string) get_option('date_format') . ' ' . (string) get_option('time_format');
			}

			// Default per part.
			if ($part === 'event_time') {
				return (string) get_option('time_format');
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

		public static function ecbb_venue_name_plain($event_id)
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
				$venue_id = (int) get_post_meta($event_id, '_EventVenueID', true);
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

		public static function ecbb_venue_id_for_event($event_id)
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
			return (int) get_post_meta($event_id, '_EventVenueID', true);
		}

		/**
		 * Plain-text full venue address for an event.
		 *
		 * @param int $event_id Event post ID.
		 * @return string Unescaped plain text; caller must escape for HTML.
		 */

		public static function ecbb_venue_full_address_plain($event_id)
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

			$address_ids = [$event_id];
			$venue_id    = self::ecbb_venue_id_for_event($event_id);
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
						$name = self::ecbb_venue_name_plain($event_id);
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

			return self::ecbb_event_part_detail_plain($event_id, 'venue_full_address');
		}

		/**
		 * Plain-text venue name with full address for an event.
		 *
		 * @param int $event_id Event post ID.
		 * @return string Unescaped plain text; caller must escape for HTML.
		 */

		public static function ecbb_venue_name_and_address_plain($event_id)
		{
			$name    = self::ecbb_venue_name_plain($event_id);
			$address = self::ecbb_venue_full_address_plain($event_id);

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

		public static function ecbb_venue_name_and_state_plain($event_id)
		{
			$name  = self::ecbb_venue_name_plain($event_id);
			$state = self::ecbb_event_part_detail_plain($event_id, 'venue_state');
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

		/**
		 * Resolved venue display format for a repeater row (Style 2 defaults to name + state).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @param string              $skin Loop skin: '' or 'style1' or 'style2'.
		 * @return string
		 */

		public static function ecbb_venue_resolved_display(array $item, $skin = '')
		{
			$display = isset($item['venue_display']) ? (string) $item['venue_display'] : '';
			if ($display === '' || $display === 'name_and_address') {
				return (string) $skin === 'style2' ? 'name_and_state' : 'full_details';
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

		public static function ecbb_venue_part_uses_full_details(array $item, $skin = '')
		{
			$display = function_exists('ecbb_venue_resolved_display')
				? self::ecbb_venue_resolved_display($item, $skin)
				: (isset($item['venue_display']) ? (string) $item['venue_display'] : 'full_details');

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

		public static function ecbb_venue_part_plain_text($event_id, array $item, $skin = '')
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

			if (self::ecbb_venue_part_uses_full_details($item, $skin)) {
				return self::ecbb_venue_name_and_address_plain($event_id);
			}

			$display = function_exists('ecbb_venue_resolved_display')
				? self::ecbb_venue_resolved_display($item, $skin)
				: (isset($item['venue_display']) ? (string) $item['venue_display'] : 'full_details');
			if ($display === 'name_and_state') {
				return self::ecbb_venue_name_and_state_plain($event_id);
			}

			return self::ecbb_venue_name_plain($event_id);
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

		public static function ecbb_event_part_venue_markup($post, array $item, $idx, $style, $skin = '')
		{
			if (! $post instanceof \WP_Post) {
				return '';
			}

			$text = self::ecbb_venue_part_plain_text($post->ID, $item, $skin);
			if ($text === '') {
				return '';
			}

			$idx  = absint($idx);
			$skin = (string) $skin;
			$attr = function_exists('ecbb_part_wrapper_attrs')
				? self::ecbb_part_wrapper_attrs($item, $idx, $style)
				: ($style !== '' ? ' style="' . esc_attr($style) . '"' : '');
			$classes = esc_attr(
				self::ecbb_part_wrap_classes('venue', $idx, $skin, $item) . ' ecbb-has-row-icon'
			);

			return '<div class="' . $classes . '"' . $attr . '>' . esc_html($text) . '</div>';
		}

		/**
		 * Plain-text organizer name for an event.
		 *
		 * @param int $event_id Event post ID.
		 * @return string Unescaped plain text; caller must escape for HTML.
		 */

		public static function ecbb_organizer_name_plain($event_id)
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
				$organizer_id = (int) get_post_meta($event_id, '_EventOrganizerID', true);
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

		public static function ecbb_organizer_full_details_plain($event_id)
		{
			$bits = array_filter(
				[
					self::ecbb_organizer_name_plain($event_id),
					self::ecbb_event_part_detail_plain($event_id, 'organizer_email'),
					self::ecbb_event_part_detail_plain($event_id, 'organizer_phone'),
					self::ecbb_event_part_detail_plain($event_id, 'organizer_website'),
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

		public static function ecbb_organizer_part_uses_full_details(array $item, $skin = '')
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

		public static function ecbb_organizer_part_plain_text($event_id, array $item, $skin = '')
		{
			$event_id = (int) $event_id;
			if ($event_id < 1) {
				return '';
			}

			if (self::ecbb_organizer_part_uses_full_details($item, $skin)) {
				return self::ecbb_organizer_full_details_plain($event_id);
			}

			return self::ecbb_organizer_name_plain($event_id);
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

		public static function ecbb_event_part_organizer_markup($post, array $item, $idx, $style, $skin = '')
		{
			if (! $post instanceof \WP_Post) {
				return '';
			}

			$text = self::ecbb_organizer_part_plain_text($post->ID, $item, $skin);
			if ($text === '') {
				return '';
			}

			$idx  = absint($idx);
			$skin = (string) $skin;
			$attr = function_exists('ecbb_part_wrapper_attrs')
				? self::ecbb_part_wrapper_attrs($item, $idx, $style)
				: ($style !== '' ? ' style="' . esc_attr($style) . '"' : '');
			$classes = esc_attr(self::ecbb_part_wrap_classes('organizer', $idx, $skin, $item));

			return '<div class="' . $classes . '"' . $attr . '>' . esc_html($text) . '</div>';
		}

		/**
		 * Plain-text detail for extra venue / organizer / event fields (repeater parts).
		 *
		 * Uses The Events Calendar template helpers when available, otherwise venue post meta.
		 *
		 * @param int    $event_id Event post ID.
		 * @param string $part     Part slug (e.g. venue_city, organizer_email).
		 * @return string          Unescaped plain text; caller must escape for HTML.
		 */

		public static function ecbb_event_part_detail_plain($event_id, $part)
		{
			$event_id = (int) $event_id;
			$part     = (string) $part;
			if ($event_id < 1) {
				return '';
			}

			switch ($part) {
				case 'venue_full_address':
					$address_ids = [$event_id];
					if (function_exists('tribe_get_venue_id')) {
						$venue_id = (int) \tribe_get_venue_id($event_id);
					} else {
						$venue_id = (int) get_post_meta($event_id, '_EventVenueID', true);
					}
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
								return $t;
							}
						}
					}
					$bits = array_filter(
						[
							ecbb_event_part_detail_plain($event_id, 'venue_street'),
							ecbb_event_part_detail_plain($event_id, 'venue_city'),
							trim(
								ecbb_event_part_detail_plain($event_id, 'venue_state')
									. ' '
									. ecbb_event_part_detail_plain($event_id, 'venue_zip')
							),
							ecbb_event_part_detail_plain($event_id, 'venue_country'),
						]
					);
					$bits = array_map('trim', $bits);
					$bits = array_filter($bits);
					return implode(', ', $bits);

				case 'venue_street':
					if (function_exists('tribe_get_address')) {
						$t = trim((string) \tribe_get_address($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_city':
					if (function_exists('tribe_get_city')) {
						$t = trim((string) \tribe_get_city($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_state':
					if (function_exists('tribe_get_province')) {
						$t = trim((string) \tribe_get_province($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					if (function_exists('tribe_get_state')) {
						$t = trim((string) \tribe_get_state($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_zip':
					if (function_exists('tribe_get_zip')) {
						$t = trim((string) \tribe_get_zip($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_country':
					if (function_exists('tribe_get_country')) {
						$t = trim((string) \tribe_get_country($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_phone':
					if (function_exists('tribe_get_phone')) {
						$t = trim((string) \tribe_get_phone($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					break;

				case 'venue_website':
					if (function_exists('tribe_get_venue_website_url')) {
						$t = trim((string) \tribe_get_venue_website_url($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					$vid = (int) get_post_meta($event_id, '_EventVenueID', true);
					if ($vid > 0) {
						$t = trim((string) get_post_meta($vid, '_VenueURL', true));
						if ($t !== '') {
							return $t;
						}
					}
					return '';

				case 'event_map_link':
					if (function_exists('tribe_get_map_link_url')) {
						return trim((string) \tribe_get_map_link_url($event_id));
					}
					if (function_exists('tribe_get_map_link')) {
						$raw = (string) \tribe_get_map_link($event_id);
						if (preg_match('/href=[\"\']([^\"\']+)[\"\']/', $raw, $m)) {
							return trim($m[1]);
						}
					}
					return '';

				case 'event_website':
					if (function_exists('tribe_get_event_website_url')) {
						$t = trim((string) \tribe_get_event_website_url($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					$m = get_post_meta($event_id, '_EventUrl', true);
					return $m ? trim((string) $m) : '';

				case 'event_phone':
					$m = get_post_meta($event_id, '_EventPhone', true);
					return $m ? trim(wp_strip_all_tags((string) $m)) : '';

				case 'organizer_email':
					if (function_exists('tribe_get_organizer_email')) {
						$t = trim((string) \tribe_get_organizer_email($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					$oid = (int) get_post_meta($event_id, '_EventOrganizerID', true);
					if ($oid > 0) {
						$t = trim((string) get_post_meta($oid, '_OrganizerEmail', true));
						if ($t !== '') {
							return $t;
						}
					}
					return '';

				case 'organizer_phone':
					if (function_exists('tribe_get_organizer_phone')) {
						$t = trim((string) \tribe_get_organizer_phone($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					$oid = (int) get_post_meta($event_id, '_EventOrganizerID', true);
					if ($oid > 0) {
						$t = trim((string) get_post_meta($oid, '_OrganizerPhone', true));
						if ($t !== '') {
							return $t;
						}
					}
					return '';

				case 'organizer_website':
					if (function_exists('tribe_get_organizer_website_url')) {
						$t = trim((string) \tribe_get_organizer_website_url($event_id));
						if ($t !== '') {
							return $t;
						}
					}
					$oid = (int) get_post_meta($event_id, '_EventOrganizerID', true);
					if ($oid > 0) {
						$t = trim((string) get_post_meta($oid, '_OrganizerWebsite', true));
						if ($t !== '') {
							return $t;
						}
					}
					return '';

				default:
					return '';
			}

			$vid = (int) get_post_meta($event_id, '_EventVenueID', true);
			if ($vid < 1) {
				return '';
			}
			switch ($part) {
				case 'venue_street':
					return trim((string) get_post_meta($vid, '_VenueAddress', true));
				case 'venue_city':
					return trim((string) get_post_meta($vid, '_VenueCity', true));
				case 'venue_state':
					$s = get_post_meta($vid, '_VenueStateProvince', true);
					if ($s === '' || $s === null) {
						$s = get_post_meta($vid, '_VenueState', true);
					}
					return trim((string) $s);
				case 'venue_zip':
					return trim((string) get_post_meta($vid, '_VenueZip', true));
				case 'venue_country':
					return trim((string) get_post_meta($vid, '_VenueCountry', true));
				case 'venue_phone':
					return trim((string) get_post_meta($vid, '_VenuePhone', true));
				default:
					return '';
			}
		}

		/**
		 * PHP date() format with lowercase am/pm (`a`) instead of uppercase (`A`).
		 *
		 * @param string $format PHP date format string.
		 * @return string
		 */
		public static function ecbb_time_format_lowercase_meridiem($format)
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
		public static function ecbb_format_time_meridiem_lowercase($time_str)
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

		public static function ecbb_event_part_build_day_time_range_parts($post_id, array $item)
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
			$time_fmt = self::ecbb_time_format_lowercase_meridiem((string) get_option('time_format'));

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

			$t_start = self::ecbb_format_time_meridiem_lowercase(trim(wp_strip_all_tags($t_start)));
			$t_end   = self::ecbb_format_time_meridiem_lowercase(trim(wp_strip_all_tags($t_end)));

			if ($t_start === '') {
				return ['day' => $day_str, 'time' => ''];
			}
			if ($t_end === '' || $t_start === $t_end) {
				return ['day' => $day_str, 'time' => $t_start];
			}

			return ['day' => $day_str, 'time' => $t_start . ' - ' . $t_end];
		}

		/**
		 * Part slugs that support the action-link row (read more, tickets, RSVP).
		 *
		 * @param string $part Part slug.
		 * @return bool
		 */

		public static function ecbb_event_part_is_action_link_part($part)
		{
			return in_array((string) $part, ['read_more', 'event_tickets', 'event_rsvp'], true);
		}

		/**
		 * Whether button chrome applies (btn_style toggle).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */

		public static function ecbb_event_part_button_style_active(array $item)
		{
			return ! empty($item['btn_style']);
		}

		/**
		 * Inner markup for read more / tickets / RSVP.
		 *
		 * Hover on: clickable link (optional button chrome). Hover off: plain text, or styled link when button styles are on.
		 *
		 * @param array<string,mixed> $item         Repeater row.
		 * @param string              $href         Destination URL.
		 * @param string              $label        Visible label.
		 * @param string              $btn_attr     Optional button layout inline attr (no colors).
		 * @param string              $link_attr    Fallback inline attr.
		 * @param string              $extra_attrs  Extra attributes before btn/link attr (e.g. target, rel).
		 * @return string HTML (not escaped as a whole).
		 */

		public static function ecbb_event_part_action_link_inner_html(array $item, $href, $label, $btn_attr = '', $link_attr = '', $extra_attrs = '')
		{
			$hover_on = ! function_exists('ecbb_event_part_hover_style_active') || self::ecbb_event_part_hover_style_active($item);

			if (! $hover_on) {
				if (self::ecbb_event_part_button_style_active($item)) {
					$attr = $btn_attr !== '' ? $btn_attr : $link_attr;
					return '<a class="ecbb-event__link" href="' . esc_url($href) . '"' . $extra_attrs . $attr . '>' . esc_html($label) . '</a>';
				}
				return '<span class="ecbb-event__plain">' . esc_html($label) . '</span>';
			}

			$attr = $extra_attrs;
			if ($btn_attr !== '') {
				$attr .= $btn_attr;
			} elseif ($link_attr !== '') {
				$attr .= $link_attr;
			}

			return '<a class="ecbb-event__link" href="' . esc_url($href) . '"' . $attr . '>' . esc_html($label) . '</a>';
		}

	public static function ecbb_event_part_button_style_attr(array $item, $skin = '')
	{
		unset( $item, $skin );
		return '';
	}

		/**
		 * Whether one cost token is "free" (zero or common free labels).
		 *
		 * @param string $token Single price fragment or whole cost.
		 * @return bool
		 */

		public static function ecbb_cost_token_is_free($token)
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
		 * Currency choices for the Event cost part (per repeater row).
		 *
		 * @return array<string,string>
		 */

		public static function ecbb_event_cost_currency_options()
		{
			return [
				'default' => esc_html__('Site default', 'ecbb'),
				'none'    => esc_html__('No currency symbol', 'ecbb'),
				'USD'     => 'USD ($)',
				'EUR'     => 'EUR (€)',
				'GBP'     => 'GBP (£)',
				'CAD'     => 'CAD ($)',
				'AUD'     => 'AUD ($)',
				'INR'     => 'INR (₹)',
				'JPY'     => 'JPY (¥)',
				'CNY'     => 'CNY (¥)',
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
				'AED'     => 'AED (د.إ)',
				'SAR'     => 'SAR (﷼)',
			];
		}

		/**
		 * @param mixed $value Saved control value.
		 * @return string One of {@see ecbb_event_cost_currency_options()}.
		 */

		public static function ecbb_sanitize_event_cost_currency($value)
		{
			$code = is_string($value) ? strtoupper(trim($value)) : '';
			if ($code === '') {
				return 'default';
			}
			if ('DEFAULT' === $code) {
				return 'default';
			}
			if ('NONE' === $code) {
				return 'none';
			}
			if ('SYMBOL' === $code) {
				return 'default';
			}
			return array_key_exists($code, self::ecbb_event_cost_currency_options()) ? $code : 'default';
		}

		/**
		 * Display symbol for a widget currency code.
		 *
		 * @param string $currency_code Sanitized currency code.
		 * @return string Empty when none / default should not force a symbol.
		 */

		public static function ecbb_event_cost_currency_symbol($currency_code)
		{
			$map = [
				'USD' => '$',
				'EUR' => '€',
				'GBP' => '£',
				'CAD' => '$',
				'AUD' => '$',
				'INR' => '₹',
				'JPY' => '¥',
				'CNY' => '¥',
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
				'AED' => 'د.إ',
				'SAR' => '﷼',
			];
			$currency_code = self::ecbb_sanitize_event_cost_currency($currency_code);
			return isset($map[$currency_code]) ? $map[$currency_code] : '';
		}

		/**
		 * Strip currency symbols from a single cost token.
		 *
		 * @param string $token Cost fragment.
		 * @return string
		 */

		public static function ecbb_strip_cost_currency_symbols($token)
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

		public static function ecbb_format_cost_token_with_currency($token, $currency_code)
		{
			$token = trim(wp_strip_all_tags(html_entity_decode((string) $token, ENT_QUOTES, 'UTF-8')));
			if ($token === '') {
				return '';
			}
			if (self::ecbb_cost_token_is_free($token)) {
				return __('Free', 'ecbb');
			}

			$currency_code = self::ecbb_sanitize_event_cost_currency($currency_code);
			if ($currency_code === 'default') {
				return $token;
			}
			if ($currency_code === 'none') {
				$plain = self::ecbb_strip_cost_currency_symbols($token);
				return $plain !== '' ? $plain : $token;
			}

			$symbol = self::ecbb_event_cost_currency_symbol($currency_code);
			$amount = self::ecbb_strip_cost_currency_symbols($token);
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

		public static function ecbb_apply_event_cost_currency($cost_text, $currency_code)
		{
			$cost_text = trim(wp_strip_all_tags(html_entity_decode((string) $cost_text, ENT_QUOTES, 'UTF-8')));
			if ($cost_text === '') {
				return '';
			}

			$currency_code = self::ecbb_sanitize_event_cost_currency($currency_code);
			if ($currency_code === 'default') {
				return $cost_text;
			}

			if (self::ecbb_cost_token_is_free($cost_text)) {
				return __('Free', 'ecbb');
			}

			if (preg_match('/^(.+?)([-–—])(.+)$/u', $cost_text, $m)) {
				$left  = self::ecbb_format_cost_token_with_currency(trim($m[1]), $currency_code);
				$right = self::ecbb_format_cost_token_with_currency(trim($m[3]), $currency_code);
				if ($left !== '' && $right !== '') {
					if (strcasecmp($left, $right) === 0) {
						return $left;
					}
					return $left . ' – ' . $right;
				}
			}

			return self::ecbb_format_cost_token_with_currency($cost_text, $currency_code);
		}

		/**
		 * Active widget settings while an Events Widget is rendering.
		 *
		 * @param array<string,mixed>|null $settings Pass null to read only.
		 * @return array<string,mixed>
		 */

		public static function ecbb_render_settings($settings = null)
		{
			static $active = [];

			if (is_array($settings)) {
				$active = $settings;
			}

			return $active;
		}

		/**
		 * Resolve cost currency for one Event cost repeater row.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */

		public static function ecbb_resolve_event_cost_currency(array $item = [])
		{
			if (isset($item['cost_currency']) && (string) $item['cost_currency'] !== '') {
				return self::ecbb_sanitize_event_cost_currency($item['cost_currency']);
			}

			$settings = self::ecbb_render_settings();
			if (isset($settings['event_cost_currency'])) {
				return self::ecbb_sanitize_event_cost_currency($settings['event_cost_currency']);
			}

			return 'default';
		}

		/**
		 * Copy legacy widget-level event_cost_currency onto Event cost repeater rows (once per row).
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string,mixed>
		 */

		public static function ecbb_migrate_event_cost_currency_into_repeaters(array $settings)
		{
			if (! isset($settings['event_cost_currency'])) {
				return $settings;
			}

			$currency = self::ecbb_sanitize_event_cost_currency($settings['event_cost_currency']);

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

		/**
		 * Build cost label: "Free", a single amount, or "min – max" for ranges.
		 *
		 * @param int                 $post_id Event post ID.
		 * @param array<string,mixed> $item    Repeater row (legacy fallback only).
		 * @return string Plain text (escaped by caller).
		 */

		public static function ecbb_format_event_cost_display($post_id, array $item = [])
		{
			$post_id = (int) $post_id;
			if ($post_id < 1) {
				return '';
			}

			$currency_code = self::ecbb_resolve_event_cost_currency($item);
			$with_currency = $currency_code === 'default';

			$formatted = '';
			if (function_exists('tribe_get_cost')) {
				$formatted = trim(wp_strip_all_tags(html_entity_decode((string) \tribe_get_cost($post_id, $with_currency), ENT_QUOTES, 'UTF-8')));
			}

			$raw = trim(wp_strip_all_tags(html_entity_decode((string) get_post_meta($post_id, '_EventCost', true), ENT_QUOTES, 'UTF-8')));

			$cost = $formatted !== '' ? $formatted : $raw;
			if ($cost === '') {
				// No cost set on the event — treat as free (matches TEC "no cost" behaviour).
				return __('Free', 'ecbb');
			}

			if (self::ecbb_cost_token_is_free($cost)) {
				return __('Free', 'ecbb');
			}

			if (preg_match('/^(.+?)([-–—])(.+)$/u', $cost, $m)) {
				$left  = trim($m[1]);
				$right = trim($m[3]);
				if ($left !== '' && $right !== '') {
					if (strcasecmp($left, $right) === 0) {
						$cost = self::ecbb_cost_token_is_free($left)
							? __('Free', 'ecbb')
							: $left;
					} elseif (self::ecbb_cost_token_is_free($left) && self::ecbb_cost_token_is_free($right)) {
						$cost = __('Free', 'ecbb');
					} else {
						$cost = $left . ' – ' . $right;
					}
				}
			}

			return self::ecbb_apply_event_cost_currency($cost, $currency_code);
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

		public static function ecbb_terms_list_html(array $terms, array $item, $style_attr = '', $skin = '', $part = 'categories')
		{
			$style_attr = (string) $style_attr;
			$skin       = (string) $skin;
			$part       = sanitize_key((string) $part);
			$link_style = $style_attr !== '' ? ' style="' . esc_attr($style_attr) . '"' : '';
			$chip_each  = ('style1' === $skin && 'categories' === $part);
			$link_terms = ! function_exists('ecbb_event_part_hover_style_active') || self::ecbb_event_part_hover_style_active($item);

			$sep = isset($item['terms_separator']) ? (string) $item['terms_separator'] : ', ';
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

		/**
		 * Short per-row class for Bricks-generated CSS (scoped under .ecbb-ev--{id}).
		 *
		 * @param int $idx Row index.
		 * @return string
		 */

		public static function ecbb_part_idx_class($idx)
		{
			return 'ecbb-p' . absint($idx);
		}

		/**
		 * Whether the title row links to the event (Bricks checkbox).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */

		public static function ecbb_event_part_title_link_active(array $item)
		{
			return self::ecbb_is_truthy_setting($item['link'] ?? false, false);
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

		public static function ecbb_part_wrap_classes($part, $idx, $skin = '', array $item = [])
		{
			$idx_c = self::ecbb_part_idx_class($idx);
			if ((string) $skin === 'style2') {
				$classes = 'ecbb-event-part ' . ecbb_list2_part_class($part) . ' ' . $idx_c;
			} else {
				$bem     = 'ecbb-event-part--' . str_replace('_', '-', (string) $part);
				$classes = 'ecbb-event-part ' . $bem . ' ' . $idx_c;
			}
			if ($item !== []) {
				$row = $item;
				if (function_exists('ecbb_normalize_part_item')) {
					$row = ecbb_normalize_part_item($row);
				}
				$ui_part = isset($item['part']) ? (string) $item['part'] : (string) $part;
				if (
					$ui_part === 'title'
					&& function_exists('ecbb_event_part_title_link_active')
					&& ! self::ecbb_event_part_title_link_active($row)
				) {
					$classes .= ' ecbb-no-hover';
				} elseif (
					function_exists('ecbb_event_part_hover_style_active')
					&& function_exists('ecbb_event_part_supports_hover_style_controls')
					&& self::ecbb_event_part_supports_hover_style_controls($ui_part)
					&& ! self::ecbb_event_part_hover_style_active($row)
				) {
					$classes .= ' ecbb-no-hover';
				}
				if (
					function_exists('ecbb_event_part_button_style_active')
					&& self::ecbb_event_part_button_style_active($row)
					&& function_exists('ecbb_button_part_slugs')
					&& in_array($ui_part, ecbb_button_part_slugs(), true)
				) {
					$classes .= ' ecbb-has-btn';
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

		public static function ecbb_part_field_id(array $item, $idx)
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

		public static function ecbb_part_field_id_attr(array $item, $idx)
		{
			$id = self::ecbb_part_field_id($item, $idx);
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

		public static function ecbb_part_wrapper_attrs(array $item, $idx, $style = '')
		{
			$attrs = self::ecbb_part_field_id_attr($item, $idx);
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

		public static function ecbb_parts_rows_assign_ids(array $rows)
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

		/**
		 * @param \WP_Post $post      Event post.
		 * @param array    $item     Repeater row settings.
		 * @param int      $idx      Row index (for CSS class).
		 * @param string   $style    Inline style attribute value (contents only), or empty.
		 * @param string   $skin     Loop skin: '' or 'style2'.
		 * @return string|false      Markup, empty string when nothing to show, false if not an extended part.
		 */

		public static function ecbb_event_part_extended_markup($post, array $item, $idx, $style, $skin = '')
		{
			if (function_exists('ecbb_normalize_part_item')) {
				$item = ecbb_normalize_part_item($item);
			}
			$part = isset($item['part']) ? (string) $item['part'] : '';
			$idx  = absint($idx);
			$skin = (string) $skin;
			$attr = function_exists('ecbb_part_wrapper_attrs')
				? self::ecbb_part_wrapper_attrs($item, $idx, $style)
				: ($style !== '' ? ' style="' . esc_attr($style) . '"' : '');
			$link_attr = '';
			$detail_parts = [
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
			$extended = ['event_date', 'event_time', 'event_day', 'event_cost', 'event_tickets', 'event_rsvp', 'read_more', 'venue', 'organizer'];
			if (! in_array($part, $extended, true) && ! in_array($part, $detail_parts, true)) {
				return false;
			}

			$wrap = function ($slug) use ($skin, $idx, $item) {
				return esc_attr(self::ecbb_part_wrap_classes($slug, $idx, $skin, $item));
			};

			if ($part === 'venue') {
				return self::ecbb_event_part_venue_markup($post, $item, $idx, $style, $skin);
			}

			if ($part === 'organizer') {
				return self::ecbb_event_part_organizer_markup($post, $item, $idx, $style, $skin);
			}

			$format = ($part === 'event_date' || $part === 'event_time')
				? self::ecbb_event_part_resolve_php_format($part, $item)
				: '';

			if ($part === 'event_date') {
				$html = '';
				if (function_exists('tribe_get_start_date')) {
					$php = $format !== '' ? $format : get_option('date_format');
					$html = (string) \tribe_get_start_date($post->ID, false, $php);
				} else {
					$raw = (string) get_post_meta($post->ID, '_EventStartDate', true);
					$ts  = $raw ? strtotime($raw) : false;
					$php = $format !== '' ? $format : get_option('date_format');
					$html = $ts ? date_i18n($php, $ts) : '';
				}
				$html = trim(wp_strip_all_tags($html));
				if ($html === '') {
					return '';
				}
				return '<div class="' . $wrap('event_date') . '"' . $attr . '>' . esc_html($html) . '</div>';
			}

			if ($part === 'event_time') {
				$html = '';
				$php  = $format !== '' ? $format : get_option('time_format');
				$php  = self::ecbb_time_format_lowercase_meridiem($php);
				if (function_exists('tribe_get_start_time')) {
					$html = (string) \tribe_get_start_time($post->ID, $php);
				} elseif (function_exists('tribe_get_start_date')) {
					$html = (string) \tribe_get_start_date($post->ID, true, $php);
				} else {
					$raw = (string) get_post_meta($post->ID, '_EventStartDate', true);
					$ts  = $raw ? strtotime($raw) : false;
					$html = $ts ? date_i18n($php, $ts) : '';
				}
				$html = self::ecbb_format_time_meridiem_lowercase(trim(wp_strip_all_tags($html)));
				if ($html === '') {
					return '';
				}
				return '<div class="' . $wrap('event_time') . ($skin !== 'style2' && $html !== '' ? ' ecbb-has-row-icon' : '') . '"' . $attr . '>' . esc_html($html) . '</div>';
			}

			if ($part === 'event_day') {
				$html = '';
				if (function_exists('ecbb_event_part_build_day_time_range_parts')) {
					$pr = self::ecbb_event_part_build_day_time_range_parts($post->ID, []);
					$html = isset($pr['day']) ? trim((string) $pr['day']) : '';
				}
				if ($html === '') {
					$raw = (string) get_post_meta($post->ID, '_EventStartDate', true);
					$ts  = $raw ? strtotime($raw) : false;
					$html = $ts ? trim(wp_strip_all_tags(date_i18n('l', $ts))) : '';
				}
				if ($html === '') {
					return '';
				}
				return '<div class="' . $wrap('event_day') . '"' . $attr . '>' . esc_html($html) . '</div>';
			}

			if (in_array($part, $detail_parts, true)) {
				$html = self::ecbb_event_part_detail_plain($post->ID, $part);
				if ($html === '') {
					return '';
				}
				$venue_physical = ['venue_full_address', 'venue_street', 'venue_city', 'venue_state', 'venue_zip', 'venue_country', 'venue_phone'];
				$loc_icon       = in_array($part, $venue_physical, true) ? ' ecbb-has-row-icon' : '';

				if ($part === 'organizer_email' && is_email($html)) {
					return '<div class="' . $wrap($part) . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url('mailto:' . $html) . '"' . $link_attr . '>' . esc_html($html) . '</a></div>';
				}

				$url_parts = ['venue_website', 'event_website', 'organizer_website', 'event_map_link'];
				if (in_array($part, $url_parts, true)) {
					$safe = esc_url_raw($html);
					if (! $safe || ! preg_match('#^https?://#i', $safe)) {
						return '<div class="' . $wrap($part) . '"' . $attr . '>' . esc_html($html) . '</div>';
					}
					$label = isset($item['detail_link_text']) ? trim((string) $item['detail_link_text']) : '';
					if ($label === '') {
						$defaults = [
							'event_map_link'    => __('Open map', 'ecbb'),
							'event_website'     => __('Event website', 'ecbb'),
							'venue_website'     => __('Venue website', 'ecbb'),
							'organizer_website' => __('Organizer website', 'ecbb'),
						];
						$label = isset($defaults[$part]) ? $defaults[$part] : $safe;
					} else {
						$label = sanitize_text_field($label);
					}
					return '<div class="' . $wrap($part) . '"' . $attr . '><a class="ecbb-event__link" href="' . esc_url($safe) . '" rel="noopener noreferrer" target="_blank"' . $link_attr . '>' . esc_html($label) . '</a></div>';
				}

				return '<div class="' . $wrap($part) . $loc_icon . '"' . $attr . '>' . esc_html($html) . '</div>';
			}

			if ($part === 'event_cost') {
				$cost = function_exists('ecbb_format_event_cost_display')
					? self::ecbb_format_event_cost_display($post->ID, $item)
					: '';
				if ($cost === '') {
					return '';
				}
				return '<div class="' . $wrap('event_cost') . '"' . $attr . '>' . esc_html($cost) . '</div>';
			}

			if ($part === 'event_tickets') {
				$url = '';
				if (function_exists('tribe_get_event')) {
					$ev = tribe_get_event($post->ID);
					if ($ev && ! empty($ev->website)) {
						$url = esc_url_raw((string) $ev->website);
					}
				}
				if ($url === '') {
					$m = get_post_meta($post->ID, '_EventUrl', true);
					$url = $m ? esc_url_raw((string) $m) : '';
				}
				if ($url === '') {
					return '';
				}
				$label = isset($item['tickets_link_text']) ? trim((string) $item['tickets_link_text']) : '';
				if ($label === '') {
					$label = esc_html__('Tickets', 'ecbb');
				} else {
					$label = sanitize_text_field($label);
				}
				$btn_attr = self::ecbb_event_part_button_style_attr($item, $skin);
				$inner_el = self::ecbb_event_part_action_link_inner_html(
					$item,
					$url,
					$label,
					$btn_attr,
					$link_attr,
					' rel="noopener noreferrer" target="_blank"'
				);
				return '<div class="' . $wrap('event_tickets') . '"' . $attr . '>' . $inner_el . '</div>';
			}

			if ($part === 'event_rsvp') {
				$url   = get_permalink($post->ID);
				$label = isset($item['rsvp_link_text']) ? trim((string) $item['rsvp_link_text']) : '';
				if ($label === '') {
					$label = esc_html__('RSVP', 'ecbb');
				} else {
					$label = sanitize_text_field($label);
				}
				$frag = '#tribe-tickets__tickets-form';
				if (function_exists('tribe_events_has_tickets') && tribe_events_has_tickets($post->ID)) {
					$url = $url . $frag;
				}
				$btn_attr = self::ecbb_event_part_button_style_attr($item, $skin);
				$inner_el = self::ecbb_event_part_action_link_inner_html($item, $url, $label, $btn_attr, $link_attr);
				return '<div class="' . $wrap('event_rsvp') . '"' . $attr . '>' . $inner_el . '</div>';
			}

			if ($part === 'read_more') {
				$label = isset($item['read_more_text']) ? trim((string) $item['read_more_text']) : '';
				if ($label === '') {
					if ($skin === 'style2') {
						$label = esc_html__('More Details', 'ecbb');
					} else {
						$label = esc_html__('Find Out More', 'ecbb');
					}
				} else {
					$label = sanitize_text_field($label);
				}
				$btn_attr = self::ecbb_event_part_button_style_attr($item, $skin);
				$inner_el = self::ecbb_event_part_action_link_inner_html(
					$item,
					get_permalink($post->ID),
					$label,
					$btn_attr,
					$link_attr
				);
				return '<div class="' . $wrap('read_more') . '"' . $attr . '>' . $inner_el . '</div>';
			}

			return false;
		}

		/**
		 * Bricks-style labels for featured image size dropdowns (matches WP registered sizes).
		 *
		 * @return array<string, string> Slug => label.
		 */

		public static function ecbb_get_image_size_control_options()
		{
			$opts = [
				'' => esc_html__('Default', 'ecbb'),
			];
			$subs = function_exists('wp_get_registered_image_subsizes') ? wp_get_registered_image_subsizes() : [];
			foreach ($subs as $slug => $data) {
				if (! is_string($slug) || $slug === '') {
					continue;
				}
				$w = isset($data['width']) ? (int) $data['width'] : 0;
				$h = isset($data['height']) ? (int) $data['height'] : 0;
				$opts[$slug] = $slug . ' (' . $w . '×' . $h . ')';
			}
			if (function_exists('get_intermediate_image_sizes')) {
				foreach (get_intermediate_image_sizes() as $slug) {
					if (is_string($slug) && $slug !== '' && ! isset($opts[$slug])) {
						$opts[$slug] = $slug;
					}
				}
			}
			$opts['full'] = esc_html__('Full', 'ecbb');
			return $opts;
		}

		/**
		 * @param string $slug   Saved size slug or empty for fallback.
		 * @param string $fallback Used when empty or invalid.
		 * @return string
		 */

		public static function ecbb_sanitize_attachment_image_size($slug, $fallback = 'large')
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

		public static function ecbb_get_image_object_align_control_options()
		{
			return [
				''   => esc_html__('Default', 'ecbb'),
				'tl' => esc_html__('Top left', 'ecbb'),
				'tc' => esc_html__('Top center', 'ecbb'),
				'tr' => esc_html__('Top right', 'ecbb'),
				'ml' => esc_html__('Middle left', 'ecbb'),
				'mc' => esc_html__('Middle center', 'ecbb'),
				'mr' => esc_html__('Middle right', 'ecbb'),
				'bl' => esc_html__('Bottom left', 'ecbb'),
				'bc' => esc_html__('Bottom center', 'ecbb'),
				'br' => esc_html__('Bottom right', 'ecbb'),
			];
		}

		/**
		 * @param string $key Short key (tl, mc, …) or empty.
		 * @return string CSS object-position value or empty when default.
		 */

		public static function ecbb_object_position_from_image_align($key)
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

		public static function ecbb_event_part_interactive_hover_part_slugs()
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

		public static function ecbb_event_part_types_with_hover_style_controls()
		{
			return array_merge(
				self::ecbb_event_part_interactive_hover_part_slugs(),
				['image']
			);
		}

		/**
		 * @param string $part Part slug.
		 * @return bool
		 */

		public static function ecbb_event_part_supports_hover_style_controls($part)
		{
			return in_array((string) $part, self::ecbb_event_part_types_with_hover_style_controls(), true);
		}

		/**
		 * Saved values that mean hover styling is enabled (checkbox legacy + select).
		 *
		 * @return array<int|string|bool>
		 */

		public static function ecbb_hover_toggle_on_values()
		{
			return ['yes', true, 1, '1'];
		}

		/**
		 * Whether a saved hover-toggle value means hover is on.
		 *
		 * @param mixed $value Raw `ecbb_use_hover` from a repeater row.
		 * @return bool
		 */

		public static function ecbb_hover_toggle_value_is_on($value)
		{
			if (in_array($value, self::ecbb_hover_toggle_on_values(), true)) {
				return true;
			}
			if ($value === false || $value === 0 || $value === '0' || $value === 'no') {
				return false;
			}
			if ($value === null || $value === '') {
				return false;
			}
			return self::ecbb_is_truthy_setting($value, true);
		}

		/**
		 * Coerce legacy checkbox values to yes/no so Bricks always persists hover state.
		 *
		 * @param array<string,mixed> $row Repeater row.
		 * @return array<string,mixed>
		 */

		public static function ecbb_normalize_part_hover_toggle_row(array $row)
		{
			if (! array_key_exists('ecbb_use_hover', $row)) {
				return $row;
			}
			$row['ecbb_use_hover'] = self::ecbb_hover_toggle_value_is_on($row['ecbb_use_hover']) ? 'yes' : 'no';
			return $row;
		}

		/**
		 * @param array<int,array<string,mixed>> $rows Repeater rows.
		 * @return array<int,array<string,mixed>>
		 */

		public static function ecbb_normalize_parts_repeater_rows_hover(array $rows)
		{
			foreach ($rows as $index => $row) {
				if (! is_array($row)) {
					continue;
				}
				$part = isset($row['part']) ? (string) $row['part'] : '';
				if (
					$part === ''
					|| ! function_exists('ecbb_event_part_supports_hover_style_controls')
					|| ! self::ecbb_event_part_supports_hover_style_controls($part)
				) {
					continue;
				}
				$rows[$index] = self::ecbb_normalize_part_hover_toggle_row($row);
			}
			return $rows;
		}

		/**
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string,mixed>
		 */

		public static function ecbb_normalize_element_parts_hover_toggles(array $settings)
		{
			foreach (['parts_style1', 'parts_style2', 'parts_grid', 'parts'] as $key) {
				if (empty($settings[$key]) || ! is_array($settings[$key])) {
					continue;
				}
				$settings[$key] = self::ecbb_normalize_parts_repeater_rows_hover($settings[$key]);
			}
			return $settings;
		}

		/**
		 * Whether hover styling is enabled for this row (legacy rows without the key stay on).
		 *
		 * @param array $item Repeater row.
		 * @return bool
		 */

		public static function ecbb_event_part_hover_style_active(array $item)
		{
			$ui_part = isset($item['part']) ? (string) $item['part'] : '';
			if (! self::ecbb_event_part_supports_hover_style_controls($ui_part)) {
				return false;
			}
			if (
				$ui_part === 'title'
				&& function_exists('ecbb_event_part_title_link_active')
				&& ! self::ecbb_event_part_title_link_active($item)
			) {
				return false;
			}
			if (! array_key_exists('ecbb_use_hover', $item)) {
				return true;
			}
			return self::ecbb_hover_toggle_value_is_on($item['ecbb_use_hover']);
		}

		/**
		 * Normalize Bricks checkbox / toggle values saved on repeater rows.
		 *
		 * @param mixed $value   Raw setting.
		 * @param bool  $default Default when value is null (not when key is absent).
		 * @return bool
		 */

		public static function ecbb_is_truthy_setting($value, $default = false)
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
		 * Whether the part uses two attachment sizes (crossfade on hover).
		 *
		 * @param array $item Repeater row.
		 * @return bool
		 */

		public static function ecbb_loop_image_uses_dual_layer(array $item)
		{
			if (function_exists('ecbb_event_part_hover_style_active') && ! self::ecbb_event_part_hover_style_active($item)) {
				return false;
			}
			$base = self::ecbb_sanitize_attachment_image_size($item['image_size'] ?? '', 'large');
			$raw  = isset($item['image_size_hover']) ? trim((string) $item['image_size_hover']) : '';
			if ($raw === '') {
				return false;
			}
			$hover = self::ecbb_sanitize_attachment_image_size($raw, $base);
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

		public static function ecbb_render_loop_featured_images($thumb_id, array $item)
		{
			$thumb_id = (int) $thumb_id;
			if (! $thumb_id) {
				return '';
			}

			if (function_exists('ecbb_event_part_hover_style_active') && ! self::ecbb_event_part_hover_style_active($item)) {
				$item['image_size_hover']              = '';
				$item['ecbb_image_object_align_hover'] = '';
			}

			$size_base = self::ecbb_sanitize_attachment_image_size($item['image_size'] ?? '', 'large');
			$raw_hover = isset($item['image_size_hover']) ? trim((string) $item['image_size_hover']) : '';
			$size_hover = $raw_hover !== '' ? self::ecbb_sanitize_attachment_image_size($raw_hover, $size_base) : '';
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
		 * Append :hover to one or more comma-separated selectors.
		 *
		 * @param string $scope_sel
		 * @return string
		 */

		public static function ecbb_event_part_hover_state_selectors($scope_sel)
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
		 * @param string $anim      One of fade_in_up, fade_in_right, …
		 * @return array{base:string, hover:string}
		 */

		public static function ecbb_event_part_hover_animation_css($scope_sel, $anim)
		{
			$anim = is_string($anim) ? $anim : '';
			$dur  = '0.38s';
			$ease = 'ease';
			$allowed = ['fade_in_up', 'fade_in_right', 'fade_in_down', 'fade_in_left', 'zoom_in', 'zoom_out'];
			if (! in_array($anim, $allowed, true)) {
				return ['base' => '', 'hover' => ''];
			}

			$base  = "{$scope_sel}{transition:transform {$dur} {$ease};transform:none;transform-origin:center center;}";
			$hover = self::ecbb_event_part_hover_state_selectors($scope_sel);

			switch ($anim) {
				case 'fade_in_up':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:translateY(-8px);}',
					];
				case 'fade_in_right':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:translateX(8px);}',
					];
				case 'fade_in_down':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:translateY(8px);}',
					];
				case 'fade_in_left':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:translateX(-8px);}',
					];
				case 'zoom_in':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:scale(1.06);}',
					];
				case 'zoom_out':
					return [
						'base'  => $base,
						'hover' => $hover . '{transform:scale(0.94);}',
					];
				default:
					return ['base' => '', 'hover' => ''];
			}
		}

		/**
		 * @param mixed $value Saved control value.
		 * @return string style-1 or style-2
		 */

		public static function ecbb_sanitize_list_item_style($value)
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

		public static function ecbb_parts_rows_clean(array $parts)
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

		public static function ecbb_parts_slug_stack(array $parts)
		{
			$clean = self::ecbb_parts_rows_clean($parts);
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
		 * Whether raw repeater rows match a layout default stack (order-sensitive slug fingerprint).
		 *
		 * @param array $parts        Raw Bricks repeater rows.
		 * @param array $default_rows Rows from an `ecbb_*_default_parts_rows()` helper.
		 * @return bool
		 */

		public static function ecbb_parts_stack_matches_defaults(array $parts, array $default_rows)
		{
			return self::ecbb_parts_slug_stack($parts) === self::ecbb_parts_slug_stack($default_rows);
		}

		/**
		 * True when every row is empty / missing `part` (Bricks sometimes saves blank rows).
		 *
		 * @param array $parts Raw repeater rows.
		 * @return bool
		 */

		public static function ecbb_parts_array_is_effectively_empty(array $parts)
		{
			return self::ecbb_parts_rows_clean($parts) === [];
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

		public static function ecbb_resolve_event_parts_for_context(array $settings, $template, $item_chrome)
		{
			$prepare = static function ($parts) {
				if (! is_array($parts)) {
					return [];
				}
				if (function_exists('ecbb_parts_rows_assign_ids')) {
					$parts = self::ecbb_parts_rows_assign_ids($parts);
				}
				if (function_exists('ecbb_normalize_parts_repeater_rows_hover')) {
					$parts = self::ecbb_normalize_parts_repeater_rows_hover($parts);
				}
				return $parts;
			};

			$template = is_string($template) ? trim($template) : '';
			if ($template === 'carousel') {
				$template = 'list';
			}
			if (! in_array($template, ['list', 'grid'], true)) {
				$template = 'list';
			}

			$item_chrome = function_exists('ecbb_sanitize_list_item_style')
				? self::ecbb_sanitize_list_item_style($item_chrome)
				: (in_array((string) $item_chrome, ['style-1', 'style-2'], true) ? (string) $item_chrome : 'style-1');

			$non_empty = static function ($key) use ($settings) {
				$v = $settings[$key] ?? null;
				if (! is_array($v) || [] === $v) {
					return null;
				}
				if (
					function_exists('ecbb_parts_array_is_effectively_empty')
					&& self::ecbb_parts_array_is_effectively_empty($v)
				) {
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
		 * @param array  $parts Clean rows (see ecbb_parts_rows_clean).
		 * @param string $slug  Part slug.
		 * @return bool
		 */

		public static function ecbb_parts_has_part(array $parts, $slug)
		{
			$slug = (string) $slug;
			foreach ($parts as $row) {
				if (! is_array($row)) {
					continue;
				}
				if (isset($row['part']) && (string) $row['part'] === $slug) {
					return true;
				}
			}
			return false;
		}
	}
}

if (! function_exists('ecbb_normalize_bricks_color')) {
	function ecbb_normalize_bricks_color(...$args)
	{
		return ECBB_Markup::ecbb_normalize_bricks_color(...$args);
	}
}
if (! function_exists('ecbb_event_part_resolve_php_format')) {
	function ecbb_event_part_resolve_php_format(...$args)
	{
		return ECBB_Markup::ecbb_event_part_resolve_php_format(...$args);
	}
}
if (! function_exists('ecbb_venue_name_plain')) {
	function ecbb_venue_name_plain(...$args)
	{
		return ECBB_Markup::ecbb_venue_name_plain(...$args);
	}
}
if (! function_exists('ecbb_venue_id_for_event')) {
	function ecbb_venue_id_for_event(...$args)
	{
		return ECBB_Markup::ecbb_venue_id_for_event(...$args);
	}
}
if (! function_exists('ecbb_venue_full_address_plain')) {
	function ecbb_venue_full_address_plain(...$args)
	{
		return ECBB_Markup::ecbb_venue_full_address_plain(...$args);
	}
}
if (! function_exists('ecbb_venue_name_and_address_plain')) {
	function ecbb_venue_name_and_address_plain(...$args)
	{
		return ECBB_Markup::ecbb_venue_name_and_address_plain(...$args);
	}
}
if (! function_exists('ecbb_venue_name_and_state_plain')) {
	function ecbb_venue_name_and_state_plain(...$args)
	{
		return ECBB_Markup::ecbb_venue_name_and_state_plain(...$args);
	}
}
if (! function_exists('ecbb_venue_resolved_display')) {
	function ecbb_venue_resolved_display(...$args)
	{
		return ECBB_Markup::ecbb_venue_resolved_display(...$args);
	}
}
if (! function_exists('ecbb_venue_part_uses_full_details')) {
	function ecbb_venue_part_uses_full_details(...$args)
	{
		return ECBB_Markup::ecbb_venue_part_uses_full_details(...$args);
	}
}
if (! function_exists('ecbb_venue_part_plain_text')) {
	function ecbb_venue_part_plain_text(...$args)
	{
		return ECBB_Markup::ecbb_venue_part_plain_text(...$args);
	}
}
if (! function_exists('ecbb_event_part_venue_markup')) {
	function ecbb_event_part_venue_markup(...$args)
	{
		return ECBB_Markup::ecbb_event_part_venue_markup(...$args);
	}
}
if (! function_exists('ecbb_organizer_name_plain')) {
	function ecbb_organizer_name_plain(...$args)
	{
		return ECBB_Markup::ecbb_organizer_name_plain(...$args);
	}
}
if (! function_exists('ecbb_organizer_full_details_plain')) {
	function ecbb_organizer_full_details_plain(...$args)
	{
		return ECBB_Markup::ecbb_organizer_full_details_plain(...$args);
	}
}
if (! function_exists('ecbb_organizer_part_uses_full_details')) {
	function ecbb_organizer_part_uses_full_details(...$args)
	{
		return ECBB_Markup::ecbb_organizer_part_uses_full_details(...$args);
	}
}
if (! function_exists('ecbb_organizer_part_plain_text')) {
	function ecbb_organizer_part_plain_text(...$args)
	{
		return ECBB_Markup::ecbb_organizer_part_plain_text(...$args);
	}
}
if (! function_exists('ecbb_event_part_organizer_markup')) {
	function ecbb_event_part_organizer_markup(...$args)
	{
		return ECBB_Markup::ecbb_event_part_organizer_markup(...$args);
	}
}
if (! function_exists('ecbb_event_part_detail_plain')) {
	function ecbb_event_part_detail_plain(...$args)
	{
		return ECBB_Markup::ecbb_event_part_detail_plain(...$args);
	}
}
if (! function_exists('ecbb_event_part_build_day_time_range_parts')) {
	function ecbb_event_part_build_day_time_range_parts(...$args)
	{
		return ECBB_Markup::ecbb_event_part_build_day_time_range_parts(...$args);
	}
}
if (! function_exists('ecbb_event_part_is_action_link_part')) {
	function ecbb_event_part_is_action_link_part(...$args)
	{
		return ECBB_Markup::ecbb_event_part_is_action_link_part(...$args);
	}
}
if (! function_exists('ecbb_event_part_button_style_active')) {
	function ecbb_event_part_button_style_active(...$args)
	{
		return ECBB_Markup::ecbb_event_part_button_style_active(...$args);
	}
}
if (! function_exists('ecbb_event_part_action_link_inner_html')) {
	function ecbb_event_part_action_link_inner_html(...$args)
	{
		return ECBB_Markup::ecbb_event_part_action_link_inner_html(...$args);
	}
}
if (! function_exists('ecbb_event_part_button_style_attr')) {
	function ecbb_event_part_button_style_attr(...$args)
	{
		return ECBB_Markup::ecbb_event_part_button_style_attr(...$args);
	}
}
if (! function_exists('ecbb_cost_token_is_free')) {
	function ecbb_cost_token_is_free(...$args)
	{
		return ECBB_Markup::ecbb_cost_token_is_free(...$args);
	}
}
if (! function_exists('ecbb_event_cost_currency_options')) {
	function ecbb_event_cost_currency_options(...$args)
	{
		return ECBB_Markup::ecbb_event_cost_currency_options(...$args);
	}
}
if (! function_exists('ecbb_sanitize_event_cost_currency')) {
	function ecbb_sanitize_event_cost_currency(...$args)
	{
		return ECBB_Markup::ecbb_sanitize_event_cost_currency(...$args);
	}
}
if (! function_exists('ecbb_event_cost_currency_symbol')) {
	function ecbb_event_cost_currency_symbol(...$args)
	{
		return ECBB_Markup::ecbb_event_cost_currency_symbol(...$args);
	}
}
if (! function_exists('ecbb_strip_cost_currency_symbols')) {
	function ecbb_strip_cost_currency_symbols(...$args)
	{
		return ECBB_Markup::ecbb_strip_cost_currency_symbols(...$args);
	}
}
if (! function_exists('ecbb_format_cost_token_with_currency')) {
	function ecbb_format_cost_token_with_currency(...$args)
	{
		return ECBB_Markup::ecbb_format_cost_token_with_currency(...$args);
	}
}
if (! function_exists('ecbb_apply_event_cost_currency')) {
	function ecbb_apply_event_cost_currency(...$args)
	{
		return ECBB_Markup::ecbb_apply_event_cost_currency(...$args);
	}
}
if (! function_exists('ecbb_render_settings')) {
	function ecbb_render_settings(...$args)
	{
		return ECBB_Markup::ecbb_render_settings(...$args);
	}
}
if (! function_exists('ecbb_resolve_event_cost_currency')) {
	function ecbb_resolve_event_cost_currency(...$args)
	{
		return ECBB_Markup::ecbb_resolve_event_cost_currency(...$args);
	}
}
if (! function_exists('ecbb_migrate_event_cost_currency_into_repeaters')) {
	function ecbb_migrate_event_cost_currency_into_repeaters(...$args)
	{
		return ECBB_Markup::ecbb_migrate_event_cost_currency_into_repeaters(...$args);
	}
}
if (! function_exists('ecbb_format_event_cost_display')) {
	function ecbb_format_event_cost_display(...$args)
	{
		return ECBB_Markup::ecbb_format_event_cost_display(...$args);
	}
}
if (! function_exists('ecbb_terms_list_html')) {
	function ecbb_terms_list_html(...$args)
	{
		return ECBB_Markup::ecbb_terms_list_html(...$args);
	}
}
if (! function_exists('ecbb_part_idx_class')) {
	function ecbb_part_idx_class(...$args)
	{
		return ECBB_Markup::ecbb_part_idx_class(...$args);
	}
}
if (! function_exists('ecbb_event_part_title_link_active')) {
	function ecbb_event_part_title_link_active(...$args)
	{
		return ECBB_Markup::ecbb_event_part_title_link_active(...$args);
	}
}
if (! function_exists('ecbb_part_wrap_classes')) {
	function ecbb_part_wrap_classes(...$args)
	{
		return ECBB_Markup::ecbb_part_wrap_classes(...$args);
	}
}
if (! function_exists('ecbb_part_field_id')) {
	function ecbb_part_field_id(...$args)
	{
		return ECBB_Markup::ecbb_part_field_id(...$args);
	}
}
if (! function_exists('ecbb_part_field_id_attr')) {
	function ecbb_part_field_id_attr(...$args)
	{
		return ECBB_Markup::ecbb_part_field_id_attr(...$args);
	}
}
if (! function_exists('ecbb_part_wrapper_attrs')) {
	function ecbb_part_wrapper_attrs(...$args)
	{
		return ECBB_Markup::ecbb_part_wrapper_attrs(...$args);
	}
}
if (! function_exists('ecbb_parts_rows_assign_ids')) {
	function ecbb_parts_rows_assign_ids(...$args)
	{
		return ECBB_Markup::ecbb_parts_rows_assign_ids(...$args);
	}
}
if (! function_exists('ecbb_event_part_extended_markup')) {
	function ecbb_event_part_extended_markup(...$args)
	{
		return ECBB_Markup::ecbb_event_part_extended_markup(...$args);
	}
}
if (! function_exists('ecbb_get_image_size_control_options')) {
	function ecbb_get_image_size_control_options(...$args)
	{
		return ECBB_Markup::ecbb_get_image_size_control_options(...$args);
	}
}
if (! function_exists('ecbb_sanitize_attachment_image_size')) {
	function ecbb_sanitize_attachment_image_size(...$args)
	{
		return ECBB_Markup::ecbb_sanitize_attachment_image_size(...$args);
	}
}
if (! function_exists('ecbb_get_image_object_align_control_options')) {
	function ecbb_get_image_object_align_control_options(...$args)
	{
		return ECBB_Markup::ecbb_get_image_object_align_control_options(...$args);
	}
}
if (! function_exists('ecbb_object_position_from_image_align')) {
	function ecbb_object_position_from_image_align(...$args)
	{
		return ECBB_Markup::ecbb_object_position_from_image_align(...$args);
	}
}
if (! function_exists('ecbb_event_part_interactive_hover_part_slugs')) {
	function ecbb_event_part_interactive_hover_part_slugs(...$args)
	{
		return ECBB_Markup::ecbb_event_part_interactive_hover_part_slugs(...$args);
	}
}
if (! function_exists('ecbb_event_part_types_with_hover_style_controls')) {
	function ecbb_event_part_types_with_hover_style_controls(...$args)
	{
		return ECBB_Markup::ecbb_event_part_types_with_hover_style_controls(...$args);
	}
}
if (! function_exists('ecbb_event_part_supports_hover_style_controls')) {
	function ecbb_event_part_supports_hover_style_controls(...$args)
	{
		return ECBB_Markup::ecbb_event_part_supports_hover_style_controls(...$args);
	}
}
if (! function_exists('ecbb_hover_toggle_on_values')) {
	function ecbb_hover_toggle_on_values(...$args)
	{
		return ECBB_Markup::ecbb_hover_toggle_on_values(...$args);
	}
}
if (! function_exists('ecbb_hover_toggle_value_is_on')) {
	function ecbb_hover_toggle_value_is_on(...$args)
	{
		return ECBB_Markup::ecbb_hover_toggle_value_is_on(...$args);
	}
}
if (! function_exists('ecbb_normalize_part_hover_toggle_row')) {
	function ecbb_normalize_part_hover_toggle_row(...$args)
	{
		return ECBB_Markup::ecbb_normalize_part_hover_toggle_row(...$args);
	}
}
if (! function_exists('ecbb_normalize_parts_repeater_rows_hover')) {
	function ecbb_normalize_parts_repeater_rows_hover(...$args)
	{
		return ECBB_Markup::ecbb_normalize_parts_repeater_rows_hover(...$args);
	}
}
if (! function_exists('ecbb_normalize_element_parts_hover_toggles')) {
	function ecbb_normalize_element_parts_hover_toggles(...$args)
	{
		return ECBB_Markup::ecbb_normalize_element_parts_hover_toggles(...$args);
	}
}
if (! function_exists('ecbb_event_part_hover_style_active')) {
	function ecbb_event_part_hover_style_active(...$args)
	{
		return ECBB_Markup::ecbb_event_part_hover_style_active(...$args);
	}
}
if (! function_exists('ecbb_is_truthy_setting')) {
	function ecbb_is_truthy_setting(...$args)
	{
		return ECBB_Markup::ecbb_is_truthy_setting(...$args);
	}
}
if (! function_exists('ecbb_loop_image_uses_dual_layer')) {
	function ecbb_loop_image_uses_dual_layer(...$args)
	{
		return ECBB_Markup::ecbb_loop_image_uses_dual_layer(...$args);
	}
}
if (! function_exists('ecbb_render_loop_featured_images')) {
	function ecbb_render_loop_featured_images(...$args)
	{
		return ECBB_Markup::ecbb_render_loop_featured_images(...$args);
	}
}
if (! function_exists('ecbb_event_part_hover_state_selectors')) {
	function ecbb_event_part_hover_state_selectors(...$args)
	{
		return ECBB_Markup::ecbb_event_part_hover_state_selectors(...$args);
	}
}
if (! function_exists('ecbb_event_part_hover_animation_css')) {
	function ecbb_event_part_hover_animation_css(...$args)
	{
		return ECBB_Markup::ecbb_event_part_hover_animation_css(...$args);
	}
}
if (! function_exists('ecbb_sanitize_list_item_style')) {
	function ecbb_sanitize_list_item_style(...$args)
	{
		return ECBB_Markup::ecbb_sanitize_list_item_style(...$args);
	}
}
if (! function_exists('ecbb_parts_rows_clean')) {
	function ecbb_parts_rows_clean(...$args)
	{
		return ECBB_Markup::ecbb_parts_rows_clean(...$args);
	}
}
if (! function_exists('ecbb_parts_slug_stack')) {
	function ecbb_parts_slug_stack(...$args)
	{
		return ECBB_Markup::ecbb_parts_slug_stack(...$args);
	}
}
if (! function_exists('ecbb_parts_stack_matches_defaults')) {
	function ecbb_parts_stack_matches_defaults(...$args)
	{
		return ECBB_Markup::ecbb_parts_stack_matches_defaults(...$args);
	}
}
if (! function_exists('ecbb_parts_array_is_effectively_empty')) {
	function ecbb_parts_array_is_effectively_empty(...$args)
	{
		return ECBB_Markup::ecbb_parts_array_is_effectively_empty(...$args);
	}
}
if (! function_exists('ecbb_resolve_event_parts_for_context')) {
	function ecbb_resolve_event_parts_for_context(...$args)
	{
		return ECBB_Markup::ecbb_resolve_event_parts_for_context(...$args);
	}
}
if (! function_exists('ecbb_parts_has_part')) {
	function ecbb_parts_has_part(...$args)
	{
		return ECBB_Markup::ecbb_parts_has_part(...$args);
	}
}
