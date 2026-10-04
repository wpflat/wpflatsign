<?php
/**
 * Plugin Name: BizyDoc
 * Description: Contracts and agreements with e-signature, sealed PDFs and an audit trail. Needs BizyCore.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Digitalzooks
 * License: GPL-2.0-or-later
 * Text Domain: bizydoc
 */

defined( 'ABSPATH' ) || exit;

define( 'BIZYDOC_VERSION', '0.1.0' );
define( 'BIZYDOC_FILE', __FILE__ );
define( 'BIZYDOC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIZYDOC_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'BIZYCORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				function () {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-error"><p><strong>BizyDoc</strong> needs the <strong>BizyCore</strong> plugin. Install and activate BizyCore, then BizyDoc will start.</p></div>';
					}
				}
			);
			return;
		}
		spl_autoload_register(
			function ( $class ) {
				if ( 0 === strpos( $class, 'BizyDoc\\Lib\\' ) ) {
					return; // the QR code library is loaded on demand by the PDF builder
				}
				if ( 0 === strpos( $class, 'BizyDoc\\' ) ) {
					$path = BIZYDOC_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, 8 ) ) . '.php';
					if ( is_readable( $path ) ) {
						require_once $path;
					}
				}
			}
		);
		BizyDoc\Module::init();
	},
	1
);
