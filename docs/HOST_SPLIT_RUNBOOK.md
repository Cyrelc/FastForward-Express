# Splitting the app and marketing site onto separate hosts

Target:

| Host | Serves |
|---|---|
| `fastforwardexpress.ca` | Marketing site (for now, still this server) |
| `www.fastforwardexpress.ca` | Redirects to `fastforwardexpress.ca` |
| `app.fastforwardexpress.ca` | The app: login, password reset, `/app/*`, and everything it calls |

`/api/*` (Stripe webhooks, public form API, mobile token login) answers on any host and is never redirected.

The code side is the `feature/split-hosts` branch. Its `RedirectToSiteHost` middleware only
switches on once `APP_URL` and `MARKETING_URL` in `.env` name different hosts, so deploying
the code and flipping the switch are separate steps.

Facts as of 2026-11-23:
- `fastforwardexpress.ca` and `www` are A records to `209.89.8.97` (Apache 2.4.58, no proxy in front).
- The TLS certificate is a Sectigo wildcard (`*.fastforwardexpress.ca` + `fastforwardexpress.ca`)
  that **expires 2026-12-08**. It already covers `app.`, but must be replaced (Let's Encrypt) by then
  regardless of this work.

---

## Phase A: same server answers both hosts

### A1. Route 53: add the `app` record

1. AWS console → Route 53 → Hosted zones → `fastforwardexpress.ca`.
2. **Create record**:
   - Record name: `app`
   - Record type: `A`
   - Value: `209.89.8.97`
   - TTL: `300`
   - Routing policy: Simple
3. While you're there, **lower the TTL** on the existing `fastforwardexpress.ca` and `www` A records
   to `300` (edit each record, change TTL only). That way, when the marketing site moves later,
   the change takes effect in minutes instead of hours. Don't change their values.

**Do not touch the MX, TXT (SPF/DKIM for Postmark) or NS records.**

Check from any machine (it can take a few minutes):

```bash
dig +short app.fastforwardexpress.ca     # expect 209.89.8.97
```

### A2. Apache: answer for `app.`

Find the site config:

```bash
grep -rl "fastforwardexpress" /etc/apache2/sites-enabled/
```

In **both** the `<VirtualHost *:443>` and `<VirtualHost *:80>` blocks, add `app.fastforwardexpress.ca`
to the `ServerAlias` line (add the line if there isn't one):

```apache
ServerName fastforwardexpress.ca
ServerAlias www.fastforwardexpress.ca app.fastforwardexpress.ca
```

If the `:80` block redirects to HTTPS with a hardcoded host (e.g. `Redirect permanent / https://fastforwardexpress.ca/`),
change it to keep the requested host:

```apache
RewriteEngine On
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

Then:

```bash
sudo apache2ctl configtest      # must say "Syntax OK"
sudo systemctl reload apache2
curl -sI https://app.fastforwardexpress.ca/login | head -1    # expect HTTP/1.1 200
```

The wildcard certificate covers `app.`, so HTTPS works straight away. Nothing is split yet:
`app.` simply shows the same site as the bare domain.

### A3. Outside services that care about the hostname

Do these before flipping the switch in A5:

- **Google Maps / Places API key** (Google Cloud console → APIs & Services → Credentials → the browser
  key): if it has *HTTP referrer* restrictions, add `https://app.fastforwardexpress.ca/*`.
  Otherwise address autocomplete and maps break inside the app. (The IP-restricted Routes key is
  server-side and unaffected.)
- **Stripe webhooks** (Stripe dashboard → Developers → Webhooks): **edit** each existing endpoint's URL
  from `https://fastforwardexpress.ca/api/...` to `https://app.fastforwardexpress.ca/api/...`.
  Editing keeps the signing secret; creating a new endpoint would give it a new one. Both hosts accept
  webhooks during Phase A, so there's no rush, but it must be done before Phase B.
- **Anything else calling `/api/login`** (the token login, e.g. a driver mobile app): point it at `app.`
  before Phase B.

### A4. Deploy the code, split still off

On the server:

```bash
cd /path/to/FastForward-Express
cp .env ~/env.before-split.bak
grep -E "^(APP_URL|MARKETING_URL|SESSION_DOMAIN|SANCTUM_STATEFUL_DOMAINS)=" .env
```

Edit `.env`:

```
APP_URL=https://fastforwardexpress.ca
```

`APP_URL` must now be a bare host. If it currently ends in `/app`, that's what this changes: the
invoice email's link now adds `/app` itself. Leave `MARKETING_URL` unset for now.

```bash
git fetch
git checkout feature/split-hosts
php artisan config:clear && php artisan route:clear && php artisan view:clear
# config:cache / route:cache again if you use them
sudo supervisorctl restart all     # workers send the invoice emails and must pick up the new APP_URL
```

What changes for users: `https://fastforwardexpress.ca/` now shows the homepage directly instead of
redirecting to `/home`. Everything else behaves as before.

### A5. Flip the switch

Pick a quiet time. **Everyone gets logged out once**, because the session cookie belongs to the
host it was issued on.

Edit `.env`:

```
APP_URL=https://app.fastforwardexpress.ca
MARKETING_URL=https://fastforwardexpress.ca
```

- `SESSION_DOMAIN` must be empty or removed. It must **not** be `.fastforwardexpress.ca`, which
  would hand the app's session cookie to whatever hosts the marketing site later.
- `SANCTUM_STATEFUL_DOMAINS`: if it's set, add `app.fastforwardexpress.ca` to it. If it isn't set,
  leave it unset (it derives from `APP_URL`).

```bash
php artisan config:clear       # then config:cache again if you use it
sudo supervisorctl restart all
```

Check:

```bash
curl -sI https://fastforwardexpress.ca/ | head -1                  # 200: homepage
curl -sI https://fastforwardexpress.ca/login | grep -i location    # https://app.fastforwardexpress.ca/login
curl -sI https://www.fastforwardexpress.ca/about | grep -i location   # https://fastforwardexpress.ca/about
curl -sI https://app.fastforwardexpress.ca/ | grep -i location     # https://app.fastforwardexpress.ca/app
curl -sI https://app.fastforwardexpress.ca/about | grep -i location   # https://fastforwardexpress.ca/about
```

Then in a browser:
1. The "Sign In" link on the marketing site lands on `app.fastforwardexpress.ca/login`.
2. Log in, then use the app normally: address autocomplete, the map, a card payment.
3. Print a bill and scan its QR code; it should open `app.fastforwardexpress.ca/app/bills/...`.
4. Request a password reset; the emailed link should use `app.`.

**Rollback** (back to one host): restore the old `.env` and code, then clear and restart.

```bash
cp ~/env.before-split.bak .env
git checkout master
php artisan config:clear && php artisan route:clear && php artisan view:clear
sudo supervisorctl restart all
```

To undo only A5 and keep the A4 code, remove `MARKETING_URL` and set
`APP_URL=https://fastforwardexpress.ca` instead.

---

## Phase B: the marketing site moves to its own host

Only when there's actually a new marketing site to point at.

### B1. What the marketing site has to provide

- **Permanent (301) redirects** to the same path on `https://app.fastforwardexpress.ca` for:
  `/login`, `/logout`, `/password/*`, `/app` and `/app/*`.
  These cover old bookmarks and password reset emails. They also cover **QR codes on waybills printed
  before the split**, which point to `https://fastforwardexpress.ca/app/bills/<id>`.
- **Forms**: either their own form tool, or POST to the app's public API:

  | Form | Endpoint | Fields |
  |---|---|---|
  | Contact | `https://app.fastforwardexpress.ca/api/public/contact` | `email`, `phone`, `subject`, `message` |
  | Request a quote / account | `https://app.fastforwardexpress.ca/api/public/requestAccount` | `companyName`, `contactName`, `email`, `phone`, `deliveryCount`, `message` |
  | Request delivery | `https://app.fastforwardexpress.ca/api/public/requestDelivery` | `contact-name`, `phone`, `email`, `email_confirmation`, `pickup-address`, `pickup-postal-code`, `pickup-time`, `delivery-address`, `delivery-postal-code`, `delivery-time`, `weight-kg`, `dimensions`, `description` |

  Send `Accept: application/json`. Include a visually hidden, empty `website` field: if it's
  filled in, the request is treated as spam and silently dropped. Responses: `200` success,
  `422` with `errors` per field, `429` when rate limited (5 a minute, 30 a day per IP).

### B2. Allow the marketing site to call the API

In `.env` on the app server:

```
CORS_ALLOWED_ORIGINS=https://fastforwardexpress.ca,https://www.fastforwardexpress.ca
```

```bash
php artisan config:clear       # then config:cache again if you use it
```

### B3. Route 53: point the marketing domain at the new host

In the `fastforwardexpress.ca` hosted zone, edit **only**:
- the `fastforwardexpress.ca` A record → the IP(s) the marketing host gives you;
- the `www` record → what they give you (often a CNAME).

Leave the `app` record on `209.89.8.97`. Leave MX/TXT/NS alone.

The marketing host is then responsible for the bare-domain and `www` certificates. The app server
only needs a certificate for `app.fastforwardexpress.ca`.

### B4. Clean up the app server

- Apache: make `app.fastforwardexpress.ca` the `ServerName` and drop the bare domain and `www`
  from that vhost. Narrow the certificate to `app.` when it's next renewed.
- Code: remove the guest Blade views, the marketing page routes and `GuestController`'s page
  methods. Keep its three form methods, which the public API uses.
