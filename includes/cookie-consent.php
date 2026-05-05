<?php
/**
 * Hybrid Cookie Consent
 *
 * Combines SNN cookie banner UX (admin tabs, services repeater, page scanner,
 * GA/Clarity Consent Mode v2, URL-based script blocking) with the
 * vanilla-cookieconsent v3 library (modal rendering, auto-clear cookies,
 * native a11y) and Polylang i18n.
 *
 * Activation:
 *   1. Add the following line in functions.php right after the SNN includes:
 *      require_once SNN_PATH . 'includes/cookie-consent.php';
 *   2. The frontend hooks of the legacy cookie-banner.php are auto-removed.
 *      The legacy admin page remains visible for reference until you delete it.
 *
 * Options storage:
 *   - General/text/style: individual cc_* options (Settings API style).
 *   - Repeater data: cc_services (array), cc_blocked_scripts (array).
 *   - Backward-compat read: when a cc_* option is unset, falls back to the
 *     equivalent key inside snn_cookie_settings_options.
 *
 * @package SNN-BRX child theme
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// CONSTANTS
// =============================================================================

define( 'CC_VERSION', '3.1.0' );
define( 'CC_PLL_CONTEXT', 'Cookie Consent' );
define( 'CC_LIB_JS', 'https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@3.1.0/dist/cookieconsent.umd.js' );
define( 'CC_LIB_CSS', 'https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@3.1.0/dist/cookieconsent.css' );

// Fixed list of categories (per project decision).
function cc_categories_list() {
	return array(
		'necessary' => array(
			'enabled'  => true,
			'readOnly' => true,
		),
		'analytics' => array(
			'enabled'  => false,
			'readOnly' => false,
		),
		'marketing' => array(
			'enabled'  => false,
			'readOnly' => false,
		),
	);
}

// =============================================================================
// SNN BACKWARD COMPATIBILITY HELPERS
// =============================================================================

/**
 * Map of cc_* option keys → SNN array keys (inside snn_cookie_settings_options).
 * Used as fallback when the cc_* option is unset.
 */
function cc_snn_compat_map() {
	return array(
		'cc_enabled'                       => 'snn_cookie_settings_enable_cookie_banner',
		'cc_disable_for_logged_in'         => 'snn_cookie_settings_disable_for_logged_in',
		'cc_disable_scripts_for_logged_in' => 'snn_cookie_settings_disable_scripts_for_logged_in',
		'cc_enable_ga_consent'             => 'snn_cookie_settings_enable_ga_consent',
		'cc_enable_clarity_consent'        => 'snn_cookie_settings_enable_clarity_consent',
		'cc_prefs_title'                   => 'snn_cookie_settings_preferences_title',
		'cc_consent_description'           => 'snn_cookie_settings_banner_description',
		'cc_consent_footer_custom_text'    => 'snn_cookie_settings_additional_description',
		'cc_enable_legal_text'             => 'snn_cookie_settings_enable_legal_text',
		'cc_consent_accept_all_btn'        => 'snn_cookie_settings_accept_button',
		'cc_consent_accept_necessary_btn'  => 'snn_cookie_settings_deny_button',
		'cc_consent_show_prefs_btn'        => 'snn_cookie_settings_preferences_button',
		'cc_iframe_block_text'             => 'snn_cookie_settings_iframe_block_text',
		'cc_custom_css'                    => 'snn_cookie_settings_custom_css',
	);
}

