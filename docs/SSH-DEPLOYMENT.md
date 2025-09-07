# SSH Deployment – Einfach erklärt

Diese Anleitung beschreibt, wie du deine WordPress‑Seite ohne GitHub direkt per SSH aktualisierst – inklusive Code (Themes/Plugins), Medien (Uploads) und Datenbank.

—

## Voraussetzungen

- `.env.local` im Projektverzeichnis mit:
  - `SSH_USERNAME=ssh-w0181e1b`
  - `KAS_PASSWORD=<All-Inkl-SSH-Passwort>`
  - `KAS_HOST=w0181e1b.kasserver.com`
  - `REMOTE_ROOT=/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com`
  - `LOCAL_URL=http://127.0.0.1:8801`
  - `LIVE_URL=https://riman-wordpress-swarm.ecomwy.com`
- SSH im KAS aktiviert. PHP der Domain auf 8.2/8.4 gestellt.
- Scripts ausführbar (einmalig):
  - `chmod +x scripts/*.sh`

—

## Häufigste Aufgaben (kurz)

- Nur Code (Themes/Plugins) deployen:
  - Vorschau: `./scripts/wp-ssh-v2.sh deploy-wp --dry-run`
  - Additiv: `./scripts/wp-ssh-v2.sh deploy-wp --no-delete`
  - 1:1 (löscht Reste): `./scripts/wp-ssh-v2.sh deploy-wp`

- Medien (Uploads) deployen:
  - Vorschau: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads`
  - Additiv: `./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads`
  - 1:1 (löscht Reste): `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads`

- Datenbank lokal → live:
  1) Dump kompatibel erzeugen (Docker):
  ```bash
  docker compose exec -T db mysqldump -u riman_user -priman_pass_2024 \
    --default-character-set=utf8mb4 --single-transaction --quick \
    --no-tablespaces --add-drop-table riman_wp \
  | sed -E 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' > /tmp/local.sql
  ```
  2) Import + URLs, Permalinks, Cache (WP‑CLI):
  ```bash
  ./scripts/wp-ssh-v2.sh wp-db-import /tmp/local.sql
  ./scripts/wp-ssh-v2.sh wp-search-replace "http://127.0.0.1:8801" "https://riman-wordpress-swarm.ecomwy.com"
  ./scripts/wp-ssh-v2.sh wp-rewrite-flush
  ./scripts/wp-ssh-v2.sh wp-cache-flush
  ```

—

## WP‑CLI über SSH (bequem)

- WP‑CLI bereitstellen/aktualisieren (im REMOTE_ROOT):
  - `./scripts/wp-ssh-v2.sh wpcli-install`
- Direkte WP‑CLI‑Befehle (PHP 8.4/8.2 automatisch, mit `--path`):
  - `./scripts/wp-ssh-v2.sh wp "core version"`
  - `./scripts/wp-ssh-v2.sh wp "option get siteurl && option get home"`
  - `./scripts/wp-ssh-v2.sh wp "media regenerate --only-missing --yes"`
- Shortcuts:
  - `wp-db-import <local.sql>` – Dump hochladen und importieren
  - `wp-search-replace <from> <to>` – URLs global ersetzen (guid ausgespart)
  - `wp-rewrite-flush` – Permalinks flushen
  - `wp-cache-flush` – Cache leeren
  - `wp-media-regenerate` – fehlende Thumbnails generieren

—

## Alles‑in‑einem (optional)

- Additiv (ohne Löschen): `bash ./scripts/sync-live-site.sh`
- 1:1 (löscht Reste): `bash ./scripts/sync-live-site.sh --mirror`

Achtung: Das Script führt Provision, Live‑DB‑Backup, Code/Uploads‑Deploy, DB‑Import, URL‑Replace und Cache‑Flush in einem Rutsch aus.

—

## Sicherheit & Scope

- Alle Befehle sind auf `REMOTE_ROOT` beschränkt; andere Domains/Ordner werden nicht angefasst.
- Nützliche Helfer:
  - Cache leeren: `./scripts/wp-ssh-v2.sh cache-clear`
  - Uploads‑Unterordner sicher entfernen: `./scripts/wp-ssh-v2.sh clean-uploads-dir <subdir>`

—

## Troubleshooting (kurz)

- SSH „Permission denied“: `.env.local` prüfen, SSH im KAS aktivieren, `./scripts/wp-ssh-v2.sh test`.
- rsync Dry‑Run Fehler „--deleten“: behoben; wir nutzen `-n -i`.
- DB‑Import „Unknown collation utf8mb4_0900_ai_ci“: Dump wie oben via `sed` auf `utf8mb4_unicode_ci` umschreiben.
- WP‑CLI „not a WordPress installation“: unsere Shortcuts setzen `--path` automatisch; sonst explizit angeben.
- PHP‑Version: WP‑CLI nutzt automatisch `php84 → php82 → php`; Domain‑PHP in KAS auf 8.2/8.4 stellen.

—

## Warum ohne GitHub?

- SSH‑Deploy ist sofort, granular (Code/Uploads/DB) und unabhängig von GitHub‑Pushes.
- GitHub weiterhin sinnvoll für Versionierung/Review; Deploy aber direkt per SSH.

