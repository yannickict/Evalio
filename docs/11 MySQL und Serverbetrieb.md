# MySQL einrichten und Evalio auf einem Server betreiben

Stand: 19. Oktober 2026. Diese Anleitung beschreibt die aktuelle Implementierung. Sie ist keine Bestätigung einer bereits geprüften Produktionsinstallation.

## 1. Welche Adresse gehört wohin?

Website und Datenbank dürfen auf unterschiedlichen Servern laufen. Die folgenden Werte haben verschiedene Aufgaben:

| Einstellung | Lokale Entwicklung | Späterer Serverbetrieb |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | Öffentliche Website, z. B. `https://evalio.example.com` |
| `DB_HOST` | `127.0.0.1` | Adresse des MySQL-Servers aus Sicht des Laravel-Servers |
| `DB_PORT` | `3307` beim aktuellen Docker-Setup | Vom Datenbankbetreiber angegebener Port, meist `3306` |
| `DB_DUMP_DRIVER` | `docker` | `native`, wenn MySQL-Clientprogramme auf dem Laravel-Server installiert sind |
| `MYSQLDUMP_BINARY` | Bei Docker nicht verwendet | Pfad zu `mysqldump` auf dem Laravel-Server |

`APP_URL` stellt selbst keinen Server bereit. Domain, DNS, HTTPS und Webserver müssen zusätzlich eingerichtet werden. `DB_HOST=127.0.0.1` ist auch im produktiven Betrieb korrekt, wenn MySQL auf derselben Maschine wie Laravel läuft. Bei einem externen Datenbankserver gehört dort dessen private Adresse oder Hostname hinein, nicht die Website-URL und kein `https://`.

## 2. Lokale Einrichtung mit Docker und MySQL

PHP 8.4+, Composer, Node/npm und Docker Desktop mit Linux-Containern bereitstellen. PHP benötigt `pdo_mysql`. Befehle im Projektverzeichnis ausführen.

1. Bei einem neuen Checkout `composer install` ausführen. Nur wenn `.env` noch fehlt, `.env.example` nach `.env` kopieren. Den Anwendungsschlüssel nur bei einer neuen Installation mit `php artisan key:generate` erzeugen.
2. Docker Desktop starten. Die vorhandene `compose.yaml` verwenden:

   ```powershell
   docker compose up -d --wait
   ```

3. In der lokalen `.env` diese Werte setzen:

   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=evalio
   DB_USERNAME=evalio
   DB_PASSWORD=evalio_local
   DB_DUMP_DRIVER=docker
   ```

   Die Zugangsdaten passen zur aktuellen Compose-Datei und sind nur für die lokale Entwicklung bestimmt. Compose legt Benutzer und Datenbank beim ersten Start mit leerem Datenvolume an. Eine spätere Änderung dieser Werte in `compose.yaml` ändert bestehende MySQL-Benutzer nicht automatisch.

4. Konfiguration laden und alle ausstehenden Migrationen anwenden:

   ```powershell
   php artisan config:clear
   php artisan migrate
   php artisan migrate:status
   ```

5. Optional einmalig `php artisan db:seed` für Demo-Inhalte ausführen. Unter `APP_ENV=local` entstehen auch Demo-Benutzer einschließlich `admin@example.com` mit Passwort `password`. Wiederholtes Seeding fügt weitere Demo-Datensätze hinzu.
6. Bei einem neuen Checkout `npm ci` ausführen, anschließend `composer dev` starten. Website unter `http://localhost:8000` öffnen.

Ein vollständig leerer Neustart ist mit `php artisan migrate:fresh --seed` möglich. Dieser Befehl löscht alle Tabellen der aktuell ausgewählten Datenbank. Für normale Updates genügt `migrate`.

`composer dev` wird mit Strg+C beendet und anschließend mit `composer dev` neu gestartet. MySQL bleibt dabei in Docker aktiv; `docker compose stop` beendet es ohne Löschen des Datenvolumes.

