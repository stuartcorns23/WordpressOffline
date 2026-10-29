<?php
/**
 * Front-end interception and landing page rendering.
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves the landing page to visitors while the site is offline.
 */
class Offline_Mode_Frontend {

	const PREVIEW_ARG = 'offline_mode_preview';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		// template_redirect only fires for front-end page loads, so wp-admin,
		// wp-login.php, admin-ajax, wp-cron and the REST API keep working.
		add_action( 'template_redirect', array( $this, 'maybe_show_landing_page' ), 0 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_notice' ), 100 );
		add_action( 'wp_head', array( $this, 'admin_bar_style' ) );
		add_action( 'admin_head', array( $this, 'admin_bar_style' ) );
	}

	/**
	 * Whether the current visitor may see the real site.
	 *
	 * @return bool
	 */
	public static function can_bypass() {
		/**
		 * Filters whether the current visitor bypasses the landing page.
		 *
		 * @param bool $bypass Default: true for any logged-in user.
		 */
		return (bool) apply_filters( 'offline_mode_can_bypass', is_user_logged_in() );
	}

	/**
	 * Whether this request is an admin asking to preview the landing page.
	 *
	 * @return bool
	 */
	private function is_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview flag.
		return isset( $_GET[ self::PREVIEW_ARG ] ) && current_user_can( 'manage_options' );
	}

	/**
	 * Show the landing page instead of the site when appropriate.
	 */
	public function maybe_show_landing_page() {
		$preview = $this->is_preview();
		$mode    = Offline_Mode_Options::mode();

		if ( ! $preview ) {
			if ( Offline_Mode_Options::MODE_OFF === $mode || self::can_bypass() ) {
				return;
			}
			// Let crawlers and browsers still fetch robots.txt and the favicon.
			if ( is_robots() || ( function_exists( 'is_favicon' ) && is_favicon() ) ) {
				return;
			}
		} elseif ( Offline_Mode_Options::MODE_OFF === $mode ) {
			// Preview whichever mode is saved, defaulting to maintenance.
			$saved = Offline_Mode_Options::saved_mode();
			$mode  = Offline_Mode_Options::MODE_OFF === $saved ? Offline_Mode_Options::MODE_MAINTENANCE : $saved;
		}

		$this->render( $mode );
		exit;
	}

	/**
	 * Send headers and output the landing page.
	 *
	 * @param string $mode Mode to render.
	 */
	public function render( $mode ) {
		nocache_headers();

		if ( Offline_Mode_Options::MODE_MAINTENANCE === $mode ) {
			status_header( 503 );
			header( 'Retry-After: ' . ( absint( Offline_Mode_Options::get( 'retry_after' ) ) * HOUR_IN_SECONDS ) );
		} else {
			status_header( 200 );
		}
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		$settings = Offline_Mode_Options::all();
		$view     = self::view_data( $settings, $mode );

		/**
		 * Filters the data passed to the landing page template.
		 *
		 * @param array  $view Template data.
		 * @param string $mode Current mode.
		 */
		$view = apply_filters( 'offline_mode_view_data', $view, $mode );

		// Themes can override the template with offline-mode/landing.php.
		$template = locate_template( 'offline-mode/landing.php' );
		if ( ! $template ) {
			$template = OFFLINE_MODE_DIR . 'templates/landing.php';
		}

		include $template;
	}

	/**
	 * Build the values the template needs.
	 *
	 * @param array  $settings Plugin settings.
	 * @param string $mode     Mode being rendered.
	 * @return array
	 */
	public static function view_data( array $settings, $mode ) {
		$fonts = Offline_Mode_Options::fonts();

		$logo_url = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'full' ) : '';
		$bg_url   = $settings['background_id'] ? wp_get_attachment_image_url( $settings['background_id'], 'full' ) : '';

		$title = $settings['page_title'];
		if ( '' === $title ) {
			$title = $settings['heading'] ? $settings['heading'] . ' – ' . get_bloginfo( 'name' ) : get_bloginfo( 'name' );
		}

		$launch = Offline_Mode_Options::launch_timestamp();

		return array(
			'mode'             => $mode,
			'title'            => $title,
			'heading'          => $settings['heading'],
			'message'          => $settings['message'],
			'logo_url'         => $logo_url ? $logo_url : '',
			'logo_width'       => (int) $settings['logo_width'],
			'background_url'   => $bg_url ? $bg_url : '',
			'background_color' => $settings['background_color'],
			'overlay_opacity'  => (int) $settings['overlay_opacity'],
			'text_color'       => $settings['text_color'],
			'accent_color'     => $settings['accent_color'],
			'font_stack'       => isset( $fonts[ $settings['font'] ] ) ? $fonts[ $settings['font'] ]['stack'] : $fonts['system']['stack'],
			'countdown_to'     => ( $settings['show_countdown'] && $launch > time() ) ? $launch : 0,
			'noindex'          => Offline_Mode_Options::MODE_MAINTENANCE === $mode || ! empty( $settings['noindex'] ),
			'login_url'        => $settings['show_login_link'] ? wp_login_url( home_url( '/' ) ) : '',
			'custom_css'       => $settings['custom_css'],
		);
	}

	/**
	 * Remind logged-in users that visitors cannot see the site.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 */
	public function admin_bar_notice( $bar ) {
		$mode = Offline_Mode_Options::mode();
		if ( Offline_Mode_Options::MODE_OFF === $mode || ! is_user_logged_in() ) {
			return;
		}

		$label = Offline_Mode_Options::MODE_MAINTENANCE === $mode
			? __( 'Maintenance mode is on', 'offline-mode' )
			: __( 'Coming soon mode is on', 'offline-mode' );

		$bar->add_node(
			array(
				'id'    => 'offline-mode',
				'title' => esc_html( $label ),
				'href'  => current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=offline-mode' ) : false,
				'meta'  => array( 'class' => 'offline-mode-active' ),
			)
		);
	}

	/**
	 * Highlight the admin bar notice.
	 */
	public function admin_bar_style() {
		if ( ! is_admin_bar_showing() || Offline_Mode_Options::MODE_OFF === Offline_Mode_Options::mode() ) {
			return;
		}
		echo '<style>#wpadminbar .offline-mode-active > .ab-item{background:#b32d2e;color:#fff;}</style>';
	}
}
