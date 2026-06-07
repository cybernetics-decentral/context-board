<?php $layout = 'layout'; ?>

<h1>Initial Setup</h1>

<p>Create the administrator account. This page will be disabled after setup.</p>

<form method="POST" action="/setup">
    <?= Security::getCsrfTokenField() ?>

    <p>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
    </p>

    <p>
        <label for="password">Password (min 8 characters):</label>
        <input type="password" id="password" name="password" required minlength="8">
    </p>

    <p>
        <label for="password_confirm">Confirm Password:</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
    </p>

    <p>
        <button type="submit">Create Admin Account</button>
    </p>
</form>
