<?php
/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaolinktracker/go.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Class LinkClick
 *
 */
class LinkClick
{

	public function __construct()
	{
	}

	public function run()
	{
		$id = intval(\Input::get('id'));
		$option = \Input::get('option');

		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linktracker WHERE published = ? AND id = ?')
		                                   ->execute(1, $id);
		if(!$objLink)
		{
			\System::log('[Linktracker] Link ID '.$id.' not exist', __CLASS__.'::'.__FUNCTION__, TL_ERROR);
			header('HTTP/1.1 501 Not Implemented');
			throw new \ErrorException('Link ID not found',2,1,basename(__FILE__),__LINE__);
		}

		// Klick ggfs. zählen und weiterleiten
		if(self::is_bot())
		{
			// Besucher ist ein Bot
			\System::log('[Linktracker] Link ID '.$id.' not tracked (Bot): '.$_SERVER['HTTP_USER_AGENT'], __CLASS__.'::'.__FUNCTION__, TL_ERROR);
		}
		else
		{
			// kein Bot, Aufruf zählen
			$tstamp = time();
			$set = array
			(
				'pid'        => $id,
				'tstamp'     => $tstamp,
				'clickTime'  => $tstamp,
				'ip'         => $_SERVER['REMOTE_ADDR'],
				'browser'    => $_SERVER['HTTP_USER_AGENT'],
				'published'  => 1,
			);
			\Database::getInstance()->prepare('INSERT INTO tl_linktracker_items %s')
			                        ->set($set)
			                        ->executeUncached($id);
		}

		if($option == 'image')
		{
			// Bild zurückliefern
			\System::log('[Linktracker] Create image ID '.$id, __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
			header('Content-type: image/gif');
			readfile(TL_ROOT.'/vendor/schachbulle/contao-linktracker-bundle/src/Resources/public/image.gif');
		}
		else
		{
			// Link weiterleiten
			\System::log('[Linktracker] Forwarding Link ID '.$id.': '.$objLink->url, __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
			\Controller::redirect($objLink->url);
		}
	}

	function is_bot()
	{
	
		if(isset($_SERVER['HTTP_USER_AGENT']))
		{
			return preg_match('/rambler|abacho|acoi|accona|aspseek|altavista|estyle|scrubby|lycos|geona|ia_archiver|alexa|sogou|skype|facebook|twitter|pinterest|linkedin|naver|bing|google|yahoo|duckduckgo|yandex|baidu|teoma|xing|java\/1.7.0_45|bot|crawl|slurp|spider|mediapartners|\sask\s|\saol\s/i', $_SERVER['HTTP_USER_AGENT']);
		}
		
		return false;
	}
}

/**
 * Instantiate controller
 */
$objClick = new LinkClick();
$objClick->run();
