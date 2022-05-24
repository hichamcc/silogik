<?php

/**
 * Class FortressDB_Forminator_Addon_Form_Settings_Exception
 * Wrapper of Form Settings FortressDB Exception
 *
 * @since 0.1.0 FortressDB Addon
 */
class FortressDB_Forminator_Addon_Form_Settings_Exception extends FortressDB_Forminator_Addon_Exception {
	
	/**
	 * Holder of input exceptions
	 *
	 * @since 0.1.0 FortressDB Addon
	 * @var array
	 */
	protected $input_exceptions = array();
	
	/**
	 * FortressDB_Forminator_Addon_Form_Settings_Exception constructor.
	 *
	 * Useful if input_id is needed for later.
	 * If no input_id needed, use @param string $message
	 *
	 * @param string $input_id
	 *
	 * @see   FortressDB_Forminator_Addon_Exception
	 *
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function __construct( $message = '', $input_id = '' ) {
		parent::__construct( $message, 0 );
		if ( ! empty( $input_id ) ) {
			$this->add_input_exception( $message, $input_id );
		}
	}
	
	/**
	 * Set exception message for an input
	 *
	 * @param $message
	 * @param $input_id
	 *
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function add_input_exception( $message, $input_id ) {
		$this->input_exceptions[ $input_id ] = $message;
	}
	
	/**
	 * Get all input exceptions
	 *
	 * @return array
	 * @since 0.1.0 FortressDB Addon
	 */
	public function get_input_exceptions() {
		return $this->input_exceptions;
	}
	
	/**
	 * Check if there is input_exceptions_is_available
	 *
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 */
	public function input_exceptions_is_available() {
		return count( $this->input_exceptions ) > 0;
	}
}
