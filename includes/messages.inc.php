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

if (!defined('InWeBid')) exit();

// Language management
if (isset($_GET['lan']) && !empty($_GET['lan']))
{
	if ($user->logged_in)
	{
		$query = "UPDATE " . $DBPrefix . "users SET language = '" . $_GET['lan'] . "' WHERE id = " . $user->user_data['id'];
	}
	else
	{
		// Set language cookie
		setcookie('USERLANGUAGE', $_GET['lan'], time() + 31536000, '/');
	}
	$language = $_GET['lan'];
}
elseif ($user->logged_in)
{
	$language = $user->user_data['language'];
}
elseif (isset($_COOKIE['USERLANGUAGE']))
{
	$language = $_COOKIE['USERLANGUAGE'];
}
else
{
	$language = $system->SETTINGS['defaultlanguage'];
}

if (!isset($language) || empty($language)) $language = $system->SETTINGS['defaultlanguage'];

require($main_path . 'language/' . $language . '/messages.inc.php');

//find installed languages
$LANGUAGES = array();
if ($handle = opendir($main_path . 'language'))
{
	while (false !== ($file = readdir($handle)))
	{ 
		if (ereg("^([A-Z]{2})$", $file, $regs))
		{
			$LANGUAGES[$regs[1]] = $regs[1];
		}
	}
}
closedir($handle);
?>