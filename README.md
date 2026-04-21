# yformfrontendcrud

Frontend-CRUD für REDAXO YForm-Tabellen mit flexiblen CSS-Frameworks, Extension Points und Format-Callbacks.

## Installation

Das AddOn über den REDAXO-Installer oder manuell in `redaxo/src/addons/yformfrontendcrud/` installieren. Anschließend im Backend unter **AddOns** aktivieren.

**Voraussetzungen:**
- REDAXO >= 5.15
- YForm >= 4.0

## Verwendung

```php
use FriendsOfRedaxo\YformFrontendCrud\YformFrontendCrud;

$renderer = new YformFrontendCrud();
$renderer->setTableName('rex_meinetabelle');
$renderer->setFields(['vorname', 'nachname', 'status']);
$renderer->setFramework('bootstrap5');
$renderer->setDisplayMode('table');

echo $renderer->render();
```

### Minimalbeispiel mit Bearbeiten-Funktion

Das `render()`-Verfahren verwaltet Liste, Formular und Löschen vollständig über URL-Parameter (`func=add`, `func=edit&id=1`, `func=delete&id=1`). Es muss nichts weiter konfiguriert werden:

```php
use FriendsOfRedaxo\YformFrontendCrud\YformFrontendCrud;

$renderer = new YformFrontendCrud();
$renderer->setTableName('rex_meinetabelle');
$renderer->setFields(['vorname', 'nachname', 'email']);

echo $renderer->render();
```

Die Klasse wechselt automatisch in den Bearbeitungs- bzw. Anlegen-Modus, sobald die entsprechenden URL-Parameter gesetzt sind – kein eigenes Routing nötig.

> **Hinweis:** Das YForm-Formular wird direkt aus der Tabellendefinition generiert. Das Feld `status` muss nicht in `setFields()` aufgeführt sein – es wird intern über `setNewStatus()` / `setEditStatus()` befüllt.

---

## Konfiguration

### Framework

```php
$renderer->setFramework('uikit');      // Standard
$renderer->setFramework('bootstrap5');
$renderer->setFramework('bootstrap4');
$renderer->setFramework('bootstrap3');
$renderer->setFramework('custom');
```

### Anzeigemodus

```php
$renderer->setDisplayMode('table');  // Standard
$renderer->setDisplayMode('cards');
$renderer->setDisplayMode('list');
```

### Aktions-Buttons

```php
$renderer->setShowActions(false); // nur Anzeige, keine CRUD-Aktionen
```

### Sortierung

```php
$renderer->setDefaultSortField('createdate');
$renderer->setDefaultSortOrder('DESC');
```

### WHERE-Filter

```php
$renderer->addWhereCondition('status', '=', 1);
```

### Status beim Speichern

```php
$renderer->setNewStatus(0);  // neuer Datensatz: offline
$renderer->setEditStatus(1); // bearbeiteter Datensatz: online
```

### Autor-Feld (ycom)

```php
$renderer->setUserField('author'); // speichert den Login-Namen des aktuellen ycom-Nutzers
```

### Identifikationsfeld (z. B. Besitzer-Relation)

```php
$renderer->setIdentId('user_id', rex_ycom_auth::getUser()->getId());
```

### YForm-Template

```php
$renderer->setFormYtemplate('bootstrap5,project');
```

### Format-Callbacks

```php
$renderer->setFormatCallback('title', function($value) {
    return '<strong>' . htmlspecialchars($value) . '</strong>';
});

$renderer->setFormatCallback('createdate', function($value) {
    return date('d.m.Y', strtotime($value));
});
```

### Übersetzungen

```php
$renderer->setTranslations([
    'status' => [
        '1' => '<span class="badge bg-success">Online</span>',
        '0' => '<span class="badge bg-danger">Offline</span>',
    ],
]);
```

### CSS-Klassen überschreiben

```php
// Einzelne Klasse
$renderer->setCssClass('bootstrap5', 'button_primary', 'btn btn-success btn-lg');

// Komplettes eigenes Template
$renderer->setCssTemplate('custom', [
    'alert_success'    => 'message success',
    'alert_danger'     => 'message error',
    'button_primary'   => 'button primary',
    'button_default'   => 'button secondary',
    'input'            => 'form-field',
    'table'            => 'data-table',
    'overflow_auto'    => 'overflow-auto',
    'margin_bottom'    => 'mb',
    'background_muted' => 'bg-muted',
    'padding'          => 'p',
    'grid_small'       => 'grid-small',
    'tooltip'          => 'tooltip',
    'icon'             => 'icon',
]);
```

