# XYZ Form Data Dashboard

A small standalone PHP app that reads Gravity Forms entries directly from
your WordPress MySQL database (no WordPress REST API required) and shows
each form as several views: the full entry list, plus one or more grouped
views (e.g. by group name, by event, by category).

It only ever runs `SELECT` queries — it does not write to the database.

## Try it with sample data (no WordPress DB needed)

```bash
php -S localhost:8000 -t demo/public
```

Then open http://localhost:8000. This runs the real app code against a
seeded local SQLite database (`demo/demo.sqlite`, built automatically on
first run) with realistic sample data for an event registration form and a
main accounting form — useful for previewing the views before wiring up
your real database. See `demo/forms.php` for how its views are configured.

## Requirements

- PHP 8.0+ with the `pdo_mysql` extension.
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

2. **Copy the env file and fill in real credentials:**

   ```bash
   cp .env.example .env
   ```

   Edit `.env`:

   ```
   DB_HOST=your-db-host
   DB_PORT=3306
   DB_NAME=your_wordpress_db
   DB_USER=wp_readonly
   DB_PASS=choose-a-strong-password
   DB_TABLE_PREFIX=wp_        # match your WordPress table prefix
   ```

3. **Configure your forms and views** in `config/forms.php`. Two example
   forms are already stubbed in (an event form and a main form) — replace
   the form IDs and field IDs with your real ones:

   - **Form ID**: the numeric ID Gravity Forms assigns a form (visible in
     the WP admin URL when editing the form, or the `id` column in
     `wp_gf_form`).
   - **Field ID**: the numeric ID of the field you want to group by (e.g.
     "Group Name", "Events Participated", "Category"). Visible in the
     Gravity Forms form editor when you click/hover a field, or in the
     `fields` array inside `wp_gf_form_meta.display_meta` (JSON) for that
     form.

   Each form can have any number of views:

   ```php
   'group_by' => null   // full data view — every entry, every field as a column
   'group_by' => 5      // grouped view — index of distinct values for field 5,
                         // click a value to see the filtered, full-column table
   ```

   Checkbox and multi-select fields (like "Events Participated") work
   automatically: an entry with multiple selections shows up under each of
   its group values, no extra config needed.

   Columns are always derived from the form's actual fields, so if you add
   or remove a field in Gravity Forms, the dashboard picks it up
   automatically the next time you load a page.

4. **Run it.**

   For local testing:

   ```bash
   php -S localhost:8000 -t public
   ```

   Then open http://localhost:8000.

   For real hosting, point your web server's document root at the
   `public/` directory (everything else — `config/`, `src/`, `.env` — should
   stay outside the web root or otherwise be blocked from direct access).

## How it's structured

```
config/
  config.php   # loads .env into a config array (db credentials, app settings)
  forms.php    # which forms + views to show, and which field IDs to group by
src/
  Env.php               # tiny .env parser
  Database.php          # PDO connection
  SchemaDetector.php    # resolves Gravity Forms table names
  FormRepository.php    # reads form/field definitions
  EntryRepository.php   # reads entries, entry values, and group counts
public/
  index.php    # lists all configured forms and their views
  view.php     # renders a view: full data, group index, or filtered-by-group
templates/     # plain PHP view templates (all output is HTML-escaped)
```

## Adding another form

Add a new top-level entry to `config/forms.php` with the form's ID, a
label, and a `views` array — no code changes needed.