function cc_snn_options() {
	static $opts = null;
	if ( null === $opts ) {
		$opts = get_option( 'snn_cookie_settings_options', array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
	}
	return $opts;
}

/**
 * Read a cc_* option, falling back to the SNN equivalent when unset.
 */
function cc_get_opt( $key, $default = '' ) {
	$bool_keys = array(
		'cc_enabled',
		'cc_disable_for_logged_in',
		'cc_disable_scripts_for_logged_in',
		'cc_enable_ga_consent',
		'cc_enable_clarity_consent',
		'cc_enable_legal_text',
		'cc_auto_clear_cookies',
		'cc_hide_from_bots',
		'cc_disable_page_interaction',
	);

	$val = get_option( $key, null );
	if ( null !== $val && '' !== $val ) {
		if ( in_array( $key, $bool_keys, true ) ) {
			return ( '1' === (string) $val || 'yes' === $val || 1 === $val || true === $val ) ? 1 : 0;
		}
		return $val;
	}

	$map = cc_snn_compat_map();
	if ( isset( $map[ $key ] ) ) {
		$snn     = cc_snn_options();
		$snn_key = $map[ $key ];
		if ( isset( $snn[ $snn_key ] ) && '' !== $snn[ $snn_key ] ) {
			if ( in_array( $key, $bool_keys, true ) ) {
				return 'yes' === $snn[ $snn_key ] ? 1 : 0;
			}
			return $snn[ $snn_key ];
		}
	}

	return $default;
}

/**
 * Read services list (cc_services), falling back to SNN repeater with auto-categorization.
 */
function cc_get_services() {
	$services = get_option( 'cc_services', null );
	if ( is_array( $services ) ) {
		return $services;
	}

	$snn          = cc_snn_options();
	$snn_services = isset( $snn['snn_cookie_settings_services'] ) ? $snn['snn_cookie_settings_services'] : array();
	if ( ! is_array( $snn_services ) ) {
		return array();
	}

	$out = array();
	foreach ( $snn_services as $s ) {
		if ( empty( $s['name'] ) && empty( $s['script'] ) ) {
			continue;
		}
		$mandatory = isset( $s['mandatory'] ) ? $s['mandatory'] : 'no';
		$out[]     = array(
			'name'        => isset( $s['name'] ) ? $s['name'] : '',
			'description' => isset( $s['description'] ) ? $s['description'] : '',
			'script'      => isset( $s['script'] ) ? $s['script'] : '',
			'position'    => isset( $s['position'] ) ? $s['position'] : 'body_bottom',
			'mandatory'   => $mandatory,
			'category'    => 'yes' === $mandatory ? 'necessary' : 'analytics',
		);
	}
	return $out;
}

/**
 * Read blocked scripts (cc_blocked_scripts), falling back to SNN with auto-categorization.
 */
function cc_get_blocked_scripts() {
	$blocked = get_option( 'cc_blocked_scripts', null );
	if ( is_array( $blocked ) ) {
		return $blocked;
	}

	$snn         = cc_snn_options();
	$snn_blocked = isset( $snn['snn_cookie_settings_blocked_scripts'] ) ? $snn['snn_cookie_settings_blocked_scripts'] : array();
	if ( ! is_array( $snn_blocked ) ) {
		return array();
	}

	$out = array();
	foreach ( $snn_blocked as $b ) {
		if ( is_string( $b ) && '' !== $b ) {
			$out[] = array(
				'url'         => $b,
				'name'        => '',
				'description' => '',
				'category'    => 'analytics',
			);
		} elseif ( is_array( $b ) && ! empty( $b['url'] ) ) {
			$out[] = array(
				'url'         => $b['url'],
				'name'        => isset( $b['name'] ) ? $b['name'] : '',
				'description' => isset( $b['description'] ) ? $b['description'] : '',
				'category'    => isset( $b['category'] ) ? $b['category'] : 'analytics',
			);
		}
	}
	return $out;
}

function cc_is_enabled() {
	return 1 === (int) cc_get_opt( 'cc_enabled', 0 );
}

// =============================================================================
// POLYLANG HELPERS
// =============================================================================

function cc_pll_register( $name, $value, $multiline = false ) {
	if ( '' === $value || null === $value ) {
		return;
	}
	if ( function_exists( 'pll_register_string' ) ) {
		pll_register_string( $name, $value, CC_PLL_CONTEXT, $multiline );
	}
}

function cc_pll( $value ) {
	if ( '' === $value || null === $value ) {
		return $value;
	}
	if ( function_exists( 'pll__' ) ) {
		return pll__( $value );
	}
	return $value;
}

function cc_pll_permalink( $post_id ) {
	if ( empty( $post_id ) ) {
		return '';
	}
	if ( function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( (int) $post_id );
		if ( $tr ) {
			$post_id = $tr;
		}
	}
	$url = get_permalink( (int) $post_id );
	return $url ? $url : '';
}

function cc_pll_current_language() {
	if ( function_exists( 'pll_current_language' ) ) {
		$code = pll_current_language( 'slug' );
		if ( $code ) {
			return $code;
		}
	}
	return cc_get_opt( 'cc_language_default', 'it' );
}

function cc_pll_languages() {
	if ( function_exists( 'pll_languages_list' ) ) {
		$list = pll_languages_list( array( 'fields' => 'slug' ) );
		if ( is_array( $list ) && ! empty( $list ) ) {
			return $list;
		}
	}
	return array( cc_get_opt( 'cc_language_default', 'it' ) );
}

// =============================================================================
// DISABLE LEGACY SNN FRONTEND HOOKS
// =============================================================================

add_action( 'init', function() {
	if ( function_exists( 'snn_output_cookie_banner' ) ) {
		remove_action( 'wp_footer', 'snn_output_cookie_banner' );
	}
	if ( function_exists( 'snn_output_script_blocker' ) ) {
		remove_action( 'wp_head', 'snn_output_script_blocker', 2 );
	}
	if ( function_exists( 'snn_output_service_scripts' ) ) {
		remove_action( 'wp_footer', 'snn_output_service_scripts', 99 );
	}
	if ( function_exists( 'snn_output_banner_js' ) ) {
		remove_action( 'wp_footer', 'snn_output_banner_js', 100 );
	}
	if ( function_exists( 'snn_output_custom_css' ) ) {
		remove_action( 'wp_footer', 'snn_output_custom_css', 999 );
	}
}, 100 );

// =============================================================================
// ADMIN MENU
// =============================================================================

function cc_admin_menu() {
	add_submenu_page(
		'snn-settings',
		__( 'Cookie Consent (Hybrid)', 'snn' ),
		__( 'Cookie Consent', 'snn' ),
		'manage_options',
		'cc-cookie-consent',
		'cc_admin_page_render'
	);
}
add_action( 'admin_menu', 'cc_admin_menu', 11 );

// =============================================================================
// SETTINGS REGISTRATION
// =============================================================================

function cc_settings_init() {
	$keys = array(
		// General
		'cc_enabled',
		'cc_disable_for_logged_in',
		'cc_disable_scripts_for_logged_in',
		'cc_enable_ga_consent',
		'cc_enable_clarity_consent',
		'cc_revision',
		'cc_hide_from_bots',
		'cc_disable_page_interaction',
		'cc_auto_clear_cookies',
		'cc_language_default',
		'cc_language_auto_detect',
		'cc_cookie_name',
		'cc_cookie_expires',
		'cc_cookie_same_site',
		// Consent modal
		'cc_consent_title',
		'cc_consent_description',
		'cc_consent_accept_all_btn',
		'cc_consent_accept_necessary_btn',
		'cc_consent_show_prefs_btn',
		'cc_consent_footer_privacy_page',
		'cc_consent_footer_cookie_page',
		'cc_consent_footer_custom_text',
		'cc_enable_legal_text',
		// Preferences modal
		'cc_prefs_title',
		'cc_prefs_accept_all_btn',
		'cc_prefs_accept_necessary_btn',
		'cc_prefs_save_btn',
		'cc_prefs_close_label',
		'cc_prefs_service_counter_label',
		'cc_prefs_intro_description',
		// Category labels & descriptions
		'cc_cat_necessary_title',
		'cc_cat_necessary_description',
		'cc_cat_analytics_title',
		'cc_cat_analytics_description',
		'cc_cat_analytics_autoclear',
		'cc_cat_marketing_title',
		'cc_cat_marketing_description',
		'cc_cat_marketing_autoclear',
		// GUI options
		'cc_consent_layout',
		'cc_consent_position_v',
		'cc_consent_position_h',
		'cc_consent_flip_buttons',
		'cc_consent_equal_weight_buttons',
		'cc_prefs_layout',
		'cc_prefs_position',
		'cc_prefs_flip_buttons',
		'cc_prefs_equal_weight_buttons',
		// Page scanner
		'cc_iframe_block_text',
		// Styles
		'cc_color_bg',
		'cc_color_text',
		'cc_color_btn_primary_bg',
		'cc_color_btn_primary_text',
		'cc_color_btn_secondary_bg',
		'cc_color_btn_secondary_text',
		'cc_color_overlay',
		'cc_custom_css',
	);

	foreach ( $keys as $k ) {
		register_setting( 'cc_settings', $k );
	}

	register_setting( 'cc_settings', 'cc_services' );
	register_setting( 'cc_settings', 'cc_blocked_scripts' );
}
add_action( 'admin_init', 'cc_settings_init' );

// =============================================================================
// AJAX: PAGE SCANNER
// =============================================================================

function cc_scan_page_ajax() {
	check_ajax_referer( 'cc_scan_page', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permission denied', 'snn' ) );
	}

	$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
	if ( empty( $page_url ) ) {
		wp_send_json_error( __( 'No page URL provided', 'snn' ) );
	}

	$response = wp_remote_get( $page_url, array(
		'timeout'   => 30,
		'sslverify' => false,
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( __( 'Failed to fetch page: ', 'snn' ) . $response->get_error_message() );
	}

	$html = wp_remote_retrieve_body( $response );
	if ( empty( $html ) ) {
		wp_send_json_error( __( 'Page content is empty', 'snn' ) );
	}

	$scripts = array();
	$iframes = array();

	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( $html );
	libxml_clear_errors();

	$resolve = function( $src ) use ( $page_url ) {
		if ( strpos( $src, '//' ) === 0 ) {
			return 'https:' . $src;
		}
		if ( strpos( $src, '/' ) === 0 ) {
			$p = wp_parse_url( $page_url );
			return $p['scheme'] . '://' . $p['host'] . $src;
		}
		if ( strpos( $src, 'http' ) !== 0 ) {
			return null;
		}
		return $src;
	};

	foreach ( $dom->getElementsByTagName( 'script' ) as $tag ) {
		$src = $tag->getAttribute( 'src' );
		if ( ! empty( $src ) ) {
			$resolved = $resolve( $src );
			if ( $resolved && ! in_array( $resolved, $scripts, true ) ) {
				$scripts[] = $resolved;
			}
		}
	}

	foreach ( $dom->getElementsByTagName( 'iframe' ) as $tag ) {
		$src = $tag->getAttribute( 'src' );
		if ( ! empty( $src ) ) {
			$resolved = $resolve( $src );
			if ( $resolved && ! in_array( $resolved, $iframes, true ) ) {
				$iframes[] = $resolved;
			}
		}
	}

	$blocked = cc_get_blocked_scripts();

	wp_send_json_success( array(
		'scripts'         => $scripts,
		'iframes'         => $iframes,
		'blocked_scripts' => $blocked,
	) );
}
add_action( 'wp_ajax_cc_scan_page_scripts', 'cc_scan_page_ajax' );

// =============================================================================
// ADMIN PAGE RENDER
// =============================================================================

function cc_admin_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['cc_options_nonce'] ) && wp_verify_nonce( $_POST['cc_options_nonce'], 'cc_save_options' ) ) {
		cc_admin_save_options();
		echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Settings saved.', 'snn' ) . '</p></div>';
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	$pll_link = '';
	if ( function_exists( 'pll_register_string' ) ) {
		$pll_link = admin_url( 'admin.php?page=mlang_strings&group=' . rawurlencode( CC_PLL_CONTEXT ) );
	}
	?>
	<div class="wrap cc-wrap">
		<h1><?php esc_html_e( 'Cookie Consent', 'snn' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Configurazione ibrida basata su vanilla-cookieconsent v3. Le opzioni esistenti del vecchio plugin SNN vengono lette automaticamente come fallback.', 'snn' ); ?>
			<a href="https://cookieconsent.orestbida.com/reference/configuration-reference.html" target="_blank"><?php esc_html_e( 'Documentazione libreria', 'snn' ); ?></a>
		</p>

		<?php if ( $pll_link ) : ?>
			<p><a href="<?php echo esc_url( $pll_link ); ?>" class="button"><?php esc_html_e( 'Traduci con Polylang', 'snn' ); ?></a></p>
		<?php else : ?>
			<p class="description" style="color:#b32d2e;"><?php esc_html_e( 'Polylang non risulta attivo: le stringhe non saranno traducibili.', 'snn' ); ?></p>
		<?php endif; ?>

		<style>
			.cc-tab-content { display:none; padding:20px; background:#fff; border:1px solid #c3c4c7; border-top:none; }
			.cc-tab-content.active { display:block; }
			.cc-services-repeater .cc-service-item,
			.cc-blocked-repeater .cc-blocked-item { margin-bottom:15px; padding:12px; border:1px solid #c3c4c7; max-width:760px; position:relative; background:#fff; }
			.cc-service-actions { position:absolute; top:8px; right:8px; display:flex; gap:6px; }
			.cc-move-btn { background:transparent; border:none; padding:2px 4px; line-height:1; cursor:pointer; color:#555; font-size:14px; }
			.cc-move-btn:hover { color:#000; }
			.cc-service-item label,
			.cc-blocked-item label { display:block; margin-bottom:6px; font-weight:600; }
			.cc-service-item input[type="text"],
			.cc-service-item textarea,
			.cc-blocked-item input[type="text"],
			.cc-blocked-item textarea { width:100%; }
			.cc-service-item .cc-radio-group label,
			.cc-service-item .cc-checkbox-line label { display:inline-block; font-weight:400; margin-right:12px; }
			.cc-input { width:300px; max-width:100%; }
			.cc-input-wide { width:100%; max-width:600px; }
			.cc-textarea { width:100%; max-width:600px; font-family:monospace; }
		</style>

		<h2 class="nav-tab-wrapper cc-tabs">
			<a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e( 'Generale', 'snn' ); ?></a>
			<a href="#consent" class="nav-tab" data-tab="consent"><?php esc_html_e( 'Modale Consenso', 'snn' ); ?></a>
			<a href="#prefs" class="nav-tab" data-tab="prefs"><?php esc_html_e( 'Modale Preferenze', 'snn' ); ?></a>
			<a href="#services" class="nav-tab" data-tab="services"><?php esc_html_e( 'Categorie & Servizi', 'snn' ); ?></a>
			<a href="#scanner" class="nav-tab" data-tab="scanner"><?php esc_html_e( 'Page Scanner', 'snn' ); ?></a>
			<a href="#styles" class="nav-tab" data-tab="styles"><?php esc_html_e( 'Stile', 'snn' ); ?></a>
		</h2>

		<form method="post">
			<?php wp_nonce_field( 'cc_save_options', 'cc_options_nonce' ); ?>

			<div id="cc-tab-general" class="cc-tab-content active" data-tab="general"><?php cc_render_tab_general(); ?></div>
			<div id="cc-tab-consent" class="cc-tab-content" data-tab="consent"><?php cc_render_tab_consent(); ?></div>
			<div id="cc-tab-prefs" class="cc-tab-content" data-tab="prefs"><?php cc_render_tab_prefs(); ?></div>
			<div id="cc-tab-services" class="cc-tab-content" data-tab="services"><?php cc_render_tab_services(); ?></div>
			<div id="cc-tab-scanner" class="cc-tab-content" data-tab="scanner"><?php cc_render_tab_scanner(); ?></div>
			<div id="cc-tab-styles" class="cc-tab-content" data-tab="styles"><?php cc_render_tab_styles(); ?></div>

			<?php submit_button( __( 'Salva Impostazioni', 'snn' ) ); ?>
		</form>
	</div>

	<script>
	(function($){
		$(document).ready(function(){
			// Color pickers
			$('.cc-color-picker').wpColorPicker();

			// Tab switching
			$('.cc-tabs .nav-tab').on('click', function(e){
				e.preventDefault();
				var tab = $(this).data('tab');
				$('.cc-tabs .nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				$('.cc-tab-content').removeClass('active');
				$('#cc-tab-' + tab).addClass('active');
				if (window.history && window.history.replaceState) {
					window.history.replaceState(null, '', '#' + tab);
				}
			});

			// Restore tab from hash
			if (window.location.hash) {
				var hash = window.location.hash.replace('#','');
				$('.cc-tabs .nav-tab[data-tab="' + hash + '"]').trigger('click');
			}
		});
	})(jQuery);
	</script>
	<?php
}

// =============================================================================
// ADMIN: SAVE
// =============================================================================

function cc_admin_save_options() {
	$bool_keys = array(
		'cc_enabled',
		'cc_disable_for_logged_in',
		'cc_disable_scripts_for_logged_in',
		'cc_enable_ga_consent',
		'cc_enable_clarity_consent',
		'cc_hide_from_bots',
		'cc_disable_page_interaction',
		'cc_auto_clear_cookies',
		'cc_enable_legal_text',
		'cc_consent_flip_buttons',
		'cc_consent_equal_weight_buttons',
		'cc_prefs_flip_buttons',
		'cc_prefs_equal_weight_buttons',
	);

	$text_keys = array(
		'cc_revision',
		'cc_language_default',
		'cc_language_auto_detect',
		'cc_cookie_name',
		'cc_cookie_expires',
		'cc_cookie_same_site',
		'cc_consent_title',
		'cc_consent_accept_all_btn',
		'cc_consent_accept_necessary_btn',
		'cc_consent_show_prefs_btn',
		'cc_consent_footer_privacy_page',
		'cc_consent_footer_cookie_page',
		'cc_prefs_title',
		'cc_prefs_accept_all_btn',
		'cc_prefs_accept_necessary_btn',
		'cc_prefs_save_btn',
		'cc_prefs_close_label',
		'cc_prefs_service_counter_label',
		'cc_cat_necessary_title',
		'cc_cat_analytics_title',
		'cc_cat_marketing_title',
		'cc_consent_layout',
		'cc_consent_position_v',
		'cc_consent_position_h',
		'cc_prefs_layout',
		'cc_prefs_position',
		'cc_iframe_block_text',
		'cc_color_bg',
		'cc_color_text',
		'cc_color_btn_primary_bg',
		'cc_color_btn_primary_text',
		'cc_color_btn_secondary_bg',
		'cc_color_btn_secondary_text',
		'cc_color_overlay',
	);

	$rich_keys = array(
		'cc_consent_description',
		'cc_consent_footer_custom_text',
		'cc_prefs_intro_description',
		'cc_cat_necessary_description',
		'cc_cat_analytics_description',
		'cc_cat_marketing_description',
		'cc_cat_analytics_autoclear',
		'cc_cat_marketing_autoclear',
		'cc_custom_css',
	);

	foreach ( $bool_keys as $k ) {
		update_option( $k, isset( $_POST[ $k ] ) ? 1 : 0 );
	}

	foreach ( $text_keys as $k ) {
		$v = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
		update_option( $k, $v );
	}

	foreach ( $rich_keys as $k ) {
		$v = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
		update_option( $k, $v );
	}

	// Services repeater
	$services = array();
	if ( isset( $_POST['cc_services'] ) && is_array( $_POST['cc_services'] ) ) {
		foreach ( array_values( $_POST['cc_services'] ) as $s ) {
			if ( empty( trim( $s['name'] ?? '' ) ) && empty( trim( $s['script'] ?? '' ) ) ) {
				continue;
			}
			$services[] = array(
				'name'        => sanitize_text_field( wp_unslash( $s['name'] ?? '' ) ),
				'description' => wp_unslash( $s['description'] ?? '' ),
				'script'      => wp_unslash( $s['script'] ?? '' ),
				'position'    => in_array( $s['position'] ?? '', array( 'head', 'body_top', 'body_bottom' ), true ) ? $s['position'] : 'body_bottom',
				'mandatory'   => isset( $s['mandatory'] ) ? 'yes' : 'no',
				'category'    => in_array( $s['category'] ?? '', array( 'necessary', 'analytics', 'marketing' ), true ) ? $s['category'] : 'analytics',
			);
		}
	}
	update_option( 'cc_services', $services );

	// Blocked scripts repeater
	$blocked = array();
	if ( isset( $_POST['cc_blocked_scripts'] ) && is_array( $_POST['cc_blocked_scripts'] ) ) {
		foreach ( array_values( $_POST['cc_blocked_scripts'] ) as $b ) {
			if ( empty( $b['url'] ) ) {
				continue;
			}
			$blocked[] = array(
				'url'         => esc_url_raw( wp_unslash( $b['url'] ) ),
				'name'        => sanitize_text_field( wp_unslash( $b['name'] ?? '' ) ),
				'description' => sanitize_text_field( wp_unslash( $b['description'] ?? '' ) ),
				'category'    => in_array( $b['category'] ?? '', array( 'necessary', 'analytics', 'marketing' ), true ) ? $b['category'] : 'analytics',
			);
		}
	}
	update_option( 'cc_blocked_scripts', $blocked );
}

// =============================================================================
// ADMIN TABS
// =============================================================================

function cc_render_tab_general() {
	$mode = cc_get_opt( 'cc_language_auto_detect', 'browser' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Abilita Cookie Consent', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_enabled" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_enabled', 0 ) ); ?>>
					<?php esc_html_e( 'Attiva il banner sul frontend.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Disabilita per utenti loggati', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_disable_for_logged_in" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_disable_for_logged_in', 0 ) ); ?>>
					<?php esc_html_e( 'Non mostrare il banner agli utenti loggati.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Disabilita scripts per utenti loggati', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_disable_scripts_for_logged_in" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_disable_scripts_for_logged_in', 0 ) ); ?>>
					<?php esc_html_e( 'Non caricare gli script di tracking se l\'utente è loggato.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Google Analytics Consent Mode v2', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_enable_ga_consent" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_enable_ga_consent', 0 ) ); ?>>
					<?php esc_html_e( 'Aggiorna automaticamente lo stato di consenso GA al cambio preferenze.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Microsoft Clarity Consent v2', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_enable_clarity_consent" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_enable_clarity_consent', 0 ) ); ?>>
					<?php esc_html_e( 'Aggiorna automaticamente lo stato di consenso Clarity al cambio preferenze.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Auto-clear cookies', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_auto_clear_cookies" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_auto_clear_cookies', 1 ) ); ?>>
					<?php esc_html_e( 'Elimina i cookie corrispondenti ai pattern definiti per categoria quando l\'utente revoca il consenso.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Nascondi ai bot', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_hide_from_bots" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_hide_from_bots', 1 ) ); ?>>
					<?php esc_html_e( 'Non mostrare la modale a crawler/bot.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Blocca interazione pagina', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_disable_page_interaction" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_disable_page_interaction', 0 ) ); ?>>
					<?php esc_html_e( 'Disabilita scroll e click sulla pagina finché l\'utente non ha espresso il consenso.', 'snn' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Revisione consenso', 'snn' ); ?></th>
			<td>
				<input type="number" name="cc_revision" value="<?php echo esc_attr( cc_get_opt( 'cc_revision', 0 ) ); ?>" min="0" class="cc-input">
				<p class="description"><?php esc_html_e( 'Incrementa per richiedere nuovamente il consenso dopo modifiche alla privacy policy (0 = disabilitato).', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Lingua predefinita', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_language_default" value="<?php echo esc_attr( cc_get_opt( 'cc_language_default', 'it' ) ); ?>" class="cc-input">
				<p class="description"><?php esc_html_e( 'Codice lingua (it, en, fr...). Usato come fallback se Polylang non è attivo.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Rilevamento automatico lingua', 'snn' ); ?></th>
			<td>
				<select name="cc_language_auto_detect">
					<option value="" <?php selected( $mode, '' ); ?>><?php esc_html_e( 'Disattivato', 'snn' ); ?></option>
					<option value="browser" <?php selected( $mode, 'browser' ); ?>><?php esc_html_e( 'Browser', 'snn' ); ?></option>
					<option value="document" <?php selected( $mode, 'document' ); ?>><?php esc_html_e( 'Document (attributo lang)', 'snn' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Nome cookie', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_cookie_name" value="<?php echo esc_attr( cc_get_opt( 'cc_cookie_name', 'cc_cookie' ) ); ?>" class="cc-input">
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Scadenza cookie (giorni)', 'snn' ); ?></th>
			<td>
				<input type="number" name="cc_cookie_expires" value="<?php echo esc_attr( cc_get_opt( 'cc_cookie_expires', 182 ) ); ?>" min="1" class="cc-input">
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'SameSite', 'snn' ); ?></th>
			<td>
				<?php $ss = cc_get_opt( 'cc_cookie_same_site', 'Lax' ); ?>
				<select name="cc_cookie_same_site">
					<option value="Lax" <?php selected( $ss, 'Lax' ); ?>>Lax</option>
					<option value="Strict" <?php selected( $ss, 'Strict' ); ?>>Strict</option>
					<option value="None" <?php selected( $ss, 'None' ); ?>>None</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Cambio preferenze (GDPR)', 'snn' ); ?></th>
			<td>
				<div style="background:#f0f8ff;border-left:4px solid #2271b1;padding:12px 15px;">
					<p style="margin:0 0 10px;"><strong><?php esc_html_e( 'Permetti agli utenti di cambiare le preferenze cookie in qualsiasi momento (richiesto dal GDPR).', 'snn' ); ?></strong></p>
					<p style="margin:0 0 10px;"><?php esc_html_e( 'Aggiungi la classe', 'snn' ); ?> <code>.cc-cookie-change</code> <?php esc_html_e( '(o', 'snn' ); ?> <code>.snn-cookie-change</code> <?php esc_html_e( 'per retro-compatibilità) a qualsiasi pulsante o link. Al click verrà riaperta la modale preferenze.', 'snn' ); ?></p>
					<code style="display:block;background:#fff;padding:8px;">&lt;a href="#" class="cc-cookie-change"&gt;<?php esc_html_e( 'Cambia preferenze cookie', 'snn' ); ?>&lt;/a&gt;</code>
				</div>
			</td>
		</tr>
	</table>
	<?php
}

function cc_render_tab_consent() {
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Titolo', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_consent_title" value="<?php echo esc_attr( cc_get_opt( 'cc_consent_title', __( 'Gestione del consenso', 'snn' ) ) ); ?>" class="cc-input-wide">
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Descrizione', 'snn' ); ?></th>
			<td>
				<?php
				wp_editor(
					cc_get_opt( 'cc_consent_description', __( 'Questo sito usa cookie per migliorare l\'esperienza di navigazione.', 'snn' ) ),
					'cc_consent_description',
					array(
						'textarea_name' => 'cc_consent_description',
						'textarea_rows' => 4,
					)
				);
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Accetta tutti"', 'snn' ); ?></th>
			<td><input type="text" name="cc_consent_accept_all_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_consent_accept_all_btn', __( 'Accetta tutti', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Accetta necessari"', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_consent_accept_necessary_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_consent_accept_necessary_btn', __( 'Solo necessari', 'snn' ) ) ); ?>" class="cc-input">
				<p class="description"><?php esc_html_e( 'Lascia vuoto per nasconderlo.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Preferenze"', 'snn' ); ?></th>
			<td><input type="text" name="cc_consent_show_prefs_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_consent_show_prefs_btn', __( 'Gestisci preferenze', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Privacy Policy (pagina)', 'snn' ); ?></th>
			<td>
				<?php
				wp_dropdown_pages( array(
					'name'              => 'cc_consent_footer_privacy_page',
					'selected'          => (int) cc_get_opt( 'cc_consent_footer_privacy_page', 0 ),
					'show_option_none'  => '— ' . __( 'Nessuna', 'snn' ) . ' —',
					'option_none_value' => '',
				) );
				?>
				<p class="description"><?php esc_html_e( 'Polylang: il link viene tradotto automaticamente in base alla lingua corrente.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Cookie Policy (pagina)', 'snn' ); ?></th>
			<td>
				<?php
				wp_dropdown_pages( array(
					'name'              => 'cc_consent_footer_cookie_page',
					'selected'          => (int) cc_get_opt( 'cc_consent_footer_cookie_page', 0 ),
					'show_option_none'  => '— ' . __( 'Nessuna', 'snn' ) . ' —',
					'option_none_value' => '',
				) );
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Testo footer personalizzato', 'snn' ); ?></th>
			<td>
				<?php
				wp_editor(
					cc_get_opt( 'cc_consent_footer_custom_text', '' ),
					'cc_consent_footer_custom_text',
					array(
						'textarea_name' => 'cc_consent_footer_custom_text',
						'textarea_rows' => 3,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'HTML permesso. Mostrato dopo i link Privacy/Cookie nel footer della modale.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Layout modale consenso', 'snn' ); ?></th>
			<td>
				<?php $l = cc_get_opt( 'cc_consent_layout', 'cloud' ); ?>
				<select name="cc_consent_layout">
					<option value="cloud" <?php selected( $l, 'cloud' ); ?>>cloud</option>
					<option value="cloud inline" <?php selected( $l, 'cloud inline' ); ?>>cloud inline</option>
					<option value="box" <?php selected( $l, 'box' ); ?>>box</option>
					<option value="box inline" <?php selected( $l, 'box inline' ); ?>>box inline</option>
					<option value="bar" <?php selected( $l, 'bar' ); ?>>bar</option>
					<option value="bar inline" <?php selected( $l, 'bar inline' ); ?>>bar inline</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Posizione verticale', 'snn' ); ?></th>
			<td>
				<?php $pv = cc_get_opt( 'cc_consent_position_v', 'bottom' ); ?>
				<select name="cc_consent_position_v">
					<option value="top" <?php selected( $pv, 'top' ); ?>>top</option>
					<option value="middle" <?php selected( $pv, 'middle' ); ?>>middle</option>
					<option value="bottom" <?php selected( $pv, 'bottom' ); ?>>bottom</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Posizione orizzontale', 'snn' ); ?></th>
			<td>
				<?php $ph = cc_get_opt( 'cc_consent_position_h', 'center' ); ?>
				<select name="cc_consent_position_h">
					<option value="left" <?php selected( $ph, 'left' ); ?>>left</option>
					<option value="center" <?php selected( $ph, 'center' ); ?>>center</option>
					<option value="right" <?php selected( $ph, 'right' ); ?>>right</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Inverti pulsanti', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_consent_flip_buttons" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_consent_flip_buttons', 0 ) ); ?>></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsanti larghezza uguale', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_consent_equal_weight_buttons" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_consent_equal_weight_buttons', 1 ) ); ?>></label>
			</td>
		</tr>
	</table>
	<?php
}

function cc_render_tab_prefs() {
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Titolo', 'snn' ); ?></th>
			<td><input type="text" name="cc_prefs_title" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_title', __( 'Le tue preferenze sulla privacy', 'snn' ) ) ); ?>" class="cc-input-wide"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Descrizione introduttiva', 'snn' ); ?></th>
			<td>
				<?php
				wp_editor(
					cc_get_opt( 'cc_prefs_intro_description', __( 'In questo pannello puoi esprimere le tue preferenze relative al trattamento dei dati personali tramite cookie.', 'snn' ) ),
					'cc_prefs_intro_description',
					array(
						'textarea_name' => 'cc_prefs_intro_description',
						'textarea_rows' => 3,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Mostrato come sezione introduttiva (senza categoria) all\'apertura della modale preferenze.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Accetta tutti"', 'snn' ); ?></th>
			<td><input type="text" name="cc_prefs_accept_all_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_accept_all_btn', __( 'Accetta tutti', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Solo necessari"', 'snn' ); ?></th>
			<td><input type="text" name="cc_prefs_accept_necessary_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_accept_necessary_btn', __( 'Solo necessari', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsante "Salva selezione"', 'snn' ); ?></th>
			<td><input type="text" name="cc_prefs_save_btn" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_save_btn', __( 'Salva preferenze', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Label chiusura modale', 'snn' ); ?></th>
			<td><input type="text" name="cc_prefs_close_label" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_close_label', __( 'Chiudi modale', 'snn' ) ) ); ?>" class="cc-input"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Label contatore servizi', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_prefs_service_counter_label" value="<?php echo esc_attr( cc_get_opt( 'cc_prefs_service_counter_label', __( 'Servizio|Servizi', 'snn' ) ) ); ?>" class="cc-input">
				<p class="description"><?php esc_html_e( 'Formato: singolare|plurale.', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Layout modale preferenze', 'snn' ); ?></th>
			<td>
				<?php $l = cc_get_opt( 'cc_prefs_layout', 'box' ); ?>
				<select name="cc_prefs_layout">
					<option value="box" <?php selected( $l, 'box' ); ?>>box</option>
					<option value="bar" <?php selected( $l, 'bar' ); ?>>bar</option>
					<option value="bar wide" <?php selected( $l, 'bar wide' ); ?>>bar wide</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Posizione modale preferenze', 'snn' ); ?></th>
			<td>
				<?php $p = cc_get_opt( 'cc_prefs_position', 'right' ); ?>
				<select name="cc_prefs_position">
					<option value="left" <?php selected( $p, 'left' ); ?>>left</option>
					<option value="right" <?php selected( $p, 'right' ); ?>>right</option>
				</select>
				<p class="description"><?php esc_html_e( 'Applicato solo ai layout "bar".', 'snn' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Inverti pulsanti', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_prefs_flip_buttons" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_prefs_flip_buttons', 0 ) ); ?>></label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pulsanti larghezza uguale', 'snn' ); ?></th>
			<td>
				<label><input type="checkbox" name="cc_prefs_equal_weight_buttons" value="1" <?php checked( 1, (int) cc_get_opt( 'cc_prefs_equal_weight_buttons', 1 ) ); ?>></label>
			</td>
		</tr>
	</table>
	<?php
}

function cc_render_tab_services() {
	$services = cc_get_services();
	?>
	<h2><?php esc_html_e( 'Categorie cookie', 'snn' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Le tre categorie standard sono predefinite. Puoi personalizzarne titolo e descrizione (traducibili con Polylang) e definire i pattern di auto-clear cookie.', 'snn' ); ?></p>
	<table class="form-table" role="presentation">
		<?php
		$cats = array(
			'necessary' => array( 'title_default' => __( 'Strettamente necessari', 'snn' ), 'desc_default' => __( 'Questi cookie sono essenziali per il corretto funzionamento del sito.', 'snn' ), 'autoclear' => false ),
			'analytics' => array( 'title_default' => __( 'Analytics', 'snn' ), 'desc_default' => __( 'Cookie utilizzati per raccogliere statistiche aggregate sull\'uso del sito.', 'snn' ), 'autoclear' => true ),
			'marketing' => array( 'title_default' => __( 'Marketing', 'snn' ), 'desc_default' => __( 'Cookie usati per profilare l\'utente e mostrare pubblicità mirata.', 'snn' ), 'autoclear' => true ),
		);
		foreach ( $cats as $key => $info ) :
			$title    = cc_get_opt( "cc_cat_{$key}_title", $info['title_default'] );
			$desc     = cc_get_opt( "cc_cat_{$key}_description", $info['desc_default'] );
			$autoclr  = cc_get_opt( "cc_cat_{$key}_autoclear", '' );
			?>
			<tr>
				<th><strong><?php echo esc_html( ucfirst( $key ) ); ?></strong></th>
				<td>
					<label style="font-weight:600;"><?php esc_html_e( 'Titolo', 'snn' ); ?></label>
					<input type="text" name="cc_cat_<?php echo esc_attr( $key ); ?>_title" value="<?php echo esc_attr( $title ); ?>" class="cc-input-wide">

					<p style="margin-top:10px;"><label style="font-weight:600;"><?php esc_html_e( 'Descrizione', 'snn' ); ?></label></p>
					<textarea name="cc_cat_<?php echo esc_attr( $key ); ?>_description" rows="2" class="cc-textarea"><?php echo esc_textarea( $desc ); ?></textarea>

					<?php if ( $info['autoclear'] ) : ?>
						<p style="margin-top:10px;"><label style="font-weight:600;"><?php esc_html_e( 'Auto-clear cookies (uno per riga)', 'snn' ); ?></label></p>
						<textarea name="cc_cat_<?php echo esc_attr( $key ); ?>_autoclear" rows="3" class="cc-textarea" placeholder="^_ga&#10;_gid&#10;^_fbp"><?php echo esc_textarea( $autoclr ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Una espressione regolare (o nome esatto) per riga. Cookies che corrispondono verranno rimossi al revoco del consenso per questa categoria.', 'snn' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		endforeach;
		?>
	</table>

	<h2 style="margin-top:30px;"><?php esc_html_e( 'Servizi', 'snn' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Inserisci qui gli script da iniettare in pagina solo dopo l\'accettazione della relativa categoria.', 'snn' ); ?><br>
		<strong><?php esc_html_e( 'Mandatory', 'snn' ); ?></strong>: <?php esc_html_e( 'forza la categoria a "necessary" (sempre attivo).', 'snn' ); ?>
	</p>

	<div id="cc-services-repeater" class="cc-services-repeater">
		<?php
		if ( ! empty( $services ) ) {
			foreach ( $services as $i => $service ) {
				cc_render_service_item( $i, $service );
			}
		} else {
			cc_render_service_item( 0, array() );
		}
		?>
	</div>
	<button type="button" id="cc-add-service" class="button"><?php esc_html_e( 'Aggiungi servizio', 'snn' ); ?></button>

	<script type="text/template" id="cc-service-template">
		<?php cc_render_service_item( '__INDEX__', array() ); ?>
	</script>

	<script>
	(function($){
		$(document).ready(function(){
			var nextIndex = <?php echo (int) ( ! empty( $services ) ? count( $services ) : 1 ); ?>;

			function reindex() {
				$('#cc-services-repeater .cc-service-item').each(function(i){
					$(this).find('input, textarea, select').each(function(){
						var n = $(this).attr('name');
						if (n) {
							$(this).attr('name', n.replace(/cc_services\[\d+\]/, 'cc_services[' + i + ']').replace(/cc_services\[__INDEX__\]/, 'cc_services[' + i + ']'));
						}
					});
				});
				nextIndex = $('#cc-services-repeater .cc-service-item').length;
			}

			$('#cc-add-service').on('click', function(){
				var tpl = $('#cc-service-template').html().replace(/__INDEX__/g, nextIndex);
				$('#cc-services-repeater').append(tpl);
				nextIndex++;
			});

			$('#cc-services-repeater').on('click', '.cc-remove-service', function(){
				$(this).closest('.cc-service-item').remove();
				reindex();
			});

			$('#cc-services-repeater').on('click', '.cc-move-up', function(){
				var $i = $(this).closest('.cc-service-item');
				var $p = $i.prev('.cc-service-item');
				if ($p.length) { $i.insertBefore($p); reindex(); }
			});

			$('#cc-services-repeater').on('click', '.cc-move-down', function(){
				var $i = $(this).closest('.cc-service-item');
				var $n = $i.next('.cc-service-item');
				if ($n.length) { $i.insertAfter($n); reindex(); }
			});

			reindex();
		});
	})(jQuery);
	</script>
	<?php
}

function cc_render_service_item( $index, $service ) {
	$name      = isset( $service['name'] ) ? $service['name'] : '';
	$desc      = isset( $service['description'] ) ? $service['description'] : '';
	$script    = isset( $service['script'] ) ? $service['script'] : '';
	$position  = isset( $service['position'] ) ? $service['position'] : 'body_bottom';
	$mandatory = isset( $service['mandatory'] ) ? $service['mandatory'] : 'no';
	$category  = isset( $service['category'] ) ? $service['category'] : 'analytics';
	$idx       = esc_attr( $index );
	?>
	<div class="cc-service-item">
		<div class="cc-service-actions">
			<button type="button" class="cc-move-btn cc-move-up" title="<?php esc_attr_e( 'Sposta su', 'snn' ); ?>">▲</button>
			<button type="button" class="cc-move-btn cc-move-down" title="<?php esc_attr_e( 'Sposta giù', 'snn' ); ?>">▼</button>
		</div>
		<label><?php esc_html_e( 'Nome servizio', 'snn' ); ?>
			<input type="text" name="cc_services[<?php echo $idx; ?>][name]" value="<?php echo esc_attr( $name ); ?>">
		</label>
		<label><?php esc_html_e( 'Descrizione', 'snn' ); ?>
			<textarea name="cc_services[<?php echo $idx; ?>][description]" rows="2"><?php echo esc_textarea( $desc ); ?></textarea>
		</label>
		<label><?php esc_html_e( 'Codice script (HTML/JS permesso)', 'snn' ); ?>
			<textarea name="cc_services[<?php echo $idx; ?>][script]" rows="5" style="font-family:monospace;"><?php echo esc_textarea( $script ); ?></textarea>
		</label>
		<label><?php esc_html_e( 'Posizione', 'snn' ); ?></label>
		<div class="cc-radio-group">
			<label><input type="radio" name="cc_services[<?php echo $idx; ?>][position]" value="head" <?php checked( $position, 'head' ); ?>> Head</label>
			<label><input type="radio" name="cc_services[<?php echo $idx; ?>][position]" value="body_top" <?php checked( $position, 'body_top' ); ?>> Body Top</label>
			<label><input type="radio" name="cc_services[<?php echo $idx; ?>][position]" value="body_bottom" <?php checked( $position, 'body_bottom' ); ?>> Body Bottom</label>
		</div>
		<label><?php esc_html_e( 'Categoria', 'snn' ); ?>
			<select name="cc_services[<?php echo $idx; ?>][category]">
				<option value="necessary" <?php selected( $category, 'necessary' ); ?>>necessary</option>
				<option value="analytics" <?php selected( $category, 'analytics' ); ?>>analytics</option>
				<option value="marketing" <?php selected( $category, 'marketing' ); ?>>marketing</option>
			</select>
		</label>
		<div class="cc-checkbox-line">
			<label><input type="checkbox" name="cc_services[<?php echo $idx; ?>][mandatory]" value="yes" <?php checked( $mandatory, 'yes' ); ?>> <?php esc_html_e( 'Mandatory (sempre attivo)', 'snn' ); ?></label>
		</div>
		<button type="button" class="button cc-remove-service" style="margin-top:8px;"><?php esc_html_e( 'Rimuovi', 'snn' ); ?></button>
	</div>
	<?php
}

function cc_render_tab_scanner() {
	$blocked = cc_get_blocked_scripts();
	$pages   = get_posts( array(
		'post_type'   => array( 'page', 'post' ),
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby'     => 'title',
		'order'       => 'ASC',
	) );
	?>
	<h2><?php esc_html_e( 'Scanner script di pagina', 'snn' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Effettua una scansione del frontend per individuare script e iframe di terze parti, poi seleziona quali bloccare fino al consenso dell\'utente.', 'snn' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Pagina da scansionare', 'snn' ); ?></th>
			<td>
				<input type="text" id="cc-page-url-input" list="cc-page-list" class="cc-input-wide" placeholder="<?php esc_attr_e( 'Inizia a digitare il titolo...', 'snn' ); ?>">
				<datalist id="cc-page-list">
					<option value="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'snn' ); ?></option>
					<?php foreach ( $pages as $p ) : ?>
						<option value="<?php echo esc_url( get_permalink( $p->ID ) ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
					<?php endforeach; ?>
				</datalist>
				<button type="button" id="cc-scan-page-btn" class="button button-primary"><?php esc_html_e( 'Scansiona', 'snn' ); ?></button>
				<div id="cc-scan-loading" style="display:none;margin-top:10px;">
					<span class="spinner is-active" style="float:none;margin:0;"></span>
					<span><?php esc_html_e( 'Scansione in corso...', 'snn' ); ?></span>
				</div>
			</td>
		</tr>
		<tr id="cc-scan-results-row" style="display:none;">
			<th><?php esc_html_e( 'Risorse rilevate', 'snn' ); ?></th>
			<td><div id="cc-scan-results"></div></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Script attualmente bloccati', 'snn' ); ?></th>
			<td>
				<div id="cc-blocked-repeater" class="cc-blocked-repeater">
					<?php if ( ! empty( $blocked ) ) : ?>
						<?php foreach ( $blocked as $i => $b ) : ?>
							<?php cc_render_blocked_item( $i, $b ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="description cc-no-blocked"><?php esc_html_e( 'Nessuno script bloccato.', 'snn' ); ?></p>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Testo placeholder iframe', 'snn' ); ?></th>
			<td>
				<input type="text" name="cc_iframe_block_text" value="<?php echo esc_attr( cc_get_opt( 'cc_iframe_block_text', __( 'Accetta i cookie per visualizzare questo contenuto.', 'snn' ) ) ); ?>" class="cc-input-wide">
			</td>
		</tr>
	</table>

	<script type="text/template" id="cc-blocked-template">
		<?php cc_render_blocked_item( '__INDEX__', array() ); ?>
	</script>

	<script>
	(function($){
		$(document).ready(function(){
			var blockedNextIndex = $('#cc-blocked-repeater .cc-blocked-item').length;

			function reindexBlocked() {
				$('#cc-blocked-repeater .cc-blocked-item').each(function(i){
					$(this).find('input, select, textarea').each(function(){
						var n = $(this).attr('name');
						if (n) {
							$(this).attr('name', n.replace(/cc_blocked_scripts\[\d+\]/, 'cc_blocked_scripts[' + i + ']').replace(/cc_blocked_scripts\[__INDEX__\]/, 'cc_blocked_scripts[' + i + ']'));
						}
					});
				});
				blockedNextIndex = $('#cc-blocked-repeater .cc-blocked-item').length;
			}

			$('#cc-scan-page-btn').on('click', function(){
				var url = $('#cc-page-url-input').val();
				if (!url) { alert('<?php echo esc_js( __( 'Seleziona o inserisci un URL.', 'snn' ) ); ?>'); return; }

				$('#cc-scan-loading').show();
				$('#cc-scan-results-row').hide();

				$.post(ajaxurl, {
					action: 'cc_scan_page_scripts',
					page_url: url,
					nonce: '<?php echo esc_js( wp_create_nonce( 'cc_scan_page' ) ); ?>'
				}).done(function(resp){
					$('#cc-scan-loading').hide();
					if (!resp.success) { alert(resp.data || 'Error'); return; }
					renderScanResults(resp.data);
					$('#cc-scan-results-row').show();
				}).fail(function(){
					$('#cc-scan-loading').hide();
					alert('<?php echo esc_js( __( 'Scansione fallita.', 'snn' ) ); ?>');
				});
			});

			function renderScanResults(data) {
				var html = '<div style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:15px;background:#f9f9f9;">';
				if (!data.scripts.length && !data.iframes.length) {
					html += '<p><?php echo esc_js( __( 'Nessuno script o iframe esterno rilevato.', 'snn' ) ); ?></p>';
				} else {
					var blockedUrls = (data.blocked_scripts || []).map(function(b){ return typeof b === 'string' ? b : b.url; });
					function renderItem(url) {
						var isBlocked = blockedUrls.indexOf(url) !== -1;
						return '<li style="padding:8px;background:#fff;border:1px solid #ddd;margin-bottom:6px;">' +
							'<label style="display:flex;align-items:center;gap:8px;font-weight:400;">' +
							'<input type="checkbox" class="cc-script-to-block" value="' + url + '"' + (isBlocked ? ' checked disabled' : '') + '>' +
							'<code style="flex:1;word-break:break-all;font-size:11px;">' + url + '</code>' +
							(isBlocked ? '<span style="color:#d63638;font-weight:600;">(<?php echo esc_js( __( 'già bloccato', 'snn' ) ); ?>)</span>' : '') +
							'</label></li>';
					}
					if (data.scripts.length) {
						html += '<h3><?php echo esc_js( __( 'Scripts trovati:', 'snn' ) ); ?> ' + data.scripts.length + '</h3><ul style="list-style:none;padding:0;">';
						data.scripts.forEach(function(s){ html += renderItem(s); });
						html += '</ul>';
					}
					if (data.iframes.length) {
						html += '<h3 style="margin-top:18px;"><?php echo esc_js( __( 'Iframe trovati:', 'snn' ) ); ?> ' + data.iframes.length + '</h3><ul style="list-style:none;padding:0;">';
						data.iframes.forEach(function(s){ html += renderItem(s); });
						html += '</ul>';
					}
					html += '<button type="button" id="cc-add-selected-scripts" class="button button-primary" style="margin-top:12px;"><?php echo esc_js( __( 'Blocca selezionati', 'snn' ) ); ?></button>';
				}
				html += '</div>';
				$('#cc-scan-results').html(html);
			}

			$(document).on('click', '#cc-add-selected-scripts', function(){
				var sel = [];
				$('.cc-script-to-block:checked:not(:disabled)').each(function(){ sel.push($(this).val()); });
				if (!sel.length) { alert('<?php echo esc_js( __( 'Seleziona almeno una risorsa.', 'snn' ) ); ?>'); return; }

				$('#cc-blocked-repeater .cc-no-blocked').remove();

				sel.forEach(function(u){
					var tpl = $('#cc-blocked-template').html().replace(/__INDEX__/g, blockedNextIndex);
					var $item = $(tpl);
					$item.find('input[name$="[url]"]').val(u);
					$item.find('.cc-blocked-url-display').text(u);
					$('#cc-blocked-repeater').append($item);
					blockedNextIndex++;
				});

				$('.cc-script-to-block').filter(function(){ return sel.indexOf($(this).val()) !== -1; }).prop('disabled', true).prop('checked', true);
				alert('<?php echo esc_js( __( 'Aggiunti alla lista bloccati. Compila nome/descrizione/categoria e salva.', 'snn' ) ); ?>');
			});

			$('#cc-blocked-repeater').on('click', '.cc-remove-blocked', function(){
				$(this).closest('.cc-blocked-item').remove();
				if (!$('#cc-blocked-repeater .cc-blocked-item').length) {
					$('#cc-blocked-repeater').html('<p class="description cc-no-blocked"><?php echo esc_js( __( 'Nessuno script bloccato.', 'snn' ) ); ?></p>');
				}
				reindexBlocked();
			});
		});
	})(jQuery);
	</script>
	<?php
}

function cc_render_blocked_item( $index, $item ) {
	$url      = isset( $item['url'] ) ? $item['url'] : '';
	$name     = isset( $item['name'] ) ? $item['name'] : '';
	$desc     = isset( $item['description'] ) ? $item['description'] : '';
	$category = isset( $item['category'] ) ? $item['category'] : 'analytics';
	$idx      = esc_attr( $index );
	?>
	<div class="cc-blocked-item">
		<button type="button" class="button button-small cc-remove-blocked" style="position:absolute;top:8px;right:8px;"><?php esc_html_e( 'Rimuovi', 'snn' ); ?></button>
		<label><?php esc_html_e( 'Nome servizio', 'snn' ); ?>
			<input type="text" name="cc_blocked_scripts[<?php echo $idx; ?>][name]" value="<?php echo esc_attr( $name ); ?>">
		</label>
		<label><?php esc_html_e( 'Descrizione', 'snn' ); ?>
			<input type="text" name="cc_blocked_scripts[<?php echo $idx; ?>][description]" value="<?php echo esc_attr( $desc ); ?>">
		</label>
		<label><?php esc_html_e( 'Categoria', 'snn' ); ?>
			<select name="cc_blocked_scripts[<?php echo $idx; ?>][category]">
				<option value="necessary" <?php selected( $category, 'necessary' ); ?>>necessary</option>
				<option value="analytics" <?php selected( $category, 'analytics' ); ?>>analytics</option>
				<option value="marketing" <?php selected( $category, 'marketing' ); ?>>marketing</option>
			</select>
		</label>
		<label><?php esc_html_e( 'URL bloccato', 'snn' ); ?></label>
		<code class="cc-blocked-url-display" style="display:block;background:#fff;padding:8px;border:1px solid #ddd;word-break:break-all;font-size:11px;"><?php echo esc_html( $url ); ?></code>
		<input type="hidden" name="cc_blocked_scripts[<?php echo $idx; ?>][url]" value="<?php echo esc_attr( $url ); ?>">
	</div>
	<?php
}

function cc_render_tab_styles() {
	?>
	<p class="description">
		<?php esc_html_e( 'I colori qui impostati vengono iniettati come variabili CSS personalizzate (--cc-*) sul selettore #cc-main, sovrascrivendo quelli definiti in style.css. Lascia vuoto per usare i valori del tema.', 'snn' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<?php
		$colors = array(
			'cc_color_bg'                => __( 'Sfondo modali', 'snn' ),
			'cc_color_text'              => __( 'Colore testo', 'snn' ),
			'cc_color_btn_primary_bg'    => __( 'Pulsante primario - sfondo', 'snn' ),
			'cc_color_btn_primary_text'  => __( 'Pulsante primario - testo', 'snn' ),
			'cc_color_btn_secondary_bg'  => __( 'Pulsante secondario - sfondo', 'snn' ),
			'cc_color_btn_secondary_text'=> __( 'Pulsante secondario - testo', 'snn' ),
			'cc_color_overlay'           => __( 'Overlay (rgba)', 'snn' ),
		);
		foreach ( $colors as $key => $label ) :
			$val = cc_get_opt( $key, '' );
			?>
			<tr>
				<th><?php echo esc_html( $label ); ?></th>
				<td><input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>" class="cc-color-picker" data-default-color=""></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th><?php esc_html_e( 'CSS personalizzato', 'snn' ); ?></th>
			<td>
				<textarea name="cc_custom_css" rows="10" class="cc-textarea" style="height:200px;"><?php echo esc_textarea( cc_get_opt( 'cc_custom_css', '' ) ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Selettori utili:', 'snn' ); ?>
					<code>#cc-main</code>, <code>#cc-main .cm__btn</code>, <code>#cc-main .pm__title</code>, <code>#cc-main .pm__section--toggle</code>.
					<?php esc_html_e( 'Per pulsanti di cambio preferenze:', 'snn' ); ?> <code>.cc-cookie-change</code> / <code>.snn-cookie-change</code>.
				</p>
			</td>
		</tr>
	</table>
	<?php
}

// =============================================================================
// POLYLANG STRING REGISTRATION
// =============================================================================

function cc_register_polylang_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$pairs = array(
		// Consent modal
		'Consent Modal - Title'              => cc_get_opt( 'cc_consent_title', '' ),
		'Consent Modal - Description'        => array( cc_get_opt( 'cc_consent_description', '' ), true ),
		'Consent Modal - Accept All Btn'     => cc_get_opt( 'cc_consent_accept_all_btn', '' ),
		'Consent Modal - Accept Necessary'   => cc_get_opt( 'cc_consent_accept_necessary_btn', '' ),
		'Consent Modal - Show Prefs Btn'     => cc_get_opt( 'cc_consent_show_prefs_btn', '' ),
		'Consent Modal - Footer Custom Text' => array( cc_get_opt( 'cc_consent_footer_custom_text', '' ), true ),
		// Preferences modal
		'Preferences Modal - Title'             => cc_get_opt( 'cc_prefs_title', '' ),
		'Preferences Modal - Intro Description' => array( cc_get_opt( 'cc_prefs_intro_description', '' ), true ),
		'Preferences Modal - Accept All Btn'    => cc_get_opt( 'cc_prefs_accept_all_btn', '' ),
		'Preferences Modal - Accept Necessary'  => cc_get_opt( 'cc_prefs_accept_necessary_btn', '' ),
		'Preferences Modal - Save Btn'          => cc_get_opt( 'cc_prefs_save_btn', '' ),
		'Preferences Modal - Close Label'       => cc_get_opt( 'cc_prefs_close_label', '' ),
		'Preferences Modal - Service Counter'   => cc_get_opt( 'cc_prefs_service_counter_label', '' ),
		// Categories
		'Category - Necessary - Title'         => cc_get_opt( 'cc_cat_necessary_title', '' ),
		'Category - Necessary - Description'   => array( cc_get_opt( 'cc_cat_necessary_description', '' ), true ),
		'Category - Analytics - Title'         => cc_get_opt( 'cc_cat_analytics_title', '' ),
		'Category - Analytics - Description'   => array( cc_get_opt( 'cc_cat_analytics_description', '' ), true ),
		'Category - Marketing - Title'         => cc_get_opt( 'cc_cat_marketing_title', '' ),
		'Category - Marketing - Description'   => array( cc_get_opt( 'cc_cat_marketing_description', '' ), true ),
		// Misc
		'Iframe Block Text' => cc_get_opt( 'cc_iframe_block_text', '' ),
	);

	foreach ( $pairs as $name => $val ) {
		if ( is_array( $val ) ) {
			cc_pll_register( $name, $val[0], (bool) $val[1] );
		} else {
			cc_pll_register( $name, $val );
		}
	}

	// Services
	foreach ( cc_get_services() as $i => $s ) {
		if ( ! empty( $s['name'] ) ) {
			cc_pll_register( "Service {$i} - Name", $s['name'] );
		}
		if ( ! empty( $s['description'] ) ) {
			cc_pll_register( "Service {$i} - Description", $s['description'], true );
		}
	}

	// Blocked scripts
	foreach ( cc_get_blocked_scripts() as $i => $b ) {
		if ( ! empty( $b['name'] ) ) {
			cc_pll_register( "Blocked Script {$i} - Name", $b['name'] );
		}
		if ( ! empty( $b['description'] ) ) {
			cc_pll_register( "Blocked Script {$i} - Description", $b['description'], true );
		}
	}
}
add_action( 'admin_init', 'cc_register_polylang_strings', 100 );
add_action( 'wp', 'cc_register_polylang_strings', 100 );

// =============================================================================
// FOOTER HTML BUILDER
// =============================================================================

function cc_generate_footer_html() {
	$parts = array();

	$privacy_id = (int) cc_get_opt( 'cc_consent_footer_privacy_page', 0 );
	if ( $privacy_id ) {
		$url   = cc_pll_permalink( $privacy_id );
		$title = get_the_title( function_exists( 'pll_get_post' ) && pll_get_post( $privacy_id ) ? pll_get_post( $privacy_id ) : $privacy_id );
		if ( $url && $title ) {
			$parts[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a>';
		}
	}

	$cookie_id = (int) cc_get_opt( 'cc_consent_footer_cookie_page', 0 );
	if ( $cookie_id ) {
		$url   = cc_pll_permalink( $cookie_id );
		$title = get_the_title( function_exists( 'pll_get_post' ) && pll_get_post( $cookie_id ) ? pll_get_post( $cookie_id ) : $cookie_id );
		if ( $url && $title ) {
			$parts[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a>';
		}
	}

	$custom = cc_get_opt( 'cc_consent_footer_custom_text', '' );
	if ( ! empty( $custom ) ) {
		$parts[] = cc_pll( $custom );
	}

	return implode( ' ', $parts );
}

// =============================================================================
// CC CONFIG BUILDER
// =============================================================================

function cc_build_config() {
	if ( ! cc_is_enabled() ) {
		return array();
	}

	$current_lang = cc_pll_current_language();
	$default_lang = cc_get_opt( 'cc_language_default', 'it' );

	// Build categories with autoClear cookies
	$categories = array();
	$cat_ids    = cc_categories_list();
	foreach ( $cat_ids as $cat_key => $cat_default ) {
		$cat = $cat_default;
		if ( 'necessary' !== $cat_key ) {
			$autoclear_raw = cc_get_opt( "cc_cat_{$cat_key}_autoclear", '' );
			$lines         = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $autoclear_raw ) ) );
			if ( ! empty( $lines ) && (int) cc_get_opt( 'cc_auto_clear_cookies', 1 ) === 1 ) {
				$cookies = array();
				foreach ( $lines as $line ) {
					$cookies[] = array( 'name' => $line );
				}
				$cat['autoClear'] = array( 'cookies' => $cookies );
			}
		}

		// Inject services per category from cc_services
		$services_for_cat = array();
		foreach ( cc_get_services() as $i => $s ) {
			$service_cat = ( 'yes' === ( $s['mandatory'] ?? 'no' ) ) ? 'necessary' : ( $s['category'] ?? 'analytics' );
			if ( $service_cat === $cat_key ) {
				$key                          = 'service_' . $i;
				$services_for_cat[ $key ]     = array(
					'label' => cc_pll( $s['name'] ),
				);
			}
		}
		// Inject blocked scripts as services per category
		foreach ( cc_get_blocked_scripts() as $i => $b ) {
			if ( ( $b['category'] ?? 'analytics' ) === $cat_key ) {
				$key                          = 'blocked_' . $i;
				$services_for_cat[ $key ]     = array(
					'label' => cc_pll( ! empty( $b['name'] ) ? $b['name'] : $b['url'] ),
				);
			}
		}
		if ( ! empty( $services_for_cat ) ) {
			$cat['services'] = $services_for_cat;
		}

		$categories[ $cat_key ] = $cat;
	}

	// Build sections (intro + per-category)
	$sections = array();
	$intro    = cc_pll( cc_get_opt( 'cc_prefs_intro_description', '' ) );
	if ( ! empty( $intro ) ) {
		$sections[] = array( 'description' => $intro );
	}
	$cat_titles = array(
		'necessary' => cc_pll( cc_get_opt( 'cc_cat_necessary_title', __( 'Strettamente necessari', 'snn' ) ) ),
		'analytics' => cc_pll( cc_get_opt( 'cc_cat_analytics_title', __( 'Analytics', 'snn' ) ) ),
		'marketing' => cc_pll( cc_get_opt( 'cc_cat_marketing_title', __( 'Marketing', 'snn' ) ) ),
	);
	$cat_descs  = array(
		'necessary' => cc_pll( cc_get_opt( 'cc_cat_necessary_description', '' ) ),
		'analytics' => cc_pll( cc_get_opt( 'cc_cat_analytics_description', '' ) ),
		'marketing' => cc_pll( cc_get_opt( 'cc_cat_marketing_description', '' ) ),
	);
	foreach ( array( 'necessary', 'analytics', 'marketing' ) as $cat_key ) {
		$sections[] = array(
			'title'          => $cat_titles[ $cat_key ],
			'description'    => $cat_descs[ $cat_key ],
			'linkedCategory' => $cat_key,
		);
	}

	$accept_necessary = cc_pll( cc_get_opt( 'cc_consent_accept_necessary_btn', '' ) );

	$consent_modal_buttons = array(
		'acceptAllBtn'       => cc_pll( cc_get_opt( 'cc_consent_accept_all_btn', __( 'Accetta tutti', 'snn' ) ) ),
		'showPreferencesBtn' => cc_pll( cc_get_opt( 'cc_consent_show_prefs_btn', __( 'Gestisci preferenze', 'snn' ) ) ),
	);
	if ( ! empty( $accept_necessary ) ) {
		$consent_modal_buttons['acceptNecessaryBtn'] = $accept_necessary;
	}

	$config = array(
		'mode'                   => 'opt-in',
		'autoShow'               => true,
		'revision'               => (int) cc_get_opt( 'cc_revision', 0 ),
		'hideFromBots'           => 1 === (int) cc_get_opt( 'cc_hide_from_bots', 1 ),
		'disablePageInteraction' => 1 === (int) cc_get_opt( 'cc_disable_page_interaction', 0 ),
		'manageScriptTags'       => true,
		'autoClearCookies'       => 1 === (int) cc_get_opt( 'cc_auto_clear_cookies', 1 ),

		'cookie' => array(
			'name'             => cc_get_opt( 'cc_cookie_name', 'cc_cookie' ),
			'domain'           => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
			'path'             => '/',
			'secure'           => is_ssl(),
			'expiresAfterDays' => (int) cc_get_opt( 'cc_cookie_expires', 182 ),
			'sameSite'         => cc_get_opt( 'cc_cookie_same_site', 'Lax' ),
		),

		'guiOptions' => array(
			'consentModal' => array(
				'layout'             => cc_get_opt( 'cc_consent_layout', 'cloud' ),
				'position'           => trim( cc_get_opt( 'cc_consent_position_v', 'bottom' ) . ' ' . cc_get_opt( 'cc_consent_position_h', 'center' ) ),
				'flipButtons'        => 1 === (int) cc_get_opt( 'cc_consent_flip_buttons', 0 ),
				'equalWeightButtons' => 1 === (int) cc_get_opt( 'cc_consent_equal_weight_buttons', 1 ),
			),
			'preferencesModal' => array(
				'layout'             => cc_get_opt( 'cc_prefs_layout', 'box' ),
				'position'           => cc_get_opt( 'cc_prefs_position', 'right' ),
				'flipButtons'        => 1 === (int) cc_get_opt( 'cc_prefs_flip_buttons', 0 ),
				'equalWeightButtons' => 1 === (int) cc_get_opt( 'cc_prefs_equal_weight_buttons', 1 ),
			),
		),

		'language' => array(
			'default'      => $current_lang,
			'autoDetect'   => cc_get_opt( 'cc_language_auto_detect', 'browser' ),
			'translations' => array(
				$current_lang => array(
					'consentModal' => array(
						'title'       => cc_pll( cc_get_opt( 'cc_consent_title', __( 'Gestione del consenso', 'snn' ) ) ),
						'description' => cc_pll( cc_get_opt( 'cc_consent_description', '' ) ),
						'footer'      => cc_generate_footer_html(),
					) + $consent_modal_buttons,
					'preferencesModal' => array(
						'title'                  => cc_pll( cc_get_opt( 'cc_prefs_title', __( 'Le tue preferenze sulla privacy', 'snn' ) ) ),
						'acceptAllBtn'           => cc_pll( cc_get_opt( 'cc_prefs_accept_all_btn', __( 'Accetta tutti', 'snn' ) ) ),
						'acceptNecessaryBtn'     => cc_pll( cc_get_opt( 'cc_prefs_accept_necessary_btn', __( 'Solo necessari', 'snn' ) ) ),
						'savePreferencesBtn'     => cc_pll( cc_get_opt( 'cc_prefs_save_btn', __( 'Salva preferenze', 'snn' ) ) ),
						'closeIconLabel'         => cc_pll( cc_get_opt( 'cc_prefs_close_label', __( 'Chiudi modale', 'snn' ) ) ),
						'serviceCounterLabel'    => cc_pll( cc_get_opt( 'cc_prefs_service_counter_label', 'Servizio|Servizi' ) ),
						'sections'               => $sections,
					),
				),
			),
		),

		'categories' => $categories,

		// Custom flags consumed by the inline init script (NOT part of CC schema).
		'_enableGaConsent'      => 1 === (int) cc_get_opt( 'cc_enable_ga_consent', 0 ),
		'_enableClarityConsent' => 1 === (int) cc_get_opt( 'cc_enable_clarity_consent', 0 ),
	);

	return $config;
}

// =============================================================================
// FRONTEND: SHOULD-OUTPUT GUARD
// =============================================================================

function cc_should_output_frontend() {
	if ( ! cc_is_enabled() ) {
		return false;
	}
	if ( is_user_logged_in() && 1 === (int) cc_get_opt( 'cc_disable_for_logged_in', 0 ) ) {
		return false;
	}
	if ( function_exists( 'bricks_is_builder_main' ) && bricks_is_builder_main() ) {
		return false;
	}
	return true;
}

function cc_should_output_scripts() {
	if ( ! cc_should_output_frontend() ) {
		return false;
	}
	if ( is_user_logged_in() && 1 === (int) cc_get_opt( 'cc_disable_scripts_for_logged_in', 0 ) ) {
		return false;
	}
	return true;
}

// =============================================================================
// FRONTEND: ENQUEUE LIBRARY
// =============================================================================

function cc_enqueue_assets() {
	if ( ! cc_should_output_frontend() ) {
		return;
	}

	wp_enqueue_script( 'cookieconsent', CC_LIB_JS, array(), CC_VERSION, true );
	wp_enqueue_style( 'cookieconsent', CC_LIB_CSS, array(), CC_VERSION );
}
add_action( 'wp_enqueue_scripts', 'cc_enqueue_assets' );

// =============================================================================
// FRONTEND: DYNAMIC CSS VARIABLES + CUSTOM CSS
// =============================================================================

function cc_output_dynamic_css() {
	if ( ! cc_should_output_frontend() ) {
		return;
	}

	$map = array(
		'--cc-bg'                       => 'cc_color_bg',
		'--cc-primary-color'            => 'cc_color_text',
		'--cc-secondary-color'          => 'cc_color_text',
		'--cc-btn-primary-bg'           => 'cc_color_btn_primary_bg',
		'--cc-btn-primary-border-color' => 'cc_color_btn_primary_bg',
		'--cc-btn-primary-color'        => 'cc_color_btn_primary_text',
		'--cc-btn-secondary-bg'         => 'cc_color_btn_secondary_bg',
		'--cc-btn-secondary-border-color' => 'cc_color_btn_secondary_bg',
		'--cc-btn-secondary-color'      => 'cc_color_btn_secondary_text',
		'--cc-overlay-bg'               => 'cc_color_overlay',
	);

	$rules = array();
	foreach ( $map as $var => $opt_key ) {
		$val = cc_get_opt( $opt_key, '' );
		if ( ! empty( $val ) ) {
			$rules[] = $var . ':' . $val . ';';
		}
	}

	if ( ! empty( $rules ) ) {
		echo '<style id="cc-dynamic-vars">#cc-main{' . implode( '', $rules ) . '}</style>'; // phpcs:ignore
	}

	$custom = cc_get_opt( 'cc_custom_css', '' );
	if ( ! empty( $custom ) ) {
		echo '<style id="cc-custom-css">' . $custom . '</style>'; // phpcs:ignore
	}
}
add_action( 'wp_head', 'cc_output_dynamic_css', 50 );

// =============================================================================
// FRONTEND: SCRIPT BLOCKER (early in <head>)
// =============================================================================

function cc_output_script_blocker() {
	if ( ! cc_should_output_scripts() ) {
		return;
	}

	$blocked = cc_get_blocked_scripts();
	if ( empty( $blocked ) ) {
		return;
	}

	// Pass URL + category so the blocker can decide per-resource.
	$blocked_map = array();
	foreach ( $blocked as $i => $b ) {
		$blocked_map[] = array(
			'url'      => $b['url'],
			'category' => $b['category'],
		);
	}

	$cookie_name      = cc_get_opt( 'cc_cookie_name', 'cc_cookie' );
	$iframe_text      = cc_pll( cc_get_opt( 'cc_iframe_block_text', __( 'Accetta i cookie per visualizzare questo contenuto.', 'snn' ) ) );
	?>
<script id="cc-script-blocker">
(function(){
	var blockedMap = <?php echo wp_json_encode( $blocked_map ); ?>;
	var ccCookieName = <?php echo wp_json_encode( $cookie_name ); ?>;
	var iframeBlockText = <?php echo wp_json_encode( $iframe_text ); ?>;

	function getCookie(n){var e=n+"=",t=document.cookie.split(";");for(var i=0;i<t.length;i++){var o=t[i];while(" "===o.charAt(0))o=o.substring(1);if(0===o.indexOf(e))return o.substring(e.length)}return null}

	function getAcceptedCategories(){
		var raw = getCookie(ccCookieName);
		if(!raw) return null;
		try{ var d = JSON.parse(decodeURIComponent(raw)); return d.categories || []; }catch(e){ return null; }
	}

	function extractDomainPath(u){
		try{ var x = new URL(u); return x.hostname + x.pathname; }
		catch(e){ var m = u.match(/^https?:\/\/([^\/\?]+)([^\?]*)/); return m ? m[1]+m[2] : u; }
	}

	function findBlockedEntry(url){
		if(!url) return null;
		var n = url; if(n.indexOf("//")===0) n = "https:"+n;
		var tmp = document.createElement("div"); tmp.innerHTML = n;
		var dec = tmp.textContent || tmp.innerText || n;
		var ndp = extractDomainPath(n), ddp = extractDomainPath(dec);
		for(var i=0;i<blockedMap.length;i++){
			var b = blockedMap[i], burl = b.url, bdp = extractDomainPath(burl);
			if(n.indexOf(burl)!==-1 || burl.indexOf(n)!==-1 || dec.indexOf(burl)!==-1 || burl.indexOf(dec)!==-1) return b;
			if(ndp===bdp || ddp===bdp || ndp.indexOf(bdp)!==-1 || ddp.indexOf(bdp)!==-1) return b;
		}
		return null;
	}

	function shouldBlock(url){
		var entry = findBlockedEntry(url);
		if(!entry) return false;
		var accepted = getAcceptedCategories();
		if(accepted === null) return true;
		return accepted.indexOf(entry.category) === -1;
	}

	function addIframePlaceholder(iframe){
		var c = document.createElement("div");
		c.setAttribute("data-cc-iframe-placeholder","true");
		c.style.cssText = "position:absolute;top:0;left:0;width:100%;height:100%;background:#f5f5f5;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif;font-size:14px;color:#666;z-index:10;box-sizing:border-box;padding:20px;text-align:center;";
		c.innerHTML = iframeBlockText;
		var p = iframe.parentNode;
		if(p && window.getComputedStyle(p).position === "static"){
			p.style.position = "relative";
			iframe.setAttribute("data-cc-parent-pos-changed","true");
		}
		if(p) p.insertBefore(c, iframe.nextSibling);
	}

	function blockNode(node){
		if(node.tagName === "SCRIPT" && node.src && shouldBlock(node.src)){
			var entry = findBlockedEntry(node.src);
			node.type = "text/plain";
			node.setAttribute("data-cc-blocked","true");
			if(entry) node.setAttribute("data-cc-category", entry.category);
		}
		if(node.tagName === "IFRAME" && node.src && shouldBlock(node.src)){
			var entry2 = findBlockedEntry(node.src);
			node.setAttribute("data-cc-blocked-src", node.src);
			node.removeAttribute("src");
			node.setAttribute("data-cc-blocked","true");
			if(entry2) node.setAttribute("data-cc-category", entry2.category);
			addIframePlaceholder(node);
		}
	}

	function scanExisting(){
		document.querySelectorAll("script[src]").forEach(blockNode);
		document.querySelectorAll("iframe[src]").forEach(blockNode);
	}

	if(document.readyState === "loading"){
		document.addEventListener("DOMContentLoaded", scanExisting);
	}else{
		scanExisting();
	}

	var observer = new MutationObserver(function(muts){
		muts.forEach(function(m){ m.addedNodes.forEach(function(node){ if(node.nodeType === 1) blockNode(node); }); });
	});
	observer.observe(document.documentElement, { childList:true, subtree:true });

	window.ccBlockedMap = blockedMap;
	window.ccScriptObserver = observer;
	window.ccIframeBlockText = iframeBlockText;
	window.ccFindBlockedEntry = findBlockedEntry;
})();
</script>
	<?php
}
add_action( 'wp_head', 'cc_output_script_blocker', 2 );

// =============================================================================
// FRONTEND: SERVICE SCRIPT PLACEHOLDERS
// =============================================================================

function cc_output_service_scripts() {
	if ( ! cc_should_output_scripts() ) {
		return;
	}

	foreach ( cc_get_services() as $i => $service ) {
		if ( empty( $service['script'] ) ) {
			continue;
		}
		$cat = ( 'yes' === ( $service['mandatory'] ?? 'no' ) ) ? 'necessary' : ( $service['category'] ?? 'analytics' );
		?>
		<div
			class="cc-service-script"
			data-service-index="<?php echo esc_attr( $i ); ?>"
			data-script="<?php echo esc_attr( base64_encode( $service['script'] ) ); ?>"
			data-position="<?php echo esc_attr( $service['position'] ?? 'body_bottom' ); ?>"
			data-mandatory="<?php echo ( 'yes' === ( $service['mandatory'] ?? 'no' ) ) ? 'yes' : 'no'; ?>"
			data-category="<?php echo esc_attr( $cat ); ?>"
			style="display:none;"></div>
		<?php
	}
}
add_action( 'wp_footer', 'cc_output_service_scripts', 99 );

// =============================================================================
// FRONTEND: INIT SCRIPT (CC.run + GA/Clarity + injection + unblock)
// =============================================================================

function cc_output_init_script() {
	if ( ! cc_should_output_frontend() ) {
		return;
	}

	$config = cc_build_config();
	if ( empty( $config ) ) {
		return;
	}
	?>
<script id="cc-init">
(function(){
	if (typeof CookieConsent === "undefined") return;

	var ccConfig = <?php echo wp_json_encode( $config ); ?>;
	var enableGa = !!ccConfig._enableGaConsent;
	var enableClarity = !!ccConfig._enableClarityConsent;
	delete ccConfig._enableGaConsent;
	delete ccConfig._enableClarityConsent;

	// GA Consent Mode v2 default state (denied) BEFORE any GA snippet runs.
	if (enableGa) {
		window.dataLayer = window.dataLayer || [];
		window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
		window.gtag("consent", "default", {
			analytics_storage: "denied",
			ad_storage: "denied",
			ad_user_data: "denied",
			ad_personalization: "denied",
			wait_for_update: 500
		});
	}

	function injectScriptHTML(html, position){
		var tmp = document.createElement("div");
		tmp.innerHTML = html;
		tmp.querySelectorAll("script").forEach(function(s){
			var n = document.createElement("script");
			for (var i=0; i<s.attributes.length; i++){
				var a = s.attributes[i];
				n.setAttribute(a.name, a.value);
			}
			n.text = s.text || "";
			if (position === "head") document.head.appendChild(n);
			else if (position === "body_top" && document.body.firstChild) document.body.insertBefore(n, document.body.firstChild);
			else document.body.appendChild(n);
		});
		// Append leftover non-script HTML
		var nonScript = tmp.cloneNode(true);
		nonScript.querySelectorAll("script").forEach(function(s){ s.remove(); });
		if (nonScript.innerHTML.trim()) {
			var wrap = document.createElement("div");
			wrap.innerHTML = nonScript.innerHTML;
			while (wrap.firstChild) {
				if (position === "head") document.head.appendChild(wrap.firstChild);
				else document.body.appendChild(wrap.firstChild);
			}
		}
	}

	function injectServiceScripts(acceptedCategories){
		document.querySelectorAll(".cc-service-script[data-script]").forEach(function(div){
			if (div.getAttribute("data-injected") === "yes") return;
			var cat = div.getAttribute("data-category");
			var mandatory = div.getAttribute("data-mandatory") === "yes";
			if (!mandatory && acceptedCategories.indexOf(cat) === -1) return;
			var encoded = div.getAttribute("data-script");
			var pos = div.getAttribute("data-position") || "body_bottom";
			if (encoded) {
				try { injectScriptHTML(atob(encoded), pos); div.setAttribute("data-injected","yes"); }
				catch(e){}
			}
		});
	}

	function unblockScripts(acceptedCategories){
		document.querySelectorAll('script[data-cc-blocked="true"]').forEach(function(s){
			var cat = s.getAttribute("data-cc-category");
			if (!cat || acceptedCategories.indexOf(cat) === -1) return;
			var ns = document.createElement("script");
			for (var i=0; i<s.attributes.length; i++){
				var a = s.attributes[i];
				if (["type","data-cc-blocked","data-cc-category"].indexOf(a.name) !== -1) continue;
				ns.setAttribute(a.name, a.value);
			}
			ns.type = "text/javascript";
			s.parentNode.replaceChild(ns, s);
		});
		document.querySelectorAll('iframe[data-cc-blocked="true"]').forEach(function(f){
			var cat = f.getAttribute("data-cc-category");
			if (!cat || acceptedCategories.indexOf(cat) === -1) return;
			var src = f.getAttribute("data-cc-blocked-src");
			if (src){
				f.src = src;
				f.removeAttribute("data-cc-blocked");
				f.removeAttribute("data-cc-blocked-src");
				var ph = f.parentNode && f.parentNode.querySelector('[data-cc-iframe-placeholder]');
				if (ph) ph.remove();
				if (f.getAttribute("data-cc-parent-pos-changed")) {
					f.parentNode.style.position = "";
					f.removeAttribute("data-cc-parent-pos-changed");
				}
			}
		});
	}

	function applyConsent(cookie){
		var cats = (cookie && cookie.categories) ? cookie.categories : [];
		var analyticsAccepted = cats.indexOf("analytics") !== -1;
		var marketingAccepted = cats.indexOf("marketing") !== -1;

		if (enableGa && typeof window.gtag === "function") {
			window.gtag("consent", "update", {
				analytics_storage: analyticsAccepted ? "granted" : "denied",
				ad_storage: marketingAccepted ? "granted" : "denied",
				ad_user_data: marketingAccepted ? "granted" : "denied",
				ad_personalization: marketingAccepted ? "granted" : "denied"
			});
		}
		if (enableClarity && typeof window.clarity === "function") {
			window.clarity("consentv2", {
				ad_Storage: marketingAccepted ? "granted" : "denied",
				analytics_Storage: analyticsAccepted ? "granted" : "denied"
			});
		}

		injectServiceScripts(cats);
		unblockScripts(cats);
	}

	ccConfig.onFirstConsent = function(payload){ applyConsent(payload && payload.cookie); };
	ccConfig.onConsent      = function(payload){ applyConsent(payload && payload.cookie); };
	ccConfig.onChange       = function(payload){ applyConsent(payload && payload.cookie); };

	function init(){
		CookieConsent.run(ccConfig).then(function(){
			var main = document.getElementById("cc-main");
			if (main) main.setAttribute("data-lenis-prevent", "true");
		}).catch(function(){});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}

	// Cookie change triggers (.cc-cookie-change + retrocompat .snn-cookie-change)
	document.addEventListener("click", function(e){
		var t = e.target.closest && e.target.closest(".cc-cookie-change, .snn-cookie-change");
		if (t) {
			e.preventDefault();
			if (typeof CookieConsent.showPreferences === "function") {
				CookieConsent.showPreferences();
			}
		}
	});
})();
</script>
	<?php
}
add_action( 'wp_footer', 'cc_output_init_script', 100 );