### Texte anpassen

Alle deutschen UI-Texte sind überschreibbar – ideal für Mehrsprachigkeit oder individuelle Formulierungen.

```php
// Einzelnen Text ändern
$renderer->setLabel('btn_add', 'Neuen Artikel anlegen');
$renderer->setLabel('confirm_delete', 'Eintrag wirklich unwiderruflich löschen?');
$renderer->setLabel('search_placeholder', 'Suche ...');

// Mehrere Texte auf einmal
$renderer->setLabels([
    'btn_add'           => 'New entry',
    'btn_reset_sort'    => 'Reset sorting',
    'search_placeholder'=> 'Search entries...',
    'col_actions'       => 'Actions',
    'action_edit'       => 'Edit',
    'action_delete'     => 'Delete',
    'confirm_delete'    => 'Really delete?',
    'success_deleted'   => 'Entry deleted.',
    'success_saved_new' => 'Entry created.',
    'success_saved_edit'=> 'Entry updated.',
    'form_title_new'    => 'New entry',
    'form_title_edit'   => 'Edit entry',
]);
```

#### Alle Label-Schlüssel

| Schlüssel | Standard-Text |
|---|---|
| `error_invalid_params` | Ungültige Parameter übergeben. |
| `error_invalid_id` | Ungültige ID. |
| `error_not_found` | Fehler: Datensatz konnte nicht gefunden werden. |
| `error_delete_prevented` | Löschen wurde durch eine Erweiterung verhindert. |
| `error_delete_failed` | Fehler: Datensatz konnte nicht gelöscht werden. |
| `error_table_not_found` | Tabelle "%s" nicht gefunden. |
| `success_deleted` | Datensatz wurde erfolgreich gelöscht. |
| `success_saved_new` | Der Datensatz wurde erfolgreich erstellt. |
| `success_saved_edit` | Der Datensatz wurde erfolgreich aktualisiert. |
| `form_title_new` | Neuer Eintrag |
| `form_title_edit` | Datensatz aktualisieren |
| `btn_add` | Neuen Eintrag erstellen |
| `btn_reset_sort` | Standard-Sortierung wiederherstellen |
| `search_placeholder` | Nach Einträgen suchen... |
| `col_actions` | Aktionen |
| `action_edit` | Bearbeiten |
| `action_delete` | Löschen |
| `confirm_delete` | Wirklich löschen? |
| `redirect_countdown` | Sie werden in %s Sekunden zur Liste zurückgeleitet. |
| `redirect_link` | Klicken Sie hier |
| `redirect_back` | , um sofort zurückzukehren. |

### Weiterleitungs-Countdown

```php
// Sekunden nach Speichern/Löschen bis zur automatischen Weiterleitung (Standard: 5)
$renderer->setRedirectSeconds(3);
```

---

## Extension Points

Die Klasse bietet sieben Extension Points, über die andere AddOns oder Projektcode eingreifen können:

| Konstante | EP-Name | Subject | Verwendung |
|-----------|---------|---------|------------|
| `YformFrontendCrud::EP_QUERY` | `YFORMFRONTENDCRUD_QUERY` | `rex_yform_manager_query` | Query vor Ausführung modifizieren |
| `YformFrontendCrud::EP_BEFORE_DELETE` | `YFORMFRONTENDCRUD_BEFORE_DELETE` | `bool` | Löschen verhindern (`return false`) |
| `YformFrontendCrud::EP_AFTER_DELETE` | `YFORMFRONTENDCRUD_AFTER_DELETE` | `int $deletedId` | Nach Löschung reagieren |
| `YformFrontendCrud::EP_BEFORE_SAVE` | `YFORMFRONTENDCRUD_BEFORE_SAVE` | `rex_yform` | Formular vor Ausführung modifizieren |
| `YformFrontendCrud::EP_AFTER_SAVE` | `YFORMFRONTENDCRUD_AFTER_SAVE` | `rex_yform_manager_dataset` | Nach Speichern reagieren |
| `YformFrontendCrud::EP_OUTPUT_ROW` | `YFORMFRONTENDCRUD_OUTPUT_ROW` | `string $rowHtml` | Zeilen-HTML modifizieren |
| `YformFrontendCrud::EP_AFTER_RENDER` | `YFORMFRONTENDCRUD_AFTER_RENDER` | `string $output` | Gesamtausgabe modifizieren |

### Beispiele

#### Zusätzlichen Query-Filter setzen

