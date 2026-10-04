<?php
namespace BizyDocs;

use BizyCore\Rest\Resource_Controller;
use BizyCore\Rest\Routes as Core_Routes;
use BizyDocs\Services\ContractAnalytics;
use BizyDocs\Services\Contract_Pdf;
use BizyDocs\Services\Contracts;
use BizyDocs\Services\Docx_Import;

defined( 'ABSPATH' ) || exit;

/** BizyDocs' REST routes (in BizyCore's bizycore/v1 namespace, gone when the module is off). */
class Routes {

	private static function reg( $route, $args ) {
		Core_Routes::add( 'docs', $route, $args );
	}

	private static function out( $x, $ok = null ) {
		return is_wp_error( $x ) ? $x : rest_ensure_response( null === $ok ? $x : $ok );
	}

	public static function register() {
		$perm = array( Resource_Controller::class, 'can' );
		$open = '__return_true'; // signers are not logged in; the unguessable link plus an emailed one-time code authorise them.

		self::reg( '/contracts/(?P<id>\d+)/send', array( 'methods' => 'POST', 'callback' => function ( $r ) { return self::out( Contracts::send( (int) $r['id'] ) ); }, 'permission_callback' => $perm ) );
		self::reg( '/contracts/(?P<id>\d+)/remind', array( 'methods' => 'POST', 'callback' => function ( $r ) { return self::out( Contracts::remind( (int) $r['id'] ) ); }, 'permission_callback' => $perm ) );
		self::reg( '/contracts/(?P<id>\d+)/void', array( 'methods' => 'POST', 'callback' => function ( $r ) { $p = (array) $r->get_json_params(); return self::out( Contracts::void( (int) $r['id'], isset( $p['reason'] ) ? (string) $p['reason'] : '' ), array( 'ok' => true ) ); }, 'permission_callback' => $perm ) );
		self::reg( '/contracts/(?P<id>\d+)/audit', array( 'methods' => 'GET', 'callback' => function ( $r ) {
			$id = (int) $r['id'];
			return rest_ensure_response( array( 'events' => Contracts::events( $id ), 'otps' => Contracts::otps( $id ), 'chain' => Contracts::verify_chain( $id ), 'integrity' => Contracts::integrity( $id ), 'verify_url' => Contracts::verify_url( Contracts::cert_id( $id ) ), 'certificate_url' => home_url( '/?bd_certificate=' . $id ) ) );
		}, 'permission_callback' => $perm ) );
		self::reg( '/contracts/tokens', array( 'methods' => 'GET', 'callback' => function () { return rest_ensure_response( Contracts::tokens_help() ); }, 'permission_callback' => $perm ) );
		self::reg( '/contracts/(?P<id>\d+)/pdf', array( 'methods' => 'POST', 'callback' => function ( $r ) { $x = Contract_Pdf::generate( (int) $r['id'] ); return self::out( $x, is_wp_error( $x ) ? null : array( 'sha256' => $x['sha256'], 'size' => $x['size'] ) ); }, 'permission_callback' => $perm ) );
		self::reg( '/contracts/(?P<id>\d+)/preview', array( 'methods' => 'POST', 'callback' => function ( $r ) {
			$id = (int) $r['id'];
			if ( ! Contracts::get( $id ) ) {
				return new \WP_Error( 'bizydocs_not_found', 'Not found.', array( 'status' => 404 ) );
			}
			$p = (array) $r->get_json_params();
			if ( isset( $p['access'] ) ) {
				Contracts::set_view_access( $id, (string) $p['access'] );
			}
			$slug = Contracts::ensure_preview( $id, ! empty( $p['regenerate'] ) );
			$c    = Contracts::get( $id );
			return rest_ensure_response( array( 'url' => Contracts::preview_url( $slug ), 'access' => $c['view_access'] ) );
		}, 'permission_callback' => $perm ) );
		self::reg( '/contracts/import', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'import' ), 'permission_callback' => $perm ) );
		self::reg( '/contracts/analytics', array( 'methods' => 'GET', 'callback' => function ( \WP_REST_Request $req ) {
			return rest_ensure_response( ContractAnalytics::query( array( 'from' => (string) $req->get_param( 'from' ), 'to' => (string) $req->get_param( 'to' ), 'department_id' => (string) $req->get_param( 'department_id' ) ) ) );
		}, 'permission_callback' => $perm ) );
		self::reg( '/contracts/overview', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'overview' ), 'permission_callback' => $perm ) );

		self::reg( '/sign/(?P<token>[a-z0-9]{10,64})/otp', array( 'methods' => 'POST', 'callback' => function ( $r ) { return self::out( Contracts::request_otp( $r['token'], (array) $r->get_json_params() ) ); }, 'permission_callback' => $open ) );
		self::reg( '/sign/(?P<token>[a-z0-9]{10,64})/submit', array( 'methods' => 'POST', 'callback' => function ( $r ) { return self::out( Contracts::sign( $r['token'], (array) $r->get_json_params() ) ); }, 'permission_callback' => $open ) );
		self::reg( '/sign/(?P<token>[a-z0-9]{10,64})/decline', array( 'methods' => 'POST', 'callback' => function ( $r ) { $p = (array) $r->get_json_params(); return self::out( Contracts::decline( $r['token'], isset( $p['reason'] ) ? (string) $p['reason'] : '' ), array( 'ok' => true ) ); }, 'permission_callback' => $open ) );

		self::reg( '/docs/settings', array(
			array( 'methods' => 'GET', 'callback' => function () { return rest_ensure_response( array_intersect_key( Settings::get(), Settings::defaults() ) ); }, 'permission_callback' => $perm ),
			array( 'methods' => 'POST', 'callback' => function ( $r ) { return rest_ensure_response( Settings::update( (array) $r->get_json_params() ) ); }, 'permission_callback' => array( Core_Routes::class, 'can_admin' ) ),
		) );
	}

	/** Word file upload or Google Docs link → clean document HTML. */
	public static function import( \WP_REST_Request $req ) {
		$files = $req->get_file_params();
		if ( ! empty( $files['file']['tmp_name'] ) && is_uploaded_file( $files['file']['tmp_name'] ) ) {
			$name = isset( $files['file']['name'] ) ? sanitize_file_name( $files['file']['name'] ) : 'document.docx';
			if ( ! preg_match( '/\.docx?$/i', $name ) ) {
				return new \WP_Error( 'bizydocs_import', 'Please choose a Word file (.doc or .docx).', array( 'status' => 400 ) );
			}
			$res = Docx_Import::from_file( $files['file']['tmp_name'], trim( str_replace( array( '-', '_' ), ' ', preg_replace( '/\.docx?$/i', '', $name ) ) ) );
		} elseif ( $req->get_param( 'url' ) ) {
			$res = Docx_Import::from_google( (string) $req->get_param( 'url' ) );
		} else {
			return new \WP_Error( 'bizydocs_import', 'Choose a Word file or paste a Google Docs link.', array( 'status' => 400 ) );
		}
		return self::out( $res );
	}

	/** Documents → Overview: what is waiting on whom, what just got signed. */
	public static function overview() {
		global $wpdb;
		$ct = Schema::table( 'contracts' );
		$sg = Schema::table( 'contract_signers' );
		$cl = Schema::table( 'clients' );
		$by = array();
		foreach ( (array) $wpdb->get_results( "SELECT status, COUNT(*) n FROM $ct GROUP BY status", ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
			$by[ $r['status'] ] = (int) $r['n'];
		}
		$month = gmdate( 'Y-m-01 00:00:00' );
		$stats = array(
			'drafts'       => isset( $by['draft'] ) ? $by['draft'] : 0,
			'awaiting'     => ( isset( $by['sent'] ) ? $by['sent'] : 0 ) + ( isset( $by['partially_signed'] ) ? $by['partially_signed'] : 0 ),
			'signed'       => isset( $by['signed'] ) ? $by['signed'] : 0,
			'signed_month' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ct WHERE status='signed' AND completed_at >= %s", $month ) ), // phpcs:ignore WordPress.DB
			'declined'     => ( isset( $by['declined'] ) ? $by['declined'] : 0 ) + ( isset( $by['void'] ) ? $by['void'] : 0 ),
			'total'        => array_sum( $by ),
		);
		$sel = "t.id, t.number, t.title, t.status, t.sent_at, t.completed_at, c.name AS client_name, (SELECT COUNT(*) FROM $sg s WHERE s.contract_id=t.id) AS signer_total, (SELECT COUNT(*) FROM $sg s2 WHERE s2.contract_id=t.id AND s2.status='signed') AS signer_done, (SELECT GROUP_CONCAT(s3.name SEPARATOR ', ') FROM $sg s3 WHERE s3.contract_id=t.id AND s3.status IN ('pending','viewed')) AS waiting_on";
		$fmt = function ( $rows ) {
			foreach ( $rows as &$r ) {
				$r['signer_total'] = (int) $r['signer_total'];
				$r['signer_done']  = (int) $r['signer_done'];
			}
			return $rows;
		};
		$q = function ( $where, $order ) use ( $wpdb, $sel, $ct, $cl, $fmt ) {
			return $fmt( (array) $wpdb->get_results( "SELECT $sel FROM $ct t LEFT JOIN $cl c ON c.id=t.client_id WHERE $where ORDER BY $order LIMIT 8", ARRAY_A ) ); // phpcs:ignore WordPress.DB
		};
		return rest_ensure_response( array(
			'stats'    => $stats,
			'awaiting' => $q( "t.status IN ('sent','partially_signed')", 't.sent_at ASC' ),
			'signed'   => $q( "t.status='signed'", 't.completed_at DESC' ),
			'drafts'   => $q( "t.status='draft'", 't.id DESC' ),
		) );
	}
}
