# ScholarMatch

A Laravel 13 / PHP 8.4 scholarship decision-support application for Malaysian students, implemented from [PRD.md](PRD.md), [ARCHITECTURE.md](ARCHITECTURE.md), and the supplied Stitch designs.

Students can register, maintain multiple qualifications and a funding programme, explore explained recommendations, and save opportunities. Administrators maintain scholarships, explicit requirement modes, alternative academic routes, source reviews, study fields, income thresholds, and account activation. Decisions are computed from current records, with no persisted eligibility cache.

## Run locally

Install PHP 8.4+, Composer 2, and MySQL 8.4. Enable cURL, fileinfo, mbstring, OpenSSL, PDO MySQL, PDO SQLite, DOM, XML, and tokenizer. The committed Composer lockfile pins the tested dependencies.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `scholarmatch` and an application user with access to it. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`, then run:

```sh
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000. On PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

Bootstrap CSS is vendored in `public/css`; Blade loads local CSS and JavaScript directly. No frontend build or continuously running worker is needed.

### This workspace's isolated Windows setup

PHP and Composer were downloaded into ignored `.tools`, with the official PHP SHA-256 checked. MySQL 8.4.11 was extracted into `.tools/mysql` and runs on loopback port 3307, with its data in `.tools/mysql-data`. No system service was installed. Its root account has a generated password in ignored `.tools/mysql-root.cnf`. The local application user's demonstration-only password is `LocalDemo!123456`. The generated local `.env` is ignored.

Use `.tools/php/php.exe` for PHP and `.tools/php/php.exe .tools/composer.phar` for Composer if neither is on PATH.

To restart that isolated database:

```powershell
& .tools/mysql/mysql-8.4.11-winx64/bin/mysqld.exe --no-defaults --basedir=D:/Github/ScholarMatchV2/.tools/mysql/mysql-8.4.11-winx64 --datadir=D:/Github/ScholarMatchV2/.tools/mysql-data --bind-address=127.0.0.1 --port=3307 --mysqlx=0 --console
```

In another terminal, start the app with `.tools/php/php.exe artisan serve --host=127.0.0.1 --port=8000`. These paths describe this machine; a fresh checkout uses the standard setup above.

### Fictional demonstration accounts

The seeder creates one administrator, ten students, and forty clearly labelled fictional scholarships. Seed data and income ranges are examples, not official scholarship information or Malaysian household statistics. Production seeding is refused.

| Account | Email | Password |
| --- | --- | --- |
| Student (Aina) | student1@scholarmatch.test | Demo!123456 |
| Administrator | admin@scholarmatch.test | Demo!123456 |
| Other demo students | student2@scholarmatch.test through student10@scholarmatch.test | Demo!123456 |

For production, provision an administrator with `php artisan scholarmatch:admin owner@example.com --name="Owner"`. The command prompts privately for a password and rejects duplicate emails. Public registration always assigns the student role.

## Tests and verification

```sh
composer test
vendor/bin/pint --test
pnpm install --frozen-lockfile
pnpm exec playwright install chromium
pnpm test
```

Browser tests launch an isolated PHP server on 127.0.0.1:8765 and a disposable SQLite database in `artifacts/browser.sqlite`; they never reset the demonstration MySQL database. Set `PHP_BINARY` if PHP is not on PATH. On this Windows workspace, set `PLAYWRIGHT_BROWSERS_PATH` to the absolute `.tools/browsers` directory.

PHPUnit defaults to in-memory SQLite. To run the same suite on MySQL, create `scholarmatch_test` and set the test connection variables before executing PHPUnit. Test database names must end in `_test`.

```powershell
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3307'
$env:DB_DATABASE='scholarmatch_test'
$env:DB_USERNAME='scholarmatch'
$env:DB_PASSWORD='LocalDemo!123456'
& .tools/php/php.exe vendor/phpunit/phpunit/phpunit --testdox
```

Remove these environment overrides before serving the app. Tests use migrations and transactions against a separate test database.

[Verification notes](docs/VERIFICATION.md) link the actual execution logs. The GitHub Actions workflow repeats PHP tests on SQLite and MySQL and runs browser tests. No remote CI run is claimed by local verification.

## Behaviour worth knowing

- Current education and the programme seeking funding are independent.
- Any definite compulsory failure takes precedence over an unresolved condition.
- Academic routes are OR alternatives; every compulsory condition inside a route must pass.
- Missing data requires verification. Only an explicit reviewed No Restriction mode yields N/A.
- Academic margin is proportional clearance of a minimum, not compatibility or award probability. Only eligible, fully satisfied numerical routes can score. Zero minima yield N/A.
- Ranking, filtering, and pagination operate on the whole current result set, with stable deadline/ID tie breaks.
- Date-only deadlines stay open through the Malaysian date. Provider times are entered in Malaysia time and stored in UTC.
- Income boundaries include both ends and use cents. Whole active configurations cover RM 0 through an unlimited upper range, with no gaps or overlaps.
- Deactivated reference fields retain historical relationships. New selections must be active.
- Repeated bookmark saves/removals are idempotent. Closed and deactivated saved opportunities remain accessible to their owner.
- Source reviews and student manual-verification results are separate. Reviews record an administrator and timestamp.

[Azure deployment instructions](docs/DEPLOYMENT.md) cover runtime checks, public document root, encrypted database connections, benefit verification, smoke testing, and backups. Online release requires an eligible Azure account and a reviewed cost configuration.
