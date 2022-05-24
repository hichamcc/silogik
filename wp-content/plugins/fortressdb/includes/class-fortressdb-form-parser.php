<?php

/**
 * @class FortressDB_Form_Parser_Exception
 */
class FortressDB_Form_Parser_Exception extends Exception {
}

/**
 * Interface IFortressDB_Element
 */
interface IFortressDB_Element {
	/**
	 * IFortressDB_Element constructor.
	 *
	 * @param array $options
	 */
	public function __construct( $options = array() );
	
	/**
	 * @param array $data
	 *
	 * @return FortressDB_Enum_Value
	 */
	public function init( array $data );
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $value
	 *
	 * @return FortressDB_Enum_Value
	 */
	public function apply( $key, $value );
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $default
	 *
	 * @return mixed|null
	 */
	public function get( $key = '', $default = null );
}

/**
 * @class FortressDB_Field
 */
class FortressDB_Field implements IFortressDB_Element {
	const MAX_NAME_LENGTH = 62;

	// field types supported by FortressDb
	const STRING = 'string';
	const INTEGER = 'integer';
	const BOOLEAN = 'boolean';
	const TEXT = 'text';
	const FILE = 'file';
	const FLOAT = 'float';
	const ENUM = 'enum';
	const TIME = 'time';
	const DATE = 'date';
	const DATETIME = 'datetime';
	const JSON = 'json';
	/**
	 * @var array
	 */
	private $data = array(
		'type'         => '',
		'defaultValue' => '',
		'allowNull'    => true,
		'extend'       => array(),
	);
	
	/**
	 * @var string
	 */
	private $path_delimiter = '.';

	/**
	 * @var array
	 */
	protected $setters = array();

	/**
	 * FortressDB_Field constructor.
	 *
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		$this->setters['name'][] = array($this, 'prepare_name');
		if ( isset( $options['base_data'] ) && is_array( $options['base_data'] ) ) {
			$this->init( $options['base_data'] );
		}
		
		if ( isset( $options['path_delimiter'] ) && is_string( $options['path_delimiter'] ) ) {
			$this->path_delimiter = $options['path_delimiter'];
		}
	}
	
	/**
	 * @param array $data
	 *
	 * @return FortressDB_Field
	 */
	public function init( array $data ) {
		$this->data = array();
		foreach ( $data as $key => $item ) {
			$this->apply( $key, $item );
		}
		
		return $this;
	}
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $value
	 *
	 * @return FortressDB_Field
	 */
	public function apply( $key, $value ) {
		if (isset($this->setters[$key])) {
			foreach ($this->setters[$key] as $setter) {
				$value = $setter($value);
			}
		}
		fdbars( $this->data, $key, $value, $this->path_delimiter );
		
		return $this;
	}
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $default
	 *
	 * @return mixed|null
	 */
	public function get( $key = '', $default = null ) {
		if ( $key ) {
			return fdbarg( $this->data, $key, $default, $this->path_delimiter );
		}
		
		return $this->data;
	}
	
	/**
	 * @param string $type
	 *
	 * @return FortressDB_Field
	 * @uses base_field_apply
	 *
	 */
	public function set_type( $type ) {
		return $this->apply( 'type', $type );
	}
	
	/**
	 * @param mixed|null $default_value
	 *
	 * @return FortressDB_Field
	 * @uses base_field_apply
	 *
	 */
	public function set_default_value( $default_value = null ) {
		return $this->apply( 'defaultValue', $default_value );
	}
	
	/**
	 * @param bool $is_required
	 *
	 * @return FortressDB_Field
	 * @uses apply
	 *
	 */
	public function set_is_required( $is_required = false ) {
		$is_required = filter_var( $is_required, FILTER_VALIDATE_BOOLEAN );
		
		return $this->apply( 'allowNull', ! $is_required )
		            ->apply_extend( 'isMandatory', $is_required );
	}
	
	/**
	 * @param string $name
	 *
	 * @return FortressDB_Field
	 * @uses apply
	 *
	 */
	public function set_name( $name ) {
		return $this->apply( 'name', $name );
	}
	
	/**
	 * @param string $id
	 *
	 * @return FortressDB_Field
	 * @uses apply
	 *
	 */
	public function set_id( $id ) {
		return $this->apply( 'id', $id );
	}
	
