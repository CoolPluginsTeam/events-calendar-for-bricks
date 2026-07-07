<?php
/**
 * ECBB_Controls facade — delegates to control registrar service classes.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dir = ECBB_DIR . 'includes/controls/controls-elements/';
foreach (
	[
		'ecbb-hover-controls.php',
		'ecbb-part-fields.php',
		'ecbb-card-style-controls.php',
		'ecbb-layout-controls.php',
		'ecbb-query-controls.php',
		'ecbb-parts-repeater-controls.php',
	] as $file
) {
	require_once $dir . $file;
}
unset( $dir, $file );

if ( ! class_exists( 'ECBB_Controls', false ) ) {

	final class ECBB_Controls {

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
						'ecbb_hover_part_types', 'ecbb_hover_field_keys', 'ecbb_btn_border_keys',
						'ecbb_style2_meta_icon_ui_parts', 'ecbb_sanitize_hover_animation_slug',
					],
					'ECBB_Hover_Controls'
				),
				$bind(
					[ 'ecbb_register_controls' ],
					'ECBB_Parts_Repeater_Controls'
				)
			);
			return self::$delegates;
		}

		public static function __callStatic( $name, $args ) {
			$map = self::delegates();
			if ( isset( $map[ $name ] ) ) {
				return forward_static_call_array( [ $map[ $name ], $name ], $args );
			}
			throw new BadMethodCallException( 'ECBB_Controls::' . $name . ' is not defined.' );
		}
	}
}
