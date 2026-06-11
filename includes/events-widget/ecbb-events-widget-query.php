<?php
/**
 * Query helpers for the Events Widget (TEC tribe_get_events args, load-more AJAX).
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
function ecbb_events_widget_query_resolve_event_time_type( array $settings ) {
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
	$legacy = isset( $settings['status'] ) ? (string) $settings['status'] : 'upcoming';
	if ( 'past' === $legacy ) {
		return 'past';
	}
	if ( 'all' === $legacy ) {
		return 'all';
	}
	return 'upcoming';
}

/**
 * @param array $settings Element or AJAX settings.
 * @return string all|between
 */
function ecbb_events_widget_query_event_time_mode( array $settings ) {
	$m = isset( $settings['event_time_mode'] ) ? (string) $settings['event_time_mode'] : 'all';
	return 'between' === $m ? 'between' : 'all';
}

/**
 * Inclusive calendar-day bounds from Bricks datepicker strings (site timezone).
 *
 * @param array $settings Element or AJAX settings.
 * @return array{0:string,1:string} Two MySQL datetime strings or both empty if invalid.
 */
function ecbb_events_widget_query_range_bounds( array $settings ) {
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
function ecbb_events_widget_query_category_slugs( array $settings ) {
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
function ecbb_events_widget_query_build_date_meta_query( array $settings ) {
	$clauses = [];
	$mode    = ecbb_events_widget_query_event_time_mode( $settings );
	$type    = ecbb_events_widget_query_resolve_event_time_type( $settings );

	if ( 'between' === $mode ) {
		list( $s, $e ) = ecbb_events_widget_query_range_bounds( $settings );
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
function ecbb_events_widget_query_build_tax_query( array $settings ) {
	$slugs = ecbb_events_widget_query_category_slugs( $settings );
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
function ecbb_events_widget_query_tribe_args( array $settings ) {
	$ppp = array_key_exists( 'posts_per_page', $settings ) ? (int) $settings['posts_per_page'] : 10;
	$order = ! empty( $settings['order'] ) && strtoupper( (string) $settings['order'] ) === 'DESC' ? 'DESC' : 'ASC';

	$args = [
		'posts_per_page' => $ppp,
		'order'          => $order,
		'orderby'        => 'meta_value',
		'meta_key'       => '_EventStartDate',
		'meta_type'      => 'DATETIME',
	];

	$mq = ecbb_events_widget_query_build_date_meta_query( $settings );
	if ( ! empty( $mq ) ) {
		$args['meta_query'] = $mq;
	}

	$tq = ecbb_events_widget_query_build_tax_query( $settings );
	if ( ! empty( $tq ) ) {
		$args['tax_query'] = $tq;
	}

	return $args;
}

/**
 * Sanitize load-more AJAX settings (scalar keys used by the handler).
 *
 * Preserves repeater arrays; validates known fields only.
 *
 * @param array $settings Decoded JSON settings from the client.
 * @return array
 */
function ecbb_events_widget_sanitize_load_more_settings( array $settings ) {
	$out = $settings;

	$template = isset( $out['layout_template'] ) ? sanitize_key( (string) $out['layout_template'] ) : 'list';
	if ( 'carousel' === $template ) {
		$template = 'list';
	}
	$out['layout_template'] = in_array( $template, [ 'list', 'grid' ], true ) ? $template : 'list';

	if ( function_exists( 'ecbb_sanitize_list_item_style' ) ) {
		$out['list_item_style'] = ecbb_sanitize_list_item_style( $out['list_item_style'] ?? 'style-1' );
	}

	if ( array_key_exists( 'style2_show_month_headings', $out ) ) {
		$out['style2_show_month_headings'] = ecbb_sanitize_style2_show_month_headings( $out['style2_show_month_headings'] );
	}

	if ( isset( $out['item_gap'] ) ) {
		$out['item_gap'] = max( 0, (float) $out['item_gap'] );
	}

	$item_gap_unit = isset( $out['item_gap_unit'] ) ? (string) $out['item_gap_unit'] : 'px';
	$out['item_gap_unit'] = in_array( $item_gap_unit, [ 'px', 'rem', 'em' ], true ) ? $item_gap_unit : 'px';

	if ( isset( $out['date_format'] ) && function_exists( 'ecbb_list1_sanitize_date_format' ) ) {
		$out['date_format'] = ecbb_list1_sanitize_date_format( $out['date_format'] );
	}

	if ( isset( $out['event_categories'] ) && is_array( $out['event_categories'] ) ) {
		$out['event_categories'] = array_values(
			array_filter(
				array_map( 'sanitize_title', $out['event_categories'] )
			)
		);
	}

	if ( isset( $out['order'] ) ) {
		$out['order'] = ( ! empty( $out['order'] ) && 'DESC' === strtoupper( (string) $out['order'] ) ) ? 'DESC' : 'ASC';
	}

	if ( isset( $out['event_type'] ) ) {
		$type = sanitize_key( (string) $out['event_type'] );
		$out['event_type'] = in_array( $type, [ 'past', 'future', 'all' ], true ) ? $type : 'future';
	}

	if ( isset( $out['event_time_mode'] ) ) {
		$mode = sanitize_key( (string) $out['event_time_mode'] );
		$out['event_time_mode'] = ( 'between' === $mode ) ? 'between' : 'all';
	}

	if ( isset( $out['event_range_start'] ) ) {
		$out['event_range_start'] = sanitize_text_field( (string) $out['event_range_start'] );
	}

	if ( isset( $out['event_range_end'] ) ) {
		$out['event_range_end'] = sanitize_text_field( (string) $out['event_range_end'] );
	}

	return $out;
}
