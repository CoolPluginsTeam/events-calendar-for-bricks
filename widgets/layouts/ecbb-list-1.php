<?php
/**
 * Events Widget — List template / Style 1 (single-row card: cyan date column + light-cyan body
 * + cyan CTA column on the right).
 *
 * File: `widgets/layouts/ecbb-list-1.php`.
 *
 * Style 1 mirrors the Style 2 architecture: a static "outside the repeater"
 * middle. Where Style 2 pulls the featured-image row out into its own side
 * column, Style 1 pulls the first read_more row out into a solid cyan CTA
 * column on the right — visually mirroring the date column on the left.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Event parts for Style 1 list (matches the reference screenshot:
 * category badge, title, time row, venue, description, "Find Out More").
 *
 * @return array<int,array<string,mixed>>
 */

if ( ! class_exists( 'ECBB_List_1', false ) ) {

	final class ECBB_List_1 {

		public static function ecbb_default_parts() {
			return class_exists( 'ECBB_Markup', false )
			? \ECBB_Markup::ecbb_parts_assign_ids(
			[
			[
			'part' => 'categories',
			],
			[
			'part' => 'title',
			'link' => true,
			],
			[
			'part'                => 'date',
			'date_text_transform' => 'none',
			],
			[
			'part'          => 'venue',
			'venue_display' => 'full_details',
			],
			[
			'part'        => 'description',
			'desc_source' => 'content',
			],
			]
			)
			: [
			[
			'part' => 'categories',
			],
			[
			'part' => 'title',
			'link' => true,
			],
			[
			'part'                => 'date',
			'date_text_transform' => 'none',
			],
			[
			'part'          => 'venue',
			'venue_display' => 'full_details',
			],
			[
			'part'        => 'description',
			'desc_source' => 'content',
			],
			];
		}

		/**
		* Normalize repeater parts for Style 1 list. Keep user order; substitute the
		* Style 1 defaults only when the repeater is effectively empty or stuck on
		* the legacy 3-row stack (title + description + date) carried over from
		* older widget defaults.
		*
		* @param array $parts Raw Bricks repeater rows.
		* @return array<int,array<string,mixed>>
		*/

		public static function ecbb_norm_parts( array $parts ) {
			if ( ! class_exists( 'ECBB_Markup', false ) ) {
				return $parts;
			}

		$clean = \ECBB_Markup::ecbb_parts_clean( $parts );
		if ( $clean === [] ) {
			return self::ecbb_default_parts();
		}
		if ( class_exists( 'ECBB_List_2', false )
		&& \ECBB_List_2::ecbb_is_legacy_stack( $clean ) ) {
			return self::ecbb_default_parts();
		}

		// Grid or Style 2 stacks sometimes remain on `parts_style1` after switching template in the builder.
		if ( class_exists( 'ECBB_Markup', false ) ) {
			if ( class_exists( 'ECBB_Grid', false )
			&& \ECBB_Markup::ecbb_parts_match_defaults( $parts, \ECBB_Grid::ecbb_default_parts() ) ) {
				return self::ecbb_default_parts();
			}
		if ( class_exists( 'ECBB_List_2', false )
		&& \ECBB_Markup::ecbb_parts_match_defaults( $parts, \ECBB_List_2::ecbb_default_parts() ) ) {
			return self::ecbb_default_parts();
		}
		}

		// Style 1 CTA is a fixed template column (not a repeater row).
		$clean = array_values(
		array_filter(
		$clean,
		static function ( $row ) {
			return is_array( $row ) && ( (string) ( $row['part'] ?? '' ) !== 'read_more' );
		}
		)
		);

		$clean = array_map(
		static function ( $row ) {
			if ( ! is_array( $row ) ) {
				return $row;
			}

		$part = (string) ( $row['part'] ?? '' );

		if ( $part === 'venue' ) {
			$display = (string) ( $row['venue_display'] ?? '' );
			if ( $display === '' || $display === 'name_and_address' ) {
				$row['venue_display'] = 'full_details';
			}
		}

		if ( $part === 'organizer' ) {
			$display = (string) ( $row['organizer_display'] ?? '' );
			if ( $display === '' ) {
				$row['organizer_display'] = 'full_details';
			}
		}

		return $row;
		},
		$clean
		);

		return $clean;
		}

		/**
		* Allowed keys for the Style 1 left-column date format (Events Query control).
		*
		* @return list<string>
		*/

		public static function ecbb_date_formats() {
			return [
			'default',
			'MD,Y',
			'FD,Y',
			'DM',
			'DML',
			'DF',
			'MD',
			'FD',
			'MD,YT',
			'full',
			'jMl',
			'd.FY',
			'd.F',
			'ldF',
			'Mdl',
			'd.Ml',
			'dFT',
			'sed',
			'sedt',
			'D.j.F',
			];
		}

		/**
		* @param mixed $value Saved control value.
		* @return string One of {@see ecbb_date_formats()}.
		*/

		public static function ecbb_sanitize_date_fmt( $value ) {
			$v = is_string( $value ) ? trim( $value ) : '';
			if ( $v === '' ) {
				return 'default';
			}
		return in_array( $v, self::ecbb_date_formats(), true ) ? $v : 'default';
		}

		/**
		* Plain time range suffix for the start day (e.g. "8:00 am - 5:00 pm"), or empty if all-day / unknown.
		*
		* @param int $post_id Event post ID.
		* @return string Leading space included when non-empty (for appending to a date string).
		*/

		public static function ecbb_time_suffix( $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return '';
			}
		if ( function_exists( 'tribe_event_is_all_day' ) && \tribe_event_is_all_day( $post_id ) ) {
			return '';
		}
		if ( ! class_exists( 'ECBB_Markup', false ) ) {
			return '';
		}
		$tp = \ECBB_Markup::ecbb_build_day_time_parts( $post_id, [] );
		$t  = isset( $tp['time'] ) ? trim( wp_strip_all_tags( (string) $tp['time'] ) ) : '';
		return $t === '' ? '' : ' ' . $t;
		}

		/**
		* Multi-day range label for SED / SEDT presets.
		*
		* @param int $start_ts Start timestamp.
		* @param int $end_ts   End timestamp.
		* @return string
		*/

		public static function ecbb_sed_label( $start_ts, $end_ts ) {
			if ( date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) ) {
				return date_i18n( 'd M Y', $start_ts );
			}
		return trim( date_i18n( 'd M', $start_ts ) . ' - ' . date_i18n( 'd M Y', $end_ts ) );
		}

		/**
		* Build 1–3 display lines for the Style 1 date column (non-default formats).
		*
		* @param int    $post_id   Event post ID.
		* @param int    $start_ts  Start timestamp.
		* @param int    $end_ts    End timestamp.
		* @param string $format_key Sanitized format key.
		* @return list<string>
		*/

		public static function ecbb_date_lines( $post_id, $start_ts, $end_ts, $format_key ) {
			$post_id = (int) $post_id;
			$site_d  = (string) get_option( 'date_format' );
			$time_s  = self::ecbb_time_suffix( $post_id );

			switch ( $format_key ) {
				case 'MD,Y':
				return [ trim( date_i18n( 'M d, Y', $start_ts ) ) ];
				case 'FD,Y':
				return [ trim( date_i18n( 'F d, Y', $start_ts ) ) ];
				case 'DM':
				return [ trim( date_i18n( 'd', $start_ts ) ), trim( date_i18n( 'M', $start_ts ) ) ];
				case 'DML':
				return [
				trim( date_i18n( 'd', $start_ts ) . ' ' . date_i18n( 'M', $start_ts ) ),
				trim( date_i18n( 'l', $start_ts ) ),
				];
				case 'DF':
				return [ trim( date_i18n( 'd', $start_ts ) . ' ' . date_i18n( 'F', $start_ts ) ) ];
				case 'MD':
				return [ trim( date_i18n( 'M d', $start_ts ) ) ];
				case 'FD':
				return [ trim( date_i18n( 'F d', $start_ts ) ) ];
				case 'MD,YT':
				return [ trim( date_i18n( 'M d, Y', $start_ts ) . $time_s ) ];
				case 'full':
				{
					$base = trim( wp_strip_all_tags( date_i18n( $site_d, $start_ts ) ) );
					return [ trim( $base . $time_s ) ];
				}
			case 'jMl':
			return [
			trim(
			date_i18n( 'j', $start_ts ) . ' '
			. date_i18n( 'M', $start_ts ) . ' '
			. date_i18n( 'l', $start_ts )
			),
			];
			case 'd.FY':
			return [
			trim(
			date_i18n( 'd', $start_ts ) . '. '
			. date_i18n( 'F', $start_ts ) . ' '
			. date_i18n( 'Y', $start_ts )
			),
			];
			case 'd.F':
			return [ trim( date_i18n( 'd', $start_ts ) . '. ' . date_i18n( 'F', $start_ts ) ) ];
			case 'ldF':
			return [
			trim(
			date_i18n( 'l', $start_ts ) . ' '
			. date_i18n( 'd', $start_ts ) . ' '
			. date_i18n( 'F', $start_ts )
			),
			];
			case 'Mdl':
			return [
			trim(
			date_i18n( 'M', $start_ts ) . ' '
			. date_i18n( 'd', $start_ts ) . ' '
			. date_i18n( 'l', $start_ts )
			),
			];
			case 'd.Ml':
			return [
			trim(
			date_i18n( 'd', $start_ts ) . '. '
			. date_i18n( 'M', $start_ts ) . ' '
			. date_i18n( 'l', $start_ts )
			),
			];
			case 'dFT':
			return [ trim( date_i18n( 'd', $start_ts ) . ' ' . date_i18n( 'F', $start_ts ) . $time_s ) ];
			case 'sed':
			return [ self::ecbb_sed_label( $start_ts, $end_ts ) ];
			case 'sedt':
			return [ trim( self::ecbb_sed_label( $start_ts, $end_ts ) . $time_s ) ];
			case 'D.j.F':
			return [
			trim(
			date_i18n( 'D', $start_ts ) . '., '
			. date_i18n( 'j', $start_ts ) . '. '
			. date_i18n( 'F', $start_ts )
			),
			];
			default:
			return [];
		}
		}

		/**
		* Left static column: day number / full month name / year stacked vertically.
		*
		* Rendered outside the repeater because the visual is fixed by the Style 1
		* design; users still control everything that appears in the body via the
		* Event parts repeater.
		*
		* @param \WP_Post $post        Event post.
		* @param string   $format_key  One of {@see self::ecbb_date_formats()}.
		* @return string Markup (aside).
		*/

		public static function ecbb_date_block( $post, $format_key = 'default' ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				return '';
			}

		$format_key = self::ecbb_sanitize_date_fmt( $format_key );

		$start_ts = false;
		$end_ts   = false;
		if ( class_exists( 'ECBB_List_2', false ) ) {
			list( $start_ts, $end_ts ) = \ECBB_List_2::ecbb_date_bounds( $post->ID );
		} else {
		$raw       = (string) get_post_meta( $post->ID, '_EventStartDate', true );
		$raw_end   = (string) get_post_meta( $post->ID, '_EventEndDate', true );
		$start_ts  = $raw ? strtotime( $raw ) : false;
		$end_ts    = $raw_end ? strtotime( $raw_end ) : $start_ts;
		}
		if ( ! $end_ts ) {
			$end_ts = $start_ts;
		}

		if ( ! $start_ts ) {
			return '<aside class="ecbb-ev__style1-date" aria-hidden="true"></aside>';
		}

		if ( $format_key === 'default' ) {
			$day   = date_i18n( 'd', $start_ts );
			$month = date_i18n( 'F', $start_ts );
			$year  = date_i18n( 'Y', $start_ts );

			return '<aside class="ecbb-ev__style1-date" aria-hidden="true">'
			. '<span class="ecbb-ev__style1-date-day">' . esc_html( $day ) . '</span>'
			. '<span class="ecbb-ev__style1-date-month">' . esc_html( $month ) . '</span>'
			. '<span class="ecbb-ev__style1-date-year">' . esc_html( $year ) . '</span>'
			. '</aside>';
		}

		$lines = self::ecbb_date_lines( $post->ID, $start_ts, $end_ts, $format_key );
		$lines = array_values(
		array_filter(
		array_map(
		static function ( $line ) {
			return trim( (string) $line );
		},
		$lines
		)
		)
		);

		if ( $lines === [] ) {
			return '<aside class="ecbb-ev__style1-date ecbb-ev__style1-date--multiline" aria-hidden="true"></aside>';
		}

		$n       = count( $lines );
		$inner   = '';
		$line_ix = 0;
		foreach ( $lines as $line ) {
			++$line_ix;
			$cls = 'ecbb-ev__style1-date-line';
			if ( $n === 1 ) {
				$cls .= ' ecbb-ev__style1-date-line--single';
			} else {
			$cls .= ' ecbb-ev__style1-date-line--' . (string) $line_ix;
		}
		$inner .= '<span class="' . esc_attr( $cls ) . '">' . esc_html( $line ) . '</span>';
		}

		return '<aside class="ecbb-ev__style1-date ecbb-ev__style1-date--multiline" aria-hidden="true">' . $inner . '</aside>';
		}

		/**
		* Whether a repeater part is handled by a static Style 1 column (and must
		* therefore be skipped in the middle body to avoid double-rendering).
		*
		* @param string $part Part slug.
		* @return bool
		*/

		public static function ecbb_skip_body_part( $part ) {
			return (string) $part === 'read_more';
		}

		/**
		* Markup for the inner Style 1 row.
		*
		* Layout:
		*   - aside (left)   : static date block (day / month / year)
		*   - body (middle)  : repeater rows in order, minus any read_more rows
		*   - aside (right)  : static CTA column (always shown; not part of repeater)
		*
		* CTA mirrors the date column: it’s a fixed part of the Style 1 template.
		*
		* @param \WP_Post $post            Event post.
		* @param array    $parts           Full repeater settings (order preserved).
		* @param callable $emit_part       function( \WP_Post $post, array $item, int $idx ): void
		* @param string   $date_format     Style 1 left-column format key (see Events Query control).
		* @return string
		*/

		public static function ecbb_item_inner( $post, array $parts, callable $emit_part, $date_format = 'default' ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				return '';
			}

		ob_start();

		$inner_class = 'ecbb-ev__item-inner ecbb-ev__item-inner--style1';
		$inner_class .= ' ecbb-ev__item-inner--style1-has-cta';

		echo '<div class="' . esc_attr( $inner_class ) . '">';
		echo self::ecbb_date_block( $post, $date_format ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder-internal HTML, fields escaped at source.

		echo '<div class="ecbb-ev__style1-body">';

		$n = count( $parts );
		for ( $i = 0; $i < $n; $i++ ) {
			$item = $parts[ $i ] ?? null;
			if ( ! is_array( $item ) ) {
				continue;
			}
		$p = isset( $item['part'] ) ? (string) $item['part'] : '';
		if ( self::ecbb_skip_body_part( $p ) ) {
			continue;
		}
		$emit_part( $post, $item, $i );
		}

		echo '</div>';

		// Static CTA column (not driven by repeater).
		echo '<aside class="ecbb-ev__style1-cta">';
		// Reuse the same markup classes the CSS expects (read_more part wrapper).
		$cta_row = [ 'part' => 'read_more' ];
		if ( class_exists( 'ECBB_Markup', false ) ) {
			$cta_wrap = \ECBB_Markup::ecbb_part_classes( 'read_more', 999, 'style1' );
		} else {
		$cta_wrap = 'ecbb-event-part ecbb-event-part--read-more ecbb-p999';
		}
		echo '<div class="' . esc_attr( $cta_wrap ) . '">';
		echo '<a class="ecbb-event__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html__( 'Find Out More', 'events-calendar-for-bricks' ) . '</a>';
		echo '</div>';
		echo '</aside>';

		echo '</div>';

		return ob_get_clean();
		}

	}

}

