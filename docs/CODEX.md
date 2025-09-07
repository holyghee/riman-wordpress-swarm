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
- Doku: `./scripts/codex-doc.sh --summary "Kurzbeschreibung"`
- Commit: `./scripts/codex-commit.sh --message "Kurzbeschreibung" [--push]`
- Workflow (typisch):
  1) Doku‑Eintrag schreiben
  2) Commit (+ optional Push)

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

