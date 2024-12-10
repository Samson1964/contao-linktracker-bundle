<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   fh-counter
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

namespace Schachbulle\ContaoLinktrackerBundle\Tags;

class Linktracker extends \Frontend
{

	public function go($strTag)
	{
		$arrSplit = explode('::', $strTag);

		// Inserttag {{linktracker::ID::Option}}
		// Liefert zu einer ID des Linktracker-Moduls den entsprechenden HTML-Code
		// Parameter 1 (ID) = ID des Linktrackers
		// Parameter 2 (Option) = optional, Art der HTML-Ausgabe festlegen
		if($arrSplit[0] == 'linktracker' || $arrSplit[0] == 'cache_linktracker')
		{
			// Parameter 1 angegeben?
			if(isset($arrSplit[1]))
			{
				// Parameter 2 (image) angegeben?
				if(isset($arrSplit[2]) && $arrSplit[2] == 'image')
				{
					// Ein Bildlink wird zurückgegeben. Beim Abruf des Bildes wird der Zugriff gezählt.
					return '<img src="'.\Environment::get('url').'/bundles/contaolinktracker/go.php?id='.$arrSplit[1].'&option=image">';
				}
				else
				{
					// Kein optionaler Parameter. Es wird nur der Link zurückgegeben.
					return \Environment::get('url').'/bundles/contaolinktracker/go.php?id='.$arrSplit[1];
				}
			}
		}

		return false; // Tag nicht dabei
	}

}
