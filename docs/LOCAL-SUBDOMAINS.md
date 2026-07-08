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

The hosts file is fine for a couple of fixed test companies, but **companies sign up with any subdomain they choose**, so you'd have to add a line for every new signup. For real multi-tenant testing, use wildcard resolution instead (next section).

## 2b. Wildcard resolution with Acrylic DNS Proxy (recommended)

[Acrylic DNS Proxy](https://mayakron.altervista.org/support/acrylic/Home.htm) is a local DNS server that **does** support wildcards, so `*.kastana.test` resolves automatically — every new company's board just works, no hosts edits. This mirrors how production behaves.

**1. Install** Acrylic DNS Proxy (Windows installer from the link above).

**2. Add a wildcard entry.** Open `C:\Program Files (x86)\Acrylic DNS Proxy\AcrylicHosts.txt` **as Administrator** and add:

```
127.0.0.1 kastana.test
127.0.0.1 *.kastana.test
```

(The `*` wildcard is the whole point — one line covers `acme.kastana.test`, `zaidjo.kastana.test`, and any future signup.)

**3. Restart the Acrylic service.** Use the Start-menu shortcut **"Restart Acrylic Service"** (installed with Acrylic).

**4. Point Windows at Acrylic.** Set your network adapter's DNS server to `127.0.0.1`:
Settings → Network & Internet → your adapter → Edit DNS → Manual → IPv4 on → **Preferred DNS: `127.0.0.1`**. (Acrylic listens on `127.0.0.1:53`.)

**5. Flush the cache:**

```
ipconfig /flushdns
```

Now open `http://anything.kastana.test/` and it resolves with no per-name setup. You can remove the individual `*.kastana.test` lines from the Windows hosts file — Acrylic handles them. (Keep the plain `kastana.test` line in either place if you like.)

> Troubleshooting: if names still don't resolve, confirm the Acrylic service is running (Services → "Acrylic DNS Proxy Service"), that the adapter's DNS is `127.0.0.1`, and re-run `ipconfig /flushdns`. To undo, set the adapter's DNS back to Automatic (DHCP).

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
