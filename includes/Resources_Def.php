<?php
namespace BizyDocs;

use BizyCore\Resources;
use BizyDocs\Services\Contracts;

defined( 'ABSPATH' ) || exit;

/** BizyDocs' REST resources: templates, contracts (and other signed documents). */
class Resources_Def {

	public static function register() {
		$defs = array(
			'contract-templates' => array(
				'route'    => 'contract-templates',
				'table'    => 'bizydocs_templates',
				'label'    => 'Contract template',
				'cols'     => array( 'name' => 's', 'description' => 's', 'doc_type' => 's', 'body' => 'h', 'is_active' => 'b', 'department_id' => 'i' ),
				'filters'  => array( 'is_active', 'department_id', 'doc_type' ),
				'search'   => array( 't.name', 't.description' ),
				'order'    => 't.name ASC',
				'required' => array( 'name' ),
				'title'    => function ( $r ) {
					return $r['name'];
				},
				'before_save' => function ( $d ) {
					if ( isset( $d['body'] ) ) {
						$d['body'] = wp_kses_post( $d['body'] );
					}
					return $d;
				},
			),

			'contracts'       => array(
				'route'    => 'contracts',
				'table'    => 'bizydocs_contracts',
				'label'    => 'Contract',
				'select'   => ', c.name AS client_name, dp.name AS department_name, (SELECT COUNT(*) FROM ' . Schema::table( 'contract_signers' ) . ' sg WHERE sg.contract_id=t.id) AS signer_total, (SELECT COUNT(*) FROM ' . Schema::table( 'contract_signers' ) . " sg2 WHERE sg2.contract_id=t.id AND sg2.status='signed') AS signer_done",
				'join'     => 'LEFT JOIN ' . Schema::table( 'clients' ) . ' c ON c.id=t.client_id LEFT JOIN ' . Schema::table( 'brands' ) . ' dp ON dp.id=t.department_id',
				'cols'     => array( 'title' => 's', 'client_id' => 'i', 'template_id' => 'i', 'doc_type' => 's', 'department_id' => 'i', 'body' => 'h', 'expires_on' => 'date', 'notes' => 't' ),
				'filters'  => array( 'client_id', 'status', 'doc_type', 'department_id' ),
				'search'   => array( 't.number', 't.title', 'c.name' ),
				'order'    => 't.id DESC',
				'order_map' => array( 'client_name' => 'c.name' ),
				'required' => array( 'title' ),
				'title'    => function ( $r ) {
					return $r['number'] . ' ' . $r['title'];
				},
				'before_save' => function ( $d, $existing, $params ) {
					global $wpdb;
					if ( $existing && 'draft' !== $existing['status'] ) {
						// Once sent, the agreed text is frozen; only housekeeping fields may change.
						foreach ( array( 'title', 'body', 'client_id', 'template_id' ) as $k ) {
							unset( $d[ $k ] );
						}
					}
					if ( isset( $d['body'] ) ) {
						$d['body'] = wp_kses_post( $d['body'] );
					}
					if ( ! $existing ) {
						$d['status']      = 'draft';
						$d['number']      = Contracts::next_number();
						$d['view_access'] = 'public' === Settings::get( 'contract_view_default' ) ? 'public' : 'private';
						if ( empty( $d['department_id'] ) ) {
							$tp_dept = ! empty( $d['template_id'] ) ? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT department_id FROM ' . Schema::table( 'contract_templates' ) . ' WHERE id=%d', (int) $d['template_id'] ) ) : 0; // phpcs:ignore WordPress.DB
							$d['department_id'] = $tp_dept ? $tp_dept : \BizyCore\Services\Departments::client_primary( isset( $d['client_id'] ) ? $d['client_id'] : 0 ); // the template's department, else the client's
						}
						if ( empty( $d['body'] ) && ! empty( $d['template_id'] ) ) {
							$d['body'] = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT body FROM ' . Schema::table( 'contract_templates' ) . ' WHERE id=%d', (int) $d['template_id'] ) ); // phpcs:ignore WordPress.DB
						}
					}
					if ( ( ! $existing || 'draft' === $existing['status'] ) && isset( $d['body'] ) ) {
						$ctx = array(
							'client_id' => isset( $d['client_id'] ) ? $d['client_id'] : ( $existing ? $existing['client_id'] : 0 ),
							'title'     => isset( $d['title'] ) ? $d['title'] : ( $existing ? $existing['title'] : '' ),
							'number'    => $existing ? $existing['number'] : $d['number'],
						);
						$d['body'] = Contracts::merge( $d['body'], $ctx );
					}
					return $d;
				},
				'after_save' => function ( $row, $existing, $params ) {
					if ( isset( $params['signers'] ) && is_array( $params['signers'] ) && 'draft' === $row['status'] ) {
						Contracts::sync_signers( $row['id'], $params['signers'] );
					}
					if ( ! $existing ) {
						Contracts::event( $row['id'], 0, 'created', array( 'number' => $row['number'] ) );
					}
				},
				'before_delete' => function ( $row ) {
					return in_array( $row['status'], array( 'draft', 'void', 'declined' ), true ) ? true : new \WP_Error( 'bizydocs_blocked', 'A contract that is out for signature or signed is a legal record and cannot be deleted. Void it instead.', array( 'status' => 409 ) );
				},
				'after_delete' => function ( $row ) {
					global $wpdb;
					if ( 'draft' === $row['status'] ) {
						foreach ( array( 'contract_signers', 'contract_events', 'contract_otps' ) as $tbl ) {
							$wpdb->delete( Schema::table( $tbl ), array( 'contract_id' => (int) $row['id'] ) );
						}
					}
				},
				'decorate' => function ( $rows, $single ) {
					foreach ( $rows as &$r ) {
						$r['signer_total'] = (int) $r['signer_total'];
						$r['signer_done']  = (int) $r['signer_done'];
						if ( $single ) {
							$r['signers']         = Contracts::signers( $r['id'] );
							$r['certificate_url'] = home_url( '/?bd_certificate=' . (int) $r['id'] );
							$r['preview_url']     = Contracts::preview_url( $r['preview_slug'] );
							$r['has_pdf']         = ! empty( $r['pdf_file'] );
							$r['pdf_url']         = Services\Contract_Pdf::url( $r['id'] );
							unset( $r['pdf_file'] );
						} else {
							unset( $r['body'] );
						}
					}
					return $rows;
				},
			),
		);
		foreach ( $defs as $name => $cfg ) {
			Resources::register( $name, $cfg, 'docs' );
		}
	}
}
