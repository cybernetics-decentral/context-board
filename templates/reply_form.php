<?php $layout = 'layout'; ?>

<h1>Post a Reply</h1>

<form method="POST" action="/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>/reply">
    <?= Security::getCsrfTokenField() ?>

    <?php if (!empty($parentId)): ?>
        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($parentId, ENT_QUOTES, 'UTF-8') ?>">
        <p><em>Replying to post #<?= htmlspecialchars($parentId, ENT_QUOTES, 'UTF-8') ?></em></p>
    <?php else: ?>
        <input type="hidden" name="parent_id" value="">
    <?php endif; ?>

    <p>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required maxlength="10000"></textarea>
    </p>

    <p>
        <button type="submit">Post Reply</button>
    </p>
</form>

<p>
    <a href="/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>">
        &laquo; Back to thread
    </a>
</p>
