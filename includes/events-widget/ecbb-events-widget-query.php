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

	if ( isset( $out['item_gap'] ) && is_array( $out['item_gap'] ) ) {
		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
			if ( isset( $out['item_gap'][ $device ] ) && $out['item_gap'][ $device ] !== '' ) {
				$out['item_gap'][ $device ] = max( 0, (float) $out['item_gap'][ $device ] );
			}
		}
	} elseif ( isset( $out['item_gap'] ) ) {
		$out['item_gap'] = max( 0, (float) $out['item_gap'] );
	}

	foreach ( array_keys( $out ) as $setting_key ) {
		if ( strpos( (string) $setting_key, 'item_gap:' ) === 0 ) {
			$out[ $setting_key ] = max( 0, (float) $out[ $setting_key ] );
		}
	}

	if ( isset( $out['grid_cols'] ) ) {
		if ( is_array( $out['grid_cols'] ) ) {
			foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
				if ( isset( $out['grid_cols'][ $device ] ) && $out['grid_cols'][ $device ] !== '' ) {
					$out['grid_cols'][ $device ] = max( 1, (int) $out['grid_cols'][ $device ] );
				}
			}
		} else {
			$out['grid_cols'] = max( 1, (int) $out['grid_cols'] );
		}
	}

	foreach ( array_keys( $out ) as $setting_key ) {
		if ( strpos( (string) $setting_key, 'grid_cols:' ) === 0 ) {
			$out[ $setting_key ] = max( 1, (int) $out[ $setting_key ] );
		}
	}

	foreach ( [ 'grid_cols_desktop', 'grid_cols_tablet', 'grid_cols_mobile' ] as $legacy_cols_key ) {
		if ( isset( $out[ $legacy_cols_key ] ) ) {
			$out[ $legacy_cols_key ] = max( 1, (int) $out[ $legacy_cols_key ] );
		}
	}

	$item_gap_unit = isset( $out['item_gap_unit'] ) ? (string) $out['item_gap_unit'] : 'px';
	$out['item_gap_unit'] = in_array( $item_gap_unit, [ 'px', 'rem', 'em' ], true ) ? $item_gap_unit : 'px';

	if ( isset( $out['date_format'] ) && function_exists( 'ecbb_list1_sanitize_date_format' ) ) {
		$out['date_format'] = ecbb_list1_sanitize_date_format( $out['date_format'] );
	}

	if ( isset( $out['event_cost_currency'] ) && function_exists( 'ecbb_events_widget_sanitize_event_cost_currency' ) ) {
		$out['event_cost_currency'] = ecbb_events_widget_sanitize_event_cost_currency( $out['event_cost_currency'] );
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

	if ( array_key_exists( 'load_more', $out ) ) {
		$lm = $out['load_more'];
		$out['load_more'] = ( $lm === true || $lm === 'true' || $lm === 1 || $lm === '1' );
	}

	foreach ( [ 'load_more_text', 'load_more_loading_text', 'load_more_no_more_text' ] as $text_key ) {
		if ( isset( $out[ $text_key ] ) ) {
			$out[ $text_key ] = sanitize_text_field( (string) $out[ $text_key ] );
		}
	}

	if ( isset( $out['load_more_done_hide_ms'] ) ) {
		$out['load_more_done_hide_ms'] = max( 300, (int) $out['load_more_done_hide_ms'] );
	}

	return $out;
}

/**
 * @param array $settings Element settings.
 * @return bool
 */
function ecbb_events_widget_load_more_enabled( array $settings ) {
	if ( empty( $settings['load_more'] ) ) {
		return false;
	}
	$v = $settings['load_more'];
	return $v === true || $v === 'true' || $v === 1 || $v === '1';
}

/**
 * Events shown per load-more batch (uses Number of events; 0 when unlimited).
 *
 * @param array $settings Element settings.
 * @return int
 */
function ecbb_events_widget_load_more_batch_size( array $settings ) {
	$ppp = array_key_exists( 'posts_per_page', $settings ) ? (int) $settings['posts_per_page'] : 10;
	return $ppp > 0 ? $ppp : 0;
}

/**
 * Query events for the initial widget render (optionally fetches one extra for pagination).
 *
 * @param array $settings Element settings.
 * @return array{0:\WP_Post[],1:bool,2:int} [events, has_more, batch_size]
 */
function ecbb_events_widget_fetch_events_for_display( array $settings ) {
	$batch     = ecbb_events_widget_load_more_batch_size( $settings );
	$load_more = ecbb_events_widget_load_more_enabled( $settings ) && $batch > 0;

	$args = ecbb_events_widget_query_tribe_args( $settings );
	if ( $load_more ) {
		$args['posts_per_page'] = $batch + 1;
	}

	$events = function_exists( 'tribe_get_events' ) ? tribe_get_events( $args ) : [];
	if ( ! is_array( $events ) ) {
		$events = [];
	}

	$has_more = false;
	if ( $load_more && count( $events ) > $batch ) {
		$has_more = true;
		array_pop( $events );
	}

	return [ $events, $has_more, $batch ];
}

/**
 * Register and enqueue the load-more script (once per request).
 *
 * @return void
 */
function ecbb_enqueue_load_more_assets() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	if ( ! wp_script_is( 'ecbb-load-more', 'registered' ) ) {
		$lm_path = ECBB_DIR . 'assets/js/events-load-more.js';
		wp_register_script(
			'ecbb-load-more',
			ECBB_URL . 'assets/js/events-load-more.js',
			[],
			file_exists( $lm_path ) ? (string) filemtime( $lm_path ) : ECBB_VERSION,
			true
		);

		wp_localize_script(
			'ecbb-load-more',
			'ECBBEventsLoadMore',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ecbb_events_load_more' ),
			]
		);
	}

	wp_enqueue_script( 'ecbb-load-more' );
}
