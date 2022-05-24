<?php

// load FortressDB_Files class
FortressDB::include_files_api();

/**
 * Class FortressDB_Forminator_Addon_Form_Hooks
 *
 * @since 1.0 FortressDB Addon
 *
 */
class FortressDB_Forminator_Addon_Form_Hooks extends Forminator_Addon_Form_Hooks_Abstract {
	
	/**
	 * Addon instance are auto available form abstract
	 * Its added here for development purpose,
	 * Auto-complete will resolve addon directly to `FortressDB` instance instead of the abstract
	 * And its public properties can be exposed
	 *
	 * @since 1.0 FortressDB Addon
	 * @var FortressDB_Forminator_Addon
	 */
	protected $addon;
	
	/**
	 * Form Settings Instance
	 *
	 * @since 1.0 FortressDB Addon
	 * @var FortressDB_Forminator_Addon_Form_Settings | null
	 */
	protected $form_settings_instance;
	
	/**
	 * @var FortressDB_Wp_Api
	 */
	private $api = null;
	
	/**
	 * FortressDB_Forminator_Addon_Form_Hooks constructor.
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $form_id
	 *
	 * @throws Forminator_Addon_Exception
	 * @throws Exception
	 * @since 1.0 FortressDB Addon
	 *
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
		parent::__construct( $addon, $form_id );
		$this->_submit_form_error_message = __( 'FortressDB failed to process submitted data. Please check your form and try again', FortressDB::DOMAIN );
		$this->form_id                    = $form_id;
		
		try {
			$this->api = FortressDB::get_api();
		} catch ( FortressDB_Wp_Api_Exception $e ) {
			fortressdb_log( $e->getMessage() );
		}
	}
	
	/**
	 * Save status of request sent and received for each connected FortressDB
	 *
	 * @param array $submitted_data
	 * @param array $form_entry_fields
	 *
	 * @return array
	 * @throws Exception
	 * @since 1.0 FortressDB Addon
	 *
	 */
	public function add_entry_fields( $submitted_data, $form_entry_fields = array() ) {
		$form_settings = $this->form_settings_instance->get_form_settings_values();
		return $this->get_status_on_send_data( $submitted_data, $form_settings, $form_entry_fields );
	}
	