	/**
	 * @param array $extend
	 *
	 * @return FortressDB_Field
	 * @uses apply
	 *
	 */
	public function set_extend( $extend ) {
		return $this->apply( 'extend', $extend );
	}
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $value
	 *
	 * @return FortressDB_Field
	 *
	 * @uses get
	 * @uses set_extend
	 *
	 */
	public function apply_extend( $key, $value ) {
		$extend = $this->get( 'extend', array() );
		fdbars( $extend, $key, $value, $this->path_delimiter );
		
		return $this->set_extend( $extend );
	}
	
	/**
	 * @param array      $candidates
	 * @param string|int $current_element_id
	 *
	 * @return array
	 */
	public static function check_candidates_to_delete( $candidates, $current_element_id ) {
		return array_filter( $candidates, function ( $field ) use ( $current_element_id ) {
			$field            = ( array ) $field;
			$field_element_id = fdbarg( $field, 'extend.source.elementId' );
			
			if ( $field_element_id && $field_element_id === $current_element_id ) {
				return false;
			}
			
			return (bool) fdbarg( $field, 'extend.source', false );
		} );
	}
	
	/**
	 * @param array            $result_fields
	 * @param array            $fdb_fields
	 * @param int              $field_order
	 * @param string           $element_id
	 * @param FortressDB_Field $simple_field
	 * @param array|null       $source
	 *
	 * @uses set_name
	 * @uses apply_extend
	 */
	public static function check_in_existing( &$result_fields, &$fdb_fields, &$field_order, $element_id, $simple_field, $source = null ) {
		$index         = false;
		$editable_keys = array(
			'caption',
			'placeholder',
			// 'isMandatory',
			'description',
			'isMultiple',
		);
		
		foreach ( $fdb_fields as $key => $field ) {
			$field            = ( array ) $field;
			$field_element_id = fdbarg( $field, 'extend.source.elementId' );
			
			if ( $field_element_id ) {
				if ( $field_element_id === $element_id ) {
					$index = $key;
					$simple_field->set_name( $field['name'] );
					$simple_field->set_id( $field['id'] );
					
					foreach ( $field['extend'] as $k => $v ) {
						if ( ! in_array( $k, $editable_keys ) ) {
							$simple_field->apply_extend( $k, $v );
						}
					}
					unset( $fdb_fields[ $key ] );
					break;
				}
			}
		}
		
		if ( $index === false ) {
			$simple_field->apply_extend( 'order', $field_order ++ );
		}
		
		if ( $source ) {
			$simple_field->apply_extend( 'source', $source );
		}
		
		$result_fields[] = $simple_field->get();
	}

	protected function prepare_name($name) {
		if (strlen($name) > self::MAX_NAME_LENGTH) {
			$parts = explode(":", $name);
			$diff = strlen($name) - self::MAX_NAME_LENGTH;
			foreach($parts as $i => $part) {
				$cut_length = min($diff, strlen($part));
				$parts[$i] = substr($part, 0, strlen($part) - $cut_length);
				$diff -= $cut_length;
			}
			$name = implode(":", $parts);
		}
		return $name;
	}
}

/**
 * @class FortressDB_Enum
 */
class FortressDB_Enum_Value implements IFortressDB_Element {
	
	/**
	 * @var array
	 */
	private $data = array(
		'label'   => '',
		'enumId'  => 0,
		'options' => array(
			'default' => false,
			'value'   => '',
			'source'  => array(),
		),
	);
	
	/**
	 * @var string
	 */
	private $path_delimiter = '.';
	
	/**
	 * FortressDB_Field constructor.
	 *
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		if ( isset( $options['base_data'] ) && is_array( $options['base_data'] ) ) {
			$this->init( $options['base_data'] );
		}
		
		if ( isset( $options['path_delimiter'] ) && is_string( $options['path_delimiter'] ) ) {
			$this->path_delimiter = $options['path_delimiter'];
		}
	}
	
	/**
	 * @param array $data
	 *
	 * @return FortressDB_Enum_Value
	 */
	public function init( array $data ) {
		$this->data = array();
		foreach ( $data as $key => $item ) {
			$this->apply( $key, $item );
		}
		
		return $this;
	}
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $value
	 *
	 * @return FortressDB_Enum_Value
	 */
	public function apply( $key, $value ) {
		fdbars( $this->data, $key, $value, $this->path_delimiter );
		
		return $this;
	}
	
