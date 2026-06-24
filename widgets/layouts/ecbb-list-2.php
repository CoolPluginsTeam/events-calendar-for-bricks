<?php
/**
 * Events Widget — List template / Style 2 (magazine list shell, month headings, date rail, inner markup).
 *
 * File: `widgets/layouts/ecbb-list-2.php`.
 *
 * Loaded after shared loop markup, Style 1, and Grid so normalizers can compare slug stacks.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Style 2 surface class for a part slug (list style 2 only).
 *
 * @param string $part Internal part slug (underscores).
 * @return string
 */

if ( ! class_exists( 'ECBB_List_2', false ) ) {

	final class ECBB_List_2 {

	public static function ecbb_list2_part_class( $part ) {
	$part = sanitize_key( (string) $part );
	static $map = [
		'title'              => 'ecbb-style2-title',
		'description'        => 'ecbb-style2-description',
		'date'               => 'ecbb-style2-date',
		'venue'              => 'ecbb-style2-venue',
		'venue_full_address' => 'ecbb-style2-venue',
		'venue_street'       => 'ecbb-style2-venue',
		'venue_city'         => 'ecbb-style2-venue',
		'venue_state'        => 'ecbb-style2-venue',
		'venue_zip'          => 'ecbb-style2-venue',
		'venue_country'      => 'ecbb-style2-venue',
		'venue_phone'        => 'ecbb-style2-venue',
		'organizer'          => 'ecbb-style2-organizer',
		'organizer_email'    => 'ecbb-style2-organizer',
		'organizer_phone'    => 'ecbb-style2-organizer',
		'categories'         => 'ecbb-style2-categories',
		'tags'               => 'ecbb-style2-tags',
		'image'              => 'ecbb-style2-image',
		'read_more'          => 'ecbb-style2-read-more',
		'event_date'         => 'ecbb-style2-event-date',
		'event_time'         => 'ecbb-style2-time',
		'event_day'          => 'ecbb-style2-day',
		'event_cost'         => 'ecbb-style2-cost',
		'event_tickets'      => 'ecbb-style2-tickets',
		'event_rsvp'         => 'ecbb-style2-rsvp',
		'venue_website'      => 'ecbb-style2-tickets',
		'event_website'      => 'ecbb-style2-tickets',
		'organizer_website'  => 'ecbb-style2-tickets',
		'event_map_link'     => 'ecbb-style2-tickets',
		'event_phone'        => 'ecbb-style2-time',
	];
	if ( isset( $map[ $part ] ) ) {
		return $map[ $part ];
	}
	return 'ecbb-style2-' . str_replace( '_', '-', $part );
}

/**
 * Sanitize the Style 2 “Show month header” control (Bricks checkbox or legacy values).
 *
 * @param mixed $value Raw control or AJAX value.
 * @return bool
 */

	public static function ecbb_sanitize_style2_show_month_headings( $value ) {
	if ( $value === false || $value === 0 || $value === '0' || $value === 'no' || $value === 'off' ) {
		return false;
	}
	if ( $value === null || $value === '' ) {
		return false;
	}
	if ( $value === true || $value === 1 || $value === '1' || $value === 'yes' || $value === 'on' ) {
		return true;
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
	if ( is_array( $value ) && $value === [] ) {
		return false;
	}
	return (bool) $value;
}

/**
 * Whether Style 2 month group headings ("May 2026" blocks) are enabled.
 *
 * Bricks checkbox (bool); legacy select values yes/no still supported.
 *
 * @param array $settings Element or AJAX settings.
 * @return bool
 */

	public static function ecbb_list2_month_headings_enabled( $settings ) {
	if ( ! is_array( $settings ) ) {
		return false;
	}
	if ( ! array_key_exists( 'style2_show_month_headings', $settings ) ) {
		return false;
	}
	return self::ecbb_sanitize_style2_show_month_headings( $settings['style2_show_month_headings'] );
}

/**
 * Detect the old 3-row default (title + description + date) so Style 2 can upgrade to the full stack.
 *
 * @param array $clean Rows from ecbb_parts_rows_clean().
 * @return bool
 */

	public static function ecbb_list2_is_legacy_stack( array $clean ) {
	if ( count( $clean ) !== 3 ) {
		return false;
	}
	$keys = [];
	foreach ( $clean as $row ) {
		$keys[] = (string) ( $row['part'] ?? '' );
	}
	sort( $keys );
	return $keys === [ 'date', 'description', 'title' ];
}

/**
 * Default Event parts for Style 2 list (body column). Featured image is not listed here;
 * {@see self::ecbb_list2_normalize_parts()} appends an `image` row when missing so the static
 * trail column can render. Read more is included by default at the end of the body stack.
 *
 * @return array<int,array<string,mixed>>
 */

	public static function ecbb_list2_default_parts_rows() {
	$rows = [
		[
			'part'                  => 'date',
			'date_text_transform'   => 'uppercase',
			'ecbb_color'            => '',
			'ecbb_background'       => '',
			'ecbb_background_inner' => '',
		],
		[
			'part'       => 'title',
			'link'       => true,
			'ecbb_color' => '',
		],
		[
			'part'          => 'venue',
			'venue_display' => 'name_and_state',
			'ecbb_color'    => '',
		],
		[
			'part'        => 'description',
			'desc_source' => 'content',
			'ecbb_color'  => '',
		],
		[
			'part'           => 'read_more',
			'read_more_text' => esc_html__( 'More Details', 'ecbb' ),
		],
	];

	return function_exists( 'ecbb_parts_rows_assign_ids' )
		? ecbb_parts_rows_assign_ids( $rows )
		: $rows;
}

/**
 * Normalize repeater parts for Style 2 list: upgrade legacy / cross-layout stacks; ensure image row.
 *
 * @param array $parts Raw Bricks repeater rows.
 * @return array<int,array<string,mixed>>
 */

	public static function ecbb_list2_normalize_parts( array $parts ) {
	if ( ! function_exists( 'ecbb_parts_rows_clean' ) ) {
		return $parts;
	}

	$clean = ecbb_parts_rows_clean( $parts );
	$reset = ( $clean === [] ) || self::ecbb_list2_is_legacy_stack( $clean );

	if ( ! $reset && function_exists( 'ecbb_parts_stack_matches_defaults' ) ) {
		if ( function_exists( 'ecbb_grid_default_parts_rows' )
			&& ecbb_parts_stack_matches_defaults( $parts, ecbb_grid_default_parts_rows() ) ) {
			$reset = true;
		} elseif ( function_exists( 'ecbb_list1_default_parts_rows' )
			&& ecbb_parts_stack_matches_defaults( $parts, ecbb_list1_default_parts_rows() ) ) {
			$reset = true;
		}
	}

	if ( $reset ) {
		$clean = self::ecbb_list2_default_parts_rows();
	}

	if ( ! ecbb_parts_has_part( $clean, 'image' ) ) {
		$clean[] = [
			'part'       => 'image',
			'image_link' => true,
			'image_size' => '',
		];
	}

	$clean = array_map(
		static function ( $row ) {
			if ( ! is_array( $row ) ) {
				return $row;
			}

			if ( (string) ( $row['part'] ?? '' ) !== 'venue' ) {
				return $row;
			}

			$display = (string) ( $row['venue_display'] ?? '' );
			if ( $display === '' || $display === 'name_and_address' ) {
				$row['venue_display'] = 'name_and_state';
			}

			return $row;
		},
		$clean
	);

	return $clean;
}

/**
 * Event start/end timestamps from TEC meta.
 *
 * @param int $post_id Event post ID.
 * @return array{0:int|false,1:int|false}
 */

	public static function ecbb_list2_date_bounds( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id < 1 ) {
		return [ false, false ];
	}
	$start_raw = (string) get_post_meta( $post_id, '_EventStartDate', true );
	$end_raw   = (string) get_post_meta( $post_id, '_EventEndDate', true );
	$start_ts  = $start_raw ? strtotime( $start_raw ) : false;
	$end_ts    = $end_raw ? strtotime( $end_raw ) : $start_ts;
	if ( ! $start_ts ) {
		return [ false, false ];
	}
	if ( ! $end_ts ) {
		$end_ts = $start_ts;
	}
	return [ $start_ts, $end_ts ];
}

/**
 * Month heading (e.g. "April 2026") when the month changes between events.
 *
 * @param int         $post_id         Event post ID.
 * @param string|null $last_month_key  Stored "Y-m" of previous row; updated by reference.
 * @param bool        $show            Whether month group headings are enabled (control).
 * @return string                      Markup or empty.
 */

	public static function ecbb_list2_maybe_month_heading_html( $post_id, &$last_month_key, $show = false ) {
	$show = (bool) $show;
	if ( ! $show ) {
		return '';
	}
	$post_id = absint( $post_id );
	if ( $post_id < 1 ) {
		return '';
	}
	list( $start_ts ) = self::ecbb_list2_date_bounds( $post_id );
	if ( ! $start_ts ) {
		return '';
	}
	$key = sanitize_text_field( date_i18n( 'Y-m', $start_ts ) );
	if ( is_string( $last_month_key ) && $last_month_key === $key ) {
		return '';
	}
	$last_month_key = $key;
	$label          = date_i18n( 'F Y', $start_ts );
	return '<div class="ecbb-style2-month"><span class="ecbb-style2-month-label">' . esc_html( $label ) . '</span><span class="ecbb-style2-month-line" role="presentation"></span></div>';
}

/**
 * Inner date stack for Style 2 LEFT rail (list template only).
 *
 * Markup: start month → days row (start | end block with sep, end day, end month).
 *
 * @param int|false $start_ts Start timestamp.
 * @param int|false $end_ts   End timestamp.
 * @return string HTML fragment (no wrapper).
 */

	public static function ecbb_list2_date_rail_stack_html_style2( $start_ts, $end_ts ) {
	if ( ! $start_ts ) {
		return '';
	}
	if ( ! $end_ts ) {
		$end_ts = $start_ts;
	}

	$start_month = strtoupper( date_i18n( 'M', $start_ts ) );
	$end_month   = strtoupper( date_i18n( 'M', $end_ts ) );

	$same_day   = ( date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) );
	$same_month = ( date_i18n( 'Ym', $start_ts ) === date_i18n( 'Ym', $end_ts ) );

	if ( $same_day ) {
		return '<div class="ecbb-style2-rail-date ecbb-style2-rail-date--single">'
			. '<div class="ecbb-style2-rail-month">' . esc_html( $start_month ) . '</div>'
			. '<div class="ecbb-style2-rail-day-start">' . esc_html( date_i18n( 'd', $start_ts ) ) . '</div>'
			. '</div>';
	}

	$d_start = date_i18n( 'd', $start_ts );
	$d_end   = date_i18n( 'd', $end_ts );

	$modifier = $same_month ? 'same-month' : 'cross-month';

	$end_month_html = $same_month
		? ''
		: '<span class="ecbb-style2-rail-month-end">' . esc_html( $end_month ) . '</span>';

	$end_block = '<div class="ecbb-style2-rail-end">'
		. '<span class="ecbb-style2-rail-sep" aria-hidden="true">-</span>'
		. '<span class="ecbb-style2-rail-day-end">' . esc_html( $d_end ) . '</span>'
		. $end_month_html
		. '</div>';

	return '<div class="ecbb-style2-rail-date ecbb-style2-rail-date--range ecbb-style2-rail-date--' . esc_attr( $modifier ) . '">'
		. '<div class="ecbb-style2-rail-month">' . esc_html( $start_month ) . '</div>'
		. '<div class="ecbb-style2-rail-range">'
		. '<div class="ecbb-style2-rail-days-row">'
		. '<span class="ecbb-style2-rail-day-start">' . esc_html( $d_start ) . '</span>'
		. $end_block
		. '</div>'
		. '</div>'
		. '</div>';
}

/**
 * Left column: start month / day range / end month (static, not from repeater).
 *
 * @param \WP_Post $post Event post.
 * @return string Markup (aside).
 */

	public static function ecbb_list2_date_rail_html( $post ) {
	if ( ! ( $post instanceof \WP_Post ) ) {
		return '';
	}

	list( $start_ts, $end_ts ) = self::ecbb_list2_date_bounds( $post->ID );
	if ( ! $start_ts ) {
		return '<aside class="ecbb-style2-rail" aria-hidden="true"><div class="ecbb-style2-rail-in"></div></aside>';
	}

	$stack = self::ecbb_list2_date_rail_stack_html_style2( $start_ts, $end_ts );

	return '<aside class="ecbb-style2-rail" aria-hidden="true">'
		. '<div class="ecbb-style2-rail-in">'
		. $stack
		. '</div></aside>';
}

/**
 * First repeater row for the featured image (for the static right column).
 *
 * @param array $parts Repeater rows.
 * @return array{index:int, row:array}
 */

	public static function ecbb_list2_find_first_image_part_row( array $parts ) {
	foreach ( $parts as $i => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$row_part = isset( $row['part'] ) ? sanitize_key( (string) $row['part'] ) : '';
		if ( 'image' === $row_part ) {
			return [ 'index' => (int) $i, 'row' => $row ];
		}
	}
	return [
		'index' => -1,
		'row'   => [
			'part'       => 'image',
			'image_link' => true,
			'image_size' => '',
		],
	];
}

/**
 * Whether a repeater part is rendered in the Style 2 middle column (excludes static columns).
 *
 * @param string $part Part slug.
 * @return bool True = skip middle (handled elsewhere or omitted).
 */

	public static function ecbb_list2_skip_middle_part( $part ) {
	$part = sanitize_key( (string) $part );
	return in_array( $part, [ 'image', 'event_date' ], true );
}

/**
 * Inline flex stack style for the Style 2 body column — slightly tighter than raw `part_gap`
 * so repeater rows do not read overly loose.
 *
 * @param float|string $part_gap      Saved part gap (number).
 * @param string       $part_gap_unit px|rem|em.
 * @return string                     Full `style=""` fragment value (no attribute wrapper).
 */

	public static function ecbb_list2_body_stack_gap_style( $part_gap, $part_gap_unit ) {
	$part_gap = is_numeric( $part_gap ) ? (float) $part_gap : 0.0;
	$unit     = in_array( (string) $part_gap_unit, [ 'px', 'rem', 'em' ], true ) ? (string) $part_gap_unit : 'px';
	if ( 'px' === $unit ) {
		$g = max( 4, min( $part_gap, 7 ) );
	} else {
		$g = max( 0.3, min( $part_gap, 0.5625 ) );
	}
	return sprintf( 'display:flex;flex-direction:column;gap:%s%s;', (string) $g, $unit );
}

/**
 * Markup for the inner Style 2 row (date rail + repeater body + optional image column).
 * Read more stays in the body column; only the featured image is placed in the trail aside.
 *
 * @param \WP_Post $post            Event post.
 * @param array    $parts           Full repeater settings (order preserved).
 * @param string   $gap_style_value       e.g. display:flex;flex-direction:column;gap:12px;
 * @param callable $emit_part       function( \WP_Post $post, array $item, int $idx ): void
 * @return string
 */

	public static function ecbb_list2_item_inner_markup( $post, array $parts, $gap_style_value, callable $emit_part ) {
	if ( ! ( $post instanceof \WP_Post ) ) {
		return '';
	}

	$gap_style_value = (string) $gap_style_value;

	ob_start();
	$img_info   = self::ecbb_list2_find_first_image_part_row( $parts );
	$has_media  = ( $img_info['index'] >= 0 );

	$inner_class = 'ecbb-style2';
	if ( $has_media ) {
		$inner_class .= ' ecbb-style2--has-media';
	}
	echo '<div class="' . esc_attr( $inner_class ) . '">';
	echo self::ecbb_list2_date_rail_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder-internal HTML, fields escaped at source.

	$gap_esc = esc_attr( $gap_style_value );
	echo '<div class="ecbb-style2-body" style="' . $gap_esc . '">';

	$n = count( $parts );
	for ( $i = 0; $i < $n; $i++ ) {
		$item = $parts[ $i ] ?? null;
		if ( ! is_array( $item ) ) {
			continue;
		}
		$p = isset( $item['part'] ) ? sanitize_key( (string) $item['part'] ) : '';
		if ( self::ecbb_list2_skip_middle_part( $p ) ) {
			continue;
		}
		$next   = $parts[ $i + 1 ] ?? null;
		$next_p = is_array( $next ) && isset( $next['part'] ) ? sanitize_key( (string) $next['part'] ) : '';
		if ( 'date' === $p ) {
			echo '<div class="ecbb-style2-dayrow">';
			$emit_part( $post, $item, $i );
			echo '</div>';
			continue;
		}
		if ( 'event_day' === $p && 'event_time' === $next_p ) {
			echo '<div class="ecbb-style2-dayrow">';
			$emit_part( $post, $item, $i );
			$emit_part( $post, $next, $i + 1 );
			echo '</div>';
			$i++;
			continue;
		}
		$emit_part( $post, $item, $i );
	}

	echo '</div>';

	if ( $has_media ) {
		echo '<aside class="ecbb-style2-trail" aria-label="' . esc_attr__( 'Event image', 'ecbb' ) . '">';
		echo '<div class="ecbb-style2-media">';
		$emit_part( $post, $img_info['row'], $img_info['index'] );
		echo '</div>';
		echo '</aside>';
	}

	echo '</div>';
	return ob_get_clean();
}

	}

}

