<?php
namespace FortressDB\Migrations;
require_once 'MigrationInterface.php';
require_once 'forminator_checkboxes.php';
require_once 'weforms_checkboxes.php';
require_once 'integrations_decryption.php';


class MigrationManager {
//  We don't need them anymore

//	protected $migrations = [
//		'forminator_checkboxes' => ForminatorCheckboxesMigration::class,
//		'weforms_checkboxes' => WeformsCheckboxesMigration::class,
//		'integrations_decryption' => IntegrationsDecryptionMigration::class,
//	];

	protected $migrations = [];

	private static $_instance = null;

	/**
	 * @return MigrationManager
	 */
	public static function getInstance() {
		if (static::$_instance == null) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}


	protected function runMigrations($migrations) {

		$new_migrations = array();
		foreach ($migrations as $migration_key) {
			$migration_cls = $this->migrations[$migration_key];
			$migration = new $migration_cls();
			if ($migration->forward()) {
				$new_migrations[] = $migration_key;
			}
		}
		return $new_migrations;
	}

	public function applyMigrations() {
		if (!fortressdb_is_connected()) {
			return false;
		}
		wp_cache_flush();
		$options = fortressdb_get_plugin_options();
		if (empty($options)) {
			return false;
		}

		$running = fdbarg($options, 'migrations.running', false);

		if ($running) {
			return false;
		}

		$applied_migrations = array();

		try {
			fdbars($options, 'migrations.running', true);
			fortressdb_update_plugin_options($options);
			$migrations          = array_keys($this->migrations);

			$done_migrations     = fdbarg($options, 'migrations.applied', array());
			$migrations_to_apply = array_diff($migrations, $done_migrations);

			if (!empty($migrations_to_apply)) {
				$applied_migrations = $this->runMigrations($migrations_to_apply);
			}
		} finally {
			wp_cache_flush();
			$options = fortressdb_get_plugin_options();
			$done_migrations = fdbarg($options, 'migrations.applied', array());
			fdbars($options, 'migrations.applied', array_merge($done_migrations, $applied_migrations));
			fdbars($options, 'migrations.running', false);
			fortressdb_update_plugin_options($options);
			wp_cache_flush();
		}
	}
}