<?php
/**
 * Settings storage, defaults and sanitisation.
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps the single option array the plugin stores.
 */
class Offline_Mode_Options {

	const OPTION = 'offline_mode_settings';

	const MODE_OFF         = 'off';
	const MODE_MAINTENANCE = 'maintenance';
	const MODE_COMING_SOON = 'coming_soon';

	/**
	 * Default values for every setting.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'mode'             => self::MODE_OFF,
			'page_title'       => '',
			'heading'          => __( 'We’ll be back soon', 'offline-mode' ),
			'message'          => __( 'We are currently performing scheduled maintenance. Please check back shortly.', 'offline-mode' ),
			'logo_id'          => 0,
			'logo_width'       => 200,
			'background_id'    => 0,
			'background_color' => '#1e293b',
			'overlay_opacity'  => 40,
			'text_color'       => '#ffffff',
			'accent_color'     => '#38bdf8',
			'font'             => 'system',
			'launch_date'      => '',
			'show_countdown'   => 0,
			'auto_launch'      => 0,
			'retry_after'      => 1,
			'noindex'          => 1,
			'show_login_link'  => 0,
			'custom_css'       => '',
		);
	}

	/**
	 * Available modes and their labels.
	 *
	 * @return array
	 */
	public static function modes() {
		return array(
			self::MODE_OFF         => __( 'Off — site is live', 'offline-mode' ),
			self::MODE_MAINTENANCE => __( 'Maintenance mode — returns 503 Service Unavailable', 'offline-mode' ),
			self::MODE_COMING_SOON => __( 'Coming soon — returns 200 OK', 'offline-mode' ),
		);
	}

	/**
	 * Available font stacks.
	 *
	 * @return array
	 */
	public static function fonts() {
		return array(
			'system' => array(
				'label' => __( 'System sans-serif', 'offline-mode' ),
				'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
			),
			'serif'  => array(
				'label' => __( 'Serif', 'offline-mode' ),
				'stack' => 'Georgia, "Times New Roman", Times, serif',
			),
			'mono'   => array(
				'label' => __( 'Monospace', 'offline-mode' ),
				'stack' => 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
			),
		);
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * A single setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Mode as saved in the settings.
	 *
	 * @return string
	 */
	public static function saved_mode() {
		$mode = self::get( 'mode' );
		return array_key_exists( $mode, self::modes() ) ? $mode : self::MODE_OFF;
	}

	/**
	 * Mode currently in effect. Coming soon switches itself off once the
	 * launch date passes when automatic launch is enabled.
	 *
	 * @return string
	 */
	public static function mode() {
		$mode = self::saved_mode();
		if ( self::MODE_COMING_SOON === $mode && self::get( 'auto_launch' ) ) {
			$launch = self::launch_timestamp();
			if ( $launch && time() >= $launch ) {
				return self::MODE_OFF;
			}
		}
		return $mode;
	}

	/**
	 * Sanitise input from the settings form.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$clean    = array();

		$mode          = isset( $input['mode'] ) ? sanitize_key( $input['mode'] ) : self::MODE_OFF;
		$clean['mode'] = array_key_exists( $mode, self::modes() ) ? $mode : self::MODE_OFF;

		$clean['page_title'] = isset( $input['page_title'] ) ? sanitize_text_field( $input['page_title'] ) : '';
		$clean['heading']    = isset( $input['heading'] ) ? sanitize_text_field( $input['heading'] ) : '';
		$clean['message']    = isset( $input['message'] ) ? wp_kses_post( $input['message'] ) : '';

		$clean['logo_id']       = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
		$clean['logo_width']    = isset( $input['logo_width'] ) ? min( 1000, max( 20, absint( $input['logo_width'] ) ) ) : $defaults['logo_width'];
		$clean['background_id'] = isset( $input['background_id'] ) ? absint( $input['background_id'] ) : 0;

		foreach ( array( 'background_color', 'text_color', 'accent_color' ) as $key ) {
			$color         = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$clean[ $key ] = $color ? $color : $defaults[ $key ];
		}

		$clean['overlay_opacity'] = isset( $input['overlay_opacity'] ) ? min( 90, absint( $input['overlay_opacity'] ) ) : $defaults['overlay_opacity'];

		$font          = isset( $input['font'] ) ? sanitize_key( $input['font'] ) : '';
		$clean['font'] = array_key_exists( $font, self::fonts() ) ? $font : $defaults['font'];

		$launch = isset( $input['launch_date'] ) ? sanitize_text_field( $input['launch_date'] ) : '';
		// Expect the HTML datetime-local format: YYYY-MM-DDTHH:MM.
		$clean['launch_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $launch ) ? $launch : '';

		$clean['retry_after'] = isset( $input['retry_after'] ) ? min( 168, max( 1, absint( $input['retry_after'] ) ) ) : $defaults['retry_after'];

		foreach ( array( 'show_countdown', 'auto_launch', 'noindex', 'show_login_link' ) as $key ) {
			$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$clean['custom_css'] = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

		return $clean;
	}

	/**
	 * Launch date as a UTC timestamp, or 0 when unset. The stored value is in the site's timezone.
	 *
	 * @return int
	 */
	public static function launch_timestamp() {
		$launch = self::get( 'launch_date' );
		if ( ! $launch ) {
			return 0;
		}
		$date = date_create_immutable_from_format( 'Y-m-d\TH:i', $launch, wp_timezone() );
		return $date ? $date->getTimestamp() : 0;
	}
}
