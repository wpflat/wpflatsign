<?php
namespace WPFlatSign;

use WPFlatCore\Links;
use WPFlatCore\Registry;
use WPFlatCore\Terminology;

defined( 'ABSPATH' ) || exit;

/** How WPFlat Sign shows up in WPFlat Core's shared places: links, search, messages, Home, department pages. */
class Integrations {

	public static function init() {
		add_filter( 'wpflatcore_search', array( __CLASS__, 'search' ), 10, 2 );
		add_filter( 'wpflatcore_message_contexts', array( __CLASS__, 'message_contexts' ), 10, 2 );
		add_filter( 'wpflatcore_message_context_name', array( __CLASS__, 'message_context_name' ), 10, 3 );
		add_filter( 'wpflatcore_home', array( __CLASS__, 'home' ) );
		add_filter( 'wpflatcore_department_card', array( __CLASS__, 'department_card' ), 10, 4 );
		add_filter( 'wpflatcore_department_detail', array( __CLASS__, 'department_detail' ), 10, 4 );
		add_filter( 'wpflatcore_lookup_defaults', array( __CLASS__, 'lookups' ) );
		add_filter( 'wpflatcore_meta', array( __CLASS__, 'meta' ) );
		add_action( 'wpflatcore_client_deleted', array( __CLASS__, 'client_deleted' ) );
	}

	private static function on() {
		return Registry::enabled( 'sign' );
	}

	private static function can() {
		return current_user_can( 'manage_options' ) || current_user_can( 'wpflatsign_contracts' );
	}

	/** The kinds of document WPFlat Sign keeps in WPFlat Core's lists. */
	public static function lookups( $d ) {
		$d['doc_type']      = array(
			array( 'contract', 'Contract', 0, '#2563eb' ),
			array( 'agreement', 'Agreement', 0, '#16a34a' ),
			array( 'nda', 'NDA', 0, '#7c3aed' ),
			array( 'approval', 'Approval', 0, '#d97706' ),
			array( 'other', 'Other', 0, '#6b7280' ),
		);
		return $d;
	}

	/** WPFlat Sign' own settings reach the browser app in meta.settings. */
	public static function meta( $m ) {
		if ( ! self::on() ) {
			return $m;
		}
		$m['settings'] = array_merge( isset( $m['settings'] ) ? (array) $m['settings'] : array(), array_intersect_key( Settings::get(), Settings::defaults() ) );
		return $m;
	}

