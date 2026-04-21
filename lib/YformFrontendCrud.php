<?php

namespace FriendsOfRedaxo\YformFrontendCrud;

use rex_article;
use rex_extension;
use rex_response;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

/**
 * Frontend-CRUD-Renderer für YForm-Manager-Tabellen.
 *
 * Unterstützt mehrere CSS-Frameworks, Anzeigemodi, Extension Points und Format-Callbacks.
 *
 * Extension Points:
 *   YFORMFRONTENDCRUD_QUERY        – Query vor Ausführung modifizieren (Subject: rex_yform_manager_query)
 *   YFORMFRONTENDCRUD_BEFORE_DELETE – Löschung erlauben/verhindern (Subject: bool, return false = abbrechen)
 *   YFORMFRONTENDCRUD_AFTER_DELETE  – Nach erfolgreicher Löschung (Subject: int $deletedId)
 *   YFORMFRONTENDCRUD_BEFORE_SAVE   – Yform-Objekt vor Ausführung modifizieren (Subject: rex_yform)
 *   YFORMFRONTENDCRUD_AFTER_SAVE    – Nach erfolgreichem Speichern (Subject: rex_yform_manager_dataset)
 *   YFORMFRONTENDCRUD_OUTPUT_ROW    – Zeilen-HTML modifizieren (Subject: string $rowHtml)
 *   YFORMFRONTENDCRUD_AFTER_RENDER  – Gesamtausgabe modifizieren (Subject: string $output)
 */
class YformFrontendCrud
{
    /** Extension Point: Wird vor der Query-Ausführung gefeuert. Subject: rex_yform_manager_query */
    public const EP_QUERY = 'YFORMFRONTENDCRUD_QUERY';

    /** Extension Point: Wird vor dem Löschen gefeuert. Subject: bool – return false bricht den Vorgang ab. */
    public const EP_BEFORE_DELETE = 'YFORMFRONTENDCRUD_BEFORE_DELETE';

    /** Extension Point: Wird nach erfolgreicher Löschung gefeuert. Subject: int $deletedId */
    public const EP_AFTER_DELETE = 'YFORMFRONTENDCRUD_AFTER_DELETE';

    /** Extension Point: Wird vor der Formularverarbeitung gefeuert. Subject: rex_yform */
    public const EP_BEFORE_SAVE = 'YFORMFRONTENDCRUD_BEFORE_SAVE';

    /** Extension Point: Wird nach erfolgreichem Speichern gefeuert. Subject: rex_yform_manager_dataset */
    public const EP_AFTER_SAVE = 'YFORMFRONTENDCRUD_AFTER_SAVE';

    /** Extension Point: Wird pro Zeile/Karte/Listeneintrag gefeuert. Subject: string $rowHtml */
    public const EP_OUTPUT_ROW = 'YFORMFRONTENDCRUD_OUTPUT_ROW';

    /** Extension Point: Wird nach der vollständigen Ausgabe gefeuert. Subject: string $output */
    public const EP_AFTER_RENDER = 'YFORMFRONTENDCRUD_AFTER_RENDER';

    private string $tableName = '';
    private array $fields = [];
    private string $defaultSortField = 'id';
    private string $defaultSortOrder = 'ASC';
    private array $whereConditions = [];
    private array $translations = [];
    private ?int $newStatus = 2;
    private ?int $editStatus = 1;
    private ?string $userField = null;
    private string $formYtemplate = 'uikit3,project,bootstrap';
    private array $formatCallbacks = [];
    private array $fieldLabels = [];
    private ?string $identField = null;
    /** @var mixed */
    private $identValue = null;
    private string $framework = 'uikit';
    private array $cssTemplates = [];
    private string $displayMode = 'table';
    private bool $showActions = true;
    private int $redirectSeconds = 5;

