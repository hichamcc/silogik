<?php
namespace FortressDB\Migrations;

require_once 'MigrationInterface.php';

class IntegrationsDecryptionMigration implements IMigration {
	/**
	 * @var \FortressDB_Wp_Api
	 */
	private $api = null;

	public function __construct()
	{
		$this->api = \FortressDB::get_api();
	}

	public function forward()
	{
		$payload  = array(
			'filter' => array(
				'where' => array(
					'operator' => '?',
					'args' => array(
						array(
							'func' => 'cast',
							'args' => array(
								'options',
								array(
									'literal' => 'jsonb'
								)
							)
						),
						array(
							'value' => 'source'
						)
					)
				)
			)
		);
		$response = $this->api->get( 'metadata', $payload );

		$rows = fdbarg( $response, 'rows', [] );
		foreach ($rows as &$metadata) {
			// 1. setup isMandatory: false, allowNull: true
			$update = false;
			foreach ($metadata['fields'] as &$field) {
				if (fdbarg( $field, 'extend.isSystem', false ) === false
					&& (
						fdbarg( $field, 'allowNull' ) === false
						|| fdbarg( $field, 'extend.isMandatory' ) === true
					)
				) {
					$update = true;
					$field['allowNull'] = true;
					$field['extend']['isMandatory'] = false;
				}
			}
			if ($update === true) {
				$metadata = $this->api->update_metadata($metadata);
			}
			// 2. setup encrypted: false
			$decrypt = false;
			foreach ($metadata['fields'] as &$field) {
				if (fdbarg( $field, 'extend.isSystem', false ) === false
					&& fdbarg( $field, 'extend.encrypted' ) === true
				) {
					$decrypt = true;
					$field['extend']['encrypted'] = false;
				}
			}
			if ($decrypt === true) {
				$this->api->update_metadata($metadata);
			}
		}

		return true;
	}

	public function rollback()
	{
		return false;
	}
}