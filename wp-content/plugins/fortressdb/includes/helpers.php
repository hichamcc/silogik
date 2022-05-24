<?php

// define constants
define( 'FORTRESSDB_LOG_LEVEL_INFO', 'info' );
define( 'FORTRESSDB_LOG_LEVEL_WARNING', 'warning' );
define( 'FORTRESSDB_LOG_LEVEL_ERROR', 'error' );
define( 'FORTRESSDB_LOG_LEVEL_DEBUG', 'debug' );

/**
 * @param string $id Add-on's slug
 * @param string $suffix
 *
 * @return string
 */
function fortressdb_addon_url( $id, $suffix = '' ) {
	return sprintf( '%s/%s%s', FORTRESSDB_PLUGIN_ADDONS_URL, $id, $suffix ? "/{$suffix}" : '' );
}

/**
 * @param string $id Add-on's slug
 * @param string $suffix
 *
 * @return string
 */
function fortressdb_addon_assets_url( $id, $suffix = '' ) {
	return fortressdb_addon_url( $id, $suffix ? "assets/{$suffix}" : 'assets' );
}

/**
 * @param string $id Add-on's slug
 * @param string $suffix
 *
 * @return string
 */
function fortressdb_addons_dir( $id, $suffix = '' ) {
	return sprintf( '%s/%s%s', FORTRESSDB_PLUGIN_ADDONS_DIR, $id, $suffix ? "/{$suffix}" : '' );
}

/**
 * @param string $suffix
 *
 * @return string
 */
function fortressdb_includes_dir( $suffix = '' ) {
	return sprintf( '%s/includes%s', FORTRESSDB_PLUGIN_DIR_PATH, $suffix ? "/{$suffix}" : '' );
}

/**
 * @param string $id         Add-on slug
 * @param string $class_name WP Plugin base class name
 * @param string $version_constant
 *
 * @uses fortressdb_addons_dir
 */
function fortressdb_addon_load( $id, $class_name, $version_constant ) {
	if ( class_exists( $class_name ) && defined( strtoupper( $version_constant ) ) ) {
		$file = fortressdb_addons_dir( $id, 'fortressdb.php' );
		if ( file_exists( $file ) ) {
			include $file;
		}
	}
}

/**
 * Defines path to log file
 * Can be override
 *
 * @return string
 * @uses apply_filters
 *
 */
function fortressdb_log_file_path() {
	$upload_dir               = wp_upload_dir();
	$uploads_dir              = basename( $upload_dir['basedir'] );
	$fortressdb_log_file_path = sprintf( '%s/%s/fortressdb.json', WP_CONTENT_DIR, $uploads_dir );
	
	return apply_filters( 'fortressdb_log_file_path', $fortressdb_log_file_path );
}

/**
 * The main logging function
 *
 * @param string|array $msg
 * @param string       $level type of the error. e.g: debug, error, info
 *
 * @uses error_log
 *
 */
function fortressdb_log( $msg = '', $level = FORTRESSDB_LOG_LEVEL_INFO ) {
	// default we are turning the debug mood on, but can be turned off
	if ( defined( 'FORTRESSDB_DEBUG_LOG' ) && false === FORTRESSDB_DEBUG_LOG ) {
		return;
	}
	
	$msg = array(
		'id'        => uniqid(),
		'timestamp' => date( 'c' ),
		'level'     => $level,
		'message'   => $msg,
	);
	
	@error_log( sprintf( "%s%s", json_encode( $msg, JSON_PRETTY_PRINT ), PHP_EOL ), 3, fortressdb_log_file_path() );
}

/**
 * @return array
 */
function fortressdb_get_plugin_options() {
	$values = array();
	
	if ( defined( 'FORTRESSDB_OPTIONS' ) ) {
		$values = get_option( FORTRESSDB_OPTIONS, array() );
	}
	
	return $values;
}

/**
 * @param mixed $values
 */
function fortressdb_update_plugin_options( $values ) {
	if ( defined( 'FORTRESSDB_OPTIONS' ) ) {
		update_option( FORTRESSDB_OPTIONS, $values );
	}
}

