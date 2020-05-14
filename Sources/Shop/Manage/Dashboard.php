<?php

/**
 * @package ST Shop
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace Shop\Manage;

use Shop\Shop;

if (!defined('SMF'))
	die('No direct access...');

class Dashboard
{
	 /**
	 * Dashboard::hookAreas()
	 *
	 * Adding the admin section
	 * @param array $admin_areas An array with all the admin areas
	 * @return
	 */
	public function hookAreas(&$admin_areas)
	{
		global $modSettings;

		loadLanguage('Shop/ShopAdmin');

		$admin_areas['shop'] = [
			'title' => Shop::getText('admin_button'),
			'permission' => ['shop_canManage'],
			'areas' => [
				'shopinfo' => [
					'label' => Shop::getText('tab_info'),
					'icon' => 'administration',
					'function' => __NAMESPACE__ . '\Dashboard::main',
				],
				'shopsettings' => [
					'label' => Shop::getText('tab_settings'),
					'icon' => 'features',
					'function' => __NAMESPACE__ . '\Settings::main',
					'permission' => ['admin_forum'],
					'subsections' => [
						'general' => [Shop::getText('settings_general')],
						'credits' => [Shop::getText('settings_credits')],
						'permissions' => [
							Shop::getText('settings_permissions'),
							'enabled' => !empty($modSettings['Shop_enable_shop']),
						],
						'profile' => [Shop::getText('settings_profile')],
						'notifications' => [Shop::getText('settings_notifications')],
					],
				],
				'shopitems' => [
					'label' => Shop::getText('tab_items'),
					'icon' => 'smiley',
					'function' => __NAMESPACE__ . '\Items::main',
					'permission' => ['shop_canManage'],
					'enabled' => !empty($modSettings['Shop_enable_shop']),
					'subsections' => [
						'index' => [Shop::getText('tab_items')],
						'add' => [Shop::getText('items_add')],
						'upload' => [Shop::getText('items_upload')],
					],
				],
				'shopmodules' => [
					'label' => Shop::getText('tab_modules'),
					'icon' => 'modifications',
					'function' => __NAMESPACE__ . '\Modules::main',
					'permission' => ['admin_forum'],
					'enabled' => !empty($modSettings['Shop_enable_shop']),
					'subsections' => [
						'index' => [Shop::getText('tab_modules')],
						'upload' => [Shop::getText('modules_upload')],
					],
				],
				'shopcategories' => [
					'label' => Shop::getText('tab_cats'),
					'icon' => 'boards',
					'function' => __NAMESPACE__ . '\Categories::main',
					'permission' => ['shop_canManage'],
					'enabled' => !empty($modSettings['Shop_enable_shop']),
					'subsections' => [
						'index' => [Shop::getText('tab_cats')],
						'add' => [Shop::getText('cats_add')],
					],
				],
				'shopgames' => [
					'label' => Shop::getText('tab_games'),
					'icon' => 'paid',
					'function' => __NAMESPACE__ . '\Games::main',
					'permission' => ['shop_canManage'],
					'enabled' => !empty($modSettings['Shop_enable_shop']) && !empty($modSettings['Shop_enable_games']),
					'subsections' => [
						'slots' => [Shop::getText('games_slots')],
						'lucky2' => [Shop::getText('games_lucky2')],
						'number' => [Shop::getText('games_number')],
						'pairs' => [Shop::getText('games_pairs')],
						'dice' => [Shop::getText('games_dice')],
					],
				],
				'shopinventory' => [
					'label' => Shop::getText('tab_inventory'),
					'icon' => 'maintain',
					'function' =>  __NAMESPACE__ . '\Inventory::main',
					'permission' => ['shop_canManage'],
					'subsections' => [
						'groupcredits' => [Shop::getText('inventory_groupcredits')],
						'usercredits' => [Shop::getText('inventory_usercredits')],
						'useritems' => [
							Shop::getText('inventory_useritems'),
							'enabled' => !empty($modSettings['Shop_enable_shop']),
						],
						'restock' => [
							Shop::getText('inventory_restock'),
							'enabled' => !empty($modSettings['Shop_enable_shop']),
						],
						'search' => [
							Shop::getText('tab_inventory'),
							'enabled' => !empty($modSettings['Shop_enable_shop']),
						],
					],
				],
				'shoplogs' => [
					'label' => Shop::getText('tab_logs'),
					'icon' => 'logs',
					'file' => 'Shop/AdminShop-Logs.php',
					'function' => 'AdminShopLogs::Main',
					'permission' => ['shop_canManage'],
					'subsections' => [
						'admin_money' => [
							Shop::getText('logs_admin_money')
						],
						'admin_items' => [
							Shop::getText('logs_admin_items'),
							'enabled' => !empty($modSettings['Shop_enable_shop']),
						],
						'buy' => [
							Shop::getText('logs_buy'),
							'enabled' => !empty($modSettings['Shop_enable_shop'])
						],
						'money' => [
							Shop::getText('logs_money'),
							'enabled' => !empty($modSettings['Shop_enable_gift']) && !empty($modSettings['Shop_enable_shop']),
						],
						'items' => [
							Shop::getText('logs_items'),
							'enabled' => !empty($modSettings['Shop_enable_gift']) && !empty($modSettings['Shop_enable_shop']),
						],
						'trade' => [
							Shop::getText('logs_trade'),
							'enabled' => !empty($modSettings['Shop_enable_trade']) && !empty($modSettings['Shop_enable_shop']),
						],
						'bank' => [
							Shop::getText('logs_bank'),
							'enabled' => !empty($modSettings['Shop_enable_bank']) && !empty($modSettings['Shop_enable_shop']),
						],
						'games' => [
							Shop::getText('logs_games'),
							'enabled' => !empty($modSettings['Shop_enable_games']) && !empty($modSettings['Shop_enable_shop']),
						],
					],
				],
			],
		];
		// Add more items?
		call_integration_hook('integrate_shop_admin_areas', array(&$admin_areas['shop']['areas']));

		// Permissions
		add_integration_function('integrate_load_permissions', 'Shop\Integration\Permissions::load_permissions', false);
		add_integration_function('integrate_load_illegal_guest_permissions', 'Shop\Integration\Permissions::illegal_guest', false);
		// Boards settings
		add_integration_function('integrate_pre_boardtree', 'Shop\Integration\Boards::pre_boardtree', false);
		add_integration_function('integrate_boardtree_board', 'Shop\Integration\Boards::boardtree_board', false);
		add_integration_function('integrate_edit_board', 'Shop\Integration\Boards::edit_board', false);
		add_integration_function('integrate_create_board', 'Shop\Integration\Boards::create_board', false);
		add_integration_function('integrate_modify_board', 'Shop\Integration\Boards::modify_board', false);
	}

	public function main()
	{
		global $scripturl, $context, $user_info;

		// Load Template
		loadTemplate('Shop/ShopAdmin');

		// Set all the page stuff
		$context['page_title'] = Shop::getText('admin_button') . ' - '. Shop::getText('tab_info');
		$context['sub_template'] = 'dashboard';
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => sprintf(Shop::getText('tab_info_desc'), $user_info['name']),
		);

		$context['Shop']['version'] = Shop::$version;
		$context['Shop']['support'] = Shop::$supportSite;
		$context['Shop']['credits'] = self::credits();

		// Feed news
		addInlineJavascript('
			$(function(){
				var shoplive = $("#smfAnnouncements");
				$.ajax({
					type: "GET",
					url: '. JavaScriptEscape($scripturl . '?action=shopfeed') .',
					cache: false,
					dataType: "xml",
					success: function(xml){
						var dl = $("<dl />");
						$(xml).find("item").each(function () {
							var item = $(this),
							title = $("<a />", {
								text: item.find("title").text(),
								href: item.find("link").attr("href")
							}),
							parsedTime = item.find("pubDate").text(),
							updated = $("<span />").text( parsedTime),
							content = $("<div/>").html(item.find("description")).text(),
							dt = $("<dt />").html(title),
							dd = $("<dd />").html(content);
							updated.appendTo(dt);
							dt.appendTo(dl);
							dd.appendTo(dl);
						});
						shoplive.html(dl);
					},
					error: function (html){}
				});
			});
		', true);
	}

	/**
	 * Dashboard::credits()
	 *
	 * Includes a list of contributors, developers and third party scripts that helped build this MOD
	 * @return array The list of credits
	 */
	public static function credits()
	{
		$credits = [
			'dev' => [
				'name' => Shop::getText('dash_devs'),
				'users' => [
					'diego' => [
						'name' => 'Diego Andrés',
						'site' => 'https://smftricks.com',
					],
				],
			],
			'icons' => [
				'name' => Shop::getText('dash_icons'),
				'users' => [
					'fugue' => [
						'name' => 'Fugue Icons',
						'site' => 'https://p.yusukekamiyamane.com/',
					],
				],
			],
			'thanksto' => [
				'name' => Shop::getText('dash_thanks'),
				'users' => [
					'daniel15' => [
						'name' => 'Daniel15',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=9547',
						'desc' => 'Original Shop Mod',
					],
					'sa' => [
						'name' => 'Sleepy Arcade',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=84438',
						'desc' => 'Original SA Shop Developer',
					],
					'vbgamer45' => [
						'name' => 'vbgamer45',
						'site' => 'https://www.smfhacks.com/',
						'desc' => 'SMF Shop Developer',
					],
					'suki' => [
						'name' => 'Suki',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=245528',
						'desc' => 'Consultant',
					],
				],
			],
			'contributors' => [
				'name' => Shop::getText('dash_contributors'),
				'users' => [
					'hcfwesker' => [
						'name' => 'hcfwesker',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=244295',
						'desc' => 'Ideas, suggestions and support for SA/ST Shop.',
					],
					'ospina' => [
						'name' => 'Cristian Ospina',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=215234',
						'desc' => 'Feedback and ideas for Shop Modules.',
					],
					'gerard' => [
						'name' => 'Zerk',
						'site' => 'https://www.simplemachines.org/community/index.php?action=profile;u=130323',
						'desc' => 'Suggestions, code and original ideas for new features',
					],
				],
			],
		];

		return $credits;
	}



	public static function logInventory($userid, $receivers, $ids, $message, $amount = 0, $itemid = 0)
	{
		global $smcFunc;

		// He sent an item
		if ($amount == 0) {
			// Transfer the item to the new user
			foreach ($receivers as $memID)
			{
				// Insert the information in the log
				$smcFunc['db_insert']('',
					'{db_prefix}shop_inventory',
					array(
						'userid' => 'int',
						'itemid' => 'int',
						'date' => 'int',
					),
					array(
						$memID,
						$itemid,
						time()
					),
					array()
				);
				// And finally, the stock gets one less item
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}shop_items
					SET	count = count - {int:count}
					WHERE itemid = {int:itemid}',
					array(
						'count' => 1,
						'itemid' => $itemid,
					)
				);
			}
		}
		// He sent money
		else {
			// Add the amount to users
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}members
				SET shopMoney = shopMoney + {int:amount}
				WHERE ' .$ids,
				array(
					'member_ids' => $receivers,
					'amount' => $amount,
				)
			);
		}

		foreach ($receivers as $memID)
		{
			// Insert the information in the log
			$smcFunc['db_insert']('',
				'{db_prefix}shop_log_gift',
				array(
					'userid' => 'int',
					'receiver' => 'int',
					'amount' => 'int',
					'itemid' => 'int',
					'invid' => 'int',
					'message' => 'string',
					'is_admin' => 'int',
					'date' => 'int',
				),
				array(
					$userid,
					$memID,
					$amount,
					$itemid,
					0,
					$message,
					1,
					time()
				),
				array()
			);
		}
	}
}