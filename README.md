# WP Categories Page

WordPress-Plugin zur Verwaltung und Anzeige von Inhaltskategorien inkl. Kategorie-Detaileinträgen.

Das Plugin stellt folgende Funktionen bereit:

- Verwaltung von **Kategorien** (mit Bild, Überschrift, Inhalt, Beschreibung)
- Verwaltung von **Kategorie-Details** (mit Details ID, Überschrift, Beschreibung, Bild, URL, Labels)
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
  - **Design**

Hinweis:
- Bildfelder in Kategorien und Kategorie-Details nutzen die WordPress-Medienbibliothek.
- Inhalt/Beschreibung werden über den WYSIWYG-Editor gepflegt.
- Unter **Design** können Schriftarten, Farben sowie Hintergründe/Farbverläufe für
  - category_overview / category_detail Einträge
  - Kategorie-Bild-Container (global)
  - Kategorie-Details-Bild-Container
  gepflegt werden.
- Der **Kategorie Bild-Hintergrund** wird pro Kategorie direkt im Kategorie-Eintrag gepflegt.
- Schriftarten/Farben werden als Auswahl aus vorhandenen **Theme-** und (falls aktiv) **Elementor-Global-Presets** angeboten.
- Die Auswahl ist getrennt für **Kategorie (overview)** und **Kategorie-Details (detail)** möglich.

---

## Datenmodell

### Kategorien

Custom Post Type: `wpcp_category`

Felder:
- Titel (WordPress Titel)
- Überschrift
- Kategorie ID (z. B. `wissen`)
- Referenz auf Details (z. B. `#wissen`)
- Bild-Hintergrund (optional, pro Kategorie; z. B. `linear-gradient(...)`)
- Bild
- Inhalt (WYSIWYG)
- Beschreibung (WYSIWYG)

### Kategorie-Details

Custom Post Type: `wpcp_detail`

Felder:
- Titel (WordPress Titel)
- Überschrift
- Kategorie ID (Auswahl aus Kategorien)
- Details ID
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
- Kategorien werden als klickbare Karten gerendert; der Link kommt aus **Referenz auf Details**

Optional für eine zentrale Detailseite:

```text
[category_overview categories="cat1;cat2" type="long" detail_page_url="/kategorie-detail/"]
```

- `detail_page_url`: Fallback-Link für Karten ohne gesetzte **Referenz auf Details**  
  (Plugin ergänzt automatisch `?category=<wert-aus-referenz-oder-slug>`)

### 2) Kategorie-Übersicht (kurz)

```text
[category_overview categories="cat1;cat2" type="short"]
```

- `type="short"`: Zeigt kompakte „mini“-Karten (wie „Weitere Kategorien“ im Referenzlayout) mit Thumbnail + Titel

### 3) Kategorie-Detailseite

```text
[category_detail category="#wissen"]
```

- `category`: Referenzwert (z. B. `#wissen` oder `wissen`)
- Wenn `category` im Shortcode nicht gesetzt ist, wird automatisch `?category=<referenzwert>` aus der URL verwendet.
- Es werden genau die Kategorie-Details gerendert, deren **Details ID** dem Referenzwert entspricht.
- Header (Überschrift, Beschreibung, Bild) wird aus der über **Kategorie ID** verknüpften Kategorie geladen.
- Ausgabe:
  - Header mit Überschrift, Beschreibung, Bild
  - Filterleiste für vorhandene Labels
  - Liste der passenden Detaileinträge mit:
    - laufender Nummer
    - Überschrift
    - Beschreibung
    - Labels
    - Link **„Website öffnen“**

Beispiel für eine zentrale Elementor-Detailseite:

```text
[category_detail]
```

Aufruf über URL:

```text
/kategorie-detail/?category=wissen
```

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
