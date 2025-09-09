# Codex Guide (Projekt-Kurzanleitung)

Nutze diese Datei als Startpunkt für Codex/Assistenten in diesem Repository. Bitte zuerst lesen und danach arbeiten.

## Ziele
- Deploy ohne GitHub: Code, Medien, Datenbank per SSH
- Optional: Code aus Docker → Repo (nur `./wp-content`)
- Saubere Commits + Doku pro Änderung

## Wichtige Dateien
- `.codex.yml` – Commit/Push‑Präferenzen (Prefix, Branch, Dateiliste)
- `docs/SSH-DEPLOYMENT.md` – Einfache Befehle für Code/Medien/DB
- `docs/CHANGELOG.md` – Änderungsprotokoll (wird automatisch ergänzt)

## Commit & Doku (auf Zuruf)
- Einzeiler (empfohlen):
  - `./scripts/commit.sh "Kurzbeschreibung"`  (schreibt CHANGELOG, commit + auto‑push per Config)
  - `./scripts/commit.sh "Kurzbeschreibung" --no-push`  (ohne Push)
  - `./scripts/commit.sh "Kurzbeschreibung" --scope working`  (Doku über Working‑Diff)
  - `./scripts/commit.sh "Kurzbeschreibung" --no-doc`  (ohne CHANGELOG)
- Ganz ohne Beschreibung (automatisch erzeugt):
  - `./scripts/auto-doc-commit.sh`  (liest geänderte Dateien, baut kurze Zusammenfassung, CHANGELOG + commit + auto‑push)
  - `./scripts/auto-doc-commit.sh --no-push`  (ohne Push)
- Alternativ (manuell):
  - Doku: `./scripts/codex-doc.sh --summary "Kurzbeschreibung"`
  - Commit: `./scripts/codex-commit.sh --message "Kurzbeschreibung" [--push|--no-push]`

## Commit‑Kurzbefehle (Beispiele)
- Auto‑Message + Push: `./scripts/auto-doc-commit.sh`
- Auto‑Message ohne Push: `./scripts/auto-doc-commit.sh --no-push`
- Eigene Message + Doku + Push: `./scripts/commit.sh "feat: update blocks"`
- Eigene Message ohne Push: `./scripts/commit.sh "fix: hero spacing" --no-push`
- Nur bestimmte Dateien committen: `git add path1 path2 && ./scripts/commit.sh "chore: partial commit" --scope staged`
- Nur Doku, danach manuell committen: `./scripts/codex-doc.sh --summary "Kurzbeschreibung" && ./scripts/codex-commit.sh --message "Kurzbeschreibung" --no-push`
- Nur Staging (kein Commit): `./scripts/codex-commit.sh --stage-only`

Hinweis: `.codex.yml` steuert `commit_prefix` (z. B. `chore(codex)`) und `push_by_default`. `--no-push` überschreibt die Voreinstellung.

## Deploy (SSH)
- Code: `./scripts/wp-ssh-v2.sh deploy-wp`
- Medien (Upload + in Mediathek registrieren):
  - Komplett: `./scripts/wp-ssh-v2.sh media-sync ./wp-content/uploads`
  - Nur Videos: `./scripts/wp-ssh-v2.sh media-sync ./wp-content/uploads/videos videos`
- Datenbank: `bash ./scripts/wp-db-sync.sh`

## Docker → Git (nur Code)
- Vorschau: `./scripts/docker-export-code.sh --dry-run`
- Mirror: `./scripts/docker-export-code.sh`
- Commit + Push: `./scripts/docker-export-code.sh --push --message "feat: export code"`

## Docker → Uploads (Medien)
- One‑Shot (Export → Deploy): `bash ./scripts/docker-media-deploy.sh [--dry-run] [--no-delete] [--import] [--cleanup-variants] [--regenerate]`
  - Alternativ manuell:
    - Export: `bash ./scripts/docker-export-uploads.sh`  (Vorschau: `--dry-run`)
    - Deploy Dry-Run: `./scripts/wp-ssh-v2.sh deploy-uploads --dry-run ./wp-content/uploads-from-docker`
    - Deploy (1:1 spiegeln): `./scripts/wp-ssh-v2.sh deploy-uploads ./wp-content/uploads-from-docker`
    - Mediathek registrieren: `./scripts/wp-ssh-v2.sh wp-media-import-all` (überspringt Varianten)
    - Cleanup Duplikate: `./scripts/wp-ssh-v2.sh wp-media-clean-variants`

## Wie der Assistent starten soll
1) Diese Datei lesen (`docs/CODEX.md`)
2) `.codex.yml` beachten (Commit‑Prefix/Branch)
3) Bei "Bitte committen/pushen":
   - `codex-doc.sh` → `codex-commit.sh`
4) Bei Deploys die Befehle aus "Deploy (SSH)" nutzen

## Hinweise
- `.gitignore` ist strikt; nur Doku + Helfer + `./wp-content` werden versioniert.
- Große Dateien (Uploads) niemals in Git – stattdessen SSH‑Sync.
- Live‑Pfad ist in `.env.local`/REMOTE_ROOT hinterlegt.
- Mediathek‑Import: `wp-media-import-all` importiert nur Originale. Wiederholtes Importieren gleicher Dateien kann Duplikate erzeugen – im Zweifel vorher `wp-media-clean-variants` ausführen.
