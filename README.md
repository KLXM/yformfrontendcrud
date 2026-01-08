# oneFileYFormCRUD

Frontend-CRUD für REDAXO YForm Tabellen mit flexiblen CSS-Frameworks
(Kurzanleitung für Beginners/Designers zuunterst)

## YFormDataListRenderer Class

### Installation

Die `YFormDataListRenderer`-Class in den Lib-Ordner des Projekt-AddOns kopieren und bei Bedarf gestalterisch anpassen.

### Überblick

Die `YFormDataListRenderer`-Class ist ein flexibles Tool, um Datensätze aus Tabellen, die mit YForm in REDAXO verwaltet werden, anzuzeigen und zu verwalten. Diese Class eignet sich für eine Vielzahl von Anwendungsfällen, wie z. B. Blog-Artikel, News-Einträge, Produkte oder andere Datentypen.

**NEU: Fragment/Template-System für CSS-Frameworks**
Die Class unterstützt jetzt verschiedene CSS-Frameworks über ein flexibles Fragment-System:
- **UIKit** (Standard)
- **Bootstrap 3**
- **Bootstrap 4** 
- **Bootstrap 5**
- **Custom CSS** (eigene Framework-Definition)

Zusätzlich können verschiedene Anzeigemodi verwendet werden:
- **Table** (Tabellenansicht)
- **Cards** (Kartenansicht)
- **List** (Listenansicht)

### Framework-Konfiguration

#### Framework setzen
```php
// UIKit (Standard)
$renderer->setFramework('uikit');

// Bootstrap 5
$renderer->setFramework('bootstrap5');

// Bootstrap 4
$renderer->setFramework('bootstrap4');

// Bootstrap 3
$renderer->setFramework('bootstrap3');

// Custom CSS
$renderer->setFramework('custom');
```

#### Anzeigemodus konfigurieren
```php
// Tabellenansicht (Standard)
$renderer->setDisplayMode('table');

// Kartenansicht
$renderer->setDisplayMode('cards');

// Listenansicht
$renderer->setDisplayMode('list');
```

#### Aktions-Buttons ein-/ausblenden
```php
// Aktionen anzeigen (Standard)
$renderer->setShowActions(true);

// Aktionen ausblenden (nur Anzeige, keine Bearbeitung)
$renderer->setShowActions(false);
```

#### Individuelle CSS-Klassen anpassen
```php
// Einzelne CSS-Klasse überschreiben
$renderer->setCssClass('bootstrap5', 'button_primary', 'btn btn-success btn-lg');

// Komplettes CSS-Template definieren
$customTemplate = [
    'alert_success' => 'message success',
    'button_primary' => 'button primary large',
    'table' => 'data-table zebra hoverable',
    // ... weitere Definitionen
];
$renderer->setCssTemplate('myframework', $customTemplate);
```

### Wie es funktioniert

#### 1. Renderer erstellen

Erstellen Sie eine Instanz der `YFormDataListRenderer`-Class:

```php
$renderer = new YFormDataListRenderer();
```

#### 2. Tabelle festlegen

Legen Sie die Tabelle fest, aus der die Datensätze abgerufen werden sollen. Zum Beispiel:

```php
$renderer->setTableName('rex_blog_articles');
```

#### 3. Felder definieren

Bestimmen Sie die Felder, die in der Liste angezeigt werden sollen:

```php
$renderer->setFields(['title', 'date', 'status', 'author']);
```

#### 4. Bearbeitungslink-Muster festlegen

Definieren Sie das URL-Muster für den Bearbeitungslink. `{id}` ist dabei der Platzhalter für die Datensatz-ID:

```php
$renderer->setEditLinkPattern(rex_getUrl('', '', ['func' => 'edit', 'id' => '{id}']));
```

#### 5. Standard-Sortieroptionen festlegen

Legen Sie das Standardfeld und die Standardreihenfolge für die Sortierung fest:

```php
$renderer->setDefaultSortField('date');
$renderer->setDefaultSortOrder('DESC');
```

#### 6. Bedingungen für die Datenauswahl hinzufügen

Fügen Sie Bedingungen für die Auswahl der Datensätze hinzu. Zum Beispiel:

```php
$renderer->addWhereCondition('status', '=', 1);
```

#### 7. Übersetzungen einrichten (optional)

Definieren Sie Übersetzungen für bestimmte Feldwerte:

```php
$renderer->setTranslations([
    'status' => [
        '1' => '<span style="color: green; font-weight: bold;">Online</span>',
        '0' => '<span style="color: red;">Offline</span>',
    ]
]);
```

#### 8. Standardstatus für neue und bearbeitete Datensätze (optional)

