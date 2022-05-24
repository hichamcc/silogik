<?php
namespace FortressDB\Migrations;

/**
 * Interface IMigration
 * @package FortressDB\Migrations
 */
interface IMigration {
	/**
	 * Appl
	 * @return boolean
	 */
	public function forward();
	public function rollback();
}