	public static function client_deleted( $client_id ) {
		global $wpdb;
		// Contracts that are out for signature or signed are legal records: they stay, only detached from the deleted client.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . Schema::table( 'contracts' ) . " SET client_id=0 WHERE client_id=%d AND status<>'draft'", (int) $client_id ) ); // phpcs:ignore WordPress.DB
		foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . Schema::table( 'contracts' ) . " WHERE client_id=%d AND status='draft'", (int) $client_id ) ) as $id ) { // phpcs:ignore WordPress.DB
			foreach ( array( 'contract_signers', 'contract_events', 'contract_otps' ) as $t ) {
				$wpdb->delete( Schema::table( $t ), array( 'contract_id' => (int) $id ) );
			}
			$wpdb->delete( Schema::table( 'contracts' ), array( 'id' => (int) $id ) );
		}
	}

	/* ---- links ---- */

	public static function link_types() {
		Links::register_type( 'contract', array( 'module' => 'sign', 'label' => 'Document', 'plural' => 'Documents', 'resolve' => array( __CLASS__, 'resolve_contract' ), 'search' => array( __CLASS__, 'search_contracts' ) ) );
	}

	public static function resolve_contract( $id ) {
		global $wpdb;
		$c = $wpdb->get_row( $wpdb->prepare( 'SELECT id, number, title, status FROM ' . Schema::table( 'contracts' ) . ' WHERE id=%d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB
		return $c ? array( 'title' => $c['number'] . ' — ' . $c['title'], 'url' => '#/contracts/' . (int) $id, 'sub' => ucfirst( str_replace( '_', ' ', $c['status'] ) ) ) : null;
	}

	public static function search_contracts( $q, $n ) {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, number, title, status FROM ' . Schema::table( 'contracts' ) . ' WHERE number LIKE %s OR title LIKE %s ORDER BY id DESC LIMIT %d', $like, $like, $n ), ARRAY_A ); // phpcs:ignore WordPress.DB
		return array_map( function ( $r ) {
			return array( 'id' => (int) $r['id'], 'title' => $r['number'] . ' — ' . $r['title'], 'sub' => $r['status'] );
		}, (array) $rows );
	}

	/* ---- search, messages, Home ---- */

	public static function search( $out, $q ) {
		global $wpdb;
		if ( ! self::on() || ! self::can() ) {
			return $out;
		}
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT t.id, t.number, t.title, c.name AS client_name FROM ' . Schema::table( 'contracts' ) . ' t LEFT JOIN ' . Schema::table( 'clients' ) . ' c ON c.id=t.client_id WHERE t.number LIKE %s OR t.title LIKE %s ORDER BY t.id DESC LIMIT 5', $like, $like ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$out[] = array( 'type' => 'contract', 'kind' => Terminology::label( 'contract' ), 'id' => (int) $r['id'], 'label' => $r['number'], 'sub' => trim( $r['title'] . ' · ' . $r['client_name'], ' ·' ), 'route' => '/contracts/' . (int) $r['id'] );
		}
		return $out;
	}

	public static function message_contexts( $list, $client_id ) {
		global $wpdb;
		if ( ! self::on() ) {
			return $list;
		}
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, number, title FROM ' . Schema::table( 'contracts' ) . " WHERE client_id=%d AND status NOT IN ('draft','void') ORDER BY id DESC LIMIT 30", (int) $client_id ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$list[] = array( 'type' => 'contract', 'id' => (int) $r['id'], 'name' => $r['number'] . ' — ' . $r['title'], 'group' => 'Documents' );
		}
		return $list;
	}

	public static function message_context_name( $name, $type, $id ) {
		global $wpdb;
		if ( 'contract' !== $type || ! self::on() ) {
			return $name;
		}
		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT number FROM ' . Schema::table( 'contracts' ) . ' WHERE id=%d', $id ) ); // phpcs:ignore WordPress.DB
	}

	/** Home: documents waiting for signatures, and the ones that have waited too long. */
	public static function home( $home ) {
		global $wpdb;
		if ( ! self::on() || ! self::can() ) {
			return $home;
		}
		$ct = Schema::table( 'contracts' );
		$home['stats']['contracts_waiting'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $ct WHERE status IN ('sent','partially_signed')" ); // phpcs:ignore WordPress.DB
		$old = gmdate( 'Y-m-d H:i:s', time() - 5 * DAY_IN_SECONDS );
		$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT t.id, t.number, t.title, t.sent_at, c.name AS client_name FROM ' . $ct . ' t LEFT JOIN ' . Schema::table( 'clients' ) . " c ON c.id=t.client_id WHERE t.status IN ('sent','partially_signed') AND t.sent_at < %s ORDER BY t.sent_at ASC LIMIT 5", $old ), ARRAY_A ); // phpcs:ignore WordPress.DB
		foreach ( $rows as $r ) {
			$home['todo'][] = array( 'kind' => 'contract', 'title' => 'Nudge ' . $r['number'], 'sub' => $r['title'] . ( $r['client_name'] ? ' · ' . $r['client_name'] : '' ) . ' — still waiting for signatures', 'link' => '/contracts/' . (int) $r['id'], 'days' => (int) floor( ( time() - strtotime( $r['sent_at'] ) ) / DAY_IN_SECONDS ) );
		}
		$home['setup'][] = array( 'key' => 'templates', 'label' => 'Create a document template', 'to' => '/contracts/templates', 'done' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Schema::table( 'contract_templates' ) ) > 0 ); // phpcs:ignore WordPress.DB
		return $home;
	}

	/* ---- department pages ---- */

	private static function dept_docs( $id ) {
		global $wpdb;
		$ct = Schema::table( 'contracts' );
		$by = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) n FROM $ct WHERE department_id=%d GROUP BY status", $id ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$by[ $r['status'] ] = (int) $r['n'];
		}
		return array(
			'by_status' => $by,
			'waiting'   => ( isset( $by['sent'] ) ? $by['sent'] : 0 ) + ( isset( $by['partially_signed'] ) ? $by['partially_signed'] : 0 ),
			'recent'    => array_map( function ( $r ) {
				return array( 'id' => (int) $r['id'], 'number' => $r['number'], 'title' => $r['title'], 'client_name' => $r['client_name'], 'status' => $r['status'], 'sent_at' => $r['sent_at'] );
			}, (array) $wpdb->get_results( $wpdb->prepare( "SELECT t.id, t.number, t.title, t.status, t.sent_at, c.name AS client_name FROM $ct t LEFT JOIN " . Schema::table( 'clients' ) . ' c ON c.id=t.client_id WHERE t.department_id=%d ORDER BY t.id DESC LIMIT 8', $id ), ARRAY_A ) ), // phpcs:ignore WordPress.DB
		);
	}

	public static function department_card( $card, $id, $from, $to ) {
		if ( self::on() && self::can() ) {
			$card['modules']['sign'] = array( 'waiting' => self::dept_docs( $id )['waiting'] );
		}
		return $card;
	}

	public static function department_detail( $data, $id, $from, $to ) {
		if ( self::on() && self::can() ) {
			$data['modules']['sign'] = self::dept_docs( $id );
		}
		return $data;
	}
}
