<?php
namespace WPFlatSign;

defined( 'ABSPATH' ) || exit;

/** WPFlat Sign' own settings. The business name, logo and address come from WPFlat Core, so the one profile is shared. */
class Settings {

	const OPTION = 'wpflatsign_settings';

	public static function defaults() {
		return array(
			'contract_view_default' => 'private', // who may open the read-only preview link: private (signers only) or public
		);
	}

	public static function get( $key = null ) {
		$own = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		if ( null === $key ) {
			return $own + \WPFlatCore\Settings::get();
		}
		if ( array_key_exists( $key, $own ) ) {
			return $own[ $key ];
		}
		return \WPFlatCore\Settings::get( $key );
	}

	public static function update( array $in ) {
		$out = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		if ( isset( $in['contract_view_default'] ) ) {
			$out['contract_view_default'] = 'public' === $in['contract_view_default'] ? 'public' : 'private';
		}
		update_option( self::OPTION, $out, false );
		return $out;
	}
}
