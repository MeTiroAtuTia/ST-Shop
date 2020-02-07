<?php

/**
 * @package ST Shop
 * @version 3.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class AdminShopModules extends AdminShop
{
	public static function Main()
	{
		global $context, $txt;

		loadTemplate('ShopAdmin');

		$context['items_url'] = Shop::$itemsdir;

		$subactions = array(
			'index' => 'AdminShopModules::Index',
			'delete' => 'AdminShopModules::Delete',
			'delete2' => 'AdminShopModules::Delete2',
			'uploadmodules' => 'AdminShopModules::Upload',
			'uploadmodules2' => 'AdminShopModules::Upload2',
		);

		$sa = isset($_GET['sa'], $subactions[$_GET['sa']]) ? $_GET['sa'] : 'index';

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['Shop_tab_modules'],
			'description' => $txt['Shop_tab_modules_desc'],
			'tabs' => array(
				'index' => array('description' => $txt['Shop_tab_modules_desc']),
				'uploadmodules' => array('description' => $txt['Shop_modules_uploadmodules_desc']),
			),
		);

		$subactions[$sa]();
	}

	public static function modulesCount()
	{
		global $smcFunc;

		// Count the modules
		$modules = $smcFunc['db_query']('', '
			SELECT id
			FROM {db_prefix}shop_modules',
			array()
		);
		$count = $smcFunc['db_num_rows']($modules);
		$smcFunc['db_free_result']($modules);

		return $count;
	}

	public static function modulesGet($start, $items_per_page, $sort)
	{
		global $context, $smcFunc;

		// Get a list of all the item
		$result = $smcFunc['db_query']('', '
			SELECT s.name, s.id, s.file, s.author, s.web, s.email, s.price, s.description, s.can_use, s.require_input, s.editable_input
			FROM {db_prefix}shop_modules AS s
			ORDER by {raw:sort}
			LIMIT {int:start}, {int:maxindex}',
			array(
				'start' => $start,
				'maxindex' => $items_per_page,
				'sort' => $sort,
			)
		);

		// Return the data
		$context['shop_modules_list'] = array();
		while ($row = $smcFunc['db_fetch_assoc']($result))
			$context['shop_modules_list'][] = $row;
		$smcFunc['db_free_result']($result);

		return $context['shop_modules_list'];
	}

	public static function Index()
	{
		global $context, $scripturl, $sourcedir, $modSettings, $txt;

		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'moduleslist';
		$context['page_title'] = $txt['Shop_tab_modules']. ' - ' . $txt['Shop_tab_modules'];

		// The entire list
		$listOptions = array(
			'id' => 'moduleslist',
			'title' => $txt['Shop_tab_modules'],
			'items_per_page' => !empty($modSettings['Shop_items_perpage']) ? $modSettings['Shop_items_perpage'] : 15,
			'base_href' => '?action=admin;area=shopmodules;sa=index',
			'default_sort_col' => 'function',
			'get_items' => array(
				'function' => 'AdminShopModules::modulesGet',
			),
			'get_count' => array(
				'function' => 'AdminShopModules::modulesCount',
			),
			'no_items_label' => $txt['Shop_no_modules'],
			'no_items_align' => 'center',
			'columns' => array(
				'module_name' => array(
					'header' => array(
						'value' => $txt['Shop_item_name'],
					),
					'data' => array(
						'db' => 'name',
						'style' => 'width: 12%',
					),
					'sort' =>  array(
						'default' => 'name DESC',
						'reverse' => 'name',
					),
				),
				'description' => array(
					'header' => array(
						'value' => $txt['Shop_item_description'],
					),
					'data' => array(
						'db' => 'description',
						'style' => 'width: 20%',
					),
					'sort' =>  array(
						'default' => 'description DESC',
						'reverse' => 'description',
					),
				),
				'function' => array(
					'header' => array(
						'value' => $txt['Shop_item_function'],
					),
					'data' => array(
						'db' => 'file',
						'style' => 'width: 5%',
					),
					'sort' =>  array(
						'default' => 'file ASC',
						'reverse' => 'file DESC',
					),
				),
				/**
					TODO: Display items using that module
				**/
				'delete' => array(
					'header' => array(
						'value' => $txt['delete']. ' <input type="checkbox" onclick="invertAll(this, this.form, \'delete[]\');" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="delete[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'class' => 'centertext',
						'style' => 'width: 2%',
					),
				),
			),
			'form' => array(
				'href' => '?action=admin;area=shopmodules;sa=delete',
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
				'include_sort' => true,
				'include_start' => true,
			),
			'additional_rows' => array(
				'submit' => array(
					'position' => 'below_table_data',
					'value' => '<input type="submit" size="18" value="'.$txt['delete']. '" class="button" />',
				),
				'updated' => array(
					'position' => 'top_of_list',
					'value' => (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : '<div class="infobox">'. $txt['Shop_items_updated']. '</div>') : '<div class="infobox">'. $txt['Shop_items_added']. '</div>') : '<div class="infobox">'. $txt['Shop_items_deleted']. '</div>'),
				),
			),
		);
		// Let's finishem
		createList($listOptions);
	}

	public static function Upload()
	{
		global $context, $scripturl, $boarddir, $txt;

		if (empty($context['user']['is_admin']))
			fatal_error($txt['Shop_modules_only_admin'], false);

		// Page information
		$context['page_title'] = $txt['Shop_admin_button'] . ' - '. $txt['Shop_modules_uploadmodules'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];
		$context['sub_template'] = 'Shop_modulesUpload';	
	}

	public static function Upload2()
	{
		global $boarddir, $context, $txt, $smcFunc;

		$context[$context['admin_menu_name']]['current_subsection'] = 'uploadmodules';

		if (empty($context['user']['is_admin']))
			fatal_error($txt['Shop_modules_only_admin'], false);

		checkSession();

		$fail = true;
		$filename = '';
		$upload = false;

		// Upload File Form
		if (isset($_FILES['newitem']['name']) && $_FILES['newitem']['name'] != '') {
			$filename = $_FILES['newitem']['name'];
			$filesize = $_FILES['newitem']['size'];
			$upload = true;
			$fail = false;
		}

		if ($fail == false)
		{
			// Copy the file
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$filename = str_replace(('.'.$ext), '', $filename);
			$uploadto = $boarddir . Shop::$modulesdir;
			$target_file = $uploadto . basename($_FILES['newitem']['name']);

			// Allow certain file formats
			if ($ext != 'php') {
				fatal_error($txt['Shop_file_error_type2'], false);
				$uploadOk = false;
			}
			// Check if file already exists
			if (file_exists($target_file)) {
				fatal_error($txt['Shop_file_already_exists'], false);
				$upload = false;
			}
			// Check file size
			if ($_FILES['newitem']['size'] > 500000) {
				fatal_error($txt['Shop_file_too_large'], false);
				$upload = false;
			}

			if ($upload == true)
			{
				if (move_uploaded_file($_FILES['newitem']['tmp_name'], $target_file))
				{
					// Add the new information to the database
					require_once($boarddir . Shop::$modulesdir . basename($_FILES['newitem']['name']));
					// Create an instance of the item (it's used below)
					$code = '
						if (class_exists(\'item_' . $filename . '\')) {
							$tempItem = new item_' . $filename . ';
							return true;
						}
						else
							return false;';	
					// If we could create an instance of the item...
					if (eval($code) !== FALSE) {
						// Get the actual info
						$tempItem->getItemDetails();
						// Insert the actual item
						$smcFunc['db_insert']('',
							'{db_prefix}shop_modules',
							array(
								'name' => 'string', 
								'description' => 'string',
								'price' => 'float',
								'file' => 'string',
								'author' => 'string',
								'email' => 'string',
								'web' => 'string',
								'require_input' => 'int',
								'can_use' => 'int',
								'editable_input' => 'int',
								),
							array(
								'name' => $tempItem->name, 
								'description' => $tempItem->desc,
								'price' => $tempItem->price,
								'file' => $filename,
								'author' => $tempItem->authorName,
								'email' => $tempItem->authorEmail,
								'web' => $tempItem->authorWeb,
								'require_input' => (int) $tempItem->require_input,
								'can_use' => (int) $tempItem->can_use_item,
								'editable_input' => (int) $tempItem->addInput_editable,
								),
							array()
						);
						// Get me out of here
						redirectexit('action=admin;area=shopmodules;sa=uploadmodules;success');
					}
					else {
						unlink($boarddir . Shop::$modulesdir . basename($_FILES['newitem']['name']));
						fatal_error($txt['Shop_modules_invalid'], false);
					}
				}
			}
		}
		else
			redirectexit('action=admin;area=shopmodules;sa=uploadmodules;error');
	}

	public static function Delete()
	{
		global $context, $smcFunc, $modSettings, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['Shop_tab_modules'] . ' - '. $txt['Shop_modules_delete'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['Shop_modules_delete'],
		);
		$context['sub_template'] = 'Shop_modulesDelete';

		// If nothing was chosen to delete
		// TODO: Should this just return to the do=edit page, and show the error there?
		if (!isset($_REQUEST['delete']))
			fatal_error($txt['item_delete_error'], false);

		// Make sure all IDs are numeric
		foreach ($_REQUEST['delete'] as $key => $value)
			$_REQUEST['delete'][$key] = (int) $value;

		// Start with an empty array of items
		$context['shop_modules_delete'] = array();

		// Get information on all the items selected to be deleted
		$result = $smcFunc['db_query']('', '
			SELECT id, name, file
			FROM {db_prefix}shop_modules
			WHERE id IN ({array_int:ids})
			ORDER BY name ASC',
			array(
				'ids' => $_REQUEST['delete']
			)
		);

		// Loop through all the results...
		while ($row = $smcFunc['db_fetch_assoc']($result))
			// ... and add them to the array
			$context['shop_modules_delete'][] = array(
				'id' => $row['id'],
				'name' => $row['name'],
				'file' => $row['file']
			);
		$smcFunc['db_free_result']($result);
	}

	public static function Delete2()
	{
		global $context, $smcFunc, $modSettings, $txt, $boarddir;

		// Set all the page stuff
		$context['page_title'] = $txt['Shop_tab_modules'] . ' - '. $txt['Shop_modules_delete'];
		$context[$context['admin_menu_name']]['current_subsection'] = 'index';

		// If nothing was chosen to delete (shouldn't happen, but meh)
		if (!isset($_REQUEST['delete']))
			fatal_error($txt['Shop_item_delete_error'], false);
		
		// Make sure all IDs are numeric
		foreach ($_REQUEST['delete'] as $key => $value)
			$_REQUEST['delete'][$key] = (int) $value;

		// Items using this module are... no longer using it
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}shop_items
			SET
				module = 0,
				function = {string:def},
				input_needed = 0,
				can_use_item = 0,
				delete_after_use = 0
			WHERE module IN ({array_int:ids})',
			array(
				'def' => 'Default',
				'ids' => $_REQUEST['delete'],
			)
		);

		// Delete all the items
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}shop_modules
			WHERE id IN ({array_int:ids})',
			array(
				'ids' => $_REQUEST['delete'],
			)
		);

		// Delete files from directory
		foreach ($_REQUEST['files'] as $key => $file) 
			unlink($boarddir . Shop::$modulesdir . basename($file. '.php'));

		// Send the user to the items list with a message
		redirectexit('action=admin;area=shopmodules;sa=index;deleted');
	}
}