Legen Sie fest, welcher Status beim Erstellen oder Bearbeiten von Datensätzen gesetzt werden soll:

```php
$renderer->setNewStatus(0);  // Neuer Datensatz ist standardmäßig "Offline"
$renderer->setEditStatus(1); // Bearbeiteter Datensatz wird auf "Online" gesetzt
```

#### 9. Benutzerfeld festlegen (optional)

Speichern Sie den aktuellen Benutzer als Autor:

```php
$renderer->setUserField('author');
```

#### 10. Formulartemplate anpassen (optional)

Legen Sie ein benutzerdefiniertes YForm-Template fest:

```php
$renderer->setFormYtemplate('custom_template');
```

#### 11. Formatierungs-Callbacks hinzufügen

Fügen Sie Formatierungs-Callbacks für bestimmte Felder hinzu:

```php
$renderer->setFormatCallback('title', function($value) {
    return '<strong>' . htmlspecialchars($value) . '</strong>';
});

$renderer->setFormatCallback('date', function($value) {
    return date('d.m.Y', strtotime($value));
});
```

#### 12. Identifikationsfeld festlegen (optional)

Falls nötig, können Sie ein Identifikationsfeld und dessen Wert setzen:

```php
$renderer->setIdentId('user_id', rex_ycom_auth::getUser()->getId());
```
user_id ist nur ein Beispiel es kann jedes Feld genommen werden das ein Int annimmt. 
Hier wird die ycom user id gespeichert (ideal für eine Relation) um den Ersteller zu tracken.  



#### 13. Liste rendern

Zum Schluss rendern Sie die Liste und geben sie aus:

```php
if (rex::isFrontend() && rex_ycom_auth::getUser() !== null) {
    echo $renderer->render();
}
```

### Vollständiges Beispiel

Hier ein vollständiger Beispielcode, der alle oben genannten Methoden verwendet:

```php
<?php

$renderer = new YFormDataListRenderer();

// Tabelle festlegen
$renderer->setTableName('rex_blog_articles');

// Felder definieren
$renderer->setFields(['title', 'date', 'status', 'author']);

// Bearbeitungslink-Muster festlegen
$renderer->setEditLinkPattern(rex_getUrl('', '', ['func' => 'edit', 'id' => '{id}']));

// Standard-Sortieroptionen festlegen
$renderer->setDefaultSortField('date');
$renderer->setDefaultSortOrder('DESC');

// Bedingungen für die Datenauswahl hinzufügen
$renderer->addWhereCondition('status', '=', 1);

// NEU: Framework konfigurieren (Bootstrap 5 statt UIKit)
$renderer->setFramework('bootstrap5');

// NEU: Anzeigemodus auf Karten setzen
$renderer->setDisplayMode('cards');

// Übersetzungen einrichten
$renderer->setTranslations([
    'status' => [
        '1' => '<span style="color: green; font-weight: bold;">Online</span>',
        '0' => '<span style="color: red;">Offline</span>',
    ]
]);

// Standardstatus für neue und bearbeitete Datensätze festlegen
$renderer->setNewStatus(0);
$renderer->setEditStatus(1);

// Benutzerfeld festlegen (optional)
$renderer->setUserField('author');

// Optional: Formulartemplate anpassen
// $renderer->setFormYtemplate('custom_template');

// Callbacks für die Formatierung
$renderer->setFormatCallback('title', function($value) {
    return '<strong>' . htmlspecialchars($value) . '</strong>';
});

$renderer->setFormatCallback('date', function($value) {
    return date('d.m.Y', strtotime($value));
});

// Identifikationsfeld festlegen (optional), bitte achte darauf dass das Feld exitiert
$renderer->setIdentId('user_id', rex_ycom_auth::getUser()->getId());

// Liste rendern und ausgeben
if (rex::isFrontend() && rex_ycom_auth::getUser() !== null) {
    echo $renderer->render();
}

?>
```

### Framework-spezifische Beispiele

#### Bootstrap 5 mit Kartenansicht
```php
$renderer = new YFormDataListRenderer();
$renderer->setTableName('rex_products');
$renderer->setFields(['name', 'price', 'category']);
$renderer->setFramework('bootstrap5');
$renderer->setDisplayMode('cards');
echo $renderer->render();
```

#### Bootstrap 4 mit angepassten CSS-Klassen
```php
$renderer = new YFormDataListRenderer();
$renderer->setTableName('rex_news');
$renderer->setFields(['title', 'teaser', 'createdate']);
$renderer->setFramework('bootstrap4');
$renderer->setCssClass('bootstrap4', 'button_primary', 'btn btn-success btn-lg');
$renderer->setCssClass('bootstrap4', 'table', 'table table-dark table-striped');
echo $renderer->render();
```

