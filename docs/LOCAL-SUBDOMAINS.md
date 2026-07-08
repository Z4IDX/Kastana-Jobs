# Local development with subdomains (`*.kastana.test`)

Multi-tenancy resolves each company from the request's subdomain, so to test it locally you serve the app at a domain root via an Apache virtual host and point a few `.test` names at `127.0.0.1`. One-time setup.

> You can keep using `http://localhost/kastana-jobs/` as before — it falls back to the **default tenant** (id 1). The vhost below is what lets you exercise *real* per-company subdomains.

## 1. Apache virtual host

Add this to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
# Kastana Jobs — multi-tenant dev vhost
<VirtualHost *:80>
    ServerName   kastana.test
    ServerAlias  *.kastana.test
    DocumentRoot "C:/xampp/htdocs/kastana-jobs"
    <Directory "C:/xampp/htdocs/kastana-jobs">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

`httpd-vhosts.conf` is already included by XAMPP's `httpd.conf` (`Include conf/extra/httpd-vhosts.conf`). **Restart Apache** from the XAMPP Control Panel afterwards.

## 2. Hosts file

The Windows hosts file has no wildcards, so add one line per subdomain you want to test. Edit `C:\Windows\System32\drivers\etc\hosts` **as Administrator**:

```
127.0.0.1 kastana.test
127.0.0.1 www.kastana.test
127.0.0.1 acme.kastana.test
127.0.0.1 globex.kastana.test
```

(For automatic wildcard resolution instead of per-name lines, install **Acrylic DNS Proxy** and map `*.kastana.test → 127.0.0.1` — optional.)

## 3. Config

Because the app now serves from the vhost's document root (not the `/kastana-jobs` subpath), set these in `config/config.local.php` (created by `install.php`) or directly in `config/config.php`:

```php
define('APP_DOMAIN', 'kastana.test'); // base domain tenants live under
define('BASE_URL',   '');             // served at the web root now, not /kastana-jobs
```

If you change `BASE_URL`, also update the two `ErrorDocument` paths in the root `.htaccess` (they're prefixed with the old base path).

## 4. Try it

- `http://kastana.test/` — the platform root (currently falls back to the default tenant; the platform landing + company signup arrive in the next step).
- `http://acme.kastana.test/` — the **acme** company's board (create a tenant with `subdomain = 'acme'` first; a signup flow is coming).

Each subdomain shows only that company's jobs; unknown subdomains and `localhost` fall back to the default tenant for now.

## 5. In production

- Point wildcard DNS `*.yourjobboard.com → your server`.
- Get a **wildcard TLS certificate** (`*.yourjobboard.com`).
- Set `APP_DOMAIN` to your real domain and `USE_HTTPS = true`.
