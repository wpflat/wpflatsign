<?php
/** Removes BizySign data on uninstall, only when "Delete all Bizy data when the plugin is deleted" is on in BizyCore's settings. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$core = get_option( 'bizycore_settings', array() );
if ( empty( $core['delete_on_uninstall'] ) ) {
	return;
}

foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'bizysign_' ) . '%' ) ) as $table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ); // phpcs:ignore WordPress.DB
}
foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'bizysign_' ) . '%' ) ) as $opt ) { // phpcs:ignore WordPress.DB
	delete_option( $opt );
}

// Signed PDFs live in BizyCore's files folder (private/contracts).
$root = rtrim( wp_normalize_path( WP_CONTENT_DIR ), '/' ) . '/bizy-files/private';
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
