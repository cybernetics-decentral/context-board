<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Context Board', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <?php if ($autoRefresh ?? false): ?>
    <meta http-equiv="refresh" content="<?= $refreshSeconds ?? 30 ?>">
    <?php endif; ?>
</head>
<body>
    <nav class="board-nav">
        <a href="/">Home</a>
        <?= $breadcrumbs ?? '' ?>
    </nav>
    <main class="board-content">
        <?= $content ?>
    </main>
    <footer>
        <p>Context Board — No JavaScript Required</p>
    </footer>
</body>
</html>
