<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Statistik;

use Doctrine\DBAL\Connection;

/**
 * Rechnet die gezählten Klicks zu den Werten zusammen, die die Statistikseite
 * anzeigt.
 *
 * Die Klasse ist bewusst von Contao gelöst: Sie bekommt nur eine
 * Datenbankverbindung und liefert einfache Felder zurück. Dadurch lässt sich
 * die Rechnerei gegen eine echte Datenbank prüfen, ohne dass ein Backend
 * hochgefahren werden muss.
 */
class Auswertung
{
	/**
	 * Anzahl der Tage, die der Tagesverlauf abdeckt.
	 */
	public const TAGE_IM_VERLAUF = 30;

	/**
	 * Anzahl der Browserkennungen in der Rangliste.
	 */
	public const BROWSER_IN_RANGLISTE = 10;

	/**
	 * @param Connection $connection Verbindung zur Contao-Datenbank
	 * @param int|null   $jetzt      Zeitpunkt, der als „jetzt" gilt, als
	 *                               Unix-Zeitstempel. Nur für die Prüfung
	 *                               gedacht; im Betrieb bleibt der Wert null und
	 *                               es gilt die aktuelle Uhrzeit
	 */
	public function __construct(
		private readonly Connection $connection,
		private readonly int|null $jetzt = null
	) {
	}

	/**
	 * Liest Titel und Adresse eines Links.
	 *
	 * @param int $intId ID des Links aus tl_linktracker
	 *
	 * @return array<string,mixed>|null Der Datensatz, oder null, wenn es zu der
	 *                                  ID keinen Link gibt
	 */
	public function getLink(int $intId): array|null
	{
		$arrLink = $this->connection->fetchAssociative(
			'SELECT id, title, url FROM tl_linktracker WHERE id = ?',
			array($intId)
		);

		return false === $arrLink ? null : $arrLink;
	}

	/**
	 * Ermittelt die Kennzahlen eines Links.
	 *
	 * Die Zeiträume beginnen jeweils um Mitternacht des ersten Tages, damit
	 * „7 Tage" den heutigen und die sechs vorangegangenen Tage umfasst und
	 * nicht eine Spanne von 168 Stunden, die mitten in einem Tag anfängt.
	 *
	 * @param int $intId ID des Links aus tl_linktracker
	 *
	 * @return array{gesamt:int,heute:int,siebenTage:int,dreissigTage:int,ueberAlteAdresse:int}
	 */
	public function getKennzahlen(int $intId): array
	{
		$intHeute = $this->getTagesbeginn();

		return array(
			'gesamt' => $this->zaehle('pid = ?', array($intId)),
			'heute' => $this->zaehle('pid = ? AND clickTime >= ?', array($intId, $intHeute)),
			'siebenTage' => $this->zaehle('pid = ? AND clickTime >= ?', array($intId, strtotime('-6 day', $intHeute))),
			'dreissigTage' => $this->zaehle('pid = ? AND clickTime >= ?', array($intId, strtotime('-29 day', $intHeute))),
			'ueberAlteAdresse' => $this->zaehle('pid = ? AND viaLegacy = ?', array($intId, '1')),
		);
	}

