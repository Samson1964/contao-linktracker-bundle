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
`public/bundles/contaolinktracker/go.php` liegen. Das Update löscht sie nicht,
denn Contao kopiert nur, es räumt nicht auf. Solange sie da ist, liefert der
Webserver sie unmittelbar aus, die Route kommt gar nicht zum Zug, und unter
Contao 5 bricht die Datei mit einem Fehler ab.

Am einfachsten ist es, sie einmal von Hand zu löschen. Wer keinen Dateizugriff
hat, kann sie stattdessen am Webserver vorbeileiten.

### Apache

In `public/.htaccess` — die folgende Zeile **vor** den Block „If the requested
filename exists" setzen, sonst liefert Apache die Datei vorher aus. Die
Umgebungsvariable `BASE` wird weiter oben in derselben Datei gesetzt und sorgt
dafür, dass die Regel auch in einem Unterverzeichnis funktioniert:

```apache
    # Linktracker: Die frühere go.php wurde durch eine Route ersetzt. Diese Zeile
    # schickt den Aufruf am Dateisystem vorbei an den Front Controller, auch wenn
    # noch eine verwaiste Kopie der Datei herumliegt.
    RewriteRule ^bundles/contaolinktracker/go\.php$ %{ENV:BASE}/index.php [L]

    # If the requested filename exists, simply serve it.
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]
```

Der Abfrageteil der Adresse (`?id=32`) bleibt dabei erhalten: Apache hängt ihn
an, solange das Ziel selbst keinen enthält.

### nginx

Der folgende Block gehört in den `server`-Abschnitt. Eine `location =` hat
Vorrang vor der Regel, die sonst alle `.php`-Dateien an PHP-FPM weiterreicht:

```nginx
location = /bundles/contaolinktracker/go.php {
    rewrite ^ /index.php last;
}
```

## Backend

Die Listenansicht zeigt in der Spalte **Aufrufe** die Gesamtzahl der Klicks und
dahinter in Klammern die Zahlen für heute und die vier vorangegangenen Tage. Die
Schaltfläche *Aufrufe anzeigen* öffnet die einzelnen Klicks mit Zeitpunkt,
IP-Adresse und Browserkennung.

Aufrufe von Suchmaschinen und anderen Robotern werden anhand der Browserkennung
erkannt und nicht gezählt; weitergeleitet wird trotzdem.

### Statistik

Die Schaltfläche mit dem Zählersymbol öffnet die Auswertung eines Links:

* die Kennzahlen gesamt, heute, letzte 7 und letzte 30 Tage
* ein Balkendiagramm der letzten 30 Tage, mit dem stärksten Tag als vollem
  Ausschlag; ein Zeigen auf einen Balken nennt Datum und Anzahl
* die Aufrufe je Monat, vom jüngsten an
* die zehn häufigsten Browserkennungen mit ihrem Anteil

Wird die Statistik ohne einen bestimmten Datensatz aufgerufen, erscheint eine
Übersicht über alle Links, nach Häufigkeit sortiert. Links ohne einen einzigen
Aufruf stehen ebenfalls darin — gerade sie sind ja oft der Anlass nachzusehen.

Die Zeiträume beginnen um Mitternacht: Ein Klick von heute früh zählt zu
„heute", auch wenn er mehr als 24 Stunden zurückliegt.

### Wird die alte Adresse noch benutzt?

Seit Version 2.1.0 hält der Tracker fest, ob ein Klick über die alte Adresse
`bundles/contaolinktracker/go.php` hereinkam. Die Statistik weist die Zahl in
beiden Ansichten aus, und in der Klickliste lässt sie sich als Filter setzen.

Steht dort über mehrere Wochen eine Null, werden weder die Route
`linktracker_go_legacy` noch die oben beschriebene Rewrite-Regel noch gebraucht.
Zu beachten: Klicks aus der Zeit vor 2.1.0 tragen den Vorgabewert und zählen
damit als *nicht* über die alte Adresse, obwohl sie zum grossen Teil von dort
stammen. Aussagekräftig ist deshalb erst, was nach dem Update hinzukommt.
