<?php

/**
 * @class FortressDB
 */
class FortressDB {

	const DOMAIN = 'fortressdb';
	
	/**
	 * @var FortressDB_Wp_Api|null
	 */
	protected static $api = null;
	
	/**
	 * @var string|null
	 */
	protected static $plugin_token = null;
	
	/**
	 * Plugs FortressDB_Wp_Api class
	 */
	public static function include_api() {
		if ( ! class_exists( 'FortressDB_Wp_Api' ) ) {
			require_once( fortressdb_includes_dir( 'class-fortressdb-api.php' ) );
		}
	}
	
	/**
	 * Plugs FortressDB_File class
	 */
	public static function include_files_api() {
		if ( ! class_exists( 'FortressDB_File' ) ) {
			require_once( fortressdb_includes_dir( 'class-fortressdb-files.php' ) );
		}
	}
	
	/**
	 * Plugs FortressDB_Form_Parser class
	 */
	public static function include_form_parser() {
		if ( ! class_exists( 'FortressDB_Form_Parser' ) ) {
			require_once( fortressdb_includes_dir( 'class-fortressdb-form-parser.php' ) );
		}
	}
	
	/**
	 * @return array
	 */
	public static function get_plugin_options() {
		return fortressdb_get_plugin_options();
	}
	
	/**
	 * @param mixed $values
	 */
	public static function update_plugin_options( $values ) {
		fortressdb_update_plugin_options( $values );
	}
	
	/**
	 * @param string $addon_slug
	 *
	 * @return mixed
	 */
	public static function get_addon_options( $addon_slug ) {
		$options = FortressDB::get_plugin_options();
		
		return fdbarg( $options, array( 'addons', $addon_slug ), array() );
	}
	
	/**
	 * @param string $addon_slug
	 * @param mixed  $values
	 * @param array  $path
	 */
	public static function update_addon_options( $addon_slug, $values = array(), $path = array() ) {
		$plugin_options = FortressDB::get_plugin_options();
		
		$keys = array( 'addons', $addon_slug );
		foreach ( $path as $item ) {
			$keys[] = $item;
		}
		
		fdbars( $plugin_options, $keys, $values );
		
		FortressDB::update_plugin_options( $plugin_options );
	}
	
	/**
	 * @param string $plugin_token
	 *
	 * @return FortressDB_Wp_Api
	 *
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws Exception
	 */
	public static function get_api( $plugin_token = '' ) {
		if ( ! self::$api ) {
			self::include_api();
			self::$api = new FortressDB_Wp_Api();
			if ( ! $plugin_token ) {
				$settings     = self::get_plugin_options();
				$plugin_token = fdbarg( $settings, 'accessToken', '' );
			}
			self::$api->pluginToken( $plugin_token );
		}
		
		return self::$api->use_short_token();
	}
	
	/**
	 * @param string     $addon_slug
	 * @param string|int $form_id
	 *
	 * @return mixed
	 */
	public static function get_form_settings( $addon_slug, $form_id ) {
		$addon_options = FortressDB::get_addon_options( $addon_slug );
		
		return fdbarg( $addon_options, $form_id, array() );
	}
	
	/**
	 * @param string     $addon_slug
	 * @param string|int $form_id
	 * @param array      $meta
	 */
	public static function update_form_settings( $addon_slug, $form_id, $meta = array() ) {
		$addon_options = FortressDB::get_addon_options( $addon_slug );
		fdbars( $addon_options, $form_id, $meta );
		FortressDB::update_addon_options( $addon_slug, $addon_options );
	}
}