	/**
	 * Ermittelt die Klicks je Tag für das Balkendiagramm.
	 *
	 * Zurückgegeben wird für jeden Tag des Zeitraums ein Eintrag, auch für Tage
	 * ohne Klicks — sonst hätte das Diagramm Lücken und die Balken lägen nicht
	 * auf einer Zeitachse. Die Höhe ist bereits als Anteil am stärksten Tag
	 * ausgerechnet, damit das Template nichts rechnen muss.
	 *
	 * Die Klickzeiten liegen als Unix-Zeitstempel in einer Textspalte. Die
	 * Zuordnung zu Tagen erfolgt deshalb in PHP und nicht mit FROM_UNIXTIME in
	 * SQL: Sonst richtete sich die Tagesgrenze nach der Zeitzone des
	 * Datenbankservers statt nach der von PHP und Contao.
	 *
	 * @param int $intId ID des Links aus tl_linktracker
	 *
	 * @return array{tage:array<int,array{datum:string,kurz:string,anzahl:int,hoehe:float}>,hoechstwert:int,summe:int}
	 */
	public function getTagesverlauf(int $intId): array
	{
		$intVon = (int) strtotime('-' . (self::TAGE_IM_VERLAUF - 1) . ' day', $this->getTagesbeginn());

		$arrZeiten = $this->connection->fetchFirstColumn(
			'SELECT clickTime FROM tl_linktracker_items WHERE pid = ? AND clickTime >= ?',
			array($intId, $intVon)
		);

		// Alle Tage des Zeitraums mit Null vorbelegen
		$arrZaehler = array();

		for ($i = 0; $i < self::TAGE_IM_VERLAUF; $i++)
		{
			$arrZaehler[date('Y-m-d', (int) strtotime('+' . $i . ' day', $intVon))] = 0;
		}

		foreach ($arrZeiten as $mixZeit)
		{
			$strTag = date('Y-m-d', (int) $mixZeit);

			if (isset($arrZaehler[$strTag]))
			{
				$arrZaehler[$strTag]++;
			}
		}

		$intHoechstwert = max($arrZaehler);
		$arrTage = array();

		foreach ($arrZaehler as $strTag => $intAnzahl)
		{
			$arrTage[] = array(
				'datum'  => $strTag,
				'kurz'   => date('d.m.', (int) strtotime($strTag)),
				'anzahl' => $intAnzahl,
				// Anteil am stärksten Tag, in Prozent. Ohne die Abfrage bliebe
				// bei lauter Nullen eine Division durch null stehen.
				'hoehe'  => $intHoechstwert > 0 ? round($intAnzahl / $intHoechstwert * 100, 1) : 0.0,
			);
		}

		return array(
			'tage'        => $arrTage,
			'hoechstwert' => $intHoechstwert,
			'summe'       => array_sum($arrZaehler),
		);
	}

	/**
	 * Ermittelt die Klicks je Monat, vom jüngsten Monat an.
	 *
	 * Die Gruppierung erfolgt wie beim Tagesverlauf in PHP, damit die
	 * Monatsgrenzen der Zeitzone von PHP folgen.
	 *
	 * @param int $intId ID des Links aus tl_linktracker
	 *
	 * @return array<int,array{monat:string,anzahl:int}> Der Schlüssel monat hat
	 *                                                   die Form JJJJ-MM
	 */
	public function getMonate(int $intId): array
	{
		$arrZeiten = $this->connection->fetchFirstColumn(
			'SELECT clickTime FROM tl_linktracker_items WHERE pid = ?',
			array($intId)
		);

		$arrZaehler = array();

		foreach ($arrZeiten as $mixZeit)
		{
			$strMonat = date('Y-m', (int) $mixZeit);
			$arrZaehler[$strMonat] = ($arrZaehler[$strMonat] ?? 0) + 1;
		}

		krsort($arrZaehler);

		$arrMonate = array();

		foreach ($arrZaehler as $strMonat => $intAnzahl)
		{
			$arrMonate[] = array('monat' => $strMonat, 'anzahl' => $intAnzahl);
		}

		return $arrMonate;
	}

