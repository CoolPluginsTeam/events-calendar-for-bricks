<?php

/**
 * Query helpers for the Events Widget (TEC tribe_get_events args).
 *
 * @package ECBB
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('ECBB_Query', false)) {

	final class ECBB_Query
	{

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return string all|between
		 */
		public static function ecbb_time_mode(array $settings)
		{
			$time_mode = isset($settings['event_time_mode']) ? (string) $settings['event_time_mode'] : 'all';
			return 'between' === $time_mode ? 'between' : 'all';
		}

		/**
		 * Inclusive calendar-day bounds from Bricks datepicker strings (site timezone).
		 *
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array{0:string,1:string} Two MySQL datetime strings, or both empty if invalid.
		 */
		public static function ecbb_range_bounds(array $settings)
		{
			$range_start_raw = isset($settings['event_range_start']) ? trim((string) $settings['event_range_start']) : '';
			$range_end_raw   = isset($settings['event_range_end']) ? trim((string) $settings['event_range_end']) : '';
			if ('' === $range_start_raw || '' === $range_end_raw) {
				return ['', ''];
			}

			$start_timestamp = strtotime($range_start_raw);
			$end_timestamp   = strtotime($range_end_raw);
			if (! $start_timestamp || ! $end_timestamp) {
				return ['', ''];
			}

			if ($start_timestamp > $end_timestamp) {
				$swap            = $start_timestamp;
				$start_timestamp = $end_timestamp;
				$end_timestamp   = $swap;
			}

			$range_start = wp_date('Y-m-d 00:00:00', $start_timestamp);
			$range_end   = wp_date('Y-m-d 23:59:59', $end_timestamp);

			return [$range_start, $range_end];
		}

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return string[] Category slugs (tribe_events_cat).
		 */
		public static function ecbb_category_slugs(array $settings)
		{
			$category_slugs = [];

			if (! empty($settings['event_categories']) && is_array($settings['event_categories'])) {
				foreach ($settings['event_categories'] as $category_slug) {
					if (is_array($category_slug)) {
						if (isset($category_slug['value'])) {
							$category_slug = $category_slug['value'];
						} elseif (isset($category_slug['name'])) {
							$category_slug = $category_slug['name'];
						} else {
							continue;
						}
					}

					$sanitized_slug = sanitize_title((string) $category_slug);
					if ('' !== $sanitized_slug) {
						$category_slugs[] = $sanitized_slug;
					}
				}

				return array_values(array_unique($category_slugs));
			}

			if (! empty($settings['category_slug'])) {
				$sanitized_slug = sanitize_title((string) $settings['category_slug']);
				if ('' !== $sanitized_slug) {
					return [$sanitized_slug];
				}
			}

			return [];
		}

		/**
		 * Build meta_query for _EventStartDate from type + optional between range.
		 *
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array<int|string, mixed> Meta query for WP_Query / tribe_get_events.
		 */
		public static function ecbb_date_meta_query(array $settings)
		{
			$meta_clauses = [];
			$time_mode    = self::ecbb_time_mode($settings);
			$time_type    = isset($settings['event_type']) && (string) $settings['event_type'] !== ''
				? (string) $settings['event_type']
				: 'all';

			if ('between' === $time_mode) {
				list($range_start, $range_end) = self::ecbb_range_bounds($settings);
				if ('' !== $range_start && '' !== $range_end) {
					$meta_clauses[] = [
						'key'     => '_EventStartDate',
						'value'   => [$range_start, $range_end],
						'compare' => 'BETWEEN',
						'type'    => 'DATETIME',
					];
				}
			}

			$now_mysql = current_time('mysql');
			if ('future' === $time_type) {
				$meta_clauses[] = [
					'key'     => '_EventStartDate',
					'value'   => $now_mysql,
					'compare' => '>=',
					'type'    => 'DATETIME',
				];
			} elseif ('past' === $time_type) {
				$meta_clauses[] = [
					'key'     => '_EventStartDate',
					'value'   => $now_mysql,
					'compare' => '<',
					'type'    => 'DATETIME',
				];
			}

			if (empty($meta_clauses)) {
				return [];
			}

			if (count($meta_clauses) > 1) {
				return array_merge(['relation' => 'AND'], $meta_clauses);
			}

			return $meta_clauses;
		}

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array<int, array<string, mixed>> Tax query clauses.
		 */
		public static function ecbb_tax_query(array $settings)
		{
			$category_slugs = self::ecbb_category_slugs($settings);
			if (empty($category_slugs)) {
				return [];
			}

			return [
				[
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'slug',
					'terms'    => $category_slugs,
					'operator' => 'IN',
				],
			];
		}

		/**
		 * Args for tribe_get_events() / matching WP_Query shape used by the Events Widget.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string, mixed>
		 */
		public static function ecbb_tribe_args(array $settings)
		{
			$posts_per_page = array_key_exists('posts_per_page', $settings) ? (int) $settings['posts_per_page'] : 10;
			$order          = ! empty($settings['order']) && strtoupper((string) $settings['order']) === 'DESC' ? 'DESC' : 'ASC';

			$query_args = [
				'posts_per_page' => $posts_per_page,
				'order'          => $order,
				'orderby'        => 'meta_value',
				'meta_key'       => '_EventStartDate',
				'meta_type'      => 'DATETIME',
			];

			$meta_query = self::ecbb_date_meta_query($settings);
			if (! empty($meta_query)) {
				$query_args['meta_query'] = $meta_query;
			}

			$tax_query = self::ecbb_tax_query($settings);
			if (! empty($tax_query)) {
				$query_args['tax_query'] = $tax_query;
			}

			return $query_args;
		}

		/**
		 * Query events for the initial widget render.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return \WP_Post[]
		 */
		public static function ecbb_fetch_events(array $settings)
		{
			$events = function_exists('tribe_get_events')
				? tribe_get_events(self::ecbb_tribe_args($settings))
				: [];

			return is_array($events) ? $events : [];
		}
	}
}
