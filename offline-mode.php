<?php
/**
 * Plugin Name:       Offline Mode
 * Description:       Switch your site into maintenance mode or coming soon mode with a customisable landing page. Logged-in users bypass it and see the site as normal.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Stuart Corns
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       offline-mode
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OFFLINE_MODE_VERSION', '1.1.0' );
define( 'OFFLINE_MODE_FILE', __FILE__ );
define( 'OFFLINE_MODE_DIR', plugin_dir_path( __FILE__ ) );
define( 'OFFLINE_MODE_URL', plugin_dir_url( __FILE__ ) );

require_once OFFLINE_MODE_DIR . 'includes/class-offline-mode-options.php';
require_once OFFLINE_MODE_DIR . 'includes/class-offline-mode-admin.php';
require_once OFFLINE_MODE_DIR . 'includes/class-offline-mode-frontend.php';
require_once OFFLINE_MODE_DIR . 'includes/class-offline-mode-conflicts.php';

/**
 * Boot the plugin.
 */
function offline_mode_init() {
	if ( is_admin() ) {
		( new Offline_Mode_Admin() )->register();
	}
	( new Offline_Mode_Frontend() )->register();
	( new Offline_Mode_Conflicts() )->register();
}
add_action( 'plugins_loaded', 'offline_mode_init' );

/**
 * Seed default options on activation without overwriting existing settings.
 */
function offline_mode_activate() {
	add_option( Offline_Mode_Options::OPTION, Offline_Mode_Options::defaults() );
}
register_activation_hook( __FILE__, 'offline_mode_activate' );
