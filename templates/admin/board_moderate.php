<?php $layout = 'layout'; ?>

<h1>Moderate: <?= htmlspecialchars($boardId ?? '', ENT_QUOTES, 'UTF-8') ?></h1>

<p>
    <a href="/admin/boards">Manage Boards</a> |
    <a href="/admin">Dashboard</a> |
    <a href="/admin/logout">Logout</a>
</p>

<?php if (empty($threads)): ?>
    <p>No threads in this board.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>Subject</th><th>Replies</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($threads as $thread): ?>
            <tr>
                <td>
                    <a href="/admin/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($thread['thread_id']) ?>">
                        <?= htmlspecialchars($thread['subject'] ?? 'No Subject', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><?= (int)($thread['reply_count'] ?? 0) ?></td>
                <td><?= htmlspecialchars(Helpers::relativeTime($thread['created_at'] ?? time()), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <form method="POST" action="/admin/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($thread['thread_id']) ?>/delete">
                        <?= Security::getCsrfTokenField() ?>
                        <label>
                            <input type="checkbox" name="confirm" value="1"> Confirm
                        </label>
                        <button type="submit" class="danger">Delete Thread</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
