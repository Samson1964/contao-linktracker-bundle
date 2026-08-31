<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Tests\Statistik;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoLinktrackerBundle\Statistik\Auswertung;

/**
 * Prüft die Auswertung der gezählten Klicks.
 *
 * Geprüft wird gegen eine echte Datenbank im Arbeitsspeicher statt gegen
 * Attrappen: Die Klasse besteht im Wesentlichen aus Abfragen, und eine Attrappe
 * würde nur bestätigen, dass die erwartete Zeichenkette abgeschickt wurde —
 * nicht, dass sie das Richtige zählt.
 */
class AuswertungTest extends TestCase
{
	/**
	 * Ein fester Zeitpunkt, damit die Zeitraumgrenzen prüfbar bleiben.
	 *
	 * In der Zeitzone Europe/Berlin ist das Sonntag, der 23. August 2026,
	 * 16:30 Uhr. Welcher Kalendertag daraus wird, hängt von der eingestellten
	 * Zeitzone ab; das macht nichts, weil sämtliche Erwartungen unten relativ
	 * zu diesem Zeitpunkt gerechnet werden. Wichtig ist allein, dass er mitten
	 * am Tag liegt — sonst fiele nicht auf, wenn die Zeiträume statt an
	 * Tagesgrenzen an vollen 24 Stunden abgeschnitten würden.
	 */
	private const JETZT = 1787495400;

	/**
	 * Die Verbindung zur Datenbank im Arbeitsspeicher.
	 */
	private Connection $connection;

	/**
	 * Legt vor jeder Prüfung die beiden Tabellen an.
	 *
	 * Die Spaltentypen entsprechen denen der DCA-Dateien, insbesondere
	 * clickTime als Text — genau daraus rührt die Notwendigkeit, die Tage in
	 * PHP statt in SQL zu gruppieren.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->connection = DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));

		$this->connection->executeStatement(
			'CREATE TABLE tl_linktracker (
				id INTEGER PRIMARY KEY,
				title TEXT NOT NULL DEFAULT "",
				url TEXT NOT NULL DEFAULT "",
				published TEXT NOT NULL DEFAULT ""
			)'
		);

		$this->connection->executeStatement(
			'CREATE TABLE tl_linktracker_items (
				id INTEGER PRIMARY KEY,
				pid INTEGER NOT NULL DEFAULT 0,
				clickTime TEXT NOT NULL DEFAULT "",
				ip TEXT NOT NULL DEFAULT "",
				browser TEXT NOT NULL DEFAULT "",
				viaLegacy TEXT NOT NULL DEFAULT "",
				published TEXT NOT NULL DEFAULT ""
			)'
		);
	}

	/**
	 * Legt einen Link an.
	 *
	 * @param int    $id           ID des Links
	 * @param string $strTitel     Titel des Links
	 * @param string $strVeroeff   '1' für veröffentlicht, sonst ''
	 *
	 * @return void
	 */
	private function legeLinkAn(int $id, string $strTitel, string $strVeroeff = '1'): void
	{
		$this->connection->insert('tl_linktracker', array(
			'id' => $id, 'title' => $strTitel, 'url' => 'https://example.org/' . $id, 'published' => $strVeroeff,
		));
	}

	/**
	 * Legt einen Klick an.
	 *
	 * @param int    $intPid       ID des Links
	 * @param int    $intZeit      Zeitpunkt des Klicks als Unix-Zeitstempel
	 * @param string $strBrowser   Browserkennung
	 * @param string $strViaLegacy '1', wenn der Klick über die alte Adresse kam
	 *
	 * @return void
	 */
	private function legeKlickAn(int $intPid, int $intZeit, string $strBrowser = 'Firefox', string $strViaLegacy = ''): void
	{
		$this->connection->insert('tl_linktracker_items', array(
			'pid' => $intPid, 'clickTime' => (string) $intZeit, 'ip' => '203.0.113.1',
			'browser' => $strBrowser, 'viaLegacy' => $strViaLegacy, 'published' => '1',
		));
	}

