<?php
/**
 * ECBB_Styles facade — delegates to style service classes.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dir = ECBB_DIR . 'includes/styles/styles-elements/';
foreach (
	[
		'ecbb-css-value-sanitizer.php',
		'ecbb-selector-factory.php',
		'ecbb-date-format-presets.php',
		'ecbb-meta-combo.php',
		'ecbb-part-options.php',
		'ecbb-layout-css-generator.php',
		'ecbb-parts-css-generator.php',
		'ecbb-shell-css-generator.php',
	] as $file
) {
	require_once $dir . $file;
}
unset( $dir, $file );

if ( ! class_exists( 'ECBB_Styles', false ) ) {

	final class ECBB_Styles {

		/** @var array<string,string>|null */
		private static $delegates = null;

		/** Build method => service class map (cached). */
		private static function delegates(): array {
			if ( is_array( self::$delegates ) ) {
				return self::$delegates;
			}
			$bind = static function ( array $methods, string $class ): array {
				return array_fill_keys( $methods, $class );
			};
			self::$delegates = array_merge(
				$bind(
					[
						'ecbb_repeater_align_selector', 'ecbb_repeater_hover_selector',
						'ecbb_repeater_type_css', 'ecbb_button_parts',
					],
					'ECBB_Selector_Factory'
				),
				$bind(
					[ 'ecbb_date_php_format', 'ecbb_date_options' ],
					'ECBB_Date_Format_Presets'
				),
				$bind(
					[
						'ecbb_meta_combo_order', 'ecbb_meta_combo_all_slugs', 'ecbb_is_meta_combo_slug',
						'ecbb_meta_combo_has_segment', 'ecbb_meta_combo_slugs_style1', 'ecbb_meta_combo_slugs_style2',
						'ecbb_meta_combo_slugs_with_segment', 'ecbb_normalize_meta_combo_row',
					],
					'ECBB_Meta_Combo'
				),
				$bind(
					[
						'ecbb_part_options', 'ecbb_part_options_style1', 'ecbb_part_options_style2',
						'ecbb_part_options_grid', 'ecbb_clean_part',
					],
					'ECBB_Part_Options'
				),
				$bind(
					[ 'ecbb_grid_cols_css', 'ecbb_gap_responsive_css' ],
					'ECBB_Layout_Css_Generator'
				),
				$bind(
					[ 'ecbb_parts_css' ],
					'ECBB_Parts_Css_Generator'
				),
				$bind(
					[ 'ecbb_layout_shell_css' ],
					'ECBB_Shell_Css_Generator'
				)
			);
			return self::$delegates;
		}

		public static function __callStatic( $name, $args ) {
			$map = self::delegates();
			if ( isset( $map[ $name ] ) ) {
				return forward_static_call_array( [ $map[ $name ], $name ], $args );
			}
			throw new BadMethodCallException( 'ECBB_Styles::' . $name . ' is not defined.' );
		}
	}
}
