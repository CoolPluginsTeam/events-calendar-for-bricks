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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'ECBB_VERSION' ) ) {
	return;
}

define( 'ECBB_VERSION', '1.0.0' );
define( 'ECBB_FILE', __FILE__ );
define( 'ECBB_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECBB_URL', plugin_dir_url( __FILE__ ) );
define( 'ECBB_BASENAME', plugin_basename( __FILE__ ) );
define( 'ECBB_TEC_PLUGIN_FILE', 'the-events-calendar/the-events-calendar.php' );

register_activation_hook( ECBB_FILE, [ 'ECBB_Addon', 'ecbb_activate' ] );

/**
 * Main plugin singleton (mirrors Events_Calendar_Addon / ECTBE bootstrap).
 */
if ( ! class_exists( 'ECBB_Addon', false ) ) {

final class ECBB_Addon {

	/** @var ECBB_Addon|null */
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'ecbb_load_textdomain' ] );
		add_action( 'admin_notices', [ $this, 'ecbb_admin_dependency_notices' ] );
		add_action( 'admin_init', [ $this, 'ecbb_maybe_deactivate_missing_deps' ], 1 );
		add_action( 'after_setup_theme', [ $this, 'ecbb_bootstrap' ], 11 );
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
		$missing = [];
		if ( ! self::ecbb_is_bricks_active() ) {
			$missing['theme'] = true;
		}
		if ( ! self::ecbb_is_tec_active() ) {
			$missing['plugin'] = true;
		}
		return $missing;
	}

	public static function ecbb_dependencies_met() {
		return self::ecbb_get_missing_dependencies() === [];
	}

	/** @param array<string,bool> $missing */
	public static function ecbb_dependency_notice_html( array $missing ) {
		$lines = [];

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

		if ( $lines === [] ) {
			return '';
		}

		$html  = '<strong>' . esc_html__( 'Events Calendar for Bricks Builder could not run.', 'ecbb' ) . '</strong> ';
		$html .= esc_html__( 'This plugin requires:', 'ecbb' );
		$html .= '<ul style="list-style:disc;margin:0.5em 0 0 1.5em;">';
		foreach ( $lines as $line ) {
			$html .= '<li>' . $line . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	public static function ecbb_activate() {
		if ( self::ecbb_dependencies_met() ) {
			return;
		}
		deactivate_plugins( ECBB_BASENAME );
		$message = self::ecbb_dependency_notice_html( self::ecbb_get_missing_dependencies() );
		if ( $message !== '' ) {
			set_transient( 'ecbb_activation_error', $message, 60 );
		}
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	public function ecbb_admin_dependency_notices() {
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
		if ( $missing === [] ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . wp_kses_post( self::ecbb_dependency_notice_html( $missing ) ) . '</p></div>';
	}

	public function ecbb_maybe_deactivate_missing_deps() {
		if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active( ECBB_BASENAME ) ) {
			return;
		}
		if ( self::ecbb_dependencies_met() ) {
			return;
		}
		deactivate_plugins( ECBB_BASENAME );
		$message = self::ecbb_dependency_notice_html( self::ecbb_get_missing_dependencies() );
		if ( $message !== '' ) {
			set_transient( 'ecbb_activation_error', $message, 60 );
		}
	}

	public function ecbb_load_textdomain() {
		load_plugin_textdomain( 'ecbb', false, dirname( ECBB_BASENAME ) . '/languages' );
	}

	/**
	 * Load widget modules when Bricks is available (TEC required for event data, not registration).
	 */
	public function ecbb_bootstrap() {
		if ( ! self::ecbb_is_bricks_active() ) {
			return;
		}

		require_once ECBB_DIR . 'includes/functions.php';

		if ( ! class_exists( 'ECBB_Plugin', false ) ) {
			return;
		}

		new ECBB_WidgetClass();
	}
}

}

/**
 * @return ECBB_Addon
 */
function ecbb_addon() {
	return ECBB_Addon::get_instance();
}

ecbb_addon();
