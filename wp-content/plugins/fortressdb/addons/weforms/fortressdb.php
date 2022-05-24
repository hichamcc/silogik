<?php
/**
 * Addon Name: WeForms
 * Version: 1.0
 * Plugin URI:  https://weformspro.com/
 * Description: Integrate WeForms Custom Forms with FortressDB to get notified in real time.
 * Author: FortressDB
 * Author URI: https://fortressdb.com
 */

define( 'FORTRESSDB_ADDON_WEFORMS_NAME', 'weforms' );
define( 'FORTRESSDB_ADDON_WEFORMS_VERSION', '0.1.0' );
define( 'FORTRESSDB_ADDON_WEFORMS_MIN_VERSION', '1.11.1' );

/**
 * @param array $integrations
 *
 * @return array
 */
function fortressdb_weforms_addon_init_integration( $integrations ) {
	require_once( fortressdb_addons_dir( FORTRESSDB_ADDON_WEFORMS_NAME, '/lib/class-fortressdb-weforms.php' ) );
	
	$integrations[] = 'FortressDB_WeForms_Addon';
	
	return $integrations;
}

add_filter( 'weforms_integrations', 'fortressdb_weforms_addon_init_integration', 10, 1 );
