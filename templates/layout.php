<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? $config['app']['title']) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><?= htmlspecialchars($config['app']['title']) ?></a>
</header>
<main class="container">
<?= $content ?>
</main>
</body>
</html>
