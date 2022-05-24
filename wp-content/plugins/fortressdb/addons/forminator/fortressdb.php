<?php

/**
 * Addon Name: FortressDB
 * Version: 1.0
 * Plugin URI:  https://premium.wpmudev.org/
 * Description: Integrate Forminator Custom Forms with FortressDB to get notified in real time.
 * Author: FortressDB
 * Author URI: https://fortressdb.com
 */

define( 'FORTRESSDB_ADDON_FORMINATOR_SLUG', 'forminator' );
define( 'FORTRESSDB_ADDON_FORMINATOR_VERSION', '0.1.0' );
define( 'FORTRESSDB_ADDON_FORMINATOR_MIN_VERSION', '1.11.1' );

require_once( fortressdb_addons_dir( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'lib/class-fortressdb-forminator.php' ) );
require_once( fortressdb_addons_dir( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'lib/class-fortressdb-forminator-form-settings.php' ) );
require_once( fortressdb_addons_dir( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'lib/class-fortressdb-forminator-form-hooks.php' ) );

try {
	Forminator_Addon_Loader::get_instance()->register( 'FortressDB_Forminator_Addon' );
} catch ( Exception $e ) {
	echo $e->getMessage();
}
