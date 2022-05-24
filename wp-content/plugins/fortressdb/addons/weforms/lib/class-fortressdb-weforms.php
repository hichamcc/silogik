<?php

require_once( 'class-fortressdb-weforms-addon-exception.php' );

/**
 * Class FortressDB_WeForms_Addon
 */
class FortressDB_WeForms_Addon extends WeForms_Abstract_Integration {

	private $_slug = 'weformsfortressdb';

	/**
	 * @var array
	 */
	private $form_settings = array();

	/**
	 * @var FortressDB_Wp_Api
	 */
	private $api = null;

	/**
	 * @var FortressDB_Form_Parser
	 */
	private $parser = null;

	/**
	 * @var array
	 */
	protected $parsers = array();

	/**
	 * @var array
	*/
	protected $processors = array();


	/**
	 * Initialize the plugin
	 *
	 * @throws Exception
	 */
	public function __construct() {
		$this->id       = 'fortressdb';
		$this->title    = __( 'FortressDB', FortressDB::DOMAIN );
		$this->icon     = fortressdb_addon_assets_url( FORTRESSDB_ADDON_WEFORMS_NAME, 'images/fortressdb.png' );
		$this->template = dirname(__FILE__) . '/component/template.php';

		$this->settings_fields = array(
			'enabled' => false,
			'group'   => array(),
			'stage'   => 'subscriber',
			'fields'  => array(
				'email'      => '',
				'first_name' => '',
				'last_name'  => '',
			),
		);

		FortressDB::include_files_api();
		FortressDB::include_form_parser();


		try {
			$this->api    = FortressDB::get_api();
			$this->parser = new FortressDB_Form_Parser( $this->_slug );
		} catch ( FortressDB_Wp_Api_Exception $e ) {
			fortressdb_log( $e->getMessage(), FORTRESSDB_LOG_LEVEL_ERROR );
		}


		if ( fortressdb_is_connected() ) {
			add_action('weforms_update_form', array($this, 'on_form_update'), 10, 3);
			add_filter('weforms_entry_submission_response', array($this, 'on_form_submission'), 10, 1);
			add_action('delete_post', array($this, 'on_post_delete'), 10, 1);
			add_filter("fortressdb_assign_enum_{$this->_slug}", array($this, 'on_assign_enum'), 10, 4);
		}

		add_filter( 'admin_footer', array( $this, 'load_template' ) );
		add_filter( 'weforms_builder_scripts', array( $this, 'enqueue_mixin' ) );
		$this->init_parsers();
	}

	/**
	 * Enqueue the mixin
	 *
	 * @param $scripts
	 *
	 * @return void
	 */
	public function enqueue_mixin( $scripts ) {

		$scripts['weforms-int-fortressdb'] = array(
			'src' => plugins_url( 'component/index.js', __FILE__ ),
			'deps' => array( 'weforms-form-builder-components' )
		);

		return $scripts;
	}



