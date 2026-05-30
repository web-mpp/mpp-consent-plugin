<?php
/**
 * Plugin Name:       WP Cookie Shield
 * Plugin URI:        https://github.com/web-mpp/mpp-consent-plugin
 * Description:       GDPR/ePrivacy/CCPA/PIPEDA compliant cookie consent manager with Google Consent Mode v2.
 * Version:           1.0.9
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Author:            MPP
 * Author URI:        https://mpp.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-cookie-shield
 * Domain Path:       /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCS_VERSION',     '1.0.9' );
define( 'WPCS_PLUGIN_FILE', __FILE__ );
define( 'WPCS_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPCS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPCS_TEXT_DOMAIN', 'wp-cookie-shield' );

/**
 * Set this to 'owner/repo-name' of your GitHub repository.
 * The updater checks for new releases and surfaces them in WP Admin → Plugins.
 * Leave empty to disable auto-updates.
 */
define( 'WPCS_GITHUB_REPO', 'web-mpp/mpp-consent-plugin' );

// PSR-4-style autoloader
spl_autoload_register( function ( string $class ): void {
	$map = [
		'WPCS_Plugin'         => 'includes/class-plugin.php',
		'WPCS_Installer'      => 'includes/class-installer.php',
		'WPCS_ConsentManager' => 'includes/class-consent-manager.php',
		'WPCS_ConsentLogger'  => 'includes/class-consent-logger.php',
		'WPCS_CookieScanner'  => 'includes/class-cookie-scanner.php',
		'WPCS_GCMHandler'     => 'includes/class-gcm-handler.php',
		'WPCS_ScriptBlocker'  => 'includes/class-script-blocker.php',
		'WPCS_Geolocation'    => 'includes/class-geolocation.php',
		'WPCS_RestAPI'        => 'includes/class-rest-api.php',
		'WPCS_WPConsentAPI'   => 'includes/class-wp-consent-api.php',
		'WPCS_I18n'           => 'includes/class-i18n.php',
		'WPCS_Admin'          => 'admin/class-admin.php',
		'WPCS_Settings'       => 'includes/class-settings.php',
		'WPCS_SettingsAdmin'  => 'admin/class-settings.php',
		'WPCS_ScannerPage'    => 'admin/class-scanner-page.php',
		'WPCS_ConsentLogPage' => 'admin/class-consent-log-page.php',
		'WPCS_Frontend'       => 'public/class-frontend.php',
		'WPCS_Updater'        => 'includes/class-updater.php',
	];

	if ( isset( $map[ $class ] ) ) {
		require_once WPCS_PLUGIN_DIR . $map[ $class ];
	}
} );

// Activation / deactivation
register_activation_hook( __FILE__,   [ 'WPCS_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WPCS_Installer', 'deactivate' ] );

// Boot
add_action( 'plugins_loaded', function (): void {
	WPCS_Plugin::get_instance();
	new WPCS_Updater();
} );
