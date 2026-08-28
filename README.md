# XYZ Form Data Portal

A standalone PHP portal for viewing and downloading Gravity Forms data from
your WordPress site — read directly from the WordPress MySQL database (no
WordPress REST API required). For each form you configure, it shows:

- **The full data set** for that form, every field as a column.
- **An interactive filter panel** above the table — combine several fields
  at once (e.g. Category + Amount range + Date range, or Group Name +
  Events Participated) to narrow down to exactly the rows you want.
- **Export to Excel** — downloads the currently filtered result set as a
  real `.xlsx` file, matching what's on screen.

Every form can have a completely different set of fields; the portal reads
each form's field definitions from Gravity Forms and builds the table,
filter controls, and export columns from that automatically — no per-form
code changes needed, just add the form ID to `config/forms.php`.

It only ever runs `SELECT` queries — it never writes to your WordPress
database.

## Try it with sample data (no WordPress DB needed)

```bash
php -S localhost:8000 -t demo/public
```

Then open http://localhost:8000. This runs the real app code against a
seeded local SQLite database (`demo/demo.sqlite`, built automatically on
first run) with realistic sample data for a registration form and an event
form — useful for trying out the filters and export before wiring up your
real database.

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

4. **List your forms** in `config/forms.php`:

   ```php
   return [
       1 => ['label' => 'Main Registration Form'],
       2 => ['label' => 'Event Registration Form'],
   ];
   ```

   The **Form ID** is the numeric ID Gravity Forms assigns a form (visible
   in the WP admin URL when editing the form, or the `id` column in
   `wp_gf_form`). That's the only per-form configuration needed — columns
   and filters are derived automatically from each form's actual fields.

5. **Run it.**

   For local testing:

   ```bash
   php -S localhost:8000 -t public
   ```

   For real hosting, point your web server's document root at the
   `public/` directory (`config/`, `src/`, `.env` should stay outside the
   web root or otherwise be blocked from direct access).

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
  forms.php    # which Gravity Forms form IDs to expose, and their labels
src/
  Env.php               # tiny .env parser
  Database.php          # PDO connection
  SchemaDetector.php    # resolves Gravity Forms table names
  FormRepository.php    # reads form/field definitions, classifies filter types
  EntryRepository.php   # entries, filtering/intersection logic, distinct values
  FilterRequest.php     # parses the filter panel's query string
  XlsxWriter.php        # dependency-free .xlsx writer (uses PHP's zip extension)
  Auth.php              # single shared portal password (session-based)
public/
  index.php    # lists all configured forms
  view.php     # filter panel + results table for one form
  export.php   # streams the current filtered set as .xlsx
  login.php / logout.php
templates/     # plain PHP view templates (all output is HTML-escaped)
demo/          # seeded SQLite sample data + copies of public/ for local preview
```

## Adding another form

Add a new entry to `config/forms.php` with the form's ID and a label —
no other code changes needed.
