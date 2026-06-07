<?php $layout = 'layout'; ?>

<h1>Admin Login</h1>

<?php if (!empty($error)): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="POST" action="/admin/login">
    <?= Security::getCsrfTokenField() ?>

    <p>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username">
    </p>

    <p>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
    </p>

    <p>
        <button type="submit">Login</button>
    </p>
</form>

<p><a href="/">&laquo; Back to Home</a></p>
