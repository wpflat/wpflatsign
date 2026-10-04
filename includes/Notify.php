<?php
namespace BizyDocs;

use BizyCore\Portal;
use BizyCore\Services\Notifications as N;

defined( 'ABSPATH' ) || exit;

/** The notifications BizyDocs raises: a document waiting for signature, signed and declined. */
class Notify {

	public static function init() {
		add_action( 'bizydocs_contract_event', array( __CLASS__, 'on_contract_event' ), 10, 4 );
	}

	public static function types() {
		N::register( 'client_contract_sent', array( 'label' => 'Document', 'audience' => 'client', 'group' => 'Documents', 'description' => 'A document is waiting for signature', 'email' => 'off', 'external' => true ) );
		N::register( 'client_contract_signed', array( 'label' => 'Document', 'audience' => 'client', 'group' => 'Documents', 'description' => 'A document has been signed by everyone', 'email' => 'off', 'external' => true ) );
		N::register( 'team_contract_signed', array( 'label' => 'Document', 'audience' => 'team', 'group' => 'Documents', 'description' => 'A document you created is fully signed', 'email' => 'instant' ) );
		N::register( 'team_contract_declined', array( 'label' => 'Document', 'audience' => 'team', 'group' => 'Documents', 'description' => 'A signer declined a document', 'email' => 'instant' ) );
	}

	public static function on_contract_event( $contract_id, $signer_id, $event, $detail ) {
		global $wpdb;
		if ( ! in_array( $event, array( 'sent', 'completed', 'declined' ), true ) ) {
			return;
		}
		$c = $wpdb->get_row( $wpdb->prepare( 'SELECT id, number, title, client_id, created_by FROM ' . Schema::table( 'contracts' ) . ' WHERE id=%d', $contract_id ), ARRAY_A ); // phpcs:ignore WordPress.DB
		if ( ! $c ) {
			return;
		}
		$staff = array_filter( array( (int) $c['created_by'] ) );
		if ( 'sent' === $event && $c['client_id'] ) {
			N::emit( 'client_contract_sent', N::client_users( $c['client_id'] ), 'Document waiting for you: ' . $c['title'], $c['number'] . ' has been sent for signature.', Portal::url( 'contracts' ), $c['client_id'], 'contract', $c['id'], 'csent:' . $c['id'] );
		} elseif ( 'completed' === $event ) {
			if ( $c['client_id'] ) {
				N::emit( 'client_contract_signed', N::client_users( $c['client_id'] ), 'Signed: ' . $c['title'], 'Everyone has signed. You can download the signed copy.', Portal::url( 'contracts' ), $c['client_id'], 'contract', $c['id'], 'csigned:' . $c['id'] );
			}
			N::emit( 'team_contract_signed', $staff, 'Fully signed: ' . $c['title'], $c['number'] . ' has been signed by everyone.', '/contracts/' . $c['id'], $c['client_id'], 'contract', $c['id'], 'tsigned:' . $c['id'] );
		} elseif ( 'declined' === $event ) {
			N::emit( 'team_contract_declined', $staff, 'Declined: ' . $c['title'], ( ! empty( $detail['reason'] ) ? 'Reason: ' . $detail['reason'] : $c['number'] . ' was declined.' ), '/contracts/' . $c['id'], $c['client_id'], 'contract', $c['id'], 'tdecl:' . $c['id'] . ':' . $signer_id );
		}
	}
}
