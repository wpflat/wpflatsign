<?php
/**
 * Plugin Name: BizySign
 * Description: Contracts and agreements with e-signature, sealed PDFs and an audit trail. Needs BizyCore.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Digitalzooks
 * License: GPL-2.0-or-later
 * Text Domain: bizysign
 */

defined( 'ABSPATH' ) || exit;

define( 'BIZYSIGN_VERSION', '0.2.0' );
define( 'BIZYSIGN_FILE', __FILE__ );
define( 'BIZYSIGN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIZYSIGN_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'BIZYCORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				function () {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-error"><p><strong>BizySign</strong> needs the <strong>BizyCore</strong> plugin. Install and activate BizyCore, then BizySign will start.</p></div>';
					}
				}
			);
			return;
		}
		spl_autoload_register(
			function ( $class ) {
				if ( 0 === strpos( $class, 'BizySign\\Lib\\' ) ) {
					return; // the QR code library is loaded on demand by the PDF builder
				}
				if ( 0 === strpos( $class, 'BizySign\\' ) ) {
					$path = BIZYSIGN_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';
					if ( is_readable( $path ) ) {
						require_once $path;
					}
				}
			}
		);
		BizySign\Module::init();
	},
	1
);
