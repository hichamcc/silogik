<?php

// load FortressDB_Wp_Api class
FortressDB::include_form_parser();

require_once( 'class-fortressdb-forminator-addon-exception.php' );

/**
 * Class FortressDB_Forminator_Addon
 * FortressDB Addon Main Class
 *
 * @since 0.1.0 FortressDB Addon
 */
final class FortressDB_Forminator_Addon extends Forminator_Addon_Abstract {
	/**
	 * @var self|null
	 */
	private static $_instance = null;
	protected $_slug = 'forminatorfortressdb';
	protected $_version = FORTRESSDB_ADDON_FORMINATOR_VERSION;
	protected $_min_forminator_version = '1.1';
	protected $_short_title = 'FortressDB';
	protected $_title = 'FortressDB';
	protected $_url = 'https://premium.wpmudev.org';
	protected $_full_path = __FILE__;
	protected $_form_settings = 'FortressDB_Forminator_Addon_Form_Settings';
	protected $_form_hooks = 'FortressDB_Forminator_Addon_Form_Hooks';
	/**
	 * @var FortressDB_Wp_Api
	 */
	private $api = null;
	/**
	 * @var FortressDB_Form_Parser
	 */
	private $parser = null;
	private $_plugin_token = '';
	private $_update_form_settings_error_message = '';

	/**
	 * @var array
	 */
	private $feed = array();

	/**
	 * @var array
	 */
	protected $parsers = array();

	/**
	 * Forminator_Addon_FortressDB_Form_Settings constructor.
	 *
	 * @throws Exception
	 *
	 * @since 0.1.0 FortressDB Addon
	 */
	public function __construct() {
		$this->_update_form_settings_error_message = __(
			'The update to your settings for this form failed, check the form input and try again.',
			FortressDB::DOMAIN
		);

		$this->_icon  = fortressdb_addon_assets_url( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'images/fortressdb.png' );
		$this->_image = fortressdb_addon_assets_url( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'images/fortressdb.png' );

		try {
			$this->api = FortressDB::get_api();
		} catch ( FortressDB_Wp_Api_Exception $e ) {
			fortressdb_log( $e->getMessage() );
		}

		$this->parser = new FortressDB_Form_Parser( $this->_slug );

		add_action( 'forminator_custom_form_action_update', array( $this, 'on_form_update' ), 10, 5 );
		add_action( "forminator_addon_{$this->_slug}_get_settings_values", array(
			$this,
			'on_get_settings_values',
		), 10, 1 );
		add_action( 'store_linked_enums', array( $this, 'on_store_linked_enums' ), 10, 2 );
		add_action( 'store_linked_enum_values', array( $this, 'on_store_linked_enum_values' ), 10, 3 );

		add_filter( "fortressdb_process_field_{$this->_slug}", array( $this, 'on_process_field' ), 10, 5 );
		add_filter( "fortressdb_assign_enum_{$this->_slug}", array( $this, 'on_assign_enum' ), 10, 4 );
		add_action( 'forminator_after_addon_activated', array( $this, 'on_forminator_after_addon_activated' ), 10, 1 );
	}

