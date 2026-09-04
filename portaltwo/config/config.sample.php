<?php

declare(strict_types=1);

/**
 * Copy this file to config.php (same directory) and fill in real values.
 * config.php is git-ignored — never commit real credentials. It's also
 * blocked from direct web access by config/.htaccess.
 */

return [
    'app' => [
        'title'           => 'XYZ Form Data Portal',
        'per_page'        => 50,
        'max_export_rows' => 20000,
        // Which visual theme to render: 'default' (the original design) or
        // 'custom1' (the redesigned one). Both use the exact same data and
        // logic — this only changes templates/themes/<name>/* and
        // assets/themes/<name>/style.css.
        'theme'           => 'default',
    ],

    // Read-only connection into the WordPress database, used only to read
    // Gravity Forms entries (wp_gf_form, wp_gf_form_meta, wp_gf_entry,
    // wp_gf_entry_meta). Create a dedicated MySQL user with SELECT-only
    // privileges on these tables — see README.md.
    'db' => [
        'host'   => '127.0.0.1',
        'port'   => '3306',
        'name'   => 'wordpress',
        'user'   => 'wp_readonly',
        'pass'   => 'CHANGE_ME',
        'prefix' => 'wp_',
    ],

    // This portal's OWN database — separate from WordPress. Holds user
    // accounts, one-time login codes, and the login audit log. Create a
    // new, empty database for this and run portal_schema.sql against it
    // once — see README.md.
    'portal_db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => '',
        'user' => '',
        'pass' => 'CHANGE_ME',
    ],

    // "From" address for login-code emails, sent via PHP's built-in
    // mail(). Must be a real address on a domain THIS server is
    // authorized to send as (SPF/DKIM) — an address on your main domain
    // is very likely to be rejected as spoofing if that domain's real
    // mail (MX) is hosted elsewhere (e.g. Google Workspace). A subdomain
    // this server itself hosts (e.g. no-reply@dashboard.example.org) is
    // the safe choice — see README.md's mail deliverability notes.
    'mail' => [
        'from'     => '',
        'app_name' => 'XYZ Form Data Portal',
    ],
];
