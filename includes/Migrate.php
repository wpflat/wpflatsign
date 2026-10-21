<?php
namespace WPFlatSign;

defined( 'ABSPATH' ) || exit;

/**
 * WPFlat Sign used to be called BizyDoc. A site that ran BizyDoc keeps everything: its tables, settings, role, permissions and the
 * "Docs" on/off switch move to the new names once, the first time WPFlat Sign runs. Safe to run again (it does nothing the second time).
 */
class Migrate {

	const DONE = 'wpflatsign_from_bizydoc';

	public static function init() {
		if ( get_option( self::DONE ) ) {
			return;
		}
		self::data();
		add_action( 'wpflatcore_ready', array( __CLASS__, 'access' ), 5 );
	}

	/** Tables and options. Runs before WPFlat Sign creates its own tables, so nothing is created empty beside the old data. */
	private static function data() {
		global $wpdb;
		foreach ( Schema::OWN as $key => $short ) {
			$old = $wpdb->prefix . 'bizydoc_' . substr( $short, 11 ); // wpflatsign_contracts → bizydoc_contracts
			$new = Schema::table( $key );
			if ( $old !== $new && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old ) ) && ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new ) ) ) { // phpcs:ignore WordPress.DB
				$wpdb->query( 'RENAME TABLE `' . esc_sql( $old ) . '` TO `' . esc_sql( $new ) . '`' ); // phpcs:ignore WordPress.DB
			}
		}
		foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'bizydoc_' ) . '%' ) ) as $name ) { // phpcs:ignore WordPress.DB
			$new = 'wpflatsign_' . substr( $name, 8 );
			if ( false === get_option( $new ) ) {
				update_option( $new, get_option( $name ), false );
			}
			delete_option( $name );
		}
		// Tables were renamed from the old schema version: let WPFlat Sign check and complete them.
		delete_option( Install::OPTION );
	}

	/** Role, permission and the module switch. Needs WPFlat Core to be ready. */
	public static function access() {
		global $wpdb;
		if ( class_exists( '\WPFlatCore\Roles' ) ) {
			\WPFlatCore\Roles::sync(); // creates the WPFlat Sign Team role
		}
		// Any role holding the old permission gets the new one.
		foreach ( wp_roles()->role_objects as $role ) {
			if ( $role->has_cap( 'bizydoc_contracts' ) ) {
				$role->add_cap( 'wpflatsign_contracts', (bool) $role->capabilities['bizydoc_contracts'] );
				$role->remove_cap( 'bizydoc_contracts' );
			}
		}
		// People holding the old team role move to the new one.
		if ( get_role( 'wpflat_sign_team' ) && get_role( 'wpflat_doc_team' ) ) {
			foreach ( get_users( array( 'role' => 'wpflat_doc_team', 'fields' => 'ID' ) ) as $uid ) {
				$u = new \WP_User( (int) $uid );
				$u->add_role( 'wpflat_sign_team' );
				$u->remove_role( 'wpflat_doc_team' );
			}
			remove_role( 'wpflat_doc_team' );
		}
		// Permissions given to single people, and "this manager may also manage Documents".
		$caps_key = $wpdb->prefix . 'capabilities';
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key=%s AND meta_value LIKE %s", $caps_key, '%bizydoc_contracts%' ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$u = new \WP_User( (int) $r['user_id'] );
			if ( $u->has_cap( 'bizydoc_contracts' ) ) {
				$u->add_cap( 'wpflatsign_contracts' );
				$u->remove_cap( 'bizydoc_contracts' );
			}
		}
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key=%s", 'wpflatcore_manager_modules' ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$v = maybe_unserialize( $r['meta_value'] );
			if ( is_array( $v ) && in_array( 'doc', $v, true ) ) {
				update_user_meta( (int) $r['user_id'], 'wpflatcore_manager_modules', array_values( array_unique( array_map( function ( $m ) {
					return 'doc' === $m ? 'sign' : $m;
				}, $v ) ) ) );
			}
		}
		// The module on/off switch and the BizyDash import state, keyed by module id.
		$off = get_option( 'wpflatcore_modules_off', array() );
		if ( is_array( $off ) && in_array( 'doc', $off, true ) ) {
			update_option( 'wpflatcore_modules_off', array_values( array_unique( array_map( function ( $m ) {
				return 'doc' === $m ? 'sign' : $m;
			}, $off ) ) ), false );
		}
		$st = get_option( 'wpflatcore_legacy' );
		if ( is_array( $st ) && isset( $st['modules']['doc'] ) && ! isset( $st['modules']['sign'] ) ) {
			$st['modules']['sign'] = $st['modules']['doc'];
			unset( $st['modules']['doc'] );
			update_option( 'wpflatcore_legacy', $st, false );
		}
		update_option( self::DONE, 1, false );
	}
}
