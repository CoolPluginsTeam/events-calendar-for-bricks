<?php
/**
 * Plugin Name: Events Calendar for Bricks Builder
 * Plugin URI: https://example.com/ecbb
 * Description: A custom addon for Bricks theme to add events-related widgets with typography. Requires The Events Calendar plugin and Bricks theme.
 * Version: 1.0.0
 * Author: Your Name
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
define( 'ECBB_TEC_PLUGIN_FILE', 'the-events-calendar/the-events-calendar.php' );

if ( ! class_exists( 'EventsCalendarForBricks' ) ) {

	class EventsCalendarForBricks {

		public function __construct() {
			register_activation_hook( ECBB_FILE, array( $this, 'ecbb_activate' ) );
			add_action( 'init', array( $this, 'ecbb_load_textdomain' ) );
			add_action( 'admin_notices', array( $this, 'ecbb_render_dependency_notice' ) );
			add_action( 'after_setup_theme', array( $this, 'ecbb_register_bricks_widget' ), 11 );
		}

		/**
		 * Load widget helpers, layouts, and bootstrap class.
		 */
		public function ecbb_load_files() {
			require_once ECBB_DIR . 'includes/query.php';
			require_once ECBB_DIR . 'includes/markup.php';
			require_once ECBB_DIR . 'includes/styles.php';
			require_once ECBB_DIR . 'includes/controls.php';
			require_once ECBB_DIR . 'widgets/layouts/ecbb-list-1.php';
			require_once ECBB_DIR . 'widgets/layouts/ecbb-grid.php';
			require_once ECBB_DIR . 'widgets/layouts/ecbb-list-2.php';
			require_once ECBB_DIR . 'includes/class-ecbb-plugin.php';
		}

		/**
		 * Load plugin text domain and track install metadata.
		 */
		public function ecbb_load_textdomain() {
			load_plugin_textdomain( 'ecbb', false, basename( dirname( ECBB_FILE ) ) . '/languages/' );

			if ( ! get_option( 'ecbb_initial_save_version' ) ) {
				add_option( 'ecbb_initial_save_version', ECBB_VERSION );
			}

			if ( ! get_option( 'ecbb-initial-installDate' ) ) {
				add_option( 'ecbb-initial-installDate', gmdate( 'Y-m-d h:i:s' ) );
			}
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

		public static function ecbb_is_tec_active() {
			if ( class_exists( 'Tribe__Events__Main' ) || function_exists( 'tribe_get_events' ) ) {
				return true;
			}
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return is_plugin_active( ECBB_TEC_PLUGIN_FILE );
		}

		/** @return array<string,bool> */
		public static function ecbb_get_missing_dependencies() {
			$missing = array();
			if ( ! self::ecbb_is_bricks_active() ) {
				$missing['theme'] = true;
			}
			if ( ! self::ecbb_is_tec_active() ) {
				$missing['plugin'] = true;
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

			if ( ! empty( $missing['plugin'] ) ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$plugins       = get_plugins();
				$tec_installed = isset( $plugins[ ECBB_TEC_PLUGIN_FILE ] );
				if ( $tec_installed ) {
					$lines[] = sprintf(
						esc_html__( 'The Events Calendar — %s', 'ecbb' ),
						'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'activate it on the Plugins screen', 'ecbb' ) . '</a>'
					);
				} else {
					$lines[] = sprintf(
						esc_html__( 'The Events Calendar — %s', 'ecbb' ),
						'<a href="' . esc_url( admin_url( 'plugin-install.php?s=the-events-calendar&tab=search&type=term' ) ) . '">' . esc_html__( 'install and activate the plugin', 'ecbb' ) . '</a>'
					);
				}
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
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$activation_error = get_transient( 'ecbb_activation_error' );
			if ( is_string( $activation_error ) && $activation_error !== '' ) {
				delete_transient( 'ecbb_activation_error' );
				echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( $activation_error ) . '</p></div>';
				return;
			}
			if ( ! is_plugin_active( ECBB_BASENAME ) ) {
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

			if ( ! self::ecbb_dependencies_met() ) {
				$message = self::ecbb_dependency_notice_html( self::ecbb_get_missing_dependencies() );
				if ( $message !== '' ) {
					set_transient( 'ecbb_activation_error', $message, 60 );
				}
			}

			if ( ! get_option( 'ecbb_initial_save_version' ) ) {
				add_option( 'ecbb_initial_save_version', ECBB_VERSION );
			}

			if ( ! get_option( 'ecbb-initial-installDate' ) ) {
				add_option( 'ecbb-initial-installDate', gmdate( 'Y-m-d h:i:s' ) );
			}
		}
	}

}

new EventsCalendarForBricks();
