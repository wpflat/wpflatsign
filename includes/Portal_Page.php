<?php
namespace BizySign;

use BizyCore\Portal;
use BizyCore\Services\Portal_Users;
use BizySign\Services\Contract_Pdf;
use BizySign\Services\Contracts;

defined( 'ABSPATH' ) || exit;

/** The client portal's "Documents" page (contracts to sign, signed copies). */
class Portal_Page {

	public static function register() {
		Portal::register_page( 'contracts', array(
			'module'   => 'sign',
			'label'    => 'Documents',
			'order'    => 30,
			'render'   => array( __CLASS__, 'render_contracts' ),
			'overview' => array( __CLASS__, 'overview' ),
		) );
	}

	private static function badge( $s ) {
		$m = array( 'sent' => array( 'Awaiting signatures', 'info' ), 'partially_signed' => array( 'Partly signed', 'warning' ), 'signed' => array( 'Signed', 'success' ), 'declined' => array( 'Declined', 'danger' ), 'expired' => array( 'Expired', 'neutral' ) );
		$x = isset( $m[ $s ] ) ? $m[ $s ] : array( ucfirst( str_replace( '_', ' ', $s ) ), 'neutral' );
		return Portal::badge( $x[0], $x[1] );
	}

	/** Contracts that have been sent (never drafts or voided ones). `$email` marks the ones this person still has to sign. */
	public static function contracts( $client_id, $email = '' ) {
		global $wpdb;
		$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, number, title, status, sent_at, completed_at, pdf_file, expires_on FROM ' . Schema::table( 'contracts' ) . " WHERE client_id=%d AND status NOT IN ('draft','void') ORDER BY COALESCE(completed_at, sent_at, created_at) DESC, id DESC", $client_id ), ARRAY_A ); // phpcs:ignore WordPress.DB
		foreach ( $rows as &$r ) {
			$r['id']       = (int) $r['id'];
			$r['signers']  = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT name, role, status, signed_at FROM ' . Schema::table( 'contract_signers' ) . ' WHERE contract_id=%d ORDER BY sort_order, id', $r['id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB
			$r['sign_url'] = '';
			if ( '' !== $email && in_array( $r['status'], array( 'sent', 'partially_signed' ), true ) ) {
				$tok = $wpdb->get_row( $wpdb->prepare( 'SELECT token FROM ' . Schema::table( 'contract_signers' ) . " WHERE contract_id=%d AND email=%s AND status NOT IN ('signed','declined') LIMIT 1", $r['id'], $email ), ARRAY_A ); // phpcs:ignore WordPress.DB
				if ( $tok ) {
					$r['sign_url'] = Contracts::sign_url( $tok['token'] );
				}
			}
			$r['has_pdf']  = 'signed' === $r['status'] && '' !== (string) $r['pdf_file'];
			$cert          = 'signed' === $r['status'] ? Contracts::cert_id( $r['id'] ) : '';
			$r['cert_url'] = $cert ? Contracts::verify_url( $cert ) : '';
			unset( $r['pdf_file'] );
		}
		return $rows;
	}

	public static function overview( $ctx ) {
		$c    = $ctx['client'];
		$ct   = self::contracts( $c['id'], $ctx['preview'] ? '' : $ctx['user']->user_email );
		$wait = array_filter( $ct, function ( $x ) {
			return '' !== $x['sign_url'];
		} );
		$cards = '<a class="stat" href="' . esc_url( Portal::link( 'contracts' ) ) . '"><span class="stat__l">Documents</span><span class="stat__v">' . count( $ct ) . '</span><span class="stat__s">' . ( $wait ? Portal::e( count( $wait ) . ' waiting for your signature' ) : 'nothing waiting for you' ) . '</span></a>';
		$attn  = '';
		foreach ( $wait as $w ) {
			$attn .= '<li><a href="' . esc_url( $w['sign_url'] ) . '"><strong>' . Portal::e( $w['title'] ) . '</strong> needs your signature</a><span>' . Portal::e( $w['number'] ) . '</span></li>';
		}
		return array( 'cards' => $cards, 'attention' => $attn, 'panels' => '' );
	}

	public static function render_contracts( $ctx, $id, $sub ) {
		$c = $ctx['client'];
		if ( $id && 'pdf' === $sub ) {
			self::stream_pdf( $c['id'], $id );
		}
		$ct = self::contracts( $c['id'], $ctx['preview'] ? '' : $ctx['user']->user_email );
		$h  = '<h1>Documents</h1><p class="lead">Agreements between us, and their signing status.</p>';
		if ( ! $ct ) {
			$h .= Portal::empty_state( 'Nothing yet', 'Documents we send you to sign will appear here, and signed copies can be downloaded.' );
		}
		foreach ( $ct as $x ) {
			$h .= '<section class="panel"><div class="head"><div><strong>' . Portal::e( $x['title'] ) . '</strong><div class="muted">' . Portal::e( $x['number'] ) . ( $x['sent_at'] ? ' · sent ' . Portal::e( Portal::date( $x['sent_at'] ) ) : '' ) . ( $x['completed_at'] ? ' · signed ' . Portal::e( Portal::date( $x['completed_at'] ) ) : '' ) . '</div></div>' . self::badge( $x['status'] ) . '</div>';
			if ( $x['signers'] ) {
				$h .= '<ul class="signers">';
				foreach ( $x['signers'] as $s ) {
					$h .= '<li><span>' . Portal::e( $s['name'] ) . ( $s['role'] ? ' <span class="muted">· ' . Portal::e( $s['role'] ) . '</span>' : '' ) . '</span><span>' . ( 'signed' === $s['status'] ? Portal::badge( 'Signed ' . Portal::date( $s['signed_at'] ), 'success' ) : ( 'declined' === $s['status'] ? Portal::badge( 'Declined', 'danger' ) : Portal::badge( 'Waiting', 'neutral' ) ) ) . '</span></li>';
				}
				$h .= '</ul>';
			}
			$h .= '<div class="actions">';
			if ( $x['sign_url'] ) {
				$h .= '<a class="btn" href="' . esc_url( $x['sign_url'] ) . '">Review and sign</a>';
			}
			if ( $x['has_pdf'] ) {
				$h .= '<a class="btn btn--ghost" href="' . esc_url( Portal::link( 'contracts/' . $x['id'] . '/pdf' ) ) . '">Download signed PDF</a>';
			}
			if ( $x['cert_url'] ) {
				$h .= '<a class="btn btn--ghost" href="' . esc_url( $x['cert_url'] ) . '">Verify authenticity</a>';
			}
			$h .= '</div></section>';
		}
		return $h;
	}

	private static function stream_pdf( $client_id, $id ) {
		global $wpdb;
		$c = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Schema::table( 'contracts' ) . " WHERE id=%d AND client_id=%d AND status='signed'", $id, $client_id ), ARRAY_A ); // phpcs:ignore WordPress.DB
		if ( ! $c || ! Contract_Pdf::verify( $c ) ) {
			status_header( 404 );
			wp_die( 'That document is not available for download.', 'Not available', array( 'response' => 404 ) );
		}
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		$path = Contract_Pdf::path( $c );
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', Contract_Pdf::filename( $c ) ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