	/**
	 * Ermittelt die häufigsten Browserkennungen eines Links.
	 *
	 * Gezählt wird die Kennung in voller Länge, nicht ein daraus abgeleiteter
	 * Produktname: Eine verlässliche Zuordnung zu Browsern bräuchte eine
	 * gepflegte Kennungsdatenbank, und die Rohkennung beantwortet die
	 * eigentliche Frage — kommen die Aufrufe von Menschen oder von Programmen —
	 * ohnehin genauer.
	 *
	 * @param int $intId ID des Links aus tl_linktracker
	 *
	 * @return array<int,array{browser:string,anzahl:int,anteil:float}> Leer,
	 *         wenn der Link noch nie aufgerufen wurde
	 */
	public function getBrowser(int $intId): array
	{
		$intGesamt = $this->zaehle('pid = ?', array($intId));

		if (0 === $intGesamt)
		{
			return array();
		}

		$arrZeilen = $this->connection->fetchAllAssociative(
			'SELECT browser, COUNT(*) AS anzahl
			   FROM tl_linktracker_items
			  WHERE pid = ?
			  GROUP BY browser
			  ORDER BY anzahl DESC, browser ASC
			  LIMIT ' . self::BROWSER_IN_RANGLISTE,
			array($intId)
		);

		$arrBrowser = array();

		foreach ($arrZeilen as $arrZeile)
		{
			$arrBrowser[] = array(
				'browser' => (string) $arrZeile['browser'],
				'anzahl'  => (int) $arrZeile['anzahl'],
				'anteil'  => round((int) $arrZeile['anzahl'] / $intGesamt * 100, 1),
			);
		}

		return $arrBrowser;
	}

	/**
	 * Ermittelt für jeden Link die Zahl seiner Aufrufe.
	 *
	 * @return array<int,array<string,mixed>> Je Link eine Zeile mit id, title,
	 *         url, published, gesamt, ueberAlteAdresse und letzter. Links ohne
	 *         Klicks erscheinen mit der Zahl 0 und letzter = null
	 */
	public function getUebersicht(): array
	{
		// LEFT JOIN statt einer Abfrage je Zeile: Links ganz ohne Klicks sollen
		// mit einer Null erscheinen und nicht aus der Liste fallen.
		$arrZeilen = $this->connection->fetchAllAssociative(
			'SELECT l.id, l.title, l.url, l.published,
			        COUNT(i.id) AS gesamt,
			        SUM(CASE WHEN i.viaLegacy = ? THEN 1 ELSE 0 END) AS ueberAlteAdresse,
			        MAX(i.clickTime) AS letzter
			   FROM tl_linktracker l
			   LEFT JOIN tl_linktracker_items i ON i.pid = l.id
			  GROUP BY l.id, l.title, l.url, l.published
			  ORDER BY gesamt DESC, l.title ASC',
			array('1')
		);

		foreach ($arrZeilen as $intPos => $arrZeile)
		{
			$arrZeilen[$intPos]['gesamt'] = (int) $arrZeile['gesamt'];
			$arrZeilen[$intPos]['ueberAlteAdresse'] = (int) $arrZeile['ueberAlteAdresse'];
			$arrZeilen[$intPos]['letzter'] = null !== $arrZeile['letzter'] ? (int) $arrZeile['letzter'] : null;
		}

		return $arrZeilen;
	}

	/**
	 * Zählt Klickdatensätze nach einer Bedingung.
	 *
	 * @param string       $strBedingung Die WHERE-Bedingung mit Platzhaltern
	 * @param array<mixed> $arrWerte     Die Werte zu den Platzhaltern
	 *
	 * @return int Die Anzahl der passenden Datensätze
	 */
	private function zaehle(string $strBedingung, array $arrWerte): int
	{
		return (int) $this->connection->fetchOne(
			'SELECT COUNT(*) FROM tl_linktracker_items WHERE ' . $strBedingung,
			$arrWerte
		);
	}

	/**
	 * Liefert den Beginn des heutigen Tages als Unix-Zeitstempel.
	 *
	 * @return int Mitternacht des Tages, der als „heute" gilt
	 */
	private function getTagesbeginn(): int
	{
		$intJetzt = $this->jetzt ?? time();

		return (int) mktime(0, 0, 0, (int) date('m', $intJetzt), (int) date('d', $intJetzt), (int) date('Y', $intJetzt));
	}
}
