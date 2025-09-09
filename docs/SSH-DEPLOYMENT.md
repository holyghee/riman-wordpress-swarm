# Deployment ohne GitHub – verständlich erklärt

So aktualisierst du deine WordPress‑Seite schnell und sicher – ohne GitHub‑Deploys. Du behältst die Kontrolle über:
- Code (Themes/Plugins)
- Medien (Uploads)
- Datenbank (Inhalte/Einstellungen)

Alles ist strikt auf deinen Projektpfad begrenzt: `REMOTE_ROOT=/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com`.

—

## 1) Vorbereitung (einmalig)

- `.env.local` ausfüllen:
  - `SSH_USERNAME=ssh-w0181e1b`
  - `KAS_PASSWORD=<All‑Inkl SSH‑Passwort>`
  - `KAS_HOST=w0181e1b.kasserver.com`
  - `REMOTE_ROOT=/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com`
  - `LOCAL_URL=http://127.0.0.1:8801` (deine lokale URL)
  - `LIVE_URL=https://riman-wordpress-swarm.ecomwy.com`
- PHP der Domain im KAS auf 8.2/8.4 stellen (empfohlen: 8.4).
- Skripte ausführbar machen: `chmod +x scripts/*.sh`

—

## 2) Dein typischer Ablauf (Cheat‑Sheet)

- Code (Themes/Plugins) – direkt per SSH:
  - Vorschau: `./scripts/wp-ssh-v2.sh deploy-wp --dry-run`
  - Sicher (ohne Löschen): `./scripts/wp-ssh-v2.sh deploy-wp --no-delete`
  - Exakt 1:1 (mit Löschen): `./scripts/wp-ssh-v2.sh deploy-wp`

