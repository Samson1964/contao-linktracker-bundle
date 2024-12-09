# Linktracker

**Frank Hoppe**

## Anwendung im Frontend

Um einen definierten Link über den Linktracker auszuwerten, muß im href-Tag stehen:

````
bundles/contaolinktracker/go.php?id=#
````
Für das # muß die entsprechende ID eingetragen werden.

### Verwendung mittels Inserttag

Standardmäßig gibt der Linktracker die URL zurück, hier für die ID 32:
````
<a href="{{linktracker::32}}">Link</a>
````

Mit dem folgenden Beispiel wird eine transparente 1x1-Grafik zurückgegeben, die mit der ID 32 verknüpft ist. Hiermit können Abrufe der Grafik (via ID 32) mitgezählt werden.
````
{{linktracker::32::image}}
````
