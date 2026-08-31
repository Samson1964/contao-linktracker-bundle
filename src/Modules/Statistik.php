<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Modules;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Date;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Schachbulle\ContaoLinktrackerBundle\Statistik\Auswertung;

/**
 * Zeigt die Auswertung der gezählten Klicks im Backend.
 *
 * Das Modul hängt als Schlüssel "statistik" am Backend-Modul und wird über die
 * gleichnamige Schaltfläche in der Listenansicht aufgerufen. Mit einer ID zeigt
 * es die Auswertung eines einzelnen Links, ohne ID eine Übersicht über alle.
 *
 * Gerechnet wird hier nichts: Das erledigt die Klasse Statistik\Auswertung, die
 * ohne Contao auskommt und sich deshalb gegen eine echte Datenbank prüfen
 * lässt. Diese Klasse besorgt nur die Umgebung — Contao erzeugt sie über
 * System::importStatic() und kann ihr deshalb keine Dienste in den Konstruktor
 * reichen.
 */
class Statistik
{
	/**
	 * Erzeugt die Ausgabe der Statistikseite.
	 *
	 * Welche der beiden Ansichten erscheint, entscheidet der Parameter "id" in
	 * der Adresse: Contao hängt ihn an, wenn die Schaltfläche in einer Zeile
	 * der Liste angeklickt wurde.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container. Contao reicht
	 *                               ihn herein; die ID wird trotzdem aus der
	 *                               Adresse gelesen, weil das Modul auch ohne
	 *                               Data Container aufrufbar ist
	 *
	 * @return string Der HTML-Block, der im Hauptbereich des Backends erscheint
	 */
	public function go(DataContainer|null $dc = null): string
	{
		$intId = (int) Input::get('id');

		// Für die ausgeschriebenen Monatsnamen in Date::parse('F Y'): Sie
		// stammen aus TL_LANG['MONTHS'] der Datei default.
		System::loadLanguageFile('default');

		$objAuswertung = new Auswertung(System::getContainer()->get('database_connection'));

		$objTemplate = new BackendTemplate('be_linktracker_statistik');
		$objTemplate->backUrl = $this->getBackUrl();
		$objTemplate->backBT = $GLOBALS['TL_LANG']['MSC']['backBT'] ?? 'Zurück';
		$objTemplate->backBTTitle = $GLOBALS['TL_LANG']['MSC']['backBTTitle'] ?? '';
		$objTemplate->lang = $GLOBALS['TL_LANG']['tl_linktracker'] ?? array();

		if ($intId > 0)
		{
			$this->fuelleEinzelansicht($objTemplate, $objAuswertung, $intId);
		}
		else
		{
			$arrZeilen = $objAuswertung->getUebersicht();

			$objTemplate->modus = 'uebersicht';
			$objTemplate->zeilen = $this->bereiteUebersichtAuf($arrZeilen);
			$objTemplate->summe = array_sum(array_column($arrZeilen, 'gesamt'));
		}

		return $objTemplate->parse();
	}

	/**
	 * Ergänzt die Zeilen der Übersicht um Adresse und Datumsangabe.
	 *
	 * Beides entsteht hier und nicht im Template: Backend::addToUrl und
	 * Config::get greifen auf den Dienstbehälter zu, und ein Template, das den
	 * braucht, lässt sich nicht mehr eigenständig prüfen.
	 *
	 * @param array<int,array<string,mixed>> $arrZeilen Die Rohwerte der Auswertung
	 *
	 * @return array<int,array<string,mixed>> Dieselben Zeilen, ergänzt um
	 *         statistikUrl und letzterText
	 */
	private function bereiteUebersichtAuf(array $arrZeilen): array
	{
		$strDatumsformat = Config::get('datimFormat');

		foreach ($arrZeilen as $intPos => $arrZeile)
		{
			$arrZeilen[$intPos]['statistikUrl'] = Backend::addToUrl('key=statistik&amp;id=' . (int) $arrZeile['id']);
			$arrZeilen[$intPos]['letzterText'] = null !== $arrZeile['letzter']
				? Date::parse($strDatumsformat, $arrZeile['letzter'])
				: '';
		}

		return $arrZeilen;
	}

