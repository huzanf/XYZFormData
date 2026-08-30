# XYZ Form Data Portal

A standalone PHP portal for viewing and downloading Gravity Forms data from
your WordPress site — read directly from the WordPress MySQL database (no
WordPress REST API required). Sign-in is per-person: each user gets an
email + one-time-code login (no password to manage), with an `admin` /
`viewer` role, added and removed from a **Manage Users** screen inside the
portal. Logging in shows a list of your forms (latest created first);
clicking one shows:

- **The full data set** for that form by default, every field as a column.
- **Named views** you define yourself from a **Manage** screen inside the
  portal — each optionally grouped by a field (e.g. "By Membership Type",
  "By Group", "By Event Type": pick a value, see just those entries) and
  with its own chosen set of columns, in the spirit of the column-mapping
  screen in Gravity Forms' Google Sheets add-on, just for this portal.
  No code editing needed — it's all point-and-click.
- **An interactive filter panel** on every view — combine several fields at
  once (e.g. Category + Amount range + Date range) to narrow down further,
  and a column picker to change what's shown on the fly.
- **Export to Excel** — downloads the currently filtered, currently
  selected-columns result set as a real `.xlsx` file, matching what's on
  screen.

Every form can have a completely different set of fields; the portal reads
each form's field definitions from Gravity Forms and builds the table,
filter controls, and column choices from that automatically.

It only ever runs `SELECT` queries — it never writes to your WordPress
database.

## Try it with sample data (no WordPress DB needed)

```bash
php -S localhost:8000 -t demo/public
```

Then open http://localhost:8000. This runs the real app code against a
seeded local SQLite database (`demo/demo.sqlite`, built automatically on
first run) with realistic sample data for a Yearly Membership form and an
Event Registration form — useful for trying out views, filters, the Manage
screen, and export before wiring up your real database.

## Requirements

- PHP 8.0+ with the `pdo_mysql` and `zip` extensions (both are on almost
  any standard PHP install/shared host).
- Network access from wherever this app runs to your WordPress MySQL server.
- Gravity Forms 2.3+ (uses the `gf_form` / `gf_form_meta` / `gf_entry` /
  `gf_entry_meta` tables). Older installs using the legacy `rg_lead` schema
  are not supported.

## Setup

1. **Create a read-only MySQL user** (recommended, so this app can never
   modify your WordPress data):

   ```sql
   CREATE USER 'wp_readonly'@'%' IDENTIFIED BY 'choose-a-strong-password';
   GRANT SELECT ON your_wordpress_db.* TO 'wp_readonly'@'%';
   FLUSH PRIVILEGES;
   ```

   Restrict the host (`'%'`) to the actual IP this app will connect from if
   possible.

2. **Copy the env file and fill in real values:**

   ```bash
   cp .env.example .env
   ```

   ```
   DB_HOST=your-db-host
   DB_PORT=3306
   DB_NAME=your_wordpress_db
   DB_USER=wp_readonly
   DB_PASS=choose-a-strong-password
   DB_TABLE_PREFIX=wp_        # match your WordPress table prefix
   ```

3. **Create the portal's own database** — separate from WordPress, used
   only for user accounts, one-time login codes, and the login audit log.
   In cPanel's "MySQL® Databases" tool: create a new empty database and a
   new user with full privileges on it. Then run `portal_schema.sql`
   against that database once (e.g. via phpMyAdmin's Import tab) to create
   its tables.

   Add its credentials to `.env`:

   ```
   PORTAL_DB_HOST=127.0.0.1
   PORTAL_DB_PORT=3306
   PORTAL_DB_NAME=your_portal_db
   PORTAL_DB_USER=your_portal_db_user
   PORTAL_DB_PASS=choose-a-strong-password
   ```

   And set `MAIL_FROM` to a real address on *your* domain (e.g.
   `no-reply@xyzfoundation.net`) — login codes are emailed via PHP's
   built-in `mail()`, and an address on an unrelated domain is very likely
   to be marked as spam or rejected outright, since that's what SPF/DKIM
   checks look at.

4. **Add your first admin user directly in the database** (the Manage
   Users screen needs an existing admin to sign in and use it):

   ```sql
   INSERT INTO users (name, email, role) VALUES ('Your Name', 'you@example.org', 'admin');
   ```

   From then on, add/remove/deactivate everyone else from **Manage Users**
   inside the portal.

5. **Run it, log in, and add your forms from the Manage screen** (`Manage
   forms & views` link on the home page) — give each a Gravity Forms form
   ID and a label. That's the only thing you strictly need before you have
   a working Full Data view; named views (grouped browsing, chosen columns)
   are added the same way, per form, from **Manage views**.

   `config/forms.php` still exists as a code-based way to define the same
   thing, but it's now only read once, the very first time the portal
   runs, to seed its real (editable-from-the-browser) config store at
   `data/views.json`. After that, `data/views.json` is authoritative and
   `config/forms.php` is no longer read — everything is managed from the
   Manage screen from then on.

6. **Deploy it.**

   For local testing:

   ```bash
   php -S localhost:8000 -t .
   ```

   For real hosting: **extract everything directly into the
   domain/subdomain's document root** (e.g. `public_html/dashboard/` for a
   subdomain) — `index.php`, `view.php`, etc. sit right at the top level
   alongside `config/`, `src/`, `templates/`, `data/`, and `.env`. No
   document-root change, no `.htaccess` rewrite trick, nothing else to
   configure — visiting the domain just works.

   `config/`, `src/`, `templates/`, `data/`, and `.env` are protected from
   direct web access by their own `.htaccess` files (`Require all
   denied`), so even though they're sitting right next to the public PHP
   files, none of them — including your DB password in `.env` — can be
   fetched directly by URL. This needs Apache (or another server that
   honors `.htaccess`) with `AllowOverride All` (or at least `AuthConfig`)
   for the directory, which is the default on essentially all shared
   hosting. If you're on nginx instead, `.htaccess` isn't read at all —
   you'd need equivalent `location` blocks denying those folders in your
   server config.

