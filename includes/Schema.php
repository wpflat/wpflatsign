<?php
namespace BizySign;

defined( 'ABSPATH' ) || exit;

/**
 * Table names. BizySign' own tables are bizysign_*; the code still asks for the short old names (contracts,
 * contract_signers…) and this maps them to the suite's tables. Core's tables are bizy_*.
 */
class Schema {

	/** old short name => BizySign table (without the WordPress prefix). */
	const OWN = array(
		'contract_templates' => 'bizysign_templates',
		'contracts'          => 'bizysign_contracts',
		'contract_signers'   => 'bizysign_signers',
		'contract_events'    => 'bizysign_events',
		'contract_otps'      => 'bizysign_otps',
	);

	const CORE = array( 'clients' => 'bizy_clients', 'brands' => 'bizy_departments', 'departments' => 'bizy_departments', 'client_brands' => 'bizy_client_departments', 'client_departments' => 'bizy_client_departments', 'client_contacts' => 'bizy_client_contacts', 'team_members' => 'bizy_team_members', 'activity' => 'bizy_activity', 'lookups' => 'bizy_lookups' );

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

	/** All of BizySign' table names, without the prefix. */
	public static function all() {
		return array_values( self::OWN );
	}
}