	/**
	 * Füllt das Template mit der Auswertung eines einzelnen Links.
	 *
	 * Gibt es zu der ID keinen Datensatz, bleibt die Ansicht leer und das
	 * Template zeigt stattdessen einen Hinweis; ein Abbruch wäre hier
	 * unangemessen, weil der Benutzer nur eine veraltete Adresse geöffnet hat.
	 *
	 * @param BackendTemplate $objTemplate   Das zu füllende Template
	 * @param Auswertung      $objAuswertung Liefert die Zahlen
	 * @param int             $intId         ID des Links aus tl_linktracker
	 *
	 * @return void
	 */
	private function fuelleEinzelansicht(BackendTemplate $objTemplate, Auswertung $objAuswertung, int $intId): void
	{
		$arrLink = $objAuswertung->getLink($intId);

		$objTemplate->modus = 'einzeln';
		$objTemplate->link = $arrLink;

		if (null === $arrLink)
		{
			return;
		}

		$objTemplate->kennzahlen = $objAuswertung->getKennzahlen($intId);
		$objTemplate->diagramm = $objAuswertung->getTagesverlauf($intId);
		$objTemplate->monate = $this->beschrifteMonate($objAuswertung->getMonate($intId));
		$objTemplate->browser = $this->benenneKennungen($objAuswertung->getBrowser($intId));
	}

	/**
	 * Ergänzt die Monatswerte um eine lesbare Beschriftung.
	 *
	 * Die Auswertung liefert den Monat in der Form JJJJ-MM; die Übersetzung in
	 * „August 2026" gehört hierher, weil sie von der eingestellten Sprache
	 * abhängt.
	 *
	 * @param array<int,array{monat:string,anzahl:int}> $arrMonate Die Rohwerte
	 *
	 * @return array<int,array{monat:string,anzahl:int,beschriftung:string}>
	 */
	private function beschrifteMonate(array $arrMonate): array
	{
		foreach ($arrMonate as $intPos => $arrMonat)
		{
			$arrMonate[$intPos]['beschriftung'] = Date::parse('F Y', (int) strtotime($arrMonat['monat'] . '-01'));
		}

		return $arrMonate;
	}

	/**
	 * Ersetzt eine leere Browserkennung durch einen lesbaren Hinweis.
	 *
	 * Aufrufer ohne Kennung gibt es tatsächlich; in der Tabelle stünde sonst
	 * eine leere Zelle, die wie ein Fehler aussieht.
	 *
	 * @param array<int,array{browser:string,anzahl:int,anteil:float}> $arrBrowser Die Rohwerte
	 *
	 * @return array<int,array{browser:string,anzahl:int,anteil:float}>
	 */
	private function benenneKennungen(array $arrBrowser): array
	{
		foreach ($arrBrowser as $intPos => $arrEintrag)
		{
			if ('' === $arrEintrag['browser'])
			{
				$arrBrowser[$intPos]['browser'] = $GLOBALS['TL_LANG']['tl_linktracker']['ohneKennung'] ?? 'ohne Kennung';
			}
		}

		return $arrBrowser;
	}

	/**
	 * Baut die Adresse für die Schaltfläche „Zurück".
	 *
	 * Sie führt zur Listenansicht des Backend-Moduls. Der Weg über den Router
	 * ist dem Auslassen von "&key=statistik" aus der aktuellen Adresse
	 * vorzuziehen, weil er auch dann stimmt, wenn das Modul unmittelbar
	 * aufgerufen wurde.
	 *
	 * @return string Die vollständige Backend-Adresse der Listenansicht
	 */
	private function getBackUrl(): string
	{
		return System::getContainer()->get('router')->generate(
			'contao_backend',
			array('do' => 'linktracker')
		);
	}
}