## 3. Externen MySQL-Server vorbereiten

Vom Datenbankbetreiber werden Hostname, Port, Datenbankname, Benutzername, Passwort und gegebenenfalls TLS-Zertifikate benötigt. Datenbank und Benutzer müssen vor `artisan migrate` existieren. Die Verbindung muss vom Laravel-Server aus erlaubt sein, beispielsweise über ein privates Netzwerk und eine Freigabe für dessen IP-Adresse.

Für die Anwendung sind Lese- und Schreibrechte erforderlich, für Migrationen zusätzlich passende Schema-Rechte wie CREATE, ALTER, INDEX, REFERENCES und DROP. Die Website verwendet aktuell dieselben Zugangsdaten auch für Dumps; diese benötigen SELECT und gegebenenfalls SHOW VIEW. Bei getrennten Migrationszugängen muss der Betreiber die Migrationen mit dem dafür vorgesehenen Zugang ausführen.

Eine neue Verbindung erstellt oder kopiert keine vorhandenen Daten. Für einen leeren Server Migrationen ausführen; sollen bestehende MySQL-Daten übernommen werden, einen Dump kontrolliert importieren und danach ausstehende Migrationen anwenden. Eine SQLite-Datei ist kein importierbarer MySQL-Dump.

## 4. `.env` für den produktiven Betrieb

Auf dem Laravel-Server eine eigene `.env` mit den tatsächlichen Werten anlegen. Platzhalter ersetzen; keine echten Zugangsdaten in Git speichern.

```dotenv
APP_NAME=Evalio
APP_ENV=production
APP_DEBUG=false
APP_URL=https://evalio.example.com
APP_KEY=

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="HIER_EIN_EIGENES_PASSWORT_MIT_MINDESTENS_12_ZEICHEN"
ADMIN_FIRST_NAME=Admin
ADMIN_LAST_NAME=Evalio

APP_LOCALE=de
APP_FALLBACK_LOCALE=en
EVALUATION_TIMEZONE=Europe/Zurich

DB_CONNECTION=mysql
DB_HOST=mysql.internal.example.com
DB_PORT=3306
DB_DATABASE=evalio
DB_USERNAME=evalio_app
DB_PASSWORD="HIER_DAS_ECHTE_PASSWORT_EINTRAGEN"

DB_DUMP_DRIVER=native
MYSQLDUMP_BINARY=/usr/bin/mysqldump

SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME="SMTP_BENUTZER"
MAIL_PASSWORD="SMTP_PASSWORT"
MAIL_FROM_ADDRESS=evalio@example.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_TIMEOUT=15
MAIL_SSL_CA_FILE=null
MAIL_TLS_SERVER_NAME=null
```

`APP_KEY` bleibt im Beispiel leer: Bei einer vollständig neuen Installation `php artisan key:generate` ausführen. Bei Umzug oder Wiederherstellung einer bestehenden Installation den bisherigen Schlüssel übernehmen und sicher aufbewahren. Nicht bei jedem Deployment neu erzeugen.

SMTP-Werte einschließlich Port und Schema nach Vorgaben des Mailanbieters setzen. `MAIL_MAILER=log` versendet keine Nachrichten. Bei SMTPS kann der Anbieter beispielsweise `MAIL_SCHEME=smtps` und Port 465 vorgeben. `SESSION_DOMAIN=null` bindet das Cookie an den jeweiligen Host; eine Domain muss dafür nicht manuell gesetzt werden.

Für interne SMTP-Server kann `MAIL_SSL_CA_FILE` auf deren vertrauenswürdiges PEM-Zertifikat zeigen. Bei Verbindung über eine IP legt `MAIL_TLS_SERVER_NAME` den im Zertifikat enthaltenen DNS-Namen fest. Damit sind keine SMTP-spezifischen Änderungen an `php.ini` oder Hosts-Datei nötig. Schritt-für-Schritt-Beispiele für den aktuellen Mailserver und spätere Serverwechsel stehen in [[09 Password Reset]].

