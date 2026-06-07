<?php $layout = 'layout'; ?>

<h1>Admin Dashboard</h1>

<p>
    <a href="/admin/boards">Manage Boards</a> |
    <a href="/admin/password">Change Password</a> |
    <a href="/admin/logout">Logout</a> |
    <a href="/">View Site</a>
</p>

<?php if (empty($stats)): ?>
    <p>No boards have been created yet. <a href="/admin/boards">Create one now.</a></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Board</th>
                <th>Threads</th>
                <th>Replies</th>
                <th>Last Activity</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats as $row): ?>
            <tr>
                <td>
                    <a href="/admin/boards/<?= urlencode($row['board']['board_id'] ?? '') ?>">
                        <?= htmlspecialchars($row['board']['name'] ?? $row['board']['board_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><?= (int)($row['thread_count'] ?? 0) ?></td>
                <td><?= (int)($row['reply_count'] ?? 0) ?></td>
                <td><?= $row['last_activity'] ? htmlspecialchars(Helpers::relativeTime($row['last_activity']), ENT_QUOTES, 'UTF-8') : 'Never' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
