<?php
/**
 *
 * Custom Topic Title Length. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Vinny <https://github.com/vinny>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vinny\customtitlelength\migrations\v100;

class install_config extends \phpbb\db\migration\migration
{
	/**
	 * Check if the migration is effectively installed.
	 *
	 * @return bool
	 */
	public function effectively_installed()
	{
		return isset($this->config['customtitlelength_max_chars']);
	}

	/**
	 * Assign migration file dependencies for this migration.
	 *
	 * @return array
	 */
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	 * Add custom title length configuration to the database.
	 *
	 * @return array
	 */
	public function update_data()
	{
		return [
			['config.add', ['customtitlelength_max_chars', 120]],
		];
	}

	/**
	 * Remove custom title length configuration when purging the extension.
	 *
	 * @return array
	 */
	public function revert_data()
	{
		return [
			['config.remove', ['customtitlelength_max_chars']],
		];
	}
}
