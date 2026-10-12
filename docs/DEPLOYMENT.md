# Azure release procedure

No Azure resources were provisioned for this implementation. The source includes a startup script and Nginx configuration, but their cloud execution and HTTPS smoke tests remain pending an actual subscription. Local development, migrations, and tests are independent of Azure availability.

## Confirm the account and cost envelope first

Record the subscription type (Azure for Students versus Starter), subscription ID, region, remaining credit, credit expiry, spending protection, App Service Free F1 availability and quotas, and the exact MySQL Flexible Server benefit eligibility, allowance, SKU, storage, and expiry. Check these in the actual subscription before provisioning. Do not upgrade to pay-as-you-go or remove spending protection. If the configuration cannot stay within confirmed benefits or available credit, preserve the local demonstration and stop cloud provisioning.

The deployment target is App Service Linux plus MySQL Flexible Server. Benefits are finite and account dependent; this document does not promise perpetual free hosting. Use the provider HTTPS hostname.

Primary references, checked during implementation:
- [Azure for Students](https://azure.microsoft.com/en-us/free/students/)
- [Student offer terms](https://azure.microsoft.com/en-us/pricing/offers/ms-azr-0170p/)
- [App Service limits](https://learn.microsoft.com/en-us/azure/azure-resource-manager/management/azure-subscription-service-limits#azure-app-service-limits)
- [PHP runtime and Nginx configuration](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php)
- [MySQL TLS configuration](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/how-to-connect-tls-ssl)

## Build and runtime

This lockfile was verified using PHP 8.4.26, Laravel 13.35.0, and MySQL 8.4.11. PHP 8.4+ is required by the resolved production dependencies. Inspect available App Service runtimes with `az webapp list-runtimes --os linux`; choose a compatible PHP runtime in the actual region. Do not suppress Composer platform checks or deploy onto PHP 8.3.

Run the complete acceptance suite, browser tests, syntax checks, and Pint before building. Build production dependencies with:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

Deploy the application and production `vendor`, including `public`, `app`, `bootstrap`, `config`, `database/migrations`, `resources/views`, `routes`, and `deploy`. Exclude `.git`, `.env`, `.tools`, `artifacts`, `node_modules`, tests, development dependencies, and demonstration exports. Static CSS/JS are already in `public`.

Configure the startup command as `sh /home/site/wwwroot/deploy/startup.sh`. It sets Nginx's document root to `/home/site/wwwroot/public`, enables writable Laravel storage/cache, and caches configuration, routes, and views. Review the generated PHP image's Nginx FastCGI address and port against `deploy/nginx.conf` before use.

## Environment and database

Set values in App Service settings, not in Git:

- `APP_NAME=ScholarMatch`, `APP_ENV=production`, `APP_DEBUG=false`.
- A persistent generated `APP_KEY`; keep it unchanged across releases and securely backed up.
- `APP_URL=https://<app>.azurewebsites.net`.
- `DB_CONNECTION=mysql` and the server hostname, port, database, least-privilege username, and password.
- `MYSQL_ATTR_SSL_CA` with the path to the CA certificate required by the actual MySQL endpoint.
- `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`.
- `QUEUE_CONNECTION=sync`, `CACHE_STORE=database`, `LOG_LEVEL=error`.

The application timezone is fixed to Asia/Kuala_Lumpur. Date-only deadlines use Malaysian end of day; explicit closing instants are stored as UTC. Configure the App Service HTTPS-only setting and the actual MySQL firewall/private connectivity policy. Verify the TLS certificate rather than disabling verification.

Back up the database before reviewed migration changes. Run `php artisan migrate --force` once per release with a controlled migration identity. Do not run the fictional seeder in production. Provision the administrator with the interactive `scholarmatch:admin` command. Load only records with reviewed official sources into the production directory.

## Online acceptance and backup

Exercise through HTTPS: registration, login/logout, profile edits with multiple qualifications, recommendation ordering and filters, administrator publication/update/deactivation, income configuration changes, field deactivation guidance, account deactivation, bookmark save/remove, and recorded application links. Compare the same fixture decisions and margins used by the local tests in a separate staging environment. Keep staging fixture accounts and data separate from production.

Confirm debug pages are unavailable, external links use HTTP/HTTPS, database TLS is enabled, sessions remain valid across a normal release with an unchanged key, and inactive accounts are refused. Measure actual warmed response times; the local 40-record timing is not a forecast for Free F1.

Export with a dedicated backup identity using `mysqldump --single-transaction --routines --triggers scholarmatch` and an encrypted credentials file or configured login path. Store the export and application key in access-controlled encrypted storage outside Git. Restore to a separate database, apply migrations, and check counts, saved relationships, and representative evaluations before switching traffic. Record the backup schedule, restore date, runtime, TLS settings, actual quotas, and benefit expiry in the release record.

There are no mandatory continuous workers. Deadline closure is evaluated during requests, so it does not depend on a scheduled task or paid background service.
