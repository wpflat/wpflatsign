<?php
namespace WPFlatSign;

use WPFlatCore\Registry;

defined( 'ABSPATH' ) || exit;

/** WPFlat Sign' connection to WPFlat Core: it describes itself, then hooks into the shared places. */
class Module {

	public static function init() {
		Migrate::init();
		Install::maybe_upgrade();
		add_action( 'wpflatcore_register', array( __CLASS__, 'register' ) );
		add_action( 'wpflatcore_register_terms', array( __CLASS__, 'terms' ) );
		add_action( 'wpflatcore_register_resources', array( Resources_Def::class, 'register' ) );
		add_action( 'wpflatcore_register_link_types', array( Integrations::class, 'link_types' ) );
		add_action( 'wpflatcore_register_notifications', array( Notify::class, 'types' ) );
		add_action( 'wpflatcore_register_portal', array( Portal_Page::class, 'register' ) );
		add_action( 'wpflatcore_register_routes', array( Routes::class, 'register' ) );
		add_filter( 'wpflatcore_restore_integrity', array( __CLASS__, 'restore_integrity' ), 10, 2 );
		Integrations::init();
		Notify::init();
		Legacy::init();
		Demo::init();
		if ( Registry::enabled( 'sign' ) || ! did_action( 'wpflatcore_ready' ) ) {
			Public_Contract::init();
		}
	}

	public static function register() {
		wpflatcore_register_module(
			array(
				'id'            => 'sign',
				'label'         => 'Sign',
				'version'       => WPFLATSIGN_VERSION,
				'requires_core' => '0.6.0',
				'desc'          => 'Contracts and agreements with e-signature, sealed PDFs and an audit trail.',
				'icon'          => 'sign',
				'route'         => '/contracts',
				'routes'        => array( 'contracts' ),
				'cap'           => 'wpflatsign_contracts',
				'caps'          => array( 'wpflatsign_contracts' ),
				'resources'     => array( 'contract-templates', 'contracts' ),
				'tables'        => Schema::all() ? array_map( function ( $t ) {
					return $t;
				}, Schema::all() ) : array(),
				'optional_tables' => array( 'wpflatsign_otps' ),
				'options'       => array( 'wpflatsign_settings', 'wpflatsign_contract_next' ),
				'refs'          => array(
					'wpflatsign_contracts' => array( 'client_id' => 'wpflat_clients', 'template_id' => 'wpflatsign_templates' ),
					'wpflatsign_signers'   => array( 'contract_id' => 'wpflatsign_contracts' ),
					'wpflatsign_events'    => array( 'contract_id' => 'wpflatsign_contracts' ),
					'wpflatsign_otps'      => array( 'contract_id' => 'wpflatsign_contracts' ),
				),
				'file_kinds'    => array( 'contracts' ),
				'public_paths'  => array( 'sign', 'verify', 'contract' ),
				'public_params' => array( 'bd_certificate' ),
				'roles'         => array(
					'wpflat_sign_team' => array( 'label' => 'Sign Team', 'desc' => 'Contracts, templates, e-signatures.', 'caps' => array( 'wpflatsign_contracts' ) ),
				),
				'client_manager_caps' => array( 'wpflatsign_contracts' ),
				'manager_grants'      => array( 'sign' => array( 'label' => 'Documents', 'caps' => array( 'wpflatsign_contracts' ) ) ),
				'menu'          => array(
					array( 'label' => 'Overview', 'route' => '/contracts' ),
					array( 'label' => 'All documents', 'route' => '/contracts/all' ),
					array( 'label' => 'Templates', 'route' => '/contracts/templates' ),
					array( 'label' => 'Analytics', 'route' => '/contracts/analytics' ),
				),
				'assets'        => array( 'script' => 'wpflatsign.js', 'style' => 'wpflatsign.css' ),
				'assets_dir'    => WPFLATSIGN_DIR . 'build',
				'wp'            => array( 'dashicons-media-document', 29 ),
			)
		);
	}

	public static function terms() {
		\WPFlatCore\Terminology::register( 'contract', 'Document', 'Documents' );
	}

	/** After a restore, say whether every signed document still verifies (its audit log chain and its sealed PDF). */
	public static function restore_integrity( $warn, $mods ) {
		global $wpdb;
		if ( ! Registry::enabled( 'sign' ) ) {
			return $warn;
		}
		$bad = array();
		foreach ( (array) $wpdb->get_col( 'SELECT id FROM ' . Schema::table( 'contracts' ) . " WHERE status='signed'" ) as $id ) { // phpcs:ignore WordPress.DB
			$chain = Services\Contracts::verify_chain( (int) $id );
			$int   = Services\Contracts::integrity( (int) $id );
			if ( empty( $chain['ok'] ) || ( is_array( $int ) && isset( $int['ok'] ) && ! $int['ok'] ) ) {
				$bad[] = $id;
			}
		}
		if ( $bad ) {
			$warn[] = 'Signed documents that no longer verify after the restore: #' . implode( ', #', array_slice( $bad, 0, 10 ) ) . '. Their audit log or sealed PDF does not match.';
		}
		return $warn;
	}
}
