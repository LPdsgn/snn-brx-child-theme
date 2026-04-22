<?php
/**
 * Lucide icon font
 *
 * Mirrors Bricks' Themify Icons implementation:
 * - Self-hosted @font-face in assets/css/libs/lucide-icons.css
 * - Font files in assets/fonts/lucide/
 * - Registered with 'bricks-frontend' as dependency
 * - Enqueued on frontend, Bricks builder and WP admin
 *
 * Provides snn_get_lucide_icon_classes() used by the custom
 * "Lucide Icon" Bricks element (includes/elements/icon-lucide.php).
 *
 * Usage in markup: <i class="icon-<name>"></i>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const SNN_LUCIDE_HANDLE = 'snn-lucide-icons';
const SNN_LUCIDE_REL    = 'assets/css/libs/lucide-icons.min.css';

function snn_lucide_css_path() { return SNN_PATH . SNN_LUCIDE_REL; }
function snn_lucide_css_url()  { return SNN_URL  . SNN_LUCIDE_REL; }

/**
 * Parse the Lucide CSS once, return all `.icon-<name>` class names.
 * Cached in an object-cache / transient keyed on the CSS file mtime.
 *
 * @return string[] e.g. [ 'icon-star', 'icon-arrow-up', ... ]
 */
function snn_get_lucide_icon_classes() {
	static $cache = null;
	if ( $cache !== null ) {
		return $cache;
	}

	$path = snn_lucide_css_path();
	if ( ! file_exists( $path ) ) {
		return $cache = [];
	}

	$version       = (string) filemtime( $path );
	$transient_key = 'snn_lucide_icons_' . md5( $version );
	$cached        = get_transient( $transient_key );

	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cache = $cached;
	}

	$css = file_get_contents( $path );
	if ( $css === false ) {
		return $cache = [];
	}

	preg_match_all( '/\.(icon-[a-z0-9-]+)::?before\s*\{/i', $css, $matches );
	$classes = array_values( array_unique( $matches[1] ?? [] ) );
	sort( $classes );

	set_transient( $transient_key, $classes, DAY_IN_SECONDS );
	return $cache = $classes;
}

/**
 * Frontend: register + enqueue icon-font stylesheet.
 */
add_action( 'wp_enqueue_scripts', function () {
	$path = snn_lucide_css_path();
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_register_style(
		SNN_LUCIDE_HANDLE,
		snn_lucide_css_url(),
		[ 'bricks-frontend' ],
		filemtime( $path )
	);

	// Always enqueue on frontend — remove this line + restore conditional logic
	// if you ever want to scan Bricks data for 'icon-' before loading.
	wp_enqueue_style( SNN_LUCIDE_HANDLE );
}, 20 );

/**
 * Admin + Bricks builder: enqueue so the custom element preview and the
 * <i class="icon-…"> markup render correctly in the editor.
 */
add_action( 'admin_enqueue_scripts', function () {
	$path = snn_lucide_css_path();
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_style(
		SNN_LUCIDE_HANDLE,
		snn_lucide_css_url(),
		[],
		filemtime( $path )
	);
} );