	/**
	 * @param array|string|int $key
	 * @param mixed            $default
	 *
	 * @return mixed|null
	 */
	public function get( $key = '', $default = null ) {
		if ( $key ) {
			return fdbarg( $this->data, $key, $default, $this->path_delimiter );
		}
		
		return $this->data;
	}
	
	/**
	 * @param string $label
	 *
	 * @return FortressDB_Enum_Value
	 * @uses apply
	 */
	public function set_label( $label ) {
		return $this->apply( 'label', $label );
	}
	
	/**
	 * @param int $enum_id
	 *
	 * @return FortressDB_Enum_Value
	 * @uses apply
	 */
	public function set_enum_id( $enum_id ) {
		return $this->apply( 'enum_id', $enum_id );
	}
	
	/**
	 * @param array $options
	 *
	 * @return FortressDB_Enum_Value
	 * @uses apply
	 */
	public function set_options( array $options ) {
		return $this->apply( 'options', $options );
	}
	
	/**
	 * @param string|int $key
	 * @param mixed      $value
	 *
	 * @return FortressDB_Enum_Value
	 * @uses set_options
	 *
	 */
	public function apply_options( $key, $value ) {
		$options = $this->get( 'options', array() );
		fdbars( $options, $key, $value, $this->path_delimiter );
		
		return $this->set_options( $options );
	}
}

/**
 * @class FortressDB_Form_Parser
 */
class FortressDB_Form_Parser {
	/**
	 * @var string
	 */
	const ELEMENT_TYPE_FIELD = 'field';
	
	/**
	 * @var string
	 */
	const ELEMENT_TYPE_ENUM = 'enum';
	
	/**
	 * @var FortressDB_Field|FortressDB_Enum_Value
	 */
	private $element = null;
	
	/**
	 * @var array
	 */
	private $options = array();
	
	/**
	 * @var string
	 */
	private $type = '';
	
	/**
	 * @var string
	 */
	public $addon_slug = '';
	
	/**
	 * FortressDBFormParser constructor.
	 *
	 * @param string $addon_slug
	 * @param array  $options
	 */
	public function __construct( $addon_slug, $options = array() ) {
		$this->addon_slug = $addon_slug;
		$this->options    = $options;
	}
	
	/**
	 * @param string $type FortressDB_Form_Parser::ELEMENT_TYPE_FIELD | FortressDB_Form_Parser::ELEMENT_TYPE_ENUM
	 *
	 * @return FortressDB_Field|FortressDB_Enum_Value
	 * @throws FortressDB_Form_Parser_Exception
	 */
	public function element( $type ) {
		$this->type = $type;
		$element    = null;
		
		switch ( $this->type ) {
			case self::ELEMENT_TYPE_FIELD:
				$element = new FortressDB_Field( $this->options );
				break;
			case self::ELEMENT_TYPE_ENUM:
				$element = new FortressDB_Enum_Value( $this->options );
				break;
			default:
				throw new FortressDB_Form_Parser_Exception( 'This element type is not supporting' );
		}
		
		$this->element = $element;
		
		return $this->element;
	}
	
