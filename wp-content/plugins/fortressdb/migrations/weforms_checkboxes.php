<?php

namespace FortressDB\Migrations;

require_once 'MigrationInterface.php';

if (!class_exists('FortressDB_Form_Parser')) {
	require_once(fortressdb_includes_dir('class-fortressdb-form-parser.php'));
}


class WeformsCheckboxesMigration implements IMigration
{
	/**
	 * @var \FortressDB_Form_Parser
	 */
	protected $form_parser = null;

	/**
	 * @var \FortressDB_Wp_Api
	 */
	private $api = null;

	/**
	 * @var \WeForms
	 */
	private $weforms = null;

	protected $_slug = 'weformsfortressdb';
	protected $id = 'fortressdb';

	public function __construct()
	{
		$this->form_parser = new \FortressDB_Form_Parser( $this->_slug );
		$this->api = \FortressDB::get_api();
	}


	public function forward()
	{
		if ( ! function_exists( 'weforms' ) ) {
			return false;
		}
		$this->weforms = weforms();
		$this->weforms->includes();
		$this->weforms->init_classes();


		foreach ($this->forms() as list($form, $form_settings)) {
			$this->process_form($form, $form_settings);
		}
		return true;
	}

	/**
	 * @param $form \WeForms_Form
	 * @param $form_settings array
	 */
	protected function process_form($form, $form_settings) {
		$checkbox_fields = $this->get_fields_to_migrate(
			$this->get_checkbox_fields($form),
			$form_settings
		);

		$fields_to_append = [];
		$fields_to_delete = [];
		$field_map = [];
		$metadata = $form_settings['metadata'];

		foreach ($checkbox_fields as $checkbox) {
			$boolean_fields = $this->find_boolean_fields($checkbox['name'], $metadata['fields']);
			if (empty($boolean_fields)) {
				continue;
			}
			$fields_to_delete = array_merge($fields_to_delete, $boolean_fields);
			$order = fdbarg($metadata['fields'], [reset($boolean_fields), 'extend', 'order']);

			list($new_field, $enum, $enum_values) = $this->parse_form_field($checkbox, $order);
			$new_field->apply_extend('order', $order);
			$fields_to_append[] = $new_field;
			fdbars($form_settings, ['linked_enums', $new_field->get('name')], $enum['id']);
			foreach ($enum_values as $enum_value) {
				$value = trim( fdbarg( $enum_value, 'options.value', '' ) );
				fdbars($form_settings, ['linked_enum_values', $enum['id'], $value], $enum_value['id']);
			}

			foreach ($boolean_fields as $boolean_field_idx) {
				$str_enum_value = fdbarg($metadata['fields'], [$boolean_field_idx, 'extend', 'source', 'value']);
				$boolean_field_name = fdbarg($metadata['fields'], [$boolean_field_idx, 'name']);
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
		\FortressDB::update_form_settings( $this->_slug, $form->id, $form_settings );
	}

	/**
	 * @return \Generator
	 */
	protected function forms() {
		foreach ($this->weforms->form->all()['forms'] as $form) {
			$integration = weforms_is_integration_active( $form->id, $this->id );
			if ( false === $integration ) {
				continue;
			}
			$form_settings = \FortressDB::get_form_settings( $this->_slug, $form->id );
			if ( !$this->migration_needed($form, $form_settings) ) {
				continue;
			}
			yield [$form, $form_settings];
		}
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
	 * @return \FortressDB_Field,
	 */
	protected function parse_form_field($checkbox) {

		/**
		 * @var \FortressDB_Field
		 */
		$simple_field = $this->form_parser->element( \FortressDB_Form_Parser::ELEMENT_TYPE_FIELD );
		// $is_required = filter_var( fdbarg( $checkbox, 'required', false ), FILTER_VALIDATE_BOOLEAN );
		// $allow_null = ! $is_required;
		$element_id = fdbarg( $checkbox, 'name', '' );
		$caption = trim( fdbarg( $checkbox, 'label', '' ) );
		$placeholder = fdbarg( $checkbox, 'placeholder', '' );
		$description = '';
		$source = array( 'name' => 'WeForms', 'elementId' => $element_id );

		// Create ENUM

		$simple_enum_value = $this->form_parser->element( \FortressDB_Form_Parser::ELEMENT_TYPE_ENUM );
		$enum_values = array();
		$enum = $this->api->post_enum( $caption, $description, array( 'source' => $source ) );
		foreach ($checkbox['options'] as $key => $option_caption) {
			$is_default = in_array($key, $checkbox['selected']);
			$enum_values[] = $simple_enum_value->init(
				array(
					'label'   => $option_caption,
					'enumId'  => $enum['id'],
					'options' => array(
						'default' => $is_default,
						'value'   => $key,
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

		$default_data        = array(
			'defaultValue' => $default_values,
			'allowNull'    => true,
			'type'         => \FortressDB_Field::ENUM,
			'name'         => $element_id,
			'extend'       => array(
				'caption'     => $caption,
				'isMandatory' => false,
				'description' => '',
				'placeholder' => $placeholder,
				'source'      => $source,
				'encrypted'   => false,
				'isMultiple'  => true,
				'enumId'      => $enum['id'],
			),
		);
		$simple_field->init($default_data);
		return [$simple_field, $enum, $enum_values];
	}

	/**
	 * @param $form \WeForms_Form
	 * @param $form_settings array
	 * @return bool
	 */
	protected function migration_needed($form, $form_settings) {
		$checkbox_fields = $this->get_checkbox_fields($form);
		$abandoned_checkboxes = $this->get_fields_to_migrate($checkbox_fields, $form_settings);

		return !empty($abandoned_checkboxes);
	}

	protected function get_checkbox_fields($form) {
		return array_filter(
			$form->get_fields(),
			function ($field) {
				return $field['template'] == 'checkbox_field';
			}
		);
	}

	protected function get_fields_to_migrate($checkbox_fields, $form_settings) {
		$fdb_fields = array_filter(
			fdbarg($form_settings, 'metadata.fields', []),
			function ($field) {
				return !fdbarg($field, 'extend.isSystem', false);
			}
		);

		return array_filter(
			$checkbox_fields,
			function ($checkbox) use ($fdb_fields) {
				foreach ($fdb_fields as $field) {
					if ($field['name'] == $checkbox['name']) {
						return false;
					}
				}
				return true;
			}
		);
	}

	protected function find_boolean_fields($element_id, $fdb_fields) {
		$target_fields = array();
		foreach ($fdb_fields as $index => $field) {
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

	public function rollback()
	{
		return false;
	}
}