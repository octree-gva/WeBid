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
include '../includes/common.inc.php';
include $include_path . 'functions_admin.php';
include 'loggedin.inc.php';

unset($ERR);

if (isset($_POST['action']) && $_POST['action'] == 'update')
{
	// Data check
	if (empty($_POST['sitename']) || empty($_POST['siteurl']) || empty($_POST['adminmail']))
	{
		$ERR = $ERR_047;
	}
	else
	{
		// Update data
		$query = "UPDATE " . $DBPrefix . "settings set
				sitename = '" . addslashes($_POST['sitename']) . "',
				adminmail = '" . addslashes($_POST['adminmail']) . "',
				siteurl = '" . addslashes($_POST['siteurl']) . "',
				copyright = '" . addslashes($_POST['copyright']) . "'";
		$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
		$ERR = $MSG['542'];
	}

	$system->SETTINGS['sitename'] = $_POST['sitename'];
	$system->SETTINGS['adminmail'] = $_POST['adminmail'];
	$system->SETTINGS['siteurl'] = $_POST['siteurl'];
	$system->SETTINGS['copyright'] = $_POST['copyright'];
}

loadblock($MSG['527'], $MSG['535'], 'text', 'sitename', $system->SETTINGS['sitename']);
loadblock($MSG['528'], $MSG['536'], 'text', 'siteurl', $system->SETTINGS['siteurl']);
loadblock($MSG['540'], $MSG['541'], 'text', 'adminmail', $system->SETTINGS['adminmail']);
loadblock($MSG['191'], $MSG['192'], 'text', 'copyright', $system->SETTINGS['copyright']);

$template->assign_vars(array(
		'ERROR' => (isset($ERR)) ? $ERR : '',
		'SITEURL' => $system->SETTINGS['siteurl'],
		'TYPE' => 'set',
		'TYPENAME' => $MSG['5142'],
		'PAGENAME' => $MSG['526']
		));

$template->set_filenames(array(
		'body' => 'adminpages.tpl'
		));
$template->display('body');
?>