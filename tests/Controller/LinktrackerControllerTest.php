<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Tests\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoLinktrackerBundle\Controller\LinktrackerController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Prüft den Controller, der die Klicks zählt und weiterleitet.
 *
 * Die Datenbankverbindung wird durchweg als Attrappe eingesetzt; geprüft wird
 * also nicht MySQL, sondern ob der Controller unter den jeweiligen Umständen
 * schreibt oder eben nicht.
 */
class LinktrackerControllerTest extends TestCase
{
	/**
	 * Die Browserkennung eines gewöhnlichen Besuchers.
	 */
	private const AGENT_BESUCHER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

	/**
	 * Die Browserkennung eines Suchmaschinen-Roboters.
	 */
	private const AGENT_ROBOTER = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

	/**
	 * Setzt den Controller mit Attrappen zusammen.
	 *
	 * @param Connection           $connection Die vorbereitete Datenbank-Attrappe
	 * @param string               $strZielUrl Was der Insert-Tag-Parser aus der
	 *                                         hinterlegten URL machen soll
	 * @param LoggerInterface|null $logger     Wird durchgereicht, wenn die
	 *                                         Prüfung die Protokollierung ansieht
	 *
	 * @return LinktrackerController Der einsatzbereite Controller
	 */
	private function createController(Connection $connection, string $strZielUrl = 'https://example.org/ziel', LoggerInterface|null $logger = null): LinktrackerController
	{
		$objParser = $this->createMock(InsertTagParser::class);
		$objParser->method('replaceInline')->willReturn($strZielUrl);

		return new LinktrackerController(
			$this->createMock(ContaoFramework::class),
			$connection,
			$objParser,
			$logger ?? $this->createMock(LoggerInterface::class)
		);
	}

	/**
	 * Baut einen Aufruf über die Route /linktracker/{id}.
	 *
	 * Die ID landet in den Request-Attributen und dort ausdrücklich als
	 * Zeichenkette, weil Symfony Routen-Platzhalter genau so durchreicht.
	 *
	 * @param int                  $id       Die Link-ID aus der Route
	 * @param string               $strAgent Die Browserkennung
	 * @param array<string,string> $arrQuery Die Abfrageparameter
	 *
	 * @return Request Der vorbereitete Aufruf
	 */
	private function createRouteRequest(int $id, string $strAgent = self::AGENT_BESUCHER, array $arrQuery = array()): Request
	{
		$objRequest = new Request($arrQuery, array(), array(), array(), array(), array('HTTP_USER_AGENT' => $strAgent));
		$objRequest->attributes->set('id', (string) $id);

		return $objRequest;
	}