- Medien (Uploads):
  - Vorschau: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads`
  - Sicher (ohne Löschen): `./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads`
  - Exakt 1:1 (mit Löschen): `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads`

- Datenbank (lokal → live):
  - Ein‑Kommando: `bash ./scripts/wp-db-sync.sh`

—

## 3) Optional: „Nur das pushen, was im Docker läuft“

Wenn du Git sauber halten willst, ohne ständig `.gitignore` nachzupflegen:

- Code aus Docker → ins Repo (nur ./wp-content, ohne Uploads):
  - Vorschau: `./scripts/docker-export-code.sh --dry-run`
  - Mirror (mit Löschen): `./scripts/docker-export-code.sh`
  - Additiv (ohne Löschen): `./scripts/docker-export-code.sh --no-delete`
  - Direkt committen/pushen (nur `./wp-content`): `./scripts/docker-export-code.sh --push`

Empfohlener Ablauf, um „alles aus Docker“ zu GitHub zu bringen (nur Code):
- Vorschau: `./scripts/docker-export-code.sh --dry-run`
- Mirror in dein Repo (nur Code): `./scripts/docker-export-code.sh`
- Push zu GitHub (nur `./wp-content`): `./scripts/docker-export-code.sh --push`
- Push mit eigener Commit‑Message: `./scripts/docker-export-code.sh --push --message "feat: update blocks"`
 - Nur committen (kein Push): `./scripts/docker-export-code.sh --commit --message "chore: export"`
 - Nur stage (selbst committen/pushen): `./scripts/docker-export-code.sh --stage-only` 

Voraussetzungen fürs Pushen:
- `origin` ist gesetzt (prüfen: `git remote -v`).
- GitHub‑Authentifizierung vorhanden (SSH‑Key oder HTTPS mit Token).

Hinweis: Standardmäßig werden ALLE Themes/Plugins aus dem Container exportiert. Ausnahmen: Standard‑WordPress „twenty*“ (außer „twentytwentyfive“/„2025“ wird mit exportiert), „akismet“, „hello.php“. Das Script zeigt am Anfang den Modus und am Ende eine Liste der exportierten Themes/Plugins – so siehst du sofort, ob etwas fehlt. Falls du filtern willst, nutze `--allowlist` sowie `--themes/--plugins`.

Hinweis: Falls „permission denied“ erscheint, Script einmal ausführbar machen oder mit bash starten:
- Ausführbar machen: `chmod +x ./scripts/docker-export-code.sh`
- Alternativ starten: `bash ./scripts/docker-export-code.sh --dry-run`

Vorteile: Es landet nur der tatsächlich aktive Code (Themes/Plugins) aus dem Container im Repo. Keine Dev‑Skripte, keine großen Medien.

—

## 4) WP‑CLI Kurzbefehle (über SSH, mit PHP 8.4)

- Einrichten/aktualisieren: `./scripts/wp-ssh-v2.sh wpcli-install`
- Direkter Aufruf:
  - `./scripts/wp-ssh-v2.sh wp "core version"`
  - `./scripts/wp-ssh-v2.sh wp "option get siteurl && option get home"`
  - `./scripts/wp-ssh-v2.sh wp "media regenerate --only-missing --yes"`
- Shortcuts:
  - `wp-db-import <local.sql>`
  - `wp-search-replace <from> <to>`
  - `wp-rewrite-flush`
  - `wp-cache-flush`
  - `wp-media-regenerate`

—

## 5) Sicher vs. Spiegeln

- „Sicher“ (ohne Löschen): `--no-delete`
  - Fügt hinzu/überschreibt, lässt vorhandenes auf Live bestehen.
- „Spiegeln“ (mit Löschen): ohne Flag
  - Macht Live exakt wie lokal – löscht Reste. Immer zuerst Dry‑Run prüfen.

—

## 6) Hilfe & Fehlerbehebung

- SSH testen: `./scripts/wp-ssh-v2.sh test`
- Cache leeren (scoped): `./scripts/wp-ssh-v2.sh cache-clear`
- Uploads‑Unterordner sicher entfernen: `./scripts/wp-ssh-v2.sh clean-uploads-dir <ordner>`
- DB‑Kollation Fehler: Dump mit `sed 'utf8mb4_0900_ai_ci' → 'utf8mb4_unicode_ci'` umschreiben (siehe oben).
- WP‑CLI „not a WordPress installation“: unsere Shortcuts setzen `--path` automatisch – sonst Pfad mitgeben.

—

## 7) Empfehlung

- Code: über Docker‑Export ins Repo (nur `./wp-content`) ODER direkt per SSH deployen.
- Medien/DB: immer per SSH (schnell, granular, kein Git‑Ballast).
- GitHub: weiterhin für Versionierung/Reviews nützlich; Deploy kannst du aber komplett über SSH laufen lassen.

—

## 8) Medien (Bilder/Videos) über SSH pushen

Grundsätzlich liegen alle Medien unter `wp-content/uploads`. Du kannst ganze Ordner oder nur Teilbereiche synchronisieren.

Beispiele:
- Vorschau (zeigt geplante Änderungen, löscht nichts):
  - Komplett: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads`
  - Monatsordner: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads/2025/09 2025/09`

- Sicher (additiv, löscht auf Live nichts – empfohlen):
  - Komplett: `./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads`
  - Nur Videos‑Ordner: `./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads/videos videos`
  - Nur Monatsordner: `./scripts/wp-ssh-v2.sh deploy-uploads --no-delete ./wp-content/uploads/2025/09 2025/09`

- Exakt 1:1 spiegeln (löscht auf Live, nur nutzen wenn bewusst gewünscht):
  - Komplett: `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads`

Tipps:
- Vor großen Uploads immer erst `--dry-run` verwenden.
- Bei Videos/Assets ist `--no-delete` meist sinnvoll, damit nichts versehentlich entfernt wird.
- Live‑Anzahl/Größe prüfen:
  - `./scripts/wp-ssh-v2.sh run "find $REMOTE_ROOT/wp-content/uploads -type f | wc -l && du -sh $REMOTE_ROOT/wp-content/uploads"`

Wichtig: Reiner Datei‑Upload legt keine Mediathek‑Einträge an. Für IDs/Referenzen gibt es den Ein‑Befehl‑Wrapper:

- Upload + Mediathek‑Import in einem Schritt:
  - Komplett: `./scripts/wp-ssh-v2.sh media-sync ./wp-content/uploads`
  - Nur Videos: `./scripts/wp-ssh-v2.sh media-sync ./wp-content/uploads/videos videos`
  - Spiegeln statt additiv: `./scripts/wp-ssh-v2.sh media-sync --mirror ./wp-content/uploads`

Hinweis: Der Import legt zu bestehenden Dateien Attachements an (falls noch nicht vorhanden). Inhalte, die Medien per ID referenzieren (z. B. Video‑Block), funktionieren danach ohne erneutes „Auswählen“.

—

## 9) Medien aus Docker (empfohlen, wenn Container „Quelle der Wahrheit“)

Falls deine Medien aus dem Docker‑Container kommen, nutze zuerst den Export in einen separaten lokalen Ordner und deploye von dort:

- Export (Container → lokal):
  - Vorschau: `bash ./scripts/docker-export-uploads.sh --dry-run`
  - Mirror: `bash ./scripts/docker-export-uploads.sh`
  - Ziel: `./wp-content/uploads-from-docker`

- Deploy (SSH):
  - Vorschau: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads-from-docker`
  - Mirror: `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads-from-docker`

- Mediathek registrieren (nur Originale; Varianten werden übersprungen):
  - `./scripts/wp-ssh-v2.sh wp-media-import-all`

- Cleanup Duplikate (falls schon Varianten als eigene Attachments existieren):
  - `./scripts/wp-ssh-v2.sh wp-media-clean-variants`

Tipps:
- Import idealerweise nur einmal pro Datei‑Set ausführen; wiederholtes Importieren kann Duplikate erzeugen.
- Bei Thumbnails‑Fehlern (`ImproperImageHeader`), Bild neu exportieren oder serverseitig mit ImageMagick neu schreiben, dann: `./scripts/wp-ssh-v2.sh wp-media-regenerate`.