    /** @var array<string, string> */
    private array $labels = [
        // Fehlermeldungen
        'error_invalid_params'   => 'Ungültige Parameter übergeben.',
        'error_invalid_id'       => 'Ungültige ID.',
        'error_not_found'        => 'Fehler: Datensatz konnte nicht gefunden werden.',
        'error_delete_prevented' => 'Löschen wurde durch eine Erweiterung verhindert.',
        'error_delete_failed'    => 'Fehler: Datensatz konnte nicht gelöscht werden.',
        'error_table_not_found'  => 'Tabelle "%s" nicht gefunden.',
        // Erfolgsmeldungen
        'success_deleted'        => 'Datensatz wurde erfolgreich gelöscht.',
        'success_saved_new'      => 'Der Datensatz wurde erfolgreich erstellt.',
        'success_saved_edit'     => 'Der Datensatz wurde erfolgreich aktualisiert.',
        // Formular
        'form_title_new'         => 'Neuer Eintrag',
        'form_title_edit'        => 'Datensatz aktualisieren',
        // Liste / Buttons
        'btn_add'                => 'Neuen Eintrag erstellen',
        'btn_reset_sort'         => 'Standard-Sortierung wiederherstellen',
        'search_placeholder'     => 'Nach Einträgen suchen...',
        'col_actions'            => 'Aktionen',
        // Aktionen
        'action_edit'            => 'Bearbeiten',
        'action_delete'          => 'Löschen',
        'confirm_delete'         => 'Wirklich löschen?',
        // Weiterleitungs-Countdown (%s = Countdown-Span)
        'redirect_countdown'     => 'Sie werden in %s Sekunden zur Liste zurückgeleitet.',
        'redirect_link'          => 'Klicken Sie hier',
        'redirect_back'          => ', um sofort zurückzukehren.',
    ];

    public function __construct()
    {
        $this->initializeCssTemplates();
    }

    // ─── Konfiguration ───────────────────────────────────────────────────────

    /**
     * Setzt den Tabellennamen und lädt die Feldbezeichnungen aus dem YForm-Manager.
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
        $this->loadFieldLabels();
    }

    /**
     * Felder, die in der Listenansicht angezeigt werden sollen.
     *
     * @param array<string> $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function setDefaultSortField(string $defaultSortField): void
    {
        $this->defaultSortField = $defaultSortField;
    }

    public function setDefaultSortOrder(string $defaultSortOrder): void
    {
        $this->defaultSortOrder = $defaultSortOrder;
    }

    /**
     * Fügt eine WHERE-Bedingung zur Abfrage hinzu.
     *
     * @param mixed $value
     */
    public function addWhereCondition(string $field, string $operator, $value): void
    {
        $this->whereConditions[] = [$field, $operator, $value];
    }

