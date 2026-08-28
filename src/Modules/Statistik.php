<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Modules;

use Contao\DataContainer;

/**
 * Platzhalter für die spätere Auswertung im Backend.
 *
 * Das Modul ist über den Schlüssel "statistik" im Backend-Modul angemeldet und
 * wird über die gleichnamige Schaltfläche in der Listenansicht aufgerufen. Eine
 * Auswertung mit Tabellen und Diagrammen ist vorgesehen, aber noch nicht
 * umgesetzt; bis dahin erscheint an dieser Stelle ein Hinweis statt einer
 * leeren Seite.
 */
class Statistik
{
	/**
	 * Erzeugt die Ausgabe der Statistikseite.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container. Contao reicht
	 *                               ihn herein; sobald die Auswertung umgesetzt
	 *                               ist, liefert er über id den Datensatz, um den
	 *                               es geht
	 *
	 * @return string Der HTML-Block, der im Hauptbereich des Backends erscheint
	 */
	public function go(DataContainer|null $dc = null): string
	{
		return '<p class="tl_empty">Die Auswertung ist noch nicht umgesetzt. '
			. 'Die Aufrufe je Link stehen bis dahin in der Spalte „Aufrufe“ der Listenansicht '
			. 'und im Einzelnen über die Schaltfläche „Aufrufe anzeigen“.</p>';
	}
}
