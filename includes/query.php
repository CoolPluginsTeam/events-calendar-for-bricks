<?php
/**
 * Query helpers for the Events Widget (TEC tribe_get_events args).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map saved settings to TEC “time type”: upcoming | past | all (legacy `status` supported).
 *
 * @param array $settings Element or AJAX settings.
 * @return string upcoming|past|all
 */

if ( ! class_exists( 'ECBB_Query', false ) ) {

	final class ECBB_Query {

	public static function ecbb_query_resolve_event_time_type( array $settings ) {
	if ( isset( $settings['event_type'] ) && (string) $settings['event_type'] !== '' ) {
		$t = (string) $settings['event_type'];
		if ( 'future' === $t ) {
			return 'upcoming';
		}
		if ( 'past' === $t ) {
			return 'past';
		}
		return 'all';
	}
	$legacy = isset( $settings['status'] ) ? (string) $settings['status'] : 'all';
	if ( 'past' === $legacy ) {
		return 'past';
	}
	if ( 'all' === $legacy ) {
		return 'all';
	}
	if ( 'upcoming' === $legacy || 'future' === $legacy ) {
		return 'upcoming';
	}
	return 'all';
}

/**
 * @param array $settings Element or AJAX settings.
 * @return string all|between
 */

	public static function ecbb_query_event_time_mode( array $settings ) {
	$m = isset( $settings['event_time_mode'] ) ? (string) $settings['event_time_mode'] : 'all';
	return 'between' === $m ? 'between' : 'all';
}

/**
 * Inclusive calendar-day bounds from Bricks datepicker strings (site timezone).
 *
 * @param array $settings Element or AJAX settings.
 * @return array{0:string,1:string} Two MySQL datetime strings or both empty if invalid.
 */

	public static function ecbb_query_range_bounds( array $settings ) {
	$raw_s = isset( $settings['event_range_start'] ) ? trim( (string) $settings['event_range_start'] ) : '';
	$raw_e = isset( $settings['event_range_end'] ) ? trim( (string) $settings['event_range_end'] ) : '';
	if ( '' === $raw_s || '' === $raw_e ) {
		return [ '', '' ];
	}
	$ts_s = strtotime( $raw_s );
	$ts_e = strtotime( $raw_e );
	if ( ! $ts_s || ! $ts_e ) {
		return [ '', '' ];
	}
	if ( $ts_s > $ts_e ) {
		$tmp  = $ts_s;
		$ts_s = $ts_e;
		$ts_e = $tmp;
	}
	$start = wp_date( 'Y-m-d 00:00:00', $ts_s );
	$end   = wp_date( 'Y-m-d 23:59:59', $ts_e );
	return [ $start, $end ];
}

/**
 * @param array $settings Element or AJAX settings.
 * @return string[] Category slugs (tribe_events_cat).
 */

	public static function ecbb_query_category_slugs( array $settings ) {
	$out = [];
	if ( ! empty( $settings['event_categories'] ) && is_array( $settings['event_categories'] ) ) {
		foreach ( $settings['event_categories'] as $slug ) {
			if ( is_array( $slug ) ) {
				if ( isset( $slug['value'] ) ) {
					$slug = $slug['value'];
				} elseif ( isset( $slug['name'] ) ) {
					$slug = $slug['name'];
				} else {
					continue;
				}
			}
			$s = sanitize_title( (string) $slug );
			if ( '' !== $s ) {
				$out[] = $s;
			}
		}
		return array_values( array_unique( $out ) );
	}
	if ( ! empty( $settings['category_slug'] ) ) {
		$s = sanitize_title( (string) $settings['category_slug'] );
		if ( '' !== $s ) {
			return [ $s ];
		}
	}
	return [];
}

/**
 * Build meta_query for _EventStartDate from type + optional between range.
 *
 * @param array $settings Element or AJAX settings.
 * @return array<int|string, mixed> Meta query for WP_Query / tribe_get_events.
 */

	public static function ecbb_query_build_date_meta_query( array $settings ) {
	$clauses = [];
	$mode    = self::ecbb_query_event_time_mode( $settings );
	$type    = self::ecbb_query_resolve_event_time_type( $settings );

	if ( 'between' === $mode ) {
		list( $s, $e ) = self::ecbb_query_range_bounds( $settings );
		if ( '' !== $s && '' !== $e ) {
			$clauses[] = [
				'key'     => '_EventStartDate',
				'value'   => [ $s, $e ],
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			];
		}
	}

	$now = current_time( 'mysql' );
	if ( 'upcoming' === $type ) {
		$clauses[] = [
			'key'     => '_EventStartDate',
			'value'   => $now,
			'compare' => '>=',
			'type'    => 'DATETIME',
		];
	} elseif ( 'past' === $type ) {
		$clauses[] = [
			'key'     => '_EventStartDate',
			'value'   => $now,
			'compare' => '<',
			'type'    => 'DATETIME',
		];
	}

	if ( empty( $clauses ) ) {
		return [];
	}
	if ( count( $clauses ) > 1 ) {
		return array_merge( [ 'relation' => 'AND' ], $clauses );
	}
	return $clauses;
}

/**
 * @param array $settings Element or AJAX settings.
 * @return array<int, array<string, mixed>> Tax query clauses.
 */

	public static function ecbb_query_build_tax_query( array $settings ) {
	$slugs = self::ecbb_query_category_slugs( $settings );
	if ( empty( $slugs ) ) {
		return [];
	}
	return [
		[
			'taxonomy' => 'tribe_events_cat',
			'field'    => 'slug',
			'terms'    => $slugs,
			'operator' => 'IN',
		],
	];
}

/**
 * Args for tribe_get_events() / matching WP_Query shape used by the Events Widget.
 *
 * @param array $settings Element settings.
 * @return array<string, mixed>
 */

	public static function ecbb_query_tribe_args( array $settings ) {
	$ppp = array_key_exists( 'posts_per_page', $settings ) ? (int) $settings['posts_per_page'] : 10;
	$order = ! empty( $settings['order'] ) && strtoupper( (string) $settings['order'] ) === 'DESC' ? 'DESC' : 'ASC';

	$args = [
		'posts_per_page' => $ppp,
		'order'          => $order,
		'orderby'        => 'meta_value',
		'meta_key'       => '_EventStartDate',
		'meta_type'      => 'DATETIME',
	];

	$mq = self::ecbb_query_build_date_meta_query( $settings );
	if ( ! empty( $mq ) ) {
		$args['meta_query'] = $mq;
	}

	$tq = self::ecbb_query_build_tax_query( $settings );
	if ( ! empty( $tq ) ) {
		$args['tax_query'] = $tq;
	}

	return $args;
}

/**
 * Query events for the initial widget render.
 *
 * @param array $settings Element settings.
 * @return \WP_Post[]
 */

	public static function ecbb_fetch_events_for_display( array $settings ) {
	$events = function_exists( 'tribe_get_events' ) ? tribe_get_events( self::ecbb_query_tribe_args( $settings ) ) : [];
	if ( ! is_array( $events ) ) {
		$events = [];
	}

	return $events;
}

	}

}

