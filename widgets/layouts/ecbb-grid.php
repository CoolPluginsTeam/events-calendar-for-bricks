<?php
/**
 * Events Widget — Grid template (card shell: static image + date, repeater body).
 *
 * File: `widgets/layouts/ecbb-grid.php`.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Event parts when the grid layout is active (time row, title, venue, cost).
 *
 * @return array<int,array<string,mixed>>
 */

if ( ! class_exists( 'ECBB_Grid', false ) ) {

	final class ECBB_Grid {

	public static function ecbb_grid_default_parts_rows() {
	$rows = [
		[
			'part'                => 'date',
			'date_text_transform' => 'none',
		],
		[
			'part' => 'title',
			'link' => true,
		],
		[
			'part'          => 'venue',
			'venue_display' => 'name_and_state',
		],
		[
			'part'          => 'event_cost',
			'cost_currency' => 'default',
		],
	];

	return function_exists( 'ecbb_parts_rows_assign_ids' )
		? ecbb_parts_rows_assign_ids( $rows )
		: $rows;
}

/**
 * Part slugs not rendered inside the grid repeater column (handled by the shell).
 *
 * @param string $slug Part slug.
 * @return bool
 */

	public static function ecbb_grid_part_skipped_in_body( $slug ) {
	return in_array( (string) $slug, [ 'image', 'event_date', 'event_day' ], true );
}

/**
 * Ordered part slugs from cleaned repeater rows.
 *
 * @param array $parts Raw repeater.
 * @return string[]
 */

	public static function ecbb_grid_part_slug_stack( array $parts ) {
	if ( function_exists( 'ecbb_parts_slug_stack' ) ) {
		return ecbb_parts_slug_stack( $parts );
	}
	if ( ! function_exists( 'ecbb_parts_rows_clean' ) ) {
		return [];
	}
	$clean = ecbb_parts_rows_clean( $parts );
	$out   = [];
	foreach ( $clean as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$out[] = isset( $row['part'] ) ? (string) $row['part'] : '';
	}
	return $out;
}

/**
 * Whether to replace repeater rows with the grid default stack.
 *
 * @param array $parts Raw repeater rows.
 * @return bool
 */

	public static function ecbb_grid_should_reset_parts( array $parts ) {
	if ( ! function_exists( 'ecbb_parts_rows_clean' ) ) {
		return false;
	}
	$clean = ecbb_parts_rows_clean( $parts );
	if ( $clean === [] ) {
		return true;
	}
	if ( function_exists( 'ecbb_list2_is_legacy_stack' )
		&& ecbb_list2_is_legacy_stack( $clean ) ) {
		return true;
	}
	if ( function_exists( 'ecbb_parts_stack_matches_defaults' ) ) {
		if ( function_exists( 'ecbb_list1_default_parts_rows' )
			&& ecbb_parts_stack_matches_defaults( $parts, ecbb_list1_default_parts_rows() ) ) {
			return true;
		}
		if ( function_exists( 'ecbb_list2_default_parts_rows' )
			&& ecbb_parts_stack_matches_defaults( $parts, ecbb_list2_default_parts_rows() ) ) {
			return true;
		}
		// Do not reset when the stack already matches grid defaults — that is the
		// normal saved state and must keep row ids + Style-tab settings (typography, etc.).
	}
	// Fresh element: Bricks factory default from the control definition.
	$factory = [ 'categories', 'title', 'date', 'venue', 'description', 'read_more' ];
	if ( function_exists( 'ecbb_parts_slug_stack' )
		&& ecbb_parts_slug_stack( $parts ) === $factory ) {
		return true;
	}
	return false;
}

/**
 * Normalize parts for grid rendering (defaults only for empty / foreign-layout stacks).
 *
 * @param array $parts Raw repeater rows.
 * @return array<int,array<string,mixed>>
 */

	public static function ecbb_grid_normalize_parts( array $parts ) {
	if ( self::ecbb_grid_should_reset_parts( $parts ) ) {
		return self::ecbb_grid_default_parts_rows();
	}
	$clean = ecbb_parts_rows_clean( $parts );
	$clean = array_map(
		static function ( $row ) {
			if ( ! is_array( $row ) ) {
				return $row;
			}
			$part = (string) ( $row['part'] ?? '' );
			if ( $part === 'venue' ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' || $display === 'name_and_address' ) {
					$row['venue_display'] = 'name_and_state';
				}
			}
			if ( $part === 'event_cost' && ( ! isset( $row['cost_currency'] ) || (string) $row['cost_currency'] === '' ) ) {
				$row['cost_currency'] = 'default';
			}
			return $row;
		},
		$clean
	);
	if ( function_exists( 'ecbb_parts_rows_assign_ids' ) ) {
		$clean = ecbb_parts_rows_assign_ids( $clean );
	}
	return $clean;
}

/**
 * Static date column: framed badge (white + gray border → blue inner) + weekday below (matches grid reference).
 *
 * @param \WP_Post $post Event post.
 * @return string
 */

	public static function ecbb_grid_date_block_html( $post ) {
	if ( ! ( $post instanceof \WP_Post ) ) {
		return '<div class="ecbb-ev__grid-date-col" aria-hidden="true"></div>';
	}

	$start_ts = false;
	if ( function_exists( 'ecbb_list2_date_bounds' ) ) {
		list( $start_ts ) = ecbb_list2_date_bounds( $post->ID );
	} else {
		$raw      = (string) get_post_meta( $post->ID, '_EventStartDate', true );
		$start_ts = $raw ? strtotime( $raw ) : false;
	}

	if ( ! $start_ts ) {
		return '<div class="ecbb-ev__grid-date-col" aria-hidden="true"></div>';
	}

	$day       = date_i18n( 'd', $start_ts );
	$month     = date_i18n( 'M', $start_ts );
	$day_of_wk = strtoupper( date_i18n( 'D', $start_ts ) );

	return '<div class="ecbb-ev__grid-date-col" aria-hidden="true">'
		. '<div class="ecbb-ev__grid-date-frame">'
		. '<div class="ecbb-ev__grid-date-badge">'
		. '<span class="ecbb-ev__grid-date-badge-day">' . esc_html( $day ) . '</span>'
		. '<span class="ecbb-ev__grid-date-badge-month">' . esc_html( $month ) . '</span>'
		. '</div>'
		. '</div>'
		. '<span class="ecbb-ev__grid-date-dow">' . esc_html( $day_of_wk ) . '</span>'
		. '</div>';
}

/**
 * Featured image for the grid card top (not driven by the repeater).
 *
 * @param \WP_Post $post Event post.
 * @return string
 */

	public static function ecbb_grid_static_image_html( $post ) {
	if ( ! ( $post instanceof \WP_Post ) ) {
		return '';
	}

	$thumb_id = (int) get_post_thumbnail_id( $post->ID );
	$url      = get_permalink( $post->ID );

	if ( ! $thumb_id ) {
		return '<div class="ecbb-ev__grid-media ecbb-ev__grid-media--placeholder" aria-hidden="true"></div>';
	}

	$size = 'large';
	if ( function_exists( 'ecbb_sanitize_attachment_image_size' ) ) {
		$size = ecbb_sanitize_attachment_image_size( '', 'large' );
	}

	$img = wp_get_attachment_image(
		$thumb_id,
		$size,
		false,
		[
			'class'    => 'ecbb-ev__grid-thumb-img',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'alt'      => wp_strip_all_tags( get_the_title( $post->ID ) ),
		]
	);
	if ( ! is_string( $img ) || $img === '' ) {
		return '<div class="ecbb-ev__grid-media ecbb-ev__grid-media--placeholder" aria-hidden="true"></div>';
	}

	$inner = '<span class="ecbb-ev__grid-media-inner">' . $img . '</span>';

	return '<div class="ecbb-ev__grid-media">'
		. '<a class="ecbb-ev__grid-media-link" href="' . esc_url( $url ) . '">' . $inner . '</a>'
		. '</div>';
}

/**
 * Inner markup for one grid item.
 *
 * @param \WP_Post $post            Event post.
 * @param array    $parts           Normalized repeater rows (order preserved).
 * @param callable $emit_part       function( \WP_Post $post, array $item, int $idx ): void
 * @return string
 */

	public static function ecbb_grid_item_inner_markup( $post, array $parts, callable $emit_part ) {
	ob_start();

	echo '<div class="ecbb-ev__item-inner ecbb-ev__item-inner--grid">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helpers.
	echo self::ecbb_grid_static_image_html( $post );

	echo '<div class="ecbb-ev__grid-meta">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo self::ecbb_grid_date_block_html( $post );

	echo '<div class="ecbb-ev__grid-body">';

	$n = count( $parts );
	for ( $i = 0; $i < $n; $i++ ) {
		$item = $parts[ $i ] ?? null;
		if ( ! is_array( $item ) ) {
			continue;
		}
		$p = isset( $item['part'] ) ? (string) $item['part'] : '';
		if ( self::ecbb_grid_part_skipped_in_body( $p ) ) {
			continue;
		}
		$emit_part( $post, $item, $i );
	}

	echo '</div></div></div>';

	return ob_get_clean();
}

	}

}

