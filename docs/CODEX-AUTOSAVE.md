# Codex CLI – Auto‑Commit

Es gibt (derzeit) keine offizielle Codex‑Konfiguration wie bei Claude Flow, die automatisch Commits schreibt. Du kannst aber sehr einfach ein leichtes Autosave im Hintergrund starten.

## Schnellstart

```bash
# 1) Script ausführbar machen (einmalig)
chmod +x scripts/codex-autosave.sh

# 2) Autosave starten (im Projekt‑Root)
./scripts/codex-autosave.sh

# Das Script committed alle Änderungen (respektiert .gitignore) alle 30 Sekunden
# mit einer Nachricht wie: "chore(codex): autosave — 2025-09-07 12:34:56 UTC — <files>"
```

Beenden mit CTRL+C.

## Optionen

```bash
# Interval anpassen (Sekunden)
INTERVAL=60 ./scripts/codex-autosave.sh

# Commit‑Prefix ändern
PREFIX="feat(codex): save" ./scripts/codex-autosave.sh

# Dateiliste in Commit‑Nachricht weglassen
SHOW_FILES=0 ./scripts/codex-autosave.sh
```

## Hinweise

- Das Autosave respektiert `.gitignore`. Stelle sicher, dass große/vertrauliche Dateien dort ausgeschlossen sind.
- Commits erfolgen auf dem aktuellen Branch. Du kannst normal weiterarbeiten, manuell pushen oder einen anderen Branch verwenden.
- Für feinere Kontrolle: `./scripts/docker-export-code.sh --stage-only` staged nur `wp-content`, damit du manuell einen kombinierten Commit (Code + Doku) erstellen kannst.