if ( ! function_exists( 'ecbb_query_resolve_event_time_type' ) ) {
	function ecbb_query_resolve_event_time_type( ...$args ) {
		return ECBB_Query::ecbb_query_resolve_event_time_type( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_event_time_mode' ) ) {
	function ecbb_query_event_time_mode( ...$args ) {
		return ECBB_Query::ecbb_query_event_time_mode( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_range_bounds' ) ) {
	function ecbb_query_range_bounds( ...$args ) {
		return ECBB_Query::ecbb_query_range_bounds( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_category_slugs' ) ) {
	function ecbb_query_category_slugs( ...$args ) {
		return ECBB_Query::ecbb_query_category_slugs( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_build_date_meta_query' ) ) {
	function ecbb_query_build_date_meta_query( ...$args ) {
		return ECBB_Query::ecbb_query_build_date_meta_query( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_build_tax_query' ) ) {
	function ecbb_query_build_tax_query( ...$args ) {
		return ECBB_Query::ecbb_query_build_tax_query( ...$args );
	}
}
if ( ! function_exists( 'ecbb_query_tribe_args' ) ) {
	function ecbb_query_tribe_args( ...$args ) {
		return ECBB_Query::ecbb_query_tribe_args( ...$args );
	}
}
if ( ! function_exists( 'ecbb_fetch_events_for_display' ) ) {
	function ecbb_fetch_events_for_display( ...$args ) {
		return ECBB_Query::ecbb_fetch_events_for_display( ...$args );
	}
}
