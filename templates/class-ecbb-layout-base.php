<?php
/**
 * Shared layout template behavior (default parts, normalization, card shell).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Layout_Base', false ) ) {

	/**
	 * Buffered item-inner renderer (explicit steps instead of mixed ob_start/echo).
	 */
	final class ECBB_Layout_Item_Renderer {

		/** @var string */
		private $html = '';

		/**
		 * @param string $card_class Escaped card root class list.
		 * @return void
		 */
		public function open_card( $card_class ) {
			$this->html .= '<div class="' . esc_attr( $card_class ) . '">';
		}

		/**
		 * @param string $chunk Raw HTML chunk (already escaped by caller).
		 * @return void
		 */
		public function append( $chunk ) {
			$this->html .= $chunk;
		}

		/**
		 * @return void
		 */
		public function close_card() {
			$this->html .= '</div>';
		}

		/**
		 * @return string
		 */
		public function to_string() {
			return $this->html;
		}
	}

	abstract class ECBB_Layout_Base {

		/**
		 * Layout key for {@see ECBB_Markup::ecbb_upgrade_layout_parts()}.
		 *
		 * @return string grid|style1|style2
		 */
		protected static function ecbb_layout_key() {
			return '';
		}

		/**
		 * Default repeater rows before id assignment.
		 *
		 * @return array<int,array<string,mixed>>
		 */
		protected static function ecbb_default_rows() {
			return [];
		}

		/**
		 * Per-row defaults after clean/upgrade.
		 *
		 * @param array<string,mixed> $row Repeater row.
		 * @return array<string,mixed>
		 */
		protected static function ecbb_normalize_row( array $row ) {
			return $row;
		}

		/**
		 * When clean yields no rows, return fallback parts stack.
		 *
		 * @param array<int,mixed> $parts Original parts passed to ecbb_norm_parts().
		 * @return array<int,array<string,mixed>>
		 */
		protected static function ecbb_empty_parts_fallback( array $parts ) {
			unset( $parts );
			return static::ecbb_default_parts();
		}

		/**
		 * Optional filter between clean and per-row normalization.
		 *
		 * @param array<int,mixed> $clean Cleaned repeater rows.
		 * @return array<int,mixed>
		 */
		protected static function ecbb_filter_parts( array $clean ) {
			return $clean;
		}

		/**
		 * Drop repeater rows whose part slug is in the blocked list.
		 *
		 * @param array<int,mixed>   $clean   Cleaned repeater rows.
		 * @param array<int,string>  $blocked Part slugs to remove.
		 * @return array<int,mixed>
		 */
		protected static function ecbb_filter_blocked_parts( array $clean, array $blocked ) {
			if ( $blocked === [] ) {
				return $clean;
			}

			return array_values(
				array_filter(
					$clean,
					static function ( $row ) use ( $blocked ) {
						if ( ! is_array( $row ) ) {
							return true;
						}
						return ! in_array( (string) ( $row['part'] ?? '' ), $blocked, true );
					}
				)
			);
		}

		/**
		 * Skin slug for part rendering and meta rows.
		 *
		 * @return string
		 */
		protected static function ecbb_part_skin() {
			return static::ecbb_layout_key();
		}

		/**
		 * Layout skin for {@see ECBB_Markup::ecbb_render_layout_parts_sequence()}.
		 *
		 * @return string
		 */
		protected static function ecbb_layout_skin() {
			return static::ecbb_part_skin();
		}

		/**
		 * Card root class list (without --no-image modifier).
		 *
		 * @return string
		 */
		protected static function ecbb_card_base_class() {
			return 'ecbb-ev__item-inner';
		}

		/**
		 * Modifier class when the featured image shell is hidden.
		 *
		 * @return string
		 */
		protected static function ecbb_no_image_class() {
			return '';
		}

		/**
		 * Modifier class when the layout date column is hidden.
		 *
		 * @return string
		 */
		protected static function ecbb_no_date_class() {
			return '';
		}

		/**
		 * @return array<int,array<string,mixed>>
		 */
		public static function ecbb_default_parts() {
			$rows = static::ecbb_default_rows();

			return class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_parts_assign_ids( $rows )
				: $rows;
		}

		/**
		 * @param array<int,mixed> $parts Repeater rows.
		 * @return array<int,mixed>
		 */
		public static function ecbb_norm_parts( array $parts ) {
			if ( ! class_exists( 'ECBB_Markup', false ) ) {
				return $parts;
			}

			$upgraded = \ECBB_Markup::ecbb_upgrade_layout_parts(
				$parts,
				static::ecbb_layout_key(),
				static function () {
					return static::ecbb_default_parts();
				}
			);
			if ( is_array( $upgraded ) ) {
				$parts = $upgraded;
			}

			$clean = \ECBB_Markup::ecbb_parts_clean( $parts );
			if ( $clean === [] ) {
				return static::ecbb_empty_parts_fallback( $parts );
			}

			$clean = static::ecbb_filter_parts( $clean );

			$clean = array_map(
				static function ( $row ) {
					if ( ! is_array( $row ) ) {
						return $row;
					}

					return static::ecbb_normalize_row( $row );
				},
				$clean
			);

			return \ECBB_Markup::ecbb_parts_assign_ids( $clean );
		}

		/**
		 * @param \WP_Post $post       Event post.
		 * @param array    $parts      Repeater rows.
		 * @param callable $emit_part  Part renderer.
		 * @param array    $settings   Widget settings.
		 * @param callable|null $emit_meta Meta row renderer (optional).
		 * @return string
		 */
		public static function ecbb_item_inner( $post, array $parts, callable $emit_part, $settings = [], callable $emit_meta = null ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				return '';
			}

			$settings = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_layout_settings( is_array( $settings ) ? $settings : [] )
				: ( is_array( $settings ) ? $settings : [] );
			$show_image = class_exists( 'ECBB_Markup', false )
				? \ECBB_Markup::ecbb_show_event_image( $settings )
				: true;

			$card_class = static::ecbb_card_base_class();
			if ( ! $show_image ) {
				$no_image_class = static::ecbb_no_image_class();
				if ( $no_image_class !== '' ) {
					$card_class .= ' ' . $no_image_class;
				}
			}

			$no_date_class = static::ecbb_no_date_class();
			if (
				$no_date_class !== ''
				&& class_exists( 'ECBB_Markup', false )
				&& ! \ECBB_Markup::ecbb_show_list1_date_column( $settings )
			) {
				$card_class .= ' ' . $no_date_class;
			}

			$renderer = new ECBB_Layout_Item_Renderer();
			$renderer->open_card( $card_class );

			if ( $show_image && class_exists( 'ECBB_Markup', false ) ) {
				ob_start();
				static::ecbb_render_image_shell( $post, $settings );
				$renderer->append( (string) ob_get_clean() );
			}

			ob_start();
			static::ecbb_open_content( $post, $settings, $show_image );
			$renderer->append( (string) ob_get_clean() );

			if ( class_exists( 'ECBB_Markup', false ) ) {
				$skin        = static::ecbb_part_skin();
				$layout_skin = static::ecbb_layout_skin();
				$emit_meta_cb = $emit_meta ?? static function ( $ev, $item, $idx, $price ) use ( $skin, $layout_skin ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ECBB_Markup::ecbb_render_meta_li().
					echo \ECBB_Markup::ecbb_render_meta_li( $ev, $item, $idx, $skin, $layout_skin, (bool) $price );
				};
				ob_start();
				\ECBB_Markup::ecbb_render_layout_parts_sequence(
					$post,
					$parts,
					$layout_skin,
					$skin,
					$emit_part,
					$emit_meta_cb
				);
				$renderer->append( (string) ob_get_clean() );
			}

			ob_start();
			static::ecbb_close_content();
			$renderer->append( (string) ob_get_clean() );

			$renderer->close_card();

			return $renderer->to_string();
		}

		/**
		 * Image shell wrapper class (empty in base = no image column).
		 *
		 * @return string
		 */
		protected static function ecbb_image_wrap_class() {
			return '';
		}

		/**
		 * Optional badge HTML inside the image shell.
		 *
		 * @param \WP_Post              $post     Event post.
		 * @param array<string,mixed>   $settings Widget settings.
		 * @return string
		 */
		protected static function ecbb_image_shell_badge_html( $post, array $settings ) {
			unset( $post, $settings );
			return '';
		}

		/**
		 * Skin key passed to ecbb_shell_featured_image().
		 *
		 * @return string
		 */
		protected static function ecbb_image_shell_featured_skin() {
			return '';
		}

		/**
		 * Featured image column (badge + image).
		 *
		 * @param \WP_Post              $post     Event post.
		 * @param array<string,mixed>   $settings Widget settings.
		 * @return void
		 */
		protected static function ecbb_render_image_shell( $post, array $settings ) {
			$wrap_class = static::ecbb_image_wrap_class();
			if ( $wrap_class === '' ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap_class ) . '">';
			$badge_html = static::ecbb_image_shell_badge_html( $post, $settings );
			if ( $badge_html !== '' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $badge_html;
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECBB_Markup::ecbb_shell_featured_image( $post, static::ecbb_image_shell_featured_skin(), true, false );
			echo '</div>';
		}

		/**
		 * Open content wrappers before the parts sequence.
		 *
		 * @param \WP_Post              $post       Event post.
		 * @param array<string,mixed>   $settings   Widget settings.
		 * @param bool                  $show_image Whether the image shell is visible.
		 * @return void
		 */
		protected static function ecbb_open_content( $post, array $settings, $show_image ) {
			unset( $post, $settings, $show_image );
		}

		/**
		 * Close content wrappers opened in ecbb_open_content().
		 *
		 * @return void
		 */
		protected static function ecbb_close_content() {
		}
	}

}
