<?php

// If Gravity Forms cannot be found, exit.
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

// load GFFeedAddOn framework
GFForms::include_feed_addon_framework();

// load FortressDB_Wp_Api
FortressDB::include_api();
FortressDB::include_files_api();
FortressDB::include_form_parser();

require_once( 'class-fortressdb-gforms-addon-exception.php' );

/**
 * @class FortressDB_GForms_Addon
 */
final class FortressDB_GForms_Addon extends GFFeedAddOn {
	
	/**
	 * @var FortressDB_GForms_Addon|null $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;
	protected $_version = FORTRESSDB_ADDON_GF_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gravityformsfortressdb';
	protected $_path = 'gravityformsfortressdb/fortressdb.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms FortressDB Add-On';
	protected $_short_title = 'FortressDB Add-On';
	/**
	 * @var array
	 */
	private $feed = array();
	/**
	 * @var FortressDB_Wp_Api
	 */
	private $api = null;
	
	/**
	 * GFFortressDB constructor.
	 *
	 * @throws Exception
	 */
	public function __construct() {
		$this->_path = sprintf( '%s/addons/%s/fortressdb.php',
			basename( FORTRESSDB_PLUGIN_DIR_PATH ), FORTRESSDB_ADDON_GF_NAME );
		
		try {
			$this->api = FortressDB::get_api();
		} catch ( FortressDB_Wp_Api_Exception $e ) {
		}
		
		parent::__construct();
	}
	
	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return FortressDB_GForms_Addon An instance of this class.
	 *
	 * @throws Exception
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new FortressDB_GForms_Addon();
		}
		
		return self::$_instance;
	}
	
	/**
	 * Implement our logic when addon initializing
	 */
	public function init() {
		parent::init();
		
		add_action( 'gform_after_save_form', array( $this, 'after_form_save' ), 10, 2 );
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
		add_action( 'store_linked_enums', array( $this, 'on_store_linked_enums' ), 10, 2 );
		add_action( 'store_linked_enum_values', array( $this, 'on_store_linked_enum_values' ), 10, 3 );
		
		add_filter( 'gforms_get_enum', array( $this, 'get_enum' ), 10, 1 );
		add_filter( "fortressdb_process_complex_field_{$this->_slug}", array(
			$this,
			'on_process_complex_field',
		), 10, 5 );
		add_filter( "fortressdb_assign_enum_{$this->_slug}", array( $this, 'on_assign_enum' ), 10, 4 );
	}
	
	/**
	 * @param array   $form
	 * @param boolean $is_new
	 *
	 * @return array
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_GF_Addon_Exception
	 * @throws FortressDB_Form_Parser_Exception
	 */
	public function after_form_save( $form, $is_new ) {
		$form_id = $form['id'];
		$this->get_form_feed( $form_id );
		
		$feed_id       = $this->feed['id'];
		$response      = null;
		$title         = fdbarg( $form, 'title', '' );
		$description   = fdbarg( $form, 'description', '' );
		$gforms_fields = fdbarg( $form, 'fields', array() );
		$source        = array( 'name' => 'GravityForms' );
		
		if ( $is_new ) {
			$metadata = $this->api->post_metadata( $title, $description, array( 'source' => $source ) );
		} else {
			$table_uuid = fdbarg( $this->feed, 'meta.metadata.tableName' );
			$metadata   = $table_uuid ? $this->api->get_metadata( $table_uuid ) : null;
			
			if ( ! $metadata ) {
				$metadata = $this->api->post_metadata( $title, $description, array( 'source' => $source ) );
			}
			
			$metadata['name']              = $title;
			$metadata['options']['source'] = $source;
			
			fdbars( $this->feed, array( 'meta', 'fields_group' ), array() );
			fdbars( $this->feed, array( 'meta', 'linked_enum_values' ), array() );
			
			$orders = array();
			foreach ( $metadata['fields'] as $field ) {
				$order = fdbarg( $field, 'extend.order', - 1 );
				if ( $order > 0 && $order < FORTRESSDB_MAX_FIELDS_ORDER ) {
					$orders[] = $order;
				}
			}
			asort( $orders );
			$field_order = end( $orders ) + 1;
			
			$result_fields  = array();
			$fdb_fields     = $fields_to_delete = fdbarg( $metadata, 'fields', array() );
			$form_parser    = new FortressDB_Form_Parser( $this->_slug );
			$complex_fields = array();
			
			foreach ( $gforms_fields as $field ) {
				$type          = fdbarg( $field, 'type', FortressDB_Field::STRING );
				$default_value = fdbarg( $field, 'defaultValue', '' );
				$placeholder   = fdbarg( $field, 'placeholder', '' );
				$description   = fdbarg( $field, 'description', '' );
				$caption       = trim( fdbarg( $field, 'label', '' ) );
				// $allow_null    = ! (bool) fdbarg( $field, 'isRequired', false );
				$element_id    = $source['elementId'] = fdbarg( $field, 'id', 0 );
				$invalid_type = false;
				$default_data = array(
					'defaultValue' => $default_value,
					'allowNull'    => true,
					'type'         => $type,
					'name'         => sprintf( 'field-%s', $element_id ),
					'extend'       => array(
						'caption'     => $caption,
						'order'       => $field_order,
						'description' => $description,
						'placeholder' => $placeholder,
						'source'      => $source,
						'encrypted'   => false,
					),
				);
				
				/**
				 * @var FortressDB_Field $simple_field
				 */
				$simple_field = $form_parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
				$simple_field->init( $default_data );
				
				if ( $type === 'post_category' ) {
					$type = fdbarg( $field, 'inputType', 'select' );
				}
				
				switch ( $type ) {
					case 'phone':
					case 'email':
					case 'website':
					case 'calculation':
					case 'password':
					case 'post_title':
						$simple_field->set_type( FortressDB_Field::STRING );
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					
					case 'html':
					case 'text':
					case 'textarea':
					case 'post_excerpt':
					case 'post_body':
					case 'post_tags':
					case 'hidden':
						$simple_field->set_type( FortressDB_Field::TEXT );
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					
					case 'fileupload':
					case 'post_image':
						$simple_field->set_type( FortressDB_Field::FILE );
						$simple_field->set_default_value();
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					
					case 'number':
						$simple_field->set_type( FortressDB_Field::FLOAT );
						$simple_field->set_default_value( (float) fdbarg( $field, 'defaultValue', 0 ) );
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					
					// fields possible with enums
					case 'name':
					case 'address':
						$inputs_key = $type === 'checkbox' ? 'choices' : 'inputs';
						$id_key     = $type === 'checkbox' ? 'value' : 'id';
						$options    = array(
							'api'          => $this->api,
							'default_data' => $default_data,
							'type'         => $type,
							'inputs_key'   => $inputs_key,
							'id_key'       => $id_key,
							'caption'      => $caption,
							'description'  => $description,
							'source'       => $source,
						);

						foreach ( $field[ $inputs_key ] as $index => $input ) {
							$options['field_name'] = $input[ $id_key ];
							$simple_field          = $form_parser->process_complex_field( $input, $element_id, $options );

							if ( ! is_null( $simple_field ) ) {
								/**
								 * @param FortressDB_Field $simple_field
								 * @param string|int       $element_id
								 * @param array            $field
								 * @param array            $input
								 * @param array            $options
								 *
								 * @return FortressDB_Field
								 */
								$simple_field = apply_filters( "fortressdb_process_complex_field_{$this->_slug}",
									$simple_field, $element_id, $field, $input, $options );

								$field_name                    = $simple_field->get( 'extend.source.elementId' );
								$complex_fields[ $field_name ] = $simple_field;
							}
						}
						break;
					
					// fields with enums
					case 'checkbox':
					case 'select':
					case 'radio':
					case 'multiselect':
						$simple_field->set_type( FortressDB_Field::ENUM );
						$simple_field->set_default_value( (array) fdbarg( $field, 'defaultValue', array() ) );
						
						$options      = array(
							'api'         => $this->api,
							'inputs_key'  => 'choices',
							'id_key'      => 'value',
							'label_key'   => 'text',
							'caption'     => $caption,
							'description' => $description,
							'source'      => $source,
							'enum'        => apply_filters( 'gforms_get_enum', $element_id ),
							'type'        => $type,
						);
						$simple_field = $form_parser->build_or_assign_enum( $simple_field, $field, $element_id, $options );
						
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
						break;
					
					case 'time':
						$time_type       = fdbarg( $field, 'timeFormat', '12' );
						$inputs          = fdbarg( $field, 'inputs', array() );
						$default_hours   = 0;
						$default_minutes = 0;
						$default_ampm    = '';
						$is_long_format  = (bool) ( $time_type === '24' );
						
						foreach ( $inputs as $input ) {
							switch ( $input['id'] ) {
								case sprintf( '%s.1', $element_id );
									$default_hours = fdbarg( $input, 'defaultValue', 0 );
									break;
								
								case sprintf( '%s.2', $element_id ):
									$default_minutes = fdbarg( $input, 'defaultValue', 0 );
									break;
								
								case sprintf( '%s.3', $element_id ):
									$default_ampm = fdbarg( $input, 'defaultValue', '' );
									break;
							}
						}
						
						$extra_time    = ! $is_long_format && strtolower( $default_ampm ) === 'pm' ? 12 : 0;
						$default_hours = $default_hours + $extra_time;
						$minutes       = $default_minutes * 60; // 01 * 60
						$hours         = $default_hours * 3600; // 01 * 60 * 60
						
						$simple_field
							->set_type( FortressDB_Field::TIME )
							->set_default_value( $hours + $minutes )
							->apply_extend( 'format.long', $is_long_format );
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					
					case 'date':
						$simple_field->set_type( FortressDB_Field::DATE );
						$simple_field->set_default_value();
						if ( $default_value ) {
							$simple_field->set_default_value( date( 'm/d/Y' ) );
						}
						
						$complex_fields[ $element_id ] = $simple_field;
						break;
					case 'list':
						$simple_field->set_type(FortressDB_Field::JSON);
						$complex_fields[$element_id] = $simple_field;
					default:
						$invalid_type = true;
						break;
				}
				
				if ( $invalid_type ) {
					continue;
				}
			}
			
			foreach ( $complex_fields as $element_id => $simple_field ) {
				FortressDB_Field::check_in_existing( $result_fields, $fdb_fields, $field_order, $element_id, $simple_field );
				$fields_to_delete = FortressDB_Field::check_candidates_to_delete( $fields_to_delete, $element_id );
			}
			
			$fdb_fields       = array_values( $fdb_fields );
			$result_fields    = array_values( $result_fields );
			$fields_to_delete = array_values( $fields_to_delete );
			
			if ( ! empty( $fdb_fields ) ) {
				foreach ( $fdb_fields as $field ) {
					$result_fields[] = $field;
				}
			}
			
			if ( ! empty( $fields_to_delete ) ) {
				foreach ( $result_fields as $index => $result_field ) {
					$element_id = fdbarg( $result_field, 'extend.source.elementId' );
					foreach ( $fields_to_delete as $item ) {
						$item_name = fdbarg( $item, 'extend.source.elementId' );
						
						if ( $item_name ) {
							if ( $item['type'] === 'enum' ) {
								$removable_enum_ids[] = fdbarg( $this->feed, array(
									'meta',
									'linked_enums',
									$item_name,
								) );
								fdbarr( $this->feed, array( 'meta', 'linked_enums', $item_name ) );
							}
							
							if ( $element_id && $item_name === $element_id ) {
								unset( $result_fields[ $index ] );
							}
						}
					}
				}
				
				if ( ! empty( $removable_enum_ids ) ) {
					$this->api->delete_enums( $removable_enum_ids );
				}
			}
			
			$metadata['fields'] = array_values( $result_fields );
			$metadata = $this->api->update_metadata( $metadata );
			
			// update stored fields_group due of changes of metadata in the backend DB
			foreach ( $metadata['fields'] as $field ) {
				$element_id = fdbarg( $field, 'extend.source.elementId' );
				if ( $element_id ) {
					fdbars( $this->feed, array( 'meta', 'fields_group', $element_id ), $field );
				}
			}
		}
		
		fdbars( $this->feed, array( 'meta', 'metadata' ), $metadata );
		$this->update_form_feed( $feed_id, $form_id );
		
		return $form;
	}
	
	/**
	 * @param int $form_id
	 *
	 * @return array
	 * @throws FortressDB_GF_Addon_Exception
	 */
	private function get_form_feed( $form_id ) {
		$this->feed = array();
		$feeds      = GFAPI::get_feeds( null, $form_id, $this->_slug );
		
		if ( is_wp_error( $feeds ) ) {
			if ( $feeds->get_error_code() !== 'not_found' ) {
				throw new FortressDB_GF_Addon_Exception( $feeds->get_error_message() );
			}
			
			$feed_id = GFAPI::add_feed( $form_id, array(), $this->_slug );
			if ( is_wp_error( $feed_id ) ) {
				throw new FortressDB_GF_Addon_Exception( $feed_id->get_error_message() );
			}
			
			$feeds = GFAPI::get_feeds( array( $feed_id ), $form_id, $this->_slug );
			if ( is_wp_error( $feeds ) ) {
				throw new FortressDB_GF_Addon_Exception( $feeds->get_error_message() );
			}
		}
		
		if ( ! empty( $feeds ) ) {
			$this->feed = $feeds[0];
		}
		
		return $this->feed;
	}
	
	/**
	 * @param int   $feed_id
	 * @param int   $form_id
	 * @param array $meta
	 *
	 * @return int
	 * @throws FortressDB_GF_Addon_Exception
	 */
	private function update_form_feed( $feed_id, $form_id, $meta = array() ) {
		$result = GFAPI::update_feed( $feed_id, $meta ?: $this->feed['meta'], $form_id );
		if ( is_wp_error( $result ) ) {
			throw new FortressDB_GF_Addon_Exception( $result->get_error_message() );
		}
		
		return $result;
	}
	
	/**
	 * @param $entry
	 * @param $form
	 *
	 * @throws FortressDB_GF_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws Exception
	 */
	public function after_submission( $entry, $form ) {
		$get_field = function ($id) use ($form) {
			foreach ($form['fields'] as $field) {
				if($field['id'] === $id) {
					return $field;
				}
			}
			return null;
		};

		$this->get_form_feed( $form['id'] );
		$data         = array();
		$table_name   = fdbarg( $this->feed, array( 'meta', 'metadata', 'tableName' ), '' );
		$fields_group = fdbarg( $this->feed, array( 'meta', 'fields_group' ), array() );
		
		foreach ( $fields_group as $field_id => $field ) {
			$field_data = fdbarg( $entry, $field_id ) ?: $field['defaultValue'];
			$field_type = $field['type'];
			$field_name = $field['name'];
			
			switch ( $field_type ) {
				case 'time':
					$field_data     = trim( $field_data );
					$is_long_format = fdbarg( $field, 'extend.format.long', false );
					$parts          = explode( ':', $field_data );
					if (2 == count($parts)) {
						$hours   = (int)$parts[0];
						$minutes = (int)$parts[1];

						if (!$is_long_format) {
							$parts_1 = explode(' ', $parts[1]);
							$minutes = (int)$parts_1[0];
							$ampm    = isset($parts_1[1]) ? strtolower($parts_1[1]) : 'am';
							$hours   = $hours + ($ampm === 'pm' ? 12 : 0);
						}

						$hours_value   = $hours * 3600; // 01 * 60 * 60
						$minutes_value = $minutes * 60; // 01 * 60

						$data[$field_name] = $hours_value + $minutes_value;
					} else {
						$data[$field_name] = fdbarg($field, 'defaultValue', null);
					}
					break;
				
				case 'enum':
					$form_field = $get_field($field_id);
					if ( $form_field['type'] === 'checkbox' ) {
						$field_data = array();
						foreach ( $form_field['inputs'] as $input ) {
							if( isset($entry[$input['id']]) && !empty($entry[$input['id']]) ) {
								$field_data[] = $entry[$input['id']];
							}
						}
					} else if ($form_field['type'] === 'multiselect') {
						$decoded    = @json_decode($field_data, true);
						$field_data = is_array($decoded) ? $decoded : (array) $field_data;
					} else {
						$field_data = is_array($field_data) ? $field_data : (array) $field_data;
					}

					$enum_id             = fdbarg( $this->feed, array( 'meta', 'linked_enums', $field_id ) );
					$data[ $field_name ] = array();
					
					foreach ( $field_data as $value ) {
						$enum_value_id = fdbarg( $this->feed, array(
							'meta',
							'linked_enum_values',
							$enum_id,
							$value,
						) );
						
						$data[ $field_name ][] = $enum_value_id;
					}
					break;
				
				case 'file':
					if ( $field_data ) {
						$files = @json_decode($field_data);
						if (null === $files) {
							$files = array($field_data);
						}
						$data[$field_name] = array();

						foreach ($files as $file_name) {
							$wp_content_base_name = basename(WP_CONTENT_DIR);
							$file_path            = sprintf('%s/%s', WP_CONTENT_DIR, explode("{$wp_content_base_name}/", $file_name)[1]);
							if (!file_exists($file_path) || !is_file($file_path)) {
								throw new FortressDB_GF_Addon_Exception("The file {$file_path} does not exist!");
							}

							try {
								$delimiter    = '-------------' . uniqid();
								$file         = new FortressDB_File($file_path);
								$content_type = $file->Mime();

								$body = FortressDB_BodyPost::Get(array(
									'fileName'    => $file->Name(),
									'type'        => substr($content_type, 0, 5) == 'image' ? 'image' : 'file',
									'contentType' => $content_type,
									'tableName'   => $table_name,
									'raw'         => $file,
								), $delimiter);

								$args = array(
									'body'    => $body,
									'headers' => array(
										'Content-Type'   => 'multipart/form-data; boundary=' . $delimiter,
										'Content-Length' => strlen($body),
										'Authorization'  => 'Bearer ' . $this->api->accessToken(),
									),
									'timeout' => 60,
								);

								fortressdb_log(array(
									'url'  => $this->api->baseUrl('files'),
									'args' => $args,
								));
								$response = wp_remote_post($this->api->baseUrl('files'), $args);
								if (is_wp_error($response)) {
									throw new FortressDB_GF_Addon_Exception($response->get_error_message());
								}

								$files_res = json_decode(wp_remote_retrieve_body($response), true);

								$data[$field_name][] = array(
									'name' => $file->Name(),
									'type' => $content_type,
									'uuid' => $files_res[0]['uuid'],
									'size' => $file->Size(),
								);
							} catch (Exception $e) {
								fortressdb_log(array(__METHOD__, $e->getMessage()), FORTRESSDB_LOG_LEVEL_ERROR);
								throw $e;
							}
						}
					}
					break;
				
				case 'boolean':
					$value               = fdbarg( $field, 'extend.source.value', '' );
					$field_data          = fdbarg( $entry, $field_id, '' );
					$data[ $field_name ] = $field_data && $value === $field_data;
					break;
				case 'json':
					$data[ $field_name ] = (array)unserialize($field_data);
					break;
				default:
					$data[ $field_name ] = $field_data;
					break;
			}
		}
		
		$this->api->post_objects( $data, $table_name );
	}
	
	/**
	 * @param FortressDB_Field $simple_field
	 * @param string|int       $element_id
	 * @param array            $field
	 * @param array            $input
	 * @param array            $options
	 *
	 * @return FortressDB_Field
	 */
	public function on_process_complex_field( $simple_field, $element_id, $field, $input, $options = array() ) {
		$parser     = fdbarg( $options, 'parser' );
		$type       = fdbarg( $options, 'type' );
		$caption    = fdbarg( $options, 'caption' );
		$field_name = fdbarg( $options, 'field_name' );
		
		switch ( $type ) {
			case 'name':
				if ( $input['id'] === "{$element_id}.2" ) { // prefix
					$simple_field->set_type( FortressDB_Field::ENUM );
					
					$options['inputs_key'] = 'choices';
					$options['id_key']     = 'value';
					$options['label_key']  = 'text';
					$options['caption']    = sprintf( '%s %s', $caption, $input['label'] );
					$options['enum']       = apply_filters( 'gforms_get_enum', $field_name );
					$simple_field          = $parser->build_or_assign_enum( $simple_field, $input, $element_id, $options );
				}
				// $is_required = fdbarg( $field, 'isRequired', false );
				$simple_field
					->set_name( sprintf( 'field-%s', $input['id'] ) )
					->set_is_required( false );
				break;
			
			case 'address':
				$default_value = $simple_field->get( 'defaultValue', '' );
				if ( $input['id'] === "{$element_id}.6" ) { // country
					$default_value = $default_value ?: fdbarg( $field, 'defaultCountry', '' );
				}
				
				$simple_field
					->set_name( sprintf( 'field-%s', $input['id'] ) )
					->set_default_value( $default_value );
				break;
			
			case 'checkbox':
				$caption       = fdbarg( $input, 'text', '' );
				$value         = fdbarg( $input, 'value', '' );
				$is_selected   = fdbarg( $input, 'isSelected', false );
				$default_value = filter_var( $is_selected, FILTER_VALIDATE_BOOLEAN );
				
				$simple_field
					->set_type( FortressDB_Field::BOOLEAN )
					->set_default_value( $default_value )
					->apply_extend( 'caption', $caption )
					->apply_extend( 'source.value', $value );
				
				foreach ( $field['inputs'] as $index => $_input ) {
					if ( $caption !== $_input['label'] ) {
						continue;
					}
					$id = fdbarg( $field, array( 'inputs', $index, 'id' ) );
					$simple_field
						->set_name( sprintf( 'field-%s', $id ) )
						->apply_extend( 'source.elementId', $id );
				}
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
		if ( $type === 'multiselect' || $type === 'checkbox' ) {
			$simple_field->apply_extend( 'isMultiple', true );
		}
		
		return $simple_field;
	}
	
	/**
	 * @param string|int $field_name
	 *
	 * @return mixed|null
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function get_enum( $field_name ) {
		$enum_id = fdbarg( $this->feed, array( 'meta', 'linked_enums', $field_name ) );
		
		return $enum_id ? $this->api->get_enum( $enum_id ) : null;
	}
	
	/**
	 * @param int        $enum_id
	 * @param string|int $field_name
	 */
	public function on_store_linked_enums( $enum_id, $field_name ) {
		fdbars( $this->feed, array( 'meta', 'linked_enums', $field_name ), $enum_id );
	}
	
	/**
	 * @param int        $enum_id
	 * @param string|int $value
	 * @param int        $enum_value_id
	 */
	public function on_store_linked_enum_values( $enum_id, $value, $enum_value_id ) {
		fdbars( $this->feed, array( 'meta', 'linked_enum_values', $enum_id, $value ), $enum_value_id );
	}
}
