<?php
/**
 * Plugin Name: Events Calendar for Bricks Builder
 *  Plugin URI: https://eventscalendaraddons.com/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
 * Description: A custom addon for Bricks theme to add events-related widgets with typography. Requires The Events Calendar plugin and Bricks theme.
 * Version: 1.0.0
 * Author: Cool Plugins
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ecbb
 * Requires Plugins: the-events-calendar
 */

namespace EventsCalendarForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'ECBB_VERSION' ) ) {
	return;
}

define( 'ECBB_VERSION', '1.0.0' );
define( 'ECBB_FILE', __FILE__ );
define( 'ECBB_DIR', plugin_dir_path( ECBB_FILE ) );
define( 'ECBB_URL', plugin_dir_url( ECBB_FILE ) );
define( 'ECBB_BASENAME', plugin_basename( ECBB_FILE ) );

if ( ! class_exists( 'EventsCalendarForBricks' ) ) {

	class EventsCalendarForBricks {

		public function __construct() {
			register_activation_hook( ECBB_FILE, array( $this, 'ecbb_activate' ) );
			add_action( 'admin_notices', array( $this, 'ecbb_render_dependency_notice' ) );
			add_action( 'after_setup_theme', array( $this, 'ecbb_register_bricks_widget' ), 11 );
		}

		/**
		 * Load the lightweight integration bootstrap (hooks only).
		 *
		 * The heavy render/builder helpers (query, markup, styles, controls,
		 * layouts) are loaded on demand by {@see ECBB_WidgetClass} so they are
		 * not parsed on cron, REST, or front-end requests that never use the
		 * events widget.
		 */
		public function ecbb_load_files() {
			require_once ECBB_DIR . 'includes/class-ecbb-plugin.php';
		}


		/**
		 * Load integration files and register the Bricks events widget element
		 * once dependencies are met (hooked on after_setup_theme).
		 */
		public function ecbb_register_bricks_widget() {
			if ( ! self::ecbb_dependencies_met() ) {
				return;
			}

			$this->ecbb_load_files();

			if ( ! class_exists( 'ECBB_Plugin', false ) ) {
				return;
			}

			new \ECBB_WidgetClass();
		}

		public static function ecbb_is_bricks_active() {
			return defined( 'BRICKS_VERSION' ) || get_template() === 'bricks';
		}

		/** @return array<string,bool> */
		public static function ecbb_get_missing_dependencies() {
			$missing = array();
			if ( ! self::ecbb_is_bricks_active() ) {
				$missing['theme'] = true;
			}
			return $missing;
		}

		public static function ecbb_dependencies_met() {
			return self::ecbb_get_missing_dependencies() === array();
		}

		/** @param array<string,bool> $missing */
		public static function ecbb_dependency_notice_html( array $missing ) {
			$lines = array();

			if ( ! empty( $missing['theme'] ) ) {
				$lines[] = sprintf(
					esc_html__( 'Bricks theme — %s', 'ecbb' ),
					'<a href="' . esc_url( admin_url( 'themes.php' ) ) . '">' . esc_html__( 'activate Bricks in Appearance → Themes', 'ecbb' ) . '</a>'
				);
			}

			if ( $lines === array() ) {
				return '';
			}

			$html  = '<strong>' . esc_html__( 'Events Calendar for Bricks Builder is missing required dependencies.', 'ecbb' ) . '</strong> ';
			$html .= esc_html__( 'This plugin requires:', 'ecbb' );
			$html .= '<ul style="list-style:disc;margin:0.5em 0 0 1.5em;">';
			foreach ( $lines as $line ) {
				$html .= '<li>' . $line . '</li>';
			}
			$html .= '</ul>';

			return $html;
		}

		public function ecbb_render_dependency_notice() {
			if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$missing = self::ecbb_get_missing_dependencies();
			if ( $missing === array() ) {
				return;
			}
			echo '<div class="notice notice-error"><p>' . wp_kses_post( self::ecbb_dependency_notice_html( $missing ) ) . '</p></div>';
		}

		public function ecbb_activate() {
			update_option( 'ecbb-installDate', gmdate( 'Y-m-d h:i:s' ) );

			if ( ! get_option( 'ecbb_initial_save_version' ) ) {
				add_option( 'ecbb_initial_save_version', ECBB_VERSION );
			}

			if ( ! get_option( 'ecbb_initial_installDate' ) ) {
				add_option( 'ecbb_initial_installDate', gmdate( 'Y-m-d h:i:s' ) );
			}
		}
	}

}

new EventsCalendarForBricks();