#### Nur-Anzeige Modus (ohne Edit-Funktionen)
```php
$renderer = new YFormDataListRenderer();
$renderer->setTableName('rex_events');
$renderer->setFields(['title', 'date', 'location']);
$renderer->setFramework('bootstrap5');
$renderer->setDisplayMode('list');
$renderer->setShowActions(false); // Keine Bearbeiten/Löschen-Buttons
echo $renderer->render();
```

#### Eigenes CSS-Framework definieren
```php
$renderer = new YFormDataListRenderer();
$renderer->setTableName('rex_gallery');
$renderer->setFields(['title', 'image', 'description']);
$renderer->setFramework('custom');

// Eigene CSS-Klassen definieren
$customTemplate = [
    'alert_success' => 'message success',
    'alert_danger' => 'message error',
    'button_primary' => 'button primary large',
    'button_default' => 'button secondary',
    'table' => 'data-table zebra hoverable',
    'input' => 'form-field',
    // ... weitere CSS-Definitionen
];
$renderer->setCssTemplate('custom', $customTemplate);
echo $renderer->render();
```

### JavaScript einbinden

Um die Benutzerfreundlichkeit zu verbessern, können Sie optional JavaScript für Funktionen wie die Live-Suche oder einen Countdown-Timer hinzufügen.

```javascript
document.addEventListener("DOMContentLoaded", function() {
    var liveSearchElement = document.getElementById("live-search");
    if (liveSearchElement) {
        liveSearchElement.addEventListener("keyup", function() {
            var searchValue = this.value.toLowerCase();
            var tableRows = document.getElementById("data-table").getElementsByTagName("tr");

            for (var i = 0; i < tableRows.length; i++) {
                var rowText = tableRows[i].textContent.toLowerCase();
                if (rowText.indexOf(searchValue) > -1) {
                    tableRows[i].style.display = "";
                } else {
                    tableRows[i].style.display = "none";
                }
            }
        });
    }

    var countdownElement = document.getElementById("countdown");
    if (countdownElement) {
        var countdown = parseInt(countdownElement.textContent, 10);
        var countdownInterval = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = window.location.pathname;
            }
        }, 1000);
    }
});
```


# Kurzanleitung für Beginner/Webdesigner:


## YForm Data List Renderer – Installationsanleitung

Diese Anleitung beschreibt die Einrichtung des **YFormDataListRenderer** für REDAXO 5 mit YForm.

---

### 1) Voraussetzungen

- AddOn **yform** aktiviert
- YForm-Tabelle vorhanden (z. B. `rex_meinetabelle`)
- Tabelle hat ein Feld `status` (checkbox, Default 1)

---

### 2) Class einbinden

#### Datei kopieren
`YFormDataListRenderer.php` kopieren nach:
```
/redaxo/src/addons/project/lib/
```

#### Boot-Datei anpassen
Folgenden Code in `/redaxo/src/addons/project/boot.php` einfügen:

```php
require_once __DIR__ . '/lib/YFormDataListRenderer.php';
```

#### AddOn re-installieren
Im Backend unter **Addons > Project**: auf **re-installieren** klicken

---

### 3) Modul-Ausgabe (Minimal)

```php
$renderer = new YFormDataListRenderer();
$renderer->setTableName('rex_meinetabelle');
$renderer->setNewStatus(1);
$renderer->setFields(['vorname','nachname','spendenbetrag']);
$renderer->setFramework('bootstrap5');
$renderer->setDisplayMode('table');

echo $renderer->render();
```

---

### 4) Template – Assets

#### Im `<head>` einfügen

Beispiel für Bootstrap 5:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
```

#### Vor `</body>` einfügen

Beispiel für Bootstrap 5:

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
```

---

### 5) Suche aktivieren (Live-Filter)

Vor `</body>` ins Template einfügen:

```html
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('live-search');
  const tbody = document.getElementById('data-table');
  if (!input || !tbody) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    tbody.querySelectorAll('tr').forEach(tr =>
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none'
    );
  });
});
</script>
```

---

### 6) Häufige Stolperfallen

- ❌ **Nur bootstrap-grid.css geladen** → Buttons & Tabellen bleiben unstyled
- ❌ **Kein status-Feld vorhanden** → Insert speichert nicht
- ❌ **Bootstrap-Icons nicht geladen** → Aktionen unsichtbar
- ❌ **Suche ohne JS** → Eingabe tut nichts

---

### Support

Bei Problemen prüfen:
1. Ist YForm aktiviert?
2. Existiert die Tabelle mit `status`-Feld?
3. Sind alle CSS/JS-Assets im Template eingebunden?
4. Wurde das Project-AddOn re-installiert?

