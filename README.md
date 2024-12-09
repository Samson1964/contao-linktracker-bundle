# Linktracker

**Frank Hoppe**

## Anwendung im Frontend

Um einen definierten Link �ber den Linktracker auszuwerten, mu� im href-Tag stehen:

````
bundles/contaolinktracker/go.php?id=#
````
F�r das # mu� die entsprechende ID eingetragen werden.

### Verwendung mittels Inserttag

Standardm��ig gibt der Linktracker die URL zur�ck, hier f�r die ID 32:
````
<a href="{{linktracker::32}}">Link</a>
````

Mit dem folgenden Beispiel wird eine transparente 1x1-Grafik zur�ckgegeben, die mit der ID 32 verkn�pft ist. Hiermit k�nnen Abrufe der Grafik (via ID 32) mitgez�hlt werden.
````
{{linktracker::32::image}}
````
