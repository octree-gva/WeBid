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

include($include_path . 'nusoap.php');
include($include_path . 'currencies.php');

function CurrenciesList()
{
	if (!isset($_SESSION['curlist']))
	{
		$s = new soapclientt('http://webservices.lb.lt/ExchangeRates/ExchangeRates.asmx/getListOfCurrencies');
		$result= $s->call('getListOfCurrencies',array(),'','http://webservices.lb.lt/ExchangeRates/getListOfCurrencies');
		$parser = xml_parser_create();
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
		xml_parse_into_struct($parser,$s->responseData,$values,$tags);
		xml_parser_free($parser);
		$CURRENCIES = array();
		foreach ($values as $k => $v)
		{
			if ($v['tag'] == 'currency') $cur = $v['value'];
			if ($v['tag'] == 'description' && $v['attributes']['lang'] == 'en')
			{
				$CURRENCIES[$cur] = $v['value'];
			}
		}
		$_SESSION['curlist'] = $CURRENCIES;
	}
	else
	{
		$CURRENCIES = $_SESSION['curlist'];
	}
	return $CURRENCIES;
}

function ConvertCurrency($FROM, $INTO, $AMOUNT)
{
	global $include_path, $conversionarray;
	
	$params1 = array(
		'FromCurrency' 	=> $FROM,
		'ToCurrency' 	=> $INTO
		);
	if ($FROM == $INTO) return $AMOUNT;
	
	$rate = findconversionrate($FROM, $INTO);
	if ($rate == 0)
	{
		$sclient = new soapclientt($include_path . 'CurrencyConverter.wdsl', 'wsdl');
		$p = $sclient->getProxy();
		$ratio = $p->ConversionRate($params1);
		if (is_array($ratio))
		{
			$ratio = googleconvert($AMOUNT, $FROM, $INTO);
			if ($ratio == false)
			{
				return false;
			}
		}
		$VAL = floatval($AMOUNT);
		$conversionarray[1][] = array('from' => $FROM, 'to' => $INTO, 'rate' => $ratio);
		buildcache($conversionarray[1]);
		return $VAL * $ratio;
	}
	else
	{
		$VAL = floatval($AMOUNT);
		return $VAL * $rate;
	}
}

function buildcache($newaarray)
{
	global $include_path;
	
	$output_filename = $include_path . 'currencies.php';
	$output = "<?\n";
	$output.= "\$conversionarray[] = '" . time() . "';\n";
	$output.= "\$conversionarray[] = array(\n";
	
	for ($i = 0; $i < count($newaarray); $i++){
		$output .= "\t" . "array('from' => '" . $newaarray[$i]['from'] . "', 'to' => '" . $newaarray[$i]['to'] . "', 'rate' => '" . $newaarray[$i]['rate'] . "')";
		if ($i < (count($newaarray) - 1))
		{
			$output .= ",\n";
		}
		else
		{
			$output .= "\n";
		}
	}
	
	$output .= ");\n?>\n";
	
	$handle = fopen($output_filename, 'w');
	fputs($handle, $output);
	fclose($handle);
}

function findconversionrate($FROM, $INTO)
{
	global $conversionarray;
	
	if (time() - (3600 * 24) < $conversionarray[0])
	{
		for ($i = 0; $i < count($conversionarray[1]); $i++)
		{
			if ($conversionarray[1][$i]['from'] == $FROM && $conversionarray[1][$i]['to'] == $INTO)
				return $conversionarray[1][$i]['rate'];
		}
	}
	else
	{
		$conversionarray = array(0, array());
	}
	return 0;
}

//if everything else fails try this
function googleconvert($amount, $fromCurrency , $toCurrency)
{
	$url = 'http://www.google.com/finance/converter?a=%s&from=%s&to=%s';
	$finalurl = sprintf($url, $amount, $fromCurrency, $toCurrency);
	
	// Renders the google page result
	$htmlrender = file_get_contents($finalurl);	   
	if (!empty($htmlrender))
	{
		preg_match('([0-9.]*)&nbsp;([a-zA-Z\ ]*)&nbsp;=&nbsp;<span class=bld>([0-9.]*)&nbsp;([a-zA-Z\ ]*)<\/span>', $htmlrender, $matches);
		return (!empty($matches[4][0])) ? ($matches[3][0] / $matches[1][0]) : false;
	}
	return false;
}
?>