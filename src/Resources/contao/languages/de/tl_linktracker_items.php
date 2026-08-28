<?php

declare(strict_types=1);

/**
 * Beschriftungen der Tabelle tl_linktracker_items
 */

// Listenansicht
$GLOBALS['TL_LANG']['tl_linktracker_items']['new'] = array('Neuer Aufruf', 'Neuen Aufruf eintragen');

// Schaltflächen der Listenansicht. Sie fehlten bisher, weshalb im Backend die
// nackten Schlüssel ("edit", "copy" …) als Beschriftung erschienen.
$GLOBALS['TL_LANG']['tl_linktracker_items']['edit'] = array('Aufruf bearbeiten', 'Den Aufruf ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_linktracker_items']['copy'] = array('Aufruf duplizieren', 'Den Aufruf ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_linktracker_items']['cut'] = array('Aufruf verschieben', 'Den Aufruf ID %s verschieben');
$GLOBALS['TL_LANG']['tl_linktracker_items']['delete'] = array('Aufruf löschen', 'Den Aufruf ID %s löschen');
$GLOBALS['TL_LANG']['tl_linktracker_items']['toggle'] = array('Aufruf veröffentlichen', 'Den Aufruf ID %s veröffentlichen oder verbergen');
$GLOBALS['TL_LANG']['tl_linktracker_items']['show'] = array('Einzelheiten anzeigen', 'Die Einzelheiten des Aufrufs ID %s anzeigen');

// Eingabemaske
$GLOBALS['TL_LANG']['tl_linktracker_items']['tracker_legend'] = 'Aufrufdetails';
$GLOBALS['TL_LANG']['tl_linktracker_items']['clickTime'] = array('Aufrufzeit', 'Zeitpunkt des Aufrufs');
$GLOBALS['TL_LANG']['tl_linktracker_items']['ip'] = array('IP-Adresse', 'IP-Adresse des Besuchers');
$GLOBALS['TL_LANG']['tl_linktracker_items']['browser'] = array('Browserkennung', 'Browserkennung des Besuchers');

$GLOBALS['TL_LANG']['tl_linktracker_items']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_linktracker_items']['published'] = array('Veröffentlicht', 'Aufruf veröffentlicht');
