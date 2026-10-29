<?php
/**
 * Settings screen.
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Settings → Offline Mode page.
 */
class Offline_Mode_Admin {

	const PAGE = 'offline-mode';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( OFFLINE_MODE_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Add the settings page.
	 */
	public function add_page() {
		add_options_page(
			__( 'Offline Mode', 'offline-mode' ),
			__( 'Offline Mode', 'offline-mode' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option and its fields.
	 */
	public function register_settings() {
		register_setting(
			self::PAGE,
			Offline_Mode_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Offline_Mode_Options', 'sanitize' ),
				'default'           => Offline_Mode_Options::defaults(),
			)
		);

		add_settings_section( 'om_status', __( 'Status', 'offline-mode' ), '__return_false', self::PAGE );
		add_settings_section( 'om_content', __( 'Landing page content', 'offline-mode' ), '__return_false', self::PAGE );
		add_settings_section( 'om_design', __( 'Design', 'offline-mode' ), '__return_false', self::PAGE );
		add_settings_section( 'om_launch', __( 'Launch date', 'offline-mode' ), '__return_false', self::PAGE );
		add_settings_section( 'om_advanced', __( 'Advanced', 'offline-mode' ), '__return_false', self::PAGE );

		$fields = array(
			'mode'             => array( 'om_status', __( 'Mode', 'offline-mode' ), 'field_mode' ),
			'page_title'       => array( 'om_content', __( 'Browser title', 'offline-mode' ), 'field_text', __( 'Leave blank to use the heading and site name.', 'offline-mode' ) ),
			'heading'          => array( 'om_content', __( 'Heading', 'offline-mode' ), 'field_text' ),
			'message'          => array( 'om_content', __( 'Message', 'offline-mode' ), 'field_editor' ),
			'logo_id'          => array( 'om_design', __( 'Logo', 'offline-mode' ), 'field_image' ),
			'logo_width'       => array( 'om_design', __( 'Logo width (px)', 'offline-mode' ), 'field_number', '', 20, 1000 ),
			'background_id'    => array( 'om_design', __( 'Background image', 'offline-mode' ), 'field_image' ),
			'overlay_opacity'  => array( 'om_design', __( 'Image overlay darkness (%)', 'offline-mode' ), 'field_number', __( 'Darkens the background image so text stays readable.', 'offline-mode' ), 0, 90 ),
			'background_color' => array( 'om_design', __( 'Background colour', 'offline-mode' ), 'field_color' ),
			'text_color'       => array( 'om_design', __( 'Text colour', 'offline-mode' ), 'field_color' ),
			'accent_color'     => array( 'om_design', __( 'Accent colour', 'offline-mode' ), 'field_color', __( 'Used for links and the countdown.', 'offline-mode' ) ),
			'font'             => array( 'om_design', __( 'Font', 'offline-mode' ), 'field_font' ),
			'launch_date'      => array( 'om_launch', __( 'Launch date and time', 'offline-mode' ), 'field_datetime' ),
			'show_countdown'   => array( 'om_launch', __( 'Countdown', 'offline-mode' ), 'field_checkbox', __( 'Show a countdown to the launch date', 'offline-mode' ) ),
			'auto_launch'      => array( 'om_launch', __( 'Automatic launch', 'offline-mode' ), 'field_checkbox', __( 'Switch coming soon mode off automatically when the launch date passes', 'offline-mode' ) ),
			'retry_after'      => array( 'om_advanced', __( 'Retry-After (hours)', 'offline-mode' ), 'field_number', __( 'Maintenance mode only: tells search engines when to check back.', 'offline-mode' ), 1, 168 ),
			'noindex'          => array( 'om_advanced', __( 'Search engines', 'offline-mode' ), 'field_checkbox', __( 'Ask search engines not to index the coming soon page', 'offline-mode' ) ),
			'show_login_link'  => array( 'om_advanced', __( 'Login link', 'offline-mode' ), 'field_checkbox', __( 'Show a discreet log in link on the landing page', 'offline-mode' ) ),
			'custom_css'       => array( 'om_advanced', __( 'Custom CSS', 'offline-mode' ), 'field_css' ),
		);

		foreach ( $fields as $key => $field ) {
			add_settings_field(
				'om_' . $key,
				$field[1],
				array( $this, $field[2] ),
				self::PAGE,
				$field[0],
				array(
					'key'       => $key,
					'label_for' => 'om_' . $key,
					'help'      => isset( $field[3] ) ? $field[3] : '',
					'min'       => isset( $field[4] ) ? $field[4] : null,
					'max'       => isset( $field[5] ) ? $field[5] : null,
				)
			);
		}
	}

	/**
	 * Load colour picker, media library and the page script on our screen only.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'offline-mode-admin',
			OFFLINE_MODE_URL . 'assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			OFFLINE_MODE_VERSION,
			true
		);
		wp_localize_script(
			'offline-mode-admin',
			'offlineModeAdmin',
			array(
				'chooseImage' => __( 'Choose image', 'offline-mode' ),
				'useImage'    => __( 'Use this image', 'offline-mode' ),
			)
		);
		wp_add_inline_style( 'wp-color-picker', '.om-image-preview img{display:block;max-width:240px;max-height:120px;margin-bottom:8px;background:#f0f0f1;}' );
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ), esc_html__( 'Settings', 'offline-mode' ) )
		);
		return $links;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$preview_url = add_query_arg( Offline_Mode_Frontend::PREVIEW_ARG, '1', home_url( '/' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Offline Mode', 'offline-mode' ); ?></h1>
			<p>
				<?php esc_html_e( 'Visitors see the landing page while a mode is on. Logged-in users bypass it and see the site as normal.', 'offline-mode' ); ?>
				<a href="<?php echo esc_url( $preview_url ); ?>" class="button button-secondary" target="_blank" rel="noopener"><?php esc_html_e( 'Preview landing page', 'offline-mode' ); ?></a>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Input name for a setting.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private function name( $key ) {
		return Offline_Mode_Options::OPTION . '[' . $key . ']';
	}

	/**
	 * Print a field's help text.
	 *
	 * @param array $args Field args.
	 */
	private function help( $args ) {
		if ( $args['help'] ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
		}
	}

	/**
	 * Mode radio buttons.
	 *
	 * @param array $args Field args.
	 */
	public function field_mode( $args ) {
		$value = Offline_Mode_Options::saved_mode();
		echo '<fieldset id="om_mode">';
		foreach ( Offline_Mode_Options::modes() as $mode => $label ) {
			printf(
				'<label><input type="radio" name="%s" value="%s" %s> %s</label><br>',
				esc_attr( $this->name( $args['key'] ) ),
				esc_attr( $mode ),
				checked( $value, $mode, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';
		if ( $value !== Offline_Mode_Options::mode() ) {
			echo '<p class="description">' . esc_html__( 'The launch date has passed, so the site is live.', 'offline-mode' ) . '</p>';
		}
	}

	/**
	 * Single-line text input.
	 *
	 * @param array $args Field args.
	 */
	public function field_text( $args ) {
		printf(
			'<input type="text" class="regular-text" id="%s" name="%s" value="%s">',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			esc_attr( Offline_Mode_Options::get( $args['key'] ) )
		);
		$this->help( $args );
	}

	/**
	 * Number input.
	 *
	 * @param array $args Field args.
	 */
	public function field_number( $args ) {
		printf(
			'<input type="number" class="small-text" id="%s" name="%s" value="%s" min="%d" max="%d">',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			esc_attr( Offline_Mode_Options::get( $args['key'] ) ),
			(int) $args['min'],
			(int) $args['max']
		);
		$this->help( $args );
	}

	/**
	 * Rich text editor.
	 *
	 * @param array $args Field args.
	 */
	public function field_editor( $args ) {
		wp_editor(
			Offline_Mode_Options::get( $args['key'] ),
			$args['label_for'],
			array(
				'textarea_name' => $this->name( $args['key'] ),
				'textarea_rows' => 6,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);
	}

	/**
	 * Media library picker.
	 *
	 * @param array $args Field args.
	 */
	public function field_image( $args ) {
		$id  = (int) Offline_Mode_Options::get( $args['key'] );
		$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		?>
		<div class="om-image-field">
			<div class="om-image-preview"><?php echo $url ? '<img src="' . esc_url( $url ) . '" alt="">' : ''; ?></div>
			<input type="hidden" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $this->name( $args['key'] ) ); ?>" value="<?php echo esc_attr( $id ); ?>">
			<button type="button" class="button om-image-select"><?php esc_html_e( 'Choose image', 'offline-mode' ); ?></button>
			<button type="button" class="button-link om-image-remove"<?php echo $id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'offline-mode' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Colour picker.
	 *
	 * @param array $args Field args.
	 */
	public function field_color( $args ) {
		$defaults = Offline_Mode_Options::defaults();
		printf(
			'<input type="text" class="om-color" id="%s" name="%s" value="%s" data-default-color="%s">',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			esc_attr( Offline_Mode_Options::get( $args['key'] ) ),
			esc_attr( $defaults[ $args['key'] ] )
		);
		$this->help( $args );
	}

	/**
	 * Font select.
	 *
	 * @param array $args Field args.
	 */
	public function field_font( $args ) {
		$value = Offline_Mode_Options::get( $args['key'] );
		printf( '<select id="%s" name="%s">', esc_attr( $args['label_for'] ), esc_attr( $this->name( $args['key'] ) ) );
		foreach ( Offline_Mode_Options::fonts() as $key => $font ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $font['label'] ) );
		}
		echo '</select>';
	}

	/**
	 * Date and time input.
	 *
	 * @param array $args Field args.
	 */
	public function field_datetime( $args ) {
		printf(
			'<input type="datetime-local" id="%s" name="%s" value="%s">',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			esc_attr( Offline_Mode_Options::get( $args['key'] ) )
		);
		printf(
			'<p class="description">%s</p>',
			/* translators: %s: site timezone, e.g. Europe/London. */
			esc_html( sprintf( __( 'In the site timezone (%s).', 'offline-mode' ), wp_timezone_string() ) )
		);
	}

	/**
	 * Checkbox.
	 *
	 * @param array $args Field args.
	 */
	public function field_checkbox( $args ) {
		printf(
			'<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			checked( (int) Offline_Mode_Options::get( $args['key'] ), 1, false ),
			esc_html( $args['help'] )
		);
	}

	/**
	 * Custom CSS textarea.
	 *
	 * @param array $args Field args.
	 */
	public function field_css( $args ) {
		printf(
			'<textarea class="large-text code" rows="8" id="%s" name="%s">%s</textarea>',
			esc_attr( $args['label_for'] ),
			esc_attr( $this->name( $args['key'] ) ),
			esc_textarea( Offline_Mode_Options::get( $args['key'] ) )
		);
	}
}
