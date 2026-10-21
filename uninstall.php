<?php
/** Removes WPFlat Sign data on uninstall, only when "Delete all WPFlat data when the plugin is deleted" is on in WPFlat Core's settings. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$core = get_option( 'wpflatcore_settings', array() );
if ( empty( $core['delete_on_uninstall'] ) ) {
	return;
}

foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'wpflatsign_' ) . '%' ) ) as $table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ); // phpcs:ignore WordPress.DB
}
foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'wpflatsign_' ) . '%' ) ) as $opt ) { // phpcs:ignore WordPress.DB
	delete_option( $opt );
}

// Signed PDFs live in WPFlat Core's files folder (private/contracts).
$root = rtrim( wp_normalize_path( WP_CONTENT_DIR ), '/' ) . '/wpflat-files/private';
foreach ( array( 'contracts' ) as $kind ) {
	$dir = $root . '/' . $kind;
	if ( is_dir( $dir ) ) {
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $it as $f ) {
			$f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions,WordPress.PHP.NoSilencedErrors
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions,WordPress.PHP.NoSilencedErrors
	}
}
