<?php

/**
 * @package ST Shop
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace Shop\View;

use Shop\Shop;
use Shop\Helper\Database;
use Shop\Helper\Images;
use Shop\Helper\Format;
use Shop\Modules;

if (!defined('SMF'))
	die('No direct access...');

class Inventory
{
	var $item_module = 'Shop\\Modules\\';
	var $tabs = [];

	public static $inventory_items = [];

	function __construct()
	{
		// Build the tabs for this section
		$this->tabs();
	}

	public function main()
	{
		global $boardurl, $context, $scripturl, $modSettings, $user_info, $memberContext, $sourcedir;

		// Check if user is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Get the actual owner of this inventory
		$memberResult = loadMemberData((isset($_REQUEST['user']) ? $_REQUEST['user'] : ((isset($_REQUEST['u']) ? $_REQUEST['u'] : $user_info['id']))), isset($_REQUEST['user']), 'profile');
	
		// Don't mind me, just checking if it's a valid profile
		if (!$memberResult)
			fatal_error(Shop::getText('user_unable_tofind'), false);

		// If all went well, we have a valid member ID!
		list ($memID) = $memberResult;
		
		// Let's have some information about this member ready, too.
		loadMemberContext($memID);
		$context['member'] = $memberContext[$memID];
		$context['user']['is_owner'] = $memID == $user_info['id'];

		// Set all the page stuff
		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'inventory';
		$context['page_title'] = Shop::getText('main_button') . ' - ' . (($user_info['id'] == $context['member']['id']) ? Shop::getText('inventory_myinventory') : sprintf(Shop::getText('inventory_viewing_who'), $context['member']['name']));
		$context['template_layers'][] = 'options';
		$context['linktree'][] = [
			'url' => $scripturl . '?action=shop;sa=inventory;u='.$context['member']['id'],
			'name' => (($user_info['id'] == $context['member']['id']) ? Shop::getText('inventory_myinventory') : sprintf(Shop::getText('inventory_viewing_who'), $context['member']['name'])),
		];
		// Sub-menu tabs
		$context['section_tabs'] = $this->tabs;
		// Images...
		$context['items_url'] = $boardurl . Shop::$itemsdir;
		$context['shop_images_list'] = Images::list();
		// ... and categories
		$context['shop_categories_list'] = Database::Get(0, 1000, 'sc.name', 'shop_categories AS sc', Database::$categories);
		$context['form_url'] = '?action=shop;sa=inventory'. (!empty($context['user']['is_owner']) ? '' : ';u='. $context['member']['id']) . (isset($_REQUEST['cat']) && $_REQUEST['cat'] >= 0 ? ';cat='.$_REQUEST['cat'] : '');

		// The entire list
		$listOptions = $this->inventory_list($memberContext[$memID], $context['form_url']);

		// Traded message
		if (isset($_REQUEST['traded']))
			$listOptions['additional_rows']['traded'] = [
				'position' => 'above_column_headers',
				'value' => '<div class="clear"></div><div class="infobox">'.Shop::getText('item_traded').'</div>',
			];

		// Purchase message
		if (isset($_REQUEST['purchased']))
			$listOptions['additional_rows']['purchased'] = [
				'position' => 'above_column_headers',
				'value' => '<div class="clear"></div><div class="infobox">'.sprintf(Shop::getText('buy_purchased'), Format::cash($user_info['shopMoney'])).'</div>',
			];

		// Remove the columns only the owner should see
		if (empty($context['user']['is_owner']))
		{
			unset($listOptions['columns']['item_use']);
			unset($listOptions['columns']['item_fav']);
			unset($listOptions['columns']['item_trade']);
		}
		// No trade no fun
		if (empty($modSettings['Shop_enable_trade']))
		{
			unset($listOptions['columns']['item_trade']);
			unset($listOptions['additional_rows']['traded']);
		}

		// Show the list
		createList($listOptions);
	}

	public function tabs()
	{
		$this->tabs = [
			'inventory' => [
				'action' => ['inventory', 'invtrade', 'invtrade2', 'invuse'],
				'label' => Shop::getText('inventory_myinventory'),
			],
			'search' => [
				'action' => ['search', 'search2'],
				'label' => Shop::getText('inventory_search'),
			],
		];
	}

	public function inventory_list($memberResult, $form_url)
	{
		global $modSettings, $user_info, $context;

		$listOptions = [
			'id' => 'inventory',
			'items_per_page' => !empty($modSettings['Shop_items_perpage']) ? $modSettings['Shop_items_perpage'] : 15,
			'base_href' => $form_url,
			'default_sort_col' => 'item_date',
			'default_sort_dir' => 'DESC',
			'get_items' => [
				'function' => 'Shop\Helper\Database::Get',
				'params' => ['shop_inventory AS si', array_merge(Database::$inventory, array_merge(Database::$items, ['sc.name AS category'])), 'WHERE si.userid = {int:user} AND si.trading = 0 AND s.status = 1'. (isset($_REQUEST['cat']) && $_REQUEST['cat'] >= 0 ? ' AND s.catid = {int:cat}' : ''), false, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid) LEFT JOIN {db_prefix}shop_categories AS sc ON (s.catid = sc.catid)', ['cat' => isset($_REQUEST['cat']) ? $_REQUEST['cat'] : 0, 'user' => $memberResult['id']]],
			],
			'get_count' => [
				'function' => 'Shop\Helper\Database::Count',
				'params' => ['shop_inventory AS si', array_merge(Database::$inventory, Database::$items), 'WHERE si.userid = {int:user} AND si.trading = 0 AND s.status = 1'. (isset($_REQUEST['cat']) && $_REQUEST['cat'] >= 0 ? ' AND s.catid = {int:cat}' : ''), 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid)', ['cat' => isset($_REQUEST['cat']) ? $_REQUEST['cat'] : 0, 'user' => $memberResult['id']]],
			],
			'no_items_label' =>  (($user_info['id'] == $memberResult['id']) ? Shop::getText('inventory_no_items') : Shop::getText('inventory_other_no_items')),
			'no_items_align' => 'center',
			'columns' => [
				'item_image' => [
					'header' => [
						'value' => Shop::getText('item_image'),
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{
							return Format::image($row['image']);
						},
						'style' => 'width: 10%',
						'class' => 'centertext',
					],
				],
				'item_name' => [
					'header' => [
						'value' => Shop::getText('item_name'),
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row)
						{
							return '<span style="font-size:110%">'.$row['name'] .'</span>' . (!empty($row['description']) ? '<br/><span class="smalltext">' . $row['description'] . '</span>' : '');
						},
						'class' => 'lefttext',
						'style' => 'width: 32%',
					],
					'sort' =>  [
						'default' => 'name',
						'reverse' => 'name DESC',
					],
				],
				'item_category' => [
					'header' => [
						'value' => Shop::getText('item_category'),
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row)
						{ 
							 return (!empty($row['catid']) ? $row['category'] : Shop::getText('item_uncategorized'));
						},
						'class' => 'lefttext',
					],
					'sort' =>  [
						'default' => 'category DESC',
						'reverse' => 'category',
					],
				],
				'item_date' => [
					'header' => [
						'value' => Shop::getText('item_date'),
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{ 
							return timeformat($row['date']);
						},
						'class' => 'centertext',
					],
					'sort' =>  [
						'default' => 'date DESC',
						'reverse' => 'date',
					],
				],
				'item_use' => [
					'header' => [
						'value' => Shop::getText('item_use'),
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{
							global $context, $scripturl;
							// Is item usable?
							if ($row['can_use_item'] == 1)
								$message = '<a href="'. $scripturl. '?action=shop;sa=invuse;id='. $row['id']. '">' . Shop::getText('item_useit') . '</a>';
							else
								$message = '<strong>'. Shop::getText('item_notusable'). '</strong>';

							return $message;
						},
						'class' => 'centertext',
					],
					'sort' =>  [
						'default' => 'id DESC',
						'reverse' => 'id',
					],
				],
				'item_fav' => [
					'header' => [
						'value' => Shop::getText('item_fav'),
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{ 
							global $scripturl, $context, $settings;

							// Is item usable?
							$fav = '<a href="'. $scripturl. '?action=shop;sa=invfav;id='. $row['id']. ';fav='. (($row['fav'] == 1) ? 0 : 1). ';'. $context['session_var'] .'='. $context['session_id'] .'">							
										<img src="'. $settings['default_images_url']. '/icons/shop/fav'. (($row['fav'] == 1) ? '' : '-empty'). '.png" />
									</a>';

							return $fav;
						},
						'class' => 'centertext',
					],
					'sort' =>  [
						'default' => 'fav DESC',
						'reverse' => 'fav',
					],
				],
				'item_trade' => [
					'header' => [
						'value' => Shop::getText('item_trade'),
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{
							global $scripturl;

							return '<a href="'. $scripturl. '?action=shop;sa=invtrade;id='. $row['id']. '">'. Shop::getText('item_trade_go'). '</a>';
						},
						'class' => 'centertext',
					],
					'sort' =>  [
						'default' => 'id DESC',
						'reverse' => 'id',
					],
				],
			],
		];

		return $listOptions;
	}

	public function search()
	{
		global $context, $scripturl;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Inventory template
		loadTemplate('Shop/Inventory');

		// Set all the page stuff
		$context['page_title'] = Shop::getText('main_button') . ' - ' . Shop::getText('inventory_search_i');
		$context['template_layers'][] = 'options';
		$context['template_layers'][] = 'shop_inventory_search';
		$context['sub_template'] = 'shop_inventory_search';
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=shop;sa=search',
			'name' => Shop::getText('inventory_search_i'),
		);
		// Sub-menu tabs
		$context['section_tabs'] = $this->tabs;
		// Form
		$context['form_url'] = '?action=shop;sa=search2';

		// Load suggest.js
		loadJavaScriptFile('suggest.js', array('default_theme' => true, 'defer' => false, 'minimize' => true), 'smf_suggest');
	}

	public function search_inventory()
	{
		global $user_info, $context, $scripturl;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Title
		$context['page_title'] = Shop::getText('main_button') . ' - ' . Shop::getText('inventory_search_i');

		// Improve it's look just for the shop action
		if ($_REQUEST['action'] == 'shop')
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=shop;sa=search',
				'name' => Shop::getText('inventory_search_i'),
			);

		checkSession();
		$member_query = [];
		$member_parameters = [];

		// Got a user?
		if (empty($_REQUEST['membername']) || !isset($_REQUEST['membername']))
			fatal_error(Shop::getText('user_unable_tofind'), false);

		// Get the member name...
		$member_name = Database::sanitize($_REQUEST['membername']);

		// Construct the query
		if (!empty($member_name))
		{
			$member_query[] = 'LOWER(member_name) = {string:member_name}';
			$member_query[] = 'LOWER(real_name) = {string:member_name}';
			$member_parameters['member_name'] = $member_name;
		}

		// Execute
		if (!empty($member_query))
		{
			$memResult = Database::Get(0, 1000, 'id_member', 'members', ['id_member'], 'WHERE (' . implode(' OR ', $member_query) . ')', true, '', $member_parameters);

			// We got a result?
			if (empty($memResult))
				fatal_error(Shop::getText('user_unable_tofind'), false);
			// Redirect
			else
				redirectexit('action=' . ($_REQUEST['action'] == 'admin' ? 'admin;area=shopinventory;sa=userinv' : 'shop;sa=inventory') . ($user_info['id'] == $memResult['id_member'] ? '' : ';u='. $memResult['id_member']));
		}
	}

	public function use()
	{
		global $context, $scripturl;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Provisional title
		$context['page_title'] = Shop::getText('main_button') . ' - ' . Shop::getText('inventory_myinventory');

		// Do we have an item? No? Bad luck...
		if (empty($_REQUEST['id']) || !isset($_REQUEST['id']))
			fatal_error(Shop::getText('item_notfound'), false);

		// Get the item id
		$use = (int) $_REQUEST['id'];

		// Item details
		$item = Database::Get('', '', '', 'shop_inventory AS si', array_merge(array_merge(Database::$inventory, Database::$items), ['sm.file']), 'WHERE si.id = {int:use} AND s.status = 1', true, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid) LEFT JOIN {db_prefix}shop_modules AS sm ON (sm.id = s.module)', ['use' => $use]);

		// Validate info
		$this->use_validate($item);

		// Set page stuff
		$context['page_title'] = Shop::getText('main_button') . ' - ' . Shop::getText('main_inventory'). ' - ' . $item['name'];
		$context['linktree'][] = [
			'url' => $scripturl . '?action=shop;sa=invuse=' . $use,
			'name' => sprintf(Shop::getText('item_using'), $item['name'])
		];
		$context['template_layers'][] = 'options';
		$context['sub_template'] = 'use';
		$context['item'] = $item;

		// Store it somewhere
		$this->item_module .= $item['file'];

		// Is the item still there?
		if (!class_exists($this->item_module))
		{
			// Disable this item?
			Database::Update('shop_items', ['id' => $item['itemid']], 'status = 0,', 'WHERE itemid = {int:id}');

			// Notify the user
			fatal_error(Shop::getText('module_notfound_admin'), false);
		}

		//... and the actual item.
		$itemModel = new $this->item_module;

		// Update values
		$itemModel->item_info[1] = $item['info1'];
		$itemModel->item_info[2] = $item['info2'];
		$itemModel->item_info[3] = $item['info3'];
		$itemModel->item_info[4] = $item['info4'];

		// Execute
		$context['shop']['use']['input'] = $itemModel->getUseInput();

		// Load suggest.js for special cases
		loadJavaScriptFile('suggest.js', array('default_theme' => true, 'defer' => false, 'minimize' => true), 'smf_suggest');
	}

	public function used()
	{
		global $context, $scripturl;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Do we have an item? No? Bad luck...
		if (empty($_REQUEST['id']) || !isset($_REQUEST['id']))
			fatal_error(Shop::getText('item_notfound'), false);

		// Get the item id
		$use = (int) $_REQUEST['id'];

		// Item details
		$item = Database::Get('', '', '', 'shop_inventory AS si', array_merge(array_merge(Database::$inventory, Database::$items), ['sm.file']), 'WHERE si.id = {int:use} AND s.status = 1', true, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid) LEFT JOIN {db_prefix}shop_modules AS sm ON (sm.id = s.module)', ['use' => $use]);

		// Validate info
		$this->use_validate($item);

		// Set page stuff
		$context['page_title'] = Shop::getText('main_button') . ' - ' . Shop::getText('main_inventory'). ' - ' . $item['name'];
		$context['linktree'][] = [
			'url' => $scripturl . '?action=shop;sa=invused=' . $use,
			'name' => sprintf(Shop::getText('item_using'), $item['name'])
		];
		$context['template_layers'][] = 'options';
		$context['sub_template'] = 'invused';
		$context['item'] = $item;

		// Check session
		checkSession();

		//... and the actual item.
		$this->item_module .= $item['file'];
		$itemModel = new $this->item_module;

		// Update values
		$itemModel->item_info[1] = $item['info1'];
		$itemModel->item_info[2] = $item['info2'];
		$itemModel->item_info[3] = $item['info3'];
		$itemModel->item_info[4] = $item['info4'];
		
		// Execute
		$context['shop']['used']['input'] = $itemModel->onUse();

		// Dow we need to remove the item after use?
		if (!empty($item['delete_after_use']))
			Database::Delete('shop_inventory', 'id', $use);
	}

	public function use_validate($data)
	{
		global $user_info;

		// No item? No luck then
		if (empty($data))
			fatal_error(Shop::getText('item_notfound'), false);
		// You cannot use items you don't own
		elseif ($data['userid'] != $user_info['id'])
			fatal_error(Shop::getText('item_notown_use'), false);
		// The item should be usable
		elseif (empty($data['can_use_item']))
			fatal_error(Shop::getText('item_not_usable'), false);
		// The item should not be on trade
		elseif (!empty($data['trading']))
			fatal_error(Shop::getText('item_currently_traded'), false);
	}

	public function fav()
	{
		global $user_info;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// Make sure we got the info
		if (empty($_REQUEST['id']) || !isset($_REQUEST['id']) || !isset($_REQUEST['fav']))
			fatal_error(Shop::getText('item_notfound'), false);

		// Get the item id
		$itemid = (int) $_REQUEST['id'];
		// Fav value
		$fav = (int) $_REQUEST['fav'];

		// Check session
		checkSession('get');

		// Item details
		$item = Database::Get('', '', '', 'shop_inventory AS si', array_merge(Database::$inventory, Database::$items), 'WHERE si.id = {int:id} AND si.trading = 0 AND s.status = 1', true, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid)', ['id' => $itemid]);

		// Lil hack
		$item['can_use_item'] = 1;

		// Validate info
		$this->use_validate($item);

		// Update it's FAValue
		Database::Update('shop_inventory', ['id' => $itemid, 'fav' => $fav], 'fav = {int:fav},', 'WHERE id = {int:id}');

		// Out!
		redirectexit('action=shop;sa=inventory');
	}

	public function owners()
	{
		global $smcFunc, $context, $scripturl, $txt, $modSettings, $sourcedir;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// We got an item?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_error(Shop::getText('item_notfound'), false);

		// Our item ID
		$itemid = $_REQUEST['id'];

		// Is that a real item?
		$item = Database::Get('', '', '', 'shop_items AS s', Database::$items, 'WHERE s.itemid = {int:id} AND s.status = 1', true, '', ['id' => $itemid]);

		if (empty($item))
			fatal_error(Shop::getText('item_notfound'), false);

		// Set all the page stuff
		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'who_list';
		$context['page_title'] = Shop::getText('main_button') . ' - ' . sprintf(Shop::getText('buy_item_who'), $item['name']);
		$context['template_layers'][] = 'options';
		$context['linktree'][] = [
			'url' => $scripturl . '?action=shop;sa=owners;id='.$item['itemid'],
			'name' => sprintf(Shop::getText('buy_item_who'), $item['name']),
		];
		$context['item'] = $item;

		// The entire list
		$listOptions = [
			'id' => 'who_list',
			'items_per_page' => !empty($modSettings['Shop_items_perpage']) ? $modSettings['Shop_items_perpage'] : 15,
			'base_href' => $scripturl.'?action=shop;sa=whohas;id='.$itemid,
			'default_sort_col' => 'item_count',
			'default_sort_dir' => 'DESC',
			'get_items' => [
				'function' => 'Shop\Helper\Database::Get',
				'params' => ['shop_inventory AS si', ['si.itemid', 'si.userid', 'si.itemid', 'COUNT(*) AS count', 'm.real_name AS user'], 'WHERE s.status = 1 AND si.itemid = {int:itemid} GROUP BY si.userid, si.itemid, user', false, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid) LEFT JOIN {db_prefix}members AS m ON (m.id_member = si.userid)', ['itemid' => $itemid]],
			],
			'get_count' => [
				'function' => 'Shop\Helper\Database::Count',
				'params' => ['shop_inventory AS si', ['si.itemid', 'si.userid', 's.status'], 'WHERE si.itemid = {int:id} AND s.status = 1', 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid)', ['id' => $itemid]],
			],
			'no_items_label' => Shop::getText('inventory_no_owners'),
			'no_items_align' => 'center',
			'columns' => [
				'item_owner' => [
					'header' => [
						'value' => Shop::getText('item_member'),
						'class' => 'lefttext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl . '?action=profile;u=%1$d">%2$s</a>',
							'params' => [
								'userid' => false,
								'user' => true
							],
						],
						'class' => 'lefttext',
						'style' => 'width: 50%',
					],
					'sort' =>  [
						'default' => 'user DESC',
						'reverse' => 'user',
					],
				],
				'item_count' => [
					'header' => [
						'value' => Shop::getText('user_count'),
						'class' => 'centertext',
					],
					'data' => [
						'db' => 'count',
						'class' => 'centertext',
						'style' => 'width: 50%',
					],
					'sort' => [
						'default' => 'count DESC',
						'reverse' => 'count',
					],
				],
			],
		];

		// Let's finishem
		createList($listOptions);
	}

	public function display($memID)
	{
		global $modSettings, $context;

		// Allowed to see inventories?
		if (!allowedTo('shop_viewInventory'))
			return false;

		// Load the inventory
		self::$inventory_items = Database::Get(0, empty($modSettings['Shop_inventory_items_num']) ? 5 : $modSettings['Shop_inventory_items_num'], 'favo DESC,' . (!empty($modSettings['Shop_inventory_show_same_once']) ? 'MAX(si.date)' : 'si.date'). ' DESC', 'shop_inventory AS si', array_merge([(!empty($modSettings['Shop_inventory_show_same_once']) ? 'SUM(si.fav)' : 'si.fav'). ' AS favo'], Database::$profile_inventory), 'WHERE si.trading = 0 AND si.userid = {int:mem} AND s.status = 1' . (!empty($modSettings['Shop_inventory_show_same_once']) ? ' GROUP BY si.itemid, si.userid, si.trading, s.name, s.status, s.image, s.description' : ''), false, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid)', ['mem' => $memID]);

		return self::$inventory_items;
	}

	public function display_extend()
	{
		global $context, $scripturl, $modSettings;

		// Allowed to see inventories?
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// We need the user id
		if (empty($_REQUEST['id']) || !isset($_REQUEST['id']))
			fatal_error(Shop::getText('user_unable_tofind'), false);

		// I want to display the name of the user, so I'll use it to throw another error maybe
		$memData = Database::Get('', '', '', 'members', ['real_name', 'id_member'], 'WHERE id_member = {int:mem}', true, '', ['mem' => $_REQUEST['id']]);

		// We need the user id
		if (empty($memData))
			fatal_error(Shop::getText('user_unable_tofind'), false);

		// Help language
		loadLanguage('Help');

		// Template
		loadTemplate('Shop/Inventory');

		// Details
		$context['page_title'] = Shop::getText('main_button') . ' - ' . sprintf(Shop::getText('inventory_viewing_who'), $memData['real_name']);
		$context['from_ajax'] = true;
		$context['sub_template'] = 'shop_inventory_extended';
		$context['linktree'][] = [
			'url' => $scripturl . '?action=shop;sa=inventory;u='.$memData['id_member'],
			'name' => sprintf(Shop::getText('inventory_viewing_who'), $memData['real_name']),
		];

		// Load the inventory
		$context['inventory_list'] = Database::Get(0, 100000, 'favo DESC,' . (!empty($modSettings['Shop_inventory_show_same_once']) ? 'MAX(si.date)' : 'si.date'). ' DESC', 'shop_inventory AS si', array_merge([(!empty($modSettings['Shop_inventory_show_same_once']) ? 'SUM(si.fav)' : 'si.fav'). ' AS favo'], Database::$profile_inventory), 'WHERE si.trading = 0 AND si.userid = {int:mem} AND s.status = 1' . (!empty($modSettings['Shop_inventory_show_same_once']) ? ' GROUP BY si.itemid, si.userid, si.trading, s.name, s.status, s.image, s.description' : ''), false, 'LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = si.itemid)', ['mem' => $memData['id_member']]);
	}



	public function Trade()
	{
		global $smcFunc, $user_info, $context, $scripturl, $modSettings, $txt;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// The trade center is actually enabled?
		if (empty($modSettings['Shop_enable_trade']))
			fatal_error($txt['Shop_currently_disabled_trade'], false);

		// Is the user is allowed to trade items?
		if (!allowedTo('shop_canTrade') && !allowedTo('shop_canManage'))
			isAllowedTo('shop_canTrade');

		// Do we have an item? No? Bad luck...
		if (empty($_REQUEST['id']))
			fatal_error($txt['Shop_item_notfound'], false);

		// Item id
		$tradeid = (int) $_REQUEST['id'];

		$result = $smcFunc['db_query']('', '
			SELECT p.id, p.itemid, p.trading, p.userid, s.name
			FROM {db_prefix}shop_inventory AS p
				LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = p.itemid)
			WHERE id = {int:tradeid}',
			array(
				'tradeid' => $tradeid,
			)
		);

		$item = $smcFunc['db_fetch_assoc']($result);
		$smcFunc['db_free_result']($result);

		// Load up the linktree!
		$context['page_title'] = $txt['Shop_main_button'] . ' - ' . $txt['Shop_item_trade_go'];
		$context['template_layers'][] = 'Shop_main';
		$context['template_layers'][] = 'Shop_invTabs';
		$context['sub_template'] = 'Shop_invTradeSet';
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=shop;sa=invtrade;id=' . $tradeid,
			'name' => sprintf($txt['Shop_item_trade_go'], $item['name'])
		);
		// Sub-menu tabs
		$context['inventory_tabs'] = $this->tabs;

		// No item found
		if (empty($item))
			fatal_error($txt['Shop_item_notfound'], false);
		// That item isn't yours, thanks for trying
		elseif ($item['userid'] != $user_info['id'])
			fatal_error($txt['Shop_item_notown'], false);
		// You cannot trade the same item twice
		elseif ($item['trading'] == 1)
			fatal_error($txt['Shop_item_alreadytraded'], false);
	}

	public static function Trade2()
	{
		global $smcFunc, $user_info, $context, $scripturl, $modSettings, $txt;

		// Check if he is allowed to access this section
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_viewInventory');

		// The trade center is actually enabled?
		if (empty($modSettings['Shop_enable_trade']))
			fatal_error($txt['Shop_currently_disabled_trade'], false);

		// Is the user is allowed to trade items?
		if (!allowedTo('shop_canManage'))
			isAllowedTo('shop_canTrade');

		// Do we have an item? No? Bad luck...
		if (empty($_REQUEST['id']))
			fatal_error($txt['Shop_item_notfound'], false);

		// Item info
		$tradeid = (int) $_REQUEST['id'];
		$tradecost = (int) $_REQUEST['tradecost'];

		// Make sure we have a price
		if (empty($_REQUEST['tradecost']))
			fatal_error($txt['Shop_item_notprice'], false);
		// No tricks with the price...
		elseif ($tradecost <= 0)
			fatal_error($txt['Shop_item_price_notnegative'], false);

		// Check session
		checkSession();

		$result = $smcFunc['db_query']('', '
			SELECT p.id, p.itemid, p.trading, p.userid, s.name
			FROM {db_prefix}shop_inventory AS p
				LEFT JOIN {db_prefix}shop_items AS s ON (s.itemid = p.itemid)
			WHERE id = {int:tradeid}',
			array(
				'tradeid' => $tradeid,
			)
		);

		$item = $smcFunc['db_fetch_assoc']($result);
		$smcFunc['db_free_result']($result);

		// Load up the linktree!
		$context['page_title'] = $txt['Shop_main_button'] . ' - ' . $txt['Shop_item_trade_go'];
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=shop;sa=invtrade;id=' . $tradeid,
			'name' => sprintf($txt['Shop_item_trade_go'], $item['name'])
		);

		// No item found
		if (empty($item))
			fatal_error($txt['Shop_item_notfound'], false);
		// That item isn't yours, thanks for trying
		elseif ($item['userid'] != $user_info['id'])
			fatal_error($txt['Shop_item_notown'], false);
		// You cannot trade the same item twice
		elseif ($item['trading'] == 1)
			fatal_error($txt['Shop_item_alreadytraded'], false);

		// Put the item into the trade center
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}shop_inventory
			SET	trading = 1,
				tradecost = {int:tradecost}
			WHERE id = {int:id}',
			array(
				'id' => $item['id'],
				'tradecost' => $tradecost,
			)
		);

		// Tell the user that the item was added successfully
		redirectexit('action=shop;sa=inventory;traded');
	}
}