    /**
     * Übersetzungen für Feldwerte (z. B. Status-Codes als HTML-Badges).
     *
     * @param array<string, array<string|int, string>> $translations
     */
    public function setTranslations(array $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * Status-Wert, der beim Anlegen eines neuen Datensatzes gesetzt wird.
     */
    public function setNewStatus(?int $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    /**
     * Status-Wert, der beim Bearbeiten eines bestehenden Datensatzes gesetzt wird.
     */
    public function setEditStatus(?int $editStatus): void
    {
        $this->editStatus = $editStatus;
    }

    /**
     * Feldname, in dem der Login-Name des aktuellen ycom-Nutzers gespeichert wird.
     */
    public function setUserField(?string $userField): void
    {
        $this->userField = $userField;
    }

    /**
     * YForm-Template-String für das Formular.
     */
    public function setFormYtemplate(string $formYtemplate): void
    {
        $this->formYtemplate = $formYtemplate;
    }

    /**
     * Registriert einen Format-Callback für ein bestimmtes Feld.
     * Der Callback erhält den Rohwert und gibt den formatierten HTML-String zurück.
     *
     * @param callable(mixed):string $callback
     */
    public function setFormatCallback(string $field, callable $callback): void
    {
        $this->formatCallbacks[$field] = $callback;
    }

    /**
     * Setzt ein verstecktes Identifikationsfeld (z. B. Besitzer-ID).
     *
     * @param mixed $value
     */
    public function setIdentId(string $field, $value): void
    {
        $this->identField = $field;
        $this->identValue = $value;
    }

    /**
     * Setzt das CSS-Framework (uikit | bootstrap3 | bootstrap4 | bootstrap5 | custom).
     */
    public function setFramework(string $framework): void
    {
        if (isset($this->cssTemplates[$framework])) {
            $this->framework = $framework;
        }
    }

    /**
     * Setzt den Anzeigemodus (table | cards | list).
     */
    public function setDisplayMode(string $mode): void
    {
        if (in_array($mode, ['table', 'cards', 'list'], true)) {
            $this->displayMode = $mode;
        }
    }

    /**
     * Aktiviert oder deaktiviert die Aktions-Buttons (Bearbeiten/Löschen/Neu).
     */
    public function setShowActions(bool $showActions): void
    {
        $this->showActions = $showActions;
    }

    /**
     * Ersetzt ein komplettes CSS-Template für ein Framework.
     *
     * @param array<string, string> $template
     */
    public function setCssTemplate(string $framework, array $template): void
    {
        $this->cssTemplates[$framework] = $template;
    }

    /**
     * Überschreibt eine einzelne CSS-Klasse in einem Framework-Template.
     */
    public function setCssClass(string $framework, string $key, string $class): void
    {
        if (isset($this->cssTemplates[$framework])) {
            $this->cssTemplates[$framework][$key] = $class;
        }
    }

    /**
     * Überschreibt einen einzelnen UI-Text anhand seines Schlüssels.
     *
     * Schlüssel: error_invalid_params, error_invalid_id, error_not_found,
     * error_delete_prevented, error_delete_failed, error_table_not_found (%s = Tabellenname),
     * success_deleted, success_saved_new, success_saved_edit,
     * form_title_new, form_title_edit, btn_add, btn_reset_sort, search_placeholder,
     * col_actions, action_edit, action_delete, confirm_delete,
     * redirect_countdown (%s = Countdown-Span), redirect_link, redirect_back.
     */
    public function setLabel(string $key, string $value): void
    {
        $this->labels[$key] = $value;
    }

    /**
     * Überschreibt mehrere UI-Texte auf einmal.
     *
     * @param array<string, string> $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = array_merge($this->labels, $labels);
    }

    /**
     * Sekunden für den Weiterleitungs-Countdown nach Speichern/Löschen (Standard: 5).
     */
    public function setRedirectSeconds(int $seconds): void
    {
        $this->redirectSeconds = max(1, $seconds);
    }

    // ─── Rendering ───────────────────────────────────────────────────────────

    /**
     * Rendert die CRUD-Oberfläche (Liste, Formular oder Lösch-Bestätigung).
     */
    public function render(): string
    {
        if ($this->tableName === '' || $this->fields === [] || !in_array(strtoupper($this->defaultSortOrder), ['ASC', 'DESC'], true)) {
            return $this->label('error_invalid_params');
        }

        $func = rex_request('func', 'string', '');

        if ($func === 'delete') {
            $output = $this->handleDelete();
        } elseif ($func === 'edit' || $func === 'add') {
            $output = $this->handleForm($func);
        } else {
            $currentSortField = rex_request('sort', 'string', $this->defaultSortField);
            $currentSortOrder = rex_request('order', 'string', $this->defaultSortOrder);
            $output = $this->handleList($currentSortField, $currentSortOrder);
        }

        return (string) rex_extension::fire(self::EP_AFTER_RENDER, $output, ['renderer' => $this]);
    }

    // ─── Aktionen ────────────────────────────────────────────────────────────

    private function handleDelete(): string
    {
        $deleteId = rex_request('id', 'int', 0);

        if ($deleteId <= 0) {
            return $this->alert('danger', $this->label('error_invalid_id'));
        }

        $dataset = rex_yform_manager_dataset::get($deleteId, $this->tableName);
        if (!$dataset) {
            return $this->alert('danger', $this->label('error_not_found'));
        }

        // EP: Externe Erweiterungen können das Löschen verhindern (return false)
        $allowed = rex_extension::fire(self::EP_BEFORE_DELETE, true, [
            'dataset' => $dataset,
            'id' => $deleteId,
            'renderer' => $this,
        ]);

        if ($allowed === false) {
            return $this->alert('danger', $this->label('error_delete_prevented'));
        }

        if (!$dataset->delete()) {
            return $this->alert('danger', $this->label('error_delete_failed'));
        }

        rex_extension::fire(self::EP_AFTER_DELETE, $deleteId, [
            'tableName' => $this->tableName,
            'renderer' => $this,
        ]);

        $backUrl = rex_getUrl(rex_article::getCurrentId());

        return $this->alert('success', $this->label('success_deleted'))
            . $this->redirectCountdown($backUrl);
    }

    private function handleForm(string $action): string
    {
        $editId = rex_request('id', 'int', -1);
        $isNew = ($editId === -1);

        $dataset = $isNew
            ? rex_yform_manager_dataset::create($this->tableName)
            : rex_yform_manager_dataset::get($editId, $this->tableName);

        if (!$dataset) {
            return $this->alert('danger', $this->label('error_not_found'));
        }

        $yform = $dataset->getForm();
        $yform->setObjectparams('form_action', rex_getUrl(rex_article::getCurrentId(), '', ['func' => $action, 'id' => $editId]));
        $yform->setObjectparams('form_showformafterupdate', 0);
        $yform->setObjectparams('main_id', $editId);
        $yform->setObjectparams('getdata', !$isNew);
        $yform->setObjectparams('form_ytemplate', $this->formYtemplate);

        if ($isNew && $this->newStatus !== null) {
            $yform->setValueField('hidden', ['status', $this->newStatus]);
        } elseif (!$isNew && $this->editStatus !== null) {
            $yform->setValueField('hidden', ['status', $this->editStatus]);
        }

        // ycom-User-Feld nur setzen wenn ycom verfügbar ist
        if ($this->userField !== null && class_exists('rex_ycom_auth') && \rex_ycom_auth::getUser() !== null) {
            $yform->setValueField('hidden', [$this->userField, \rex_ycom_auth::getUser()->getValue('login')]);
        }

        if ($this->identField !== null) {
            $yform->setValueField('hidden', [$this->identField, $this->identValue]);
        }

        // EP: Yform-Objekt vor Ausführung modifizieren (z. B. zusätzliche hidden-Felder)
        $yform = rex_extension::fire(self::EP_BEFORE_SAVE, $yform, [
            'dataset' => $dataset,
            'isNew' => $isNew,
            'renderer' => $this,
        ]);

        $formHtml = '<div class="' . $this->getCssClass('background_muted') . ' ' . $this->getCssClass('padding') . '">'
            . $dataset->executeForm($yform)
            . '</div>';

        if ($yform->objparams['actions_executed']) {
            rex_extension::fire(self::EP_AFTER_SAVE, $dataset, [
                'isNew' => $isNew,
                'renderer' => $this,
            ]);

            $backUrl = rex_getUrl(rex_article::getCurrentId());
            $saveMsg = $isNew ? $this->label('success_saved_new') : $this->label('success_saved_edit');

            return $this->alert('success', $saveMsg)
                . $this->redirectCountdown($backUrl);
        }

        $title = $isNew ? $this->label('form_title_new') : $this->label('form_title_edit');

        return '<h2>' . $title . '</h2>' . $formHtml;
    }

    private function handleList(string $currentSortField, string $currentSortOrder): string
    {
        $table = rex_yform_manager_table::get($this->tableName);

        if (!$table) {
            return $this->alert('danger', $this->label('error_table_not_found', htmlspecialchars($this->tableName)));
        }

        $query = $table->query();

        foreach ($this->whereConditions as [$field, $operator, $value]) {
            $query->whereRaw('`' . $field . '` ' . $operator . ' ?', [$value]);
        }

        $query->orderBy($currentSortField, $currentSortOrder);

        // EP: Query modifizieren – z. B. zusätzliche Filter, Joins oder Limits hinzufügen
        $query = rex_extension::fire(self::EP_QUERY, $query, [
            'tableName' => $this->tableName,
            'renderer' => $this,
        ]);

        $datasets = $query->find();

        return $this->renderDataDisplay($datasets, $currentSortField, $currentSortOrder);
    }

    // ─── Ausgabe-Helfer ───────────────────────────────────────────────────────

    private function renderDataDisplay(array $datasets, string $currentSortField, string $currentSortOrder): string
    {
        $buttons = '';
        if ($this->showActions) {
            $buttons = '<div class="' . $this->getCssClass('margin_bottom') . '">'
                . '<a href="' . rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'add']) . '" class="' . $this->getCssClass('button_primary') . '">' . $this->label('btn_add') . '</a> '
                . '<a href="' . rex_getUrl(rex_article::getCurrentId()) . '" class="' . $this->getCssClass('button_default') . '">' . $this->label('btn_reset_sort') . '</a>'
                . '</div>';
        }

        $searchInput = '<input class="' . $this->getCssClass('input') . ' ' . $this->getCssClass('margin_bottom') . '" id="live-search" type="text" placeholder="' . htmlspecialchars($this->label('search_placeholder')) . '">';

        switch ($this->displayMode) {
            case 'cards':
                return $buttons . $searchInput . $this->renderCardsView($datasets);
            case 'list':
                return $buttons . $searchInput . $this->renderListView($datasets);
            case 'table':
            default:
                return $buttons . $searchInput . $this->renderTableView($datasets, $currentSortField, $currentSortOrder);
        }
    }

