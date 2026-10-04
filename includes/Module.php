<?php
namespace BizyDoc;

use BizyCore\Registry;

defined( 'ABSPATH' ) || exit;

/** BizyDoc' connection to BizyCore: it describes itself, then hooks into the shared places. */
class Module {

	public static function init() {
		Install::maybe_upgrade();
		add_action( 'bizycore_register', array( __CLASS__, 'register' ) );
		add_action( 'bizycore_register_terms', array( __CLASS__, 'terms' ) );
		add_action( 'bizycore_register_resources', array( Resources_Def::class, 'register' ) );
		add_action( 'bizycore_register_link_types', array( Integrations::class, 'link_types' ) );
		add_action( 'bizycore_register_notifications', array( Notify::class, 'types' ) );
		add_action( 'bizycore_register_portal', array( Portal_Page::class, 'register' ) );
		add_action( 'bizycore_register_routes', array( Routes::class, 'register' ) );
		add_filter( 'bizycore_restore_integrity', array( __CLASS__, 'restore_integrity' ), 10, 2 );
		Integrations::init();
		Notify::init();
		Legacy::init();
		Demo::init();
		if ( Registry::enabled( 'doc' ) || ! did_action( 'bizycore_ready' ) ) {
			Public_Contract::init();
		}
	}

	public static function register() {
		bizycore_register_module(
			array(
				'id'            => 'doc',
				'label'         => 'Docs',
				'version'       => BIZYDOC_VERSION,
				'requires_core' => '0.1.0',
				'desc'          => 'Contracts and agreements with e-signature, sealed PDFs and an audit trail.',
				'icon'          => 'doc',
				'route'         => '/contracts',
				'routes'        => array( 'contracts' ),
				'cap'           => 'bizydoc_contracts',
				'caps'          => array( 'bizydoc_contracts' ),
				'resources'     => array( 'contract-templates', 'contracts' ),
				'tables'        => Schema::all() ? array_map( function ( $t ) {
					return $t;
				}, Schema::all() ) : array(),
				'optional_tables' => array( 'bizydoc_otps' ),
				'options'       => array( 'bizydoc_settings', 'bizydoc_contract_next' ),
				'refs'          => array(
					'bizydoc_contracts' => array( 'client_id' => 'bizy_clients', 'template_id' => 'bizydoc_templates' ),
					'bizydoc_signers'   => array( 'contract_id' => 'bizydoc_contracts' ),
					'bizydoc_events'    => array( 'contract_id' => 'bizydoc_contracts' ),
					'bizydoc_otps'      => array( 'contract_id' => 'bizydoc_contracts' ),
				),
				'file_kinds'    => array( 'contracts' ),
				'public_paths'  => array( 'sign', 'verify', 'contract' ),
				'public_params' => array( 'bd_certificate' ),
				'roles'         => array(
					'bizy_doc_team' => array( 'label' => 'Bizy Doc Team', 'desc' => 'Contracts, templates, e-signatures.', 'caps' => array( 'bizydoc_contracts' ) ),
				),
				'client_manager_caps' => array( 'bizydoc_contracts' ),
				'manager_grants'      => array( 'doc' => array( 'label' => 'Documents', 'caps' => array( 'bizydoc_contracts' ) ) ),
				'menu'          => array(
					array( 'label' => 'Overview', 'route' => '/contracts' ),
					array( 'label' => 'All documents', 'route' => '/contracts/all' ),
					array( 'label' => 'Templates', 'route' => '/contracts/templates' ),
					array( 'label' => 'Analytics', 'route' => '/contracts/analytics' ),
				),
				'assets'        => array( 'script' => 'bizydoc.js', 'style' => 'bizydoc.css' ),
				'assets_dir'    => BIZYDOC_DIR . 'build',
				'wp'            => array( 'dashicons-media-document', 29 ),
			)
		);
	}

	public static function terms() {
		\BizyCore\Terminology::register( 'contract', 'Document', 'Documents' );
	}

	/** After a restore, say whether every signed document still verifies (its audit log chain and its sealed PDF). */
	public static function restore_integrity( $warn, $mods ) {
		global $wpdb;
		if ( ! Registry::enabled( 'doc' ) ) {
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
