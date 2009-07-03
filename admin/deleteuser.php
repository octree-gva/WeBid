<?php

/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

define('InAdmin', 1);
include "../includes/common.inc.php";
include $include_path . 'functions_admin.php';
include 'loggedin.inc.php';
include $include_path."countries.inc.php";

unset($ERR);
$id = intval($_REQUEST['id']);

// Data check
if (empty($id) || $id <= 0)
{
	header('location: listusers.php?PAGE=' . intval($_GET['offset']));
	exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'update')
{
	$ERR = '';
	$has_auctions = false;
	$has_bids = false;
	$catscontrol = new MPTTcategories();

	// Check if the users has some auction
	$query = "SELECT id, title FROM " . $DBPrefix . "auctions WHERE user = " . $id;
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$num_auctions = mysql_num_rows($res);

	if ($num_auctions > 0)
	{
		$ERR = $MSG['420'];
		while ($row = mysql_fetch_assoc($res))
		{
			$has_auctions = true;
			$ERR .= $row['id'] . ' - <a href="' . $system->SETTINGS['siteurl'] . 'item.php?id=' . $row['id'] . '" target="_blank">' . $row['title'] . '</a><br>';
		}
	}

	// Check if the user is BIDDER in some auction
	$query = "SELECT * FROM " . $DBPrefix . "bids WHERE bidder = " . $id;
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$num_bids = mysql_num_rows($res);

	if ($num_bids > 0)
	{
		$has_bids = true;
		$ERR .= sprintf($MSG['421'], $num_auctions);
	}

	if (!isset($_POST['ignore']) && ($has_bids || $has_auctions))
	{
		$template->assign_vars(array(
				'MESSAGE' => $ERR . '<p>' . $MSG['419'] . '</p>',
				'STRING' => $MSG['030'],
				'ID' => $id,
				'OFFSET' => $_POST['offset']
				));
		
		$template->set_filenames(array(
				'body' => 'excludeuser.tpl'
				));
		$template->display('body');
		exit;
	}

	// check if user is suspended or not
	$query = "SELECT suspended FROM " . $DBPrefix . "users WHERE id = " . $id;
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$myrow = mysql_fetch_assoc($res);
	$suspended = $myrow['suspended'];

	// delete user
	$query = "DELETE FROM " . $DBPrefix . "users WHERE id = " . $id;
	$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

	if ($has_auctions)
	{
		// update categories table
		$query = "SELECT c.level, c.left_id, c.right_id FROM " . $DBPrefix . "auctions a
				LEFT JOIN " . $DBPrefix . "categories c ON (a.category = c.cat_id)
				WHERE a.user = " . $id;
		$res = mysql_query($query);
		$system->check_mysql($res, $query, __LINE__, __FILE__);
		while ($row = mysql_fetch_array($res))
		{
			$crumbs = $catscontrol->get_bread_crumbs($row['left_id'], $row['right_id']);
			for ($i = 0; $i < count($crumbs); $i++)
			{
				$query = "UPDATE " . $DBPrefix . "categories SET counter = counter - 1, sub_counter = sub_counter - 1 WHERE cat_id = " . $crumbs[$i]['cat_id'];
				$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			}
		}

		// delete user's auctions
		$query = "DELETE FROM " . $DBPrefix . "auctions WHERE user = " . $id;
		$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	}

	if ($has_bids)
	{
		// update auctions table
		$query = "SELECT a.id, a.current_bid, b.bid FROM " . $DBPrefix . "bids b
				LEFT JOIN " . $DBPrefix . "auctions a ON (b.auction = a.id)
				WHERE b.bidder = " . $id . " ORDER BY b.bid DESC";
		$res = mysql_query($query);
		$system->check_mysql($res, $query, __LINE__, __FILE__);
		while ($row = mysql_fetch_array($res))
		{
			// check if user is highest bidder
			if ($row['current_bid'] == $row['bid'])
			{
				$query = "SELECT bid FROM " . $DBPrefix . "bids WHERE auction = " . $row['id'] . " ORDER BY bid DESC LIMIT 1, 1";
				$res = mysql_query($query);
				$system->check_mysql($res, $query, __LINE__, __FILE__);
				$next_bid = mysql_fetch_assoc($res);
				// set new highest bid
				$extra = ", current_bid = '" . $next_bid['bid'] . "'";
			}
			$query = "UPDATE " . $DBPrefix . "auctions SET num_bids = num_bids - 1" . $extra . " WHERE id = " . $row['id'];
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
		}

		// delete bids
		$query = "DELETE FROM " . $DBPrefix . "bids WHERE bidder = " . $id;
		$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	}

	// Update user counters
	if ($suspended == 0)
	{
		$query = "UPDATE " . $DBPrefix . "counters set users = users - 1, bids = bids - " . $num_bids . ", auctions = auctions - " . $num_auctions;
	}
	else
	{
		$query = "UPDATE " . $DBPrefix . "counters set inactiveusers = inactiveusers - 1, bids = bids - " . $num_bids . ", auctions = auctions - " . $num_auctions;
	}
	$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

	header('location: listusers.php?PAGE=' . intval($_REQUEST['offset']));
	exit;
}

// load the page
$query = "SELECT * FROM " . $DBPrefix . "users WHERE id = " . $id;
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$user_data = mysql_fetch_assoc($res);

$birth_day = substr($user_data['birthdate'], 6, 2);
$birth_month = substr($user_data['birthdate'], 4, 2);
$birth_year = substr($user_data['birthdate'], 0, 4);

if ($system->SETTINGS['datesformat'] == 'USA')
{
	$birthdate = $birth_month . '/' . $birth_day . '/' . $birth_year;
}
else
{
	$birthdate = $birth_day . '/' . $birth_month . '/' . $birth_year;
}

$template->assign_vars(array(
		'ACTION' => $MSG['304'],
		'REALNAME' => $user_data['name'],
		'USERNAME' => $user_data['nick'],
		'EMAIL' => $user_data['email'],
		'ADDRESS' => $user_data['address'],
		'PROV' => $user_data['prov'],
		'ZIP' => $user_data['zip'],
		'COUNTRY' => $user_data['country'],
		'PHONE' => $user_data['phone'],
		'RATE' => ($user_data['rate_num'] == 0) ? 0 : ($user_data['rate_sum'] / $user_data['rate_num']),
		'DOB' => $birthdate,
		'QUESTION' => $MSG['307'],
		'MODE' => '',
		'ID' => $_GET['id'],
		'OFFSET' => $_GET['offset']
		));

$template->set_filenames(array(
		'body' => 'excludeuser.tpl'
		));
$template->display('body');
?>