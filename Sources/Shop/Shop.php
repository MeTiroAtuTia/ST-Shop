<?php

/**
 * @package ST Shop
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace Shop;

if (!defined('SMF'))
	die('No direct access...');

class Shop
{
	public static $name = 'Shop';
	public static $version = '4.0';
	public static $itemsdir = '/shop_items/items/';
	public static $modulesdir = '/Shop/Modules/';
	public static $gamesdir = '/shop_items/games';
	public static $supportSite = 'https://smftricks.com/index.php?action=.xml;sa=news;board=51;limit=10;type=rss2';

	public static function initialize()
	{
		self::setDefaults();
		self::defineHooks();
		self::userHooks();
	}

	/**
	 * Shop::setDefaults()
	 *
	 * Sets almost every setting to a default value
	 * @return void
	 */
	public static function setDefaults()
	{
		global $modSettings;

		$defaults = array(
			'Shop_enable_shop' => 0,
			'Shop_stats_refresh' => 900,
			'Shop_credits_register' => 200,
			'Shop_credits_topic' => 25,
			'Shop_credits_post' => 10,
			//'Shop_credits_likes_post' => 0,
			'Shop_credits_word' => 0,
			'Shop_credits_character' => 0,
			'Shop_credits_limit' => 0,
			'Shop_bank_interest' => 2,
			'Shop_bank_interest_yesterday' => 0,
			'Shop_bank_withdrawal_fee' => 0,
			'Shop_bank_deposit_fee' => 0,
			'Shop_bank_withdrawal_max' => 0,
			'Shop_bank_withdrawal_min' => 0,
			'Shop_bank_deposit_max' => 0,
			'Shop_bank_deposit_min' => 0,
			'Shop_credits_prefix' => '',
			'Shop_credits_suffix' => 'Credits',
			'Shop_images_width' => '32px',
			'Shop_images_height' => '32px',
			'Shop_items_perpage' => 15,
			'Shop_items_trade_fee' => 0,
			'Shop_display_pocket' => 0,
			'Shop_display_pocket_placement' => 0,
			'Shop_display_bank' => 0,
			'Shop_display_bank_placement' => 0,
			'Shop_inventory_enable' => 0,
			'Shop_inventory_show_same_once' => 0,
			'Shop_inventory_items_num' => 5,
			'Shop_inventory_placement' => 0,
			'Shop_inventory_allow_hide' => 0,
			'Shop_settings_slots_losing' => 500,
			'Shop_settings_lucky2_losing' => 500,
			'Shop_settings_numberslots_losing' => 500,
			'Shop_settings_pairs_losing' => 500,
			'Shop_settings_dice_losing' => 500,
			'Shop_settings_slots_7' => 2000,
			'Shop_settings_slots_bell' => 150,
			'Shop_settings_slots_cherry' => 65,
			'Shop_settings_slots_lemon' => 20,
			'Shop_settings_slots_orange' => 75,
			'Shop_settings_slots_plum' => 50,
			'Shop_settings_slots_dollar' => 100,
			'Shop_settings_slots_melon' => 700,
			'Shop_settings_slots_grapes' => 400,
			'Shop_settings_lucky2_price' => 1000,
			'Shop_settings_number_losing' => 100,
			'Shop_settings_number_complete' => 700,
			'Shop_settings_number_firsttwo' => 450,
			'Shop_settings_number_secondtwo' => 250,
			'Shop_settings_number_firstlast' => 100,
			'Shop_settings_pairs_clubs_1' => 2000,
			'Shop_settings_pairs_clubs_2' => 2000,
			'Shop_settings_pairs_clubs_3' => 2000,
			'Shop_settings_pairs_clubs_4' => 2000,
			'Shop_settings_pairs_clubs_5' => 2000,
			'Shop_settings_pairs_clubs_6' => 2000,
			'Shop_settings_pairs_clubs_7' => 2000,
			'Shop_settings_pairs_clubs_8' => 2000,
			'Shop_settings_pairs_clubs_9' => 2000,
			'Shop_settings_pairs_clubs_10' => 2000,
			'Shop_settings_pairs_clubs_11' => 2000,
			'Shop_settings_pairs_clubs_12' => 2000,
			'Shop_settings_pairs_clubs_13' => 2000,
			'Shop_settings_pairs_diam_1' => 150,
			'Shop_settings_pairs_diam_2' => 150,
			'Shop_settings_pairs_diam_3' => 150,
			'Shop_settings_pairs_diam_4' => 150,
			'Shop_settings_pairs_diam_5' => 150,
			'Shop_settings_pairs_diam_6' => 150,
			'Shop_settings_pairs_diam_7' => 150,
			'Shop_settings_pairs_diam_8' => 150,
			'Shop_settings_pairs_diam_9' => 150,
			'Shop_settings_pairs_diam_10' => 150,
			'Shop_settings_pairs_diam_11' => 150,
			'Shop_settings_pairs_diam_12' => 150,
			'Shop_settings_pairs_diam_13' => 150,
			'Shop_settings_pairs_hearts_1' => 50,
			'Shop_settings_pairs_hearts_2' => 50,
			'Shop_settings_pairs_hearts_3' => 50,
			'Shop_settings_pairs_hearts_4' => 50,
			'Shop_settings_pairs_hearts_5' => 50,
			'Shop_settings_pairs_hearts_6' => 50,
			'Shop_settings_pairs_hearts_7' => 50,
			'Shop_settings_pairs_hearts_8' => 50,
			'Shop_settings_pairs_hearts_9' => 50,
			'Shop_settings_pairs_hearts_10' => 50,
			'Shop_settings_pairs_hearts_11' => 50,
			'Shop_settings_pairs_hearts_12' => 50,
			'Shop_settings_pairs_hearts_13' => 50,
			'Shop_settings_pairs_spades_1' => 200,
			'Shop_settings_pairs_spades_2' => 200,
			'Shop_settings_pairs_spades_3' => 200,
			'Shop_settings_pairs_spades_4' => 200,
			'Shop_settings_pairs_spades_5' => 200,
			'Shop_settings_pairs_spades_6' => 200,
			'Shop_settings_pairs_spades_7' => 200,
			'Shop_settings_pairs_spades_8' => 200,
			'Shop_settings_pairs_spades_9' => 200,
			'Shop_settings_pairs_spades_10' => 200,
			'Shop_settings_pairs_spades_11' => 200,
			'Shop_settings_pairs_spades_12' => 200,
			'Shop_settings_pairs_spades_13' => 200,
			'Shop_settings_dice_1' => 150,
			'Shop_settings_dice_2' => 550,
			'Shop_settings_dice_3' => 750,
			'Shop_settings_dice_4' => 900,
			'Shop_settings_dice_5' => 1500,
			'Shop_settings_dice_6' => 2000,
			'Shop_noty_trade' => 0,
			'Shop_noty_credits' => 0,
			'Shop_noty_items' => 0,
		);
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * Shop::defineHooks()
	 *
	 * Load hooks quietly
	 * @return void
	 */
	public static function defineHooks()
	{
		$hooks = array(
			'autoload' => 'autoload',
			'menu_buttons' => 'hookButtons',
			'actions' => 'hookActions',
			//'issue_like' => 'Shop::likePost',
			//'show_alert' => 'Shop::showAlerts',
			//'fetch_alerts' => 'Shop::fetchAlerts',
		);
		foreach ($hooks as $point => $callable)
			add_integration_function('integrate_' . $point, __CLASS__ . '::'.$callable, false);
	}

	/**
	 * Shop::autoload()
	 *
	 * @param array $classMap
	 * @return void
	 */
	public static function autoload(&$classMap)
	{
		$classMap['Shop\\'] = 'Shop/';
	}

	/**
	 * Shop::hookActions()
	 *
	 * Insert the actions needed by this mod
	 * @param array $actions An array containing all possible SMF actions. This includes loading different hooks for certain areas.
	 * @return void
	 */
	public static function hookActions(&$actions)
	{
		// The main action
		$actions['shop'] = ['Shop/View/Home.php', __NAMESPACE__  . '\View\Home::main#'];

		// Feed
		$actions['shopfeed'] = array(false, __CLASS__ . '::getFeed');
		
		// Add some hooks by action
		switch ($_REQUEST['action'])
		{
			case 'helpadmin':
				loadLanguage(__NAMESPACE__ . '/ShopAdmin');
				break;
			case 'admin':
				add_integration_function('integrate_admin_areas', __NAMESPACE__ . '\Manage\Dashboard::hookAreas', false);
				break;
			case 'post':
			case 'post2':
				add_integration_function('integrate_after_create_post', __NAMESPACE__ . '\Integration\Posting::after_create_post', false);
				break;
			case 'who':
				add_integration_function('who_allowed', __NAMESPACE__ . '\Integration\Who::who_allowed', false);
				add_integration_function('integrate_whos_online', __NAMESPACE__ . '\Integration\Who::whos_online', false);
				break;
			case 'profile':
				add_integration_function('integrate_pre_profile_areas', __NAMESPACE__ . '\Integration\Profile::hookAreas', false);
				break;
			case 'signup':
			case 'signup2':
				add_integration_function('integrate_register', __NAMESPACE__ . '\Integration\Signup::register', false);
				break;
		}
	}

	/**
	 * Shop::userHooks()
	 *
	 * Load member and custom fields hooks
	 * @return void
	 */
	public static function userHooks()
	{
		global $sourcedir;

		// Load our Profile file
		$hooks = array(
			'load_member_data' => 'load_member_data',
			'user_info' => 'user_info',
			'simple_actions' => 'simple_actions',
			'member_context' => 'member_context',
		);
		foreach ($hooks as $point => $callable)
			add_integration_function('integrate_' . $point, __NAMESPACE__ . '\Integration\User::' . $callable, false);
	}

	/**
	 * Shop::hookButtons()
	 *
	 * Insert a Shop button on the menu buttons array
	 * @param array $buttons An array containing all possible tabs for the main menu.
	 * @return void
	 */
	public static function hookButtons(&$buttons)
	{
		global $context, $scripturl, $modSettings, $settings;

		// Too lazy for adding the menu on all the sub-templates
		if (!empty($modSettings['Shop_enable_shop']))
		{
			self::ShopLayer();

			// Languages
			loadLanguage(__NAMESPACE__ . '/Shop');
		}

		$before = 'mlist';
		$temp_buttons = array();
		foreach ($buttons as $k => $v) {
			if ($k == $before) {
				$temp_buttons['shop'] = array(
					'title' => self::getText('main_button'),
					'href' => $scripturl . '?action=shop',
					'icon' => 'icons/shop.png',
					'show' => (allowedTo('shop_canAccess') || allowedTo('shop_canManage')) && !empty($modSettings['Shop_enable_shop']),
					'sub_buttons' => array(
						'shopadmin' => array(
							'title' => self::getText('admin_button'),
							'href' => $scripturl . '?action=admin;area=shopinfo',
							'show' => allowedTo('shop_canManage'),
						),
					),
				);
			}
			$temp_buttons[$k] = $v;
		}
		$buttons = $temp_buttons;
	}

	/**
	 * Shop::ShopLayer()
	 *
	 * Used for adding the shop tabs quickly
	 * @return void
	 */
	public static function ShopLayer()
	{
		global $context;

		if (isset($context['current_action']) && $context['current_action'] === 'shop' && (allowedTo('shop_canAccess') || allowedTo('shop_canManage'))) {
			$position = array_search('body', $context['template_layers']);
			if ($position === false)
				$position = array_search('main', $context['template_layers']);

			if ($position !== false) {
				$before = array_slice($context['template_layers'], 0, $position + 1);
				$after = array_slice($context['template_layers'], $position + 1);
				$context['template_layers'] = array_merge($before, array('shop'), $after);
			}
		}
	}

	

	

	/**
	 * Shop::likePost()
	 *
	 * Gives or removes points to author of the post for each like.
	 * @param int $message id of the message
	 * @return void
	 */
	/*public static function likePost($like_type, $like_content, $like_userid, $alreadyLiked, $validlikes)
	{
		global $smcFunc, $modSettings;

		//Are we giving credits per like?
		if (!empty($modSettings['Shop_credits_likes_post']))
		{
			// We are only interested in messages for now
			if ($like_type == 'msg')
			{
				$msglikes = $smcFunc['db_query']('', '
					SELECT id_member
					FROM {db_prefix}messages
					WHERE id_msg = {int:like}',
					array(
						'like' => $like_content,
					)
				);
				$likedAuthor = $smcFunc['db_fetch_assoc']($msglikes);
				$smcFunc['db_free_result']($msglikes);

				// Like removed, points too!
				if ($alreadyLiked)
				{
					$result = $smcFunc['db_query']('','
						UPDATE {db_prefix}members
						SET shopMoney = shopMoney - {int:likepost}
						WHERE id_member = {int:id_member}',
						array(
							'likepost' => $modSettings['Shop_credits_likes_post'],
							'id_member' => $likedAuthor['id_member'],
						)
					);
				}
				// Post liked, points delivered!
				else
				{
					$result = $smcFunc['db_query']('','
						UPDATE {db_prefix}members
						SET shopMoney = shopMoney + {int:likepost}
						WHERE id_member = {int:id_member}',
						array(
							'likepost' => $modSettings['Shop_credits_likes_post'],
							'id_member' => $likedAuthor['id_member'],
						)
					);
				}
			}
		}
	}*/

	public static function deployAlert($id_member, $type, $content_id, $link, $extra_items = array(), $ask = true)
	{
		global $smcFunc, $sourcedir, $user_info, $scripturl;

		// Should we ask the user?
		if (!empty($ask))
		{
			// Check user preferences
			require_once($sourcedir . '/Subs-Notify.php');
			$prefs = getNotifyPrefs($id_member, 'shop_user'.$type, true);

			// Check the value. If no value or it's empty, they didn't want alerts, oh well.
			if (empty($prefs[$id_member]['shop_user'.$type]))
				return true;
		}

		$author = false;
		// We need to figure out who the owner of this is.
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.pm_ignore_list
			FROM {db_prefix}members AS mem
			WHERE mem.id_member = {int:user}',
			array(
				'user' => $id_member,
			)
		);
		if ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$author = $row['id_member'];
		}
		$smcFunc['db_free_result']($request);

		// If we didn't have a member... leave.
		if (empty($author))
			return true;

		// If the person who sent the notification is the person whose content it is, do nothing.
		if ($author == $user_info['id'])
			return true;

		// Don't spam the alerts: if there is an existing unread alert of the
		// requested type for the target user from the sender, don't make a new one.
		$request = $smcFunc['db_query']('', '
			SELECT id_alert
			FROM {db_prefix}user_alerts
			WHERE id_member = {int:id_member}
				AND is_read = 0
				AND content_type = {string:content_type}
				AND content_id = {int:content_id}
				AND content_action = {string:content_action}',
			array(
				'id_member' => $author,
				'content_type' => 'shop',
				'content_id' => $content_id,
				'content_action' => $type,
			)
		);

		if ($smcFunc['db_num_rows']($request) > 0)
			return true;
		$smcFunc['db_free_result']($request);

		// Alert link
		$extra_items['shop_link'] = $scripturl.$link;

		// Issue, update, move on.
		$smcFunc['db_insert']('insert',
			'{db_prefix}user_alerts',
			array(
				'alert_time' => 'int',
				'id_member' => 'int',
				'id_member_started' => 'int',
				'member_name' => 'string',
				'content_type' => 'string',
				'content_id' => 'int',
				'content_action' => 'string',
				'is_read' => 'int',
				'extra' => 'string'
			),
			array(
				time(),
				$author,
				$user_info['id'],
				$user_info['name'],
				'shop',
				$content_id,
				$type,
				0,
				$smcFunc['json_encode']($extra_items)
			),
			array('id_alert')
		);

		updateMemberData($author, array('alerts' => '+'));
	}

	public static function showAlerts(&$alert, &$link)
	{		
		if (isset($alert['extra']['shop_link']))
			$link = $alert['extra']['shop_link'];
	}

	public static function fetchAlerts(&$alerts, &$formats)
	{
		foreach ($alerts as $alert_id => $alert)
			if ($alert['content_type'] == 'shop' && isset($alert['extra']['item_icon']))
				$alerts[$alert_id]['icon'] = '<img class="alert_icon" src="'.$alert['extra']['item_icon'].'">';
	}

	/**
	 * Shop::scheduled_shopBank()
	 *
	 * Creates a scheduled task for making money in the bank of every user
	 * @return void
	 */
	public static function scheduled_shopBank()
	{
		global $smcFunc, $modSettings;

		// Create some cash out of nowhere. How? By magical means, of course!
		if (!empty($modSettings['Shop_enable_shop']) && !empty($modSettings['Shop_enable_bank']) && $modSettings['Shop_bank_interest'] > 0)
		{
			// Thanks to Zerk for the idea
			$yesterday = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));

			$smcFunc['db_query']('', '
				UPDATE {db_prefix}members
				SET shopBank = shopBank + (shopBank * {float:interest})' . (!empty($modSettings['Shop_bank_interest_yesterday']) ?
				'WHERE last_login > {int:yesterday}' : ''),
				array(
					'interest' => ($modSettings['Shop_bank_interest'] / 100),
					'yesterday' => $yesterday,
				)
			);
		}
	}




	
	/**
	 * Shop::getText()
	 *
	 * Gets a string key, and returns the associated text string.
	 */
	public function getText($text, $pattern = true)
	{
		global $txt;

		return !empty($pattern) ? (!empty($txt[__NAMESPACE__ . '_' . $text]) ? $txt[__NAMESPACE__ . '_' . $text] : '') : (!empty($txt[$text]) ? $txt[$text] : '');
	}

	/**
	 * Shop::getFeed()
	 *
	 * Proxy function to avoid Cross-origin errors.
	 * @return string
	 * @author Jessica González <suki@missallsunday.com>
	 */
	public static function getFeed()
	{
		global $sourcedir;

		require_once($sourcedir . '/Class-CurlFetchWeb.php');
		$fetch = new \curl_fetch_web_data();
		$fetch->get_url_data(self::$supportSite);
		if ($fetch->result('code') == 200 && !$fetch->result('error'))
			$data = $fetch->result('body');
		else
			exit;
		smf_serverResponse($data, 'Content-type: text/xml');
	}
}