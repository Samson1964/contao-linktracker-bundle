<?php

declare(strict_types=1);

/**
 * Konfiguration des Linktrackers.
 *
 * Der frühere Kopf "if (!defined('TL_ROOT')) die(...)" ist entfallen: Die
 * Konstante gibt es unter Contao 5 nicht mehr, die Datei hätte sich dort also
 * kommentarlos selbst beendet und das Backend-Modul wäre spurlos verschwunden.
 *
 * Auch der Hook replaceInsertTags wird hier nicht mehr eingetragen; er hängt
 * jetzt als Dienst-Tag an Schachbulle\ContaoLinktrackerBundle\Tags\Linktracker.
 */

/**
 * Backend-Modul an zweiter Stelle im Bereich "Inhalte" einhängen.
 *
 * Die Contao-Hilfsfunktion array_insert() gibt es unter Contao 5 nicht mehr
 * (das gesamte Verzeichnis mit den Hilfsfunktionen ist dort entfallen), deshalb
 * wird die Position hier von Hand gesetzt. array_slice() mit erhaltenen
 * Schlüsseln bewahrt dabei die Namen der übrigen Module.
 */
$GLOBALS['BE_MOD']['content'] = array_merge(
	array_slice($GLOBALS['BE_MOD']['content'], 0, 1, true),
	array(
		'linktracker' => array(
			'tables'    => array('tl_linktracker', 'tl_linktracker_items'),
			'statistik' => array('Schachbulle\ContaoLinktrackerBundle\Modules\Statistik', 'go'),
		),
	),
	array_slice($GLOBALS['BE_MOD']['content'], 1, null, true)
);