	protected function init_parsers() {
		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_string_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::STRING );

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_text_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::TEXT );

			return true;
		};


		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_name_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field        = (array) fdbarg( $options, 'field', array() );
			$default_data = (array) fdbarg( $options, 'default_data', array() );
			// $is_required  = (bool) fdbarg( $options, 'is_required', false );

			$inputs = explode( '-', fdbarg( $options, 'field.format', '' ) );

			foreach ( $inputs as $prefix ) {
				$input_name    = sprintf( '%s_name', $prefix );
				$input         = fdbarg( $field, $input_name, array() );
				$field_name    = sprintf( '%s:%s', $element_id, $prefix );
				$caption       = fdbarg( $input, 'sub', '' );
				$default_value = fdbarg( $input, 'default', '' );
				$placeholder   = fdbarg( $input, 'placeholder', '' );
				$simple_field  = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
				$simple_field->init( $default_data )
							 ->set_name($field_name)
							 ->set_type( FortressDB_Field::STRING )
							 ->set_default_value( $default_value )
							 ->apply_extend( 'caption', $caption )
							 ->apply_extend( 'placeholder', $placeholder )
							 ->apply_extend( 'isMandatory', false )
							 ->apply_extend( 'source.elementId', $field_name );

				$complex_fields[ $field_name ] = $simple_field;
			}

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_enum_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$field   = (array) fdbarg( $options, 'field', array() );
			$caption = (string) fdbarg( $options, 'caption', '' );
			$source  = (array) fdbarg( $options, 'source', array() );
			$form_id = fdbarg( $options, 'form_id', '' );
			$type    = fdbarg( $options, 'type', '' );

			$selected = (array) fdbarg( $options, 'field.selected', array() );

			$simple_field->set_type( FortressDB_Field::ENUM )
						 ->set_default_value( $selected )
						 ->apply_extend('encrypted', false);

			$options      = array(
				'inputs_key'  => 'options',
				'caption'     => $caption,
				'description' => '',
				'source'      => $source,
				'enum'        => $this->get_enum( $form_id, $element_id ),
				'type'        => $type,
				'form_id'     => $form_id,
			);
			$simple_field = $this->build_or_assign_enum( $simple_field, $field, $element_id, $options );

			/**
			 * @param FortressDB_Field $simple_field
			 * @param string|int       $element_id
			 * @param array            $field
			 * @param string           $type
			 *
			 * @return FortressDB_Field
			 */
			$simple_field = apply_filters( "fortressdb_assign_enum_{$this->_slug}", $simple_field, $element_id, $field, $type );

			$complex_fields[ $element_id ] = $simple_field;

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_date_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$is_time_enabled = filter_var( fdbarg( $options, 'field.time' ), FILTER_VALIDATE_BOOLEAN );
			$default_value   = fdbarg( $options, 'field.default', '' );

			$field_type    = $is_time_enabled ? FortressDB_Field::DATETIME : FortressDB_Field::DATE;
			$default_value = $default_value ? date( 'm/d/Y' ) : null;
			$simple_field  = $simple_field->set_type( $field_type )->set_default_value( $default_value );

			$complex_fields[ $element_id ] = $simple_field;

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_file_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::FILE )->set_default_value();

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_number_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$default_value = (float) fdbarg( $options, 'field.default', 0 );

			$complex_fields[ $element_id ] = $simple_field->set_type( FortressDB_Field::FLOAT )->set_default_value( $default_value );

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_repeat_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::STRING )
				->apply_extend( 'original_type', 'repeat_field');
			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_hidden_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$caption = sprintf("Hidden (%s)", $element_id);
			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::STRING )
				->apply_extend( 'caption', $caption );
			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_address_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$default_data = (array) fdbarg( $options, 'default_data', array() );
			$fields = (array) fdbarg( $options, 'field.address', array() );
			$common_caption = (string) fdbarg($options, 'caption', '');
			foreach ($fields as $field_id => $field_options) {
				$is_enabled = (bool) (fdbarg($field_options, 'checked', 'no') === 'checked');
				if (!$is_enabled) {
					continue;
				}
				$field_name = sprintf("%s:%s", $element_id, $field_id);
				// $is_required = (bool) (fdbarg($field_options, 'required', 'no') === 'checked');
				$default_value = (string) fdbarg($field_options, 'value', '');
				$simple_field  = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
				$caption = sprintf("%s - %s", $common_caption, fdbarg($field_options, 'label', ''));
				$placeholder = (string) fdbarg($field_options, 'placeholder', '');

				$simple_field->init($default_data)
					->set_type(FortressDB_Field::STRING)
					->set_name($field_name)
					->set_default_value($default_value)
					->apply_extend( 'caption', $caption )
					->apply_extend( 'placeholder', $placeholder )
					->apply_extend( 'isMandatory', false )
					->apply_extend( 'original_type', 'address_field')
					->apply_extend( 'source.elementId', $field_name );

				$complex_fields[ $field_name ] = $simple_field;
			}
			return true;
		};


		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_signature_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::FILE )
				->apply_extend( 'original_type', 'signature_field')
				->set_default_value();
			return true;
		};


		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_column_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$inner_fields = fdbarg($options, 'field.inner_fields', array());
			$field_order = fdbarg($options, 'default_data.extend.order', array());
			foreach ($inner_fields as $column => $fields) {
				foreach ($fields as $field) {
					$element_id = fdbarg( $field, 'name', '' );
					$this->parse_field($complex_fields, $field, $field_order);
					if (isset($complex_fields[$element_id])) {
						$field_name = $column . "_" . $element_id;
						$complex_fields[$element_id]->set_name($field_name);
					}
				}
			}
		};


		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_grid_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::JSON )
				->set_name($element_id)
				->apply_extend('encrypted', false);

			return true;
		};

		/**
		 * @param array            $complex_fields
		 * @param string|int       $element_id
		 * @param FortressDB_Field $simple_field
		 * @param array            $options
		 *
		 * @return bool
		 */
		$parse_toc_fields = function ( &$complex_fields, $element_id, $simple_field, $options = array() ) {
			$complex_fields[ $element_id ] = $simple_field
				->set_type( FortressDB_Field::BOOLEAN )
				->set_name($element_id)
				->apply_extend( 'caption', 'Terms and Conditions' )
				->apply_extend( 'original_type', 'toc');
			return true;
		};


		/**
		* @param array  $data
		* @param array  $submitted_data
		* @param array  $field
		 */
		$process_repeat_field = function(array &$data, $submitted_data, $field) {
			$field_name = fdbarg($field, 'name');
			$source = fdbarg($field, 'extend.source.elementId');
			if ( $source === null) {
				return;
			}
			$field_data = fdbarg($submitted_data, array('data', $source), fdbarg($field, 'defaultValue'));
			$data[$field_name] = implode("| ", $field_data);
		};

		/**
		 * @param array                 $data
		 * @param array                 $submitted_data
		 * @param FortressDB_Field      $field
		 */
		$process_address_field = function(array &$data, $submitted_data, $field) {
			$field_name = fdbarg($field, 'name');
			list($source_field, $current_field) = explode(":", $field_name);
			$address_data = fdbarg($submitted_data, array('data', $source_field), array());
			$field_data = fdbarg($address_data, $current_field);
			$field_data = $field_data ? $field_data : fdbarg($field, 'defaultValue');
			$data[$field_name] = $field_data;
		};


		/**
		 * @param array                 $data
		 * @param array                 $submitted_data
		 * @param FortressDB_Field      $field
		 */
		$process_signature_field = function(array &$data, $submitted_data, $field) {
			$signature_data = (string)fdbarg($submitted_data, array(
				'data',
				'wpuf_signature_image'
			), '');
			list($type, $base64) = explode(',', $signature_data);
			$image = base64_decode($base64);
			$field_name = fdbarg($field, 'name');

			$form_id = fdbarg( $submitted_data, 'form_id' );
			$this->get_form_settings( $form_id );

			$table_name = fdbarg( $this->form_settings, 'metadata.tableName', '' );
			$file_name = $field_name . '_' . uniqid() . '.png';
			$file = new FortressDB_File($file_name, 'image/png', $image);
			$data[$field_name][] = $this->upload_file($file, $table_name);
		};


		/**
		 * @param array                 $data
		 * @param array                 $submitted_data
		 * @param FortressDB_Field      $field
		 */
		$process_toc_field = function (array &$data, $submitted_data, $field) {
			$toc_data = (string)fdbarg($submitted_data, array(
				'data',
				'wpuf_accept_toc'
			), 'off');
			$field_name = fdbarg($field, 'name');
			$toc_accepted = (bool)($toc_data === 'on');
			$data[$field_name] = $toc_accepted;
		};


		$this->parsers['email_address']        = $parse_string_fields;
		$this->parsers['website_url']          = $parse_string_fields;
		$this->parsers['custom_html']          = $parse_text_fields;
		$this->parsers['text_field']           = $parse_text_fields;
		$this->parsers['textarea_field']       = $parse_text_fields;
		$this->parsers['checkbox_field']       = $parse_enum_fields;
		$this->parsers['name_field']           = $parse_name_fields;
		$this->parsers['dropdown_field']       = $parse_enum_fields;
		$this->parsers['multiple_select']      = $parse_enum_fields;
		$this->parsers['radio_field']          = $parse_enum_fields;
		$this->parsers['date_field']           = $parse_date_fields;
		$this->parsers['file_upload']          = $parse_file_fields;
		$this->parsers['image_upload']         = $parse_file_fields;
		$this->parsers['number_field']         = $parse_number_fields;
		$this->parsers['numeric_text_field']   = $parse_number_fields;

		$this->parsers['phone_field']          = $parse_string_fields;
		$this->parsers['country_list_field']   = $parse_string_fields;
		$this->parsers['custom_hidden_field']  = $parse_hidden_fields;
		$this->parsers['repeat_field']         = $parse_repeat_fields;
		$this->parsers['address_field']        = $parse_address_fields;
		$this->parsers['signature_field']      = $parse_signature_fields;
		$this->parsers['google_map']           = $parse_string_fields;
		$this->parsers['column_field']         = $parse_column_fields;
		$this->parsers['ratings']              = $parse_enum_fields;
		$this->parsers['linear_scale']         = $parse_number_fields;
		$this->parsers['multiple_choice_grid'] = $parse_grid_fields;
		$this->parsers['checkbox_grid']        = $parse_grid_fields;
		$this->parsers['toc']                  = $parse_toc_fields;

		$this->processors['repeat_field']      = $process_repeat_field;
		$this->processors['address_field']     = $process_address_field;
		$this->processors['signature_field']   = $process_signature_field;
		$this->processors['toc']               = $process_toc_field;
	}


	/**
	 * @param FortressDB_File $file
	 * @param string $table_name
	 * @return array
	 * @throws FortressDB_WeForms_Addon_Exception
	 */
	protected function upload_file(FortressDB_File $file, $table_name) {
		try {
			$delimiter = '-------------' . uniqid();
			$content_type = $file->Mime();

			$body = FortressDB_BodyPost::Get(array(
				'fileName' => $file->Name(),
				'type' => substr($content_type, 0, 5) == 'image' ? 'image' : 'file',
				'contentType' => $content_type,
				'tableName' => $table_name,
				'raw' => $file,
			), $delimiter);

			$args = array(
				'body' => $body,
				'headers' => array(
					'Content-Type' => 'multipart/form-data; boundary=' . $delimiter,
					'Content-Length' => strlen($body),
					'Authorization' => 'Bearer ' . $this->api->accessToken(),
				),
				'timeout' => 60,
			);

			$response = wp_remote_post($this->api->baseUrl('files'), $args);
			if (is_wp_error($response)) {
				throw new FortressDB_WeForms_Addon_Exception($response->get_error_message());
			}

			$files_res = json_decode(wp_remote_retrieve_body($response), true);

			return array(
				'name' => $file->Name(),
				'type' => $content_type,
				'uuid' => $files_res[0]['uuid'],
				'size' => $file->Size(),
			);
		} catch (Exception $e) {
			fortressdb_log(array(
				__METHOD__,
				$e->getMessage(),
			), FORTRESSDB_LOG_LEVEL_ERROR);
			throw $e;
		}
	}

	/**
	 * @param int|string $form_id
	 * @param array      $form
	 * @param bool       $update
	 *
	 * @throws FortressDB_WeForms_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function on_form_save( $form_id, $form, $update ) {
		$integration = weforms_is_integration_active( $form_id, $this->id );
		if ( false === $integration ) {
			return;
		}

		$form   = (array) $form;
		$source = array( 'name' => 'WeForms' );
		$this->get_form_settings( $form_id );

		$title       = fdbarg( $form, 'post_title', '' );
		$description = fdbarg( $form, 'post_excerpt', '' );

		if ( $update ) {
			$table_uuid = fdbarg( $this->form_settings, 'metadata.tableName', '' );
			$metadata   = $this->api->get_metadata( $table_uuid );
			if ( ! $metadata ) {
				$metadata = $this->api->post_metadata( $title, $description, array( 'source' => $source ) );
			}

			$table_id = fdbarg( $metadata, 'id' );
			if ( $table_id ) {
				$metadata['name']              = $title;
				$metadata['description']       = $description;
				$metadata['options']['source'] = $source;

				$metadata = $this->api->update_metadata( $metadata );
			}
		} else {
			$metadata = $this->api->post_metadata( $title, $description, array( 'source' => $source ) );
		}

		fdbars( $this->form_settings, 'metadata', $metadata );

		$this->update_form_settings( $form_id );
	}

	protected function parse_field(&$complex_fields, $field, $field_order, $form_id) {
		$source   = array( 'name' => 'WeForms' );
		/**
		 * @var FortressDB_Field $simple_field
		 */
		$simple_field        = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
		$element_id          = fdbarg( $field, 'name', '' );
		$type                = fdbarg( $field, 'template', FortressDB_Field::STRING );
		$default_value       = fdbarg( $field, 'default', '' );
		$placeholder         = fdbarg( $field, 'placeholder', '' );
		$caption             = trim( fdbarg( $field, 'label', '' ) );
		// $is_required         = filter_var( fdbarg( $field, 'required', false ), FILTER_VALIDATE_BOOLEAN );
		// $allow_null          = ! $is_required;
		$source['elementId'] = $element_id;
		$default_data        = array(
			'defaultValue' => $default_value,
			'allowNull'    => true,
			'type'         => $type,
			'name'         => $element_id,
			'extend'       => array(
				'caption'     => $caption,
				'order'       => $field_order,
				'isMandatory' => false,
				'description' => '',
				'placeholder' => $placeholder,
				'source'      => $source,
				'encrypted'   => false,
			),
		);

		$simple_field->init( $default_data );

		$parser = fdbarg( $this->parsers, $type, false );
		if ( $parser ) {
			$options    = array(
				'field'        => $field,
				'default_data' => $default_data,
				// 'is_required'  => $is_required,
				'caption'      => $caption,
				'type'         => $type,
				'form_id'      => $form_id,
				'source'       => $source
			);
			return $parser( $complex_fields, $element_id, $simple_field, $options );
		}
		return false;
	}

	/**
	 * @param int   $form_id
	 * @param array $form_fields
	 * @param array $settings
	 *
	 * @throws FortressDB_Form_Parser_Exception
	 * @throws FortressDB_WeForms_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function on_form_update( $form_id, $form_fields, $settings = array() ) {
		$form_id = (int) $form_id;
		$integration = weforms_is_integration_active( $form_id, $this->id );
		if ( false === $integration ) {
			return;
		}

		fortressdb_log( array(
			$form_id,
			$form_fields,
		) );
		$this->get_form_settings( $form_id );

		$metadata = fdbarg( $this->form_settings, 'metadata' );
		$orders   = array();
		if ( $metadata ) {
			if ( !$this->api->get_metadata($metadata['tableName']) ) {
				$metadata = null;
				fdbars( $this->form_settings, 'metadata', $metadata );
				$this->update_form_settings( $form_id );
			}
		}

		foreach ( array( 'fields_group', 'linked_enum_values' ) as $key ) {
			fdbars( $this->form_settings, $key, array() );
		}

		$source      = array( 'name' => 'WeForms' );
		$form        = get_post( $form_id, ARRAY_A );
		$title       = fdbarg( $form, 'post_title', '' );
		$description = fdbarg( $form, 'post_excerpt', '' );

		if ( ! $metadata ) {
			$metadata = $this->api->post_metadata( $title, $description, array( 'source' => $source ) );
			fdbars( $this->form_settings, 'metadata', $metadata );
			$this->update_form_settings( $form_id );
		} else {
			fdbars($metadata, 'name', $title);
			fdbars($metadata, 'description', $description);
		}


		foreach ( $metadata['fields'] as $fdb_field ) {
			$order = fdbarg( $fdb_field, 'extend.order', - 1 );
			if ( $order > 0 && $order < FORTRESSDB_MAX_FIELDS_ORDER ) {
				$orders[] = $order;
			}
		}
		asort( $orders );

		$fdb_fields     = $fields_to_delete = fdbarg( $metadata, 'fields', array() );
		$field_order    = end( $orders ) + 1;
		$result_fields  = array();
		$complex_fields = array();

		foreach ( $form_fields as $field ) {
			$valid_type = $this->parse_field($complex_fields, $field, $field_order, $form_id);
			if ( ! $valid_type ) {
				continue;
			}
		}

		if ( empty( $complex_fields ) ) {
			return;
		}

		foreach ( $complex_fields as $element_id => $simple_field ) {
			FortressDB_Field::check_in_existing( $result_fields, $fdb_fields, $field_order, $element_id, $simple_field );
			$fields_to_delete = FortressDB_Field::check_candidates_to_delete( $fields_to_delete, $element_id );
		}

		$result_fields    = array_values( $result_fields );
		$fdb_fields       = array_values( $fdb_fields );
		$fields_to_delete = array_values( $fields_to_delete );

		if ( ! empty( $fdb_fields ) ) {
			foreach ( $fdb_fields as $fdb_field ) {
				$result_fields[] = $fdb_field;
			}
		}

		if ( ! empty( $fields_to_delete ) ) {
			foreach ( $result_fields as $index => $result_field ) {
				$element_id = fdbarg( $result_field, 'extend.source.elementId' );
				foreach ( $fields_to_delete as $item ) {
					$item_name = fdbarg( $item, 'extend.source.elementId' );

					if ( $item_name ) {
						if ( $item['type'] === 'enum' ) {
							$removable_enum_ids[] = fdbarg( $this->form_settings, array(
								'linked_enums',
								$item_name,
							) );
							fdbarr( $this->form_settings, array( 'linked_enums', $item_name ) );
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

		fdbars( $metadata, 'fields', array_values( $result_fields ) );
		$metadata = $this->api->update_metadata( $metadata );
		fdbars( $this->form_settings, 'metadata', $metadata );

		// update stored fields_group due of changes of metadata in the backend DB
		foreach ( $metadata['fields'] as $fdb_field ) {
			$element_id = fdbarg( $fdb_field, 'extend.source.elementId' );
			if ( $element_id ) {
				fdbars( $this->form_settings, array( 'fields_group', $element_id ), $fdb_field );
			}
		}

		$this->update_form_settings( $form_id );
	}

	/**
	 * @param array $submitted_data
	 *
	 * @return array
	 */
	public function on_form_submission( $submitted_data ) {
		$data = array();
		$form_id = (int) fdbarg( $submitted_data, 'form_id' );

		$integration = weforms_is_integration_active( $form_id, $this->id );
		if ( false === $integration ) {
			return $submitted_data;
		}

		try {
			$this->get_form_settings( $form_id );

			$table_name   = fdbarg( $this->form_settings, 'metadata.tableName', '' );
			$fields_group = fdbarg( $this->form_settings, 'fields_group', array() );

			foreach ( $fields_group as $field_id => $field ) {
				$field_data = fdbarg( $submitted_data, array( 'data', $field_id ), array() );
				$field_data = ! empty( $field_data ) ? $field_data : $field['defaultValue'];
				$field_type = fdbarg( $field, 'type' );
				$field_name = fdbarg( $field, 'name' );

				$original_field_type = fdbarg($field, 'extend.original_type');
				$processor = $original_field_type && isset($this->processors[$original_field_type]) ? $this->processors[$original_field_type] : null;

				if ($processor !== null) {
					$processor($data, $submitted_data, $field);
				} else {

					switch ($field_type) {
						case 'string':
							if (false !== strpos($field_id, 'name')) {
								list($element_id, $input_name) = explode(':', $field_id);
								$field_data = fdbarg($submitted_data, array('data', $element_id, $input_name), '');
							}
							$data[$field_name] = $field_data;
							break;

						case 'time':
							$field_data = trim($field_data);
							// convert all parts to seconds
							$parts = explode(':', $field_data);
							$hours = (int)$parts[0];
							$is_long_format = fdbarg($field, 'extend.format.long', false);

							if (!$is_long_format) {
								$parts_1 = explode(' ', $parts[1]);
								$parts[1] = (int)$parts_1[0];
								$parts[2] = isset($parts_1[1]) ? strtolower($parts_1[1]) : 'am'; // day part: am | pm
							}

							if (isset($parts[2]) && $parts[2] === 'pm') {
								$hours = $parts[0] + 12;
							}

							$hours_value = $hours * 3600; // 01 * 60 * 60
							$minutes_value = $parts[1] * 60; // 01 * 60
							$data[$field_name] = $hours_value + $minutes_value;
							break;

						case 'date':
							$format = 'd/m/Y';
							$date = $field_data ? DateTime::createFromFormat($format, $field_data, wp_timezone()) : null;
							$data[$field_name] = $date ? $date
								->setTimezone(new DateTimeZone('UTC'))
								->format('Y-m-d') : null;
							break;

						case 'datetime':
							$format = 'd/m/Y H:i';
							$date = $field_data ? DateTime::createFromFormat($format, $field_data, wp_timezone()) : null;
							$data[$field_name] = $date ? $date
								->setTimezone(new DateTimeZone('UTC'))
								->format('Y-m-d H:i:s') : null;
							break;

						case 'enum':
							$field_data = (array)$field_data;
							$enum_id = fdbarg($this->form_settings, array('linked_enums', $field_id));
							$data[$field_name] = array();

							foreach ($field_data as $value) {
								$enum_value_id = fdbarg($this->form_settings, array(
									'linked_enum_values',
									$enum_id,
									$value,
								));

								$data[$field_name][] = $enum_value_id;
							}
							break;

						case 'file':
							$field_data = (array)fdbarg($submitted_data, array(
								'data',
								'wpuf_files',
								$field_id,
							), array());

							if ($field_data) {
								foreach ($field_data as $media_id) {
									$file_path = get_attached_file((int)$media_id);
									if (!file_exists($file_path) || !is_file($file_path)) {
										throw new FortressDB_WeForms_Addon_Exception('The file ' . $file_path . ' does not exist!');
									}

									$content_type = get_post_mime_type($media_id);
									$file = new FortressDB_File($file_path, $content_type);
									$data[$field_name][] = $this->upload_file($file, $table_name);
								}
							}
							break;

						case 'boolean':
							list($field_id, $value) = explode(':', $field_id);
							$field_data = fdbarg($submitted_data, array('data', $field_id), array());

							$data[$field_name] = (bool)(!empty($field_data) && array_search($value, $field_data) !== false);
							break;

						default:
							$data[$field_name] = $field_data;
							break;
					}
				}
			}

			$this->api->post_objects( $data, $table_name );
		} catch ( Exception $e ) {
			fortressdb_log( $e->getMessage(), FORTRESSDB_LOG_LEVEL_ERROR );
			fortressdb_log( array( 'data' => $data ) );

			return $submitted_data;
		}

		return $submitted_data;
	}

	/**
	 * @param string|int $post_id
	 *
	 * @return bool
	 *
	 * @throws FortressDB_WeForms_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function on_post_delete( $post_id ) {
		$post      = get_post( $post_id, ARRAY_A );
		$post_type = fdbarg( $post, 'post_type' );

		switch ( $post_type ) {
			case 'wpuf_contact_form':
				$form_id = $post_id;
				if ( false === weforms_is_integration_active( $form_id, $this->id ) ) {
					return true;
				}
				$this->get_form_settings( $form_id );
				$metadata = fdbarg( $this->form_settings, 'metadata' );
				if ( ! $metadata ) {
					return true;
				}

				$this->api->delete_metadata( $metadata['tableName'] );

				$this->update_form_settings( $form_id, array() );
				break;

			case 'wpuf_input':
				$form_id = fdbarg( $post, 'post_parent', 0 );

				if ( false === weforms_is_integration_active( $form_id, $this->id ) ) {
					return true;
				}

				$this->get_form_settings( $form_id );
				$metadata = fdbarg( $this->form_settings, 'metadata' );
				if ( ! $metadata ) {
					return true;
				}

				$field_meta      = maybe_unserialize( $post['post_content'] );
				$element_id      = fdbarg( $field_meta, 'name', '' );
				$field_names     = array();
				$removable_enums = array();

				switch ( $field_meta['template'] ) {
					case 'name_field':
						$inputs = explode( '-', fdbarg( $field_meta, 'format', '' ) );
						foreach ( $inputs as $prefix ) {
							$input_name    = sprintf( '%s_name', $prefix );
							$field_names[] = sprintf( '%s:%s', $element_id, $input_name );
						}
						break;

					default:
						$field_names[] = $element_id;
						break;
				}

				foreach ( $metadata['fields'] as $index => $fdb_field ) {
					$fdb_element_id = fdbarg( $fdb_field, 'extend.source.elementId' );

					if ( $fdb_element_id && in_array( $fdb_element_id, $field_names ) ) {
						fdbarr( $metadata, array( 'fields', $index ) );

						if ( $fdb_field['type'] === 'enum' ) {
							$linked_enums_path = array( 'linked_enums', $fdb_element_id );
							$removable_enums[] = fdbarg( $this->form_settings, $linked_enums_path );
							fdbarr( $this->form_settings, $linked_enums_path );
						}
					}
				}

				if ( ! empty( $removable_enums ) ) {
					$this->api->delete_enums( $removable_enums );
				}

				$metadata['fields'] = array_values( $metadata['fields'] );
				$metadata           = $this->api->update_metadata( $metadata );

				fdbars( $this->form_settings, 'metadata', $metadata );
				$this->update_form_settings( $form_id );
				break;
		}

		return true;
	}

	/**
	 * @param FortressDB_Field $simple_field
	 * @param string|int       $element_id
	 * @param array            $field
	 * @param string           $type
	 *
	 * @return mixed
	 */
	public function on_assign_enum( $simple_field, $element_id, $field, $type ) {
		switch ( $type ) {
			case 'checkbox_field':
			case 'multiple_select':
				$simple_field->apply_extend( 'isMultiple', true );
				break;
		}

		return $simple_field;
	}

	/**
	 * @param int|string $form_id
	 *
	 * @return array|mixed
	 * @throws FortressDB_WeForms_Addon_Exception
	 */
	private function get_form_settings( $form_id ) {
		$this->form_settings = array();

		try {
			$this->form_settings = FortressDB::get_form_settings( $this->_slug, $form_id );
		} catch ( Exception $e ) {
			throw new FortressDB_WeForms_Addon_Exception( $e->getMessage() );
		}

		return $this->form_settings;
	}

	/**
	 * @param int|string $form_id
	 * @param array      $settings
	 *
	 * @throws FortressDB_WeForms_Addon_Exception
	 */
	private function update_form_settings( $form_id, $settings = array() ) {
		try {
			FortressDB::update_form_settings( $this->_slug, $form_id, $settings ?: $this->form_settings );
		} catch ( Exception $e ) {
			throw new FortressDB_WeForms_Addon_Exception( $e->getMessage() );
		}
	}

	/**
	 * @param int|string $form_id
	 * @param int|string $element_id
	 *
	 * @return mixed|null
	 * @throws FortressDB_WeForms_Addon_Exception
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	private function get_enum( $form_id, $element_id ) {
		if ( ! $this->form_settings ) {
			$this->get_form_settings( $form_id );
		}
		$enum_id = fdbarg( $this->form_settings, array( 'linked_enums', $element_id ) );

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
		$field_name  = fdbarg( $options, 'field_name', $element_id );
		$enum        = fdbarg( $options, 'enum' );

		if ( is_null( $enum ) ) {
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
		$fdb_enum_values    = $this->api->get_enum_values( $enum_id );

		foreach ( $fdb_enum_values as $enum_value ) {
			$value    = fdbarg( $enum_value, 'options.value', '' );
			$values[] = $value;

			$enum_values_by_val[ $value ] = $enum_value;
		}

		foreach ( $field[ $inputs_key ] as $key => $value ) {
			$label          = $value;
			$new_options[]  = $option_value = $key;
			$selected_value = fdbarg( $field, 'selected', '' );
			$is_default     = (bool) ( $selected_value === $key );

			/**
			 * @var FortressDB_Enum_Value $simple_enum_value
			 */
			$simple_enum_value = $this->parser->element( FortressDB_Form_Parser::ELEMENT_TYPE_ENUM );

			if ( ! in_array( $value, $values ) ) {
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
		$fdb_enum_values = $this->api->get_enum_values( $enum_id );

		// remove all unmatched values
		$values_to_delete = array();
		foreach ( $fdb_enum_values as $enum_value ) {
			$value = fdbarg( $enum_value, 'options.value', '' );
			if ( ! in_array( $value, $new_options ) ) {
				$values_to_delete[] = $enum_value['id'];
			}
		}

		if ( ! empty( $values_to_delete ) ) {
			$this->api->delete_enum_values( $enum_id, $values_to_delete );
			$fdb_enum_values = $this->api->get_enum_values( $enum_id );
		}

		// process enumValues after all changes
		// prepare them to store in form options
		$default_values = array();
		foreach ( $fdb_enum_values as $enum_value ) {
			$enum_value_id = $enum_value['id'];

			$is_default = fdbarg( $enum_value, 'options.default', false );
			if ( $is_default ) {
				$default_values[] = $enum_value_id;
			}

			// prepare linked_enum_values entries for this enum
			$value = trim( fdbarg( $enum_value, 'options.value', '' ) );

			fdbars( $this->form_settings, array( 'linked_enum_values', $enum_id, $value ), $enum_value_id );
		}

		fdbars( $this->form_settings, array( 'linked_enums', $field_name ), $enum_id );

		return $simple_field
			->set_default_value( $default_values )
			->apply_extend( 'enumId', $enum_id );
	}
}
