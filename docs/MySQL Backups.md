# MySQL backups

The administrator Settings page exports the database selected by Laravel's default connection. Export SQL dump downloads a SQL file; Create backup retains one under `storage/app/private/backups`. These actions back up database tables and data, not uploaded files or application configuration. Evalio does not define triggers, stored routines or events; these are not included in exports.

## Local Docker setup

Keep your existing MySQL `DB_*` settings in `.env` and add:

```dotenv
DB_DUMP_DRIVER=docker
```

Run `php artisan config:clear` and restart the development server. Docker Desktop must be running. On Windows, the exporter invokes `C:/Program Files/Docker/Docker/resources/cli-plugins/docker-compose.exe` directly to avoid plugin discovery differences in web processes. Set `DB_DUMP_COMPOSE_BINARY` if Docker is installed elsewhere. On other platforms it defaults to `docker compose`; `DB_DUMP_DOCKER_BINARY` can override the Docker executable. The export runs in the Compose `mysql` service, connecting to port 3306 inside that container. Host PHP continues to connect on port 3307. Credentials come from Laravel's resolved database configuration; they are passed to the export process through its environment.

See [[11 MySQL und Serverbetrieb]] for the full local installation and production `.env` examples, including website domain, mail, scheduler and external database configuration.

Log in as an administrator, open Settings and test both buttons. A failed export displays an error instead of a success message. Inspect the downloaded file and verify it by importing into a separate disposable database before relying on it for recovery.

## External MySQL server

Install a compatible MySQL client on the machine running Laravel, then configure:

```dotenv
DB_DUMP_DRIVER=native
MYSQLDUMP_BINARY="C:/path/to/mysql/bin/mysqldump.exe"
```

Update the normal `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` settings and clear Laravel's configuration. Linux deployments can use `MYSQLDUMP_BINARY=/usr/bin/mysqldump`. No Docker installation is needed with the native driver. The export account needs SELECT and SHOW VIEW on the database. Native export uses TCP; deployment-specific TLS requirements need explicit client configuration before relying on remote exports.

Exports run synchronously with a 120-second process limit and buffer the dump in memory. This is intended for small databases; large installations should use queued exports with streaming storage. Dumps contain private application data. Stored backups remain on the application host; copy them to separate storage for recovery from host failure. There is no restore action or automatic retention policy in the website.