Vorhandene `DB_URL`- oder `MAIL_URL`-Werte prüfen: Diese können einzelne Verbindungswerte überschreiben. Für die hier gezeigte Konfiguration solche alternativen URLs entfernen.

### TLS bei einer entfernten Datenbank

Die Laravel-Verbindung unterstützt über `MYSQL_ATTR_SSL_CA=/pfad/zum/ca.pem` eine CA-Datei. Weitere Anforderungen des Anbieters, etwa erzwungene Zertifikatsprüfung oder Clientzertifikate, müssen in `config/database.php` berücksichtigt und getestet werden.

Der aktuelle Dump-Service übernimmt zusätzliche TLS-Optionen nicht aus der Laravel-Verbindung und verwendet `--no-defaults`. Eine MySQL-Optionsdatei allein konfiguriert den Dump deshalb nicht. Bei einem Anbieter mit vorgeschriebenen TLS-Optionen muss der Dump-Aufruf entsprechend erweitert werden. Erfolgreiche Seitenaufrufe bestätigen daher noch nicht die Funktionsfähigkeit der Backups.

## 5. Website auf dem Server installieren

Die folgenden Befehle setzen eine neue Linux-Installation mit leerer Datenbank und bereits angelegter `.env` voraus:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan config:clear
# Nur bei einer neuen Installation ohne vorhandenen APP_KEY:
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
npm ci
npm run build
php artisan config:cache
php artisan view:cache
```

Der explizite `AdminSeeder` legt nur ein freigegebenes Administratorkonto aus den `ADMIN_*`-Werten an, keine Demo-Benutzer, Kurse oder Fragebögen. Die für die Anwendung nötigen Rollen werden bereits durch die Migration angelegt. Optional kann `php artisan db:seed --class=QuestionnaireSeeder --force` den Standardfragebogen anlegen. Diesen nicht bei jedem Deployment neu seeden, da der Seeder vorhandene Inhalte aktualisiert.

Nginx oder Apache mit PHP-FPM beziehungsweise einer passenden PHP-Anbindung einrichten. Der Document Root muss auf den Ordner `public/` zeigen, etwa `/var/www/evalio/public`. Anfragen müssen an `public/index.php` weitergeleitet werden. `storage/` und `bootstrap/cache/` benötigen Schreibrechte für den Anwendungsbenutzer. `.env` und private Backups dürfen nicht öffentlich ausgeliefert werden.

DNS der Website-Domain auf den Webserver richten und ein gültiges HTTPS-Zertifikat einrichten. Bei vorgeschaltetem Proxy dessen HTTPS-Weiterleitung und Laravel-Proxy-Vertrauen passend zur Infrastruktur konfigurieren. `composer dev` ist der lokale Entwicklungsstarter; produktiv übernimmt der Webserver die HTTP-Anfragen und ausgelieferte Assets stammen aus `npm run build`.

### Erstes Administratorkonto

In `.env` eine eigene E-Mail-Adresse, Vorname, Nachname und ein Passwort mit mindestens 12 Zeichen unter `ADMIN_EMAIL`, `ADMIN_FIRST_NAME`, `ADMIN_LAST_NAME` und `ADMIN_PASSWORD` setzen. Anschließend:

```bash
php artisan config:clear
php artisan db:seed --class=AdminSeeder --force
php artisan config:cache
```

Mit den konfigurierten Zugangsdaten anmelden. Das Passwort wird gehasht gespeichert. Wiederholtes Ausführen verändert einen vorhandenen Admin mit derselben E-Mail nicht, insbesondere nicht dessen Passwort. Gehört die E-Mail zu einem anderen Benutzer oder existiert bereits ein Admin mit anderer E-Mail, bricht der Seeder ab. Bestehende Benutzer werden nicht gelöscht. Für Passwortänderungen den normalen Passwort-Reset verwenden.

Nach erfolgreicher Erstellung kann `ADMIN_PASSWORD` aus `.env` entfernt werden; danach den Konfigurationscache erneut erstellen. Der normale Befehl `php artisan db:seed` bleibt unverändert und erzeugt unter `local` weiterhin Demo-Daten. Für nur ein Administratorkonto immer `--class=AdminSeeder` verwenden.

Nur für eine ausdrücklich gewollte, leere Neueinrichtung der lokalen Datenbank kann `php artisan migrate:fresh --seed --seeder=AdminSeeder` verwendet werden. Dabei werden alle vorhandenen Tabellen und Daten gelöscht; der Seeder selbst bereinigt keine bestehenden Datenbanken.

## 6. Scheduler, Queue und spätere Änderungen

Für die automatische Öffnung und Schließung von Evaluationen den Laravel-Scheduler jede Minute aufrufen. Beispiel für Linux-Cron; Pfade anpassen:

```cron
* * * * * cd /var/www/evalio && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Falls Queue-Jobs verwendet werden, `php artisan queue:work --tries=3` über einen Prozessmanager wie systemd oder Supervisor betreiben. Nach Code- oder Konfigurationsänderungen Worker mit `php artisan queue:restart` neu starten lassen.

