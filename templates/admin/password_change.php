<?php $layout = 'layout'; ?>

<h1>Change Password</h1>

<p>
    <a href="/admin">Dashboard</a> |
    <a href="/admin/logout">Logout</a>
</p>

<form method="POST" action="/admin/password">
    <?= Security::getCsrfTokenField() ?>

    <p>
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    </p>

    <p>
        <label for="new_password">New Password (min 8 characters):</label>
        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
    </p>

    <p>
        <label for="new_password_confirm">Confirm New Password:</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8" autocomplete="new-password">
    </p>

    <p>
        <button type="submit">Change Password</button>
    </p>
</form>