	protected function init_parsers() {
		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_string_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::STRING );

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_text_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::TEXT );

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_file_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$file_type = fdbarg( $options, 'field.file-type', '' );

			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::FILE )
				->apply_extend( 'isMultiple', $file_type === 'multiple' )
				->set_default_value();

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_number_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$default_value = (float) fdbarg( $options, 'field.default_value', 0 );

			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::FLOAT )
				->set_default_value( $default_value );

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_name_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field         = (array) fdbarg( $options, 'field', array() );
			$multiple_name = (bool) fdbarg( $options, 'field.multiple_name', false );

			$multiple_name = filter_var( $multiple_name, FILTER_VALIDATE_BOOLEAN );
			if ( $multiple_name ) {
				$inputs         = array(
					'prefix',
					'fname',
					'mname',
					'lname',
				);
				$proceed_fields = $this->process_fields( $inputs, $field, $options );
				$complex_fields = array_merge( $complex_fields, $proceed_fields );
			} else {
				$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::STRING );
			}

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_postdata_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field = (array) fdbarg( $options, 'field', array() );

			$inputs = array(
				'post_title',
				'post_content',
				'post_excerpt',
				'post_image',
				'category',
				'post_tag',
				'post_custom_fields',
			);

			$proceed_fields = $this->process_fields( $inputs, $field, $options );
			$complex_fields = array_merge( $complex_fields, $proceed_fields );

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_address_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field = (array) fdbarg( $options, 'field', array() );

			$inputs = array(
				'street_address',
				'address_line',
				'address_city',
				'address_state',
				'address_zip',
				'address_country',
			);

			$processed_fields = $this->process_fields( $inputs, $field, $options );
			$complex_fields   = array_merge( $complex_fields, $processed_fields );

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_enum_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field         = (array) fdbarg( $options, 'field', array() );
			$default_value = (array) fdbarg( $options, 'field.defaultValue', array() );
			$source        = (array) fdbarg( $options, 'source', array() );
			$caption       = (string) fdbarg( $options, 'caption', '' );
			$description   = (string) fdbarg( $options, 'description', '' );
			$form_id       = (string) fdbarg( $options, 'form_id', '' );
			$type          = (string) fdbarg( $options, 'type', '' );

			$simple_field->set_type( FortressDB_Field::ENUM )->set_default_value( $default_value );

			$options      = array(
				'inputs_key'  => 'options',
				'id_key'      => 'value',
				'label_key'   => 'label',
				'caption'     => $caption,
				'description' => $description,
				'source'      => $source,
				'enum'        => $this->get_enum( $form_id, $element_id ),
				'type'        => $type,
			);
			$simple_field = $this->build_or_assign_enum( $simple_field, $field, $element_id, $options );

			/**
			 * @param FortressDB_Field $simple_field
			 * @param array            $field
			 * @param string|int       $element_id
			 * @param array            $type
			 *
			 * @return FortressDB_Field
			 */
			$simple_field = apply_filters( "fortressdb_assign_enum_{$this->_slug}", $simple_field, $field, $element_id, $type );

			$complex_fields[ $element_id ] = $simple_field;

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_checkbox_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) use ($parse_enum_fields) {
			$result = $parse_enum_fields($complex_fields, $element_id, $simple_field, $options);
			if ($result && isset($complex_fields[ $element_id ])) {
				$complex_fields[ $element_id ]->apply_extend( 'isMultiple', true );
			}
			return $result;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_time_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field = (array) fdbarg( $options, 'field', array() );

			$time_type       = fdbarg( $field, 'time_type', 'twelve' );
			$is_long_format  = (bool) ( $time_type === 'twentyfour' );
			$default_hours   = (int) fdbarg( $field, 'default_time_hour', 0 );
			$default_minutes = (int) fdbarg( $field, 'default_time_minute', 0 );
			$default_ampm    = fdbarg( $field, 'default_time_ampm', '' );
			$extra_time      = ( ! $is_long_format && strtolower( $default_ampm ) === 'pm' ) ? 12 : 0;
			$default_hours   = $default_hours + $extra_time;
			$hours           = $default_hours * 3600; // 01 * 60 * 60
			$minutes         = $default_minutes * 60; // 01 * 60

			$simple_field
				->set_type( FortressDB_Field::TIME )
				->set_default_value( $hours + $minutes )
				->apply_extend( 'caption', 'Time' )
				->apply_extend( 'format.long', $is_long_format );

			$complex_fields[ $element_id ] = $simple_field;

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_date_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field = (array) fdbarg( $options, 'field', array() );

			$simple_field->set_default_value();
			$default_date = fdbarg( $field, 'default_date' );

			if ( $default_date ) {
				switch ( $default_date ) {
					case 'today':
						$simple_field->set_default_value( date( 'm/d/Y' ) );
						break;
					case 'custom':
						$date = fdbarg( $field, 'date' );
						if ( $date ) {
							$simple_field->set_default_value( $date );
						}
						break;
					case 'none':
					default:
						$simple_field->set_default_value();
						break;
				}
			}

			$complex_fields[ $element_id ] = $simple_field;

			return true;
		};

		/**
		 * @param       $complex_fields
		 * @param       $element_id
		 * @param       $simple_field
		 * @param array $options
		 *
		 * @return bool
		 */
		$parse_consent_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::BOOLEAN );
			return true;
		};

		// string fields
		$this->parsers['phone']       = $parse_string_fields;
		$this->parsers['email']       = $parse_string_fields;
		$this->parsers['url']         = $parse_string_fields;
		$this->parsers['currency']    = $parse_string_fields;
		$this->parsers['calculation'] = $parse_string_fields;

		// text fields
		$this->parsers['text']     = $parse_text_fields;
		$this->parsers['textarea'] = $parse_text_fields;
		$this->parsers['html']     = $parse_text_fields;

		// file fields
		$this->parsers['upload']    = $parse_file_fields;
		$this->parsers['signature'] = $parse_file_fields;

		// number fields
		$this->parsers['number'] = $parse_number_fields;

		// name fields
		$this->parsers['name'] = $parse_name_fields;

		// postdata fields
		$this->parsers['postdata'] = $parse_postdata_fields;

		// address fields
		$this->parsers['address'] = $parse_address_fields;

		// checkbox fields
		$this->parsers['checkbox'] = $parse_checkbox_fields;

		// enum fields
		$this->parsers['select'] = $parse_enum_fields;
		$this->parsers['radio']  = $parse_enum_fields;

		// time fields
		$this->parsers['time'] = $parse_time_fields;

		// date fields
		$this->parsers['date'] = $parse_date_fields;

		$this->parsers['hidden'] = $parse_string_fields;
		$this->parsers['consent'] = $parse_consent_fields;
	}

	/**
	 * Get Instance
	 *
	 * @return self|null
	 * @throws Exception
	 * @since 0.1.0 FortressDB Addon
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Flag show full log on entries
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 */
	public static function is_show_full_log() {
		$show_full_log = ( defined( 'FORMINATOR_ADDON_FORTRESSDB_SHOW_FULL_LOG' ) && FORMINATOR_ADDON_FORTRESSDB_SHOW_FULL_LOG );

		/**
		 * Filter show full log of FortressDB
		 *
		 * @param bool $show_full_log
		 *
		 * @since 1.2
		 *
		 */
		$show_full_log = apply_filters( 'forminator_addon_fortressdb_show_full_log', $show_full_log );

		return $show_full_log;
	}

	/**
	 * Settings wizard
	 *
	 * @return array
	 * @since 0.1.0 FortressDB Addon
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'wait_authorization' ),
				'is_completed' => array( $this, 'wait_authorization_success' ),
			),
		);
	}

	/**
	 * Override settings available,
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * @param $form_id
	 * @param $title
	 * @param $status
	 * @param $wrappers
	 * @param $settings
	 *
	 * @return void
	 * @throws FortressDB_Form_Parser_Exception
	 * @throws FortressDB_Forminator_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function on_form_update( $form_id, $title, $status, $wrappers, $settings ) {
		if ( $this->is_form_connected( $form_id ) && $status === 'publish' ) {
			$this->init_parsers();

			$this->get_form_feed( $form_id );
			$source     = array( 'name' => 'Forminator' );
			$table_uuid = fdbarg( $this->feed, 'metadata.tableName', '' );
			$metadata   = $this->api->get_metadata( $table_uuid ) ?:
				$this->api->post_metadata( $title, '', array( 'source' => $source ) );

			fdbars( $metadata, 'name', $title );
			fdbars( $metadata, 'options.source', $source );
			fdbars( $this->feed, 'fields_group', array() );
			fdbars( $this->feed, 'linked_enum_values', array() );

			$fdb_fields = fdbarg( $metadata, 'fields', array() );
			$orders     = array();
			foreach ( $fdb_fields as $fdb_field ) {
				$order = fdbarg( $fdb_field, 'extend.order', - 1 );
				if ( $order > 0 && $order < FORTRESSDB_MAX_FIELDS_ORDER ) {
					$orders[] = $order;
				}
			}
			asort( $orders );

			$field_order      = end( $orders ) + 1;
			$result_fields    = array();
			$complex_fields   = array();
			$fields_to_delete = array();

			/**
			 * @var FortressDB_Field $simple_field
			 */

			foreach ( $wrappers as $wrapper ) {
				foreach ( $wrapper['fields'] as $field ) {
					$simple_field       = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
					$type               = fdbarg( $field, 'type', FortressDB_Field::STRING );
					$default_value      = fdbarg( $field, 'default_value', '' );
					$placeholder        = fdbarg( $field, 'placeholder', '' );
					$description        = fdbarg( $field, 'description', '' );
					// $allow_null         = ! (bool) fdbarg( $field, 'required', false );
					$element_id         = $source['elementId'] = trim( fdbarg( $field, 'element_id', 0 ) );
					$caption            = trim( fdbarg( $field, 'field_label', '' ) );
					$caption            = !empty($caption) ? $caption : $element_id;
					$default_field_data = array(
						'defaultValue' => $default_value,
						'allowNull'    => true,
						'type'         => $type,
						'name'         => $element_id,
						'extend'       => array(
							'caption'     => $caption,
							'order'       => $field_order,
							'description' => $description,
							'placeholder' => $placeholder,
							'source'      => $source,
							'encrypted'   => false,
						),
					);

					$simple_field->init( $default_field_data );

					if ( isset( $this->parsers[ $type ] ) ) {
						$options    = array(
							'field'         => $field,
							'default_value' => $default_value,
							'source'        => $source,
							'caption'       => $caption,
							'description'   => $description,
							'form_id'       => $form_id,
							'type'          => $type,
						);
						$valid_type = $this->parsers[$type]( $complex_fields, $element_id, $simple_field, $options );
					} else {
						$valid_type = false;
					}

					if ( ! $valid_type ) {
						continue;
					}
				}
			}

			if ( empty( $complex_fields ) ) {
				$fields_to_delete = FortressDB_Field::check_candidates_to_delete( $fdb_fields, null );
			}

			foreach ( $complex_fields as $element_id => $simple_field ) {
				FortressDB_Field::check_in_existing( $result_fields, $fdb_fields, $field_order, $element_id, $simple_field );
				$fields_to_delete = FortressDB_Field::check_candidates_to_delete( $fdb_fields, $element_id );
			}

			$fdb_fields       = array_values( $fdb_fields );
			$result_fields    = array_values( $result_fields );
			$fields_to_delete = array_values( $fields_to_delete );

			foreach ( $fdb_fields as $field ) {
				$result_fields[] = $field;
			}

			if ( ! empty( $fields_to_delete ) ) {
				foreach ( $fields_to_delete as $item ) {
					$element_id = $item_name = fdbarg( $item, 'extend.source.elementId' );
					if ( $item_name ) {
						if ( $item['type'] === 'enum' ) {
							$linked_enums_path = array( 'linked_enums', $element_id );
							$removable_enums[] = fdbarg( $this->feed, $linked_enums_path );
							fdbarr( $this->feed, $linked_enums_path );
						}

						foreach ( $result_fields as $index => $result_field ) {
							$element_id = fdbarg( $result_field, 'extend.source.elementId' );
							if ( $element_id && $item_name === $element_id ) {
								unset( $result_fields[ $index ] );
							}
						}
					}
				}

				if ( ! empty( $removable_enums ) ) {
					try {
						$this->api->delete_enums($removable_enums);
					} catch (FortressDB_Wp_Api_Exception $e) {
						fortressdb_log(array(__METHOD__, $e->getMessage()), FORTRESSDB_LOG_LEVEL_ERROR);
					}
				}
			}

			$metadata['fields'] = array_values( $result_fields );

			$metadata = $this->api->update_metadata( $metadata );

			// update stored fields_group due changes of metadata in the backend DB
			foreach ( $metadata['fields'] as $field ) {
				$element_id = fdbarg( $field, 'extend.source.elementId' );
				if ( $element_id ) {
					fdbars( $this->feed, array( 'fields_group', $element_id ), $field );
				}
			}

			fdbars( $this->feed, 'metadata', $metadata );

			$this->update_form_feed( $form_id );
		}
	}

	/**
	 * Wrapper for `get_addon_settings` for backwards compatibility
	 * Reason: get_addon_form_settings removed in Forminator 1.15.4.
	 *
	 * @param $form_id
	 * @return Forminator_Addon_Form_Settings_Abstract|null
	 */
	protected function get_form_settings($form_id) {

		if (method_exists($this, 'get_addon_form_settings')) {
			return $this->get_addon_form_settings( $form_id );
		}

		return $this->get_addon_settings($form_id, 'form');
	}

	/**
	 * Check if FortressDB is connected with current form
	 *
	 * @param $form_id
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function is_form_connected( $form_id ) {
		$form_settings_instance = null;
		try {
			if ( ! $this->is_connected() ) {
				throw new FortressDB_Forminator_Addon_Exception( __( 'FortressDB is not connected', FortressDB::DOMAIN ) );
			}

			$form_settings_instance = $this->get_form_settings( $form_id );

			if ( ! $form_settings_instance instanceof FortressDB_Forminator_Addon_Form_Settings ) {
				throw new FortressDB_Forminator_Addon_Exception( __( 'Invalid Form Settings of FortressDB', FortressDB::DOMAIN ) );
			}

			// Mark as active when there is at least one active connection
			if ( false === $form_settings_instance->find_active_connection() ) {
				throw new FortressDB_Forminator_Addon_Exception( __( 'No active FortressDB connection found in this form', FortressDB::DOMAIN ) );
			}

			$is_form_connected = true;
		} catch ( FortressDB_Forminator_Addon_Exception $e ) {
			$is_form_connected = false;
			fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_WARNING );
		}

		/**
		 * Filter connected status of FortressDB with the form
		 *
		 * @param bool                                           $is_form_connected
		 * @param int                                            $form_id                Current Form ID
		 * @param FortressDB_Forminator_Addon_Form_Settings|null $form_settings_instance Instance of form settings, or null when unavailable
		 *
		 * @since 1.2
		 *
		 */
		$is_form_connected = apply_filters( 'forminator_addon_fortressdb_is_form_connected', $is_form_connected, $form_id, $form_settings_instance );

		return $is_form_connected;
	}

	/**
	 * Override on is_connected
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function is_connected() {
		return $this->fortressdb_plugin_is_installed();
	}

	/**
	 * Check if fortressdb plugin is installed, active and connected
	 *
	 * @return bool
	 */
	public function fortressdb_plugin_is_installed() {
		$is_installed = false;

		try {
			$is_installed = $this->authorize_app();
		} catch ( Exception $e ) {
			fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_WARNING );
		}

		return $is_installed;
	}

	/**
	 * FortressDB Authorize Page
	 *
	 * @return string
	 * @throws Exception
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function authorize_app() {
		$is_authorized = false;
		if ( ! $this->_plugin_token ) {
			$settings            = $this->get_settings_values();
			$this->_plugin_token = fdbarg( $settings, 'accessToken', '' );
		}

		if ( $this->_plugin_token ) {
			try {
				$validated = $this->validate_plugin_token( $this->_plugin_token );
				if ( ! $validated ) {
					throw new FortressDB_Forminator_Addon_Exception( $validated );
				}
				if ( ! $this->is_active() ) {
					$activated = Forminator_Addon_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Addon_Loader::get_instance()->get_last_error_message();
						throw new FortressDB_Forminator_Addon_Exception( $last_message );
					}
				}
				$is_authorized = true;
			} catch ( FortressDB_Forminator_Addon_Exception $e ) {
				fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_WARNING );
				$is_authorized = false;
			}
		}

		return $is_authorized;
	}

	/**
	 * Validate token with fortressdb API
	 *
	 * @param $token
	 *
	 * @return bool
	 * @throws Exception
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	private function validate_plugin_token( $token ) {
		$validated = false;

		try {
			$accessToken = FortressDB::get_api( $token )->accessToken();

			if ( ! $accessToken ) {
				throw new Exception( "Access Token validation failed" );
			}
			$validated = true;
		} catch ( FortressDB_Wp_Api_Exception $e ) {
		} catch ( Exception $e ) {
			fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_WARNING );
		}

		return $validated;
	}

	/**
	 * @param string|int $form_id
	 *
	 * @return array
	 * @throws FortressDB_Forminator_Addon_Exception
	 */
	public function get_form_feed( $form_id ) {
		if ( !empty($this->feed) ) {
			return $this->feed;
		}

		try {
			$feed_instance = $this->get_form_settings( $form_id );
			$this->feed    = $feed_instance->get_form_settings_values();
		} catch ( Exception $e ) {
			throw new FortressDB_Forminator_Addon_Exception( $e->getMessage() );
		}

		return $this->feed;
	}

	/**
	 * @param array $inputs
	 * @param array $field
	 * @param array $options
	 *
	 * @return array
	 *
	 * @throws FortressDB_Forminator_Addon_Exception
	 * @throws FortressDB_Form_Parser_Exception
	 */
	private function process_fields( $inputs, $field, $options = array() ) {
		/**
		 * @var FortressDB_Form_Parser $parser
		 * @var FortressDB_Field       $simple_field
		 */

		$element_id    = fdbarg( $field, 'element_id', '' );
		$type          = fdbarg( $options, 'type', '' );
		$default_value = fdbarg( $options, 'default_value', '' );
		$source        = fdbarg( $options, 'source', array() );
		$parser        = fdbarg( $options, 'parser', new FortressDB_Form_Parser( $this->_slug ) );

		if ( ! $parser ) {
			throw new FortressDB_Forminator_Addon_Exception( 'Invalid options: cannot find fields parser' );
		}

		$complex_fields = array();

		foreach ( $inputs as $input_name ) {
			$use_input = filter_var( fdbarg( $field, $input_name, false ), FILTER_VALIDATE_BOOLEAN );
			if ( $use_input ) {
				$caption             = fdbarg( $field, sprintf( '%s_label', $input_name ), '' );
				$placeholder         = fdbarg( $field, sprintf( '%s_placeholder', $input_name ), '' );
				// $allow_null          = ! fdbarg( $field, sprintf( '%s_required', $input_name ), false );
				$source['elementId'] = sprintf( '%s-%s', $element_id, $input_name );
				$simple_field        = $parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );

				$simple_field->init( array(
					'defaultValue' => $default_value,
					'allowNull'    => true,
					'type'         => 'string',
					'name'         => $element_id,
					'extend'       => array(
						'caption'     => $caption,
						'placeholder' => $placeholder,
						'source'      => $source,
					),
				) );

				/**
				 * @param FortressDB_Field $simple_field
				 * @param string|int       $element_id
				 * @param array            $field
				 * @param string           $input_name
				 * @param string           $type
				 *
				 * @return FortressDB_Field
				 */
				$simple_field = apply_filters( "fortressdb_process_field_{$this->_slug}",
					$simple_field, $element_id, $field, $input_name, $type );

				$field_name                    = $simple_field->get( 'extend.source.elementId' );
				$complex_fields[ $field_name ] = $simple_field;
			}
		}

		return $complex_fields;
	}

	/**
	 * @param string|int $form_id
	 * @param string|int $element_id
	 *
	 * @return array
	 *
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 * @throws FortressDB_Forminator_Addon_Exception
	 */
	public function get_enum( $form_id, $element_id ) {
		$this->get_form_feed( $form_id );
		$enum_id = fdbarg( $this->feed, array( 'linked_enums', $element_id ) );

		return $enum_id ? $this->api->get_enum( $enum_id ) : null;
	}

	/**
	 * @param FortressDB_Field $simple_field
	 * @param array            $field
	 * @param string|int       $element_id
	 * @param array            $options
	 *
	 * @return FortressDB_Field
	 *
	 * @throws FortressDB_Form_Parser_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	private function build_or_assign_enum( FortressDB_Field $simple_field, $field, $element_id, $options = array() ) {
		$source      = fdbarg( $options, 'source', array() );
		$caption     = fdbarg( $options, 'caption', '' );
		$description = fdbarg( $options, 'description', '' );
		$inputs_key  = fdbarg( $options, 'inputs_key', 'choices' );
		$id_key      = fdbarg( $options, 'id_key', 'id' );
		$label_key   = fdbarg( $options, 'label_key', 'label' );
		$field_name  = fdbarg( $options, 'field_name', $element_id );
		$enum        = fdbarg( $options, 'enum' );

		/**
		 * @var FortressDB_Enum_Value $simple_enum_value
		 */
		$simple_enum_value = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_ENUM );

		if ( ! $enum ) {
			$enum = $this->api->post_enum( $caption, $description, array( 'source' => $source ) );
		} elseif ( $caption !== $enum['name'] ) {
			$enum['options']['source'] = $source;
			$enum['name']              = $caption;

			$enum = $this->api->update_enum( $enum );
		}


		// get its enumValues
		$enum_id            = $enum['id'];
		$new_options        = array();
		$old_values         = array();
		$enum_values_by_val = array();
		$values             = array();
		$enum_values        = $this->api->get_enum_values( $enum_id );

		foreach ( $enum_values as $enum_value ) {
			$value    = fdbarg( $enum_value, 'options.value', '' );
			$values[] = $value;

			$enum_values_by_val[ $value ] = $enum_value;
		}

		foreach ( $field[ $inputs_key ] as $input ) {
			$new_options[] = $option_value = $input[ $id_key ];
			$is_default    = fdbarg( $input, 'isSelected', false );
			$label         = fdbarg( $input, $label_key, '' );

			if ( ! in_array( $option_value, $values ) ) {
				$new_values[] = $simple_enum_value
					->init( array(
						'label'   => $label,
						'enumId'  => $enum_id,
						'options' => array(
							'default' => $is_default,
							'value'   => $option_value,
							'source'  => $source,
						),
					) )
					->get();
			} else {
				$old_values[] = $simple_enum_value
					->init( $enum_values_by_val[ $option_value ] )
					->set_label( $label )
					->apply_options( 'default', $is_default )
					->get();
			}
		}

		if ( ! empty( $old_values ) ) {
			$this->api->update_enum_values( $enum_id, $old_values );
		}

		if ( ! empty( $new_values ) ) {
			$this->api->post_enum_values( $enum_id, $new_values );
		}

		// get updated enumValues
		$enum_values = $this->api->get_enum_values( $enum_id );

		// remove all unmatched values
		$values_to_delete = array();
		foreach ( $enum_values as $enum_value ) {
			$value = fdbarg( $enum_value, 'options.value', '' );
			if ( ! in_array( $value, $new_options ) ) {
				$values_to_delete[] = $enum_value['id'];
			}
		}

		if ( ! empty( $values_to_delete ) ) {
			$this->api->delete_enum_values( $enum_id, $values_to_delete );
			$enum_values = $this->api->get_enum_values( $enum_id );
		}

		// process enumValues after all changes
		// prepare them to store in form options
		$default_values = array();
		foreach ( $enum_values as $enum_value ) {
			$enum_value_id = $enum_value['id'];

			$is_default = fdbarg( $enum_value, 'options.default', false );
			if ( $is_default ) {
				$default_values[] = $enum_value_id;
			}

			// prepare linked_enum_values entries for this enum
			$value = trim( fdbarg( $enum_value, 'options.value', '' ) );

			fdbars( $this->feed, array( 'linked_enum_values', $enum_id, $value ), $enum_value_id );
		}

		fdbars( $this->feed, array( 'linked_enums', $field_name ), $enum_id );
		return $simple_field
			->set_default_value( $default_values )
			->apply_extend( 'enumId', $enum_id );
	}

	/**
	 * @param int|string $form_id
	 * @param array      $meta
	 *
	 * @throws FortressDB_Forminator_Addon_Exception
	 */
	private function update_form_feed( $form_id, $meta = array() ) {
		try {
			$feed_instance = $this->get_form_settings( $form_id );
			$feed_instance->save_form_settings_values( $meta ?: $this->feed );
		} catch ( Exception $e ) {
			throw new FortressDB_Forminator_Addon_Exception( $e->getMessage() );
		}
	}

	/**
	 * @param $values
	 *
	 * @return array
	 */
	public function on_get_settings_values( $values ) {
		$values = array_merge( $values, FortressDB::get_plugin_options() );

		return $values;
	}

	/**
	 * @param FortressDB_Field $simple_field
	 * @param string|int       $element_id
	 * @param array            $field
	 * @param string           $field_name
	 * @param string           $type
	 *
	 * @return FortressDB_Field
	 */
	public function on_process_field( $simple_field, $element_id, $field, $field_name, $type ) {
		$suffix = $field_name;
		switch ( $type ) {
			case 'name':
				switch ( $field_name ) {
					case 'fname':
						$suffix = 'first-name';
						break;
					case 'mname':
						$suffix = 'middle-name';
						break;
					case 'lname':
						$suffix = 'last-name';
						break;
				}
				$element_id_ext = sprintf( '%s-%s', $element_id, $suffix );
				$simple_field
					->set_name( $element_id_ext )
					->apply_extend( 'source.elementId', $element_id_ext );
				break;

			case 'postdata':
				switch ( $field_name ) {
					case 'post_content':
					case 'post_excerpt':
						$simple_field->set_type( FortressDB_Field::TEXT );
						$suffix = str_replace( '_', '-', $field_name );
						break;
					case 'post_image':
						$simple_field->set_type( FortressDB_Field::FILE );
						$suffix = str_replace( '_', '-', $field_name );
						break;
					case 'post_title':
					case 'post_tag':
						$simple_field->set_type( FortressDB_Field::STRING );
						$suffix = str_replace( '_', '-', $field_name );
						break;
				}
				$element_id_ext = sprintf( '%s-%s', $element_id, $suffix );
				$simple_field
					->set_name( $element_id_ext )
					->apply_extend( 'source.elementId', $element_id_ext );
				break;

			case 'address':
				switch ( $field_name ) {
					case 'address_city':
					case 'address_state':
					case 'address_zip':
					case 'address_country':
						$simple_field->set_type( FortressDB_Field::STRING );
						$suffix = str_replace( 'address_', '', $field_name );
						break;
				}
				$element_id_ext = sprintf( '%s-%s', $element_id, $suffix );
				$simple_field
					->set_name( $element_id_ext )
					->apply_extend( 'source.elementId', $element_id_ext );
				break;
		}

		return $simple_field;
	}

	/**
	 * @param FortressDB_Field $simple_field
	 * @param array            $field
	 * @param string|int       $element_id
	 * @param array            $type
	 *
	 * @return FortressDB_Field
	 */
	public function on_assign_enum( $simple_field, $field, $element_id, $type ) {
		if ( $type === 'select' ) {
			$simple_field->apply_extend( 'isMultiple', fdbarg( $field, 'value_type' ) === 'multiselect' );
		}

		return $simple_field;
	}

	public function on_forminator_after_addon_activated( $addon ) {
		if ($addon !== $this) {
			return;
		}

		$option = 'forminator_activated_addons';
		$activated_addons = get_option( $option, false );

		if ( ! empty( $activated_addons ) && in_array( $this->_slug, $activated_addons, true ) ) {
			return;
		}

		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( isset( $notoptions[ $option ] ) ) {
			wp_cache_delete( 'notoptions', 'options' );
		}

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions[ $option ] ) ) {
			wp_cache_delete( 'alloptions', 'options' );
		}
	}

	/**
	 * @param int        $enum_id
	 * @param string|int $field_name
	 */
	public function on_store_linked_enums( $enum_id, $field_name ) {
		fdbars( $this->feed, array( 'linked_enums', $field_name ), $enum_id );
	}

	/**
	 * @param int        $enum_id
	 * @param string|int $value
	 * @param int        $enum_value_id
	 */
	public function on_store_linked_enum_values( $enum_id, $value, $enum_value_id ) {
		fdbars( $this->feed, array( 'linked_enum_values', $enum_id, $value ), $enum_value_id );
	}

	// # FORM WIZARD CALLBACKS -----------------------------------------------------------------------------------------

	/**
	 * Wait Authorize Access wizard
	 *
	 * @return array
	 * @throws Exception
	 * @since 0.1.0 FortressDB Addon
	 */
	public function wait_authorization() {
		$template         = fortressdb_addons_dir( 'forminator', 'views/settings/connection/wait-authorize.php' );
		$template_success = fortressdb_addons_dir( 'forminator', 'views/settings/connection/success-authorize.php' );
		$template_fail    = fortressdb_addons_dir( 'forminator', 'views/settings/connection/fail-authorize.php' );
		$buttons          = array();
		$is_poll          = true;
		$template_params  = array(
			'token' => $this->_plugin_token,
		);

		$authorized = $this->authorize_app();
		if ( $authorized && $this->_plugin_token ) {
			$is_poll  = false;
			$template = $template_success;
		} else {
			$is_poll  = false;
			$template = $template_fail;
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function wait_authorization_success( $submitted_data ) {
		$settings_values = $this->get_settings_values();

		// check token set up
		return isset( $settings_values['accessToken'] ) && ! empty( $settings_values['accessToken'] );
	}
}