	/**
	 * Ein gewöhnlicher Klick muss gezählt werden und in einer Weiterleitung
	 * enden, die der Browser nicht zwischenspeichern darf.
	 */
	public function testZaehltDenKlickUndLeitetWeiter(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => 32 === $arrSet['pid'] && '1' === $arrSet['published'])
		);

		$objResponse = $this->createController($objConnection)($this->createRouteRequest(32));

		$this->assertInstanceOf(RedirectResponse::class, $objResponse);
		$this->assertSame('https://example.org/ziel', $objResponse->getTargetUrl());
		$this->assertSame(302, $objResponse->getStatusCode());
		$this->assertStringContainsString('no-store', (string) $objResponse->headers->get('Cache-Control'));
	}

	/**
	 * Die ID aus der Route kommt als Zeichenkette an und muss trotzdem als
	 * Zahl in der Datenbank landen.
	 */
	public function testWandeltDieIdAusDerRouteInEineZahl(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => 32 === $arrSet['pid'])
		);

		$this->createController($objConnection)($this->createRouteRequest(32));
	}

	/**
	 * Ein Aufruf über die neue Adresse wird nicht als Altaufruf gekennzeichnet.
	 */
	public function testKennzeichnetDieNeueAdresseNicht(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => '' === $arrSet['viaLegacy'])
		);

		$objRequest = $this->createRouteRequest(32);
		$objRequest->attributes->set('_route', 'linktracker_go');

		$this->createController($objConnection)($objRequest);
	}

	/**
	 * Ein Aufruf über die alte Adresse wird gekennzeichnet. Nur so lässt sich
	 * später beurteilen, ob diese Adresse noch gebraucht wird.
	 */
	public function testKennzeichnetDieAlteAdresse(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => '1' === $arrSet['viaLegacy'])
		);

		$objRequest = new Request(array('id' => '32'), array(), array(), array(), array(), array('HTTP_USER_AGENT' => self::AGENT_BESUCHER));
		$objRequest->attributes->set('_route', LinktrackerController::ROUTE_LEGACY);

		$this->createController($objConnection)($objRequest);
	}

	/**
	 * Ein Roboter wird weitergeleitet, aber nicht gezählt.
	 */
	public function testZaehltRoboterNicht(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->never())->method('insert');

		$objResponse = $this->createController($objConnection)(
			$this->createRouteRequest(32, self::AGENT_ROBOTER)
		);

		$this->assertInstanceOf(RedirectResponse::class, $objResponse);
	}

	/**
	 * Fehlt die Browserkennung ganz, wird trotzdem gezählt — das war schon
	 * vor der Umstellung so und soll sich nicht ändern.
	 */
	public function testZaehltAuchOhneBrowserkennung(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert');

		$objRequest = new Request();
		$objRequest->attributes->set('id', '32');

		$this->createController($objConnection)($objRequest);
	}

	/**
	 * Eine überlange Browserkennung muss auf die Spaltenbreite gekürzt werden,
	 * sonst bricht der INSERT ab und der Aufruf endet mit Status 500.
	 */
	public function testKuerztDieBrowserkennung(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => 255 === \strlen($arrSet['browser']))
		);

		$this->createController($objConnection)(
			$this->createRouteRequest(32, str_repeat('a', 400))
		);
	}

	/**
	 * Mit option=image kommt das Zählpixel statt einer Weiterleitung, und der
	 * Klick wird trotzdem gezählt.
	 */
	public function testLiefertDasZaehlpixel(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => ''));
		$objConnection->expects($this->once())->method('insert');

		$objResponse = $this->createController($objConnection)(
			$this->createRouteRequest(32, self::AGENT_BESUCHER, array('option' => 'image'))
		);

		$this->assertInstanceOf(BinaryFileResponse::class, $objResponse);
		$this->assertSame('image/gif', $objResponse->headers->get('Content-Type'));
		$this->assertStringContainsString('no-store', (string) $objResponse->headers->get('Cache-Control'));
	}

	/**
	 * Die alte Adresse übergibt die ID als Abfrageparameter statt über die
	 * Route; auch so muss der richtige Datensatz gezählt werden.
	 */
	public function testNimmtDieIdAuchAusDerAbfrage(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => 'https://example.org/ziel'));
		$objConnection->expects($this->once())->method('insert')->with(
			'tl_linktracker_items',
			$this->callback(static fn (array $arrSet): bool => 77 === $arrSet['pid'])
		);

		$objRequest = new Request(array('id' => '77'), array(), array(), array(), array(), array('HTTP_USER_AGENT' => self::AGENT_BESUCHER));

		$objResponse = $this->createController($objConnection)($objRequest);

		$this->assertInstanceOf(RedirectResponse::class, $objResponse);
	}

	/**
	 * Eine unbekannte oder nicht veröffentlichte ID muss mit 404 beantwortet
	 * und protokolliert werden. Vor der Umstellung lief der Aufruf hier mit
	 * leerer Adresse in die Weiterleitung.
	 */
	public function testMeldetUnbekannteId(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(false);
		$objConnection->expects($this->never())->method('insert');

		$objLogger = $this->createMock(LoggerInterface::class);
		$objLogger->expects($this->once())->method('error');

		$this->expectException(NotFoundHttpException::class);

		$this->createController($objConnection, 'https://example.org/ziel', $objLogger)(
			$this->createRouteRequest(999)
		);
	}

	/**
	 * Ein Datensatz ohne Ziel-URL ist nur als Zählpixel sinnvoll. Wird er
	 * trotzdem als Verweis aufgerufen, muss das auffallen statt in einer
	 * leeren Weiterleitung zu enden.
	 */
	public function testMeldetFehlendeZielUrl(): void
	{
		$objConnection = $this->createMock(Connection::class);
		$objConnection->method('fetchAssociative')->willReturn(array('url' => ''));

		$objLogger = $this->createMock(LoggerInterface::class);
		$objLogger->expects($this->once())->method('error');

		$this->expectException(NotFoundHttpException::class);

		$this->createController($objConnection, '', $objLogger)(
			$this->createRouteRequest(32)
		);
	}
}