	/**
	 * Get status on send FortressDB data
	 *
	 * @param array $submitted_data
	 * @param array $form_settings
	 * @param array $form_entry_fields
	 *
	 * @return array `is_sent` true means its success send data to FortressDB, false otherwise
	 * @throws Exception
	 * @since 1.0 FortressDB Addon
	 *
	 */
	private function get_status_on_send_data( $submitted_data, $form_settings, $form_entry_fields ) {
		$result = array(
			'is_sent'       => true,
			'description'   => __( 'Successfully sent data to FortressDB', FortressDB::DOMAIN ),
			'data_sent'     => null,
			'data_received' => null,
			'url_request'   => null,
		);
		
		// fortressdb_log( array( 'submitted_data' => $submitted_data ) );

		//check required fields
		try {
			$data               = array();
			$table_name         = fdbarg( $form_settings, 'metadata.tableName', '' );
			$fields_group       = fdbarg( $form_settings, 'fields_group', array() );
			$calculation_fields = $this->find_calculation_fields_meta_from_entry_fields($form_entry_fields);
			foreach ( $fields_group as $field_id => $field ) {
				$field_data = fdbarg( $submitted_data, $field_id, array() );
				$field_data = ! empty( $field_data ) ? $field_data : $field['defaultValue'];
				$field_type = fdbarg( $field, 'type' );
				$field_name = fdbarg( $field, 'name' );
				
				switch ( $field_type ) {
					case 'time':
						$field_data = trim( $field_data );
						// convert all parts to seconds
						$parts          = explode( ':', $field_data );
						$hours          = (int) $parts[0];
						$is_long_format = fdbarg( $field, 'extend.format.long', false );
						
						if ( ! $is_long_format ) {
							$parts_1  = explode( ' ', $parts[1] );
							$parts[1] = (int) $parts_1[0];
							$parts[2] = isset( $parts_1[1] ) ? strtolower( $parts_1[1] ) : 'am'; // day part: am | pm
						}
						
						if ( isset( $parts[2] ) && $parts[2] === 'pm' ) {
							$hours = $parts[0] + 12;
						}
						
						$hours_value         = $hours * 3600; // 01 * 60 * 60
						$minutes_value       = $parts[1] * 60; // 01 * 60
						$data[ $field_name ] = $hours_value + $minutes_value;
						break;
					
					case 'enum':
						$field_data          = (array) $field_data;
						$enum_id             = fdbarg( $form_settings, array( 'linked_enums', $field_id ) );
						$data[ $field_name ] = array();
						
						foreach ( $field_data as $value ) {
							$enum_value_id = fdbarg( $form_settings, array(
								'linked_enum_values',
								$enum_id,
								$value,
							) );
							
							$data[ $field_name ][] = $enum_value_id;
						}
						break;
					
					case 'file':
						$signature_field_name = "field-{$field_id}";
						$files                = array();
						$isMultiple           = fdbarg( $field, 'extend.isMultiple', false );

						if (!$field_data) {
							foreach ($form_entry_fields as $entry_field) {
								if ($entry_field['name'] === $field_name && isset($entry_field['value']['file'])) {
									$field_data = $entry_field['value']['file'];
									break;
								}
							}
						}

						if ( is_array( $field_data ) && isset( $field_data['success'] ) &&
						     $field_data['success'] === true && isset( $field_data['file_path'] ) ) {
							if ( $isMultiple ) {
								foreach ( (array) $field_data['file_path'] as $index => $file_path ) {
									if ( is_file( $file_path ) ) {
										$files[] = array(
											'file'         => new FortressDB_File( $file_path ),
											'file_name'    => $field_data['name'][ $index ],
											'file_size'    => $field_data['size'][ $index ],
											'content_type' => $field_data['type'][ $index ],
										);
									}
								}
							} else if ( is_file( $field_data['file_path'] ) ) {
								$files[] = array(
									'file'         => new FortressDB_File( $field_data['file_path'] ),
									'file_name'    => $field_data['name'],
									'file_size'    => $field_data['size'],
									'content_type' => $field_data['type'],
								);
							}
						} elseif ( isset ( $submitted_data[ $signature_field_name ] ) ) {
							try {
								$field_hash            = $submitted_data[ $signature_field_name ];
								$signature_data_canvas = $submitted_data["ctlSignature{$field_hash}_data_canvas"];
								$content_type          = str_replace( 'data:', '', explode( ';base64,', $signature_data_canvas )[0] );
								$file_content          = file_get_contents( $signature_data_canvas );
								$temp                  = tmpfile();
								fputs( $temp, $file_content );
								$file_name = uniqid();
								$file_size = strlen( $file_content );
								
								$files[] = array(
									'file'         => new FortressDB_File( stream_get_meta_data( $temp )['uri'] ),
									'file_name'    => $file_name,
									'file_size'    => $file_size,
									'content_type' => $content_type,
								);
							} catch ( Exception $e ) {
								fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_ERROR );
							}
						} elseif ( is_string( $field_data ) && isset( $submitted_data['forminator-multifile-hidden'] ) && $isMultiple ) {
							// Forminator Version 1.15.9, multifile field
							$multifile = fdbarg( json_decode( $submitted_data['forminator-multifile-hidden'], true ), $field_id, array() );
							$upload_dir = wp_get_upload_dir();
							$basedir = $upload_dir['basedir'];
							$baseurl_length = strlen( $upload_dir['baseurl'] );
							$multipath = array_map( function ( $url ) use ( $basedir, $baseurl_length ) {
								return $basedir . substr( $url, $baseurl_length );
							}, explode( ',', $field_data ) );
							if ( count( $multifile ) === count( $multipath ) ) {
								for ( $i = 0, $l = count( $multifile ); $i < $l; ++$i ) {
									$file_info = (array) $multifile[$i];
									if ( isset( $file_info['success'] ) && $file_info['success'] === true ) {
										$files[] = array(
											'file'         => new FortressDB_File( $multipath[$i] ),
											'content_type' => isset( $file_info['mime_type'] ) ? $file_info['mime_type'] : null,
											'file_name'    => isset( $file_info['file_name'] ) ? $file_info['file_name'] : null,
											'file_size'    => null,
										);
									}
								}
							}
						}
						
						if ( ! empty( $files ) ) {
							$data[ $field['name'] ] = array();
							try {
								foreach ( $files as $file_descriptor ) {
									$file         = fdbarg( $file_descriptor, 'file' );
									$content_type = fdbarg( $file_descriptor, 'content_type' );
									$file_name    = fdbarg( $file_descriptor, 'file_name' );
									$file_size    = fdbarg( $file_descriptor, 'file_size' );

									$content_type = $content_type === null ? $file->Mime() : $content_type;
									$file_name = $file_name === null ? $file->Name() : $file_name;
									$file_size = $file_size === null ? $file->Size() : $file_size;

									$delimiter = '-------------' . uniqid();
									$body      = FortressDB_BodyPost::Get( array(
										'fileName'    => $file->Name(),
										'type'        => substr( $content_type, 0, 5 ) == 'image' ? 'image' : 'file',
										'contentType' => $content_type,
										'tableName'   => $table_name,
										'raw'         => $file,
									), $delimiter );
									
									$args = array(
										'body'    => $body,
										'headers' => array(
											'Content-Type'   => 'multipart/form-data; boundary=' . $delimiter,
											'Content-Length' => strlen( $body ),
											'Authorization'  => 'Bearer ' . $this->api->accessToken(),
										),
										'timeout' => 60,
									);
									
									$response = wp_remote_post( $this->api->baseUrl( 'files' ), $args );
									if ( is_wp_error( $response ) ) {
										throw new FortressDB_Forminator_Addon_Exception( $response->get_error_message() );
									}
									
									$files_res = json_decode( wp_remote_retrieve_body( $response ), true );
									
									$data[ $field['name'] ][] = array(
										'name' => $file_name,
										'type' => $content_type,
										'uuid' => $files_res[0]['uuid'],
										'size' => $file_size,
									);
								}
							} catch ( Exception $e ) {
								fortressdb_log( array( __METHOD__, $e->getMessage() ), FORTRESSDB_LOG_LEVEL_ERROR );
								throw $e;
							}
							$result['files_sent'] = true;
						}
						break;
					
					case 'boolean':
						$field_id            = explode( ':', $field_id )[0];
						$value               = fdbarg( $field, 'extend.source.value', '' );
						$field_data          = fdbarg( $submitted_data, $field_id, array() );
						$data[ $field_name ] = (bool) ( ! empty( $field_data ) && array_search( $value, $field_data ) !== false );
						break;
					case "string":
						if ( isset($calculation_fields[ $field_name ]) ) {
							$data[ $field_name ] = sprintf("%.2f", $calculation_fields[$field_name]['result']);
						} else {
							$data[ $field_name ] = $field_data;
						}
						break;
					case 'date':
						$form_field = $this->custom_form->get_field($field_name);
						$date_format = 'mm/dd/yy';
						if ($form_field) {
							$date_format = $form_field['date_format'];
						}
						$date_format = str_replace(['dd', 'mm', 'yy'], ['d', 'm', 'Y'], $date_format);
						$date = DateTime::createFromFormat($date_format, $field_data, wp_timezone());
						if ($date) {
							$data[$field_name] = $date->format(DateTime::ATOM);
						}
						break;
					default:
						$data[ $field_name ] = $field_data;
						break;
				}
			}
			
			// fortressdb_log( array( 'forminator_data_to_send' => $data ) );
			
			$this->api->post_objects( $data, $table_name );
			
			$result['data_sent']     = $this->api->get_last_data_sent();
			$result['data_received'] = $this->api->get_last_data_received();
			$result['url_request']   = $this->api->get_last_url_request();
			
			if ( isset( $result['data_received']->name ) && $result['data_received']->name === 'Error' ) {
				throw new FortressDB_Forminator_Addon_Exception( 'Form sending was failed' );
			}
		} catch ( FortressDB_Forminator_Addon_Exception $e ) {
			$result['is_sent']       = false;
			$result['description']   = $e->getMessage();
			$result['data_sent']     = $this->api->get_last_data_sent();
			$result['data_received'] = $this->api->get_last_data_received();
			$result['url_request']   = $this->api->get_last_url_request();
		}
		
