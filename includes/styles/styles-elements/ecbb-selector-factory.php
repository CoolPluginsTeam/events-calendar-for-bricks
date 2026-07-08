<?php
/**
 * ECBB_Selector_Factory service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Selector_Factory', false ) ) {

	final class ECBB_Selector_Factory {

		public static function ecbb_type_selectors( $scope_sel, $part_type ) {
			if ( 'title' === $part_type ) {
				return self::ecbb_title_inner_selectors( $scope_sel );
			}

		if ( in_array( $part_type, self::ecbb_chip_parts(), true ) ) {
			return self::ecbb_chip_selectors( $scope_sel );
		}

		if ( in_array( $part_type, self::ecbb_button_parts(), true ) ) {
			return self::ecbb_button_inner_selectors( $scope_sel );
		}

		if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
			return $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' a.event-button,'
			. $scope_sel . ' > a.event-button,'
			. $scope_sel . ' a.ecbb-event-card__button,'
			. $scope_sel . ' > a.ecbb-event-card__button,'
			. $scope_sel . ' .ecbb-event__term,'
			. $scope_sel . ' > .ecbb-event__term';
		}

		return $scope_sel . ','
		. $scope_sel . ' .ecbb-event__link,'
		. $scope_sel . ' > .ecbb-event__link';
		}

		public static function ecbb_title_inner_selectors( $scope_sel ) {
			return $scope_sel . ','
			. $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' .ecbb-event__title-text';
		}

		public static function ecbb_hover_anim_scope( $scope_sel, $part_type ) {
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				return $scope_sel . ' .ecbb-event__term-chip,'
				. $scope_sel . ' .ecbb-event__link,'
				. $scope_sel . ' > .ecbb-event__link,'
				. $scope_sel . ' .ecbb-event-card__category,'
				. $scope_sel . ' > .ecbb-event-card__category,'
				. $scope_sel . ' a.event-button,'
				. $scope_sel . ' > a.event-button,'
				. $scope_sel . ' a.ecbb-event-card__button,'
				. $scope_sel . ' > a.ecbb-event-card__button,'
				. $scope_sel . ' .ecbb-event__term';
			}

		return $scope_sel;
		}

		public static function ecbb_chip_parts() {
			return [ 'categories' ];
		}

		public static function ecbb_link_hover_parts() {
			return [ 'categories', 'tags', 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		public static function ecbb_meta_icon_selector( $scope_sel ) {
			$scope_sel = trim( (string) $scope_sel );
			if ( $scope_sel === '' ) {
				return '';
			}
			if ( preg_match( '#^(\.[a-zA-Z0-9\-_]+)\s+(.+)$#', $scope_sel, $matches ) ) {
				return $matches[1] . ' li:has(> ' . $matches[2] . ') > .ecbb-event-card__meta-icon';
			}

			return '';
		}

		public static function ecbb_chip_selectors( $scope_sel ) {
			return $scope_sel . ' .ecbb-event__term-chip,'
			. $scope_sel . ' .ecbb-event__term-chip > .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event__link,'
			. $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > .ecbb-event-card__category,'
			. $scope_sel . ' .ecbb-event-card__category,'
			. $scope_sel . ' > .ecbb-event__term';
		}

		public static function ecbb_hover_selectors( $scope_sel, $part_type ) {
			if ( in_array( $part_type, self::ecbb_link_hover_parts(), true ) ) {
				return $scope_sel . ' .ecbb-event__term-chip:hover,'
				. $scope_sel . ' .ecbb-event__link:hover,'
				. $scope_sel . ' > .ecbb-event__link:hover,'
				. $scope_sel . ' .ecbb-event-card__category:hover,'
				. $scope_sel . ' > .ecbb-event-card__category:hover,'
				. $scope_sel . ' a.event-button:hover,'
				. $scope_sel . ' > a.event-button:hover,'
				. $scope_sel . ' a.ecbb-event-card__button:hover,'
				. $scope_sel . ' > a.ecbb-event-card__button:hover,'
				. $scope_sel . ' .ecbb-event__term:hover';
			}

		return $scope_sel . ':hover,'
		. $scope_sel . ':hover *,'
		. $scope_sel . ' a:hover,'
		. $scope_sel . ' a:hover *,'
		. $scope_sel . ' .ecbb-event__term:hover';
		}

		public static function ecbb_composite_boxed_meta_icon_selector( $scope_sel ) {
			$scope_sel = trim( (string) $scope_sel );
			if ( $scope_sel === '' ) {
				return '';
			}

			return $scope_sel . ' .ecbb-event__meta-segment > .ecbb-event-card__meta-icon';
		}

		public static function ecbb_is_builder_preview() {
			if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
				return true;
			}
			return function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call();
		}

		public static function ecbb_repeater_hover_surface_suffixes() {
			return [
				' .ecbb-event__term-chip:hover',
				' .ecbb-event__link:hover',
				' > .ecbb-event__link:hover',
				' a.event-button:hover',
				' > a.event-button:hover',
				' a.ecbb-event-card__button:hover',
				' > a.ecbb-event-card__button:hover',
				' .ecbb-event__term:hover',
				' .ecbb-event__title-text:hover',
				' .ecbb-event-card__category:hover',
			];
		}

		public static function ecbb_repeater_hover_li_has_suffixes() {
			return [
				':hover > .ecbb-event-card__meta-icon',
				':hover .ecbb-event-card__meta-icon--inline',
			];
		}

		public static function ecbb_repeater_hover_selector() {
			$parts = array_map(
				static function ( $suffix ) {
					return '&' . $suffix;
				},
				self::ecbb_repeater_hover_surface_suffixes()
			);
			foreach ( self::ecbb_repeater_hover_li_has_suffixes() as $suffix ) {
				$parts[] = 'li:has(> &)' . $suffix;
			}

			return implode( ', ', $parts );
		}

		public static function ecbb_button_inner_selectors( $scope_sel ) {
			return $scope_sel . ' .ecbb-event__link,'
			. $scope_sel . ' > a,'
			. $scope_sel . ' .ecbb-event__plain,'
			. $scope_sel . ' a.event-button,'
			. $scope_sel . ' a.ecbb-event-card__button';
		}

		public static function ecbb_meta_row_selector( $scope_sel ) {
			$scope_sel = trim( (string) $scope_sel );
			if ( $scope_sel === '' ) {
				return '';
			}
			if ( preg_match( '#^(\.[a-zA-Z0-9\-_]+)\s+(.+)$#', $scope_sel, $matches ) ) {
				return $matches[1] . ' li:has(> ' . $matches[2] . ')';
			}

			return '';
		}

		private static function ecbb_repeater_inner_surface_selectors() {
			return '& .ecbb-event__term-chip, & .ecbb-event__link, & > .ecbb-event__link, & .ecbb-event__term, & > .ecbb-event__term, '
				. '& .ecbb-event__title-text, & .ecbb-event-card__category, & > .ecbb-event-card__category, '
				. '& a.event-button, & > a.event-button, & a.ecbb-event-card__button, & > a.ecbb-event-card__button';
		}

		public static function ecbb_repeater_type_selector() {
			// Layout CTA wrappers are full-width rows; bare `&` typography only adds phantom line-box height.
			$wrapper_exclude = ':not(.ecbb-event-part--read-more):not(.ecbb-event-part--event-tickets):not(.ecbb-event-part--event-rsvp)'
				. ':not(.ecbb-style2-read-more):not(.ecbb-style2-event-tickets):not(.ecbb-style2-event-rsvp)';

			return '&' . $wrapper_exclude . ', ' . self::ecbb_repeater_inner_surface_selectors();
		}

		public static function ecbb_composite_inline_meta_icon_selector( $scope_sel ) {
			$scope_sel = trim( (string) $scope_sel );
			if ( $scope_sel === '' ) {
				return '';
			}

			return $scope_sel . ' .ecbb-event-card__meta-icon--inline';
		}

		public static function ecbb_meta_list_selector( $scope_sel ) {
			$scope_sel = trim( (string) $scope_sel );
			if ( $scope_sel === '' ) {
				return '';
			}
			if ( preg_match( '#^(\.[a-zA-Z0-9\-_]+)\s+(.+)$#', $scope_sel, $matches ) ) {
				$root   = $matches[1];
				$part   = $matches[2];
				$needle = '> li > ' . $part;

				return $root . ' ul.event-meta:has(' . $needle . '),'
					. $root . ' ul.ecbb-event-card__meta:has(' . $needle . ')';
			}

			return '';
		}

		public static function ecbb_repeater_type_css() {
			return [
			[
			'property' => 'typography',
					'selector' => self::ecbb_repeater_type_selector(),
			],
			];
		}

		public static function ecbb_button_parts() {
			return [ 'read_more', 'event_tickets', 'event_rsvp' ];
		}

		public static function ecbb_resolved_meta_icon_selector( $scope_sel, $part_type = '', $list_item_style = '' ) {
			if (
				class_exists( 'ECBB_Markup', false )
				&& \ECBB_Markup::ecbb_part_uses_composite_inline_meta_icons( (string) $part_type )
			) {
				if ( (string) $list_item_style === 'style-2' ) {
					return self::ecbb_composite_boxed_meta_icon_selector( $scope_sel );
				}

				return self::ecbb_composite_inline_meta_icon_selector( $scope_sel );
			}

			return self::ecbb_meta_icon_selector( $scope_sel );
		}

		public static function ecbb_repeater_align_selector() {
			return '&, ' . self::ecbb_repeater_inner_surface_selectors();
		}

	}
}
