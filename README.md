# XYZ Form Data Portal

A standalone PHP portal for viewing and downloading Gravity Forms data from
your WordPress site — read directly from the WordPress MySQL database (no
WordPress REST API required). Logging in shows a list of your forms
(latest created first); clicking one shows:

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

3. **Set a portal password.** This portal has no per-user accounts — it's
   a single shared password gating the whole thing, since it can display
   and export personal/financial data. Generate a hash and put it in `.env`:

   ```bash
   php -r "echo password_hash('your-password-here', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   ```
   PORTAL_PASSWORD_HASH=$2y$10$...
   ```

   Leaving this blank disables login — only do that if the portal is
   otherwise access-restricted (e.g. behind a VPN).

4. **Run it, log in, and add your forms from the Manage screen** (`Manage
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

5. **Deploy it.**

   For local testing:

   ```bash
   php -S localhost:8000 -t public
   ```

   For real hosting, you have two options — pick based on what your host
   lets you do:

   - **Point the document root at `public/` (preferred, if your host lets
     you).** Most cPanel-style hosts let you set an addon domain or
     subdomain's document root to any folder — set it to this app's
     `public/` folder. `config/`, `src/`, `templates/`, `data/`, `.env`
     then sit completely outside anything the web server will ever serve,
     which is the most secure setup.

   - **Extract the whole zip directly into the domain/subdomain's root
     folder** (e.g. if your host only gives you a fixed `public_html` and
     no way to change it). The included root `.htaccess` transparently
     routes every request through `public/`, so the app still works from
     the domain's normal URL with no extra configuration. `config/`,
     `src/`, `templates/`, `data/`, `demo/`, and `.env` are additionally
     protected by their own `.htaccess` files (`Require all denied`) as
     defense in depth, in case `.htaccess` rewriting isn't available for
     some reason.

   Both options require Apache (or another server that honors
   `.htaccess`/`mod_rewrite`) with `AllowOverride All` for the directory —
   the default on essentially all shared hosting. If you're on nginx
   instead, the equivalent is pointing `root` at `public/` in your server
   block; nginx doesn't read `.htaccess` at all.

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
  config.php   # loads .env into a config array (db, app, auth settings)
  forms.php    # one-time seed for data/views.json on first run only
data/
  views.json   # the real, editable-from-the-browser forms/views config
src/
  Env.php               # tiny .env parser
  Database.php          # PDO connection
  SchemaDetector.php    # resolves Gravity Forms table names
  FormRepository.php    # reads form/field definitions, classifies filter types
  EntryRepository.php   # entries, filtering/intersection logic, distinct values
  FilterRequest.php     # parses the filter panel's query string
  ColumnSelection.php   # resolves which fields to show as columns
  ConfigStore.php       # reads/writes data/views.json
  XlsxWriter.php        # dependency-free .xlsx writer (uses PHP's zip extension)
  Auth.php              # single shared portal password (session-based)
public/
  index.php    # lists all configured forms, latest first
  view.php     # tabs + filter panel + results table for one form
  export.php   # streams the current filtered set as .xlsx
  manage.php   # admin screen: add/edit/remove forms and their views
  login.php / logout.php
templates/     # plain PHP view templates (all output is HTML-escaped)
demo/          # seeded SQLite sample data + copies of public/ for local preview
```
