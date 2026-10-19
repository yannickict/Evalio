# Password Reset

Source review: 19 October 2026.

## SMTP einrichten: nur `.env` bearbeiten

Die SMTP-Anbindung ist bereits vorhanden. Es ist keine zusätzliche Bibliothek oder Datenbankmigration erforderlich. Auf dem Computer beziehungsweise Server, auf dem Laravel läuft, in der bestehenden `.env` folgende Werte ersetzen:

```dotenv
APP_URL=https://evalio.deine-domain.ch
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.dein-anbieter.ch
MAIL_PORT=587
MAIL_USERNAME="DEIN_SMTP_BENUTZER"
MAIL_PASSWORD="DEIN_SMTP_PASSWORT"
MAIL_FROM_ADDRESS="noreply@deine-domain.ch"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_TIMEOUT=15
MAIL_SSL_CA_FILE=null
MAIL_TLS_SERVER_NAME=null
```

### Zertifikate und interner SMTP-Server

Zertifikatsdatei und erwarteter Servername sind jetzt direkt über `.env` konfigurierbar. Änderungen an `php.ini` oder der Windows-Hosts-Datei sind dafür nicht erforderlich. Der Dateipfad gilt auf dem Rechner, auf dem Laravel ausgeführt wird; er darf absolut oder relativ zum Projektverzeichnis sein.