	/**
	 * Erzeugt die Auswertung mit dem festen Zeitpunkt.
	 *
	 * @return Auswertung Die vorbereitete Auswertung
	 */
	private function createAuswertung(): Auswertung
	{
		return new Auswertung($this->connection, self::JETZT);
	}

	/**
	 * Liefert Mitternacht des Tages, der als „heute" gilt.
	 *
	 * @return int Der Zeitstempel
	 */
	private function heute(): int
	{
		return (int) mktime(0, 0, 0, (int) date('m', self::JETZT), (int) date('d', self::JETZT), (int) date('Y', self::JETZT));
	}

	/**
	 * Zu einer unbekannten ID darf kein Link herauskommen.
	 */
	public function testMeldetUnbekanntenLink(): void
	{
		$this->assertNull($this->createAuswertung()->getLink(999));
	}

	/**
	 * Ein vorhandener Link kommt mit Titel und Adresse zurück.
	 */
	public function testLiefertDenLink(): void
	{
		$this->legeLinkAn(7, 'Turnierbericht');

		$arrLink = $this->createAuswertung()->getLink(7);

		$this->assertIsArray($arrLink);
		$this->assertSame('Turnierbericht', $arrLink['title']);
	}

	/**
	 * Die Kennzahlen müssen die Zeiträume richtig abgrenzen. Entscheidend ist,
	 * dass die Grenzen auf Mitternacht liegen: Ein Klick von heute früh gehört
	 * zu „heute", auch wenn er mehr als 24 Stunden zurückliegt.
	 */
	public function testGrenztDieZeitraeumeAnTagesgrenzenAb(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$intHeute = $this->heute();

		$this->legeKlickAn(1, $intHeute);                              // heute, 0 Uhr
		$this->legeKlickAn(1, self::JETZT);                            // heute, mitten am Tag
		$this->legeKlickAn(1, (int) strtotime('-1 day', $intHeute));   // gestern
		$this->legeKlickAn(1, (int) strtotime('-6 day', $intHeute));   // Rand der 7 Tage
		$this->legeKlickAn(1, (int) strtotime('-7 day', $intHeute));   // knapp ausserhalb
		$this->legeKlickAn(1, (int) strtotime('-29 day', $intHeute));  // Rand der 30 Tage
		$this->legeKlickAn(1, (int) strtotime('-30 day', $intHeute));  // knapp ausserhalb

		$arrKennzahlen = $this->createAuswertung()->getKennzahlen(1);

		$this->assertSame(7, $arrKennzahlen['gesamt']);
		$this->assertSame(2, $arrKennzahlen['heute']);
		$this->assertSame(4, $arrKennzahlen['siebenTage']);
		$this->assertSame(6, $arrKennzahlen['dreissigTage']);
	}

	/**
	 * Klicks anderer Links dürfen nicht mitgezählt werden.
	 */
	public function testZaehltNurDenGefragtenLink(): void
	{
		$this->legeLinkAn(1, 'Erster');
		$this->legeLinkAn(2, 'Zweiter');
		$this->legeKlickAn(1, self::JETZT);
		$this->legeKlickAn(2, self::JETZT);
		$this->legeKlickAn(2, self::JETZT);

		$this->assertSame(1, $this->createAuswertung()->getKennzahlen(1)['gesamt']);
		$this->assertSame(2, $this->createAuswertung()->getKennzahlen(2)['gesamt']);
	}

	/**
	 * Die Aufrufe über die alte Adresse werden getrennt ausgewiesen — das ist
	 * die Zahl, an der sich ablesen lässt, ob die Adresse noch gebraucht wird.
	 */
	public function testWeistDieAufrufeUeberDieAlteAdresseAus(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$this->legeKlickAn(1, self::JETZT, 'Firefox', '1');
		$this->legeKlickAn(1, self::JETZT, 'Firefox', '1');
		$this->legeKlickAn(1, self::JETZT, 'Firefox', '');

		$arrKennzahlen = $this->createAuswertung()->getKennzahlen(1);

		$this->assertSame(3, $arrKennzahlen['gesamt']);
		$this->assertSame(2, $arrKennzahlen['ueberAlteAdresse']);
	}

