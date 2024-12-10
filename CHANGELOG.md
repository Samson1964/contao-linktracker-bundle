# Linktracker

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