Für den aktuellen internen Mailserver mit selbstsigniertem Zertifikat:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=192.168.0.174
MAIL_PORT=587
MAIL_USERNAME="DEIN_SMTP_BENUTZER"
MAIL_PASSWORD="DEIN_SMTP_PASSWORT"
MAIL_FROM_ADDRESS="DEIN_FREIGEGEBENER_ABSENDER"
MAIL_SSL_CA_FILE="C:/Users/yanni/certificates/mail-zitbb.crt"
MAIL_TLS_SERVER_NAME=mail.zitbb.ch
```

`MAIL_HOST` legt das Verbindungsziel fest. `MAIL_TLS_SERVER_NAME` legt den Namen fest, gegen den das Zertifikat geprüft wird; er muss im Zertifikat enthalten sein. Damit funktioniert eine interne IP auch ohne zusätzlichen Hosts-Eintrag. Die Zertifikats- und Namensprüfung bleiben aktiv; SMTP verlangt TLS.

Das Zertifikat vor dem Vertrauen aus einer bestätigten Quelle beziehen. Die Datei muss ein lesbares PEM-Zertifikat beziehungsweise CA-Bundle sein; der Inhalt beginnt mit `-----BEGIN CERTIFICATE-----`. Nie einen privaten Schlüssel verwenden. Wenn das Zertifikat erneuert wird, eine aktuelle vertrauenswürdige Datei bereitstellen. Fehlende Dateien führen zu einem Fehler, nicht zum Überspringen der Prüfung.

### Finale Installation auf einem anderen Server

1. Website wie in [[11 MySQL und Serverbetrieb]] einrichten und `APP_URL` auf deren öffentliche HTTPS-Adresse setzen.
2. SMTP-Zugangsdaten und Absender in der dortigen `.env` eintragen.
3. Bei einem internen oder selbstsignierten Zertifikat dessen vertrauenswürdige PEM-Datei auf den Laravel-Server kopieren, etwa nach `/etc/evalio/certificates/mail-zitbb.crt`. Der PHP-Benutzer benötigt Leserechte.
4. `MAIL_SSL_CA_FILE=/etc/evalio/certificates/mail-zitbb.crt` setzen. Bei Verbindung über eine IP `MAIL_TLS_SERVER_NAME=mail.zitbb.ch` setzen. Der neue Server muss die interne IP erreichen können; andernfalls den tatsächlich erreichbaren SMTP-Host verwenden.
5. Bei einem normalen Anbieter mit öffentlich vertrauenswürdigem Zertifikat `MAIL_HOST` auf dessen SMTP-Hostnamen setzen und `MAIL_SSL_CA_FILE=null` sowie `MAIL_TLS_SERVER_NAME=null` verwenden. PHP benötigt dann einen aktuellen allgemeinen CA-Vertrauensspeicher. Falls die Installation keinen bereitstellt, kann ein vertrauenswürdiges allgemeines CA-Bundle über `MAIL_SSL_CA_FILE` angegeben werden.
6. Produktiv `php artisan config:cache` ausführen und dauerhaft laufende PHP-/Anwendungsprozesse neu starten lassen. Beide Passwort-Reset-Schritte einschließlich tatsächlicher Zustellung testen.

Falls früher nur für diesen Mailserver `openssl.cafile` in `php.ini` gesetzt wurde, ist dieser globale Eintrag für Evalio-SMTP nicht mehr nötig. Einen ausschließlich auf das interne Zertifikat zeigenden Eintrag entfernen, sofern keine andere Anwendung davon abhängt, und PHP neu starten. Ein allgemeines CA-Bundle darf bestehen bleiben. Ein bisheriger Hosts-Eintrag wird nicht mehr benötigt, wenn direkt die IP mit `MAIL_TLS_SERVER_NAME` verwendet wird.

Zertifikatsdateien separat bereitstellen; die private lokale Ablage `storage/app/private/` ist ebenfalls möglich und wird nicht versioniert. Zugangsdaten verbleiben ausschließlich in der jeweiligen `.env`. Für einen Wechsel des Mailservers sind damit keine Codeänderungen nötig. Änderungen der TLS-Einstellungen betreffen nur den SMTP-Transport dieser Anwendung.

Host, Benutzer, Passwort, Port und erlaubte Absenderadresse vom Mailanbieter übernehmen. Für Port 587 ist üblicherweise `MAIL_SCHEME=smtp` mit automatischem STARTTLS vorgesehen; für Port 465 `MAIL_SCHEME=smtps`. Nicht `tls` als Schema eintragen. Wenn der Anbieter ein App-Passwort verlangt, dieses statt des normalen Kontopassworts verwenden. Ein eventuell vorhandenes `MAIL_URL` entfernen, wenn die einzelnen Werte verwendet werden sollen.

`APP_URL` wird ausdrücklich für die Links in den Reset-Mails verwendet, auch wenn die Anfrage unter einer anderen Adresse eintrifft. Lokal kann es `http://localhost:8000` sein; dieser Link funktioniert nur auf dem Entwicklungscomputer. Für andere Empfänger die tatsächlich erreichbare Website-Adresse mit HTTPS eintragen. Ein abschließender Schrägstrich wird berücksichtigt.

Nach dem Bearbeiten lokal:

```powershell
php artisan config:clear
```

Falls `composer dev` läuft, mit Strg+C beenden und erneut starten. Produktiv stattdessen den Konfigurationscache mit `php artisan config:cache` neu erstellen und dauerhaft laufende Anwendungsprozesse neu starten lassen. Weitere Änderungen am PHP-Code sind für einen normalen SMTP-Anbieter mit Benutzername/Passwort nicht nötig. Anbieter, die ausschließlich OAuth erlauben oder SMTP-Authentifizierung deaktiviert haben, benötigen eine entsprechende Freigabe oder einen anderen Transport.

### Versand prüfen

1. Auf der Login-Seite abmelden und „Passwort vergessen“ öffnen.
2. Die E-Mail-Adresse eines bestehenden Kontos eintragen, dessen Postfach erreichbar ist.
3. Eingang und Spamordner prüfen; der Link muss auf die konfigurierte `APP_URL` zeigen.
4. Link öffnen, neues Passwort setzen und damit anmelden. Der Link ist 60 Minuten gültig und nur einmal verwendbar. Zwischen zwei Anfragen für dasselbe Konto mindestens 60 Sekunden warten.