    private function renderTableView(array $datasets, string $currentSortField, string $currentSortOrder): string
    {
        $output = '<div class="' . $this->getCssClass('overflow_auto') . '">'
            . '<table class="' . $this->getCssClass('table') . '">'
            . '<thead><tr>';

        foreach ($this->fields as $field) {
            $label = $this->getFieldLabel($field);
            $sortIcon = '';
            if ($field === $currentSortField) {
                $sortIcon = $currentSortOrder === 'ASC' ? ' &uarr;' : ' &darr;';
            }
            $toggleOrder = $currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            $output .= '<th><a href="' . rex_getUrl(rex_article::getCurrentId(), '', ['sort' => $field, 'order' => $toggleOrder]) . '">'
                . htmlspecialchars($label) . $sortIcon
                . '</a></th>';
        }

        if ($this->showActions) {
            $output .= '<th>' . $this->label('col_actions') . '</th>';
        }

        $output .= '</tr></thead><tbody id="data-table">';

        foreach ($datasets as $dataset) {
            $rowHtml = '<tr>';

            foreach ($this->fields as $field) {
                $rowHtml .= '<td>' . $this->formatValue($field, $dataset->getValue($field)) . '</td>';
            }

            if ($this->showActions) {
                $rowHtml .= $this->renderActionCell($dataset->getId());
            }

            $rowHtml .= '</tr>';

            // EP: Zeilen-HTML modifizieren
            $output .= (string) rex_extension::fire(self::EP_OUTPUT_ROW, $rowHtml, [
                'dataset' => $dataset,
                'displayMode' => $this->displayMode,
                'renderer' => $this,
            ]);
        }

        return $output . '</tbody></table></div>';
    }

