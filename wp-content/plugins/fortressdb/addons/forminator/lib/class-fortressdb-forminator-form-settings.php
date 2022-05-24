<?php

require_once( 'class-fortressdb-forminator-form-settings-exception.php' );

/**
 * Class FortressDB_Forminator_Addon_Form_Settings
 * Handle how form settings displayed and saved
 *
 * @since 0.1.0 FortressDB Addon
 */
class FortressDB_Forminator_Addon_Form_Settings extends Forminator_Addon_Form_Settings_Abstract {
	
	/**
	 * @var FortressDB_Forminator_Addon
	 * @since 0.1.0 FortressDB Addon
	 */
	protected $addon;
	
	protected $_image;
	protected $_image_x2;
	
	/**
	 * FortressDB_Forminator_Addon_Form_Settings constructor.
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $form_id
	 *
	 * @throws Forminator_Addon_Exception
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
		parent::__construct( $addon, $form_id );
		
		$this->_update_form_settings_error_message = __(
			'The update to your settings for this form failed, check the form input and try again.',
			FortressDB::DOMAIN
		);
		
		$this->_image    = fortressdb_addon_assets_url( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'images/fortressdb.png' );
		$this->_image_x2 = fortressdb_addon_assets_url( FORTRESSDB_ADDON_FORMINATOR_SLUG, 'images/fortressdb@2x.png' );
	}
	
	/**
	 * FortressDB Form Settings wizard
	 *
	 * @return array
	 * @since 0.1.0 FortressDB Addon
	 */
	public function form_settings_wizards() {
		// numerical array steps
		return array(
			array(
				'callback'     => array( $this, 'fortressdb_form_connect' ),
				'is_completed' => array( $this, 'fortressdb_form_connect_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'fortressdb_connection_success' ),
				'is_completed' => array( $this, 'fortressdb_connection_success_is_completed' ),
			),
		);
	}
	
	/**
	 * Connect to FortressDB
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function fortressdb_form_connect( $submitted_data ) {
		$template        = fortressdb_addons_dir( 'forminator', 'views/settings/form/fortressdb_form_connect.php' );
		$template_params = array(
			'fortressdb_form_connect_error' => false,
			'action'                        => 'connect',
			'step_description'              => __(
				"Do you want to connect your form to a secure FortressDB data table? " .
				"Click here to continue",
				FortressDB::DOMAIN
			),
		);
		
		$form          = Forminator_API::get_form( $this->form_id );
		$form_name     = $form->settings['formName'];
		$form_settings = $this->find_active_connection();
		$is_connected  = $form_settings !== false;
		$is_submit     = ! empty( $submitted_data );
		$has_errors    = false;
		$buttons       = array();
		
		if ( $is_submit ) {
			$form_settings['is_connected'] = ! (bool) $is_connected;
			$form_settings['time_added']   = isset( $form_settings['time_added'] ) ? $form_settings['time_added'] : time();
			
			try {
				if ( ! $is_connected && version_compare( FORMINATOR_VERSION, FORTRESSDB_ADDON_FORMINATOR_MIN_VERSION ) < 0 ) {
					throw new FortressDB_Forminator_Addon_Exception( sprintf( 'You are using Forminator version less than %s, please update it', FORTRESSDB_ADDON_FORMINATOR_MIN_VERSION ) );
				}
				$this->save_form_settings_values( $form_settings );
			} catch ( Exception $e ) {
				$template_params['fortressdb_form_connect_error'] = $e->getMessage();
				$has_errors                                       = true;
			}
		}
		
		if ( $is_connected ) {
			$template_params['action']           = 'disconnect';
			$template_params['step_description'] = sprintf(
				__(
					"We have created a data table for this form,<br > it’s called <strong>%s</strong>.<br > " .
					"All your form data will be securely stored here.",
					FortressDB::DOMAIN
				),
				$form_name
			);
			
			$buttons['disconnect']['markup'] = Forminator_Addon_Abstract::get_button_markup(
				esc_html__( 'Disconnect', FortressDB::DOMAIN ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate FortressDB Integration from this Form.', FortressDB::DOMAIN )
			);
		} else {
			$buttons['next']['markup'] = Forminator_Addon_Abstract::get_button_markup(
				esc_html__( 'Connect', FortressDB::DOMAIN ),
				'forminator-addon-next'
			);
		}
		
		return array(
			'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}
	
	/**
	 * @return bool
	 * @since 0.1.0 FortressDB Addon
	 *
	 */
	public function fortressdb_form_connect_is_completed() {
		return true;
	}
	
	/**
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function fortressdb_connection_success( $submitted_data ) {
		$template = fortressdb_addons_dir( 'forminator', 'views/settings/form/success-fortressdb_form_connect.php' );
		
		$template_params = array(
			'step_description' => __(
				"Congratulations!",
				FortressDB::DOMAIN
			),
		);
		
		$buttons = array();
		
		return array(
			'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'has_back'   => true,
			'redirect'   => false,
			'has_errors' => false,
		);
	}
	
	/**
	 * Check if form connection is completed
	 *
	 * @return bool
	 */
	public function fortressdb_connection_success_is_completed() {
		if ( ! $this->fortressdb_form_connect_is_completed() ) {
			return false;
		}
		
		return $this->find_active_connection() !== false;
	}
	
	public function find_active_connection() {
		$addon_form_settings = $this->get_form_settings_values();
		if ( isset( $addon_form_settings['is_connected'] ) && true === (bool)$addon_form_settings['is_connected'] ) {
			return $addon_form_settings;
		}
		
		return false;
	}

	public function disconnect_form($submitted_data) {
		$settings = $this->get_form_settings_values();
		$settings['is_connected'] = false;
		$this->save_form_settings_values( $settings );
	}

	public function before_save_form_settings_values($values) {
		$settings = $this->get_form_settings_values();
		return array_merge($settings, $values);
	}
}
