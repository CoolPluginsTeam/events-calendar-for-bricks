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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECBB_VERSION', '1.0.0' );
define( 'ECBB_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECBB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for translation-ready strings.
 *
 * @return void
 */
function ecbb_load_textdomain() {
	load_plugin_textdomain(
		'ecbb',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'ecbb_load_textdomain' );

/**
 * Check Bricks theme and The Events Calendar dependencies.
 *
 * @return bool
 */
function ecbb_check_dependencies() {
	$missing = [];

	if ( get_template() !== 'bricks' ) {
		$missing['theme'] = true;
	}

	if ( ! class_exists( 'Tribe__Events__Main' ) && ! function_exists( 'tribe_get_events' ) ) {
		$missing['plugin'] = true;
	}

	if ( ! empty( $missing ) ) {
		add_action(
			'admin_notices',
			static function () use ( $missing ) {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>' . esc_html__( 'Events Calendar for Bricks Builder:', 'ecbb' ) . '</strong> ';
				echo esc_html__( 'This plugin requires the following to be installed and activated:', 'ecbb' );
				echo '</p><ul>';
				if ( ! empty( $missing['theme'] ) ) {
					printf(
						'<li><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></li>',
						esc_url( admin_url( 'theme-install.php?search=bricks' ) ),
						esc_html__( 'Bricks Theme', 'ecbb' )
					);
				}
				if ( ! empty( $missing['plugin'] ) ) {
					printf(
						'<li><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></li>',
						esc_url( admin_url( 'plugin-install.php?s=the-events-calendar' ) ),
						esc_html__( 'The Events Calendar Plugin', 'ecbb' )
					);
				}
				echo '</ul></div>';
			}
		);
		return false;
	}

	return true;
}

require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-query.php';
require_once ECBB_DIR . 'includes/events-widget/ecbb-events-widget-loop-markup.php';
require_once ECBB_DIR . 'includes/events-widget/template/list/list-style-1.php';
require_once ECBB_DIR . 'includes/events-widget/grid/ecbb-events-widget-grid-markup.php';
require_once ECBB_DIR . 'includes/events-widget/template/list/list-style-2.php';
require_once ECBB_DIR . 'includes/class-ecbb-plugin.php';

/**
 * Initialize plugin when dependencies are met.
 *
 * @return void
 */
function ecbb_init() {
	if ( ecbb_check_dependencies() && class_exists( 'Bricks\Elements' ) ) {
		new ECBB_Plugin();
	}
}
add_action( 'init', 'ecbb_init' );
