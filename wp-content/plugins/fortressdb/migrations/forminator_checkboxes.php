<?php
namespace FortressDB\Migrations;

require_once 'MigrationInterface.php';

if ( ! class_exists( 'FortressDB_Form_Parser' ) ) {
	require_once( fortressdb_includes_dir( 'class-fortressdb-form-parser.php' ) );
}


class ForminatorCheckboxesMigration implements IMigration {
	/**
	 * @var \FortressDB_Form_Parser
	 */
	protected $form_parser = null;

	/**
	 * @var \FortressDB_Wp_Api
	 */
	private $api = null;
	protected $_slug = 'forminatorfortressdb';

	public function __construct()
	{
		$this->form_parser = new \FortressDB_Form_Parser( $this->_slug );
		$this->api = \FortressDB::get_api();
	}

	public function forward()
	{
		if ( ! class_exists( '\Forminator_API' ) ) {
			return false;
		}

		foreach ($this->forms() as list($form, $form_settings, $addon)) {
			$this->process_form($form, $form_settings, $addon);
		}
		/* TODO: return true */
		return true;
	}

	private function parse_form_field($field, $order) {
		$source             = array( 'name' => 'Forminator' );
		$placeholder        = fdbarg( $field, 'placeholder', '' );
		$description        = fdbarg( $field, 'description', '' );
		// $allow_null         = ! (bool) fdbarg( $field, 'required', false );
		$element_id         = $source['elementId'] = trim( fdbarg( $field, 'element_id', 0 ) );
		$caption            = trim( fdbarg( $field, 'field_label', '' ) );

		$enum = $this->api->post_enum( $caption, $description, array( 'source' => $source ) );

		$enum_values = array();
		$simple_enum_value = $this->form_parser->element( \FortressDB_Form_Parser::ELEMENT_TYPE_ENUM );
		$default_values = [];
		foreach ($field['options'] as $enum_value) {
			$is_default    = fdbarg( $enum_value, 'default', false );
			$enum_values[] = $simple_enum_value->init(
				array(
					'label'   => $enum_value['label'],
					'enumId'  => $enum['id'],
					'options' => array(
						'default' => $is_default,
						'value'   => fdbarg($enum_value, 'value'),
						'source'  => $source,
					),
				)
			)->get();
		}

		$this->api->post_enum_values($enum['id'], $enum_values);
		$enum_values = $this->api->get_enum_values($enum['id']);
		$default_values = array();
		foreach ($enum_values as $enum_value) {
			$is_default = fdbarg( $enum_value, 'options.default', false );
			if ( $is_default ) {
				$default_values[] = $enum_value['id'];
			}
		}

		$new_field = $this->form_parser->element(\FortressDB_Form_Parser::ELEMENT_TYPE_FIELD);
		$new_field->init(
			array(
				'defaultValue' => $default_values,
				'allowNull'    => true,
				'name'         => $element_id,
				'extend'       => array(
					'caption'     => $caption,
					'order'       => $order,
					'description' => $description,
					'placeholder' => $placeholder,
					'source'      => $source,
					'encrypted'   => false,
					'enumId'      => $enum['id'],
					'isMultiple'  => true
				),
			)
		);
		$new_field->set_type(\FortressDB_Field::ENUM);
		return [$new_field, $enum, $enum_values];

	}

	/**
	 * @param $fields
	 * @param $element_id
	 * @return mixed|null
	 */
	private function find_field_by_element_id($fields, $element_id) {
		foreach ($fields as $field) {
			$field_element_id = fdbarg($field, 'extend.source.elementId');
			if ($field_element_id == $element_id) {
				return $field;
			}
		}
		return null;
	}

	private function find_boolean_fields($fields, $element_id) {
		$target_fields = array();
		foreach ($fields as $index => $field) {
			$field_element_id = fdbarg($field, 'extend.source.elementId');
			if (strpos($field_element_id, ":") > 0) {
				list($parent_element_id, $tail) = explode(":", $field_element_id);
				if ($parent_element_id == $element_id) {
					$target_fields[] = $index;
				}
			}
		}
		return $target_fields;
	}