```php
rex_extension::register(YformFrontendCrud::EP_QUERY, function(rex_extension_point $ep) {
    $query = $ep->getSubject();
    $query->whereRaw('`createdate` >= ?', [date('Y-m-d', strtotime('-30 days'))]);
    return $query;
});
```

#### Löschen für bestimmte Einträge verhindern

```php
rex_extension::register(YformFrontendCrud::EP_BEFORE_DELETE, function(rex_extension_point $ep) {
    $dataset = $ep->getParam('dataset');
    if ($dataset->getValue('protected') == 1) {
        return false; // Löschung abbrechen
    }
});
```

#### Nach dem Speichern eine Aktion auslösen

```php
rex_extension::register(YformFrontendCrud::EP_AFTER_SAVE, function(rex_extension_point $ep) {
    $dataset = $ep->getSubject();
    $isNew   = $ep->getParam('isNew');
    // z. B. Cache leeren, E-Mail senden, etc.
});
```

#### Zeilen-HTML erweitern (z. B. hervorgehobene Zeile)

```php
rex_extension::register(YformFrontendCrud::EP_OUTPUT_ROW, function(rex_extension_point $ep) {
    $dataset = $ep->getParam('dataset');
    $html    = $ep->getSubject();
    if ($dataset->getValue('featured') == 1) {
        $html = str_replace('<tr>', '<tr class="table-warning">', $html);
    }
    return $html;
});
```

---

## Vollständiges Beispiel

```php
use FriendsOfRedaxo\YformFrontendCrud\YformFrontendCrud;

if (rex::isFrontend() && class_exists('rex_ycom_auth') && rex_ycom_auth::getUser() !== null) {

    $renderer = new YformFrontendCrud();

    // Tabelle und Felder
    $renderer->setTableName('rex_blog_articles');
    $renderer->setFields(['title', 'date', 'status', 'author']);

    // Sortierung
    $renderer->setDefaultSortField('date');
    $renderer->setDefaultSortOrder('DESC');

    // Filter
    $renderer->addWhereCondition('status', '=', 1);

    // Framework und Anzeigemodus
    $renderer->setFramework('bootstrap5');
    $renderer->setDisplayMode('cards'); // table | cards | list

    // Status beim Anlegen/Bearbeiten
    $renderer->setNewStatus(0);  // neuer Datensatz: offline
    $renderer->setEditStatus(1); // bearbeiteter Datensatz: online

    // Aktuellen ycom-Nutzer als Autor speichern
    $renderer->setUserField('author');

    // YForm-Formulartemplate
    $renderer->setFormYtemplate('bootstrap5,project');

    // Identifikationsfeld (z. B. zum Verknüpfen mit dem eingeloggten Nutzer)
    // Wichtig: Das Feld muss in der YForm-Tabelle existieren!
    $renderer->setIdentId('user_id', rex_ycom_auth::getUser()->getId());

    // Format-Callbacks
    $renderer->setFormatCallback('title', function($value) {
        return '<strong>' . htmlspecialchars($value) . '</strong>';
    });
    $renderer->setFormatCallback('date', function($value) {
        return date('d.m.Y', strtotime($value));
    });

    // Übersetzungen für Feldwerte
    $renderer->setTranslations([
        'status' => [
            '1' => '<span class="badge bg-success">Online</span>',
            '0' => '<span class="badge bg-secondary">Entwurf</span>',
        ],
    ]);

    // Texte anpassen (optional)
    $renderer->setLabel('btn_add', 'Neuen Artikel anlegen');
    $renderer->setLabel('confirm_delete', 'Artikel wirklich löschen?');

    // Weiterleitungs-Countdown in Sekunden (Standard: 5)
    $renderer->setRedirectSeconds(3);

    echo $renderer->render();
}
```

---

## Live-Suche (JavaScript)

Das Suchfeld mit der ID `live-search` ist bereits im HTML enthalten. Dieses Snippet vor `</body>` einfügen:

> **Hinweis zu CSP:** Das Inline-Skript für den Weiterleitungs-Countdown verwendet automatisch den REDAXO-CSP-Nonce (`rex_response::getNonce()`). Das Suchfeld-Snippet muss manuell mit einem Nonce versehen werden, falls eine `Content-Security-Policy` aktiv ist.

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

## Häufige Fehlerquellen

- **Kein `status`-Feld in der Tabelle** → Insert schlägt fehl, wenn `setNewStatus` gesetzt ist
- **YForm nicht aktiviert** → Klasse nicht verfügbar
- **Bootstrap-Icons nicht geladen** → Aktions-Buttons bei Bootstrap 5 unsichtbar
- **Falscher Tabellenname** → Renderer gibt einen Fehler-Alert aus
