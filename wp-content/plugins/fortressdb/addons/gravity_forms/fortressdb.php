<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'FORTRESSDB_ADDON_GF_VERSION', '0.1.0' );
define( 'FORTRESSDB_ADDON_GF_NAME', 'gravity_forms' );

/**
 * @class GF_FortressDB_Bootstrap
 */
class GF_FortressDB_Bootstrap {
	/**
	 * @return void
	 */
	public static function load() {
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}
		
		require_once( 'lib/class-fortressdb-gforms.php' );
		
		GFAddOn::register( 'FortressDB_GForms_Addon' );
	}
}

/**
 * Returns an instance of the FortressDB_GForms_Addon class
 *
 * @return object FortressDB_GForms_Addon
 * @throws Exception
 * @see    FortressDB_GForms_Addon::get_instance()
 *
 */
function gf_fortressdb_addon() {
	return FortressDB_GForms_Addon::get_instance();
}

GF_FortressDB_Bootstrap::load();