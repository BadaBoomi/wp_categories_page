# WP Categories Page

WordPress-Plugin zur Verwaltung und Anzeige von Inhaltskategorien inkl. Kategorie-Detaileinträgen.

Das Plugin stellt folgende Funktionen bereit:

- Verwaltung von **Kategorien** (mit Bild, Überschrift, Inhalt, Beschreibung)
- Verwaltung von **Kategorie-Details** (mit Kategorie-Zuordnung, Überschrift, Beschreibung, Bild, URL, Labels)
- Ausgabe über Shortcodes:
  - `category_overview` (`type=long|short`)
  - `category_detail`
- Styling-Konfiguration (Schrift/Farben) über WordPress-Optionen

---

## Admin-UI

Nach Aktivierung findest du die Verwaltung hier:

- **Meine Kategorien**
  - **Kategorien**
  - **Kategorie-Details**
- **Einstellungen → Category Styling**

Hinweis:
- Bildfelder in Kategorien und Kategorie-Details nutzen die WordPress-Medienbibliothek.
- Inhalt/Beschreibung werden über den WYSIWYG-Editor gepflegt.

---

## Datenmodell

### Kategorien

Custom Post Type: `wpcp_category`

Felder:
- Titel (WordPress Titel)
- Überschrift
- Bild
- Inhalt (WYSIWYG)
- Beschreibung (WYSIWYG)

### Kategorie-Details

Custom Post Type: `wpcp_detail`

Felder:
- Titel (WordPress Titel)
- Zugehörige Kategorie
- Bild
- URL
- Labels (durch `,` oder `;` trennen)
- Beschreibung (WYSIWYG)

---

## Shortcodes

### 1) Kategorie-Übersicht (lang)

```text
[category_overview categories="cat1;cat2" type="long"]
```

- `categories`: Liste von Kategorie-Slugs, getrennt mit `;`
- `type="long"`: Zeigt Bild, Überschrift und Inhalt

### 2) Kategorie-Übersicht (kurz)

```text
[category_overview categories="cat1;cat2" type="short"]
```

- `type="short"`: Zeigt Bild und Überschrift (kompakter Stil)

### 3) Kategorie-Detailseite

```text
[category_detail category="cat1"]
```

- `category`: Kategorie-Slug
- Ausgabe:
  - Header mit Überschrift, Beschreibung, Bild
  - Filterleiste für vorhandene Labels
  - Liste der passenden Detaileinträge mit:
    - laufender Nummer
    - Überschrift
    - Beschreibung
    - Labels
    - Link **„Website öffnen“**

---

## Installation (manuell per ZIP)

1. ZIP im Projekt erzeugen (siehe Build):
   - Ausgabe im Ordner `dist/`
2. In WordPress:
   - **Plugins → Installieren → Plugin hochladen**
   - ZIP auswählen und installieren
   - Plugin aktivieren

Wenn bereits eine ältere/defekte Installation vorhanden ist:

1. Plugin ggf. deaktivieren
2. Ordner `wp-content/plugins/wp-categories-page` auf dem Server löschen
3. ZIP erneut hochladen und aktivieren

---

## Build / Release

Build-Skript:

```bash
./scripts/build-plugin-zip.sh --patch
./scripts/build-plugin-zip.sh --minor
./scripts/build-plugin-zip.sh --major
```

Parameter:
- `--patch` (Default): erhöht Patch-Version (x.y.Z)
- `--minor`: erhöht Minor-Version (x.Y.0)
- `--major`: erhöht Major-Version (X.0.0)

Das Skript:
- aktualisiert `Version:` im Plugin-Header
- aktualisiert `WPCP_PLUGIN_VERSION`
- erstellt ein deploybares ZIP in `dist/`

Beispiel:
- `dist/wp-categories-page-1.2.5.zip`

---

## Entwicklung

Hauptdatei des Plugins:

- `wp-content/plugins/wp-categories-page/wp-categories-page.php`

Referenz-Mockup (nicht Teil des Builds):

- `beispiel.html`