/**
 * @param array        $array
 * @param array|string $path
 * @param null         $default
 * @param string       $delimiter = '.'
 *
 * @return mixed
 */
function fdbarg( $array, $path, $default = null, $delimiter = '.' ) {
	if ( ! is_array( $array ) ) {
		$array = (array) $array;
	}
	
	$keys = $path;
	if ( ! is_array( $path ) ) {
		if ( array_key_exists( $path, $array ) ) {
			return $array[ $path ];
		}
		
		// Split the keys by delimiter
		$path = ltrim( $path, "{$delimiter} " );
		$path = rtrim( $path, "{$delimiter} *" );
		$keys = explode( $delimiter, $path );
	}
	
	do {
		$key = array_shift( $keys );
		if ( ctype_digit( $key ) ) {
			$key = (int) $key;
		}
		
		if ( isset( $array[ $key ] ) ) {
			if ( $keys ) {
				if ( is_array( $array[ $key ] ) ) {
					$array = $array[ $key ];
				} else {
					break;
				}
			} else {
				return $array[ $key ];
			}
		} elseif ( $key === '*' ) {
			$values = array();
			foreach ( $array as $arr ) {
				if ( $value = fdbarg( $arr, implode( $delimiter, $keys ) ) ) {
					$values[] = $value;
				}
			}
			
			if ( $values ) {
				return $values;
			} else {
				break;
			}
		} else {
			break;
		}
	} while ( $keys );
	
	return $default;
}

/**
 * FortressDB Array Set
 *
 * @param array        $array
 * @param string|array $path
 * @param mixed        $value
 * @param string       $delimiter = '.'
 */
function fdbars( array &$array, $path, $value, $delimiter = '.' ) {
	// The path has already been separated into keys
	$keys = $path;
	if ( ! is_array( $path ) ) {
		// Split the keys by delimiter
		$path = ltrim( $path, "{$delimiter} " );
		$path = rtrim( $path, "{$delimiter} *" );
		$keys = explode( $delimiter, $path );
	}
	
	// Set current $array to inner-most array path
	while ( count( $keys ) > 1 ) {
		$key = array_shift( $keys );
		
		if ( ctype_digit( $key ) ) {
			// Make the key an integer
			$key = (int) $key;
		}
		
		if ( ! isset( $array[ $key ] ) ) {
			$new_array = array();
			if ( $key == "_" || empty( $key ) ) {
				$array[] = $new_array;
				$key     = array_search( $new_array, $array );
			} else {
				$array[ $key ] = $new_array;
			}
		}
		
		$array = &$array[ $key ];
	}
	
	$key = array_shift( $keys );
	
	if ( $key == "_" || empty( $key ) ) {
		$array[] = $value;
	} else {
		// Set key on inner-most array
		$array[ $key ] = $value;
	}
}

/**
 * FortressDB Array Remove
 *
 * @param array        $array
 * @param array|string $path
 * @param string       $delimiter
 */
function fdbarr( &$array, $path, $delimiter = '.' ) {
	// The path has already been separated into keys
	$keys = $path;
	if ( ! is_array( $path ) ) {
		// Split the keys by delimiter
		$path = ltrim( $path, "{$delimiter} " );
		$path = rtrim( $path, "{$delimiter} *" );
		$keys = explode( $delimiter, $path );
	}
	
	$prev = null;
	$el   = &$array;
	$key  = null;
	foreach ( $keys as $key ) {
		if ( ctype_digit( $key ) ) {
			$key = (int) $key;
		}
		$prev = &$el;
		$el   = &$el[ $key ];
	}
	
	if ( $prev ) {
		unset( $prev[ $key ] );
	}
}

/**
 * FortressDB Array Collect
 *
 * @param array $array
 * @param       $key
 *
 * @return array
 */
function fdbarc( array $array, $key ) {
	$collection = array();
	foreach ( $array as $k => $item ) {
		if ( $key == $k ) {
			$collection[] = $item;
		}
	}
	
	return $collection;
}
