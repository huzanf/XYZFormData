<?php

declare(strict_types=1);

// The demo deliberately has no portal_db (see demo/bootstrap.php), so
// hide/mark-paid can't actually persist anything here — this just avoids
// a 404 when clicking those buttons in the local preview, and sends you
// back to where you were.

$redirect = (string) ($_POST['redirect'] ?? 'view.php?form=' . (int) ($_POST['form_id'] ?? 0));
header('Location: ' . $redirect);