Der Versand erfolgt derzeit direkt während der Anfrage; ein Queue-Worker ist für Reset-Mails nicht erforderlich. Der SMTP-Timeout ist über `MAIL_TIMEOUT` konfigurierbar. Die allgemeine Bestätigung wird auch für unbekannte Konten angezeigt und beweist deshalb keine Zustellung. Bei Fehlern `storage/logs/laravel.log` prüfen, ohne Zugangsdaten oder Reset-Links zu veröffentlichen. Mit `MAIL_MAILER=log` wird weiterhin nur ins Log geschrieben und keine echte E-Mail verschickt. In der Produktion einen vom Anbieter freigegebenen Absender und gegebenenfalls dessen Domain-Einträge für SPF/DKIM einrichten.

Ohne echte SMTP-Zugangsdaten ist nur der Ablauf automatisiert geprüft, nicht die Zustellung beim Anbieter.

Guests can use **Forgot password?** on login to request an email link, choose a new password and return to login. Resetting a password never approves an account or changes its role. Pending users still require administrator approval before login.

## Implementation

The four guest routes are `password.request` (GET form), `password.email` (POST link request), `password.reset` (GET token form) and `password.update` (POST reset). Both POST routes have a five-requests-per-minute rate limit.

`ForgotPasswordRequest` validates the email; `ResetPasswordRequest` validates the token, email and confirmed password with an eight-character minimum. `PasswordResetController` delegates token creation and validation to Laravel's password broker. Existing `password_reset_tokens` storage and `config/auth.php` configure a 60-minute expiry and a 60-second wait between link requests for the same account. No new migration is needed.

Known and unknown emails receive the same public confirmation. A valid reset saves a hashed password, rotates the remember token, emits `PasswordReset`, consumes the reset token and redirects to login. The shared auth layout displays confirmation messages and validation errors. Resetting does not automatically log the user in or invalidate all existing authenticated sessions.

## Local email and logs

Use these local settings with the URL matching your development server:

```env
MAIL_MAILER=log
APP_URL=http://localhost:8000
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

Run `php artisan config:clear`, log out, and request a reset for an email that exists in the current database. The log mailer writes the message and reset link to `storage/logs/laravel.log`. Unknown accounts generate no reset email; recently requested accounts are broker-throttled. The generic confirmation alone does not prove an email was sent.

Operational entries record `Password reset link request processed.` and `Password reset attempt processed.` with the broker's `status`. These entries include no email addresses, passwords or reset tokens. The local log mailer itself includes the email and token-bearing link; use it only for development and do not commit or share its contents.

For diagnosis, inspect the active configuration and broker result in Tinker:

```php
config('mail.default');
config('logging.default');
Password::sendResetLink(['email' => 'admin@example.com']);
```

Common statuses are `passwords.sent`, `passwords.user`, `passwords.throttled`, `passwords.reset` and `passwords.token`. Sending exceptions remain application errors rather than successful confirmations.

## Production

Configure a real mail transport, valid sender address and your public HTTPS `APP_URL`. Restrict accepted hosts at the web server or through Laravel trusted-host configuration so generated reset links use the intended host. Expired token cleanup can be scheduled with `auth:clear-resets`; the broker rejects expired tokens even before cleanup.

## Verification

On 8 October 2026, the user confirmed that local reset email logging works. `PasswordResetTest` adds coverage for guest forms, notifications, generic confirmations, success and login, token reuse, invalid/expired/wrong-account tokens, pending accounts, validation, safe status logging and rate limits. The reset regressions subsequently passed in the full PHP suite; current verification is recorded in [[07 Implementation Status|Implementation Status]].

```powershell
php artisan test --filter='PasswordResetTest|AuthTest|PasswordChangeTest'
composer ci:check
```

Installed dependencies require PHP 8.4 or newer. The compatible local herd-lite PHP 8.4 executable is documented in [[06 Development Setup|Development Setup]]. XAMPP PHP 8.2 cannot run the locked Laravel test suite or required formatter.