	/**
	 * Der Tagesverlauf muss für jeden Tag des Zeitraums einen Eintrag liefern,
	 * auch für Tage ohne Klicks — sonst hätte das Diagramm Lücken.
	 */
	public function testLiefertJedenTagDesZeitraums(): void
	{
		$this->legeLinkAn(1, 'Ein Link');

		$arrVerlauf = $this->createAuswertung()->getTagesverlauf(1);

		$this->assertCount(Auswertung::TAGE_IM_VERLAUF, $arrVerlauf['tage']);
		$this->assertSame(0, $arrVerlauf['hoechstwert']);
		$this->assertSame(0, $arrVerlauf['summe']);
		$this->assertSame(date('Y-m-d', $this->heute()), end($arrVerlauf['tage'])['datum']);
	}

	/**
	 * Ohne einen einzigen Klick darf die Höhenrechnung nicht durch null teilen.
	 */
	public function testRechnetOhneKlickeOhneDivisionDurchNull(): void
	{
		$this->legeLinkAn(1, 'Ein Link');

		foreach ($this->createAuswertung()->getTagesverlauf(1)['tage'] as $arrTag)
		{
			$this->assertSame(0.0, $arrTag['hoehe']);
		}
	}

	/**
	 * Die Balkenhöhe ist der Anteil am stärksten Tag.
	 */
	public function testRechnetDieBalkenhoehen(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$intHeute = $this->heute();

		// Vier Klicks heute, einer gestern
		for ($i = 0; $i < 4; $i++)
		{
			$this->legeKlickAn(1, $intHeute);
		}

		$this->legeKlickAn(1, (int) strtotime('-1 day', $intHeute));

		$arrVerlauf = $this->createAuswertung()->getTagesverlauf(1);
		$arrTage = $arrVerlauf['tage'];
		$arrHeute = end($arrTage);
		$arrGestern = prev($arrTage);

		$this->assertSame(4, $arrVerlauf['hoechstwert']);
		$this->assertSame(5, $arrVerlauf['summe']);
		$this->assertSame(4, $arrHeute['anzahl']);
		$this->assertSame(100.0, $arrHeute['hoehe']);
		$this->assertSame(1, $arrGestern['anzahl']);
		$this->assertSame(25.0, $arrGestern['hoehe']);
	}

	/**
	 * Klicks ausserhalb des Zeitraums gehören nicht in den Verlauf.
	 */
	public function testLaesstAelteresAusDemVerlaufWeg(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$this->legeKlickAn(1, (int) strtotime('-60 day', $this->heute()));

		$this->assertSame(0, $this->createAuswertung()->getTagesverlauf(1)['summe']);
	}

	/**
	 * Die Monate kommen vom jüngsten an, mit den richtigen Summen.
	 */
	public function testFasstDieMonateZusammen(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$this->legeKlickAn(1, (int) mktime(12, 0, 0, 8, 3, 2026));
		$this->legeKlickAn(1, (int) mktime(12, 0, 0, 8, 20, 2026));
		$this->legeKlickAn(1, (int) mktime(12, 0, 0, 6, 15, 2026));

		$arrMonate = $this->createAuswertung()->getMonate(1);

		$this->assertCount(2, $arrMonate);
		$this->assertSame('2026-08', $arrMonate[0]['monat']);
		$this->assertSame(2, $arrMonate[0]['anzahl']);
		$this->assertSame('2026-06', $arrMonate[1]['monat']);
		$this->assertSame(1, $arrMonate[1]['anzahl']);
	}

	/**
	 * Die Browserrangliste zählt nach Häufigkeit und rechnet die Anteile aus.
	 */
	public function testStelltDieBrowserRanglisteAuf(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$this->legeKlickAn(1, self::JETZT, 'Firefox');
		$this->legeKlickAn(1, self::JETZT, 'Firefox');
		$this->legeKlickAn(1, self::JETZT, 'Firefox');
		$this->legeKlickAn(1, self::JETZT, 'Chrome');

		$arrBrowser = $this->createAuswertung()->getBrowser(1);

		$this->assertCount(2, $arrBrowser);
		$this->assertSame('Firefox', $arrBrowser[0]['browser']);
		$this->assertSame(3, $arrBrowser[0]['anzahl']);
		$this->assertSame(75.0, $arrBrowser[0]['anteil']);
		$this->assertSame('Chrome', $arrBrowser[1]['browser']);
		$this->assertSame(25.0, $arrBrowser[1]['anteil']);
	}

