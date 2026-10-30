<?php
/**
 * Detects leftovers from other maintenance plugins and clears page caches.
 *
 * A deleted maintenance plugin can leave files behind (drop-ins, must-use
 * plugins, .htaccess rules) or a cached copy of its page, and those are
 * served instead of ours.
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conflict detection and cache purging.
 */
class Offline_Mode_Conflicts {

	const PURGE_HOOK = 'offline_mode_purge_cache';

	/**
	 * Words that suggest another plugin or file serves a maintenance page.
	 */
	const PATTERN = '/maintenance|coming[\s_-]*soon|under[\s_-]*construction|seedprod/i';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'update_option_' . Offline_Mode_Options::OPTION, array( $this, 'on_settings_saved' ), 10, 2 );
		add_action( 'add_option_' . Offline_Mode_Options::OPTION, array( $this, 'on_settings_added' ), 10, 2 );
		add_action( self::PURGE_HOOK, array( __CLASS__, 'purge_caches' ) );
	}

	/**
	 * First save of the settings.
	 *
	 * @param string $option Option name.
	 * @param array  $value  New value.
	 */
	public function on_settings_added( $option, $value ) {
		$this->on_settings_saved( array(), $value );
	}

	/**
	 * Purge caches after a save so visitors get the new mode straight away,
	 * and schedule a purge for the automatic launch time.
	 *
	 * @param mixed $old_value Previous settings.
	 * @param mixed $value     New settings.
	 */
	public function on_settings_saved( $old_value, $value ) {
		self::purge_caches();

		wp_clear_scheduled_hook( self::PURGE_HOOK );
		if ( is_array( $value ) && Offline_Mode_Options::MODE_COMING_SOON === ( isset( $value['mode'] ) ? $value['mode'] : '' ) && ! empty( $value['auto_launch'] ) ) {
			$launch = Offline_Mode_Options::launch_timestamp();
			if ( $launch > time() ) {
				wp_schedule_single_event( $launch, self::PURGE_HOOK );
			}
		}
	}

	/**
	 * Clear the object cache and any page cache from popular plugins and hosts.
	 */
	public static function purge_caches() {
		wp_cache_flush();

		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		// WP Fastest Cache.
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache( true );
		}
		// SiteGround Optimizer.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		// WP Engine.
		if ( class_exists( 'WpeCommon' ) ) {
			if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
				WpeCommon::purge_memcached();
			}
			if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
				WpeCommon::purge_varnish_cache();
			}
		}
		// LiteSpeed Cache, Cache Enabler, Breeze, Nginx Helper.
		do_action( 'litespeed_purge_all' );
		do_action( 'cache_enabler_clear_complete_cache' );
		do_action( 'breeze_clear_all_cache' );
		do_action( 'rt_nginx_helper_purge_all' );

		/**
		 * Fires after Offline Mode purges caches, for other caches to hook in.
		 */
		do_action( 'offline_mode_after_purge_cache' );
	}

	/**
	 * Find anything that may serve another plugin's maintenance page.
	 *
	 * @return string[] Human-readable problems, already safe for HTML.
	 */
	public static function detect() {
		$problems = array();

		if ( file_exists( ABSPATH . '.maintenance' ) ) {
			$problems[] = sprintf(
				/* translators: %s: file path. */
				__( '%s exists, so WordPress is in its own maintenance mode for everyone. Delete it unless an update is running right now.', 'offline-mode' ),
				'<code>' . esc_html( ABSPATH . '.maintenance' ) . '</code>'
			);
		}

		if ( file_exists( WP_CONTENT_DIR . '/maintenance.php' ) ) {
			$problems[] = sprintf(
				/* translators: %s: file path. */
				__( '%s is a maintenance page left by another plugin. Delete it.', 'offline-mode' ),
				'<code>' . esc_html( WP_CONTENT_DIR . '/maintenance.php' ) . '</code>'
			);
		}

		if ( ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_mu_plugins() as $file => $data ) {
			if ( preg_match( self::PATTERN, $file . ' ' . $data['Name'] ) ) {
				$problems[] = sprintf(
					/* translators: %s: file path. */
					__( '%s is a must-use plugin that may show its own maintenance page. Must-use plugins keep running after the main plugin is deleted. Delete it if it belongs to an old plugin.', 'offline-mode' ),
					'<code>' . esc_html( WPMU_PLUGIN_DIR . '/' . $file ) . '</code>'
				);
			}
		}

		foreach ( get_plugins() as $file => $data ) {
			if ( dirname( $file ) === dirname( plugin_basename( OFFLINE_MODE_FILE ) ) || ! is_plugin_active( $file ) ) {
				continue;
			}
			if ( preg_match( self::PATTERN, $data['Name'] ) ) {
				$problems[] = sprintf(
					/* translators: %s: plugin name. */
					__( 'The plugin %s is active and may show its own maintenance page. Deactivate it.', 'offline-mode' ),
					'<strong>' . esc_html( $data['Name'] ) . '</strong>'
				);
			}
		}

		$htaccess = ABSPATH . '.htaccess';
		if ( is_readable( $htaccess ) ) {
			$contents = (string) file_get_contents( $htaccess ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			foreach ( preg_split( '/\R/', $contents ) as $line ) {
				$line = trim( $line );
				if ( '' !== $line && '#' !== $line[0] && preg_match( self::PATTERN, $line ) ) {
					$problems[] = sprintf(
						/* translators: 1: file path, 2: the matching line. */
						__( '%1$s has a rule that may send visitors to another maintenance page: %2$s', 'offline-mode' ),
						'<code>' . esc_html( $htaccess ) . '</code>',
						'<code>' . esc_html( $line ) . '</code>'
					);
					break;
				}
			}
		}

		if ( defined( 'WP_CACHE' ) && WP_CACHE && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
			$problems[] = __( 'A page cache is active. Offline Mode clears common caches when you save, but if visitors still see an old page, clear your caching plugin, host or CDN (for example Cloudflare) cache by hand.', 'offline-mode' );
		}

		return $problems;
	}
}
