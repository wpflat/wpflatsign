<?php
namespace BizyDoc;

use BizyCore\Migration\Legacy as Core_Legacy;
use BizyCore\Migration\Reader;

defined( 'ABSPATH' ) || exit;

/**
 * Takes the documents out of an old BizyDash backup: templates, contracts, signers, the audit log and the sealed PDFs.
 * Ids and hashes are kept exactly, so the hash chain and the PDF fingerprints still verify. A contract's old
 * project_id becomes a link when BizyTasks is installed.
 */
class Legacy {

	/** old table => new short table name */
	const TABLES = array(
		'contract_templates' => 'contract_templates',
		'contracts'          => 'contracts',
		'contract_signers'   => 'contract_signers',
		'contract_events'    => 'contract_events',
		'contract_otps'      => 'contract_otps',
	);

	public static function init() {
		add_filter( 'bizycore_legacy_import_docs', array( __CLASS__, 'import' ), 10, 2 );
	}

	/** @return array|\WP_Error */
	public static function import( $report, Reader $reader ) {
		global $wpdb;
		$had = 0;
		foreach ( self::TABLES as $new ) {
			$had += (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Schema::table( $new ) ); // phpcs:ignore WordPress.DB
		}
		if ( $had && empty( Core_Legacy::module_state( 'doc' )['done'] ) ) {
			return new \WP_Error( 'bizydoc_not_empty', 'BizyDoc already has records. Importing would mix them with the old ones. Remove them first, or skip this import.', array( 'status' => 409 ) );
		}
		foreach ( self::TABLES as $new ) {
			$wpdb->query( 'TRUNCATE TABLE ' . Schema::table( $new ) ); // phpcs:ignore WordPress.DB
		}
		$out  = array();
		$user = array( 'created_by', 'user_id' );
		foreach ( self::TABLES as $old => $new ) {
			$cols = (array) $wpdb->get_col( 'SHOW COLUMNS FROM ' . Schema::table( $new ), 0 ); // phpcs:ignore WordPress.DB
			$n    = 0;
			$f    = 0;
			foreach ( $reader->rows( $old ) as $row ) {
				$data = array();
				foreach ( $cols as $c ) {
					if ( ! array_key_exists( $c, $row ) ) {
						continue;
					}
					$data[ $c ] = in_array( $c, $user, true ) ? Core_Legacy::map_user( $row[ $c ] ) : $row[ $c ];
				}
				false === $wpdb->insert( Schema::table( $new ), $data ) ? $f++ : $n++;
			}
			$out[ $old ] = $n;
			if ( $f ) {
				$out[ $old . '_failed' ] = $f;
			}
		}
		// A contract tied to a project becomes a link (needs BizyTasks).
		$linked = 0;
		if ( \BizyCore\Links::is_available( 'project' ) ) {
			foreach ( $reader->rows( 'contracts' ) as $c ) {
				if ( (int) $c['project_id'] > 0 && ! is_wp_error( \BizyCore\Links::add( 'contract', (int) $c['id'], 'project', (int) $c['project_id'] ) ) ) {
					$linked++;
				}
			}
		}
		$out['project_links'] = $linked;
		// Sealed PDFs
		$pdfs = 0;
		foreach ( $reader->files_in( 'files/contract-pdfs/' ) as $e ) {
			$dest = \BizyCore\Paths::dir( 'contracts' ) . '/' . basename( $e );
			if ( $reader->extract_file( $e, $dest ) ) {
				@chmod( $dest, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
				$pdfs++;
			}
		}
		$out['pdfs'] = $pdfs;
		// Pictures that old document text points at (/wp-content/uploads/…): kept under the public folder's legacy/ copy.
		$imgs = 0;
		foreach ( $reader->files_in( 'files/uploads/' ) as $e ) {
			$rel  = substr( $e, strlen( 'files/uploads/' ) );
			$dest = \BizyCore\Media::dir() . '/legacy/' . $rel;
			wp_mkdir_p( dirname( $dest ) );
			if ( $reader->extract_file( $e, $dest ) ) {
				$imgs++;
			}
		}
		$out['images'] = $imgs;
		// Settings and counters
		$set = Core_Legacy::rest( 'settings' );
		if ( isset( $set['contract_view_default'] ) ) {
			Settings::update( array( 'contract_view_default' => $set['contract_view_default'] ) );
		}
		$opts = Core_Legacy::rest( 'options' );
		if ( isset( $opts['bizydash_contract_next'] ) ) {
			update_option( 'bizydoc_contract_next', (int) $opts['bizydash_contract_next'], false );
		}
		return $out;
	}
}
