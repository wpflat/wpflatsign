<?php
/**
 * Plugin Name: WPFlat Sign
 * Description: Contracts and agreements with e-signature, sealed PDFs and an audit trail. Needs WPFlat Core.
 * Version: 0.4.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Digitalzooks
 * License: GPL-2.0-or-later
 * Text Domain: wpflatsign
 */

defined( 'ABSPATH' ) || exit;

define( 'WPFLATSIGN_VERSION', '0.4.1' );
define( 'WPFLATSIGN_FILE', __FILE__ );
define( 'WPFLATSIGN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPFLATSIGN_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'WPFLATCORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				function () {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-error"><p><strong>WPFlat Sign</strong> needs the <strong>WPFlat Core</strong> plugin. Install and activate WPFlat Core, then WPFlat Sign will start.</p></div>';
					}
				}
			);
			return;
		}
		spl_autoload_register(
			function ( $class ) {
				if ( 0 === strpos( $class, 'WPFlatSign\\Lib\\' ) ) {
					return; // the QR code library is loaded on demand by the PDF builder
				}
				if ( 0 === strpos( $class, 'WPFlatSign\\' ) ) {
					$path = WPFLATSIGN_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, 11 ) ) . '.php';
					if ( is_readable( $path ) ) {
						require_once $path;
					}
				}
			}
		);
		WPFlatSign\Module::init();
	},
	1
);
