<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Nimmt den Aufruf eines getrackten Links entgegen, zählt ihn und leitet weiter.
 *
 * Diese Klasse ersetzt die frühere Datei src/Resources/public/go.php. Jene band
 * mit system/initialize.php den alten Contao-Einstiegspunkt ein, den es unter
 * Contao 5 nicht mehr gibt; ausserdem setzte sie die Konstanten TL_MODE und
 * TL_ROOT voraus, die dort ebenfalls entfallen sind. Als Symfony-Controller
 * läuft derselbe Ablauf unter Contao 4.13 wie unter Contao 5.
 */
class LinktrackerController
{
	/**
	 * Baut den Controller mit allen Diensten auf, die für das Zählen und
	 * Weiterleiten gebraucht werden.
	 *
	 * @param ContaoFramework $framework       Wird vor dem Ersetzen von Insert-Tags
	 *                                         hochgefahren, weil der Insert-Tag-Parser
	 *                                         auf Contao-Klassen zugreift
	 * @param Connection      $connection      Datenbankverbindung für das Lesen des
	 *                                         Links und das Schreiben des Klicks
	 * @param InsertTagParser $insertTagParser  Löst Insert-Tags in der Ziel-URL auf
	 * @param LoggerInterface $logger          Contao-Fehlerkanal; unbekannte Link-IDs
	 *                                         landen hier statt im alten System-Log
	 */
	public function __construct(
		private readonly ContaoFramework $framework,
		private readonly Connection $connection,
		private readonly InsertTagParser $insertTagParser,
		private readonly LoggerInterface $logger
	) {
	}

	/**
	 * Zählt den Aufruf eines Links und liefert je nach Aufruf eine
	 * Weiterleitung oder das Zählpixel zurück.
	 *
	 * Die Link-ID kommt entweder aus dem Routen-Platzhalter (neue Adresse
	 * /linktracker/{id}) oder aus dem Abfrageparameter "id" (alte Adresse
	 * bundles/contaolinktracker/go.php?id=…). Mit option=image wird statt der
	 * Weiterleitung eine transparente 1x1-Grafik ausgeliefert, damit sich auch
	 * Abrufe in E-Mails zählen lassen.
	 *
	 * Aufrufe von Suchmaschinen und anderen Robotern werden erkannt und nicht
	 * gezählt, aber trotzdem weitergeleitet; sonst würde die Statistik durch
	 * Vorschau-Abrufe unbrauchbar.
	 *
	 * Seiteneffekt: Bei jedem gezählten Aufruf entsteht ein Datensatz in
	 * tl_linktracker_items.
	 *
	 * @param Request $request Der eingehende Aufruf, liefert Browserkennung, IP,
	 *                         den Parameter option und die Link-ID
	 *
	 * @return Response Weiterleitung auf die hinterlegte URL oder das Zählpixel
	 *
	 * @throws NotFoundHttpException Wenn es zu der ID keinen veröffentlichten
	 *                               Datensatz gibt, oder wenn der Datensatz keine
	 *                               URL hat und auch kein Pixel angefordert wurde
	 */
	public function __invoke(Request $request): Response
	{
		// Die ID wird bewusst selbst aus den Attributen gelesen statt als
		// typisierter Methodenparameter entgegengenommen: Symfony reicht den
		// Platzhalter einer Route immer als Zeichenkette durch, und in einer
		// Datei mit strict_types wäre ein int-Parameter dann darauf angewiesen,
		// dass der Aufruf aus dem HttpKernel seinerseits ohne strict_types
		// erfolgt. Das ist heute so, aber nichts, worauf man bauen sollte.
		$id = (int) ($request->attributes->get('id') ?? $request->query->get('id', 0));
		$blnImage = 'image' === $request->query->get('option');

		$arrLink = $this->connection->fetchAssociative(
			'SELECT url FROM tl_linktracker WHERE published = ? AND id = ?',
			array('1', $id)
		);

		if (false === $arrLink)
		{
			$this->logger->error('[Linktracker] Link-ID ' . $id . ' existiert nicht oder ist nicht veröffentlicht');

			throw new NotFoundHttpException('Linktracker: unbekannte Link-ID ' . $id);
		}

		if (!$this->isBot($request))
		{
			$this->countClick($id, $request);
		}

		if ($blnImage)
		{
			return $this->createPixelResponse();
		}

		// Insert-Tags erst hier auflösen: Die URL darf {{link_url::42}} oder
		// Ähnliches enthalten, und dafür muss das Contao-Framework stehen.
		$this->framework->initialize();
		$strUrl = $this->insertTagParser->replaceInline((string) $arrLink['url']);

		if ('' === $strUrl)
		{
			$this->logger->error('[Linktracker] Link-ID ' . $id . ' hat keine Ziel-URL');

			throw new NotFoundHttpException('Linktracker: Link-ID ' . $id . ' hat keine Ziel-URL');
		}

		// 302 statt einer dauerhaften Weiterleitung, und ausdrücklich ohne
		// Zwischenspeicherung: Cachte der Browser das Ziel, käme der zweite
		// Klick desselben Besuchers gar nicht mehr hier an und fehlte deshalb
		// in der Statistik.
		$objResponse = new RedirectResponse($strUrl, Response::HTTP_FOUND);
		$objResponse->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

		return $objResponse;
	}

