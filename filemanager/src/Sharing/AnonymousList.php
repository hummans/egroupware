<?php
/**
 * EGroupware - Filemanager - UI for sharing with anonymous user
 *
 * @link http://www.egroupware.org
 * @package filemanager
 * @author Nathan Gray
 * @copyright (c) 2020 Nathan Gray
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */
namespace EGroupware\Filemanager\Sharing;

use \filemanager_ui;
use EGroupware\Api;
use EGroupware\Api\Vfs;

/**
 * This is the file list for an anonymous user.
 * Logged in users may also end up here, but they will normally just use filemanager_ui
 */
class AnonymousList extends filemanager_ui
{
	/**
	 * Constructor
	 *
	 * Reimplemented to load filemanager translations
	 */
	function __construct()
	{
		parent::__construct();

		Api\Translation::add_app('filemanager');
	}

	/**
	 * Get active view - override so it points to this class
	 *
	 * @return callable
	 */
	public static function get_view()
	{
		return array(new AnonymousList(), 'listview');
	}

	/**
	 * Filemanager listview
	 *
	 * @param array $content
	 * @param string $msg
	 */
	function listview(array $content = null, $msg = null)
	{
		$this->etemplate = $this->etemplate ? $this->etemplate : new Api\Etemplate(static::LIST_TEMPLATE);

		// Override and take over get_rows so we can customize
		$content['nm']['get_rows'] = '.' . get_class($this) . '.get_rows';

		return parent::listview($content, $msg);
	}

	/**
	 * Get the configured start directory for the current user
	 *
	 * @return string
	 */
	static function get_home_dir()
	{
		return $GLOBALS['egw']->sharing->get_root();
	}

	/**
	 * Context menu
	 *
	 * @return array
	 */
	public static function get_actions()
	{
		$actions = parent::get_actions();
		$group = 1;
		// do not add edit setting action when we are in sharing
		unset($actions['edit']);
		if (Vfs::is_writable($GLOBALS['egw']->sharing->get_root()))
		{
			return $actions;
		}
		$actions += array(
				'egw_copy' => array(
						'enabled' => false,
						'group' => $group + 0.5,
						'hideOnDisabled' => true
				),
				'egw_copy_add' => array(
						'enabled' => false,
						'group' => $group + 0.5,
						'hideOnDisabled' => true
				),
				'paste' => array(
						'enabled' => false,
						'group' => $group + 0.5,
						'hideOnDisabled' => true
				),
		);
		return $actions;
	}

	protected function get_vfs_options($query)
	{
		$options = parent::get_vfs_options($query);

		// Hide symlinks
		// TODO: This hides everything, see Vfs::_check_add() line 648
		//$options['type'] = '!l';

		return $options;
	}

	/**
	 * Callback to fetch the rows for the nextmatch widget
	 *
	 * @param array $query
	 * @param array &$rows
	 * @return int
	 */
	function get_rows(&$query, &$rows)
	{
		// Check for navigating outside share, redirect back to share
		if (!empty($query['path']) && (!Vfs::stat($query['path'], false) || !Vfs::is_dir($query['path']) || !Vfs::check_access($query['path'], Vfs::READABLE)))
		{
			// only redirect, if it would be to some other location, gives redirect-loop otherwise
			if ($query['path'] != ($path = static::get_home_dir()))
			{
				// we will leave here, since we are not allowed, go back to root
				// TODO: Give message about it, redirect to home dir
			}
			$rows = array();
			return 0;
		}

		// Get file list from parent
		$total = parent::get_rows($query, $rows);

		return $total;
	}
}