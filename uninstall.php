<?php
/**
 * Remove plugin data on uninstall.
 *
 * @package OfflineMode
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'offline_mode_settings' );
