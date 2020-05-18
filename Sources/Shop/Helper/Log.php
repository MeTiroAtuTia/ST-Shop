<?php

/**
 * @package ST Shop
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace Shop\Helper;

use Shop\Shop;

if (!defined('SMF'))
	die('No direct access...');

class Log
{
	private static $gifts = [
		'userid' => 'int',
		'receiver' => 'int',
		'amount' => 'int',
		'itemid' => 'int',
		'invid' => 'int',
		'message' => 'string',
		'is_admin' => 'int',
		'date' => 'int',
	];
	private static $buy = [
		'itemid' => 'int',
		'invid' => 'int',
		'userid' => 'int',
		'sellerid' => 'int',
		'amount' => 'int',
		'fee' => 'int',
		'date' => 'int',
	];
	private static $inventory = [
		'userid' => 'int',
		'itemid' => 'int',
		'trading' => 'int',
		'tradecost' => 'int',
		'date' => 'int',
		'tradedate' => 'int',
		'fav' => 'int',
	];
	private static $types = [];

	public function credits($sender, $users, $amount, $admin = false, $message = '')
	{
		// Send credits over to the user
		Database::Update('members', ['users' => $users, 'credits' => $amount], 'shopMoney = shopMoney + {int:credits},', 'WHERE id_member' . (is_array($users) ? ' IN ({array_int:users})' : ' = {int:users}'));

		// Log the information
		if (is_array($users))
			foreach ($users as $memID)
				Database::Insert('shop_log_gift', [
					$sender,
					$memID,
					$amount,
					0,
					0,
					$message,
					!empty($admin) ? 1 : 0,
					time()
				], self::$gifts);
		// Single user
		else
			Database::Insert('shop_log_gift', [
				$sender,
				$users,
				$amount,
				0,
				0,
				$message,
				!empty($admin) ? 1 : 0,
				time()
			], self::$gifts);

		// Regular user? Deduct these credits
		if (empty($admin))
			Database::Update('members', ['user' => $sender, 'credits' => $amount], 'shopMoney = shopMoney - {int:credits}', 'WHERE id_member = {int:user}');
	}

	public function items($sender, $users, $item, $invid = 0, $admin = false, $message = '')
	{
		// Log the information
		if (is_array($users))
			foreach ($users as $memID)
				Database::Insert('shop_log_gift', [
					$sender,
					$memID,
					0,
					$item,
					$invid,
					$message,
					!empty($admin) ? 1 : 0,
					time()
				], self::$gifts);
		// Single user
		else
			Database::Insert('shop_log_gift', [
				$sender,
				$users,
				0,
				$item,
				$invid,
				$message,
				!empty($admin) ? 1 : 0,
				time()
			], self::$gifts);

		// Regular user? Just switch the item from one inventory to another
		if (empty($admin))
			Database::Update('shop_inventory', ['user' => $users, 'invid' => $invid], 'userid = {int:user}', 'WHERE id = {int:invid}');
		// Admin? Insert a new item on each inventory, and reduce stock?
		else
		{
			foreach ($users as $memID)
			{
				Database::Insert('shop_inventory', [
					$memID,
					$item,
					0,
					0,
					time(),
					0,
					0
				], self::$inventory);
				Database::Update('shop_items', ['stock' => 1, 'itemid' => $item], 'stock = {int:stock}', 'WHERE itemid = {int:itemid}');
			}
		}
	}

	public function purchase($itemid, $userid, $amount, $seller = 0, $fee = 0, $invid = 0)
	{
		// Remove the credits from the buyer
		Database::Update('members', ['user' => $userid, 'credits' => $amount], 'shopMoney = shopMoney - {int:credits},', 'WHERE id_member = {int:user}');

		// Is he purchasing an item fro items list?
		if (empty($seller))
		{
			// Insert in inventory
			Database::Insert('shop_inventory', [
				$userid,
				$itemid,
				0,
				0,
				time(),
				0,
				0
			], self::$inventory);

			// Discount stock
			Database::Update('shop_items', ['count' => 1, 'itemid' => $itemid], 'stock = stock - {int:count},', 'WHERE itemid = {int:itemid}');
		}
		// Purchasing at the trade center
		else
		{
			// Move item to buyer inventory
			Database::Update('shop_inventory', ['user' => $userid, 'invid' => $invid, 'date' => time()], 'userid = {int:user}, trading = 0, tradecost = 0, tradedate = 0, date = {int:date},', 'WHERE id = {int:invid}');

			// Add the credits to the seller
			Database::Update('members', ['user' => $seller, 'paid' => $amount, 'fee' => $fee], 'shopMoney = shopMoney + ({int:paid} - {int:fee}),', 'WHERE id_member = {int:user}');
		}

		// Insert info in the log
		Database::Insert('shop_log_buy', [
			$itemid,
			$invid,
			$userid,
			$seller,
			$amount,
			$fee,
			time()
		], self::$buy);
	}
}