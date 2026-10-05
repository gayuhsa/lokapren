# Lokapren

Indonesian city-focused craft marketplace (CodeIgniter 4 + MariaDB + Shield +
OpenStreetMap/Leaflet). See `docs/DATABASE.md` for the schema.

## Quick start (native, against MariaDB)

```bash
# .env already points database.default.* at a local MariaDB (user root, no password);
# adjust it if yours differs.
mysql -uroot -e "CREATE DATABASE lokapren CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php spark migrate --all
php spark db:seed 'App\Database\Seeds\DemoSeeder'

php spark serve
# open http://localhost:8080
```

Every demo account uses the password `lokapren123`:

| Role | Login |
|---|---|
| Customer | `sari@lokapren.test` |
| Seller | `gebyok@lokapren.test` / `tenun@lokapren.test` / `keramik@lokapren.test` |

Sellers manage their shop under `/seller`; the store locator is `/location`.

## Tests

```bash
php vendor/bin/phpunit --no-coverage
```

(85 tests, 932 assertions — runs against the SQLite test database; the seeder
suite exercises the same code path the CLI uses, and the MariaDB path is covered
by the instructions above.)

## Running the Container

> **Note for Podman users:** Replace `docker` with `podman` in any of the commands below.

### Build the Image
```bash
docker build -t lokapren .

```

### Run the Container

#### Linux / macOS

```bash
docker run --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren

```

#### Windows

```cmd
docker run --rm -p 8080:80 -v "%cd%":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren

```

### Enter the Container Shell

If you need to debug or run commands inside the container:

**Linux / macOS:**

```bash
docker run -it --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren bash

```

**Windows:**

```cmd
docker run -it --rm -p 8080:80 -v "%cd%":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren bash

```

### Clean Up

To remove any stopped containers built from this image and delete the local image itself:

```bash
podman rm -f $(podman ps -a -q --filter ancestor=lokapren) 2>/dev/null || true
podman rmi lokapren
```

