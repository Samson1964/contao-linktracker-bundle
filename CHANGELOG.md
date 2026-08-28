# Linktracker Changelog

## Version 2.0.0 (2026-08-28)

Diese Fassung läuft unter Contao 4.13 **und** Contao 5 mit PHP 8.1 bis 8.4.
Beide Fassungen wurden gegen die echten Quellen von Contao 4.13.58 und 5.7.7
mit PHP 8.4.24 geprüft.

* Change: Die Zählung läuft nicht mehr über die Datei `src/Resources/public/go.php`, sondern über einen Symfony-Controller mit eigener Route. Die alte Datei band `system/initialize.php` ein und setzte die Konstanten `TL_MODE` und `TL_ROOT` voraus — alles drei gibt es unter Contao 5 nicht mehr
* Add: Neue Adresse `/linktracker/{id}`; die alte Adresse `bundles/contaolinktracker/go.php?id=…` wird weiterhin bedient, damit bereits verschickte Links nicht ins Leere laufen. Wo Contao die Bundle-Dateien kopiert statt verlinkt, ist die verwaiste `public/bundles/contaolinktracker/go.php` nach dem Update von Hand zu löschen (siehe README)
* Add: Tests für den Controller und das Insert-Tag unter `tests/`
* Add: Abschnitt „Einbindung" in der Eingabemaske. Er zeigt die beiden Insert-Tags und die unmittelbare Adresse, jeweils bereits mit der ID des Datensatzes, zum Kopieren mit einem Klick
* Fix: Der Kopf `if (!defined('TL_ROOT')) die(...)` in der `config.php` hätte die Datei unter Contao 5 kommentarlos beendet, das Backend-Modul wäre spurlos verschwunden
* Fix: `array_insert()` beim Einhängen des Backend-Moduls ersetzt — die Contao-Hilfsfunktionen gibt es unter Contao 5 nicht mehr
* Fix: `'dataContainer' => 'Table'` durch den vollständigen Klassennamen ersetzt; der Kurzname ist unter Contao 5 entfallen
* Fix: Die DCA-Klassen leiteten von `Backend` ohne Namensraum ab und riefen `specialchars()` und `Image::` auf. Contao 5 registriert keine globalen Klassenaliasse mehr und kennt `specialchars()` nicht
* Fix: `System::log()` durch den Contao-Fehlerkanal ersetzt, ebenfalls unter Contao 5 entfallen
* Change: Der Umschalter „Veröffentlicht" kommt jetzt vom Contao-Kern statt von `codefog/contao-haste`; die Abhängigkeit ist damit entfallen
* Change: Der Hook `replaceInsertTags` wird als Dienst-Tag angemeldet statt in der `config.php`; die Adresse im Insert-Tag stammt jetzt vom Router und stimmt deshalb auch in einem Unterverzeichnis
* Fix: Die Beschriftungen der Schaltflächen in beiden Listenansichten fehlten vollständig, das Backend zeigte die nackten Schlüssel „edit", „copy" und so fort
* Fix: Die Spalte „Aufrufe" las bei jedem Seitenaufbau sämtliche Klickdatensätze in den Speicher, und das je Zeile fünfmal — jetzt `COUNT(*)`
* Change: Die Statistikseite ist weiterhin nicht umgesetzt, zeigt aber einen Hinweis statt einer leeren Seite
* Change: Der Zeitpunkt in der Klickliste folgt jetzt dem im Backend eingestellten Datumsformat
* Change: Unbekannte oder unveröffentlichte Link-IDs beantwortet der Tracker mit 404 statt mit 501 und einer Ausnahme
* Change: `declare(strict_types=1)` in allen PHP-Dateien, deutsche Kommentarblöcke an allen Funktionen

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