if ( ! function_exists( 'ecbb_list2_part_class' ) ) {
	function ecbb_list2_part_class( ...$args ) {
		return ECBB_List_2::ecbb_list2_part_class( ...$args );
	}
}
if ( ! function_exists( 'ecbb_sanitize_style2_show_month_headings' ) ) {
	function ecbb_sanitize_style2_show_month_headings( ...$args ) {
		return ECBB_List_2::ecbb_sanitize_style2_show_month_headings( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_month_headings_enabled' ) ) {
	function ecbb_list2_month_headings_enabled( ...$args ) {
		return ECBB_List_2::ecbb_list2_month_headings_enabled( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_is_legacy_stack' ) ) {
	function ecbb_list2_is_legacy_stack( ...$args ) {
		return ECBB_List_2::ecbb_list2_is_legacy_stack( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_default_parts_rows' ) ) {
	function ecbb_list2_default_parts_rows( ...$args ) {
		return ECBB_List_2::ecbb_list2_default_parts_rows( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_normalize_parts' ) ) {
	function ecbb_list2_normalize_parts( ...$args ) {
		return ECBB_List_2::ecbb_list2_normalize_parts( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_date_bounds' ) ) {
	function ecbb_list2_date_bounds( ...$args ) {
		return ECBB_List_2::ecbb_list2_date_bounds( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_maybe_month_heading_html' ) ) {
	function ecbb_list2_maybe_month_heading_html( ...$args ) {
		return ECBB_List_2::ecbb_list2_maybe_month_heading_html( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_date_rail_stack_html_style2' ) ) {
	function ecbb_list2_date_rail_stack_html_style2( ...$args ) {
		return ECBB_List_2::ecbb_list2_date_rail_stack_html_style2( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_date_rail_html' ) ) {
	function ecbb_list2_date_rail_html( ...$args ) {
		return ECBB_List_2::ecbb_list2_date_rail_html( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_find_first_image_part_row' ) ) {
	function ecbb_list2_find_first_image_part_row( ...$args ) {
		return ECBB_List_2::ecbb_list2_find_first_image_part_row( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_skip_middle_part' ) ) {
	function ecbb_list2_skip_middle_part( ...$args ) {
		return ECBB_List_2::ecbb_list2_skip_middle_part( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_body_stack_gap_style' ) ) {
	function ecbb_list2_body_stack_gap_style( ...$args ) {
		return ECBB_List_2::ecbb_list2_body_stack_gap_style( ...$args );
	}
}
if ( ! function_exists( 'ecbb_list2_item_inner_markup' ) ) {
	function ecbb_list2_item_inner_markup( ...$args ) {
		return ECBB_List_2::ecbb_list2_item_inner_markup( ...$args );
	}
}
