<?php

declare(strict_types=1);

// The demo's session is a simulated always-logged-in admin (see
// ../bootstrap.php) — there's nothing real to log out of, this just
// clears the session so the header stops asking for one and reloads.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION = [];
session_destroy();

header('Location: index.php');