		return $result;
	}
	
	/**
	 * It wil add new row on entry table of submission page, with couple of subentries
	 * subentries included are defined in @param Forminator_Form_Entry_Model $entry_model
	 *
	 * @param                             $addon_meta_data
	 *
	 * @return array
	 * @since 1.0 FortressDB Addon
	 *
	 * @see   FortressDB_Forminator_Addon_Form_Hooks::get_additional_entry_item()
	 *
	 */
	public function on_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return array();
		}
		
		return $this->on_render_entry_multi_connection( $addon_meta_datas );
	}
	
	/**
	 * Loop through addon meta data on multiple fortressdb setup(s)
	 *
	 * @param $addon_meta_datas
	 *
	 * @return array
	 * @since 1.0 FortressDB Addon
	 *
	 */
	private function on_render_entry_multi_connection( $addon_meta_datas ) {
		$additional_entry_item = array();
		foreach ( $addon_meta_datas as $addon_meta_data ) {
			$additional_entry_item[] = $this->get_additional_entry_item( $addon_meta_data );
		}
		
		return $additional_entry_item;
	}
	
	/**
	 * Format additional entry item as label and value arrays
	 *
	 * - Integration Name : its defined by user when they adding FortressDB integration on their form
	 * - Sent To FortressDB : will be Yes/No value, that indicates whether sending data to FortressDB API was successful
	 * - Info : Text that are generated by addon when building and sending data to FortressDB @param $addon_meta_data
	 *
	 * @return array
	 * @see FortressDB_Forminator_Addon_Form_Hooks::add_entry_fields()
	 * - Below subentries will be added if full log enabled, @since 1.0 FortressDB Addon
	 * @see FortressDB_Forminator_Addon::is_show_full_log() @see FORMINATOR_ADDON_FORTRESSDB_SHOW_FULL_LOG
	 *      - API URL : URL that wes requested when sending data to FortressDB
	 *      - Data sent to FortressDB : encoded body request that was sent
	 *      - Data received from FortressDB : json encoded body response that was received
	 *
	 */
	private function get_additional_entry_item( $addon_meta_data ) {
		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}
		$status                = $addon_meta_data['value'];
		$additional_entry_item = array(
			'label' => __( 'FortressDB Integration', FortressDB::DOMAIN ),
			'value' => '',
		);
		
		
		$sub_entries = array();
		if ( isset( $status['connection_name'] ) ) {
			$sub_entries[] = array(
				'label' => __( 'Integration Name', FortressDB::DOMAIN ),
				'value' => $status['connection_name'],
			);
		}
		
		if ( isset( $status['is_sent'] ) ) {
			$is_sent       = true === $status['is_sent'] ? __( 'Yes', FortressDB::DOMAIN ) : __( 'No', FortressDB::DOMAIN );
			$sub_entries[] = array(
				'label' => __( 'Sent To FortressDB', FortressDB::DOMAIN ),
				'value' => $is_sent,
			);
		}
		
		if ( isset( $status['description'] ) ) {
			$sub_entries[] = array(
				'label' => __( 'Info', FortressDB::DOMAIN ),
				'value' => $status['description'],
			);
		}
		
		if ( FortressDB_Forminator_Addon::is_show_full_log() ) {
			// too long to be added on entry data enable this with `define('FORMINATOR_ADDON_FORTRESSDB_SHOW_FULL_LOG', true)`
			if ( isset( $status['url_request'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'API URL', FortressDB::DOMAIN ),
					'value' => $status['url_request'],
				);
			}
			
			if ( isset( $status['data_sent'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data sent to FortressDB', FortressDB::DOMAIN ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_sent'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
			
			if ( isset( $status['data_received'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data received from FortressDB', FortressDB::DOMAIN ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_received'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
		}
		
		
		$additional_entry_item['sub_entries'] = $sub_entries;
		
		// return single array
		return $additional_entry_item;
	}
	
	/**
	 * FortressDB will add a column on the title/header row
	 * its called `FortressDB Info` which can be translated on forminator lang
	 *
	 * @return array
	 * @since 1.0 FortressDB Addon
	 */
	public function on_export_render_title_row() {
		return array(
			'info' => __( 'FortressDB Info', FortressDB::DOMAIN ),
		);
	}
	
	/**
	 * FortressDB will add a column that give user information whether sending data to FortressDB successfully or not
	 * It will only add one column even its multiple connection, every connection will be separated by comma
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param                             $addon_meta_data
	 *
	 * @return array
	 * @since 1.0 FortressDB Addon
	 *
	 */
	public function on_export_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		return array(
			'info' => $this->get_from_addon_meta_data( $addon_meta_data, 'description', '' ),
		);
	}
	
	/**
	 * Get Addon meta data, will be recursive if meta data is multiple because of multiple connection added
	 *
	 * @param        $addon_meta_data
	 * @param        $key
	 * @param string $default
	 *
	 * @return string
	 * @since 1.0 FortressDB Addon
	 *
	 */
	private function get_from_addon_meta_data( $addon_meta_data, $key, $default = '' ) {
		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return $default;
		}
		
		$addon_meta_data = $addon_meta_data[0];
		
		// make sure its `status`, because we only add this
		if ( 'status' !== $addon_meta_data['name'] ) {
			if ( stripos( $addon_meta_data['name'], 'status-' ) === 0 ) {
				$meta_data = array();
				foreach ( $addon_meta_datas as $addon_meta_data ) {
					// make it like single value so it will be processed like single meta data
					$addon_meta_data['name'] = 'status';
					
					// add it on an array for next recursive process
					$meta_data[] = $this->get_from_addon_meta_data( array( $addon_meta_data ), $key, $default );
				}
				
				return implode( ', ', $meta_data );
			}
			
			return $default;
		}
		
		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return $default;
		}
		$status = $addon_meta_data['value'];
		if ( isset( $status[ $key ] ) ) {
			$connection_name = '';
			if ( 'connection_name' !== $key ) {
				if ( isset( $status['connection_name'] ) ) {
					$connection_name = '[' . $status['connection_name'] . '] ';
				}
			}
			
			return $connection_name . $status[ $key ];
		}
		
		return $default;
	}
}
