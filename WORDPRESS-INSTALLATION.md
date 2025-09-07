# WordPress Installation - Riman WordPress Swarm

## 🎉 Installation erfolgreich!

### Website-Details:
- **URL**: https://riman-wordpress-swarm.ecomwy.com/  (SSL aktiviert!)
- **Admin-Panel**: https://riman-wordpress-swarm.ecomwy.com/wp-admin/

### Zugangsdaten:
- **Benutzer**: admin
- **Passwort**: xH3m80KyqXw5hHoW

### Datenbank-Details:
- **DB Name**: riman-wordpress-swarm  (KORRIGIERT!)
- **DB User**: d044b291
- **DB Pass**: MGax7QMfvTMaCexwT5Jv
- **DB Host**: localhost

### Server-Pfad:
```
/www/htdocs/w0181e1b/riman-wordpress-swarm.ecomwy.com/
```

## 🚀 Nächste Schritte:

### 1. WordPress-Admin öffnen:
```bash
open http://riman-wordpress-swam.ecomwy.com/wp-admin/
```

### 2. Theme deployen:
```bash
./scripts/deploy-ftp.sh deploy-theme ./my-theme-folder
```

### 3. Plugin installieren:
```bash
./scripts/deploy-ftp.sh deploy-plugin ./my-plugin-folder
```

### 4. Backup erstellen:
```bash
./scripts/wp-db-manager.sh export
./scripts/wp-ftp-manager.sh backup-essential
```

## 📝 Wichtige Scripts:

### Dateiverwaltung:
```bash
# Datei hochladen
./scripts/deploy-ftp.sh upload local-file.php /www/htdocs/w0181e1b/riman-wordpress-swam.ecomwy.com/

# WordPress-Verzeichnis anzeigen
./scripts/deploy-ftp.sh list
```

### Datenbank-Management:
```bash
# DB-Info anzeigen
./scripts/wp-db-manager.sh info

# DB exportieren
./scripts/wp-db-manager.sh export

# Tabellen optimieren
./scripts/wp-db-manager.sh optimize
```

### SSH-Befehle:
```bash
# Site-Status prüfen
./scripts/wp-ssh-v2.sh test

# PHP-Version anzeigen
./scripts/wp-ssh-v2.sh php

# Themes anzeigen
./scripts/wp-ssh-v2.sh themes

# Plugins anzeigen
./scripts/wp-ssh-v2.sh plugins
```

## 🔧 Konfiguration anpassen:

### wp-config.php bearbeiten:
```bash
# Downloaden
./scripts/wp-ftp-manager.sh wp-config-download

# Bearbeiten
nano wp-config-backup.php

# Hochladen
./scripts/deploy-ftp.sh upload wp-config-backup.php /www/htdocs/w0181e1b/riman-wordpress-swam.ecomwy.com/wp-config.php
```

### Debug aktivieren:
```bash
./scripts/wp-ftp-manager.sh debug-enable
```

## 🛡️ Sicherheit:

1. **Ändere das Admin-Passwort** nach dem ersten Login
2. Aktiviere **2-Faktor-Authentifizierung**
3. Installiere ein **Security Plugin** (z.B. Wordfence)
4. Erstelle regelmäßige **Backups**

## 📞 Support:

- **All-Inkl KAS**: https://kas.all-inkl.com
- **WordPress Admin**: http://riman-wordpress-swam.ecomwy.com/wp-admin/
- **phpMyAdmin**: Im KAS unter "Datenbanken"

---

*Installation durchgeführt am: $(date)*