	/**
	 * Die Rangliste ist auf eine feste Länge begrenzt.
	 */
	public function testBegrenztDieBrowserRangliste(): void
	{
		$this->legeLinkAn(1, 'Ein Link');

		for ($i = 0; $i < Auswertung::BROWSER_IN_RANGLISTE + 5; $i++)
		{
			$this->legeKlickAn(1, self::JETZT, 'Browser ' . $i);
		}

		$this->assertCount(Auswertung::BROWSER_IN_RANGLISTE, $this->createAuswertung()->getBrowser(1));
	}

	/**
	 * Ohne Klicks bleibt die Rangliste leer, statt durch null zu teilen.
	 */
	public function testLiefertOhneKlickeKeineBrowser(): void
	{
		$this->legeLinkAn(1, 'Ein Link');

		$this->assertSame(array(), $this->createAuswertung()->getBrowser(1));
	}

	/**
	 * In der Übersicht muss auch ein Link ohne Klicks erscheinen — sonst fiele
	 * gerade der auf, um den man sich kümmern will, aus der Liste.
	 */
	public function testZeigtAuchLinksOhneKlicksInDerUebersicht(): void
	{
		$this->legeLinkAn(1, 'Mit Klicks');
		$this->legeLinkAn(2, 'Ohne Klicks');
		$this->legeKlickAn(1, self::JETZT);

		$arrZeilen = $this->createAuswertung()->getUebersicht();

		$this->assertCount(2, $arrZeilen);
		$this->assertSame('Mit Klicks', $arrZeilen[0]['title']);
		$this->assertSame(1, $arrZeilen[0]['gesamt']);
		$this->assertSame('Ohne Klicks', $arrZeilen[1]['title']);
		$this->assertSame(0, $arrZeilen[1]['gesamt']);
		$this->assertNull($arrZeilen[1]['letzter']);
	}

	/**
	 * Die Übersicht sortiert nach Häufigkeit und nennt den letzten Aufruf.
	 */
	public function testSortiertDieUebersichtNachHaeufigkeit(): void
	{
		$this->legeLinkAn(1, 'Selten');
		$this->legeLinkAn(2, 'Oft');
		$this->legeKlickAn(1, self::JETZT - 500);
		$this->legeKlickAn(2, self::JETZT - 900);
		$this->legeKlickAn(2, self::JETZT);

		$arrZeilen = $this->createAuswertung()->getUebersicht();

		$this->assertSame('Oft', $arrZeilen[0]['title']);
		$this->assertSame(2, $arrZeilen[0]['gesamt']);
		$this->assertSame(self::JETZT, $arrZeilen[0]['letzter']);
	}

	/**
	 * Auch in der Übersicht werden die Aufrufe über die alte Adresse je Link
	 * getrennt ausgewiesen.
	 */
	public function testWeistDieAlteAdresseInDerUebersichtAus(): void
	{
		$this->legeLinkAn(1, 'Ein Link');
		$this->legeKlickAn(1, self::JETZT, 'Firefox', '1');
		$this->legeKlickAn(1, self::JETZT, 'Firefox', '');

		$arrZeilen = $this->createAuswertung()->getUebersicht();

		$this->assertSame(2, $arrZeilen[0]['gesamt']);
		$this->assertSame(1, $arrZeilen[0]['ueberAlteAdresse']);
	}

	/**
	 * Ohne angelegte Links bleibt die Übersicht leer.
	 */
	public function testLiefertLeereUebersichtOhneLinks(): void
	{
		$this->assertSame(array(), $this->createAuswertung()->getUebersicht());
	}
}