if ( ! function_exists( 'ecbb_grid_default_parts_rows' ) ) {
	function ecbb_grid_default_parts_rows( ...$args ) {
		return ECBB_Grid::ecbb_grid_default_parts_rows( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_part_skipped_in_body' ) ) {
	function ecbb_grid_part_skipped_in_body( ...$args ) {
		return ECBB_Grid::ecbb_grid_part_skipped_in_body( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_part_slug_stack' ) ) {
	function ecbb_grid_part_slug_stack( ...$args ) {
		return ECBB_Grid::ecbb_grid_part_slug_stack( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_should_reset_parts' ) ) {
	function ecbb_grid_should_reset_parts( ...$args ) {
		return ECBB_Grid::ecbb_grid_should_reset_parts( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_normalize_parts' ) ) {
	function ecbb_grid_normalize_parts( ...$args ) {
		return ECBB_Grid::ecbb_grid_normalize_parts( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_date_block_html' ) ) {
	function ecbb_grid_date_block_html( ...$args ) {
		return ECBB_Grid::ecbb_grid_date_block_html( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_static_image_html' ) ) {
	function ecbb_grid_static_image_html( ...$args ) {
		return ECBB_Grid::ecbb_grid_static_image_html( ...$args );
	}
}
if ( ! function_exists( 'ecbb_grid_item_inner_markup' ) ) {
	function ecbb_grid_item_inner_markup( ...$args ) {
		return ECBB_Grid::ecbb_grid_item_inner_markup( ...$args );
	}
}
