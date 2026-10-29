<?php
/**
 * Landing page shown to visitors while the site is offline.
 *
 * Copy to your theme as offline-mode/landing.php to override.
 * Available: $view (array) — see Offline_Mode_Frontend::view_data().
 *
 * @package OfflineMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( $view['noindex'] ) : ?>
	<meta name="robots" content="noindex, nofollow">
	<?php endif; ?>
	<title><?php echo esc_html( $view['title'] ); ?></title>
	<?php wp_site_icon(); ?>
	<style>
		:root {
			--om-bg: <?php echo esc_html( $view['background_color'] ); ?>;
			--om-text: <?php echo esc_html( $view['text_color'] ); ?>;
			--om-accent: <?php echo esc_html( $view['accent_color'] ); ?>;
			--om-font: <?php echo wp_strip_all_tags( $view['font_stack'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS context; entities would break quoted font names. ?>;
			--om-overlay: <?php echo esc_html( (string) ( $view['overlay_opacity'] / 100 ) ); ?>;
		}
		*, *::before, *::after { box-sizing: border-box; }
		html, body { margin: 0; min-height: 100%; }
		body {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px 16px;
			background-color: var(--om-bg);
			<?php if ( $view['background_url'] ) : ?>
			background-image: url("<?php echo esc_url_raw( $view['background_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS context. ?>");
			background-size: cover;
			background-position: center;
			<?php endif; ?>
			color: var(--om-text);
			font-family: var(--om-font);
			line-height: 1.6;
			position: relative;
		}
		<?php if ( $view['background_url'] ) : ?>
		body::before {
			content: "";
			position: fixed;
			inset: 0;
			background: #000;
			opacity: var(--om-overlay);
		}
		<?php endif; ?>
		.om-wrap {
			position: relative;
			width: 100%;
			max-width: 640px;
			text-align: center;
		}
		.om-logo { display: block; margin: 0 auto 32px; height: auto; max-width: 100%; }
		.om-heading { margin: 0 0 16px; font-size: clamp(1.75rem, 5vw, 2.75rem); line-height: 1.2; }
		.om-message { font-size: 1.125rem; opacity: .9; }
		.om-message a, .om-login a { color: var(--om-accent); }
		.om-countdown {
			display: flex;
			justify-content: center;
			flex-wrap: wrap;
			gap: 12px;
			margin: 32px 0 0;
			padding: 0;
			list-style: none;
		}
		.om-countdown li {
			min-width: 72px;
			padding: 12px 8px;
			border: 2px solid var(--om-accent);
			border-radius: 8px;
		}
		.om-countdown strong { display: block; font-size: 1.75rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
		.om-countdown span { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; opacity: .8; }
		.om-login { margin-top: 40px; font-size: .875rem; opacity: .8; }
		<?php echo $view['custom_css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tags stripped on save; admin-only input. ?>
	</style>
</head>
<body class="offline-mode offline-mode--<?php echo esc_attr( str_replace( '_', '-', $view['mode'] ) ); ?>">
	<main class="om-wrap">
		<?php if ( $view['logo_url'] ) : ?>
			<img class="om-logo" src="<?php echo esc_url( $view['logo_url'] ); ?>" width="<?php echo esc_attr( $view['logo_width'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<?php endif; ?>

		<?php if ( $view['heading'] ) : ?>
			<h1 class="om-heading"><?php echo esc_html( $view['heading'] ); ?></h1>
		<?php endif; ?>

		<?php if ( $view['message'] ) : ?>
			<div class="om-message"><?php echo wp_kses_post( wpautop( $view['message'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( $view['countdown_to'] ) : ?>
			<ul class="om-countdown" data-launch="<?php echo esc_attr( $view['countdown_to'] ); ?>" aria-live="polite">
				<li><strong data-unit="days">0</strong><span><?php esc_html_e( 'Days', 'offline-mode' ); ?></span></li>
				<li><strong data-unit="hours">0</strong><span><?php esc_html_e( 'Hours', 'offline-mode' ); ?></span></li>
				<li><strong data-unit="minutes">0</strong><span><?php esc_html_e( 'Minutes', 'offline-mode' ); ?></span></li>
				<li><strong data-unit="seconds">0</strong><span><?php esc_html_e( 'Seconds', 'offline-mode' ); ?></span></li>
			</ul>
			<script>
			( function () {
				var el = document.querySelector( '.om-countdown' );
				var launch = parseInt( el.getAttribute( 'data-launch' ), 10 ) * 1000;
				var units = { days: 86400, hours: 3600, minutes: 60, seconds: 1 };
				function tick() {
					var left = Math.max( 0, Math.floor( ( launch - Date.now() ) / 1000 ) );
					Object.keys( units ).forEach( function ( unit ) {
						el.querySelector( '[data-unit="' + unit + '"]' ).textContent = Math.floor( left / units[ unit ] );
						left %= units[ unit ];
					} );
				}
				tick();
				setInterval( tick, 1000 );
			}() );
			</script>
		<?php endif; ?>

		<?php if ( $view['login_url'] ) : ?>
			<p class="om-login"><a href="<?php echo esc_url( $view['login_url'] ); ?>"><?php esc_html_e( 'Log in', 'offline-mode' ); ?></a></p>
		<?php endif; ?>
	</main>
</body>
</html>
