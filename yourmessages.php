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

// If user is not logged in redirect to login page
if (!$user->logged_in)
{
	header('location: user_login.php');
	exit;
}

$messageid = intval($_GET['id']);
// check message is to user
$query = "SELECT m.*, u.nick FROM " . $DBPrefix . "messages m
		LEFT JOIN " . $DBPrefix . "users u ON (u.id = m.from)
		WHERE m.sentto = " . $user->user_data['id'] . " AND m.id = " . $messageid;
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$check = mysql_num_rows($res);

if ($check == 0)
{
	$_SESSION['message'] = $ERR_070;
	header('location: mail.php');
}

$array = mysql_fetch_array($res);
$sent = gmdate('M d, Y H:ia', $array['when'] + $system->tdiff);
$from = $array['from'];
$sendusername = $array['nick'];
$subject = $array['subject'];
$replied = $array['replied'];
$message = $array['message'];
$hash = md5(rand(1, 9999));
$array['message'] = str_replace('<br>', '', $array['message']);
$_SESSION['msg' . $hash] = "\n\n-+-+-+-+-+-+-+-+-+\n\n" . $array['message'];

$senderusername = '<a href="profile.php?user_id=1&auction_id=' . $from . '">' . $sendusername . '</a>';

// if admin message
if ($from == 0)
{
	$senderusername = $MSG['110'];
}

// Update message
$query = "UPDATE " . $DBPrefix . "messages SET `read` = 1 WHERE id = " . $messageid;
$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

// set session for reply
$_SESSION['subject' . $hash] = (substr($subject, 0, 3) == 'Re:') ? $subject : 'Re: ' . $subject;
$_SESSION['sendto' . $hash] = $sendusername;
$_SESSION['reply' . $hash] = $messageid;

$template->assign_vars(array(
		'SUBJECT' => $subject,
		'SENDERNAME' => $senderusername,
		'SENT' => $sent,
		'MESSAGE' => $message,
		'ID' => $messageid,
		'HASH' => $hash
		));

include 'header.php';
include 'includes/user_cp.php';
$template->set_filenames(array(
		'body' => 'yourmessages.tpl'
		));
$template->display('body');
include 'footer.php';
?>