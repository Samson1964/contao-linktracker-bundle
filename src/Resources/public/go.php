<?php
//ini_set('display_errors', '1');
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

		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linktracker WHERE published = ? AND id = ?')
		                                   ->execute(1, $id);
		if(!$objLink)
		{
			\System::log('[Linktracker] Forwarding link ID '.$id.' not exist', __CLASS__.'::'.__FUNCTION__, TL_ERROR);
			header('HTTP/1.1 501 Not Implemented');
			throw new \ErrorException('Link ID not found',2,1,basename(__FILE__),__LINE__);
		}

		// Klick zählen und weiterleiten
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

		\System::log('[Linktracker] Forwarding link ID '.$id.': '.$objLink->url, __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
		\Controller::redirect($objLink->url);

	}
}

/**
 * Instantiate controller
 */
$objClick = new LinkClick();
$objClick->run();

