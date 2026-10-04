<?php
namespace BizyDocs;

defined( 'ABSPATH' ) || exit;

/** BizyDocs' tables. */
class Install {

	const DB_VERSION = '2';
	const OPTION     = 'bizydocs_db_version';

	/** Cheap check on every request so an updated plugin upgrades itself. */
	public static function maybe_upgrade() {
		if ( get_option( self::OPTION ) !== self::DB_VERSION ) {
			self::install_tables();
		}
	}

	public static function install_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$t       = array();
		foreach ( array_keys( Schema::OWN ) as $n ) {
			$t[ $n ] = Schema::table( $n );
		}
		$sql = array();

		$sql[] = "CREATE TABLE {$t['contract_templates']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL DEFAULT '',
  description varchar(255) NOT NULL DEFAULT '',
  doc_type varchar(30) NOT NULL DEFAULT 'contract',
  body longtext,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  department_id bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id)
) $charset;";

		$sql[] = "CREATE TABLE {$t['contracts']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  number varchar(30) NOT NULL DEFAULT '',
  title varchar(190) NOT NULL DEFAULT '',
  client_id bigint(20) unsigned NOT NULL DEFAULT 0,
  template_id bigint(20) unsigned NOT NULL DEFAULT 0,
  doc_type varchar(30) NOT NULL DEFAULT 'contract',
  department_id bigint(20) unsigned NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'draft',
  body longtext,
  body_hash char(64) NOT NULL DEFAULT '',
  final_hash char(64) NOT NULL DEFAULT '',
  pdf_file varchar(190) NOT NULL DEFAULT '',
  pdf_hash char(64) NOT NULL DEFAULT '',
  pdf_size int(11) NOT NULL DEFAULT 0,
  pdf_at datetime DEFAULT NULL,
  preview_slug varchar(64) NOT NULL DEFAULT '',
  view_access varchar(10) NOT NULL DEFAULT 'private',
  sent_at datetime DEFAULT NULL,
  completed_at datetime DEFAULT NULL,
  expires_on date DEFAULT NULL,
  notes text,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY client_id (client_id),
  KEY status (status),
  KEY doc_type (doc_type),
  KEY preview_slug (preview_slug)
) $charset;";

		$sql[] = "CREATE TABLE {$t['contract_signers']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
  name varchar(190) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  role varchar(20) NOT NULL DEFAULT 'client',
  sort_order int(11) NOT NULL DEFAULT 0,
  token varchar(64) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'pending',
  invited_at datetime DEFAULT NULL,
  viewed_at datetime DEFAULT NULL,
  consent_at datetime DEFAULT NULL,
  otp_verified_at datetime DEFAULT NULL,
  signed_at datetime DEFAULT NULL,
  sign_name varchar(190) NOT NULL DEFAULT '',
  sign_method varchar(12) NOT NULL DEFAULT '',
  sign_image mediumtext,
  sign_ip varchar(64) NOT NULL DEFAULT '',
  sign_forwarded varchar(255) NOT NULL DEFAULT '',
  sign_ua varchar(500) NOT NULL DEFAULT '',
  sign_env text,
  declined_reason text,
  PRIMARY KEY  (id),
  KEY contract_id (contract_id),
  KEY token (token)
) $charset;";

		$sql[] = "CREATE TABLE {$t['contract_events']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
  signer_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  event varchar(40) NOT NULL DEFAULT '',
  detail text,
  ip varchar(64) NOT NULL DEFAULT '',
  ua varchar(500) NOT NULL DEFAULT '',
  created_at datetime DEFAULT NULL,
  prev_hash char(64) NOT NULL DEFAULT '',
  hash char(64) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY contract_id (contract_id)
) $charset;";

		$sql[] = "CREATE TABLE {$t['contract_otps']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
  signer_id bigint(20) unsigned NOT NULL DEFAULT 0,
  email varchar(190) NOT NULL DEFAULT '',
  code varchar(10) NOT NULL DEFAULT '',
  sent_at datetime DEFAULT NULL,
  expires_at datetime DEFAULT NULL,
  mail_ok tinyint(1) NOT NULL DEFAULT 0,
  mail_error varchar(255) NOT NULL DEFAULT '',
  attempts int(11) NOT NULL DEFAULT 0,
  entered_code varchar(10) NOT NULL DEFAULT '',
  entered_at datetime DEFAULT NULL,
  status varchar(12) NOT NULL DEFAULT 'sent',
  purpose varchar(8) NOT NULL DEFAULT 'sign',
  ip varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY signer_id (signer_id)
) $charset;";

		foreach ( $sql as $q ) {
			dbDelta( $q );
		}
		update_option( self::OPTION, self::DB_VERSION, false );
	}
}