	/**
	 * @param \Forminator_Custom_Form_Model $form
	 * @param array $form_settings
	 * @param \FortressDB_Forminator_Addon $addon
	 */
	private function process_form($form, $form_settings, $addon) {
		$checkbox_fields = $form->get_fields_by_type('checkbox');
		if (empty($checkbox_fields)) {
			return true;
		}

		$fdb_fields = array_filter(
			$form_settings['metadata']['fields'],
			function ($v, $k) {
				return !$v['extend']['isSystem'];
			},
			ARRAY_FILTER_USE_BOTH);

		$fields_to_delete = array();
		$fields_to_append = array();

		$field_map = array();

		foreach ($checkbox_fields as $field) {
			$checkbox = $field->to_array();
			$element_id = $checkbox['element_id'];
			$fdb_field = $this->find_field_by_element_id($fdb_fields, $element_id);
			if (!is_null($fdb_field)) {
				return true;
			}

			$boolean_fields = $this->find_boolean_fields($fdb_fields, $element_id);

			if (empty($boolean_fields)) {
				return true;
			}

			$fields_to_delete = array_merge($fields_to_delete, $boolean_fields);
			$order = fdbarg($fdb_fields[reset($boolean_fields)], 'extend.order');

			list($new_field, $enum, $enum_values) = $this->parse_form_field($checkbox, $order);

			$fields_to_append[] = $new_field;
			fdbars($form_settings, ['linked_enums', $new_field->get('name')], $enum['id']);
			foreach ($enum_values as $enum_value) {
				$value = trim( fdbarg( $enum_value, 'options.value', '' ) );
				fdbars($form_settings, ['linked_enum_values', $enum['id'], $value], $enum_value['id']);
			}

			foreach ($boolean_fields as $boolean_field_idx) {
				$str_enum_value = fdbarg($fdb_fields, [$boolean_field_idx, 'extend', 'source', 'value']);
				$boolean_field_name = fdbarg($fdb_fields, [$boolean_field_idx, 'name']);
				foreach ($enum_values as $enum_value) {
					if (fdbarg($enum_value, 'options.value', '') == $str_enum_value) {
						$field_map[$new_field->get('name')][$boolean_field_name] = $enum_value['id'];
						break;
					}
				}
			}
		}

		foreach ($fields_to_append as $field) {
			$form_settings['metadata']['fields'][] = $field->get();
		}
		$metadata = $this->api->update_metadata($form_settings['metadata']);
		$this->move_data($form_settings['metadata']['tableName'], $field_map);

		if(!empty($fields_to_delete)) {
			$elements_to_delete = array_map(
				function($id) use ($form_settings) {
					return fdbarg($form_settings, ['metadata', 'fields', $id, 'extend', 'source', 'elementId']);
				},
				$fields_to_delete
			);
			$metadata['fields'] = array_values(
				array_filter(
					$metadata['fields'],
					function($v) use ($metadata, $elements_to_delete) {
						return !in_array(fdbarg($v, 'extend.source.elementId', null), $elements_to_delete);
					}
				)
			);
			$metadata = $this->api->update_metadata($metadata);
		}

		$form_settings['metadata'] = $metadata;

		fdbars($form_settings, 'fields_group', array());
		foreach ($form_settings['metadata']['fields'] as $field) {
			$element_id = fdbarg( $field, 'extend.source.elementId' );
			if ( $element_id ) {
				fdbars( $form_settings, array( 'fields_group', $element_id ), $field );
			}
		}

		$addon
			->get_addon_form_settings($form->id)
			->save_form_settings_values($form_settings);
		return false;
	}

	private function move_data($tableName, $field_map) {
		$objects = $this->api->get('objects', array("context" => ["tableName" => $tableName]));
		foreach (fdbarg($objects, 'rows', []) as $row) {
			$data_piece = array("id" => $row['id']);
			$append = false;

			foreach ($field_map as $checkbox => $enum_map) {
				$data_piece[$checkbox] = array();
				foreach ($enum_map as $field_name => $enum_value) {
					if(isset($row[$field_name]) && $row[$field_name] == true) {
						$data_piece[$checkbox][] = $enum_value;
						$append = true;
					}
				}
			}
			if ($append) {
				$this->api->put("objects",
					array(
						"context" => array(
							"tableName" => $tableName
						),
						"data" => [$data_piece]
					)
				);
			}
		}
	}

	/**
	 * @return \Generator
	 */
	private function forms() {
		for($page = 1;;$page++) {
			$forms = \Forminator_API::get_forms(null, $page);
			if (empty($forms)) {
				break;
			}
			foreach ($forms as $form) {
				$connected_addons = forminator_get_addons_instance_connected_with_form( $form->id );
				foreach ($connected_addons as $addon) {
					if ($addon instanceof \FortressDB_Forminator_Addon) {
						$feed = $addon->get_addon_form_settings($form->id);
						$form_settings = $feed->get_form_settings_values();
						if ($form_settings && isset($form_settings['is_connected']) && $form_settings['is_connected']) {
							yield [$form, $form_settings, $addon];
						}
						break;
					}
				}

			}
		}
	}

	public function rollback()
	{
		return false;
	}
}