## Managing forms and views

Everything is done from inside the portal, no code editing needed:

- **Manage Forms** (`manage.php`) — add a form by its Gravity Forms form ID
  and a label of your choice; edit or remove it later. A form that's
  configured but whose ID isn't found in the database shows a warning on
  the home page, so a typo is easy to spot.
- **Manage Views** (`manage.php?form=ID`, or the "Manage views" link next
  to a form's tabs) — for that form, add/edit/delete named views:
  - **Label** — what shows on the tab.
  - **Group by** (optional) — pick a field and the view becomes a
    browsable index (distinct values with counts); click a value to see
    just those entries. Leave it unset for a view that's just a saved
    column layout on the full list.
  - **Columns** — check the fields you want shown for this view; leave
    none checked to show every field. This is the "column mapping" piece,
    matching the idea from Gravity Forms' Google Sheets add-on.

On any view's page, the filter panel's own column picker can further
override the saved columns for that visit only (e.g. to peek at one extra
field) without changing the saved view.

## Managing users

**Manage Users** (`users.php`, admin only) — add a person by name, email,
and role (`admin` or `viewer`); deactivate them later (their account stays
on record, just can't sign in — you can reactivate it any time). There are
no passwords: signing in is always email + a fresh 6-digit code, valid for
10 minutes.

A few things worth knowing:

- **One active session per person.** Signing in anywhere immediately signs
  that person out everywhere else — there's no password to also protect a
  stolen session, so this is the main defense if a browser session leaks.
- **Auto sign-out after 30 minutes idle**, given this can show
  personal/financial data.
- Every sign-in, sign-out, and failed code attempt is written to
  `audit_log` in the portal database, for later review if needed.
- `admin` users get Manage Forms/Views/Users; `viewer` users get everything
  else (view and export data) but not those.

## How filtering works

Each field gets a filter control based on its Gravity Forms type:

| Field type | Filter control | Match |
|---|---|---|
| select, radio | Dropdown | exact value |
| checkbox | Multi-select checkboxes | entry has any of the checked values |
| number | Min / max | numeric range |
| everything else (text, name, address, email, phone, etc.) | Text box | "contains" |

There's also always a **Date from / Date to** filter based on the entry's
actual submission date, regardless of the form's fields.

Dropdown and checkbox options are populated from values that actually exist
in your data (with counts), not just the form's configured choices, so
they always match what's filterable.

All active filters are combined with AND. The "Export to Excel" button
downloads exactly the filtered set currently shown (not just the current
page) as a `.xlsx` file — capped at `APP_MAX_EXPORT_ROWS` (default 20,000)
as a safety limit.

## How it's structured

```
config/
  config.php   # loads .env into a config array (wp db, portal db, mail, app settings)
  forms.php    # one-time seed for data/views.json on first run only
data/
  views.json   # the real, editable-from-the-browser forms/views config
src/
  Env.php               # tiny .env parser
  Database.php          # PDO connections, cached per named config (wp / portal)
  SchemaDetector.php    # resolves Gravity Forms table names
  FormRepository.php    # reads form/field definitions, classifies filter types
  EntryRepository.php   # entries, filtering/intersection logic, distinct values
  FilterRequest.php     # parses the filter panel's query string
  ColumnSelection.php   # resolves which fields to show as columns
  ConfigStore.php       # reads/writes data/views.json
  XlsxWriter.php        # dependency-free .xlsx writer (uses PHP's zip extension)
  Auth.php              # multi-user email + one-time-code login, roles, audit log
  Mailer.php            # sends login-code emails via PHP's built-in mail()
templates/     # plain PHP view templates (all output is HTML-escaped)
assets/        # style.css
portal_schema.sql  # run once against the portal's own (new, empty) database
index.php      # lists all configured forms, latest first
view.php       # tabs + filter panel + results table for one form
export.php     # streams the current filtered set as .xlsx
manage.php     # admin screen: add/edit/remove forms and their views
users.php      # admin screen: add/edit/deactivate portal users
login.php / logout.php
demo/          # seeded SQLite sample data + a self-contained copy of the
               # app (its own index.php/view.php/etc under demo/public/)
               # for local preview via `php -S localhost:8000 -t demo/public`
               # — login is simulated (always signed in as a demo admin),
               # since the demo deliberately skips setting up a portal DB
```