    private function renderCardsView(array $datasets): string
    {
        $isBootstrap = in_array($this->framework, ['bootstrap3', 'bootstrap4', 'bootstrap5'], true);
        $output = '<div class="' . $this->getCssClass('grid_small') . '" data-grid>';

        foreach ($datasets as $dataset) {
            $cardHtml = '<div class="card-item">';

            if ($isBootstrap) {
                $cardHtml .= '<div class="card"><div class="card-body">';
            } elseif ($this->framework === 'uikit') {
                $cardHtml .= '<div class="uk-card uk-card-default uk-card-body">';
            } else {
                $cardHtml .= '<div class="card">';
            }

            foreach ($this->fields as $field) {
                $cardHtml .= '<p><strong>' . htmlspecialchars($this->getFieldLabel($field)) . ':</strong> '
                    . $this->formatValue($field, $dataset->getValue($field)) . '</p>';
            }

            if ($this->showActions) {
                $id = $dataset->getId();
                $editLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'edit', 'id' => $id]);
                $deleteLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'delete', 'id' => $id]);
                $cardHtml .= '<div class="actions">'
                    . '<a href="' . $editLink . '" class="' . $this->getCssClass('button_default') . '">' . $this->getEditIcon() . ' ' . $this->label('action_edit') . '</a> '
                    . '<a href="' . $deleteLink . '" class="' . $this->getCssClass('button_default') . '" ' . $this->confirmDeleteAttr() . '>' . $this->getDeleteIcon() . ' ' . $this->label('action_delete') . '</a>'
                    . '</div>';
            }

            $cardHtml .= $isBootstrap ? '</div></div>' : '</div>';
            $cardHtml .= '</div>';

            // EP: Karten-HTML modifizieren
            $output .= (string) rex_extension::fire(self::EP_OUTPUT_ROW, $cardHtml, [
                'dataset' => $dataset,
                'displayMode' => $this->displayMode,
                'renderer' => $this,
            ]);
        }

        return $output . '</div>';
    }

    private function renderListView(array $datasets): string
    {
        $output = '<ul class="data-list">';

        foreach ($datasets as $dataset) {
            $itemHtml = '<li>';

            foreach ($this->fields as $field) {
                $itemHtml .= '<span class="field-' . htmlspecialchars($field) . '"><strong>'
                    . htmlspecialchars($this->getFieldLabel($field)) . ':</strong> '
                    . $this->formatValue($field, $dataset->getValue($field)) . '</span> ';
            }

            if ($this->showActions) {
                $id = $dataset->getId();
                $editLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'edit', 'id' => $id]);
                $deleteLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'delete', 'id' => $id]);
                $itemHtml .= '<span class="actions">'
                    . '<a href="' . $editLink . '">' . $this->getEditIcon() . '</a> '
                    . '<a href="' . $deleteLink . '" ' . $this->confirmDeleteAttr() . '>' . $this->getDeleteIcon() . '</a>'
                    . '</span>';
            }

            $itemHtml .= '</li>';

            // EP: Listen-HTML modifizieren
            $output .= (string) rex_extension::fire(self::EP_OUTPUT_ROW, $itemHtml, [
                'dataset' => $dataset,
                'displayMode' => $this->displayMode,
                'renderer' => $this,
            ]);
        }

        return $output . '</ul>';
    }

    // ─── Interne Helfer ───────────────────────────────────────────────────────

    private function loadFieldLabels(): void
    {
        $table = rex_yform_manager_table::get($this->tableName);
        if ($table === null) {
            return;
        }

        foreach ($table->getFields() as $field) {
            $this->fieldLabels[$field->getName()] = $field->getLabel();
        }
    }

    private function getFieldLabel(string $field): string
    {
        return $this->fieldLabels[$field] ?? $field;
    }

    /**
     * Wendet Übersetzungen und Format-Callbacks auf einen Feldwert an.
     *
     * @param mixed $value
     */
    private function formatValue(string $field, $value): string
    {
        if (isset($this->translations[$field][(string) $value])) {
            $value = $this->translations[$field][(string) $value];
        }

        if (isset($this->formatCallbacks[$field])) {
            $value = ($this->formatCallbacks[$field])($value);
        }

        return (string) $value;
    }

    /**
     * Gibt einen übersetzten Label-Text zurück, optional mit sprintf-Interpolation.
     */
    private function label(string $key, mixed ...$args): string
    {
        $text = $this->labels[$key] ?? $key;
        return $args !== [] ? sprintf($text, ...$args) : $text;
    }

    /**
     * Erzeugt das onclick-Bestätigungs-Attribut für den Löschen-Dialog.
     */
    private function confirmDeleteAttr(): string
    {
        return 'onclick="return confirm(\'' . addslashes($this->label('confirm_delete')) . '\');"';
    }

    private function renderActionCell(int $id): string
    {
        $editLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'edit', 'id' => $id]);
        $deleteLink = rex_getUrl(rex_article::getCurrentId(), '', ['func' => 'delete', 'id' => $id]);

        if ($this->framework === 'uikit') {
            return '<td>
                <div class="uk-grid-small uk-child-width-auto" uk-grid>
                    <div><a href="' . $editLink . '" uk-tooltip="' . $this->label('action_edit') . '" uk-icon="icon: pencil"></a></div>
                    <div><a href="' . $deleteLink . '" uk-tooltip="' . $this->label('action_delete') . '" uk-icon="icon: trash" ' . $this->confirmDeleteAttr() . '></a></div>
                </div>
            </td>';
        }

        return '<td>
            <div class="' . $this->getCssClass('grid_small') . '">
                <a href="' . $editLink . '" title="' . $this->label('action_edit') . '">' . $this->getEditIcon() . '</a>
                <a href="' . $deleteLink . '" title="' . $this->label('action_delete') . '" ' . $this->confirmDeleteAttr() . '>' . $this->getDeleteIcon() . '</a>
            </div>
        </td>';
    }

    private function alert(string $type, string $message): string
    {
        $cssClass = $this->getCssClass('alert_' . $type);
        $wrapper = $this->getCssClass('alert_wrapper');
        $wrapperAttr = $wrapper !== '' ? ' class="' . $wrapper . '"' : '';

        if ($this->framework === 'uikit') {
            $wrapperAttr = ' uk-alert';
        }

        return '<div class="' . $cssClass . '"' . $wrapperAttr . '>' . $message . '</div>';
    }

    private function redirectCountdown(string $url): string
    {
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES);
        $nonce = rex_response::getNonce();
        $nonceAttr = $nonce !== '' ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"' : '';
        $seconds = $this->redirectSeconds;
        $countdownSpan = '<span id="countdown">' . $seconds . '</span>';

        return '<p>' . sprintf($this->label('redirect_countdown'), $countdownSpan) . '</p>'
            . '<p><a href="' . $escapedUrl . '">' . $this->label('redirect_link') . '</a>' . $this->label('redirect_back') . '</p>'
            . '<script' . $nonceAttr . '>!function(){var c=' . $seconds . ',t=document.getElementById("countdown"),i=setInterval(function(){t.textContent=--c;if(c<=0){clearInterval(i);window.location.href="' . addslashes($url) . '";}},1000);}();</script>';
    }

    private function getCssClass(string $key): string
    {
        return $this->cssTemplates[$this->framework][$key] ?? '';
    }

    private function getEditIcon(): string
    {
        return match ($this->framework) {
            'bootstrap3' => '<span class="glyphicon glyphicon-pencil"></span>',
            'bootstrap4' => '<i class="fas fa-edit"></i>',
            'bootstrap5' => '<i class="bi bi-pencil"></i>',
            'uikit'      => '',
            default      => '<span class="icon-edit">&#9998;</span>',
        };
    }

    private function getDeleteIcon(): string
    {
        return match ($this->framework) {
            'bootstrap3' => '<span class="glyphicon glyphicon-trash"></span>',
            'bootstrap4' => '<i class="fas fa-trash"></i>',
            'bootstrap5' => '<i class="bi bi-trash"></i>',
            'uikit'      => '',
            default      => '<span class="icon-delete">&#128465;</span>',
        };
    }

    private function initializeCssTemplates(): void
    {
        $this->cssTemplates = [
            'uikit' => [
                'alert_success'   => 'uk-alert-success',
                'alert_danger'    => 'uk-alert-danger',
                'alert_wrapper'   => 'uk-alert',
                'button_primary'  => 'uk-button uk-button-primary',
                'button_default'  => 'uk-button uk-button-default',
                'input'           => 'uk-input',
                'margin_bottom'   => 'uk-margin-bottom',
                'overflow_auto'   => 'uk-overflow-auto',
                'table'           => 'uk-table uk-table-striped uk-table-hover',
                'background_muted' => 'uk-background-muted',
                'padding'         => 'uk-padding',
                'grid_small'      => 'uk-grid-small uk-child-width-auto',
                'tooltip'         => 'uk-tooltip',
                'icon'            => 'uk-icon',
            ],
            'bootstrap3' => [
                'alert_success'   => 'alert alert-success',
                'alert_danger'    => 'alert alert-danger',
                'alert_wrapper'   => '',
                'button_primary'  => 'btn btn-primary',
                'button_default'  => 'btn btn-default',
                'input'           => 'form-control',
                'margin_bottom'   => 'margin-bottom',
                'overflow_auto'   => 'table-responsive',
                'table'           => 'table table-striped table-hover',
                'background_muted' => 'bg-muted',
                'padding'         => 'padding',
                'grid_small'      => 'row',
                'tooltip'         => 'title',
                'icon'            => 'glyphicon',
            ],
            'bootstrap4' => [
                'alert_success'   => 'alert alert-success',
                'alert_danger'    => 'alert alert-danger',
                'alert_wrapper'   => '',
                'button_primary'  => 'btn btn-primary',
                'button_default'  => 'btn btn-secondary',
                'input'           => 'form-control',
                'margin_bottom'   => 'mb-3',
                'overflow_auto'   => 'table-responsive',
                'table'           => 'table table-striped table-hover',
                'background_muted' => 'bg-light',
                'padding'         => 'p-3',
                'grid_small'      => 'row',
                'tooltip'         => 'title',
                'icon'            => 'fas',
            ],
            'bootstrap5' => [
                'alert_success'   => 'alert alert-success',
                'alert_danger'    => 'alert alert-danger',
                'alert_wrapper'   => '',
                'button_primary'  => 'btn btn-primary',
                'button_default'  => 'btn btn-secondary',
                'input'           => 'form-control',
                'margin_bottom'   => 'mb-3',
                'overflow_auto'   => 'table-responsive',
                'table'           => 'table table-striped table-hover',
                'background_muted' => 'bg-light',
                'padding'         => 'p-3',
                'grid_small'      => 'row',
                'tooltip'         => 'title',
                'icon'            => 'bi',
            ],
            'custom' => [
                'alert_success'   => 'success-message',
                'alert_danger'    => 'error-message',
                'alert_wrapper'   => 'alert',
                'button_primary'  => 'btn-primary',
                'button_default'  => 'btn-default',
                'input'           => 'input',
                'margin_bottom'   => 'mb',
                'overflow_auto'   => 'overflow-auto',
                'table'           => 'table striped hover',
                'background_muted' => 'bg-muted',
                'padding'         => 'p',
                'grid_small'      => 'grid-small',
                'tooltip'         => 'tooltip',
                'icon'            => 'icon',
            ],
        ];
    }
}
