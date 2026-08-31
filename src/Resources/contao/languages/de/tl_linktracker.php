<?php

declare(strict_types=1);

/**
 * Beschriftungen der Tabelle tl_linktracker
 */

// Listenansicht
$GLOBALS['TL_LANG']['tl_linktracker']['new'] = array('Neue URL', 'Neue URL eintragen');

// Schaltflächen der Listenansicht. Sie fehlten bisher, weshalb im Backend die
// nackten Schlüssel ("edit", "copy" …) als Beschriftung erschienen.
$GLOBALS['TL_LANG']['tl_linktracker']['edit'] = array('Aufrufe anzeigen', 'Die Aufrufe der URL ID %s anzeigen');
$GLOBALS['TL_LANG']['tl_linktracker']['editheader'] = array('URL bearbeiten', 'Die URL ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_linktracker']['copy'] = array('URL duplizieren', 'Die URL ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_linktracker']['delete'] = array('URL löschen', 'Die URL ID %s löschen');
$GLOBALS['TL_LANG']['tl_linktracker']['toggle'] = array('URL veröffentlichen', 'Die URL ID %s veröffentlichen oder verbergen');
$GLOBALS['TL_LANG']['tl_linktracker']['show'] = array('Einzelheiten anzeigen', 'Die Einzelheiten der URL ID %s anzeigen');
$GLOBALS['TL_LANG']['tl_linktracker']['statistik'] = array('Statistik', 'Die Statistik der URL ID %s anzeigen');

// Eingabemaske
$GLOBALS['TL_LANG']['tl_linktracker']['id'] = array('ID', 'ID des Datensatzes');

$GLOBALS['TL_LANG']['tl_linktracker']['title_legend'] = 'Titel und URL';
$GLOBALS['TL_LANG']['tl_linktracker']['title'] = array('Titel', 'Name/Bezeichnung der URL');
$GLOBALS['TL_LANG']['tl_linktracker']['url'] = array('URL', 'URL. Leerlassen, wenn nicht weitergeleitet werden soll.');
$GLOBALS['TL_LANG']['tl_linktracker']['hits'] = array('Aufrufe', 'Aufrufe');
$GLOBALS['TL_LANG']['tl_linktracker']['hitsHelp'] = 'Gesamt (heute und die letzten vier Tage)';

$GLOBALS['TL_LANG']['tl_linktracker']['einbindung_legend'] = 'Einbindung';
$GLOBALS['TL_LANG']['tl_linktracker']['einbindung'] = array('Einbindung', 'So lässt sich dieser Link verwenden');
$GLOBALS['TL_LANG']['tl_linktracker']['einbindungHelp'] = 'Die folgenden Zeilen enthalten bereits die ID dieses Datensatzes. Ein Klick in ein Feld markiert den ganzen Inhalt zum Kopieren.';
$GLOBALS['TL_LANG']['tl_linktracker']['einbindungLink'] = 'Als Verweis im Text- oder HTML-Element';
$GLOBALS['TL_LANG']['tl_linktracker']['einbindungBild'] = 'Als Zählpixel, etwa in einem Newsletter';
$GLOBALS['TL_LANG']['tl_linktracker']['einbindungUrl'] = 'Als unmittelbare Adresse, etwa in einer E-Mail';

$GLOBALS['TL_LANG']['tl_linktracker']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_linktracker']['published'] = array('Veröffentlicht', 'URL veröffentlicht');

// Statistikseite
$GLOBALS['TL_LANG']['tl_linktracker']['statGesamt'] = 'Aufrufe gesamt';
$GLOBALS['TL_LANG']['tl_linktracker']['statHeute'] = 'heute';
$GLOBALS['TL_LANG']['tl_linktracker']['stat7Tage'] = 'letzte 7 Tage';
$GLOBALS['TL_LANG']['tl_linktracker']['stat30Tage'] = 'letzte 30 Tage';
$GLOBALS['TL_LANG']['tl_linktracker']['statAlteAdresse'] = 'über die alte Adresse';
$GLOBALS['TL_LANG']['tl_linktracker']['statVerlauf'] = 'Aufrufe der letzten 30 Tage';
$GLOBALS['TL_LANG']['tl_linktracker']['statVerlaufSumme'] = 'Höchster Tageswert: %s. Im Zeitraum insgesamt: %s.';
$GLOBALS['TL_LANG']['tl_linktracker']['statKeineImZeitraum'] = 'In diesem Zeitraum wurde der Link nicht aufgerufen.';
$GLOBALS['TL_LANG']['tl_linktracker']['statMonate'] = 'Aufrufe je Monat';
$GLOBALS['TL_LANG']['tl_linktracker']['statMonat'] = 'Monat';
$GLOBALS['TL_LANG']['tl_linktracker']['statAnzahl'] = 'Aufrufe';
$GLOBALS['TL_LANG']['tl_linktracker']['statAnteil'] = 'Anteil';
$GLOBALS['TL_LANG']['tl_linktracker']['statBrowser'] = 'Häufigste Browserkennungen';
$GLOBALS['TL_LANG']['tl_linktracker']['statKennung'] = 'Kennung';
$GLOBALS['TL_LANG']['tl_linktracker']['statUebersicht'] = 'Aufrufe aller Links';
$GLOBALS['TL_LANG']['tl_linktracker']['statSumme'] = 'Insgesamt gezählt: %s Aufrufe.';
$GLOBALS['TL_LANG']['tl_linktracker']['statLink'] = 'Link';
$GLOBALS['TL_LANG']['tl_linktracker']['statLetzter'] = 'letzter Aufruf';
$GLOBALS['TL_LANG']['tl_linktracker']['statKeineLinks'] = 'Es ist noch kein Link angelegt.';
$GLOBALS['TL_LANG']['tl_linktracker']['statUnveroeffentlicht'] = 'nicht veröffentlicht';
$GLOBALS['TL_LANG']['tl_linktracker']['statistikUnbekannt'] = 'Zu dieser ID gibt es keinen Link.';
$GLOBALS['TL_LANG']['tl_linktracker']['ohneKennung'] = 'ohne Kennung';
