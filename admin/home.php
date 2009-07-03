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

if (isset($_GET['action']))
{
	switch($_GET['action'])
	{
		case 'clearcache':
			if (is_dir($main_path.'cache'))
			{
				$dir = opendir($main_path.'cache');
				while (($myfile = readdir($dir)) !== false)
				{
					if ($myfile != '.' && $myfile != '..' && $myfile != 'index.php')
					{
						unlink($main_path.'cache/'.$myfile);
					}
				}
				closedir($dir);
			}
			$errmsg = $MSG['30_0033'];
		break;
		
		case 'updatecounters':
			//update users counter
			$query = "SELECT COUNT(id) FROM " . $DBPrefix . "users WHERE suspended = 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$USERS = mysql_result($res, 0);
			$query = "UPDATE " . $DBPrefix . "counters SET users = " . $USERS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			//update suspended users counter
			$query = "SELECT COUNT(id) FROM " . $DBPrefix . "users WHERE suspended != 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$USERS = mysql_result($res, 0);
			$query = "UPDATE " . $DBPrefix . "counters SET inactiveusers = " . $USERS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			//update auction counter
			$query = "SELECT COUNT(id) FROM " . $DBPrefix . "auctions WHERE closed = 0 AND suspended = 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$AUCTIONS = mysql_result($res, 0);
			$query = "UPDATE " . $DBPrefix . "counters SET auctions = " . $AUCTIONS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			//update closed auction counter
			$query = "SELECT COUNT(id) FROM " . $DBPrefix . "auctions WHERE closed != 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$AUCTIONS = mysql_result($res, 0);
			$query = "UPDATE " . $DBPrefix . "counters SET closedauctions = " . $AUCTIONS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			//update suspended auctions counter
			$query = "SELECT COUNT(id) FROM " . $DBPrefix . "auctions WHERE closed = 0 and suspended != 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$AUCTIONS = mysql_result($res, 0);
			$query = "UPDATE " . $DBPrefix . "counters SET suspendedauctions = " . $AUCTIONS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			//update bids
			$query = "SELECT COUNT(b.id) FROM " . $DBPrefix . "bids b, " . $DBPrefix . "auctions a
					WHERE b.auction=a.id AND a.closed = 0 AND a.suspended = 0";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$BIDS = mysql_num_rows($res);
			$query = "UPDATE " . $DBPrefix . "counters set bids = " . $BIDS;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			
			$errmsg = $MSG['1029'];
		break;
	}
}

foreach ($LANGUAGES as $k => $v){
	$template->assign_block_vars('langs', array(
			'LANG' => $v,
			'B_DEFAULT' => ($k == $system->SETTINGS['defaultlanguage'])
			));
}

$query = "SELECT * FROM " . $DBPrefix . "counters";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$COUNTERS = mysql_fetch_array($res);

$query = "SELECT * FROM " . $DBPrefix . "currentaccesses WHERE year = " . gmdate('Y') . " AND month = " . gmdate('n') . " AND day = " . gmdate('j');
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$ACCESS = mysql_fetch_array($res);
$ACCESS['pageviews'] = (!isset($ACCESS['pageviews']) || empty($ACCESS['pageviews'])) ? 0 : $ACCESS['pageviews'];
$ACCESS['uniquevisitors'] = (!isset($ACCESS['uniquevisitors']) || empty($ACCESS['uniquevisitors'])) ? 0 : $ACCESS['uniquevisitors'];
$ACCESS['usersessions'] = (!isset($ACCESS['usersessions']) || empty($ACCESS['usersessions'])) ? 0 : $ACCESS['usersessions'];

$template->assign_vars(array(
		'ERROR' => (isset($errmsg)) ? $errmsg : '',
		'SITEURL' => $system->SETTINGS['siteurl'],
		'SITENAME' => stripslashes($system->SETTINGS['sitename']),
		'ADMINMAIL' => $system->SETTINGS['adminmail'],
		'CRON' => ($system->SETTINGS['cron'] == 1) ? '<b>' . $MSG['373'] . '</b><br>' . $MSG['25_0027'] : '<b>' . $MSG['374'] . '</b>',
		'GALLERY' => ($system->SETTINGS['picturesgallery'] == 1) ? '<b>' . $MGS_2__0066 . '</b><br>' . $MSG['666'] . ': ' . $system->SETTINGS['maxpictures'] . '<br>' . $MSG['671'] . ': ' . $system->SETTINGS['maxpicturesize'] : '<b>' . $MGS_2__0067 . '</b>',
		'BUY_NOW' => ($system->SETTINGS['buy_now'] == 1) ? '<b>' . $MGS_2__0067 . '</b>' : '<b>' . $MGS_2__0066 . '</b>',
		'CURRENCY' => $system->SETTINGS['currency'],
		'TIMEZONE' => ($system->SETTINGS['timecorrection'] == 0) ? $MSG['25_0036'] : $system->SETTINGS['timecorrection'] . $MSG['25_0037'],
		'DATEFORMAT' => $system->SETTINGS['datesformat'],
		'DATEEXAMPLE' => ($system->SETTINGS['datesformat'] == 'USA') ? $MSG['382'] : $MSG['383'],
		'DEFULTCONTRY' => $system->SETTINGS['defaultcountry'],
		
		'C_USERS' => $COUNTERS['users'],
		'C_IUSERS' => $COUNTERS['inactiveusers'],
		'C_AUCTIONS' => $COUNTERS['auctions'],
		'C_CLOSED' => $COUNTERS['closedauctions'],
		'C_BIDS' => $COUNTERS['bids'],
		
		'A_PAGEVIEWS' => $ACCESS['pageviews'],
		'A_UVISITS' => $ACCESS['uniquevisitors'],
		'A_USESSIONS' => $ACCESS['usersessions']
		));

$template->set_filenames(array(
		'body' => 'adminhome.tpl'
		));
$template->display('body');
?>