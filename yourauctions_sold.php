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

include 'includes/common.inc.php';
include $include_path . 'auctionstoshow.inc.php';

// If user is not logged in redirect to login page
if (!$user->logged_in)
{
	header('location: user_login.php');
	exit;
}

$NOW = time();
$NOWB = gmdate('Ymd');

// Update
if (isset($_POST['action']) && $_POST['action'] == 'update')
{
	// Delete auction
	if (is_array($_POST['delete']))
	{
		foreach ($_POST['delete'] as $k => $v)
		{
			$v = intval($v);
			// Pictures Gallery
			if (file_exists($upload_path . $v))
			{
				if ($dir = @opendir($upload_path . $v))
				{
					while ($file = readdir($dir))
					{
						if ($file != '.' && $file != '..')
						{
							unlink($upload_path . $v . '/' . $file);
						}
					}
					closedir($dir);
					@rmdir($upload_path . $v);
				}
			}
			$query = "UPDATE " . $DBPrefix . "counters SET closedauctions = closedauctions - 1";
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			$query = "DELETE FROM " . $DBPrefix . "auccounter WHERE auction_id = " . $v;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			$query = "DELETE FROM " . $DBPrefix . "auctions WHERE id = " . $v;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			// Bids
			$query = "DELETE FROM " . $DBPrefix . "bids WHERE auction = " . $v;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			// Proxy Bids
			$query = "DELETE FROM " . $DBPrefix . "proxybid WHERE itemid = " . $v;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
		}
	}
}

// Retrieve closed auction data from the database
$query = "SELECT a.*  FROM " . $DBPrefix . "auctions a, " . $DBPrefix . "winners w
		WHERE a.user = " . $user->user_data['id'] . " AND a.closed = 1 AND a.suspended = 0 AND a.id = w.auction GROUP BY w.auction";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$TOTALAUCTIONS = mysql_num_rows($res);

if (!isset($_GET['PAGE']) || $_GET['PAGE'] < 0 || empty($_GET['PAGE']))
{
	$OFFSET = 0;
	$PAGE = 1;
}
else
{
	$OFFSET = ($_GET['PAGE'] - 1) * $LIMIT;
	$PAGE = $_GET['PAGE'];
}

$PAGES = ceil($TOTALAUCTIONS / $LIMIT);
if (!$PAGES) $PAGES = 1;

// Handle columns sorting variables
if (!isset($_SESSION['solda_ord']) && empty($_GET['solda_ord']))
{
	$_SESSION['solda_ord'] = 'title';
	$_SESSION['solda_type'] = 'asc';
}
elseif (!empty($_GET['solda_ord']))
{
	$_SESSION['solda_ord'] = mysql_escape_string($_GET['solda_ord']);
	$_SESSION['solda_type'] = mysql_escape_string($_GET['solda_type']);
}
elseif (isset($_SESSION['solda_ord']) && empty($_GET['solda_ord']))
{
	$_SESSION['solda_nexttype'] = $_SESSION['solda_type'];
}

if (!isset($_SESSION['solda_nexttype']) || $_SESSION['solda_nexttype'] == 'desc')
{
	$_SESSION['solda_nexttype'] = 'asc';
}
else
{
	$_SESSION['solda_nexttype'] = 'desc';
}

if (!isset($_SESSION['solda_type']) || $_SESSION['solda_type'] == 'desc')
{
	$_SESSION['solda_type_img'] = '<img src="images/arrow_up.gif" align="center" hspace="2" border="0" alt="up"/>';
}
else
{
	$_SESSION['solda_type_img'] = '<img src="images/arrow_down.gif" align="center" hspace="2" border="0" alt="down"/>';
}

$query = "SELECT a.* FROM " . $DBPrefix . "auctions a, " . $DBPrefix . "winners w
		WHERE a.user = " . $user->user_data['id'] . "
		AND a.closed = 1
		AND a.suspended = 0
		AND a.id = w.auction
		GROUP BY w.auction
		ORDER BY " . $_SESSION['solda_ord'] . " " . $_SESSION['solda_type'] . " LIMIT $OFFSET,$LIMIT";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($item = mysql_fetch_assoc($res))
{
	$relist = false;
	if ((($reserve - $current) < 0) && ($item['reserve_price'] > 0 || $item['num_bids'] == 0)) $relist = true;
	$template->assign_block_vars('items', array(
			'BGCOLOUR' => ($i % 2) ? '#FFCCFF' : '#EEEEEE',
			'ID' => $item['id'],
			'TITLE' => $item['title'],
			'STARTS' => FormatDate($item['starts']),
			'ENDS' => FormatDate($item['ends']),
			'BID' => ($item['current_bid'] == 0) ? '-' : $system->print_money($item['current_bid']),
			'BIDS' => $item['num_bids'],

			'B_RELIST' => $relist,
			'B_CLOSED' => ($item['closed'] == 1),
			'B_HASNOBIDS' => ($item['current_bid'] == 0)
			));
	$i++;
}

// get pagenation
$PREV = intval($PAGE - 1);
$NEXT = intval($PAGE + 1);
if ($PAGES > 1)
{
	$LOW = $PAGE - 5;
	if ($LOW <= 0) $LOW = 1;
	$COUNTER = $LOW;
	while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
	{
		$template->assign_block_vars('pages', array(
				'PAGE' => ($PAGE == $COUNTER) ? '<b>' . $COUNTER . '</b>' : '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_sold.php?PAGE=' . $COUNTER . '&id=' . $id . '"><u>' . $COUNTER . '</u></a>'
				));
		$COUNTER++;
	}
}

$template->assign_vars(array(
		'TBLHEADERCOLOUR' => $system->SETTINGS['tableheadercolor'],
		'ORDERCOL' => $_SESSION['solda_ord'],
		'ORDERNEXT' => $_SESSION['solda_nexttype'],
		'ORDERTYPEIMG' => $_SESSION['solda_type_img'],

		'PREV' => ($PAGES > 1 && $PAGE > 1) ? '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_sold.php?PAGE=' . $PREV . '&id=' . $id . '"><u>' . $MSG['5119'] . '</u></a>&nbsp;&nbsp;' : '',
		'NEXT' => ($PAGE < $PAGES) ? '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_sold.php?PAGE=' . $NEXT . '&id=' . $id . '"><u>' . $MSG['5120'] . '</u></a>' : '',
		'PAGE' => $PAGE,
		'PAGES' => $PAGES
		));

include 'header.php';
$TMP_usmenutitle = $MSG['25_0119'];
include 'includes/user_cp.php';
$template->set_filenames(array(
		'body' => 'yourauctions_sold.tpl'
		));
$template->display('body');
include 'footer.php';
?>