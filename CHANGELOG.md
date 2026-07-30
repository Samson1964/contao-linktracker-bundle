# Linktracker Changelog

## Version 1.2.7 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 1.2.6 (2026-07-30)

* Fix: Ein Klick mit langem User-Agent brach mit „SQLSTATE[22001]: Data too long for column 'browser'" ab und lieferte Status 500. Der User-Agent wird jetzt auf die Spaltenbreite von 255 Zeichen gekürzt; verbreitete Kennungen sind über 300 Zeichen lang
* Fix: Fehlte der User-Agent-Header ganz, meldete PHP 8 „Undefined array key HTTP_USER_AGENT". Gleiches für REMOTE_ADDR
* Fix: Die Prüfung auf eine nicht vorhandene Link-ID konnte nie zutreffen, weil `execute()` auch ohne Treffer ein Result-Objekt liefert. Unbekannte oder unveröffentlichte IDs liefen dadurch mit leerer URL in die Weiterleitung, statt wie vorgesehen abgebrochen zu werden — geprüft wird jetzt `numRows`
* Change: Kommentarblöcke für `run()` und `is_bot()` nachgetragen

## Version 1.2.5 (2026-07-29)

* Fix: Warning: Undefined array key "deleteConfirm" bei contao:migrate -> Lesezugriffe auf $GLOBALS['TL_LANG'] in den DCA-Dateien mit `?? null` bzw. `?? array()` abgesichert, da der DcaLoader die Sprachdateien noch nicht geladen hat

## Version 1.2.4 (2024-12-10)

* Fix: Inserttags in Links werden nicht ersetzt -> \Controller::replaceInsertTags hinzugefügt

## Version 1.2.3 (2024-12-10)

* Fix: Hostausgabe fehlt in Inserttag linktracker -> \Environment::get('url') hinzugefügt

## Version 1.2.2 (2024-12-10)

* Fix: go.php liefert immer nur noch die Grafik aus (unter PHP 7 und Contao 4.13/4.9 - unter PHP 8 scheint alles okay) -> evtl. war die Umwandlung von option mit intval verantwortlich

## Version 1.2.1 (2024-12-10)

* Change: title komplett alphabetisch sortieren, statt nur erster Buchstabe
* Add: Modul Statistik angelegt, für die spätere Ausgabe von Tabellen und Diagrammen im Backend
* Fix: Normale Links werden als Grafik ausgeliefert: "if(isset($option) == 'image')" ersetzt durch "if(isset($option) && $option == 'image')"

## Version 1.2.0 (2024-12-09)

* Add: Insert-Tag linktracker
* Change: URL nicht mehr als Pflichtfeld, um Bildlinks zu ermöglichen

## Version 1.1.0 (2024-04-18)

* Add: codefog/contao-haste
* Change: Haste-Toggler statt des normalen Togglers
* Add: Kompatibilität PHP 8

## Version 1.0.1 (2021-11-30)

* Add: Funktion is_bot zur Bot-Erkennung
* Add: Statistikanzeige gesamt und letzte 4 Tage in Übersicht
* Add: Übersetzungen tl_linktracker_items

## Version 1.0.0 (2021-11-30)

* Ausbau der Version bis zur Einsatzreife

## Version 0.0.1 (2021-11-29)

* Erste Alphaversion