	/**
	 * Schreibt einen Klick in die Tabelle tl_linktracker_items.
	 *
	 * @param int     $id      ID des Links aus tl_linktracker, wird als pid abgelegt
	 * @param Request $request Liefert IP-Adresse und Browserkennung des Aufrufers
	 *
	 * @return void
	 */
	private function countClick(int $id, Request $request): void
	{
		$intTime = time();

		$this->connection->insert('tl_linktracker_items', array(
			'pid'       => $id,
			'tstamp'    => $intTime,
			'clickTime' => $intTime,
			'ip'        => (string) $request->getClientIp(),
			// Auf die Spaltenbreite kürzen. Browserkennungen sind teilweise über
			// 300 Zeichen lang; ungekürzt bricht MySQL den INSERT mit "Data too
			// long for column 'browser'" ab und der Aufruf endet mit Status 500.
			'browser'   => mb_substr($this->getUserAgent($request), 0, 255, 'UTF-8'),
			'published' => '1',
		));
	}

	/**
	 * Baut die Antwort mit der transparenten 1x1-Grafik auf.
	 *
	 * Die Grafik liegt im Bundle und wird direkt von dort ausgeliefert, damit
	 * die Antwort nicht davon abhängt, ob die öffentlichen Bundle-Dateien
	 * kopiert oder verlinkt wurden.
	 *
	 * @return BinaryFileResponse Die Grafik, ausdrücklich ohne Zwischenspeicherung,
	 *                            weil sonst nur der erste Abruf gezählt würde
	 */
	private function createPixelResponse(): BinaryFileResponse
	{
		$objResponse = new BinaryFileResponse(__DIR__ . '/../Resources/public/image.gif');
		$objResponse->headers->set('Content-Type', 'image/gif');
		$objResponse->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

		return $objResponse;
	}

	/**
	 * Liest die Browserkennung des Aufrufers aus.
	 *
	 * @param Request $request Der eingehende Aufruf
	 *
	 * @return string Die Kennung, oder eine leere Zeichenkette, wenn der
	 *                Aufrufer gar keine Kennung gesendet hat
	 */
	private function getUserAgent(Request $request): string
	{
		return (string) $request->headers->get('User-Agent', '');
	}

	/**
	 * Prüft anhand der Browserkennung, ob der Aufrufer ein Roboter ist.
	 *
	 * Die Erkennung erfolgt über eine feste Liste bekannter Kennungen. Sie ist
	 * bewusst großzügig ('bot', 'crawl', 'spider'), weil ein fälschlich
	 * gezählter Roboter die Statistik stärker verfälscht als ein nicht
	 * gezählter Besucher.
	 *
	 * @param Request $request Der eingehende Aufruf
	 *
	 * @return bool true, wenn die Kennung auf einen Roboter hindeutet;
	 *              false auch dann, wenn gar keine Kennung gesendet wurde —
	 *              solche Aufrufe werden also gezählt
	 */
	private function isBot(Request $request): bool
	{
		$strAgent = $this->getUserAgent($request);

		if ('' === $strAgent)
		{
			return false;
		}

		return 1 === preg_match('/rambler|abacho|acoi|accona|aspseek|altavista|estyle|scrubby|lycos|geona|ia_archiver|alexa|sogou|skype|facebook|twitter|pinterest|linkedin|naver|bing|google|yahoo|duckduckgo|yandex|baidu|teoma|xing|java\/1\.7\.0_45|bot|crawl|slurp|spider|mediapartners|\sask\s|\saol\s/i', $strAgent);
	}
}