Nach Änderungen an `.env` im produktiven Betrieb `php artisan config:cache` ausführen und dauerhaft laufende Prozesse neu starten. Für spätere Schema-Updates zuerst sichern und dann `php artisan migrate --force` ausführen. `migrate:fresh` gehört nicht in den normalen produktiven Update-Ablauf.

## 7. Backups und Funktionsprüfung

Für den nativen Export muss `mysqldump` auf dem Laravel-Server installiert und vom PHP-Prozess ausführbar sein. Unter Windows kann `MYSQLDUMP_BINARY="C:/Programme/MySQL/bin/mysqldump.exe"` auf den tatsächlichen Installationspfad gesetzt werden. Auf einem Linux-Server ist Docker für diesen Exportmodus nicht erforderlich.

Die Settings-Aktionen erzeugen manuelle Sicherungen:

- **SQL-Export:** Download einer SQL-Datei; die temporäre Serverdatei wird danach entfernt.
- **Sicherung erstellen:** SQL-Datei mit eindeutigem Namen unter `storage/app/private/backups/` auf dem Laravel-Server. Lokal ist das `C:\Users\yanni\Documents\Evalio\storage\app\private\backups\`.

Gesichert werden Tabellen und Daten einschließlich Migrationshistorie. Dateien, `.env`, Anwendungscode, Trigger, Routinen und Events sind nicht enthalten. Für eine vollständige Wiederherstellung Schlüssel/Konfiguration und benötigte Dateien getrennt sichern. Backups zusätzlich außerhalb des Anwendungsservers aufbewahren. Automatische Sicherungsplanung, Aufbewahrungsregeln und eine Wiederherstellungsoberfläche sind nicht implementiert.

Nach der Einrichtung prüfen:

1. `php artisan migrate:status`: alle erwarteten Migrationen sind ausgeführt.
2. Website über die tatsächliche HTTPS-Domain öffnen; Anmeldung und Sprachwechsel prüfen.
3. Passwort-zurücksetzen-Mail über den echten Mailanbieter testen.
4. `php artisan schedule:list` prüfen und den regelmäßigen Scheduler-Aufruf auf dem Server sicherstellen.
5. Beide Backup-Buttons im Administrationsbereich testen.
6. Einen Dump in eine separate Testdatenbank importieren und deren Tabellen und Daten prüfen. Nie die aktive Produktionsdatenbank für diesen Test überschreiben.

Weitere Details: [[MySQL Backups]]. Technische Grundlagen: [Laravel Deployment](https://laravel.com/framework/docs/deployment) und [MySQL mysqldump](https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html).
