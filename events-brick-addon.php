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

define( 'ECBB_VERSION', '1.0.0' );
define( 'ECBB_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECBB_URL', plugin_dir_url( __FILE__ ) );
define( 'ECBB_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Slug for The Events Calendar main plugin file (relative to wp-content/plugins).
 */
define( 'ECBB_TEC_PLUGIN_FILE', 'the-events-calendar/the-events-calendar.php' );

/**
 * @return bool
 */
function ecbb_is_bricks_active() {
	return get_template() === 'bricks';
}

/**
 * @return bool
 */
function ecbb_is_tec_active() {
	if ( class_exists( 'Tribe__Events__Main' ) || function_exists( 'tribe_get_events' ) ) {
		return true;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( ECBB_TEC_PLUGIN_FILE );
}

/**
 * Missing dependency keys: theme, plugin.
 *
 * @return array<string,bool>
 */
function ecbb_get_missing_dependencies() {
	$missing = [];

	if ( ! ecbb_is_bricks_active() ) {
		$missing['theme'] = true;
	}

	if ( ! ecbb_is_tec_active() ) {
		$missing['plugin'] = true;
	}

	return $missing;
}

/**
 * @return bool
 */
function ecbb_dependencies_met() {
	return ecbb_get_missing_dependencies() === [];
}

/**
 * @param array<string,bool> $missing
 * @return string HTML message.
 */
function ecbb_dependency_notice_html( array $missing ) {
	$lines = [];

	if ( ! empty( $missing['theme'] ) ) {
		$lines[] = sprintf(
			/* translators: %s: link to Bricks theme */
			esc_html__( 'Bricks theme — %s', 'ecbb' ),
			'<a href="' . esc_url( admin_url( 'themes.php' ) ) . '">' . esc_html__( 'activate Bricks in Appearance → Themes', 'ecbb' ) . '</a>'
		);
	}

	if ( ! empty( $missing['plugin'] ) ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins     = get_plugins();
		$tec_installed = isset( $plugins[ ECBB_TEC_PLUGIN_FILE ] );

		if ( $tec_installed ) {
			$lines[] = sprintf(
				/* translators: %s: link to plugins screen */
				esc_html__( 'The Events Calendar — %s', 'ecbb' ),
				'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'activate it on the Plugins screen', 'ecbb' ) . '</a>'
			);
		} else {
			$install_url = admin_url( 'plugin-install.php?s=the-events-calendar&tab=search&type=term' );
			$lines[]     = sprintf(
				/* translators: %s: link to install The Events Calendar */
				esc_html__( 'The Events Calendar — %s', 'ecbb' ),
				'<a href="' . esc_url( $install_url ) . '">' . esc_html__( 'install and activate the plugin', 'ecbb' ) . '</a>'
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

/**
 * Block activation when Bricks or The Events Calendar is missing.
 *
 * @return void
 */
function ecbb_on_activation() {
	if ( ecbb_dependencies_met() ) {
		return;
	}

	deactivate_plugins( ECBB_BASENAME );

	$message = ecbb_dependency_notice_html( ecbb_get_missing_dependencies() );
	if ( $message !== '' ) {
		set_transient( 'ecbb_activation_error', $message, 60 );
	}

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}
register_activation_hook( __FILE__, 'ecbb_on_activation' );

/**
 * Show activation failure or runtime dependency notices in wp-admin.
 *
 * @return void
 */
function ecbb_admin_dependency_notices() {
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

	$missing = ecbb_get_missing_dependencies();
	if ( $missing === [] ) {
		return;
	}

	echo '<div class="notice notice-error"><p>' . wp_kses_post( ecbb_dependency_notice_html( $missing ) ) . '</p></div>';
}
add_action( 'admin_notices', 'ecbb_admin_dependency_notices' );

/**
 * Deactivate this plugin if a required dependency was removed after activation.
 *
 * @return void
 */
function ecbb_maybe_deactivate_missing_deps() {
	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( ECBB_BASENAME ) ) {
		return;
	}

	if ( ecbb_dependencies_met() ) {
		return;
	}

	deactivate_plugins( ECBB_BASENAME );

	$message = ecbb_dependency_notice_html( ecbb_get_missing_dependencies() );
	if ( $message !== '' ) {
		set_transient( 'ecbb_activation_error', $message, 60 );
	}
}
add_action( 'admin_init', 'ecbb_maybe_deactivate_missing_deps', 1 );

/**
 * Load plugin text domain for translation-ready strings.
 *
 * @return void
 */
function ecbb_load_textdomain() {
	load_plugin_textdomain(
		'ecbb',
		false,
		dirname( ECBB_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'ecbb_load_textdomain' );

/**
 * Load plugin files and bootstrap features when dependencies are satisfied.
 *
 * @return void
 */
function ecbb_bootstrap() {
	if ( ! ecbb_dependencies_met() ) {
		return;
	}

	require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-query.php';
	require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-loop-markup.php';
	require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-part-styles.php';
	require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-controls.php';
	require_once ECBB_DIR . 'includes/events-widget/template/list/list-style-1.php';
	require_once ECBB_DIR . 'includes/events-widget/grid/ecbb-events-widget-grid-markup.php';
	require_once ECBB_DIR . 'includes/events-widget/template/list/list-style-2.php';
	require_once ECBB_DIR . 'includes/class-ecbb-plugin.php';

	if ( class_exists( 'Bricks\Elements' ) ) {
		new ECBB_Plugin();
	}
}
add_action( 'init', 'ecbb_bootstrap' );
