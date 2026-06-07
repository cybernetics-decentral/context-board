<?php $layout = 'layout'; ?>

<h1><?= htmlspecialchars($board['name'] ?? 'Board', ENT_QUOTES, 'UTF-8') ?> — New Thread</h1>

<form method="POST" action="/boards/<?= urlencode($board_id) ?>/new">
    <?= Security::getCsrfTokenField() ?>

    <p>
        <label for="subject">Subject (optional):</label>
        <input type="text" id="subject" name="subject" maxlength="200"
               value="<?= htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </p>

    <p>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required maxlength="10000"><?=
            htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8')
        ?></textarea>
    </p>

    <p>
        <button type="submit">Create Thread</button>
    </p>
</form>

<p><a href="/boards/<?= urlencode($board_id) ?>">&laquo; Back to board</a></p>
