<?php

define( 'FORTRESSDB_MAX_FIELDS_ORDER', 1000 );

function fortressdb_load_all_addons() {
	$addons = array(
		'forminator' => array(
			'version_constant' => 'FORMINATOR_VERSION',
			'class_name' => 'Forminator_Addon_Loader',
			'action' => 'wp_loaded'
		),
		'gravity_forms' => array(
			'version_constant' => 'GF_SUPPORTED_WP_VERSION',
			'class_name' => 'GFForms',
			'action' => 'gform_loaded'
		),
		'weforms' => array(
			'version_constant' => 'WEFORMS_VERSION',
			'class_name' => 'WeForms',
			'action' => 'init'
		)
	);
	
	foreach ( $addons as $id => $addon ) {
		add_action($addon['action'], function() use ($id, $addon) {
			fortressdb_addon_load( $id, $addon['class_name'], $addon['version_constant'] );
		});
	}
}
