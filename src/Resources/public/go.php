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

	/**
	 * Zählt den Aufruf eines getrackten Links und leitet anschließend weiter.
	 *
	 * Die Link-ID kommt als Parameter 'id', 'option=image' liefert statt einer
	 * Weiterleitung ein 1x1-Pixel zurück (für Zählungen in E-Mails). Aufrufe
	 * von Bots werden nur protokolliert und nicht gezählt.
	 *
	 * @return void Die Methode endet entweder mit einer Weiterleitung, der
	 *              Ausgabe des Zählpixels oder einer ErrorException, wenn die
	 *              Link-ID nicht existiert oder nicht veröffentlicht ist
	 */
	public function run()
	{
		$id = intval(\Input::get('id'));
		$option = \Input::get('option');

		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linktracker WHERE published = ? AND id = ?')
		                                   ->execute(1, $id);

		// numRows statt einer Prüfung auf das Objekt selbst: execute() liefert
		// immer ein Result-Objekt, auch wenn kein Datensatz gefunden wurde. Die
		// frühere Prüfung "!$objLink" konnte deshalb nie zutreffen, und der Code
		// lief mit einer leeren URL in die Weiterleitung.
		if($objLink->numRows < 1)
		{
			\System::log('[Linktracker] Link ID '.$id.' not exist', __CLASS__.'::'.__FUNCTION__, TL_ERROR);
			header('HTTP/1.1 501 Not Implemented');
			throw new \ErrorException('Link ID not found',2,1,basename(__FILE__),__LINE__);
		}

		// Der User-Agent fehlt bei manchen Aufrufern ganz, deshalb der Standardwert.
		$strAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		// Klick ggfs. zählen und weiterleiten
		if(self::is_bot())
		{
			// Besucher ist ein Bot
			\System::log('[Linktracker] Link ID '.$id.' not tracked (Bot): '.$strAgent, __CLASS__.'::'.__FUNCTION__, TL_ERROR);
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
				'ip'         => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				// Auf die Spaltenbreite kürzen. User-Agents sind teilweise
				// über 300 Zeichen lang; ungekürzt bricht MySQL den INSERT mit
				// "Data too long for column 'browser'" ab, und die daraus
				// entstehende Exception beendet den Aufruf mit Status 500.
				'browser'    => mb_substr($strAgent, 0, 255, 'UTF-8'),
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
			\Controller::redirect(\Controller::replaceInsertTags($objLink->url));
		}
	}

	/**
	 * Prüft anhand des User-Agents, ob der Aufrufer ein Bot ist.
	 *
	 * Die Erkennung erfolgt über eine feste Liste bekannter Kennungen. Sie ist
	 * bewusst großzügig ('bot', 'crawl', 'spider'), weil eine falsch gezählte
	 * Bot-Anfrage die Statistik stärker verfälscht als ein nicht gezählter
	 * Besucher.
	 *
	 * @return bool true, wenn der User-Agent auf einen Bot hindeutet;
	 *              false auch dann, wenn gar kein User-Agent gesendet wurde —
	 *              solche Aufrufe werden also gezählt
	 */
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