	/**
	 * @param array      $input
	 * @param string|int $element_id
	 * @param array      $options
	 *
	 * @return boolean|FortressDB_Field
	 * @throws FortressDB_Form_Parser_Exception
	 */
	public function process_complex_field( $input, $element_id, $options = array() ) {
		$is_hidden = fdbarg( $input, 'isHidden', false );
		if ( $is_hidden ) {
			return null;
		}
		
		$label_key     = fdbarg( $options, 'label_key', 'label' );
		$default_key   = fdbarg( $options, 'default_key', 'defaultValue' );
		$default_data  = fdbarg( $options, 'default_data', array() );
		$field_name    = fdbarg( $options, 'field_name', $element_id );
		$label         = fdbarg( $input, $label_key, '' );
		$default_value = fdbarg( $input, $default_key, '' );
		$caption       = fdbarg( $input, 'customLabel', $label );
		$placeholder   = fdbarg( $input, 'placeholder', '' );
		
		/**
		 * @var FortressDB_Field $simple_field
		 */
		$simple_field = $this->element( FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
		$simple_field->init( $default_data )
		             ->set_type( FortressDB_Field::STRING )
		             ->set_default_value( $default_value )
		             ->apply_extend( 'source.elementId', $field_name )
		             ->apply_extend( 'caption', $caption )
		             ->apply_extend( 'placeholder', $placeholder );
		
		return $simple_field;
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
	 */
	public function build_or_assign_enum( FortressDB_Field $simple_field, $field, $element_id, $options = array() ) {
		$source         = fdbarg( $options, 'source', array() );
		$caption        = fdbarg( $options, 'caption', '' );
		$description    = fdbarg( $options, 'description', '' );
		$inputs_key     = fdbarg( $options, 'inputs_key', 'choices' );
		$id_key         = fdbarg( $options, 'id_key', 'id' );
		$label_key      = fdbarg( $options, 'label_key', 'label' );
		$field_name     = fdbarg( $options, 'field_name', $element_id );
		$enum           = fdbarg( $options, 'enum' );
		$fortressdb_api = fdbarg( $options, 'api' );
		
		if ( ! $fortressdb_api ) {
			throw new FortressDB_Form_Parser_Exception( 'Invalid options: FortressDb API not found' );
		}
		
		/**
		 * @var FortressDB_Enum_Value $simple_enum_value
		 */
		$simple_enum_value = $this->element( FortressDB_Form_Parser::ELEMENT_TYPE_ENUM );
		
		if ( ! $enum ) {
			$enum = $fortressdb_api->post_enum( $caption, $description, array( 'source' => $source ) );
		} elseif ( $caption !== $enum['name'] ) {
			$enum['options']['source'] = $source;
			
			$enum['name'] = $caption;
			$enum         = $fortressdb_api->update_enum( $enum );
		}
		
		$enum_id = $enum['id'];
		
		$simple_field->apply_extend( 'enumId', $enum_id );
		
		/**
		 * store current values regarding their IDs
		 *
		 * @param int    $enum_id
		 * @param string $field_name
		 */
		do_action( 'store_linked_enums', $enum_id, $field_name );
		
		// get its enumValues
		$enum_values        = $fortressdb_api->get_enum_values( $enum_id );
		$new_options        = array();
		$old_values         = array();
		$enum_values_by_val = array();
		$values             = array();
		
		foreach ( $enum_values as $enum_value ) {
			$value    = fdbarg( $enum_value, 'options.value', '' );
			$values[] = $value;
			
			$enum_values_by_val[ $value ] = $enum_value;
		}
		
		foreach ( $field[ $inputs_key ] as $choice ) {
			$new_options[] = $option_value = $choice[ $id_key ];
			$default_value = fdbarg( $choice, 'isSelected', false );
			$label         = fdbarg( $choice, $label_key, '' );
			
			$simple_enum_value->init( array(
				'label'   => $label,
				'enumId'  => $enum_id,
				'options' => array(
					'default' => $default_value,
					'value'   => $option_value,
					'source'  => $source,
				),
			) );
			
			if ( ! in_array( $option_value, $values ) ) {
				$new_values[] = $simple_enum_value->get();
			} else {
				$simple_enum_value
					->init( $enum_values_by_val[ $option_value ] )
					->set_label( $label )
					->apply_options( 'default', $default_value );
				$old_values[] = $simple_enum_value->get();
			}
		}
		
		if ( ! empty( $old_values ) ) {
			$fortressdb_api->update_enum_values( $enum_id, $old_values );
		}
		if ( ! empty( $new_values ) ) {
			$fortressdb_api->post_enum_values( $enum_id, $new_values );
		}
		
		// get updated enumValues
		$enum_values = $fortressdb_api->get_enum_values( $enum_id );
		
		// remove all unmatched values
		$values_to_delete = array();
		foreach ( $enum_values as $enum_value ) {
			$value = fdbarg( $enum_value, 'options.value', '' );
			if ( ! in_array( $value, $new_options ) ) {
				$values_to_delete[] = $enum_value['id'];
			}
		}
		
		if ( ! empty( $values_to_delete ) ) {
			$fortressdb_api->delete_enum_values( $enum_id, $values_to_delete );
			$enum_values = $fortressdb_api->get_enum_values( $enum_id );
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
			
			/**
			 * @param int    $enum_id
			 * @param string $value
			 * @param int    $enum_value_id
			 */
			do_action( 'store_linked_enum_values', $enum_id, $value, $enum_value_id );
		}
		
		return $simple_field->set_default_value( $default_values );
	}
}
