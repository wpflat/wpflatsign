<?php
namespace WPFlatSign;

defined( 'ABSPATH' ) || exit;

/**
 * Table names. WPFlat Sign' own tables are wpflatsign_*; the code still asks for the short old names (contracts,
 * contract_signers…) and this maps them to the suite's tables. Core's tables are wpflat_*.
 */
class Schema {

	/** old short name => WPFlat Sign table (without the WordPress prefix). */
	const OWN = array(
		'contract_templates' => 'wpflatsign_templates',
		'contracts'          => 'wpflatsign_contracts',
		'contract_signers'   => 'wpflatsign_signers',
		'contract_events'    => 'wpflatsign_events',
		'contract_otps'      => 'wpflatsign_otps',
	);

	const CORE = array( 'clients' => 'wpflat_clients', 'brands' => 'wpflat_departments', 'departments' => 'wpflat_departments', 'client_brands' => 'wpflat_client_departments', 'client_departments' => 'wpflat_client_departments', 'client_contacts' => 'wpflat_client_contacts', 'team_members' => 'wpflat_team_members', 'activity' => 'wpflat_activity', 'lookups' => 'wpflat_lookups' );

	public static function table( $name ) {
		global $wpdb;
		if ( isset( self::OWN[ $name ] ) ) {
			return $wpdb->prefix . self::OWN[ $name ];
		}
		if ( isset( self::CORE[ $name ] ) ) {
			return $wpdb->prefix . self::CORE[ $name ];
		}
		return $wpdb->prefix . $name;
	}

	/** All of WPFlat Sign' table names, without the prefix. */
	public static function all() {
		return array_values( self::OWN );
	}
}
