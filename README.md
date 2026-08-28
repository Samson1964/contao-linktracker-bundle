# Linktracker

**Frank Hoppe**

Zählt Klicks auf Links und wertet sie im Backend aus. Läuft unter Contao 4.13
und Contao 5 mit PHP 8.1 bis 8.4.

## Anwendung

Jeder Datensatz im Backend-Modul *Linktracker* bekommt eine ID. In der
Eingabemaske zeigt der Abschnitt **Einbindung** die drei möglichen Schreibweisen
bereits mit der ID des jeweiligen Datensatzes; ein Klick in eines der Felder
markiert den Inhalt zum Kopieren.

### Als Verweis

```html
<a href="{{linktracker::32}}">Link</a>
```

Das Insert-Tag liefert die vollständige Adresse. Wer darauf klickt, wird gezählt
und anschließend auf die hinterlegte URL weitergeleitet.

### Als Zählpixel

```
{{linktracker::32::image}}
```

Liefert ein `img`-Element mit einer transparenten 1×1-Grafik. Damit lassen sich
Abrufe zählen, bei denen es nichts zu klicken gibt — etwa in einem Newsletter.

### Als unmittelbare Adresse

```
https://example.org/linktracker/32
```

Für E-Mails und andere Stellen, an denen keine Insert-Tags ersetzt werden.

## Hinweis für bestehende Installationen

Bis Version 1.2.7 lief die Zählung über die Datei
`bundles/contaolinktracker/go.php`. Diese Datei gibt es nicht mehr: Sie band den
alten Contao-Einstiegspunkt `system/initialize.php` ein, den es unter Contao 5
nicht mehr gibt.

Die alte Adresse funktioniert trotzdem weiter — sie wird jetzt von einer Route
bedient. Bereits verschickte Links und bereits gepflegte Verweise müssen also
nicht angefasst werden. Für neue Verweise ist die kurze Form
`/linktracker/{id}` vorzuziehen.

Damit die Route greift, darf die alte Datei nicht mehr im öffentlichen
Verzeichnis liegen. Wo Contao die Bundle-Dateien verlinkt, erledigt sich das von
selbst. Wo sie stattdessen kopiert werden — das kommt bei Hosting ohne
Symlink-Rechte vor —, bleibt nach dem Update eine verwaiste Datei
`public/bundles/contaolinktracker/go.php` liegen. Sie ist zu löschen; unter
Contao 5 bricht sie sonst mit einem Fehler ab, statt den Aufruf durchzulassen.

## Backend

Die Listenansicht zeigt in der Spalte **Aufrufe** die Gesamtzahl der Klicks und
dahinter in Klammern die Zahlen für heute und die vier vorangegangenen Tage. Die
Schaltfläche *Aufrufe anzeigen* öffnet die einzelnen Klicks mit Zeitpunkt,
IP-Adresse und Browserkennung.

Aufrufe von Suchmaschinen und anderen Robotern werden anhand der Browserkennung
erkannt und nicht gezählt; weitergeleitet wird trotzdem.
